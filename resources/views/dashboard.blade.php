@extends('layouts.app-custom')

@php
    use App\Models\User;

    $currentUser = auth()->user();
    $isAdmin = $currentUser->role === User::ROLE_ADMIN;
    $roleLabel = $currentUser->role === User::ROLE_USER ? 'Member' : ucfirst($currentUser->role);

    $formattedRevenue = '$'.number_format((float) ($totalRevenue ?? 0), 2);

    $membersTrend = [
        max((int) $totalMembers - 8, 0),
        max((int) $totalMembers - 5, 0),
        max((int) $totalMembers - 3, 0),
        max((int) $totalMembers - 2, 0),
        max((int) $totalMembers - 1, 0),
        (int) $totalMembers,
        (int) $totalMembers + 2,
    ];

    $editorsTrend = [
        max((int) $totalEditors - 3, 0),
        max((int) $totalEditors - 2, 0),
        max((int) $totalEditors - 2, 0),
        max((int) $totalEditors - 1, 0),
        (int) $totalEditors,
        (int) $totalEditors,
        (int) $totalEditors + 1,
    ];

    $chitsTrend = [
        max((int) $totalChits - 5, 0),
        max((int) $totalChits - 4, 0),
        max((int) $totalChits - 2, 0),
        max((int) $totalChits - 1, 0),
        (int) $totalChits,
        (int) $totalChits + 1,
        (int) $totalChits + 2,
    ];
@endphp

@section('title', __('Dashboard'))
@section('header', __('Dashboard'))

@section('content')
    <section class="section dashboard">
        @if ($isAdmin)
            <div class="row">
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Chits <span>| Current</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-grid"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $totalChits }}</h6>
                                    <span class="text-success small pt-1 fw-bold">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card customers-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Members <span>| Current</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $totalMembers }}</h6>
                                    <span class="text-success small pt-1 fw-bold">Growing</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card revenue-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Editors <span>| Current</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $totalEditors }}</h6>
                                    <span class="text-success small pt-1 fw-bold">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card revenue-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Revenue <span>| Overall</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ $formattedRevenue }}</h6>
                                    <span class="text-primary small pt-1 fw-bold">Committee</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Reports <span>/Last 7 points</span></h5>
                            <div id="reportsChart"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body pb-0">
                            <h5 class="card-title">Recent Activity <span>| Today</span></h5>

                            <div class="activity">
                                <div class="activity-item d-flex">
                                    <div class="activite-label">32 min</div>
                                    <i class='bi bi-circle-fill activity-badge text-success align-self-start'></i>
                                    <div class="activity-content">New member profile approved by admin.</div>
                                </div>

                                <div class="activity-item d-flex">
                                    <div class="activite-label">56 min</div>
                                    <i class='bi bi-circle-fill activity-badge text-warning align-self-start'></i>
                                    <div class="activity-content">Editor profile is pending verification.</div>
                                </div>

                                <div class="activity-item d-flex">
                                    <div class="activite-label">2 hrs</div>
                                    <i class='bi bi-circle-fill activity-badge text-primary align-self-start'></i>
                                    <div class="activity-content">Chit cycle updated for Group A-12.</div>
                                </div>

                                <div class="activity-item d-flex">
                                    <div class="activite-label">1 day</div>
                                    <i class='bi bi-circle-fill activity-badge text-info align-self-start'></i>
                                    <div class="activity-content">System log archive completed successfully.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Budget Split <span>| This Month</span></h5>

                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 64%" aria-valuenow="64" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-3">Operations 64%</p>

                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 48%" aria-valuenow="48" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-3">Reserve 48%</p>

                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 39%" aria-valuenow="39" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-0">Member Welfare 39%</p>
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

@if ($isAdmin)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const chartElement = document.querySelector('#reportsChart');

                if (!chartElement || typeof ApexCharts === 'undefined') {
                    return;
                }

                new ApexCharts(chartElement, {
                    series: [
                        {
                            name: 'Members',
                            data: @json($membersTrend),
                        },
                        {
                            name: 'Editors',
                            data: @json($editorsTrend),
                        },
                        {
                            name: 'Chits',
                            data: @json($chitsTrend),
                        },
                    ],
                    chart: {
                        height: 350,
                        type: 'area',
                        toolbar: {
                            show: false,
                        },
                    },
                    markers: {
                        size: 4,
                    },
                    colors: ['#4154f1', '#2eca6a', '#ff771d'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.3,
                            opacityTo: 0.4,
                            stops: [0, 90, 100],
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                    },
                    xaxis: {
                        categories: ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'],
                    },
                    tooltip: {
                        shared: true,
                    },
                }).render();
            });
        </script>
    @endpush
@endif
