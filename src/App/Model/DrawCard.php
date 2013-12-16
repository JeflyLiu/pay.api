<?php
namespace App\Models;

use App\Model\Base;

class DrawCard extends Base {
	public $id;
	public $account_id;
	public $card_type;
	public $card_name;
	public $card_no;
	public $bank_id;
	public $bank_name;
	public $bank_province;
	public $bank_city;
	public $is_default;
	public $created_at;
	public $updated_at;
}
