<?php

namespace App\Model;

use App\Model\Base;

class ErrorLog extends Base
{
	protected $table = 'error_log';
	
	//public $timestamps = false;

	protected $guarded = array('id');

	const TYPE_ACCOUNT = 1;
	const TYPE_TRADE   = 2;
	const TYPE_DRAW    = 3;
	const TYPE_INPOUR  = 4;


}