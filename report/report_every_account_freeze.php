<?php
/**
 * 用于对账每个账户的冻结资金是否正确
 */

require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/database.php';
use Illuminate\Database\Capsule\Manager as DB;

$account_list = DB::table("account")->get();
$db = DB::connection("default")->getPdo();
foreach ($account_list as $account){
	$sql = "SELECT SUM(amount) FROM pay.draw where status in (0) and account_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($account["id"]));
	$draw_freeze_out= $stmt->fetchColumn(0);
	if($draw_freeze_out == ""){
		$draw_freeze_out = 0.00;
	}
	
	//部分支付导致的冻结
	$sql = "SELECT SUM(has_fee) FROM pay.trade where status in (2) and from_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($account["id"]));
	$trade_freeze_out= $stmt->fetchColumn(0);
	if($trade_freeze_out == ""){
		$trade_freeze_out = 0.00;
	}
	
	//已完成支付，但是未确认收货导致的冻结
	$sql = "SELECT SUM(amount) FROM pay.trade where status in (3,4) and to_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($account["id"]));
	$trade_freeze_in= $stmt->fetchColumn(0);
	if($trade_freeze_in == ""){
		$trade_freeze_in = 0.00;
	}
	
	//账户的freeze_in必须等于交易中的冻结收入总金额
	if($account['freeze_in'] != $trade_freeze_in){
		echo "{$account['id']}\tfreeze_in:{$account['freeze_in']}\ttrade_freeze_in:{$trade_freeze_in}\n";
	}
	
	//账户的freeze_out必须等于交易中的冻结支出总金额+处理中的提现冻结总金额
	if($account['freeze_out'] != $draw_freeze_out + $trade_freeze_out){
		echo "{$account['id']}\tfreeze_out:{$account['freeze_out']}\tdraw_freeze_out:{$draw_freeze_out}\ttrade_freeze_out:{$trade_freeze_out}\n";
	}
}
