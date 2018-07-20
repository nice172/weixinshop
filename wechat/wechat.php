<?php

define('CERT_PATH', __DIR__.'/wxpay/cert');

$appId = 'wx4bd459545a672aaa';
$appSecret = 'f22de972d4b4fdc99a508280ab1982f5';

$jscode = isset($_GET['code']) ? trim($_GET['code']) : '';
if (empty($jscode)) exit(json_encode(['code' => 0,'msg' => '获取code失败']));

$get_openid = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$jscode}&grant_type=authorization_code";

$res = httpRequest($get_openid);

exit(json_encode(['code' => 1,'data' => $res]));

function httpRequest($url,$method='GET',$params=array(),$auth=''){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    #curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    //SSL验证
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    $header[] = "Content-Type:application/json;charset=utf-8";
    if(!empty($header)){
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header );
    }
    $timeout = 30;
    curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    switch ($method){
        case "GET" :
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            break;
        case "POST":
            if(is_array($params)){
                $params = json_encode($params,320);
            }
            #curl_setopt($curl, CURLOPT_POST,true);
            #curl_setopt($curl, CURLOPT_NOBODY, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            //设置提交的信息
            curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
            break;
        case "PUT" :
            curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($params,320));
            break;
        case "DELETE":
            curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
            break;
    }
    
    //传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。
    if (!empty($auth) && isset($auth['username']) && isset($auth['password'])) {
        curl_setopt($curl, CURLOPT_USERPWD, "{$auth['username']}:{$auth['password']}");
    }

    $data = curl_exec($curl);//执行预定义的CURL
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);//获取http返回值,最后一个收到的HTTP代码
    curl_close($curl);//关闭cURL会话
    $res = json_decode($data,true);
    return $res;
}