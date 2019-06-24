<?php

namespace Signifly\Translator\Http\Middleware;

use Closure;
use Signifly\Translator\Facades\Translator;

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
        $param = Translator::languageParameter();
        $defaultLang = Translator::defaultLanguageCode();

        Translator::activateLanguage($request->input($param, $defaultLang));

        return $next($request);
    }
}
