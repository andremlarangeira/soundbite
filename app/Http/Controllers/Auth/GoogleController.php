<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\AuthenticateWithGoogleAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

final class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(AuthenticateWithGoogleAction $authenticateWithGoogle): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $authenticateWithGoogle->handle($googleUser);

        return redirect()->route('dashboard'); // Or home, or whatever intended page
    }
}
