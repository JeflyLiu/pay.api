<?php
namespace App\Controller;

use App\Controller\Base;
use App\Controller\Response;
use App\Model\Account as AccountModel;

class Account extends Base {
	
	/**
	 * 创建钱包账户
	 * @param int $member_id
	 * @param int $account_id 可选，可指定account_id,用于创建系统账户
	 * @return array ('result'=>0,'msg'=>'', 'account_id'=>int)
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为001
	 * 可能的错误码为1:账号account_id已存储 2：此member_id已创建过账户
	 */
	function create(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'member_id'=>array('int','required'),
				'account_id'=>array('int','optional')
		);
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
			return;
		}
		$account_model = new \App\Service\Account();
		$member_id = $params['member_id'];
		$account_id = NULL;
		if(isset($_GET['account_id'])){
			$account_id = $_GET['account_id'];
		}
		$result = $account_model->create($member_id, $account_id);
		Response::render(200,$result);
	}
	
	/**
	 * 通过account_id获取账户信息
	 * @param int $account_id
	 * @return array
	 * account_id
	 * member_id
	 * balance
	 * freeze
	 * status
	 * create_time
	 * last_update
	 */
	function getByAccountId(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required')
		);
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		
		$account_id = $params['account_id'];
		$account_model = new \App\Service\Account();
		$result = $account_model->getByAccountId($account_id);
		unset($result['pwd']);
		Response::render(200,$result);
	}
	
	
	/**
	 * 通过member_id获取账户信息
	 * @param int $member_id
	 * @return array
	 * account_id
	 * member_id
	 * balance
	 * freeze
	 * status
	 * create_time
	 * last_update
	 */
	function getByMemberId(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'member_id'=>array('int','required'),
				'create_if_not_exist'=>array('int','optional')
		);
		$result = $this->validateParams($params_config, $params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		$account_model = new \App\Service\Account();
		
		$member_id = $params['member_id'];
		if(isset($_GET['create_if_not_exist']) && $_GET['create_if_not_exist'] == 1){
			$result = $account_model->getByMemberId($member_id, true);
		}
		else{
			$result = $account_model->getByMemberId($member_id);
		}
		
		
		unset($result['pwd']);
		Response::render(200,$result);
	}
	
	/**
	 * 支付密码验证，每天只允许出错三次
	 * @param int $account_id
	 * @param string $pay_password
	 * @return array ('result'=>0,'msg'=>'')
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为003
	 * 可能的错误码为1:账户不存在，2:密码验证失败 ，3：账户密码已被锁定
	 */
	function validatePayPassword($account_id, $pay_password){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				'pay_password'=>array('string','required'),
		);
		$result = $this->validateParams($params_config,$params);
		if($result['result']>0){
			Response::render(200,$result);
		}
		
		$account_id = $params['account_id'];
		$pay_password = $params['pay_password'];
		$account_model = new \App\Service\Account();
		$ret = $account_model->validatePayPassword($account_id,$pay_password);
		unset($result['pwd']);
		Response::render(200,$ret);
	}
	
	function setPayPassword(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				'pay_password'=>array('string','required'),
		);
		$result = $this->validateParams($params_config,$params );
		if($result['result']>0){
			Response::render(200,$result);
		}
		
		$account_id = $params['account_id'];
		$pay_password = $params['pay_password'];
		$result = \App\Service\Account::setPayPassword($account_id, $pay_password);
		Response::render(200,$result);
	}
	
	function changePayPassword(){
		$params = array_merge($_POST, $_GET);
		$params_config = array(
				'account_id'=>array('int','required'),
				'old_pay_password'=>array('string','required'),
				'new_pay_password'=>array('string','required'),
		);
		$result = $this->validateParams($params_config,$params);
		if($result['result']>0){
			return json_encode($result);
		}
		
		$account_id = $params['account_id'];
		$old_pay_password = $params['old_pay_password'];
		$new_pay_password = $params['new_pay_password'];
		$result = \App\Service\Account::changePayPassword($account_id,$old_pay_password,$new_pay_password);
		Response::render(200,$result);
	}

	public function getShow($id)
	{
		$model = AccountModel::find($id);

		if (!$model)
		{
			return Response::render(404);
		}

		return Response::render(200, $model->toArray());
	}
}

?>