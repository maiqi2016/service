<?php
return [
    'app_name' => 'Service',

    'use_cache' => true,

    'private' => [
        'root_user_ids' => '1' // user ids, comma separated
    ],

    'pagenum' => 15,
    'sendmail_interval' => 90,
    'contact_us' => '021-60494472',
    'company_tel' => '4008068821',
    'wechat_id' => 'KAKE_Hotel',

    'captcha_timeout' => 600,
    'captcha_send_again' => 60,

    'sms_id' => 'dh19431',
    'sms_secret' => '44X3Jsn3',
    'sms_host' => 'http://www.dh3t.com',
    'sms_sign' => '【KAKE】',
    'sms_tpl_1' => '您的后台登录验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',
    'sms_tpl_2' => '您的订单验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',
    'sms_tpl_3' => '您的登录验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',
    'sms_tpl_4' => '您的订单支付成功，订单编号为%s，可在右上角菜单>订单中心进行查看和预约等操作，有任何问题可拨打客服电话%s。',
    'sms_tpl_5' => '您预约的酒店入住申通已经通过，入住人%s，入住时间%s，订单确认号%s，有任何问题可拨打客服电话%s。',
    'sms_tpl_6' => '您预约的酒店入住申通很遗憾未被通过，入住人%s，入住时间%s，原因是%s，有任何问题可拨打客服电话%s。',
    'sms_tpl_7' => '您的分销商申请已经成功通过，可通过后台地址%s登录获取推广链接/二维码和相关的分销管理。',

    'default_purchase_limit' => 10
];