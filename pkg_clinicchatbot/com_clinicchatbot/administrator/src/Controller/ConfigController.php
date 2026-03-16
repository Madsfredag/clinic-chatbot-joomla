<?php

namespace Mads\Component\Clinicchatbot\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Extension;
use Joomla\Registry\Registry;
use Mads\Component\Clinicchatbot\Administrator\Support\XlsxWorkbookReader;
use Throwable;

final class ConfigController extends BaseController
{
    private const BACKEND_CHAT_URL = 'http://localhost:3000/api/chat';
    public function save(): void
    {
        Session::checkToken() or jexit('Invalid Token');

        $app = Factory::getApplication();

        try {
            $data = $this->getPostedConfigData();
            $this->persistConfig($data);
            $this->syncTenantIfRegistered($data);

            $app->enqueueMessage('Indstillinger gemt.', 'message');
        } catch (Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_clinicchatbot');
    }

    public function registerClinic(): void
    {
        Session::checkToken() or jexit('Invalid Token');

        $app = Factory::getApplication();

        try {
            $data = $this->getPostedConfigData();

            $backendUrl = self::BACKEND_CHAT_URL;

            if (!filter_var($backendUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Backend URL i koden er ugyldig.');
            }

            $response = $this->postJson(
                $this->buildTenantRegistrationEndpointUrl($backendUrl),
                [
                    'clinicName' => $data['clinic_name'],
                    'phone' => $data['clinic_phone'],
                    'address' => $data['clinic_address'],
                    'bookingUrl' => $data['booking_url'],
                    'welcomeMessage' => $data['welcome_message'],
                    'clinicKnowledge' => $data['clinic_knowledge'],
                ],
                (int) $data['timeout_seconds']
            );

            $tenant = $response['tenant'] ?? null;

            if (!is_array($tenant)) {
                throw new \RuntimeException('Backend returnerede ikke tenant-oplysninger.');
            }

            $data['client_id'] = trim((string) ($tenant['clientId'] ?? ''));
            $data['client_secret'] = trim((string) ($tenant['clientSecret'] ?? ''));

            if ($data['client_id'] === '' || $data['client_secret'] === '') {
                throw new \RuntimeException('Backend returnerede ikke gyldige credentials.');
            }

            $this->persistConfig($data);

            $app->enqueueMessage(
                'Klinikken blev registreret og forbundet til backend. Fremtidige ændringer synkroniseres automatisk.',
                'message'
            );
        } catch (Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_clinicchatbot');
    }

    public function uploadPrices(): void
    {
        Session::checkToken() or jexit('Invalid Token');

        $app = Factory::getApplication();

        try {
            $file = $app->input->files->get('prices_xlsx', null, 'array');

            if (!is_array($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new \RuntimeException('Vælg en XLSX-fil først.');
            }

            $originalName = (string) ($file['name'] ?? '');
            $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

            if ($extension !== 'xlsx') {
                throw new \RuntimeException('Filen skal være en .xlsx-fil.');
            }

            $workbook = XlsxWorkbookReader::readWorkbook($file['tmp_name']);

            if (!isset($workbook['Behandlinger'])) {
                throw new \RuntimeException('Arket "Behandlinger" blev ikke fundet i XLSX-filen.');
            }

            if (!isset($workbook['Danmark ekstra tilskud'])) {
                throw new \RuntimeException('Arket "Danmark ekstra tilskud" blev ikke fundet i XLSX-filen.');
            }

            $payload = [
                'sheets' => [
                    'behandlinger' => $workbook['Behandlinger'],
                    'danmarkEkstraTilskud' => $workbook['Danmark ekstra tilskud'],
                ],
            ];

            $params = ComponentHelper::getParams('com_clinicchatbot');

            $backendUrl = self::BACKEND_CHAT_URL;
            $clientId = trim((string) $params->get('client_id', ''));
            $clientSecret = (string) $params->get('client_secret', '');
            $timeoutSeconds = 30;

            if (!filter_var($backendUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Backend URL i koden er ugyldig.');
            }

            if ($clientId === '' || $clientSecret === '') {
                throw new \RuntimeException('Klik først på "Registrer klinik" for at oprette forbindelsen til backend.');
            }

            $pricesUrl = $this->buildPricesEndpointUrl($backendUrl);
            $decoded = $this->postSignedJson($pricesUrl, $payload, $clientId, $clientSecret, $timeoutSeconds);

            $sheetsStored = is_array($decoded) && isset($decoded['sheetsStored']) ? (int) $decoded['sheetsStored'] : 0;
            $rowsStored = is_array($decoded) && isset($decoded['rowsStored']) ? (int) $decoded['rowsStored'] : 0;

            $app->enqueueMessage(
                'Prisfil uploadet. Ark gemt: ' . $sheetsStored . '. Rækker gemt: ' . $rowsStored . '.',
                'message'
            );
        } catch (Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_clinicchatbot');
    }

    private function getPostedConfigData(): array
    {
        $data = $this->input->post->get('jform', [], 'array');

        return [
            'clinic_name' => trim((string) ($data['clinic_name'] ?? '')),
            'clinic_phone' => trim((string) ($data['clinic_phone'] ?? '')),
            'clinic_address' => trim((string) ($data['clinic_address'] ?? '')),
            'booking_url' => trim((string) ($data['booking_url'] ?? '')),
            'welcome_message' => trim((string) ($data['welcome_message'] ?? '')),
            'clinic_knowledge' => trim((string) ($data['clinic_knowledge'] ?? '')),
            'client_id' => trim((string) ($data['client_id'] ?? '')),
            'client_secret' => trim((string) ($data['client_secret'] ?? '')),
            'timeout_seconds' => max(1, min((int) ($data['timeout_seconds'] ?? 10), 60)),
        ];
    }

    private function persistConfig(array $data): void
    {
        $table = new Extension($this->getDatabase());
        $table->load(['element' => 'com_clinicchatbot', 'type' => 'component']);

        $registry = new Registry($table->params);

        foreach ($data as $field => $value) {
            $registry->set($field, $value);
        }

        $table->params = (string) $registry;

        if (!$table->store()) {
            throw new \RuntimeException('Kunne ikke gemme indstillinger.');
        }
    }

    private function syncTenantIfRegistered(array $data): void
    {
        if ($data['client_id'] === '' || $data['client_secret'] === '') {
            return;
        }

        $this->postSignedJson(
            $this->buildTenantSyncEndpointUrl(self::BACKEND_CHAT_URL),
            [
                'clinicName' => $data['clinic_name'],
                'phone' => $data['clinic_phone'],
                'address' => $data['clinic_address'],
                'bookingUrl' => $data['booking_url'],
                'welcomeMessage' => $data['welcome_message'],
                'clinicKnowledge' => $data['clinic_knowledge'],
            ],
            $data['client_id'],
            $data['client_secret'],
            (int) $data['timeout_seconds']
        );
    }

    private function postJson(string $url, array $payload, int $timeoutSeconds): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new \RuntimeException('Kunne ikke oprette JSON-payload.');
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $options = new Registry([
            'timeout' => max(1, min($timeoutSeconds, 60)),
        ]);

        $http = HttpFactory::getHttp($options);
        $response = $http->post($url, $body, $headers);
        $decoded = json_decode((string) $response->body, true);

        if ((int) $response->code < 200 || (int) $response->code >= 300) {
            $message = 'Backend request failed.';

            if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
                $message = $decoded['error'];
            }

            throw new \RuntimeException($message, (int) $response->code);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid backend response.');
        }

        return $decoded;
    }

    private function postSignedJson(
        string $url,
        array $payload,
        string $clientId,
        string $clientSecret,
        int $timeoutSeconds
    ): array {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new \RuntimeException('Kunne ikke oprette JSON-payload.');
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
        $response = $http->post($url, $body, $headers);
        $decoded = json_decode((string) $response->body, true);

        if ((int) $response->code < 200 || (int) $response->code >= 300) {
            $message = 'Backend request failed.';

            if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
                $message = $decoded['error'];
            }

            throw new \RuntimeException($message, (int) $response->code);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid backend response.');
        }

        return $decoded;
    }

    private function buildPricesEndpointUrl(string $backendUrl): string
    {
        $trimmed = rtrim($backendUrl, '/');

        if (preg_match('#/api/chat$#', $trimmed) === 1) {
            return preg_replace('#/api/chat$#', '/api/clinic/prices', $trimmed) ?? $trimmed;
        }

        return $trimmed . '/api/clinic/prices';
    }

    private function buildTenantRegistrationEndpointUrl(string $backendUrl): string
    {
        $trimmed = rtrim($backendUrl, '/');

        if (preg_match('#/api/chat$#', $trimmed) === 1) {
            return preg_replace('#/api/chat$#', '/api/tenants/register', $trimmed) ?? $trimmed;
        }

        return $trimmed . '/api/tenants/register';
    }

    private function buildTenantSyncEndpointUrl(string $backendUrl): string
    {
        $trimmed = rtrim($backendUrl, '/');

        if (preg_match('#/api/chat$#', $trimmed) === 1) {
            return preg_replace('#/api/chat$#', '/api/tenants/sync', $trimmed) ?? $trimmed;
        }

        return $trimmed . '/api/tenants/sync';
    }

    private function getDatabase()
    {
        return Factory::getContainer()->get('DatabaseDriver');
    }
}
