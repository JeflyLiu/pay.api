<?php
/**
 * 用于对账每个账户的总资金是否正确
 */

require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/database.php';
use Illuminate\Database\Capsule\Manager as DB;


$account_list = DB::table("account")->get();
$db = DB::connection("default")->getPdo();
foreach ($account_list as $account){
	$sql = "SELECT SUM(amount) FROM pay.bill where bill_type in (0,1,3) and to_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($account["id"]));
	$total_in= $stmt->fetchColumn(0);
	if($total_in == ""){
		$total_in = 0.00;
	}
	
	$sql = "SELECT SUM(amount) FROM pay.bill where bill_type in (0,2) and from_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($account["id"]));
	$total_out= $stmt->fetchColumn(0);
	if($total_out == ""){
		$total_out = 0.00;
	}
	
	$account_total_fund = $account['balance']+$account['freeze_in']+$account['freeze_out'];
	if($account_total_fund !== ($total_in-$total_out)){
		echo "{$account['id']}\t{$account_total_fund}\t{$total_in}\t{$total_out}\n";
	}
}
