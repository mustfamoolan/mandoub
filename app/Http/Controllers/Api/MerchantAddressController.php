<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantAddress;
use Illuminate\Http\Request;

class MerchantAddressController extends Controller
{
    /**
     * Display a listing of merchant addresses.
     */
    public function index($merchantId)
    {
        $merchant = Merchant::find($merchantId);
        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $addresses = MerchantAddress::where('merchant_id', $merchantId)
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    /**
     * Store a newly created merchant address.
     */
    public function store(Request $request, $merchantId)
    {
        $merchant = Merchant::find($merchantId);
        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'التاجر غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'address_name' => 'required|string|max:255',
            'address_details' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        $existingCount = MerchantAddress::where('merchant_id', $merchantId)->count();
        $isDefault = $validated['is_default'] ?? ($existingCount === 0);

        if ($isDefault) {
            MerchantAddress::where('merchant_id', $merchantId)->update(['is_default' => false]);
        }

        $address = MerchantAddress::create([
            'merchant_id' => $merchantId,
            'address_name' => $validated['address_name'],
            'address_details' => $validated['address_details'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العنوان بنجاح',
            'data' => $address,
        ], 201);
    }

    /**
     * Set a merchant address as default.
     */
    public function setDefault($merchantId, $addressId)
    {
        $address = MerchantAddress::where('merchant_id', $merchantId)->where('id', $addressId)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير موجود',
            ], 404);
        }

        MerchantAddress::where('merchant_id', $merchantId)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين العنوان كعنوان افتراضي بنجاح',
            'data' => $address,
        ]);
    }

    /**
     * Remove the specified merchant address.
     */
    public function destroy($merchantId, $addressId)
    {
        $address = MerchantAddress::where('merchant_id', $merchantId)->where('id', $addressId)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير موجود',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = MerchantAddress::where('merchant_id', $merchantId)->latest('id')->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان بنجاح',
        ]);
    }
}
