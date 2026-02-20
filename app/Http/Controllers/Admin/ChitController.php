<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
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
        $members = User::query()
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

        $selectedMembers = $this->normalizeSelectionPayload((string) old('selected_members', '{}'));
        $memberLimit = max(1, (int) old('member_limit', Chit::DEFAULT_MEMBER_LIMIT));

        return view('admin.chits.create', [
            'planOptions' => Chit::planOptionsByKey(),
            'memberLimit' => $memberLimit,
            'maxRepeatPerMember' => Chit::MAX_REPEAT_PER_MEMBER,
            'members' => $members,
            'selectedMembers' => $selectedMembers,
        ]);
    }

    public function show(Request $request, Chit $chit): View
    {
        $activeTab = trim((string) $request->query('tab', 'overview'));
        if (! in_array($activeTab, ['overview', 'payments'], true)) {
            $activeTab = 'overview';
        }

        $memberSearch = trim((string) $request->query('member_search', ''));
        $memberStatus = trim((string) $request->query('member_status', 'all'));
        $statusOptions = ChitMemberPayment::statusOptionsByKey();

        if ($memberStatus !== 'all' && ! array_key_exists($memberStatus, $statusOptions)) {
            $memberStatus = 'all';
        }

        $chit->load('creator:id,name');
        $this->ensureMemberAssignmentsExist($chit);

        $currentMonth = $chit->resolvedCurrentMonth();

        $slots = ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->with([
                'user:id,name,email,mobile_number,primary_phone,profile_image_path',
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
            $baseName = trim((string) ($slot->user?->name ?? 'Member'));
            $repetitionCount = (int) ($repetitionCountByUser->get($slot->user_id) ?? 0);
            $displayName = $repetitionCount > 1
                ? trim($baseName.' '.$slot->slot_sequence)
                : $baseName;

            return [
                'slot' => $slot,
                'member' => $slot->user,
                'display_name' => $displayName,
                'phone' => (string) ($slot->user?->mobile_number ?: $slot->user?->primary_phone ?: 'Not available'),
                'referred_by' => $slot->referred_by_name ?: 'Not provided',
                'month_number' => $snapshot['month_number'],
                'expected_amount' => $snapshot['expected_amount'],
                'paid_amount' => $snapshot['paid_amount'],
                'due_amount' => $snapshot['due_amount'],
                'extra_paid_amount' => $snapshot['extra_paid_amount'],
                'status_key' => $snapshot['status_key'],
                'status_label' => $snapshot['status_label'],
                'is_paid' => $snapshot['is_paid'],
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
            ],
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

        DB::transaction(function () use ($chitName, $durationMonths, $memberCounts, $memberLimit, $monthlyAmount, $planCode, $request, $totalAmount, $totalSlots, $typeCode): void {
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
            $referredByName = $request->user()?->name;

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
                        'referred_by_name' => $referredByName,
                    ]);
                    $displayOrder++;
                }
            }
        });

        return redirect()->route('admin.chits.index')->with('status', 'chit-created');
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
        $referredByName = $chit->creator()->value('name');
        $payload = [];

        foreach ($aggregates as $aggregate) {
            $slotCount = max(0, (int) $aggregate->slot_count);

            for ($slotSequence = 1; $slotSequence <= $slotCount; $slotSequence++) {
                $payload[] = [
                    'chit_id' => $chit->id,
                    'chit_member_id' => $aggregate->id,
                    'user_id' => $aggregate->user_id,
                    'slot_sequence' => $slotSequence,
                    'display_order' => $displayOrder,
                    'referred_by_name' => $referredByName,
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
}
