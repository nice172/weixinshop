<?php
use phpDocumentor\Reflection\Types\Array_;

define('IN_ECS', true);
require ('../includes/init.php');
require ('../includes/lib_order.php');
require ('../includes/lib_clips.php');
include_once ('includes/cls_json.php');
if ((DEBUG_MODE & 2) != 2) {
    $smarty->caching = true;
}

/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
// 判断是否有ajax请求
// $act = !empty($_GET['act']) ? $_REQUEST['act'] : '';
// $act = trim($act);
$act = isset($_GET['act']) ? trim($_GET['act']) : trim($_REQUEST['act']);
if (empty($act)) {
    //$act = trim($_REQUEST['act']);
    exit(json_encode(['msg' => '非法操作']));
}
$json = new JSON();
// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr = array(
    "hot",
    "home_shownum",
    "categories",
    "purchase",
    "send_msg",
    "act_register",
    "act_login",
    "good",
    "cart",
    "add_to_cart",
    "brand_list",
    'mingdanbrand',
    "get_cat_info",
    'test',
    'detail',
    'search',
    'quickcate',
    'create',
    'findpwd',
    'cart_num',
    'shipping',
    'item_num',
    'logout'
);

/* 显示页面的action列表 */
$ui_arr = array(
    "user_info",
	"orders_list",
    "act_edit_address",
    "drop_consignee",
    "mem_info",
    "purchase_list",
    'purchase_delete',
    "account_log",
    "act_edit_purchase",
    "stock_list",
    "order_list",
    'upload',
    'apply',
    'quick',
    'get_address_default',
    'payment',
	'userinfo',
    'updateuser',
    'getpurchase',
    'recharge',
    'mingdansend',
    'mingdanlist','friends',
    'deletemingdan','isapply','update_goods',
    'address_list','order_pay','delete_goods',
    'confirm','cancelorder','refundgoods'
);

/* 未登录处理 */
if (empty($_SESSION['user_id'])) {
    if (! in_array($act, $not_login_arr)) {
        if (in_array($act, $ui_arr)) {
            echo json_encode(array(
                "code" => "20001",
                "msg" => "需要登录!"
            ));
            exit();
        } else {
            // 未登录提交数据。非正常途径提交数据！
            ajaxReturn(array(
                'code' => 0,
                "msg" => "非法操作!"
            ));
        }
    }
}

$user_id = $_SESSION['user_id'];

if ($act == 'create'){

    
    //$db->query("alter table ccl_users add truename varchar(255) not null default '' comment '姓名'");
    return;
}

function getOpenId(){
    $appId = 'wx4bd459545a672aaa';
    $appSecret = 'f22de972d4b4fdc99a508280ab1982f5';
    $jscode = isset($_GET['code']) ? trim($_GET['code']) : '';
    if (empty($jscode)) exit(json_encode(['code' => 0,'msg' => '获取code失败']));

    $get_openid = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$jscode}&grant_type=authorization_code";
    
    $wxResult = httpRequest($get_openid);
    if (empty($wxResult) || !isset($wxResult['openid']) || empty($wxResult['openid'])){
        exit(json_encode(['code' => 0,'msg' => '获取openid失败']));
    }
    
    define('CERT_PATH', __DIR__.'/wxpay/cert');
    require_once "wxpay/lib/WxPay.Api.php";
    require_once "wxpay/WxPay.JsApiPay.php";
    require_once "wxpay/WxPay.Config.php";
    require_once 'wxpay/log.php';
    
    //初始化日志
    $logHandler= new CLogFileHandler("./wxpay/logs/".date('Y-m-d').'.log');
    $log = Log::Init($logHandler, 15);
    
    return $wxResult;
}

if ($act == 'logout'){
    $sesid = SESS_ID;
    $db->query("delete from {$ecs->table('sessions')} where sesskey='{$sesid}'");
    $db->query("delete from {$ecs->table('sessions_data')} where sesskey='{$sesid}'");
    session_unset();
    session_destroy();
    echo 1;
    return;
}

if ($act == 'item_num'){
    $rec_type = CART_GENERAL_GOODS;
    $sql = "SELECT count(*) as count FROM " . $ecs->table('cart') . " " . " WHERE session_id = '" . SESS_ID . "' AND rec_type = '".$rec_type."'";
    $res = $db->getRow($sql);
    ajaxReturn(['cart_count' => $res['count']]);
}

if ($act == 'recharge'){
    $data = post();
    $amount = isset($data['amount']) ? intval($data['amount']) : 0;
    if ($amount <= 0) exit(json_encode(['code' => 0,'msg' => '充值金额不正确']));
    $wxResult = getOpenId();

    $order = [
       'user_id' => $user_id,
       'amount' => $amount,
        'add_time' => time(),
        'paid_time' => 0,
        'user_note' => '用户充值',
        'process_type' => 0,
        'is_paid' => 0
    ];
    $db->autoExecute($ecs->table('user_account'), $order);
    $insertId = $db->insert_id();
    if ($insertId <= 0){
        ajaxReturn(['code' => 0,'msg' => '充值失败']);
    }
    $order_sn = $insertId.'N'.time().'U'.$user_id;
    try{
        
        $tools = new JsApiPay();
        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("账户充值");
        $input->SetAttach("recharge");
        $input->SetOut_trade_no($order_sn);
        //$input->SetTotal_fee("1");
        $money = (string) $order['amount']*100;
        $input->SetTotal_fee($money);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("账户充值");
        $input->SetNotify_url("https://www.ccl711.com/wechat/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($wxResult['openid']);
        $config = new WxPayConfig();
        $wxorder = WxPayApi::unifiedOrder($config, $input);
        $jsApiParameters = $tools->GetJsApiParameters($wxorder);
        ajaxReturn(['code' => 1,'order' => json_decode($jsApiParameters,true)]);
        
    } catch(Exception $e) {
        Log::ERROR(json_encode($e));
    }
    return;
}

if ($act == 'search'){
    
    if (!isset($_GET['id']) || empty($_GET['id'])) ajaxReturn(['code' => 0,'msg' => '搜索商品失败']);
    
    include 'list.php';
    
    return;
}

if ($act == 'confirm'){
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    if ($order_id <= 0) ajaxReturn(['code' => 0,'msg' => '确认收货失败']);
    $db->autoExecute($ecs->table('order_info'), [
        'order_status' => 5,'shipping_status' => 2,'pay_status' => 2
    ],'UPDATE', "user_id='{$user_id}' and order_id='{$order_id}'");
    if ($db->affected_rows()){
        $db->autoExecute($ecs->table('order_action'), [
            'order_id' => $order_id,'action_user' => '买家',
            'order_status' => 5,'shipping_status' => 2,
            'pay_status' => 2,'action_note' => '用户确认',
            'log_time' => time()
        ]);
        ajaxReturn(['code' => 1,'msg' =>'确认收货成功']); 
    }
    ajaxReturn(['code' => 0,'msg' => '确认收货失败']);
}

if ($act == 'cancelorder'){
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    if ($order_id <= 0) ajaxReturn(['code' => 0,'msg' => '取消订单失败']);
    $db->autoExecute($ecs->table('order_info'), [
        'order_status' => 2,'shipping_status' => 0,'pay_status' => 0
    ],'UPDATE', "user_id='{$user_id}' and order_id='{$order_id}'");
    if ($db->affected_rows()){
        $db->autoExecute($ecs->table('order_action'), [
            'order_id' => $order_id,'action_user' => '买家',
            'order_status' => 2,'shipping_status' => 0,
            'pay_status' => 0,'action_note' => '用户取消',
            'log_time' => time()
        ]);
        ajaxReturn(['code' => 1,'msg' =>'取消订单成功']);
    }
    ajaxReturn(['code' => 0,'msg' => '取消订单失败']);
}

if ($act == 'refundgoods'){
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    if ($order_id <= 0) ajaxReturn(['code' => 0,'msg' => '退货操作失败']);
    $db->autoExecute($ecs->table('order_info'), [
        'order_status' => 4,'shipping_status' => 0,'pay_status' => 0
    ],'UPDATE', "user_id='{$user_id}' and order_id='{$order_id}'");
    if ($db->affected_rows()){
        $db->autoExecute($ecs->table('order_action'), [
            'order_id' => $order_id,'action_user' => '买家',
            'order_status' => 4,'shipping_status' => 0,
            'pay_status' => 0,'action_note' => '用户退货',
            'log_time' => time()
        ]);
        ajaxReturn(['code' => 1,'msg' =>'退货操作成功']);
    }
    ajaxReturn(['code' => 0,'msg' => '退货操作失败']);
}

if ($act == 'purchase_delete'){
 
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) ajaxReturn(['code' => 0,'msg' => '删除失败']);
    
    $db->query("delete from {$ecs->table('user_purchase')} where user_id='{$user_id}' and purchase_id='{$id}'");
    if ($db->affected_rows()){
        ajaxReturn(['code' => 1,'msg' => '删除成功']);
    }else{
        ajaxReturn(['code' => 0,'msg' => '删除失败']);
    }
}

if ($act == 'deletemingdan'){
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) ajaxReturn(['code' => 0,'msg' => '删除失败']);
    $db->query("delete from {$ecs->table('user_black')} where user_id='{$user_id}' and id='{$id}'");
    if ($db->affected_rows()){
        ajaxReturn(['code' => 1,'msg' => '删除成功']);
    }else{
        ajaxReturn(['code' => 0,'msg' => '删除失败']);
    }
    return;
}

