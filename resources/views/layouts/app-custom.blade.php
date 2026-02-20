@php
    use App\Models\User;

    $currentUser = auth()->user();
    $isAdmin = $currentUser?->role === User::ROLE_ADMIN;
    $isEditor = $currentUser?->role === User::ROLE_EDITOR;
    $hasManagementAccess = $isAdmin || $isEditor;
    $canManageMutations = $isAdmin;
    $roleLabel = $currentUser
        ? ($currentUser->role === User::ROLE_USER ? 'Member' : ucfirst($currentUser->role))
        : 'Guest';

    $pageHeading = trim($__env->yieldContent('header'));

    if ($pageHeading === '') {
        $pageHeading = trim($__env->yieldContent('title')) ?: 'Dashboard';
    }

    $membersMenuOpen = request()->routeIs('admin.members.*');
    $editorsMenuOpen = request()->routeIs('admin.editors.*');
    $chitsMenuOpen = request()->routeIs('admin.chits.*');
    $interestMenuOpen = request()->routeIs('admin.interest.*');
    $cssVersion = file_exists(public_path('niceadmin/assets/css/goud-custom.css'))
        ? filemtime(public_path('niceadmin/assets/css/goud-custom.css'))
        : time();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="{{ asset('niceadmin/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('niceadmin/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <link href="{{ asset('niceadmin/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <link href="{{ asset('niceadmin/assets/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('niceadmin/assets/css/goud-custom.css') }}?v={{ $cssVersion }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="antialiased">
    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between">
            <i class="bi bi-list toggle-sidebar-btn" aria-label="Toggle sidebar"></i>
        </div>

        <div class="search-bar">
            <form class="search-form d-flex align-items-center" method="GET" action="{{ route('dashboard') }}">
                @if (request()->routeIs('dashboard') && request()->filled('period'))
                    <input type="hidden" name="period" value="{{ request()->query('period') }}">
                @endif
                <input
                    type="text"
                    name="query"
                    value="{{ request()->routeIs('dashboard') ? request()->query('query', '') : '' }}"
                    placeholder="Search"
                    title="Enter search keyword"
                    autocomplete="off"
                >
                <button type="submit" title="Search"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle" href="#">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge bg-primary badge-number">4</span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
                        <li class="dropdown-header">
                            You have 4 new notifications
                            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="notification-item">
                            <i class="bi bi-check-circle text-success"></i>
                            <div>
                                <h4>System</h4>
                                <p>Dashboard is updated and active.</p>
                                <p>Just now</p>
                            </div>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="{{ $currentUser?->profile_image_url ?? asset('images/default-avatar.svg') }}" alt="Profile" class="rounded-circle">
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ $currentUser?->name ?? 'User' }}</span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6>{{ $currentUser?->name ?? 'User' }}</h6>
                            <span>{{ $roleLabel }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear"></i>
                                <span>Account Settings</span>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center w-100 border-0 bg-transparent text-start goud-logout-dropdown-btn">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Log Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <aside id="sidebar" class="sidebar">
        <div class="goud-sidebar-brand-wrap">
            <a href="{{ route('dashboard') }}" class="logo d-flex align-items-center">
                <span class="goud-sidebar-mark">GS</span>
                <span class="d-lg-block">Goud Sangam</span>
            </a>
        </div>

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? '' : 'collapsed' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            @if ($hasManagementAccess)
                <li class="nav-item">
                    <a class="nav-link {{ $chitsMenuOpen ? '' : 'collapsed' }}" href="{{ route('admin.chits.index') }}">
                        <i class="bi bi-journal-text"></i>
                        <span>Chits</span>
                    </a>
                </li>

                @if ($canManageMutations)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.chits.create') ? '' : 'collapsed' }}" href="{{ route('admin.chits.create') }}">
                            <i class="bi bi-plus-circle"></i>
                            <span>Create Chit</span>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link {{ $interestMenuOpen ? '' : 'collapsed' }}" href="{{ route('admin.interest.index') }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Interest</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $membersMenuOpen ? '' : 'collapsed' }}" data-bs-target="#members-nav" data-bs-toggle="collapse" href="#">
                        <i class="bi bi-people"></i><span>Members</span><i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="members-nav" class="nav-content collapse {{ $membersMenuOpen ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                        <li>
                            <a class="{{ request()->routeIs('admin.members.index') ? 'active' : '' }}" href="{{ route('admin.members.index') }}">
                                <i class="bi bi-circle"></i><span>All Members</span>
                            </a>
                        </li>
                        @if ($canManageMutations)
                            <li>
                                <a class="{{ request()->routeIs('admin.members.create') ? 'active' : '' }}" href="{{ route('admin.members.create') }}">
                                    <i class="bi bi-circle"></i><span>Add Member</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

                @if ($isAdmin)
                    <li class="nav-item">
                        <a class="nav-link {{ $editorsMenuOpen ? '' : 'collapsed' }}" data-bs-target="#editors-nav" data-bs-toggle="collapse" href="#">
                            <i class="bi bi-person-badge"></i><span>Editors</span><i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="editors-nav" class="nav-content collapse {{ $editorsMenuOpen ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                            <li>
                                <a class="{{ request()->routeIs('admin.editors.index') ? 'active' : '' }}" href="{{ route('admin.editors.index') }}">
                                    <i class="bi bi-circle"></i><span>All Editors</span>
                                </a>
                            </li>
                            <li>
                                <a class="{{ request()->routeIs('admin.editors.create') ? 'active' : '' }}" href="{{ route('admin.editors.create') }}">
                                    <i class="bi bi-circle"></i><span>Add Editor</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.system-logs.index') ? '' : 'collapsed' }}" href="{{ route('admin.system-logs.index') }}">
                            <i class="bi bi-clipboard-data"></i>
                            <span>System Logs</span>
                        </a>
                    </li>
                @endif
            @else
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profile.edit') ? '' : 'collapsed' }}" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person"></i>
                        <span>Account Settings</span>
                    </a>
                </li>
            @endif

            <li class="nav-item mt-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link collapsed w-100 border-0 bg-transparent text-start">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>{{ $pageHeading }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">{{ $pageHeading }}</li>
                </ol>
            </nav>
        </div>

        @yield('content')
    </main>

    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; {{ now()->year }} <strong><span>Goud Sangam</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
            @if ($isAdmin)
                <a href="{{ route('admin.chits.index') }}">Chits</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.interest.index') }}">Interest</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.members.create') }}">Add Member</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.editors.create') }}">Add Editor</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.system-logs.index') }}">System Logs</a>
            @elseif ($isEditor)
                <a href="{{ route('admin.chits.index') }}">Chits</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.members.index') }}">Members</a>
                &nbsp;|&nbsp;
                <a href="{{ route('admin.interest.index') }}">Interest</a>
            @else
                <a href="{{ route('profile.edit') }}">Account Settings</a>
            @endif
        </div>
    </footer>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="{{ asset('niceadmin/assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/quill/quill.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('niceadmin/assets/vendor/php-email-form/validate.js') }}"></script>

    @stack('scripts')
    <script src="{{ asset('niceadmin/assets/js/main.js') }}"></script>
</body>
</html>
