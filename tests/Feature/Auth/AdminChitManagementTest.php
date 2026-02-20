<?php

use App\Models\Chit;
use App\Models\ChitMonth;
use App\Models\ChitMember;
use App\Models\ChitMemberPayment;
use App\Models\ChitMemberSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('blocks non-admin users from chit routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $chit = Chit::query()->create([
        'chit_name' => 'Access Test Chit',
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 20,
        'current_month' => 1,
        'member_limit' => 1,
        'monthly_amount' => 100000,
        'total_slots_assigned' => 1,
        'status_code' => 1,
        'created_by' => $user->id,
    ]);

    $payload = [
        'chit_name' => 'Access Update Chit',
        'chit_type' => 'auction',
        'total_amount' => 100000,
        'member_limit' => 1,
        'duration_months' => 20,
        'selected_members' => json_encode([$user->id => 1]),
    ];

    $this->actingAs($user)->get('/admin/chits')->assertForbidden();
    $this->actingAs($user)->get('/admin/chits/create')->assertForbidden();
    $this->actingAs($user)->post('/admin/chits', $payload)->assertForbidden();
    $this->actingAs($user)->get('/admin/chits/'.$chit->id.'/edit')->assertForbidden();
    $this->actingAs($user)->patch('/admin/chits/'.$chit->id, $payload)->assertForbidden();
    $this->actingAs($user)->delete('/admin/chits/'.$chit->id)->assertForbidden();
});

it('requires correct admin password to delete a chit', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $chit = Chit::query()->create([
        'chit_name' => 'Protected Delete Chit',
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 20,
        'current_month' => 1,
        'member_limit' => 1,
        'monthly_amount' => 100000,
        'total_slots_assigned' => 1,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->from('/admin/chits')
        ->delete('/admin/chits/'.$chit->id, [
            'password' => 'wrong-password',
            'delete_chit_id' => (string) $chit->id,
        ])
        ->assertRedirect('/admin/chits')
        ->assertSessionHasErrors(['password'], null, 'deleteChit');

    $this->assertDatabaseHas('chits', ['id' => $chit->id]);

    $this->actingAs($admin)
        ->delete('/admin/chits/'.$chit->id, [
            'password' => 'password',
            'delete_chit_id' => (string) $chit->id,
        ])
        ->assertRedirect('/admin/chits');

    $this->assertDatabaseMissing('chits', ['id' => $chit->id]);
});

it('allows editor to view chit pages but blocks write actions', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
    $member = User::factory()->create(['role' => User::ROLE_USER]);

    $chit = Chit::query()->create([
        'chit_name' => 'Editor Access Chit',
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
    ]);

    $this->actingAs($editor)->get('/admin/chits')->assertOk();
    $this->actingAs($editor)->get('/admin/chits/'.$chit->id)->assertOk();
    $this->actingAs($editor)->get('/admin/chits/'.$chit->id.'/members/'.$slot->id)->assertOk();

    $this->actingAs($editor)->get('/admin/chits/create')->assertForbidden();
    $this->actingAs($editor)->post('/admin/chits', [
        'chit_name' => 'Editor Write Block',
        'chit_type' => 'auction',
        'total_amount' => 100000,
        'member_limit' => 1,
        'duration_months' => 20,
        'selected_members' => json_encode([$member->id => 1]),
    ])->assertForbidden();
    $this->actingAs($editor)->delete('/admin/chits/'.$chit->id, [
        'password' => 'password',
        'delete_chit_id' => (string) $chit->id,
    ])->assertForbidden();
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

it('uses member referrer when available and falls back to no one for chit slots', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'name' => 'Primary Admin',
    ]);

    $memberWithReferrer = User::factory()->create([
        'role' => User::ROLE_USER,
        'referred_by_name' => 'External Referrer',
    ]);

    $memberWithoutReferrer = User::factory()->create([
        'role' => User::ROLE_USER,
        'referred_by_name' => null,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits', [
            'chit_name' => 'Referrer Check Chit',
            'chit_type' => 'fixed',
            'total_amount' => 100000,
            'member_limit' => 2,
            'duration_months' => 10,
            'selected_members' => json_encode([
                $memberWithReferrer->id => 1,
                $memberWithoutReferrer->id => 1,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit = Chit::query()->latest('id')->firstOrFail();

    $this->assertDatabaseHas('chit_member_slots', [
        'chit_id' => $chit->id,
        'user_id' => $memberWithReferrer->id,
        'referred_by_name' => 'External Referrer',
    ]);

    $this->assertDatabaseHas('chit_member_slots', [
        'chit_id' => $chit->id,
        'user_id' => $memberWithoutReferrer->id,
        'referred_by_name' => null,
    ]);

    $this->actingAs($admin)
        ->get('/admin/chits/'.$chit->id.'?tab=overview')
        ->assertOk()
        ->assertSee('No One');
});

it('shows updated member referrer in chit overview even if slot has older fallback referrer', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'name' => 'Primary Admin',
    ]);

    $member = User::factory()->create([
        'role' => User::ROLE_USER,
        'name' => 'Member Ref',
        'referred_by_name' => null,
    ]);

    $chit = Chit::query()->create([
        'chit_name' => 'Referrer Update Chit',
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 10,
        'current_month' => 1,
        'member_limit' => 1,
        'monthly_amount' => 100000,
        'total_slots_assigned' => 1,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $aggregate = ChitMember::query()->create([
        'chit_id' => $chit->id,
        'user_id' => $member->id,
        'slot_count' => 1,
    ]);

    ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregate->id,
        'user_id' => $member->id,
        'slot_sequence' => 1,
        'display_order' => 1,
        'referred_by_name' => 'Primary Admin',
    ]);

    $member->update([
        'referred_by_name' => 'Updated Referrer',
    ]);

    $this->actingAs($admin)
        ->get('/admin/chits/'.$chit->id.'?tab=overview')
        ->assertOk()
        ->assertSee('Updated Referrer');
});

