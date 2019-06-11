<?php

namespace Signifly\Translator\Http\Middleware;

use Closure;

class ActivateLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $param = config('translator.language_parameter');
        $defaultLang = config('translator.default_language_code');

        config(['translator.active_language_code' => $request->input($param, $defaultLang)]);

        return $next($request);
    }
}
