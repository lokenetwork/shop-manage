<?php

/**
 * @name GoodsController
 * @author root
 * @desc 商品控制器
 *
 */
class ShopController extends UserController {

	public function init(){
		parent::init();
	}

	function indexAction(){
		global $r_db;

		$shop_name = $this->_get('shop_name');
		$page = $this->_get('page', 1);
		$add_time_start = $this->_get('add_time_start');
		$this->getView()->assign('add_time_start', $add_time_start);
		$add_time_end = $this->_get('add_time_end');
		$this->getView()->assign('add_time_end', $add_time_end);


		$condition = ['ORDER' => ['shop_id DESC']];
		$condition['AND']['is_delete'] = 0;


		$lock = $this->_get('lock', 0);
		if($lock){
			$condition['AND']['lock'] = $lock;
		}

		if($shop_name){
			$condition['AND']['shop_name[~]'] = $shop_name;
		}
		if($add_time_start){
			$condition['AND']['add_time[>]'] = $add_time_start . ' 00:00:00';
		}
		if($add_time_end){
			$condition['AND']['add_time[<]'] = $add_time_end . ' 00:00:00';
		}
		$spec_num = $r_db->count('shop', $condition);

		$Pagination = new Pagination($spec_num, $page, 20);
		$this->getView()->assign('pagination', $Pagination->show());

		$condition["LIMIT"] = [$Pagination->firstRow, $Pagination->listRows];
		$fields = "*";
		$list = $r_db->select('shop', $fields, $condition);

		//循环处理数据
		foreach($list as $key => $item){
			$l_info = $this->get_shop_location($item);
			$list[$key]['loaction_url'] = $l_info['loaction_url'];
		}

		$this->getView()->assign('shop_name', $shop_name);

		$this->getView()->assign('list', $list);
		$this->getView()->assign('pagination', $Pagination->show());

		$this->getView()->assign('_title', '店铺列表');

	}

	function detailAction(){
		global $r_db;

		$shop_id = $_SESSION['shop_id'];

		$fields = "*";
		$condition = ['shop_id' => $shop_id];
		$shop_info = $r_db->get('shop', $fields, $condition);
		$shop_info['pic_url_display'] = $this->img_diaplay_domain.$shop_info['pic_url'];

		$l_info = $this->get_shop_location($shop_info);
		$shop_info['location'] = $l_info['location'];
		$shop_info['location_url'] = $l_info['location_url'];
		//var_dump($shop_info);


		$this->getView()->assign('shop_info', $shop_info);
		$this->getView()->assign('img_upload_domain', $this->img_upload_domain);
		$this->getView()->assign('img_diaplay_domain', $this->img_diaplay_domain);

		$this->getView()->assign('_title', '店铺信息');

	}

	function selectLocationAction(){
		global $r_db;

		$shop_id = $_SESSION['shop_id'];

		$fields = "*";
		$condition = ['shop_id' => $shop_id];
		$shop_info = $r_db->get('shop', $fields, $condition);


		$l_info = $this->get_shop_location($shop_info);
		$shop_info['location_url'] = $l_info['location_url'];


		$this->getView()->assign('shop_info', $shop_info);

		$this->getView()->assign('_title', '店铺位置');

	}

	//获取店铺位置函数
	private function get_shop_location($shop_info){
		global $r_db;
		$url = "https://map.baidu.com/?latlng=%s,%s&title=%s&content=%s&autoOpen=true&l";
		$arr['location_url'] = sprintf($url, $shop_info['longitude'], $shop_info['latitude'], $this->get_company_name(), $shop_info['shop_name']);
		return ($arr);

	}

	function editPostAction(){
		global $w_db;

		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接
		$redis->rawCommand('geoadd', 'shop_location', $this->_post('longitude'), $this->_post('latitude'), $this->shop_id);

		//把图片设置成已使用
		$pic_ids = [$this->_post('pic_id')];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->img_upload_domain."/Upload/updateUploadImageId");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		$post_data = array(
			"upload_image_id" => implode(",",$pic_ids)
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		$data = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($data,true);
		if( !$data['status'] ){
			$this->getView()->assign("title", '操作失败');
			$this->getView()->assign("desc", '店铺信息未更新!');
			$this->getView()->assign("type", 'success');
			$this->getView()->display('common/tips.html');
			return false;
		}

		//todo，调图片API删除之前的图片，把图片改成未使用，自动删除就行。

		$data = [];
		$data['shop_name'] = $this->_post('shop_name');
		$data['shop_profile'] = $this->_post('shop_profile');
		$data['pic_id'] = $this->_post('pic_id');
		$data['pic_url'] = $this->_post('pic_url');
		$data['qq'] = $this->_post('qq');
		$data['wechat'] = $this->_post('wechat');
		$data['mobile'] = $this->_post('mobile');
		$data['email'] = $this->_post('email');
		$data['contact'] = $this->_post('contact');

		$data['province'] = $this->_post('province');
		$data['city'] = $this->_post('city');
		$data['address'] = $this->_post('address');
		$data['address_display'] = $this->_post('address_display');
		$data['longitude'] = $this->_post('longitude');
		$data['latitude'] = $this->_post('latitude');

		$w_db->update('shop', $data, ['shop_id' => $this->shop_id]);

		$this->getView()->assign("title", '操作提醒');
		$this->getView()->assign("desc", '店铺信息已更新!');
		$this->getView()->assign("type", 'success');
		$this->getView()->display('common/tips.html');
		return false;
	}

	/*
	function editLocationAction(){
	  global $w_db;

	  $data = [];
	  $data['province'] = $this->_post('province');
	  $data['city'] = $this->_post('city');
	  $data['address'] = $this->_post('address');
	  $data['longitude'] = $this->_post('longitude');
	  $data['latitude'] = $this->_post('latitude');

	  $w_db->update('shop',$data,['shop_id'=>$this->shop_id]);

	  $this->getView()->assign("title", '操作提醒');
	  $this->getView()->assign("desc", '店铺信息已更新!');
	  $this->getView()->assign("type", 'success');
	  $this->getView()->display('common/tips.html');
	  return false;
	}
	*/

}
