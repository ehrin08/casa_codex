@extends('layouts.app')

@section('title', ($therapist->exists ? 'Edit Therapist' : 'Add Therapist').' | Casa Paraiso')
@section('page_title', $therapist->exists ? 'Edit Therapist Profile' : 'Add Therapist Profile')
@section('page_description', 'Maintain the therapist identity, account link, contact details, and care specialty.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.therapists.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to therapists</a></div>
    @include('management.therapists._form')
@endsection
