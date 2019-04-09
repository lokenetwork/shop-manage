<?php

/**
 * @name GoodsController
 * @author root
 * @desc 商品控制器
 *
 */
class GoodsController extends UserController {

  protected $Category;
  public function init(){
    parent::init();
    $this->Category = new Category();
  }

  function indexAction(){
    global $r_db;

    $goods_name = $this->_get('goods_name');
    $page = $this->_get('page', 1);
    $goods_add_time_start = $this->_get('goods_add_time_start');
    $this->getView()->assign('goods_add_time_start',$goods_add_time_start);
    $goods_add_time_end = $this->_get('goods_add_time_end');
    $this->getView()->assign('goods_add_time_end',$goods_add_time_end);

    $condition = [];
    $condition['AND']['is_delete'] = 0;
    $condition['AND']['shop_id'] = $this->shop_id;

    $is_on_sale = $this->_get('is_on_sale',0);
    if($is_on_sale){
      $condition['AND']['is_on_sale'] = $is_on_sale;
    }

    $is_new = $this->_get('is_new',0);
    if($is_new){
      $condition['AND']['is_new'] = $is_new;
    }

    $is_hot = $this->_get('is_hot',0);
    if($is_hot){
      $condition['AND']['is_hot'] = $is_hot;
    }

    $is_cheap = $this->_get('is_cheap',0);
    if($is_cheap){
      $condition['AND']['is_cheap'] = $is_cheap;
    }

    $lock = $this->_get('lock',0);
    if($lock){
      $condition['AND']['lock'] = $lock;
    }

    if($goods_name){
      $condition['AND']['goods_name[~]'] = $goods_name;
    }
    if($goods_add_time_start){
      $condition['AND']['add_time[>]'] = $goods_add_time_start.' 00:00:00';
    }
    if($goods_add_time_end){
      $condition['AND']['add_time[<]'] = $goods_add_time_end.' 00:00:00';
    }
    $spec_num = $r_db->count('goods', $condition);

    $Pagination = new Pagination($spec_num, $page, 20);
    $this->getView()->assign('pagination', $Pagination->show());

    //处理排序
    $order_field = $this->_get('order_field',"add_time");
    $order_type = $this->_get('order_type',"DESC");
    $order_info = $this->get_order_url($order_field, $order_type);
    $condition['ORDER'] = $order_info['condition_order'];

    $condition["LIMIT"] = [$Pagination->firstRow, $Pagination->listRows];
    //$fields = ['goods_id','goods_name','goods_price','goods_number','is_on_sale','page_view','add_time'];
    $fields = "*";
    $goods_list = $r_db->select('goods', $fields, $condition);

    //循环处理数据
    foreach($goods_list as $key=>$item){
      $goods_list[$key]['origin_goods_name'] = $item['goods_name'];
      $goods_list[$key]['goods_name'] = mb_substr($item['goods_name'],0,30,"utf-8");
    }


    $this->getView()->assign('goods_name',$goods_name);
    $this->getView()->assign('order_info',$order_info);

    $this->getView()->assign('goods_list',$goods_list);
    $this->getView()->assign('pagination',$Pagination->show());

    $this->getView()->assign('_title','商品列表');

  }

  function get_order_url($order_filed, $order_type){
    $order_info = [];

    //可排序的字段
    $field_arr = ['view', 'collect', 'add_time'];
    if( !in_array($order_filed,$field_arr) || ('DESC' != $order_type && 'ASC' != $order_type) ){
      exit("排序数据错误");
    }
    $uri = preg_replace("/&order_field=(\S)*&order_type=((DESC)|(ASC))/",'',$_SERVER['REQUEST_URI']);
    $base_url = $uri."&order_field=%s&order_type=%s";

    foreach( $field_arr as $item ){
      if( $item == $order_filed){
        if( 'ASC' == $order_type ){
          $order_info[$item]['url'] = sprintf($base_url,$item,"DESC");
          $order_info[$item]['class'] = "icon-arrow-down";
        }else{
          $order_info[$item]['url'] = sprintf($base_url,$item,"ASC");
          $order_info[$item]['class'] = "icon-arrow-up";
        }
        $condition_order = ["$item"=>"$order_type"];
      }else{
        //其他字段默认倒序排序
        $order_info[$item]['url'] = sprintf($base_url,$item,"DESC");
        $order_info[$item]['class'] = "icon-arrow-down";
      }
    }

    $order_info['condition_order'] = $condition_order;
    return $order_info;
  }

