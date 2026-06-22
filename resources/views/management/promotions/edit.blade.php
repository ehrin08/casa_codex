@extends('layouts.app')

@section('title', 'Edit Promotion Rule | Casa Paraiso')
@section('page_title', 'Edit Promotion Rule')
@section('page_description', 'Update the discount, RFM eligibility criteria, or availability window.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('management.promotions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to promotion rules</a>
    </div>

    @include('management.promotions._form')
@endsection
