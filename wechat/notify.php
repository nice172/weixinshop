<?php
define('IN_ECS', true);
define('CERT_PATH', __DIR__.'/wxpay/cert');
require ('../includes/init.php');
require_once "./wxpay/lib/WxPay.Api.php";
require_once './wxpay/lib/WxPay.Notify.php';
require_once "./wxpay/WxPay.Config.php";
require_once './wxpay/log.php';

//初始化日志
$logHandler= new CLogFileHandler("./wxpay/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify {
    
    public function getResult(){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        return $this->FromXml($xml);
    }
    
	//查询订单
	public function Queryorder($transaction_id){
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);

		$config = new WxPayConfig();
		$result = WxPayApi::orderQuery($config, $input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}

	/**
	*
	* 回包前的回调方法
	* 业务可以继承该方法，打印日志方便定位
	* @param string $xmlData 返回的xml参数
	*
	**/
	public function LogAfterProcess($xmlData){
		Log::DEBUG("call back， return xml:" . $xmlData);
		return;
	}
	
	//重写回调处理函数
	/**
	 * @param WxPayNotifyResults $data 回调解释出的参数
	 * @param WxPayConfigInterface $config
	 * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	public function NotifyProcess($objData, $config, &$msg){
		$data = $objData->GetValues();
		//TODO 1、进行参数校验
		if(!array_key_exists("return_code", $data) 
			||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
			//TODO失败,不是支付成功的通知
			//如果有需要可以做失败时候的一些清理处理，并且做一些监控
			$msg = "异常异常";
			return false;
		}
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}

		//TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
				Log::ERROR("签名错误...");
				return false;
			}
		} catch(Exception $e) {
			Log::ERROR(json_encode($e));
		}

		//TODO 3、处理业务逻辑
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();
		
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}

$config = new WxPayConfig();
Log::DEBUG("begin notify");
$notify = new PayNotifyCallBack();
$notify->Handle($config, false);
if ($notify->GetReturn_code() == 'SUCCESS'){
    Log::INFO("result:" . json_encode($notify->getResult()));
    $result = $notify->getResult();
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
        
        return;
    }

    $orderInfo = $db->getRow("select * from {$ecs->table('order_info')} where pay_status!=2 and order_sn='{$order_sn}' and user_id='{$user_id}'");
    //if (!empty($orderInfo) && ($result['total_fee']/100) == $orderInfo['order_amount']){
    if (!empty($orderInfo)){
        $db->autoExecute($ecs->table('order_info'), ['pay_status' => 2],'UPDATE',"user_id='{$orderInfo['user_id']}' and order_id='{$orderInfo['order_id']}'");
        $pay_log = $db->getRow("select * from {$ecs->table('pay_log')} where order_id='{$orderInfo['order_id']}'");
        Log::INFO('pay_log:'. json_encode($pay_log));
        $db->autoExecute($ecs->table('pay_log'), ['is_paid' => 1],'UPDATE','order_id='.$orderInfo['order_id']);
    }
}

