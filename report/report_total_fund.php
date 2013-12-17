<?php
/**
 * 用于获取总资产，总收入，以及总支出信息
 */

require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/database.php';
use Illuminate\Database\Capsule\Manager as DB;
$db = DB::connection("default")->getPdo();
$sql = "SELECT SUM(balance)+SUM(freeze_in)+SUM(freeze_out) as total_fund  FROM pay.account;";
$stmt = $db->prepare($sql);
$stmt->execute();
$total_fund = $stmt->fetchColumn(0);

$sql = "SELECT SUM(amount) FROM pay.bill where bill_type in (1,3)";
$stmt = $db->prepare($sql);
$stmt->execute();
$total_in= $stmt->fetchColumn(0);
if($total_in == ""){
	$total_in = 0.00;
}

$sql = "SELECT SUM(amount) FROM pay.bill where bill_type in (2)";
$stmt = $db->prepare($sql);
$stmt->execute();
$total_out= $stmt->fetchColumn(0);
if($total_out == ""){
	$total_out = 0.00;
}

echo "total_fund:{$total_fund}\n";
echo "total_in:{$total_in}\n";
echo "total_out:{$total_out}\n";

