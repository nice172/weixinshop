<?php

include_once('../includes/lib_clips.php');
include_once('../includes/lib_payment.php');

/* 取得购物类型 */
$flow_type = CART_GENERAL_GOODS;

/* 检查购物车中是否有商品 */
$sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
" WHERE session_id = '" . SESS_ID . "' " .
"AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
if ($db->getOne($sql) == 0){
    ajaxReturn(['code' => 0,'msg' => $_LANG['no_goods_in_cart']]);
}

/* 检查商品库存 */
/* 如果使用库存，且下订单时减库存，则减少库存 */
if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE){
    $cart_goods_stock = get_cart_goods();
    $_cart_goods_stock = array();
    foreach ($cart_goods_stock['goods_list'] as $value){
        $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
    }
    flow_cart_stock($_cart_goods_stock);
    unset($cart_goods_stock, $_cart_goods_stock);
}

/*
 * 检查用户是否已经登录
 * 如果用户已经登录了则检查是否有默认的收货地址
 * 如果没有登录则跳转到登录和注册页面
 */
if (empty($_SESSION['user_id'])){
    /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
    ajaxReturn(['code' => 0,'msg' => '请登录用户']);
}

$consignee = get_consignee($_SESSION['user_id']);

/* 检查收货人信息是否完整 */
if (!check_consignee_info($consignee, $flow_type)){
    /* 如果不完整则转向到收货人信息填写界面 */
    ajaxReturn(['code' => 0,'msg' => '检查收货人信息是否完整']);
    exit;
}

$_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
$_POST['card_message'] = isset($_POST['card_message']) ? compile_str($_POST['card_message']) : '';
$_POST['inv_type'] = !empty($_POST['inv_type']) ? compile_str($_POST['inv_type']) : '';
$_POST['inv_payee'] = isset($_POST['inv_payee']) ? compile_str($_POST['inv_payee']) : '';
$_POST['inv_content'] = isset($_POST['inv_content']) ? compile_str($_POST['inv_content']) : '';
$_POST['postscript'] = isset($_POST['postscript']) ? compile_str($_POST['postscript']) : '';
$_POST['need_ban'] = isset($_POST['need_ban']) ? intval($_POST['need_ban']) : '';
$_POST['ban_text'] = isset($_POST['ban_text']) ? compile_str($_POST['ban_text']) : '';
$ban_file = "";
if($_POST['need_ban'] == 1){
    $ban_file = '';
    if ((isset($_FILES['ban_file']['error']) && $_FILES['ban_file']['error'] == 0) || (!isset($_FILES['ban_file']['error']) && isset($_FILES['ban_file']['tmp_name']) && $_FILES['ban_file']['tmp_name'] != 'none'))
    {
        // 复制文件
        $res = upload_file($_FILES['ban_file']);
        if ($res != false)
        {
            $ban_file = "/data/".$res;
        }
    }
}

$order = array(
    'shipping_id'     => 1,
    'pay_id'          => 5, //5微信支付
    'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
    'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,
    'card_message'    => trim($_POST['card_message']),
    'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
    'integral'        => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
    'bonus_id'        => isset($_POST['bonus']) ? intval($_POST['bonus']) : 0,
    'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
    'inv_type'        => $_POST['inv_type'],
    'inv_payee'       => trim($_POST['inv_payee']),
    'inv_content'     => $_POST['inv_content'],
    'postscript'      => trim($_POST['postscript']),
    'ban_file'        => trim($ban_file),
    'ban_text'        => trim($_POST['ban_text']),
    'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
    'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
    'user_id'         => $_SESSION['user_id'],
    'add_time'        => gmtime(),
    'order_status'    => OS_UNCONFIRMED,
    'shipping_status' => SS_UNSHIPPED,
    'pay_status'      => PS_UNPAYED,
    'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']))
);

