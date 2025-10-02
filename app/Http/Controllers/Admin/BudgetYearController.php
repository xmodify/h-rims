<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\BudgetYear;

class BudgetYearController extends Controller
{
    public function index()
    {
        $budget_year = BudgetYear::orderBy('LEAVE_YEAR_ID', 'DESC')->get();
        return view('admin.budget_year.index', compact('budget_year'));
    }

    // public function create()
    // {
    //     return view('admin.budget_year.create');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'LEAVE_YEAR_ID' => 'required',
            'LEAVE_YEAR_NAME' => 'required',
            'DATE_BEGIN' => 'required',
            'DATE_END' => 'required', 
        ]);

        BudgetYear::create([
            'LEAVE_YEAR_ID' => $request->LEAVE_YEAR_ID,
            'LEAVE_YEAR_NAME' => $request->LEAVE_YEAR_NAME,
            'DATE_BEGIN' => $request->DATE_BEGIN,
            'DATE_END' => $request->DATE_END,
        ]);

        return redirect()->route('admin.budget_year.index')->with('success', 'เพิ่มข้อมูลสำเร็จ');
    }

    // public function edit(BudgetYear $budget)
    // {
    //     return view('admin.budget_year.edit', compact('budget_year'));
    // }

    public function update(Request $request, BudgetYear $budget_year)
    {
        $validated = $request->validate([
        'LEAVE_YEAR_ID' => 'required',
        'LEAVE_YEAR_NAME' => 'required',
        'DATE_BEGIN' => 'required',
        'DATE_END' => 'required'
        ]);

        $data = [
            'LEAVE_YEAR_ID' => $request->LEAVE_YEAR_ID,
            'LEAVE_YEAR_NAME' => $request->LEAVE_YEAR_NAME,
            'DATE_BEGIN' => $request->DATE_BEGIN,           
            'DATE_END' => $request->DATE_END,
        ];

        $budget_year->update($data);

        return redirect()->route('admin.budget_year.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(BudgetYear $budget_year)
    {
        $budget_year->delete();
        return redirect()->route('admin.budget_year.index')->with('success', 'ลบข้อมูลสำเร็จ');
    }
    
}
