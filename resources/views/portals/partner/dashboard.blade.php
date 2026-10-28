@extends('layouts.app')
@section('content')
@php
    $partnerData = [
        'id' => $partner->id,
        'name' => $partner->name,
        'email' => $partner->email,
        'phone' => $partner->contact_number,
        'partnerCode' => $partner->partner_code,
        'status' => $partner->status,
    ];
@endphp

<div
    data-react-app="partner-dashboard"
    data-partner-name="{{ $partner->name }}"
    data-partner="{{ json_encode($partnerData) }}"
    data-centers="{{ json_encode($centers) }}"
></div>

<script>
    window.partnerDashboardData = {{ Illuminate\Support\Js::from([
        'id' => $partner->id,
        'name' => $partner->name,
        'email' => $partner->email,
        'phone' => $partner->contact_number,
        'status' => $partner->status,
        'partnerCode' => $partner->partner_code,
    ]) }};
</script>

<script type="application/json" id="partner-translations">
    @json(__('partner'))
</script>
<script>
    window.partnerDashboardData = {
        centers: @json($centers)
    };
</script>

@endsection
