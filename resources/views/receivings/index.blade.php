@extends('layouts.app')

@section('title', 'Receiving')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-truck"></i> Ingredients Receiving</h1>
      <p>Record incoming stock in receiving packages</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Receiving</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Receiving History</h3>
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
                  <th>Date</th>
                  <th>Ingredient</th>
                  <th>Received</th>
                  <th>Added to stock</th>
                  <th>Conversion used</th>
                  <th>Purchase cost</th>
                  <th>Supplier</th>
                  <th>Status</th>
                  <th>Recorded by</th>
                  @if(auth()->user()->hasPermission('inventory.manage'))
                    <th style="min-width:90px">Actions</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($receivings as $receiving)
                  @php
                    $ingredient = $receiving->ingredient;
                  @endphp
                  <tr class="{{ $receiving->isCancelled() ? 'receiving-row-cancelled' : '' }}">
                    <td>{{ $receivings->firstItem() + $loop->index }}</td>
                    <td>{{ $receiving->received_at->format('d M Y') }}</td>
                    <td><strong>{{ $ingredient?->name ?? '—' }}</strong></td>
                    <td>{{ $receiving->formattedEntry() }}</td>
                    <td>
                      <strong class="{{ $receiving->isCancelled() ? 'text-muted' : 'text-success' }}">
                        +{{ number_format((float) $receiving->usage_quantity_added, 0) }}
                        {{ $ingredient?->usagePackageUnit?->symbol }}
                      </strong>
                    </td>
                    <td>
                      1 {{ $ingredient?->receivingPackageUnit?->symbol }} = {{ number_format((float) $receiving->usage_per_receiving, 0) }} {{ $ingredient?->usagePackageUnit?->symbol }}
                    </td>
                    <td>{{ $receiving->formattedPurchaseCost() }}</td>
                    <td>{{ $receiving->supplier ?: '—' }}</td>
                    <td>
                      <span class="badge {{ $receiving->statusBadgeClass() }}">{{ $receiving->statusLabel() }}</span>
                      @if($receiving->cancelled_at)
                        <br><small class="text-muted">{{ $receiving->cancelled_at->format('d M Y H:i') }}</small>
                      @endif
                    </td>
                    <td>{{ $receiving->user?->name ?? '—' }}</td>
                    @if(auth()->user()->hasPermission('inventory.manage'))
                      <td>
                        @if($receiving->canBeCancelled())
                          <form action="{{ route('receivings.destroy', $receiving) }}" method="POST" class="d-inline js-swal-delete">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Cancel receiving"
                              data-title="Cancel receiving?"
                              data-text="This will reverse {{ number_format((float) $receiving->usage_quantity_added, 0) }} {{ $ingredient?->usagePackageUnit?->symbol }} from stock. The record will stay in the list as cancelled.">
                              <i class="fa fa-times"></i>
                            </button>
                          </form>
                        @elseif(! $receiving->isCancelled())
                          <span class="text-muted small" title="Stock from this receiving has already been used">In use</span>
                        @else
                          —
                        @endif
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ auth()->user()->hasPermission('inventory.manage') ? 11 : 10 }}" class="text-center text-muted py-4">
                      No receiving records yet.
                      @if(auth()->user()->hasPermission('inventory.manage'))
                        <a href="{{ route('receivings.create') }}">Receive your first stock</a>.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $receivings->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .receiving-row-cancelled {
      opacity: 0.85;
      background-color: #f8f9fa;
    }
  </style>
@endpush
