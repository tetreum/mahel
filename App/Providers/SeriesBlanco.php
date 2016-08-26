<?php
namespace App\Providers;

use App\System\App;
use App\System\Provider;
use App\System\ProviderException;

class SeriesBlanco extends \App\System\Provider
{
    private $domain = "http://seriesblanco.com";

    public function start ()
    {
        $xml = @simplexml_load_string($this->getRawContent($this->domain . "/sitemap.xml"));
        
        if (empty($xml)) {
            throw new ProviderException(ProviderException::CRAWLING_ERROR, "failed to fetch sitemap.xml");
        }

        foreach ($xml->url as $obj)
        {
            if (strpos($obj->loc, "/serie/") !== false) {
                $this->parseSerie("http://seriesblanco.com/serie/182/el-mentalista.html");
                $this->parseSerie($obj->loc);
            }
        }
    }

    private function parseSerie ($url)
    {
        $html = $this->getContent($url);

        $data = [
            "provider" => "SeriesBlanco",
            "type" => self::TYPE_SERIE,
            "url" => $url,
            "title" => [
                "es" =>trim($html->find(".cd-tabs-content h4")[0]->text())
            ],
            "seasons" => []
        ];

        // Parse its episodes
        $maxEpisodes = [];

        $i = 1;
        foreach ($html->find(".post-body.entry-content table.zebra") as $table) {
            $maxEpisodes[$i] = sizeof($table->find("tr")); // max episodes per season
            $i++;
        }

        $data["episodes"] = $this->parseEpisodes($url, $maxEpisodes);

        exit;
    }

    private function parseEpisodes ($url, array $maxEpisodes)
    {
        // from http://seriesblanco.com/serie/182/el-mentalista.html
        // to http://seriesblanco.com/serie/182/
        // to http://seriesblanco.com/serie/182/temporada-4/capitulo-02/el-mentalista.html
        preg_match("/\/serie\/(\d+)\/(.*)\.html/", $url, $matches);

        if (!isset($matches[2])) {
            return false;
        }
        $slug = $matches[2] . ".html";
        $baseUrl = str_replace($slug, "", $url);
        $maxSeason = sizeof($maxEpisodes);
        $season = 1;
        $episodes = [];

        while ($season < $maxSeason)
        {
            $episode = 1;

            while ($episode < $maxEpisodes[$season])
            {
                $prettyEpisode = $episode;

                if ($episode < 10) {
                    $prettyEpisode = "0" . $episode;
                }

                $html = $this->getContent($baseUrl . "temporada-$season/capitulo-$prettyEpisode/" . $slug);
                $links = [];

                foreach ($html->find(".as_gridder_table")[0]->find(".odd, .even") as $link)
                {
                    // get the audio language
                    $src = $link->find(".grid_content2.sno img")[0]->attr("src");
                    $src = str_replace("http://seriesblanco.com/banderas/", "", $src);
                    $audio = str_replace(".png", "", $src);
                    $tds = $link->find("td");

                    $links[] = [
                        "url" => $link->find("a")[0]->attr("href"),
                        "uploader" => trim($tds[3]->text()),
                        "created_at" => trim($tds[0]->text()),
                        "audio" => trim($audio),
                    ];
                }

                if (!isset($episodes[$season])){
                    $episodes[$season] = [];
                }
                $episodes[$season][$episode] = $links;

                $episode++;
            }
            $season++;
        }

        return $episodes;
    }
}
