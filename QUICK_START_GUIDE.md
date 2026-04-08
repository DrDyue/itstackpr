# Quick Reference: Top 10 Actionable Items

## Priority 1️⃣: Controller Refactoring (Week 1-2)

```bash
# 1. Create Form Requests
php artisan make:request StoreDeviceRequest
php artisan make:request UpdateDeviceRequest
php artisan make:request AssignDeviceRequest

# 2. Extract to Actions
mkdir -p app/Actions/Device
# Then create files like CreateDeviceAction.php

# 3. Extract to Filters
mkdir -p app/Filters
# Create DeviceIndexFilter.php for query building
```

**DeviceController reduction:** 1,688 → ~300 lines

---

## Priority 2️⃣: Database Indexes (Day 1)

```php
// Create new migration
php artisan make:migration add_indexes_to_devices_table --table=devices

// In migration up():
Schema::table('devices', function (Blueprint $table) {
    $table->index('code');
    $table->index('status');
    $table->index('device_type_id');
    $table->index('assigned_to_id');
    $table->index('created_at');
    $table->index(['building_id', 'room_id']);
});

Schema::table('audit_log', function (Blueprint $table) {
    $table->index('user_id');
    $table->index('created_at');
});

// Run: php artisan migrate
```

**Impact:** 50-200% faster queries on filtered/sorted lists

---

## Priority 3️⃣: Authorization Policies (Day 2-3)

```bash
# Generate policies
php artisan make:policy DevicePolicy --model=Device
php artisan make:policy RepairPolicy --model=Repair
php artisan make:policy UserPolicy --model=User
```

```php
// app/Policies/DevicePolicy.php
class DevicePolicy {
    public function create(User $user): bool {
        return $user->isAdmin();
    }
    
    public function update(User $user, Device $device): bool {
        return $user->isAdmin();
    }
    
    public function delete(User $user, Device $device): bool {
        return $user->canManageRequests() && $user->id !== auth()->id();
    }
}

// In controller:
$this->authorize('update', $device);
$device->update($validated);
```

---

## Priority 4️⃣: Containerization (Day 4-5)

```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine
RUN docker-php-ext-install pdo pdo_mysql bcmath
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app
COPY . .
RUN composer install --no-dev
RUN chown -R www-data:www-data /app/storage
EXPOSE 9000
CMD ["php-fpm"]
```

```yaml
# docker-compose.yml (simplified)
version: '3.9'
services:
  app:
    build: .
    container_name: itstackpr-app
    volumes:
      - ./:/app
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: itstackpr
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
```

```bash
docker-compose up -d
```

---

## Priority 5️⃣: GitHub Actions CI/CD (Day 6)

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: php artisan migrate --env=testing
      - run: php artisan test
```

Push to main, GitHub Actions runs automatically.

---

## Priority 6️⃣: Form Requests for Validation (Week 2)

```php
// app/Http/Requests/StoreDeviceRequest.php
class StoreDeviceRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()->canManageRequests();
    }
    
    public function rules(): array {
        return [
            'code' => ['required', 'string', 'unique:devices', 'max:20'],
            'name' => ['required', 'string', 'max:200'],
            'device_type_id' => ['required', 'exists:device_types,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}

// In controller - BEFORE:
public function store(Request $request) {
    $validated = $request->validate([...]);  // 20 lines of rules
    
// IN controller - AFTER:
public function store(StoreDeviceRequest $request) {  // Validation automatic!
    $validated = $request->validated();
    Device::create($validated);
}
```

---

## Priority 7️⃣: Production Monitoring (Week 3)

```bash
# Add Sentry
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_SENTRY_DSN

# .env
SENTRY_LARAVEL_DSN=https://key@sentry.io/12345
```

```php
// Automatic error tracking
// Your app now sends errors to Sentry dashboard
```

**Benefits:**
- Real-time error alerts
- Stack traces in browser
- User tracking
- Performance monitoring

---

## Priority 8️⃣: Test Coverage (Week 4)

```php
// tests/Feature/DeviceTest.php
class DeviceTest extends TestCase {
    use RefreshDatabase;
    
    public function test_user_can_create_device(): void {
        $user = User::factory()->admin()->create();
        
        $response = $this->actingAs($user)
            ->post(route('devices.store'), [
                'code' => 'DEV001',
                'name' => 'Test Device',
                'device_type_id' => DeviceType::factory()->create()->id,
            ]);
        
        $this->assertDatabaseHas('devices', ['code' => 'DEV001']);
    }
}

// Run: php artisan test --coverage
```

**Target:** 60%+ overall coverage in 2 weeks

---

## Priority 9️⃣: Replace FTP with SSH (Week 3)

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/itstackpr
            git pull
            composer install --no-dev
            php artisan migrate --force
            php artisan cache:clear
```

**Advantages over FTP:**
- Version controlled
- Builds/tests before deploying
- Automatic rollback capability
- Zero-downtime with health checks
- Audit trail of all deployments

---

## Priority 🔟: Backup Strategy (Week 4)

```bash
# Add Laravel Backup
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

```php
// config/backup.php - set up S3 storage
'backup' => [
    'destination' => [
        'disks' => ['s3-backup'],
    ],
];

// app/console/Kernel.php
protected function schedule(Schedule $schedule): void {
    $schedule->command('backup:run')->daily()->at('02:00');
}
```

```bash
# Test backup
php artisan backup:run
# Check S3 bucket for backup file
```

---

## Quick Wins (Easy Wins - Do Today)

### ✅ 5-Minute Wins:
1. Add `php artisan config:cache` to deploy script
2. Set debug mode to false in production (.env)
3. Add security headers middleware
4. Set CORS headers properly
5. Enable query caching for DeviceType list

### ✅ 30-Minute Wins:
1. Create first FormRequest (copy LoginRequest structure)
2. Add indexes to audit_log table
3. Write first unit test for User model
4. Set up Sentry error tracking
5. Create basic Docker setup

### ✅ 2-Hour Wins:
1. Extract one large helper method into Action class
2. Create comprehensive GitHub Actions workflow
3. Refactor DeviceController validation to FormRequest
4. Write 5 critical feature tests
5. Deploy with SSH instead of FTP

---

## Metric Targets (Use for Tracking Progress)

| Metric | Current | Target | Timeline |
|--------|---------|--------|----------|
| Test Coverage | ~10% | 60% | 4 weeks |
| Largest Controller Size | 1,688 lines | <400 lines | 2 weeks |
| Database Query Time (index) | 50+ ms | <10 ms | 1 week |
| Deployment Time | Manual via FTP | Automated (5 min) | 3 weeks |
| Mean Time to Production | 30+ min | 5 min | 3 weeks |
| Error Response Time | Manual | <1 min (Sentry) | 1 week |
| Automated Tests | 4 files | 20+ files | 4 weeks |

---

## Resources & Links

- **Laravel Docs:** https://laravel.com/docs
- **Laravel Best Practices:** https://github.com/alexeymezenin/laravel-best-practices
- **Docker for Laravel:** https://www.digitalocean.com/community/tutorials/containerizing-a-laravel-application-for-development-with-docker-compose
- **GitHub Actions:** https://docs.github.com/en/actions
- **Sentry Setup:** https://docs.sentry.io/platforms/php/guides/laravel/
- **spatie/laravel-backup:** https://spatie.be/docs/laravel-backup
- **Laravel Policies:** https://laravel.com/docs/authorization#creating-policies

---

## Getting Started - Day 1 Checklist

- [ ] Add database indexes (15 min)
- [ ] Create first FormRequest (30 min)
- [ ] Set up Sentry (30 min)
- [ ] Create Docker setup (1 hour)
- [ ] Write first unit test (1 hour)

**Day 1 Effort: ~3.5 hours**

After Day 1, have automated testing and error tracking in place - massive foundation!

