@extends('layouts.app')

@section('title', 'Ingredient Usage')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-flask"></i> Ingredient Usage</h1>
      <p>Record what was used for prep (outside a sale).</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Ingredient Usage</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Usage log</h3>
          <p>
            @if(auth()->user()->hasPermission('inventory.manage'))
              <button type="button" class="btn btn-primary icon-btn" data-toggle="modal" data-target="#recordUsageModal">
                <i class="fa fa-plus"></i> Record usage
              </button>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 detail-card-table">
              <thead>
                <tr>
                  <th class="d-none d-md-table-cell">#</th>
                  <th>Date</th>
                  <th>Ingredient</th>
                  <th>Qty</th>
                  <th>By</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @forelse($usages as $usage)
                  <tr>
                    <td class="d-none d-md-table-cell" data-label="#">{{ $usages->firstItem() + $loop->index }}</td>
                    <td data-label="Date">{{ $usage->used_at->format('d M Y') }}</td>
                    <td data-label="Ingredient"><strong>{{ $usage->ingredient?->name ?? '—' }}</strong></td>
                    <td data-label="Qty">{{ $usage->formattedQuantity() }}</td>
                    <td data-label="By">{{ $usage->user?->name ?? '—' }}</td>
                    <td data-label="Notes">{{ $usage->notes ?: '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      No manual usage recorded yet.
                      @if(auth()->user()->hasPermission('inventory.manage'))
                        <button type="button" class="btn btn-link p-0 align-baseline" data-toggle="modal" data-target="#recordUsageModal">
                          Record the first usage
                        </button>
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $usages->links() }}</div>
        </div>
      </div>
    </div>
  </div>

  @if(auth()->user()->hasPermission('inventory.manage'))
    <div class="modal fade" id="recordUsageModal" tabindex="-1" role="dialog" aria-labelledby="recordUsageModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ route('ingredient-usages.store') }}" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="recordUsageModalLabel">Record usage</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              @if($errors->any())
                <div class="alert alert-danger py-2">
                  {{ $errors->first() }}
                </div>
              @endif

              <div class="form-group">
                <label class="control-label" for="usageIngredientId">Ingredient <span class="text-danger">*</span></label>
                <select class="form-control @error('ingredient_id') is-invalid @enderror" id="usageIngredientId" name="ingredient_id" required>
                  <option value="">Select...</option>
                  @foreach($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}" @selected(old('ingredient_id') == $ingredient->id)>
                      {{ $ingredient->name }} · stock {{ $ingredient->formattedStock() }}
                    </option>
                  @endforeach
                </select>
                @error('ingredient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label class="control-label" for="usageQuantityUsed">Qty used <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('quantity_used') is-invalid @enderror"
                  id="usageQuantityUsed" name="quantity_used"
                  min="0.0001" step="any" value="{{ old('quantity_used') }}" required>
                @error('quantity_used')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label class="control-label" for="usageUsedAt">Date used <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('used_at') is-invalid @enderror"
                  id="usageUsedAt" name="used_at"
                  value="{{ old('used_at', now()->format('Y-m-d')) }}" required>
                @error('used_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group mb-0">
                <label class="control-label" for="usageNotes">Notes</label>
                <input type="text" class="form-control @error('notes') is-invalid @enderror"
                  id="usageNotes" name="notes"
                  value="{{ old('notes') }}" maxlength="500" placeholder="e.g. morning prep">
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-check-circle"></i> Save usage
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  @if($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
          jQuery('#recordUsageModal').modal('show');
        }
      });
    </script>
  @endif
@endpush
