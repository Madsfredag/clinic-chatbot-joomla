<?php

namespace Mads\Component\Clinicchatbot\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

final class ConfigModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_clinicchatbot.config',
            'config',
            [
                'control' => 'jform',
                'load_data' => $loadData,
            ]
        );
    }

    protected function loadFormData()
    {
        return [];
    }
}