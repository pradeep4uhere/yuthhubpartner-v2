<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use DB;

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
                return $this->logout(
                    $request,
                    'partner',
                    'partner.login'
                );
            }

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
