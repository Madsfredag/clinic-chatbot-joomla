<?php

namespace Mads\Module\ClinicChatbot\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Factory;

class Dispatcher implements DispatcherInterface
{
    public function dispatch()
    {
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $wa = $document->getWebAssetManager();

        $wa->registerAndUseStyle(
            'mod_clinic_chatbot.styles',
            'modules/mod_clinic_chatbot/media/css/chatbot.css'
        );

        $wa->registerAndUseScript(
            'mod_clinic_chatbot.script',
            'modules/mod_clinic_chatbot/media/js/chatbot.js',
            [],
            ['defer' => true]
        );

        $module = $app->getDocument()->getBuffer('modules');

        $params = $app->getParams();

        // In module dispatcher context, use global $module if available
        global $module;
        $moduleParams = $module ? $module->params : null;

        $registry = is_string($moduleParams) ? json_decode($moduleParams, true) : [];

        $clinicId = htmlspecialchars($registry['clinic_id'] ?? 'default-clinic', ENT_QUOTES, 'UTF-8');
        $clinicName = htmlspecialchars($registry['clinic_name'] ?? 'Clinic Assistant', ENT_QUOTES, 'UTF-8');
        $clinicPhone = htmlspecialchars($registry['clinic_phone'] ?? '', ENT_QUOTES, 'UTF-8');
        $clinicAddress = htmlspecialchars($registry['clinic_address'] ?? '', ENT_QUOTES, 'UTF-8');
        $bookingUrl = htmlspecialchars($registry['booking_url'] ?? '', ENT_QUOTES, 'UTF-8');
        $apiEndpoint = htmlspecialchars($registry['api_endpoint'] ?? '', ENT_QUOTES, 'UTF-8');
        $welcomeMessage = htmlspecialchars(
            $registry['welcome_message'] ?? 'Hello! I can help with clinic information, treatments, and general dental questions.',
            ENT_QUOTES,
            'UTF-8'
        );

        $config = [
            'clinicId' => $clinicId,
            'clinicName' => $clinicName,
            'clinicPhone' => $clinicPhone,
            'clinicAddress' => $clinicAddress,
            'bookingUrl' => $bookingUrl,
            'apiEndpoint' => $apiEndpoint,
            'welcomeMessage' => $welcomeMessage,
        ];

        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo '
<div
    id="clinic-chatbot"
    class="clinic-chatbot"
    data-chatbot-config=\'' . htmlspecialchars($configJson, ENT_QUOTES, 'UTF-8') . '\'
>
    <div id="chatbot-window" class="clinic-chatbot__window" aria-hidden="true">
        <div class="clinic-chatbot__header">
            <div>
                <div class="clinic-chatbot__title">' . $clinicName . '</div>
                <div class="clinic-chatbot__subtitle">How can we help today?</div>
            </div>
            <button id="chatbot-minimize" class="clinic-chatbot__minimize" type="button" aria-label="Minimize chat">−</button>
        </div>

        <div id="chatbot-messages" class="clinic-chatbot__messages">
            <div class="chatbot-message bot">' . $welcomeMessage . '</div>
        </div>

        <div class="clinic-chatbot__input-row">
            <input
                type="text"
                id="chatbot-input"
                class="clinic-chatbot__input"
                placeholder="Write your question..."
                autocomplete="off"
            />
            <button id="chatbot-send" class="clinic-chatbot__send" type="button">Send</button>
        </div>
    </div>

    <button id="chatbot-toggle" class="clinic-chatbot__toggle" type="button" aria-label="Open chat">
        <span class="clinic-chatbot__toggle-icon">💬</span>
    </button>
</div>';
    }
}