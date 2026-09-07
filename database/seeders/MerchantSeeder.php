<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Merchant::count() === 0) {
            Merchant::create([
                'store_name' => 'متجر البغدادي للتسوق',
                'owner_name' => 'محمد الفتلاوي',
                'username' => 'merchant_baghdad',
                'password' => Hash::make('password123'),
                'phone' => '07712345678',
                'status' => 'active',
            ]);

            Merchant::create([
                'store_name' => 'سوبرماركت الأمل',
                'owner_name' => 'علي حسن الساعدي',
                'username' => 'merchant_amal',
                'password' => Hash::make('password123'),
                'phone' => '07801122334',
                'status' => 'active',
            ]);

            Merchant::create([
                'store_name' => 'معرض دجلة للإلكترونيات',
                'owner_name' => 'عمر العبيدي',
                'username' => 'merchant_dijla',
                'password' => Hash::make('password123'),
                'phone' => '07705566778',
                'status' => 'pending',
            ]);

            Merchant::create([
                'store_name' => 'محل الفرات للملابس',
                'owner_name' => 'حسين الكعبي',
                'username' => 'merchant_furat',
                'password' => Hash::make('password123'),
                'phone' => '07819988776',
                'status' => 'closed',
            ]);
        }
    }
}
