<?php
//error_reporting(E_ALL);

define('CERT_PATH', __DIR__.'/wxpay/cert');

$appId = 'wx4bd459545a672aaa';
$appSecret = 'f22de972d4b4fdc99a508280ab1982f5';
// $jscode = isset($_GET['code']) ? trim($_GET['code']) : '';
// if (empty($jscode)) exit(json_encode(['code' => 0,'msg' => '获取code失败']));

$openId = isset($_GET['openid']) ? trim($_GET['openid']) : '';
if (empty($openId)) exit(json_encode(['code' => 0,'msg' => '获取openid失败']));

require_once "wxpay/lib/WxPay.Api.php";
require_once "wxpay/WxPay.JsApiPay.php";
require_once "wxpay/WxPay.Config.php";
require_once 'wxpay/log.php';


//初始化日志
$logHandler= new CLogFileHandler("./wxpay/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//①、获取用户openid
try{
    
    $tools = new JsApiPay();
    //$openId = $tools->GetOpenid();
    //②、统一下单
    $input = new WxPayUnifiedOrder();
    $input->SetBody("test");
    $input->SetAttach("test");
    $input->SetOut_trade_no("sdkphp".date("YmdHis"));
    $input->SetTotal_fee("1");
    $input->SetTime_start(date("YmdHis"));
    $input->SetTime_expire(date("YmdHis", time() + 600));
    $input->SetGoods_tag("test");
    $input->SetNotify_url("https://www.ccl711.com/wechat/notify.php");
    $input->SetTrade_type("JSAPI");
    $input->SetOpenid($openId);
    $config = new WxPayConfig();
    $order = WxPayApi::unifiedOrder($config, $input);
    //$timeStamp = time();
    //$order['timeStamp'] = "$timeStamp";
    //$paySign = md5('appId='.$config->GetAppId().'&nonceStr='.$order['nonce_str'].'&package=prepay_id='.$order['prepay_id'].'&signType=MD5&timeStamp='.$timeStamp.'&key='.$config->GetKey());
    //$order['paySign'] = $paySign;
    $jsApiParameters = $tools->GetJsApiParameters($order);
    exit(json_encode(['code' => 1,'order' => json_decode($jsApiParameters,true)]));
//     //获取共享收货地址js函数参数
//     $editAddress = $tools->GetEditAddressParameters();
} catch(Exception $e) {
    Log::ERROR(json_encode($e));
}