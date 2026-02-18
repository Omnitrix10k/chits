<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $totalMembers = User::query()->where('role', User::ROLE_USER)->count();
    $totalEditors = User::query()->where('role', User::ROLE_EDITOR)->count();

    $totalChits = 0;
    $totalRevenue = 0.0;

    if (Schema::hasTable('chits')) {
        $totalChits = DB::table('chits')->count();

        $revenueColumns = ['total_amount', 'amount', 'revenue'];
        $revenueColumn = collect($revenueColumns)->first(
            fn (string $column): bool => Schema::hasColumn('chits', $column)
        );

        if ($revenueColumn) {
            $totalRevenue = (float) DB::table('chits')->sum($revenueColumn);
        }
    }

    return view('dashboard', [
        'totalMembers' => $totalMembers,
        'totalEditors' => $totalEditors,
        'totalChits' => $totalChits,
        'totalRevenue' => $totalRevenue,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/chits', 'admin.chits.index')->name('chits.index');
    Route::view('/chits/create', 'admin.chits.create')->name('chits.create');
    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/members', [UserManagementController::class, 'membersIndex'])->name('members.index');
    Route::get('/members/create', [UserManagementController::class, 'createMember'])->name('members.create');
    Route::post('/members', [UserManagementController::class, 'storeMember'])->name('members.store');
    Route::get('/members/{user}/edit', [UserManagementController::class, 'editMember'])->name('members.edit');
    Route::patch('/members/{user}', [UserManagementController::class, 'updateMember'])->name('members.update');
    Route::delete('/members/{user}', [UserManagementController::class, 'destroyMember'])->name('members.destroy');
    Route::get('/members/{user}/government-id', [UserManagementController::class, 'downloadMemberGovernmentId'])->name('members.government-id.download');

    Route::get('/editors', [UserManagementController::class, 'editorsIndex'])->name('editors.index');
    Route::get('/editors/create', [UserManagementController::class, 'createEditor'])->name('editors.create');
    Route::post('/editors', [UserManagementController::class, 'storeEditor'])->name('editors.store');
    Route::get('/editors/{user}/edit', [UserManagementController::class, 'editEditor'])->name('editors.edit');
    Route::patch('/editors/{user}', [UserManagementController::class, 'updateEditor'])->name('editors.update');
    Route::delete('/editors/{user}', [UserManagementController::class, 'destroyEditor'])->name('editors.destroy');
});

require __DIR__.'/auth.php';
