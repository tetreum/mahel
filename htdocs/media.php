<?php

header("Access-Control-Allow-Origin: *");

require '../bootstrap.php';

/**
* 
* Returns media all information 
* 
*/

use \App\System\App;
use \App\System\Provider;

$id = (int)$_REQUEST["id"];
$key = $_REQUEST["key"];
$ivSize = $_REQUEST["iv_size"];
$iv = $_REQUEST["iv"];

if ($id < 1 ||
    App::$config["encryption"]["key"] != $key ||
    App::$config["encryption"]["ivSize"] != $ivSize ||
    App::$config["encryption"]["iv"] != $iv) {
    die("-");
}

$db = App::getDB("medias");

$media = $db->load($id);
$media = Provider::treatLinks($media, false);

echo str_replace('\u0000', "", json_encode($media));


