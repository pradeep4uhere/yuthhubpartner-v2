<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PartnerPortalController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Partner Login Page
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Partner Password Authentication
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('partner')->attempt([
            ...$credentials,
            'status' => 'active',
        ])) {
            $request->session()->regenerate();

            $partner = $request->user('partner');

            // Reset previous MPIN verification state.
            $request->session()->forget([
                'partner_mpin_pending',
                'partner_mpin_verified',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Redirect to MPIN Verification When Enabled
            |--------------------------------------------------------------------------
            */

            if ($partner->mpin_login_enabled) {
                $request->session()->put(
                    'partner_mpin_pending',
                    true
                );

                return redirect()->route('partner.mpin.verify');
            }

            return redirect()->intended(
                route('partner.dashboard')
            );
        }

        return back()
            ->withErrors([
                'email' => 'These credentials do not match an active partner account.',
            ])
            ->onlyInput('email');
    }

    /*
    |--------------------------------------------------------------------------
    | Display Partner MPIN Verification Page
    |--------------------------------------------------------------------------
    */

    public function showMpinVerification(
        Request $request
    ): View|RedirectResponse {

        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return $this->logout(
                $request,
                'partner',
                'partner.login'
            );
        }

        if (!$partner->mpin_login_enabled) {
            $request->session()->forget([
                'partner_mpin_pending',
                'partner_mpin_verified',
            ]);

            return redirect()->route('partner.dashboard');
        }

        if (
            $request->session()->get(
                'partner_mpin_verified',
                false
            )
        ) {
            return redirect()->route('partner.dashboard');
        }

        if (
            !$request->session()->get(
                'partner_mpin_pending',
                false
            )
        ) {
            $request->session()->put(
                'partner_mpin_pending',
                true
            );
        }

        return view('portals.partner.mpin-verification', [
            'partner' => $partner,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Partner MPIN
    |--------------------------------------------------------------------------
    */

    public function verifyMpin(
        Request $request
    ): RedirectResponse {

        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return $this->logout(
                $request,
                'partner',
                'partner.login'
            );
        }

        if (!$partner->mpin_login_enabled) {
            $request->session()->forget([
                'partner_mpin_pending',
                'partner_mpin_verified',
            ]);

            return redirect()->route('partner.dashboard');
        }

        if (
            $request->session()->get(
                'partner_mpin_verified',
                false
            )
        ) {
            return redirect()->route('partner.dashboard');
        }

        if (
            !$request->session()->get(
                'partner_mpin_pending',
                false
            )
        ) {
            return redirect()->route('partner.mpin.verify');
        }

        $validated = $request->validate([
            'mpin' => ['required', 'digits:6'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Verify Hashed MPIN
        |--------------------------------------------------------------------------
        */

        if (
            empty($partner->mpin_hash)
            || !Hash::check(
                $validated['mpin'],
                $partner->mpin_hash
            )
        ) {
            return back()->withErrors([
                'mpin' => 'The MPIN entered is incorrect. Please try again.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Current Session as MPIN Verified
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        $request->session()->put(
            'partner_mpin_verified',
            true
        );

        $request->session()->forget(
            'partner_mpin_pending'
        );

        return redirect()->intended(
            route('partner.dashboard')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard(
        Request $request
    ): View|RedirectResponse {

        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return $this->logout(
                $request,
                'partner',
                'partner.login'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Enforce MPIN Verification
        |--------------------------------------------------------------------------
        */

        if ($partner->mpin_login_enabled) {

            if (
                !$request->session()->get(
                    'partner_mpin_verified',
                    false
                )
            ) {
                $request->session()->put(
                    'partner_mpin_pending',
                    true
                );

                return redirect()->route(
                    'partner.mpin.verify'
                );
            }

        } else {

            $request->session()->forget([
                'partner_mpin_pending',
                'partner_mpin_verified',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Partner Centers Query - Unchanged
        |--------------------------------------------------------------------------
        */

        $centers = DB::table('partner_centers as pc')
            ->where('pc.partner_id', $partner->id)
            ->select(
                'pc.id',
                'pc.name',
                'pc.partner_code as code',
                'pc.status'
            )

            // Count learners for each center
            ->selectSub(function ($query) use ($partner) {
                $query->from('learners as l')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'l.partner_center_id',
                        'pc.id'
                    )
                    ->where('l.partner_id', $partner->id);
            }, 'learners_count')

            // Count field agents for each center
            ->selectSub(function ($query) use ($partner) {
                $query->from('field_agents as fa')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'fa.partner_center_id',
                        'pc.id'
                    )
                    ->where('fa.partner_id', $partner->id);
            }, 'field_agents_count')

            ->orderBy('pc.name')
            ->get();

        return view('portals.partner.dashboard', [
            'partner' => $partner,
            'centers' => $centers,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Logout
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request
    ): RedirectResponse {

        return $this->logout(
            $request,
            'partner',
            'partner.login'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Common Logout Helper
    |--------------------------------------------------------------------------
    */

    private function logout(
        Request $request,
        string $guard,
        string $loginRoute
    ): RedirectResponse {

        Auth::guard($guard)->logout();

        $request->session()->forget([
            'partner_mpin_pending',
            'partner_mpin_verified',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
