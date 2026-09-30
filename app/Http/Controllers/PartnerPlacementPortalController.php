<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PartnerPlacementPortalController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::guard('partner_placement')->check()) {
            return redirect()->route('partner-placement.dashboard');
        }

        return view('portals.login', [
            'portalName' => 'Placement User',
            'portalDescription' => 'Sign in to access your placement workspace.',
            'loginAction' => route('partner-placement.login.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('partner_placement')->attempt([...$credentials, 'status' => 'active'])) {
            $request->session()->regenerate();

            return redirect()->intended(route('partner-placement.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'These credentials do not match an active placement account.'])
            ->onlyInput('email');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $placementUser = $request->user('partner_placement');

        if ($placementUser->status !== 'active') {
            return $this->logout($request);
        }

        return view('portals.partner-placement.dashboard', [
            'placementUser' => $placementUser,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }

    private function logout(Request $request): RedirectResponse
    {
        Auth::guard('partner_placement')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner-placement.login');
    }
}
