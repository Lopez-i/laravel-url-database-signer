<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Library\UrlSigner;
use Auth;
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
        if (Auth::check() || !Session::get('id'))
            abort(404);
        else
        {
            if (!($urlIsSigned = UrlSigner::validateUrl($request, Session::get('id'))))
            {
                UrlSigner::invalidate(Session::get('id'), $request);
                return redirect(route('connection-session-expired'));
            }
        }
        return $next($request);
    }
}
