@extends('layouts.app-custom')

@php
    use App\Models\User;

    $currentUser = auth()->user();
    $isAdmin = $currentUser->role === User::ROLE_ADMIN;
    $isEditor = $currentUser->role === User::ROLE_EDITOR;
    $hasManagementDashboard = $isAdmin || $isEditor;
    $roleLabel = $currentUser->role === User::ROLE_USER ? 'Member' : ucfirst($currentUser->role);
    $periodLabel = $dashboardPeriodLabel ?? 'This Month';
    $periodOptions = $dashboardPeriodOptions ?? [
        'this_month' => 'This Month',
        'last_3_months' => 'Last 3 Months',
        'this_year' => 'This Year',
    ];
    $selectedPeriod = $dashboardPeriod ?? 'this_month';
    $searchQuery = trim((string) ($dashboardSearchQuery ?? request()->query('query', '')));
    $dashboardMembers = $dashboardMembers ?? null;
    $dashboardEditors = $dashboardEditors ?? null;
    $dashboardCssVersion = file_exists(public_path('css/dashboard-pro.css'))
        ? filemtime(public_path('css/dashboard-pro.css'))
        : time();

    $revenueAmount = (int) round((float) ($totalRevenue ?? 0));
    $revenueAmountRaw = (string) $revenueAmount;
    if (strlen($revenueAmountRaw) > 3) {
        $revenueLastThree = substr($revenueAmountRaw, -3);
        $revenueLeading = substr($revenueAmountRaw, 0, -3);
        $revenueLeading = (string) preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $revenueLeading);
        $formattedRevenue = $revenueLeading.','.$revenueLastThree;
    } else {
        $formattedRevenue = $revenueAmountRaw;
    }

    $kpiChartData = [
        'Chits' => (int) $totalChits,
        'Members' => (int) $totalMembers,
        'Editors' => (int) $totalEditors,
        'Revenue (Lakh ₹)' => round(((float) $totalRevenue) / 100000, 2),
    ];
@endphp

@section('title', __('Dashboard'))
@section('header', __('Dashboard'))

