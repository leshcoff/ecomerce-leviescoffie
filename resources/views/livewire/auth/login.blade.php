
<div class="mx-auto mt-16 max-w-sm rounded-xl bg-white p-8 shadow">
    <h1 class="mb-6 text-center text-2xl font-bold">Administración</h1>

    @if (session('error'))
        <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Correo</label>
            <input type="email" wire:model="email" autofocus autocomplete="username"
                   class="w-full rounded border-gray-300 px-3 py-2 ring-1 ring-gray-300 focus:ring-indigo-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Alpine: mostrar/ocultar contraseña sin ir al servidor --}}
        <div x-data="{ show: false }">
            <label class="mb-1 block text-sm font-medium">Contraseña</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" wire:model="password" autocomplete="current-password"
                       class="w-full rounded px-3 py-2 pr-16 ring-1 ring-gray-300 focus:ring-indigo-500">
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-2 text-xs text-gray-500"
                        x-text="show ? 'Ocultar' : 'Mostrar'"></button>
            </div>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember"> Recordarme
        </label>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded bg-indigo-600 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login">Verificando…</span>
        </button>
    </form>


</div>