if($order['inv_payee']){
    //发票信息
    $sql = "SELECT * FROM " .$ecs->table('user_ticket'). " WHERE user_id = '".$_SESSION['user_id']."' AND ticket_id=".$order['inv_payee'];
    $ticket_info = $db->getRow($sql);
    if($ticket_info){
        $sql = "SELECT region_name FROM " . $ecs->table('region')." WHERE region_id=".$ticket_info['province'];
        $province = $db->getRow($sql);
        
        $sql = "SELECT region_name FROM " . $ecs->table('region')." WHERE region_id=".$ticket_info['city'];
        $city = $db->getRow($sql);
        
        $sql = "SELECT region_name FROM " . $ecs->table('region')." WHERE region_id=".$ticket_info['district'];
        $district = $db->getRow($sql);
        
        $order['inv_payee'] = "发票抬头：{$ticket_info['company_name']}<br>";
        $order['inv_payee'] .= "纳税人识别号：{$ticket_info['company_sn']}<br>";
        $order['inv_payee'] .= "注册地址：{$province['region_name']}，{$city['region_name']}，{$district['region_name']}，{$ticket_info['company_address']}<br>";
        $order['inv_payee'] .= "注册电话：{$ticket_info['company_phone']}<br>";
        $order['inv_payee'] .= "开户银行：{$ticket_info['company_bank']}<br>";
        $order['inv_payee'] .= "银行账户：{$ticket_info['company_bankcard']}";
    }
    $smarty->assign('ticket_list', $ticket_list);
}
// print_r($ticket_info);
// print_r($province);
// print_r($order['inv_payee']);
// exit;
/* 扩展信息 */
if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS)
{
    $order['extension_code'] = $_SESSION['extension_code'];
    $order['extension_id'] = $_SESSION['extension_id'];
}
else
{
    $order['extension_code'] = '';
    $order['extension_id'] = 0;
}

/* 检查积分余额是否合法 */
$user_id = $_SESSION['user_id'];
if ($user_id > 0)
{
    $user_info = user_info($user_id);
    
    $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
    if ($order['surplus'] < 0)
    {
        $order['surplus'] = 0;
    }
    
    // 查询用户有多少积分
    $flow_points = flow_available_points();  // 该订单允许使用的积分
    $user_points = $user_info['pay_points']; // 用户的积分总数
    
    $order['integral'] = min($order['integral'], $user_points, $flow_points);
    if ($order['integral'] < 0)
    {
        $order['integral'] = 0;
    }
}
else
{
    $order['surplus']  = 0;
    $order['integral'] = 0;
}

/* 检查红包是否存在 */
if ($order['bonus_id'] > 0)
{
    $bonus = bonus_info($order['bonus_id']);
    
    if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type))
    {
        $order['bonus_id'] = 0;
    }
}
elseif (isset($_POST['bonus_sn']))
{
    $bonus_sn = trim($_POST['bonus_sn']);
    $bonus = bonus_info(0, $bonus_sn);
    $now = gmtime();
    if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type) || $now > $bonus['use_end_date'])
    {
    }
    else
    {
        if ($user_id > 0)
        {
            $sql = "UPDATE " . $ecs->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
            $db->query($sql);
        }
        $order['bonus_id'] = $bonus['bonus_id'];
        $order['bonus_sn'] = $bonus_sn;
    }
}

/* 订单中的商品 */
$cart_goods = cart_goods($flow_type);

if (empty($cart_goods))
{
    ajaxReturn(['code' => 0,'msg' => $_LANG['no_goods_in_cart']]);
}

/* 检查商品总额是否达到最低限购金额 */
if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $_CFG['min_goods_amount'])
{
    // show_message(sprintf($_LANG['goods_amount_not_enough'], price_format($_CFG['min_goods_amount'], false)));
}

/* 收货人信息 */
foreach ($consignee as $key => $value)
{
    $order[$key] = addslashes($value);
}

/* 判断是不是实体商品 */
foreach ($cart_goods AS $val)
{
    /* 统计实体商品的个数 */
    if ($val['is_real'])
    {
        $is_real_good=1;
    }
}
if(isset($is_real_good))
{
    $sql="SELECT shipping_id FROM " . $ecs->table('shipping') . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1";
    if(!$db->getOne($sql))
    {
        ajaxReturn(['code' => 0,'msg' => $_LANG['flow_no_shipping']]);
    }
}
/* 订单中的总额 */
$total = order_fee($order, $cart_goods, $consignee);
// exit;
$order['bonus']        = $total['bonus'];
$order['goods_amount'] = $total['goods_price'];
$order['discount']     = $total['discount'];
$order['surplus']      = $total['surplus'];
$order['tax']          = $total['tax'];

