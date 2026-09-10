@extends('layouts.app')

@section('title', 'Stock')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-cubes"></i> Stock</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Stock</li>
    </ul>
  </div>

  @if($outOfStockItems->isNotEmpty())
    <div class="alert alert-danger">
      <i class="fa fa-times-circle"></i>
      <strong>Out of stock:</strong>
      {{ $outOfStockItems->pluck('name')->join(', ') }}
    </div>
  @endif

  @if($lowStockItems->isNotEmpty())
    <div class="alert alert-warning">
      <i class="fa fa-exclamation-triangle"></i>
      <strong>Low stock:</strong>
      @foreach($lowStockItems as $item)
        {{ $item->name }} ({{ $item->formattedStock() }})@if(! $loop->last), @endif
      @endforeach
    </div>
  @endif

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Current Stock</h3>
          <p>
            @if(auth()->user()->hasPermission('inventory.manage'))
              <a class="btn btn-primary icon-btn" href="{{ route('receivings.create') }}">
                <i class="fa fa-plus"></i> Receive Stock
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
                  <th>Stock</th>
                  <th>In packages</th>
                  <th>Reorder at</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($ingredients as $ingredient)
                  <tr class="{{ $ingredient->stockStatusClass() === 'danger' ? 'table-danger' : ($ingredient->stockStatusClass() === 'warning' ? 'table-warning' : '') }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                      <strong>{{ $ingredient->name }}</strong>
                      @if($ingredient->stockStatusClass() !== 'success')
                        <i class="fa fa-exclamation-triangle text-{{ $ingredient->stockStatusClass() }} ml-1" title="{{ $ingredient->stockStatusLabel() }}"></i>
                      @endif
                    </td>
                    <td><strong>{{ $ingredient->formattedStock() }}</strong></td>
                    <td>{{ $ingredient->formattedStockInReceiving() }}</td>
                    <td>
                      {{ number_format((float) $ingredient->reorder_level, 0) }}
                      {{ $ingredient->usagePackageUnit?->symbol }}
                    </td>
                    <td>
                      <span class="badge badge-{{ $ingredient->stockStatusClass() }}">
                        {{ $ingredient->stockStatusLabel() }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      No ingredients yet.
                      @if(auth()->user()->hasPermission('inventory.manage'))
                        <a href="{{ route('ingredients.create') }}">Add ingredients</a> first.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
