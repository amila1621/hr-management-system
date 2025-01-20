<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\ClaudeAIController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\MissingHoursController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SalaryUpdatedController;
use App\Http\Controllers\SickLeaveController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TourDurationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourGuideController;
use App\Models\TourDuration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function () {
    Route::post('/updatedate', [TourDurationController::class, 'updatedate'])->name('updatedate');
    Route::post('/updatedateForBusDrivers', [TourDurationController::class, 'updatedateForBusDrivers'])->name('updatedateForBusDrivers');
    Route::get('/fixxx', [GoogleCalendarController::class, 'fixxx'])->name('fixxx');


    Route::get('/fetch-events', [GoogleCalendarController::class, 'fetchAndStoreEvents'])->name('fetch.events');
    Route::get('/', [GoogleCalendarController::class, 'dashboard'])->name('dashboard');
    Route::post('/fetch-filter-events', [GoogleCalendarController::class, 'fetchFilterEvents'])->name('fetch.filter.events');
    Route::post('/fetch-filter-chores', [GoogleCalendarController::class, 'fetchFilterChores'])->name('fetch.filter.chores');
    Route::get('/all-events', [GoogleCalendarController::class, 'fetchAllEvents'])->name('fetch.all.events');

    Route::resource('tour-guides', TourGuideController::class);
    Route::put('/tour-guides/{id}/change-password', [TourGuideController::class, 'changePassword'])->name('tour-guides.change-password');
    Route::put('/tour-guides/{id}/hide', [TourGuideController::class, 'hide'])->name('tour-guides.hide');
    Route::put('/tour-guides/{id}/unhide', [TourGuideController::class, 'unhide'])->name('tour-guides.unhide');

    Route::put('/staff/{id}/change-password', [StaffController::class, 'changePassword'])->name('staff.change-password');
    Route::get('/staffs/index', [TourGuideController::class, 'staffIndex'])->name('tour-guides.staff-index');
    Route::get('/operations/index', [TourGuideController::class, 'operationsIndex'])->name('tour-guides.operations-index');
    Route::get('/supervisors/index', [TourGuideController::class, 'supervisorsIndex'])->name('tour-guides.supervisors-index');
    Route::get('/team-leads/index', [TourGuideController::class, 'teamLeadsIndex'])->name('tour-guides.team-leads-index');
    Route::get('/hr-assistants/index', [TourGuideController::class, 'hrAssistantsIndex'])->name('tour-guides.hr-assistants-index');
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/staffs/edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');

    Route::get('/operations/destroy/{id}', [StaffController::class, 'operationsDestroy'])->name('operations.destroy');
    Route::get('/hr-assistants/destroy/{id}', [StaffController::class, 'hrAssistantDestroy'])->name('hr-assistants.destroy');
    Route::get('/team-leads/destroy/{id}', [StaffController::class, 'teamLeadsDestroy'])->name('team-leads.destroy');
    Route::get('/supervisors/destroy/{id}', [StaffController::class, 'supervisorsDestroy'])->name('supervisors.destroy');
    Route::get('/operations/edit/{id}', [StaffController::class, 'operationsEdit'])->name('operations.edit');
    Route::get('/hr-assistants/edit/{id}', [StaffController::class, 'hrAssistantsEdit'])->name('hr-assistants.edit');
    Route::get('/team-leads/edit/{id}', [StaffController::class, 'teamLeadsEdit'])->name('team-leads.edit');
    Route::get('/supervisors/edit/{id}', [StaffController::class, 'supervisorsEdit'])->name('supervisors.edit');
    Route::put('/operations/{id}', [StaffController::class, 'operationsUpdate'])->name('operations.update');
    Route::put('/hr-assistants/{id}', [StaffController::class, 'hrAssistantsUpdate'])->name('hr-assistants.update');
    Route::put('/team-leads/{id}', [StaffController::class, 'teamLeadsUpdate'])->name('team-leads.update');
    Route::put('/supervisors/{id}', [StaffController::class, 'supervisorsUpdate'])->name('supervisors.update');
    Route::put('/operations/{id}/change-password', [StaffController::class, 'operationsChangePassword'])->name('operations.change-password');


    Route::get('/staff/dashboard', [StaffController::class, 'StaffDashboard'])->name('staff.schedule');

    Route::get('/create-a-new-tour', [ReportController::class, 'createNewTour'])->name('tours.create-a-new-tour');
    Route::post('/tours/store-manual', [ReportController::class, 'storeManual'])->name('tours.store-manual');
    
    Route::get('/create-a-sick-leave-tour', [SickLeaveController::class, 'createNewSickLeaveTour'])->name('tours.create-a-sick-leave-tour');
    Route::post('/tours/sick-leave-store-manual', [SickLeaveController::class, 'sickLeaveStoreManual'])->name('tours.sick-leave-store-manual');
    Route::put('/sick-leaves/{id}', [SickLeaveController::class, 'sickLeaveUpdate']);
    Route::get('/tours/sick-leave-tours', [SickLeaveController::class, 'index'])->name('tours.sick-leave-tours');
    Route::delete('/tours/sick-leaves-destroy/{id}', [SickLeaveController::class, 'destroy'])->name('sick-leaves.destroy');

    Route::get('/event-salary/{id}', [SalaryController::class, 'eventsalary'])->name('salary.eventsalary');
    Route::post('/salary/add-extra-hours', [SalaryController::class, 'addExtraHours'])->name('salary.add-extra-hours');
    Route::post('/salary/add-extra-hours-ajax', [SalaryController::class, 'addExtraHoursAjax'])->name('salary.add-extra-hours-ajax');


    Route::get('/monthly-reports', [ReportController::class, 'monthlyReportCreate'])->name('reports.monthly-report-create');
    Route::get('/monthly-reports-christmas', [ReportController::class, 'monthlyReportCreateChristmas'])->name('reports.monthly-report-christmas');
    Route::get('/get-monthly-reports', [ReportController::class, 'getMonthlyReport'])->name('reports.getMonthlyReport');
    Route::get('/get-monthly-reports-op', [ReportController::class, 'getMonthlyReportOp'])->name('reports.getMonthlyReportOp');
    Route::get('/monthly-report-create-op', [ReportController::class, 'monthlyReportCreateOp'])->name('reports.monthly-report-create-op');
    Route::get('/guide-wise-reports', [ReportController::class, 'guideWiseReportCreate'])->name('reports.guide-wise-report-create');
    Route::get('/get-guide-wise-reports', [ReportController::class, 'getGuideWiseReport'])->name('reports.getGuideWiseReport');
    Route::get('/guide/{guideId}/report', [ReportController::class, 'getGuideWiseReportByMonth'])->name('guide.report-by-month');
    Route::get('/manually-added-entries', [ReportController::class, 'ManualEntries'])->name('reports.manually-added-entries-create');
    Route::get('/manually-added-tours', [ReportController::class, 'ManualTours'])->name('reports.manually-added-tours-create');
    Route::get('/rejected-hours', [ReportController::class, 'rejectedHours'])->name('reports.rejected-hours');

    Route::get('/guide-time-reports', [ReportController::class, 'guideTimeReportCreate'])->name('reports.guide-time-report-create');
    Route::get('/guide-time-reports-christmas', [ReportController::class, 'guideTimeReportCreateChristmas'])->name('reports.guide-time-report-christmas');
    Route::get('/get-guide-time-reports', [ReportController::class, 'getGuideTimeReport'])->name('reports.getGuideTimeReport');
    Route::get('/get-guide-time-reports-christmas', [ReportController::class, 'getGuideTimeReportChristmas'])->name('reports.getGuideTimeReportChristmas');

    Route::get('/tour-durations/manage', [TourDurationController::class, 'index'])->name('tour-durations.index');
    Route::get('/tour-durations-sauna/manage', [TourDurationController::class, 'indexSauna'])->name('tour-durations-sauna.index');
    Route::post('/tour-durations/store', [TourDurationController::class, 'store'])->name('tour-durations.store');
    Route::post('/tour-durations-sauna/store', [TourDurationController::class, 'storeSauna'])->name('tour-durations.sauna-store');

    Route::get('/calculate-all', [SalaryController::class, 'calculateAll'])->name('calculate-all');
    Route::post('/manual-calculation', [SalaryController::class, 'manualCalculation'])->name('salary.manual-calculation');
    Route::post('/manual-calculation-ajax', [SalaryController::class, 'manualCalculationAjax'])->name('salary.manual-calculation-ajax');
    Route::post('/events/ignore', [SalaryController::class, 'ignoreEvent'])->name('events.ignore');


    Route::get('/error-display', [SalaryController::class, 'errorLog'])->name('error.display');
    Route::get('/error-log', [SalaryController::class, 'errorLog'])->name('errors.log');
    Route::get('/error-filter', [SalaryController::class, 'errorFilter'])->name('errors.filter');
    Route::match(['get', 'post'], '/reports/working-hours', [ReportController::class, 'calculateWorkingHours'])->name('guides.working-hours');
    Route::match(['get', 'post'], '/reports/ranking-for-hours-bus-drivers', [ReportController::class, 'rankingForHoursBusDrivers'])->name('guides.ranking-for-hours-bus-drivers');


    Route::get('tour-durations/{id}/edit', [TourDurationController::class, 'edit'])->name('tour-durations.edit');
    Route::put('tour-durations/{id}', [TourDurationController::class, 'update'])->name('tour-durations.update');
    Route::delete('tour-durations/{id}', [TourDurationController::class, 'destroy'])->name('tour-durations.destroy');

    Route::get('tour-durations-sauna/{id}/edit', [TourDurationController::class, 'editSauna'])->name('tour-durations.sauna-edit');
    Route::put('tour-durations-sauna/{id}', [TourDurationController::class, 'updateSauna'])->name('tour-durations.sauna-update');
    Route::delete('tour-durations-sauna/{id}', [TourDurationController::class, 'destroySauna'])->name('tour-durations.sauna-destroy');



    Route::get('guide/work-report', [TourGuideController::class, 'workReport'])->name('guide.work-report');
    Route::post('/event-salary/{id}/update-hours', [TourGuideController::class, 'updateHours'])->name('event-salary.update-hours');
    Route::post('/event-salary/{id}/update-hours-by-guides', [TourGuideController::class, 'updateHoursByGuides'])->name('event-salary.update-hours-by-guides');

    Route::get('guide/report-hours', [TourGuideController::class, 'reportHours'])->name('guide.report-hours');
    Route::post('guide/report-hours', [TourGuideController::class, 'reportHoursStore'])->name('guide.report-hours-store');



    // Display pending approvals
    Route::get('pending-approvals', [ReportController::class, 'pendingApprovals'])->name('admin.pending-approvals');
    Route::get('pending-16plus-approvals', [ReportController::class, 'pending16plusApprovals'])->name('admin.pending-16plus-approvals');
    Route::get('missing-hours', [MissingHoursController::class, 'manageMissingHours'])->name('admin.missing-hours');
    Route::post('missing-hours', [MissingHoursController::class, 'storeMissingHours'])->name('missing-hours.store');
    Route::delete('missing-hours/{id}', [MissingHoursController::class, 'destroyMissingHours'])->name('missing-hours.destroy');
    Route::put('/missing-hours/{id}', [MissingHoursController::class, 'update'])->name('missing-hours.update');
    Route::post('pending-approvals-update-date', [ReportController::class, 'pendingApprovalsDateUpdate'])->name('reports.pending-approvals-date-update');
    Route::get('recalculate', [SalaryController::class, 'recalculate']);

    // Approve or reject a guide's updated hours with an optional comment, and a new "Needs More Info" status
    Route::post('pending-approvals/{id}/approve', [ReportController::class, 'approve'])->name('admin.approve');
    Route::post('pending-approvals/{id}/adjust', [ReportController::class, 'adjust'])->name('admin.adjust');
    Route::post('pending-approvals/{id}/reject', [ReportController::class, 'reject'])->name('admin.reject');
    Route::post('pending-approvals/{id}/modify', [ReportController::class, 'modify'])->name('admin.modify-time');
    Route::post('pending-approvals/{id}/needs-info', [ReportController::class, 'needsInfo'])->name('admin.needs-info');


    Route::get('/supervisor/enter-working-hours', [SupervisorController::class, 'enterWorkingHours'])->name('supervisor.enter-working-hours');
    Route::post('/supervisor/enter-working-hours', [SupervisorController::class, 'storeWorkingHours'])->name('supervisor.working-hours.store');

    Route::get('/supervisor/display-schedule', [SupervisorController::class, 'displaySchedule'])->name('supervisor.display-schedule');

    Route::post('/supervisor/save-midnight-phone', [SupervisorController::class, 'saveMidnightPhone'])->name('supervisor.save-midnight-phone');

    Route::get('/supervisor/week-data', [SupervisorController::class, 'getWeekData'])->name('supervisor.week-data');
    Route::get('/supervisor/view-time-plan', [SupervisorController::class, 'viewTimePlan'])->name('supervisor.view-time-plan');
    Route::get('/supervisor/view-holiday-time-plan', [SupervisorController::class, 'viewHolidayTimePlan'])->name('supervisor.view-holiday-time-plan');



    Route::get('/work-hours/delete/{id}', [ReportController::class, 'deleteWorkHours'])->name('work-hours.delete');




    Route::post('/process-description', [ChatGPTController::class, 'getResponse'])->name('process.description');
    Route::get('/chatgpt', [ChatGPTController::class, 'getForm'])->name('chatgpt');

    Route::post('/process-description-claude', [ClaudeAIController::class, 'getResponse'])->name('process.description.claude');
    Route::get('/claudeai', [ClaudeAIController::class, 'getForm'])->name('claudeai');

    Route::post('/ai/analyze-event', [ClaudeAIController::class, 'analyzeEvent'])->name('ai.analyze-event');
    Route::post('/salary/calculate-from-ai', [SalaryController::class, 'calculateFromAI'])->name('salary.calculate-from-ai');

    //Announcements
    Route::get('/announcements', [AnnouncementController::class, 'manageAnnouncements'])->name('announcements.manage');
    Route::post('/announcements/store', [AnnouncementController::class, 'storeAnnouncements'])->name('announcements.store');
    Route::delete('/announcements/destroy/{id}', [AnnouncementController::class, 'destroyAnnouncements'])->name('announcements.destroy');
    Route::post('/announcements/acknowledge/{id}', [AnnouncementController::class, 'acknowledgeAnnouncement'])->name('announcements.acknowledge');

    Route::get('/manager/dashboard', [ManagerController::class, 'managerDashboard'])->name('manager.dashboard');
    Route::get('/manager/guide-report', [ManagerController::class, 'managerGuideReport'])->name('manager.guide-report');
    Route::post('/manager/working-hours', [ManagerController::class, 'managerDashboard'])->name('manager.working-hours');
    Route::get('/admin/assign-guides-to-managers', [ManagerController::class, 'assignGuidesToManagers'])->name('admin.assign-guides-to-managers');
    Route::post('/admin/assign-guides-to-managers', [ManagerController::class, 'assignGuidesToManagersStore'])->name('managers.assign-guides-store');
    Route::get('/admin/get-manager-guides/{managerId}', [ManagerController::class, 'getManagerGuides']);

    Route::get('/time-adjustments', [SalaryController::class, 'timeAdjustments'])
        ->name('time-adjustments')
        ->middleware('auth');

    Route::prefix('vehicles')->group(function () {
        Route::get('/', [OperationsController::class, 'manageVehicles'])->name('vehicles.index');
        Route::post('/store', [OperationsController::class, 'storeVehicle'])->name('vehicles.store');
        Route::get('/edit/{vehicle}', [OperationsController::class, 'editVehicle'])->name('vehicles.edit');
        Route::put('/update/{vehicle}', [OperationsController::class, 'updateVehicle'])->name('vehicles.update');
        Route::delete('/destroy/{vehicle}', [OperationsController::class, 'destroyVehicle'])->name('vehicles.destroy');
    });
    Route::prefix('operations')->group(function () {
        Route::get('/fetch-events', [OperationsController::class, 'fetchEvents'])->name('operations.fetch-events');
        Route::get('/create-sheet', [OperationsController::class, 'createSheet'])->name('operations.create-sheet');
        Route::get('/check-sheet', [OperationsController::class, 'checkSheet'])->name('operation.checkin-sheet');
    });
});

