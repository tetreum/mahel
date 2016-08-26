<?php

namespace App\System;

use DiDom\Document;
use Exception;

class Provider {

	const TYPE_MOVIE = "movie";
	const TYPE_SERIE = "serie";
	
	private $requiredFields = [
        'provider' => 'string',
        'title' => 'array',
    ];

	private $optionalFields = [
		'credits' => 'array',
		'screenLabel' => 'string',
		'audio' => 'string',
		'duration' => 'integer',
		'year' => 'integer',
		'media_type' => 'string',
    ];

	private $arrayFields = [
        "title" => [
            "type" => "indexed",
            "fields" => [
                "required" => [
                    "en" => "string",
                    "es" => "string",
                ]
            ]
        ]
    ];

    /**
     * @var Matcher
     */
    private $matcher = null;

    private function getMatcher ()
    {
        if (empty($this->matcher)) {
            $this->matcher = new Matcher();
        }

        return $this->matcher;
    }

	/**
	 * Sends an item to db queue list after checking item's integrity
	 *
	 * @param array $item
	 * @return boolean
	 */
	public function sendToDB ($item)
    {
		$item = $this->checkIntegrity($item);

        // encrypt all links
        $item = self::treatLinks($item);

		App::debug(json_encode($item) . "\n");

        if (!App::config("debug") || true)
        {
            $success = $this->getMatcher()->match($item);

            // create new medias only if item has more data than a simple title
            if (!$success && sizeof($item) > 4) {
                $this->getMatcher()->createMedia($item);
            }
        }
	}

	public static function treatLinks ($item, $encrypt = true)
    {
        $action = "decrypt";

        if ($encrypt) {
            $action = "encrypt";
        }
        $item["url"] = Encrypter::$action($item["url"]);

        if (isset($item["links"])) {
            foreach ($item["links"] as &$link) {
                $link["url"] = Encrypter::$action($link["url"]);
            }
        } else if (isset($item["episodes"])) {
            foreach ($item["episodes"] as &$season)
            {
                foreach ($season as &$episode) {
                    foreach ($episode["links"] as &$link) {
                        $link["url"] = Encrypter::$action($link["url"]);
                    }
                }
            }
        }

        return $item;
    }

    private function isValidValue ($key, &$value, $expectedType)
    {
        switch ($expectedType)
        {
            case 'array':
                if ($this->arrayFields[$key]["type"] == "value") {
                    foreach ($value as $entry) {
                        $this->validateEntry($entry, $this->arrayFields[$key]["fields"]);
                    }
                    return true;
                } else {
                    return $this->validateEntry($value, $this->arrayFields[$key]["fields"]);
                }
            case 'numeric':
                return is_numeric($value);
            case 'date':
                return Utils::isValidDate($value);
            case 'url':
                return (strpos($value, "http") !== false);
            case 'imdb':
                return $this->isValidImdbid($value);
            case 'string':
                if(!gettype($value) == $expectedType) {
                    return false;
                }

                $expectedType = trim(strip_tags($expectedType));

                return (!empty($expectedType));
                break;
            default:
                return (gettype($value) == $expectedType);
        }
    }

	/**
	 * Verifies that the object begin sent to db has the required fields
	 *
	 * @param array $item
	 *
	 * @return array
	 * @throws ProviderException if integrity check failed
	 */
	public function checkIntegrity ($item)
    {
		foreach ($this->requiredFields as $field => $type)
        {
			if (!isset($item[$field]) || empty($item[$field]) || !$this->isValidValue($field, $item[$field], $type)) {
				throw new ProviderException(ProviderException::INVALID_INTEGRITY, $field.':'.var_dump($item));
			}

			switch ($field) {
				case 'title':
					foreach ($item[$field] as $k => &$title) {
                        $title = trim(strip_tags($title));

                        if (empty($title)) {
                            throw new ProviderException(ProviderException::INVALID_INTEGRITY, $field.':'.var_dump($item));
                        }
					}
					break;
			}
		}

        foreach ($this->optionalFields as $field => $expectedType)
        {
            if (!isset($item[$field]) || ($expectedType != 'boolean' && empty($item[$field]))) {
                continue;
            }
            if (!$this->isValidValue($field, $item[$field], $expectedType)) {
                throw new ProviderException(ProviderException::INVALID_INTEGRITY, "$field: " . var_dump($item));
            }
        }
		return $item;
	}

