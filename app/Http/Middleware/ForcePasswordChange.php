<?php
// app/Http/Middleware/ForcePasswordChange.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check() &&
            auth()->user()->password_change_required &&
            !$request->routeIs('password.change', 'password.change.update', 'logout')
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
