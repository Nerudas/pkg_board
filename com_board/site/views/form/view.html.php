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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class BoardViewForm extends HtmlView
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
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Get model data.
		$this->form       = $this->get('Form');
		$this->item       = $this->get('Item');
		$this->state      = $this->get('State');
		$this->categories = $this->get('Categories');

		$this->link        = BoardHelperRoute::getFormRoute($this->state->get('item.id'), $this->state->get('category.id'),
			$this->state->get('category.default'));
		$this->return_page = $this->get('ReturnPage');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode('\n', $errors), 500);
		}

		// Set Layout
		$params = $this->state->get('params');
		$active = $app->getMenu()->getActive();

		$layout = ($active && !empty($active->query['layout']) &&
			strpos($active->link, 'view=form') &&
			strpos($active->link, '&catid=' . (string) $this->state->get('category.id')) &&
			strpos($active->link, '&id=' . (string) $this->state->get('item.id'))
		) ? $active->query['layout'] : $params->get('form_layout', 'default');


		// Check actions
		$authorised = (empty($this->item->id)) ? $user->authorise('core.create', 'com_board') :
			$user->authorise('core.edit', 'com_board.item.' . $this->item->id) ||
			($user->authorise('core.edit.own', 'com_board.item.' . $this->item->id)
				&& $this->item->created_by == $user->id);

		if (!$authorised && $user->guest)
		{
			$login = Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()));
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login, 403);
		}
		elseif (!$authorised)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return false;
		}

		$this->setLayout($layout);

		$this->_prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function _prepareDocument()
	{
		$app        = Factory::getApplication();
		$link       = $this->link;
		$breadcrumb = ($this->item->id) ? Text::_('COM_BOARD_EDIT') : Text::_('COM_BOARD_ADD');
		$pathway    = $app->getPathway();
		$pathway->addItem($breadcrumb, $link);

		// Set pathway title
		$title = array();
		foreach ($pathway->getPathWay() as $value)
		{
			$title[] = $value->name;
		}
		$title = implode(' / ', $title);

		$this->document->setTitle($title);
		$this->document->setMetadata('robots', 'noindex');
	}
}