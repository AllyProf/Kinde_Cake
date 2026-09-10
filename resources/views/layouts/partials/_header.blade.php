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
        @if(($cakePointNotificationCount ?? 0) > 0)
          <span class="app-nav__badge">{{ $cakePointNotificationCount > 9 ? '9+' : $cakePointNotificationCount }}</span>
        @endif
      </a>
      <ul class="app-notification dropdown-menu dropdown-menu-right">
        <li class="app-notification__title">
          @if(($cakePointNotificationCount ?? 0) > 0)
            You have {{ $cakePointNotificationCount }} new {{ Str::plural('order', $cakePointNotificationCount) }}.
          @elseif(($cakePointNotifications ?? collect())->isNotEmpty())
            Recent orders
          @else
            No new notifications.
          @endif
        </li>
        <div class="app-notification__content">
          @forelse($cakePointNotifications ?? [] as $notification)
            <li>
              <a class="app-notification__item" href="{{ route('cake-point.index', ['view' => auth()->user()->isOwner() ? 'assigned' : 'mine']) }}">
                <span class="app-notification__icon fa fa-birthday-cake text-warning"></span>
                <div>
                  <p class="app-notification__message">
                    <strong>{{ $notification->sale_number }}</strong>
                    · {{ $notification->customer_name ?: 'Walk-in' }}
                  </p>
                  <p class="app-notification__meta">{{ $notification->itemsSummary() }}</p>
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
        @if(auth()->user()?->hasPermission('orders.view'))
          <li class="app-notification__footer">
            <a href="{{ route('cake-point.index') }}">View orders</a>
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
