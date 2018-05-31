<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.8
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class BoardController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $default_view = 'items';
}