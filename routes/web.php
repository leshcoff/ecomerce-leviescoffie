<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Auth\Login;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Productos\Index as ProductosIndex;
use App\Livewire\Shop\Catalogo;
use App\Livewire\Shop\Detalle;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\MisPedidos;
use App\Livewire\Admin\Reportes\Index as ReportesIndex;



// Tienda pública
Route::get('/', Catalogo::class)->name('home');
Route::get('/products/{producto:sku}', Detalle::class)->name('products.show');
Route::get('/checkout', Checkout::class)->name('checkout');
Route::get('/account/orders', MisPedidos::class)->middleware('auth')->name('account.orders');





// Solo para visitantes sin sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');

    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.callback');
});

Route::post('/logout', function (Request $request) {
    $eraAdmin = (bool) $request->user()?->is_admin;   // se lee ANTES de cerrar sesión

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route($eraAdmin ? 'login' : 'home');
})->middleware('auth')->name('logout');

// Administración: solo usuarios autenticados
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/products', ProductosIndex::class)->name('products.index');
    Route::get('/reports', ReportesIndex::class)->name('reports.index');
});
