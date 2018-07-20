<?php
define('IN_ECS', true);
require ('../includes/init.php');

$json = '{"appid":"wx4bd459545a672aaa","attach":"recharge","bank_type":"CFT","cash_fee":"100","fee_type":"CNY","is_subscribe":"N","mch_id":"1490631652","nonce_str":"0zfex0kuy5gnboactw1l85u9hqujco25","openid":"o6M-84mBkUjhwkK8XBF7WCqIkRu8","out_trade_no":"8N1532072088U29","result_code":"SUCCESS","return_code":"SUCCESS","sign":"CBB619D308152F0DD134B2B7864659ED18C9A89237CD4F8843357276F4A033AF","time_end":"20180720153456","total_fee":"100","trade_type":"JSAPI","transaction_id":"4200000116201807209508038787"}';


//$db->autoExecute($ecs->table('users'), ['user_money' => 3],'UPDATE',"user_id='29'");

exit;
$result = json_decode($json,true);

$user_id = trim($result['attach']);
$order_sn = $result['out_trade_no'];
$total_fee = $result['total_fee'];
if ($user_id == 'recharge'){
    //用户充值
    list($sn,$new_user_id) = explode('U', $order_sn);
    if (isset($sn) && !empty($sn)) {
        list($insertId,$time) = explode('N', $sn);
        if ($insertId && $new_user_id){
            $user_acount = $db->getRow("select * from {$ecs->table('user_account')} where user_id='{$new_user_id}' and id='{$insertId}'");
            if (!empty($user_acount) && $user_acount['is_paid'] == 0){
                $db->autoExecute($ecs->table('user_account'), ['is_paid' => 1,'paid_time' => time()],'UPDATE',"user_id='{$new_user_id}' and id='{$insertId}'");
                $userinfo = $db->getRow("select * from {$ecs->table('users')} where user_id=".$new_user_id);
                if (!empty($userinfo)){
                    $user_money = $userinfo['user_money'] + $total_fee/100;
                    $db->autoExecute($ecs->table('users'), ['user_money' => $user_money],'UPDATE',"user_id='{$new_user_id}'");
                }
            }
        }
    }
    echo $user_id;
    return;
}