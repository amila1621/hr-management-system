<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\ClaudeAIController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\LastToursController;
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
use App\Http\Controllers\SyncDatabasesController;
use App\Http\Controllers\TourDurationController;
use App\Http\Controllers\AccountantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourGuideController;
use App\Http\Controllers\SalarySummaryController;
use App\Http\Controllers\CombinedReportController;
use App\Http\Controllers\ExtraHoursRequestController;
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

Route::middleware(['auth','activity'])->group(function () {

    // Hotel Reports
    Route::get('/hotel/create', [CombinedReportController::class, 'hotelReportCreate'])->name('combined-reports.hotel.create');
    Route::post('/hotel/monthly', [CombinedReportController::class, 'hotelGetMonthlyReport'])->name('combined-reports.hotel.monthly');

    // Combined Reports (Guides + Operations + Other Staff)
    Route::get('combined-reports/all-departments/create', [CombinedReportController::class, 'allDepartmentsCreate'])->name('combined-reports.all-departments.create');
    Route::post('combined-reports/all-departments/monthly', [CombinedReportController::class, 'allDepartmentsGetMonthlyReport'])->name('combined-reports.all-departments.monthly');

    // Combined Accountant Reports (NUT Staff + Guides)
    Route::get('combined-reports/combined-accountant/create', [CombinedReportController::class, 'combinedAccountantCreate'])->name('combined-reports.combined-accountant.create');
    Route::post('combined-reports/combined-accountant/monthly', [CombinedReportController::class, 'combinedAccountantGetMonthlyReport'])->name('combined-reports.combined-accountant.monthly');


    Route::get('/supervisor/populate-previous-week', [SupervisorController::class, 'populatePreviousWeek'])
    ->name('supervisor.populate-previous-week');

    Route::get('/supervisor/clear-current-week', [SupervisorController::class, 'clearCurrentWeek'])
    ->name('supervisor.clear-current-week');



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
    Route::post('/tour-guides/{id}/terminate', [TourGuideController::class, 'terminate'])->name('tour-guides.terminate');
    Route::put('/tour-guides/{id}/make-guide-staff', [TourGuideController::class, 'makeGuideStaff'])->name('tour-guides.make-guide-staff');

    Route::put('/staff/{id}/change-password', [StaffController::class, 'changePassword'])->name('staff.change-password');
    Route::get('/staffs/index', [TourGuideController::class, 'staffIndex'])->name('tour-guides.staff-index');
    Route::get('/operations/index', [TourGuideController::class, 'operationsIndex'])->name('tour-guides.operations-index');
    Route::get('/supervisors/index', [TourGuideController::class, 'supervisorsIndex'])->name('tour-guides.supervisors-index');
    Route::get('/am-supervisors/index', [TourGuideController::class, 'amSupervisorsIndex'])->name('tour-guides.am-supervisors-index');
    Route::get('/team-leads/index', [TourGuideController::class, 'teamLeadsIndex'])->name('tour-guides.team-leads-index');
    Route::get('/hr-assistants/index', [TourGuideController::class, 'hrAssistantsIndex'])->name('tour-guides.hr-assistants-index');
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/staffs/edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{id}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{id}/update-color', [SupervisorController::class, 'updateStaffColor'])->name('staff.update-color');

    Route::get('/operations/destroy/{id}', [StaffController::class, 'operationsDestroy'])->name('operations.destroy');
    Route::get('/hr-assistants/destroy/{id}', [StaffController::class, 'hrAssistantDestroy'])->name('hr-assistants.destroy');
    Route::get('/team-leads/destroy/{id}', [StaffController::class, 'teamLeadsDestroy'])->name('team-leads.destroy');
    Route::get('/supervisors/destroy/{id}', [StaffController::class, 'supervisorsDestroy'])->name('supervisors.destroy');




    Route::get('/admin/manage-sick-leaves', [SupervisorController::class, 'adminManageSickLeaves'])->name('admin.manage-sick-leaves');

    Route::get('/supervisor/manage-sick-leaves', [SupervisorController::class, 'supervisorsManageSickLeaves'])->name('supervisor.manage-sick-leaves');
    Route::get('/staff/request-sick-leaves', [SickLeaveController::class, 'requestSickLeaves'])->name('sick-leave.request-sick-leaves');
    Route::post('/staff/store-sick-leaves', [SickLeaveController::class, 'requestSickLeavesStore'])->name('sick-leave.store-sick-leaves');
    
    Route::get('/staff/manage-sick-leaves', [SickLeaveController::class, 'manageSickLeaves'])->name('sick-leave.manage-sick-leaves');

    Route::post('/supervisor-sick-leaves/{id}/admin-approve', [SickLeaveController::class, 'adminApprove'])
        ->name('supervisor-sick-leaves.admin-approve');
    Route::post('/supervisor-sick-leaves/{id}/admin-reject', [SickLeaveController::class, 'adminReject'])
        ->name('supervisor-sick-leaves.admin-reject');
    Route::post('/supervisor-sick-leaves/{id}/cancel', [SickLeaveController::class, 'cancelSickLeave'])
        ->name('supervisor-sick-leaves.cancel');
    
    
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
    Route::get('/staff/hours-report', [StaffController::class, 'StaffHoursReport'])->name('staff.hours-report');
    Route::get('/staff-guide/hours-report', [StaffController::class, 'StaffGuideHoursReport'])->name('staff-guide.hours-report');

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

    Route::post('/supervisor-sick-leaves/{id}/approve', [SickLeaveController::class, 'approveSickLeave'])->name('supervisor.sick-leaves.approve');
    Route::post('/supervisor-sick-leaves/{id}/reject', [SickLeaveController::class, 'rejectSickLeave'])->name('supervisor.sick-leaves.reject');
    Route::post('/supervisor-sick-leaves/{id}/cancel', [SickLeaveController::class, 'cancelSickLeave'])->name('supervisor.sick-leaves.cancel');

    Route::get('/accountant-monthly-report-create', [AccountantController::class, 'accountantReportCreate'])->name('reports.accountant-report-create');
    Route::get('/accountant-monthly-report', [AccountantController::class, 'accountantGetMonthlyReport'])->name('reports.getAccountantMonthlyReport');

    Route::get('/staff-monthly-report-create', [AccountantController::class, 'staffReportCreate'])->name('reports.staff-report-create');
    Route::get('/staff-monthly-report', [AccountantController::class, 'staffGetMonthlyReport'])->name('reports.getStaffMonthlyReport');
 
    Route::get('/operation-staff-monthly-report-create', [AccountantController::class, 'operationStaffReportCreate'])->name('reports.operation-staff-report-create');
    Route::get('/operation-staff-monthly-report', [AccountantController::class, 'operationStaffGetMonthlyReport'])->name('reports.getOperationStaffMonthlyReport');

    Route::get('/hotel-monthly-report-create', [AccountantController::class, 'hotelReportCreate'])->name('reports.hotel-report-create');
    Route::get('/hotel-monthly-report', [AccountantController::class, 'hotelGetMonthlyReport'])->name('reports.getHotelMonthlyReport');


    Route::get('/monthly-reports', [ReportController::class, 'monthlyReportCreate'])->name('reports.monthly-report-create');
    Route::get('/monthly-reports-christmas', [ReportController::class, 'monthlyReportCreateChristmas'])->name('reports.monthly-report-christmas');
    Route::get('/get-monthly-reports', [ReportController::class, 'getMonthlyReport'])->name('reports.getMonthlyReport');
    Route::get('/get-monthly-reports-op', [ReportController::class, 'getMonthlyReportOp'])->name('reports.getMonthlyReportOp');
    Route::get('/monthly-report-create-op', [ReportController::class, 'monthlyReportCreateOp'])->name('reports.monthly-report-create-op');
    Route::get('/guide-wise-reports', [ReportController::class, 'guideWiseReportCreate'])->name('reports.guide-wise-report-create');
    Route::get('/guide-wise-custom-reports', [ReportController::class, 'guideWiseReportCustomCreate'])->name('reports.guide-wise-report-custom-create');
    Route::get('/terminated-guide-wise-reports', [ReportController::class, 'terminatedGuideWiseReportCreate'])->name('reports.terminated-guide-wise-report-create');
    Route::get('/guide/{guideId}/report', [ReportController::class, 'getGuideWiseReportByMonth'])->name('guide.report-by-month');
    Route::get('/terminated-guide/{guideId}/report', [ReportController::class, 'getTerminatedGuideWiseReportByMonth'])->name('guide.terminated-report-by-month');
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
    Route::match(['get', 'post'], '/reports/supervisor-working-hours', [ReportController::class, 'calculateWorkingHoursSupervisors'])->name('supervisors.working-hours');
    Route::match(['get', 'post'], '/staff/working-hours', [ReportController::class, 'calculateWorkingHoursStaff'])->name('staff.working-hours');
    Route::match(['get', 'post'], '/reports/supervisor-missing-hours', [SupervisorController::class, 'manageMissingHours'])->name('supervisors.missing-hours');
    Route::post('/reports/supervisor-missing-hours-store', [SupervisorController::class, 'store'])->name('missing-hours-supervisor.store');
    Route::delete('/missing-hours-supervisor/{id}', [SupervisorController::class, 'destroy'])->name('missing-hours-supervisor.destroy');
    Route::put('/missing-hours-supervisor/{id}', [SupervisorController::class, 'update'])->name('missing-hours-supervisor.update');
    
    Route::get('/staff-hours-report', [SupervisorController::class, 'staffHourReport'])->name('supervisor.staff-report');
    Route::get('/hotel-staff-hours-report', [SupervisorController::class, 'hotelStaffHourReport'])->name('supervisor.hotel-staff-report');

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
    
    Route::get('guide/extra-hours-request', [TourGuideController::class, 'extraHoursRequest'])->name('guide.extra-hours-request');
    Route::get('guide/extra-hours-request/tours', [TourGuideController::class, 'getToursByDateAjax'])->name('guide.extra-hours-request.tours');
    Route::get('guide/extra-hours-request/test', function() {
        return response()->json(['success' => true, 'message' => 'Route working']);
    })->name('guide.extra-hours-request.test');
    Route::post('guide/extra-hours-request', [TourGuideController::class, 'extraHoursRequestSubmit'])->name('guide.extra-hours-request.submit');
    
    Route::get('admin/extra-hours-requests', [ExtraHoursRequestController::class, 'index'])->name('admin.extra-hours-requests');
    Route::post('admin/extra-hours-requests/{id}/approve', [ExtraHoursRequestController::class, 'approve'])->name('admin.extra-hours-requests.approve');
    Route::post('admin/extra-hours-requests/{id}/reject', [ExtraHoursRequestController::class, 'reject'])->name('admin.extra-hours-requests.reject');

    Route::get('staff/report-hours', [StaffController::class, 'reportStaffHours'])->name('staff.report-hours');
    Route::post('staff/report-hours', [StaffController::class, 'reportStaffHoursStore'])->name('staff.report-hours-store');



    // Display pending approvals
    Route::get('pending-approvals', [ReportController::class, 'pendingApprovals'])->name('admin.pending-approvals');
    Route::get('pending-16plus-approvals', [ReportController::class, 'pending16plusApprovals'])->name('admin.pending-16plus-approvals');
    Route::get('missing-hours', [MissingHoursController::class, 'manageMissingHours'])->name('admin.missing-hours');
    Route::get('teamlead-missing-hours', [MissingHoursController::class, 'teamleadManageMissingHours'])->name('teamlead.missing-hours');
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
    // Redirect old route to consolidated staff report hours route
    Route::get('/guide-supervisor/enter-working-hours', function() {
        return redirect()->route('staff.report-hours');
    })->name('guide-supervisor.enter-working-hours');
    Route::post('/supervisor/enter-working-hours', [SupervisorController::class, 'storeWorkingHours'])->name('supervisor.working-hours.store');
    Route::post('/staff/enter-working-hours', [SupervisorController::class, 'staffStoreWorkingHours'])->name('staff.working-hours.store');
    Route::post('/supervisor/approve-employee', [SupervisorController::class, 'approveEmployee'])->name('supervisor.approve-employee');
    Route::get('/am-supervisor/enter-working-hours', [SupervisorController::class, 'amEnterWorkingHours'])->name('am-supervisor.enter-working-hours');
    Route::get('/supervisor/print-roster', [SupervisorController::class, 'printRoster'])->name('supervisor.print-roster');

    Route::get('/supervisor/manage-staff', [SupervisorController::class, 'manageStaff'])->name('supervisor.manage-staff');
   
   
    Route::get('/supervisor/display-schedule', [SupervisorController::class, 'displaySchedule'])->name('supervisor.display-schedule');
    Route::post('/admin/sick-leaves/update-status', [SupervisorController::class, 'updateSickLeaveStatus'])->name('admin.sick-leaves.update-status');

    Route::post('/supervisor/save-midnight-phone', [SupervisorController::class, 'saveMidnightPhone'])->name('supervisor.save-midnight-phone');

    Route::get('/supervisor/week-data', [SupervisorController::class, 'getWeekData'])->name('supervisor.week-data');
    Route::get('/supervisor/view-time-plan', [SupervisorController::class, 'viewTimePlan'])->name('supervisor.view-time-plan');
    Route::get('/supervisor/view-holiday-time-plan', [SupervisorController::class, 'viewHolidayTimePlan'])->name('supervisor.view-holiday-time-plan');
    Route::post('/supervisor/sick-leaves/upload-prescription', [SupervisorController::class, 'uploadPrescription'])->name('supervisor.sick-leaves.upload-prescription');
    Route::post('/supervisor/sick-leaves/{id}/cancel', [SupervisorController::class, 'cancelSickLeave'])->name('supervisor.sick-leaves.cancel');

    Route::post('/supervisor/staff/reorder', [SupervisorController::class, 'reorderStaff'])->name('supervisor.staff.reorder');

    Route::get('/work-hours/delete/{id}', [ReportController::class, 'deleteWorkHours'])->name('work-hours.delete');

    Route::post('/staff/{id}/toggle-visibility', [SupervisorController::class, 'toggleStaffVisibility']);


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
        Route::get('/update-durations', [OperationsController::class, 'updateTourDurations'])->name('operation.update-durations');
    });

