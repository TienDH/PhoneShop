<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\GHNService;
use App\Services\CartService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(protected GHNService $ghn) {}

    // -------------------------------------------------------------------------
    // GET /locations/provinces
    // -------------------------------------------------------------------------

    public function provinces(): JsonResponse
    {
        $data = $this->ghn->getProvinces();

        // Sắp xếp theo tên tỉnh
        usort($data, fn($a, $b) => strcmp($a['ProvinceName'] ?? '', $b['ProvinceName'] ?? ''));

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /locations/districts/{provinceId}
    // -------------------------------------------------------------------------

    public function districts(int $provinceId): JsonResponse
    {
        if ($provinceId <= 0) {
            return response()->json(['success' => false, 'message' => 'Province ID không hợp lệ.'], 422);
        }

        $data = $this->ghn->getDistricts($provinceId);

        usort($data, fn($a, $b) => strcmp($a['DistrictName'] ?? '', $b['DistrictName'] ?? ''));

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /locations/wards/{districtId}
    // -------------------------------------------------------------------------

    public function wards(int $districtId): JsonResponse
    {
        if ($districtId <= 0) {
            return response()->json(['success' => false, 'message' => 'District ID không hợp lệ.'], 422);
        }

        $data = $this->ghn->getWards($districtId);

        usort($data, fn($a, $b) => strcmp($a['WardName'] ?? '', $b['WardName'] ?? ''));

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /locations/calculate-fee
    // Body: { district_id, ward_code, subtotal }
    // Lấy trọng lượng từ giỏ hàng session hiện tại
    // -------------------------------------------------------------------------

    public function calculateFee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => 'required|integer|min:1',
            'ward_code'   => 'required|string',
            'selected_keys' => 'nullable|string|max:5000',
        ]);

        // Tính tổng trọng lượng từ giỏ hàng session
        $cart           = app(CartService::class)->refresh(session()->get('cart', []));
        $selectedKeys   = $request->input('selected_keys', []);

        // Nếu có selected_keys thì chỉ tính những item được chọn
        if (! empty($selectedKeys)) {
            if (is_string($selectedKeys)) {
                $selectedKeys = array_filter(explode(',', $selectedKeys));
            }
            $items = array_intersect_key($cart, array_flip($selectedKeys));
        } else {
            $items = $cart;
        }

        $defaultWeight = (int) config('ghn.default_weight', 200); // gram/sản phẩm

        $totalWeight = 0;
        foreach ($items as $item) {
            $itemWeight  = isset($item['weight']) ? (int) $item['weight'] : $defaultWeight;
            $totalWeight += $itemWeight * (int) ($item['quantity'] ?? 1);
        }

        // GHN yêu cầu tối thiểu 1g
        if ($totalWeight <= 0) {
            $totalWeight = $defaultWeight;
        }

        $subtotal = array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items));
        $fee = app(ShippingService::class)->quote($items, $subtotal, (int) $validated['district_id'], $validated['ward_code']);
        return response()->json([
            'success' => true, 'fee' => $fee, 'weight' => $totalWeight,
        ]);
    }
}
