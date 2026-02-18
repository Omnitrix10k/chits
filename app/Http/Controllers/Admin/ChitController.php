<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                $matchingPlanCodes = collect(Chit::PLAN_MAP)
                    ->filter(static fn (array $item): bool => str_contains(strtolower($item['label']), $normalizedSearch))
                    ->keys()
                    ->all();

                $matchingTypeCodes = collect(Chit::TYPE_MAP)
                    ->filter(static fn (array $item): bool => str_contains(strtolower($item['label']), $normalizedSearch))
                    ->keys()
                    ->all();

                if ($matchingPlanCodes === [] && $matchingTypeCodes === []) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where(function ($builder) use ($matchingPlanCodes, $matchingTypeCodes): void {
                        if ($matchingPlanCodes !== []) {
                            $builder->orWhereIn('plan_code', $matchingPlanCodes);
                        }
                        if ($matchingTypeCodes !== []) {
                            $builder->orWhereIn('chit_type_code', $matchingTypeCodes);
                        }
                    });
                }
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

        return view('admin.chits.create', [
            'planOptions' => Chit::planOptionsByKey(),
            'memberLimit' => Chit::MEMBER_LIMIT,
            'maxRepeatPerMember' => Chit::MAX_REPEAT_PER_MEMBER,
            'members' => $members,
            'selectedMembers' => $selectedMembers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $planKeys = array_keys(Chit::planOptionsByKey());
        $typeKeys = collect(Chit::TYPE_MAP)->pluck('key')->all();

        $validated = $request->validate([
            'chit_name' => ['required', Rule::in($planKeys)],
            'chit_type' => ['required', Rule::in($typeKeys)],
            'duration_months' => ['required', 'integer', 'min:1', 'max:240'],
            'selected_members' => ['required', 'string'],
        ]);

        $planCode = Chit::planCodeFromKey($validated['chit_name']);
        $typeCode = Chit::typeCodeFromKey($validated['chit_type']);
        $plan = Chit::planFromCode($planCode);

        if (! $planCode || ! $typeCode || ! $plan) {
            throw ValidationException::withMessages([
                'chit_name' => 'Invalid chit plan or type selected.',
            ]);
        }

        $memberCounts = $this->normalizeSelectionPayload($validated['selected_members']);
        $totalSlots = array_sum($memberCounts);

        if ($totalSlots !== Chit::MEMBER_LIMIT) {
            throw ValidationException::withMessages([
                'selected_members' => 'You must assign exactly '.Chit::MEMBER_LIMIT.' member slots.',
            ]);
        }

        foreach ($memberCounts as $memberId => $count) {
            if ($count > Chit::MAX_REPEAT_PER_MEMBER) {
                throw ValidationException::withMessages([
                    'selected_members' => 'A member can be assigned at most '.Chit::MAX_REPEAT_PER_MEMBER.' times.',
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

        $durationMonths = (int) $validated['duration_months'];
        $monthlyAmount = (int) round($plan['amount'] / max($durationMonths, 1));

        DB::transaction(function () use ($durationMonths, $memberCounts, $monthlyAmount, $plan, $planCode, $request, $typeCode): void {
            $chit = Chit::query()->create([
                'plan_code' => $planCode,
                'chit_type_code' => $typeCode,
                'total_amount' => $plan['amount'],
                'duration_months' => $durationMonths,
                'member_limit' => Chit::MEMBER_LIMIT,
                'monthly_amount' => $monthlyAmount,
                'total_slots_assigned' => Chit::MEMBER_LIMIT,
                'status_code' => 1,
                'created_by' => $request->user()?->id,
            ]);

            $payload = [];
            foreach ($memberCounts as $memberId => $count) {
                $payload[] = [
                    'chit_id' => $chit->id,
                    'user_id' => $memberId,
                    'slot_count' => $count,
                ];
            }

            $chit->memberSlots()->insert($payload);
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
}
