<?php

return [
    'shipping_flat_fee' => (int) env('SHIPPING_FLAT_FEE', 30000),
    'shipping_free_from' => (int) env('SHIPPING_FREE_FROM', 500000),
];
