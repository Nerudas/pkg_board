<?php
/**
 * @package    Sitemap - Bulletin Board Plugin
 * @version    1.3.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class plgSitemapBoard extends CMSPlugin
{

	/**
	 * Urls array
	 *
	 * @var    array
	 *
	 * @since  1.2.1
	 */
	protected $_urls = null;

	/**
	 * Method to get Links array
	 *
	 * @return array
	 *
	 * @since 1.2.1
	 */
	public function getUrls()
	{
		if ($this->_urls === null)
		{
			// Include route helper
			JLoader::register('BoardHelperRoute', JPATH_SITE . '/components/com_board/helpers/route.php');

			$db   = Factory::getDbo();
			$user = Factory::getUser(0);

			// Last item
			// Get items
			$query = $db->getQuery(true)
				->select('b.created')
				->from($db->quoteName('#__board_items', 'b'))
				->where('b.state = 1')
				->where('b.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
				->order('b.created DESC');
			$db->setQuery($query);
			$modified = $db->loadResult();


			// Get Tags
			$navtags    = ComponentHelper::getParams('com_board')->get('tags', array());
			$changefreq = $this->params->def('changefreq', 'weekly');
			$priority   = $this->params->def('priority', '0.5');

			$tags              = array();
			$tags[1]           = new stdClass();
			$tags[1]->id       = 1;
			$tags[1]->modified = $modified;

			if (!empty($navtags))
			{
				$query = $db->getQuery(true)
					->select(array('tm.tag_id as id', 'max(tm.tag_date) as modified'))
					->from($db->quoteName('#__contentitem_tag_map', 'tm'))
					->join('LEFT', '#__tags AS t ON t.id = tm.tag_id')
					->where($db->quoteName('tm.type_alias') . ' = ' . $db->quote('com_companies.company'))
					->where('tm.tag_id IN (' . implode(',', $navtags) . ')')
					->where('t.published = 1')
					->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
					->group('t.id');
				$db->setQuery($query);

				$tags = $tags + $db->loadObjectList('id');
			}

			$tags_urls = array();
			foreach ($tags as $tag)
			{
				$url             = new stdClass();
				$url->loc        = BoardHelperRoute::getListRoute($tag->id);
				$url->changefreq = $changefreq;
				$url->priority   = $priority;
				$url->lastmod    = $tag->modified;

				$tags_urls[] = $url;
			}

			$this->_urls = $tags_urls;
		}

		return $this->_urls;
	}
}
