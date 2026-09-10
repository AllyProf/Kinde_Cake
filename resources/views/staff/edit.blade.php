@extends('layouts.app')

@section('title', 'Edit Staff')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Edit Staff</h1>
      <p>{{ $staff->name }}</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
      <li class="breadcrumb-item">Edit</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Staff Details</h3>
        <div class="tile-body">
          <form action="{{ route('staff.update', $staff) }}" method="POST" class="js-controlled-form">
            @csrf
            @method('PUT')
            @include('staff.partials._form-fields', ['staff' => $staff, 'isEdit' => true])
            <div class="tile-footer">
              <button class="btn btn-primary js-controlled-submit" type="submit" disabled><i class="fa fa-check-circle"></i> Save Staff</button>
              <a class="btn btn-secondary" href="{{ route('staff.index') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
