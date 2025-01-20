<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\ManagerGuideAssignment;
use App\Models\TourGuide;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    // public function storeActivityLog(Request $request)
    // {
    //     $activityLog = ActivityLog::create($request->all());
        
    //     $request->validate([
    //         'id' => 'required|integer',
    //         'logged_in_user' => 'required|string',
    //         'page_endpoint_route' => 'required|string',
    //         'ip_address' => 'required|string',
    //         'created_at' => 'required|date',
    //         'updated_at' => 'required|date'
    //     ]);
        
    //     try {
    //         // Begin transaction
    //         DB::beginTransaction();

    //         // Convert comma-separated string to array and filter out empty values
    //         $guideIds = array_filter(explode(',', $request->guide_ids));

    //         // Validate that all guide IDs exist
    //         $validGuideIds = TourGuide::whereIn('id', $guideIds)->pluck('id')->toArray();
    //         if (count($validGuideIds) !== count($guideIds)) {
    //             throw new \Exception('Invalid guide ID detected');
    //         }

    //         // Remove existing assignments for this manager
    //         ManagerGuideAssignment::where('manager_id', $request->manager_id)->delete();

    //         // Create new assignments
    //         foreach ($guideIds as $guideId) {
    //             ManagerGuideAssignment::create([
    //                 'manager_id' => $request->manager_id,
    //                 'guide_id' => $guideId
    //             ]);
    //         }

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Activity log created successfully!');

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('failed', 'Failed to create log activity: ' . $e->getMessage());
    //     }
    // }
}