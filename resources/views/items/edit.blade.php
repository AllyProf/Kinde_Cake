@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Edit Item</h1>
      <p>{{ $item->name }}</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('items.index') }}">Items</a></li>
      <li class="breadcrumb-item">Edit</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Item Details</h3>
        <div class="tile-body">
          @include('items.partials._catalog-notice')
          <form action="{{ route('items.update', $item) }}" method="POST" class="js-controlled-form">
            @csrf
            @method('PUT')
            @include('items.partials._form-fields', ['item' => $item, 'isEdit' => true])
            <div class="tile-footer">
              <button class="btn btn-primary js-controlled-submit" type="submit" disabled><i class="fa fa-check-circle"></i> Save Item</button>
              <a class="btn btn-secondary" href="{{ route('items.index') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
