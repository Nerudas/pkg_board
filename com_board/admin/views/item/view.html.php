<?php
/**
 * @package    Bulletin Board Component
 * @version    1.2.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\SiteApplication;

class BoardViewItem extends HtmlView
{
	/**
	 * The JForm object
	 *
	 * @var  JForm
	 *
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 *
	 * @since  1.0.0
	 */
	protected $item;

	/**
	 * The categories array
	 *
	 * @var  array
	 *
	 * @since  1.0.0
	 */
	protected $categories;

	/**
	 * The model state
	 *
	 * @var  object
	 *
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * The actions the user is authorised to perform
	 *
	 * @var  JObject
	 *
	 * @since  1.0.0
	 */
	protected $canDo;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$this->form       = $this->get('Form');
		$this->item       = $this->get('Item');
		$this->state      = $this->get('State');
		$this->categories = $this->get('Categories');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Returns the categories array
	 *
	 * @return  mixed  array
	 *
	 * @since  1.0.0
	 */
	public function getCategories()
	{
		if (!is_array($this->categories))
		{
			$this->categories = $this->get('Categories');
		}

		return $this->categories;
	}

	/**
	 * Add the type title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
		$isNew      = ($this->item->id == 0);
		$this->user = Factory::getUser();
		$canDo      = BoardHelper::getActions('com_board', 'item', $this->item->id);

		if ($isNew)
		{
			// Add title
			JToolBarHelper::title(
				TEXT::_('COM_BOARD') . ': ' . TEXT::_('COM_BOARD_ITEM_ADD'), 'bookmark'
			);
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::apply('item.apply');
				JToolbarHelper::save('item.save');
				JToolbarHelper::save2new('item.save2new');
			}
		}
		// Edit
		else
		{
			// Add title
			JToolBarHelper::title(
				TEXT::_('COM_BOARD') . ': ' . TEXT::_('COM_BOARD_ITEM_EDIT'), 'bookmark'
			);
			// Can't save the record if it's and editable
			if ($canDo->get('core.edit'))
			{
				JToolbarHelper::apply('item.apply');
				JToolbarHelper::save('item.save');
				JToolbarHelper::save2new('item.save2new');
			}

			// Go to page
			JLoader::register('BoardHelperRoute', JPATH_SITE . '/components/com_board/helpers/route.php');
			$siteRouter = SiteApplication::getRouter();

			$itemLink = $siteRouter->build(BoardHelperRoute::getItemRoute($this->item->id))->toString();
			$itemLink = str_replace('administrator/', '', $itemLink);

			$toolbar = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('Custom', '<a href="' . $itemLink . '" class="btn btn-small btn-primary"
					target="_blank">' . Text::_('COM_BOARD_GO_TO_ITEM') . '</a>', 'goTo');
		}

		JToolbarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
		JToolbarHelper::divider();

		JToolbarHelper::custom('item.setContacts', 'upload', '',
			'COM_BOARD_ITEM_SET_CONTACTS', false);
	}
}