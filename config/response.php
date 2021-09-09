<?php

return [
    'is_restful' => false,              // 是否 RESTful 规范(设置为 false, HTTP 状态码统一返回 200)
    'is_unified_return_json' => true,   // 是否统一返回 JSON
    'code' => [
        'success' => 'Success|10000',
        'fail' => 'Fail|20000',
        'error' => 'Error|30000',
        'unauthorized' => 'Unauthenticated|20001',
        'validation' => 'Unprocessable Entity|20002',
    ],
    'format' => [
        'collection_field' => 'list',
        'pagination' => [
            'meta_field' => 'meta',
            'return_fields' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'from',
                'to',
                'path',
                'prev_page_url',
                'next_page_url',
            ]
        ]
    ]
];