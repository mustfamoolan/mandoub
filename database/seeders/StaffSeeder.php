<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Staff::count() === 0) {
            Staff::create([
                'name' => 'المدير العام',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'phone' => '07700000000',
                'role' => 'admin',
            ]);

            Staff::create([
                'name' => 'علي حسن (موظف استقبال)',
                'username' => 'ali',
                'password' => Hash::make('ali123'),
                'phone' => '07800000001',
                'role' => 'employee',
            ]);
        }
    }
}
