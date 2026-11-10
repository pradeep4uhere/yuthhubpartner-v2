@extends('layouts.app')

@section('content')
    <div
        data-react-app="admin-announcements"
        data-can-view="{{ auth('admin')->user()->can('announcements.view') ? 'true' : 'false' }}"
        data-can-create="{{ auth('admin')->user()->can('announcements.create') ? 'true' : 'false' }}"
        data-can-update="{{ auth('admin')->user()->can('announcements.update') ? 'true' : 'false' }}"
        data-can-delete="{{ auth('admin')->user()->can('announcements.delete') ? 'true' : 'false' }}"
        data-can-publish="{{ auth('admin')->user()->can('announcements.publish') ? 'true' : 'false' }}"
    ></div>
@endsection
