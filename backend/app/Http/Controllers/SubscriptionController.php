<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Get current subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        return response()->json([
            'subscription' => $subscription,
        ]);
    }

    /**
     * Get available subscription plans.
     */
    public function plans(): JsonResponse
    {
        $plans = [
            [
                'key' => 'basic',
                'name' => 'Basic',
                'price' => 0,
                'features' => SubscriptionFeature::forPlan('basic')->enabled()->get(),
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'price' => 29,
                'features' => SubscriptionFeature::forPlan('pro')->enabled()->get(),
            ],
            [
                'key' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 99,
                'features' => SubscriptionFeature::forPlan('enterprise')->enabled()->get(),
            ],
        ];

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get available features for current subscription.
     */
    public function features(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        $features = SubscriptionFeature::forPlan($subscription->plan)
            ->enabled()
            ->get();

        return response()->json([
            'plan' => $subscription->plan,
            'features' => $features,
        ]);
    }

    /**
     * Upgrade subscription plan.
     */
    public function upgrade(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|in:pro,enterprise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = tenancy()->tenant;
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        // Check if upgrade is valid
        $planHierarchy = ['basic' => 1, 'pro' => 2, 'enterprise' => 3];
        $currentLevel = $planHierarchy[$subscription->plan] ?? 0;
        $newLevel = $planHierarchy[$request->plan] ?? 0;

        if ($newLevel <= $currentLevel) {
            return response()->json([
                'message' => 'Invalid upgrade. Please select a higher tier plan.'
            ], 400);
        }

        $subscription->update([
            'plan' => $request->plan,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'subscription' => $subscription->fresh(),
        ]);
    }

    /**
     * Downgrade subscription plan.
     */
    public function downgrade(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|in:basic,pro',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = tenancy()->tenant;
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        // Check if downgrade is valid
        $planHierarchy = ['basic' => 1, 'pro' => 2, 'enterprise' => 3];
        $currentLevel = $planHierarchy[$subscription->plan] ?? 0;
        $newLevel = $planHierarchy[$request->plan] ?? 0;

        if ($newLevel >= $currentLevel) {
            return response()->json([
                'message' => 'Invalid downgrade. Please select a lower tier plan.'
            ], 400);
        }

        $subscription->update([
            'plan' => $request->plan,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription downgraded successfully',
            'subscription' => $subscription->fresh(),
        ]);
    }
}
