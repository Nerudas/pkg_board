<?php
/**
 * @package    Bulletin Board Component
 * @version    1.3.1
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
	 * @param  int $tag_id Tag ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0n
	 */
	public static function getListRoute($tag_id = 1)
	{
		return 'index.php?option=com_board&view=list&id=' . $tag_id;
	}

	/**
	 * Fetches the item route
	 *
	 * @param   int $id Item ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public static function getItemRoute($id = null)
	{
		$link = 'index.php?option=com_board&view=item&tag_id=1&id=' . $id;


		return $link;
	}

	/**
	 * Fetches the form route
	 *
	 * @param  int $id     Item ID
	 * @param int  $tag_id Tag ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public static function getFormRoute($id = null, $tag_id = 1)
	{
		$link = 'index.php?option=com_board&view=form&tag_id=' .$tag_id;
		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}

		return $link;
	}
}