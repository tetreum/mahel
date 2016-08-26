<?php

namespace App\System;

use QueryPath\Exception;

class Matcher
{
    private $index = null;
    private $indexParsed = null;

    private $dbFolder = null;

    /**
     * @var \MicroDB\Database
     */
    private $db = null;

    private function getDB ()
    {
        if (empty($this->db)) {
            $this->db = App::getDB("medias");
        }

        return $this->db;
    }

    public function createMedia ($item)
    {
        $this->getDB()->create($item);
    }

    private function getForcedMatches ($provider)
    {
        $filePath = $this->dbFolder . "forced_matches/" . strtolower($provider) . ".json";

        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
        } else {
            $json = "{}";
        }

        return json_decode($json);
    }

    public function match ($media)
    {
        $forcedMatches = $this->getForcedMatches($media["provider"]);

        // check if this url was already matched with a media
        if (isset($forcedMatches->{$media["url"]})) {
            $id = $forcedMatches->{$media["url"]};
        } else {
            $id = $this->searchByName($media);
        }

        if (!$id) {
            return false;
        }

        $this->merge($id, $media);
        return true;
    }

    private function merge ($id, array $media)
    {
        $originalMedia = $this->getMedia($id);

        // try to increase media's data
        foreach ($media as $k => $v)
        {
            if (!isset($originalMedia[$k])) {
                $originalMedia[$k] = $v;
            }
            else if ($k == "links")
            {
                $linksList = [];

                foreach ($originalMedia["links"] as $link) {
                    $linksList[] = $link["url"];
                }

                foreach ($v as $link) {
                    if (!in_array($link["url"], $linksList)) {
                        $originalMedia["links"][] = $link;
                    }
                }
            } else if ($k == "episodes") {
                //@ ToDo merge for episode links
                continue;
            }
        }
        $this->setMedia($id, $originalMedia);
    }

    public function searchByName ($media)
    {
        $this->getIndex();

        // no shortest distance found, yet
        $shortest = -1;
        $maxDistance = 5;

        foreach ($this->indexParsed as $word)
        {
            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein(strtolower($media["title"]['es']), strtolower($word));

            // check for an exact match
            if ($lev == 0)
            {
                $closest = $word;
                $shortest = 0;
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest  = $word;
                $shortest = $lev;
            }
        }

        if (!isset($closest) || $shortest > $maxDistance) {
            return false;
        }

        return $this->index[$closest][0];
    }

    private function getMedia ($id)
    {
        return $this->getDB()->load($id);
    }

    private function setMedia ($id, $media)
    {
        $this->getDB()->save($id, $media);
    }

    public function getIndex ()
    {
        if (empty($this->index))
        {
            try {
                $index = json_decode(@file_get_contents($this->dbFolder . "medias/_title_index"), true);

                if (empty($index)) {
                    throw new \Exception("index not available");
                }

                $this->index = $index["map"];
                $this->indexParsed = array_keys($this->index);
            } catch (\Exception $e) {
                $this->index = [];
                $this->indexParsed = [];
            }
        }
        return $this->index;
    }
}