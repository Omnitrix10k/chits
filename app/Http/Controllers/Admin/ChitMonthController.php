<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chit;
use App\Models\ChitMonth;
use App\Models\ChitMemberPayment;
use App\Models\ChitMemberSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChitMonthController extends Controller
{
    public function initialize(Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $currentMonth = $chit->resolvedCurrentMonth();

        if ($month !== $currentMonth) {
            return $this->paymentsRedirect($chit, $month, 'only-current-month-can-be-initialized');
        }

        if ((int) $monthRecord->status_code === ChitMonth::STATUS_PENDING) {
            $monthRecord->forceFill([
                'status_code' => ChitMonth::STATUS_OPEN,
                'initialized_at' => now(),
            ])->save();
        }

        return $this->paymentsRedirect($chit, $month, 'month-initialized');
    }

    public function saveAuction(Request $request, Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $this->assertCurrentOpenMonth($chit, $monthRecord, $month, 'Auction');

        $committeeInterestAmount = (int) round(((int) $chit->total_amount) * 0.04);
        $maxAuctionAmount = (int) floor(((int) $chit->total_amount) * 0.30);

        $validated = $request->validate([
            'auction_amount' => ['required', 'integer', 'min:'.$committeeInterestAmount, 'max:'.$maxAuctionAmount],
            'winner_slot_id' => ['required', 'integer'],
        ], [
            'auction_amount.min' => 'Auction amount must be at least Rs '.number_format($committeeInterestAmount).' to cover committee interest.',
        ]);

        $winnerSlotId = (int) $validated['winner_slot_id'];
        $slot = ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->where('id', $winnerSlotId)
            ->first();

        if (! $slot) {
            throw ValidationException::withMessages([
                'winner_slot_id' => 'Selected winner is not part of this chit.',
            ]);
        }

        $wasWinnerBefore = ChitMonth::query()
            ->where('chit_id', $chit->id)
            ->where('month_number', '<', $month)
            ->where('auction_winner_slot_id', $winnerSlotId)
            ->exists();

        if ($wasWinnerBefore) {
            throw ValidationException::withMessages([
                'winner_slot_id' => 'Selected winner already won in a previous month.',
            ]);
        }

        $monthRecord->forceFill([
            'auction_amount' => (int) $validated['auction_amount'],
            'auction_winner_slot_id' => $winnerSlotId,
            'auction_recorded_at' => now(),
        ])->save();

        return $this->paymentsRedirect($chit, $month, 'auction-saved');
    }

    public function markAllPaid(Request $request, Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $this->assertCurrentOpenMonth($chit, $monthRecord, $month, 'Mark all paid');

        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $slotIds = ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->pluck('id')
            ->all();

        foreach ($slotIds as $slotId) {
            ChitMemberPayment::query()->updateOrCreate(
                [
                    'chit_member_slot_id' => (int) $slotId,
                    'month_number' => $month,
                ],
                [
                    'chit_id' => $chit->id,
                    'expected_amount' => $expectedAmount,
                    'paid_amount' => $expectedAmount,
                    'due_amount' => 0,
                    'extra_paid_amount' => 0,
                    'status_code' => ChitMemberPayment::STATUS_PAID,
                    'is_paid' => true,
                    'paid_at' => now(),
                    'recorded_by' => $request->user()?->id,
                    'notes' => null,
                ]
            );
        }

        return $this->paymentsRedirect($chit, $month, 'all-members-marked-paid');
    }

    public function resetMonth(Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $currentMonth = $chit->resolvedCurrentMonth();

        if ($month !== $currentMonth || (int) $monthRecord->status_code === ChitMonth::STATUS_CLOSED) {
            return $this->paymentsRedirect($chit, $month, 'only-open-current-month-can-be-reset');
        }

        ChitMemberPayment::query()
            ->where('chit_id', $chit->id)
            ->where('month_number', $month)
            ->delete();

        $monthRecord->forceFill([
            'status_code' => ChitMonth::STATUS_OPEN,
            'initialized_at' => $monthRecord->initialized_at ?: now(),
            'auction_amount' => null,
            'auction_winner_slot_id' => null,
            'auction_recorded_at' => null,
            'closed_at' => null,
        ])->save();

        return $this->paymentsRedirect($chit, $month, 'month-reset');
    }

    public function closeMonth(Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $this->assertCurrentOpenMonth($chit, $monthRecord, $month, 'Close month');

        $totalSlots = (int) ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->count();

        $paidSlots = (int) ChitMemberPayment::query()
            ->where('chit_id', $chit->id)
            ->where('month_number', $month)
            ->where('status_code', ChitMemberPayment::STATUS_PAID)
            ->distinct('chit_member_slot_id')
            ->count('chit_member_slot_id');

        if ($totalSlots < 1 || $paidSlots !== $totalSlots) {
            return $this->paymentsRedirect($chit, $month, 'all-members-must-be-paid-before-closing');
        }

        $monthRecord->forceFill([
            'status_code' => ChitMonth::STATUS_CLOSED,
            'closed_at' => now(),
            'initialized_at' => $monthRecord->initialized_at ?: now(),
        ])->save();

        $durationMonths = max(1, (int) $chit->duration_months);
        $currentMonth = $chit->resolvedCurrentMonth();
        $nextMonth = min($durationMonths, $currentMonth + 1);

        if ($currentMonth < $durationMonths) {
            $chit->forceFill(['current_month' => $nextMonth])->save();
            $chit->ensureMonthTimeline();
        }

        return $this->paymentsRedirect($chit, $month, 'month-closed');
    }

    public function updatePayment(Request $request, Chit $chit, int $month, ChitMemberSlot $slot): RedirectResponse
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        $currentMonth = $chit->resolvedCurrentMonth();
        if ($month < 1 || $month > $currentMonth) {
            return $this->paymentsRedirect($chit, $currentMonth, 'invalid-month');
        }

        $statusKeys = array_keys(ChitMemberPayment::statusOptionsByKey());
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in($statusKeys)],
            'paid_amount' => ['required', 'integer', 'min:0', 'max:999999999'],
        ]);

        $paymentStatus = (string) $validated['payment_status'];
        $paidAmount = max(0, (int) $validated['paid_amount']);
        $expectedAmount = max(0, (int) $chit->monthly_amount);

        if ($paymentStatus === 'paid') {
            $paidAmount = max($paidAmount, $expectedAmount);
        } elseif ($paymentStatus === 'not_paid') {
            $paidAmount = 0;
        } else {
            if ($paidAmount <= 0) {
                $paymentStatus = 'not_paid';
                $paidAmount = 0;
            } elseif ($paidAmount >= $expectedAmount) {
                $paymentStatus = 'paid';
            }
        }

        $statusCode = ChitMemberPayment::statusCodeFromKey($paymentStatus) ?? ChitMemberPayment::STATUS_NOT_PAID;
        $dueAmount = max($expectedAmount - $paidAmount, 0);
        $extraPaidAmount = max($paidAmount - $expectedAmount, 0);
        $isPaid = $statusCode === ChitMemberPayment::STATUS_PAID;

        ChitMemberPayment::query()->updateOrCreate(
            [
                'chit_member_slot_id' => $slot->id,
                'month_number' => $month,
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
                'notes' => null,
            ]
        );

        return $this->paymentsRedirect($chit, $month, 'payment-updated');
    }

    public function bulkUpdateStatus(Request $request, Chit $chit, int $month): RedirectResponse
    {
        $monthRecord = $this->resolveMonthRecord($chit, $month);
        $this->assertCurrentOpenMonth($chit, $monthRecord, $month, 'Bulk payment update');

        $validated = $request->validate([
            'selected_slots' => ['required', 'array', 'min:1'],
            'selected_slots.*' => ['integer'],
            'bulk_status' => ['required', Rule::in(['paid', 'not_paid'])],
        ]);

        $slotIds = collect($validated['selected_slots'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($slotIds === []) {
            throw ValidationException::withMessages([
                'selected_slots' => 'Please select at least one member row.',
            ]);
        }

        $validSlotCount = ChitMemberSlot::query()
            ->where('chit_id', $chit->id)
            ->whereIn('id', $slotIds)
            ->count();

        if ($validSlotCount !== count($slotIds)) {
            throw ValidationException::withMessages([
                'selected_slots' => 'Invalid row selection.',
            ]);
        }

        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $bulkStatus = (string) $validated['bulk_status'];

        foreach ($slotIds as $slotId) {
            if ($bulkStatus === 'paid') {
                ChitMemberPayment::query()->updateOrCreate(
                    [
                        'chit_member_slot_id' => (int) $slotId,
                        'month_number' => $month,
                    ],
                    [
                        'chit_id' => $chit->id,
                        'expected_amount' => $expectedAmount,
                        'paid_amount' => $expectedAmount,
                        'due_amount' => 0,
                        'extra_paid_amount' => 0,
                        'status_code' => ChitMemberPayment::STATUS_PAID,
                        'is_paid' => true,
                        'paid_at' => now(),
                        'recorded_by' => $request->user()?->id,
                        'notes' => null,
                    ]
                );
            } else {
                ChitMemberPayment::query()->updateOrCreate(
                    [
                        'chit_member_slot_id' => (int) $slotId,
                        'month_number' => $month,
                    ],
                    [
                        'chit_id' => $chit->id,
                        'expected_amount' => $expectedAmount,
                        'paid_amount' => 0,
                        'due_amount' => $expectedAmount,
                        'extra_paid_amount' => 0,
                        'status_code' => ChitMemberPayment::STATUS_NOT_PAID,
                        'is_paid' => false,
                        'paid_at' => null,
                        'recorded_by' => $request->user()?->id,
                        'notes' => null,
                    ]
                );
            }
        }

        return $this->paymentsRedirect($chit, $month, 'bulk-status-updated');
    }

    public function invoice(Chit $chit, int $month, ChitMemberSlot $slot): View
    {
        $this->assertSlotBelongsToChit($chit, $slot);

        $currentMonth = $chit->resolvedCurrentMonth();
        if ($month < 1 || $month > $currentMonth) {
            abort(404);
        }

        $slot->load([
            'user:id,name,email,mobile_number,primary_phone,address,profile_image_path',
            'payments' => static function ($query) use ($month): void {
                $query->where('month_number', $month)->limit(1);
            },
        ]);

        $payment = $slot->payments->first();
        $expectedAmount = max(0, (int) $chit->monthly_amount);
        $status = $payment
            ? (ChitMemberPayment::statusFromCode((int) $payment->status_code) ?? ['key' => 'not_paid', 'label' => 'Not Paid'])
            : ['key' => 'not_paid', 'label' => 'Not Paid'];

        return view('admin.chits.invoice', [
            'chit' => $chit,
            'slot' => $slot,
            'monthNumber' => $month,
            'payment' => $payment,
            'invoice' => [
                'status_key' => $status['key'],
                'status_label' => $status['label'],
                'expected_amount' => $payment ? (int) $payment->expected_amount : $expectedAmount,
                'paid_amount' => $payment ? (int) $payment->paid_amount : 0,
                'due_amount' => $payment ? (int) $payment->due_amount : $expectedAmount,
                'extra_paid_amount' => $payment ? (int) $payment->extra_paid_amount : 0,
                'paid_at' => $payment?->paid_at,
                'payment_date' => $payment?->paid_at ?: $payment?->updated_at ?: $payment?->created_at,
            ],
        ]);
    }

    private function resolveMonthRecord(Chit $chit, int $month): ChitMonth
    {
        $durationMonths = max(1, (int) $chit->duration_months);
        if ($month < 1 || $month > $durationMonths) {
            abort(404);
        }

        return $chit->ensureMonthTimeline()->firstWhere('month_number', $month)
            ?? ChitMonth::query()->create([
                'chit_id' => $chit->id,
                'month_number' => $month,
                'status_code' => ChitMonth::STATUS_PENDING,
            ]);
    }

    private function assertSlotBelongsToChit(Chit $chit, ChitMemberSlot $slot): void
    {
        if ((int) $slot->chit_id !== (int) $chit->id) {
            abort(404);
        }
    }

    private function assertCurrentOpenMonth(Chit $chit, ChitMonth $monthRecord, int $month, string $context): void
    {
        $currentMonth = $chit->resolvedCurrentMonth();
        if ($month !== $currentMonth || (int) $monthRecord->status_code !== ChitMonth::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'month' => $context.' is allowed only for the open current month.',
            ]);
        }
    }

    private function paymentsRedirect(Chit $chit, int $month, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.chits.show', [
                'chit' => $chit->id,
                'tab' => 'payments',
                'month' => $month,
            ])
            ->with('status', $status);
    }
}
