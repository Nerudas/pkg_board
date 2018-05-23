<?php
/**
 * @package    Bulletin Board - Latest Module
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// Include route helper
JLoader::register('BoardHelperRoute', JPATH_SITE . '/components/com_board/helpers/route.php');
JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

// Load Language
$language = Factory::getLanguage();
$language->load('com_board', JPATH_SITE, $language->getTag(), false);

// Initialize model
BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_board/models');
$model = BaseDatabaseModel::getInstance('Items', 'BoardModel', array('ignore_request' => true));
$model->setState('list.limit', $params->get('limit', 5));
$model->setState('filter.category', $params->get('category', 1));
if ((!Factory::getUser()->authorise('core.edit.state', 'com_board.item')) &&
	(!Factory::getUser()->authorise('core.edit', 'com_board.item')))
{
	$model->setState('filter.published', 1);
}
else
{
	$model->setState('filter.published', array(0, 1));
}
$model->setState('filter.for_when', $params->get('for_when', ''));
$model->setState('filter.allregions', $params->get('allregions', ''));
$model->setState('filter.allregions', $params->get('allregions', ''));
$model->setState('filter.onlymy', $params->get('onlymy', ''));
$model->setState('filter.author_id', $params->get('author_id', ''));

// Variables
$items        = $model->getItems();
$categoryLink = BoardHelperRoute::getListRoute($params->get('category', 1));

if (!empty($params->get('author_id', '')))
{
	$categoryLink .= '&filter[author_id]=' . $params->get('author_id');
}
if (!empty($params->get('onlymy', '')))
{
	$categoryLink .= '&filter[onlymy]=' . $params->get('onlymy');
}
$categoryLink = Route::_($categoryLink);

$addLink = Route::_(BoardHelperRoute::getFormRoute());

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));