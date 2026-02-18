<?php

use App\Models\Chit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('blocks non-admin users from chit routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $payload = [
        'chit_name' => 'one_lakh',
        'chit_type' => 'auction',
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
        'chit_name' => 'one_lakh',
        'chit_type' => 'auction',
        'duration_months' => 25,
        'selected_members' => json_encode($selection),
    ]);

    $response->assertRedirect('/admin/chits');

    $chit = Chit::query()->first();

    expect($chit)->not->toBeNull();
    expect($chit->plan_code)->toBe(1);
    expect($chit->chit_type_code)->toBe(1);
    expect($chit->total_amount)->toBe(100000);
    expect($chit->monthly_amount)->toBe(4000);
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
