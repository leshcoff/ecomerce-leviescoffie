import Chart from 'chart.js/auto'

//
// Moneda elegida por el visitante; $persist la guarda en localStorage
document.addEventListener('alpine:init', () => {
    Alpine.store('moneda', {
        actual: Alpine.$persist('MXN').as('moneda'),
        cambiar(m) {
            this.actual = m
            console.log('----------moneda ----------');
            console.log(m);

        },
    })

    // Formulario de tarjeta de Stripe (Payment Element) para el driver "stripe_card"
    Alpine.data('pagoTarjeta', (clientSecret, stripeKey, returnUrl) => ({
        stripe: null,
        elements: null,
        listo: false,        // el formulario de Stripe terminó de cargar
        procesando: false,   // se está confirmando el pago
        error: '',

        // init() se ejecuta solo cuando Alpine crea el componente
        init() {
            this.stripe = Stripe(stripeKey)   // llave PÚBLICA (pk_test_)
            this.elements = this.stripe.elements({
                clientSecret,
                locale: 'es',
                appearance: {
                    theme: 'stripe',
                    variables: { colorPrimary: '#682f8b', borderRadius: '8px' },   // tu color primario
                },
            })

            const tarjeta = this.elements.create('payment')
            tarjeta.mount(this.$refs.tarjeta)        // dibuja el iframe dentro de x-ref="tarjeta"
            tarjeta.on('ready', () => (this.listo = true))
        },

        async pagar() {
            this.procesando = true
            this.error = ''

            // El navegador envía la tarjeta DIRECTO a Stripe (nunca pasa por Laravel).
            // Si el banco pide 3D Secure, Stripe muestra su ventana aquí mismo.
            const { error, paymentIntent } = await this.stripe.confirmPayment({
                elements: this.elements,
                redirect: 'if_required',               // solo redirige si el método de pago lo exige
                confirmParams: { return_url: returnUrl },
            })

            if (error) {
                this.error = error.message             // tarjeta rechazada, datos incompletos, etc.
                this.procesando = false
                return
            }

            if (paymentIntent.status === 'succeeded') {
                // Avisamos a Livewire; el SERVIDOR verifica con Stripe antes de confirmar el pedido
                await this.$wire.confirmarTarjeta(paymentIntent.id)
            } else {
                this.error = `El pago quedó en estado "${paymentIntent.status}".`
            }

            this.procesando = false
        },
    }))


    // Gráfica de ventas por periodo (Chart.js) para /admin/reports
    Alpine.data('graficaVentas', (datos) => {
        // ⚠️ La instancia de Chart va FUERA del objeto que devuelve Alpine.
        // Si Alpine la vuelve "reactiva" (la envuelve en un Proxy), Chart.js entra en un ciclo infinito.
        let grafica = null

        const mxn = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' })
        const compacto = new Intl.NumberFormat('es-MX', { notation: 'compact', maximumFractionDigits: 1 })

        return {
            init() {
                grafica = new Chart(this.$refs.lienzo, {
                    type: 'bar',
                    data: {
                        labels: datos.etiquetas,
                        datasets: [{
                            label: 'Ventas sin IVA',
                            data: datos.valores,
                            backgroundColor: '#874dae',        // primary-500
                            hoverBackgroundColor: '#682f8b',   // primary-600 al pasar el mouse
                            maxBarThickness: 24,               // barras delgadas aunque haya pocos periodos
                            borderRadius: { topLeft: 4, topRight: 4 },   // redondeo solo arriba
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,            // usa la altura del contenedor (h-72)
                        plugins: {
                            legend: { display: false },        // una sola serie: el título la nombra
                            tooltip: { callbacks: { label: (c) => mxn.format(c.parsed.y) } },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#6b7280', maxRotation: 0, autoSkip: true } },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: '#f3f4f6' },
                                ticks: { color: '#6b7280', callback: (v) => '$' + compacto.format(v) },   // $10 mil
                            },
                        },
                    },
                })
            },

            // Livewire manda los datos nuevos cuando cambian los filtros
            actualizar(d) {
                grafica.data.labels = d.etiquetas
                grafica.data.datasets[0].data = d.valores
                grafica.update()   // anima las barras en lugar de redibujar todo
            },

            // Alpine lo llama al quitar el elemento del DOM (por ejemplo, con wire:navigate)
            destroy() {
                grafica?.destroy()
            },
        }
    })


})

