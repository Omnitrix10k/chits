<?php

use App\Models\Chit;
use App\Models\ChitMonth;
use App\Models\User;

it('blocks non-admin users from interest report route', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/admin/interest')
        ->assertForbidden();
});

it('shows monthly and per-chit interest summaries for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin']);

    $chitA = Chit::query()->create([
        'chit_name' => 'Growth Fund A',
        'plan_code' => 1,
        'chit_type_code' => 1,
        'total_amount' => 100000,
        'duration_months' => 20,
        'current_month' => 2,
        'member_limit' => 10,
        'monthly_amount' => 10000,
        'total_slots_assigned' => 10,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    $chitB = Chit::query()->create([
        'chit_name' => 'Prime Fund B',
        'plan_code' => 2,
        'chit_type_code' => 1,
        'total_amount' => 200000,
        'duration_months' => 24,
        'current_month' => 1,
        'member_limit' => 10,
        'monthly_amount' => 20000,
        'total_slots_assigned' => 10,
        'status_code' => 1,
        'created_by' => $admin->id,
    ]);

    ChitMonth::query()->create([
        'chit_id' => $chitA->id,
        'month_number' => 1,
        'status_code' => ChitMonth::STATUS_CLOSED,
        'auction_amount' => 30000,
        'auction_recorded_at' => now()->subDays(8),
        'closed_at' => now()->subDays(6),
    ]);

    ChitMonth::query()->create([
        'chit_id' => $chitA->id,
        'month_number' => 2,
        'status_code' => ChitMonth::STATUS_OPEN,
        'auction_amount' => 28000,
        'auction_recorded_at' => now()->subDays(2),
        'closed_at' => null,
    ]);

    ChitMonth::query()->create([
        'chit_id' => $chitB->id,
        'month_number' => 1,
        'status_code' => ChitMonth::STATUS_CLOSED,
        'auction_amount' => 50000,
        'auction_recorded_at' => now()->subDay(),
        'closed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/interest?period=this_year')
        ->assertOk()
        ->assertSee('Interest Analytics')
        ->assertSee('Growth Fund A')
        ->assertSee('Prime Fund B')
        ->assertSee('Month-wise Interest Ledger')
        ->assertSee('16,000');
});

