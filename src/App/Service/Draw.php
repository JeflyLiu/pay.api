<?php
namespace App\Service;
use Illuminate\Database\Capsule\Manager as DB;
class Draw {
	const APPLY_STATUS_CREATED = 0;
	const APPLY_STATUS_FINISHED = 1;
	const APPLY_STATUS_CLOSED = 2;
	
	const DRAW_CARD_TYPE_BANK = 0;
	const DRAW_CARD_TYPE_ALIPAY = 1;
	
	/**
	 * 提交提现申请
	 * @param unknown_type $account_id
	 * @param unknown_type $pay_password
	 * @param unknown_type $card_type
	 * @param unknown_type $bank_id
	 * @param unknown_type $bank_name
	 * @param unknown_type $bank_branch
	 * @param unknown_type $card_no
	 * @param unknown_type $card_name
	 * @param unknown_type $bank_province
	 * @param unknown_type $bank_city
	 * @param unknown_type $amount
	 * @return Ambigous <multitype:, string>|Ambigous <multitype:number string , multitype:, string>
	 */
	static function apply($params, $pay_password){
		
		//验证支付密码
		$result = Account::validatePayPassword($params['account_id'], $pay_password);
		if($result['result'] >0){
			return $result;
		}
		//尝试冻结提现资金
		$freeze_sql = "update account set freeze_out = freeze_out + ?, balance = balance - ? where balance >= ? and id = ?";
		$db = DB::connection("default")->getPdo();
		$db->beginTransaction();
		$stmt = $db->prepare($freeze_sql);
		//$stmt->bindValue(":amount", $params['amount'], \PDO::PARAM_STR);
		//$stmt->bindValue(":account_id", $params['account_id'], \PDO::PARAM_INT);
		$stmt->bindValue(1, $params['amount'], \PDO::PARAM_STR);
		$stmt->bindValue(2, $params['amount'], \PDO::PARAM_STR);
		$stmt->bindValue(3, $params['amount'], \PDO::PARAM_STR);
		$stmt->bindValue(4, $params['account_id'], \PDO::PARAM_INT);
		$stmt->execute();
		if($stmt->rowCount() >0){
			//成功冻结，可执行申请
			$data = array("account_id"=>$params['account_id'],
					"amount"=>$params['amount'],
					"status"=>self::APPLY_STATUS_CREATED,
					"card_type"=>$params['card_type'],
					"card_name"=>$params['card_name'],
					"card_no"=>$params['card_no'],
					"bank_id"=>$params['bank_id'],
					"bank_branch"=>$params['bank_branch'],
					"bank_province"=>$params['bank_province'],
					"bank_city"=>$params['bank_city'],
					"created_at"=>new \DateTime(),
					);
			$insert_id = DB::table("draw")->insertGetId($data);
			$result['apply_id'] = $insert_id;
			if($insert_id >0){
				$db->commit();
			}
			else{
				$result = array('result'=>1,'msg'=>$db->errorInfo());
				$db->rollBack();
			}
		}
		else{
			$result = array('result'=>1,'msg'=>'可用余额不足');
		}
		return $result;
	}
	
