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

use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;

class BoardModelItem extends ItemModel
{
	/**
	 * Model context string.
	 *
	 * @var        string
	 *
	 * @since  1.0.0
	 */
	protected $_context = 'com_board.item';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getInt('id');
		$this->setState('item.id', $pk);

		$offset = $app->input->getUInt('limitstart');
		$this->setState('list.offset', $offset);

		$user = Factory::getUser();
		// Published state
		$asset = 'com_board';
		if ($pk)
		{
			$asset .= '.item.' . $pk;
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

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

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
	public function getItem($pk = null)
	{
		$app = Factory::getApplication();
		$pk  = (!empty($pk)) ? $pk : (int) $this->getState('item.id');

		if (!isset($this->_item[$pk]))
		{
			$errorRedirect = Route::_(BoardHelperRoute::getListRoute());
			$errorMsg      = Text::_('COM_BOARD_ERROR_ITEM_NOT_FOUND');
			try
			{
				$db   = $this->getDbo();
				$user = Factory::getUser();

				$query = $db->getQuery(true)
					->select('i.*')
					->from('#__board_items AS i')
					->where('i.id = ' . (int) $pk);

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

				// Join over the regions.
				$query->select(array('r.id as region_id', 'r.name as region_name', 'r.icon as region_icon'))
					->join('LEFT', '#__location_regions AS r ON r.id = i.region');

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

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

					return false;
				}

				// Link
				$data->link     = Route::_(BoardHelperRoute::getItemRoute($data->id));
				$data->editLink = false;
				if (!$user->guest)
				{
					$userId = $user->id;
					$asset  = 'com_board.item.' . $data->id;

					$editLink = Route::_(BoardHelperRoute::getFormRoute($data->id));
					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset))
					{
						$data->editLink = $editLink;
					}
					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
					{
						// Check for a valid user and that they are the owner.
						if ($userId == $data->created_by)
						{
							$data->editLink = $editLink;
						}
					}
				}

				// For When Check;
				$today          = new Date(date('Y-m-d'));
				$data->for_when = ($data->created >= $today->toSql()) ? $data->for_when : '';

				// Convert the contacts field from json.
				$data->contacts = new Registry($data->contacts);
				if ($phones = $data->contacts->get('phones'))
				{
					$phones = ArrayHelper::fromObject($phones, false);
					$data->contacts->set('phones', $phones);
				}

				// Convert the map field from json.
				$data->map = (!empty($data->latitude) && !empty($data->longitude) &&
					$data->latitude !== '0.000000' && $data->longitude !== '0.000000') ? new Registry($data->map) : false;

				// Convert the images field to an array.
				$registry     = new Registry($data->images);
				$data->images = $registry->toArray();
				$data->image  = (!empty($data->images) && !empty(reset($data->images)['src'])) ?
					reset($data->images)['src'] : false;

				// Convert the metadata field
				$data->metadata = new Registry($data->metadata);

				// Prepare author data
				$author_avatar         = (!empty($data->author_avatar) && JFile::exists(JPATH_ROOT . '/' . $data->author_avatar)) ?
					$data->author_avatar : 'media/com_profiles/images/no-avatar.jpg';
				$data->author_avatar   = Uri::root(true) . '/' . $author_avatar;
				$data->author_link     = Route::_(ProfilesHelperRoute::getProfileRoute($data->author_id));
				$data->author_job_logo = (!empty($data->author_job_logo) && JFile::exists(JPATH_ROOT . '/' . $data->author_job_logo)) ?
					Uri::root(true) . '/' . $data->author_job_logo : false;
				$data->author_job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($data->author_job_id));

				// Get Tags
				$data->tags = new TagsHelper;
				$data->tags->getItemTags('com_board.item', $data->id);

				// Get region
				$data->region_icon = (!empty($data->region_icon) && JFile::exists(JPATH_ROOT . '/' . $data->region_icon)) ?
					Uri::root(true) . $data->region_icon : false;
				if ($data->region == '*')
				{
					$data->region_icon = false;
					$data->region_name = Text::_('JGLOBAL_FIELD_REGIONS_ALL');
				}

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, Factory::getUser()->getAuthorisedViewLevels()));

				// Get placemark
				$data->placemark = ($data->map) ? $data->map->get('placemark') : false;
				if ($data->placemark)
				{
					$html = LayoutHelper::render('components.com_board.placemark', $data);
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

					$data->placemark->id                      = $data->id;
					$data->placemark->link                    = $data->link;
					$data->placemark->options                 = array();
					$data->placemark->options['customLayout'] = $html;
					$data->placemark->options['iconShape']    = $iconShape;

					$data->map->set('placemark', $data->placemark);
				}

				$this->_item[$pk] = $data;
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
					$this->_item[$pk] = false;
				}
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Increment the hit counter for the article.
	 *
	 * @param   integer $pk Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since  1.0.0
	 */
	public function hit($pk = 0)
	{
		$app      = Factory::getApplication();
		$hitcount = $app->input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');

			$table = Table::getInstance('Items', 'BoardTable');
			$table->load($pk);
			$table->hit($pk);
		}

		return true;
	}
}