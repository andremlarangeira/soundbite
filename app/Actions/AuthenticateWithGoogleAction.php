<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class AuthenticateWithGoogleAction
{
    /**
     * Execute the action.
     */
    public function handle(SocialiteUser $googleUser): User
    {
        return DB::transaction(function () use ($googleUser): User {
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (! $user) {
                $user = new User();
                $user->email = $googleUser->getEmail();
                $user->name = $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User';
                $user->password = Hash::make(Str::random(32));
                // If using MustVerifyEmail, we can mark it as verified since Google already verified it
                $user->email_verified_at = now();
            }

            $user->google_id = $googleUser->getId();
            $user->google_token = $googleUser->token;
            $user->google_refresh_token = $googleUser->refreshToken;

            $user->save();

            Auth::login($user);

            return $user;
        });
    }
}
