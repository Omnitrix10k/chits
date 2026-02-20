<?php

use App\Models\Chit;
use App\Models\ChitMember;
use App\Models\ChitMemberPayment;
use App\Models\ChitMemberSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('blocks non-admin users from chit routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $payload = [
        'chit_name' => 'one_lakh',
        'chit_type' => 'auction',
        'total_amount' => 100000,
        'member_limit' => 20,
        'duration_months' => 20,
        'selected_members' => json_encode([$user->id => 20]),
    ];

    $this->actingAs($user)->get('/admin/chits')->assertForbidden();
    $this->actingAs($user)->get('/admin/chits/create')->assertForbidden();
    $this->actingAs($user)->post('/admin/chits', $payload)->assertForbidden();
});

it('allows admin to create a chit with repeated members up to limit', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(3)->create(['role' => User::ROLE_USER]);

    $selection = [
        $members[0]->id => 9,
        $members[1]->id => 9,
        $members[2]->id => 2,
    ];

    $response = $this->actingAs($admin)->post('/admin/chits', [
        'chit_name' => 'One Lakh Chit',
        'chit_type' => 'auction',
        'total_amount' => 100000,
        'member_limit' => 20,
        'duration_months' => 25,
        'selected_members' => json_encode($selection),
    ]);

    $response->assertRedirect('/admin/chits');

    $chit = Chit::query()->first();

    expect($chit)->not->toBeNull();
    expect($chit->plan_code)->toBe(1);
    expect($chit->chit_type_code)->toBe(1);
    expect($chit->total_amount)->toBe(100000);
    expect($chit->monthly_amount)->toBe(5000);
    expect($chit->member_limit)->toBe(20);
    expect($chit->total_slots_assigned)->toBe(20);

    foreach ($selection as $memberId => $count) {
        $this->assertDatabaseHas('chit_members', [
            'chit_id' => $chit->id,
            'user_id' => $memberId,
            'slot_count' => $count,
        ]);
    }

    $totalSlots = (int) DB::table('chit_members')
        ->where('chit_id', $chit->id)
        ->sum('slot_count');

    expect($totalSlots)->toBe(20);
});

it('rejects chit creation when member slot rules are violated', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(2)->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->from('/admin/chits/create')
        ->post('/admin/chits', [
            'chit_name' => 'one_lakh',
            'chit_type' => 'fixed',
            'total_amount' => 100000,
            'member_limit' => 20,
            'duration_months' => 20,
            'selected_members' => json_encode([
                $members[0]->id => 10,
                $members[1]->id => 10,
            ]),
        ])
        ->assertRedirect('/admin/chits/create')
        ->assertSessionHasErrors('selected_members');

    $this->actingAs($admin)
        ->from('/admin/chits/create')
        ->post('/admin/chits', [
            'chit_name' => 'two_lakh',
            'chit_type' => 'auction',
            'total_amount' => 200000,
            'member_limit' => 20,
            'duration_months' => 24,
            'selected_members' => json_encode([
                $members[0]->id => 6,
                $members[1]->id => 6,
            ]),
        ])
        ->assertRedirect('/admin/chits/create')
        ->assertSessionHasErrors('selected_members');

    $this->assertDatabaseCount('chits', 0);
});

it('shows chit overview with repeated member names and payment statuses', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin']);
    $memberA = User::factory()->create(['role' => User::ROLE_USER, 'name' => 'Member Alpha']);
    $memberB = User::factory()->create(['role' => User::ROLE_USER, 'name' => 'Member Beta']);

    $chit = Chit::query()->create([
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 20,
        'current_month' => 1,
        'member_limit' => 3,
        'monthly_amount' => 5000,
        'total_slots_assigned' => 3,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $aggregateA = ChitMember::query()->create([
        'chit_id' => $chit->id,
        'user_id' => $memberA->id,
        'slot_count' => 2,
    ]);

    $aggregateB = ChitMember::query()->create([
        'chit_id' => $chit->id,
        'user_id' => $memberB->id,
        'slot_count' => 1,
    ]);

    $slotA1 = ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregateA->id,
        'user_id' => $memberA->id,
        'slot_sequence' => 1,
        'display_order' => 1,
        'referred_by_name' => 'Admin',
    ]);

    ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregateA->id,
        'user_id' => $memberA->id,
        'slot_sequence' => 2,
        'display_order' => 2,
        'referred_by_name' => 'Admin',
    ]);

    ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregateB->id,
        'user_id' => $memberB->id,
        'slot_sequence' => 1,
        'display_order' => 3,
        'referred_by_name' => 'Admin',
    ]);

    ChitMemberPayment::query()->create([
        'chit_id' => $chit->id,
        'chit_member_slot_id' => $slotA1->id,
        'month_number' => 1,
        'expected_amount' => 5000,
        'paid_amount' => 3000,
        'due_amount' => 2000,
        'extra_paid_amount' => 0,
        'status_code' => ChitMemberPayment::STATUS_DUE,
        'is_paid' => false,
        'recorded_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/chits/'.$chit->id.'?tab=overview')
        ->assertOk()
        ->assertSee('Member Alpha 1')
        ->assertSee('Member Alpha 2')
        ->assertSee('Member Beta')
        ->assertSee('Due');
});

it('updates payment status for a chit member slot', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create(['role' => User::ROLE_USER]);

    $chit = Chit::query()->create([
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 20,
        'current_month' => 1,
        'member_limit' => 1,
        'monthly_amount' => 5000,
        'total_slots_assigned' => 1,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $aggregate = ChitMember::query()->create([
        'chit_id' => $chit->id,
        'user_id' => $member->id,
        'slot_count' => 1,
    ]);

    $slot = ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregate->id,
        'user_id' => $member->id,
        'slot_sequence' => 1,
        'display_order' => 1,
        'referred_by_name' => 'Admin',
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/members/'.$slot->id.'/payments', [
            'month_number' => 1,
            'payment_status' => 'due',
            'paid_amount' => 3000,
            'notes' => 'first due',
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'/members/'.$slot->id);

    $this->assertDatabaseHas('chit_member_slot_payments', [
        'chit_member_slot_id' => $slot->id,
        'month_number' => 1,
        'status_code' => ChitMemberPayment::STATUS_DUE,
        'due_amount' => 2000,
        'extra_paid_amount' => 0,
        'is_paid' => 0,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/members/'.$slot->id.'/payments', [
            'month_number' => 1,
            'payment_status' => 'due',
            'paid_amount' => 1000,
            'mark_paid' => '1',
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'/members/'.$slot->id);

    $this->assertDatabaseHas('chit_member_slot_payments', [
        'chit_member_slot_id' => $slot->id,
        'month_number' => 1,
        'status_code' => ChitMemberPayment::STATUS_PAID,
        'due_amount' => 0,
        'is_paid' => 1,
    ]);
});
