<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class Trade extends Base
{
	protected $table = 'trade';
	
	//public $timestamps = false;

	protected $guarded = array('id', 'trade_sn');

	const STATUS_CLOSE = 0;
	const STATUS_START = 1;
	const STATUS_PART = 2;
	const STATUS_PAY = 3;
	const STATUS_SHIP = 4;
	const STATUS_CONFIRM = 5;
	const STATUS_APPEAL = 6;
	const STATUS_REFUND = 7;
	const STATUS_FAIL = 8;

	static protected $status_name = array(
	   	self::STATUS_CLOSE =>'关闭',
		self::STATUS_START => '待付款',
		self::STATUS_PART => '部分支付',
		self::STATUS_PAY => '支付完成',
		self::STATUS_SHIP => '已经发货',
		self::STATUS_CONFIRM => '确认收货',
		self::STATUS_APPEAL => '申请退款',
		self::STATUS_REFUND => '退款',
		self::STATUS_FAIL => '失败',
	);

	public static function getTradeByTradeSN($trade_sn)
	{
		return parent::where('trade_sn', '=', $trade_sn)->first();
	}


	public static function getList($params)
	{
		$limit = (int)((isset($params['limit'])) ? $params['limit']: 10);
		$offset = (int)((isset($params['offset'])) ? $params['offset']: 0);
		$offset = $offset * $limit;
		$account_id = (int) ((isset($params['account_id'])) ? $params['account_id']: 0);

		$select = parent::whereRaw('deleted_at = 0');
		
		if ($account_id)
		{
			$select = $select->where('from_id', $account_id)->orWhere('to_id',$account_id);
		}

		$count = $select->count();
		$list = $select->skip($offset)->take($limit)->get();
		$list = $list->toArray();

		foreach ($list as & $value) {
			$value['trade_flow'] = (int) ($account_id != $value['from_id']);
			$opposite = $value['trade_flow'] ? $value['from_id'] : $value['to_id'];
			$value['opposite'] = $opposite;
			$value['status_name'] = static::$status_name[$value['status']];
		}

		return array('count' => $count, 'list' => $list);
	}

	public static function getCreate($from_id,$to_id,$amount)
	{
		if ($error = Account::disabled($from_id))
		{
			return $error;
		}
		if ($error = Account::disabled($to_id))
		{
			return $error;
		}
		if ($amount <= 0)
		{
			return array(404,array('msg'=>'交易金额有误！'));
		}

		$model = new Trade();
		$model->from_id = $from_id;
		$model->to_id = $to_id;
		$model->amount = $amount;
		$model->trade_sn = self::createSN(15);
		$model->status = self::STATUS_START;
		
		if($errors = $model->validate())
		{
			return array(404,array('msg' => array_values($errors)));
		}

		$model->save();

		return array(201,$model->toArray());
	}

	//交易支出
	public static function account($from_id, $amount, $use_wallet)
	{
		$transaction = Account::freeze_out($from_id, $amount, AccountRecord::TYPE_TRADE, AccountRecord::FLOW_OUT);
		
		if (! $transaction)
		{
			return array(500,array('msg'=>'交易失败！'));
		}

		return array(200,array('msg'=>'交易成功！'));
	}

	//交易支出充值冻结
	public static function freeze_in($from_id,$amount)
	{
		return DB::transaction(function() use($from_id,$amount)
		{
			$created_at = new \DateTime;
			$ac = DB::table('account')
				->where('id',$from_id)
				->increment('freeze_out',$amount);

			if ($ac)
			{
				DB::table('account_record')->insert(array(
					'account_id' => $from_id, 
					'rec_type' => AccountRecord::TYPE_TRADE_INPOUR, 
					'amount' => $amount,
					'fund_flow' => AccountRecord::FLOW_IN, 
					'created_at'=> $created_at,
				));
			}
			
		});
	}

	public static function store($trade_sn, $amount, $use_wallet = false)
	{
		$trade = Trade::getTradeByTradeSN($trade_sn);

		if (! $trade)
		{
			return array(404,array('msg'=>'交易不存在！'));
		}
		if ($error = Account::disabled($trade->from_id))
		{
			return $error;
		}

		$account = Account::find($trade->from_id);
		//账户支付金额
		$has_fee = $use_wallet ? (($account->balance >= $amount) ? $amount : ($amount - $account->balance)) : 0 ;
		//外部支付金额
		$not_fee = $amount - $wallet_fee;

		DB::transaction(function() use($trade)
		{
			$created_at = new \DateTime;
			$result = DB::table('trade')
				->where('id', $trade->id)
				->where('status', Trade::STATUS_START)
				->update(array('status'=>Trade::STATUS_PAY,'bill_pay'=>$bill_sn));

			DB::table('bill')->insert(array(
				'bill_sn' => $bill_sn, 
				'bill_type' => Bill::TYPE_TRADE, 
				'amount' => $amount,
				'from_id' => $from_id, 
				'to_id' => $to_id,
				'created_at' => $created_at,
			));

			
			
			DB::table('account')
				->where('id', $from_id)
				->decrement('freeze_out', $amount);

			DB::table('account_record')->insert(array(
				'account_id' => $from_id, 
				'rec_type' => AccountRecord::TYPE_TRADE, 
				'amount' => $amount,
				'fund_flow' => AccountRecord::FLOW_OUT, 
				'created_at'=> $created_at,
			));

			DB::table('account')
				->where('id', $to_id)
				->increment('balance', $to);

			DB::table('account_record')->insert(array(
				'account_id' => $from_id, 
				'rec_type' => AccountRecord::TYPE_TRADE, 
				'amount' => $amount,
				'fund_flow' => AccountRecord::FLOW_IN, 
				'created_at'=> $created_at,
			));
		});
	}

	/**
	*
	* @return $errors
	*/
	public function validate()
	{
		$errors = false;
		try{
			v::key('trade_class',v::int())
			->key('amount',v::numeric())
			->key('from_id', v::int())
			->key('to_id',v::int())
			->key('status',v::int())
			->assert($this->attributes);
		} catch (\InvalidArgumentException $e) {
			$errors = array_filter($e->findMessages(array_keys($this->attributes)));
		}
		return $errors;
	}

	/**
	 * Get the unique identifier for the Trade.
	 *
	 * @return string
	 */
	public static function createSN($len = 15)
	{
		if( ($num = self::createNum($len)) != null)
		{
			return $num;
		}

		self::createSN($len);
	}

	/**
	 * Generate a random, unique Number.
	 *
	 * @return string
	 */
	public static function createNum($len)
	{
		return date('ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,$len-6);
	}

}