<?php

declare(strict_types=1);

namespace App\Service;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const GROQ_BASE_URL = 'https://api.groq.com/openai/v1/chat/completions';

    /** @var string[] */
    private const MODELS = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-flash-8b',
    ];

    /** @var string[] */
    private const GROQ_MODELS = [
        'llama-3.1-8b-instant',
        'llama-3.3-70b-versatile',
        'mixtral-8x7b-32768',
    ];

    /** @var string[] */
    private const PSYCHOLOGICAL_KEYWORDS = [
        'psycholog',
        'mental',
        'emotion',
        'stress',
        'anx',
        'angoiss',
        'depress',
        'triste',
        'humeur',
        'peur',
        'panique',
        'trauma',
        'burnout',
        'fatigue mentale',
        'sommeil',
        'insomnie',
        'estime de soi',
        'confiance en soi',
        'therapie',
        'psy',
        'relation',
        'solitude',
        'harcelement',
        'deuil',
    ];

    /** @var string[] */
    private const GREETING_KEYWORDS = [
        'salut',
        'bonjour',
        'bonsoir',
        'hello',
        'hey',
        'coucou',
    ];

    private HttpClientInterface $client;
    private string $apiKey;
    private string $groqApiKey;

    public function __construct(HttpClientInterface $client, string $geminiApiKey, string $groqApiKey = '')
    {
        $this->client = $client;
        $this->apiKey = trim($geminiApiKey);
        $this->groqApiKey = trim($groqApiKey);
    }

    /**
     * @return array{ok: bool, reply?: string, error?: string, status?: int}
     */
    public function ask(string $message): array
    {
        if ($this->apiKey === '' && $this->groqApiKey === '') {
            return [
                'ok' => false,
                'status' => 500,
                'error' => 'Cles API absentes. Configurez GEMINI_API_KEY ou GROQ_API_KEY dans .env.local.',
            ];
        }

        $cleanMessage = trim($message);
        if ($cleanMessage === '') {
            return [
                'ok' => false,
                'status' => 400,
                'error' => 'Le message ne peut pas etre vide.',
            ];
        }

        if (!$this->isPsychologicalMessage($cleanMessage)) {
            return [
                'ok' => false,
                'status' => 400,
                'error' => "Je suis un assistant de soutien psychologique.\n\nJe peux vous aider sur :\n- le stress, l'anxiete, la tristesse\n- les emotions et les relations\n- la confiance en soi, le sommeil, le burnout\n\nExemples de questions :\n- \"Je me sens triste, que faire ?\"\n- \"Comment gerer mon anxiete ?\"\n- \"J'ai du mal a dormir a cause du stress\"",
            ];
        }

        if ($this->apiKey !== '') {
            $geminiResult = $this->askWithGemini($cleanMessage);
            if (($geminiResult['ok'] ?? false) === true) {
                return $geminiResult;
            }
        } else {
            $geminiResult = [
                'ok' => false,
                'status' => 500,
                'error' => 'Cle API Gemini absente.',
            ];
        }

        if ($this->groqApiKey !== '') {
            $groqResult = $this->askWithGroq($cleanMessage);
            if (($groqResult['ok'] ?? false) === true) {
                return $groqResult;
            }
        } else {
            $groqResult = [
                'ok' => false,
                'status' => 500,
                'error' => 'Cle API Groq absente.',
            ];
        }

        $geminiError = $this->normalizeText((string) ($geminiResult['error'] ?? 'Erreur API Gemini.'));
        $groqError = $this->normalizeText((string) ($groqResult['error'] ?? 'Erreur API Groq.'));
        $status = (int) max((int) ($geminiResult['status'] ?? 502), (int) ($groqResult['status'] ?? 502));
        if ($status < 400 || $status > 599) {
            $status = 502;
        }

        return [
            'ok' => false,
            'status' => $status,
            'error' => $this->normalizeText('Gemini: '.$geminiError.' | Groq: '.$groqError),
        ];
    }

    /**
     * @return array{ok: bool, reply?: string, error?: string, status?: int}
     */
    private function askWithGemini(string $cleanMessage): array
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'Tu es un assistant de soutien bienveillant. Tu donnes des conseils generaux et tu recommandes de consulter un professionnel en cas de risque ou de detresse. Reponds en francais, clairement et brievement.\n\nMessage utilisateur : '.$cleanMessage,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 512,
            ],
        ];

        $lastStatus = 502;
        $lastError = 'Erreur API Gemini.';

        try {
            foreach (self::MODELS as $model) {
                $response = $this->client->request('POST', sprintf(self::BASE_URL, $model), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-goog-api-key' => $this->apiKey,
                    ],
                    'json' => $payload,
                    'timeout' => 20,
                ]);

                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);

                if ($statusCode < 400) {
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (is_string($text) && trim($text) !== '') {
                        return [
                            'ok' => true,
                            'reply' => $this->normalizeText($text),
                        ];
                    }

                    $lastStatus = 502;
                    $lastError = 'Reponse Gemini invalide ou vide.';
                    continue;
                }

                $apiError = (string) ($data['error']['message'] ?? 'Erreur API Gemini (HTTP '.$statusCode.').');
                $lastStatus = $statusCode;
                $lastError = $this->translateGeminiError($statusCode, $apiError);

                if ($statusCode !== 404) {
                    break;
                }
            }

            return [
                'ok' => false,
                'status' => $lastStatus,
                'error' => $this->normalizeText($lastError),
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'ok' => false,
                'status' => 503,
                'error' => 'Impossible de contacter Gemini. Verifiez la connexion reseau.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'error' => 'Erreur interne lors de l\'appel a Gemini.',
            ];
        }
    }

    /**
     * @return array{ok: bool, reply?: string, error?: string, status?: int}
     */
    private function askWithGroq(string $cleanMessage): array
    {
        $lastStatus = 502;
        $lastError = 'Erreur API Groq.';

        foreach (self::GROQ_MODELS as $model) {
            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un assistant de soutien bienveillant. Tu donnes des conseils generaux et tu recommandes de consulter un professionnel en cas de risque ou de detresse. Reponds en francais, clairement et brievement.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $cleanMessage,
                    ],
                ],
                'temperature' => 0.4,
                'max_tokens' => 512,
            ];

            try {
                $response = $this->client->request('POST', self::GROQ_BASE_URL, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->groqApiKey,
                    ],
                    'json' => $payload,
                    'timeout' => 20,
                ]);

                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);

                if ($statusCode < 400) {
                    $text = $data['choices'][0]['message']['content'] ?? null;
                    if (is_string($text) && trim($text) !== '') {
                        return [
                            'ok' => true,
                            'reply' => $this->normalizeText($text),
                        ];
                    }

                    $lastStatus = 502;
                    $lastError = 'Reponse Groq invalide ou vide.';
                    continue;
                }

                $apiError = (string) ($data['error']['message'] ?? 'Erreur API Groq (HTTP '.$statusCode.').');
                $lastStatus = $statusCode;
                $lastError = $this->translateGroqError($statusCode, $apiError);

                if ($statusCode !== 404) {
                    break;
                }
            } catch (TransportExceptionInterface $e) {
                $lastStatus = 503;
                $lastError = 'Impossible de contacter Groq. Verifiez la connexion reseau.';
                break;
            } catch (\Throwable $e) {
                $lastStatus = 500;
                $lastError = 'Erreur interne lors de l\'appel a Groq.';
                break;
            }
        }

        return [
            'ok' => false,
            'status' => $lastStatus,
            'error' => $this->normalizeText($lastError),
        ];
    }

    private function translateGeminiError(int $statusCode, string $apiError): string
    {
        $raw = $this->normalizeText($apiError);

        if ($statusCode === 429) {
            $retry = null;
            if (preg_match('/Please retry in\s+([0-9.]+)s/i', $raw, $match) === 1) {
                $retry = (int) ceil((float) $match[1]);
            }

            if (str_contains(strtolower($raw), 'quota exceeded')) {
                $message = 'Quota Gemini depasse sur ce projet Google Cloud.';
                if ($retry !== null) {
                    return $message.' Reessayez dans environ '.$retry.' secondes.';
                }

                return $message.' Reessayez plus tard ou activez un plan de facturation.';
            }

            return 'Trop de requetes vers Gemini. Reessayez dans quelques instants.';
        }

        if ($statusCode === 401 || $statusCode === 403) {
            return 'Cle API Gemini invalide ou non autorisee.';
        }

        if ($statusCode === 404) {
            return 'Modele Gemini indisponible sur votre cle API.';
        }

        return $raw !== '' ? $raw : 'Erreur API Gemini (HTTP '.$statusCode.').';
    }

    private function translateGroqError(int $statusCode, string $apiError): string
    {
        $raw = $this->normalizeText($apiError);

        if ($statusCode === 429) {
            return 'Trop de requetes vers Groq. Reessayez dans quelques instants.';
        }

        if ($statusCode === 401 || $statusCode === 403) {
            return 'Cle API Groq invalide ou non autorisee.';
        }

        if ($statusCode === 404) {
            return 'Modele Groq indisponible sur votre cle API.';
        }

        return $raw !== '' ? $raw : 'Erreur API Groq (HTTP '.$statusCode.').';
    }

    private function normalizeText(string $text): string
    {
        $value = trim($text);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        return trim($value);
    }

    private function isPsychologicalMessage(string $message): bool
    {
        $normalized = strtolower($this->normalizeText($message));
        if ($normalized === '') {
            return false;
        }

        foreach (self::GREETING_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        foreach (self::PSYCHOLOGICAL_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
