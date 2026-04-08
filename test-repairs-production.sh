#!/usr/bin/env bash

# 🧪 Repairs Page Testing Script
# Run this on the production server to verify all fixes work

echo "=== REPAIRS PAGE PRODUCTION TEST SUITE ==="
echo ""

# Test 1: Verify repairs.show route exists
echo "TEST 1: Checking if repairs.show route is registered..."
php artisan route:list | grep "repairs.show" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ repairs.show route is registered"
else
    echo "❌ repairs.show route NOT FOUND"
    exit 1
fi

# Test 2: Check Blade template syntax
echo ""
echo "TEST 2: Checking repairs view template syntax..."
php artisan view:cache 2>&1 | grep -i "error\|exception" > /dev/null
if [ $? -ne 0 ]; then
    echo "✅ Blade template compiles successfully"
else
    echo "❌ Blade template has syntax errors"
    exit 1
fi

# Test 3: Verify controller method exists
echo ""
echo "TEST 3: Checking RepairController::show() method..."
grep -q "public function show" app/Http/Controllers/RepairController.php
if [ $? -eq 0 ]; then
    echo "✅ show() method exists in RepairController"
else
    echo "❌ show() method NOT FOUND"
    exit 1
fi

# Test 4: Verify Repair model binding
echo ""
echo "TEST 4: Checking Repair model table binding..."
grep -q "protected \$table = 'repairs'" app/Models/Repair.php
if [ $? -eq 0 ]; then
    echo "✅ Repair model correctly bound to 'repairs' table"
else
    echo "❌ Repair model table binding incorrect"
    exit 1
fi

# Test 5: Clear Laravel caches for production
echo ""
echo "TEST 5: Clearing Laravel caches..."
php artisan cache:clear
php artisan view:clear
php artisan config:clear
echo "✅ Caches cleared"

# Test 6: Verify database has repairs table
echo ""
echo "TEST 6: Checking if repairs table exists in database..."
php artisan tinker --execute "
echo Schema::hasTable('repairs') ? '✅ repairs table exists' : '❌ repairs table NOT FOUND';
" 2>/dev/null

echo ""
echo "=== MANUAL VERIFICATION STEPS ==="
echo ""
echo "1. Open browser to: https://itstack.gt.tc/repairs"
echo "2. Verify:"
echo "   ✓ Page loads without errors"
echo "   ✓ Repairs table displays"
echo "   ✓ 'Ātrais skats' button works"
echo "   ✓ Filters/search functional"
echo ""
echo "=== TEST COMPLETE ==="
