<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class InitializeTenancyByRequestDataCustom extends InitializeTenancyByRequestData
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
        // Get tenant identifier from header or query parameter
        $tenantId = $request->header('X-Tenant-ID') 
            ?? $request->header('X-Tenant') 
            ?? $request->query('tenant');

        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant could not be identified by request data',
                'error' => 'Missing tenant identifier in header (X-Tenant-ID or X-Tenant) or query parameter (tenant)'
            ], 400);
        }

        // Find tenant by ID or slug
        $tenant = Tenant::where('id', $tenantId)
            ->orWhere('slug', $tenantId)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
                'error' => "No tenant found with identifier: {$tenantId}"
            ], 404);
        }

        // Initialize tenancy
        tenancy()->initialize($tenant);

        return $next($request);
    }
}

