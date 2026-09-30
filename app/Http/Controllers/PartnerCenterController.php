<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $partnerCenters = PartnerCenter::query()
            ->with('partner:id,name,partner_code')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('partner_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn ($partnerQuery) => $partnerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['partner_id'] ?? null, fn ($query, int $partnerId) => $query->where('partner_id', $partnerId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.partner-centers.index', [
            'partnerCenters' => $partnerCenters,
            'filters' => $filters,
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'partner_code']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.partner-centers.form', [
            'partnerCenter' => new PartnerCenter,
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'partner_code']),
            'formAction' => route('admin.partner-centers.store'),
            'formMethod' => 'POST',
            'pageTitle' => 'Add partner center',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $partnerCenter = PartnerCenter::create($this->validatedData($request));

        return redirect()->route('admin.partner-centers.index')->with('status', "Partner center {$partnerCenter->name} created.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PartnerCenter $partnerCenter): View
    {
        return view('admin.partner-centers.form', [
            'partnerCenter' => $partnerCenter,
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'partner_code']),
            'formAction' => route('admin.partner-centers.update', $partnerCenter),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit partner center',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartnerCenter $partnerCenter): RedirectResponse
    {
        $partnerCenter->update($this->validatedData($request, $partnerCenter));

        return redirect()->route('admin.partner-centers.index')->with('status', "Partner center {$partnerCenter->name} updated.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PartnerCenter $partnerCenter): RedirectResponse
    {
        $centerName = $partnerCenter->name;
        $partnerCenter->delete();

        return redirect()->route('admin.partner-centers.index')->with('status', "Partner center {$centerName} deleted.");
    }

    /**
     * @return array{partner_id: int, partner_code: string, name: string, contact_number: ?string, email: ?string, status: string}
     */
    private function validatedData(Request $request, ?PartnerCenter $partnerCenter = null): array
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'partner_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('partner_centers', 'partner_code')->ignore($partnerCenter),
            ],
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('partner_centers', 'email')->ignore($partnerCenter),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => $partnerCenter === null
                ? ['required', 'string', 'min:12', 'confirmed']
                : ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        if ($partnerCenter !== null && blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}
