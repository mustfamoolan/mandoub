<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Merchant;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppSettingController extends Controller
{
    /**
     * Get active app settings (or create default initial record).
     */
    public function index()
    {
        $settings = AppSetting::first();

        if (!$settings) {
            $settings = AppSetting::create([
                'delivery_fee' => 5000.00,
                'about_us' => 'تطبيق مندوب - المنصة المتكاملة لخدمة التوصيل السريع وإدارة الطلبات بين التاجر والمندوب بحرفية عالية.',
                'terms_and_conditions' => 'الشروط والأحكام لاستخدام تطبيق مندوب: الالتزام بدقة معلومات المواد والتوصيل، والالتزام بالسداد عند التصفية المالية.',
                'privacy_policy' => 'سياسة الخصوصية: نحن نحترم خصوصية بيانات المتجر والعملاء ونحافظ على سرية المعاملات المالية والموقع الجغرافي.',
                'support_phone' => '07700000000',
                'whatsapp_phone' => '9647700000000',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update app settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'delivery_fee' => 'nullable|numeric|min:0',
            'about_us' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
            'support_phone' => 'nullable|string|max:50',
            'whatsapp_phone' => 'nullable|string|max:50',
        ]);

        $settings = AppSetting::first();
        if (!$settings) {
            $settings = AppSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        // Notify all merchants via FCM push notification when app settings/delivery fee is updated
        try {
            $merchants = Merchant::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();

            $title = 'تحديث إعدادات التطبيق ⚙️';
            $body = 'تم تحديث إعدادات وبيانات التطبيق من قِبَل الإدارة.';
            $keys = array_keys($validated);

            if (in_array('delivery_fee', $keys) && !in_array('about_us', $keys) && !in_array('terms_and_conditions', $keys) && !in_array('privacy_policy', $keys)) {
                $deliveryFeeFormatted = number_format((float)$settings->delivery_fee, 0);
                $title = 'تحديث سعر التوصيل 🛵';
                $body = "تم تحديث سعر التوصيل الافتراضي في التطبيق إلى {$deliveryFeeFormatted} د.ع.";
            } elseif (in_array('about_us', $keys) && !in_array('delivery_fee', $keys)) {
                $title = 'تحديث معلومات التطبيق ℹ️';
                $body = "تم تحديث نص ومعلومات 'عن التطبيق' من قِبَل الإدارة.";
            } elseif (in_array('terms_and_conditions', $keys) && !in_array('delivery_fee', $keys)) {
                $title = 'تحديث الشروط والأحكام 📜';
                $body = 'تم تحديث الشروط والأحكام الرسمية لاستخدام الخدمة.';
            } elseif (in_array('privacy_policy', $keys) && !in_array('delivery_fee', $keys)) {
                $title = 'تحديث سياسة الخصوصية 🛡️';
                $body = 'تم تحديث سياسة الخصوصية وحماية البيانات الخاصة بالمنصة.';
            } elseif ((in_array('support_phone', $keys) || in_array('whatsapp_phone', $keys)) && !in_array('delivery_fee', $keys)) {
                $title = 'تحديث بيانات التواصل والدعم 📞';
                $body = 'تم تحديث أرقام التواصل والدعم الفني المباشر للتجار.';
            }

            foreach ($merchants as $merchant) {
                FcmService::sendNotification(
                    $merchant->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'app_settings_updated',
                        'delivery_fee' => (string)$settings->delivery_fee,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error("FCM notification error on app settings update: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات وبيانات التطبيق بنجاح',
            'data' => $settings,
        ]);
    }
}
