<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MpinSettingsController extends Controller
{
    /**
     * Retrieve the authenticated Field Agent's MPIN settings.
     *
     * Returns whether MPIN login is enabled and whether an MPIN
     * has already been configured. The MPIN hash is never returned.
     *
     * @param Request $request The authenticated request.
     * @return JsonResponse The current MPIN settings.
     */
    public function show(Request $request): JsonResponse
    {
        $fieldAgent = $request->user('field_agent');

        if (!$fieldAgent) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        return response()->json([
            'mpin_is_set' => !empty($fieldAgent->mpin_hash),
            'mpin_login_enabled' => (bool) $fieldAgent->mpin_login_enabled,
        ]);
    }

    /**
     * Set or change the MPIN and update the MPIN login preference.
     *
     * An MPIN must be configured before login can be enabled.
     * The FieldAgent model's hashed cast handles secure hashing.
     * Existing MPIN settings remain unchanged if validation fails.
     *
     * @param Request $request The incoming settings request.
     * @return JsonResponse The updated settings or validation errors.
     */
    public function update(Request $request): JsonResponse
    {
        $fieldAgent = $request->user('field_agent');

        if (!$fieldAgent) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'mpin' => ['nullable', 'string', 'digits:6'],
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
                'mpin_confirmation.required_with' => 'mpin_confirmation_required',
                'mpin_confirmation.same' => 'mpin_confirmation_mismatch',
                'mpin_login_enabled.required' => 'mpin_login_status_required',
                'mpin_login_enabled.boolean' => 'mpin_login_status_invalid',
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
         * Do not enable MPIN login until an MPIN has been configured.
         */
        $hasNewMpin = isset($validated['mpin'])
            && $validated['mpin'] !== '';

        if (
            $enableMpinLogin
            && !$hasNewMpin
            && empty($fieldAgent->mpin_hash)
        ) {
            return response()->json([
                'message' => 'mpin_setup_required',
            ], 422);
        }

        /*
         * The model's "hashed" cast hashes the new MPIN automatically.
         * Never return or log the submitted MPIN.
         */
        if ($hasNewMpin) {
            $fieldAgent->mpin_hash = $validated['mpin'];
        }

        $fieldAgent->mpin_login_enabled = $enableMpinLogin;
        $fieldAgent->save();

        return response()->json([
            'message' => 'mpin_settings_updated',
            'mpin_is_set' => !empty($fieldAgent->mpin_hash),
            'mpin_login_enabled' => (bool) $fieldAgent->mpin_login_enabled,
        ]);
    }
}
