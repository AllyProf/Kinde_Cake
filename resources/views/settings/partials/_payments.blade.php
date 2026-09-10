<div class="row">
  <div class="col-md-12">
    <div class="tile settings-panel">
      <div class="tile-title-w-btn">
        <h3 class="title">Payment Providers</h3>
        <p>
          <form action="{{ route('settings.payments.import') }}" method="POST" class="d-inline js-swal-confirm settings-import-form">
            @csrf
            <button type="submit" class="btn btn-secondary icon-btn"
              data-title="Import built-in payment providers?"
              data-text="This will add default mobile and bank providers. You can remove any you don't use."
              data-confirm="Yes, import"
              data-cancel="Cancel">
              <i class="fa fa-download"></i> Import Built-in Providers
            </button>
          </form>
        </p>
      </div>
      <div class="tile-body">
        <p class="text-muted mb-4">
          Configure mobile money and bank providers used when staff record payments on sales.
          Cash and credit do not require a provider.
        </p>

        <div class="row mb-4 settings-add-section">
          <div class="col-12 col-lg-8">
            <h5 class="mb-3">Add custom provider</h5>
            <form action="{{ route('settings.payments.store') }}" method="POST" class="row settings-add-form">
              @csrf
              <div class="col-12 col-md-5">
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                  placeholder="Provider name" value="{{ old('name') }}" required maxlength="120">
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-5">
                <select class="form-control @error('type') is-invalid @enderror" name="type" required>
                  <option value="">Type</option>
                  <option value="mobile" @selected(old('type') === 'mobile')>Mobile payment</option>
                  <option value="bank" @selected(old('type') === 'bank')>Bank payment</option>
                </select>
                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Add</button>
              </div>
            </form>
          </div>
          <div class="col-12 col-lg-4">
            <h5 class="mb-3">Built-in available</h5>
            <div class="small text-muted settings-builtin-badges">
              @foreach($builtinPaymentProviders as $item)
                <span class="badge badge-light border mr-1 mb-1">{{ $item['name'] }}</span>
              @endforeach
            </div>
          </div>
        </div>

        <div class="table-responsive settings-list">
          <table class="table table-hover table-bordered settings-list-table detail-card-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Source</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($paymentProviders as $provider)
                <tr class="settings-data-row">
                  <td data-label="Name"><strong>{{ $provider->name }}</strong></td>
                  <td data-label="Type">{{ $provider->typeLabel() }}</td>
                  <td data-label="Source">
                    @if($provider->is_builtin)
                      <span class="badge badge-info">Built-in</span>
                    @else
                      <span class="badge badge-secondary">Custom</span>
                    @endif
                  </td>
                  <td data-label="Status">
                    @if($provider->is_active)
                      <span class="badge badge-success">Active</span>
                    @else
                      <span class="badge badge-secondary">Inactive</span>
                    @endif
                  </td>
                  <td class="settings-list-actions" data-label="Actions">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#edit-payment-{{ $provider->id }}">
                      <i class="fa fa-edit"></i> Edit
                    </button>
                    <form action="{{ route('settings.payments.destroy', $provider) }}" method="POST" class="d-inline js-swal-delete">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" data-title="Remove provider?" data-text="Remove {{ $provider->name }}?">
                        <i class="fa fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <tr class="collapse settings-edit-row" id="edit-payment-{{ $provider->id }}">
                  <td colspan="5">
                    <form action="{{ route('settings.payments.update', $provider) }}" method="POST" class="row py-2 settings-edit-form">
                      @csrf
                      @method('PUT')
                      <div class="col-12 col-md-4">
                        <label class="control-label small">Name</label>
                        <input type="text" class="form-control" name="name" value="{{ $provider->name }}" required maxlength="120">
                      </div>
                      <div class="col-12 col-sm-6 col-md-3">
                        <label class="control-label small">Type</label>
                        <select class="form-control" name="type" required>
                          <option value="mobile" @selected($provider->type === 'mobile')>Mobile payment</option>
                          <option value="bank" @selected($provider->type === 'bank')>Bank payment</option>
                        </select>
                      </div>
                      <div class="col-12 col-sm-6 col-md-3">
                        <label class="custom-control custom-checkbox mt-md-4 d-block">
                          <input type="checkbox" class="custom-control-input" name="is_active" value="1" @checked($provider->is_active)>
                          <span class="custom-control-label">Active</span>
                        </label>
                      </div>
                      <div class="col-12 col-md-2">
                        <label class="control-label small d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block">Save</button>
                      </div>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    No payment providers yet.
                    <button type="button" class="btn btn-link p-0 align-baseline" onclick="document.querySelector('[data-title=\'Import built-in payment providers?\']').click()">Import built-in providers</button>.
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
