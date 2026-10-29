@php
  $user = auth()->user();

  $salesOrdersActive = request()->routeIs('sales.*', 'customer-orders.*', 'debts.*', 'cake-point.*', 'day-closes.*', 'expenses.*');
  $reportsActive = request()->routeIs('day-reports.*', 'business-trends.*');
  $customersSmsActive = request()->routeIs('customers.*', 'sms.*');
  $inventoryActive = request()->routeIs('ingredients.*', 'receivings.*', 'stock.*', 'ingredient-usages.*');
  $catalogActive = request()->routeIs('items.*');
  $adminActive = request()->routeIs('roles.*', 'staff.*', 'audit-logs.*', 'settings.*');

  $canViewOrders = $user->hasPermission('orders.view');
  $canViewExpenses = $user->hasPermission('expenses.view') || $user->hasPermission('expenses.manage');
  $canViewCustomers = $user->hasPermission('customers.view');
  $canViewSms = $user->canViewSms();
  $canViewInventory = $user->hasPermission('inventory.view');
  $canViewReports = $user->hasPermission('reports.view');
  $canViewCatalog = $user->hasPermission('catalog.view') || $user->hasPermission('catalog.manage');
  $canViewStaff = $user->hasPermission('staff.view') || $user->hasPermission('staff.manage');
  $canManageRoles = $user->hasPermission('roles.manage');
  $canViewAudit = $user->hasPermission('audit.view');
  $canViewSettings = $user->hasPermission('settings.view') || $user->hasPermission('settings.manage');
  $isOwner = $user->isOwner();
  $canViewFinancials = $isOwner || $canViewReports;
  $canViewDebts = $canViewFinancials || $user->hasPermission('orders.create');

  $showSalesOrders = $canViewOrders || $canViewExpenses;
  $showCustomersSms = $canViewCustomers || $canViewSms;
  $showReports = $canViewReports;
  $showCatalog = $canViewCatalog;
  $showAdministration = $canViewStaff || $canManageRoles || $canViewAudit || $canViewSettings || $isOwner;

  $userInitial = mb_strtoupper(mb_substr(trim($user->name ?: 'U'), 0, 1));
@endphp

<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar">
  <div class="app-sidebar__user">
    <div class="app-sidebar__user-avatar" aria-hidden="true">{{ $userInitial }}</div>
    <div>
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
    <li>
      <a class="app-menu__item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="app-menu__icon fa fa-dashboard"></i>
        <span class="app-menu__label">Dashboard</span>
      </a>
    </li>

    @if($canViewInventory)
      <li class="treeview {{ $inventoryActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-cubes"></i>
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
          <li>
            <a class="treeview-item {{ request()->routeIs('ingredient-usages.*') ? 'active' : '' }}" href="{{ route('ingredient-usages.index') }}">
              <i class="fa fa-flask"></i> Usage log
            </a>
          </li>
        </ul>
      </li>
    @endif

    @if($showSalesOrders)
      @php
        $cakePointMenuCount = (int) (collect($appNotifications ?? [])->firstWhere('icon', 'fa-birthday-cake')['count'] ?? 0);
      @endphp
      <li class="treeview {{ $salesOrdersActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-shopping-cart"></i>
          <span class="app-menu__label">Sales & Orders</span>
          @if($cakePointMenuCount > 0)
            <span class="app-menu__badge">{{ $cakePointMenuCount > 9 ? '9+' : $cakePointMenuCount }}</span>
          @endif
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          @if($canViewOrders)
            <li>
              <a class="treeview-item {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                <i class="fa fa-list"></i> Sales
              </a>
            </li>
            <li>
              <a class="treeview-item {{ request()->routeIs('customer-orders.*') ? 'active' : '' }}" href="{{ route('customer-orders.index') }}">
                <i class="fa fa-calendar-check-o"></i> Customer Orders
              </a>
            </li>
            <li>
              <a class="treeview-item {{ request()->routeIs('cake-point.*') ? 'active' : '' }}" href="{{ route('cake-point.index') }}">
                <i class="fa fa-birthday-cake"></i> Cake Point
                @if($cakePointMenuCount > 0)
                  <span class="badge badge-warning ml-1">{{ $cakePointMenuCount > 9 ? '9+' : $cakePointMenuCount }}</span>
                @endif
              </a>
            </li>
          @endif
          @if($canViewDebts)
            <li>
              <a class="treeview-item {{ request()->routeIs('debts.*') ? 'active' : '' }}" href="{{ route('debts.index') }}">
                <i class="fa fa-credit-card"></i> Debt Management
              </a>
            </li>
          @endif
          @if($canViewExpenses)
            <li>
              <a class="treeview-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                <i class="fa fa-money"></i> Expenses
              </a>
            </li>
          @endif
          @if($canViewOrders)
            <li>
              <a class="treeview-item {{ request()->routeIs('day-closes.*') ? 'active' : '' }}" href="{{ route('day-closes.index') }}">
                <i class="fa fa-calendar-check-o"></i> Close Day
              </a>
            </li>
          @endif
        </ul>
      </li>
    @endif

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

    @if($showAdministration)
      <li class="treeview {{ $adminActive ? 'is-expanded' : '' }}">
        <a class="app-menu__item" href="#" data-toggle="treeview">
          <i class="app-menu__icon fa fa-cog"></i>
          <span class="app-menu__label">Administration</span>
          <i class="treeview-indicator fa fa-angle-right"></i>
        </a>
        <ul class="treeview-menu">
          @if($canViewStaff)
            <li>
              <a class="treeview-item {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
                <i class="fa fa-user"></i> Staff
              </a>
            </li>
          @endif
          @if($canManageRoles)
            <li>
              <a class="treeview-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                <i class="fa fa-shield"></i> Roles & Permissions
              </a>
            </li>
          @endif
          @if($canViewAudit)
            <li>
              <a class="treeview-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">
                <i class="fa fa-history"></i> Audit Logs
              </a>
            </li>
          @endif
          @if($canViewSettings)
            <li>
              <a class="treeview-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                <i class="fa fa-sliders"></i> Settings
              </a>
            </li>
          @endif
        </ul>
      </li>
    @endif
  </ul>
</aside>
