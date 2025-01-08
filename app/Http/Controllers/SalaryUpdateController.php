<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryUpdate;
use App\Notifications\SalaryUpdateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Activity;

class SalaryUpdateController
{
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'previous_salary' => 'required|numeric|min:0',
                'new_salary' => 'required|numeric|min:0',
                'update_date' => 'required|date',
                'reason' => 'required|string|max:500',
                'status' => 'required|in:pending,approved,rejected'
            ]);

            // Check if employee exists and get current salary
            $employee = Employee::findOrFail($validated['employee_id']);
            
            // Verify previous salary matches current employee salary
            if ($employee->current_salary != $validated['previous_salary']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Previous salary does not match employee\'s current salary.');
            }

            // Calculate increment and percentage
            $increment = $validated['new_salary'] - $validated['previous_salary'];
            $increment_percentage = ($increment / $validated['previous_salary']) * 100;

            // Create salary update record
            $salaryUpdate = SalaryUpdate::create([
                'employee_id' => $validated['employee_id'],
                'previous_salary' => $validated['previous_salary'],
                'new_salary' => $validated['new_salary'],
                'increment' => $increment,
                'increment_percentage' => $increment_percentage,
                'update_date' => $validated['update_date'],
                'reason' => $validated['reason'],
                'status' => $validated['status'],
                'created_by' => auth()->id()
            ]);

            // If status is approved, update employee's current salary
            if ($validated['status'] === 'approved') {
                $employee->update([
                    'current_salary' => $validated['new_salary'],
                    'last_salary_update' => $validated['update_date']
                ]);

                // Create audit log
                activity()
                    ->performedOn($employee)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'previous_salary' => $validated['previous_salary'],
                        'new_salary' => $validated['new_salary'],
                        'increment' => $increment,
                        'increment_percentage' => $increment_percentage,
                        'reason' => $validated['reason']
                    ])
                    ->log('salary_updated');

                // Notify relevant stakeholders
                Notification::send($employee, new SalaryUpdateNotification($salaryUpdate));
            }

            return redirect()->route('salary-updates.index')
                ->with('success', 'Salary update has been created successfully.');

        } catch (\Exception $e) {
            \Log::error('Salary Update Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating the salary update. Please try again.');
        }
    }
} 