	/**
	 * 打款成功完结提现申请
	 * @param int $apply_id
	 * @param string $out_trade_number
	 * @param int $operator
	 * @param string $comment
	 * @return array ('result'=>0,'msg'=>'')
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为002
	 * 可能的错误码为
	*/
	static function finishApply($apply_id, $pay_voucher, $pay_user, $pay_type, $pay_amount, $pay_note = ""){
		$db = DB::connection("default")->getPdo();
		$db->beginTransaction();
		//尝试标记完结
		$set = array('status'=>self::APPLY_STATUS_FINISHED,
				'pay_at'=>new \DateTime,
				'updated_at'=>new \DateTime,
				'pay_note'=>$pay_note,
				"pay_user"=>$pay_user,
				"pay_voucher"=>$pay_voucher,
				"pay_type"=>$pay_type,
				"pay_amount"=>$pay_amount,
				);
		$affected_rows = 
		DB::table("draw")->where('id',"=", $apply_id)
		->where('status', "=", self::APPLY_STATUS_CREATED)
		->update($set);
		
		if($affected_rows>0){
			$draw_info = self::getByApplyId($apply_id);
			$amount = $draw_info['amount'];
			$account_id = $draw_info['account_id'];
			//尝试扣除提现资金
			$affected_row = DB::table('account')
			->where('id', $account_id)
			->where('freeze_out',">=", $amount)
			->decrement('freeze_out', $amount);
			
			
			if($affected_row>0){
				$created_at = new \DateTime();
				DB::table('account_record')->insert(array(
				'account_id' => $account_id,
				'rec_type'   => \App\Model\AccountRecord::TYPE_DRAW,
				'amount'     => $amount,
				'fund_flow'  => \App\Model\AccountRecord::FLOW_OUT,
				'created_at' => $created_at,
				));
				
				$bill_sn = \App\Model\Bill::createSN();
				DB::table('bill')->insert(array(
					'bill_sn'    => $bill_sn, 
					'bill_type'  => \App\Model\Bill::TYPE_DRAW, 
					'amount'     => $amount,
					'from_id'    => $account_id, 
					'to_id'      => $draw_info["bank_id"],
					'created_at' => $created_at,
				));
				$db->commit();
			}
				
			else {
				//todo
				//异常啦，账户不存在或者冻结资金不足。
				$db->rollBack();
			}
		}
		else {
			//提现申请不存在，或者申请已经不处于待处理的状态了。
			$db->rollBack();
		}
		return array("result"=>0);
	}
	
	
	/**
	 * 打款未成功关闭申请
	 * @param int $apply_id
	 * @param int $operator
	 * @param string $comment
	 * @return array ('result'=>0,'msg'=>'')
	 * result为0表示成功
	 * result非0，msg必定提供错误描述
	 * 方法分区错误码为003
	 * 可能的错误码为
	*/
	static function closeApply($apply_id, $pay_user, $pay_note){
		//尝试标记关闭
		$set = array('status'=>self::APPLY_STATUS_CLOSED,
				'updated_at'=>new \DateTime,
				'pay_note'=>$pay_note,
				"pay_user"=>$pay_user,
		);
		$affected_rows = DB::table("draw")
						->where('id',"=", $apply_id)
						->where('status', "=", self::APPLY_STATUS_CREATED)
						->update($set);
		
		if($affected_rows>0){
			$draw_info = self::getByApplyId($apply_id);
			$amount = $draw_info['amount'];
			$account_id = $draw_info['account_id'];
			//尝试解冻提现资金
			$freeze_sql = "update account set freeze_out = freeze_out - ?, balance=balance + ?
				where freeze_out >= ? and id = ?";
			$db = DB::connection("default")->getPdo();
			$stmt = $db->prepare($freeze_sql);
			$stmt->bindValue(1, $amount , \PDO::PARAM_STR);
			$stmt->bindValue(2, $amount , \PDO::PARAM_STR);
			$stmt->bindValue(3, $amount , \PDO::PARAM_STR);
			$stmt->bindValue(4, $account_id , \PDO::PARAM_INT);
			$stmt->execute();
			if($stmt->rowCount() == 0){
				//todo 资金出现异常，因为冻结资金小于提现资金
			}
		}
		else{
			//todo 不存在此申请或申请已不可用
		}
		return array("result"=>0);
	}
	
	
	static function getByApplyId($apply_id){
		$result = DB::table("draw")->where("id","=", $apply_id)->first();
		$data = self::fillDrawIdNames(array($result));
		return $data[0];
	}
	
