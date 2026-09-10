@if(\App\Models\User::isImpersonating())
  <div class="impersonation-banner">
    <div class="impersonation-banner__content">
      <i class="fa fa-user-secret"></i>
      <span>
        Viewing as <strong>{{ auth()->user()->name }}</strong>
        ({{ auth()->user()->roleLabel() }})
      </span>
    </div>
    <form action="{{ route('impersonate.stop') }}" method="POST" class="mb-0">
      @csrf
      <button type="submit" class="btn btn-sm btn-light">
        <i class="fa fa-sign-out"></i> Exit impersonation
      </button>
    </form>
  </div>
@endif
