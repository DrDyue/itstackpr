<?php

namespace App\Constants;

class StatusConstants
{
    // Repair statuses
    public const REPAIR_STATUS_WAITING = 'waiting';
    public const REPAIR_STATUS_IN_PROGRESS = 'in-progress';
    public const REPAIR_STATUS_COMPLETED = 'completed';
    public const REPAIR_STATUS_CANCELLED = 'cancelled';

    public const REPAIR_STATUSES = [
        self::REPAIR_STATUS_WAITING,
        self::REPAIR_STATUS_IN_PROGRESS,
        self::REPAIR_STATUS_COMPLETED,
        self::REPAIR_STATUS_CANCELLED,
    ];

    // Repair types
    public const REPAIR_TYPE_INTERNAL = 'internal';
    public const REPAIR_TYPE_EXTERNAL = 'external';

    public const REPAIR_TYPES = [
        self::REPAIR_TYPE_INTERNAL,
        self::REPAIR_TYPE_EXTERNAL,
    ];

    // Repair priorities
    public const REPAIR_PRIORITY_LOW = 'low';
    public const REPAIR_PRIORITY_MEDIUM = 'medium';
    public const REPAIR_PRIORITY_HIGH = 'high';
    public const REPAIR_PRIORITY_CRITICAL = 'critical';

    public const REPAIR_PRIORITIES = [
        self::REPAIR_PRIORITY_LOW,
        self::REPAIR_PRIORITY_MEDIUM,
        self::REPAIR_PRIORITY_HIGH,
        self::REPAIR_PRIORITY_CRITICAL,
    ];

    // Request statuses (WriteoffRequest, DeviceTransfer, RepairRequest)
    public const REQUEST_STATUS_PENDING = 'pending';
    public const REQUEST_STATUS_APPROVED = 'approved';
    public const REQUEST_STATUS_DENIED = 'denied';

    public const REQUEST_STATUSES = [
        self::REQUEST_STATUS_PENDING,
        self::REQUEST_STATUS_APPROVED,
        self::REQUEST_STATUS_DENIED,
    ];

    // Device statuses
    public const DEVICE_STATUS_ACTIVE = 'active';
    public const DEVICE_STATUS_RESERVE = 'reserve';
    public const DEVICE_STATUS_BROKEN = 'broken';
    public const DEVICE_STATUS_KITTING = 'kitting';

    public const DEVICE_STATUSES = [
        self::DEVICE_STATUS_ACTIVE,
        self::DEVICE_STATUS_RESERVE,
        self::DEVICE_STATUS_BROKEN,
        self::DEVICE_STATUS_KITTING,
    ];

    // Restorable device statuses (for repairs)
    public const RESTORABLE_DEVICE_STATUSES = [
        self::DEVICE_STATUS_ACTIVE,
        self::DEVICE_STATUS_RESERVE,
        self::DEVICE_STATUS_BROKEN,
        self::DEVICE_STATUS_KITTING,
    ];
}
