<?php
/**
 * @package    Bulletin Board Component
 * @version    1.1.0
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
use Joomla\CMS\Layout\LayoutHelper;

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
		}

		$app->close();

		return true;
	}

	/**
	 * Method to set contacts from profile
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function setContacts()
	{
		$data    = $this->input->post->get('jform', array(), 'array');
		$context = "$this->option.edit.$this->context";
		$id      = (isset($data['id'])) ? $data['id'] : '';

		$data['created_by'] = (!empty($data['created_by'])) ? $data['created_by'] : Factory::getUser()->id;
		$data['contacts']   = $this->getModel()->getProfileContacts($data['created_by']);

		// Save the data in the session.
		Factory::getApplication()->setUserState($context . '.data', $data);

		// Redirect back to the edit screen.
		$this->setRedirect('index.php?option=com_board&view=item&layout=edit&id=' . $id);

		return true;
	}

	/**
	 * Method to get Item placemark
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function getPlacemark()
	{
		$app  = Factory::getApplication();
		$data = $this->input->post->get('jform', array(), 'array');

		$options                 = array();
		$options['customLayout'] = LayoutHelper::render('components.com_board.map.placemark', $data);

		$iconShape              = new stdClass();
		$iconShape->type        = 'Polygon';
		$iconShape->coordinates = json_decode(ComponentHelper::getParams('com_board')
			->get('placemark_coordinates',
				'[[[-24, -48],[300, -48],[24, -8],[24, -8],[0, 0],[-24, -10],[-24, -10]]]'));
		$options['iconShape']   = $iconShape;

		echo new JsonResponse($options);
		$app->close();

		return true;
	}

}