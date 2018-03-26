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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class BoardModelItem extends AdminModel
{

	/**
	 * Type data
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_categories = null;

	/**
	 * Imagefolder helper helper
	 *
	 * @var    new imageFolderHelper
	 *
	 * @since  1.0.0
	 */
	protected $imageFolderHelper = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     AdminModel
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		$this->imageFolderHelper = new imageFolderHelper('images/board/categories');

		parent::__construct($config);
	}

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
		$pk  = $app->input->getInt('id', 0);
		if (empty($pk))
		{
			$default_category = $app->input->getInt('category', 1);
			$this->setState('category.default', $default_category);
		}

		parent::populateState();
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry       = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the contacts field to an array.
			$registry       = new Registry($item->contacts);
			$item->contacts = $registry->toArray();

			// Convert the attribs field to an array.
			$registry      = new Registry($item->attribs);
			$item->attribs = $registry->toArray();

			// Convert the map field to an array.
			$registry  = new Registry($item->map);
			$item->map = $registry->toArray();

			// Get Tags
			$item->tags = new TagsHelper;
			$item->tags->getTagIds($item->id, 'com_board.item');

			// Convert the extra field to an array.
			$registry    = new Registry($item->extra);
			$item->extra = $registry->toArray();

			$item->published = $item->state;
		}

		return $item;
	}

	/**
	 * Method to get a single record.
	 *
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getCategories()
	{
		if (!is_array($this->_categories))
		{
			$access = Factory::getUser()->getAuthorisedViewLevels();

			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->select(array('c.id', 'c.title', 'c.icon', 'parent_id', 'level'))
				->from($db->quoteName('#__board_categories', 'c'))
				->where($db->quoteName('c.alias') . ' <> ' . $db->quote('root'))
				->order('c.lft', 'ask')
				->where('c.state =  1')
				->where('c.access IN (' . implode(',', $access) . ')');;
			$db->setQuery($query);
			$categories = $db->loadObjectList('id');

			$item             = $this->getItem();
			$itemTags         = (!empty($item->tags->tags)) ? explode(',', $item->tags->tags) : array();
			$default_category = $this->getState('category.default', 1);


			foreach ($categories as &$category)
			{
				// Get Tags
				$tags = new TagsHelper;
				$tags->getTagIds($category->id, 'com_board.category');
				$category->tags = (!empty($tags->tags)) ? explode(',', $tags->tags) : array();

				// Set active
				$category->active = (!empty($itemTags) && !empty($category->tags));
				if ($category->active)
				{
					foreach ($category->tags as $tag)
					{
						if (!in_array($tag, $itemTags))
						{
							$category->active = false;
						}
						if (!$category->active)
						{
							break;
						}
					}
				}

				if (!$category->active && $default_category == $category->id && empty($item->id))
				{
					$category->active = true;
				}

				$category->active_full = ($category->active &&
					(empty($item->id) && $default_category == $category->id) || count($category->tags) == count($itemTags));
			}

			$this->_categories = $categories;
		}

		return $this->_categories;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 * @since  1.0.0
	 */
	public function getTable($type = 'Items', $prefix = 'BoardTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since  1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app  = Factory::getApplication();
		$form = $this->loadForm('com_board.item', 'item', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		/*
		 * The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		 * The back end uses id so we use that the rest of the time and set it to 0 by default.
		 */
		$id   = ($this->getState('item.id')) ? $this->getState('item.id') : $app->input->get('id', 0);
		$user = Factory::getUser();

		// Check for existing item.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_board.item.' . (int) $id)))
		{
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an item you can edit.
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		// Set update images link
		$form->setFieldAttribute('images', 'saveurl',
			Uri::base(true) . '/index.php?option=com_board&task=item.updateImages&field=images&id=' . $id);

		// Set Palcemark link
		$form->setFieldAttribute('map', 'placemarkurl',
			Uri::base(true) . '/index.php?option=com_board&task=item.getPlacemark&id=' . $id);

		// Set tags
		if (!$form->getFieldAttribute('tags', 'ids'))
		{
			$categories = $this->getCategories();
			$tags       = array();
			$actives    = array();
			foreach ($categories as $category)
			{
				if (!empty($category->tags))
				{
					foreach ($category->tags as $tag)
					{
						if ($category->active)
						{
							$actives[] = $tag;
						}
						$tags[] = $tag;
					}
				}
			}
			$tags = implode(',', array_unique($tags));
			$form->setFieldAttribute('tags', 'ids', $tags);
			$value = $form->getValue('tags');
			if (!empty($actives) && is_object($value) && empty($value->tags) && empty($id) && $this->getState('category.default', 1) > 1)
			{

				$value->tags = implode(',', array_unique($actives));
				$form->setValue('tags', '', $value);

			}
		}

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
		$app  = Factory::getApplication();
		$data = Factory::getApplication()->getUserState('com_board.edit.item.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
			// Pre-select some filters (Status,  Language, Access) in edit form if those have been selected in item Manager: Pages
			if ($this->getState('item.id') == 0)
			{
				$filters = (array) $app->getUserState('com_board.categories.filter');
				$data->set('access',
					$app->input->getInt('access', (!empty($filters['access']) ? $filters['access'] : Factory::getConfig()->get('access')))
				);
			}
		}
		$this->preprocessData('com_board.item', $data);

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
		$app    = Factory::getApplication();
		$pk     = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$filter = InputFilter::getInstance();
		$table  = $this->getTable();
		$db     = Factory::getDbo();

		// Load the row if saving an existing type.
		if ($pk > 0)
		{
			$table->load($pk);
		}

		if (empty($data['created']))
		{
			$data['created'] = Factory::getDate()->toSql();
		}
		if (empty($data['region']))
		{
			$data['region'] = $app->input->cookie->get('region', '*');
		}

//		Remove actual function
//		if (!empty($data['actual']) && empty($data['publish_down']))
//		{
//			$publish_down         = new \Joomla\CMS\Date\Date($data['actual']);
//			$data['publish_down'] = $publish_down->toSql();
//		}

		if (isset($data['metadata']) && isset($data['metadata']['author']))
		{
			$data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
		}

		if (isset($data['contacts']) && is_array($data['contacts']))
		{
			$registry         = new Registry($data['contacts']);
			$data['contacts'] = (string) $registry;
		}

		if (isset($data['map']) && is_array($data['map']))
		{
			if (!empty($data['map']['placemark']) && !empty($data['map']['placemark']['coordinates']))
			{
				$data['latitude']  = $data['map']['placemark']['latitude'];
				$data['longitude'] = $data['map']['placemark']['longitude'];
			}
			$registry    = new Registry($data['map']);
			$data['map'] = (string) $registry;
		}
		if (!isset($data['latitude']) && !isset($data['longitude']))
		{
			$data['latitude']  = '';
			$data['longitude'] = '';
		}

		if (isset($data['attribs']) && is_array($data['attribs']))
		{
			$registry        = new Registry($data['attribs']);
			$data['attribs'] = (string) $registry;
		}

		if (isset($data['metadata']) && is_array($data['metadata']))
		{
			$registry         = new Registry($data['metadata']);
			$data['metadata'] = (string) $registry;
		}

		if (empty($data['created_by']))
		{
			$data['created_by'] = Factory::getUser()->id;
		}

		if (isset($data['attribs']) && is_array($data['attribs']))
		{
			$registry        = new Registry($data['attribs']);
			$data['attribs'] = (string) $registry;
		}

		if (isset($data['extra']) && is_array($data['extra']))
		{
			$registry      = new Registry($data['extra']);
			$data['extra'] = (string) $registry;
		}

		// Get tags search
		if (!empty($data['tags']))
		{
			$query = $db->getQuery(true)
				->select(array('id', 'title'))
				->from('#__tags')
				->where('id IN (' . implode(',', $data['tags']) . ')');
			$db->setQuery($query);
			$tags = $db->loadObjectList();

			$tags_search = array();
			$tags_map    = array();
			foreach ($tags as $tag)
			{
				$tags_search[$tag->id] = $tag->title;
				$tags_map[$tag->id]    = '[' . $tag->id . ']';
			}

			$data['tags_search'] = implode(', ', $tags_search);
			$data['tags_map']    = implode('', $tags_map);
		}
		else
		{
			$data['tags_search'] = '';
			$data['tags_map']    = '';
		}

		if (parent::save($data))
		{
			$id = $this->getState($this->getName() . '.id');

			// Save images
			$data['images']      = (!isset($data['images'])) ? '' : $data['images'];
			$data['imagefolder'] = (!isset($data['imagefolder'])) ? '' : $data['imagefolder'];

			$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__board_items', 'images', $data['images']);

			return true;
		}

		return false;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since  1.0.0
	 */
	public function delete(&$pks)
	{
		if (parent::delete($pks))
		{
			// Delete images
			foreach ($pks as $pk)
			{
				$this->imageFolderHelper->deleteItemImageFolder($pk);
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to duplicate one or more records.
	 *
	 * @param   array &$pks An array of primary key IDs.
	 *
	 * @return  boolean|JException  Boolean true on success, JException instance on error
	 *
	 * @since  1.0.0
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		// Access checks.
		if (!Factory::getUser()->authorise('core.create', 'com_board'))
		{
			throw new Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		foreach ($pks as $pk)
		{

			if ($item = $this->getItem($pk))
			{
				unset($item->id);
				$item->title       = $item->title . ' ' . Text::_('JGLOBAL_COPY');
				$item->published   = $item->state = 0;
				$item->tags        = (!empty($item->tags) && !empty($item->tags->tags)) ? explode(',', $item->tags->tags) :
					array();
				$item->imagefolder = $this->imageFolderHelper->createTemporaryFolder();
				if (!empty($item->images))
				{
					$registry     = new Registry($item->images);
					$item->images = $registry->toArray();
					foreach ($item->images as &$image)
					{
						$old          = JPATH_ROOT . '/' . $image['src'];
						$image['src'] = $item->imagefolder . '/' . $image['file'];
						$new          = JPATH_ROOT . '/' . $image['src'];
						JFile::copy($old, $new);
					}
				}

				$this->save(ArrayHelper::fromObject($item));
			}
		}

		$this->cleanCache();

		return true;
	}

}