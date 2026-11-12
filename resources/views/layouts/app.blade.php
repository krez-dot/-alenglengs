@php
    use App\Support\Ui;
    $navItems = [
        ['label' => 'Checkout', 'icon' => 'bag', 'route' => 'pos.index', 'match' => 'checkout*', 'badge' => 'POS'],
        ['label' => 'History', 'icon' => 'file', 'route' => 'sales.index', 'match' => 'sales*'],
        ['label' => 'Products', 'icon' => 'hexagon', 'route' => 'products.index', 'match' => 'products*', 'role' => 'admin'],
        ['label' => 'Categories', 'icon' => 'tag', 'route' => 'categories.index', 'match' => 'categories*', 'role' => 'admin'],
        ['label' => 'Suppliers', 'icon' => 'truck', 'route' => 'suppliers.index', 'match' => 'suppliers*', 'role' => 'admin'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) {}
        })();
    </script>
    <title>@yield('title', 'Aling Leng') - Aling Leng's Sari-Sari Store</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="appSidebar">
        <a href="{{ route('products.index') }}" class="brand">
            <span class="brand-icon">{{ Ui::navIcon('bag') }}</span>
            <span class="brand-text">
                <span class="brand-name">Aling Leng's</span>
                <span class="brand-sub">Sari-Sari Store POS</span>
            </span>
        </a>
        <nav>
            <ul class="nav-list">
                @foreach ($navItems as $item)
                    @if (empty($item['role']) || auth()->user()->hasRole($item['role']))
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="nav-link {{ request()->is($item['match']) ? 'active' : '' }}">
                            {{ Ui::navIcon($item['icon']) }}
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))<span class="nav-badge">{{ $item['badge'] }}</span>@endif
                        </a>
                    </li>
                    @endif
                @endforeach
            </ul>
        </nav>
        <div class="sidebar-footer">
            <button type="button" class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                <span id="themeIcon">&#127769;</span>
                <span id="themeLabel">Dark Mode</span>
            </button>
            <div class="user-card">
                <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ ucfirst(auth()->user()->role) }}</span>
                </span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="user-logout">Log out</button>
                </form>
            </div>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="main-content">
        <div class="mobile-topbar">
            <button type="button" class="icon-btn" id="sidebarToggle" aria-label="Toggle menu">{{ Ui::navIcon('menu') }}</button>
            <span class="brand-name">Aling Leng's</span>
        </div>

        @foreach (['success', 'danger'] as $type)
            @if (session($type))
                <div class="alert alert-{{ $type }}">
                    <span>{{ session($type) }}</span>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>
</div>
<script>
(function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    function close() { sidebar.classList.remove('open'); backdrop.classList.remove('open'); }
    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('open');
        });
    }
    if (backdrop) { backdrop.addEventListener('click', close); }
})();

(function () {
    var toggle = document.getElementById('themeToggle');
    var icon = document.getElementById('themeIcon');
    var label = document.getElementById('themeLabel');
    if (!toggle) return;
    function isDark() {
        var current = document.documentElement.getAttribute('data-theme');
        if (current === 'dark') return true;
        if (current === 'light') return false;
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    function render() {
        var dark = isDark();
        icon.textContent = dark ? '☀️' : '🌙';
        label.textContent = dark ? 'Light Mode' : 'Dark Mode';
    }
    toggle.addEventListener('click', function () {
        var next = isDark() ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        render();
    });
    render();
})();
</script>
@stack('scripts')
</body>
</html>
