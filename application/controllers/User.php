<?php
/**
 * @name UserController
 * @author root
 * @desc use this control must be login
 * @see
 */
class UserController extends BaseController {
  public $shop_id;

  public function init(){
    parent::init();
    $this->checkLoginAction();
    $this->shop_id = $_SESSION['shop_id'];

  }
  /**
   * get the shop info from database
   */
  public function useInfoAction($field){
    if( $field == '*' ){
      //limit select with *
      return false;
    }
    $condition = [
      "shop_id" => $_SESSION['shop_id'],
    ];
    $c_info = $GLOBALS['r_db']->get("shop_info", $field, $condition);
    return $c_info;
  }

  function logout(){
    unset($_SESSION['admin_id']);
    return false;
  }


  //Check the user is login or not
  function checkLoginAction(){
    if( !isset($_SESSION['shop_id']) ){
      $this->setViewPath(VIEW_PATH);
      $login_url = 'http://'.$this->passport_domain.'/Index/shop';
      $this->getView()->assign("title", '温馨提示');
      $this->getView()->assign("desc", '请先登录!');
      $this->getView()->assign("url", $login_url);
      $this->getView()->assign("type", 'warning');
      $this->getView()->display('common/tips.html');
      exit;
    }
  }


}