Route::get('/salary-updates', [SalaryUpdatedController::class, 'index'])->name('salary-updates.index');
Route::post('/salary-updates', [SalaryUpdatedController::class, 'store'])->name('salary-updates.store');
Route::put('/salary-updates/{id}', [SalaryUpdatedController::class, 'update'])->name('salary-updates.update');
Route::delete('/salary-updates/{id}', [SalaryUpdatedController::class, 'destroy'])->name('salary-updates.destroy');

Route::get('/receipts-create', [ReceiptController::class, 'create'])->name('receipts.create');
Route::get('/receipts-manage', [ReceiptController::class, 'manage'])->name('receipts.manage');
Route::get('/receipts-approve', [ReceiptController::class, 'approve'])->name('receipts.approve');
Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
Route::patch('/receipts/{receipt}/status', [ReceiptController::class, 'updateStatus'])->name('receipts.update-status');


Route::get('/fetch-last-tours', [GoogleCalendarController::class, 'saveLastTour'])->name('fetch.last-tours');

Route::get('/fix-storage-link', function () {
    // Define the path to the public storage link
    $publicStorageLink = public_path('storage');

    // Check if the symlink exists, and if it does, remove it
    if (File::exists($publicStorageLink)) {
        File::delete($publicStorageLink);
    }

    // Create a new symbolic link
    Artisan::call('storage:link');

    return "Storage link has been successfully recreated!";
})->name('fix-storage-link');

Route::post('/calculate-pickup-duration', [SalaryController::class, 'calculatePickupDuration'])->name('calculate.pickup.duration');

require __DIR__ . '/auth.php';

