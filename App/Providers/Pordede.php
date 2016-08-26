<?php
namespace App\Providers;

use App\System\App;
use App\System\Provider;
use App\System\Utils;

class Pordede extends \App\System\Provider
{
    private $domain = "http://www.pordede.com";

    private $cookies = null;
    private $sessCheck = null;
    private $cookieFile = "/tmp/pordede.txt";

    public function start ()
    {
        $this->login();

        $this->getMovies();
    }

    private function getMovies ()
    {
        $f = Utils::curl($this->domain . "/pelis", [], [
            "method" => "get",
            "headers" => [
                "X-Requested-With:XMLHttpRequest"
            ],
            "cookieFile" => $this->cookieFile
        ]);
        p($f);
    }

    private function query ($url, array $data, array $options)
    {
        $defaultOptions = [
            "method" => "get",
            "headers" => [
                "X-Requested-With:XMLHttpRequest"
            ],
            "cookieFile" => $this->cookieFile
        ];

        // overwrite default options
        foreach ($options as $k => $v) {
            $defaultOptions[$k] = $v;
        }
        return json_decode(Utils::curl($this->domain . $url, $data, $defaultOptions));
    }

    private function login ()
    {
        // get sess check
        $html = $this->getContent($this->domain);
        $tmp = $html->find("script")[0]->text();

        preg_match("/SESS = \"(.*)?\"/", $tmp, $matches);
        $this->sessCheck = $matches[1];

        // login
        $json = $this->query("/site/login", [
        "LoginForm[username]" => App::$config["pordede"]["login"]["username"],
        "LoginForm[password]" => App::$config["pordede"]["login"]["password"],
        "popup" => 1,
        "sesscheck" => $this->sessCheck,
    ], [
            "method" => "post",
            "cookieFile" => $this->cookieFile,
            "saveCookies" => true
        ]);

        if (strpos($json->html, "Login correcto, entrando...") === false) {
            throw new \Exception("Login failed");
        }
    }
}