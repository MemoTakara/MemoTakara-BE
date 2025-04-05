<?php

return [

    'paths' => ['api/*'], // Các đường dẫn mà bạn muốn áp dụng CORS

    'allowed_methods' => ['*'], // Cho phép tất cả các phương thức HTTP

    'allowed_origins' => [
        'http://localhost:5173', // Địa chỉ frontend thứ nhất
        'https://memo-takara-fe-web.vercel.app/',   // Địa chỉ frontend thứ hai
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Cho phép tất cả các header

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Có nên hỗ trợ cookie và thông tin phiên được chia sẻ

];
