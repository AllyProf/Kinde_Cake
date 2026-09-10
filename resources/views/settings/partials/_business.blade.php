<div class="row">
  <div class="col-lg-7">
    <div class="tile">
      <h3 class="tile-title">Business Profile</h3>
      <div class="tile-body">
        <form action="{{ route('settings.business.update') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="form-group">
            <label class="control-label">Business name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('business_name') is-invalid @enderror" name="business_name"
              value="{{ old('business_name', $business['name']) }}" required>
            @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="control-label">Tagline</label>
            <input type="text" class="form-control @error('business_tagline') is-invalid @enderror" name="business_tagline"
              value="{{ old('business_tagline', $business['tagline']) }}" placeholder="Fresh cakes & sweet moments">
            @error('business_tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Phone</label>
                <input type="text" class="form-control @error('business_phone') is-invalid @enderror" name="business_phone"
                  value="{{ old('business_phone', $business['phone']) }}">
                @error('business_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Email</label>
                <input type="email" class="form-control @error('business_email') is-invalid @enderror" name="business_email"
                  value="{{ old('business_email', $business['email']) }}">
                @error('business_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="control-label">Address</label>
            <textarea class="form-control @error('business_address') is-invalid @enderror" name="business_address" rows="2">{{ old('business_address', $business['address']) }}</textarea>
            @error('business_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="control-label">City</label>
            <input type="text" class="form-control @error('business_city') is-invalid @enderror" name="business_city"
              value="{{ old('business_city', $business['city']) }}">
            @error('business_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="tile-footer px-0 pb-0">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-check-circle"></i> Save Business Profile
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="tile">
      <h3 class="tile-title">Your Business</h3>
      <div class="tile-body">
        <div class="p-3 rounded mb-3" style="background: var(--brand); color: var(--white);">
          <h4 class="mb-1">{{ $business['name'] ?: config('app.name') }}</h4>
          <p class="mb-0 small">{{ $business['tagline'] ?: 'Your business tagline appears here' }}</p>
        </div>
        <p class="mb-1"><i class="fa fa-phone text-muted"></i> {{ $business['phone'] ?: '—' }}</p>
        <p class="mb-1"><i class="fa fa-envelope text-muted"></i> {{ $business['email'] ?: '—' }}</p>
        <p class="mb-1"><i class="fa fa-map-marker text-muted"></i> {{ $business['address'] ?: '—' }}</p>
        <p class="mb-0"><i class="fa fa-building text-muted"></i> {{ $business['city'] ?: '—' }}</p>
      </div>
    </div>
  </div>
</div>
