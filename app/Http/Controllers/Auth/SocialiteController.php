<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    /** Envía al cliente a la pantalla de Google */
    public function redirect(string $provider): RedirectResponse
    {
        // Recordar desde qué página de la tienda hizo clic, para regresarlo ahí después
        $anterior = url()->previous();

        if (! session()->has('url.intended')
            && str_starts_with($anterior, url('/'))
            && ! str_contains($anterior, '/auth/')) {
            session()->put('url.intended', $anterior);
        }

        return Socialite::driver($provider)->redirect();
    }

    /** Google regresa aquí con los datos del usuario */
    public function callback(string $provider): RedirectResponse
    {
        // 1) Pedir a Google los datos del usuario
        try {
            $social = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            report($e);

            $mensaje = 'No se pudo iniciar sesión con '.ucfirst($provider).'.';
            if (app()->isLocal()) {
                $mensaje .= ' ['.class_basename($e).': '.$e->getMessage().']';
            }

            return redirect()->route('home')->with('error', $mensaje);
        }

        // 2) Sin correo no podemos identificar ni contactar al cliente
        if (blank($social->getEmail())) {
            return redirect()->route('home')->with('error', 'Tu cuenta de '.ucfirst($provider).' no compartió un correo electrónico.');
        }

        // 3) Buscar al usuario: primero por el id de Google, luego por correo
        $usuario = User::where("{$provider}_id", $social->getId())->first()
            ?? User::where('email', $social->getEmail())->first();

        // 4) Si no existe, CREAR la cuenta como cliente
        if ($usuario === null) {
            $usuario = User::create([
                'name'  => $social->getName() ?: Str::before($social->getEmail(), '@'),
                'email' => $social->getEmail(),
                // Sin password: este cliente solo entra con Google.
                // is_admin no se envía (no es "fillable"): la BD lo deja en false.
            ]);
        }

        // 5) Guardar o actualizar el vínculo con Google y la foto
        $usuario->update([
            "{$provider}_id" => $social->getId(),
            'avatar'         => $social->getAvatar(),
        ]);

        // 6) Iniciar sesión
        Auth::login($usuario, remember: true);
        request()->session()->regenerate();

        // 7) ¿A dónde lo mandamos?
        if ($usuario->is_admin === true) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('home'));   // la página desde la que hizo clic, o el catálogo
    }
}
