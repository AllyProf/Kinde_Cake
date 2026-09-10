@php
  $user = auth()->user();

  $salesOrdersActive = request()->routeIs('sales.*', 'debts.*', 'cake-point.*', 'day-closes.*');
  $reportsActive = request()->routeIs('day-reports.*', 'business-trends.*');
  $customersSmsActive = request()->routeIs('customers.*', 'sms.*');
  $inventoryActive = request()->routeIs('ingredients.*', 'receivings.*', 'stock.*');
  $catalogActive = request()->routeIs('items.*');
  $adminActive = request()->routeIs('roles.*', 'staff.*', 'audit-logs.*', 'settings.*');

  $canViewOrders = $user->hasPermission('orders.view');
  $canViewCustomers = $user->hasPermission('customers.view');
  $canViewSms = $user->canViewSms();
  $canViewInventory = $user->hasPermission('inventory.view');
  $isOwner = $user->isOwner();

  $showSalesOrders = $canViewOrders;
  $showCustomersSms = $canViewCustomers || $canViewSms;
  $showReports = $isOwner;
  $showCatalog = $isOwner;
  $showAdministration = $isOwner;
@endphp

<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar">
  <div class="app-sidebar__user">
    @if($user->hasPhoto())
      <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="app-sidebar__user-avatar">
    @else
      <div class="app-sidebar__user-avatar-initials" aria-hidden="true">{{ $user->initials() }}</div>
    @endif
    <div class="app-sidebar__user-info">
      <p class="app-sidebar__user-name">{{ $user->name }}</p>
      <p class="app-sidebar__user-designation">{{ $user->roleLabel() }}</p>
      @if(\App\Models\User::isImpersonating())
        <p class="app-sidebar__impersonation-note">
          <i class="fa fa-user-secret"></i> Impersonating
        </p>
      @endif
    </div>
  </div>

  <ul class="app-menu">
    {{-- Overview --}}
    <li>
      <a class="app-menu__item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="app-menu__icon fa fa-dashboard"></i>
        <span class="app-menu__label">Dashboard</span>
      </a>
    </li>

    {{-- Sales & Orders --}}
    @if($showSalesOrders)
      <li class="treeview {{ $salesOrdersActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-shopping-cart"></i>
          <span class="app-menu__label">Sales & Orders</span>
          @if(($cakePointNotificationCount ?? 0) > 0)
            <span class="app-menu__badge">{{ $cakePointNotificationCount > 9 ? '9+' : $cakePointNotificationCount }}</span>
          @endif
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          <li>
            <a class="treeview-item {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
              <i class="fa fa-list"></i> Sales
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('cake-point.*') ? 'active' : '' }}" href="{{ route('cake-point.index') }}">
              <i class="fa fa-birthday-cake"></i> Orders
              @if(($cakePointNotificationCount ?? 0) > 0)
                <span class="badge badge-warning ml-1">{{ $cakePointNotificationCount > 9 ? '9+' : $cakePointNotificationCount }}</span>
              @endif
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('debts.*') ? 'active' : '' }}" href="{{ route('debts.index') }}">
              <i class="fa fa-credit-card"></i> Debt Management
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('day-closes.*') ? 'active' : '' }}" href="{{ route('day-closes.index') }}">
              <i class="fa fa-calendar-check-o"></i> Close Day
            </a>
          </li>
        </ul>
      </li>
    @endif

    {{-- Reports & Analytics --}}
    @if($showReports)
      <li class="treeview {{ $reportsActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-bar-chart"></i>
          <span class="app-menu__label">Reports & Analytics</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          <li>
            <a class="treeview-item {{ request()->routeIs('day-reports.*') ? 'active' : '' }}" href="{{ route('day-reports.index') }}">
              <i class="fa fa-file-text-o"></i> Reports
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('business-trends.*') ? 'active' : '' }}" href="{{ route('business-trends.index') }}">
              <i class="fa fa-line-chart"></i> Business Trends
            </a>
          </li>
        </ul>
      </li>
    @endif

    {{-- Customers & Communication --}}
    @if($showCustomersSms)
      <li class="treeview {{ $customersSmsActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-address-book"></i>
          <span class="app-menu__label">Customers & SMS</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          @if($canViewCustomers)
            <li>
              <a class="treeview-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                <i class="fa fa-users"></i> Customers
              </a>
            </li>
          @endif
          @if($canViewSms)
            <li>
              <a class="treeview-item {{ request()->routeIs('sms.*') ? 'active' : '' }}" href="{{ route('sms.index') }}">
                <i class="fa fa-commenting"></i> SMS
              </a>
            </li>
          @endif
        </ul>
      </li>
    @endif

    {{-- Inventory --}}
    @if($canViewInventory)
      <li class="treeview {{ $inventoryActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-archive"></i>
          <span class="app-menu__label">Inventory</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          <li>
            <a class="treeview-item {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}">
              <i class="fa fa-cubes"></i> Stock
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('ingredients.*') ? 'active' : '' }}" href="{{ route('ingredients.index') }}">
              <i class="fa fa-leaf"></i> Ingredients
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('receivings.*') ? 'active' : '' }}" href="{{ route('receivings.index') }}">
              <i class="fa fa-truck"></i> Receiving
            </a>
          </li>
        </ul>
      </li>
    @endif

    {{-- Product Catalog --}}
    @if($showCatalog)
      <li class="treeview {{ $catalogActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-shopping-basket"></i>
          <span class="app-menu__label">Product Catalog</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          <li>
            <a class="treeview-item {{ request()->routeIs('items.*') ? 'active' : '' }}" href="{{ route('items.index') }}">
              <i class="fa fa-tag"></i> Items
            </a>
          </li>
        </ul>
      </li>
    @endif

    {{-- Administration --}}
    @if($showAdministration)
      <li class="treeview {{ $adminActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-cog"></i>
          <span class="app-menu__label">Administration</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          <li>
            <a class="treeview-item {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
              <i class="fa fa-user"></i> Staff
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
              <i class="fa fa-shield"></i> Roles & Permissions
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">
              <i class="fa fa-history"></i> Audit Logs
            </a>
          </li>
          <li>
            <a class="treeview-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
              <i class="fa fa-sliders"></i> Settings
            </a>
          </li>
        </ul>
      </li>
    @endif
  </ul>
</aside>
