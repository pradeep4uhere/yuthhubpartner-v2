@extends('layouts.app')

@section('content')
    <div
        data-react-app="admin-dashboard"
        data-admin-name="{{ auth('admin')->user()->name }}"
        data-can-manage-admins="{{ auth('admin')->user()->can('admins.manage') ? 'true' : 'false' }}"
        data-can-view-partners="{{ auth('admin')->user()->can('partners.view') ? 'true' : 'false' }}"
        data-can-view-partner-centers="{{ auth('admin')->user()->can('partner_centers.view') ? 'true' : 'false' }}"
        data-can-manage-placement-users="{{ auth('admin')->user()->can('partner_placement_users.view') ? 'true' : 'false' }}"
        data-can-view-field-agents="{{ auth('admin')->user()->can('field_agents.view') ? 'true' : 'false' }}"
        data-can-view-event-types="{{ auth('admin')->user()->can('event_types.view') ? 'true' : 'false' }}"
        data-can-view-event-categories="{{ auth('admin')->user()->can('event_categories.view') ? 'true' : 'false' }}"
        data-can-view-event-masters="{{ auth('admin')->user()->can('event_masters.view') ? 'true' : 'false' }}"
        data-can-view-downloads="{{ auth('admin')->user()->can('downloads.view') ? 'true' : 'false' }}"
    ></div>
@endsection
