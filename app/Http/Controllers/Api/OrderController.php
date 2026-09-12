<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Create a new order (from merchant app).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'merchant_address_id' => 'nullable|exists:merchant_addresses,id',
            'merchant_address' => 'nullable|string',
            'merchant_latitude' => 'nullable|numeric',
            'merchant_longitude' => 'nullable|numeric',
            'customer_address' => 'required|string',
            'customer_latitude' => 'nullable|numeric',
            'customer_longitude' => 'nullable|numeric',
            'customer_phone' => 'required|string',
            'order_description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
        ]);

        $total = (float)$validated['total_amount'];
        $fee = (float)$validated['delivery_fee'];
        $net = max(0, $total - $fee);
        $merchantAddressStr = $validated['merchant_address'] ?? '';
        if (empty($merchantAddressStr) && !empty($validated['merchant_address_id'])) {
            $addr = \App\Models\MerchantAddress::find($validated['merchant_address_id']);
            if ($addr) {
                $merchantAddressStr = $addr->address;
                if (empty($validated['merchant_latitude'])) {
                    $validated['merchant_latitude'] = $addr->latitude;
                }
                if (empty($validated['merchant_longitude'])) {
                    $validated['merchant_longitude'] = $addr->longitude;
                }
            }
        }

        // Generate unique order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

        $order = Order::create([
            'order_number' => $orderNumber,
            'merchant_id' => $validated['merchant_id'],
            'merchant_address_id' => $validated['merchant_address_id'] ?? null,
            'merchant_address' => $merchantAddressStr,
            'merchant_latitude' => $validated['merchant_latitude'] ?? null,
            'merchant_longitude' => $validated['merchant_longitude'] ?? null,
            'customer_address' => $validated['customer_address'],
            'customer_latitude' => $validated['customer_latitude'] ?? null,
            'customer_longitude' => $validated['customer_longitude'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'order_description' => $validated['order_description'] ?? '',
            'total_amount' => $total,
            'delivery_fee' => $fee,
            'net_payout' => $net,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع طلب التوصيل بنجاح وسيتم توجيهه لأقرب مندوب',
            'data' => $order,
        ], 201);
    }

    /**
     * Get merchant's orders list (with status filter).
     */
    public function merchantOrders(Request $request, $merchantId)
    {
        $query = Order::with('driver')->where('merchant_id', $merchantId)
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get all system orders for Admin app.
     */
    public function adminOrders(Request $request)
    {
        $query = Order::with(['merchant', 'driver'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_address', 'like', "%{$search}%")
                  ->orWhereHas('merchant', function ($mq) use ($search) {
                      $mq->where('store_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('driver', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get single order details.
     */
    public function show($id)
    {
        $order = Order::with(['merchant', 'driver'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order status & dispatch FCM push notification to merchant.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,driver_assigned,in_delivery,delivered,returned,cancelled',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $order = Order::with(['merchant', 'driver'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $updateData = ['status' => $newStatus];
        if (array_key_exists('driver_id', $validated)) {
            $updateData['driver_id'] = $validated['driver_id'];
        }

        $order->update($updateData);

        // Send FCM notification to merchant if token is available
        try {
            $merchant = $order->merchant;
            if ($merchant && !empty($merchant->fcm_token)) {
                $title = 'تحديث حالة الطلب 📦';
                $body = "تم تحديث حالة الطلب رقم {$order->order_number} إلى {$order->status_arabic}.";

                switch ($newStatus) {
                    case 'driver_assigned':
                        $title = 'المندوب قبل الطلب 🛵';
                        $body = "تم قبول طلبك رقم {$order->order_number} من قِبَل المندوب وهو في الطريق لاستلام الشحنة.";
                        break;
                    case 'in_delivery':
                        $title = 'طلبك قيد التوصيل 🚚';
                        $body = "الطلب رقم {$order->order_number} خرج للتوصيل وهو في الطريق للزبون.";
                        break;
                    case 'delivered':
                        $title = 'تم تسليم الطلب بنجاح ✅';
                        $body = "تم تسليم الطلب رقم {$order->order_number} للزبون واستلام المبلغ.";
                        break;
                    case 'returned':
                        $title = 'تنبيه: طلب مسترجع 🔄';
                        $body = "تم إرجاع الطلب رقم {$order->order_number}.";
                        break;
                    case 'cancelled':
                        $title = 'تنبيه: إلغاء طلب ❌';
                        $body = "تم إلغاء الطلب رقم {$order->order_number}.";
                        break;
                }

                FcmService::sendNotification(
                    $merchant->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'order_status_updated',
                        'order_id' => (string)$order->id,
                        'order_number' => $order->order_number,
                        'status' => $newStatus,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error("FCM status update error for order {$id}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $order->fresh(['merchant', 'driver']),
        ]);
    }
}