if ($act == 'quickcate'){
    include 'WxCategory.class.php';
    $sql = "SELECT brand_id,brand_name FROM " . $GLOBALS['ecs']->table('brand');
    $brand_list = $db->getAll($sql);
    $WxCategory = new WxCategory();
    $catelist = $WxCategory->get_child_tree();
    $ban_categories = $WxCategory->get_lists($catelist);
    
    $model = $ban_categories[218]['attrs']; //型号
    $tonghou = $ban_categories[227]['attrs']; //铜厚
    $banhou = $ban_categories[226]['attrs']; //板厚
    $size = $ban_categories[229]['attrs']; //尺寸

    $tempCate = $tempmodel = $temptonghou = $tempbanhou = $tempsize = array();
    foreach ($catelist as $key => $value){
        $tempCate[] = $value;
    }
    $catelist = $tempCate;
    
    foreach ($model as $v){
        $tempmodel[] = $v;
    }
    $model = $tempmodel;
    
    foreach ($tonghou as $v){
        $temptonghou[] = $v;
    }
    $tonghou = $temptonghou;
    
    foreach ($banhou as $v){
        $tempbanhou[] = $v;
    }
    $banhou = $tempbanhou;
    
    foreach ($size as $v){
        $tempsize[] = $v;
    }
    $size = $tempsize;
    
    unset($tempCate,$tempmodel,$temptonghou,$tempbanhou,$tempsize);
    ajaxReturn(['code' => 1,'tonghou' => $tonghou, 
        'banhou' => $banhou,'catelist' => $catelist,
        'model' => $model,'size' => $size,'bandlist' => $brand_list]);
}

if ($act == 'mingdanlist'){
    
    $whiteList = $db->getAll("select * from {$ecs->table('user_black')} where type=0 and user_id=$user_id");
    $blackList = $db->getAll("select * from {$ecs->table('user_black')} where type=1 and user_id=$user_id");
    ajaxReturn(['code' => 1,
        'white' => $whiteList,
        'whiteTotal' => count($whiteList),
        'blackTotal' => count($blackList),
        'black' => $blackList]);
    return;
}

if ($act == 'friends'){
    $userlist1 = $db->getAll("select user_id,user_name,reg_time from {$ecs->table('users')} where parent_id=$user_id order by user_id desc");
    $userlist2 = [];
    foreach ($userlist1 as $key => $value){
        if(mb_strlen($value['user_name']) >= 10){
            $start = 3;
            $end = 5;
        }else{
            $start = 3;
            $end = 4;
        }
        $userlist1[$key]['user_name'] = str_replace(mb_substr($value['user_name'], $start,$end), '***', $value['user_name']);
        $userlist1[$key]['icon'] = '';
        $userlist1[$key]['reg_time'] = date('Y-m-d H:i:s',$value['reg_time']);
        $where = 'user_id='.$value['user_id'].' and pay_status=2 and shipping_status=2 and order_status=5';
        $sql = "SELECT SUM(order_amount) AS total_fee FROM " .$ecs->table('order_info') ." WHERE {$where}";
        $total_fee = $db->getRow($sql);
        $userlist1[$key]['monetary'] = $total_fee['total_fee'] ? $total_fee['total_fee'] : 0;
        $sql = "SELECT count(*) as count FROM " .$ecs->table('order_info') ." WHERE {$where}";
        $countOrder = $db->getRow($sql);
        $userlist1[$key]['order'] = $countOrder['count'];
        
        $user_id = $value['user_id'];
        $user2 = $db->getRow("select user_id,user_name,reg_time from {$ecs->table('users')} where parent_id=$user_id");
        
        if (!empty($user2)){
            if(mb_strlen($user2['user_name']) >= 10){
                $start = 3;
                $end = 5;
            }else{
                $start = 3;
                $end = 4;
            }
            $user2['user_name'] = str_replace(mb_substr($user2['user_name'], $start,$end), '***', $user2['user_name']);
	        $user2['reg_time'] = date('Y-m-d H:i:s',$user2['reg_time']);
	        $where = 'user_id='.$user2['user_id'].' and pay_status=2 and shipping_status=2 and order_status=5';
	        $sql = "SELECT SUM(order_amount) AS total_fee FROM " .$ecs->table('order_info') ." WHERE {$where}";
	        $total_fee = $db->getRow($sql);
	        $user2['monetary'] = $total_fee['total_fee'] ? $total_fee['total_fee']: 0;
	        $sql = "SELECT count(*) as count FROM " .$ecs->table('order_info') ." WHERE {$where}";
	        $countOrder = $db->getRow($sql);
	        $user2['order'] = $countOrder['count'];
	        $user2['icon'] = '';
	        $userlist2[] = $user2;
        }
    }
    
    ajaxReturn(['code' => 1,
        'userlist' => $userlist1,
        'userlist2' => $userlist2
        ]
    );
    return;
}

if ($act == 'mingdansend'){
    $data = post();
    if (empty($data) || !is_array($data)) ajaxReturn(['code' => 0,'msg' => '提交失败']);
    $data['type'] = intval($data['type']);
    $data['user_id'] = $user_id;
    $data['brand_id'] = intval($data['brand_id']);
    $data['brand_name'] = htmlspecialchars($data['brand_name']);
    
    $row = $db->getRow("select * from {$ecs->table('user_black')} where user_id=$user_id and brand_id='{$data['brand_id']}' and type='{$data['type']}'");
    if (!empty($row)) ajaxReturn(['code' => 0,'msg' => '不能重复提交']);
    
    if($db->autoExecute($ecs->table('user_black'), $data)){
        ajaxReturn(['code' => 1,'msg' => '提交成功']);
    }else{
        ajaxReturn(['code' => 0,'msg' => '提交失败']);
    }
    return;
}

if ($act == 'getpurchase'){
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) ajaxReturn(['code' => 0,'msg' => '加载数据失败']);
    $data = $db->getRow("select * from {$ecs->table('user_purchase')} where user_id='{$user_id}' and purchase_id='{$id}'");
    if (empty($data)) ajaxReturn(['code' => 0,'msg' => '加载数据失败']);
    ajaxReturn(['code' => 1,'data' => $data]);
}

if ($act == 'updateuser'){
    $data = post();
    if (empty($data) || !is_array($data)) ajaxReturn(['code' => 0, 'msg' => '修改失败']);
    if (!checkmobile($data['mobile_phone'])) ajaxReturn(['code' => 0,'msg' => '手机号不合法']);
    $username = $db->getRow("select * from {$ecs->table('users')} where user_id!='{$user_id}' and truename='{$data['user_name']}'");
    if (!empty($username)) ajaxReturn(['code' => 0,'msg' => '用户名已存在']);
    $mobile = $db->getRow("select * from {$ecs->table('users')} where user_id!='{$user_id}' and mobile_phone='{$data['mobile_phone']}'");
    if (!empty($mobile)) ajaxReturn(['code' => 0,'msg' => '手机号已存在']);
    $wxuser = $db->getRow("select * from {$ecs->table('users')} where user_id!='{$user_id}' and wxuser='{$data['wxuser']}'");
    if (!empty($wxuser)) ajaxReturn(['code' => 0,'msg' => '微信号已存在']);
    $data['truename'] = $data['user_name'];
    unset($data['user_name']);
    if($db->autoExecute($ecs->table("users"), $data, 'UPDATE', "user_id='{$user_id}'")){
        ajaxReturn(['code' => 1, 'msg' => '修改成功']);
    }
    ajaxReturn(['code' => 0, 'msg' => '修改失败']);
}

if ($act == 'payment'){
 
    $goodsIdArray = explode(',', $_POST['goodsids']);
    
    if (empty($goodsIdArray)) ajaxReturn(['code' => 0,'msg' => '请至少选择一个商品']);
    
    $count_type = 'payment';
    
    $wxResult = getOpenId();

    include 'order.php';
        
    return;
}

if ($act == 'shipping'){
    $data = post();
    if (empty($data['goodsid'])){
        ajaxReturn(['code' => 0,'shipping_free' => '0.00']);
    }
    $count_type = 'shipping';
    $shipping_free = '';
    $goodsIdArray = $data['goodsid'];
    $order = include 'order.php';
    ajaxReturn(['code' => 1,'order' => $order]);
    
}

if ($act == 'order_pay'){
    
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    if ($order_id <= 0) ajaxReturn(['code' => 0,'msg' => '获取订单信息失败']);

    $orderInfo = $db->getRow("select * from {$ecs->table('order_info')} where user_id='{$user_id}' and pay_status!=2 and order_id='{$order_id}'");
    if (empty($orderInfo)) {
        ajaxReturn(['code' => 0,'msg' => '获取订单信息失败']);
    }
    
    $wxResult = getOpenId();
    try{
        $tools = new JsApiPay();
        $input = new WxPayUnifiedOrder();
        $input->SetBody("购买商品");
        $input->SetAttach($user_id);
        $input->SetOut_trade_no($orderInfo['order_sn']);
        $money = (string) $orderInfo['order_amount']*100;
        $input->SetTotal_fee($money);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("购买商品");
        $input->SetNotify_url("https://www.ccl711.com/wechat/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($wxResult['openid']);
        $config = new WxPayConfig();
        $wxorder = WxPayApi::unifiedOrder($config, $input);
        $jsApiParameters = $tools->GetJsApiParameters($wxorder);
        ajaxReturn(['code' => 1,'order' => json_decode($jsApiParameters,true)]);
        
    } catch(Exception $e) {
        Log::ERROR(json_encode($e));
    }
    
    return;
    
}

if ($act == 'userinfo'){
	
	$userinfo = $db->getRow("select * from {$ecs->table('users')} where user_id='{$user_id}'");
	if (empty($userinfo)){
		ajaxReturn(['code' => 0,'msg' => '获取用户信息失败']);
	}
	$userinfo['user_name'] = $userinfo['truename'];
	ajaxReturn(['code' => 1,'currentDate' => date('Y-m-d') ,'userinfo' => $userinfo]);
	return;
}

if ($act == 'quick'){
    $data = post();
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    if (empty($data) || !is_array($data) || 
        empty($data['phone']) || !$data['num'] || empty($data['username'])){
        ajaxReturn(['code' => 0,'msg' => '提交失败']);
    }
    
    if ($data['province'] && $data['city'] && $data['district']) {
        $province = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $data['province'] . " LIMIT 1");
        $city = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $data['city'] . " LIMIT 1");
        $district = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $data['district'] . " LIMIT 1");
    }
    $province = $province['region_name']?$province['region_name']:'';
    $city = $city['region_name']?$city['region_name']:'';
    $district = $district['region_name']?$district['region_name']:'';
    $attr_desc = '';
    if (isset($data['model'])){
    	$attr_desc = '型号：'.$data['model'].'；铜厚：'.$data['tong'].'；板厚：'.$data['ban'].'；尺寸：'.$data['size'];
    }
    $insertData = [
        'user_id' => $user_id,
        'cate_name' => isset($data['cate_name']) ? $data['cate_name'] : '',
        'brand_name' => isset($data['brand_name']) ? $data['brand_name'] : '',
    	'attr_desc' => $attr_desc,
        'realname' => $data['username'],
        'mobile' => $data['phone'],
        'address' => $province.$city.$district.$data['address'],
        'num' => $data['num'],
        'ctime' => date("Y-m-d H:i:s")
    ];
    if (empty($type)) {
        $insertData['images'] = json_encode($data['images']);
    }
    if ($type == 'audio') {
        $insertData['audio'] = $data['audiofile'];
    }
    if($db->autoExecute($ecs->table('order_quick'), $insertData)){
        ajaxReturn(['msg' => '提交成功','code' => 1]);
    }else{
        ajaxReturn(['msg' => '提交失败，请重试','code' => 0]);
    }
    return;
}