	/**
	 * 读取全部提现申请列表
	 * @param array $search_params
	 * email:不支持模糊查找
	 * created_at:$from, $to
	 * card_name:开户名
	 * status:提现状态
	 * id:提现流水号
	 * pay_voucher:凭证号
	 * 
	 * @return array 每一个row类型为array，包含以下字段
	 * int $account_id
	 * string $pay_pw
	 * string $bank_name
	 * string $bank_branch
	 * string $card_number
	 * string $bank_account_name
	 * string $bank_province
	 * string $bank_city
	 * decimal $amount
	 * int create_time
	 * int complete_time
	*/
	static function getAllApplyList($search_params, $order_by, $limit, $offset){
		$db = DB::connection("default")->getPdo();
		$conditions = array();
		$param_values = array();
		foreach ($search_params as $key=>$value){
			if($value === ""){
				unset($search_params[$key]);
			}
		}
		
		$query = DB::table("draw");
		if(isset($search_params['user']) && !empty($search_params['user'])){
			$hdd_pdo = DB::connection("haodingdan")->getPdo();
			$sql = "select id from garmentoffice.member where email = ? or mobile = ?";
			$stmt = $hdd_pdo->prepare($sql);
			$stmt->bindValue(1, $search_params['user']);
			$stmt->bindValue(2, $search_params['user']);
			$stmt->execute();
			$id_list = $stmt->fetchAll(\PDO::FETCH_COLUMN,0);
														
			if(!empty($id_list)){
				$query = $query->join("account", "account.id","=", "draw.account_id")
								->whereIn("account.member_id", $id_list);
			}
		}
		if(isset($search_params["created_at"])){
			$time_splits = explode(",", $search_params["created_at"]);
			$create_at_from = $time_splits[0];
			$query = $query->where("draw.created_at",">", $create_at_from);
			if(count($time_splits)>1){
				$query = $query->where("draw.created_at","<", $time_splits[1]);
			}
		}
		
		if(isset($search_params["card_name"])){
			$query = $query->where("draw.card_name","like", "%{$search_params["card_name"]}%");
		}
		if(isset($search_params["status"])){
			$query = $query->where("draw.status","=", $search_params["status"]);
		}
		if(isset($search_params["id"])){
			$query = $query->where("draw.id","=", $search_params["id"]);
		}
		if(isset($search_params["pay_voucher"])){
			$query = $query->where("draw.pay_voucher","=", $search_params["pay_voucher"]);
		}
		if($order_by){
			$query = $query->orderBy($order_by, "desc");
		}
		if($limit){
			$query = $query->limit($limit);
			if($offset){
				$query = $query->offset($offset);
			}
		}
		$result = $query->get();
		$result = self::fillDrawIdNames($result);
		return $result;
	}
	
	/**
	 * 读取个人提现申请列表
	 * @param int $account_id
	 * @param int $time_start
	 * @param int $time_end
	 * @return array 每一个row类型为array，包含以下字段
	 * int $account_id
	 * string $pay_pw
	 * string $bank_name
	 * string $bank_branch
	 * string $card_number
	 * string $bank_account_name
	 * string $bank_province
	 * string $bank_city
	 * decimal $amount
	 * int create_time
	 * int complete_time
	*/
	static function getApplyListByAccount($account_id, $time_start, $time_end){
		$sql = "select * from `draw` where account_id = ? and created_at > ? and created_at < ?";
		$db = DB::getPDO();
		$stmt = $db->prepare($sql);
		$stmt->bindValue(1, $account_id , \PDO::PARAM_INT);
		$stmt->bindValue(2, $time_start , \PDO::PARAM_STR);
		$stmt->bindValue(3, $time_end , \PDO::PARAM_STR);
		$stmt->execute();
		$result = $query = DB::table("draw")->where("account_id", $account_id)
		->where("created_at", ">", $time_start)
		->where("created_at", "<", $time_end)
		->get();
		$result = self::fillDrawIdNames($result);
		return $result;
	}
	
	static function addDrawCard($data){
		$now = new \DateTime();
		$data['created_at'] = $now->format("Y-m-d H:i:s");
		$bank_info = DB::table("draw_bank")->where("bank_id", $data["bank_id"])->first();
		//$data['bank_name'] = $bank_info['bank_name'];
		$id = DB::table("draw_card")->insertGetId($data);
		return array("result"=>0,"draw_card_id"=>$id);
	}
	
	static function deleteDrawCard($draw_card_id){
		DB::table("draw")->where("id", $draw_card_id)->update(array("is_removed"=>1));
	}
	
	static function getDrawCardById($draw_card_id){
		$result = DB::table("draw_card")->where("id", $draw_card_id)->first();
		return $result;
	}
	
