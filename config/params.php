<?php
return [
    'app_name' => 'Service',

    'private' => [
        'root_user_ids' => '1' // user ids, comma separated
    ],

    'pagenum' => 20,
    'sendmail_interval' => 90,
    'company_tel' => '021-60494472',
    'wechat_id' => 'KAKE_Hotel',

    'use_cache' => true,
    'cache_time' => 7200,

    'captcha_timeout' => 600,
    'captcha_send_again' => 60,

    'sms_id' => 'dh19431',
    'sms_secret' => '44X3Jsn3',
    'sms_host' => 'http://www.dh3t.com',
    'sms_sign' => '【KAKE】',
    'sms_tpl_backend_login' => '您的验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',
    'sms_tpl_frontend_login' => '您的验证码是%s，请不要将验证码泄露给其他人，验证码将在%d分钟后失效。',

    'wechat_tpl_bind_link' => '点击这里<a href="%s">绑定KAKE</a>账号',

    'default_purchase_limit' => 10
];
