<?php

/**
 * Created by PhpStorm.
 * User: loken_mac
 * Date: 1/22/16
 * Time: 9:52 PM
 */
class BaseController extends Yaf_Controller_Abstract {

  protected $passport_name;
  protected $root_domain;
  protected $passport_domain;
  protected $img_upload_domain;
  protected $img_diaplay_domain;
  protected $redis_server;
  protected $redis_port;
  public $passport_info;
	protected $password_key;


	public function init(){
    $this->passport_name = Yaf_Application::app()->getConfig()->passport->name;
    $this->passport_domain = Yaf_Application::app()->getConfig()->passport->domain;
    $this->passport_info = $this->getPassportInfo();
    $this->root_domain = GetUrlToDomain($_SERVER['SERVER_NAME']);
		$this->password_key = Yaf_Application::app()->getConfig()->password_key;

    $this->img_upload_domain = Yaf_Application::app()->getConfig()->img->upload_domain;
    $this->img_diaplay_domain = Yaf_Application::app()->getConfig()->img->diaplay_domain;

    $this->redis_port = Yaf_Application::app()->getConfig()->redis->port;
    $this->redis_server = Yaf_Application::app()->getConfig()->redis->server;

    $this->getView()->assign("css_rel", CSS_REL);
    $this->getView()->assign("css_type", CSS_TYPE);
    if( ini_get("yaf.environ") == 'dev' ){
      $this->getView()->assign("client_less", '<script src="/static/common_js/less.js"></script>');
    }else{
      $this->getView()->assign("client_less", '');
    }
    $this->getView()->company_name = $this->get_company_name();

    $this->passport_deal();
  }

  /*
   * 处理passport登录自动写入session,登出自动删除session
   * */
  private function passport_deal(){
    global $w_db;

    if( $this->passport_info->passport_login_status > 0){

      //没有登录自动实现登录
      if(!isset($_SESSION['shop_id'])){
        $Ctime = new Ctime();

        $condition = ["passport_user_id" => $this->passport_info->passport_user_info->user_id];
        $field = '*';
        $shop_info = $w_db->get("shop", $field, $condition);

        $_SESSION['shop_id'] = $shop_info['shop_id'];

        //记录店铺的登陆日志.
        $login_info = [];
        $login_info['shop_id'] = $shop_info['shop_id'];
        $login_info['login_time'] = $Ctime->long_time();
        $login_info['ip'] = ip2long('192.168.1.1');
        $w_db->insert('shop_login',$login_info);

      }

    }else if( $this->passport_info->passport_login_status === -1 ) {
      //Delete session
      unset($_SESSION['shop_id']);
    }
  }

  /**
   * get the group name quickly
   */
  public function get_company_name(){
    $condition = ["name" => 'company_name',];
    $field = ['value'];
    $company_info = $GLOBALS['r_db']->get("setting", $field, $condition);
    return $company_info['value'];
  }

  /*
   * pdo 查看错误信息demo
   * */
  function pdo_error_demo(){
    global $r_db;
    var_dump($r_db->pdo->errorInfo());
  }

  function getPassportInfo(){
    $Curl = new Curl();
    $data['passport_login_key'] = '-';
    if( isset($_COOKIE[$this->passport_name]) ){
      $data['passport_login_key'] = $_COOKIE[$this->passport_name];
    }
    $passport_info = ($Curl->http($this->passport_domain.'/User/echoJsonInfo', $data, 'GET', array("Content-type: text/html; charset=utf-8")));
    return json_decode($passport_info);
  }



  /*
   * 再封装下get,post,为以后过滤做准备,直接改yaf我们不熟
   * */
  protected function _get($name, $default_value = ''){
    $Yaf_Request_Http = new Yaf_Request_Http();
    $value = $Yaf_Request_Http->get($name);
    if( $value === null ){
      $responed = $default_value;
    }else{
      $responed = $value;
    }
    if( is_string($responed) ){
      $responed = trim($responed);
    }
    return $responed;
  }

  protected function _post($name, $default_value = ''){
    $Yaf_Request_Http = new Yaf_Request_Http();
    $value = $Yaf_Request_Http->getPost($name);
    if( $value === null  ){
      $responed = $default_value;
    }else{
      $responed = $value;
    }
    if( is_string($responed) ){
      $responed = trim($responed);
    }
    return $responed;
  }

}
