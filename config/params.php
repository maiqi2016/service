<?php
return [
    'app_name' => 'Service',

    'use_cache' => true,
    'private' => ['root_user_ids' => '1'],

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

    'sms_tpl_1' => '您的后台登录验证码是 %s，请不要将验证码泄露给其他人，验证码将在 %d 分钟后失效。',
    'sms_tpl_2' => '您的订单验证码是 %s，请不要将验证码泄露给其他人，验证码将在 %d 分钟后失效。',
    'sms_tpl_3' => '您的登录验证码是 %s，请不要将验证码泄露给其他人，验证码将在 %d 分钟后失效。',
    'sms_tpl_4' => '您的活动参与验证码是 %s，请不要将验证码泄露给其他人，验证码将在 %d 分钟后失效。',

    'sms_tpl_order_success' => '您的订单支付成功，订单编号为 %s，可在右上角菜单 > 订单中心进行查看和预约等操作，有任何问题可拨打客服电话 %s。',
    'sms_tpl_apply_check_success' => '您预约的酒店入住申通已经通过，入住人 %s，入住时间 %s，订单确认号 %s，有任何问题可拨打客服电话 %s。',
    'sms_tpl_apply_check_fail' => '您预约的酒店入住申通很遗憾未被通过，入住人 %s，入住时间 %s，原因是 %s，有任何问题可拨打客服电话 %s。',
    'sms_tpl_apply_refund_success' => '您申请的订单 %s 退款已成功通过，退款金额共计 %s，退款金额最晚将在3个工作日内按支付原路退回到您的账户内，请注意账户明细。',
    'sms_tpl_apply_refund_fail' => '您申请的订单 %s 退款已被拒绝，退款金额共计 %s，原因是 %s。',
    'sms_tpl_apply_distributor_success' => '您的分销商申请已经成功通过，重新登录后可在菜单中找到分销管理进入。',

    'default_purchase_limit' => 10
];