@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Edit Customer</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
      <li class="breadcrumb-item">Edit</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Details</h3>
        <div class="tile-body">
          <form action="{{ route('customers.update', $customer) }}" method="POST" class="js-controlled-form">
            @csrf
            @method('PUT')
            @include('customers.partials._form-fields', ['customer' => $customer])
            <div class="tile-footer customer-form-footer">
              <button class="btn btn-primary js-controlled-submit" type="submit" disabled><i class="fa fa-check-circle"></i> Save</button>
              <a class="btn btn-secondary" href="{{ route('customers.index') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
