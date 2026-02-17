<?php

use App\Models\User;

test('non admin users can not access user management', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('admins can create users with available roles', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    foreach (User::ROLES as $index => $role) {
        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => ucfirst($role).' Account',
            'email' => $role.$index.'@example.com',
            'mobile_number' => '+15555550'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'role' => $role,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'email' => $role.$index.'@example.com',
            'role' => $role,
        ]);
    }
});
