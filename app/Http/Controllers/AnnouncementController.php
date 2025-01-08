<?php

namespace App\Http\Controllers;

use App\Models\Announcements;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function manageAnnouncements()
    {
        $announcements = Announcements::all();
        return view('announcements.manage', compact('announcements'));
    }

    public function storeAnnouncements(Request $request)
    {
        $announcement = Announcements::create($request->all());
        return redirect()->route('announcements.manage')->with('success', 'Announcement created successfully');
    }

    public function destroyAnnouncements($id)
    {
        $announcement = Announcements::findOrFail($id);
        $announcement->delete();
        return redirect()->route('announcements.manage')->with('success', 'Announcement deleted successfully');
    }

    public function acknowledgeAnnouncement($id)
    {
        $announcement = Announcements::findOrFail($id);
        $announcement->acknowledgedBy()->attach(auth()->user());
        return redirect()->back()->with('success', 'Announcement acknowledged successfully');
    }
}
