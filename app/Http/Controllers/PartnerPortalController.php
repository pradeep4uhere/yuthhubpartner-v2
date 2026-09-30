<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PartnerPortalController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::guard('partner')->check()) {
            return redirect()->route('partner.dashboard');
        }

        return view('portals.login', [
            'portalName' => 'Partner',
            'portalDescription' => 'Sign in to manage your organisation and partner centers.',
            'loginAction' => route('partner.login.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('partner')->attempt([...$credentials, 'status' => 'active'])) {
            $request->session()->regenerate();

            return redirect()->intended(route('partner.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'These credentials do not match an active partner account.'])
            ->onlyInput('email');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $partner = $request->user('partner');

        if ($partner->status !== 'active') {
            return $this->logout($request, 'partner', 'partner.login');
        }

        return view('portals.partner.dashboard', [
            'partner' => $partner->load('centers:id,partner_id,partner_code,name,status'),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request, 'partner', 'partner.login');
    }

    private function logout(Request $request, string $guard, string $loginRoute): RedirectResponse
    {
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
