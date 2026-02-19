<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepairController extends Controller
{
    private const STATUSES = ['waiting', 'in-progress', 'completed', 'cancelled'];
    private const TYPES = ['internal', 'external'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public function index(Request $request)
    {
        $q = $request->query('q');

        $repairs = Repair::with(['device', 'reporter', 'assignee'])
            ->when($q, function ($query) use ($q) {
                $query->where('description', 'like', "%{$q}%")
                    ->orWhere('invoice_number', 'like', "%{$q}%")
                    ->orWhere('vendor_name', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->get();

        return view('repairs.index', compact('repairs', 'q'));
    }

    public function create()
    {
        return view('repairs.create', $this->formData());
    }

    public function store(Request $request)
    {
        Repair::create($this->validatedData($request));

        return redirect()->route('repairs.index')->with('success', 'Remonts veiksmigi pievienots');
    }

    public function edit(Repair $repair)
    {
        return view('repairs.edit', array_merge(['repair' => $repair], $this->formData()));
    }

    public function update(Request $request, Repair $repair)
    {
        $repair->update($this->validatedData($request));

        return redirect()->route('repairs.index')->with('success', 'Remonts atjauninats');
    }

    public function destroy(Repair $repair)
    {
        $repair->delete();

        return redirect()->route('repairs.index')->with('success', 'Remonts dzests');
    }

    public function show(Repair $repair)
    {
        return redirect()->route('repairs.index');
    }

    private function formData(): array
    {
        return [
            'devices' => Device::orderByDesc('id')->get(),
            'users' => User::with('employee')->orderByDesc('id')->get(),
            'statuses' => self::STATUSES,
            'repairTypes' => self::TYPES,
            'priorities' => self::PRIORITIES,
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'repair_type' => ['required', Rule::in(self::TYPES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'start_date' => ['required', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'issue_reported_by' => ['nullable', 'exists:users,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        foreach ([
            'status', 'priority', 'estimated_completion', 'actual_completion',
            'vendor_name', 'vendor_contact', 'invoice_number', 'issue_reported_by', 'assigned_to',
        ] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
