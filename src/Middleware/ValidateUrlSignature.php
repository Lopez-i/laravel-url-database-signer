<?php

namespace lopez_i\Middleware;

use lopez_i\UrlSigner;
use Closure;
use Session;

class ValidateUrlSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!($urlIsSigned = UrlSigner::validateUrl($request, Session::get('id'))))
            {
                UrlSigner::invalidate(Session::get('id'), $request);
                if (config('redirect') == '')
                    abort(404);
                else
                    return redirect('/' . config('redirect'));
            }
        return $next($request);
    }
}
