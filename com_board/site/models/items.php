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

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Date\Date;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

class BoardModelItems extends ListModel
{
	/**
	 * Category tags
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $_categoryTags = null;

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
				'tags_search', 'i.tags_search',
				'extra', 'i.extra',
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
	protected function populateState($ordering = 'i.created', $direction = 'desc')
	{
		$app  = Factory::getApplication('site');
		$user = Factory::getUser();

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

		$map = $this->getUserStateFromRequest($this->context . '.map', 'map', false);
		$this->setState('map', $map);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '');
		$this->setState('filter.category', $category);

		if ((!$user->authorise('core.edit.state', 'com_board.item'))
			&& (!$user->authorise('core.edit', 'com_board.item')))
		{
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		$price = $this->getUserStateFromRequest($this->context . '.filter.price', 'filter_price', '');
		$this->setState('filter.price', $price);

		$payment_method = $this->getUserStateFromRequest($this->context . '.filter.payment_method', 'filter_payment_method', '');
		$this->setState('filter.payment_method', $payment_method);

		$prepayment = $this->getUserStateFromRequest($this->context . '.filter.prepayment', 'filter_prepayment', '');
		$this->setState('filter.prepayment', $prepayment);

		$allregions = $this->getUserStateFromRequest($this->context . '.filter.allregions', 'filter_allregions', '');
		$this->setState('filter.allregions', $allregions);

		$onlymy    = $this->getUserStateFromRequest($this->context . '.filter.onlymy', 'filter_onlymy', '');
		$author_id = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id', '');
		if (!empty($author_id) && $author_id == $user->id)
		{
			$onlymy = 1;
		}
		$this->setState('filter.onlymy', $onlymy);
		$this->setState('filter.author_id', $author_id);

		$for_when = $this->getUserStateFromRequest($this->context . '.filter.for_when', 'filter_for_when', '');
		$this->setState('filter.for_when', $for_when);

		$coordinates = $this->getUserStateFromRequest($this->context . '.filter.coordinates', 'filter_coordinates', '');
		$this->setState('filter.coordinates', $coordinates);

		// List state information.
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('items_limit', 10, 'uint'));
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		// Set ordering & direction for query.
		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);
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
		$id .= ':' . $this->getState('map');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.category');
		$id .= ':' . serialize($this->getState('filter.price'));
		$id .= ':' . serialize($this->getState('filter.payment_method'));
		$id .= ':' . serialize($this->getState('filter.prepayment'));
		$id .= ':' . $this->getState('filter.allregions');
		$id .= ':' . $this->getState('filter.onlymy');
		$id .= ':' . $this->getState('filter.author_id');
		$id .= ':' . serialize($this->getState('filter.for_when'));
		$id .= ':' . serialize($this->getState('filter.coordinates'));

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  1.0.0
	 */
	protected function getListQuery()
	{
		$user    = Factory::getUser();
		$db      = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array('i.*', 'r.name AS region_name'))
			->from($db->quoteName('#__board_items', 'i'));

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE i.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE i.region END)');

		// Filter by access level on categories.
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

		// Filter by regions
//		$region     = $app->input->cookie->get('region', '*');
//		$allregions = $this->getState('filter.allregions');
//		if (is_numeric($region) && empty($allregions) && !$map)
//		{
//			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
//			$regionModel = JModelLegacy::getInstance('regions', 'NerudasModel');
//			$regions     = $regionModel->getRegionsIds($region);
//			$regions[]   = $db->quote('*');
//			$regions[]   = $regionModel->getRegion($region)->parent;
//			$regions     = array_unique($regions);
//			$query->where('(' . $db->quoteName('i.region') . ' IN (' . implode(',', $regions) . ')'
//				. 'OR i.created_by = ' . $user->id . ')');
//		}

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

		// Filter by category
		$category = $this->getState('filter.category');
		if ($category > 1)
		{
			$categoryTags = $this->getCategoryTags($category);

			$sql = array();
			foreach ($categoryTags as $category => $tags)
			{
				if (!empty($tags))
				{
					$categorySql = array();

					foreach ($tags as $tag)
					{
						$categorySql[] = $db->quoteName('i.tags_map') . ' LIKE ' . $db->quote('%[' . $tag . ']%');
					}
				}
				$sql[] = '(' . implode(' AND ', $categorySql) . ')';
			}

			if (!empty($sql))
			{
				$query->where('(' . implode(' OR ', $sql) . ')');
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
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items     = parent::getItems();
		$today     = new Date(date('Y-m-d'));
		$user      = Factory::getUser();
		$component = ComponentHelper::getParams('com_board');
		$placemark = $component->get('placemark', '');

		if (!empty($items))
		{
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

				// Get placemark
				$item->placemark = ($item->map) ? $item->map->get('placemark') : false;
				if ($item->placemark)
				{
					$item->placemark->id      = $item->id;
					$item->placemark->link    = $item->link;
					$item->placemark->options = array();
					if (!empty($placemark))
					{
						if (!empty($placemark->customLayout))
						{
							$customLayout = htmlspecialchars_decode($placemark->customLayout);
							$customLayout = str_replace('{id}', $item->id, $customLayout);
							$customLayout = str_replace('{title}', $item->title, $customLayout);

							$item->placemark->options['customLayout'] = $customLayout;
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

							$item->placemark->options['iconShape'] = $iconShape;
						}
					}
					$item->map->set('placemark', $item->placemark);
				}

				// Convert the images field to an array.
				$registry     = new Registry($item->images);
				$item->images = $registry->toArray();
				$item->image  = (!empty($item->images) && !empty(reset($item->images)['src'])) ?
					reset($item->images)['src'] : false;

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_board.item', $item->id);

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
	 * @since  1.0.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadObjectList('id');
	}


	/**
	 * Method to get an array of categorytags.
	 *
	 * @param int $pk category id
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getCategoryTags($pk = null)
	{
		if (!is_array($this->_categoryTags))
		{
			$pk = (!empty($pk)) ? $pk : $this->getState('filter.category');

			$tags = array();
			if (!empty($pk))
			{
				$db = Factory::getDbo();

				$query = $db->getQuery(true)
					->select('c.id')
					->from($db->quoteName('#__board_categories', 'c'))
					->join('INNER', '#__board_categories as this ON c.lft > this.lft AND c.rgt < this.rgt')
					->where('this.id = ' . (int) $pk);
				$db->setQuery($query);

				$categories   = $db->loadColumn();
				$categories[] = $pk;
				$categories   = array_unique($categories);

				foreach ($categories as $category)
				{
					$tagsHelper = new TagsHelper;
					$tagsHelper->getTagIds($category, 'com_board.category');
					$tags[$category] = (!empty($tagsHelper->tags)) ? array_unique(explode(',', $tagsHelper->tags)) : array();
				}

			}

			$this->_categoryTags = $tags;
		}

		return $this->_categoryTags;
	}

}