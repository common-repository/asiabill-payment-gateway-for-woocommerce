<?php

return [
    'version' => 'V2022-03',
    'payment' => [
        // 正式交易接口
        'live' => 'https://safepay.asiabill.com/',
        // 测试交易接口
        'test' => 'https://testpay.asiabill.com/',
    ],
    'openapi' => [
        // 正式开放API
        'live' => 'https://openapi.asiabill.com/openApi/',
        // 测试开放API
        'test' => 'https://api-uat.asiabill.com/openApi/',
    ],
    'uri'     => [
        'payment' => [
            // 操作客户
            'customers'             => '/customers',
            // 获取sessionToken
            'sessionToken'          => '/sessionToken',
            // 创建paymentMethodId
            'paymentMethods'        => '/payment_methods',
            // 查询paymentMethod列表
            'paymentMethods_list'   => '/payment_methods/list/{customerId}',
            // 更新paymentMethodId信息
            'paymentMethods_update' => '/payment_methods/update',
            // 查询paymentMethodId信息
            'paymentMethods_query'  => '/payment_methods/{customerPaymentMethodId}',
            // 解绑paymentMethodId
            'paymentMethods_detach' => '/payment_methods/{customerPaymentMethodId}/detach',
            // 绑定paymentMethodId
            'paymentMethods_attach' => '/payment_methods/{customerPaymentMethodId}/{customerId}/attach',
            // 确认扣款
            'confirmCharge'         => '/confirmCharge',
            // 获取支付页面地址
            'checkoutPayment'       => '/checkout/payment',
            // 预授权接口
            'Authorize'             => '/AuthorizeInterface',
        ],
        'openapi' => [
            // 预授权接口
            'Authorize'    => '/AuthorizeInterface',
            // 拒付查询
            'chargebacks'  => '/chargebacks',
            // 退款申请
            'refund'       => '/refund',
            // 退款查询
            'refund_query' => '/refund/{batchNo}',
            // 上传物流信息
            'logistics'    => '/logistics',
            // 交易流水列表
            'transactions' => '/transactions',
            // 交易详情
            'orderInfo'    => '/orderInfo/{tradeNo}',
        ]
    ],
    'webhook' => [
        'timeout' => 600000
    ],
    'logger'  => [
        // 日志类
        'class' => 'Asiabill\Classes\AsiabillLogger',
        'method' => 'addLog',
        // 是否开启日志
        'start' => false,
        // 记录日志的目录
        'dir'   => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.date('Ym'),
        // 日志文件
        'file'  => date('d').'_asiabill.log',
    ]
];