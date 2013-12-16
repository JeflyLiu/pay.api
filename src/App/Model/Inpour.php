<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class Inpour extends Base
{
	protected $table = 'inpour';
	
	//public $timestamps = false;

	protected $guarded = array('id');

	const STATUS_CLOSE = 0;
	const STATUS_START = 1;
	const STATUS_END   = 2;
	const STATUS_FAIL  = 3;
	
	static protected $status_name = array(
		self::STATUS_CLOSE => '关闭',
		self::STATUS_START => '待支付',
		self::STATUS_END   => '成功',
		self::STATUS_FAIL  => '失败',
	);

	public static function getList($params)
	{
		$limit = (int) ((isset($params['limit'])) ? $params['limit']: 10);
		$offset = (int) ((isset($params['offset'])) ? $params['offset']: 0);
		$offset = $offset * $limit;
		$account_id = (int) ((isset($params['account_id'])) ? $params['account_id']: 0);

		$select = parent::whereRaw('deleted_at = 0');
		if ($account_id)
		{
			$select = $select->where('account_id',$account_id);
		}

		$count = $select->count();
		$list = $select->skip($offset)->take($limit)->get();
		$list = $list->toArray();
		foreach ($list as & $value) {
			$value['status_name'] = static::$status_name[$value['status']];
			$value['channels'] = ($bank = DB::table('union_bank')->where('inst_code','=', $value['channels'])->lists('inst_name')) ? $bank[0] : $value['channels'];
		}

		return array('count' => $count, 'list' => $list);
	}

	public static function getCreate($account_id, $amount, $channels)
	{
		if ($error = Account::disabled($account_id))
		{
			return $error;
		}

		$model = new Inpour();
		$model->account_id = $account_id;
		$model->amount = $amount;
		$model->channels = $channels;
		$model->status = self::STATUS_START;
		
		if($errors = $model->validate())
		{
			return array(404,array('msg' => array_values($errors)));
		}

		$model->save();

		return array(201,$model->toArray());
	}

	public static function store($inpour_id, $amount)
	{
		$result = array('code' => 201, 'data' => array());
		$inpour = parent::find($inpour_id);

		if (! $inpour)
		{
			$log = new ErrorLog(array(
				'obj_id' => $inpour_id, 
				'e_type' => ErrorLog::TYPE_INPOUR,
				'ip' => getIp(),
				'code' => 'I001',//充值单号不存在
				'note' => "inpour_id : {$inpour_id} ",
			));
			$log->save();

			$result['code'] = 404;
			$result['data'] = array('msg' => '充值单号不存在！');

			return $result;
		}

		if ($inpour['status'] !== Inpour::STATUS_START)
		{
			$log = new ErrorLog(array(
				'obj_id' => $inpour_id, 
				'e_type' => ErrorLog::TYPE_INPOUR,
				'ip' => getIp(),
				'code' => 'I003',//充值单状态异常
				'note' => "状态：{$inpour['status']} ",
			));
			$log->save();
			switch ($inpour['status']) {
				case Inpour::STATUS_END:
					$result['data'] = array('msg' => '订单已支付！');
					break;
				
				case Inpour::STATUS_CLOSE:
					$result['data'] = array('msg' => '订单已关闭！');
					break;
				case Inpour::STATUS_FAIL:
				default:
					$result['data'] = array('msg' => '订单支付失败！');
					break;
			}

			$result['code'] = 404;

			return $result;
		}

		if ($amount != $inpour['amount'])
		{
			$log = new ErrorLog(array(
				'obj_id' => $inpour_id, 
				'e_type' => ErrorLog::TYPE_INPOUR,
				'ip' => getIp(),
				'code' => 'I002',//应付金额不符
				'note' => "应付：{$inpour['amount']}￥ 实付：{$amount}￥ ",
			));
			$log->save();
		}

		DB::transaction(function() use($inpour,$amount)
		{
			$account_id = $inpour['account_id'];
			$bill_sn = Bill::createSN(15);
			$from_id = ($bank = DB::table('union_bank')->where('inst_code','=', $inpour['channels'])->lists('id')) ? $bank[0] : 0;
			$created_at = new \DateTime;

			DB::table('bill')->insert(array(
				'bill_sn' => $bill_sn, 
				'bill_type' => Bill::TYPE_INPOUR, 
				'amount' => $amount,
				'from_id' => $from_id, 
				'to_id' => $account_id,
				'created_at'=> $created_at,
			));

			DB::table('inpour')
				->where('id',$inpour['id'])
				->update(array('bill_sn' => $bill_sn,'status'=>Inpour::STATUS_END,'amount'=>$amount));
			
			DB::table('account')
				->where('id',$account_id)
				->increment('balance',$amount);

			DB::table('account_record')->insert(array(
				'account_id' => $account_id, 
				'rec_type' => AccountRecord::TYPE_INPOUR, 
				'amount' => $amount,
				'fund_flow' => AccountRecord::FLOW_IN, 
				'created_at'=> $created_at,
			));
			
		});

		return $result;
	}

	/**
	*
	* @return $errors
	*/
	public function validate()
	{
		$errors = false;
		try{
			v::key('account_id',v::int())
			->key('amount',v::numeric())
			->key('status', v::int())
			->key('bill_id',v::int())
			->key('channels',v::string())
			->assert($this->attributes);
		} catch (\InvalidArgumentException $e) {
			$errors = array_filter($e->findMessages(array_keys($this->attributes)));
		}
		return $errors;
	}

}