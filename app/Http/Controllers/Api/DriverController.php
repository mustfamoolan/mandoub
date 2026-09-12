<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers.
     */
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $drivers = $query->withCount('orders')->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    /**
     * Store a newly created driver in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'username' => 'required|string|max:255|unique:drivers,username',
            'password' => 'required|string|min:4',
            'avatar' => 'nullable|string',
            'rating' => 'nullable|numeric|between:0,5',
            'status' => 'nullable|in:active,inactive,busy',
        ]);

        $driver = Driver::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
            'rating' => $validated['rating'] ?? 5.00,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة سائق التوصيل بنجاح',
            'data' => $driver,
        ], 201);
    }

    /**
     * Display the specified driver.
     */
    public function show($id)
    {
        $driver = Driver::with(['orders' => function ($q) {
            $q->with('merchant')->orderBy('id', 'desc');
        }])->withCount('orders')->find($id);

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'السائق غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $driver,
        ]);
    }

    /**
     * Update the specified driver in storage.
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'السائق غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'username' => 'required|string|max:255|unique:drivers,username,' . $id,
            'password' => 'nullable|string|min:4',
            'avatar' => 'nullable|string',
            'rating' => 'nullable|numeric|between:0,5',
            'status' => 'nullable|in:active,inactive,busy',
        ]);

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'username' => $validated['username'],
            'avatar' => array_key_exists('avatar', $validated) ? $validated['avatar'] : $driver->avatar,
            'rating' => $validated['rating'] ?? $driver->rating,
            'status' => $validated['status'] ?? $driver->status,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $driver->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات السائق بنجاح',
            'data' => $driver->fresh(),
        ]);
    }

    /**
     * Remove the specified driver from storage.
     */
    public function destroy($id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'السائق غير موجود',
            ], 404);
        }

        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف السائق بنجاح',
        ]);
    }

    /**
     * Driver Login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $driver = Driver::where('username', $request->input('username'))->first();

        if (!$driver || !Hash::check($request->input('password'), $driver->password)) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401);
        }

        if ($driver->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'حساب السائق غير نشط حالياً، يرجى مراجعة الإدارة',
            ], 403);
        }

        if ($request->filled('fcm_token')) {
            $driver->update(['fcm_token' => $request->input('fcm_token')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل دخول السائق بنجاح',
            'data' => $driver,
        ]);
    }
}
