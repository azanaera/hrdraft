<?php

use App\Domain\ATS\Http\Controllers\ApplicationController;
use App\Domain\ATS\Http\Controllers\CandidateController;
use App\Domain\ATS\Http\Controllers\InterviewNoteController;
use App\Domain\ATS\Http\Controllers\JobRequisitionController;
use App\Domain\ATS\Http\Controllers\PipelineStageController;
use App\Domain\Compensation\Http\Controllers\BankingInfoController;
use App\Domain\Compensation\Http\Controllers\CompensationController;
use App\Domain\Documents\Http\Controllers\DocumentCategoryController;
use App\Domain\Documents\Http\Controllers\DocumentController;
use App\Domain\Employee\Http\Controllers\EmployeeController;
use App\Domain\Employee\Http\Controllers\PersonController;
use App\Domain\Notifications\Http\Controllers\NotificationController;
use App\Domain\Onboarding\Http\Controllers\BackgroundCheckController;
use App\Domain\Reporting\Http\Controllers\DashboardController;
use App\Domain\Reporting\Http\Controllers\TurnoverReportController;
use App\Domain\Offboarding\Http\Controllers\OffboardingTaskController;
use App\Domain\Offboarding\Http\Controllers\OffboardingWorkflowController;
use App\Domain\Onboarding\Http\Controllers\OnboardingTaskController;
use App\Domain\Onboarding\Http\Controllers\OnboardingTemplateController;
use App\Domain\Onboarding\Http\Controllers\OnboardingWorkflowController;
use App\Domain\Timeline\Http\Controllers\EmployeeEventController;
use App\Domain\TimeOff\Http\Controllers\TimeOffBalanceController;
use App\Domain\TimeOff\Http\Controllers\TimeOffPolicyController;
use App\Domain\TimeOff\Http\Controllers\TimeOffRequestController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\DocumentCategoryController as AdminDocumentCategoryController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\OnboardingTemplateController as AdminOnboardingTemplateController;
use App\Http\Controllers\Admin\PositionController as AdminPositionController;
use App\Http\Controllers\Admin\TimeOffPolicyController as AdminTimeOffPolicyController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// login/mobile-login rate-limit themselves (only failed attempts count —
// see AuthController::assertNotRateLimited), so no throttle middleware here.
Route::post('/v1/auth/login', [AuthController::class, 'login']);
Route::post('/v1/auth/mobile-login', [AuthController::class, 'mobileLogin']);
Route::post('/v1/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
Route::post('/v1/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'active.employment', 'throttle:api'])->prefix('v1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // Employee core
    Route::get('/people', [PersonController::class, 'index']);
    Route::post('/people/{person}/rehire', [PersonController::class, 'rehire']);

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{employment}', [EmployeeController::class, 'show']);
    Route::post('/employees/{employment}/transfer', [EmployeeController::class, 'transfer']);
    Route::post('/employees/bulk-transfer', [EmployeeController::class, 'bulkTransfer']);
    Route::post('/employees/{employment}/terminate', [EmployeeController::class, 'terminate']);

    // Timeline / notes
    Route::get('/people/{person}/timeline', [EmployeeEventController::class, 'index']);
    Route::post('/people/{person}/timeline', [EmployeeEventController::class, 'store']);

    // Compensation
    Route::get('/employees/{employment}/compensation', [CompensationController::class, 'index']);
    Route::post('/employees/{employment}/compensation', [CompensationController::class, 'store']);
    Route::get('/employees/{employment}/banking-info', [BankingInfoController::class, 'show']);
    Route::post('/employees/{employment}/banking-info', [BankingInfoController::class, 'store']);

    // Documents
    Route::get('/document-categories', [DocumentCategoryController::class, 'index']);
    Route::get('/employees/{employment}/documents', [DocumentController::class, 'index']);
    Route::post('/employees/{employment}/documents', [DocumentController::class, 'store']);
    Route::get('/employees/{employment}/documents/{document}/download', [DocumentController::class, 'download']);
    Route::post('/employees/{employment}/documents/{document}/acknowledge', [DocumentController::class, 'acknowledge']);

    // Onboarding
    Route::get('/onboarding/templates', [OnboardingTemplateController::class, 'index']);
    Route::get('/employees/{employment}/onboarding', [OnboardingWorkflowController::class, 'show']);
    Route::post('/employees/{employment}/onboarding', [OnboardingWorkflowController::class, 'store']);
    Route::post('/onboarding/tasks/{task}/complete', [OnboardingTaskController::class, 'complete']);
    Route::get('/employees/{employment}/background-checks', [BackgroundCheckController::class, 'index']);

    // Offboarding
    Route::get('/employees/{employment}/offboarding', [OffboardingWorkflowController::class, 'show']);
    Route::post('/offboarding/tasks/{task}/complete', [OffboardingTaskController::class, 'complete']);

    // Time off
    Route::get('/time-off/policies', [TimeOffPolicyController::class, 'index']);
    Route::get('/time-off/requests', [TimeOffRequestController::class, 'index']);
    Route::post('/time-off/requests', [TimeOffRequestController::class, 'store']);
    Route::post('/time-off/requests/{timeOffRequest}/approve', [TimeOffRequestController::class, 'approve']);
    Route::post('/time-off/requests/{timeOffRequest}/deny', [TimeOffRequestController::class, 'deny']);
    Route::get('/employees/{employment}/time-off-balances', [TimeOffBalanceController::class, 'index']);

    // ATS
    Route::get('/ats/pipeline-stages', [PipelineStageController::class, 'index']);
    Route::get('/ats/requisitions', [JobRequisitionController::class, 'index']);
    Route::post('/ats/requisitions', [JobRequisitionController::class, 'store']);
    Route::get('/ats/requisitions/{requisition}', [JobRequisitionController::class, 'show']);
    Route::patch('/ats/requisitions/{requisition}', [JobRequisitionController::class, 'update']);
    Route::post('/ats/candidates', [CandidateController::class, 'store']);
    Route::post('/ats/candidates/{candidate}/confirm-former-employee', [CandidateController::class, 'confirmFormerEmployee']);
    Route::get('/ats/applications', [ApplicationController::class, 'index']);
    Route::post('/ats/applications/{application}/move-stage', [ApplicationController::class, 'moveStage']);
    Route::post('/ats/applications/{application}/reject', [ApplicationController::class, 'reject']);
    Route::post('/ats/applications/{application}/hire', [ApplicationController::class, 'hire']);
    Route::post('/ats/applications/{application}/interview-notes', [InterviewNoteController::class, 'store']);

    // Data migration / import
    Route::post('/admin/import/preview', [ImportController::class, 'preview']);
    Route::post('/admin/import/commit', [ImportController::class, 'commit']);

    // Reporting
    Route::get('/reports/turnover', [TurnoverReportController::class, 'show']);
    Route::get('/dashboard', [DashboardController::class, 'show']);

    // Admin — non-technical HR/ops config, previously seeder-only
    Route::get('/admin/locations', [AdminLocationController::class, 'index']);
    Route::post('/admin/locations', [AdminLocationController::class, 'store']);
    Route::patch('/admin/locations/{location}', [AdminLocationController::class, 'update']);

    Route::get('/admin/departments', [AdminDepartmentController::class, 'index']);
    Route::post('/admin/departments', [AdminDepartmentController::class, 'store']);
    Route::patch('/admin/departments/{department}', [AdminDepartmentController::class, 'update']);

    Route::get('/admin/positions', [AdminPositionController::class, 'index']);
    Route::post('/admin/positions', [AdminPositionController::class, 'store']);
    Route::patch('/admin/positions/{position}', [AdminPositionController::class, 'update']);

    Route::get('/admin/time-off-policies', [AdminTimeOffPolicyController::class, 'index']);
    Route::post('/admin/time-off-policies', [AdminTimeOffPolicyController::class, 'store']);
    Route::patch('/admin/time-off-policies/{timeOffPolicy}', [AdminTimeOffPolicyController::class, 'update']);

    Route::get('/admin/document-categories', [AdminDocumentCategoryController::class, 'index']);
    Route::post('/admin/document-categories', [AdminDocumentCategoryController::class, 'store']);
    Route::patch('/admin/document-categories/{documentCategory}', [AdminDocumentCategoryController::class, 'update']);

    Route::get('/admin/onboarding-templates', [AdminOnboardingTemplateController::class, 'index']);
    Route::post('/admin/onboarding-templates', [AdminOnboardingTemplateController::class, 'store']);
    Route::post('/admin/onboarding-templates/{onboardingTemplate}/tasks', [AdminOnboardingTemplateController::class, 'addTask']);
});