if ($act == 'isapply'){
	$oneData = $db->getOne("SELECT COUNT(*) as count FROM {$ecs->table('suppliers')} WHERE user_id={$user_id} AND is_check=2");
	if ($oneData > 0) ajaxReturn(['msg' => '你的申请资料已经提交，请等待系统管理审核通过，谢谢！','code' => 1]);
	$oneData2 = $db->getOne("SELECT COUNT(*) as count FROM {$ecs->table('suppliers')} WHERE user_id={$user_id} AND is_check=1");
	if ($oneData2 > 0) ajaxReturn(['msg' => '您已经是供应商了！','code' => 1]);
	ajaxReturn(['msg' => '未申请','code' => 0]);
}

if ($act == 'apply'){   
    //判断是否申请过
    $oneData = $db->getOne("SELECT COUNT(*) as count FROM {$ecs->table('suppliers')} WHERE user_id={$user_id} AND is_check=2");
    if ($oneData > 0) ajaxReturn(['msg' => '你的申请资料已经提交，请等待系统管理审核通过，谢谢！','code' => 0]);
    $oneData2 = $db->getOne("SELECT COUNT(*) as count FROM {$ecs->table('suppliers')} WHERE user_id={$user_id} AND is_check=1");
    if ($oneData2 > 0) ajaxReturn(['msg' => '您已经是供应商了！','code' => 0]);
    $data = post();
    $ctime = date('Y-m-d H:i:s');
    $sql = "INSERT INTO ".$ecs->table('suppliers')." 
        (suppliers_name,suppliers_desc,
         suppliers_sn,is_check,user_id,
          ctime,company_name,province,city,
            district,company_address,company_img,
          company_phone,company_contactor,up_file) VALUES (
            '{$data['suppliers_name']}',
            '{$data['suppliers_desc']}',
            '','2','{$user_id}','{$ctime}','{$data['company_name']}',
            '{$data['province']}','{$data['city']}','{$data['district']}',
            '{$data['company_address']}','{$data['sendIdcardImg']}',
             '{$data['company_phone']}','{$data['company_contactor']}','{$data['sendLogoImg']}')";
    
    if ($db->query($sql) === true){
        ajaxReturn(['msg' => '申请成功','code' => 1]);
    }
    ajaxReturn(['msg' => '申请失败，请重试','code' => 0]);
    return;
}

function post(){
    if (!empty($_POST)) return $_POST;
    $data = file_get_contents('php://input');
    return is_array(json_decode($data,true)) ? addslashes_deep(json_decode($data,TRUE)) : $data;
}

if ($act == 'upload'){
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    if (empty($type)) ajaxReturn(array('code' => 0,'msg' => '上传失败'));
    include 'Upload.class.php';
    $upload = new Upload();
    if ($type == 'logo'){
        $upload->savePath = 'brandlogo/';
    }elseif ($type == 'idcard'){
        $upload->savePath = 'company_img/';
    }elseif ($type == 'quick'){
        $upload->savePath = 'images/';
    }elseif ($type == 'audio'){
        $upload->savePath = 'audio/';
    }
    $info = $upload->upload();
    if (!$info){
        ajaxReturn(array(
            'code' => 0,
            'msg' => $upload->getError()
        ));
    }else{
        $info = $info['file'];
        $path = '/data/'.$info['savepath'].$info['savename'];
        ajaxReturn(array(
            'code' => 1,
            'path' => $path
        ));
    }
    return;
}

if ($act == 'test'){
    
    echo 123;
    return;
}


