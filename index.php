<?php
require_once 'vendor/autoload.php';
require_once 'asanaWorker.class.php';

$asanaWorker = new asanaWorker();
echo $asanaWorker->render();