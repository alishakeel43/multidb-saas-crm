<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Locale
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(
            core()->getConfigData('general.general.locale_settings.locale')
                ?: app()->getLocale()
        );

        return $next($request);
    }
}
