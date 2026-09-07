<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle staff login request.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $staff = Staff::where('username', $validated['username'])->first();

        if (!$staff || !Hash::check($validated['password'], $staff->password)) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'username' => $staff->username,
                'phone' => $staff->phone,
                'role' => $staff->role,
                'token' => 'staff_token_' . $staff->id . '_' . time(),
            ],
        ]);
    }

    /**
     * Handle staff logout request.
     */
    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
