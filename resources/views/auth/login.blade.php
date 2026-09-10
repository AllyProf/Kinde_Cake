@extends('layouts.auth')

@section('title', 'Login')

@section('content')
  <section class="material-half-bg">
    <div class="cover"></div>
  </section>
  <section class="login-content">
    <div class="logo">
      <h1>{{ config('app.name', 'Kinde Cake') }}</h1>
    </div>
    <div class="login-box">
      <form class="login-form" action="{{ route('login') }}" method="POST">
        @csrf
        <h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>SIGN IN</h3>

        @if($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="form-group">
          <label class="control-label">EMAIL</label>
          <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="Email" autofocus required>
          @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="control-label">PASSWORD</label>
          <div class="input-group">
            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password"
              id="loginPassword" placeholder="Password" required autocomplete="current-password">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="#loginPassword"
                aria-label="Show password">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>
          @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <div class="utility">
            <div class="animated-checkbox">
              <label>
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}><span class="label-text">Stay Signed in</span>
              </label>
            </div>
          </div>
        </div>
        <div class="form-group btn-container">
          <button class="btn btn-primary btn-block" type="submit" data-loading-text="Signing in...">
            <i class="fa fa-sign-in fa-lg fa-fw"></i>SIGN IN
          </button>
        </div>
      </form>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (function () {
      function updateToggleIcon(input) {
        var btn = document.querySelector('.js-toggle-password[data-target="#' + input.id + '"]');
        if (!btn) return;
        var icon = btn.querySelector('i');
        if (!icon) return;
        icon.className = input.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
      }

      document.querySelectorAll('.js-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = document.querySelector(btn.getAttribute('data-target'));
          if (!target) return;
          target.type = target.type === 'password' ? 'text' : 'password';
          updateToggleIcon(target);
        });
      });

      $('.login-content [data-toggle="flip"]').click(function() {
        $('.login-box').toggleClass('flipped');
        return false;
      });
    })();
  </script>
@endpush
