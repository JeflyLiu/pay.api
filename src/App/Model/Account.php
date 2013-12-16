<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class Account extends Base
{
	protected $table = 'account';
	
	// public $timestamps = false;

	protected $guarded = array('id');

	const STATUS_NORMAL = 0;
	const STATUS_LOCK   = 1;
	const STATUS_UN     = 2;

	/**
	* 验证账户是否可用
	* @param int id
	* @return false | $error
	*/
	public static function disabled($id)
	{
		$account = DB::table('account')->whereRaw('id = ? AND deleted_at = 0',array((int) $id))->first();

		if (!$account)
		{
			return array(404, array('msg' => '账户不存在！'));
		}
		elseif ($account['status'] == 1)
		{
			return array(404, array('msg' => '账户已锁定！'));
		}
		elseif ($account['status'] == 2)
		{
			return array(404,array('msg' => '账户未初始化！'));
		}

		return false;
	}

	/**
	* 支出冻结
	* 
	*/
	public static function freeze_out($id, $amount, $rec_type, $fund_flow)
	{
		return DB::transaction(function() use($id, $amount, $rec_type, $fund_flow)
		{
			$created_at = new \DateTime;

			$ac = DB::table('account')
				->where('id', $id)
				->where('balance', '>=', $amount)
				->decrement('balance', $amount);
			if ($ac)
			{
				DB::table('account')
					->where('id', $id)
					->where('balance', ' >=', $amount)
					->increment('freeze_out', $amount);

				DB::table('account_record')->insert(array(
					'account_id' => $id, 
					'rec_type'   => $rec_type, 
					'amount'     => $amount,
					'fund_flow'  => $fund_flow, 
					'created_at' => $created_at,
				));
			}
			return $ac;
		});
	}

	/**
	* 收入冻结
	* 
	*/
	public static function freeze_in($from_id, $amount, $rec_type, $fund_flow)
	{
		return DB::transaction(function() use($from_id,$amount, $rec_type, $fund_flow)
		{
			$created_at = new \DateTime;
			$ac = DB::table('account')
				->where('id',$from_id)
				->increment('freeze_in',$amount);
			
			if ($ac)
			{
				DB::table('account_record')->insert(array(
					'account_id' => $from_id, 
					'rec_type'   => $rec_type, 
					'amount'     => $amount,
					'fund_flow'  => $fund_flow, 
					'created_at' => $created_at,
				));
			}
			return $ac;
		});
	}

}