if ($act == 'hot') {
    // 热门商品
//     $jsontext = get_recommend_goods('hot');

    $hot = $db->getAll("select goods_id as id,promote_price,cat_id,goods_name as name,market_price,shop_price,goods_thumb as thumb,goods_img from {$ecs->table('goods')} where is_hot=1 and is_on_sale=1 and is_delete=0 order by goods_id asc");

    ajaxReturn($hot);
    
} else if ($act == 'orders_list') {
    // 热点滚动列表
    $sql = "select oi.order_amount,oi.consignee, oi.province, oi.city, oi.district, oi.order_status, oi.shipping_status, oi.pay_status, oi.add_time, og.goods_id, og.goods_name, og.goods_number  from " . $ecs->table('order_info') . " oi INNER JOIN " . $ecs->table('order_goods') . "  og ON oi.order_id = og.order_id ORDER BY oi.order_id desc LIMIT 8";
    $orders_list = $db->getAll($sql);
    $jsonlist = array();
    foreach ($orders_list as $key => $val) {
        $orders_list[$key]['consignee'] = mb_substr($val['consignee'], 0, 3) . "XX";
        
        $province = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['province'] . " LIMIT 1");
        $city = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['city'] . " LIMIT 1");
        $district = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['district'] . " LIMIT 1");
        $orders_list[$key]['mem_addr'] = $city['region_name'] . $district['region_name'];
        
        $orders_list[$key]['add_time'] = date('Y-m-d', $val['add_time']);
        $status = "已确认";
        if ($orders_list[$key]['order_status'] == "0") {
            $status = "未确认";
        } else if ($orders_list[$key]['order_status'] == "2") {
            $status = "已取消";
        } else if ($orders_list[$key]['order_status'] == "3") {
            $status = "无效";
        } else if ($orders_list[$key]['order_status'] == "4") {
            $status = "退货";
        }
        $jsonlist[$key] = $orders_list[$key]['consignee'] . "  订购了 " . $orders_list[$key]['goods_name'] . '  ' . $orders_list[$key]['order_amount'] . "元   " . $status;
    }
    ;
    echo $json->encode($jsonlist);
} else if ($act == 'home_shownum') {
    // 商家数量
    $sql = 'SELECT value FROM ' . $ecs->table("shop_config") . ' WHERE id = 905';
    $home_shownum = $db->getRow($sql, true);
    // print_r($home_shownum);
    $home_shownum = explode(',', $home_shownum['value']);
    echo $json->encode($home_shownum);
} else if ($act == 'categories') {
    // 分类树
    $ban_categories = get_child_tree('695');
    $part_categories = get_child_tree('696');
} else if ($act == 'purchase') {
    
    $page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
    $page_size = 20;
    $count = $db->getOne("select count(*) as count from {$ecs->table('user_purchase')} where verify_status=1 and status=1");
    $totalPage = ceil($count / $page_size);
    $limit = ($page - 1) * $page_size . ','. $page_size;
    
    //紧急采购
    $sql = "select * from " . $ecs->table('user_purchase') . "  where  verify_status = 1 and status=1 ORDER BY purchase_id desc LIMIT $limit";
    $purchase_list = $db->getAll($sql);
    foreach ($purchase_list as $key => $val) {
        $purchase_list[$key]['mem_addr'] = '';
        
        if ($val['province'] && $val['city'] && $val['district']) {
            $province = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['province'] . " LIMIT 1");
            $city = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['city'] . " LIMIT 1");
            $district = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $val['district'] . " LIMIT 1");
        }
        $purchase_list[$key]['mem_addr'] = $city['region_name'] . $district['region_name'];
    }
    ajaxReturn(['list' => $purchase_list,'totalPage' => $totalPage]);
}elseif ($act == 'detail') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) ajaxReturn(['code' => 0,'msg' => '获取数据失败']);

    $row = $db->getRow("select * from {$ecs->table('user_purchase')} where purchase_id='{$id}'");
   
    if (empty($row)) ajaxReturn(['code' => 0,'msg' => '获取数据失败']);
    $province = $city = $district = '';
    if ($row['province'] && $row['city'] && $row['district']) {
        $province = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $row['province'] . " LIMIT 1");
        $city = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $row['city'] . " LIMIT 1");
        $district = $db->getRow("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = " . $row['district'] . " LIMIT 1");
    }
    $row['province'] = $province['region_name'] ? $province['region_name'] : '';
    $row['city'] = $city['region_name'] ? $city['region_name'] : '';
    $row['district'] = $district['region_name'] ? $district['region_name'] : '';
    ajaxReturn(['code' => 1,'data' => $row]);
    
}else if ($action == 'send_msg') {
    // 发送验证码
    $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']);
    if ($mobile) {
        $reg_m_code = rand(100000, 999999);
        $msg = array(
            'code' => $reg_m_code
        );
        $rs = sendMsg($mobile, $msg);
        if ($rs) {
            $_SESSION['reg_m_code'] = $reg_m_code;
            echo json_encode(array(
                'status' => '200',
                'msg' => '发送成功'
            ));
            exit();
        } else {
            echo json_encode(array(
                'status' => '100',
                'msg' => '发送失败',
                'mobile' => $_POST['mobile']
            ));
            exit();
        }
    }
    echo json_encode(array(
        'status' => '0',
        'msg' => '手机号为空!',
        'mobile' => $_POST['mobile']
    ));
    exit();
}elseif ($act == 'findpwd'){
	$data = post();
	if (empty($data['username'])) ajaxReturn(['code' => 0,'msg' => '请输入手机号']);
	if (empty($data['verfy_code'])) ajaxReturn(['code' => 0,'msg' => '请输入验证码']);
	if (empty($data['password'])) ajaxReturn(['code' => 0,'msg' => '请输入新密码']);
	if ($data['confirm_password'] != $data['password']) ajaxReturn(['code' => 0,'msg' => '再次密码不一致']);
	$reg_m_code = $_SESSION['reg_m_code'];
	if ($data['verfy_code'] != $reg_m_code) {
		ajaxReturn(['code' => 0,'msg' => '验证码不正确']);
	}
	if (strlen($data['password']) < 6) {
		ajaxReturn(['code' => 0,'msg' => '密码长度不能小于6位']);
	}
	if (strpos($data['password'], ' ') > 0) {
		ajaxReturn(['code' => 0,'msg' => $_LANG['passwd_balnk']]);
	}
	unset($_SESSION['reg_m_code']);
	$find = $db->getRow("select * from {$ecs->table('users')} where user_name='{$data['username']}'");
	if (empty($find)){
		ajaxReturn(['code' => 0,'msg' => '修改失败']);
	}
	$db->autoExecute($ecs->table('users'), ['ec_salt' => '','salt' => 0,'password' => md5($data['password'])],'UPDATE',"user_id='{$find['user_id']}'");
	if ($db->affected_rows()){
		ajaxReturn(['code' => 1,'msg' => '修改成功']);
	}
	ajaxReturn(['code' => 0,'msg' => '修改失败']);
}
/* 注册会员的处理 */
else if ($act == 'act_register') {
    /* 增加是否关闭注册 */
    if ($_CFG['shop_reg_closed']) {
        $smarty->assign('action', 'register');
        $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
        echo json_encode($smarty,'','','error');
        exit();
    } else {
        include_once (ROOT_PATH . 'includes/lib_passport.php');
        
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $email = isset($_POST['email']) && ! empty($_POST['email']) ? trim($_POST['email']) : $username . "@ccl711.com";
        $other['msn'] = isset($_POST['extend_field1']) ? $_POST['extend_field1'] : '';
        $other['qq'] = isset($_POST['extend_field2']) ? $_POST['extend_field2'] : '';
        $other['office_phone'] = isset($_POST['extend_field3']) ? $_POST['extend_field3'] : '';
        $other['home_phone'] = isset($_POST['extend_field4']) ? $_POST['extend_field4'] : '';
        $other['mobile_phone'] = isset($_POST['extend_field5']) ? $_POST['extend_field5'] : '';
        $other['verfy_code'] = isset($_POST['verfy_code']) ? $_POST['verfy_code'] : '';
        $other['parent_id'] = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
        $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';
        
        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
        
        if (empty($_POST['agreement'])) {
            show_json_message($_LANG['passport_js']['agreement'],'','','error');
        }
        if (strlen($username) < 3) {
            show_json_message($_LANG['passport_js']['username_shorter'],'','','error');
        }
        
        if (strlen($password) < 6) {
            show_json_message($_LANG['passport_js']['password_shorter'],'','','error');
        }
        
        if (strpos($password, ' ') > 0) {
            show_json_message($_LANG['passwd_balnk'],'','','error');
        }
        
        if ($username) {
            $reg_m_code = $_SESSION['reg_m_code'];
            if ($other['verfy_code'] != $reg_m_code) {
                show_json_message('验证码不正确','','','error');
            }
        }
        /* 验证码检查 */
        if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0) {
            if (empty($_POST['captcha'])) {
                show_json_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }
            
            /* 检查验证码 */
            include_once ('includes/cls_captcha.php');
            
            $validator = new captcha();
            if (! $validator->check_word($_POST['captcha'])) {
                show_json_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');
            }
        }
        
        if (register($username, $password, $email, $other) !== false) {
            /* 把新注册用户的扩展信息插入数据库 */
            $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有自定义扩展字段的id
            $fields_arr = $db->getAll($sql);
            
            $db->autoExecute($ecs->table('users'), ['parent_id' => $other['parent_id']],'UPDATE','user_id='.$_SESSION['user_id']);
            
            $extend_field_str = ''; // 生成扩展字段的内容字符串
            foreach ($fields_arr as $val) {
                $extend_field_index = 'extend_field' . $val['id'];
                if (! empty($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
                    $extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";
                }
            }
            $extend_field_str = substr($extend_field_str, 0, - 1);
            
            if ($extend_field_str) // 插入注册扩展数据
            {
                $sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
                $db->query($sql);
            }
            
            /* 写入密码提示问题和答案 */
            if (! empty($passwd_answer) && ! empty($sel_question)) {
                $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
                $db->query($sql);
            }
            /* 判断是否需要自动发送注册邮件 */
            if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email']) {
                send_regiter_hash($_SESSION['user_id']);
            }
            $ucdata = empty($user->ucdata) ? "" : $user->ucdata;
            show_json_message(sprintf($_LANG['register_success'], $username . $ucdata), array(
                'user_id' => $_SESSION['user_id']
            ), array(
                $back_act,
                'user.php'
            ), 'info');
        } else {
           show_json_message('注册失败','','','error');
        }
    }
    exit();
} else if ($act == 'act_login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';
    
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0) {
        if (empty($_POST['captcha'])) {
            show_json_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }
        
        /* 检查验证码 */
        include_once ('includes/cls_captcha.php');
        
        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (! $validator->check_word($_POST['captcha'])) {
            show_json_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }
    }
    
    if ($user->login($username, $password, isset($_POST['remember']))) {
        update_user_info();
        recalculate_price();
        
        $ucdata = isset($user->ucdata) ? $user->ucdata : '';
        show_json_message($_LANG['login_success'] . $ucdata, array(
//             $_LANG['back_up_page'],
//             $_LANG['profile_lnk'],
            'user_id' => $_SESSION['user_id']
        ), array(
            $back_act,
            'user.php'
        ), 'info');
    } else {
        $_SESSION['login_fail'] ++;
        show_json_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
    }
} else if ($act == 'good') {
    $goods_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $goods = get_goods_info($goods_id);
    $properties = get_goods_properties($goods_id);
    $jsonlist = array();
    $jsonlist['specification'] = $properties['pro'];
    $jsonlist['goods'] = $goods;
    
    $rec_type = CART_GENERAL_GOODS;
    $sql = "SELECT count(*) as count FROM " . $ecs->table('cart') . " " . " WHERE session_id = '" . SESS_ID . "' AND rec_type = '".$rec_type."'";
    $res = $db->getRow($sql);
    $jsonlist['cart_count'] = $res['count'];
    
    $sql = "select g.goods_number,g.goods_name,o.order_id,o.add_time,o.user_id,u.user_name from {$ecs->table('order_info')} o inner 
join {$ecs->table('order_goods')} g on o.order_id=g.order_id inner 
join {$ecs->table('users')} u on o.user_id=u.user_id where goods_id='{$goods_id}' and o.pay_status=2 and o.shipping_status=2 and o.order_status=5";
    $recordList = $db->getAll($sql);
    foreach ($recordList as $key => $value){
        $recordList[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        if(mb_strlen($value['user_name']) >= 10){
            $start = 3;
            $end = 5;
        }else{
            $start = 3;
            $end = 4;
        }
        $recordList[$key]['icon'] = '';
        $recordList[$key]['user_name'] = str_replace(mb_substr($value['user_name'], $start, $end), '***', $value['user_name']);
    }
    $jsonlist['recordList'] = $recordList;
    
    echo $json->encode($jsonlist);
} else if ($act == 'cart') {
    /* 标记购物流程为普通商品 */
    $_SESSION['flow_type'] = CART_GENERAL_GOODS;
    /* 取得商品列表，计算合计 */
    $cart_goods = wx_get_cart_goods();
    // echo '<pre>';
    if ($cart_goods['goods_list']) {
        foreach ($cart_goods['goods_list'] as $key => $val) {
            $sql = "select s.suppliers_name,s.suppliers_sn,g.goods_number from " . $GLOBALS['ecs']->table('suppliers') . " s inner join " . $GLOBALS['ecs']->table('goods') . " g on g.suppliers_id = s.suppliers_id where g.goods_id='{$val['goods_id']}';";
            $info = $GLOBALS['db']->getRow($sql);
            
            // $cart_goods['goods_list'][$key]['supplier'] = $info['suppliers_name']."（编号：{$info['suppliers_sn']}）";
            $cart_goods['goods_list'][$key]['supplier'] = $info['suppliers_sn'];
            $cart_goods['goods_list'][$key]['goods_number_max'] = $info['goods_number'];
            if (! $info['goods_number']) {
                $sql = "select goods_number from " . $GLOBALS['ecs']->table('goods') . " where goods_id='{$val['goods_id']}';";
                $goods_info = $GLOBALS['db']->getRow($sql);
                $cart_goods['goods_list'][$key]['goods_number_max'] = $goods_info['goods_number'];
            }
        }
    }
    // echo '<pre>';
    // print_r($cart_goods['goods_list']);exit;
    $smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);
    
    // 购物车的描述的格式化
    $smarty->assign('shopping_money', sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']));
    $smarty->assign('market_price_desc', sprintf($_LANG['than_market_price'], $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']));
    
    // 显示收藏夹内的商品
    if ($_SESSION['user_id'] > 0) {
        require_once (ROOT_PATH . 'includes/lib_clips.php');
        $collection_goods = get_collection_goods($_SESSION['user_id']);
        $smarty->assign('collection_goods', $collection_goods);
    }
    
    /* 取得优惠活动 */
    $favourable_list = favourable_list($_SESSION['user_rank']);
    usort($favourable_list, 'cmp_favourable');
    
    $smarty->assign('favourable_list', $favourable_list);
    
    /* 计算折扣 */
    $discount = compute_discount();
    $smarty->assign('discount', $discount['discount']);
    $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
    $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));
    
    /* 增加是否在购物车里显示商品图 */
    $smarty->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);
    
    /* 增加是否在购物车里显示商品属性 */
    $smarty->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);
    
    /* 购物车中商品配件列表 */
    // 取得购物车中基本件ID
    $sql = "SELECT goods_id " . "FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "' " . "AND rec_type = '" . CART_GENERAL_GOODS . "' " . "AND is_gift = 0 " . "AND extension_code <> 'package_buy' " . "AND parent_id = 0 ";
    $parent_list = $GLOBALS['db']->getCol($sql);
    
    $fittings_list = get_goods_fittings($parent_list);
    $smarty->assign('fittings_list', $fittings_list);
    echo json_encode(array(
        'fittings_list' => $fittings_list,
        'goods_list' => $cart_goods['goods_list']
    ));
    exit();
} else if ($act == 'add_to_cart') {
    include_once ('../includes/cls_json.php');
    $_POST['goods'] = strip_tags(urldecode($_POST['goods']));
    $_POST['goods'] = json_str_iconv($_POST['goods']);
    
    if (! empty($_REQUEST['goods_id']) && empty($_POST['goods'])) {
        if (! is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0) {
            ecs_header("Location:./\n");
        }
        $goods_id = intval($_REQUEST['goods_id']);
        exit();
    }
    
    $result = array(
        'error' => 0,
        'message' => '',
        'content' => '',
        'goods_id' => ''
    );
    $json = new JSON();
    
    if (empty($_POST['goods'])) {
        $result['error'] = 1;
        die($json->encode($result));
    }
    
    $goods = $json->decode($_POST['goods']);
    
    /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
    if (empty($goods->spec) and empty($goods->quick)) {
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, " . "g.goods_attr_id, g.attr_value, g.attr_price " . 'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' . "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " . 'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';
        
        $res = $GLOBALS['db']->getAll($sql);
        
        if (! empty($res)) {
            $spe_arr = array();
            foreach ($res as $row) {
                $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $spe_arr[$row['attr_id']]['name'] = $row['attr_name'];
                $spe_arr[$row['attr_id']]['attr_id'] = $row['attr_id'];
                $spe_arr[$row['attr_id']]['values'][] = array(
                    'label' => $row['attr_value'],
                    'price' => $row['attr_price'],
                    'format_price' => price_format($row['attr_price'], false),
                    'id' => $row['goods_attr_id']
                );
            }
            $i = 0;
            $spe_array = array();
            foreach ($spe_arr as $row) {
                $spe_array[] = $row;
            }
            $result['error'] = ERR_NEED_SELECT_ATTR;
            $result['goods_id'] = $goods->goods_id;
            $result['parent'] = $goods->parent;
            $result['message'] = $spe_array;
            
            die($json->encode($result));
        }
    }
    
    /* 更新：如果是一步购物，先清空购物车 */
    if ($_CFG['one_step_buy'] == '1') {
        clear_cart();
    }
    
    /* 检查：商品数量是否合法 */
    if (!is_numeric($goods->number) || intval($goods->number) == 0) {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_number'];
    } /* 更新：购物车 */
    else {
        if (! empty($goods->spec)) {
            foreach ($goods->spec as $key => $val) {
                $goods->spec[$key] = intval($val);
            }
        }
        // 更新：添加到购物车
        if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent)) {
            if ($_CFG['cart_confirm'] > 2) {
                $result['message'] = '';
            } else {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }
            
            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_CFG['one_step_buy'];
        } else {
            $result['message'] = $err->last_message();
            $result['error'] = $err->error_no;
            $result['goods_id'] = stripslashes($goods->goods_id);
            if (is_array($goods->spec)) {
                $result['product_spec'] = implode(',', $goods->spec);
            } else {
                $result['product_spec'] = $goods->spec;
            }
        }
    }
    
    $result['confirm_type'] = ! empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
