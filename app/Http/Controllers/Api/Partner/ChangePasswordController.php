<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Handle password changes for the authenticated Field Agent.
 *
 * Verifies the current password, validates the new password and its
 * confirmation, and saves the updated password using the FieldAgent model's
 * hashed password cast.
 */
class ChangePasswordController extends Controller
{
    /**
     * Validate and update the authenticated Field Agent's password.
     *
     * @param Request $request The incoming password-change request.
     * @return JsonResponse JSON success or validation response.
     */
    public function update(Request $request): JsonResponse
    {
        $fieldAgent = $request->user('partner');

        if (!$fieldAgent) {
            return response()->json([
                'message' => 'unauthenticated',
            ], 401);
        }

        $validator = Validator::make(
        $request->all(),
        [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ],
        [
            'current_password.required' => 'complete_all_password_fields',
            'password.required' => 'complete_all_password_fields',
            'password.min' => 'password_minimum_length',
            'password.confirmed' => 'password_confirmation_mismatch',
        ]
    );

    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    $validated = $validator->validated();

        if (!Hash::check(
            $validated['current_password'],
            $fieldAgent->password
        )) {
            return response()->json([
                'message' => 'current_password_incorrect',
            ], 422);
        }

        // The FieldAgent model's "hashed" cast securely hashes this value.
        $fieldAgent->password = $validated['password'];
        $fieldAgent->save();

        return response()->json([
            'message' => 'password_updated_successfully',
        ]);
    }
}
