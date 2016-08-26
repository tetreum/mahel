<?php
// Includes vendor libraries
require "vendor/autoload.php";

use App\System\App;

// Include configurations and global constants
if (isset($argv[1])) {
    App::$config = require "conf.php";
} else {
    App::$config = require "conf.prod.php";
}

\App\System\Encrypter::setKeys(App::$config["encryption"]);