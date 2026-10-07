<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GHN (Giao Hàng Nhanh) Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình tích hợp API GHN. Đặt các giá trị tương ứng trong file .env.
    |
    | GHN_BASE_URL      : URL cơ sở API (sandbox hoặc production)
    |                     Sandbox  : https://dev-online-gateway.ghn.vn/shiip/public-api
    |                     Production: https://online-gateway.ghn.vn/shiip/public-api
    |
    | GHN_TOKEN         : API Token từ tài khoản GHN (Settings → API)
    | GHN_SHOP_ID       : Shop ID từ tài khoản GHN (Settings → Shop)
    | GHN_FROM_DISTRICT_ID : District ID GHN của kho hàng/cửa hàng gửi đi
    | GHN_VERIFY_SSL    : true (production) / false (nếu môi trường dev gặp lỗi SSL)
    |
    */

    'base_url'         => env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
    'token'            => env('GHN_TOKEN', ''),
    'shop_id'          => env('GHN_SHOP_ID', 0),
    'from_district_id' => env('GHN_FROM_DISTRICT_ID', 0),
    'verify_ssl'       => env('GHN_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Trọng lượng mặc định (gram) cho mỗi sản phẩm nếu chưa có trường weight
    |--------------------------------------------------------------------------
    */
    'default_weight'   => env('GHN_DEFAULT_WEIGHT', 200),

];