elseif ($act == 'cart_num'){
    $_POST['goods'] = str_replace('\\', '', $_POST['goods']);
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $data = json_decode($_POST['goods'],true);
    if (empty($data)){
        ajaxReturn(['code' => 0,'msg' => '更新数量失败']);
    }
    $goods_id = $data['goods_id'];
    $number = intval($data['number']);
    $sessid = SESS_ID;

    $row = $db->getRow("select * from {$ecs->table('cart')} where session_id='{$sessid}' and goods_id='{$goods_id}'");
    if (!empty($row)){
        
        if (empty($type)){
        if ($number == '-1' && $row['goods_number'] > 1){
            $new_number = $row['goods_number'] - 1;
        }elseif ($number == 1){
            $new_number = $row['goods_number'] + 1;
        }elseif ($number > 1){
            $new_number = $number;
        }else{
            $new_number = 1;
        }
        }else{
            $new_number = $number;
        }
        
        $goodsInfo = $db->getRow("select * from {$ecs->table('goods')} where goods_id='{$goods_id}'");
        if ($goodsInfo['goods_number'] < $new_number){
            ajaxReturn(['code' => -1,'num' => $row['goods_number'],'msg' => '超出商品库存量']);   
        }
        $db->autoExecute($ecs->table('cart'), ['goods_number' => $new_number],'UPDATE',"session_id='{$sessid}' and goods_id='{$goods_id}'");
        ajaxReturn(['code' => 1,'num' => $new_number,'msg' => '更新数量成功']);  
    }
} else if ($act == "user_info") {
    $user_id = $_SESSION['user_id'];
    $result = array();
    $result["info"] = wx_get_user_default($user_id);
    echo json_encode($result);
    exit();
} else if ($act == 'act_edit_address') {
    include_once (ROOT_PATH . 'includes/lib_transaction.php');
    include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
    $smarty->assign('lang', $_LANG);
    $user_id = $_SESSION['user_id'];
    $address = array(
        'user_id' => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country' => isset($_POST['country']) ? intval($_POST['country']) : 1,
        'province' => isset($_POST['province']) ? intval($_POST['province']) : 0,
        'city' => isset($_POST['city']) ? intval($_POST['city']) : 0,
        'district' => isset($_POST['district']) ? intval($_POST['district']) : 0,
        'address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '',
        'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '',
        'email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '',
        'tel' => isset($_POST['tel']) ? compile_str(make_semiangle(trim($_POST['tel']))) : '',
        'mobile' => isset($_POST['mobile']) ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
        'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '',
        'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
        'zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
        'is_default' => empty($_POST['is_default']) ? '' : intval($_POST['is_default'])
    );
    
    if ($address['is_default'] == 1) {
        $db->autoExecute($ecs->table('user_address'), array(
            'is_default' => 0
        ), 'UPDATE', 'user_id = ' . $address['user_id']);
    }
    $db->autoExecute($ecs->table('users'), array(
    		'address_id' => $address['address_id']
    ), 'UPDATE', 'user_id = ' . $address['user_id']);
    if (update_address($address)) {
        show_json_message('设置成功', $_LANG['address_list_lnk'], 'user.php?act=address_list');
    }
} else if ($act == "city_list") {
    // 取得国家列表，如果有收货人列表，取得省市区列表
    $shop_province_list = get_regions(1, $_CFG['shop_country']);
    foreach ($shop_province_list as $region_id => $consignee) {
        $city_list[$consignee['region_id']] = get_regions(2, $consignee['region_id']);
        foreach ($city_list[$consignee['region_id']] as $city_id => $city) {
            $district_list[$city["region_id"]] = get_regions(3, $city["region_id"]);
        }
    }
    echo json_encode(array(
        'shop_province_list' => $shop_province_list,
        'city_list' => $city_list,
        'district_list' => $district_list
    ));
    exit();
} else if ($act == "address_list") {
    /* 获得用户所有的收货人信息 */
    $consignee_list = wx_get_consignee_list($_SESSION['user_id']);
    echo json_encode(array(
        'consignee_list' => $consignee_list
    ));
    exit();
} elseif($act == "get_address_default"){
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') . " WHERE user_id = '$user_id' and is_default=1";
    if(count($GLOBALS['db']->getAll($sql)) <= 0){
        ajaxReturn(['code' => 0,'msg' => '请设置一个默认地址']);
    }
    ajaxReturn(['code' => 1,'msg' => 'ok']);
    return;
}elseif ($act == 'drop_consignee') {
    include_once ('includes/lib_transaction.php');
    
    $consignee_id = intval($_GET['id']);
    
    if (drop_consignee($consignee_id)) {
        show_json_message("删除地址成功!");
    } else {
        show_json_message($_LANG['del_address_false']);
    }
}else if ($act == 'mem_info') {
    $info = wx_get_user_set_default();
    echo json_encode(array(
        'info' => $info
    ));
    exit();
}else if($act == 'brand_list'){
    $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand');
    $brand_list = $db->getAll($sql);
    
    include 'WxCategory.class.php';
    $WxCategory = new WxCategory();
    $catelist = $WxCategory->get_child_tree();
    $ban_categories = $WxCategory->get_lists($catelist);
    $model = $ban_categories[218]['attrs']; //型号
    $tempmodel = [];
    foreach ($model as $v){
    	$tempmodel[] = $v;
    }
    echo json_encode(array(
        'brand_list' => $brand_list,
    		'model' => $tempmodel
    ));
    exit();
}elseif ($act == 'mingdanbrand'){
    $sql = "SELECT brand_id,brand_name FROM " . $GLOBALS['ecs']->table('brand');
    $brand_list = $db->getAll($sql);
    ajaxReturn(['code' => 1,'bandlist' => $brand_list]);
}
/* 会员充值和提现申请记录 */
elseif ($act == 'account_log')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
    $account_log = wx_get_account_log($user_id, 20, $page);
    echo json_encode($account_log);
    exit();
    
    
}/* 查看订单列表 */
elseif ($act == 'order_list')
{
    $orders = wx_get_user_orders($user_id, 100, 0);
    foreach ($orders as $key =>$order) {
        $goods_list = wx_order_goods($order["order_id"]);
        $orders[$key]["items"] = $goods_list;
    }
    
    echo json_encode($orders);
    exit();
}
//紧急采购列表
else if($act == 'purchase_list'){
    $pager  = ! empty($_GET['act']) ? $_GET['act'] : '0';
    $pager= $pager*20;
    $sql = "SELECT * FROM " .$ecs->table('user_purchase'). " WHERE user_id = '$user_id' order by purchase_id desc LIMIT {$pager}, 100";
    $purchases = $db->getAll($sql);
    echo json_encode($purchases);
    exit();
}elseif ($act == 'delete_goods'){
	$sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE user_id = '$user_id'";
	$suppliers = $db->getRow($sql);
	$suppliers_id = $suppliers['suppliers_id'];
	if (!$suppliers_id){
		ajaxReturn(['code' => 0,'msg' => '无店铺suppliers_id']);
	}
	$goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
	if ($goods_id <= 0) ajaxReturn(['code' => 0,'msg' => '删除商品失败']);
	$db->autoExecute($ecs->table('goods'), ['is_delete' => 1],'UPDATE',"suppliers_id={$suppliers_id} and goods_id=".$goods_id);
	if ($db->affected_rows()){
		ajaxReturn(['code' => 1,'msg' => '删除商品成功']);
	}
	ajaxReturn(['code' => 0,'msg' => '删除商品失败']);
}elseif ($act == 'update_goods'){
	$data = post();
	if (empty($data)) ajaxReturn(['code' => 0,'msg' => '更新商品失败']);
	$sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE user_id = '$user_id'";
	$suppliers = $db->getRow($sql);
	$suppliers_id = $suppliers['suppliers_id'];
	if (!$suppliers_id){
		ajaxReturn(['code' => 0,'msg' => '无店铺suppliers_id']);
	}
	$goods_id = isset($data['goods_id']) ? intval($data['goods_id']) : 0;
	$shop_price= isset($data['shop_price']) ? round(trim($data['shop_price']),2) : 0;
	$goods_number= isset($data['goods_number']) ? intval($data['goods_number']) : 0;
	if ($goods_id <= 0) ajaxReturn(['code' => 0,'msg' => '更新商品失败']);
	if ($shop_price < 0) ajaxReturn(['code' => 0,'msg' => '商品价格不正确']);
	if ($goods_number < 0) ajaxReturn(['code' => 0,'msg' => '商品库存不正确']);
	$db->autoExecute($ecs->table('goods'), ['goods_number' => $goods_number,'shop_price' => $shop_price],'UPDATE',"suppliers_id={$suppliers_id} and goods_id=".$goods_id);
	if ($db->affected_rows()){
		ajaxReturn(['code' => 1,'msg' => '更新商品成功']);
	}
	ajaxReturn(['code' => 0,'msg' => '更新商品失败']);
}
//获取用户库存列表
else if($act == 'stock_list'){
    $sql = "SELECT * FROM " . $ecs->table('suppliers') . " WHERE is_check=1 and user_id = '$user_id'";
    $suppliers = $db->getRow($sql);
    $suppliers_id = $suppliers['suppliers_id'];
    if (!$suppliers_id){
        ajaxReturn(['code' => 0,'msg' => '暂无开通店铺']);
    }
   $page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
   $page_size = 20;
   $count = $db->getOne("select count(*) as count from {$ecs->table('goods')} where is_delete = 0 AND suppliers_id = '{$suppliers_id}'");
   $totalPage = ceil($count / $page_size);
   $limit = ($page - 1) * $page_size . ','. $page_size;
   
   $sql = "SELECT * FROM " . $ecs->table('goods') . " WHERE is_delete=0 AND is_on_sale=1 AND suppliers_id = '{$suppliers_id}' order by goods_id asc limit $limit";
   $goods = $db->getAll($sql);
	if (!empty($goods)){
		foreach ($goods as $key =>$good) {
			$properties = get_goods_properties($good["goods_id"]);
			$goods[$key]["properties"] = $properties["lnk"];
			$pics = get_goods_gallery($good["goods_id"]);
			$goods[$key]["img"] =$pics;
		}
	}
    echo  json_encode(array(goods => $goods,
    		'totalPage' => $totalPage,
    		'count' => $count));
    exit();
}//紧急采购管理增加修改
elseif ($act == 'act_edit_purchase')
{
    
    $purchase = array(
        'user_id'    => $user_id,
        'purchase_id' => intval($_POST['purchase_id']),
        // 'country'    => isset($_POST['country'])   ? intval($_POST['country'])  : 1,
        'country'    => 1,
        'province'   => isset($_POST['province'])  ? intval($_POST['province']) : 0,
        'city'       => isset($_POST['city'])      ? intval($_POST['city'])     : 0,
        'district'   => isset($_POST['district'])  ? intval($_POST['district']) : 0,
        'name'     => isset($_POST['name'])   ? compile_str($_POST['name'])  : '',
        'brand'    => isset($_POST['brand'])  ? compile_str($_POST['brand']) : '',
        'cate'     => isset($_POST['cate'])  ? compile_str($_POST['cate']) : '',
        'pur_num'  => isset($_POST['pur_num'])      ? intval($_POST['pur_num'])     : 0,
        'address'  => isset($_POST['address'])   ? compile_str(trim($_POST['address']))    : '',
        'content'  => isset($_POST['content']) ? compile_str(trim($_POST['content']))  : '',
        'status'   => isset($_POST['status'])      ? intval($_POST['status'])     : 0,
        'ctime'   => date('Y-m-d H:i:s'),
    );
    
    $purchase_id = intval($purchase['purchase_id']);
    unset($purchase['purchase_id']);
    
    if (!$purchase['pur_num']){
        show_json_message('采购数量不正确', '我的采购', 'user.php?act=purchase_list&purchase_id='.$purchase_id, 'error');
        exit;
    }
    
   	if (empty($purchase['cate'])) {
   		ajaxReturn(['code' => 0,'msg' => '请选择一个型号']);
   	}
    
    if(!$purchase['province'] || !$purchase['city'] || !$purchase['district'] || !$purchase['name'] || !$purchase['pur_num'] || !$purchase['content']){
        show_json_message('必填项不能为空', '我的采购', 'user.php?act=purchase_list&purchase_id='.$purchase_id, 'error');
        exit;
    }
    
    if ($purchase_id > 0)
    {
        
        unset($purchase['ctime'],$purchase['user_id'],$purchase['status']);
        
        /* 更新指定记录 */
        $rs = $db->autoExecute($ecs->table('user_purchase'), $purchase, 'UPDATE', 'purchase_id = ' .$purchase_id . ' AND user_id = ' . $user_id);
        if ($rs)
        {
            show_json_message('修改需求成功', '我的采购', 'user.php?act=purchase_list');
        }
    }
    else
    {
        /* 插入一条新记录 */
        $rs = $db->autoExecute($ecs->table('user_purchase'), $purchase, 'INSERT');
        // $purchase_id = $db->insert_id();
        if ($rs)
        {
            show_json_message('添加需求成功', '我的采购', 'user.php?act=purchase_list');
        }
    }
}
//获取分类列表
else if($act == 'get_cat_info'){
	include 'WxCategory.class.php';
	$wxcategory = new WxCategory();
	$category_list = $wxcategory->get_child_tree();
	//获取品牌
	$brandList = $wxcategory->get_brand();
	//$cateAttr = $wxcategory->get_attr($category_list);
	$ban_categories = $wxcategory->get_lists($category_list);
	unset($ban_categories[230]); //出厂日期
	
	$data[] = array(
		'attr_name' => '分类',
		'type' => 'cate',
		'attrs' => $category_list
	);
	
	$data[] = array(
		'attr_name' => '品牌',
		'type' => 'brand',
		'attrs' => $brandList
	);
	
	ajaxReturn(array_merge($data,$ban_categories));
}else {

    echo json_encode(array(
        'act' => $act
    ));
    exit();
}
function wx_get_brands($children){
    /* 品牌筛选 */
    $sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num ".
        "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
        $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
        "WHERE g.brand_id = b.brand_id AND ($children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id), array_keys(wx_cat_list($cat_id, 0, false))))) . ") AND b.is_show = 1 " .
        " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
        "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";
        
        $brands = $GLOBALS['db']->getAll($sql);
        return $brands;
}
/**
 * 获得指定分类同级的所有分类以及该分类下的子分类
 *
 * @access  public
 * @param   integer     $cat_id     分类编号
 * @return  array
 */
