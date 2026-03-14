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
    public function save(): void
    {
        Session::checkToken() or jexit('Invalid Token');

        $app = Factory::getApplication();
        $data = $this->input->post->get('jform', [], 'array');

        $table = new Extension($this->getDatabase());
        $table->load(['element' => 'com_clinicchatbot', 'type' => 'component']);

        $registry = new Registry($table->params);

        $fields = [
            'clinic_id',
            'clinic_name',
            'clinic_phone',
            'clinic_address',
            'booking_url',
            'welcome_message',
            'clinic_knowledge',
            'backend_url',
            'client_id',
            'client_secret',
            'timeout_seconds',
        ];

        foreach ($fields as $field) {
            $registry->set($field, $data[$field] ?? '');
        }

        $table->params = (string) $registry;

        if (!$table->store()) {
            $app->enqueueMessage('Kunne ikke gemme indstillinger.', 'error');
            $this->setRedirect('index.php?option=com_clinicchatbot');
            return;
        }

        $app->enqueueMessage('Indstillinger gemt.', 'message');
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

            $backendUrl = trim((string) $params->get('backend_url', ''));
            $clientId = trim((string) $params->get('client_id', ''));
            $clientSecret = (string) $params->get('client_secret', '');
            $timeoutSeconds = (int) $params->get('timeout_seconds', 10);

            if ($backendUrl === '' || !filter_var($backendUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Backend URL mangler eller er ugyldig.');
            }

            if ($clientId === '' || $clientSecret === '') {
                throw new \RuntimeException('Client ID eller Client Secret mangler.');
            }

            $pricesUrl = $this->buildPricesEndpointUrl($backendUrl);

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
            $response = $http->post($pricesUrl, $body, $headers);

            $decoded = json_decode((string) $response->body, true);

            if ((int) $response->code < 200 || (int) $response->code >= 300) {
                $message = 'Upload til backend fejlede.';

                if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
                    $message = $decoded['error'];
                }

                throw new \RuntimeException($message);
            }

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

    private function buildPricesEndpointUrl(string $backendUrl): string
    {
        $trimmed = rtrim($backendUrl, '/');

        if (preg_match('#/api/chat$#', $trimmed) === 1) {
            return preg_replace('#/api/chat$#', '/api/clinic/prices', $trimmed) ?? $trimmed;
        }

        return $trimmed . '/api/clinic/prices';
    }

    private function getDatabase()
    {
        return Factory::getContainer()->get('DatabaseDriver');
    }
}