// 购物车中的商品能享受红包支付的总额
$discount_amout = compute_discount_amount();
// 红包和积分最多能支付的金额为商品总额
$temp_amout = $order['goods_amount'] - $discount_amout;
if ($temp_amout <= 0)
{
    $order['bonus_id'] = 0;
}

/* 配送方式 */
if ($order['shipping_id'] > 0)
{
    $shipping = shipping_info($order['shipping_id']);
    $order['shipping_name'] = addslashes($shipping['shipping_name']);
}
$order['shipping_fee'] = $total['shipping_fee'];
$order['insure_fee']   = $total['shipping_insure'];

/* 支付方式 */
if ($order['pay_id'] > 0)
{
    $payment = payment_info($order['pay_id']);
    $order['pay_name'] = addslashes($payment['pay_name']);
}
$order['pay_fee'] = $total['pay_fee'];
$order['cod_fee'] = $total['cod_fee'];

/* 商品包装 */
if ($order['pack_id'] > 0)
{
    $pack               = pack_info($order['pack_id']);
    $order['pack_name'] = addslashes($pack['pack_name']);
}
$order['pack_fee'] = $total['pack_fee'];

/* 祝福贺卡 */
if ($order['card_id'] > 0)
{
    $card               = card_info($order['card_id']);
    $order['card_name'] = addslashes($card['card_name']);
}
$order['card_fee']      = $total['card_fee'];

$order['order_amount']  = number_format($total['amount'], 2, '.', '');


/* 如果全部使用余额支付，检查余额是否足够 */
if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
{
    if($order['surplus'] >0) //余额支付里如果输入了一个金额
    {
        $order['order_amount'] = $order['order_amount'] + $order['surplus'];
        $order['surplus'] = 0;
    }
    if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
    {
        ajaxReturn(['code' => 0,'msg' => $_LANG['balance_not_enough']]);
    }
    else
    {
        $order['surplus'] = $order['order_amount'];
        $order['order_amount'] = 0;
    }
}

/* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
if ($order['order_amount'] <= 0)
{
    $order['order_status'] = OS_CONFIRMED;
    $order['confirm_time'] = gmtime();
    $order['pay_status']   = PS_PAYED;
    $order['pay_time']     = gmtime();
    $order['order_amount'] = 0;
}

// print_r($order['order_amount']);
// print_r($order['quick_ship_fee']);
// exit;
// $order['order_amount'] += $order['quick_ship_fee'];
if($order['order_amount'] < 100){
    $order['order_amount'] = sprintf("%.2f", "100.00");
}
$total['amount_formated'] = "￥".sprintf("%.2f", $order['order_amount'])."元";

$order['integral_money']   = $total['integral_money'];
$order['integral']         = $total['integral'];

if ($order['extension_code'] == 'exchange_goods')
{
    $order['integral_money']   = 0;
    $order['integral']         = $total['exchange_integral'];
}

$order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
$order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';

/* 记录扩展信息 */
if ($flow_type != CART_GENERAL_GOODS)
{
    $order['extension_code'] = $_SESSION['extension_code'];
    $order['extension_id'] = $_SESSION['extension_id'];
}

$affiliate = unserialize($_CFG['affiliate']);
if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
{
    //推荐订单分成
    $parent_id = get_affiliate();
    if($user_id == $parent_id)
    {
        $parent_id = 0;
    }
}
elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
{
    //推荐注册分成
    $parent_id = 0;
}
else
{
    //分成功能关闭
    $parent_id = 0;
}
$order['parent_id'] = $parent_id;

/* 插入订单表 */
$error_no = 0;
do
{
    $order['order_sn'] = get_order_sn(); //获取新订单号
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');
    
    $error_no = $GLOBALS['db']->errno();
    
    if ($error_no > 0 && $error_no != 1062)
    {
        die($GLOBALS['db']->errorMsg());
    }
}
while ($error_no == 1062); //如果是订单号重复则重新提交数据

