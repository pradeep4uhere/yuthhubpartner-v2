<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MpinSettingsController extends Controller
{
    /**
     * Retrieve the authenticated Partner's MPIN settings.
     *
     * Returns whether MPIN login is enabled and whether an MPIN
     * has already been configured.
     *
     * The MPIN hash is never returned.
     */
    public function show(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        if (!$partner) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        return response()->json([
            'mpin_is_set' => !empty($partner->mpin_hash),
            'mpin_login_enabled' => (bool) $partner->mpin_login_enabled,
        ]);
    }

    /**
     * Set or change the Partner MPIN and update
     * the MPIN login preference.
     *
     * An MPIN must be configured before MPIN login can be enabled.
     *
     * The Partner model's hashed cast handles secure hashing.
     */
    public function update(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        if (!$partner) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'mpin' => [
                    'nullable',
                    'string',
                    'digits:6',
                ],

                'mpin_confirmation' => [
                    'nullable',
                    'required_with:mpin',
                    'same:mpin',
                ],

                'mpin_login_enabled' => [
                    'required',
                    'boolean',
                ],
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

        $validated = $validator->validated();

        $enableMpinLogin = filter_var(
            $validated['mpin_login_enabled'],
            FILTER_VALIDATE_BOOLEAN
        );

        /*
        |--------------------------------------------------------------------------
        | Check Whether New MPIN Was Submitted
        |--------------------------------------------------------------------------
        */

        $hasNewMpin = isset($validated['mpin'])
            && $validated['mpin'] !== '';

        /*
        |--------------------------------------------------------------------------
        | Prevent Enabling MPIN Login Without MPIN Setup
        |--------------------------------------------------------------------------
        */

        if (
            $enableMpinLogin
            && !$hasNewMpin
            && empty($partner->mpin_hash)
        ) {
            return response()->json([
                'message' => 'mpin_setup_required',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update MPIN
        |--------------------------------------------------------------------------
        | Partner model must use the hashed cast for mpin_hash.
        | Never return or log the submitted MPIN.
        */

        if ($hasNewMpin) {
            $partner->mpin_hash = $validated['mpin'];
        }

        /*
        |--------------------------------------------------------------------------
        | Update MPIN Login Preference
        |--------------------------------------------------------------------------
        */

        $partner->mpin_login_enabled = $enableMpinLogin;

        $partner->save();

        return response()->json([
            'message' => 'mpin_settings_updated',

            'mpin_is_set' => !empty($partner->mpin_hash),

            'mpin_login_enabled' =>
                (bool) $partner->mpin_login_enabled,
        ]);
    }
}
