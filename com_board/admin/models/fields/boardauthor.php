<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

class JFormFieldBoardAuthor extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public $type = 'boardauthor';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('u.id AS value, u.name AS text')
			->from($db->quoteName('#__users', 'u'))
			->join('INNER', '#__board_items AS i ON i.created_by = u.id')
			->group(array('u.id', 'u.name'))
			->order('u.name');
		$db->setQuery($query);

		return array_merge(parent::getOptions(), $db->loadObjectList());

	}
}