Route::get('/salary-updates', [SalaryUpdatedController::class, 'index'])->name('salary-updates.index');
Route::post('/salary-updates', [SalaryUpdatedController::class, 'store'])->name('salary-updates.store');
Route::put('/salary-updates/{id}', [SalaryUpdatedController::class, 'update'])->name('salary-updates.update');
Route::delete('/salary-updates/{id}', [SalaryUpdatedController::class, 'destroy'])->name('salary-updates.destroy');

Route::get('/receipts-manage', [ReceiptController::class, 'manage'])->name('receipts.manage');
Route::get('/supervisor-receipts-manage', [ReceiptController::class, 'supervisorReceiptManage'])->name('supervisor.manage-receipts');
Route::get('/receipts-approve', [ReceiptController::class, 'approve'])->name('receipts.approve');
Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
Route::patch('/receipts/{receipt}/status', [ReceiptController::class, 'updateStatus'])->name('receipts.update-status');
Route::patch('/receipts/{receipt}/status-supervisor', [ReceiptController::class, 'supervisorUpdateStatus'])->name('receipts.supervisor-update-status');
// Activity log routes
    Route::get('/staff/report-by-month', [SupervisorController::class, 'staffHourReport'])->name('staff.report-by-month');
    Route::get('/get-guide-wise-reports', [ReportController::class, 'getGuideWiseReport'])->name('reports.getGuideWiseReport');
    Route::get('/get-office-staff-wise-reports', [StaffController::class, 'getOfficeStaffWiseReport'])->name('reports.getOfficeStaffWiseReport');
    Route::get('/get-guide-wise-custom-reports', [ReportController::class, 'getGuideWiseCustomReport'])->name('reports.getGuideWiseCustomReport');
    Route::get('/get-terminated-guide-wise-reports', [ReportController::class, 'getTerminatedGuideWiseReport'])->name('reports.getTerminatedGuideWiseReport');
   
    Route::get('/receipts-create', [ReceiptController::class, 'create'])->name('receipts.create');


