<?php

namespace Mads\Plugin\System\Clinicchatbot\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Clinicchatbot extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender' => 'onAfterRender',
        ];
    }

    public function onAfterRender(Event $event): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $document = $app->getDocument();

        if (!method_exists($document, 'getType') || $document->getType() !== 'html') {
            return;
        }

        $body = $app->getBody();

        if (!is_string($body) || $body === '') {
            return;
        }

        if (stripos($body, '</body>') === false) {
            return;
        }

        if (str_contains($body, 'id="clinic-chatbot"')) {
            return;
        }

        $params = ComponentHelper::getParams('com_clinicchatbot');

        $clinicName = trim((string) $params->get('clinic_name', ''));
        $clinicPhone = trim((string) $params->get('clinic_phone', ''));
        $clinicAddress = trim((string) $params->get('clinic_address', ''));
        $bookingUrl = trim((string) $params->get('booking_url', ''));
        $welcomeMessage = trim((string) $params->get(
            'welcome_message',
            'Hej og velkommen. Jeg kan hjælpe med spørgsmål om klinikken, behandlinger og generelle tandspørgsmål.'
        ));

        if (
            $clinicName === '' ||
            $clinicPhone === '' ||
            $clinicAddress === '' ||
            $bookingUrl === ''
        ) {
            return;
        }

        $config = [
            'clinicName' => $clinicName,
            'clinicPhone' => $clinicPhone,
            'clinicAddress' => $clinicAddress,
            'bookingUrl' => $bookingUrl,
            'welcomeMessage' => $welcomeMessage,
            'apiEndpoint' => Uri::root() . 'index.php?option=com_clinicchatbot&task=chat.send',
        ];

        $configJson = json_encode(
            $config,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_HEX_AMP
        );

        if ($configJson === false) {
            return;
        }

        $assetBase = Uri::root() . 'media/com_clinicchatbot';
        $escapedConfigJson = htmlspecialchars($configJson, ENT_QUOTES, 'UTF-8');
        $escapedClinicName = htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8');
        $escapedWelcomeMessage = htmlspecialchars($welcomeMessage, ENT_QUOTES, 'UTF-8');

        $widgetHtml = '
<link rel="stylesheet" href="' . htmlspecialchars($assetBase . '/css/chatbot.css', ENT_QUOTES, 'UTF-8') . '">
<div
    id="clinic-chatbot"
    class="clinic-chatbot"
    data-chatbot-config=\'' . $escapedConfigJson . '\'
>
    <div id="chatbot-window" class="clinic-chatbot__window" aria-hidden="true">
        <div class="clinic-chatbot__header">
            <div>
                <div class="clinic-chatbot__title">' . $escapedClinicName . '</div>
                <div class="clinic-chatbot__subtitle">Svar på spørgsmål om klinikken og behandlinger</div>
            </div>
            <button id="chatbot-minimize" class="clinic-chatbot__minimize" type="button" aria-label="Minimer chat">−</button>
        </div>

        <div id="chatbot-messages" class="clinic-chatbot__messages">
            <div class="chatbot-message bot">' . $escapedWelcomeMessage . '</div>
        </div>

        <div class="clinic-chatbot__input-row">
            <input
                type="text"
                id="chatbot-input"
                class="clinic-chatbot__input"
                placeholder="Skriv dit spørgsmål..."
                autocomplete="off"
            />
            <button id="chatbot-send" class="clinic-chatbot__send" type="button">Send</button>
        </div>
    </div>

    <button id="chatbot-toggle" class="clinic-chatbot__toggle" type="button" aria-label="Åbn chat">
        <span class="clinic-chatbot__toggle-icon">✦</span>
    </button>
</div>
<script defer src="' . htmlspecialchars($assetBase . '/js/chatbot.js', ENT_QUOTES, 'UTF-8') . '"></script>
';

        $body = preg_replace('/<\/body>/i', $widgetHtml . '</body>', $body, 1);

        if (is_string($body)) {
            $app->setBody($body);
        }
    }
}