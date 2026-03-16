<?php

namespace Mads\Plugin\Ajax\Clinicchatbotproxy\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

final class Clinicchatbotproxy extends CMSPlugin
{
    protected $autoloadLanguage = true;
    private const BACKEND_CHAT_URL = 'http://localhost:3000/api/chat';

    public function onAjaxClinicchatbotproxy(): array
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

        if ($message === '' || mb_strlen($message) > 4000) {
            throw new \RuntimeException('Invalid message', 400);
        }

        if ($sessionId === '' || mb_strlen($sessionId) > 200) {
            throw new \RuntimeException('Invalid sessionId', 400);
        }

        if ($pageUrl === '' || mb_strlen($pageUrl) > 2000 || !filter_var($pageUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid pageUrl', 400);
        }

        $params = ComponentHelper::getParams('com_clinicchatbot');

        $clinicName = trim((string) $params->get('clinic_name', ''));
        $clinicPhone = trim((string) $params->get('clinic_phone', ''));
        $clinicAddress = trim((string) $params->get('clinic_address', ''));
        $bookingUrl = trim((string) $params->get('booking_url', ''));

        $backendUrl = self::BACKEND_CHAT_URL;
        $clientId = trim((string) $params->get('client_id', ''));
        $clientSecret = (string) $params->get('client_secret', '');
        $timeoutSeconds = 30;

        if (
            $clinicName === '' ||
            $clinicPhone === '' ||
            $clinicAddress === '' ||
            $bookingUrl === '' ||
            !filter_var($bookingUrl, FILTER_VALIDATE_URL)
        ) {
            throw new \RuntimeException('Clinic settings are incomplete', 500);
        }

        if (!filter_var($backendUrl, FILTER_VALIDATE_URL) || $clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Component backend configuration is incomplete', 500);
        }

        $payload = [
            'message' => $message,
            'sessionId' => $sessionId,
            'pageUrl' => $pageUrl,
            'clinic' => [
                'clinicName' => $clinicName,
                'phone' => $clinicPhone,
                'address' => $clinicAddress,
                'bookingUrl' => $bookingUrl,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new \RuntimeException('Failed to encode request body', 500);
        }

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $clientSecret);

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
        $response = $http->post($backendUrl, $body, $headers);

        $responseBody = (string) $response->body;
        $decoded = json_decode($responseBody, true);

        if ((int) $response->code < 200 || (int) $response->code >= 300) {
            $backendMessage = 'Backend request failed';

            if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
                $backendMessage = $decoded['error'];
            }

            throw new \RuntimeException($backendMessage, (int) $response->code);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid backend response', 502);
        }

        return $decoded;
    }
}
