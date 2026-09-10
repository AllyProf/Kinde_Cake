@extends('layouts.app')

@section('title', 'Edit Ingredient')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Edit Ingredient</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('ingredients.index') }}">Ingredients</a></li>
      <li class="breadcrumb-item">Edit</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Details</h3>
        <div class="tile-body">
          <form action="{{ route('ingredients.update', $ingredient) }}" method="POST" class="js-controlled-form">
            @csrf
            @method('PUT')
            @include('ingredients.partials._form-fields', ['ingredient' => $ingredient, 'isEdit' => true])
            <div class="tile-footer">
              <button class="btn btn-primary js-controlled-submit" type="submit" disabled><i class="fa fa-check-circle"></i> Save</button>
              <a class="btn btn-secondary" href="{{ route('ingredients.index') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
