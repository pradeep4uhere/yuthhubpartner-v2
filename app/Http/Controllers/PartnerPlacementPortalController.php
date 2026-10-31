<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PartnerPlacementPortalController extends Controller
{
    /**
     * Display the Placement Partner login page or redirect an authenticated user.
     */
    public function create(): RedirectResponse|View
    {
        if (Auth::guard('partner_placement')->check()) {
            $placementUser = Auth::guard('partner_placement')->user();

            if (
                $placementUser->mpin_login_enabled &&
                !session('placement_mpin_verified', false)
            ) {
                return redirect()->route('partner-placement.mpin.verify');
            }

            return redirect()->route('partner-placement.dashboard');
        }

        return view('portals.partner-placement.login', [
            'portalName' => 'Placement User',
            'portalDescription' => 'Sign in to access your placement workspace.',
            'loginAction' => route('partner-placement.login.store'),
        ]);
    }

    /**
     * Authenticate the Placement Partner and require MPIN verification when enabled.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('partner_placement')->attempt([
            ...$credentials,
            'status' => 'active',
        ])) {
            return back()
                ->withErrors([
                    'email' => 'These credentials do not match an active placement account.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $placementUser = $request->user('partner_placement');

        $request->session()->forget([
            'placement_mpin_pending',
            'placement_mpin_verified',
        ]);

        if ($placementUser->mpin_login_enabled) {
            if (empty($placementUser->mpin_hash)) {
                return $this->logout($request)->with(
                    'error',
                    'Please configure your MPIN before signing in.'
                );
            }

            $request->session()->put('placement_mpin_pending', true);

            return redirect()->route('partner-placement.mpin.verify');
        }

        return redirect()->intended(route('partner-placement.dashboard'));
    }

    /**
     * Show the MPIN verification page for an authenticated Placement Partner.
     */
    public function showMpinVerification(Request $request): RedirectResponse|View
    {
        $placementUser = $request->user('partner_placement');

        if (!$placementUser) {
            return redirect()->route('partner-placement.login');
        }

        if ($placementUser->status !== 'active') {
            return $this->logout($request);
        }

        if (!$placementUser->mpin_login_enabled) {
            $request->session()->forget([
                'placement_mpin_pending',
                'placement_mpin_verified',
            ]);

            return redirect()->route('partner-placement.dashboard');
        }

        if (empty($placementUser->mpin_hash)) {
            return $this->logout($request)->with(
                'error',
                'Your MPIN is not configured. Please contact support.'
            );
        }

        if (session('placement_mpin_verified', false)) {
            return redirect()->route('partner-placement.dashboard');
        }

        $request->session()->put('placement_mpin_pending', true);

        return view('portals.partner-placement.mpin-verification', [
            'placementUser' => $placementUser,
        ]);
    }

    /**
     * Validate the submitted MPIN and mark the session as verified.
     */
    public function verifyMpin(Request $request): RedirectResponse
    {
        $placementUser = $request->user('partner_placement');

        if (!$placementUser) {
            return redirect()->route('partner-placement.login');
        }

        if ($placementUser->status !== 'active') {
            return $this->logout($request);
        }

        if (!$placementUser->mpin_login_enabled) {
            return redirect()->route('partner-placement.dashboard');
        }

        if (!session('placement_mpin_pending', false)) {
            return redirect()->route('partner-placement.mpin.verify');
        }

        $validated = $request->validate([
            'mpin' => ['required', 'digits:6'],
        ]);

        if (
            empty($placementUser->mpin_hash) ||
            !Hash::check($validated['mpin'], $placementUser->mpin_hash)
        ) {
            return back()
                ->withErrors(['mpin' => 'The MPIN you entered is incorrect.'])
                ->onlyInput('mpin');
        }

        $request->session()->regenerate();
        $request->session()->put('placement_mpin_verified', true);
        $request->session()->forget('placement_mpin_pending');

        return redirect()->intended(route('partner-placement.dashboard'));
    }

    /**
     * Display the dashboard only after the required MPIN has been verified.
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $placementUser = $request->user('partner_placement');

        if (!$placementUser) {
            return redirect()->route('partner-placement.login');
        }

        if ($placementUser->status !== 'active') {
            return $this->logout($request);
        }

        if ($placementUser->mpin_login_enabled) {
            if (empty($placementUser->mpin_hash)) {
                return $this->logout($request)->with(
                    'error',
                    'Your MPIN is not configured. Please contact support.'
                );
            }

            if (!session('placement_mpin_verified', false)) {
                $request->session()->put('placement_mpin_pending', true);

                return redirect()->route('partner-placement.mpin.verify');
            }
        } else {
            $request->session()->forget([
                'placement_mpin_pending',
                'placement_mpin_verified',
            ]);
        }

        return view('portals.partner-placement.dashboard', [
            'placementUser' => $placementUser,
        ]);
    }

    /**
     * Log out the Placement Partner and clear MPIN verification session state.
     */
    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }

    /**
     * Perform a complete logout for the Placement Partner guard.
     */
    private function logout(Request $request): RedirectResponse
    {
        Auth::guard('partner_placement')->logout();

        $request->session()->forget([
            'placement_mpin_pending',
            'placement_mpin_verified',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner-placement.login');
    }
}
