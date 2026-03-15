<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'first_name' => trim((string) $request->query('first_name', '')),
            'last_name' => trim((string) $request->query('last_name', '')),
            'phone' => trim((string) $request->query('phone', '')),
            'job_title' => trim((string) $request->query('job_title', '')),
            'is_active' => (string) $request->query('is_active', ''),
        ];

        $allowedSorts = ['full_name', 'phone', 'job_title', 'is_active', 'created_at'];
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $employees = Employee::query()
            ->when($filters['first_name'] !== '', function ($query) use ($filters) {
                $query->where('full_name', 'like', '%' . $filters['first_name'] . '%');
            })
            ->when($filters['last_name'] !== '', function ($query) use ($filters) {
                $query->where('full_name', 'like', '%' . $filters['last_name'] . '%');
            })
            ->when($filters['phone'] !== '', function ($query) use ($filters) {
                $query->where('phone', 'like', '%' . $filters['phone'] . '%');
            })
            ->when($filters['job_title'] !== '', function ($query) use ($filters) {
                $query->where('job_title', $filters['job_title']);
            })
            ->when($filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active'] === '1');
            })
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $jobTitles = Employee::query()
            ->whereNotNull('job_title')
            ->where('job_title', '!=', '')
            ->distinct()
            ->orderBy('job_title')
            ->pluck('job_title');

        return view('employees.index', compact('employees', 'filters', 'jobTitles', 'sort', 'direction'));
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
        $userId = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;
        AuditTrail::created($userId, $employee);

        return redirect()->route('employees.index')->with('success', 'Darbinieks veiksmigi pievienots');
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
        $userId = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;
        AuditTrail::updatedFromState($userId, $employee, $before, $after);

        return redirect()->route('employees.index')->with('success', 'Darbinieka dati atjauninati');
    }

    public function destroy(Employee $employee)
    {
        $userId = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        AuditTrail::deleted($userId, $employee);

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Darbinieks dzests');
    }

    
    public function show(Employee $employee)
    {
        return redirect()->route('employees.index');
    }
}
