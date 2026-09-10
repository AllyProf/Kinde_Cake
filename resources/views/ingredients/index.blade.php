@extends('layouts.app')

@section('title', 'Ingredients')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-leaf"></i> Ingredients</h1>
      <p>Register raw materials with receiving and usage packages</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Ingredients</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">All Ingredients</h3>
          <p>
            @if(auth()->user()->hasPermission('inventory.manage'))
              <a class="btn btn-primary icon-btn" href="{{ route('ingredients.create') }}">
                <i class="fa fa-plus"></i> Add Ingredient
              </a>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Ingredient</th>
                  <th>Receiving unit</th>
                  <th>Usage unit</th>
                  <th>Conversion</th>
                  <th>Current stock</th>
                  <th>Status</th>
                  @if(auth()->user()->hasPermission('inventory.manage'))
                    <th>Actions</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($ingredients as $ingredient)
                  <tr>
                    <td>{{ $ingredients->firstItem() + $loop->index }}</td>
                    <td>
                      <strong>{{ $ingredient->name }}</strong>
                      @if($ingredient->description)
                        <br><small class="text-muted">{{ Str::limit($ingredient->description, 50) }}</small>
                      @endif
                    </td>
                    <td>{{ $ingredient->receivingPackageUnit?->name }} ({{ $ingredient->receivingPackageUnit?->symbol }})</td>
                    <td>{{ $ingredient->usagePackageUnit?->name }} ({{ $ingredient->usagePackageUnit?->symbol }})</td>
                    <td>{{ $ingredient->conversionLabel() }}</td>
                    <td>
                      <strong>{{ $ingredient->formattedStock() }}</strong>
                      @if($ingredient->isLowStock())
                        <br><span class="badge badge-warning">Low stock</span>
                      @endif
                    </td>
                    <td>
                      @if($ingredient->is_active)
                        <span class="badge badge-success">Active</span>
                      @else
                        <span class="badge badge-secondary">Inactive</span>
                      @endif
                    </td>
                    @if(auth()->user()->hasPermission('inventory.manage'))
                      <td>
                        <a class="btn btn-sm btn-primary" href="{{ route('ingredients.edit', $ingredient) }}">
                          <i class="fa fa-edit"></i>
                        </a>
                        <form action="{{ route('ingredients.destroy', $ingredient) }}" method="POST" class="d-inline js-swal-delete">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" data-title="Delete ingredient?" data-text="Remove {{ $ingredient->name }}?">
                            <i class="fa fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      No ingredients yet.
                      @if(auth()->user()->hasPermission('inventory.manage'))
                        <a href="{{ route('ingredients.create') }}">Add your first ingredient</a>.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $ingredients->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection
