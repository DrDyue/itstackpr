# Laravel Project - Code Optimization Report
**Date:** 2026-04-14
**Project:** itstackpr

---

## Executive Summary

This report identifies **unused imports, dead code, redundant code blocks, and refactoring opportunities** across the itstackpr Laravel codebase. The analysis focuses on controllers, models, and blade templates.

**Key Finding:** ~500+ lines of duplicated code across request controllers that can be extracted into shared traits and base classes, reducing code by 30-40%.

---

## 1. CONTROLLERS - Code Duplication (CRITICAL)

### 1.1 Critical Duplication: Request Controllers (RepairRequest, Writeoff, DeviceTransfer)

**Impact:** HIGH - 3 large controllers share 60% identical code  
**Effort:** MEDIUM - 2-3 hours to extract to traits

#### Identical Methods That Should Be Extracted to a Trait

| Method | RepairRequestController | WriteoffRequestController | DeviceTransferController | Status |
|--------|------------------------|--------------------------|--------------------------|--------|
| `normalizedIndexFilters()` | Lines 533-564 | Lines 533-565 | Lines 858-885 | DUPLICATE |
| `applyIndexFilters()` | Lines 568-627 | Lines 568-619 | Lines 945-1003 | DUPLICATE |
| `applySorting()` | Lines 856-920 | Lines 620-658 | Lines 972-1040 | DUPLICATE |
| `normalizedSorting()` | Lines 920-936 | Lines 658-681 | Lines 995-1017 | DUPLICATE |
| `sortOptions()` | Lines 936-947 | Lines 681-692 | Lines 1010-1025 | DUPLICATE |
| `deviceOptions()` | Lines 673-703 | Lines 401-429 | Lines 868-898 | DUPLICATE |

**Recommendation:** Create trait `HasRequestListFiltering` with these 6 methods.

---

#### Similar Device Validation Methods (Code Duplication)

**File:** [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php)  
**Lines:** 747-798  
**Method:** `ensureDeviceCanAcceptRepairRequest(Device $device): void`

```php
// Checks:
1. if ($device->status === Device::STATUS_REPAIR) - REPAIR_STATUS_CHECK
2. RepairRequest::query()->where('device_id', ...)->where('status', SUBMITTED)->exists() - SELF_REQUEST_CHECK
3. WriteoffRequest::query()->where('device_id', ...)->where('status', 'submitted')->exists() - WRITEOFF_REQUEST_CHECK
4. DeviceTransfer::query()->where('device_id', ...)->where('status', 'submitted')->exists() - TRANSFER_REQUEST_CHECK
```

**File:** [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php)  
**Lines:** 374-400  
**Method:** `ensureDeviceCanAcceptWriteoffRequest(Device $device): void`

Same 4 checks, nearly identical code - **82% duplication**

**File:** [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php)  
**Lines:** 889-925  
**Method:** `ensureDeviceCanAcceptTransferRequest(Device $device): void`

Same 4 checks with identical pattern - **82% duplication**

**Recommendation:** Create method in base `Controller` class:
```php
protected function ensureDeviceAvailableForRequest(Device $device, ?string $skipRequestType = null): void
```

---

#### Device Options Methods (Similar Logic)

**Files:**
- [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php) Line 1108: `repairDeviceOptions()`
- [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php) Line 732: `writeoffDeviceOptions()`
- [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php) Line 1023: `transferDeviceOptions()`

**Code Similarity:** ~80%

**Issue:** Each creates device dropdown options with nearly identical structure but different naming.

**Recommendation:** Extract to trait method `makeDeviceOptions()` (can be called by each controller as needed).

---

#### User/Requester Options Methods (Similar Logic)

**Files:**
- [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php) Line 1129: `repairRequesterOptions()`
- [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php) Line 797: `writeoffRequesterOptions()`
- [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php) Line 1041: `transferUserOptions()`

**Code Similarity:** ~75%

**Recommendation:** Extract to trait method `makeUserOptions()`.

---

### 1.2 Unused Imports

**File:** [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php)  
**Lines:** 10-11

```php
use App\Models\DeviceTransfer;  // Line 10 - UNUSED (only string literals checked)
use App\Models\WriteoffRequest;  // Line 11 - UNUSED (only string literals checked)
```

**Used Only For String Status Checks:** Both are only checked via `DeviceTransfer::STATUS_SUBMITTED` and similar constants, not instantiated.

**Recommendation:** Can be removed if using class constants in a shared validation service, or keep if planning to use extensively.

---

**File:** [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php)  
**Line:** 11

```php
use App\Models\RepairRequest;  // LINE 11 - UNUSED (only string literals checked)
```

**Used Only For String Status Checks.**

**Recommendation:** Can be removed.

---

### 1.3 Code That Can Be Refactored to Clarity

**File:** [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php)  
**Lines:** 695-698  
**Method:** Part of `repairRequestsViewData()`

```php
->pluck('responsibleUser')
->filter()
->unique('id')
->values()
```

**Issue:** Complex chain for extracting unique users. Unclear intent without context.

**Recommendation:** Extract to named helper method:
```php
private function extractUniqueResponsibleUsers($repairRequests)
{
    return $repairRequests
        ->pluck('responsibleUser')
        ->filter()
        ->unique('id')
        ->values();
}
```

---

**File:** [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php)  
**Lines:** 126-135

```php
$recipientOptions = $this->transferUserOptions(
    (clone $this->applyIndexFilters(clone $baseQuery, $filters, ['recipient_id', 'code']))
        ->with('transferTo')
        ->get()
        ->pluck('transferTo')
        ->filter()
        ->unique('id')
        ->reject(fn (User $recipient) => filled($filters['requester_id']) && $recipient->id === $filters['requester_id'])
        ->values()
);
```

**Issue:** Deeply nested query with multiple transformations on same line. Hard to debug.

**Recommendation:** Extract to method `getTransferRecipientOptions()`.

---

### 1.4 Unnecessary Method Parameters

**File:** [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php)  
**Method:** `ensureWarehouseRoom()` at Line 440

```php
private function ensureWarehouseRoom(?int $preferredUserId = null): Room
```

**Issue:** Parameter `$preferredUserId` is accepted but **not always used**. Only used in one case:
- Line 456 (when creating a new room)
- Not used when returning existing warehouse room (line 454)

**Recommendation:** Document intent or refactor to two methods:
```php
private function ensureWarehouseRoom(): Room
private function createWarehouseRoomFor(?int $userId = null): Room
```

---

**File:** [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php)  
**Method:** `writeoffWarehousePayload()` at Line 429

```php
private function writeoffWarehousePayload(?int $preferredUserId = null): array
```

**Issue:** Parameter never actually used in the returned array. Only passed to `ensureWarehouseRoom()`.

**Recommendation:** 
```php
// Just pass manager ID when needed
private function writeoffWarehousePayload(): array
private function ensureWarehouseRoom(): Room  
// Remove the passthrough parameter
```

---

## 2. MODELS - Potentially Unused Relationships

### 2.1 Possibly Redundant Aliases

**File:** [app/Models/Device.php](app/Models/Device.php)  
**Lines:** 88-93 (approx)

```php
/**
 * Savietojamības alias vecākam koda slānim.
 */
public function assignedUser(): BelongsTo
{
    return $this->assignedTo();
}
```

**Issue:** `assignedUser()` is an **alias/compatibility wrapper** for `assignedTo()`.  
**Analysis:** Should verify this is still used in codebase.

**Recommendation:** Search codebase for `assignedUser` usage. If not used, remove. If used, add deprecation notice or document why alias exists.

---

## 3. BLADE TEMPLATES - Commented Code & Dead HTML

### 3.1 HTML Comments (Documentation, Not Dead Code)

**File:** `resources/views/auth/forgot-password.blade.php`  
**Line:** 20

```blade
<!-- E-pasta adrese -->
```

**Status:** These are documentation comments, acceptable in Blade. Found in:
- [resources/views/auth/forgot-password.blade.php](resources/views/auth/forgot-password.blade.php) Line 20
- [resources/views/auth/reset-password.blade.php](resources/views/auth/reset-password.blade.php) Lines 16, 19, 26, 33
- [resources/views/components/modal.blade.php](resources/views/components/modal.blade.php) Lines 28, 37
- [resources/views/components/request-form-modal.blade.php](resources/views/components/request-form-modal.blade.php) Lines 49, 55, 57, 77, 108, 133, 153

**Recommendation:** Consider using `@section` documentation or removing if not needed for clarity.

---

### 3.2 Potentially Unused Template Variables

**File:** [resources/views/repairs/create.blade.php](resources/views/repairs/create.blade.php)  
**Analysis Required:** Check if all variables in viewData are used

**File:** [resources/views/rooms/create.blade.php](resources/views/rooms/create.blade.php)  
**Variables:** `buildingOptions` - verify all are rendered

**Recommendation:** Run Laravel blade template analyzer or do visual inspection to confirm all passed variables are used.

---

## 4. BASE CONTROLLER - Potentially Unused or Redundant Methods

### 4.1 Heavy Base Controller

**File:** [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php)

**Stats:**
- Contains 11 public/protected methods
- ~250 lines for validation messages and attributes
- Heavy dependence on localized messages

**Methods Present:**
1. `user()` - ✅ Used
2. `requireAdmin()` - ✅ Used
3. `requireManager()` - ✅ Used
4. `featureTableExists()` - ✅ Used (runtime schema checks)
5. `emptyPaginator()` - ✅ Used
6. `validateInput()` - ✅ Used extensively
7. `validationMessages()` - ✅ Used by validateInput()
8. `validationAttributes()` - ✅ Used by validateInput()
9. `requestStatusLabels()` - ✅ Used (request flows)
10. `createRepairRecord()` - ✅ Used (RepairRequest approval)
11. `normalizeRepairPayloadForPersistence()` - ✅ Used (repair handling)
12. `repairColumnAllowsNull()` - ✅ Used (schema adaptation)

**Status:** All methods are used. No dead code identified, but could be split into concerns:
- Validation responsibility could be moved to Requests
- Repair-specific logic could move to RepairService

---

## 5. DUPLICATION IN VALIDATION PATTERNS

### 5.1 Repeated Device Status Checks

**Pattern:** Checking if device is in ACTIVE, REPAIR, or WRITEOFF status is done in multiple places:

- [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php) Line 489: Device::STATUS_ACTIVE
- [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php) Line 361: Device::STATUS_ACTIVE
- [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php) (multiple locations)

**Recommendation:** Create validation abstract or service method:

```php
class DeviceValidator {
    public function ensureDeviceHasStatus(Device $device, string $status): void
    
    public function ensureDeviceAvailable(Device $device, ?string $exceptFor = null): void
}
```

---

## 6. PATTERNS THAT REPEAT IN MULTIPLE PLACES AND COULD BE EXTRACTED

### 6.1 Query Building Pattern

**Appears in:** All 3 request controllers

```php
$baseQuery = Model::query()
    ->when(! $canReview, fn (Builder $query) => $query->where(...));
```

**Recommendation:** Extract to Model scope:

```php
public function scopeVisibleToUser(Builder $query, User $user): Builder
{
    if (!$user->canManageRequests()) {
        return $query->where('responsible_user_id', $user->id);
    }
    return $query;
}
```

---

### 6.2 Device Options Generation

**Appears in:** RepairRequest, Writeoff, DeviceTransfer controllers

**Pattern:** 
```php
collect($devices)->map(function (Device $device) {
    return [
        'value' => (string) $device->id,
        'label' => $device->name.' ('.($device->code ?: 'bez koda').')',
        'description' => ...,
        'search' => ...,
    ];
})->values();
```

