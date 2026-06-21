@extends('layouts.app')

@section('title', ($availability->exists ? 'Edit Availability' : 'Add Availability').' | Casa Paraiso')
@section('page_title', $availability->exists ? 'Edit Availability' : 'Add Availability')
@section('page_description', 'Choose a recurring weekday or one specific date, then define the therapist working window.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.availability.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to availability</a></div>
    @include('management.availability._form')
@endsection
