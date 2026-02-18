<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        $employees = Employee::query()
            ->when($q, function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('job_title', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->get();

        return view('employees.index', compact('employees', 'q'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable'], // checkbox
        ]);

        // checkbox -> boolean
        $data['is_active'] = $request->has('is_active');

        $employee = Employee::create($data);

        // audit log
        $userId = auth()->check() ? auth()->id() : null;
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'CREATE',
            'entity_type' => 'Employee',
            'entity_id' => (string)$employee->id,
            'description' => 'Employee created: ' . $employee->full_name,
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee created');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->has('is_active');

       
        $before = $employee->only(['full_name','email','phone','job_title','is_active']);

        $employee->update($data);

        $after = $employee->fresh()->only(array_keys($before));
        $changed = [];

        foreach ($before as $k => $old) {
            $new = $after[$k] ?? null;
            if ((string)$old !== (string)$new) $changed[] = $k;
        }

        $userId = auth()->check() ? auth()->id() : null;
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'UPDATE',
            'entity_type' => 'Employee',
            'entity_id' => (string)$employee->id,
            'description' => 'Employee updated: ' . $employee->full_name . (count($changed) ? ' | fields: ' . implode(', ', $changed) : ''),
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee updated');
    }

    public function destroy(Employee $employee)
    {
        $userId = auth()->check() ? auth()->id() : null;

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'DELETE',
            'entity_type' => 'Employee',
            'entity_id' => (string)$employee->id,
            'description' => 'Employee deleted: ' . $employee->full_name,
        ]);

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted');
    }

    
    public function show(Employee $employee)
    {
        return redirect()->route('employees.index');
    }
}
