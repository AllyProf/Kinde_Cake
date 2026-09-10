@extends('layouts.app')

@section('title', 'Customers')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-address-book"></i> Customers</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Customers</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">All Customers</h3>
          <p>
            @if(auth()->user()->hasPermission('customers.manage'))
              <a class="btn btn-primary icon-btn" href="{{ route('customers.create') }}">
                <i class="fa fa-plus"></i> Add Customer
              </a>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <div class="table-responsive customers-list">
            <table class="table table-hover table-bordered customers-list-table detail-card-table">
              <thead>
                <tr>
                  <th class="d-none d-md-table-cell">#</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Region</th>
                  <th>District</th>
                  <th>Location</th>
                  <th>Email</th>
                  <th>Status</th>
                  @if(auth()->user()->hasPermission('customers.manage'))
                    <th>Actions</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($customers as $customer)
                  <tr>
                    <td class="d-none d-md-table-cell" data-label="#">{{ $customers->firstItem() + $loop->index }}</td>
                    <td data-label="Name"><strong>{{ $customer->name }}</strong></td>
                    <td data-label="Phone">{{ $customer->phone ?: '—' }}</td>
                    <td data-label="Region">{{ $customer->region ?: '—' }}</td>
                    <td data-label="District">{{ $customer->district ?: '—' }}</td>
                    <td data-label="Location">{{ $customer->location ?: '—' }}</td>
                    <td data-label="Email">{{ $customer->email ?: '—' }}</td>
                    <td data-label="Status">
                      @if($customer->is_active)
                        <span class="badge badge-success">Active</span>
                      @else
                        <span class="badge badge-secondary">Inactive</span>
                      @endif
                    </td>
                    @if(auth()->user()->hasPermission('customers.manage'))
                      <td class="customers-list-actions" data-label="Actions">
                        <a class="btn btn-sm btn-primary" href="{{ route('customers.edit', $customer) }}">
                          <i class="fa fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline js-swal-delete">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" data-title="Delete customer?" data-text="Remove {{ $customer->name }}?">
                            <i class="fa fa-trash"></i> Delete
                          </button>
                        </form>
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ auth()->user()->hasPermission('customers.manage') ? 9 : 8 }}" class="text-center text-muted py-4">
                      No customers yet.
                      @if(auth()->user()->hasPermission('customers.manage'))
                        <a href="{{ route('customers.create') }}">Add your first customer</a>.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $customers->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection
