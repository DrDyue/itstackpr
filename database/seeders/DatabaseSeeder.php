<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\Building;
use App\Models\DeviceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test employees
        $emp1 = Employee::create([
            'full_name' => 'John Administrator',
            'email' => 'admin@example.com',
            'phone' => '+371 29123456',
            'job_title' => 'IT Administrator',
            'is_active' => true,
        ]);

        $emp2 = Employee::create([
            'full_name' => 'Jane Technician',
            'email' => 'technician@example.com',
            'phone' => '+371 29654321',
            'job_title' => 'IT Technician',
            'is_active' => true,
        ]);

        $emp3 = Employee::create([
            'full_name' => 'Bob User',
            'email' => 'user@example.com',
            'phone' => '+371 29999999',
            'job_title' => 'Regular User',
            'is_active' => true,
        ]);

        // Create test users
        User::create([
            'employee_id' => $emp1->id,
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'employee_id' => $emp2->id,
            'password' => bcrypt('password'),
            'role' => 'technician',
            'is_active' => true,
        ]);

        User::create([
            'employee_id' => $emp3->id,
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create test buildings
        Building::create([
            'building_name' => 'Main Office',
            'address' => 'Brīvības iela 123',
            'city' => 'Rīga',
            'total_floors' => 5,
            'notes' => 'Central building',
        ]);

        // Create test device types
        DeviceType::create([
            'type_name' => 'Laptop',
            'category' => 'Computers',
            'icon_name' => 'laptop.png',
            'description' => 'Portable computers',
            'expected_lifetime_years' => 4,
        ]);

        DeviceType::create([
            'type_name' => 'Desktop',
            'category' => 'Computers',
            'icon_name' => 'desktop.png',
            'description' => 'Stationary computers',
            'expected_lifetime_years' => 5,
        ]);

        DeviceType::create([
            'type_name' => 'Monitor',
            'category' => 'Peripherals',
            'icon_name' => 'monitor.png',
            'description' => 'Display monitors',
            'expected_lifetime_years' => 6,
        ]);

        DeviceType::create([
            'type_name' => 'Printer',
            'category' => 'Peripherals',
            'icon_name' => 'printer.png',
            'description' => 'Printing devices',
            'expected_lifetime_years' => 5,
        ]);
    }
}
