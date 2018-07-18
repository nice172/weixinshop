<?php


/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */
$cat_list = get_categories_tree();

$cate_ids_arr = array();
foreach ($cat_list as $p_cate_id => $val) {
    $tmp = array_keys($val['cat_id']);
    $tmp[] = $p_cate_id;
    $cate_ids_arr[$p_cate_id] = $tmp;
}

/* 获得请求的分类 ID */
if (isset($_REQUEST['id']) && $_REQUEST['id'])
{
    $cat_id = intval($_REQUEST['id']);
}
elseif (isset($_REQUEST['category']) && $_REQUEST['category'])
{
    $cat_id = intval($_REQUEST['category']);
}
else
{      
    $tmparr = current($cat_list);
    $cat_id = !empty($tmparr) ? $tmparr['id'] : '';
}
if(!$cat_id){
    ajaxReturn(['code' => 0,'msg' => '搜索商品失败']);
}


/* 初始化分页信息 */
$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
$price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
$price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
$filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';
$keywords = isset($_REQUEST['keywords']) ? htmlspecialchars(trim($_REQUEST['keywords'])) : '';


$filter_attr_str = trim(urldecode($filter_attr_str));
$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
$display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
$display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 页面的缓存ID */
$cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
    $_CFG['lang'] .'-'. $brand. '-' . $price_max . '-' .$price_min . '-' . $filter_attr_str . '-' . $keywords));