@push('styles')
    <link href="{{ asset('css/dashboard-pro.css') }}?v={{ $dashboardCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section dashboard">
        @if ($hasManagementDashboard)
            <div class="row g-3">
                <div class="col-12">
                    <div class="card dashboard-toolbar-card">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="card-title mb-1">Performance Dashboard <span>| {{ $periodLabel }}</span></h5>
                                <p class="small text-muted mb-0">
                                    All KPI cards and revenue use the selected period filter.
                                    @if ($searchQuery !== '')
                                        Member/editor tables are filtered by: <strong>{{ $searchQuery }}</strong>.
                                    @endif
                                </p>
                            </div>
                            <div class="filter">
                                <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-funnel"></i></a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                    <li class="dropdown-header text-start">
                                        <h6>Filter Period</h6>
                                    </li>
                                    @foreach ($periodOptions as $periodKey => $optionLabel)
                                        @php
                                            $periodLinkParams = ['period' => $periodKey];
                                            if ($searchQuery !== '') {
                                                $periodLinkParams['query'] = $searchQuery;
                                            }
                                        @endphp
                                        <li>
                                            <a class="dropdown-item @if ($selectedPeriod === $periodKey) active @endif" href="{{ route('dashboard', $periodLinkParams) }}">
                                                {{ $optionLabel }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card kpi-card kpi-card-chits">
                        <div class="card-body">
                            <div class="kpi-head">
                                <h5 class="kpi-title">Total Chits</h5>
                                <span class="kpi-period">{{ $periodLabel }}</span>
                            </div>
                            <div class="d-flex align-items-center kpi-body">
                                <div class="card-icon d-flex align-items-center justify-content-center">
                                    <i class="bi bi-grid"></i>
                                </div>
                                <div class="ps-3 kpi-content">
                                    <h6 class="kpi-value kpi-value-number">
                                        <span class="kpi-amount">{{ number_format((int) $totalChits) }}</span>
                                    </h6>
                                    <span class="kpi-meta">Active Chits</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card kpi-card kpi-card-members">
                        <div class="card-body">
                            <div class="kpi-head">
                                <h5 class="kpi-title">Total Members</h5>
                                <span class="kpi-period">{{ $periodLabel }}</span>
                            </div>
                            <div class="d-flex align-items-center kpi-body">
                                <div class="card-icon d-flex align-items-center justify-content-center">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ps-3 kpi-content">
                                    <h6 class="kpi-value kpi-value-number">
                                        <span class="kpi-amount">{{ number_format((int) $totalMembers) }}</span>
                                    </h6>
                                    <span class="kpi-meta">Enrolled Members</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card kpi-card kpi-card-editors">
                        <div class="card-body">
                            <div class="kpi-head">
                                <h5 class="kpi-title">Total Editors</h5>
                                <span class="kpi-period">{{ $periodLabel }}</span>
                            </div>
                            <div class="d-flex align-items-center kpi-body">
                                <div class="card-icon d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div class="ps-3 kpi-content">
                                    <h6 class="kpi-value kpi-value-number">
                                        <span class="kpi-amount">{{ number_format((int) $totalEditors) }}</span>
                                    </h6>
                                    <span class="kpi-meta">Active Editors</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card kpi-card kpi-card-revenue">
                        <div class="card-body">
                            <div class="kpi-head">
                                <h5 class="kpi-title">Total Revenue</h5>
                                <span class="kpi-period">{{ $periodLabel }}</span>
                            </div>
                            <div class="d-flex align-items-center kpi-body">
                                <div class="card-icon d-flex align-items-center justify-content-center">
                                    <i class="bi bi-currency-rupee"></i>
                                </div>
                                <div class="ps-3 kpi-content">
                                    <h6 class="kpi-value kpi-value-money">
                                        <span class="kpi-currency">₹</span>
                                        <span class="kpi-amount">{{ $formattedRevenue }}</span>
                                    </h6>
                                    <span class="kpi-meta">Revenue Collected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card insight-card">
                        <div class="card-body">
                            <h5 class="card-title">KPI Radar <span>| {{ $periodLabel }}</span></h5>
                            <div id="kpiRadarChart" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card insight-card">
                        <div class="card-body">
                            <h5 class="card-title">KPI Distribution <span>| {{ $periodLabel }}</span></h5>
                            <div id="kpiPieChart" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xxl-7">
                    <div class="card dashboard-table-card">
                        <div class="card-body">
                            <div class="dashboard-table-head">
                                <h5 class="card-title mb-0">Member Details <span>| {{ $periodLabel }}</span></h5>
                                <span class="dashboard-table-badge">
                                    {{ $dashboardMembers ? number_format((int) $dashboardMembers->total()) : 0 }} Results
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle dashboard-data-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Referred By</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dashboardMembers ?? [] as $member)
                                            @php
                                                $displayName = trim((string) ($member->name ?: trim(($member->first_name ?? '').' '.($member->last_name ?? ''))));
                                                if ($displayName === '') {
                                                    $displayName = 'Member #'.$member->id;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ (int) (($dashboardMembers->firstItem() ?? 1) + $loop->index) }}</td>
                                                <td>{{ $displayName }}</td>
                                                <td>{{ $member->email ?: 'Not set' }}</td>
                                                <td>{{ $member->mobile_number ?: $member->primary_phone ?: 'Not set' }}</td>
                                                <td>{{ $member->referred_by_name ?: 'No one' }}</td>
                                                <td>{{ optional($member->created_at)->format('d M Y') ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    No members found for the selected filters.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($dashboardMembers && $dashboardMembers->hasPages())
                                <div class="dashboard-table-pagination">
                                    {{ $dashboardMembers->appends(['period' => $selectedPeriod, 'query' => $searchQuery])->links('pagination::bootstrap-5') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xxl-5">
                    <div class="card dashboard-table-card">
                        <div class="card-body">
                            <div class="dashboard-table-head">
                                <h5 class="card-title mb-0">Editor Details <span>| {{ $periodLabel }}</span></h5>
                                <span class="dashboard-table-badge">
                                    {{ $dashboardEditors ? number_format((int) $dashboardEditors->total()) : 0 }} Results
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle dashboard-data-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dashboardEditors ?? [] as $editor)
                                            @php
                                                $editorName = trim((string) ($editor->name ?: trim(($editor->first_name ?? '').' '.($editor->last_name ?? ''))));
                                                if ($editorName === '') {
                                                    $editorName = 'Editor #'.$editor->id;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ (int) (($dashboardEditors->firstItem() ?? 1) + $loop->index) }}</td>
                                                <td>{{ $editorName }}</td>
                                                <td>{{ $editor->email ?: 'Not set' }}</td>
                                                <td>{{ $editor->mobile_number ?: $editor->primary_phone ?: 'Not set' }}</td>
                                                <td>{{ optional($editor->created_at)->format('d M Y') ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    No editors found for the selected filters.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($dashboardEditors && $dashboardEditors->hasPages())
                                <div class="dashboard-table-pagination">
                                    {{ $dashboardEditors->appends(['period' => $selectedPeriod, 'query' => $searchQuery])->links('pagination::bootstrap-5') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Welcome <span>| {{ $roleLabel }}</span></h5>
                            <p class="mb-3">You are signed in to Goud Sangam. This dashboard uses the NiceAdmin layout now.</p>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <strong>Name</strong>
                                        <div class="text-muted">{{ $currentUser->name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <strong>Email</strong>
                                        <div class="text-muted">{{ $currentUser->email ?: 'Not set' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <strong>Mobile</strong>
                                        <div class="text-muted">{{ $currentUser->mobile_number ?: 'Not set' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection

@if ($hasManagementDashboard)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof echarts === 'undefined') {
                    return;
                }

                const radarElement = document.getElementById('kpiRadarChart');
                const pieElement = document.getElementById('kpiPieChart');
                if (!radarElement || !pieElement) {
                    return;
                }

                const rawKpiData = @json($kpiChartData);
                const kpiEntries = Object.entries(rawKpiData).map(([name, value]) => ({
                    name,
                    value: Number(value) || 0,
                }));

                const radarIndicators = kpiEntries.map((entry) => ({
                    name: entry.name,
                    max: Math.max(1, Math.ceil(entry.value * 1.25)),
                }));

                const radarChart = echarts.init(radarElement);
                radarChart.setOption({
                    color: ['#4154f1'],
                    tooltip: {
                        trigger: 'item',
                    },
                    radar: {
                        indicator: radarIndicators,
                        radius: '64%',
                        splitNumber: 5,
                        splitArea: {
                            areaStyle: {
                                color: ['#ffffff', '#f8faff'],
                            },
                        },
                        axisLine: {
                            lineStyle: {
                                color: '#d7e2f5',
                            },
                        },
                        splitLine: {
                            lineStyle: {
                                color: '#d7e2f5',
                            },
                        },
                    },
                    series: [
                        {
                            type: 'radar',
                            data: [
                                {
                                    value: kpiEntries.map((entry) => entry.value),
                                    name: 'KPI',
                                    areaStyle: {
                                        color: 'rgba(65, 84, 241, 0.18)',
                                    },
                                },
                            ],
                            symbol: 'circle',
                            symbolSize: 6,
                            lineStyle: {
                                width: 2.4,
                            },
                            itemStyle: {
                                color: '#4154f1',
                            },
                        },
                    ],
                });

                const pieChart = echarts.init(pieElement);
                const pieTotal = kpiEntries.reduce((sum, entry) => sum + entry.value, 0);
                pieChart.setOption({
                    color: ['#4154f1', '#2eca6a', '#ff771d', '#20c997'],
                    tooltip: {
                        trigger: 'item',
                    },
                    legend: {
                        bottom: 0,
                        left: 'center',
                        textStyle: {
                            color: '#4f5f78',
                            fontSize: 12,
                        },
                    },
                    series: [
                        {
                            name: 'KPI',
                            type: 'pie',
                            radius: ['44%', '72%'],
                            avoidLabelOverlap: true,
                            label: {
                                show: true,
                                formatter: '{b}',
                                fontSize: 11,
                            },
                            emphasis: {
                                label: {
                                    show: true,
                                    fontSize: 13,
                                    fontWeight: 700,
                                },
                            },
                            data: kpiEntries.map((entry) => ({
                                value: pieTotal === 0 ? 0 : entry.value,
                                name: entry.name,
                            })),
                        },
                    ],
                });

                window.addEventListener('resize', function () {
                    radarChart.resize();
                    pieChart.resize();
                });
            });
        </script>
    @endpush
@endif
