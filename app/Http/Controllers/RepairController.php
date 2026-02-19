<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;

class RepairController extends Controller
{
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
        $devices = Device::orderByDesc('id')->get();

        // если у тебя пока нет пользователей/авторизации — можно оставить пустыми
        $users = User::orderBy('name')->get();

        $statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $repairTypes = ['internal', 'external'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return view('repairs.create', compact('devices', 'users', 'statuses', 'repairTypes', 'priorities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],

            'status' => ['nullable', 'in:waiting,in-progress,completed,cancelled'],
            'repair_type' => ['required', 'in:internal,external'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],

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

        // Если status/priority пустые, база сама подставит default
        // Но чтобы не было "" строк, приводим к null
        foreach ([
            'status','priority','estimated_completion','actual_completion',
            'vendor_name','vendor_contact','invoice_number','issue_reported_by','assigned_to'
        ] as $k) {
            if (($data[$k] ?? null) === '') $data[$k] = null;
        }

        Repair::create($data);

        return redirect()->route('repairs.index')->with('success', 'Repair created');
    }

    public function edit(Repair $repair)
    {
        $devices = Device::orderByDesc('id')->get();
        $users = User::orderBy('name')->get();

        $statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $repairTypes = ['internal', 'external'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return view('repairs.edit', compact('repair', 'devices', 'users', 'statuses', 'repairTypes', 'priorities'));
    }

    public function update(Request $request, Repair $repair)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],

            'status' => ['nullable', 'in:waiting,in-progress,completed,cancelled'],
            'repair_type' => ['required', 'in:internal,external'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],

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
            'status','priority','estimated_completion','actual_completion',
            'vendor_name','vendor_contact','invoice_number','issue_reported_by','assigned_to'
        ] as $k) {
            if (($data[$k] ?? null) === '') $data[$k] = null;
        }

        $repair->update($data);

        return redirect()->route('repairs.index')->with('success', 'Repair updated');
    }

    public function destroy(Repair $repair)
    {
        $repair->delete();
        return redirect()->route('repairs.index')->with('success', 'Repair deleted');
    }

    public function show(Repair $repair)
    {
        return redirect()->route('repairs.index');
    }
}
    