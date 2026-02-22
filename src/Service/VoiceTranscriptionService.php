<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class VoiceTranscriptionService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @return array{text:string,provider:string}
     */
    public function transcribe(string $filePath): array
    {
        $apiKey = (string) ($_ENV['OPENAI_API_KEY'] ?? '');
        if ($apiKey === '') {
            throw new \RuntimeException('Service vocal non configure. Merci de contacter l administrateur.');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'body' => [
                    'model' => 'whisper-1',
                    'language' => 'fr',
                    'response_format' => 'json',
                    'prompt' => 'Transcription medicale en francais. Conserver strictement les phrases importantes de detresse.',
                    'file' => fopen($filePath, 'r'),
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray(false);
            $text = trim((string) ($data['text'] ?? ''));

            if ($text === '') {
                throw new \RuntimeException('Transcription vide. Merci de reessayer avec un enregistrement plus clair.');
            }

            return [
                'text' => $text,
                'provider' => 'openai_whisper',
            ];
        } catch (\Throwable $e) {
            throw new \RuntimeException('Echec de la transcription vocale automatique.', 0, $e);
        }
    }
}
