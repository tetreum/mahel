<?php

namespace App\System;

class App
{
	public static $config;
    public static $sess;

    /**
	 * Gets/sets a config key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public static function config ($key, $value = null)
	{
		if (empty($value)) {
			return self::$config[$key];
		} else {
			self::$config[$key] = $value;
		}
	}

    /**
     * Gets/sets a sess key
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function sess ($key, $value = null)
    {
        if (empty($value)) {
            return self::$sess[$key];
        } else {
            self::$sess[$key] = $value;
        }
    }

	/**
	 * On debug mode prints lines
	 *
	 * @param string $line line to print
	 * @return void
	 */
	public static function debug ($line)
    {
		if (App::config("debug")) {
			echo $line."\n";
		}
	}

	public static function getDB ($name)
    {
        $db = new \MicroDB\Database(APP_ROOT . "db/" . $name);

        //set here all required indexes
        switch ($name) {
            case "medias":
                new \MicroDB\Index($db, 'title', 'title');
                break;
        }

        return $db;
    }

    /**
     * Executes a provider
     * @param string $pName
     *
     * @return void
     */
	public static function runProvider ($pName)
    {
        App::debug("*********************************\n**** Starting provider " . $pName . " *****\n*********************************");
        $class = "\\App\\Providers\\" . $pName;
        $provider = new $class();
        $provider->start();
    }
}