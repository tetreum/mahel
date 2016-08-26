<?php
namespace App\Providers;

use App\System\App;
use App\System\Encrypter;
use App\System\Provider;

class Pepecine extends \App\System\Provider
{
    private $domain = "http://pepecine.net";

    private $token = null;

    public function start ()
    {
        $this->crawlPage(1);
    }

    private function crawlPage ($page)
    {
        $perPage = 40;
        $json = $this->getJson($this->domain . "/titles/paginate?_token=" . $this->getToken() . "&perPage=" . $perPage . "&page=$page&order=mc_num_of_votesDesc&type=movie&minRating=&maxRating=&availToStream=true");

        if (isset($json->items) && sizeof($json->items) > 1)
        {
            foreach ($json->items as $item) {
                $this->parseItem($item);
            }
            $page++;
            $this->crawlPage($page);
        }
    }

    private function getToken ()
    {
        if (empty($this->token))
        {
            $html = $this->getContent($this->domain . "/ver-pelicula-online");
            $scriptText = $html->find("script")[1]->text();

            preg_match("/token: '(.*)?'/", $scriptText, $matches);

            if (!isset($matches[1])) {
                throw new \Exception("Couldnt get token");
            }
            $this->token = $matches[1];
        }

        return $this->token;
    }

    private function parseItem ($item)
    {
        $data = [
            "provider" => "Pepecine",
            "type" => self::TYPE_MOVIE,
            "url" => "http://pepecine.net/ver-pelicula-online/" . $item->id,
            "title" => [
                "en" => $item->original_title,
                "es" => $item->title,
            ],
            "release" => [
                "es" => $item->release_date
            ],
            "year" => $item->year,
            "external_ids" => [
                "tmdb" => $item->tmdb_id
            ],
            "links" => [
            ]
        ];

        $audio = [
            "castellano" => "es",
            "latino" => "la",
            "zla" => "la",
            "subtitulado" => "subtitled",
        ];
        $qualities = [
            "hd" => "hd",
            "sd" => "sd",
            "rip" => "hd",
            "screener" => "sd",
        ];

        foreach ($item->link as $linkInfo)
        {
            $link = [
                "url" => $linkInfo->url,
                "created_at" => $linkInfo->created_at,
                "reports" => $linkInfo->reports
            ];

            foreach ($qualities as $keyword => $quality)
            {
                if (strpos(strtolower($linkInfo->quality), $keyword) !== false) {
                    $link["quality"] = $quality;
                    break;
                }
            }

            if (!isset($link["quality"])) {
                $link["quality"] = "sd";
            }

            foreach ($audio as $keyword => $lang)
            {
                if (strpos(strtolower($linkInfo->label), $keyword) !== false) {

                    if ($lang == "subtitled") {
                        $link["subtitled"] = "es";
                        $link["audio"] = "en";
                    } else {
                        $link["audio"] = $lang;
                    }

                    break;
                }
            }

            if (!isset($link["audio"])) {
                $link["audio"] = "en";
            }

            $data["links"][] = $link;
        }

        $this->sendToDB($data);
    }
}
