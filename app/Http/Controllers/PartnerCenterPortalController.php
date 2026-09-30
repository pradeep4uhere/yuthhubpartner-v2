<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PartnerCenterPortalController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::guard('partner_center')->check()) {
            return redirect()->route('partner-center.dashboard');
        }

        return view('portals.login', [
            'portalName' => 'Partner Center',
            'portalDescription' => 'Sign in to access your center workspace.',
            'loginAction' => route('partner-center.login.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('partner_center')->attempt([...$credentials, 'status' => 'active'])) {
            $request->session()->regenerate();

            return redirect()->intended(route('partner-center.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'These credentials do not match an active partner center account.'])
            ->onlyInput('email');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $partnerCenter = $request->user('partner_center');

        if ($partnerCenter->status !== 'active') {
            return $this->logout($request, 'partner_center', 'partner-center.login');
        }

        return view('portals.partner-centers.dashboard', [
            'partnerCenter' => $partnerCenter->load('partner:id,partner_code,name,email,contact_number,status'),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request, 'partner_center', 'partner-center.login');
    }

    private function logout(Request $request, string $guard, string $loginRoute): RedirectResponse
    {
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
