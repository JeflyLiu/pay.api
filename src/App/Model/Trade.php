<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class Trade extends Base
{
	protected $table = 'trade';
	protected $guarded = array('id', 'trade_sn');

	const FREEZED_AT = 15; //交易金额冻结天数
	
	const STATUS_CLOSE   = 0;
	const STATUS_START   = 1;
	const STATUS_PART    = 2;
	const STATUS_PAY     = 3;
	const STATUS_SHIP    = 4;
	const STATUS_CONFIRM = 5;
	const STATUS_APPEAL  = 6;
	const STATUS_REFUND  = 7;
	const STATUS_FAIL    = 8;


	public static function getTradeByTradeSN($trade_sn)
	{
		return parent::where('trade_sn', '=', $trade_sn)->first();
	}


	public static function searchList($params)
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
		$list = $select->skip($offset)->take($limit)->get()->toArray();

		foreach ($list as & $value) {
			$value['trade_flow'] = (int) ($account_id != $value['from_id']);
			$opposite = $value['trade_flow'] ? $value['from_id'] : $value['to_id'];
			$value['opposite'] = $opposite;
			$value['status_name'] = static::$status_name[$value['status']];
		}

		return array('count' => $count, 'list' => $list);
	}

	public static function create($from_id,$to_id,$amount)
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
			return array(404,array('msg'=>'交易金额有误！');
		}

		$model = new Trade();
		$model->from_id  = $from_id;
		$model->to_id    = $to_id;
		$model->amount   = $amount;
		$model->trade_sn = self::createSN(15);
		$model->status   = self::STATUS_START;
		
		if($errors = $model->validate())
		{
			return array(404,array('msg' => array_values($errors)));
		}

		$model->save();

		return array(201,$model->toArray());
	}

	public static function store($trade_sn, $use_wallet = false)
	{
		$trade = Trade::getTradeByTradeSN($trade_sn);

		if (! $trade)
		{
			return array(404,array('msg'=>'交易不存在！'));
		}

		$account = Account::find($trade->from_id);
		//账户支付金额
		$has_fee = $use_wallet 
				? (($account->balance >= $trade->amount) ? $trade->amount : ($trade->amount - $account->balance)) 
				: 0 ;
		//外部支付金额
		$not_fee = $trade->amount - $wallet_fee;

		if ($has_fee == $trade->amount)
		{
			$result = DB::transaction(function() use($trade)
			{
				$created_at = new \DateTime;
				$bill_sn = Bill::createSN(15);
				$rs = DB::table('account')
					->where('id', $trade->from_id)
					->where('balance', '>=', $trade->amount)
					->decrement('balance', $trade->amount);
				if ($rs)
				{
					DB::table('account')->where('id', $trade->from_id)->increment('freeze_out', $trade->amount);
					DB::table('account_record')->insert(array(
						'account_id' => $trade->from_id,
						'amount'     => $trade->amount,
						'rec_type'   => AccountRecord::TYPE_TRADE_FREEZE, 
						'fund_flow'  => AccountRecord::FLOW_OUT, 
						'created_at' => $created_at,
					));

					DB::table('trade')->where('id', $trade->id)->update(array(
						'status'    => Trade::STATUS_PAY,
						'total_fee' => $trade->amount,
						'bill_pay'  => $bill_sn
					));

					DB::table('bill')->insert(array(
						'bill_sn'    => $bill_sn, 
						'bill_type'  => Bill::TYPE_TRADE, 
						'amount'     => $trade->amount,
						'from_id'    => $trade->from_id, 
						'to_id'      => $trade->to_id,
						'created_at' => $created_at,
					));
				}
				return $rs;
			});
		}
		else
		{
			$result = DB::transaction(function() use($trade, $has_fee, $not_fee)
			{
				$created_at = new \DateTime;
				$bill_sn = Bill::createSN(15);
				$rs = DB::table('account')
					->where('id', $trade->from_id)
					->where('balance', '>=', $has_fee)
					->decrement('balance', $has_fee);
				if ($rs)
				{
					DB::table('account')->where('id', $trade->from_id)->increment('freeze_out', $has_fee);
					DB::table('account_record')->insert(array(
						'account_id' => $trade->from_id,
						'amount'     => $has_fee,
						'rec_type'   => AccountRecord::TYPE_FREEZE, 
						'fund_flow'  => AccountRecord::FLOW_OUT, 
						'created_at' => $created_at,
					));

					DB::table('trade')->where('id', $trade->id)->update(array(
						'status'  => Trade::STATUS_PART,
						'has_fee' => $has_fee,
						'not_fee' => $not_fee,
					));
				}
				return $rs;
			});
		}

		return  $result ? Trade::getTradeByTradeSN($trade_sn)->toArray() : array(500,array('msg'=>'交易失败！'));
	}

	public static function inpour($trade_sn, $amount)
	{
		$trade = Trade::getTradeByTradeSN($trade_sn);

		if (! $trade)
		{
			return array(404, array('msg'=>'交易单不存在！'));
		}
		if($trade->not_fee != $amount)
		{
			$log = new ErrorLog(array(
				'obj_id' => $inpour_id, 
				'e_type' => ErrorLog::TYPE_TRADE_INPOUR,
				'ip' => getIp(),
				'code' => 'T001',//交易充值金额有误
				'note' => "金额: {$amount} ",
			));
			$log->save();

			return array(412, array('msg'=>'支付金额有误！'));
		}

		$result = DB::transaction(function() use($trade, $amount)
		{
			$rs = DB::table('account')->where('id', $trade->from_id)->increment('freeze_out', $amount);
			if ($rs)
			{
				DB::table('account_record')->insert(array(
					'account_id' => $trade->from_id,
					'amount'     => $amount,
					'rec_type'   => AccountRecord::TYPE_TRADE_INPOUR, 
					'fund_flow'  => AccountRecord::FLOW_IN, 
					'created_at' => $created_at,
				));
				DB::table('trade')
				->where('id', $trade->id)
				->update(array('status'=>Trade::STATUS_PAY,'total_fee'=>$trade->amount));
			}
			return $rs;
		});

		return $result;
	}

	//账户交易
	public static function transfer($from_id, $to_id, $amount)
	{
		if ($error = Account::disabled($from_id))
		{
			return $error;
		}
		if ($error = Account::disabled($to_id))
		{
			return $error;
		}

		$account = parent::find($from_id);

		if ($amount <= 0) 
		{
			return array(404,array('msg' => '交易金额有误！');
		}

		if ($account->balance < $amount) 
		{
			return array(404,array('msg' => '余额不足！');
		}

		DB::transaction(function() use($from_id, $to_id, $amount)
		{
			$created_at = new \DateTime;

			$ac = DB::table('account')
				->where('id', $from_id)
				->where('balance', '>=', $amount)
				->decrement('balance', $amount);
			if ($ac)
			{
				//付款
				DB::table('account_record')->insert(array(
					'account_id' => $from_id, 
					'rec_type'   => AccountRecord::TYPE_TRADE, 
					'amount'     => $amount,
					'fund_flow'  => AccountRecord::FLOW_OUT, 
					'created_at' => $created_at,
				));

				//收款
				DB::table('account')
					->where('id', $to_id)
					->increment('balance', $amount);
				DB::table('account_record')->insert(array(
					'account_id' => $to_id, 
					'rec_type'   => AccountRecord::TYPE_TRADE, 
					'amount'     => $amount,
					'fund_flow'  => AccountRecord::FLOW_IN, 
					'created_at' => $created_at,
				));
			}

			return $ac;
		});

		return array(200,array('msg'=>'交易成功！'));
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