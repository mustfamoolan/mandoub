<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request)
    {
        $query = Staff::query();

        // Search filter (name, username, phone)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Ordering
        $query->orderBy('id', 'asc');

        $perPage = (int) $request->input('per_page', 10);
        $staff = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $staff->items(),
            'pagination' => [
                'total' => $staff->total(),
                'current_page' => $staff->currentPage(),
                'per_page' => $staff->perPage(),
                'last_page' => $staff->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:staff,username',
            'password' => 'required|string|min:4',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,employee',
        ]);

        $staff = Staff::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الموظف بنجاح',
            'data' => $staff,
        ], 201);
    }

    /**
     * Display the specified staff member.
     */
    public function show($id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, $id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:staff,username,' . $id,
            'password' => 'nullable|string|min:4',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,employee',
        ]);

        $data = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $staff->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الموظف بنجاح',
            'data' => $staff,
        ]);
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy($id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف غير موجود',
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموظف بنجاح',
        ]);
    }
}
