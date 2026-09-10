<div class="row">
  <div class="col-md-12">
    <div class="tile settings-panel">
      <div class="tile-title-w-btn">
        <h3 class="title">Categories</h3>
        <p>
          <form action="{{ route('settings.categories.import') }}" method="POST" class="d-inline js-swal-confirm settings-import-form">
            @csrf
            <button type="submit" class="btn btn-secondary icon-btn"
              data-title="Import built-in categories?"
              data-text="This will add the default cake categories to your list. You can remove any you don't need."
              data-confirm="Yes, import"
              data-cancel="Cancel">
              <i class="fa fa-download"></i> Import Built-in Categories
            </button>
          </form>
        </p>
      </div>
      <div class="tile-body">
        <div class="row mb-4 settings-add-section">
          <div class="col-12 col-lg-8">
            <h5 class="mb-3">Add custom category</h5>
            <form action="{{ route('settings.categories.store') }}" method="POST" class="row settings-add-form">
              @csrf
              <div class="col-12 col-md-5">
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                  placeholder="Category name" value="{{ old('name') }}" required maxlength="120">
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-12 col-md-5">
                <input type="text" class="form-control" name="description" placeholder="Description (optional)"
                  value="{{ old('description') }}" maxlength="500">
              </div>
              <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Add</button>
              </div>
            </form>
          </div>
          <div class="col-12 col-lg-4">
            <h5 class="mb-3">Built-in available</h5>
            <div class="small text-muted settings-builtin-badges">
              @foreach($builtinCategories as $item)
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
                <th>Description</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
                <tr class="settings-data-row">
                  <td data-label="Name"><strong>{{ $category->name }}</strong></td>
                  <td data-label="Description">{{ $category->description ?: '—' }}</td>
                  <td data-label="Type">
                    @if($category->is_builtin)
                      <span class="badge badge-info">Built-in</span>
                    @else
                      <span class="badge badge-secondary">Custom</span>
                    @endif
                  </td>
                  <td data-label="Status">
                    @if($category->is_active)
                      <span class="badge badge-success">Active</span>
                    @else
                      <span class="badge badge-secondary">Inactive</span>
                    @endif
                  </td>
                  <td class="settings-list-actions" data-label="Actions">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#edit-category-{{ $category->id }}">
                      <i class="fa fa-edit"></i> Edit
                    </button>
                    <form action="{{ route('settings.categories.destroy', $category) }}" method="POST" class="d-inline js-swal-delete">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger" data-title="Delete category?" data-text="Remove {{ $category->name }}?">
                        <i class="fa fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <tr class="collapse settings-edit-row" id="edit-category-{{ $category->id }}">
                  <td colspan="5">
                    <form action="{{ route('settings.categories.update', $category) }}" method="POST" class="row align-items-end settings-edit-form">
                      @csrf
                      @method('PUT')
                      <div class="col-12 col-md-4">
                        <label class="control-label small">Name</label>
                        <input type="text" class="form-control" name="name" value="{{ $category->name }}" required maxlength="120">
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="control-label small">Description</label>
                        <input type="text" class="form-control" name="description" value="{{ $category->description }}" maxlength="500">
                      </div>
                      <div class="col-12 col-sm-6 col-md-2">
                        <label class="mb-0">
                          <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }}> Active
                        </label>
                      </div>
                      <div class="col-12 col-sm-6 col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Save</button>
                      </div>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    No categories yet. Click <strong>Import Built-in Categories</strong> or add a custom one.
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
