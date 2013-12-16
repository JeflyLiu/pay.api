<?php

namespace App\Model;

use App\Model\Base;

class UnionBank extends Base
{
	protected $table = 'union_bank';
	
	public $timestamps = false;

	protected $guarded = array('id');

	const TYPE_TRADE = 0;
	const TYPE_INPOUR = 1;
	const TYPE_DRAW = 2;
	const TYPE_TRADE_INPOUR = 3;
	const TYPE_FREEZE = 4;

	const FLOW_OUT = 0;
	const FLOW_IN = 1;

}