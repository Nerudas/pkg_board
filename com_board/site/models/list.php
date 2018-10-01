<?php
/**
 * @package    Bulletin Board Component
 * @version    1.3.0
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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Layout\LayoutHelper;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');

class BoardModelList extends ListModel
{
	/**
	 * This tag
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $_tag = null;

	/**
	 * Model context string.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	public $_context = 'com_board.list';

	/**
	 * Name of the filter form to load
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $filterFormName = 'filter_list';

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
		$this->setState('tag.id', $pk);

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
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . serialize($this->getState('filter.price'));
		$id .= ':' . serialize($this->getState('filter.payment_method'));
		$id .= ':' . serialize($this->getState('filter.prepayment'));
		$id .= ':' . $this->getState('filter.onlymy');
		$id .= ':' . $this->getState('filter.author_id');
		$id .= ':' . serialize($this->getState('filter.for_when'));
		$id .= ':' . serialize($this->getState('filter.item_id'));
		$id .= ':' . $this->getState('filter.item_id.include');

		return parent::getStoreId($id);
	}


	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  1.1.0
	 */
	protected function getListQuery()
	{
		$user  = Factory::getUser();
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array('i.*', 'r.name AS region_name'))
			->from($db->quoteName('#__board_items', 'i'));

		// Join over the author.
		$offline      = (int) ComponentHelper::getParams('com_profiles')->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select(array(
			'author.id as author_id',
			'author.name as author_name',
			'author.status as author_status',
			'(session.time IS NOT NULL) AS author_online',
			'(company.id IS NOT NULL) AS author_job',
			'company.id as author_job_id',
			'company.name as author_job_name',
			'employees.position as  author_position'
		))
			->join('LEFT', '#__profiles AS author ON author.id = i.created_by')
			->join('LEFT', '#__session AS session ON session.userid = author.id AND session.time > ' . $offline_time)
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = author.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');


		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name as region_name'))
			->join('LEFT', '#__location_regions AS r ON r.id = i.region');

		// Filter by access level.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('i.access IN (' . $groups . ')');
		}

		// Filter by price.
		$price = $this->getState('filter.price');
		if (is_array($price) && !empty($price))
		{
			if (!empty($price['from']) && !empty($price['to']))
			{
				$sql = $db->quoteName('i.price') . '  BETWEEN ' . $price['from'] . ' AND ' . $price['to'];
				if (isset($price['contract']) && $price['contract'] == '-0')
				{
					$sql .= ' OR ' . $db->quoteName('i.price') . ' = ' . $db->quote('-0');
				}
			}
			elseif (!empty($price['from']))
			{
				$sql = $db->quoteName('i.price') . '  >= ' . $price['from'];
				if (isset($price['contract']) && $price['contract'] == '-0')
				{
					$sql .= ' OR ' . $db->quoteName('i.price') . ' = ' . $db->quote('-0');
				}
			}
			elseif (!empty($price['to']))
			{
				//$sql = $db->quoteName('i.price') . '  BETWEEN ' . $price['from'] . ' AND ' . $price['to'];
				$sql = $db->quoteName('i.price') . '  <= ' . $price['to'];
				if ($price['contract'] !== '-0')
				{
					$sql .= ' AND ' . $db->quoteName('i.price') . ' <> ' . $db->quote('-0');
				}
			}

			if (empty($sql) && isset($price['contract']) && $price['contract'] == '-0')
			{
				$sql = $db->quoteName('i.price') . ' = ' . $db->quote('-0');
			}

			if (!empty($sql))
			{
				$query->where('( ' . $sql . ')');
			}
		}

		// Filter by payment_method.
		$payment_method = $this->getState('filter.payment_method');
		if (is_array($payment_method) && !empty($payment_method))
		{
			$payment_method[] = 'all';
			foreach ($payment_method as &$value)
			{
				$value = $db->quote($value);
			}
			$payment_method = implode(',', $payment_method);

			if (!empty($payment_method))
			{
				$query->where($db->quoteName('i.payment_method') . ' IN (' . $payment_method . ')');
			}
		}

		// Filter by prepayment.
		$prepayment = $this->getState('filter.prepayment');
		if (is_array($prepayment) && !empty($prepayment))
		{
			$prepayment[] = 'all';
			foreach ($prepayment as &$value)
			{
				$value = $db->quote($value);
			}
			$prepayment = implode(',', $prepayment);

			if (!empty($prepayment))
			{
				$query->where($db->quoteName('i.prepayment') . ' IN (' . $prepayment . ')');
			}

		}

		// Filter by author
		$authorId = $this->getState('filter.author_id');
		$onlymy   = $this->getState('filter.onlymy');
		if (empty($authorId) && !empty($onlymy) && !$user->guest)
		{
			$authorId = $user->id;
		}
		if (is_numeric($authorId))
		{
			$query->where('i.created_by = ' . (int) $authorId);
		}

		// Filter by for_when.
		$for_when = $this->getState('filter.for_when');
		if (is_array($for_when))
		{
			$today = new Date(date('Y-m-d'));
			foreach ($for_when as &$value)
			{
				$value = $db->quote($value);
			}
			$for_when = implode(',', $for_when);

			if (!empty($for_when))
			{
				$query->where('(' . $db->quoteName('i.created') . ' >= ' . $db->quote($today->toSql()) .
					' AND ' . $db->quoteName('i.for_when') . ' IN (' . $for_when . '))');
			}
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (!empty($published))
		{
			$nullDate = $db->getNullDate();
			$now      = Factory::getDate()->toSql();

			if (is_numeric($published))
			{
				$query->where('( i.state = ' . (int) $published .
					' OR ( i.created_by = ' . $user->id . ' AND i.state IN (0,1)))');
				$query->where('(' . $db->quoteName('i.publish_down') . ' = ' . $db->Quote($nullDate) . ' OR '
					. $db->quoteName('i.publish_down') . '  >= ' . $db->Quote($now)
					. 'OR i.created_by = ' . $user->id . ')');
			}
			elseif (is_array($published))
			{
				$query->where('i.state IN (' . implode(',', $published) . ')');
			}
		}

		// Filter by coordinates.
		$coordinates = $this->getState('filter.coordinates');
		if (!empty($coordinates))
		{
			$query->where('(i.latitude BETWEEN ' . $db->quote($coordinates['south']) . ' AND ' . $db->quote($coordinates['north']) . ')');
			if (isset($coordinates['west']) && isset($coordinates['east']))
			{
				if ($coordinates['west'] > 0 && $coordinates['east'] > 0 && $coordinates['west'] < $coordinates['east'])
				{
					$query->where('(i.longitude BETWEEN ' . $db->quote($coordinates['west']) .
						' AND ' . $db->quote($coordinates['east']) . ')');
				}
				if ($coordinates['west'] > 0 && $coordinates['east'] > 0 && $coordinates['west'] > $coordinates['east'])
				{
					$query->where('(i.longitude BETWEEN ' . $db->quote($coordinates['west']) . ' AND ' . $db->quote(180)
						. ' OR i.longitude BETWEEN ' . $db->quote(-180) . ' AND ' . $db->quote(0)
						. ' OR i.longitude BETWEEN ' . $db->quote(0) . ' AND ' . $db->quote($coordinates['east']) . ')');
				}
				if ($coordinates['west'] > 0 && $coordinates['east'] < 0 && $coordinates['west'] > $coordinates['east'])
				{
					$query->where('((i.longitude BETWEEN ' . $db->quote(-180) . ' AND ' . $db->quote($coordinates['east']) . ')' .
						' OR (i.longitude BETWEEN ' . $db->quote($coordinates['west']) . ' AND ' . $db->quote(180) . '))');
				}
				if ($coordinates['west'] < 0 && $coordinates['east'] < 0 && $coordinates['west'] < $coordinates['east'])
				{
					$query->where('(i.longitude BETWEEN ' . $db->quote($coordinates['west']) . ' AND ' . $db->quote($coordinates['east']) . ')');
				}
				if ($coordinates['west'] < 0 && $coordinates['east'] > 0 && $coordinates['west'] < $coordinates['east'])
				{
					$query->where('((i.longitude BETWEEN ' . $db->quote($coordinates['west']) . ' AND ' . $db->quote(0) . ')' .
						' OR (i.longitude BETWEEN ' . $db->quote(0) . ' AND ' . $db->quote($coordinates['east']) . '))');
				}
			}
		}

		// Filter by tag.
		$tag = (int) $this->getState('tag.id');
		if ($tag > 1)
		{
			$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
				. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('i.id')
				. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_board.item'))
				->where($db->quoteName('tagmap.tag_id') . ' = ' . $tag);
		}


		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			$cols = array('i.title', 'r.name', 'i.text', 'i.tags_search', 'i.extra');
			$sql  = array();
			foreach ($cols as $col)
			{
				$sql[] = $db->quoteName($col) . ' LIKE '
					. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			}
			$query->where('(' . implode(' OR ', $sql) . ')');
		}

		// Group by
		$query->group(array('i.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'i.created');
		$direction = $this->state->get('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  1.1.0
	 */
	public function getItems()
	{
		$items = parent::getItems();
		if (!empty($items))
		{
			$today        = new Date(date('Y-m-d'));
			$user         = Factory::getUser();
			$mainTags     = ComponentHelper::getParams('com_board')->get('tags', array());
			$imagesHelper = new FieldTypesFilesHelper();
			foreach ($items as &$item)
			{
				$item->for_when = ($item->created >= $today->toSql()) ? $item->for_when : '';

				$item->link     = Route::_(BoardHelperRoute::getItemRoute($item->id));
				$item->editLink = false;
				if (!$user->guest)
				{
					$userId = $user->id;
					$asset  = 'com_board.item.' . $item->id;

					$editLink = Route::_(BoardHelperRoute::getFormRoute($item->id));

					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset))
					{
						$item->editLink = $editLink;
					}
					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
					{
						// Check for a valid user and that they are the owner.
						if ($userId == $item->created_by)
						{
							$item->editLink = $editLink;
						}
					}
				}

				// Convert the contacts field from json.
				$item->contacts = new Registry($item->contacts);
				if ($phones = $item->contacts->get('phones'))
				{
					$phones = ArrayHelper::fromObject($phones, false);
					$item->contacts->set('phones', $phones);
				}

				// Convert the map field from json.
				$item->map = (!empty($item->latitude) && !empty($item->longitude) &&
					$item->latitude !== '0.000000' && $item->longitude !== '0.000000') ? new Registry($item->map) : false;

				// Convert the images field to an array.

				$imageFolder  = 'images/board/items/' . $item->id;
				$registry     = new Registry($item->images);
				$item->images = $registry->toArray();
				$item->images = $imagesHelper->getImages('content', $imageFolder, $item->images,
					array('text' => true, 'for_field' => false));
				$item->image  = (!empty($item->images) && !empty(reset($item->images)->src)) ?
					reset($item->images)->src : false;

				// Prepare author data
				$author_avatar         = $imagesHelper->getImage('avatar', 'images/profiles/' . $item->author_id,
					'media/com_profiles/images/no-avatar.jpg', false);
				$item->author_avatar   = Uri::root(true) . '/' . $author_avatar;
				$item->author_link     = Route::_(ProfilesHelperRoute::getProfileRoute($item->author_id));
				$item->author_job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($item->author_job_id));

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_board.item', $item->id);
				if (!empty($item->tags->itemTags))
				{
					foreach ($item->tags->itemTags as &$tag)
					{
						$tag->main = (in_array($tag->id, $mainTags));
					}
					$item->tags->itemTags = ArrayHelper::sortObjects($item->tags->itemTags, 'main', -1);
				}

				// Get region
				$item->region_icon = $imagesHelper->getImage('icon', 'images/location/regions/' . $item->region_id, false, false);

				// Get placemark
				$item->placemark = ($item->map) ? $item->map->get('placemark') : false;
				if ($item->placemark)
				{
					$html = LayoutHelper::render('components.com_board.placemark', $item);
					preg_match('/data-placemark-coordinates="([^"]*)"/', $html, $matches);
					$coordinates = '[]';
					if (!empty($matches[1]))
					{
						$coordinates = $matches[1];
						$html        = str_replace($matches[0], '', $html);
					}

					$iconShape              = new stdClass();
					$iconShape->type        = 'Polygon';
					$iconShape->coordinates = json_decode($coordinates);

					$item->placemark->id                      = $item->id;
					$item->placemark->link                    = $item->link;
					$item->placemark->options                 = array();
					$item->placemark->options['customLayout'] = $html;
					$item->placemark->options['iconShape']    = $iconShape;
					$item->map->set('placemark', $item->placemark);
				}

			}
		}

		return $items;
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string  $query      The query.
	 * @param   integer $limitstart Offset.
	 * @param   integer $limit      The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since  1.1.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadObjectList('id');
	}

	/**
	 * Get the current tag
	 *
	 * @param null $pk
	 *
	 * @return object|false
	 *
	 * @since 1.1.0
	 */
	public function getTag($pk = null)
	{
		if (!is_object($this->_tag))
		{
			$app    = Factory::getApplication();
			$pk     = (!empty($pk)) ? (int) $pk : (int) $this->getState('tag.id', $app->input->get('id', 1));
			$tag_id = $pk;

			$root            = new stdClass();
			$root->title     = Text::_('JGLOBAL_ROOT');
			$root->id        = 1;
			$root->parent_id = 0;
			$root->link      = Route::_(BoardHelperRoute::getListRoute(1));
			$root->metadata  = new Registry();

			if ($tag_id > 1)
			{
				$errorRedirect = Route::_(BoardHelperRoute::getListRoute(1));
				$errorMsg      = Text::_('COM_BOARD_ERROR_TAG_NOT_FOUND');
				try
				{
					$db    = $this->getDbo();
					$query = $db->getQuery(true)
						->select(array('t.id', 't.parent_id', 't.title', 'pt.title as parent_title',
							'mt.metakey', 'mt.metadesc', 'mt.metadata'))
						->from('#__tags AS t')
						->where('t.id = ' . (int) $tag_id)
						->join('LEFT', '#__tags AS pt ON pt.id = t.parent_id')
						->join('LEFT', '#__board_tags AS mt ON mt.id = t.id');

					$user = Factory::getUser();
					if (!$user->authorise('core.admin'))
					{
						$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
					}
					if (!$user->authorise('core.manage', 'com_tags'))
					{
						$query->where('t.published =  1');
					}

					$db->setQuery($query);
					$data = $db->loadObject();

					if (empty($data))
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

						return false;
					}

					$data->link = Route::_(BoardHelperRoute::getListRoute($data->id));

					$imagesHelper = new FieldTypesFilesHelper();
					$imageFolder  = 'images/board/tags/' . $data->id;

					// Convert the metadata field
					$data->metadata = new Registry($data->metadata);
					$data->metadata->set('image', $imagesHelper->getImage('meta', $imageFolder, false, false));

					$this->_tag = $data;
				}
				catch (Exception $e)
				{
					if ($e->getCode() == 404)
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);
					}
					else
					{
						$this->setError($e);
						$this->_tag = false;
					}
				}
			}
			else
			{
				$this->_tag = $root;
			}
		}

		return $this->_tag;
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