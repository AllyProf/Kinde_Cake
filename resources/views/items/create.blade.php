@extends('layouts.app')

@section('title', 'Register Item')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-plus"></i> Register Item</h1>
      <p>Add a product with category, unit, and price</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('items.index') }}">Items</a></li>
      <li class="breadcrumb-item">Register</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Item Details</h3>
        <div class="tile-body">
          @include('items.partials._catalog-notice')
          <form action="{{ route('items.store') }}" method="POST" class="js-controlled-form">
            @csrf
            @include('items.partials._form-fields')
            <div class="tile-footer">
              <button class="btn btn-primary js-controlled-submit" type="submit" disabled><i class="fa fa-check-circle"></i> Register Item</button>
              <a class="btn btn-secondary" href="{{ route('items.index') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
