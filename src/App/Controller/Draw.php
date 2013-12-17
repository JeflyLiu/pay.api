<?php
namespace App\Controller;

use App\Controller\Base;
use App\Controller\Response;
class Draw extends Base {

	/**
	 * 提交提现申请
	 */
	function apply(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				"account_id"=>array('int','required'),
				"amount"=>array('int','required'),
				"card_type"=>array('int','required'),
				"card_name"=>array('string','required'),
				"card_no"=>array('string','required'),
				"bank_id"=>array('int','optional'),
				"bank_name"=>array('string','optional'),
				"bank_province"=>array('int','optional'),
				"bank_city"=>array('int','optional'),
				"pay_password"=>array('string','required'),
				//'is_default'=>array('int','required'),
		);
		
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		$apply_id = \App\Service\Draw::apply($params, $params["pay_password"]);
		$result['draw_id'] = $apply_id;
		Response::render(200,$result);
	}
	
	/**
	 * 打款成功完结提现申请
	 */
	function finishApply(){
		$params = array_merge($_POST, $_GET);
		$apply_id = $params["draw_id"];
		$pay_user = $params["pay_user"];
		$pay_voucher = $params["pay_voucher"];
		$pay_type = $params["pay_type"];
		$pay_amount = $params["pay_amount"];
		var_dump($params);
		$result = \App\Service\Draw::finishApply($apply_id, $pay_voucher, $pay_user, $pay_type, $pay_amount);
		Response::render(200,$result);
	}
	
	
	/**
	 * 打款未成功关闭申请
	 */
	function closeApply(){
		$params = array_merge($_POST, $_GET);
		$apply_id = $params["draw_id"];
		$pay_user = $params["pay_user"];
		$pay_note = $params["pay_note"];
		
		$result = \App\Service\Draw::closeApply($apply_id, $pay_user, $pay_note);
		Response::render(200,$result);
	}
	
	
	function getByApplyId(){
		$params = array_merge($_POST, $_GET);
		$apply_id = $params["draw_id"];
		$result = \App\Service\Draw::getByApplyId($apply_id);
		Response::render(200,$result);
	}
	
	/**
	 * 读取全部提现申请列表
	 * 
	 */
	function getAllApplyList(){
		$params = array_merge($_POST, $_GET);
		$result = \App\Service\Draw::getAllApplyList($params, null,null,null);
		Response::render(200,$result);
	}
	
	/**
	 * 读取个人提现申请列表
	 */
	function getApplyListByAccount(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				'time_start'=>array('string','optional'),
				'time_end'=>array('string','optional'),
		);
		
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		$account_id = $params['account_id'];
		$time_start = isset($params['time_start'])?$params['time_start']:'0000-0-0';
		$time_end = isset($params['time_end'])?$params['time_end']:date('Y-m-d H:i:s');
		$result = \App\Service\Draw::getApplyListByAccount($account_id, $time_start, $time_end);
		Response::render(200,$result);
	}
	
	function addDrawCard(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				'card_type'=>array('int','required'),
				'card_name'=>array('string','required'),
				'card_no'=>array('string','required'),
				//'is_default'=>array('int','required'),
		);
		
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		if($params['card_type'] == \App\Service\Draw::DRAW_CARD_TYPE_BANK){
			$params_config = array(
					'bank_id'=>array('int','required'),
					'bank_branch'=>array('string','required'),
					'bank_province'=>array('int','required'),
					'bank_city'=>array('int','required'),
			);
			$result = $this->validateParams($params_config, $params);
			if($result['result']>0){
				Response::render(200,$result);
			}
		}
		
		$result = \App\Service\Draw::addDrawCard($params);
		Response::render(200,$result);
	}
	
	static function deleteDrawCard($draw_card_id){
	}
	
	function getDrawCardById(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'draw_card_id'=>array('int','required'),
		);
		
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		$draw_card_id = $params["draw_card_id"];
		$result = \App\Service\Draw::getDrawCardById($draw_card_id);
		Response::render(200,$result);
	}
	
	function getDrawCardListByAccountId(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				//'is_default'=>array('int','required'),
		);
		
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		$account_id = $params["account_id"];
		$result = \App\Service\Draw::getDrawCardListByAccountId($account_id);
		Response::render(200,$result);
	}
}

?>