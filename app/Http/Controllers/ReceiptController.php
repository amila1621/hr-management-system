<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\Receipts;

class ReceiptController extends Controller
{
    public function create()
    {
        return view('receipts.create');
    }

    public function manage(){
        $receipts = Receipts::where('user_id', auth()->user()->id)->latest()->get();
        return view('receipts.manage', compact('receipts'));
    }
    public function approve(){
        $receipts = Receipts::where('status',0)->get();
        return view('receipts.approve', compact('receipts'));
    }

    public function destroy($id){
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
        ]);

        try {
            // Handle file upload
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('receipts', $fileName, 'public');
            }

            // Create receipt record
            Receipts::create([
                'receipt' => $path ?? null,
                'note' => $request->note,
                'user_id' => auth()->id(),
                'status' => 0, // 0: pending
                'approved_by' => null,
            ]);

            return redirect()->back()->with('success', 'Receipt submitted successfully!');

        } catch (\Exception $e) {
            \Log::error('Receipt creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to submit receipt. Please try again.')
                ->withInput();
        }
    }

    public function updateStatus(Receipts $receipt, Request $request)
    {
        $request->validate([
            'status' => 'required|in:1,2'
        ]);

        $receipt->update([
            'status' => $request->status,
            'approved_by' => auth()->id()
        ]);


        $statusText = $request->status == 1 ? 'approved' : 'rejected';
        return redirect()->back()->with('success', "Receipt has been {$statusText} successfully.");
    }
}