$key_no_search = isset($_REQUEST['key_no_search']) ? intval($_REQUEST['key_no_search'])  : 0;
$contact = isset($_REQUEST['contact']) ? trim($_REQUEST['contact'])  : '';
$mobile = isset($_REQUEST['mobile']) ? trim($_REQUEST['mobile'])  : '';
$serach_keywords = isset($_REQUEST['serach_keywords']) ? htmlspecialchars(trim($_REQUEST['serach_keywords'])) : '';
if($serach_keywords && $key_no_search > 0){
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_nosearch') . " (" .
                "s_key, contact, mobile, is_in_owner, ctime) VALUES('".$serach_keywords."', '".$contact."', '".$mobile."', '".$key_no_search."', '".date('Y-m-d H:i:s')."')";
    $GLOBALS['db']->query($sql);
    $search_id = $GLOBALS['db']->insert_id();

    if($key_no_search == 2 && $search_id){
        $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_owner = 4  AND is_delete = 0 AND is_on_sale = 1 AND goods_name LIKE '%".$keywords."%'";
        $res = $GLOBALS['db']->getAll($sql);
        if($res){
            foreach ($res as $val) {
                $goods_id = $val['goods_id'];
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_search_rel') . " (" .
                    "search_id, goods_id) VALUES('".$search_id."', '".$goods_id."')";
                $GLOBALS['db']->query($sql);
                // echo $sql.PHP_EOL;
            }
        }
    }

    ecs_header("Location: ./\n");
    exit;
}
// $smarty->is_cached('category.dwt', $cache_id);
// if (1)

    /* 如果页面没有被缓存则重新获取页面的内容 */

    $children = get_children($cat_id);

    $cat = get_cat_info($cat_id);   // 获得分类的相关信息

    if (!empty($cat))
    {
        $smarty->assign('keywords',    htmlspecialchars($cat['keywords']));
        $smarty->assign('description', htmlspecialchars($cat['cat_desc']));
        $smarty->assign('cat_style',   htmlspecialchars($cat['style']));
    }
    else
    {
        /* 如果分类不存在则返回首页 */
        ecs_header("Location: ./\n");

        exit;
    }

    /* 赋值固定内容 */
    if ($brand > 0)
    {
        $sql = "SELECT brand_name FROM " .$GLOBALS['ecs']->table('brand'). " WHERE brand_id = '$brand'";
        $brand_name = $db->getOne($sql);
    }
    else
    {
        $brand_name = '';
    }

    /* 获取价格分级 */
    if ($cat['grade'] == 0  && $cat['parent_id'] != 0)
    {
        $cat['grade'] = get_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
    }

    if ($cat['grade'] > 1)
    {
        $sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max ".
               " FROM " . $ecs->table('goods'). " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  ';
               //获得当前分类下商品价格的最大值、最小值

        $row = $db->getRow($sql);

        // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
        $price_grade = 0.0001;
        for($i=-2; $i<= log10($row['max']); $i++)
        {
            $price_grade *= 10;
        }

        //跨度
        $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
        if($dx == 0)
        {
            $dx = $price_grade;
        }

        for($i = 1; $row['min'] > $dx * $i; $i ++);

        for($j = 1; $row['min'] > $dx * ($i-1) + $price_grade * $j; $j++);
        $row['min'] = $dx * ($i-1) + $price_grade * ($j - 1);

        for(; $row['max'] >= $dx * $i; $i ++);
        $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

        $sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num  ".
               " FROM " . $ecs->table('goods') . " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
               " GROUP BY sn ";

        $price_grade = $db->getAll($sql);

        foreach ($price_grade as $key=>$val)
        {
            $temp_key = $key + 1;
            $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
            $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
            $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
            $price_grade[$temp_key]['price_range'] = $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
            $price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
            $price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);
            $price_grade[$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_grade[$temp_key]['start'], 'price_max'=> $price_grade[$temp_key]['end'], 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

            /* 判断价格区间是否被选中 */
            if (isset($_REQUEST['price_min']) && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max)
            {
                $price_grade[$temp_key]['selected'] = 1;
            }
            else
            {
                $price_grade[$temp_key]['selected'] = 0;
            }
        }

        $price_grade[0]['start'] = 0;
        $price_grade[0]['end'] = 0;
        $price_grade[0]['price_range'] = $_LANG['all_attribute'];
        $price_grade[0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>0, 'price_max'=> 0, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);
        $price_grade[0]['selected'] = empty($price_max) ? 1 : 0;

        $smarty->assign('price_grade',     $price_grade);

    }
    
    /* 品牌筛选 */
    if(in_array($cat_id, $cate_ids_arr['696'])){
        $brands = array();
    }else{
        $sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num ".
                "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                    $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
                "WHERE g.brand_id = b.brand_id AND b.is_show = 1 " .
                // "WHERE g.brand_id = b.brand_id AND ($children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id), array_keys(cat_list($cat_id, 0, false))))) . ") AND b.is_show = 1 " .
                " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
                "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

        $brands = $GLOBALS['db']->getAll($sql);

        foreach ($brands AS $key => $val)
        {
            $temp_key = $key + 1;
            $brands[$temp_key]['brand_name'] = $val['brand_name'];
            $brands[$temp_key]['url'] = build_uri('category', array('cid' => $cat_id, 'bid' => $val['brand_id'], 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

            /* 判断品牌是否被选中 */
            if ($brand == $brands[$key]['brand_id'])
            {
                $brands[$temp_key]['selected'] = 1;
            }
            else
            {
                $brands[$temp_key]['selected'] = 0;
            }
        }
    }

    /* 属性筛选 */
    $ext = ' AND (g.is_owner <> 4 OR g.is_owner IS NULL) '; //商品查询条件扩展
    if($keywords){
        $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE (is_owner <> 4 OR is_owner IS NULL) AND is_delete = 0 AND is_on_sale = 1 AND goods_name LIKE '%".$keywords."%'";
        $res = $GLOBALS['db']->getAll($sql);
        if(!$res){
            $key_no_search = 1;
        }

        $sql = "SELECT goods_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_owner = 4 AND is_delete = 0 AND is_on_sale = 1 AND goods_name LIKE '%".$keywords."%'";
        $res = $GLOBALS['db']->getAll($sql);
        if($res){
            $key_no_search = 2;
            $goods_id_arr = array_column($res, 'goods_id');
            $goods_id_str = json_encode($goods_id_arr);
        }
    }
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
                if($temp_name['attr_name'] == '铜价'){
                    continue;
                }
                if($temp_name['attr_name'] == '图名/品名'){
                    continue;
                }
                if($temp_name['attr_name'] == '单位'){
                    continue;
                }
                if($temp_name['attr_name'] == '参数'){
                    continue;
                }
                if($temp_name['attr_name'] == '新旧程度'){
                    continue;
                }
                if($temp_name['attr_name'] == '包装'){
                    continue;
                }
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
                // echo $sql;
                // print_r($attr_list);exit;
                $temp_arrt_url_arr = array();

                for ($i = 0; $i < count($cat_filter_attr); $i++)        //获取当前url中已选择属性的值，并保留在数组中
                {
                    $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                }

                $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                // $all_attr_list[$key]['attr_list'][0]['attr_value'] = $_LANG['all_attribute'];
                // $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url), $cat['cat_name']);
                // $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                foreach ($attr_list as $k => $v)
                {
                    $temp_key = $k + 1;
                    $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                    if($v['attr_value'] == '/'){
                        continue;
                    }
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
//         $smarty->assign('filter_attr_list',  $all_attr_list);
        /* 扩展商品查询条件 */
        if (!empty($filter_attr))
        {
            $ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods_attr') . " AS b " .  "WHERE ";
            $ext_group_goods = array();

            foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
            {
                if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
                {
                    $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] ." AND a.goods_attr_id = " . $v;
                    $ext_group_goods = $db->getColCached($sql);
                    $ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                }
            }
        }
    }

    assign_template('c', array($cat_id));

    $position = assign_ur_here($cat_id, $brand_name);
    $smarty->assign('page_title',       $position['title']);    // 页面标题
    $smarty->assign('ur_here',          $position['ur_here']);  // 当前位置
    $categories = get_categories_tree($cat_id);
   
    foreach ($categories as $p_cat_id=>$val) {
        if($p_cat_id == $cat_id && isset($categories[$cat_id]) && !empty($categories[$cat_id]['cat_id'])){
            $categories = $categories[$cat_id]['cat_id'];
            break;
        }
    }
  
    if ($brand > 0)
    {
        $arr['all'] = array('brand_id'  => 0,
                        'brand_name'    => $GLOBALS['_LANG']['all_goods'],
                        'brand_logo'    => '',
                        'goods_num'     => '',
                        'url'           => build_uri('category', array('cid'=>$cat_id), $cat['cat_name'])
                    );
    }
    else
    {
        $arr = array();
    }

    $brand_list = array_merge($arr, get_brands($cat_id, 'category'));

    /* 调查 */
    $vote = get_vote();
    if (!empty($vote))
    {
        $smarty->assign('vote_id',     $vote['id']);
        $smarty->assign('vote',        $vote['content']);
    }

    $smarty->assign('best_goods',      get_category_recommend_goods('best', $children, $brand, $price_min, $price_max, $ext));
    $smarty->assign('promotion_goods', get_category_recommend_goods('promote', $children, $brand, $price_min, $price_max, $ext));
    $smarty->assign('hot_goods',       get_category_recommend_goods('hot', $children, $brand, $price_min, $price_max, $ext));
    if($keywords){
        $ext .= " AND goods_name LIKE '%".$keywords."%'";
    }
    $count = get_cagtegory_goods_count($children, $brand, $price_min, $price_max, $ext);
    $max_page = ($count> 0) ? ceil($count / $size) : 1;
    if ($page > $max_page)
    {
        $page = $max_page;
    }
    $goodslist = category_get_goods($children, $brand, $price_min, $price_max, $ext, $size, $page, $sort, $order, $keywords);
    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    // echo '<pre>';
    // print_r($goodslist);exit;
    $smarty->assign('goods_list',       $goodslist);
    $smarty->assign('category',         $cat_id);

    assign_pager('category',            $cat_id, $count, $size, $sort, $order, $page, $keywords, $brand, $price_min, $price_max, $display, $filter_attr_str); // 分页

// echo '<pre>';
ajaxReturn(['code' => 1,'max_page' => $max_page,'page' => $page,'goodsList' => $goodslist]);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

function array_sort($array, $row, $type){
  $array_temp = array();
  foreach($array as $v){
    $array_temp[$v[$row]] = $v;
  }
  if($type == 'asc'){
    ksort($array_temp);
  }elseif($type='desc'){
    krsort($array_temp);
  }else{
  }
  return $array_temp;
}

/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT cat_name, keywords, cat_desc, style, grade, filter_attr, parent_id FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($children, $brand, $min, $max, $ext, $size, $page, $sort, $order, $keywords)
{
    $display = $GLOBALS['display'];
    $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
            "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  "AND g.brand_id=$brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    if($keywords){
        $where .= " AND g.goods_name like '%{$keywords}%' ";
    }

    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.goods_number, g.suppliers_id, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "g.shop_price, g.promote_price, g.goods_type, " .
                // "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            "WHERE $where $ext ORDER BY $sort $order";
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
    // echo $sql;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }
        $arr[$row['goods_id']]['name']             = $row['goods_name'];
        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_number']      = $row['goods_number'];
        $arr[$row['goods_id']]['suppliers_id'] = "";
        
        //供应商
        $suppliers_info = $GLOBALS['db']->getRow("SELECT suppliers_sn, province, city, district FROM " .$GLOBALS['ecs']->table('suppliers'). " WHERE suppliers_id = " . $row['suppliers_id'] . " AND is_check = 1 LIMIT 1");
        if($suppliers_info && $suppliers_info['province'] && $suppliers_info['city']){
            $province = $GLOBALS['db']->getRow("SELECT region_name FROM " .$GLOBALS['ecs']->table('region'). " WHERE region_id = " . $suppliers_info['province'] . " LIMIT 1");
            $city = $GLOBALS['db']->getRow("SELECT region_name FROM " .$GLOBALS['ecs']->table('region'). " WHERE region_id = " . $suppliers_info['city'] . " LIMIT 1");
            $district = $GLOBALS['db']->getRow("SELECT region_name FROM " .$GLOBALS['ecs']->table('region'). " WHERE region_id = " . $suppliers_info['district'] . " LIMIT 1");
            // $arr[$row['goods_id']]['suppliers_id']      = str_replace('省', '', $province['region_name']).str_replace('市', '', $city['region_name']);
            $arr[$row['goods_id']]['suppliers_id']      = $city['region_name'].$district['region_name'];
        }

        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        // $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['shop_price']       = $row['shop_price'];
        $arr[$row['goods_id']]['org_price']        = price_format($row['org_price']);
        $arr[$row['goods_id']]['type']             = $row['goods_type'];
        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        $properties = get_goods_properties($row['goods_id']);  // 获得商品的规格和属性
        // echo '<pre>';
        // print_r($properties);exit;
        $arr[$row['goods_id']]['chuchang'] = !empty($properties) && isset($properties['pro']['商品属性']['230']['value']) ? $properties['pro']['商品属性']['230']['value'] : "";
    }
    return $arr;
}

/**
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_count($children, $brand = 0, $min = 0, $max = 0, $ext='')
{
    $where  = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  " AND g.brand_id = $brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 返回商品总数 */
    return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . " AS g WHERE $where $ext");
}

/**
 * 取得最近的上级分类的grade值
 *
 * @access  public
 * @param   int     $cat_id    //当前的cat_id
 *
 * @return int
 */
function get_parent_grade($cat_id)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_parent_grade');
        if ($data === false)
        {
            $sql = "SELECT parent_id, cat_id, grade ".
                   " FROM " . $GLOBALS['ecs']->table('category');
            $res = $GLOBALS['db']->getAll($sql);
            write_static_cache('cat_parent_grade', $res);
        }
        else
        {
            $res = $data;
        }
    }

    if (!$res)
    {
        return 0;
    }

    $parent_arr = array();
    $grade_arr = array();

    foreach ($res as $val)
    {
        $parent_arr[$val['cat_id']] = $val['parent_id'];
        $grade_arr[$val['cat_id']] = $val['grade'];
    }

    while ($parent_arr[$cat_id] >0 && $grade_arr[$cat_id] == 0)
    {
        $cat_id = $parent_arr[$cat_id];
    }

    return $grade_arr[$cat_id];

}


?>
