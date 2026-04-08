# Code Quality Analysis Report
**Generated:** 2026-04-08  
**Scope:** Controllers, Models, Support Classes  
**Status:** Comprehensive audit completed

---

## Executive Summary

The codebase exhibits **high code duplication across controllers**, **migration compatibility remnants in models**, and **opportunities for helper extraction**. Below are 16 critical and high-priority issues identified for cleanup and better maintainability.

---

## 🔴 CRITICAL ISSUES

### 1. **Duplicate Model Relationship Methods - Repair Model**
**File:** [app/Models/Repair.php](app/Models/Repair.php)  
**Type:** Duplicate/Orphaned Methods  
**Lines:** 65-82  
**Priority:** CRITICAL  

**Issue:**  
Three methods define relationships to the same `accepted_by` foreign key:
- `executor()` (line 65)
- `assignee()` (line 72)  
- `acceptedBy()` (line 79)

All three return `$this->belongsTo(User::class, 'accepted_by')`, making them true duplicates that cause confusion and maintenance overhead.

**Impact:** Code clarity, API confusion, unnecessary indirection  
**Recommendation:** Remove `executor()` and `assignee()`, keep only `acceptedBy()` with clear naming

---

### 2. **Duplicate Relationship Methods - User Model**
**File:** [app/Models/User.php](app/Models/User.php)  
**Lines:** 87-91  
**Priority:** CRITICAL  

**Issue:**  
```php
public function assignedRepairs(): HasMany  // Line 87
{
    return $this->hasMany(Repair::class, 'accepted_by');
}

public function acceptedRepairs(): HasMany  // Line 91
{
    return $this->hasMany(Repair::class, 'accepted_by');
}
```

Both methods are identical - they query repairs where the user is in the `accepted_by` column. These should be consolidated into a single method.

**Impact:** Maintenance confusion, API ambiguity  
**Recommendation:** Remove `assignedRepairs()`, use only `acceptedRepairs()`

---

## 🟠 HIGH-PRIORITY ISSUES

### 3. **Migration Remnants - Compatibility Getters/Setters (Repair Model)**
**File:** [app/Models/Repair.php](app/Models/Repair.php)  
**Lines:** 129-158  
**Type:** Migration Remnants  
**Priority:** HIGH  

**Issue:**  
Three sets of compatibility attributes handle legacy field name mappings:
- `getReportedByUserIdAttribute()` / `setReportedByUserIdAttribute()` (lines 129-135)
- `getAcceptedByUserIdAttribute()` / `setAcceptedByUserIdAttribute()` (lines 137-143)
- `getActualCompletionAttribute()` / `setActualCompletionAttribute()` (lines 145-151)

These map:
- `issue_reported_by` ↔ `reported_by_user_id`
- `accepted_by` ↔ `accepted_by_user_id`
- `end_date` ↔ `actual_completion`

**Impact:** Codebase bloat, suggests incomplete migration/schema unification  
**Recommendation:** Verify which columns are actually in use and remove obsolete mappings

---

### 4. **Migration Remnants - Compatibility Getters/Setters (DeviceTransfer Model)**
**File:** [app/Models/DeviceTransfer.php](app/Models/DeviceTransfer.php)  
**Lines:** 55-64  
**Type:** Migration Remnants  
**Priority:** HIGH  

**Issue:**  
```php
public function getTransferToUserIdAttribute(): mixed
{
    return $this->attributes['transfered_to_id'] ?? $this->attributes['transfer_to_user_id'] ?? null;
}

public function setTransferToUserIdAttribute(mixed $value): void
{
    $this->attributes['transfered_to_id'] = $value;
    $this->attributes['transfer_to_user_id'] = $value;
}
```

Maps `transfered_to_id` ↔ `transfer_to_user_id`, indicating incomplete schema migration.

**Impact:** Data consistency risks, code duplication  
**Recommendation:** Verify actual database column and remove unnecessary aliases

---

### 5. **Massive Code Duplication - `repairStatusLabel()` Method**
**Files:** **6 controllers**  
**Type:** Duplicate/Verbose Code Pattern  
**Priority:** HIGH  

