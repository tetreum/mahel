<?php

header("Access-Control-Allow-Origin: *");

require '../bootstrap.php';

/**
* 
* Returns titles index
* 
*/

echo file_get_contents(APP_ROOT . 'db/medias/_title_index');