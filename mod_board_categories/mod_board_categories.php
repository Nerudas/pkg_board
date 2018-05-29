<?php
/**
 * @package    Bulletin Board - Categories Module
 * @version    1.0.7
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

// Include Route Helper
JLoader::register('BoardHelperRoute', JPATH_SITE . '/components/com_board/helpers/route.php');

// Include Module Helper
require_once __DIR__ . '/helper.php';

// Variables
$categories = modBoardCategoriesHelper::getCategories($params);
$children   = ($categories) ? $categories->children : array();
$root       = ($categories) ? $categories->root : array();
$categories = ($categories) ? $categories->all : false;

$app       = Factory::getApplication();
$view      = $params->get('view', 'list');
$checkView = ($app->input->get('option') == 'com_board' && $app->input->get('view') == $view);
$all       = ($params->get('show_all') && (!$checkView || $app->input->getInt('id') != 1)) ?
	Text::_('MOD_BOARD_CATEGORIES_ALL') : false;
$allLink   = ($view == 'map') ? Route::_(BoardHelperRoute::getMapRoute(1)) :
	Route::_(BoardHelperRoute::getListRoute(1));

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));