**Issue:**  
The `repairStatusLabel(?string $status): string` method is defined identically across:
- [app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php#L247)
- [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php#L1167)
- [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php#L506)
- [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php#L452)
- [app/Http/Controllers/UserRequestCenterController.php](app/Http/Controllers/UserRequestCenterController.php#L291)
- [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php#L405)

**Code:**
```php
private function repairStatusLabel(?string $status): string
{
    return match ($status) {
        'waiting' => 'Gaida',
        'in-progress' => 'Procesā',
        default => null,
    };
}
```

**Impact:** 180+ lines of duplicated code, maintenance nightmare, inconsistent translations if changed  
**Recommendation:** Extract to shared trait `RepairStatusLabels` or service class

---

### 6. **Warehouse Room Setup - Duplicated Logic**
**Files:** 3 controllers  
**Type:** Duplicate/Verbose Code Pattern  
**Priority:** HIGH  

**Issue:**  
`ensureWarehouseRoom()` method implemented in both:
- [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php#L989-L1015) (27 lines)
- [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php#L455-L481) (27 lines)

Both attempt to create or fetch warehouse rooms with identical logic.

**Impact:** High duplication, risk of inconsistent behavior  
**Recommendation:** Extract to `WarehouseRoomManager` service or support class

---

### 7. **Device Validation - Similar Duplicate Methods**
**Files:** 4 controllers  
**Type:** Verbose Code Pattern  
**Priority:** HIGH  

**Issue:**  
Similar validation methods across controllers, each handling specific request types:
- `ensureDeviceCanAcceptRepairRequest()` - [RepairRequestController.php](app/Http/Controllers/RepairRequestController.php#L425)
- `ensureDeviceCanAcceptTransferRequest()` - [DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php#L479)
- `ensureDeviceCanAcceptWriteoffRequest()` - [WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php#L378)
- `ensureDeviceCanAcceptRequest()` - [UserRequestCenterController.php](app/Http/Controllers/UserRequestCenterController.php#L207)

All check:
- If device already has pending repair
- If device is in compatible status

**Impact:** Code duplication, maintenance burden, consistency risks  
**Recommendation:** Consolidate to single `DeviceRequestValidator` service with request type parameter

---

## 🟡 MEDIUM-PRIORITY ISSUES

### 8. **Verbose Warehouse Room Constants**
**Files:** Multiple controllers  
**Type:** Verbose/Repetitive Code Patterns  
**Priority:** MEDIUM  

**Issue:**  
Same constants defined in multiple controllers:
- [DeviceController.php](app/Http/Controllers/DeviceController.php#L38-L41): Lines 38-41
- [WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php#L24-L28): Lines 24-28

```php
private const DEFAULT_WAREHOUSE_ROOM_NAME = 'Noliktava';
private const DEFAULT_WAREHOUSE_ROOM_NUMBER_PREFIX = 'NOL-';
private const DEFAULT_BUILDING_NAME = 'Ludzas novada pašvaldība';
```

**Impact:** Configuration scattered, hard to update globally  
**Recommendation:** Move constants to `config/devices.php` or `app/Support/WarehouseConstants.php`

---

### 9. **RuntimeSchemaBootstrapper - Incomplete Migration Compatibility**
**File:** [app/Support/RuntimeSchemaBootstrapper.php](app/Support/RuntimeSchemaBootstrapper.php)  
**Type:** Migration Remnants  
**Priority:** MEDIUM  

**Issue:**  
Large support class (600+ lines) dedicated to schema flexibility at runtime. While necessary for migration scenarios, the presence indicates:
- Schema not fully stabilized
- Multiple code paths for legacy vs. new columns
- Ongoing migration cleanup needed

**Lines:** 1-600+  
**Impact:** Complexity, legacy baggage, runtime overheard  
**Recommendation:** Create clear schema version and schedule deprecation timeline

---

### 10. **AuthBootstrapper - Legacy Sync Code**
**File:** [app/Support/AuthBootstrapper.php](app/Support/AuthBootstrapper.php#L149)  
**Type:** Migration Remnants  
**Priority:** MEDIUM  

**Issue:**  
Comment indicates legacy employee table sync was removed:
```php
// Legacy employees table sync removed - employees table is dropped in migration 2026_03_18_010000_drop_unused_legacy_features
```

But the bootstrapper still handles backfill scenarios and legacy role normalization. Suggests incomplete cleanup.

**Impact:** Residual legacy handling code, potential for bugs  
**Recommendation:** Clean up legacy normalization functions or document why they're still needed

---

## 🔵 LOWER-PRIORITY ISSUES

### 11. **Verbose Export in User Model**
**File:** [app/Models/User.php](app/Models/User.php#L107-L180)  
**Type:** Verbose Code Pattern  
**Priority:** MEDIUM  

**Issue:**  
User model has 15+ public/protected relationship methods for various scenarios:
- `createdDevices()`, `assignedDevices()`
- `reportedRepairs()`, `assignedRepairs()`, `acceptedRepairs()` (with duplicates per issue #2)
- `repairRequests()`, `reviewedRepairRequests()`
- `writeoffRequests()`, `reviewedWriteoffRequests()`
- `outgoingTransfers()`, `incomingTransfers()`, `reviewedTransfers()`
- `responsibleRooms()`, `auditLogs()`

While well-organized, this could use a relationship factory or lazy loader to reduce cognitive load.

**Impact:** Model becomes fat, harder to navigate  
**Recommendation:** Move relationship definitions to separate `UserRelationships` trait

---

### 12. **Device Model - Relationship Naming Inconsistency**
**File:** [app/Models/Device.php](app/Models/Device.php#L86-92)  
**Lines:** 86-92  
**Type:** Code Pattern Issue  
**Priority:** MEDIUM  

**Issue:**  
Method naming inconsistency:
```php
public function assignedTo(): BelongsTo  // Line 82
public function assignedUser(): BelongsTo  // Line 89 - Alias comment
```

Comment notes Aliasing for legacy code, but unclear if both should be kept.

**Impact:** API confusion, unclear intent  
**Recommendation:** Deprecate one in favor of the other (likely `assignedUser()`)

---

### 13. **Repair Model - Calculated Attributes Verbose**
**File:** [app/Models/Repair.php](app/Models/Repair.php#L89-122)  
**Lines:** 89-122  
**Type:** Verbose Code Pattern  
**Priority:** LOW  

**Issue:**  
Two calculated accessor methods that perform database queries:
- `getApprovalActorAttribute()` - queries if not loaded, falls back to request
- `getApprovalActorNameAttribute()` - similar with multiple fallbacks

Both use N+1 query patterns and nested conditionals for fallbacks.

**Impact:** Performance concerns, complex logic  
**Recommendation:** Consolidate and use eager loading in queries

---

### 14. **DeviceController - Missing Separation of Concerns**
**File:** [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php)  
**Lines:** 1-1700  
**Type:** Verbose/Complex Code Pattern  
**Priority:** MEDIUM  

**Issue:** The largest controller at 1,697 lines handles:
- Index/table views with complex filtering
- Full CRUD operations
- Asset management
- Warehouse room creation
- Repair status displays
- Quick actions
- Form validation

**Impact:** Testing difficult, code navigation hard, unclear responsibilities  
**Recommendation:** Extract to:
- `DeviceListController` (index/table)
- `DeviceCRUDController` (create/edit/delete)
- Traits for shared functionality

---

### 15. **AuditTrail Support Class - Many Static Methods Without Interface**
**File:** [app/Support/AuditTrail.php](app/Support/AuditTrail.php)  
**Type:** Code Pattern  
**Priority:** LOW  

**Issue:**  
Large support class (~40+ public static methods) for audit logging. All static methods make testing harder (can't mock), and there's no clear interface contract.

**Impact:** Testing difficulty, rigid API  
**Recommendation:** Consider creating injectable `AuditService` interface for non-critical logging

---

### 16. **RouteHelper - Inline Usage vs Extracted Model**
**File:** [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php#L1346-L1348)  
**Lines:** 1346-1348  
**Type:** Code Pattern  
**Priority:** LOW  

**Issue:**  
Route checking/generation scattered across controllers:
```php
'repair' => Route::has('repair-requests.index') ? route('repair-requests.index', $params) : null,
```

Repeated in multiple views/controllers for similar constructs.

**Impact:** Route names duplicated, hard to refactor route names  
**Recommendation:** Create view helper `routeIfExists(name, params)` or model method

---

## Summary Table

| Issue # | Type | File | Priority | Lines | Issue |
|---------|------|------|----------|-------|-------|
| 1 | Duplicate Methods | Repair.php | CRITICAL | 65-82 | executor/assignee/acceptedBy |
| 2 | Duplicate Methods | User.php | CRITICAL | 87-91 | assignedRepairs/acceptedRepairs |
| 3 | Migration Remnants | Repair.php | HIGH | 129-158 | Compatibility getters/setters |
| 4 | Migration Remnants | DeviceTransfer.php | HIGH | 55-64 | Compatibility getters/setters |
| 5 | Code Duplication | 6 Controllers | HIGH | Various | repairStatusLabel() |
| 6 | Code Duplication | 2 Controllers | HIGH | 989-1015 | ensureWarehouseRoom() |
| 7 | Code Duplication | 4 Controllers | HIGH | Various | Device validation methods |
| 8 | Constants Scattered | Multiple | MEDIUM | Multiple | Warehouse constants |
| 9 | Complex Support | RuntimeSchemaBootstrapper.php | MEDIUM | 1-600+ | Schema flexibility bloat |
| 10 | Legacy Code | AuthBootstrapper.php | MEDIUM | 149+ | Legacy sync remnants |
| 11 | Fat Model | User.php | MEDIUM | 107-180 | Too many relationships |
| 12 | Naming Inconsistency | Device.php | MEDIUM | 86-92 | assignedTo vs assignedUser |
| 13 | Verbose Accessors | Repair.php | LOW | 89-122 | Calculated attributes |
| 14 | Fat Controller | DeviceController.php | MEDIUM | 1-1700 | 1,697 lines, multiple concerns |
| 15 | Static Methods | AuditTrail.php | LOW | Various | 40+ static methods |
| 16 | Scattered Routes | Multiple | LOW | Various | Inline route logic |

---

## Recommended Cleanup Sequence

### Phase 1: Quick Wins (1-2 hours)
1. Remove duplicate methods: `executor()`, `assignee()` in Repair; `assignedRepairs()` in User
2. Move warehouse constants to config file
3. Add deprecation notices for `assignedUser()` in Device model

### Phase 2: Medium Effort (4-6 hours)
4. Create `RepairStatusLabelsTrait` and use across controllers
5. Extract `DeviceRequestValidator` service for validation consolidation
6. Extract `WarehouseRoomManager` service
7. Move compatibility getters/setters to separate migration utility

### Phase 3: Refactoring (1-2 days)
8. Split DeviceController into logical controllers
9. Extract User model relationships into trait
10. Consider AuditTrail interface/service pattern
11. Create route helper for conditional routes

### Phase 4: Deprecation & Cleanup (Ongoing)
12. Schedule removal of RuntimeSchemaBootstrapper (post-production-stabilization)
13. Plan schema version stability and document it
14. Remove legacy compatibility code once confirmed not needed

---

## Files to Review First

**By Priority:**
1. [app/Models/Repair.php](app/Models/Repair.php) - Issues #1, #3, #13
2. [app/Models/User.php](app/Models/User.php) - Issues #2, #11
3. [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php) - Issues #5, #6, #8, #14
4. Controllers: RepairRequestController, DeviceTransferController, WriteoffRequestController - Issue #7
5. [app/Support/RuntimeSchemaBootstrapper.php](app/Support/RuntimeSchemaBootstrapper.php) - Issue #9

---

## Notes

- All issue line numbers are 1-indexed per standard
- Recommendations are suggestions; evaluate based on project constraints
- This analysis prepared with focus on AI context clarity and maintainability
- Code style is generally clean; issues are structural/duplicative, not stylistic
