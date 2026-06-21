@extends('layouts.app')

@section('title', ($customer->exists ? 'Edit Customer' : 'Add Customer').' | Casa Paraiso')
@section('page_title', $customer->exists ? 'Edit Customer Profile' : 'Add Customer Profile')
@section('page_description', 'Maintain a linked customer account or a standalone walk-in guest record.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.customers.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to customers</a></div>
    @include('management.customers._form')
@endsection
