<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class BoardModelList extends ListModel
{
	/**
	 * Type data
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_category = null;

	/**
	 * Items Model
	 *
	 * @var     bool|JModelLegacy
	 *
	 * @since  1.0.0
	 */
	protected $_itemsModel = null;

	/**
	 * Category parent data
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_parent = null;

	/**
	 * Model context string.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	public $_context = 'com_board.list';

	/**
	 * Category items array
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_items = null;

	/**
	 * Category items array
	 *
	 * @var    JPagination
	 *
	 * @since  1.0.0
	 */
	protected $_pagination = null;

	/**
	 * Name of the filter form to load
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $filterFormName = 'filter_items';

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since  1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'i.id',
				'title', 'i.title',
				'state', 'i.state',
				'created', 'i.created',
				'created_by', 'i.created_by',
				'for_when', 'i.for_when',
				'publish_down', 'i.publish_down',
				'map', 'i.map',
				'latitude', 'i.latitude',
				'longitude', 'i.longitude',
				'price', 'i.price',
				'payment_method', 'i.payment_method',
				'prepayment', 'i.prepayment',
				'access', 'i.access', 'access_level',
				'region', 'i.region', 'region_name',
				'hits', 'i.hits',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication('site');
		$user = Factory::getUser();

		// Set id state
		$pk = $app->input->getInt('id', 1);
		$this->setState('category.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params     = $app->getParams();
		$menuParams = new Registry;
		$menu       = $app->getMenu()->getActive();
		if ($menu)
		{
			$menuParams->loadString($menu->getParams());
		}
		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);
		$this->setState('params', $mergedParams);

		// Published state
		$asset = 'com_board';
		if ($pk)
		{
			$asset .= '.category.' . $pk;
		}
		if ((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset)))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$price = $this->getUserStateFromRequest($this->context . '.filter.price', 'filter_price', '');
		$this->setState('filter.price', $price);

		$payment_method = $this->getUserStateFromRequest($this->context . '.filter.payment_method', 'filter_payment_method', '');
		$this->setState('filter.payment_method', $payment_method);

		$prepayment = $this->getUserStateFromRequest($this->context . '.filter.prepayment', 'filter_prepayment', '');
		$this->setState('filter.prepayment', $prepayment);

		$allregions = $this->getUserStateFromRequest($this->context . '.filter.allregions', 'filter_allregions', '');
		$this->setState('filter.allregions', $allregions);

		$author_id = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id', '');
		$this->setState('filter.author_id', $author_id);

		$onlymy = $this->getUserStateFromRequest($this->context . '.filter.onlymy', 'filter_onlymy', '');
		$this->setState('filter.onlymy', $onlymy);

		$for_when = $this->getUserStateFromRequest($this->context . '.filter.for_when', 'filter_for_when', '');
		$this->setState('filter.for_when', $for_when);

		// List state information.
		$ordering  = empty($ordering) ? 'i.created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('items_limit', 10, 'uint'));
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string $id A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since  1.0.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('category.id');
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . serialize($this->getState('filter.price'));
		$id .= ':' . serialize($this->getState('filter.payment_method'));
		$id .= ':' . serialize($this->getState('filter.prepayment'));
		$id .= ':' . $this->getState('filter.allregions');
		$id .= ':' . $this->getState('filter.onlymy');
		$id .= ':' . $this->getState('filter.author_id');
		$id .= ':' . serialize($this->getState('filter.for_when'));

		return parent::getStoreId($id);
	}

	/**
	 * Method to get type data for the current type
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since  1.0.0
	 */
	public function getCategory($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

		if (!is_object($this->_category))
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('c.*')
					->from('#__board_categories AS c')
					->where('c.id = ' . (int) $pk);

				// Filter by published state.
				$published = $this->getState('filter.published');
				if (is_numeric($published))
				{
					$query->where('c.state = ' . (int) $published);
				}
				elseif (is_array($published))
				{
					$query->where('c.state IN (' . implode(',', $published) . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					return JError::raiseError(404, Text::_('COM_BOARD_ERROR_CATEGORY_NOT_FOUND'));
				}

				// Root
				$data->root = ($data->id == 1);

				// Links
				$data->listLink = Route::_(BoardHelperRoute::getListRoute($data->id));
				$data->addLink  = Route::_(BoardHelperRoute::getFormRoute());
				$data->mapLink  = Route::_(BoardHelperRoute::getMapRoute($data->id));

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, Factory::getUser()->getAuthorisedViewLevels()));

				// Convert metadata fields to objects.
				$data->metadata = new Registry($data->metadata);

				$this->_category = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					JError::raiseError(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_category = false;
				}
			}
		}

		return $this->_category;
	}

	/**
	 * Get the parent of this category
	 *
	 * @param   integer $pk     The id of the type.
	 * @param  integer  $parent The parent_id of the type.
	 *
	 * @return object
	 *
	 * @since  1.0.0
	 */
	public function &getParent($pk = null, $parent = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

		if (!isset($this->_parent[$pk]))
		{
			$db = Factory::getDbo();
			if (empty($parent))
			{
				$query = $db->getQuery(true)
					->select('parent_id')
					->from('#__board_categories')
					->where('id = ' . (int) $pk);
				$db->setQuery($query);
				$parent = $db->loadResult();
			}
			try
			{
				if ($parent > 1)
				{
					$query = $db->getQuery(true)
						->select(array('id', 'title', 'alias', 'parent_id'))
						->from('#__board_categories')
						->where('id = ' . (int) $parent);

					$db->setQuery($query);
					$item = $db->loadObject();

					if ($item)
					{

						$item->listLink = Route::_(BoardHelperRoute::getListRoute($item->id));
						$item->mapLink  = Route::_(BoardHelperRoute::getListRoute($item->id));

						$this->_parent[$pk] = $item;
					}
					else
					{
						$this->_parent[$pk] = false;
					}
				}
				elseif ($parent == 1)
				{
					$root            = new stdClass();
					$root->id        = 1;
					$root->alias     = 'root';
					$root->title     = Text::_('COM_BOARD_CATEGORY_ROOT');
					$root->parent_id = 0;

					$this->_parent[$pk] = $root;
				}
				else
				{
					$this->_parent[$pk] = false;
				}

			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					JError::raiseError(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_parent[$pk] = false;
				}
			}
		}

		return $this->_parent[$pk];
	}

	/**
	 * Method to get items model
	 *
	 * @param bool $reset Reset model
	 *
	 * @return bool|JModelLegacy
	 *
	 * @since  1.0.0
	 */
	protected function getItemsModel($reset = false)
	{
		if ($this->_itemsModel == null || $reset)
		{
			$model = BaseDatabaseModel::getInstance('Items', 'BoardModel', array('ignore_request' => true));
			$model->setState('params', $this->getState('params'));
			$model->setState('map', false);
			$model->setState('filter.category', $this->getState('category.id', 1));
			if ((!Factory::getUser()->authorise('core.edit.state', 'com_board.item')) &&
				(!Factory::getUser()->authorise('core.edit', 'com_board.item')))
			{
				$model->setState('filter.published', 1);
			}
			else
			{
				$model->setState('filter.published', array(0, 1));
			}
			$model->setState('filter.search', $this->getState('filter.search'));
			$model->setState('filter.price', $this->getState('filter.price'));
			$model->setState('filter.payment_method', $this->getState('filter.payment_method'));
			$model->setState('filter.prepayment', $this->getState('filter.prepayment'));
			$model->setState('filter.allregions', $this->getState('filter.allregions'));
			$model->setState('filter.onlymy', $this->getState('filter.onlymy'));
			$model->setState('filter.author_id', $this->getState('filter.author_id'));
			$model->setState('filter.for_when', $this->getState('filter.for_when'));

			// Set limit & limitstart for query.
			$model->setState('list.limit', $this->getState('list.limit'));
			$model->setState('list.start', $this->getState('list.start'));

			$this->_itemsModel = $model;
		}

		return $this->_itemsModel;
	}

	/**
	 * Get the articles in the category
	 *
	 * @return  mixed  An array of articles or false if an error occurs.
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		if ($this->_items === null)
		{
			$model        = $this->getItemsModel();
			$this->_items = $model->getItems();

			if ($this->_items === false)
			{
				$this->setError($model->getError());
			}

			$this->_pagination = $model->getPagination();
		}

		return $this->_items;
	}

	/**
	 * Method to get a \JPagination object for the data set.
	 *
	 * @return  \JPagination  A \JPagination object for the data set.
	 *
	 * @since  1.0.0
	 */
	public function getPagination()
	{
		if ($this->_pagination === null)
		{
			$model             = $this->getItemsModel();
			$this->_pagination = $model->getPagination();

			if ($this->_pagination === false)
			{
				$this->setError($model->getError());
			}
		}

		return $this->_pagination;
	}

	/**
	 * Gets the value of a user state variable and sets it in the session
	 *
	 * This is the same as the method in \JApplication except that this also can optionally
	 * force you back to the first page when a filter has changed
	 *
	 * @param   string  $key       The key of the user state variable.
	 * @param   string  $request   The name of the variable passed in a request.
	 * @param   string  $default   The default value for the variable if not found. Optional.
	 * @param   string  $type      Filter for the variable, for valid values see {@link \JFilterInput::clean()}. Optional.
	 * @param   boolean $resetPage If true, the limitstart in request is set to zero
	 *
	 * @return  mixed  The request user state.
	 *
	 * @since  1.0.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
	{
		$app       = Factory::getApplication();
		$set_state = $app->input->get($request, null, $type);
		$new_state = parent::getUserStateFromRequest($key, $request, $default, $type, $resetPage);
		if ($new_state == $set_state)
		{
			return $new_state;
		}
		$app->setUserState($key, $set_state);

		return $set_state;
	}
}