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

use Joomla\CMS\Factory;

JLoader::register('BoardModelItem', JPATH_ADMINISTRATOR . '/components/com_board/models/item.php');

class BoardModelForm extends BoardModelItem
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('id', 0);
		$this->setState('item.id', $pk);

		$catid = $app->input->getInt('catid', 1);
		$this->setState('category.id', $catid);

		if (empty($pk))
		{
			$default_category = $app->input->getInt('category', 1);
			$this->setState('category.default', $default_category);
		}

		$return = $app->input->get('return', null, 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

		parent::populateState();
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Joomla\CMS\Form\Form |boolean  A JForm object on success, false on failure
	 *
	 * @since  1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = parent::getForm($data, $loadData);
		$form->setValue('category', '', $this->getState('category.default', 1));

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  1.0.0
	 */
	protected function loadFormData()
	{
		$data = parent::loadFormData();

		if (is_object($data))
		{
			if (empty($data->id) && empty($data->created_by))
			{
				$data->created_by = Factory::getUser()->id;
			}

			if (empty($data->id) && !empty($data->created_by) && empty($data->contacts))
			{
				$data->contacts = parent::getProfileContacts($data->created_by);
			}
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.0.0
	 */
	public function save($data)
	{
		if ($id = parent::save($data))
		{
			Factory::getApplication()->input->set('id', $id);

			return $id;
		}

		return false;
	}

	/**
	 * Get the return URL.
	 *
	 * @return  string    The return URL.
	 *
	 * @since  1.0.0
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

}