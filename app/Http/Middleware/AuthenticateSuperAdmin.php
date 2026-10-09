<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSuperAdmin
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function handle(Request $request, \Closure $next): Response
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            return redirect()->guest(Filament::getLoginUrl());
        }

        $user = $guard->user();

        abort_unless(
            $user instanceof FilamentUser && $user->canAccessPanel(Filament::getCurrentPanel()),
            403,
        );

        $this->auth->shouldUse(Filament::getAuthGuard());

        return $next($request);
    }
}
