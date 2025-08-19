<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExtraHoursRequest;
use App\Models\EventSalary;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExtraHoursRequestController extends Controller
{
    public function index()
    {
        $requests = ExtraHoursRequest::with(['guide', 'event', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.extra-hours-requests', compact('requests'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_comment' => 'nullable|string|max:500'
        ]);

        $extraHoursRequest = ExtraHoursRequest::findOrFail($id);
        
        if ($extraHoursRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $extraHoursRequest->update([
            'status' => 'approved',
            'admin_comment' => $request->admin_comment,
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now()
        ]);

        $eventSalary = EventSalary::where('eventId', $extraHoursRequest->event_id)
            ->where('guideId', $extraHoursRequest->guide_id)
            ->first();

        if ($eventSalary) {
            $eventSalary->update([
                'guide_end_time' => $extraHoursRequest->requested_end_time,
                'is_guide_updated' => true
            ]);
        }

        return redirect()->back()->with('success', 'Extra hours request approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_comment' => 'nullable|string|max:500'
        ]);

        $extraHoursRequest = ExtraHoursRequest::findOrFail($id);
        
        if ($extraHoursRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $extraHoursRequest->update([
            'status' => 'rejected',
            'admin_comment' => $request->admin_comment,
            'approved_by' => Auth::id(),
            'approved_at' => Carbon::now()
        ]);

        return redirect()->back()->with('success', 'Extra hours request rejected.');
    }
}