function wx_child_tree($tree_id = 0)
{
    $three_arr = array();
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$tree_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $tree_id == 0)
    {
        $child_sql = 'SELECT cat_id, cat_name, parent_id, is_show ' .
            'FROM ' . $GLOBALS['ecs']->table('category') .
            "WHERE parent_id = '$tree_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
        $res = $GLOBALS['db']->getAll($child_sql);
        foreach ($res AS $row)
        {
            if ($row['is_show'])
                
                $three_arr[$row['cat_id']]['id']   = $row['cat_id'];
                $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $three_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
                
                if (isset($row['cat_id']) != NULL)
                {
                    $three_arr[$row['cat_id']]['cat_id'] = wx_child_tree($row['cat_id']);
                    
                }
        }
    }
    return $three_arr;
}
/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 * @return  string
 */
function wx_get_children($cat = 0)
{
    return 'g.cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(wx_cat_list($cat, 0, false)))));
}

function wx_get_categories_tree($cat_id = 0)
{
    if ($cat_id > 0)
    {
        $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
        $parent_id = $GLOBALS['db']->getOne($sql);
    }
    else
    {
        $parent_id = 0;
    }
    
    /*
     判断当前分类中全是是否是底级分类，
     如果是取出底级分类上级分类，
     如果不是取当前分类及其下的子分类
     */
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$parent_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0)
    {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT cat_id,cat_name ,parent_id,is_show ' .
            'FROM ' . $GLOBALS['ecs']->table('category') .
            "WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
        
        $res = $GLOBALS['db']->getAll($sql);
        
        foreach ($res AS $row)
        {
            if ($row['is_show'])
            {
                $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                if (isset($row['cat_id']) != NULL)
                {
                    $cat_arr[$row['cat_id']]['cat_id'] = wx_child_tree($row['cat_id']);
                }
            }
        }
    }
    if(isset($cat_arr))
    {
        return $cat_arr;
    }
}
/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  
 */
