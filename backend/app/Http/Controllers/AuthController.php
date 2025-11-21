<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new tenant and user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'tenant_slug' => 'required|string|max:255|unique:tenants,slug',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:manager,team_lead,agent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create tenant
        $tenant = Tenant::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
        ]);
        
        // Set name and slug as direct columns
        $tenant->name = $request->tenant_name;
        $tenant->slug = $request->tenant_slug;
        $tenant->save();

        // Create domain for tenant (optional - for subdomain identification)
        // For single database with request data, this might not be needed
        // but keeping it for flexibility
        try {
            $domain = $request->tenant_slug . '.' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');
            $tenant->domains()->create([
                'domain' => $domain,
            ]);
        } catch (\Exception $e) {
            // Domain might already exist or not needed for single DB mode
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'manager',
        ]);

        // Link user to tenant
        $tenant->users()->attach($user);

        // Initialize tenancy for this tenant
        tenancy()->initialize($tenant);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user->load('tenants'),
            'tenant' => $tenant,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_id' => 'required|string', // Tenant ID or slug
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find tenant by ID or slug
        $tenant = Tenant::where('id', $request->tenant_id)
            ->orWhere('slug', $request->tenant_id)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found'
            ], 404);
        }

        // Check if user exists and belongs to tenant
        $user = User::where('email', $request->email)
            ->whereHas('tenants', function ($query) use ($tenant) {
                $query->where('tenants.id', $tenant->id);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Initialize tenancy
        tenancy()->initialize($tenant);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load('tenants'),
            'tenant' => $tenant,
            'token' => $token,
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user with tenant info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenants');
        $tenant = tenancy()->tenant;

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }
}
