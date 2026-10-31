<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlacementPartnerChangePasswordController extends Controller
{
    /**
     * Verify the current password and update the Placement Partner password.
     */
    public function update(Request $request): JsonResponse
    {
        // Resolve the authenticated Placement Partner using its dedicated guard.
        $placementPartner = $request->user('partner_placement');

        if (!$placementPartner) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Validate the current password and the new password confirmation.
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ],
        ]);

        // Verify that the current password matches the stored password hash.
        if (!Hash::check(
            $validated['current_password'],
            $placementPartner->password
        )) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'The current password is incorrect.',
                ],
            ]);
        }

        // Save the new password; the model's hashed cast hashes it automatically.
        $placementPartner->password = $validated['password'];
        $placementPartner->save();

        return response()->json([
            'message' => 'password_updated_successfully',
        ]);
    }
}