$new_order_id = $db->insert_id();
$order['order_id'] = $new_order_id;

/* 插入订单商品 */
$sql = "SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
    "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id".
    " FROM " .$ecs->table('cart') .
    " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
$res = $GLOBALS['db']->getAll($sql);
if($res){
    foreach ($res as $val) {
        $goods_id = $val['goods_id'];
        $goods_name = $val['goods_name'];
        $goods_sn = $val['goods_sn'];
        $product_id = $val['product_id'];
        $goods_number = $val['goods_number'];
        $market_price = $val['market_price'];
        $goods_price = $val['goods_price'];
        $goods_attr = $val['goods_attr'];
        $is_real = $val['is_real'];
        $extension_code = $val['extension_code'];
        $parent_id = $val['parent_id'];
        $is_gift = $val['is_gift'];
        $goods_attr_id = $val['goods_attr_id'];
        
        $sql = "SELECT ori_price, supply_price, suppliers_id FROM " .$ecs->table('goods') ." WHERE goods_id = '".$goods_id."'";
        $info = $GLOBALS['db']->getRow($sql);
        $ori_price = $info['ori_price'];
        $supply_price = $info['supply_price'];
        $suppliers_id = $info['suppliers_id'];
        
        $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
            "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
            "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, ori_price, supply_price, suppliers_id) ".
            " VALUES('{$new_order_id}', '{$goods_id}', '{$goods_name}', '{$goods_sn}', '{$product_id}', '{$goods_number}', '{$market_price}', ".
            "'{$goods_price}', '{$goods_attr}', '{$is_real}', '{$extension_code}', '{$parent_id}', '{$is_gift}', '{$goods_attr_id}', '{$ori_price}', '{$supply_price}', '{$suppliers_id}')";
        // echo $sql.PHP_EOL;
        $db->query($sql);
    }
}

// $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
//     "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
//     "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, ori_price, supply_price, suppliers_id) ".
// " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, ".
//     "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, ori_price, supply_price, suppliers_id".
// " FROM " .$ecs->table('cart') .
// " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
// $db->query($sql);


/* 修改拍卖活动状态 */
if ($order['extension_code']=='auction')
{
    $sql = "UPDATE ". $ecs->table('goods_activity') ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
    $db->query($sql);
}

/* 处理余额、积分、红包 */
if ($order['user_id'] > 0 && $order['surplus'] > 0)
{
    log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($_LANG['pay_order'], $order['order_sn']));
}
if ($order['user_id'] > 0 && $order['integral'] > 0)
{
    log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($_LANG['pay_order'], $order['order_sn']));
}


if ($order['bonus_id'] > 0 && $temp_amout > 0)
{
    use_bonus($order['bonus_id'], $new_order_id);
}

/* 如果使用库存，且下订单时减库存，则减少库存 */
if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
{
    change_order_goods_storage($order['order_id'], true, SDT_PLACE);
}

