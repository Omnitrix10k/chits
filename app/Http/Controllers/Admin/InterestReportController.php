<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InterestReportController extends Controller
{
    private const COMMITTEE_INTEREST_RATE = 0.04;

    public function index(Request $request): View
    {
        $periodOptions = [
            'this_month' => 'This Month',
            'last_3_months' => 'Last 3 Months',
            'this_year' => 'This Year',
            'all_time' => 'All Time',
        ];

        $selectedPeriod = (string) $request->query('period', 'this_month');
        if (! array_key_exists($selectedPeriod, $periodOptions)) {
            $selectedPeriod = 'this_month';
        }

        $chitOptions = Chit::query()
            ->orderByRaw('COALESCE(chit_name, "") asc')
            ->orderBy('id')
            ->get(['id', 'chit_name']);

        $selectedChit = (string) $request->query('chit', 'all');
        $availableChitIds = $chitOptions->pluck('id')->map(static fn ($id): string => (string) $id)->all();
        if ($selectedChit !== 'all' && ! in_array($selectedChit, $availableChitIds, true)) {
            $selectedChit = 'all';
        }

        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($selectedPeriod);

        $baseQuery = DB::table('chit_months as cm')
            ->join('chits as c', 'c.id', '=', 'cm.chit_id')
            ->whereNotNull('cm.auction_recorded_at');

        if ($rangeStart) {
            $baseQuery->whereBetween('cm.auction_recorded_at', [$rangeStart, $rangeEnd]);
        }

        if ($selectedChit !== 'all') {
            $baseQuery->where('cm.chit_id', (int) $selectedChit);
        }

        $ledger = (clone $baseQuery)
            ->select([
                'cm.id',
                'cm.chit_id',
                'cm.month_number',
                'cm.auction_amount',
                'cm.auction_recorded_at',
                'cm.closed_at',
                'c.chit_name',
                'c.total_amount',
                'c.duration_months',
            ])
            ->orderByDesc('cm.auction_recorded_at')
            ->orderByDesc('cm.id')
            ->paginate(12)
            ->withQueryString();

        $monthlyRows = (clone $baseQuery)
            ->select(['cm.auction_recorded_at', 'c.total_amount'])
            ->orderBy('cm.auction_recorded_at')
            ->get();

        $chitRows = (clone $baseQuery)
            ->select([
                'cm.chit_id',
                'cm.auction_recorded_at',
                'c.chit_name',
                'c.total_amount',
                'c.duration_months',
            ])
            ->get();

        $monthlySummary = $this->buildMonthlySummary($monthlyRows);
        $chitTotals = $this->buildChitTotals($chitRows);

        $totalInterest = (int) $monthlySummary->sum('interest_total');
        $totalAuctionMonths = (int) $monthlyRows->count();
        $trackedChitsCount = (int) $chitTotals->count();
        $averageMonthlyInterest = $totalAuctionMonths > 0
            ? (int) floor($totalInterest / $totalAuctionMonths)
            : 0;

        $latestMonthlySummary = $monthlySummary
            ->sortByDesc('period_key')
            ->take(6)
            ->values();

        $chartLabels = $monthlySummary->pluck('period_label')->values()->all();
        $chartValues = $monthlySummary->pluck('interest_total')
            ->map(static fn ($value): int => (int) $value)
            ->values()
            ->all();

        return view('admin.interest.index', [
            'periodOptions' => $periodOptions,
            'selectedPeriod' => $selectedPeriod,
            'selectedChit' => $selectedChit,
            'chitOptions' => $chitOptions,
            'totalInterest' => $totalInterest,
            'totalAuctionMonths' => $totalAuctionMonths,
            'trackedChitsCount' => $trackedChitsCount,
            'averageMonthlyInterest' => $averageMonthlyInterest,
            'monthlySummary' => $monthlySummary,
            'latestMonthlySummary' => $latestMonthlySummary,
            'chitTotals' => $chitTotals,
            'ledger' => $ledger,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ]);
    }

    /**
     * @return array{0:\Illuminate\Support\Carbon|null,1:\Illuminate\Support\Carbon}
     */
    private function resolvePeriodRange(string $period): array
    {
        $rangeEnd = now();

        return match ($period) {
            'this_month' => [now()->copy()->startOfMonth(), $rangeEnd],
            'last_3_months' => [now()->copy()->subMonths(2)->startOfMonth(), $rangeEnd],
            'this_year' => [now()->copy()->startOfYear(), $rangeEnd],
            default => [null, $rangeEnd],
        };
    }

    /**
     * @param  Collection<int, object{auction_recorded_at:string, total_amount:int|string}>  $rows
     * @return Collection<int, array{period_key:string, period_label:string, interest_total:int, cycles_count:int}>
     */
    private function buildMonthlySummary(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->map(function (object $row): array {
                $auctionDate = Carbon::parse((string) $row->auction_recorded_at);
                $interest = $this->calculateCommitteeInterest((int) $row->total_amount);

                return [
                    'period_key' => $auctionDate->format('Y-m'),
                    'period_label' => $auctionDate->format('M Y'),
                    'interest' => $interest,
                ];
            })
            ->groupBy('period_key')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'period_key' => (string) ($first['period_key'] ?? ''),
                    'period_label' => (string) ($first['period_label'] ?? ''),
                    'interest_total' => (int) $group->sum('interest'),
                    'cycles_count' => (int) $group->count(),
                ];
            })
            ->sortBy('period_key')
            ->values();
    }

    /**
     * @param  Collection<int, object{chit_id:int, chit_name:string|null, total_amount:int|string, duration_months:int|string, auction_recorded_at:string}>  $rows
     * @return Collection<int, array{chit_id:int, chit_name:string, total_amount:int, duration_months:int, auction_months:int, interest_per_month:int, total_interest:int, last_auction_at:\Illuminate\Support\Carbon|null}>
     */
    private function buildChitTotals(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy(static fn (object $row): int => (int) $row->chit_id)
            ->map(function (Collection $group, int $chitId): array {
                $first = $group->first();
                $totalAmount = max(0, (int) ($first->total_amount ?? 0));
                $durationMonths = max(1, (int) ($first->duration_months ?? 1));
                $auctionMonths = (int) $group->count();
                $interestPerMonth = $this->calculateCommitteeInterest($totalAmount);
                $lastAuctionAt = $group
                    ->map(static fn (object $row): Carbon => Carbon::parse((string) $row->auction_recorded_at))
                    ->sortDesc()
                    ->first();

                return [
                    'chit_id' => $chitId,
                    'chit_name' => trim((string) ($first->chit_name ?? '')) !== ''
                        ? trim((string) $first->chit_name)
                        : 'Chit #'.$chitId,
                    'total_amount' => $totalAmount,
                    'duration_months' => $durationMonths,
                    'auction_months' => $auctionMonths,
                    'interest_per_month' => $interestPerMonth,
                    'total_interest' => $interestPerMonth * $auctionMonths,
                    'last_auction_at' => $lastAuctionAt,
                ];
            })
            ->sortByDesc('total_interest')
            ->values();
    }

    private function calculateCommitteeInterest(int $totalAmount): int
    {
        return (int) round(max(0, $totalAmount) * self::COMMITTEE_INTEREST_RATE);
    }
}

