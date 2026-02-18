<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Repair;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $repairs = Repair::with('device')
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->get();

        $statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];

        return view('repairs.index', compact('repairs', 'status', 'statuses'));
    }

    public function create()
    {
        $devices = Device::orderBy('name')->get();

        $statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $types = ['internal', 'external'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return view('repairs.create', compact('devices', 'statuses', 'types', 'priorities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],

            'status' => ['required', 'in:waiting,in-progress,completed,cancelled'],
            'repair_type' => ['required', 'in:internal,external'],
            'priority' => ['required', 'in:low,medium,high,critical'],

            'start_date' => ['required', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],

            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
        ]);

        // пока без users — позже подключим auth и будем писать issue_reported_by/assigned_to
        $data['issue_reported_by'] = auth()->check() ? auth()->id() : null;
        $data['assigned_to'] = null;

        Repair::create($data);

        return redirect()->route('repairs.index')->with('success', 'Repair created');
    }

    public function edit(Repair $repair)
    {
        $devices = Device::orderBy('name')->get();

        $statuses = ['waiting', 'in-progress', 'completed', 'cancelled'];
        $types = ['internal', 'external'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return view('repairs.edit', compact('repair', 'devices', 'statuses', 'types', 'priorities'));
    }

    public function update(Request $request, Repair $repair)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'description' => ['required', 'string'],

            'status' => ['required', 'in:waiting,in-progress,completed,cancelled'],
            'repair_type' => ['required', 'in:internal,external'],
            'priority' => ['required', 'in:low,medium,high,critical'],

            'start_date' => ['required', 'date'],
            'estimated_completion' => ['nullable', 'date'],
            'actual_completion' => ['nullable', 'date'],

            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:100'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
        ]);

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
