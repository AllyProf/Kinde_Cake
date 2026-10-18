<header class="app-header">
  <a class="app-header__logo" href="{{ route('dashboard') }}">{{ config('app.name', 'Kinde Cake') }}</a>
  <a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
  <ul class="app-nav">
    <li class="app-search">
      <input class="app-search__input" type="search" placeholder="Search">
      <button class="app-search__button"><i class="fa fa-search"></i></button>
    </li>
    <li class="dropdown">
      <a class="app-nav__item app-nav__item--notify" href="#" data-toggle="dropdown" aria-label="Show notifications">
        <i class="fa fa-bell-o fa-lg"></i>
        @if(($appNotificationCount ?? 0) > 0)
          <span class="app-nav__badge">{{ $appNotificationCount > 9 ? '9+' : $appNotificationCount }}</span>
        @endif
      </a>
      <ul class="app-notification dropdown-menu dropdown-menu-right">
        <li class="app-notification__title">
          @if(($appNotificationCount ?? 0) > 0)
            You have {{ $appNotificationCount }} {{ Str::plural('notification', $appNotificationCount) }}.
          @else
            No new notifications.
          @endif
        </li>
        <div class="app-notification__content">
          @forelse($appNotifications ?? [] as $notification)
            @php
              $typeColor = match ($notification['type'] ?? 'info') {
                'danger' => 'text-danger',
                'warning' => 'text-warning',
                'success' => 'text-success',
                default => 'text-info',
              };
              $icon = $notification['icon'] ?? 'fa-bell-o';
            @endphp
            <li>
              <a class="app-notification__item" href="{{ $notification['link'] ?? route('dashboard') }}">
                <span class="app-notification__icon fa {{ $icon }} {{ $typeColor }}"></span>
                <div>
                  <p class="app-notification__message">{{ $notification['message'] }}</p>
                  @if(! empty($notification['label']))
                    <p class="app-notification__meta">{{ $notification['label'] }}</p>
                  @endif
                </div>
              </a>
            </li>
          @empty
            <li class="text-center py-3 text-muted">
              <i class="fa fa-check-circle-o fa-2x d-block mb-2"></i>
              All caught up!
            </li>
          @endforelse
        </div>
        @if(($appNotificationCount ?? 0) > 0)
          <li class="app-notification__footer">
            <a href="{{ route('dashboard') }}">Open dashboard</a>
          </li>
        @endif
      </ul>
    </li>
    <li class="dropdown">
      <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu">
        <i class="fa fa-user fa-lg"></i>
      </a>
      <ul class="dropdown-menu settings-menu dropdown-menu-right">
        @if(\App\Models\User::isImpersonating())
          <li>
            <form action="{{ route('impersonate.stop') }}" method="POST">
              @csrf
              <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-left">
                <i class="fa fa-user-secret fa-lg"></i> Exit impersonation
              </button>
            </form>
          </li>
        @endif
        <li>
          <a class="dropdown-item" href="{{ route('profile.edit') }}">
            <i class="fa fa-user fa-lg"></i> Profile
          </a>
        </li>
        <li>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-left">
              <i class="fa fa-sign-out fa-lg"></i> Logout
            </button>
          </form>
        </li>
      </ul>
    </li>
  </ul>
</header>