	static function getDrawCardListByAccountId($account_id){
		$result = DB::table("draw_card")->where("account_id", $account_id)->get();
		$result = self::fillDrawCardIdNames($result);
		return $result;
	}
	
	
	private static function fillDrawIdNames($data){
		if(empty($data)) return $data;
		
		$bank_province_ids = array();
		$bank_city_ids = array();
		$bank_names = DB::table("draw_bank")->get();
		
		$bank_key_names = array();
		foreach ($bank_names as $bank_row){
			$bank_key_names[$bank_row["bank_id"]] = $bank_row["bank_name"];
		}
		
		foreach ($data as $draw){
			if($draw["card_type"] == self::DRAW_CARD_TYPE_BANK){
				if(!isset($bank_province_ids[$draw["bank_province"]])){
					$bank_province_ids[$draw["bank_province"]] = "";
				}
				if(!isset($bank_province_ids[$draw["bank_city"]])){
					$bank_city_ids[$draw["bank_city"]] = "";
				}
			}
		}
		
		$province_data = array();
		if(!empty($bank_province_ids)){
		
			$province_data = DB::table("province","haodingdan")->whereIn("id", array_keys($bank_province_ids))->get();
		}
		
		$city_data = array();
		if(!empty($bank_city_ids)){
			$city_data = DB::table("city","haodingdan")->whereIn("id", array_keys($bank_city_ids))->get();
		}
		
		
		$province_key_data = array();
		foreach ($province_data as $row){
			$province_key_data[$row["id"]] = $row["name"];
		}
		
		$city_key_data = array();
		foreach ($city_data as $row){
			$city_key_data[$row["id"]] = $row["name"];
		}
		
		foreach ($data as &$draw){
			if($draw["card_type"] == self::DRAW_CARD_TYPE_BANK){
				$draw["bank_province_name"] = $province_key_data[$draw["bank_province"]];
				$draw["bank_city_name"] = $city_key_data[$draw["bank_city"]];
			}
			else{
				$draw["bank_province_name"] = "";
				$draw["bank_city_name"] = "";
			}
			$draw["bank_name"] = $bank_key_names[$draw["bank_id"]];
			if($draw["status"] == self::APPLY_STATUS_CREATED){
				$draw["status_name"] = "处理中";
			}
			elseif($draw["status"] == self::APPLY_STATUS_FINISHED){
				$draw["status_name"] = "完成";
			}
			if($draw["status"] == self::APPLY_STATUS_CLOSED){
				$draw["status_name"] = "关闭";
			}
		}
		return $data;
	}
	
	private static function fillDrawCardIdNames($data){
		if(empty($data)) return $data;
		$bank_province_ids = array();
		$bank_city_ids = array();
		$bank_names = DB::table("draw_bank")->get();
		
		$bank_key_names = array();
		foreach ($bank_names as $bank_row){
			$bank_key_names[$bank_row["bank_id"]] = $bank_row["bank_name"];
		}
		
		foreach ($data as $draw_card){
			if($draw_card["card_type"] == self::DRAW_CARD_TYPE_BANK){
				if(!isset($bank_province_ids[$draw_card["bank_province"]])){
					$bank_province_ids[$draw_card["bank_province"]] = "";
				}
				if(!isset($bank_province_ids[$draw_card["bank_city"]])){
					$bank_city_ids[$draw_card["bank_city"]] = "";
				}
			}
		}
		
		$province_data = array();
		if(!empty($bank_province_ids)){
		
			$province_data = DB::table("province","haodingdan")->whereIn("id", array_keys($bank_province_ids))->get();
		}
		
		$city_data = array();
		if(!empty($bank_city_ids)){
			$city_data = DB::table("city","haodingdan")->whereIn("id", array_keys($bank_city_ids))->get();
		}
		$province_key_data = array();
		foreach ($province_data as $row){
			$province_key_data[$row["id"]] = $row["name"];
		}
		
		$city_key_data = array();
		foreach ($city_data as $row){
			$city_key_data[$row["id"]] = $row["name"];
		}
		
		foreach ($data as &$draw_card){
			if($draw_card["card_type"] == self::DRAW_CARD_TYPE_BANK){
				$draw_card["bank_province_name"] = $province_key_data[$draw_card["bank_province"]];
				$draw_card["bank_city_name"] = $city_key_data[$draw_card["bank_city"]];
			}
			else{
				$draw_card["bank_province_name"] = "";
				$draw_card["bank_city_name"] = "";
			}
			$draw_card["bank_name"] = $bank_key_names[$draw_card["bank_id"]];
		}
		return $data;
	}
}

?>