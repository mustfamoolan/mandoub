<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MerchantController extends Controller
{
    /**
     * Display a listing of merchants.
     */
    public function index(Request $request)
    {
        $query = Merchant::query();

        // Instant Search (store_name, owner_name, username, phone)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('id', 'asc');

        $perPage = (int) $request->input('per_page', 10);
        $merchants = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $merchants->items(),
            'pagination' => [
                'total' => $merchants->total(),
                'current_page' => $merchants->currentPage(),
                'per_page' => $merchants->perPage(),
                'last_page' => $merchants->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created merchant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:merchants,username',
            'password' => 'required|string|min:4',
            'phone' => 'nullable|string|max:50',
            'store_image' => 'nullable|string',
            'status' => 'required|in:active,closed,pending,banned',
        ]);

        $merchant = Merchant::create([
            'store_name' => $validated['store_name'],
            'owner_name' => $validated['owner_name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'store_image' => $validated['store_image'] ?? null,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التاجر بنجاح',
            'data' => $merchant,
        ], 201);
    }

    /**
     * Display the specified merchant.
     */
    public function show($id)
    {
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $merchant,
        ]);
    }

    /**
     * Update the specified merchant in storage.
     */
    public function update(Request $request, $id)
    {
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:merchants,username,' . $id,
            'password' => 'nullable|string|min:4',
            'phone' => 'nullable|string|max:50',
            'store_image' => 'nullable|string',
            'status' => 'nullable|in:active,closed,pending,banned',
        ]);

        $newStatus = $validated['status'] ?? $merchant->status;

        $data = [
            'store_name' => $validated['store_name'],
            'owner_name' => $validated['owner_name'],
            'username' => $validated['username'],
            'phone' => $validated['phone'] ?? null,
            'store_image' => array_key_exists('store_image', $validated) ? $validated['store_image'] : $merchant->store_image,
            'status' => $newStatus,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $oldStatus = $merchant->status;
        $merchant->update($data);

        if ($oldStatus !== $merchant->status) {
            $this->notifyMerchantStatusChange($merchant, $merchant->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات التاجر بنجاح',
            'data' => $merchant->fresh(),
        ]);
    }

    /**
     * Remove the specified merchant from storage.
     */
    public function destroy($id)
    {
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $merchant->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التاجر بنجاح',
        ]);
    }

    /**
     * Merchant Login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $merchant = Merchant::where('username', $request->input('username'))->first();

        if (!$merchant || !Hash::check($request->input('password'), $merchant->password)) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401);
        }

        if ($merchant->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب محظور، يرجى مراجعة الإدارة',
            ], 403);
        }

        if ($request->filled('fcm_token')) {
            $merchant->update(['fcm_token' => $request->input('fcm_token')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => $merchant,
        ]);
    }

    /**
     * Update merchant status (active <-> closed).
     */
    public function updateStatus(Request $request, $id)
    {
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,closed,pending,banned',
        ]);

        $oldStatus = $merchant->status;
        $merchant->update(['status' => $validated['status']]);

        if ($oldStatus !== $validated['status']) {
            $this->notifyMerchantStatusChange($merchant, $validated['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة المتجر بنجاح',
            'data' => $merchant,
        ]);
    }

    /**
     * Update FCM Token for merchant.
     */
    public function updateFcmToken(Request $request, $id)
    {
        $merchant = Merchant::find($id);

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $merchant->update(['fcm_token' => $validated['fcm_token']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رمز FCM بنجاح',
            'data' => $merchant,
        ]);
    }

    /**
     * Send Push Notification on merchant status change.
     */
    private function notifyMerchantStatusChange(Merchant $merchant, string $newStatus)
    {
        if (empty($merchant->fcm_token)) {
            return;
        }

        $title = 'تحديث حالة المتجر';
        $body = '';

        switch ($newStatus) {
            case 'active':
                $title = 'تنشيط الحساب';
                $body = 'تنبيه الإدارة: تم تفعيل وتنشيط متجرك الآن بنجاح.';
                break;

            case 'closed':
                $title = 'إغلاق المتجر';
                $body = 'تنبيه الإدارة: تم تغيير حالة متجرك إلى مغلق مؤقتاً.';
                break;

            case 'pending':
                $title = 'تعليق الحساب';
                $body = 'تنبيه الإدارة: حساب متجرك قيد الانتظار والمراجعة.';
                break;

            case 'banned':
                $title = 'حظر الحساب';
                $body = 'تنبيه الإدارة: تم حظر حساب متجرك! يرجى التواصل مع الإدارة.';
                break;

            default:
                $body = "تنبيه الإدارة: تم تحديث حالة حسابك إلى $newStatus.";
                break;
        }

        FcmService::sendNotification(
            $merchant->fcm_token,
            $title,
            $body,
            [
                'category' => 'system',
                'status' => $newStatus,
            ]
        );
    }
}

