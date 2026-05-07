<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MailjetSmsService
{
    private $httpClient;
    private $logger;
    private $apiKey;
    private $secretKey;
    private $from;
    private $debug;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $_ENV['MAILJET_API_KEY'] ?? null;
        $this->secretKey = $_ENV['MAILJET_SECRET_KEY'] ?? null;
        $this->from = $_ENV['MAILJET_SMS_FROM'] ?? 'Emonado';
        $this->debug = true; // Mode debug en attendant la validation
    }

    public function sendSms(string $to, string $message): bool
    {
        if ($this->debug) {
            $this->logger->info("🔧 [DEBUG] SMS simulé vers $to : $message");
            return true;
        }

        if (!$this->apiKey || !$this->secretKey) {
            $this->logger->error('Clés Mailjet non configurées');
            return false;
        }

        try {
            $to = preg_replace('/\s+/', '', $to);
            $auth = base64_encode($this->apiKey . ':' . $this->secretKey);
            
            $response = $this->httpClient->request('POST', 'https://api.mailjet.com/v4/sms-send', [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'From' => $this->from,
                    'To' => $to,
                    'Text' => $message,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            
            if ($statusCode === 200) {
                $this->logger->info("✅ SMS envoyé avec succès à $to");
                return true;
            } else {
                $this->logger->error("❌ Erreur Mailjet ($statusCode) : $content");
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('❌ Exception Mailjet : ' . $e->getMessage());
            return false;
        }
    }

    public function sendRappelRdv(string $to, string $patient, string $psychologue, string $date): bool
    {
        $message = sprintf(
            "🔔 RAPPEL RENDEZ-VOUS\n%s\nDr. %s\n%s",
            $patient,
            $psychologue,
            $date
        );
        return $this->sendSms($to, $message);
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
}