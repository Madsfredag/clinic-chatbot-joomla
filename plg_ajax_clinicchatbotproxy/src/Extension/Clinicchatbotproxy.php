<?php

namespace ClinicChatbotProxy\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

final class Clinicchatbotproxy extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAjaxClinicchatbotproxy' => 'onAjaxClinicchatbotproxy',
        ];
    }

    public function onAjaxClinicchatbotproxy(Event $event): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            throw new \RuntimeException('Invalid client', 404);
        }

        if (strtoupper($app->input->server->getString('REQUEST_METHOD')) !== 'POST') {
            throw new \RuntimeException('POST required', 405);
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            throw new \RuntimeException('Empty request body', 400);
        }

        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON', 400);
        }

        $message = trim((string) ($data['message'] ?? ''));
        $sessionId = trim((string) ($data['sessionId'] ?? ''));
        $pageUrl = trim((string) ($data['pageUrl'] ?? ''));
        $clinic = $data['clinic'] ?? null;

        if ($message === '' || mb_strlen($message) > 4000) {
            throw new \RuntimeException('Invalid message', 400);
        }

        if ($sessionId === '' || mb_strlen($sessionId) > 200) {
            throw new \RuntimeException('Invalid sessionId', 400);
        }

        if ($pageUrl === '' || mb_strlen($pageUrl) > 2000 || !filter_var($pageUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid pageUrl', 400);
        }

        if (!is_array($clinic)) {
            throw new \RuntimeException('Invalid clinic data', 400);
        }

        $clinicName = trim((string) ($clinic['clinicName'] ?? ''));
        $phone = trim((string) ($clinic['phone'] ?? ''));
        $address = trim((string) ($clinic['address'] ?? ''));
        $bookingUrl = trim((string) ($clinic['bookingUrl'] ?? ''));

        if ($clinicName === '') {
            throw new \RuntimeException('Invalid clinicName', 400);
        }

        if ($phone === '') {
            throw new \RuntimeException('Invalid phone', 400);
        }

        if ($address === '') {
            throw new \RuntimeException('Invalid address', 400);
        }

        if ($bookingUrl === '' || !filter_var($bookingUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid bookingUrl', 400);
        }

        $backendUrl = trim((string) $this->params->get('backend_url', ''));
        $clientId = trim((string) $this->params->get('client_id', ''));
        $clientSecret = (string) $this->params->get('client_secret', '');
        $timeoutSeconds = (int) $this->params->get('timeout_seconds', 10);

        if ($backendUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Plugin not configured', 500);
        }

        $payload = [
            'message' => $message,
            'sessionId' => $sessionId,
            'pageUrl' => $pageUrl,
            'clinic' => [
                'clinicName' => $clinicName,
                'phone' => $phone,
                'address' => $address,
                'bookingUrl' => $bookingUrl,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new \RuntimeException('Failed to encode request body', 500);
        }

        $timestamp = (string) time();
        $signedPayload = $timestamp . '.' . $body;
        $signature = hash_hmac('sha256', $signedPayload, $clientSecret);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Client-Id' => $clientId,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ];

        $options = new Registry([
            'timeout' => max(1, min($timeoutSeconds, 60)),
        ]);

        $http = HttpFactory::getHttp($options);

        try {
            $response = $http->post($backendUrl, $body, $headers);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Backend connection failed', 502, $e);
        }

        $responseBody = (string) $response->body;
        $decodedResponse = json_decode($responseBody, true);

        if ((int) $response->code < 200 || (int) $response->code >= 300) {
            $backendMessage = 'Backend request failed';

            if (is_array($decodedResponse) && isset($decodedResponse['error']) && is_string($decodedResponse['error'])) {
                $backendMessage = $decodedResponse['error'];
            }

            throw new \RuntimeException($backendMessage, (int) $response->code);
        }

        if (!is_array($decodedResponse)) {
            throw new \RuntimeException('Invalid backend response', 502);
        }

        $event->addResult($decodedResponse);
    }
}