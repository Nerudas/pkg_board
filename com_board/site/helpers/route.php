<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\RouteHelper;

class BoardHelperRoute extends RouteHelper
{
	/**
	 * Fetches the list route
	 *
	 * @param   int    $catid     Category ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 *
	 * @throws  \InvalidArgumentException
	 */
	public static function getListRoute($catid = 1)
	{
		return 'index.php?option=com_board&view=list&id=' . $catid;
	}

	/**
	 * Fetches the item route
	 *
	 * @param   int $catid Category ID
	 * @param   int $id    Item ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 *
	 * @throws  \InvalidArgumentException
	 */
	public static function getItemRoute($id = null, $catid = 1)
	{
		$link = 'index.php?option=com_board&view=item';
		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}
		if (!empty($catid))
		{
			$link .= '&catid=' . $catid;
		}

		return $link;
	}

	/**
	 * Fetches the form route
	 *
	 * @param  int $id       Item ID
	 * @param  int $catid    Category ID
	 * @param  int $category Default Category ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 *
	 */
	public static function getFormRoute($id = null, $catid = 1, $category = null)
	{
		$link = 'index.php?option=com_board&view=form&catid=' . $catid;
		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}
		if (!empty($category))
		{
			$link .= '&category=' . $category;
		}

		return $link;
	}

	/**
	 * Fetches the category route
	 *
	 * @param   int $catid Category ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 *
	 * @throws  \InvalidArgumentException
	 */
	public static function getMapRoute($catid = 1)
	{
		return 'index.php?option=com_board&view=map&id=' . $catid;
	}
}