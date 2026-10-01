<?php

namespace App\Http\Controllers;

use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\FieldAgentSakhiHistory;
use App\Services\ExportService;



class FieldAgentController extends Controller
{



public function export(
        Request $request,
        ExportService $exportService
    ): RedirectResponse {
        $admin = auth('admin')->user();

        $filters = array_filter([
            'search' => $request->input('search'),
            'partner_id' => $request->input('partner_id'),
            'status' => $request->input('status'),
        ], fn ($value) => $value !== null && $value !== '');

        $exportService->create($admin, [
            'type' => 'field_agents',

            'scope_type' => 'system',

            'scope_id' => null,

            'title' => 'Field Agents Export',

            'filters' => $filters,
        ]);

        return redirect()
            ->route('admin.field-agents.index')
            ->with(
                'status',
                'Field agent export has been queued. You can download it from Downloads.'
            );
    }

    private function generateSakhiId(int $partnerId, int $partnerCenterId): string
    {
        $partner = Partner::findOrFail($partnerId);
        $center = PartnerCenter::findOrFail($partnerCenterId);

        $partnerCode = strtoupper($partner->partner_code);
        $centerCode = strtoupper($center->partner_code);

        $lastSakhi = FieldAgent::where('partner_id', $partnerId)
            ->where('partner_center_id', $partnerCenterId)
            ->whereNotNull('sakhi_id')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1001;

        if ($lastSakhi) {
            $parts = explode('_', $lastSakhi->sakhi_id);

            $lastNumber = (int) end($parts);

            $nextNumber = $lastNumber + 1;
        }

        return "{$partnerCode}_{$centerCode}_{$nextNumber}";
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
        ]);

        $fieldAgents = FieldAgent::query()
            ->with([
                'partner:id,name,partner_code',
                'partnerCenter:id,name,partner_code',
                'placementPartnerUser:id,name,partner_placement_name,pp_code',
            ])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('csc_id', 'like', "%{$search}%")
                        ->orWhere('sakhi_id', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn ($relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('partnerCenter', fn ($relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('placementPartnerUser', fn ($relation) => $relation->where('partner_placement_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['partner_id'] ?? null, fn ($query, int $partnerId) => $query->where('partner_id', $partnerId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.field-agents.index', [
            'fieldAgents' => $fieldAgents,
            'filters' => $filters,
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'partner_code']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.field-agents.form', [
            'fieldAgent' => new FieldAgent,
            ...$this->formOptions(),
            'formAction' => route('admin.field-agents.store'),
            'formMethod' => 'POST',
            'pageTitle' => 'Add field agent',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {


        $data = $this->validatedData($request);

        $data['sakhi_id'] = $this->generateSakhiId(
            $data['partner_id'],
            $data['partner_center_id']
        );

        $fieldAgent = FieldAgent::create($data);


        return redirect()->route('admin.field-agents.index')->with('status', "Field agent {$fieldAgent->name} created.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FieldAgent $fieldAgent): View
    {
        return view('admin.field-agents.form', [
            'fieldAgent' => $fieldAgent,
            ...$this->formOptions(),
            'formAction' => route('admin.field-agents.update', $fieldAgent),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit field agent',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */


    public function update(Request $request, FieldAgent $fieldAgent): RedirectResponse
    {
        $data = $this->validatedData($request, $fieldAgent);

        $shouldGenerateSakhiId =
            blank($fieldAgent->sakhi_id) ||
            $fieldAgent->partner_id != $data['partner_id'] ||
            $fieldAgent->partner_center_id != $data['partner_center_id'];

        if ($shouldGenerateSakhiId) {

            $oldSakhiId = $fieldAgent->sakhi_id;

            $newSakhiId = $this->generateSakhiId(
                $data['partner_id'],
                $data['partner_center_id']
            );

            $data['sakhi_id'] = $newSakhiId;

            FieldAgentSakhiHistory::create([
                'field_agent_id' => $fieldAgent->id,
                'old_sakhi_id'   => $oldSakhiId,
                'new_sakhi_id'   => $newSakhiId,
                'changed_by'     => auth()->id(),
                'changed_at'     => now(),
            ]);


        }

        $fieldAgent->update($data);

        return redirect()
            ->route('admin.field-agents.index')
            ->with('status', "Field agent {$fieldAgent->name} updated.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FieldAgent $fieldAgent): RedirectResponse
    {
        $agentName = $fieldAgent->name;
        $fieldAgent->delete();

        return redirect()->route('admin.field-agents.index')->with('status', "Field agent {$agentName} deleted.");
    }

    /**
     * @return array{partners: Collection, partnerCenters: Collection, placementUsers: Collection}
     */
    private function formOptions(): array
    {
        return [
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'partner_code']),
            'partnerCenters' => PartnerCenter::with('partner:id,name')->orderBy('name')->get(['id', 'partner_id', 'name', 'partner_code']),
            'placementUsers' => PartnerPlacementUser::orderBy('partner_placement_name')->get(['id', 'name', 'partner_placement_name', 'pp_code']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?FieldAgent $fieldAgent = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('field_agents', 'email')->ignore($fieldAgent)],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'csc_id' => ['nullable', 'string', 'max:100', Rule::unique('field_agents', 'csc_id')->ignore($fieldAgent)],
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'partner_center_id' => [
                'required',
                'integer',
                Rule::exists('partner_centers', 'id')->where(fn (Builder $query) => $query->where('partner_id', $request->integer('partner_id'))),
            ],
            'placement_partner_user_id' => ['required', 'integer', 'exists:partner_placement_users,id'],
            'state' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => $fieldAgent === null
                ? ['required', 'string', 'min:12', 'confirmed']
                : ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        if ($fieldAgent !== null && blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}
