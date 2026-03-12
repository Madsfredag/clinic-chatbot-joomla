<?php

namespace Mads\Component\Clinicchatbot\Administrator\View\Config;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $paramsData = [];

    public function display($tpl = null): void
    {
        $params = ComponentHelper::getParams('com_clinicchatbot');

        $this->paramsData = [
            'clinic_id' => (string) $params->get('clinic_id', ''),
            'clinic_name' => (string) $params->get('clinic_name', ''),
            'clinic_phone' => (string) $params->get('clinic_phone', ''),
            'clinic_address' => (string) $params->get('clinic_address', ''),
            'booking_url' => (string) $params->get('booking_url', ''),
            'welcome_message' => (string) $params->get('welcome_message', ''),
            'clinic_knowledge' => (string) $params->get('clinic_knowledge', ''),
            'backend_url' => (string) $params->get('backend_url', ''),
            'client_id' => (string) $params->get('client_id', ''),
            'client_secret' => (string) $params->get('client_secret', ''),
            'timeout_seconds' => (string) $params->get('timeout_seconds', '10'),
        ];

        parent::display($tpl);
    }
}