<?php
/**
 * @package    Bulletin Board Component
 * @version    1.2.0
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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

class BoardModelItems extends ListModel
{

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
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $created_by);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$tags = $this->getUserStateFromRequest($this->context . '.filter.tags', 'filter_tags', '');
		$this->setState('filter.tags', $tags);

		// List state information.
		$ordering  = empty($ordering) ? 'i.created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
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
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . serialize($this->getState('filter.tags'));

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
		$query = $db->getQuery(true)
			->select('i.*')
			->from($db->quoteName('#__board_items', 'i'));

		// Join over the author.
		$offline      = (int) ComponentHelper::getParams('com_profiles')->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select(array(
			'author.id as author_id',
			'author.name as author_name',
			'author.avatar as author_avatar',
			'author.status as author_status',
			'(session.time IS NOT NULL) AS author_online',
			'(company.id IS NOT NULL) AS author_job',
			'company.id as author_job_id',
			'company.name as author_job_name',
			'company.logo as author_job_logo',
			'employees.position as  author_position'
		))
			->join('LEFT', '#__profiles AS author ON author.id = i.created_by')
			->join('LEFT', '#__session AS session ON session.userid = author.id AND session.time > ' . $offline_time)
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = author.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = i.access');

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name as region_name', 'r.icon as region_icon'))
			->join('LEFT', '#__location_regions AS r ON r.id = i.region');

		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('i.access = ' . (int) $access);
		}

		// Filter by region
		$region = $this->getState('filter.region');
		if (!empty($region))
		{
			$query->where($db->quoteName('i.region') . ' = ' . $db->quoteName($region));
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

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');
		if (!empty($created_by))
		{
			$query->where('i.created_by = ' . (int) $created_by);
		}

		// Filter by tags.
		$tags = $this->getState('filter.tags');
		if (is_array($tags))
		{
			$tags = ArrayHelper::toInteger($tags);
			$tags = implode(',', $tags);
			if (!empty($tags))
			{
				$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
					. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('i.id')
					. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_board.item'))
					->where($db->quoteName('tagmap.tag_id') . ' IN (' . $tags . ')');
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
			else
			{
				$cols = array('i.title', 'r.name', 'i.text', 'i.tags_search', 'ua.name', 'i.extra');
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
		if (!empty($items))
		{
			$today    = new Date(date('Y-m-d'));
			$mainTags = ComponentHelper::getParams('com_board')->get('tags', array());
			foreach ($items as &$item)
			{
				$item->for_when = ($item->created >= $today->toSql()) ? $item->for_when : '';

				$author_avatar       = (!empty($item->author_avatar) && JFile::exists(JPATH_ROOT . '/' . $item->author_avatar)) ?
					$item->author_avatar : 'media/com_profiles/images/no-avatar.jpg';
				$item->author_avatar = Uri::root(true) . '/' . $author_avatar;

				$item->author_job_logo = (!empty($item->author_job_logo) && JFile::exists(JPATH_ROOT . '/' . $item->author_job_logo)) ?
					Uri::root(true) . '/' . $item->author_job_logo : false;

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
				$item->region_icon = (!empty($item->region_icon) && JFile::exists(JPATH_ROOT . '/' . $item->region_icon)) ?
					Uri::root(true) . $item->region_icon : false;
				if ($item->region == '*')
				{
					$item->region_icon = false;
					$item->region_name = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}
			}
		}

		return $items;
	}
}