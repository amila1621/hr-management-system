<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryUpdated;
use App\Models\TourGuide;

class SalaryUpdatedController extends Controller
{

    public function index()
    {
        $guides = TourGuide::orderBy('name','asc')->get();
        $salaryUpdated = SalaryUpdated::all();

        return view('salary_updated.index', compact('guides','salaryUpdated'));
    }

    public function store(Request $request)
    {
       

        $guideName = TourGuide::find($request->guide_id)->name;
        $salaryUpdated = new SalaryUpdated();
        $salaryUpdated->guide_id = $request->guide_id;
        $salaryUpdated->guide_name = $guideName;
        $salaryUpdated->effective_date = $request->effective_date;

        $salaryUpdated->save();

        return redirect()->route('salary-updates.index')->with('success', 'Salary update added successfully');
    }

    public function edit($id)
    {
        $salaryUpdated = SalaryUpdated::find($id);
        return view('salary_updated.edit', compact('salaryUpdated'));
    }

    public function update(Request $request, $id)
    {
        $salaryUpdated = SalaryUpdated::find($id);
        $salaryUpdated->effective_date = $request->effective_date;
        $salaryUpdated->save();
        return redirect()->route('salary-updates.index')->with('success', 'Salary update updated successfully');
    }

    public function destroy($id)
    {
        $salaryUpdated = SalaryUpdated::find($id);
        $salaryUpdated->delete();
        return redirect()->route('salary-updates.index')->with('success', 'Salary update deleted successfully');
    }
}
