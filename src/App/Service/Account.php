<?php
namespace App\Service;
use Illuminate\Database\Capsule\Manager as DB;
class Account
{
	//账户状态
	const ACCOUNT_STATUS_NORMAL = 0;
	const ACCOUNT_STATUS_LOCKED = 1;
	const ACCOUNT_STATUS_NOT_INIT = 2;
	
	//密码的混淆
	const ACCOUNT_PWD_SALT = 'holyshit!';
	
	//通过token设定密码的token混淆
	const ACCOUNT_PWD_TOKEN_SALT = 'holyshit!';
	
	const PWD_WRONG_MAX_TIME = 3;
	
	const ACCOUNT_SYSTEM_ID = 10000;
	
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
	static function create($member_id, $account_id = NULL){
		$account = self::getByMemberId($member_id);
		$ret_code = 0;
		if(!empty($account)){
			$ret_code = 2;
		}
		elseif($account_id !== NULL){
			$account = self::getByAccountId($account_id);
			if(!empty($account)){
				$ret_code = 1;
			}
		}
		$result = array();
		if($ret_code === 0){
			$data = array('id'=>$account_id,
					'pwd'=>'',
					'balance'=>'0.00',
					'freeze_in'=>'0.00',
					'freeze_out'=>'0.00',
					'status'=>self::ACCOUNT_STATUS_NOT_INIT,
					'member_id'=>$member_id,
					'created_at'=>date('Y-m-d H:i:s'),
					'updated_at'=>date('Y-m-d H:i:s'),
					'deleted_at'=>'0000 00:00:00',
					'last_pwd_rest_time'=>'0000 00:00:00');
			//$insert_id = DB::insert("account", $data, true);
			
			$insert_id = DB::table("account","default")->insertGetId($data);
			
			if($insert_id == 0){
				
			}
			
			if($account_id === NULL){
				$result['account_id'] = $insert_id;
			}
			else{
				$result['account_id'] = $account_id;
			}
		}
		
		$error_msgs = array(1=>'账号account_id已存在',2=>'此member_id已创建过账户');
		
		$result['result'] = $ret_code;
		$result['msg'] = '';
		if($result['result'] >0){
			if(isset($error_msgs[$ret_code])){
				$result['msg'] = $error_msgs[$ret_code];
			}
			else{
				$result['msg'] = '未知错误';
			}
		}
		return $result;
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
	static function getByAccountId($account_id){
		/*
		$sql = "select * from `account` where id = ?";
		$db = DB::getPDO();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(1, $account_id , \PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		*/
		$result = DB::table("account","default")->where("id","=", $account_id)->first();
		
		if(!empty($result)){
			$result['is_pwd_locked'] = self::isPwdLocked($result['id'], $result['last_pwd_rest_time']);
		}
		//unset($result['ac_pwd']);
		return $result;
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
	static function getByMemberId($member_id, $create_if_not_exist = false){
		$result = DB::table("account","default")->where("member_id","=", $member_id)->first();
		if(!empty($result)){
			$result['is_pwd_locked'] = self::isPwdLocked($result['id'],$result['last_pwd_rest_time']);
		}
		else{
			if($create_if_not_exist){
				self::create($member_id);
				$result = DB::table("account","default")->where("member_id","=", $member_id)->first();
			}
			else{
				$result = array();
			}
		}
		//unset($result['ac_pwd']);
		return $result;
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
	static function validatePayPassword($account_id, $pay_password){
		
		$data = self::getByAccountId($account_id);
		
		$ret_code = 0;
		$result = array();
		if(empty($data)){
			$ret_code = 1;
		}
		else{
			if($data['is_pwd_locked'] === true){
				$ret_code = 3;
			}
			else{
				$encrypt_pwd = md5(self::ACCOUNT_PWD_SALT.$pay_password);
				if($data['pwd'] == $encrypt_pwd){
					$ret_code = 0;
				}
				else{
					$ret_code = 2;
					$log_data = array('account_id'=>$account_id,
							'created_at'=>new \DateTime(),
							'e_type'=>0,
							'ip'=>'');
					DB::table('account_error')->insert($log_data);
					//DB::insert('account_error', $log_data);
				}
			}
		}
		
		$error_msgs = array(1=>'账户不存在',2=>'密码错误',3=>'账户密码已被锁定');
		$result = array();
		$result['result'] = $ret_code;
		$result['msg'] = '';
		if($result['result'] >0){
			if(isset($error_msgs[$ret_code])){
				$result['msg'] = $error_msgs[$ret_code];
			}
			else{
				$result['msg'] = '未知错误';
			}
		}
		return $result;
	}
	
	/**
	 * 判断一个账号的密码是否已经被锁定
	 * @param unknown_type $account_id
	 * @return boolean
	 */
	static private function isPwdLocked($account_id, $last_pwd_rest_time){
		//$db = DB::getPDO();
		$db = DB::connection('default')->getPdo();
		//$today = strtotime("today");
		$today = date("Y-m-d H:i:s");
		$start_time = $last_pwd_rest_time>$today?$last_pwd_rest_time:$today;
		$log_sql = "select count(*) as num from `account_error` where account_id = ? and created_at > ? and e_type = 0";
		$stmt = $db->prepare($log_sql);
		$stmt->bindValue(1, $account_id , \PDO::PARAM_INT);
		$stmt->bindValue(2, $start_time , \PDO::PARAM_INT);
		$stmt->execute();
		$count = $stmt->fetchColumn();
		return $count >= self::PWD_WRONG_MAX_TIME;
	}
	
	/**
	 * 通过token设定支付密码，用于：1. 首次设定。2.密码找回
	 * @param int $account_id
	 * @param string $pay_password
	 * @param string $token
	 * @return array ('result'=>0,'msg'=>'')
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为004
	 * 可能的错误码为1:token验证失败
	 */
	static function setPayPassword($account_id, $pay_password){
		$encrypt_pwd = md5(self::ACCOUNT_PWD_SALT.$pay_password);
		$result = array('result'=>0,'msg'=>'');
		$account_info = self::getByAccountId($account_id);
		$set = array("last_pwd_rest_time"=>new \DateTime(),
				"pwd"=>$encrypt_pwd);
		if($account_info['status'] == self::ACCOUNT_STATUS_NOT_INIT){
			$set["status"] = self::ACCOUNT_STATUS_NORMAL;
		}
		DB::table("account","default")->where("id",'=',$account_id)
											->update($set);
		return $result;
	}
	
	/**
	 * 通过旧密码设定新的支付密码
	 * @param int $account_id
	 * @param string $old_pay_password
	 * @param string $new_pay_password
	 * @return array ('result'=>0,'msg'=>'')
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为005
	 * 可能的错误码为1:旧密码验证失败
	*/
	static function changePayPassword($account_id, $old_pay_password, $new_pay_password){
		$validate = self::validatePayPassword($account_id,$old_pay_password);
		if($validate['result'] == 0){
			self::setPayPassword($account_id, $new_pay_password);
		}
		return $validate;
	}
	
}

?>