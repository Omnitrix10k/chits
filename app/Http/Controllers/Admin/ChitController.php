<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
use App\Models\ChitMonth;
use App\Models\ChitMemberPayment;
use App\Models\ChitMemberSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $plan = trim((string) $request->query('plan', 'all'));
        $type = trim((string) $request->query('type', 'all'));

        $planOptions = Chit::planOptionsByKey();
        $typeOptions = collect(Chit::TYPE_MAP)
            ->mapWithKeys(static fn (array $item): array => [$item['key'] => $item['label']])
            ->all();

        if ($plan !== 'all' && ! array_key_exists($plan, $planOptions)) {
            $plan = 'all';
        }

        if ($type !== 'all' && ! array_key_exists($type, $typeOptions)) {
            $type = 'all';
        }

        $query = Chit::query()
            ->with('creator:id,name')
            ->withCount('memberSlots as unique_members_count')
            ->withSum('memberSlots as assigned_slots', 'slot_count')
            ->latest();

        if ($plan !== 'all') {
            $planCode = Chit::planCodeFromKey($plan);
            if ($planCode) {
                $query->where('plan_code', $planCode);
            }
        }

        if ($type !== 'all') {
            $typeCode = Chit::typeCodeFromKey($type);
            if ($typeCode) {
                $query->where('chit_type_code', $typeCode);
            }
        }

        if ($search !== '') {
            if (is_numeric($search)) {
                $query->where('id', (int) $search);
            } else {
                $normalizedSearch = strtolower($search);
                $searchTerm = '%'.$search.'%';
                $matchingPlanCodes = collect(Chit::PLAN_MAP)
                    ->filter(static fn (array $item): bool => str_contains(strtolower($item['label']), $normalizedSearch))
                    ->keys()
                    ->all();

                $matchingTypeCodes = collect(Chit::TYPE_MAP)
                    ->filter(static fn (array $item): bool => str_contains(strtolower($item['label']), $normalizedSearch))
                    ->keys()
                    ->all();

                $query->where(function ($builder) use ($matchingPlanCodes, $matchingTypeCodes, $searchTerm): void {
                    $builder->where('chit_name', 'like', $searchTerm);

                    if ($matchingPlanCodes !== []) {
                        $builder->orWhereIn('plan_code', $matchingPlanCodes);
                    }
                    if ($matchingTypeCodes !== []) {
                        $builder->orWhereIn('chit_type_code', $matchingTypeCodes);
                    }
                });
            }
        }

        $chits = $query->paginate(9)->withQueryString();

        return view('admin.chits.index', [
            'chits' => $chits,
            'planOptions' => $planOptions,
            'typeOptions' => $typeOptions,
            'filters' => [
                'search' => $search,
                'plan' => $plan,
                'type' => $type,
            ],
            'totalChits' => Chit::query()->count(),
        ]);
    }

    public function create(): View
    {
        $selectedMembers = $this->normalizeSelectionPayload((string) old('selected_members', '{}'));
        $memberLimit = max(1, (int) old('member_limit', Chit::DEFAULT_MEMBER_LIMIT));

        return view('admin.chits.create', [
            'planOptions' => Chit::planOptionsByKey(),
            'memberLimit' => $memberLimit,
            'maxRepeatPerMember' => Chit::MAX_REPEAT_PER_MEMBER,
            'members' => $this->memberDirectory(),
            'selectedMembers' => $selectedMembers,
            'isEdit' => false,
            'chit' => null,
        ]);
    }

    public function edit(Chit $chit): View
    {
        $this->ensureMemberAssignmentsExist($chit);

        $persistedSelection = $chit->memberSlots()
            ->orderBy('id')
            ->pluck('slot_count', 'user_id')
            ->map(static fn ($count): int => max(0, (int) $count))
            ->filter(static fn (int $count): bool => $count > 0)
            ->toArray();

        $selectedMembers = old('selected_members')
            ? $this->normalizeSelectionPayload((string) old('selected_members'))
            : $persistedSelection;

        $memberLimit = max(
            1,
            (int) old('member_limit', (int) ($chit->member_limit ?: (array_sum($selectedMembers) ?: Chit::DEFAULT_MEMBER_LIMIT)))
        );

        return view('admin.chits.create', [
            'planOptions' => Chit::planOptionsByKey(),
            'memberLimit' => $memberLimit,
            'maxRepeatPerMember' => Chit::MAX_REPEAT_PER_MEMBER,
            'members' => $this->memberDirectory(),
            'selectedMembers' => $selectedMembers,
            'isEdit' => true,
            'chit' => $chit,
        ]);
    }

    public function show(Request $request, Chit $chit): View
    {
        $activeTab = trim((string) $request->query('tab', 'overview'));
        if (! in_array($activeTab, ['overview', 'payments'], true)) {
            $activeTab = 'overview';
        }

        $chit->load('creator:id,name');
        $this->ensureMemberAssignmentsExist($chit);
        $monthTimeline = $chit->ensureMonthTimeline();

        $currentMonth = $chit->resolvedCurrentMonth();
        $statusOptions = ChitMemberPayment::statusOptionsByKey();

        if ($activeTab === 'payments') {
            $selectedMonth = (int) $request->query('month', $currentMonth);
            $selectedMonth = max(1, min($selectedMonth, max(1, (int) $chit->duration_months)));
            $selectedMonth = min($selectedMonth, $currentMonth);

            $selectedMonthRecord = $monthTimeline->firstWhere('month_number', $selectedMonth);
            if (! $selectedMonthRecord) {
                $selectedMonthRecord = new ChitMonth([
                    'month_number' => $selectedMonth,
                    'status_code' => ChitMonth::STATUS_PENDING,
                ]);
            }

            $paymentSearch = trim((string) $request->query('payment_search', ''));
            $paymentFilter = trim((string) $request->query('payment_filter', 'all'));
            if (! in_array($paymentFilter, ['all', 'pending', 'paid'], true)) {
                $paymentFilter = 'all';
            }

            $slots = ChitMemberSlot::query()
                ->where('chit_id', $chit->id)
                ->with([
                    'user:id,name,email,mobile_number,primary_phone,profile_image_path',
                    'payments' => static function ($query) use ($selectedMonth): void {
                        $query
                            ->where('month_number', $selectedMonth)
                            ->select([
                                'id',
                                'chit_member_slot_id',
                                'month_number',
                                'expected_amount',
                                'paid_amount',
                                'due_amount',
                                'extra_paid_amount',
                                'status_code',
                                'is_paid',
                                'paid_at',
                                'created_at',
                                'updated_at',
                            ]);
                    },
                ])
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            $repetitionCountByUser = $slots
                ->groupBy('user_id')
                ->map(static fn (Collection $memberSlots): int => $memberSlots->count());

            $allPaymentRows = $slots->values()->map(function (ChitMemberSlot $slot, int $index) use ($chit, $selectedMonth, $repetitionCountByUser): array {
                $snapshot = $this->resolveCurrentMonthPaymentSnapshot($chit, $slot, $selectedMonth);
                $payment = $slot->payments->first();
                $baseName = trim((string) ($slot->user?->name ?? 'Member'));
                $repetitionCount = (int) ($repetitionCountByUser->get($slot->user_id) ?? 0);
                $displayName = $repetitionCount > 1
                    ? trim($baseName.' '.$slot->slot_sequence)
                    : $baseName;

                return [
                    'serial' => $index + 1,
                    'slot' => $slot,
                    'payment' => $payment,
                    'display_name' => $displayName,
                    'phone' => (string) ($slot->user?->mobile_number ?: $slot->user?->primary_phone ?: 'Not available'),
                    'status_key' => $snapshot['status_key'],
                    'status_label' => $snapshot['status_label'],
                    'expected_amount' => $snapshot['expected_amount'],
                    'paid_amount' => $snapshot['paid_amount'],
                    'due_amount' => $snapshot['due_amount'],
                    'extra_paid_amount' => $snapshot['extra_paid_amount'],
                    'paid_at' => $payment?->paid_at,
                    'payment_date' => $payment?->paid_at ?: $payment?->updated_at ?: $payment?->created_at,
                ];
            });

            $filteredPaymentRows = $this->applyPaymentRowFilters($allPaymentRows, $paymentSearch, $paymentFilter);
            $collectedAmount = (int) $allPaymentRows->sum('paid_amount');
            $expectedCollection = max(0, (int) $chit->total_amount);
            $pendingAmount = max($expectedCollection - $collectedAmount, 0);
            $paidMembersCount = (int) $allPaymentRows->where('status_key', 'paid')->count();
            $pendingMembersCount = max(0, $allPaymentRows->count() - $paidMembersCount);
            $previousWinnerSlotIds = $monthTimeline
                ->filter(static fn (ChitMonth $month): bool => (int) $month->month_number < $selectedMonth)
                ->pluck('auction_winner_slot_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $availableWinnerRows = $allPaymentRows
                ->reject(static fn (array $row): bool => in_array((int) $row['slot']->id, $previousWinnerSlotIds, true))
                ->values();

            $auctionAmount = max(0, (int) ($selectedMonthRecord->auction_amount ?? 0));
            $committeeInterest = max(0, (int) round(((int) $chit->total_amount) * 0.04));
            $winnerClaimAmount = max(0, (int) $chit->total_amount - $auctionAmount);
            $memberInterestPool = max(0, $auctionAmount - $committeeInterest);
            $memberCountForInterest = max(1, (int) $allPaymentRows->count());
            $interestPerMember = (int) floor($memberInterestPool / $memberCountForInterest);
            $auctionWinnerRow = $allPaymentRows->first(static function (array $row) use ($selectedMonthRecord): bool {
                return (int) ($row['slot']->id ?? 0) === (int) ($selectedMonthRecord->auction_winner_slot_id ?? 0);
            });

            $canManageCurrentMonth = $selectedMonth === $currentMonth;
            $monthStatusKey = $selectedMonthRecord->status_key;
            $canInitializeMonth = $canManageCurrentMonth && $monthStatusKey === 'pending';
            $canMarkAllPaid = $canManageCurrentMonth && $monthStatusKey === 'open';
            $canResetMonth = $canManageCurrentMonth && $monthStatusKey !== 'closed';
            $canCloseMonth = $canManageCurrentMonth && $monthStatusKey === 'open';

            $auctionToastMessage = $selectedMonthRecord->auction_recorded_at
                ? 'Auction completed on '.$selectedMonthRecord->auction_recorded_at->format('d M Y h:i A').'.'
                : 'Auction will be starting soon.';

            return view('admin.chits.show', [
                'chit' => $chit,
                'activeTab' => $activeTab,
                'currentMonth' => $currentMonth,
                'memberCards' => collect(),
                'allMemberCards' => collect(),
                'statusSummary' => [
                    'not_paid' => (int) $allPaymentRows->where('status_key', 'not_paid')->count(),
                    'due' => (int) $allPaymentRows->where('status_key', 'due')->count(),
                    'paid' => $paidMembersCount,
                ],
                'statusOptions' => $statusOptions,
                'filters' => [
                    'member_search' => '',
                    'member_status' => 'all',
                    'payment_search' => $paymentSearch,
                    'payment_filter' => $paymentFilter,
                ],
                'monthTimeline' => $monthTimeline,
                'selectedMonth' => $selectedMonth,
                'selectedMonthRecord' => $selectedMonthRecord,
                'paymentRows' => $filteredPaymentRows,
                'allPaymentRows' => $allPaymentRows,
                'paymentSummary' => [
                    'members_count' => (int) $allPaymentRows->count(),
                    'expected_collection' => $expectedCollection,
                    'collected_amount' => $collectedAmount,
                    'pending_amount' => $pendingAmount,
                    'paid_members_count' => $paidMembersCount,
                    'pending_members_count' => $pendingMembersCount,
                ],
                'auctionToastMessage' => $auctionToastMessage,
                'maxAuctionAmount' => (int) floor(((int) $chit->total_amount) * 0.30),
                'availableWinnerRows' => $availableWinnerRows,
                'auctionInsights' => [
                    'is_available' => $selectedMonthRecord->auction_recorded_at !== null && $selectedMonthRecord->auction_amount !== null,
                    'winner_name' => $auctionWinnerRow['display_name'] ?? 'Not selected',
                    'auction_amount' => $auctionAmount,
                    'winner_claim_amount' => $winnerClaimAmount,
                    'committee_interest' => $committeeInterest,
                    'member_interest_pool' => $memberInterestPool,
                    'interest_per_member' => $interestPerMember,
                ],
                'canInitializeMonth' => $canInitializeMonth,
                'canMarkAllPaid' => $canMarkAllPaid,
                'canResetMonth' => $canResetMonth,
                'canCloseMonth' => $canCloseMonth,
            ]);
        }

        $memberSearch = trim((string) $request->query('member_search', ''));
        $memberStatus = trim((string) $request->query('member_status', 'all'));

        if ($memberStatus !== 'all' && ! array_key_exists($memberStatus, $statusOptions)) {
            $memberStatus = 'all';
        }

            $slots = ChitMemberSlot::query()
                ->where('chit_id', $chit->id)
                ->with([
                    'user:id,name,email,mobile_number,primary_phone,profile_image_path,referred_by_name',
                    'payments' => static function ($query) use ($currentMonth): void {
                        $query
                            ->where('month_number', $currentMonth)
                            ->select([
                                'id',
                                'chit_member_slot_id',
                                'month_number',
                                'expected_amount',
                                'paid_amount',
                                'due_amount',
                                'extra_paid_amount',
                                'status_code',
                                'is_paid',
                                'paid_at',
                                'created_at',
                                'updated_at',
                        ]);
                },
            ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $repetitionCountByUser = $slots
            ->groupBy('user_id')
            ->map(static fn (Collection $memberSlots): int => $memberSlots->count());

        $allMemberCards = $slots->map(function (ChitMemberSlot $slot) use ($chit, $currentMonth, $repetitionCountByUser): array {
            $snapshot = $this->resolveCurrentMonthPaymentSnapshot($chit, $slot, $currentMonth);
            $payment = $slot->payments->first();
            $baseName = trim((string) ($slot->user?->name ?? 'Member'));
            $repetitionCount = (int) ($repetitionCountByUser->get($slot->user_id) ?? 0);
            $displayName = $repetitionCount > 1
                ? trim($baseName.' '.$slot->slot_sequence)
                : $baseName;
            $paymentDate = $payment?->paid_at ?: $payment?->updated_at ?: $payment?->created_at;
            $whatsAppUrl = $this->buildMemberWhatsAppUrl(
                phone: (string) ($slot->user?->mobile_number ?: $slot->user?->primary_phone ?: ''),
                memberName: $displayName,
                chitName: (string) $chit->plan_label,
                chitStartDate: $chit->created_at,
                totalChitAmount: (int) $chit->total_amount,
                durationMonths: (int) $chit->duration_months,
                monthNumber: (int) $snapshot['month_number'],
                statusKey: (string) $snapshot['status_key'],
                statusLabel: (string) $snapshot['status_label'],
                expectedAmount: (int) $snapshot['expected_amount'],
                paidAmount: (int) $snapshot['paid_amount'],
                dueAmount: (int) $snapshot['due_amount'],
                paymentDate: $paymentDate,
            );

            return [
                'slot' => $slot,
                'member' => $slot->user,
                'display_name' => $displayName,
                'phone' => (string) ($slot->user?->mobile_number ?: $slot->user?->primary_phone ?: 'Not available'),
                'referred_by' => $this->resolveSlotReferrerName($slot),
                'month_number' => $snapshot['month_number'],
                'expected_amount' => $snapshot['expected_amount'],
                'paid_amount' => $snapshot['paid_amount'],
                'due_amount' => $snapshot['due_amount'],
                'extra_paid_amount' => $snapshot['extra_paid_amount'],
                'status_key' => $snapshot['status_key'],
                'status_label' => $snapshot['status_label'],
                'is_paid' => $snapshot['is_paid'],
                'whatsapp_url' => $whatsAppUrl,
            ];
        });

        $filteredMemberCards = $this->applyMemberCardFilters($allMemberCards, $memberSearch, $memberStatus);

        $statusSummary = [
            'not_paid' => 0,
            'due' => 0,
            'paid' => 0,
        ];

        foreach ($allMemberCards as $card) {
            $statusKey = (string) ($card['status_key'] ?? 'not_paid');
            if (array_key_exists($statusKey, $statusSummary)) {
                $statusSummary[$statusKey]++;
            }
        }

        return view('admin.chits.show', [
            'chit' => $chit,
            'activeTab' => $activeTab,
            'currentMonth' => $currentMonth,
            'memberCards' => $filteredMemberCards,
            'allMemberCards' => $allMemberCards,
            'statusSummary' => $statusSummary,
            'statusOptions' => $statusOptions,
            'filters' => [
                'member_search' => $memberSearch,
                'member_status' => $memberStatus,
                'payment_search' => '',
                'payment_filter' => 'all',
            ],
            'monthTimeline' => $monthTimeline,
            'selectedMonth' => $currentMonth,
            'selectedMonthRecord' => null,
            'paymentRows' => collect(),
            'allPaymentRows' => collect(),
            'paymentSummary' => [
                'members_count' => 0,
                'expected_collection' => 0,
                'collected_amount' => 0,
                'pending_amount' => 0,
                'paid_members_count' => 0,
                'pending_members_count' => 0,
            ],
            'auctionToastMessage' => null,
            'maxAuctionAmount' => 0,
            'availableWinnerRows' => collect(),
            'auctionInsights' => [
                'is_available' => false,
                'winner_name' => 'Not selected',
                'auction_amount' => 0,
                'winner_claim_amount' => 0,
                'committee_interest' => 0,
                'member_interest_pool' => 0,
                'interest_per_member' => 0,
            ],
            'canInitializeMonth' => false,
            'canMarkAllPaid' => false,
            'canResetMonth' => false,
            'canCloseMonth' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $typeKeys = collect(Chit::TYPE_MAP)->pluck('key')->all();

        $validated = $request->validate([
            'chit_name' => ['required', 'string', 'max:255'],
            'chit_type' => ['required', Rule::in($typeKeys)],
            'total_amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:240'],
            'member_limit' => ['required', 'integer', 'min:1', 'max:250'],
            'selected_members' => ['required', 'string'],
        ]);

        $chitName = trim((string) $validated['chit_name']);
        if ($chitName === '') {
            throw ValidationException::withMessages([
                'chit_name' => 'Chit name is required.',
            ]);
        }

        $resolvedPlan = Chit::resolvePlanInput($chitName);
        $planCode = (int) ($resolvedPlan['code'] ?? Chit::planCodeFromAmount((int) $validated['total_amount']));
        $typeCode = Chit::typeCodeFromKey($validated['chit_type']);
        if (! $planCode || ! $typeCode) {
            throw ValidationException::withMessages([
                'chit_type' => 'Invalid chit type selected.',
            ]);
        }

        $memberCounts = $this->normalizeSelectionPayload($validated['selected_members']);
        $totalSlots = array_sum($memberCounts);
        $memberLimit = max(1, (int) $validated['member_limit']);

        if ($totalSlots !== $memberLimit) {
            throw ValidationException::withMessages([
                'selected_members' => 'You must assign exactly '.$memberLimit.' member slots.',
            ]);
        }

        $maxRepeatPerMember = min(Chit::MAX_REPEAT_PER_MEMBER, $memberLimit);

        foreach ($memberCounts as $memberId => $count) {
            if ($count > $maxRepeatPerMember) {
                throw ValidationException::withMessages([
                    'selected_members' => 'A member can be assigned at most '.$maxRepeatPerMember.' times for this chit.',
                ]);
            }
            if ($count < 1) {
                unset($memberCounts[$memberId]);
            }
        }

        if ($memberCounts === []) {
            throw ValidationException::withMessages([
                'selected_members' => 'Select at least one member.',
            ]);
        }

        $memberIds = array_keys($memberCounts);
        $validMemberCount = User::query()
            ->where('role', User::ROLE_USER)
            ->whereIn('id', $memberIds)
            ->count();

        if ($validMemberCount !== count($memberIds)) {
            throw ValidationException::withMessages([
                'selected_members' => 'One or more selected users are invalid. Only members can be assigned.',
            ]);
        }

        $totalAmount = (int) $validated['total_amount'];
        $durationMonths = (int) $validated['duration_months'];
        $monthlyAmount = Chit::calculateMonthlyAmount($totalAmount, $durationMonths, $memberLimit);
        $memberReferrerMap = $this->resolveMemberReferrersForAssignments($memberIds);

        DB::transaction(function () use ($chitName, $durationMonths, $memberCounts, $memberLimit, $monthlyAmount, $planCode, $request, $totalAmount, $totalSlots, $typeCode, $memberReferrerMap): void {
            $chit = Chit::query()->create([
                'chit_name' => $chitName,
                'plan_code' => $planCode,
                'chit_type_code' => $typeCode,
                'total_amount' => $totalAmount,
                'duration_months' => $durationMonths,
                'current_month' => 1,
                'member_limit' => $memberLimit,
                'monthly_amount' => $monthlyAmount,
                'total_slots_assigned' => $totalSlots,
                'status_code' => 1,
                'created_by' => $request->user()?->id,
            ]);

            $displayOrder = 1;

            foreach ($memberCounts as $memberId => $count) {
                $chitMember = $chit->memberSlots()->create([
                    'chit_id' => $chit->id,
                    'user_id' => $memberId,
                    'slot_count' => $count,
                ]);

                for ($slotSequence = 1; $slotSequence <= $count; $slotSequence++) {
                    $chit->memberAssignments()->create([
                        'chit_id' => $chit->id,
                        'chit_member_id' => $chitMember->id,
                        'user_id' => $memberId,
                        'slot_sequence' => $slotSequence,
                        'display_order' => $displayOrder,
                        'referred_by_name' => $memberReferrerMap[(int) $memberId] ?? null,
                    ]);
                    $displayOrder++;
                }
            }

            $chit->ensureMonthTimeline();
        });

        return redirect()->route('admin.chits.index')->with('status', 'chit-created');
    }

    public function update(Request $request, Chit $chit): RedirectResponse
    {
        $typeKeys = collect(Chit::TYPE_MAP)->pluck('key')->all();

        $validated = $request->validate([
            'chit_name' => ['required', 'string', 'max:255'],
            'chit_type' => ['required', Rule::in($typeKeys)],
            'total_amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:240'],
            'member_limit' => ['required', 'integer', 'min:1', 'max:250'],
            'selected_members' => ['required', 'string'],
        ]);

        $chitName = trim((string) $validated['chit_name']);
        if ($chitName === '') {
            throw ValidationException::withMessages([
                'chit_name' => 'Chit name is required.',
            ]);
        }

        $resolvedPlan = Chit::resolvePlanInput($chitName);
        $planCode = (int) ($resolvedPlan['code'] ?? Chit::planCodeFromAmount((int) $validated['total_amount']));
        $typeCode = Chit::typeCodeFromKey($validated['chit_type']);
        if (! $planCode || ! $typeCode) {
            throw ValidationException::withMessages([
                'chit_type' => 'Invalid chit type selected.',
            ]);
        }

        $memberCounts = $this->normalizeSelectionPayload($validated['selected_members']);
        $totalSlots = array_sum($memberCounts);
        $memberLimit = max(1, (int) $validated['member_limit']);

        if ($totalSlots !== $memberLimit) {
            throw ValidationException::withMessages([
                'selected_members' => 'You must assign exactly '.$memberLimit.' member slots.',
            ]);
        }

        $maxRepeatPerMember = min(Chit::MAX_REPEAT_PER_MEMBER, $memberLimit);
        foreach ($memberCounts as $memberId => $count) {
            if ($count > $maxRepeatPerMember) {
                throw ValidationException::withMessages([
                    'selected_members' => 'A member can be assigned at most '.$maxRepeatPerMember.' times for this chit.',
                ]);
            }
            if ($count < 1) {
                unset($memberCounts[$memberId]);
            }
        }

        if ($memberCounts === []) {
            throw ValidationException::withMessages([
                'selected_members' => 'Select at least one member.',
            ]);
        }

        $memberIds = array_keys($memberCounts);
        $validMemberCount = User::query()
            ->where('role', User::ROLE_USER)
            ->whereIn('id', $memberIds)
            ->count();

        if ($validMemberCount !== count($memberIds)) {
            throw ValidationException::withMessages([
                'selected_members' => 'One or more selected users are invalid. Only members can be assigned.',
            ]);
        }

        $totalAmount = (int) $validated['total_amount'];
        $durationMonths = (int) $validated['duration_months'];
        $monthlyAmount = Chit::calculateMonthlyAmount($totalAmount, $durationMonths, $memberLimit);
        $memberReferrerMap = $this->resolveMemberReferrersForAssignments($memberIds);

        $existingMemberCounts = $chit->memberSlots()
            ->pluck('slot_count', 'user_id')
            ->map(static fn ($count): int => max(0, (int) $count))
            ->filter(static fn (int $count): bool => $count > 0)
            ->sortKeys()
            ->toArray();

        $incomingMemberCounts = collect($memberCounts)
            ->map(static fn ($count): int => max(0, (int) $count))
            ->filter(static fn (int $count): bool => $count > 0)
            ->sortKeys()
            ->toArray();

        $membersChanged = $incomingMemberCounts !== $existingMemberCounts;
        $currentMonth = min(max(1, (int) ($chit->current_month ?: 1)), $durationMonths);

        DB::transaction(function () use ($chit, $chitName, $currentMonth, $durationMonths, $incomingMemberCounts, $memberLimit, $membersChanged, $monthlyAmount, $planCode, $request, $totalAmount, $totalSlots, $typeCode, $memberReferrerMap): void {
            $chit->forceFill([
                'chit_name' => $chitName,
                'plan_code' => $planCode,
                'chit_type_code' => $typeCode,
                'total_amount' => $totalAmount,
                'duration_months' => $durationMonths,
                'current_month' => $currentMonth,
                'member_limit' => $memberLimit,
                'monthly_amount' => $monthlyAmount,
                'total_slots_assigned' => $totalSlots,
            ])->save();

            if ($membersChanged) {
                $this->replaceMemberAssignments($chit, $incomingMemberCounts, $memberReferrerMap);
            }

            $chit->ensureMonthTimeline();
        });

        return redirect()->route('admin.chits.index')->with('status', 'chit-updated');
    }

    public function destroy(Request $request, Chit $chit): RedirectResponse
    {
        $request->validateWithBag('deleteChit', [
            'password' => ['required', 'current_password'],
        ]);

        $chit->delete();

        return redirect()->route('admin.chits.index')->with('status', 'chit-deleted');
    }

    /**
     * @return array<int, int>
     */
    private function normalizeSelectionPayload(string $rawJson): array
    {
        $decoded = json_decode($rawJson, true);

        if (! is_array($decoded)) {
            return [];
        }

        $normalized = [];

        foreach ($decoded as $memberId => $count) {
            if (! is_numeric($memberId) || ! is_numeric($count)) {
                continue;
            }

            $memberIdInt = (int) $memberId;
            $countInt = (int) $count;

            if ($memberIdInt <= 0 || $countInt <= 0) {
                continue;
            }

            $normalized[$memberIdInt] = $countInt;
        }

        return $normalized;
    }

    private function ensureMemberAssignmentsExist(Chit $chit): void
    {
        $assignmentCount = (int) $chit->memberAssignments()->count();
        if ($assignmentCount > 0) {
            return;
        }

        $aggregates = $chit->memberSlots()
            ->orderBy('id')
            ->get(['id', 'user_id', 'slot_count']);

        if ($aggregates->isEmpty()) {
            return;
        }

        $displayOrder = 1;
        $now = now();
        $memberReferrerMap = $this->resolveMemberReferrersForAssignments(
            $aggregates->pluck('user_id')->map(static fn ($id): int => (int) $id)->all()
        );
        $payload = [];

        foreach ($aggregates as $aggregate) {
            $slotCount = max(0, (int) $aggregate->slot_count);
            $resolvedReferrer = $memberReferrerMap[(int) $aggregate->user_id] ?? null;

            for ($slotSequence = 1; $slotSequence <= $slotCount; $slotSequence++) {
                $payload[] = [
                    'chit_id' => $chit->id,
                    'chit_member_id' => $aggregate->id,
                    'user_id' => $aggregate->user_id,
                    'slot_sequence' => $slotSequence,
                    'display_order' => $displayOrder,
                    'referred_by_name' => $resolvedReferrer,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $displayOrder++;
            }
        }

        if ($payload === []) {
            return;
        }

        ChitMemberSlot::query()->insert($payload);
        $chit->forceFill(['total_slots_assigned' => count($payload)])->save();
    }

    /**
     * @param  array<int, int>  $memberCounts
     * @param  array<int, string|null>  $memberReferrerMap
     */
    private function replaceMemberAssignments(Chit $chit, array $memberCounts, array $memberReferrerMap = []): void
    {
        $chit->memberAssignments()->delete();
        $chit->memberSlots()->delete();

        $displayOrder = 1;
        $totalSlots = 0;

        foreach ($memberCounts as $memberId => $count) {
            $slotCount = max(0, (int) $count);
            if ($slotCount < 1) {
                continue;
            }
            $resolvedReferrer = $memberReferrerMap[(int) $memberId] ?? null;

            $chitMember = $chit->memberSlots()->create([
                'chit_id' => $chit->id,
                'user_id' => (int) $memberId,
                'slot_count' => $slotCount,
            ]);

            for ($slotSequence = 1; $slotSequence <= $slotCount; $slotSequence++) {
                $chit->memberAssignments()->create([
                    'chit_id' => $chit->id,
                    'chit_member_id' => $chitMember->id,
                    'user_id' => (int) $memberId,
                    'slot_sequence' => $slotSequence,
                    'display_order' => $displayOrder,
                    'referred_by_name' => $resolvedReferrer,
                ]);
                $displayOrder++;
                $totalSlots++;
            }
        }

        $chit->forceFill(['total_slots_assigned' => $totalSlots])->save();
    }

    /**
     * @param  array<int, int>  $memberIds
     * @return array<int, string|null>
     */
    private function resolveMemberReferrersForAssignments(array $memberIds): array
    {
        $uniqueMemberIds = collect($memberIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($uniqueMemberIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $uniqueMemberIds)
            ->get(['id', 'referred_by_name'])
            ->mapWithKeys(function (User $member): array {
                $memberReferrer = $this->normalizeNullableString($member->referred_by_name);

                return [(int) $member->id => $memberReferrer];
            })
            ->all();
    }

    private function resolveSlotReferrerName(ChitMemberSlot $slot): string
    {
        $referrer = $this->normalizeNullableString($slot->user?->referred_by_name);

        return $referrer ?: 'No One';
    }

    private function buildMemberWhatsAppUrl(
        string $phone,
        string $memberName,
        string $chitName,
        ?\DateTimeInterface $chitStartDate,
        int $totalChitAmount,
        int $durationMonths,
        int $monthNumber,
        string $statusKey,
        string $statusLabel,
        int $expectedAmount,
        int $paidAmount,
        int $dueAmount,
        ?\DateTimeInterface $paymentDate = null,
    ): ?string {
        $normalizedPhone = $this->normalizeWhatsAppNumber($phone);
        if (! $normalizedPhone) {
            return null;
        }

        $statusText = strtolower(trim($statusLabel));
        $paymentDateText = $paymentDate ? $paymentDate->format('d M Y') : 'Not recorded';
        $startDateText = $chitStartDate ? $chitStartDate->format('d M Y') : 'Not recorded';
        $chitLabel = trim($chitName) !== '' ? trim($chitName) : 'Chit';
        $baseLines = [
            'Hi '.$memberName.',',
            'Payment update from Goud Sangam',
            'Chit: '.$chitLabel,
            'Chit Start Date: '.$startDateText,
            'Total Chit Value: Rs '.number_format($totalChitAmount),
            'Duration: '.max(1, $durationMonths).' months',
            'Month: '.$monthNumber,
        ];

        if ($statusKey === 'paid') {
            $statusLines = [
                'Status: PAID',
                'Expected Amount: Rs '.number_format($expectedAmount),
                'Amount Paid: Rs '.number_format($paidAmount),
                'Due Amount: Rs '.number_format($dueAmount),
                'Payment Date: '.$paymentDateText,
                'Thank you.',
            ];
        } elseif ($statusKey === 'due') {
            $statusLines = [
                'Status: DUE',
                'Expected Amount: Rs '.number_format($expectedAmount),
                'Amount Paid: Rs '.number_format($paidAmount),
                'Due Amount: Rs '.number_format($dueAmount),
                'Please clear the due amount.',
            ];
        } else {
            $statusLines = [
                'Status: '.strtoupper($statusText),
                'Expected Amount: Rs '.number_format($expectedAmount),
                'Amount Paid: Rs '.number_format($paidAmount),
                'Due Amount: Rs '.number_format($dueAmount),
                'Please complete this month payment.',
            ];
        }

        $message = implode("\n", array_merge($baseLines, $statusLines));

        return 'https://wa.me/'.$normalizedPhone.'?text='.urlencode($message);
    }

    private function normalizeWhatsAppNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return $digits;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array{
     *     month_number:int,
     *     expected_amount:int,
     *     paid_amount:int,
     *     due_amount:int,
     *     extra_paid_amount:int,
     *     status_key:string,
     *     status_label:string,
     *     is_paid:bool
     * }
     */
    private function resolveCurrentMonthPaymentSnapshot(Chit $chit, ChitMemberSlot $slot, int $currentMonth): array
    {
        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $payment = $slot->payments->first();

        if (! $payment) {
            return [
                'month_number' => $currentMonth,
                'expected_amount' => $expectedAmount,
                'paid_amount' => 0,
                'due_amount' => $expectedAmount,
                'extra_paid_amount' => 0,
                'status_key' => 'not_paid',
                'status_label' => 'Not Paid',
                'is_paid' => false,
            ];
        }

        $status = ChitMemberPayment::statusFromCode((int) $payment->status_code) ?? ['key' => 'not_paid', 'label' => 'Not Paid'];

        return [
            'month_number' => (int) $payment->month_number,
            'expected_amount' => max(0, (int) $payment->expected_amount),
            'paid_amount' => max(0, (int) $payment->paid_amount),
            'due_amount' => max(0, (int) $payment->due_amount),
            'extra_paid_amount' => max(0, (int) $payment->extra_paid_amount),
            'status_key' => $status['key'],
            'status_label' => $status['label'],
            'is_paid' => (bool) $payment->is_paid,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $cards
     * @return Collection<int, array<string, mixed>>
     */
    private function applyMemberCardFilters(Collection $cards, string $search, string $status): Collection
    {
        $filtered = $cards;

        if ($search !== '') {
            $normalizedSearch = strtolower($search);

            $filtered = $filtered->filter(static function (array $card) use ($normalizedSearch): bool {
                $haystack = strtolower(implode(' ', [
                    (string) ($card['display_name'] ?? ''),
                    (string) ($card['phone'] ?? ''),
                    (string) ($card['referred_by'] ?? ''),
                    (string) ($card['status_label'] ?? ''),
                ]));

                return str_contains($haystack, $normalizedSearch);
            });
        }

        if ($status !== 'all') {
            $filtered = $filtered->filter(static fn (array $card): bool => (string) ($card['status_key'] ?? 'not_paid') === $status);
        }

        return $filtered->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function applyPaymentRowFilters(Collection $rows, string $search, string $filter): Collection
    {
        $filtered = $rows;

        if ($search !== '') {
            $normalizedSearch = strtolower($search);

            $filtered = $filtered->filter(static function (array $row) use ($normalizedSearch): bool {
                $haystack = strtolower(implode(' ', [
                    (string) ($row['display_name'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                    (string) ($row['status_label'] ?? ''),
                ]));

                return str_contains($haystack, $normalizedSearch);
            });
        }

        if ($filter === 'paid') {
            $filtered = $filtered->filter(static fn (array $row): bool => (string) ($row['status_key'] ?? 'not_paid') === 'paid');
        } elseif ($filter === 'pending') {
            $filtered = $filtered->filter(static fn (array $row): bool => (string) ($row['status_key'] ?? 'not_paid') !== 'paid');
        }

        return $filtered->values();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function memberDirectory(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->where('role', User::ROLE_USER)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'mobile_number',
                'government_id_path',
                'profile_image_path',
            ]);
    }
}
