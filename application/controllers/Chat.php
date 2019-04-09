<?php

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Keychain; // just to make our life simpler
use Lcobucci\JWT\Signer\Hmac\Sha256; // 如果在使用 ECDSA 可以使用 Lcobucci\JWT\Signer\Ecdsa\Sha256

class ChatController extends UserController {

	public function init(){
		parent::init();
	}

	public function index2Action(){
		$account = $this->_post('account');
		$password = $this->_post('password');
		$account = "13723772347";
		$password = "88888888";

		$hash = password_hash($password, PASSWORD_DEFAULT);

		global $r_db;
		$user_info = $r_db->get("client", "*", ['account' => $account]);
		if(!$user_info){
			$ajax_data = [];
			$ajax_data['status'] = -1;
			$ajax_data['status_name'] = "账号不存在，请核对账号";
			ajaxReturn($ajax_data);
		}else{
			//token 过期时间
			$token_time = 3600 * 24 * 7;
			$password_right = password_verify($password, $user_info['password']);
			if($password_right){
				$builder = new Builder();
				$signer = new Sha256();
				// 设置发行人
				$builder->setIssuer('http://example.com');
				// 设置接收人
				$builder->setAudience('http://example.org');
				// 设置id
				$builder->setId('4f1g23a12aa', true);
				// 设置生成token的时间
				$builder->setIssuedAt(time());
				// 设置在60秒内该token无法使用
				$builder->setNotBefore(time() - 60);
				// 设置过期时间
				$builder->setExpiration(time() + $token_time);
				// 给token设置一个id
				$builder->set('client_id', $user_info['client_id']);
				$builder->set('client_type', 'shoper_auth');
				// 对上面的信息使用sha256算法签名
				$builder->sign($signer, $this->password_key);
				// 获取生成的token
				$token = (string)$builder->getToken();
				$ajax_data = [];
				$ajax_data['status'] = 1;
				$ajax_data['status_name'] = "认证通过";
				$ajax_data['token'] = $token;
				$ajax_data['token_time'] = $token_time - 100;
				$ajax_data['nick_name'] = $user_info['nick_name'];
				$ajax_data['account'] = $user_info['account'];
				$ajax_data['distance'] = $user_info['distance'];
				ajaxReturn($ajax_data);
			}else{
				$ajax_data = [];
				$ajax_data['status'] = -2;
				$ajax_data['status_name'] = "密码错误，请重新输入密码";
				ajaxReturn($ajax_data);
			}
		}
		return false;
	}

	function indexAction(){
		global $r_db;
		//生成一个token
		//token 过期时间
		$token_time = 30;
		$builder = new Builder();
		$signer = new Sha256();
		// 设置发行人
		$builder->setIssuer('http://example.com');
		// 设置接收人
		$builder->setAudience('http://example.org');
		// 设置id
		$builder->setId('4f1g23a12aa', true);
		// 设置生成token的时间
		$builder->setIssuedAt(time());
		// 设置在60秒内该token无法使用
		$builder->setNotBefore(time() - 60);
		// 设置过期时间
		$builder->setExpiration(time() + $token_time);
		// 给token设置一个id
		$builder->set('client_id', $this->shop_id);
		$builder->set('client_type', 'shoper_auth');
		// 对上面的信息使用sha256算法签名
		$builder->sign($signer, $this->password_key);
		// 获取生成的token
		$token = (string)$builder->getToken();

		$this->getView()->assign('token', $token);
		$this->getView()->assign('img_upload_domain', $this->img_upload_domain);
		$this->getView()->assign('img_diaplay_domain', $this->img_diaplay_domain);
		$this->getView()->assign('_title', $this->get_company_name() . "客服系统");

	}

	function sessionAction(){
		global $r_db;

		//查询出所有会话列表
		$where = ['chat_session.shop_id' => $this->shop_id, "ORDER" => ["chat_session.shop_read" => "ASC"]];
		$field = ['chat_session.client_id', 'chat_session.shop_id', 'chat_session.shop_read', 'client.nick_name'];
		$session_list = $r_db->select("chat_session", ["[>]client" => ["client_id" => "client_id"]], $field, $where);


		$this->getView()->assign('session_list', json_encode($session_list));
		$this->getView()->assign('img_upload_domain', $this->img_upload_domain);
		$this->getView()->assign('img_diaplay_domain', $this->img_diaplay_domain);
		$this->getView()->assign('_title', $this->get_company_name() . "客服系统");

	}

	function sessionreadAction(){
		global $w_db;
		$client_id = $this->_post('client_id');

		$update_data = ['shop_read' => 1];
		$where = ['shop_id' => $this->shop_id, 'client_id' => $client_id,];
		$w_db->update("chat_session", $update_data, $where);
		ajaxReturn(['status' => 1]);
		return false;

	}

	function chatAction(){
		global $r_db;

		$this->getView()->assign('img_upload_domain', $this->img_upload_domain);
		$this->getView()->assign('img_diaplay_domain', $this->img_diaplay_domain);
		$this->getView()->assign('_title', $this->get_company_name() . "客服系统");

	}

	function getnicknameAction(){
		global $r_db;
		$client_id = $this->_post('client_id');
		$nick_name = $r_db->get('client', 'nick_name', ['client_id' => $client_id]);
		echo $nick_name;
		return false;
	}

	function getchathistoryAction(){
		global $r_db;
		$client_id = $this->_post('client_id');
		$page = $this->_post('p', 1);
		//$page = 1;
		//$client_id = 1;
		$page_num = 10;
		$page_start = ($page - 1) * $page_num;

		$where = ['shoper_id' => $this->shop_id, 'client_id' => $client_id, "ORDER" => ["message_id" => "DESC"], 'LIMIT' => [$page_start, $page_num]];

		$field = ["message_id", "message_type(messageType)", "sender_type(senderType)", "sender_type(senderType)", "shoper_id(shoperId)", "client_id(clientId)", "content"];
		//todo,倒序取出最后20条聊天记录。向上拉分页获取。
		$message_list = $r_db->select('chat_history', $field, $where);

		ajaxReturn($message_list);
		return false;
	}

}
