<?php

namespace Signifly\Translator\Http\Middleware;

use Closure;
use Signifly\Translator\Facades\Translator;

class AutoTranslate
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
        Translator::enableAutoTranslation();

        return $next($request);
    }
}
