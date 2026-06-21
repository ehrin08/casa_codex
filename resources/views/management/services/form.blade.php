@extends('layouts.app')

@section('title', ($service->exists ? 'Edit Service' : 'Add Service').' | Casa Paraiso')
@section('page_title', $service->exists ? 'Edit Service' : 'Add Service')
@section('page_description', 'Set the treatment details guests and the Casa Paraiso team will see.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.services.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to services</a></div>
    @include('management.services._form')
@endsection
