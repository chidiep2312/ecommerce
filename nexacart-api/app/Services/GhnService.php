<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GhnService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $baseUrl = config('services.ghn.base_url');
        $token = config('services.ghn.token');

        if (!$baseUrl) {
            throw new RuntimeException(
                'GHN_BASE_URL chưa được cấu hình.',
            );
        }

        if (!$token) {
            throw new RuntimeException(
                'GHN_TOKEN chưa được cấu hình.',
            );
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function getProvinces(): array
    {
        $response = Http::withHeaders([
            'Token' => $this->token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->get(
                $this->baseUrl
                . '/shiip/public-api/master-data/province',
            );

        $response->throw();

        return $response->json('data') ?? [];
    }

    public function getDistricts(int $provinceId): array
    {
        $response = Http::withHeaders([
            'Token' => $this->token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/master-data/district',
                [
                    'province_id' => $provinceId,
                ],
            );

        $response->throw();

        return $response->json('data') ?? [];
    }

    public function getWards(int $districtId): array
    {
        $response = Http::withHeaders([
            'Token' => $this->token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/master-data/ward',
                [
                    'district_id' => $districtId,
                ],
            );

        $response->throw();

        return $response->json('data') ?? [];
    }

    public function getAvailableServices(
        int $shopId,
        int $fromDistrictId,
        int $toDistrictId,
    ): array {
        $response = Http::withHeaders([
            'Token' => $this->token,
        ])
            ->acceptJson()
            ->timeout(10)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/v2/shipping-order/available-services',
                [
                    'shop_id' => $shopId,
                    'from_district' => $fromDistrictId,
                    'to_district' => $toDistrictId,
                ],
            );

        $response->throw();

        return $response->json('data') ?? [];
    }

    public function calculateFee(
        int $shopId,
        int $fromDistrictId,
        string $fromWardCode,
        int $toDistrictId,
        string $toWardCode,
        int $serviceId,
        int $weight,
        int $length,
        int $width,
        int $height,
    ): array {
        $response = Http::withHeaders([
            'Token' => $this->token,
            'ShopId' => (string) $shopId,
        ])
            ->acceptJson()
            ->timeout(10)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/v2/shipping-order/fee',
                [
                    'service_id' => $serviceId,
                    'from_district_id' => $fromDistrictId,
                    'from_ward_code' => $fromWardCode,
                    'to_district_id' => $toDistrictId,
                    'to_ward_code' => $toWardCode,
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'insurance_value' => 0,
                ],
            );

        $response->throw();

        return $response->json('data') ?? [];
    }

    public function createStore(
        string $name,
        string $phone,
        string $address,
        int $districtId,
        string $wardCode,
    ): int {
        $response = Http::withHeaders([
            'Token' => $this->token,
        ])
            ->acceptJson()
            ->timeout(15)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/v2/shop/register',
                [
                    'district_id' => $districtId,
                    'ward_code' => $wardCode,
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address,
                ],
            );

        $response->throw();

        $shopId = $response->json('data.shop_id');

        if (!$shopId) {
            throw new RuntimeException(
                'GHN không trả về shop_id.',
            );
        }

        return (int) $shopId;
    }

    public function createShippingOrder(
        int $shopId,
        array $payload,
    ): array {
        $response = Http::withHeaders([
            'Token' => $this->token,
            'ShopId' => (string) $shopId,
        ])
            ->acceptJson()
            ->timeout(15)
            ->post(
                $this->baseUrl
                . '/shiip/public-api/v2/shipping-order/create',
                $payload,
            );

        $response->throw();

        $data = $response->json('data');

        if (
            !is_array($data)
            || empty($data['order_code'])
        ) {
            throw new RuntimeException(
                'GHN không trả về mã vận đơn.',
            );
        }

        return $data;
    }
}