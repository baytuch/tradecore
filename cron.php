<?php

require_once('app/core.php');

header('Content-type: text/plain; charset=utf-8');

$app = new tradecore();
$app->cron();

?>