Route::resource('activity-log', ActivityLogController::class);

Route::get('/fetch-last-tours', [GoogleCalendarController::class, 'saveLastTour'])->name('fetch.last-tours');


//  Accountant routes
Route::get('/accountant/manage-access', [AccountantController::class, 'manageAccess'])->name('accountant.manage-access');
Route::post('/accountant/update-access', [AccountantController::class, 'updateAccess'])->name('accountant.update.access');
Route::get('/accountant/manage-income-expenses', [AccountantController::class, 'manageIncomeExpenses'])->name('accountant.manage-income-expenses');
Route::post('/accountant/income-expense-type', [AccountantController::class, 'storeIncomeExpenseType'])->name('accountant.store-income-expense');
Route::put('/accountant/income-expense-type/{id}', [AccountantController::class, 'updateIncomeExpenseType'])->name('accountant.update-income-expense');
Route::delete('/accountant/income-expense-type/{id}', [AccountantController::class, 'deleteIncomeExpenseType'])->name('accountant.delete-income-expense');

Route::get('/accountant/add-records', [AccountantController::class, 'createRecord'])->name('accountant.records.store');
Route::post('/accountant/store', [AccountantController::class, 'store'])->name('accountant.records.add');
Route::post('/accountant/records/delete', [AccountantController::class, 'deleteRecord'])->name('accountant.records.delete');





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
});


Route::get('/update-last-tour', [LastToursController::class, 'updateLastTours']);
Route::get('/update-midnightphone', [LastToursController::class, 'updateMidnightPhone']);
Route::get('/test-last-tour', [LastToursController::class, 'testLastTours']);
Route::get('/handleSyncDB', [SyncDatabasesController::class, 'handleSync']);



Route::get('/calculateSalaryHours/{date}', [SalarySummaryController::class, 'calculateSalaryHours']);

Route::get('/calculateSalaryHoursForMonth/{month?}', [SalarySummaryController::class, 'calculateSalaryHoursForMonth']);




require __DIR__ . '/auth.php';
