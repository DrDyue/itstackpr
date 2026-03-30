<?php

namespace App\Models;

use App\Support\DeviceAssetManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Galvenais inventāra modelis.
 *
 * Šis modelis apvieno ierīces pamatdatus, piesaisti telpai un lietotājam,
 * kā arī šaites uz remontiem un dažādu tipu pieprasījumiem.
 */
class Device extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REPAIR = 'repair';
    public const STATUS_WRITEOFF = 'writeoff';

    protected $table = 'devices';

    protected $fillable = [
        'code',
        'name',
        'device_type_id',
        'model',
        'status',
        'building_id',
        'room_id',
        'assigned_to_id',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'serial_number',
        'manufacturer',
        'notes',
        'device_image_url',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
            'purchase_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Ierīces klasifikācija pēc tipa.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class, 'device_type_id');
    }

    /**
     * Ēka, kurā ierīce šobrīd atrodas.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Telpa, kurā ierīce šobrīd atrodas.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Pašreizējāis atbildīgais lietotājs.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * Savietojamības alias vecākam koda slānim.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->assignedTo();
    }

    /**
     * Lietotājs, kurš ierīces ierakstu sākotnēji izveidoja.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Visi ierīces remonta ieraksti.
     */
    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    /**
     * Aktīvais remonta ieraksts, ja ierīce ir gaidīšanas vai procesā stāvoklī.
     */
    public function activeRepair(): HasOne
    {
        return $this->hasOne(Repair::class)
            ->whereIn('status', ['waiting', 'in-progress'])
            ->latestOfMany('id');
    }

    /**
     * Jaunākais remonta ieraksts neatkarīgi no statusa.
     */
    public function latestRepair(): HasOne
    {
        return $this->hasOne(Repair::class)->latestOfMany('id');
    }

    /**
     * Visi remonta pieprasījumi šai ierīcei.
     */
    public function repairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class);
    }

    /**
     * Pēdējais iesniegtais remonta pieprasījums, kas vēl gaida izskatīšanu.
     */
    public function pendingRepairRequest(): HasOne
    {
        return $this->hasOne(RepairRequest::class)
            ->where('status', RepairRequest::STATUS_SUBMITTED)
            ->latestOfMany('id');
    }

    /**
     * Visi norakstīšanas pieprasījumi šai ierīcei.
     */
    public function writeoffRequests(): HasMany
    {
        return $this->hasMany(WriteoffRequest::class);
    }

    /**
     * Pēdējais iesniegtais norakstīšanas pieprasījums.
     */
    public function pendingWriteoffRequest(): HasOne
    {
        return $this->hasOne(WriteoffRequest::class)
            ->where('status', WriteoffRequest::STATUS_SUBMITTED)
            ->latestOfMany('id');
    }

    /**
     * Visi ierīces nodošanas pieprasījumi.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(DeviceTransfer::class);
    }

    /**
     * Pēdējais iesniegtais nodošanas pieprasījums.
     */
    public function pendingTransferRequest(): HasOne
    {
        return $this->hasOne(DeviceTransfer::class)
            ->where('status', DeviceTransfer::STATUS_SUBMITTED)
            ->latestOfMany('id');
    }

    /**
     * Pilna izmēra attēla URL, ko droši izmantot Blade skatā.
     */
    public function deviceImageUrl(): ?string
    {
        return app(DeviceAssetManager::class)->url($this->device_image_url);
    }

    /**
     * Mazā priekšskatījuma attēla URL tabulām un kartītēm.
     */
    public function deviceImageThumbUrl(): ?string
    {
        return app(DeviceAssetManager::class)->thumbUrl($this->device_image_url);
    }

    /**
     * Savietojamības getteris vecākiem kolonnu nosaukumiem.
     */
    public function getAssignedUserIdAttribute(): mixed
    {
        return $this->attributes['assigned_to_id'] ?? $this->attributes['assigned_user_id'] ?? null;
    }

    /**
     * Savietojamības setteris vecākiem kolonnu nosaukumiem.
     */
    public function setAssignedUserIdAttribute(mixed $value): void
    {
        $this->attributes['assigned_to_id'] = $value;
        $this->attributes['assigned_user_id'] = $value;
    }

    /**
     * Vienmēr atgriež normalizētu ierīces statusu.
     */
    public function getStatusAttribute(?string $value): string
    {
        return self::normalizeStatus($value);
    }

    /**
     * Saglabāšanas brīdī izlīdzina vecos un jaunos statusu nosaukumus.
     */
    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = self::normalizeStatus($value);
    }

    /**
     * Pārveido statusu vienotā iekšējā formātā.
     */
    public static function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'repair' => self::STATUS_REPAIR,
            'writeoff', 'written_off' => self::STATUS_WRITEOFF,
            default => self::STATUS_ACTIVE,
        };
    }
}