function  wx_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
{
    static $res = NULL;
    
    if ($res === NULL)
    {
        $data = read_static_cache('cat_pid_releate');
        if ($data === false)
        {
            $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children ".
                'FROM ' . $GLOBALS['ecs']->table('category') . " AS c ".
                "LEFT JOIN " . $GLOBALS['ecs']->table('category') . " AS s ON s.parent_id=c.cat_id ".
                "GROUP BY c.cat_id ".
                'ORDER BY c.parent_id, c.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);
            
            $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                " FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE is_delete = 0 AND is_on_sale = 1 " .
                " GROUP BY cat_id";
            $res2 = $GLOBALS['db']->getAll($sql);
            
            $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                " FROM " . $GLOBALS['ecs']->table('goods_cat') . " AS gc , " . $GLOBALS['ecs']->table('goods') . " AS g " .
                " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 " .
                " GROUP BY gc.cat_id";
            $res3 = $GLOBALS['db']->getAll($sql);
            
            $newres = array();
            foreach($res2 as $k=>$v)
            {
                $newres[$v['cat_id']] = $v['goods_num'];
                foreach($res3 as $ks=>$vs)
                {
                    if($v['cat_id'] == $vs['cat_id'])
                    {
                        $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                    }
                }
            }
            
            foreach($res as $k=>$v)
            {
                $res[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
            }
            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000)
            {
                write_static_cache('cat_pid_releate', $res);
            }
        }
        else
        {
            $res = $data;
        }
    }
    
    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }
    
    $options = cat_options($cat_id, $res); // 获得指定分类下的子分类的数组
    
    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false)
    {
        foreach ($options as $key => $val)
        {
            if ($val['level'] > $children_level)
            {
                unset($options[$key]);
            }
            else
            {
                if ($val['is_show'] == 0)
                {
                    unset($options[$key]);
                    if ($children_level > $val['level'])
                    {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                }
                else
                {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }
    
    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }
        
        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }
    
    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }
        
        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('category', array('cid' => $value['cat_id']), $value['cat_name']);
        }
        
        return $options;
    }
}
/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function wx_get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT cat_name, keywords, cat_desc, style, grade, filter_attr, parent_id FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}
function wx_get_filter_attr($cat,$children){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    if ($cat['filter_attr'] > 0)
    {
        $sql = "SELECT attr_id FROM " . $ecs->table('attribute') . " WHERE attr_id in ({$cat['filter_attr']}) ORDER BY sort_order ASC";
        $attrs = $db->getAll($sql);
        // echo '<pre>';
        // print_r($attrs);
        $cat['filter_attr'] = array_column($attrs, 'attr_id');
        // print_r($cat['filter_attr']);
        $cat_filter_attr = $cat['filter_attr'];
        // $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类、品牌的筛选属性
        $all_attr_list = array();
        
        foreach ($cat_filter_attr AS $key => $value)
        {
            $sql = "SELECT a.attr_name,a.sort_order FROM " . $ecs->table('attribute') . " AS a, " . $ecs->table('goods_attr') . " AS ga, " . $ecs->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value' ";
            if($brand){
                $sql .=" AND g.brand_id = {$brand}";
            }
            // $sql .= " ORDER BY a.sort_order ASC";
            if($temp_name = $db->getRow($sql))
            {
                $all_attr_list[$key]['filter_attr_name'] = $temp_name['attr_name'];
                $all_attr_list[$key]['sort_order'] = $temp_name['sort_order'];
                
                $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods') .
                " AS g" .
                " WHERE ($children OR " . get_extension_goods($children) . ") AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_value <> '' AND a.attr_value IS NOT NULL ";
                if($brand){
                    $sql .=" AND g.brand_id = {$brand}";
                }
                $sql .=" AND a.attr_id='$value' GROUP BY a.attr_value ORDER BY a.attr_value asc";
                
                $attr_list = $db->getAll($sql);
                
                $temp_arrt_url_arr = array();
                
                for ($i = 0; $i < count($cat_filter_attr); $i++)        //获取当前url中已选择属性的值，并保留在数组中
                {
                    $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                }
                
                $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                $all_attr_list[$key]['attr_list'][0]['attr_value'] = $_LANG['all_attribute'];
                $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);
                $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;
                
                foreach ($attr_list as $k => $v)
                {
                    $temp_key = $k + 1;
                    $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                    
                    $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                    $all_attr_list[$key]['attr_list'][$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);
                    
                    if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['goods_id'])
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                    }
                    else
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                    }
                }
            }
            
        }
        // $all_attr_list = array_sort($all_attr_list, 'sort_order', 'asc');
        // echo '<pre>';
        // print_r($all_attr_list);
        // exit;
        return $all_attr_list;
    }
    
}
/**
 * 取得订单商品
 * @param   int     $order_id   订单id
 * @return  array   订单商品数组
 */
function wx_order_goods($order_id)
{
    $sql = "SELECT rec_id, goods_id, goods_name, goods_sn, market_price, goods_number, " .
        "goods_price, goods_attr, is_real, parent_id, is_gift, " .
        "goods_price * goods_number AS subtotal, extension_code " .
        "FROM " . $GLOBALS['ecs']->table('order_goods') .
        " WHERE order_id = '$order_id'";
    
    $res = $GLOBALS['db']->query($sql);
    
    $http = 'https://'.$_SERVER['HTTP_HOST'].'/';
    
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['extension_code'] == 'package_buy'){
            $row['package_goods_list'] = wx_get_package_goods($row['goods_id']);
        }
        
        $imgRow = $GLOBALS['db']->getRow("select * from {$GLOBALS['ecs']->table('goods_gallery')} where goods_id={$row['goods_id']}");
        $row['img'] = $imgRow['thumb_url'] ? $http.$imgRow['thumb_url'] : '';
        $goods_list[] = $row;
    }
    
    //return $GLOBALS['db']->getAll($sql);
    return $goods_list;
}
/**
 * 获得指定礼包的商品
 *
 * @access  public
 * @param   integer $package_id
 * @return  array
 */
function wx_get_package_goods($package_id)
{
    $sql = "SELECT pg.goods_id, g.goods_name, pg.goods_number, p.goods_attr, p.product_number, p.product_id
            FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                LEFT JOIN " .$GLOBALS['ecs']->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON pg.product_id = p.product_id
            WHERE pg.package_id = '$package_id'";
    if ($package_id == 0)
    {
        $sql .= " AND pg.admin_id = '$_SESSION[admin_id]'";
    }
    $resource = $GLOBALS['db']->query($sql);
    if (!$resource)
    {
        return array();
    }
    
    $row = array();
    
    /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
    $good_product_str = '';
    while ($_row = $GLOBALS['db']->fetch_array($resource))
    {
        if ($_row['product_id'] > 0)
        {
            /* 取存商品id */
            $good_product_str .= ',' . $_row['goods_id'];
            
            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
        }
        else
        {
            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'];
        }
        
        //生成结果数组
        $row[] = $_row;
    }
    $good_product_str = trim($good_product_str, ',');
    
    /* 释放空间 */
    unset($resource, $_row, $sql);
    
    /* 取商品属性 */
    if ($good_product_str != '')
    {
        $sql = "SELECT goods_attr_id, attr_value FROM " .$GLOBALS['ecs']->table('goods_attr'). " WHERE goods_id IN ($good_product_str)";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);
        
        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }
    }
    
    /* 过滤货品 */
    $format[0] = '%s[%s]--[%d]';
    $format[1] = '%s--[%d]';
    foreach ($row as $key => $value)
    {
        if ($value['goods_attr'] != '')
        {
            $goods_attr_array = explode('|', $value['goods_attr']);
            
            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = $_goods_attr[$_attr];
            }
            
            $row[$key]['goods_name'] = sprintf($format[0], $value['goods_name'], implode('，', $goods_attr), $value['goods_number']);
        }
        else
        {
            $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['goods_number']);
        }
    }
    
    return $row;
}

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function wx_get_user_orders($user_id, $num = 100, $start = 0){
	
	$where = '';
	//未发货
	if (isset($_GET['shipping_status'])){
	    //and order_status=1
		$where = 'and pay_status=2 and shipping_status=0';
	}
	//未支付
	if (isset($_GET['not_pay'])){
		$where = 'and order_status=0 and shipping_status=0 and pay_status=0';
	}
	//已发货
	if (isset($_GET['order_status'])){
		$where = 'and pay_status=2 and shipping_status=1 and order_status=5';
	}
	//已完成
	if (isset($_GET['order_done'])){
		$where = 'and pay_status=2 and shipping_status=2 and order_status=5';
	}
	//退货
	if (isset($_GET['refund_goods'])){
		$where = 'and shipping_status=0 and order_status=4 and pay_status=0';
	}

    /* 取得订单列表 */
    $arr    = array();
    $sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time, " .
        "order_amount AS total_fee ".
        // "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
    " FROM " .$GLOBALS['ecs']->table('order_info') .
    " WHERE user_id = '$user_id' {$where} ORDER BY add_time DESC";
    
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        
        //$row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        //$row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];
        
        $arr[] = array('order_id'       => $row['order_id'],
            'order_sn'       => $row['order_sn'],
            'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
            'order_status'   => $row['order_status'],
            'shipping_status'   =>  $row['shipping_status'],
            'pay_status' => $row['pay_status'],
            //'total_fee'      => price_format($row['total_fee'], false),
            'total_fee'      => $row['total_fee'],
            'handler'        => $row['handler']);
    }
    
    return $arr;
}
/**
 * 查询会员余额的操作记录
 *
 * @access  public
 * @param   int     $user_id    会员ID
 * @param   int     $num        每页显示数量
 * @param   int     $start      开始显示的条数
 * @return  array
 */
