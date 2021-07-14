<?php


namespace App\Service\API\LOL;


class SummonerApi extends BaseApi
{

    const URL = "https://{region}.api.riotgames.com/lol/summoner/v4/summoners/by-name/{name}";

    public function getSummoner(string $region,string $name)
    {
        $url = $this->constructUrl(self::URL, ['region' => $region, 'name' => $name]);
        $this->callApi($url, "GET",  [
            'headers' => [
                'X-Riot-Token' => $this->apiKey,
            ]
        ]);
    }

    private function constructUrl(string $url, array $params)
    {
        foreach($params as $key => $param){
            $url = str_replace("{{$key}}", $param, $url);
        }
        return $url;
    }
}