  function addAction(){
    global $r_db;

    $this->getView()->assign('img_upload_domain',$this->img_upload_domain);
    $this->getView()->assign('img_diaplay_domain',$this->img_diaplay_domain);
    $this->getView()->assign('_title','添加商品');
  }

	function addPostAction(){
		global $w_db;

		//检测 post get.
		filer_get_post(['intro']);

		$res = $this->update_upload_pic_id();

		$intro = $this->_post('intro');
		$purifier = new HTMLPurifier();
		$intro = $purifier->purify($intro);
		$trans_result = $this->pachong_intro($intro);
		if(!$res || !$trans_result['status']){
			$this->getView()->assign("title", '操作提醒');
			$this->getView()->assign("desc", '商品添加失败,请重试!');
			$this->getView()->assign("type", 'error');
			$this->getView()->display('common/tips.html');
			exit;
		}else{
			$intro = $trans_result['intro'];
		}

		$w_db->begin_transaction();

		$Ctime = new Ctime();

		$goods_data = [];
		$goods_data['shop_id'] = $this->shop_id;
		$goods_data['goods_name'] = $this->_post('goods_name');
		$goods_data['ucat_id'] = $this->_post('cat_id');
		$goods_data['goods_number'] = $this->get_goods_store();
		$goods_data['goods_price'] = $this->get_sku_low_prive();
		$goods_data['is_on_sale'] = $this->_post('is_on_sale');
		$goods_data['is_new'] = $this->_post('is_new');
		$goods_data['is_hot'] = $this->_post('is_hot');
		$goods_data['is_cheap'] = $this->_post('is_cheap');
		$goods_data['first_picture'] = $this->_post('main_pic_url');
		$goods_data['first_picture_id'] = $this->_post('main_pic_id');
		$goods_data['add_time'] = $Ctime->long_time();
		$goods_data['last_update_time'] = $Ctime->long_time();

		$w_db->insert('goods', $goods_data);
		$goods_id = $w_db->id();
		$intro_data = [];
		$intro_data['goods_id'] = $goods_id;
		$intro_data['intro'] = $intro;
		$w_db->insert('goods_intro', $intro_data);

		$goods_sku = $this->_post('goods_sku');
		//处理商品sku
		foreach($goods_sku as $item){
			$sku_data = [];
			$sku_data['goods_id'] = $goods_id;
			$sku_data['color'] = $item['color'];
			$sku_data['size'] = $item['size'];
			$sku_data['sku_price'] = $item['price'];
			$sku_data['sku_num'] = $item['store'];
			$sku_data['sku_code'] = $item['code'];
			$sku_data['pic_url'] = $item['pic_url'];
			$sku_data['pic_id'] = $item['pic_id'];
			$w_db->insert('goods_sku', $sku_data);
		}

		$pic_url = $this->_post('pic_url');
		foreach($pic_url as $key => $item){
			$img_data = [];
			$img_data['goods_id'] = $goods_id;
			$img_data['pic_url'] = $item;
			$img_data['pic_id'] = $key;
			$w_db->insert('goods_img', $img_data);
		}

		//店铺商品数量++
		$w_db->update('shop', ["goods_sku[+]" => 1], ['shop_id' => $this->shop_id]);

		//处理商品分词
		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接
		//根据空格切分 输入
		$arr = explode(" ", $goods_data['goods_name']);
		$all_words = [];
		foreach($arr as $item){
			$all_words = array_merge($this->pullword($item, $redis), $all_words);
		}
		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接
		foreach($all_words as $word){
			//查询出词的id
			$word_id =  $w_db->get('search_word','word_id',['word'=>$word]);
			$insert_data = [];
			$insert_data['goods_id'] = $goods_id;
			$insert_data['shop_id'] = $this->shop_id;
			$insert_data['word_id'] = $word_id;
			$w_db->insert('search_goods_word',$insert_data);
			//存进redis
			$word_godds_set = "word_".$word."_goods";
			$redis->sAdd($word_godds_set,$goods_id);
		}
		//录入店铺商品数据进redis集合
		$goods_shop_set = "goods_shop_".$this->shop_id;
		$redis->sAdd($goods_shop_set,$goods_id);

		//记录商品创建数量
		//获取当前的小时
		$now_timestamp = time();
		$time_format = 'Y-m-d 00:00:01';
		$now_time = $Ctime->custom($time_format,$now_timestamp);
		$goods_create_day_info = $w_db->get('goods_create_day','*',['create_time'=>$now_time]);
		if( $goods_create_day_info ){
			$w_db->update('goods_create_day',['goods_num[+]'=>1],['create_time'=>$now_time]);
		}else{
			$insert_data = [];
			$insert_data['goods_num'] = 1;
			$insert_data['create_time'] = $now_time;
			$w_db->insert('goods_create_day',$insert_data);
		}

		$time_format = 'Y-m-01 00:00:00';
		$now_month_time = $Ctime->custom($time_format,$now_timestamp);
		$goods_create_month_info = $w_db->get('goods_create_month','*',['create_time'=>$now_month_time]);
		if( $goods_create_month_info ){
			$w_db->update('goods_create_month',['goods_num[+]'=>1],['create_time'=>$now_month_time]);
		}else{
			$insert_data = [];
			$insert_data['goods_num'] = 1;
			$insert_data['create_time'] = $now_month_time;
			$w_db->insert('goods_create_month',$insert_data);
		}

		$w_db->commit();
		$this->getView()->assign("title", '操作提醒');
		$this->getView()->assign("desc", '商品添加成功!');
		$this->getView()->assign("url", '/Goods/index');
		$this->getView()->assign("type", 'success');
		$this->getView()->display('common/tips.html');
		return false;

	}

  private function pachong_intro($intro){
    global $logger;
    /*
    $intro = '<p><img src="http://127.0.0.1:9016/goods/20190201/5c5431eecef9e.jpg" style="max-width:100%;"><br></p>';
    $intro .= '<p><img src="https://img.alicdn.com/imgextra/i2/56457695/TB2IYerJQKWBuNjy1zjXXcOypXa_!!56457695.jpg" style="max-width:100%;"><br></p>';
    $intro .= '<p><img src="https://img.alicdn.com/imgextra/i2/56457695/TB2IYerJQKWBuNjy1zjXXcOypXa_!!56457695.jpg" style="max-width:100%;"><br></p>';
    */
    //抓取第三方的图片保存到我们服务器.替换掉原来的地址
    $purifier = new HTMLPurifier();

    $intro = $purifier->purify($intro);


    preg_match_all('/<img[^>]*?src="([^"]*?)"[^>]*?>/i',$intro,$match);
    $fi = new finfo(FILEINFO_MIME_TYPE);
    foreach($match[1] as $item){
      if(false === strstr($item,$this->img_diaplay_domain)){
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $item);
        //设置头文件的信息作为数据流输出
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'));
        //执行命令
        $remote_data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //把数据写进临时文件
	      $tmp_file_name = base64_encode($item) . $this->shop_id . '.' . substr(strrchr($item, '.'), 1);
	      $file_path = APPLICATION_PATH . "/tmp/" . $tmp_file_name;
	      $fd = fopen($file_path, "w");
	      fwrite($fd, $remote_data);
		//上传文件到图片服务器
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->img_upload_domain . "/Index/uploadMultipleGoodsPicture");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('multipart/form-data'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //php.ini 代码 默认执行时间是30秒。
        curl_setopt($ch, CURLOPT_TIMEOUT, 100); //设置请求超时时间 秒
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $post_data = array(
          'file'=>curl_file_create($file_path, $fi->file($file_path), $tmp_file_name),
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($post_data));
        $upload_result = json_decode(curl_exec($ch),true);
        curl_close($ch);
        if( !$upload_result['status'] ){
          $logger->error("uploadMultipleGoodsPicture fail",$upload_result);
          $return = [];
          $return['status'] = 0;
          return $return;
        }


        unlink($file_path);
        //替换图片路径
        $intro = str_replace($item,$this->img_diaplay_domain.$upload_result['image_info'][0]['image_name'],$intro);

        //更新图片ID
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $this->img_upload_domain."/Upload/updateUploadImageId");
        //设置头文件的信息作为数据流输出
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        $post_data = array(
          "upload_image_id" => $upload_result['upload_ids'][0]
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        $data = json_decode($data,true);

        if( !$data['status'] ){
          $logger->error("updateUploadImageId fail",$upload_result);
          $return = [];
          $return['status'] = 0;
          return $return;
        }

      }else{
        //如果图片地址是我们的域名,不抓.
        $pic_path = str_replace($this->img_diaplay_domain,'',$item);
        //根据图片url更新文件是否已经使用.
        //初始化
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->img_upload_domain."/Upload/updateUploadImageUrl");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        $post_data = array(
          "pic_url" => $pic_path,
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        $data = json_decode($data,true);
        if( !$data['status'] ){
          $logger->error("updateUploadImageId fail",$upload_result);
          $return = [];
          $return['status'] = 0;
          return $return;
        }
      }
    }

    $return = [];
    $return['status'] = 1;
    $return['intro'] = $intro;
    return $return;
  }

  //更新所有图片ID 为可用
  private function update_upload_pic_id(){
    global $w_db;
    $pic_ids = $this->_post('pic_id');
    array_push($pic_ids,$this->_post('main_pic_id'));
    $goods_sku = $this->_post('goods_sku');
    foreach($goods_sku as $item){
      array_push($pic_ids,$item['pic_id']);
    }
    //获取所有图片id.


    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $this->img_upload_domain."/Upload/updateUploadImageId");
    //设置头文件的信息作为数据流输出
    //curl_setopt($curl, CURLOPT_HEADER, 1);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    //设置post数据
    $post_data = array(
      "upload_image_id" => implode(",",$pic_ids)
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    $data = json_decode($data,true);
    if( $data['status'] ){
      return true;
    }else{
      return false;
    }

  }

  private function get_goods_store(){
    $goods_sku = $this->_post('goods_sku');
    //获取商品sku的数量总和
    $num = 0;
    foreach($goods_sku as $item){
      $num += $item['store'];
    }
    return $num;
  }
  private function get_sku_low_prive(){
    $goods_sku = $this->_post('goods_sku');
    //获取商品sku的数量总和
    $price = 0;
    foreach($goods_sku as $item){
      if( bccomp($item['price'],$price) ){
        $price = $item['price'];
      }
    }
    return $price;
  }

  function editAction(){
    global $w_db;

    $goods_id = $this->_get('id');
    $g_info = $w_db->get('goods','*',['goods_id'=>$goods_id]);

    if( $this->shop_id != $g_info['shop_id'] ){
      $this->getView()->assign("desc", '商品不存在!');
      $this->getView()->assign("type", 'error');
      $this->getView()->display('common/tips.html');
      exit;
    }

    //查询出商品的多级分类
    //category_level_0;
    $c_id = $g_info['ucat_id'];
    $condition = [];
    $condition['AND']['shop_id'] = $this->shop_id;
    $condition['AND']['category_id'] = $c_id;
    $c_info = $w_db->get('shop_category',"*",$condition);
    $current_level = $c_info['level'];
    $p_id = $c_info['parent_id'];
    $_GET['category_level_'.($c_info['level'])] = $c_id;
    while($current_level>0){
      $condition = [];
      $condition['AND']['category_id'] = $p_id;
      $info = $w_db->get('shop_category',"*",$condition);
      $_GET['category_level_'.($info['level'])] = $info['category_id'];
      $current_level = $info['level'];
      $p_id = $info['parent_id'];
    }

    //查询出商品sku信息
    $sku = $w_db->select('goods_sku','*',['goods_id'=>$goods_id]);

    //查询出商品的普通图片
    $g_img = $w_db->select('goods_img','*',['goods_id'=>$goods_id]);

    //查询商品介绍
    $intro = $w_db->get('goods_intro','intro',['goods_id'=>$goods_id]);

    $this->getView()->assign('img_upload_domain',$this->img_upload_domain);
    $this->getView()->assign('img_diaplay_domain',$this->img_diaplay_domain);
    $this->getView()->assign('g_info',$g_info);
    $this->getView()->assign('sku',$sku);
    $this->getView()->assign('intro',$intro);
    $this->getView()->assign('cat_id',$g_info['ucat_id']);

    $this->getView()->assign('g_img',$g_img);
    $this->getView()->assign('_title','修改商品信息');
  }

	function editPostAction(){
		global $w_db;

		//检测 post get.
		filer_get_post(['intro']);

		$res = $this->update_upload_pic_id();

		$intro = $this->_post('intro');
		$goods_id = $this->_post('goods_id');
		$purifier = new HTMLPurifier();
		$intro = $purifier->purify($intro);
		$trans_result = $this->pachong_intro($intro);
		$this->getView()->assign("title", '操作提醒');

		if(!$res || !$trans_result['status']){
			$this->getView()->assign("desc", '商品修改失败,请重试!');
			$this->getView()->assign("type", 'error');
			$this->getView()->display('common/tips.html');
			exit;
		}else{
			$intro = $trans_result['intro'];
		}

		//判断商品ID是否是这个店铺的
		$old_g_info = $w_db->get('goods', '*', ['goods_id' => $goods_id]);
		if($this->shop_id != $old_g_info['shop_id']){
			$this->getView()->assign("desc", '商品修改失败!');
			$this->getView()->assign("type", 'error');
			$this->getView()->display('common/tips.html');
			exit;
		}

		$w_db->begin_transaction();

		$Ctime = new Ctime();

		$goods_data = [];
		$goods_data['goods_name'] = $this->_post('goods_name');
		$goods_data['ucat_id'] = $this->_post('cat_id');
		$goods_data['goods_number'] = $this->get_goods_store();
		$goods_data['goods_price'] = $this->get_sku_low_prive();
		$goods_data['is_on_sale'] = $this->_post('is_on_sale');
		$goods_data['is_new'] = $this->_post('is_new');
		$goods_data['is_hot'] = $this->_post('is_hot');
		$goods_data['is_cheap'] = $this->_post('is_cheap');
		$goods_data['first_picture'] = $this->_post('main_pic_url');
		$goods_data['first_picture_id'] = $this->_post('main_pic_id');
		$goods_data['last_update_time'] = $Ctime->long_time();

		$w_db->update('goods', $goods_data, ['goods_id' => $goods_id]);

		$intro_data = [];
		$intro_data['intro'] = $intro;
		$w_db->update('goods_intro', $intro_data, ['goods_id' => $goods_id]);

		//处理商品sku
		$goods_sku = $this->_post('goods_sku');
		foreach($goods_sku as $item){
			$sku_data = [];
			$sku_data['goods_id'] = $goods_id;
			$sku_data['color'] = $item['color'];
			$sku_data['size'] = $item['size'];
			$sku_data['sku_price'] = $item['price'];
			$sku_data['sku_num'] = $item['store'];
			$sku_data['sku_code'] = $item['code'];
			$sku_data['pic_url'] = $item['pic_url'];
			$sku_data['pic_id'] = $item['pic_id'];
			//如果有sku_id则更新之前的数据
			if($item['sku_id']){
				$w_db->update('goods_sku', $sku_data, ['sku_id' => $item['sku_id']]);
			}else{
				$w_db->insert('goods_sku', $sku_data);
			}
		}
		$del_sku_id = explode(',', $this->_post('del_sku_id'));
		if(!empty($del_sku_id)){
			$w_db->delete('goods_sku', ['sku_id' => $del_sku_id]);
		}

		//删除之前的图片地址
		$pic_url = $this->_post('pic_url');
		$w_db->delete('goods_img', ['goods_id' => $goods_id]);
		foreach($pic_url as $key => $item){
			$img_data = [];
			$img_data['goods_id'] = $goods_id;
			$img_data['pic_url'] = $item;
			$img_data['pic_id'] = $key;
			$w_db->insert('goods_img', $img_data);
		}

		//处理商品分词
		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接
		//把之前的分词删掉
		$relative_info = $w_db->select('search_goods_word',"*",['goods_id'=>$goods_id]);
		foreach($relative_info as $item){
			$word = $w_db->get('search_word', 'word', ['word_id' => $item['word_id']]);
			$word_godds_set = "word_".$word."_goods";
			$redis->sRemove($word_godds_set,$goods_id);
		}
		$w_db->delete('search_goods_word',['goods_id'=>$goods_id]);

		//根据空格切分 输入
		$arr = explode(" ", $goods_data['goods_name']);
		$all_words = [];
		foreach($arr as $item){
			$all_words = array_merge($this->pullword($item, $redis), $all_words);
		}
		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接
		foreach($all_words as $word){
			//查询出词的id
			$word_id = $w_db->get('search_word', 'word_id', ['word' => $word]);
			$insert_data = [];
			$insert_data['goods_id'] = $goods_id;
			$insert_data['shop_id'] = $this->shop_id;
			$insert_data['word_id'] = $word_id;
			$w_db->insert('search_goods_word', $insert_data);
			//存进redis
			$word_godds_set = "word_" . $word . "_goods";
			$redis->sAdd($word_godds_set, $goods_id);
		}

		$w_db->commit();

		$this->getView()->assign("title", '操作提醒');
		$this->getView()->assign("desc", '商品修改成功!');
		$this->getView()->assign("type", 'success');
		$this->getView()->display('common/tips.html');
		return false;

	}

  function upAction(){
    global $w_db;

    //查询出商品信息
    $goods_id = $this->_get('id',0);

    $w_db->update('goods',['is_on_sale'=>1],['goods_id'=>$goods_id]);

    $this->getView()->assign("title", '操作提醒');
    $this->getView()->assign("desc", '商品已上架!');
    $this->getView()->assign("type", 'success');
    $this->getView()->display('common/tips.html');
    return false;
  }

	function pullwordAction(){

		$goods_name = $this->_post('name');
		//$goods_name = "碎花连衣裙";

		$redis = new Redis();
		$redis->pconnect($this->redis_server, $this->redis_port, 1);//长链接，本地host，端口为6379，超过1秒放弃链接

		//根据空格切分 输入
		$arr = explode(" ", $goods_name);

		$all_words = [];

		foreach($arr as $item){
			$all_words = array_merge($this->pullword($item, $redis), $all_words);
		}
		$data = [];
		$data['status'] = 1;
		$data['res'] = $all_words;

		ajaxReturn($data);
		return false;
	}

	function pullword($str, $redis){

		$charset = "UTF-8";
		//词条 redis集合
		$set_name = "word_set";

		//最大的词有10个字符,这里考虑了英文单词.
		$max_word_len = 10;

		$finish_word = [];

		$search_str = $str;

		$remain_str = $search_str;

		/* 正向最大词匹配 */
		//无限循环,符合特定条件才退出
		for(; ;){

			//如果待切分的短语 少于最大词长度
			if(mb_strlen($remain_str, $charset) < $max_word_len){
				$word_len = mb_strlen($remain_str, $charset);
			}else{
				$word_len = $max_word_len;
			}

			$maybe_word = mb_substr($remain_str, 0, $word_len, $charset);

			//判断分词是否完成
			$pullword_finish = false;

			//这个标示如果是true,则 maybe_word 里肯定有一个词.否者没有词则退出分词,分词结束
			$is_mark = false;

			for($i = 0; $i < $word_len; $i++){
				$tmp_word = mb_substr($maybe_word, 0, $word_len - $i, $charset);
				$word_exist = $redis->sIsMember($set_name, $tmp_word);

				//找到词,退出循环
				if($word_exist){
					$is_mark = true;
					array_push($finish_word, $tmp_word);

					//去除已经匹配到的短语
					$remain_str = substr($remain_str, strlen($tmp_word));
					if(mb_strlen($remain_str, $charset) <= 0){
						//分词已经完成
						$pullword_finish = true;
					}

					break;
				}else{

				}

			}

			//$maybe_word 里没有一个词,分词完成
			if(false == $is_mark){
				break;
			}

			if($pullword_finish){
				break;
			}

		}

		/* 逆向最大词匹配 */
		for(; ;){

			//如果待切分的短语 少于最大词长度,那就从待切分短语的开头读取字符串

			//如果待切分的短语 大于最大词长度,那就从 (待切分短语长度-最大词长度[10]) 位置读取 最大词长度[10] 个字符出来匹配

			//如果待切分的短语 少于最大词长度
			if(mb_strlen($remain_str, $charset) <= $max_word_len){
				$maybe_word = mb_substr($remain_str, 0, $max_word_len, $charset);
			}else{
				$maybe_word = mb_substr($remain_str, mb_strlen($remain_str, $charset) - $max_word_len, $max_word_len, $charset);
			}

			//判断分词是否完成
			$pullword_finish = false;

			//这个标示如果是true,则 maybe_word 里肯定有一个词.否者没有词则退出分词,分词结束
			$is_mark = false;

			$word_len = mb_strlen($maybe_word, $charset);

			for($i = 0; $i < $word_len; $i++){
				$tmp_word = mb_substr($maybe_word, $i, $word_len - $i, $charset);
				$word_exist = $redis->sIsMember($set_name, $tmp_word);

				//找到词,退出循环
				if($word_exist){
					$is_mark = true;
					array_push($finish_word, $tmp_word);

					//词在待切分短语的偏移位置
					$tmp_word_position = mb_strlen($remain_str, $charset) - mb_strlen($tmp_word, $charset);

					//去除已经匹配到的短语
					$remain_str = mb_substr($remain_str, 0, $tmp_word_position, $charset);
					if(mb_strlen($remain_str, $charset) <= 0){
						//分词已经完成
						$pullword_finish = true;
					}

					break;
				}else{

				}

			}

			//$maybe_word 里没有一个词,分词完成
			if(false == $is_mark){
				break;
			}

			if($pullword_finish){
				break;
			}
		}
		return $finish_word;

	}

	function pullwordReportAction(){
		global  $w_db;
		$goods_name = $this->_post('goods_name');
		$pullword = $this->_post('pullword');
		$data = [];
		$data['goods_name'] = $goods_name;
		$data['pullword'] = $pullword;
		$data['shop_id'] = $this->shop_id;
		$w_db->insert('search_pullword_report',$data);
		/*
		$this->getView()->assign("title", '操作提醒');
		$this->getView()->assign("desc", '反馈已提交，工作人员正在火速处理，请继续添加商品。!');
		$this->getView()->assign("type", 'success');
		$this->getView()->display('common/tips.html');
		*/
		$data = [];
		$data['status'] = 1;
		ajaxReturn($data);
		return false;
	}


}