function wx_get_account_log($user_id, $num, $start)
{
    $account_log = array();
    $sql = 'SELECT * FROM ' .$GLOBALS['ecs']->table('user_account').
    " WHERE user_id = '$user_id'" .
    " AND process_type " . db_create_in(array(SURPLUS_SAVE)) .
    " ORDER BY add_time DESC";//, SURPLUS_RETURN
    $res = $GLOBALS['db']->selectLimit($sql, $num, $start);
    
    if ($res)
    {
        while ($rows = $GLOBALS['db']->fetchRow($res))
        {
            $rows['add_time']         = local_date('Y-m-d H:i:s', $rows['add_time']);
            $rows['admin_note']       = nl2br(htmlspecialchars($rows['admin_note']));
            $rows['short_admin_note'] = ($rows['admin_note'] > '') ? sub_str($rows['admin_note'], 30) : 'N/A';
            $rows['user_note']        = nl2br(htmlspecialchars($rows['user_note']));
            $rows['short_user_note']  = ($rows['user_note'] > '') ? sub_str($rows['user_note'], 30) : 'N/A';
            $rows['pay_status']       = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirm'] : $GLOBALS['_LANG']['is_confirm'];
            $rows['amount']           = abs($rows['amount']);
            
            /* 会员的操作类型： 冲值，提现 */
            if ($rows['process_type'] == 0)
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
            }
            else
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
            }
            
            /* 支付方式的ID */
            $sql = 'SELECT pay_id FROM ' .$GLOBALS['ecs']->table('payment').
            " WHERE pay_name = '$rows[payment]' AND enabled = 1";
            $pid = $GLOBALS['db']->getOne($sql);
            
            /* 如果是预付款而且还没有付款, 允许付款 */
            if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0))
            {
                $rows['handle'] = '<a href="user.php?act=pay&id='.$rows['id'].'&pid='.$pid.'">'.$GLOBALS['_LANG']['pay'].'</a>';
            }
            
            $account_log[] = $rows;
        }
        
        return $account_log;
    }
    else
    {
        return false;
    }
}
/**
 * 取得收货人地址列表
 *
 * @param int $user_id
 *            用户编号
 * @return array
 */
function wx_get_consignee_list($user_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') . " WHERE user_id = '$user_id'";
    
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 获取用户中心默认页面所需的数据
 *
 * @access public
 * @param int $user_id
 *            用户ID
 *            
 * @return array $info 默认页面所需资料数组
 */
function wx_get_user_default($user_id)
{
    $user_bonus = get_user_bonus();
    
    $sql = "SELECT pay_points, user_money,user_id,user_pic, credit_line, last_login, is_validated FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
    $row = $GLOBALS['db']->getRow($sql);
    $info = array();
    $info['username'] = stripslashes($_SESSION['user_name']);
    $info['shop_name'] = $GLOBALS['_CFG']['shop_name'];
    $info['integral'] = $row['pay_points'];
    $info['gender'] = $row['user_id'];
    $info['avatarUrl'] = $row['user_pic'];
    $info['user_money'] = $row['user_money'];
    /* 增加是否开启会员邮件验证开关 */
    $info['is_validate'] = ($GLOBALS['_CFG']['member_email_validate'] && ! $row['is_validated']) ? 0 : 1;
    $info['credit_line'] = $row['credit_line'];
    $info['formated_credit_line'] = price_format($info['credit_line'], false);
    
    // 如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
    $last_time = ! isset($_SESSION['last_time']) ? $row['last_login'] : $_SESSION['last_time'];
    
    if ($last_time == 0) {
        $_SESSION['last_time'] = $last_time = gmtime();
    }
    
    $info['last_time'] = local_date($GLOBALS['_CFG']['time_format'], $last_time);
    $info['surplus'] = $row['user_money'];
    $info['bonus'] = sprintf($GLOBALS['_LANG']['user_bonus_info'], $user_bonus['bonus_count'], price_format($user_bonus['bonus_value'], false));
    
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id = '" . $user_id . "' AND add_time > '" . local_strtotime('-1 months') . "'";
    $info['order_count'] = $GLOBALS['db']->getOne($sql);
    
    include_once (ROOT_PATH . 'includes/lib_order.php');
    $sql = "SELECT order_id, order_sn " . " FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE user_id = '" . $user_id . "' AND shipping_time > '" . $last_time . "'" . order_query_sql('shipped');
    $info['shipped_order'] = $GLOBALS['db']->getAll($sql);
    
    return $info;
}
/**
 * 获取用户设置默认页面所需的数据
 *
 * @access public
 * @param int $user_id
 *            用户ID
 *
 * @return array $info 默认页面所需资料数组
 */
function wx_get_user_set_default($user_id)
{
    $user_bonus = get_user_bonus();
    
    $sql = "SELECT user_name,nickname,wexin,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
    $row = $GLOBALS['db']->getRow($sql);
    $info = array();
    $info['username'] = stripslashes($_SESSION['user_name']);
    $info['wexin'] = $row['wexin'];
    $info['mobile_phone'] = $row['mobile_phone'];
    $info['nickname'] = $row['nickname'];
    
    return $info;
}
/**
 * 获得购物车中的商品
 *
 * @access public
 * @return array
 */
function wx_get_cart_goods($rec_type = CART_GENERAL_GOODS)
{
    /* 初始化 */
    $goods_list = array();
    $total = array(
        'goods_price' => 0, // 本店售价合计（有格式）
        'market_price' => 0, // 市场售价合计（有格式）
        'saving' => 0, // 节省金额（有格式）
        'save_rate' => 0, // 节省百分比
        'goods_amount' => 0 // 本店售价合计（无格式）
    );
    
    /* 循环、统计 */
    $sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " . " FROM " . $GLOBALS['ecs']->table('cart') . " " . " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . $rec_type . "'" . " ORDER BY pid, parent_id";
    $res = $GLOBALS['db']->query($sql);
    
    /* 用于统计购物车中实体商品和虚拟商品的个数 */
    $virtual_goods_count = 0;
    $real_goods_count = 0;
    
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        
        $row['subtotal'] = price_format($row['goods_price'] * $row['goods_number'], false);
        
        /* 统计实体商品和虚拟商品的个数 */
        if ($row['is_real']) {
            $real_goods_count ++;
        } else {
            $virtual_goods_count ++;
        }
        
        /* 查询规格 */
        if (trim($row['goods_attr']) != '') {
            $row['goods_attr'] = addslashes($row['goods_attr']);
            $sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id " . db_create_in($row['goods_attr']);
            $attr_list = $GLOBALS['db']->getCol($sql);
            foreach ($attr_list as $attr) {
                $row['goods_name'] .= ' [' . $attr . '] ';
            }
        }
        /* 增加是否在购物车里显示商品图 */
        if (($GLOBALS['_CFG']['show_goods_in_cart'] == "2" || $GLOBALS['_CFG']['show_goods_in_cart'] == "3") && $row['extension_code'] != 'package_buy') {
            $goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");
            $row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
        }
        if ($row['extension_code'] == 'package_buy') {
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }
        $goods_img = $GLOBALS['db']->getOne("SELECT `goods_img` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");
        $row['goods_img'] = get_image_path($row['goods_id'], $goods_img, true);
        $goods_list[] = $row;
    }
    $total['goods_amount'] = $total['goods_price'];
    $total['saving'] = price_format($total['market_price'] - $total['goods_price'], false);
    if ($total['market_price'] > 0) {
        $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']) . '%' : 0;
    }
    $total['goods_price'] = price_format($total['goods_price'], false);
    $total['market_price'] = price_format($total['market_price'], false);
    $total['real_goods_count'] = $real_goods_count;
    $total['virtual_goods_count'] = $virtual_goods_count;
    
    return array(
        'goods_list' => $goods_list,
        'total' => $total
    );
}

/**
 * 取得某用户等级当前时间可以享受的优惠活动
 *
 * @param int $user_rank
 *            用户等级id，0表示非会员
 * @return array
 */
function favourable_list($user_rank)
{
    /* 购物车中已有的优惠活动及数量 */
    $used_list = cart_favourable();
    
    /* 当前用户可享受的优惠活动 */
    $favourable_list = array();
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();
    $sql = "SELECT * " . "FROM " . $GLOBALS['ecs']->table('favourable_activity') . " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" . " AND start_time <= '$now' AND end_time >= '$now'" . " AND act_type = '" . FAT_GOODS . "'" . " ORDER BY sort_order";
    $res = $GLOBALS['db']->query($sql);
    while ($favourable = $GLOBALS['db']->fetchRow($res)) {
        $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
        $favourable['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
        $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
        $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
        $favourable['gift'] = unserialize($favourable['gift']);
        
        foreach ($favourable['gift'] as $key => $value) {
            $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = " . $value['id'];
            $is_sale = $GLOBALS['db']->getOne($sql);
            if (! $is_sale) {
                unset($favourable['gift'][$key]);
            }
        }
        
        $favourable['act_range_desc'] = act_range_desc($favourable);
        $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);
        
        /* 是否能享受 */
        $favourable['available'] = favourable_available($favourable);
        if ($favourable['available']) {
            /* 是否尚未享受 */
            $favourable['available'] = ! favourable_used($favourable, $used_list);
        }
        
        $favourable_list[] = $favourable;
    }
    
    return $favourable_list;
}

/**
 * 取得购物车中已有的优惠活动及数量
 *
 * @return array
 */
function cart_favourable()
{
    $list = array();
    $sql = "SELECT is_gift, COUNT(*) AS num " . "FROM " . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . SESS_ID . "'" . " AND rec_type = '" . CART_GENERAL_GOODS . "'" . " AND is_gift > 0" . " GROUP BY is_gift";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $list[$row['is_gift']] = $row['num'];
    }
    
    return $list;
}

/**
 * 显示一个json提示信息
 *
 * @access public
 * @param string $content
 * @param string $link
 * @param string $href
 * @param string $type
 *            信息类型：warning, error, info
 * @param string $auto_redirect
 *            是否自动跳转
 * @return void
 */
function show_json_message($content, $links = '', $hrefs = '', $type = 'info', $auto_redirect = true){
    echo json_encode(array(
        'show_json_message' => 'show_json_message',
        'content' => $content,
        'links' => $links,
        'hrefs' => $hrefs,
        'type' => $type,
        'auto_redirect' => $auto_redirect
    ));
    exit();
}

function ajaxReturn($data){
	header('Content-Type:application/json;charset=utf-8');
	echo json_encode($data);
	exit;
}

function checkmobile($mobilephone) {
    $mobilephone = trim($mobilephone);
    if(preg_match("/^13[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/",$mobilephone)){
        return  $mobilephone;
    } else {
        return false;
    }
}

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

?>