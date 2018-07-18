<?php

class WxCategory{
	
	//默认读取一个分类的子类
	public function get_child_tree($tree_id= 695){
		$three_arr = array();
			$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$tree_id' AND is_show = 1 ";
			if ($GLOBALS['db']->getOne($sql) || $tree_id == 0){
				$child_sql = 'SELECT cat_id, cat_name, parent_id, filter_attr, is_show ' .
						'FROM ' . $GLOBALS['ecs']->table('category') .
						"WHERE parent_id = '$tree_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
				$res = $GLOBALS['db']->getAll($child_sql);
				foreach ($res AS $row){
					if ($row['is_show'])
						$three_arr[$row['cat_id']]['id']   = $row['cat_id'];
						$three_arr[$row['cat_id']]['name'] = $row['cat_name'];
						$three_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
						$three_arr[$row['cat_id']]['filter_attr'] = $row['filter_attr'];
						
						if (isset($row['cat_id']) != NULL){
							$three_arr[$row['cat_id']]['cat_id'] = self::get_child_tree($row['cat_id']);
						}
				}
			}
			return $three_arr;
	}
	
	//获取共同品牌
	public function get_brand(){
		$sql = "select brand_id,brand_name from ".$GLOBALS['ecs']->table("brand")." where is_show=1 order by sort_order asc";
		$temp = $GLOBALS['db']->getAll($sql);
		
		$brandList = array();
		
		foreach ($temp as $key => $value){
			$brandList[$value['brand_id']] = $value;
		}
		
		return $brandList;
	}
	
	//获取分类下的属性
	public function get_attr($category_list){
		/* 获得请求的分类 ID */
		$_REQUEST['id'] = 683;
		if (isset($_REQUEST['id']) && $_REQUEST['id']){
			$cat_id = intval($_REQUEST['id']);
		}elseif (isset($_REQUEST['category']) && $_REQUEST['category']){
			$cat_id = intval($_REQUEST['category']);
		}else{
			$tmparr = current($cat_list);
			$cat_id = !empty($tmparr) ? $tmparr['id'] : '';
		}
		
		print_r($category_list);
		exit;
	}
	
	public function get_lists($ban_categories=array()){
		
		$sql = "select g.cat_id, a.attr_id, a.attr_name, ga.goods_attr_id, ga.attr_value from " .  $GLOBALS['ecs']->table("goods_attr") . " ga INNER JOIN " .  $GLOBALS['ecs']->table("attribute") . " a ON ga.attr_id = a.attr_id INNER JOIN " .  $GLOBALS['ecs']->table("goods") . " g ON g.goods_id = ga.goods_id  WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ga.attr_value <> '' AND ga.attr_value IS NOT NULL ";
		$sql .= "GROUP BY a.attr_id, a.attr_name, ga.attr_value ORDER BY a.sort_order asc, ga.attr_value asc;";
		
		// echo $sql;
		$attrs = $GLOBALS['db']->getAll($sql);
		$ban_goods_attributes = array();
		$part_goods_attributes = array();
		foreach ($attrs as $val) {
			if(isset($ban_categories[$val['cat_id']]) && $ban_categories[$val['cat_id']]){
				if($val['attr_name'] == '铜价'){
					continue;
				}
				$ban_goods_attributes[$val['attr_id']]['attr_name'] = $val['attr_name'];
				$ban_goods_attributes[$val['attr_id']]['attrs'][$val['goods_attr_id']]['goods_attr_id'] = $val['goods_attr_id'];
				$ban_goods_attributes[$val['attr_id']]['attrs'][$val['goods_attr_id']]['goods_attr_val'] = $val['attr_value'];
			}
			
			if(isset($part_categories[$val['cat_id']]) && $part_categories[$val['cat_id']]){
				
				$part_goods_attributes[$val['attr_id']]['attr_name'] = $val['attr_name'];
				$part_goods_attributes[$val['attr_id']]['attrs'][$val['goods_attr_id']]['goods_attr_id'] = $val['goods_attr_id'];
				$part_goods_attributes[$val['attr_id']]['attrs'][$val['goods_attr_id']]['goods_attr_val'] = $val['attr_value'];
			}
		}
		
		return $ban_goods_attributes;
	}
	
	/**
	 * 获得指定分类同级的所有分类以及该分类下的子分类
	 *
	 * @access  public
	 * @param   integer     $cat_id     分类编号
	 * @return  array
	 */
	public function get_categories_tree($cat_id = 0){
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
					$cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
					
					if (isset($row['cat_id']) != NULL)
					{
						$cat_arr[$row['cat_id']]['cat_id'] = get_child_tree($row['cat_id']);
					}
				}
			}
		}
		if(isset($cat_arr))
		{
			return $cat_arr;
		}
	}
	
}