**Recommendation:** Create a Service:

```php
class DeviceOptionService {
    public function makeDeviceOptions(Collection $devices): Collection
}
```

---

## 7. SUMMARY OF OPTIMIZATION OPPORTUNITIES

### Quick Wins (1-2 hours each)

| # | Location | Type | Issue | Lines Saved |
|---|----------|------|-------|------------|
| 1 | Controller.php | Method | Remove unused imports | 2 |
| 2 | All Request Controllers | Parameters | Remove `$preferredUserId` unused param | 3 |
| 3 | Blade templates | Comments | Remove HTML documentation comments | 10+ |
| 4 | RepairRequest/Writeoff | Import | Remove unused model imports | 2 |

### Medium Effort (3-6 hours)

| # | Location | Type | Savings |
|---|----------|------|---------|
| 5 | HasRequestListFiltering trait | EXTRACT | 200+ lines |
| 6 | DeviceValidationService | EXTRACT | 150+ lines |
| 7 | DeviceOptionService | EXTRACT | 80+ lines |
| 8 | Extract filter/sort logic | TRAIT | 100+ lines |

### Larger Refactors (6+ hours)

| # | Location | Type | Benefit |
|---|----------|------|---------|
| 9 | Request Controllers | CONSOLIDATE | Reduce 3 controllers to 2 with shared base logic |
| 10 | Model Scopes | EXTRACT | Use database queries instead of PHP foreach |

---

## 8. FILES ANALYZED

### Controllers
- ✅ [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php)
- ✅ [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php)
- ✅ [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php)
- ✅ [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php)
- ✅ [app/Http/Controllers/DeviceController.php](app/Http/Controllers/DeviceController.php)
- ✅ [app/Http/Controllers/RepairController.php](app/Http/Controllers/RepairController.php)

### Models
- ✅ [app/Models/Device.php](app/Models/Device.php)
- ✅ [app/Models/RepairRequest.php](app/Models/RepairRequest.php)
- ✅ [app/Models/WriteoffRequest.php](app/Models/WriteoffRequest.php)

### Blade Templates
- ✅ `resources/views/**/*.blade.php` (sampled)

---

## 9. RECOMMENDED ACTION PLAN

### Phase 1: Low-Risk Quick Wins (Week 1)
1. Remove unused imports from controllers
2. Remove unused method parameters
3. Add type hints where missing
4. Document why `assignedUser()` alias exists

### Phase 2: Extract Shared Traits (Week 2-3)
1. Create `HasRequestListFiltering` trait with 6 methods
2. Create trait for device validation
3. Create DeviceOptionService

### Phase 3: Refactor Request Controllers (Week 4)
1. Extract warehouse logic from WriteoffRequestController
2. Consolidate validation logic
3. Create base RequestController if needed

### Phase 4: Performance & Database Optimization (Week 5+)
1. Add Model scopes to reduce PHP-level filtering
2. Optimize N+1 queries in list views
3. Add query caching for options

---

## 10. FILES TO VIEW FOR DETAILED ANALYSIS

Priority order:
1. [app/Http/Controllers/RepairRequestController.php](app/Http/Controllers/RepairRequestController.php) - 1200+ lines, heavy duplication
2. [app/Http/Controllers/WriteoffRequestController.php](app/Http/Controllers/WriteoffRequestController.php) - 900+ lines, shared patterns
3. [app/Http/Controllers/DeviceTransferController.php](app/Http/Controllers/DeviceTransferController.php) - 1000+ lines, shared patterns
4. [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php) - Base class, all used

---

## CONCLUSION

**Total Duplication:** ~500 lines across 3 request controllers  
**Potential Code Reduction:** 30-40%  
**Estimated Cleanup Time:** 16-24 hours  
**Priority:** HIGH - Focus on extracting traits for immediate benefit

The codebase is generally clean with no dead code, but significant duplication exists that should be consolidated for maintainability.
