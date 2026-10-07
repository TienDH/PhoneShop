<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GHNService
{
    protected string $baseUrl;
    protected string $token;
    protected int    $shopId;
    protected bool   $verifySSL;
    protected int    $fromDistrictId;

    public function __construct()
    {
        $this->baseUrl        = rtrim(config('ghn.base_url', 'https://dev-online-gateway.ghn.vn/shiip/public-api'), '/');
        $this->token          = config('ghn.token', '');
        $this->shopId         = (int) config('ghn.shop_id', 0);
        $this->verifySSL      = (bool) config('ghn.verify_ssl', true);
        $this->fromDistrictId = (int) config('ghn.from_district_id', 0);
    }

    // -------------------------------------------------------------------------
    // HTTP helper
    // -------------------------------------------------------------------------

    protected function http(bool $withShop = false)
    {
        $headers = [
            'Token'        => $this->token,
            'Content-Type' => 'application/json',
        ];

        if ($withShop) {
            $headers['ShopId'] = (string) $this->shopId;
        }

        return Http::withOptions(['verify' => $this->verifySSL, 'connect_timeout' => 5])
                   ->timeout(10)
                   ->withHeaders($headers);
    }

    // -------------------------------------------------------------------------
    // 1. Lấy danh sách Tỉnh/Thành phố
    // -------------------------------------------------------------------------

    public function getProvinces(): array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/master-data/province");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('[GHN] getProvinces failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return [];
        } catch (\Throwable $e) {
            Log::error('[GHN] getProvinces exception: ' . $e->getMessage());
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // 2. Lấy danh sách Quận/Huyện theo Province ID
    // -------------------------------------------------------------------------

    public function getDistricts(int $provinceId): array
    {
        try {
            $response = $this->http()->post("{$this->baseUrl}/master-data/district", [
                'province_id' => $provinceId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('[GHN] getDistricts failed', [
                'province_id' => $provinceId,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
            return [];
        } catch (\Throwable $e) {
            Log::error('[GHN] getDistricts exception: ' . $e->getMessage());
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // 3. Lấy danh sách Phường/Xã theo District ID
    // -------------------------------------------------------------------------

    public function getWards(int $districtId): array
    {
        try {
            // GHN /master-data/ward nhận cả GET (query string) lẫn POST (body JSON).
            // Code cũ: dùng POST nhưng truyền district_id qua URL query string và body rỗng
            // → GHN trả 400 vì không đọc được DistrictID trong body.
            // Sửa: dùng GET với query string (xác nhận hoạt động đúng).
            $response = $this->http()->get("{$this->baseUrl}/master-data/ward", [
                'district_id' => $districtId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            Log::warning('[GHN] getWards failed', [
                'district_id' => $districtId,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
            return [];
        } catch (\Throwable $e) {
            Log::error('[GHN] getWards exception: ' . $e->getMessage());
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // Helper: Lấy danh sách dịch vụ giao hàng
    // -------------------------------------------------------------------------

    public function getAvailableServices(int $toDistrictId): array
    {
        try {
            $response = $this->http(withShop: true)->post("{$this->baseUrl}/v2/shipping-order/available-services", [
                'shop_id'       => $this->shopId,
                'from_district' => $this->fromDistrictId,
                'to_district'   => $toDistrictId,
            ]);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // 4. Tính phí vận chuyển
    //
    //  $toDistrictId  : District ID GHN của địa chỉ người nhận
    //  $toWardCode    : Ward Code GHN của địa chỉ người nhận
    //  $weight        : tổng trọng lượng (gram)
    //  $insuranceValue: giá trị bảo hiểm (VNĐ) — giá trị đơn hàng
    //  $serviceTypeId : 2 = Standard, 5 = Express (mặc định 2)
    // -------------------------------------------------------------------------

    public function calculateFee(
        int $toDistrictId,
        string $toWardCode,
        int $weight,
        int $insuranceValue = 0,
        int $serviceTypeId = 2
    ): array {
        try {
            $services = $this->getAvailableServices($toDistrictId);
            if (empty($services)) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy dịch vụ giao hàng cho tuyến đường này.',
                ];
            }

            $serviceId = $services[0]['service_id'];
            foreach ($services as $service) {
                if (($service['service_type_id'] ?? 0) == $serviceTypeId) {
                    $serviceId = $service['service_id'];
                    break;
                }
            }

            $payload = [
                'service_id'       => $serviceId,
                'from_district_id' => $this->fromDistrictId,
                'to_district_id'   => $toDistrictId,
                'to_ward_code'     => $toWardCode,
                'weight'           => $weight,
                'insurance_value'  => $insuranceValue,
            ];

            $response = $this->http(withShop: true)
                             ->post("{$this->baseUrl}/v2/shipping-order/fee", $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['data']['total'])) {
                return [
                    'success' => true,
                    'fee'     => (int) $data['data']['total'],
                    'data'    => $data['data'],
                ];
            }

            $message = $data['message'] ?? 'Không thể tính phí vận chuyển.';

            Log::warning('[GHN] calculateFee failed', [
                'payload' => $payload,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::error('[GHN] calculateFee exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi kết nối đến GHN.',
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Helper: kiểm tra cấu hình hợp lệ
    // -------------------------------------------------------------------------

    public function isConfigured(): bool
    {
        return ! empty($this->token) && $this->shopId > 0 && $this->fromDistrictId > 0;
    }
}
