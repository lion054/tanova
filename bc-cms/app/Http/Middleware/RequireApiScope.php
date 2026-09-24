<?php

namespace App\Http\Middleware;

use App\Support\ApiScopes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `api.scope:bookings:write` on a route: the key must be allowed that scope.
 * Runs after ResolveVendorApiKey, which puts the key on the request.
 */
class RequireApiScope
{
    public function handle(Request $request, Closure $next, string ...$scope): Response
    {
        // `api.scope:area,bookings` means: bookings:read for a read, bookings:write for anything else.
        $needed = ($scope[0] ?? '') === 'area'
            ? ($scope[1] . (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) ? ':read' : ':write'))
            : implode(':', $scope);
        $key = $request->attributes->get('resolved_api_key');

        if ($key && !ApiScopes::allows(is_array($key->scopes) ? $key->scopes : null, $needed)) {
            return response()->json(['error' => [
                'code'    => 'insufficient_scope',
                'message' => "This key is not allowed to use \"{$needed}\". Give it that scope under Settings > API keys.",
                'scope'   => $needed,
            ]], 403);
        }

        return $next($request);
    }
}
