<?php
set_time_limit(0);

require 'bootstrap.php';

use App\System\App;

$dir = __DIR__ . DIRECTORY_SEPARATOR . "tmp";

if (!file_exists($dir) && !is_dir($dir)) {
    mkdir($dir);
}

// Are you debugging a provider?
if (isset($argv[1])) {
    if (!isset($argv[2])) {
        App::config("debug", true); // force debug mode
    }

	if (!in_array($argv[1], App::config('providers'))) {
        throw new Exception("Provider not listed in config");
	}

	App::runProvider($argv[1]);
	exit;
} else {
	foreach (App::config('providers') as $pName) {
		App::runProvider($pName);
	}
}



