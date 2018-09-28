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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class BoardModelItem extends AdminModel
{
	/**
	 * Profile contacts
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_contacts = null;

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
		$this->imageFolderHelper = new imageFolderHelper('images/board/items');

		parent::__construct($config);
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

		// Set Tags parents
		$config = ComponentHelper::getParams('com_board');
		if ($config->get('item_tags'))
		{
			$form->setFieldAttribute('tags', 'parents', implode(',', $config->get('item_tags')));
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
		$data = Factory::getApplication()->getUserState('com_board.edit.item.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_board.item', $data);

		return $data;
	}

	/**
	 * Method to get profile contacts
	 *
	 * @param int $pk Profile ID
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getProfileContacts($pk = null)
	{
		if (!is_array($this->_contacts))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('contacts')
				->from('#__profiles')
				->where('id = ' . $pk);
			$db->setQuery($query);

			$contacts        = new Registry($db->loadResult());
			$this->_contacts = $contacts->toArray();
		}

		return $this->_contacts;

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
		$isNew  = true;

		// Load the row if saving an existing type.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		if (empty($data['created']))
		{
			$data['created'] = Factory::getDate()->toSql();
		}


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

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_location/models', 'LocationModel');
		$regionsModel = BaseDatabaseModel::getInstance('Regions', 'LocationModel', array('ignore_request' => false));
		if (empty($data['region']))
		{
			$data['region'] = ($app->isSite()) ? $regionsModel->getVisitorRegion()->id : $regionsModel->getProfileRegion($data['created_by'])->id;
		}
		$region = $regionsModel->getRegion($data['region']);

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
		$data['tags'] = (is_array($data['tags'])) ? $data['tags'] : array();
		if ($region && !empty($region->items_tags))
		{
			$data['tags'] = array_unique(array_merge($data['tags'], explode(',', $region->items_tags)));
		}
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
			$data['imagefolder'] = (!empty($data['imagefolder'])) ? $data['imagefolder'] :
				$this->imageFolderHelper->getItemImageFolder($id);

			if ($isNew)
			{
				$data['images'] = (isset($data['images'])) ? $data['images'] : array();
			}

			if (isset($data['images']))
			{
				$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__board_items', 'images', $data['images']);
			}

			// Import contacts
			if (!empty($data['contacts']) && $data['contacts'] != '{}')
			{
				$query = $db->getQuery(true)
					->select('contacts')
					->from('#__profiles')
					->where('id = ' . $data['created_by']);
				$db->setQuery($query);
				$authorContacts = $db->loadResult();
				if (empty($authorContacts) || $authorContacts == '{}')
				{
					$update           = new stdClass();
					$update->id       = $data['created_by'];
					$update->contacts = $data['contacts'];
					$db->updateObject('#__profiles', $update, 'id');
				}
			}

			return $id;
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