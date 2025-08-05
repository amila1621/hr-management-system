<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\Receipts;
use App\Models\StaffUser;
use App\Models\User;
use App\Models\Supervisors;
use App\Models\TourGuide;
use Carbon\Carbon;
use App\Models\AccountingRecord;
use App\Models\AccountingIncomeExpenseType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ReceiptController extends Controller
{
    public function create()
    {
        // Get employees for supervisor
        $employees = collect();
        $employees = User::where('id', Auth::id())->get();

        return view('receipts.create', compact('employees'));
    }

    public function manage()
    {
        $receipts = Receipts::where('user_id', auth()->user()->id)->latest()->get();
        return view('receipts.manage', compact('receipts'));
    }

    public function supervisorReceiptManage()
    {

        $supervisor = \App\Models\Supervisors::where('user_id', auth()->id())->first();
        $supervisorDepartments = explode(', ', $supervisor->department);

        // Get receipt counts for the supervisor's departments
        $receipts = \App\Models\Receipts::where(function ($query) use ($supervisorDepartments) {
            foreach ($supervisorDepartments as $department) {
                $query->orWhere('department', 'LIKE', $department)
                    ->orWhere('department', 'LIKE', $department . ',%')
                    ->orWhere('department', 'LIKE', '%, ' . $department)
                    ->orWhere('department', 'LIKE', '%, ' . $department . ',%');
            }
        })->where('status', 0)->get();


        return view('receipts.supervisor-manage', compact('receipts'));
    }

    public function approve()
    {
        $receipts = Receipts::whereIn('status', [0,1])->get();
        $accountingCategories = AccountingIncomeExpenseType::where('active', true)
            ->orderBy('name')
            ->get();
        return view('receipts.approve', compact('receipts', 'accountingCategories'));
    }

    public function destroy($id)
    {
        $receipt = Receipts::find($id);
        $receipt->delete();
        return redirect()->back()->with('success', 'Receipt deleted successfully.');
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'note' => 'nullable|string|max:1000',
            'amount' => 'required',
            'employee_id' => 'nullable|exists:users,id', // This validates the employee selection
        ]);

        try {
            // Handle file upload
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('receipts', $fileName, 'public');
            }

            // Format applied_month if provided
            $appliedMonth = null;
            if ($request->applied_month) {
                $appliedMonth = Carbon::createFromFormat('Y-m', $request->applied_month)->startOfMonth();
            }

            // Get employee_id from form input and use it as user_id in the database
            // If no employee is selected, use the current authenticated user's ID
            $userId = $request->employee_id ?? auth()->id();
            $department = StaffUser::where('user_id', $userId)->first()->department;

            // Create receipt record
            Receipts::create([
                'receipt' => $path ?? null,
                'note' => $request->note,
                'user_id' => $userId, // Saving the selected employee's ID as user_id
                'department' => $department,
                'status' => 0, // 0: pending
                'approved_by' => null,
                'amount' => $request->amount,
                'created_by' => auth()->id(), // The current user who is creating this record
                'applied_month' => Carbon::now()->format('Y-m-01'),
            ]);

            return redirect()->back()->with('success', 'Receipt submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to submit receipt. Please try again.')
                ->withInput();
        }
    }

    public function supervisorUpdateStatus(Request $request, $id)
    {
        $receipt = Receipts::findOrFail($id);

        // Update status
        $receipt->status = $request->status;

        // For approval
        if ($request->status == 1) {
            $receipt->approved_by = auth()->id();
            $receipt->applied_month = $request->applied_month;
        }

        // For rejection
        if ($request->status == 3) {
            $receipt->rejection_reason = $request->rejection_reason;
        }

        $receipt->save();

        $statusMessage = $request->status == 1 ? 'approved' : 'rejected';
        return redirect()->back()->with('success', "Receipt has been {$statusMessage} successfully.");
    }

    public function updateStatus(Receipts $receipt, Request $request)
    {
        $request->validate([
            'status' => 'required|in:1,2,3',
            'rejection_reason' => 'required_if:status,3',
            'create_accounting_record' => 'sometimes|boolean',
            'accounting_category_id' => 'required_if:create_accounting_record,1',
            'accounting_description' => 'nullable|string|max:255',
            'approval_description' => 'nullable|string|max:255',
            'accounting_amount' => 'nullable|numeric',
            'applied_month' => 'nullable|string',
        ]);
    
        // Update receipt status
        $receipt->update([
            'status' => $request->status,
            'approved_by' => $request->status == 2 ? auth()->id() : null,
            'rejection_reason' => $request->status == 3 ? $request->rejection_reason : null,
            'approval_description' => $request->status == 2 ? $request->approval_description : null,
            'applied_month' => $request->applied_month ? Carbon::parse($request->applied_month . '-01')->format('Y-m-d') : $receipt->applied_month,
        ]);
    
        // Create accounting record if approved and checkbox is checked
        if ($request->status == 2 && $request->create_accounting_record == 1) {
            try {
                // Get the category to use its name as the record_type
                $category = AccountingIncomeExpenseType::findOrFail($request->accounting_category_id);
                
                // Use provided amount or fall back to receipt amount
                $amount = $request->accounting_amount ?: $receipt->amount;
                
                // Format the date correctly - ensure it's the first day of the month
                $appliedDate = Carbon::parse($request->applied_month)->format('Y-m-d');
                
                // Create the record data array
                $recordData = [
                    'user_id' => $receipt->user_id,
                    'record_type' => $category->name, 
                    'expense_type' => 'payback', 
                    'amount' => $amount,
                    'date' => $appliedDate,
                    'status' => 'approved',
                    'created_by' => auth()->id(),
                    // 'description' => $request->accounting_description ?: "Receipt #{$receipt->id}",
                    // 'receipt_id' => $receipt->id,  // Link back to the receipt
                ];
                
                // Actually create the accounting record
                AccountingRecord::create($recordData);
                
            } catch (\Exception $e) {
                \Log::error('Failed to create accounting record: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Receipt was approved but failed to create accounting record: ' . $e->getMessage());
            }
        }
    
        $statusText = $request->status == 2 ? 'approved' : ($request->status == 3 ? 'rejected' : 'updated');
        return redirect()->back()->with('success', "Receipt has been {$statusText} successfully.");
    }
}
