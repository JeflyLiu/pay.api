<?php
require __DIR__.'/../../bootstrap/autoload.php';
require __DIR__.'/../../bootstrap/database.php';
use \App\Service as Service;

$result = Service\Account::changePayPassword(3, "123456", "654321");
var_dump($result);