/* 给商家发邮件 */
/* 增加是否给客服发送邮件选项 */
if ($_CFG['send_service_email'] && $_CFG['service_email'] != '')
{
    $tpl = get_mail_template('remind_of_new_order');
    $smarty->assign('order', $order);
    $smarty->assign('goods_list', $cart_goods);
    $smarty->assign('shop_name', $_CFG['shop_name']);
    $smarty->assign('send_date', date($_CFG['time_format']));
    $content = $smarty->fetch('str:' . $tpl['template_content']);
    send_mail($_CFG['shop_name'], $_CFG['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
}

/* 如果需要，发短信 */
if ($_CFG['sms_order_placed'] == '1' && $_CFG['sms_shop_mobile'] != '')
{
    include_once('../includes/cls_sms.php');
    $sms = new sms();
    $msg = $order['pay_status'] == PS_UNPAYED ?
    $_LANG['order_placed_sms'] : $_LANG['order_placed_sms'] . '[' . $_LANG['sms_paid'] . ']';
    $sms->send($_CFG['sms_shop_mobile'], sprintf($msg, $order['consignee'], $order['tel']),'', 13,1);
}

/* 如果订单金额为0 处理虚拟卡 */
if ($order['order_amount'] <= 0)
{
    $sql = "SELECT goods_id, goods_name, goods_number AS num FROM ".
        $GLOBALS['ecs']->table('cart') .
        " WHERE is_real = 0 AND extension_code = 'virtual_card'".
        " AND session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
        
        $res = $GLOBALS['db']->getAll($sql);
        
        $virtual_goods = array();
        foreach ($res AS $row)
        {
            $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
        }
        
        if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
        {
            /* 虚拟卡发货 */
            if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
            {
                /* 如果没有实体商品，修改发货状态，送积分和红包 */
                $sql = "SELECT COUNT(*)" .
                    " FROM " . $ecs->table('order_goods') .
                    " WHERE order_id = '$order[order_id]' " .
                    " AND is_real = 1";
                if ($db->getOne($sql) <= 0)
                {
                    /* 修改订单状态 */
                    update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));
                    
                    /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                    if ($order['user_id'] > 0)
                    {
                        /* 取得用户信息 */
                        $user = user_info($order['user_id']);
                        
                        /* 计算并发放积分 */
                        $integral = integral_to_give($order);
                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));
                        
                        /* 发放红包 */
                        send_order_bonus($order['order_id']);
                    }
                }
            }
        }
        
}

/* 清空购物车 */
clear_cart($flow_type);
/* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
clear_all_files();

/* 插入支付日志 */
$order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

/* 取得支付信息，生成支付代码 */
if ($order['order_amount'] > 0){
    if($order['pay_id'] == 5){ //微信支付
        $arr['orderId'] = $order['order_id'];
        $arr['orderFee'] = $order['order_amount'];
        $orderJson = base64_encode(json_encode($arr));
        
        ajaxReturn($order);
        
        exit;
        $pay_online = '<div style="text-align:center"><input type="button" class="orderJson" data-id= "'.$orderJson.'" value="立即支付" /></div>';
    }else{
        
        $payment = payment_info($order['pay_id']);
        
        include_once('../includes/modules/payment/' . $payment['pay_code'] . '.php');
        
        $pay_obj    = new $payment['pay_code'];
        
        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
        
        $order['pay_desc'] = $payment['pay_desc'];
    }
    
    $smarty->assign('pay_online', $pay_online);
}
if(!empty($order['shipping_name']))
{
    $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
}

/* 订单信息 */
$smarty->assign('order',      $order);
$smarty->assign('total',      $total);
$smarty->assign('goods_list', $cart_goods);
$smarty->assign('order_submit_back', sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center'])); // 返回提示

user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
unset($_SESSION['flow_order']);
unset($_SESSION['direct_shopping']);


/**
 * 检查订单中商品库存
 *
 * @access  public
 * @param   array   $arr
 *
 * @return  void
 */
function flow_cart_stock($arr)
{
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0 || !is_numeric($key))
        {
            continue;
        }
        
        $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
        " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);
        
        $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
            "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
            $GLOBALS['ecs']->table('cart'). " AS c ".
            "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            $row = $GLOBALS['db']->getRow($sql);
            
            //系统启用了库存，检查输入的商品数量是否有效
            if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
            {
                if ($row['goods_number'] < $val)
                {
                    ajaxReturn(['code' => 0 ,'msg' => sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                        $row['goods_number'], $row['goods_number'])]);
                    exit;
                }
                
                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id']))
                {
                    $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                    $product_number = $GLOBALS['db']->getOne($sql);
                    if ($product_number < $val)
                    {
                        ajaxReturn(['code' => 0 ,'msg' =>sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                            $row['goods_number'], $row['goods_number'])]);
                        exit;
                    }
                }
            }
            elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
            {
                if (judge_package_stock($goods['goods_id'], $val))
                {
                    ajaxReturn(['code' => 0,'msg' => $GLOBALS['_LANG']['package_stock_insufficiency']]);
                    exit;
                }
            }
    }
    
}

/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
function flow_available_points()
{
    $sql = "SELECT SUM(g.integral * c.goods_number) ".
        "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
        "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
        "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
    
    $val = intval($GLOBALS['db']->getOne($sql));
    
    return integral_of_value($val);
}