it('shows no one when member referrer is empty even if slot has legacy referrer', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'name' => 'Primary Admin',
    ]);

    $member = User::factory()->create([
        'role' => User::ROLE_USER,
        'name' => 'Legacy Slot Member',
        'referred_by_name' => null,
    ]);

    $chit = Chit::query()->create([
        'chit_name' => 'Legacy Ref Chit',
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 10,
        'current_month' => 1,
        'member_limit' => 1,
        'monthly_amount' => 100000,
        'total_slots_assigned' => 1,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $aggregate = ChitMember::query()->create([
        'chit_id' => $chit->id,
        'user_id' => $member->id,
        'slot_count' => 1,
    ]);

    ChitMemberSlot::query()->create([
        'chit_id' => $chit->id,
        'chit_member_id' => $aggregate->id,
        'user_id' => $member->id,
        'slot_sequence' => 1,
        'display_order' => 1,
        'referred_by_name' => 'Legacy Slot Referrer',
    ]);

    $this->actingAs($admin)
        ->get('/admin/chits/'.$chit->id.'?tab=overview')
        ->assertOk()
        ->assertSee('No One')
        ->assertDontSee('Legacy Slot Referrer');
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
        ->assertSee('Due')
        ->assertSee('wa.me')
        ->assertSee('invoice');
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

it('allows admin to edit and delete a chit', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(4)->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->post('/admin/chits', [
            'chit_name' => 'Launch Chit',
            'chit_type' => 'auction',
            'total_amount' => 100000,
            'member_limit' => 4,
            'duration_months' => 20,
            'selected_members' => json_encode([
                $members[0]->id => 2,
                $members[1]->id => 2,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit = Chit::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->patch('/admin/chits/'.$chit->id, [
            'chit_name' => 'Prime Growth Chit',
            'chit_type' => 'fixed',
            'total_amount' => 240000,
            'member_limit' => 4,
            'duration_months' => 24,
            'selected_members' => json_encode([
                $members[0]->id => 3,
                $members[2]->id => 1,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit->refresh();

    expect($chit->chit_name)->toBe('Prime Growth Chit');
    expect($chit->chit_type_code)->toBe(2);
    expect($chit->total_amount)->toBe(240000);
    expect($chit->duration_months)->toBe(24);
    expect($chit->member_limit)->toBe(4);
    expect($chit->monthly_amount)->toBe(60000);
    expect($chit->total_slots_assigned)->toBe(4);

    $this->assertDatabaseHas('chit_members', [
        'chit_id' => $chit->id,
        'user_id' => $members[0]->id,
        'slot_count' => 3,
    ]);

    $this->assertDatabaseHas('chit_members', [
        'chit_id' => $chit->id,
        'user_id' => $members[2]->id,
        'slot_count' => 1,
    ]);

    $this->assertDatabaseMissing('chit_members', [
        'chit_id' => $chit->id,
        'user_id' => $members[1]->id,
    ]);

    expect((int) DB::table('chit_members')->where('chit_id', $chit->id)->sum('slot_count'))->toBe(4);
    expect(ChitMemberSlot::query()->where('chit_id', $chit->id)->count())->toBe(4);

    $this->actingAs($admin)
        ->delete('/admin/chits/'.$chit->id, [
            'password' => 'password',
            'delete_chit_id' => (string) $chit->id,
        ])
        ->assertRedirect('/admin/chits');

    $this->assertDatabaseMissing('chits', ['id' => $chit->id]);
    $this->assertDatabaseMissing('chit_members', ['chit_id' => $chit->id]);
    $this->assertDatabaseMissing('chit_member_slots', ['chit_id' => $chit->id]);
});

it('enforces month initialization and close progression before next month access', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(2)->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->post('/admin/chits', [
            'chit_name' => 'Cycle Flow Chit',
            'chit_type' => 'auction',
            'total_amount' => 100000,
            'member_limit' => 2,
            'duration_months' => 3,
            'selected_members' => json_encode([
                $members[0]->id => 1,
                $members[1]->id => 1,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit = Chit::query()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/1/initialize')
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 1,
        'status_code' => ChitMonth::STATUS_OPEN,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/2/initialize')
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=2');

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 2,
        'status_code' => ChitMonth::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/1/mark-all-paid')
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/1/close')
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $chit->refresh();
    expect($chit->current_month)->toBe(2);

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 1,
        'status_code' => ChitMonth::STATUS_CLOSED,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/2/initialize')
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=2');

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 2,
        'status_code' => ChitMonth::STATUS_OPEN,
    ]);
});

it('validates auction cap and prevents selecting same slot winner in later month', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(2)->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->post('/admin/chits', [
            'chit_name' => 'Auction Flow Chit',
            'chit_type' => 'auction',
            'total_amount' => 100000,
            'member_limit' => 2,
            'duration_months' => 3,
            'selected_members' => json_encode([
                $members[0]->id => 1,
                $members[1]->id => 1,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit = Chit::query()->latest('id')->firstOrFail();
    $slots = ChitMemberSlot::query()
        ->where('chit_id', $chit->id)
        ->orderBy('id')
        ->get();

    $slotA = $slots->firstOrFail();
    $slotB = $slots->last();
    expect($slotB)->not->toBeNull();

    $this->actingAs($admin)->post('/admin/chits/'.$chit->id.'/months/1/initialize');

    $this->actingAs($admin)
        ->from('/admin/chits/'.$chit->id.'?tab=payments&month=1')
        ->patch('/admin/chits/'.$chit->id.'/months/1/auction', [
            'auction_amount' => 35000,
            'winner_slot_id' => $slotA->id,
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1')
        ->assertSessionHasErrors('auction_amount');

    $this->actingAs($admin)
        ->patch('/admin/chits/'.$chit->id.'/months/1/auction', [
            'auction_amount' => 25000,
            'winner_slot_id' => $slotA->id,
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 1,
        'auction_amount' => 25000,
        'auction_winner_slot_id' => $slotA->id,
    ]);

    $this->actingAs($admin)->post('/admin/chits/'.$chit->id.'/months/1/mark-all-paid');
    $this->actingAs($admin)->post('/admin/chits/'.$chit->id.'/months/1/close');
    $this->actingAs($admin)->post('/admin/chits/'.$chit->id.'/months/2/initialize');

    $this->actingAs($admin)
        ->from('/admin/chits/'.$chit->id.'?tab=payments&month=2')
        ->patch('/admin/chits/'.$chit->id.'/months/2/auction', [
            'auction_amount' => 20000,
            'winner_slot_id' => $slotA->id,
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=2')
        ->assertSessionHasErrors('winner_slot_id');

    $this->actingAs($admin)
        ->patch('/admin/chits/'.$chit->id.'/months/2/auction', [
            'auction_amount' => 20000,
            'winner_slot_id' => $slotB->id,
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=2');

    $this->assertDatabaseHas('chit_months', [
        'chit_id' => $chit->id,
        'month_number' => 2,
        'auction_winner_slot_id' => $slotB->id,
    ]);
});

it('updates selected member rows in bulk to paid or not paid', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $members = User::factory()->count(3)->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
        ->post('/admin/chits', [
            'chit_name' => 'Bulk Row Update Chit',
            'chit_type' => 'fixed',
            'total_amount' => 120000,
            'member_limit' => 3,
            'duration_months' => 5,
            'selected_members' => json_encode([
                $members[0]->id => 1,
                $members[1]->id => 1,
                $members[2]->id => 1,
            ]),
        ])
        ->assertRedirect('/admin/chits');

    $chit = Chit::query()->latest('id')->firstOrFail();
    $slots = ChitMemberSlot::query()
        ->where('chit_id', $chit->id)
        ->orderBy('id')
        ->get();

    $this->actingAs($admin)->post('/admin/chits/'.$chit->id.'/months/1/initialize');

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/1/bulk-status', [
            'bulk_status' => 'paid',
            'selected_slots' => [$slots[0]->id, $slots[1]->id],
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $this->assertDatabaseHas('chit_member_slot_payments', [
        'chit_member_slot_id' => $slots[0]->id,
        'month_number' => 1,
        'status_code' => ChitMemberPayment::STATUS_PAID,
        'due_amount' => 0,
        'notes' => null,
    ]);

    $this->assertDatabaseHas('chit_member_slot_payments', [
        'chit_member_slot_id' => $slots[1]->id,
        'month_number' => 1,
        'status_code' => ChitMemberPayment::STATUS_PAID,
        'due_amount' => 0,
        'notes' => null,
    ]);

    $this->actingAs($admin)
        ->post('/admin/chits/'.$chit->id.'/months/1/bulk-status', [
            'bulk_status' => 'not_paid',
            'selected_slots' => [$slots[1]->id],
        ])
        ->assertRedirect('/admin/chits/'.$chit->id.'?tab=payments&month=1');

    $this->assertDatabaseHas('chit_member_slot_payments', [
        'chit_member_slot_id' => $slots[1]->id,
        'month_number' => 1,
        'status_code' => ChitMemberPayment::STATUS_NOT_PAID,
        'paid_amount' => 0,
        'due_amount' => 40000,
        'notes' => null,
    ]);
});
