<?php

namespace App\Controller;

use App\Form\SummonerType;
use App\Service\API\LOL\LeagueApi;
use App\Service\API\LOL\MatchApi;
use App\Service\API\LOL\SummonerApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SummonerController
 * @package App\Controller
 * @Route("/summoner")
 */
class SummonerController extends AbstractController
{
    /**
     * @var SummonerApi
     */
    private $summonerApi;

    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var LeagueApi
     */
    private $leagueApi;
    /**
     * @var MatchApi
     */
    private $matchApi;

    /**
     * SummonerController constructor.
     * @param SummonerApi $summonerApi
     * @param LeagueApi $leagueApi
     * @param MatchApi $matchApi
     * @param RequestStack $requestStack
     */
    public function __construct(
        SummonerApi $summonerApi,
        LeagueApi $leagueApi,
        MatchApi $matchApi,
        RequestStack $requestStack
    ) {
        $this->summonerApi = $summonerApi;
        $this->leagueApi = $leagueApi;
        $this->matchApi = $matchApi;
        $this->requestStack = $requestStack;
    }


    /**
     * @Route("/", name="summoner_index")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(SummonerType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->requestStack->getSession()->set('platform', $data['platform']);
            return $this->redirectToRoute("summoner_show", ['name' => $data['name']]);
        }

        return $this->render('summoner/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{name}", name="summoner_show")
     */
    public function show(string $name): Response
    {
        $platform = $this->requestStack->getSession()->get('platform'); // Recup la platform en session

        $summoner = $this->summonerApi->getSummonerBySummonerName($platform, $name);
        if (is_null($summoner)) {
            $this->addFlash('summoner', 'Summoners Non trouvé');
            return $this->redirectToRoute('summoner_index');
        }

        $infoSummonerleague = $this->leagueApi->getLeagueBySummonerId($summoner->getId(), $platform);

        $listMatchId      = $this->matchApi->getListIdMatchBySummonerPuuid($summoner->getPuuid(), $platform);
        $matchSummoner = [];
        foreach ($listMatchId as $key => $matchId) {
            $matchSummoner[$matchId] =  $this->matchApi->getMatchByMatchId($matchId, $platform);
        }

        $leagueSummoner     = $this->leagueApi->getLeagueByLeagueId($platform, $infoSummonerleague[0]->getLeagueId());
        $leagues            = $this->trieParRank($leagueSummoner->getEntries());
        $this->descendingSort($leagues[$infoSummonerleague[0]->getRank()], "leaguePoints");

        return $this->render('summoner/show.html.twig', [
            'summoner'              => $summoner,
            'infoSummonerleague'    => $infoSummonerleague[0],
            'leagues'               => $leagues[$infoSummonerleague[0]->getRank()],
            'matchsDetail'          => $matchSummoner,
            "matchSummoner"         => $matchSummoner
        ]);
    }



    /**
     * @param array $data
     * @param string $field
     */
    private function descendingSort(array &$data, string $field)
    {
        usort($data, function ($item1, $item2) use ($field) {
            $method = "get$field";
            return $item2->$method() <=> $item1->$method();
        });
    }

    /**
     * @param array $datas
     * @return array|null
     */
    private function trieParRank(array $datas): ?array
    {
        $league = [];
        foreach ($datas as $key => $summonerRank) {
            switch ($summonerRank->getRank()) {
                case "I":
                    $league['I'][] = $summonerRank;
                    break;
                case "II":
                    $league['II'][] = $summonerRank;
                    break;
                case "III":
                    $league['III'][] = $summonerRank;
                    break;
                case "IV":
                    $league['IV'][] = $summonerRank;
                    break;
            }
        }
        if (empty($league)) {
            return null;
        }
        return $league;
    }
}
