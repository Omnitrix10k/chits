<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
use App\Models\ChitMember;
use App\Models\ChitMemberPayment;
use App\Models\ChitMemberSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChitMemberController extends Controller
{
    public function show(Chit $chit, ChitMemberSlot $slot): View
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        $slot->load([
            'user:id,name,first_name,last_name,email,mobile_number,primary_phone,address,profile_image_path',
            'payments' => static function ($query): void {
                $query->orderBy('month_number');
            },
        ]);

        $currentMonth = $chit->resolvedCurrentMonth();
        $repetitionCount = (int) ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->where('user_id', $slot->user_id)
            ->count();
        $baseName = trim((string) ($slot->user?->name ?? 'Member'));
        $displayName = $repetitionCount > 1
            ? trim($baseName.' '.$slot->slot_sequence)
            : $baseName;

        $history = $this->buildPaymentHistory($chit, $slot, $currentMonth);
        $latestSnapshot = $history->last() ?: $this->defaultSnapshot(1, (int) $chit->monthly_amount);

        return view('admin.chits.member-show', [
            'chit' => $chit,
            'slot' => $slot,
            'member' => $slot->user,
            'displayName' => $displayName,
            'currentMonth' => $currentMonth,
            'history' => $history,
            'latestSnapshot' => $latestSnapshot,
            'statusOptions' => ChitMemberPayment::statusOptionsByKey(),
        ]);
    }

    public function update(Request $request, Chit $chit, ChitMemberSlot $slot): RedirectResponse
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        $validated = $request->validate([
            'referred_by_name' => ['nullable', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $slot->update([
            'referred_by_name' => $this->normalizeNullableString($validated['referred_by_name'] ?? null),
            'age' => $validated['age'] ?? null,
        ]);

        return redirect()
            ->route('admin.chits.members.show', [$chit, $slot])
            ->with('status', 'member-slot-updated');
    }

    public function storePayment(Request $request, Chit $chit, ChitMemberSlot $slot): RedirectResponse
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        $statusKeys = array_keys(ChitMemberPayment::statusOptionsByKey());
        $validated = $request->validate([
            'month_number' => ['required', 'integer', 'min:1', 'max:'.max(1, (int) $chit->duration_months)],
            'payment_status' => ['required', Rule::in($statusKeys)],
            'paid_amount' => ['required', 'integer', 'min:0', 'max:999999999'],
            'mark_paid' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $monthNumber = (int) $validated['month_number'];
        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $paidAmount = max(0, (int) $validated['paid_amount']);
        $selectedStatus = (string) $validated['payment_status'];
        $markPaid = $request->boolean('mark_paid');

        if ($markPaid) {
            $selectedStatus = 'paid';
        }

        if ($selectedStatus === 'paid') {
            $paidAmount = max($paidAmount, $expectedAmount);
        } else {
            if ($paidAmount >= $expectedAmount) {
                $selectedStatus = 'paid';
            } elseif ($paidAmount <= 0) {
                $selectedStatus = 'not_paid';
                $paidAmount = 0;
            } else {
                $selectedStatus = 'due';
            }
        }

        $statusCode = ChitMemberPayment::statusCodeFromKey($selectedStatus) ?? ChitMemberPayment::STATUS_NOT_PAID;
        $dueAmount = max($expectedAmount - $paidAmount, 0);
        $extraPaidAmount = max($paidAmount - $expectedAmount, 0);
        $isPaid = $statusCode === ChitMemberPayment::STATUS_PAID;

        ChitMemberPayment::query()->updateOrCreate(
            [
                'chit_member_slot_id' => $slot->id,
                'month_number' => $monthNumber,
            ],
            [
                'chit_id' => $chit->id,
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'extra_paid_amount' => $extraPaidAmount,
                'status_code' => $statusCode,
                'is_paid' => $isPaid,
                'paid_at' => $isPaid ? now() : null,
                'recorded_by' => $request->user()?->id,
                'notes' => $this->normalizeNullableString($validated['notes'] ?? null),
            ]
        );

        return redirect()
            ->route('admin.chits.members.show', [$chit, $slot])
            ->with('status', 'member-payment-updated');
    }

    public function destroy(Chit $chit, ChitMemberSlot $slot): RedirectResponse
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        DB::transaction(function () use ($chit, $slot): void {
            $userId = (int) $slot->user_id;
            $chitMemberId = $slot->chit_member_id ? (int) $slot->chit_member_id : null;

            $slot->payments()->delete();
            $slot->delete();

            $aggregate = null;
            if ($chitMemberId) {
                $aggregate = ChitMember::query()
                    ->where('id', $chitMemberId)
                    ->where('chit_id', $chit->id)
                    ->first();
            }

            if (! $aggregate) {
                $aggregate = ChitMember::query()
                    ->where('chit_id', $chit->id)
                    ->where('user_id', $userId)
                    ->first();
            }

            if ($aggregate) {
                if ((int) $aggregate->slot_count <= 1) {
                    $aggregate->delete();
                } else {
                    $aggregate->decrement('slot_count');
                }
            }

            $this->resequenceSlots($chit);
        });

        return redirect()
            ->route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'overview'])
            ->with('status', 'member-slot-deleted');
    }

    private function assertSlotBelongsToChit(Chit $chit, ChitMemberSlot $slot): void
    {
        if ((int) $slot->chit_id !== (int) $chit->id) {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     month_number:int,
     *     expected_amount:int,
     *     paid_amount:int,
     *     due_amount:int,
     *     extra_paid_amount:int,
     *     status_key:string,
     *     status_label:string,
     *     is_paid:bool,
     *     notes:?string
     * }>
     */
    private function buildPaymentHistory(Chit $chit, ChitMemberSlot $slot, int $currentMonth): \Illuminate\Support\Collection
    {
        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $paymentsByMonth = $slot->payments->keyBy('month_number');

        return collect(range(1, max(1, $currentMonth)))
            ->map(function (int $monthNumber) use ($expectedAmount, $paymentsByMonth): array {
                /** @var ChitMemberPayment|null $payment */
                $payment = $paymentsByMonth->get($monthNumber);
                if (! $payment) {
                    return $this->defaultSnapshot($monthNumber, $expectedAmount);
                }

                $status = ChitMemberPayment::statusFromCode((int) $payment->status_code) ?? ['key' => 'not_paid', 'label' => 'Not Paid'];

                return [
                    'month_number' => $monthNumber,
                    'expected_amount' => max(0, (int) $payment->expected_amount),
                    'paid_amount' => max(0, (int) $payment->paid_amount),
                    'due_amount' => max(0, (int) $payment->due_amount),
                    'extra_paid_amount' => max(0, (int) $payment->extra_paid_amount),
                    'status_key' => $status['key'],
                    'status_label' => $status['label'],
                    'is_paid' => (bool) $payment->is_paid,
                    'notes' => $payment->notes ?: null,
                ];
            });
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
     *     is_paid:bool,
     *     notes:?string
     * }
     */
    private function defaultSnapshot(int $monthNumber, int $expectedAmount): array
    {
        return [
            'month_number' => $monthNumber,
            'expected_amount' => $expectedAmount,
            'paid_amount' => 0,
            'due_amount' => $expectedAmount,
            'extra_paid_amount' => 0,
            'status_key' => 'not_paid',
            'status_label' => 'Not Paid',
            'is_paid' => false,
            'notes' => null,
        ];
    }

    private function resequenceSlots(Chit $chit): void
    {
        $slots = ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'user_id', 'display_order', 'slot_sequence']);

        $displayOrder = 1;
        $sequenceByUser = [];

        foreach ($slots as $slot) {
            $userId = (int) $slot->user_id;
            $sequenceByUser[$userId] = ($sequenceByUser[$userId] ?? 0) + 1;
            $slotSequence = $sequenceByUser[$userId];

            if ((int) $slot->display_order !== $displayOrder || (int) $slot->slot_sequence !== $slotSequence) {
                $slot->forceFill([
                    'display_order' => $displayOrder,
                    'slot_sequence' => $slotSequence,
                ])->save();
            }

            $displayOrder++;
        }

        $chit->forceFill([
            'total_slots_assigned' => $slots->count(),
        ])->save();
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
