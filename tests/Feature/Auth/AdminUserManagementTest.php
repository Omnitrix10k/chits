<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('blocks non-admin users from admin management routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/members')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/editors')
        ->assertForbidden();
});

it('allows admin to create a member with surety details and government id pdf', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post('/admin/members', [
        'first_name' => 'Member',
        'last_name' => 'One',
        'email' => 'member1@example.com',
        'mobile_number' => '+15555550111',
        'government_id' => UploadedFile::fake()->create('member-id.pdf', 100, 'application/pdf'),
        'surety_name' => 'Surety Person',
        'surety_relation' => 'father',
        'surety_phone_number' => '+15555550112',
        'surety_address' => 'Surety Address',
        'surety_government_id' => 'SURETY123',
        'surety_bank_name' => 'ABC Bank',
        'surety_cheque_book_number' => 'CHQ1001',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/admin/members');

    $member = User::query()->where('email', 'member1@example.com')->first();

    expect($member)->not->toBeNull();
    expect($member->role)->toBe(User::ROLE_USER);
    expect($member->family_name)->toBe('Surety Person');
    expect($member->government_id_path)->not->toBeNull();

    Storage::disk('local')->assertExists($member->government_id_path);
});

it('allows admin to create an editor with basic fields only', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post('/admin/editors', [
        'name' => 'Editor One',
        'email' => 'editor1@example.com',
        'mobile_number' => '+15555550113',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/admin/editors');

    $editor = User::query()->where('email', 'editor1@example.com')->first();

    expect($editor)->not->toBeNull();
    expect($editor->role)->toBe(User::ROLE_EDITOR);
    expect($editor->family_name)->toBeNull();
});

it('allows admin to update and delete members and editors', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $member = User::factory()->create([
        'role' => User::ROLE_USER,
        'first_name' => 'Old',
        'last_name' => 'Member',
        'name' => 'Old Member',
        'email' => 'old-member@example.com',
        'mobile_number' => '+15555550114',
        'primary_phone' => '+15555550114',
        'family_name' => 'Old Surety',
        'family_relation' => 'father',
        'family_phone_number' => '+15555550115',
        'family_address' => 'Old Surety Address',
        'family_government_id' => 'OLDGI',
        'family_bank_name' => 'Old Bank',
        'family_cheque_number' => 'OLDCHQ',
    ]);

    $editor = User::factory()->create([
        'role' => User::ROLE_EDITOR,
        'name' => 'Old Editor',
        'email' => 'old-editor@example.com',
        'mobile_number' => '+15555550116',
        'primary_phone' => '+15555550116',
    ]);

    $this->actingAs($admin)
        ->patch('/admin/members/'.$member->id, [
            'first_name' => 'New',
            'last_name' => 'Member',
            'email' => 'new-member@example.com',
            'mobile_number' => '+15555550117',
            'surety_name' => 'New Surety',
            'surety_relation' => 'mother',
            'surety_phone_number' => '+15555550118',
            'surety_address' => 'New Surety Address',
            'surety_government_id' => 'NEWGI',
            'surety_bank_name' => 'New Bank',
            'surety_cheque_book_number' => 'NEWCHQ',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect('/admin/members');

    $this->actingAs($admin)
        ->patch('/admin/editors/'.$editor->id, [
            'name' => 'New Editor',
            'email' => 'new-editor@example.com',
            'mobile_number' => '+15555550119',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect('/admin/editors');

    expect($member->refresh()->first_name)->toBe('New');
    expect($editor->refresh()->name)->toBe('New Editor');

    $this->actingAs($admin)->delete('/admin/members/'.$member->id)->assertRedirect('/admin/members');
    $this->actingAs($admin)->delete('/admin/editors/'.$editor->id)->assertRedirect('/admin/editors');

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
    $this->assertDatabaseMissing('users', ['id' => $editor->id]);
});
