<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Handles MPIN settings for the authenticated Placement Partner.
 */
class PlacementPartnerMpinSettingsController extends Controller
{
    /**
     * Retrieve MPIN configuration without exposing the stored MPIN hash.
     */
    public function show(Request $request): JsonResponse
    {
        // Resolve the user through the dedicated Placement Partner guard.
        $placementPartner = $request->user('partner_placement');

        if (!$placementPartner) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        // Return only the MPIN configuration status.
        return response()->json([
            'mpin_is_set' => !empty($placementPartner->mpin_hash),
            'mpin_login_enabled' =>
                (bool) $placementPartner->mpin_login_enabled,
        ]);
    }

    /**
     * Validate and save the MPIN and its login preference.
     */
    public function update(Request $request): JsonResponse
    {
        // Resolve the authenticated Placement Partner.
        $placementPartner = $request->user('partner_placement');

        if (!$placementPartner) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        // Validate the submitted MPIN and login preference.
        $validator = Validator::make(
            $request->all(),
            [
                'mpin' => ['nullable', 'string', 'digits:6'],
                'mpin_confirmation' => [
                    'nullable',
                    'required_with:mpin',
                    'same:mpin',
                ],
                'mpin_login_enabled' => ['required', 'boolean'],
            ],
            [
                'mpin.digits' => 'mpin_must_be_six_digits',
                'mpin_confirmation.required_with' =>
                    'mpin_confirmation_required',
                'mpin_confirmation.same' =>
                    'mpin_confirmation_mismatch',
                'mpin_login_enabled.required' =>
                    'mpin_login_status_required',
                'mpin_login_enabled.boolean' =>
                    'mpin_login_status_invalid',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Extract validated values and normalize the login preference.
        $validated = $validator->validated();

        $enableMpinLogin = filter_var(
            $validated['mpin_login_enabled'],
            FILTER_VALIDATE_BOOLEAN
        );

        // Determine whether a new MPIN was provided.
        $hasNewMpin = isset($validated['mpin'])
            && $validated['mpin'] !== '';

        // Require an existing or newly configured MPIN before enabling login.
        if (
            $enableMpinLogin
            && !$hasNewMpin
            && empty($placementPartner->mpin_hash)
        ) {
            return response()->json([
                'message' => 'mpin_setup_required',
            ], 422);
        }

        // Update the MPIN only when a new value has been submitted.
        if ($hasNewMpin) {
            $placementPartner->mpin_hash = $validated['mpin'];
        }

        // Persist the Placement Partner's MPIN login preference.
        $placementPartner->mpin_login_enabled = $enableMpinLogin;
        $placementPartner->save();

        // Return configuration status without exposing the MPIN or hash.
        return response()->json([
            'message' => 'mpin_settings_updated',
            'mpin_is_set' => !empty($placementPartner->mpin_hash),
            'mpin_login_enabled' =>
                (bool) $placementPartner->mpin_login_enabled,
        ]);
    }
}
