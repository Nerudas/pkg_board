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
use Joomla\CMS\Date\Date;

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
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

		$authorId = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '');
		$this->setState('filter.category', $category);

		// List state information.
		parent::populateState($ordering, $direction);
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
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . serialize($this->getState('filter.access'));
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.author_id');
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . $this->getState('filter.category');

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
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = Factory::getUser();

		$query->select('i.*')
			->from($db->quoteName('#__board_items', 'i'));

		// Join over the users for the author.
		$query->select('ua.name AS author_name')
			->join('LEFT', '#__users AS ua ON ua.id = i.created_by');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = i.access');

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE i.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE i.region END)');


		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('i.access = ' . (int) $access);
		}

		// Filter by regions
		$region = $this->getState('filter.region');
		if (is_numeric($region))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
			$regionModel = JModelLegacy::getInstance('regions', 'NerudasModel');
			$regions     = $regionModel->getRegionsIds($region);
			$regions[]   = $db->quote('*');
			$regions[]   = $regionModel->getRegion($region)->parent;
			$regions     = array_unique($regions);
			$query->where($db->quoteName('i.region') . ' IN (' . implode(',', $regions) . ')');
		}

		// Filter by access level on categories.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('i.access IN (' . $groups . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('i.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(i.state = 0 OR i.state = 1)');
		}

		// Filter by author
		$authorId = $this->getState('filter.author_id');

		if (is_numeric($authorId))
		{
			$query->where('i.created_by = ' . (int) $authorId);
		}

		// Filter by category
		$category = $this->getState('filter.category');
		if ($category > 1 || $category == 'without')
		{
			$categoryTags = $this->getCategoryTags($category);

			$sql = array();
			foreach ($categoryTags as $tags)
			{
				if (!empty($tags))
				{
					$categorySql = array();
					$operator    = ($category != 'without') ? ' LIKE ' : ' NOT LIKE ';
					foreach ($tags as $tag)
					{

						$categorySql[] = $db->quoteName('i.tags_map') . $operator . $db->quote('%[' . $tag . ']%');
					}
				}
				$operator = ($category != 'without') ? ' AND ' : ' OR ';
				$sql[]    = '(' . implode($operator, $categorySql) . ')';
			}

			if (!empty($sql))
			{
				$operator = ($category != 'without') ? ' OR ' : ' AND ';
				$query->where('(' . implode($operator, $sql) . ')');
			}
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('i.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'author:') === 0)
			{
				$search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
				$query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
			}
			else
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
		$items = parent::getItems();
		$today = new Date(date('Y-m-d'));
		if (!empty($items))
		{
			foreach ($items as &$item)
			{
				$item->for_when = ($item->created >= $today->toSql()) ? $item->for_when : '';

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_board.item', $item->id);
			}
		}

		return $items;
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
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('c.id')
					->from($db->quoteName('#__board_categories', 'c'))
					->where($db->quoteName('c.alias') . ' <>' . $db->quote('root'));

				if ($pk != 'without')
				{
					$query->join('INNER', '#__board_categories as this ON c.lft > this.lft AND c.rgt < this.rgt')
						->where('this.id = ' . (int) $pk);
				}
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