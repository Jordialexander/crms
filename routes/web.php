<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImplementationController;
use App\Http\Controllers\NeedReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ApprovalRuleController;
use App\Http\Controllers\CrOptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskAssessmentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UnderReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('authenticate');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', fn () => redirect()->route('dashboard'));

// Protected routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Change Request
    Route::prefix('change-request')->name('cr.')->group(function () {
        Route::get('/', [ChangeRequestController::class, 'index'])->name('index');
        Route::get('/create', [ChangeRequestController::class, 'create'])->name('create');
        Route::post('/', [ChangeRequestController::class, 'store'])->name('store');
        Route::get('/{cr}', [ChangeRequestController::class, 'show'])->name('show');
        Route::get('/{cr}/edit', [ChangeRequestController::class, 'edit'])->name('edit');
        Route::put('/{cr}', [ChangeRequestController::class, 'update'])->name('update');
        Route::delete('/{cr}', [ChangeRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{cr}/submit', [ChangeRequestController::class, 'submit'])->name('submit');
        Route::post('/{cr}/cancel', [ChangeRequestController::class, 'cancel'])->name('cancel');
        Route::post('/{cr}/close-rejected', [ChangeRequestController::class, 'closeRejected'])->name('close-rejected');
        Route::post('/{cr}/start-implementation', [ChangeRequestController::class, 'startImplementation'])->name('start-implementation');

        // Risk Assessment (scoped under a CR)
        Route::post('/{cr}/risk-assessment', [RiskAssessmentController::class, 'store'])->name('risk.store');

        // Schedule
        Route::post('/{cr}/schedule', [ScheduleController::class, 'store'])->name('schedule.store');

        // Implementation
        Route::post('/{cr}/implementation', [ImplementationController::class, 'store'])->name('implementation.store');
        Route::post('/{cr}/post-mortem', [ImplementationController::class, 'postMortem'])->name('post-mortem');
        Route::post('/{cr}/close', [ImplementationController::class, 'close'])->name('close');

        // Reschedule decision (approver L1, for failed/rollback CRs)
        Route::post('/{cr}/reschedule', [ScheduleController::class, 'reschedule'])->name('reschedule');
        Route::post('/{cr}/reschedule-decision', [ApprovalController::class, 'rescheduleDecision'])->name('reschedule-decision');

        // Attachments & Evidence download
        Route::get('/{cr}/attachment/{attachment}/download', [AttachmentController::class, 'download'])->name('attachment.download');
        Route::get('/{cr}/evidence/{log}/download', [AttachmentController::class, 'downloadEvidence'])->name('evidence.download');
    });

    // Need Review
    Route::prefix('need-review')->name('need-review.')->group(function () {
        Route::get('/', [NeedReviewController::class, 'index'])->name('index');
        Route::get('/{cr}', [NeedReviewController::class, 'show'])->name('show');
        Route::post('/{cr}/start', [NeedReviewController::class, 'startReview'])->name('start');
    });

    // Under Review
    Route::prefix('under-review')->name('under-review.')->group(function () {
        Route::get('/', [UnderReviewController::class, 'index'])->name('index');
        Route::get('/{cr}', [UnderReviewController::class, 'show'])->name('show');
        Route::post('/{cr}/submit', [UnderReviewController::class, 'submitForApproval'])->name('submit');
    });

    // Approval
    Route::prefix('approval')->name('approval.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{cr}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{cr}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{cr}/reject', [ApprovalController::class, 'reject'])->name('reject');
    });

    // Schedule
    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
    });

    // Report
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('pdf');
        Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('excel');
    });

    // Users
    Route::prefix('users')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Approval Rules (Admin)
    Route::prefix('approval-rule')->name('approval-rule.')->group(function () {
        Route::get('/', [ApprovalRuleController::class, 'index'])->name('index');
        Route::get('/create', [ApprovalRuleController::class, 'create'])->name('create');
        Route::post('/', [ApprovalRuleController::class, 'store'])->name('store');
        Route::get('/{approvalRule}/edit', [ApprovalRuleController::class, 'edit'])->name('edit');
        Route::put('/{approvalRule}', [ApprovalRuleController::class, 'update'])->name('update');
        Route::delete('/{approvalRule}', [ApprovalRuleController::class, 'destroy'])->name('destroy');
    });

    // Activity Log / Timeline (per-CR, akses sesuai akses CR)
    Route::get('/activity-log/{cr}', [ActivityLogController::class, 'show'])->name('activity-log.show');
    Route::get('/activity-log/{cr}/more', [ActivityLogController::class, 'loadMore'])->name('activity-log.more');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [NotificationController::class, 'readAll'])->name('readAll');
        Route::post('/{id}/open', [NotificationController::class, 'open'])->name('open');
    });

    // CR Options (Admin) — per-type pages
    Route::prefix('cr-options')->name('cr-options.')->group(function () {
        Route::get('/{type}', [CrOptionController::class, 'index'])->name('index');
        Route::post('/{type}', [CrOptionController::class, 'store'])->name('store');
        Route::put('/{type}/{crOption}', [CrOptionController::class, 'update'])->name('update');
        Route::post('/{type}/{crOption}/toggle', [CrOptionController::class, 'toggleActive'])->name('toggle');
    });

    // Roles (Admin/Supervisor)
    Route::prefix('roles')->name('role.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

});
