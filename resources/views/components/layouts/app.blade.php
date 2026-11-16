<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">
@auth
    <nav class="bg-white shadow">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">

            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="font-bold text-primary-700">Admin · {{ config('app.name') }}</a>

                @foreach ([
                    'admin.products.index' => ['Productos', 'admin.products.*'],
                    'admin.reports.index'  => ['Reportes', 'admin.reports.*'],
                ] as $ruta => [$texto, $patron])
                    <a href="{{ route($ruta) }}" wire:navigate
                        @class(['text-sm hover:text-primary-600', 'font-semibold text-primary-600' => request()->routeIs($patron)])>
                        {{ $texto }}
                    </a>
                @endforeach
            </div>


            {{-- Avatar --}}
            <div class="flex items-center gap-3">
                <x-avatar :user="auth()->user()" />

                <span class="text-sm">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-red-600 hover:underline cursor-pointer">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>
@endauth

<main class="mx-auto max-w-6xl px-4 py-8">
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
