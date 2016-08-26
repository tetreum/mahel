<?php

namespace App\System;

class Utils
{
	/**
	 * Checks if given date is valid
	 * @param string $date
	 * @return bool
	 */
	public static function isValidDate ($date)
	{
		try {
			new \DateTime($date);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Checks if a date is a valid ISO8601
	 *
	 * @param string $date
	 * @return bool
	 */
	public static function isValidISO8601($date)
	{
		if (preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $date) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 * Prints any kind of var in any environment
	 * **/
	public static function p()
	{
		$consolePrint = false;

		if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] == null) {
			$consolePrint = true;
		}

		if (!$consolePrint) {
			echo '<pre>';
		}
		$args = func_get_args();

		foreach ($args as $var)
		{
			if ($var == null || $var == '') {
				var_dump($var);
			} elseif (is_array($var) || is_object($var)) {
				print_r($var);
			} else {
				echo $var;
			}
			if (!$consolePrint) {
				echo '<br>';
			} else {
				echo "\n";
			}
		}
		if (!$consolePrint) {
			echo '</pre>';
		}
	}

	public static function convertCountry ($country) {
		$country = trim(strtolower($country));

		$translation = array(
			'usa' => 'us',
			'estados unidos' => 'es',
			'españa' => 'es',
		);

		if (isset($translation[$country])) {
			return $translation[$country];
		} else {
			return false;
		}
	}

	public static function curl($url, $data = [], $options = [])
    {
        if (isset($options["method"]) && $options["method"] == "get") {
            $url .= "?" . http_build_query($data);
        }

        //echo "$url - " . json_encode($data) . " - " . json_encode($options). "\n";

		$ch = curl_init($url);
		$headers = [];
        $headers[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $headers[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $headers[]   = "Cache-Control: max-age=0";
        $headers[]   = "Connection: keep-alive";
        $headers[]   = "Keep-Alive: 300";
        $headers[]   = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $headers[]   = "Accept-Language: en-us,en;q=0.5";
        $headers[]   = "Pragma: "; // browsers keep this blank.

        if (isset($options["headers"]))
        {
            foreach ($options["headers"] as $header) {
                $headers[] = $header;
            }
        }

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (isset($options["method"]))
        {
            switch ($options["method"])
            {
                case "post":
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    break;
            }
        } else {
            if (sizeof($data) > 0) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

		if (isset($options["saveCookies"])) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $options["cookieFile"]);
        }else if (isset($options["cookieFile"])) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $options["cookieFile"]);
        }

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);

		return curl_exec($ch);
	}

	public static function convertLang ($lang) {
		$lang = strtolower($lang);

		$translation = array(
			'catalán' => 'ca',
			'inglés' => 'en',
			'castellano' => 'es',
			'español' => 'es',
			'italiano' => 'it',
			'francés' => 'fr',
			'portugués' => 'pt',
			'holandés' => 'nl',
			'danés' => 'da',
			'noruego' => 'no',
			'sueco' => 'se',
			'finlandés' => 'fi',
			'alemán' => 'de',
			'checo' => 'cs',
			'húngaro' => 'hu',
			'polaco' => 'pl',
			'croata' => 'hr',
			'griego' => 'el',
			'hebreo' => 'he',
			'rumano' => 'ro',
			'serbio' => 'sr',
			'esloveno' => 'sl',
			'turco' => 'tr',
			'ruso' => 'ru',
			'chino mandarín' => 'cmn',
		);

		if (isset($translation[$lang])) {
			return $translation[$lang];
		} else {
			return false;
		}
	}
}