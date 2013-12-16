<?php

namespace App\Model;

use App\Model\Base;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class Bill extends Base
{
	protected $table = 'bill';
	
	//public $timestamps = false;

	protected $guarded = array('id');
	
	const TYPE_TRADE  = 0;
	const TYPE_INPOUR = 1;
	const TYPE_DRAW   = 2;

	/**
	* 验证数据
	*
	* @return $errors
	*/
	public function validate()
	{
		$errors = false;
		try{
			v::key('bill_type',v::int())
			->key('amount',v::numeric())
			->key('from_id', v::int())
			->key('to_id',v::int())
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