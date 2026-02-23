<?php

namespace App\Service;

use App\Entity\Journal;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MusicTherapyService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @return array{
     *   objective:string,
     *   source:string,
     *   generatedAt:string,
     *   tracks:array<int, array{title:string,artist:string,url:string}>
     * }
     */
    public function generateForJournal(Journal $journal): array
    {
        $context = $this->buildMoodContext($journal);
        $tracks = $this->fetchSpotifyTracks($context['query']);
        $source = 'spotify';

        if ($tracks === []) {
            $tracks = $this->fallbackTracks($context['fallbackKey']);
            $source = 'fallback';
        }

        return [
            'objective' => $context['objective'],
            'source' => $source,
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'tracks' => $tracks,
        ];
    }

    /**
     * @return array{objective:string,query:string,fallbackKey:string}
     */
    private function buildMoodContext(Journal $journal): array
    {
        $humeur = mb_strtolower((string) $journal->getHumeur());
        $stress = $journal->getAnalysisEmotionnelle()?->getNiveauStress() ?? 5;

        if ($humeur === 'sos' || $stress >= 7) {
            return [
                'objective' => 'Stabiliser le souffle et reduire la surcharge emotionnelle en 10 minutes.',
                'query' => 'ambient healing breathwork calm piano',
                'fallbackKey' => 'crisis',
            ];
        }

        if ($humeur === 'en colere') {
            return [
                'objective' => 'Redescendre l activation physique sans couper l energie mentale.',
                'query' => 'lofi chill focus calm beats',
                'fallbackKey' => 'anger',
            ];
        }

        if ($humeur === 'heureux') {
            return [
                'objective' => 'Consolider l elan positif avec une routine musicale de gratitude.',
                'query' => 'acoustic uplifting positive mood',
                'fallbackKey' => 'joy',
            ];
        }

        return [
            'objective' => 'Maintenir un equilibre doux et une concentration stable.',
            'query' => 'soft piano relaxing focus',
            'fallbackKey' => 'calm',
        ];
    }

    /**
     * @return array<int, array{title:string,artist:string,url:string}>
     */
    private function fetchSpotifyTracks(string $query): array
    {
        $clientId = trim((string) ($_ENV['SPOTIFY_CLIENT_ID'] ?? ''));
        $clientSecret = trim((string) ($_ENV['SPOTIFY_CLIENT_SECRET'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            return [];
        }

        try {
            $tokenResponse = $this->httpClient->request(
                'POST',
                'https://accounts.spotify.com/api/token',
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => 'grant_type=client_credentials',
                    'timeout' => 8,
                ]
            );
            $tokenPayload = $tokenResponse->toArray(false);
            $accessToken = (string) ($tokenPayload['access_token'] ?? '');

            if ($accessToken === '') {
                return [];
            }

            $searchResponse = $this->httpClient->request(
                'GET',
                'https://api.spotify.com/v1/search',
                [
                    'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                    'query' => [
                        'q' => $query,
                        'type' => 'track',
                        'limit' => 5,
                        'market' => 'US',
                    ],
                    'timeout' => 8,
                ]
            );
            $searchPayload = $searchResponse->toArray(false);
            $items = $searchPayload['tracks']['items'] ?? [];

            $tracks = [];
            foreach ($items as $item) {
                $tracks[] = [
                    'title' => (string) ($item['name'] ?? 'Titre inconnu'),
                    'artist' => (string) ($item['artists'][0]['name'] ?? 'Artiste inconnu'),
                    'url' => (string) ($item['external_urls']['spotify'] ?? '#'),
                ];
            }

            return $tracks;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{title:string,artist:string,url:string}>
     */
    private function fallbackTracks(string $key): array
    {
        $catalog = [
            'crisis' => [
                ['title' => 'Weightless', 'artist' => 'Marconi Union', 'url' => 'https://open.spotify.com/search/Weightless%20Marconi%20Union'],
                ['title' => 'Nuvole Bianche', 'artist' => 'Ludovico Einaudi', 'url' => 'https://open.spotify.com/search/Nuvole%20Bianche%20Einaudi'],
                ['title' => 'Clair de Lune', 'artist' => 'Debussy', 'url' => 'https://open.spotify.com/search/Clair%20de%20Lune%20Debussy'],
            ],
            'anger' => [
                ['title' => 'Sunset Lover', 'artist' => 'Petit Biscuit', 'url' => 'https://open.spotify.com/search/Sunset%20Lover%20Petit%20Biscuit'],
                ['title' => 'Night Owl', 'artist' => 'Galimatias', 'url' => 'https://open.spotify.com/search/Night%20Owl%20Galimatias'],
                ['title' => 'Cold Little Heart', 'artist' => 'Michael Kiwanuka', 'url' => 'https://open.spotify.com/search/Cold%20Little%20Heart%20Kiwanuka'],
            ],
            'joy' => [
                ['title' => 'Good Life', 'artist' => 'OneRepublic', 'url' => 'https://open.spotify.com/search/Good%20Life%20OneRepublic'],
                ['title' => 'Walking on Sunshine', 'artist' => 'Katrina and the Waves', 'url' => 'https://open.spotify.com/search/Walking%20on%20Sunshine'],
                ['title' => 'Best Day of My Life', 'artist' => 'American Authors', 'url' => 'https://open.spotify.com/search/Best%20Day%20of%20My%20Life'],
            ],
            'calm' => [
                ['title' => 'River Flows in You', 'artist' => 'Yiruma', 'url' => 'https://open.spotify.com/search/River%20Flows%20in%20You%20Yiruma'],
                ['title' => 'Comptine d un autre ete', 'artist' => 'Yann Tiersen', 'url' => 'https://open.spotify.com/search/Comptine%20d%20un%20autre%20ete'],
                ['title' => 'Experience', 'artist' => 'Ludovico Einaudi', 'url' => 'https://open.spotify.com/search/Experience%20Ludovico%20Einaudi'],
            ],
        ];

        return $catalog[$key] ?? $catalog['calm'];
    }
}
