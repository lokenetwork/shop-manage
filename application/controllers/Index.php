<?php
/**
 * @name IndexController
 * @author root
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class IndexController extends UserController {

  /*
   * 操作盘
   * */
  public function indexAction(){
    $Ctime = new Ctime();
    global $r_db;

    $start_time = $this->_get('start_time', $Ctime->short_time());
    $end_time = $this->_get('end_time', $Ctime->addDate($Ctime->short_time(), 1, 'd'));
	  $start_time = $this->_get('start_time', '2019-04-08');
	  $end_time = $this->_get('end_time', '2019-04-09');
    $type = $this->_get('type', 'hour');


    $condition = [];
    $condition['AND']['shop_id'] = $this->shop_id;
    $condition['AND']['view_time[>=]'] = $start_time . ' 00:00:00';
    $condition['AND']['view_time[<=]'] = $end_time . ' 00:00:00';
    //限制查询1500条数据
    $condition["LIMIT"] = 1500;
    if( 'hour' == $type ){
      $view_data = $r_db->select('shop_view_hour', '*', $condition);
    }else if('day' == $type){
      $view_data = $r_db->select('shop_view_day', '*', $condition);
    }else if('month' == $type){
      $view_data = $r_db->select('shop_view_month', '*', $condition);
    }

    $chat_view_data = [];
    $chat_user_data = [];
    //处理数据
    foreach($view_data as $key => $item){
      $view_time = strtotime($item['view_time']) * 1000;
      $chat_view_data[$key][0] = $view_time;
      $chat_view_data[$key][1] = $item['view_num'];

      $chat_user_data[$key][0] = $view_time;
      $chat_user_data[$key][1] = $item['view_user_num'];
    }


    $this->getView()->assign("type", $type);
    $this->getView()->assign("start_timestamp", strtotime($start_time) * 1000);
    $this->getView()->assign("end_timestamp", strtotime($end_time) * 1000);
    $this->getView()->assign("start_time", $start_time);
    $this->getView()->assign("end_time", $end_time);
    $this->getView()->assign("chat_view_data", json_encode($chat_view_data));
    $this->getView()->assign("chat_user_data", json_encode($chat_user_data));
    $this->getView()->assign('_title', '店铺统计');


    return TRUE;
  }

  public function goodsAction(){
    $Ctime = new Ctime();
    global $r_db;

    //$start_time = $this->_get('start_time', $Ctime->short_time());
   $start_time = $this->_get('start_time', '2019-04-08');
	  //$end_time = $this->_get('end_time', $Ctime->addDate($Ctime->short_time(), 1, 'd'));
	  $end_time = $this->_get('end_time', '2019-04-09');
    $type = $this->_get('type', 'hour');

    $condition = [];
    $condition['AND']['shop_id'] = $this->shop_id;
    $condition['AND']['view_time[>=]'] = $start_time . ' 00:00:00';
    $condition['AND']['view_time[<=]'] = $end_time . ' 00:00:00';
    //todo,购买商品浏览统计服务可以看到前10个商品.
    $condition["LIMIT"] = 3;
    $condition["GROUP"] = 'goods_id';
    $condition["ORDER"] = ['sum_view_num'=>'DESC'];

    $field = ['shop_id','goods_id','sum_view_num' => Medoo::raw('SUM(<view_num>)')];
    
    if( 'hour' == $type ){
      $top_goods_list = $r_db->select('goods_view_hour', $field, $condition);
    }else if('day' == $type){
      $top_goods_list = $r_db->select('goods_view_day', $field, $condition);
    }else if('month' == $type){
      $top_goods_list = $r_db->select('goods_view_month', $field, $condition);
    }

    $flot_data = [];
    foreach($top_goods_list as $k=>$item){
      $condition = [];
      $condition['AND']['goods_id'] = $item['goods_id'];
      $condition['AND']['view_time[>]'] = $start_time . ' 00:00:00';
      $condition['AND']['view_time[<]'] = $end_time . ' 00:00:00';
      $condition["LIMIT"] = 1500;

      if( 'hour' == $type ){
        $g_view_data = $r_db->select('goods_view_hour', '*', $condition);
      }else if('day' == $type){
        $g_view_data = $r_db->select('goods_view_day', '*', $condition);
      }else if('month' == $type){
        $g_view_data = $r_db->select('goods_view_month', '*', $condition);
      }
      $chat_view_data = [];
      //处理数据
      foreach($g_view_data as $key => $value){
        $view_time = strtotime($value['view_time']) * 1000;
        $chat_view_data[$key][0] = $view_time;
        $chat_view_data[$key][1] = $value['view_num'];
      }
      $g_info = $r_db->get('goods','*',['goods_id'=>$item['goods_id']]);
      $flot_data[$k]['data'] = $chat_view_data;
      $flot_data[$k]['label'] = 'ID: '.$item['goods_id'];
      $flot_data[$k]['id'] = $item['goods_id'];
      $flot_data[$k]['goods_name'] = $g_info['goods_name'];
    }

    $this->getView()->assign("type", $type);
    $this->getView()->assign("start_timestamp", strtotime($start_time) * 1000);
    $this->getView()->assign("end_timestamp", strtotime($end_time) * 1000);
    $this->getView()->assign("start_time", $start_time);
    $this->getView()->assign("end_time", $end_time);
    $this->getView()->assign("flot_data", json_encode($flot_data));
    $this->getView()->assign("full_flot_data", $flot_data);
    $this->getView()->assign('_title', '店铺统计');
    return TRUE;
  }


  public function indexBackAction($name = "Stranger"){
    //1. fetch query
    $get = $this->getRequest()->getQuery("get", "default value");
    //2. fetch model
    $model = new SampleModel();
    //3. assign
    $this->getView()->assign("content", $model->selectSample());
    $this->getView()->assign("name", $name);
    //phpinfo();
    //4. render by Yaf, 如果这里返回FALSE, Yaf将不会调用自动视图引擎Render模板
    return TRUE;
  }

  public function testAction(){
    $Ctime = new Ctime();
    global $w_db;

    /*
    //随机插入 shop_view 表数据
    $time = strtotime('2019-02-03 00:00:00');
    for( $i=0; $i < 100 ;$i++ ){
      $num = rand(1,1000);
      $shop_view_data = [];
      $shop_view_data['view_num'] = 10+$num;
      $shop_view_data['view_user_num'] = $num*4/5;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('shop_view_hour',$shop_view_data);
    }
    */

    /*
    $time = strtotime('2018-02-03 00:00:00');
    for( $i=0; $i < 100 ;$i++ ){
      $shop_view_data = [];
      $shop_view_data['view_num'] = 10+$i;
      $shop_view_data['view_user_num'] = 5+$i;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('shop_view_day',$shop_view_data);
    }
    */

    /*
    $time = strtotime('2018-02-03 00:00:00');
    for( $i=0; $i < 100 ;$i++ ){
      $shop_view_data = [];
      $shop_view_data['view_num'] = 10+$i;
      $shop_view_data['view_user_num'] = 5+$i;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('shop_view_month',$shop_view_data);
    }
    */

    $time = strtotime('2018-02-03 00:00:00');
    for( $i=0; $i < 100 ;$i++ ){
      $shop_view_data = [];
      $shop_view_data['view_num'] = rand(1,1000);
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 92;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('goods_view_hour',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = rand(1,1000);

      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 94;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('goods_view_hour',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = rand(1,1000);

      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 96;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('goods_view_hour',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = rand(1,1000);

      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 98;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('goods_view_hour',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = rand(1,1000);
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 100;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600));
      $w_db->insert('goods_view_hour',$shop_view_data);
    }

    /*
    $time = strtotime('2018-02-03 00:00:00');
    for( $i=0; $i < 10 ;$i++ ){
      $shop_view_data = [];
      $shop_view_data['view_num'] = $i;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 92;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('goods_view_day',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+2;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 94;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('goods_view_day',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+4;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 96;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('goods_view_day',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+6;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 98;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('goods_view_day',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+8;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 100;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24));
      $w_db->insert('goods_view_day',$shop_view_data);
    }
    */
    /*
    $time = strtotime('2018-02-03 00:00:00');
    for( $i=0; $i < 10 ;$i++ ){
      $shop_view_data = [];
      $shop_view_data['view_num'] = $i;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 92;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('goods_view_month',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+2;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 94;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('goods_view_month',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+4;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 96;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('goods_view_month',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+6;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 98;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('goods_view_month',$shop_view_data);

      $shop_view_data = [];
      $shop_view_data['view_num'] = $i+8;
      $shop_view_data['shop_id'] = 1;
      $shop_view_data['goods_id'] = 100;
      $shop_view_data['view_time'] = date("Y-m-d H:i:s",$time+($i*3600*24*30));
      $w_db->insert('goods_view_month',$shop_view_data);
    }
    */

    return false;
  }
}
