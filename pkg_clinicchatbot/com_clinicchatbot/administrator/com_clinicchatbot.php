<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$app = Factory::getApplication();
$app->bootComponent('com_clinicchatbot')->getDispatcher($app)->dispatch();