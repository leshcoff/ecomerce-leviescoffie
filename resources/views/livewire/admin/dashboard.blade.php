
<div>
    <h1 class="mb-6 text-2xl font-bold">Hola, {{ auth()->user()->name }}</h1>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-gray-500">Productos</p>
            <p class="text-3xl font-bold">{{ $productos }}</p>
        </div>
        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-gray-500">Pedidos</p>
            <p class="text-3xl font-bold">{{ $pedidos }}</p>
        </div>
    </div>
</div>
