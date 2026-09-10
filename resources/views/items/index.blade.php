@extends('layouts.app')

@section('title', 'Items')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-shopping-basket"></i> Items</h1>
      <p>Register products you sell and their prices</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Items</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">All Items</h3>
          <p>
            <a class="btn btn-primary icon-btn" href="{{ route('items.create') }}">
              <i class="fa fa-plus"></i> Register Item
            </a>
          </p>
        </div>
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Item</th>
                  <th>Category</th>
                  <th>Unit</th>
                  <th>Price</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($items as $item)
                  <tr>
                    <td>{{ $items->firstItem() + $loop->index }}</td>
                    <td>
                      <strong>{{ $item->name }}</strong>
                      @if($item->description)
                        <br><small class="text-muted">{{ Str::limit($item->description, 60) }}</small>
                      @endif
                    </td>
                    <td>{{ $item->category?->name ?? '—' }}</td>
                    <td>{{ $item->packageUnit?->symbol ?? '—' }}</td>
                    <td><strong>{{ $item->formattedPrice() }}</strong></td>
                    <td>
                      @if($item->is_active)
                        <span class="badge badge-success">Active</span>
                      @else
                        <span class="badge badge-secondary">Inactive</span>
                      @endif
                    </td>
                    <td>
                      <a class="btn btn-sm btn-primary" href="{{ route('items.edit', $item) }}">
                        <i class="fa fa-edit"></i> Edit
                      </a>
                      <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline js-swal-delete">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" data-title="Delete item?" data-text="Remove {{ $item->name }} from your catalog?">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      No items registered yet.
                      @if(auth()->user()->isOwner())
                        <a href="{{ route('items.create') }}">Register your first item</a>
                        or import categories and packages from
                        <a href="{{ route('settings.index', ['tab' => 'categories']) }}">Settings</a>.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $items->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection
