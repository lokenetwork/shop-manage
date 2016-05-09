<?php

/**
 * @name GoodsController
 * @author root
 * @desc 商品控制器
 *
 */
class GoodsController extends UserController {

  public function init(){
    parent::init();
  }

  function indexAction(){
    global $r_db;

    $goods_name =get('goods_name');
    $page =get('page', 1);
    $is_on_sale =get('is_on_sale', 1);

    $condition = ['AND' => ['shop_id' => $_SESSION['shop_id'], 'is_delete' => 0], 'ORDER' => ['goods_id DESC']];
    $condition['AND']['is_on_sale'] = $is_on_sale;

    if($goods_name){
      $condition['AND']['goods_name[~]'] = $goods_name;
    }

    $spec_num = $r_db->count('goods', $condition);
    $Pagination = new Pagination($spec_num, $page, 20);
    $this->getView()->assign('pagination', $Pagination->show());

    $condition["LIMIT"] = [$Pagination->firstRow, $Pagination->listRows];
    $fields = ['goods_id', 'goods_name', 'first_picture', 'goods_price', 'goods_number', 'is_on_sale', 'page_view'];
    $goods_list = $r_db->select('goods', $fields, $condition);

    $this->getView()->assign('goods_name', $goods_name);
    $this->getView()->assign('goods_list', $goods_list);
    $this->getView()->assign('pagination', $Pagination->show());
    $this->getView()->assign('_title', 'Goods list');

  }

  function get_category_model_attribute_info($category_id){
    global $r_db;
    //SELECT the category model id
    $category_model_id = $r_db->get('category', 'model_id', ['cat_id' => $category_id]);

    //IF the category had bind the goods model, go on
    if($category_model_id){
      $goods_model_info_where = ['id' => $category_model_id];
      $goods_model_spec_ids = $r_db->get('goods_model', 'spec_ids', $goods_model_info_where);
    }


    if($goods_model_spec_ids){
      $goods_model_spec_ids = $goods_model_spec_ids ? unserialize($goods_model_spec_ids) : array();

      $goods_spec_ids = [];
      foreach($goods_model_spec_ids as $key => $item){
        $goods_model_spec_info[$item['id']]['is_attr'] = $item['is_attr'];
        $goods_model_spec_info[$item['id']]['is_required'] = $item['is_required'];
        $goods_spec_ids[] = $item['id'];
      }

      if(!empty($goods_spec_ids)){
        //$sql = "select * from `$spec` where id in ($id) order by find_in_set(id,'$id')";

        $goods_spec_info_filed = ['id', 'name', 'input_type', 'show_type', 'value'];
        $goods_spec_info_where = ['AND' => ['id' => $goods_spec_ids], "ORDER" => ["id", $goods_spec_ids]];
        $goods_spec_info = $r_db->select('goods_spec', $goods_spec_info_filed, $goods_spec_info_where);
        $category_model_attribute_info['special_attribute_info']['special_attribute_num'] = 0;
        $category_model_attribute_info['list'] = [];
        if($goods_spec_info){
          $i = 0;
          foreach($goods_spec_info as $k => $v){
            $category_model_attribute_info['list'][$i]['id'] = $v['id'];
            $category_model_attribute_info['list'][$i]['name'] = $v['name'];
            /*
            if( $v['name'] == '颜色' ){
              $goods_model_spec_info[$v['id']]['is_attr'] = 1;
            }
            */
            $category_model_attribute_info['list'][$i]['input_type'] = $v['input_type'];
            $category_model_attribute_info['list'][$i]['show_type'] = $v['show_type'];
            $category_model_attribute_info['list'][$i]['attr_type'] = $goods_model_spec_info[$v['id']]['is_attr'];

            if( $goods_model_spec_info[$v['id']]['is_attr'] == 0 ){
              $category_model_attribute_info['special_attribute_info']['special_attribute_num']++;
              $category_model_attribute_info['list'][$i]['special_attribute_index'] = $category_model_attribute_info['special_attribute_info']['special_attribute_num'];
              $category_model_attribute_info['special_attribute_info']['title'][$category_model_attribute_info['list'][$i]['special_attribute_index']] = $v['name'];
            }
            $category_model_attribute_info['list'][$i]['is_required'] = $goods_model_spec_info[$v['id']]['is_required'];
            $category_model_attribute_info['list'][$i]['spec_value'] = unserialize($v['value']);
            $i++;
          }
        }
        return $category_model_attribute_info;
      }
    }
  }

  function addAction(){
    global $r_db;
    $category_id =get('category_id', 8443);
    $this->getView()->assign('category_id', $category_id);


    $this->getView()->assign('_title', 'Add goods');


    $category_model_attribute_info = $this->get_category_model_attribute_info($category_id);
    $this->getView()->assign('category_model_attribute_list', $category_model_attribute_info['list']);
    $this->getView()->assign('special_attribute_info_json', json_encode( $category_model_attribute_info['special_attribute_info'] ));
    $category_model_attribute_info['special_attribute_info']['all_title'] = '';
    foreach( $category_model_attribute_info['special_attribute_info']['title'] as $value ){
      $category_model_attribute_info['special_attribute_info']['all_title'] .= $value.'+';
    }
    $category_model_attribute_info['special_attribute_info']['all_title'] = trim($category_model_attribute_info['special_attribute_info']['all_title'],'+');
    $this->getView()->assign('special_attribute_info', $category_model_attribute_info['special_attribute_info'] );

    //查询出最底层的分类信息
    //$sql = "SELECT cat_name, arr_parent_id FROM $t_category WHERE cat_id = {$cat_id}";
    $last_category_info_field = ['cat_name', 'arr_parent_id'];
    $last_category_info_where = ['cat_id' => $category_id];
    $last_category_info = $r_db->get('category', $last_category_info_field, $last_category_info_where);
    $this->getView()->assign('last_category_info', $last_category_info);

    $parent_category_ids = explode(',', $last_category_info['arr_parent_id']);
    $parent_category_info_where = ['cat_id' => $parent_category_ids];
    $parent_category_info = $r_db->select('category', 'cat_name', $parent_category_info_where);
    $this->getView()->assign('parent_category_info', $parent_category_info);

    $goods_unit_field = ['unit_value'];
    $goods_unit_where = ['available' => 1];
    $goods_unit_info = $r_db->select('goods_unit', $goods_unit_field, $goods_unit_where);
    $this->getView()->assign('goods_unit_info', $goods_unit_info);


  }

  private function update_shop_goods_sku(){
    return fasle;
    global $w_db;
    //店铺商品sku,要算规格
    $update_shop_info_field = ["goods_sku[+]" => 1,];
    $update_shop_info_where = ['shop_id' => $_SESSION['shop_id']];
    $w_db->update('shop_info', $update_shop_info_field, $update_shop_info_where);
  }

  private function insert_goods_base_info(){
    global $w_db;

    $insert_goods_base_data = [
      'goods_name' =>get('goods_name'),
      'offer_type' =>get('offer_type'),
      'cat_id' =>get('category_id'),
      'goods_number' =>get('goods_number'),
      'unit' =>get('goods_unit'),
      'goods_price'	=>get('goods_price')*100,
      'goods_weight' =>get('goods_weight')*1000,
      'is_on_sale' =>get('is_on_sale'),
      //'transport_template_id' =>get('transport_template_id'),
      'shop_id'=>$_SESSION['shop_id'],
      'add_time' => $GLOBALS['TimeStamp'],
    ];
    $goods_id = $w_db->insert('goods', $insert_goods_base_data);
    return $goods_id;
  }
  private function transform_goods_attr_value_to_string($attr_array_value){
    $attr_array_string = '';
    foreach($attr_array_value as $value){
      $attr_array_string .= $value . "\r\n";
    }
    return $attr_array_string;
  }

  private function insert_goods_attr($goods_id){
    global $w_db;
    $goods_attr = post('goods_attr');
    $category_model_attribute_info = $this->get_category_model_attribute_info(post('category_id'));
    if(empty($goods_attr)){
      return false;
    }
    $insert_goods_attr_data = [];
    $i = 0;
    foreach($goods_attr as $attr_id => $attr_array_value){
      if($attr_array_value){

        $insert_goods_attr_data[$i]['goods_id'] = $goods_id;
        $insert_goods_attr_data[$i]['attr_id'] = $attr_id;
        $attr_value_string = $this->transform_goods_attr_value_to_string($attr_array_value);
        $insert_goods_attr_data[$i]['attr_values'] = $attr_value_string;
        foreach($category_model_attribute_info['list'] as $item){
          if( $item['id'] == $attr_id ){
            if( $item['attr_type'] == 1 ){
              $insert_goods_attr_data[$i]['is_sku_attr'] = 0;
            }else{
              $insert_goods_attr_data[$i]['is_sku_attr'] = 1;
            }
          }
        }
        $i++;
      }
    }
    $insert_attr_result =  $w_db->insert('goods_attr',$insert_goods_attr_data);
    return $insert_attr_result;
  }
  private function insert_goods_sku($goods_id){
    global $w_db;
    $goods_sku = post('goods_sku');
    $insert_goods_sku_data = [];
    $i = 0;

    foreach($goods_sku as $sku_value_name => $sku_other_value){
      $insert_goods_sku_data[$i]['sku_value'] = $sku_value_name;
      $insert_goods_sku_data[$i]['goods_id'] = $goods_id;
      $insert_goods_sku_data[$i]['shop_id'] = $_SESSION['shop_id'];
      $insert_goods_sku_data[$i]['goods_price'] = $sku_other_value['goods_sku_price']*100;
      $insert_goods_sku_data[$i]['goods_weight'] = $sku_other_value['goods_sku_weight']*1000;
      $insert_goods_sku_data[$i]['goods_store'] = $sku_other_value['goods_sku_store'];
      $insert_goods_sku_data[$i]['commodity_code'] = $sku_other_value['sku_commodity_code'];
      $i++;
    }
    $insert_sku_result =  $w_db->insert('goods_sku',$insert_goods_sku_data);
    return $insert_sku_result;
  }


  function addPostAction(){

    global $w_db;

    //$goods_id = $this->insert_goods_base_info();
    $goods_id = 1231231;
    if($goods_id){
      $this->insert_goods_attr($goods_id);
      $this->insert_goods_sku($goods_id);
      $this->update_shop_goods_sku();
    }else{
    }
    exit;
  }


}