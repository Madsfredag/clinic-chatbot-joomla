<?php

namespace Mads\Component\Clinicchatbot\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Table\Extension;
use Joomla\Registry\Registry;

final class ConfigController extends BaseController
{
    public function save(): void
    {
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

    private function getDatabase()
    {
        return Factory::getContainer()->get('DatabaseDriver');
    }
}