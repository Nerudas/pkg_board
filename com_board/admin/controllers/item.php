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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

class BoardControllerItem extends FormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_BOARD_ITEM';

	/**
	 * Method to update item icon
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function updateImages()
	{
		$app   = Factory::getApplication();
		$id    = $app->input->get('id', 0, 'int');
		$value = $app->input->get('value', '', 'raw');
		$field = $app->input->get('field', '', 'raw');
		if (!empty($id) & !empty($field))
		{
			JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
			$helper = new imageFolderHelper('images/board/items');
			$helper->saveImagesValue($id, '#__board_items', $field, $value);
			echo '<pre>', print_r('aaa', true), '</pre>';
		}

		$app->close();

		return true;
	}

	/**
	 * Method to update item icon
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function getPlacemark()
	{
		$app       = Factory::getApplication();
		$data      = $this->input->post->get('jform', array(), 'array');
		$component = ComponentHelper::getParams('com_board');
		$placemark = $component->get('placemark', '');
		$id        = (!empty($data['id'])) ? $data['id'] : 'x';
		$title     = (!empty($data['title'])) ? $data['title'] : Text::_('JGLOBAL_TITLE');

		$options = array();
		if (!empty($placemark->customLayout))
		{
			$customLayout = htmlspecialchars_decode($placemark->customLayout);
			$customLayout = str_replace('{id}', $id, $customLayout);
			$customLayout = str_replace('{title}', $title, $customLayout);

			$options['customLayout'] = $customLayout;
		}

		if (!empty($placemark->iconShape))
		{
			$iconShape = new stdClass();
			if ((!empty($placemark->iconShape->type)))
			{
				$iconShape->type = $placemark->iconShape->type;
			}
			if ((!empty($placemark->iconShape->coordinates)))
			{
				$iconShape->coordinates = json_decode($placemark->iconShape->coordinates);
			}

			$options['iconShape'] = $iconShape;
		}

		echo new JsonResponse($options);
		$app->close();

		return true;
	}

}