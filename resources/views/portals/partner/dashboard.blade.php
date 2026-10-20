@extends('layouts.app')
@section('content')
<div
    data-react-app="partner-dashboard"
    data-partner-name="{{ $partner->name }}"
></div>
@endsection
