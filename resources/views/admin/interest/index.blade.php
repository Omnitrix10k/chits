@extends('layouts.app-custom')

@section('title', __('Interest Report'))
@section('header', __('Interest Report'))

@php
    $interestCssVersion = file_exists(public_path('css/interest-report.css'))
        ? filemtime(public_path('css/interest-report.css'))
        : time();

    $selectedPeriodLabel = $periodOptions[$selectedPeriod] ?? 'This Month';
    $topChit = $chitTotals->first();
@endphp

@push('styles')
    <link href="{{ asset('css/interest-report.css') }}?v={{ $interestCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section interest-report-page">
        <div class="card interest-toolbar-card mb-3">
            <div class="card-body">
                <div class="interest-toolbar-head">
                    <div>
                        <h5 class="card-title mb-1">Interest Analytics <span>| {{ $selectedPeriodLabel }}</span></h5>
                        <p class="text-muted mb-0">Track committee interest month-wise across all chits and monitor total earnings.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.interest.index') }}" class="row g-3 align-items-end mt-1">
                    <div class="col-md-4">
                        <label for="period" class="form-label">Period</label>
                        <select id="period" name="period" class="form-select">
                            @foreach ($periodOptions as $periodKey => $periodLabel)
                                <option value="{{ $periodKey }}" @selected($selectedPeriod === $periodKey)>{{ $periodLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="chit" class="form-label">Chit</label>
                        <select id="chit" name="chit" class="form-select">
                            <option value="all" @selected($selectedChit === 'all')>All Chits</option>
                            @foreach ($chitOptions as $chitOption)
                                @php
                                    $chitOptionLabel = trim((string) $chitOption->chit_name) !== ''
                                        ? trim((string) $chitOption->chit_name)
                                        : 'Chit #'.$chitOption->id;
                                @endphp
                                <option value="{{ $chitOption->id }}" @selected($selectedChit === (string) $chitOption->id)>{{ $chitOptionLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('admin.interest.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card interest-kpi-card kpi-total">
                    <div class="card-body">
                        <span class="kpi-label">Total Interest</span>
                        <h4 class="kpi-value">₹ {{ number_format((int) $totalInterest) }}</h4>
                        <p class="kpi-subtext mb-0">All selected chits and months</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card interest-kpi-card kpi-months">
                    <div class="card-body">
                        <span class="kpi-label">Auction Months</span>
                        <h4 class="kpi-value">{{ number_format((int) $totalAuctionMonths) }}</h4>
                        <p class="kpi-subtext mb-0">Months with auction completed</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card interest-kpi-card kpi-chits">
                    <div class="card-body">
                        <span class="kpi-label">Chits Tracked</span>
                        <h4 class="kpi-value">{{ number_format((int) $trackedChitsCount) }}</h4>
                        <p class="kpi-subtext mb-0">Chits contributing interest</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card interest-kpi-card kpi-average">
                    <div class="card-body">
                        <span class="kpi-label">Average Monthly Interest</span>
                        <h4 class="kpi-value">₹ {{ number_format((int) $averageMonthlyInterest) }}</h4>
                        <p class="kpi-subtext mb-0">Per auction month average</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <div class="card interest-chart-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Interest Trend <span>| {{ $selectedPeriodLabel }}</span></h5>
                        <div id="interestTrendChart" class="interest-chart-area"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card interest-highlight-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Top Earning Chit</h5>
                        @if ($topChit)
                            <h6 class="interest-top-name mb-1">{{ $topChit['chit_name'] }}</h6>
                            <p class="text-muted small mb-2">Total Value: ₹ {{ number_format((int) $topChit['total_amount']) }}</p>
                            <div class="interest-top-metric">
                                <span>Total Interest</span>
                                <strong>₹ {{ number_format((int) $topChit['total_interest']) }}</strong>
                            </div>
                            <div class="interest-top-metric">
                                <span>Auction Months</span>
                                <strong>{{ (int) $topChit['auction_months'] }}/{{ (int) $topChit['duration_months'] }}</strong>
                            </div>
                        @else
                            <p class="text-muted mb-0">No interest data available for the selected filters.</p>
                        @endif

                        <hr>

                        <h6 class="interest-mini-title">Recent Month Summary</h6>
                        @if ($latestMonthlySummary->isEmpty())
                            <p class="text-muted mb-0 small">No monthly snapshots available.</p>
                        @else
                            <ul class="interest-mini-list mb-0">
                                @foreach ($latestMonthlySummary as $monthItem)
                                    <li>
                                        <span>{{ $monthItem['period_label'] }}</span>
                                        <strong>₹ {{ number_format((int) $monthItem['interest_total']) }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Per Chit Interest Totals</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Chit</th>
                                <th>Total Value</th>
                                <th>Interest / Month</th>
                                <th>Auction Months</th>
                                <th>Total Interest</th>
                                <th>Last Auction</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($chitTotals as $chitTotal)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $chitTotal['chit_name'] }}</div>
                                        <small class="text-muted">Chit #{{ $chitTotal['chit_id'] }}</small>
                                    </td>
                                    <td>₹ {{ number_format((int) $chitTotal['total_amount']) }}</td>
                                    <td>₹ {{ number_format((int) $chitTotal['interest_per_month']) }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ (int) $chitTotal['auction_months'] }}</span>
                                        <small class="text-muted">/ {{ (int) $chitTotal['duration_months'] }}</small>
                                    </td>
                                    <td class="interest-total-cell">₹ {{ number_format((int) $chitTotal['total_interest']) }}</td>
                                    <td>
                                        @if ($chitTotal['last_auction_at'])
                                            {{ $chitTotal['last_auction_at']->format('d M Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No chit interest data found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Month-wise Interest Ledger</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Chit</th>
                                <th>Chit Month</th>
                                <th>Auction Amount</th>
                                <th>Committee Interest</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledger as $row)
                                @php
                                    $chitName = trim((string) $row->chit_name) !== ''
                                        ? trim((string) $row->chit_name)
                                        : 'Chit #'.$row->chit_id;
                                    $auctionDate = \Illuminate\Support\Carbon::parse((string) $row->auction_recorded_at);
                                    $isClosed = ! empty($row->closed_at);
                                    $committeeInterest = (int) round(((int) $row->total_amount) * 0.04);
                                @endphp
                                <tr>
                                    <td>{{ $auctionDate->format('d M Y') }}</td>
                                    <td>{{ $chitName }}</td>
                                    <td>Month {{ (int) $row->month_number }}</td>
                                    <td>₹ {{ number_format((int) $row->auction_amount) }}</td>
                                    <td class="interest-total-cell">₹ {{ number_format($committeeInterest) }}</td>
                                    <td>
                                        <span class="badge {{ $isClosed ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                            {{ $isClosed ? 'Closed' : 'Open' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No monthly interest ledger entries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($ledger->hasPages())
                    <div class="mt-3">
                        {{ $ledger->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof echarts === 'undefined') {
                return;
            }

            const chartElement = document.getElementById('interestTrendChart');
            if (!chartElement) {
                return;
            }

            const labels = @json($chartLabels);
            const values = @json($chartValues);

            const chart = echarts.init(chartElement);
            chart.setOption({
                color: ['#2f67d7'],
                tooltip: {
                    trigger: 'axis',
                    valueFormatter: function (value) {
                        const numericValue = Number(value) || 0;
                        return '₹ ' + numericValue.toLocaleString('en-IN');
                    },
                },
                grid: {
                    left: 24,
                    right: 16,
                    top: 16,
                    bottom: 24,
                    containLabel: true,
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: labels,
                    axisLine: {
                        lineStyle: {
                            color: '#d2ddf2',
                        },
                    },
                    axisLabel: {
                        color: '#677f9f',
                    },
                },
                yAxis: {
                    type: 'value',
                    axisLine: {
                        show: false,
                    },
                    splitLine: {
                        lineStyle: {
                            color: '#e5ecfa',
                        },
                    },
                    axisLabel: {
                        color: '#677f9f',
                        formatter: function (value) {
                            return '₹ ' + Number(value).toLocaleString('en-IN');
                        },
                    },
                },
                series: [{
                    name: 'Committee Interest',
                    data: values,
                    type: 'line',
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 7,
                    lineStyle: {
                        width: 3,
                    },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0,
                            y: 0,
                            x2: 0,
                            y2: 1,
                            colorStops: [{
                                offset: 0,
                                color: 'rgba(47, 103, 215, 0.28)',
                            }, {
                                offset: 1,
                                color: 'rgba(47, 103, 215, 0.02)',
                            }],
                        },
                    },
                }],
            });

            window.addEventListener('resize', function () {
                chart.resize();
            });
        });
    </script>
@endpush

