<?php

use App\Http\Controllers\Admin\ChitController;
use App\Http\Controllers\Admin\ChitMemberController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (Request $request) {
    $periodOptions = [
        'this_month' => 'This Month',
        'last_3_months' => 'Last 3 Months',
        'this_year' => 'This Year',
    ];

    $dashboardPeriod = (string) $request->query('period', 'this_month');
    if (! array_key_exists($dashboardPeriod, $periodOptions)) {
        $dashboardPeriod = 'this_month';
    }

    $rangeStart = null;
    $rangeEnd = now();

    if ($dashboardPeriod === 'this_month') {
        $rangeStart = now()->copy()->startOfMonth();
    } elseif ($dashboardPeriod === 'last_3_months') {
        $rangeStart = now()->copy()->subMonths(2)->startOfMonth();
    } elseif ($dashboardPeriod === 'this_year') {
        $rangeStart = now()->copy()->startOfYear();
    }

    $membersQuery = User::query()->where('role', User::ROLE_USER);
    $editorsQuery = User::query()->where('role', User::ROLE_EDITOR);

    if ($rangeStart) {
        $membersQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        $editorsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
    }

    $totalMembers = $membersQuery->count();
    $totalEditors = $editorsQuery->count();

    $totalChits = 0;
    $totalRevenue = 0.0;

    if (Schema::hasTable('chits')) {
        $chitsQuery = DB::table('chits');

        if ($rangeStart && Schema::hasColumn('chits', 'created_at')) {
            $chitsQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }

        $totalChits = (clone $chitsQuery)->count();

        $revenueColumns = ['total_amount', 'amount', 'revenue'];
        $revenueColumn = collect($revenueColumns)->first(
            fn (string $column): bool => Schema::hasColumn('chits', $column)
        );

        if ($revenueColumn) {
            $totalRevenue = (float) (clone $chitsQuery)->sum($revenueColumn);
        }
    }

    return view('dashboard', [
        'totalMembers' => $totalMembers,
        'totalEditors' => $totalEditors,
        'totalChits' => $totalChits,
        'totalRevenue' => $totalRevenue,
        'dashboardPeriod' => $dashboardPeriod,
        'dashboardPeriodLabel' => $periodOptions[$dashboardPeriod],
        'dashboardPeriodOptions' => $periodOptions,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/chits', [ChitController::class, 'index'])->name('chits.index');
    Route::get('/chits/create', [ChitController::class, 'create'])->name('chits.create');
    Route::post('/chits', [ChitController::class, 'store'])->name('chits.store');
    Route::get('/chits/{chit}/edit', [ChitController::class, 'edit'])->name('chits.edit');
    Route::patch('/chits/{chit}', [ChitController::class, 'update'])->name('chits.update');
    Route::delete('/chits/{chit}', [ChitController::class, 'destroy'])->name('chits.destroy');
    Route::get('/chits/{chit}', [ChitController::class, 'show'])->name('chits.show');
    Route::get('/chits/{chit}/members/{slot}', [ChitMemberController::class, 'show'])->name('chits.members.show');
    Route::patch('/chits/{chit}/members/{slot}', [ChitMemberController::class, 'update'])->name('chits.members.update');
    Route::post('/chits/{chit}/members/{slot}/payments', [ChitMemberController::class, 'storePayment'])->name('chits.members.payments.store');
    Route::delete('/chits/{chit}/members/{slot}', [ChitMemberController::class, 'destroy'])->name('chits.members.destroy');
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
