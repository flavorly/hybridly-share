<?php

namespace Flavorly\HybridlyShare;

use Closure;
use Flavorly\HybridlyShare\Exceptions\DriverNotSupportedException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HybridlyContainerShareMiddleware
{
    /**
     * Should share after hybridly container is built & already shared
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     * @throws DriverNotSupportedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        app(HybridlyShare::class)->sync();

        return $next($request);
    }
}
