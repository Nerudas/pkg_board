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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

class BoardControllerItem extends FormController
{

	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $view_list = 'list';

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

		$html = LayoutHelper::render('components.com_board.placemark', $data);
		preg_match('/data-placemark-coordinates="([^"]*)"/', $html, $matches);
		$coordinates = '[]';
		if (!empty($matches[1]))
		{
			$coordinates = $matches[1];
			$html        = str_replace($matches[0], '', $html);
		}

		$options                 = array();
		$options['customLayout'] = $html;
		$iconShape               = new stdClass();
		$iconShape->type         = 'Polygon';
		$iconShape->coordinates  = json_decode($coordinates);
		$options['iconShape']    = $iconShape;

		echo new JsonResponse($options);
		$app->close();

		return true;
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$result   = parent::save($key, $urlVar);
		$app      = Factory::getApplication();
		$data     = $this->input->post->get('jform', array(), 'array');
		$id       = $app->input->getInt('id');
		$catid    = $app->input->getInt('catid');
		$category = $app->input->getInt('category', $data['category']);

		if ($result)
		{
			$this->setMessage(Text::_($this->text_prefix . (($data['id'] == 0) ? '_SUBMIT' : '') . '_SAVE_SUCCESS'));
		}

		$return = ($result) ? BoardHelperRoute::getItemRoute($id) :
			BoardHelperRoute::getFormRoute($id, $catid, $category);
		$this->setRedirect(Route::_($return));

		return $result;
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data An array of input data.
	 * @param   string $key  The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		$user     = Factory::getUser();
		$selector = (!empty($data[$key])) ? $data[$key] : 0;
		$author   = (!empty($data['created_by'])) ? $data['created_by'] : 0;
		$canEdit  = $user->authorise('core.edit', 'com_board.item.' . $selector) ||
			($user->authorise('core.edit.own', 'com_board.item.' . $selector)
				&& $author == $user->id);

		return $canEdit;
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string $key The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since  1.0.0
	 */

	public function cancel($key = null)
	{
		parent::cancel($key);

		$app   = Factory::getApplication();
		$id    = $app->input->getInt('id');
		$catid = $app->input->getInt('catid');

		$return = (!empty($id)) ? BoardHelperRoute::getItemRoute($id) :
			BoardHelperRoute::getListRoute($catid);

		$this->setRedirect(Route::_($return));

		return $result;
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'Form', $prefix = 'BoardModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

}