    private function validateEntry ($entry, $fieldList)
    {
        foreach ($fieldList as $type => $fields)
        {
            foreach ($fields as $field => $expectedType)
            {
                if (!isset($entry[$field]) || ($expectedType != 'boolean' && empty($entry[$field]))) {
                    if ($type == "optional") {
                        continue;
                    } else {
                        throw new ProviderException(ProviderException::INVALID_INTEGRITY, "$field: " . var_dump($entry));
                    }
                }
                if (!$this->isValidValue($field, $entry[$field], $expectedType)) {
                    throw new ProviderException(ProviderException::INVALID_INTEGRITY, "$field: " . var_dump($entry));
                }
            }
        }
        return true;
    }

	/**
	 * Checks if an string is a valid imdbid
	 *
	 * @param string $string
	 *
	 * @return bool
	 ***/
	public function isValidImdbid ($string) {
		preg_match("/tt([0-9]+)/", $string, $matches);
		if (sizeof($matches) == 0) {
			return false;
		}
		return true;
	}

	/***
	 * Cleans unwanted tags from media/product title
	 * @param string $title
	 *
	 * @return string $title
	 */
	public function cleanTitle ($title) {
		return trim(strtolower($title));
	}

	/**
	 * delete downloaded file after parsing it
	 *
	 * @return void
	 */
	function deleteCache () {
		if (!App::config("debug") && isset($this->tmpUncompressedFile)) {
			unlink($this->tmpUncompressedFile);
		}
	}

	public function getJson ($url)
    {
        if (App::config("debug"))
        {
            $fileCache = App::config("tmp") . md5($url);
            if (file_exists($fileCache))
            {
                $html = file_get_contents($fileCache);

                return json_decode($html);
            }
        }

        $json = Utils::curl($url);

        if (App::config("debug")) {
            file_put_contents($fileCache, $json);
        }

        return json_decode($json);
    }

    public function getRawContent ($url)
    {
        if (App::config("debug"))
        {
            $fileCache = App::config("tmp") . md5($url);
            if (file_exists($fileCache))
            {
                $html = file_get_contents($fileCache);

                return $html;
            }
        }

        $html = Utils::curl($url);

        if (App::config("debug")) {
            file_put_contents($fileCache, $html);
        }

        return $html;
    }

	/**
	 * Downloads html and temporary saves them in debug mode
	 *
	 * @param string $url
	 * @param array $options
	 *
	 * @return Document
	 */
	function getContent ($url, $options = [])
	{
		if (App::config("debug"))
        {
			$fileCache = App::config("tmp") . md5($url);
			if (file_exists($fileCache))
            {
                $html = file_get_contents($fileCache);

				return new Document($html);
			}
		}

		$html = Utils::curl($url);

		// lower string size
		if (isset($options["onlyBody"]))
        {
			if (strpos('<body>', $html) !== false) {
				$tmp = explode('<body>', $html);
				$tmp = explode('</body>', $tmp[1]);
				$html = $tmp[0];
			}
		}

		if (App::config("debug")) {
			file_put_contents($fileCache, $html);
		}

		return new Document($html);
	}
}

class ProviderException extends Exception {

	const INVALID_INTEGRITY = "invalid object integrity";
	const CRAWLING_ERROR = "provider couldn't be crawled properly";

	public function __construct($constant, $extraInfo, $code = 0, Exception $previous = null) {

		parent::__construct($constant . ': ' . $extraInfo, $code, $previous);
	}
}