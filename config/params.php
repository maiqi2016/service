<?php
return [
    'app_name' => 'Service',

    'use_cache' => true,

    'private' => [
        'root_user_ids' => '1' // user ids, comma separated
    ],

    'pagenum' => 15,
    'sendmail_interval' => 90,
    'company_tel' => '021-60494472',
    'wechat_id' => 'KAKE_Hotel',

    'captcha_timeout' => 600,
    'captcha_send_again' => 60,

    'sms_id' => 'dh19431',
    'sms_secret' => '44X3Jsn3',
    'sms_host' => 'http://www.dh3t.com',
    'sms_sign' => '【KAKE】',
    'sms_tpl_1' => '您的验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',
    'sms_tpl_2' => '您的验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',

    'default_purchase_limit' => 10
];