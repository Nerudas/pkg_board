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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class com_BoardInstallerScript
{
	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @return bool
	 *
	 * @since  1.0.0
	 */
	function postflight()
	{
		$path = '/components/com_board';
		$this->fixTables($path);
		$this->tagsIntegration();
		$this->moveLayouts($path);
		$this->createImageFolders();
		$this->createRootCategory();

		return true;
	}

	/**
	 * This method is called after a component is updated.
	 *
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function update($parent)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('id', 'icon'))
			->from($db->quoteName('#__board_categories'))
			->where($db->quoteName('alias') . ' <> ' . $db->quote('root'));
		$db->setQuery($query);
		$categories = $db->loadObjectList('id');

		foreach ($categories as $category)
		{
			$folder = JPATH_ROOT . '/images/board/categories/' . $category->id;
			if (!JFolder::exists($folder))
			{
				JFolder::create($folder);
				JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
			}
			if (!empty($category->icon))
			{
				$old  = JPATH_ROOT . '/' . $category->icon;
				$icon = 'images/board/categories/' . $category->id . '/icon.' . JFile::getExt($category->icon);
				$new  = JPATH_ROOT . '/' . $icon;

				JFile::move($old, $new);
				$category->icon = $icon;
				$db->updateObject('#__board_categories', $category, 'id');
			}
		}
	}

	/**
	 * Create root category
	 *
	 * @since  1.0.0
	 */
	protected function createRootCategory()
	{
		$db = Factory::getDbo();

		// Category
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__board_categories'))
			->where($db->quoteName('id') . ' = ' . $db->quote(1));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$root            = new stdClass();
		$root->id        = 1;
		$root->parent_id = 0;
		$root->lft       = 0;
		$root->rgt       = 1;
		$root->level     = 0;
		$root->path      = '';
		$root->alias     = 'root';
		$root->access    = 1;
		$root->state     = 1;

		(!empty($current_id)) ? $db->updateObject('#__board_categories', $root, 'id')
			: $db->insertObject('#__board_categories', $root);
	}

	/**
	 * Create or image folders
	 *
	 * @since  1.0.0
	 */
	protected function createImageFolders()
	{
		$folders = array(
			'images/board',
			'images/board/items',
			'images/board/categories',
		);


		foreach ($folders as $path)
		{
			$folder = JPATH_ROOT . '/' . $path;
			if (!JFolder::exists($folder))
			{
				JFolder::create($folder);
				JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
			}
		}
	}

	/**
	 * Create or update tags integration
	 *
	 * @since  1.0.0
	 */
	protected function tagsIntegration()
	{
		$db = Factory::getDbo();

		// Category
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.category'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$category                                               = new stdClass();
		$category->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$category->type_title                                   = 'Board Category';
		$category->type_alias                                   = 'com_board.category';
		$category->table                                        = new stdClass();
		$category->table->special                               = new stdClass();
		$category->table->special->dbtable                      = '#__board_categories';
		$category->table->special->key                          = 'id';
		$category->table->special->type                         = 'Categories';
		$category->table->special->prefix                       = 'BoardTable';
		$category->table->special->config                       = 'array()';
		$category->table->common                                = new stdClass();
		$category->table->common->dbtable                       = '#__ucm_content';
		$category->table->common->key                           = 'ucm_id';
		$category->table->common->type                          = 'Corecontent';
		$category->table->common->prefix                        = 'JTable';
		$category->table->common->config                        = 'array()';
		$category->table                                        = json_encode($category->table);
		$category->rules                                        = '';
		$category->field_mappings                               = new stdClass();
		$category->field_mappings->common                       = new stdClass();
		$category->field_mappings->common->core_content_item_id = 'id';
		$category->field_mappings->common->core_title           = 'title';
		$category->field_mappings->common->core_state           = 'state';
		$category->field_mappings->common->core_alias           = 'alias';
		$category->field_mappings->common->core_created_time    = 'null';
		$category->field_mappings->common->core_modified_time   = 'null';
		$category->field_mappings->common->core_body            = 'null';
		$category->field_mappings->common->core_hits            = 'null';
		$category->field_mappings->common->core_publish_up      = 'null';
		$category->field_mappings->common->core_publish_down    = 'null';
		$category->field_mappings->common->core_access          = 'access';
		$category->field_mappings->common->core_params          = 'attribs';
		$category->field_mappings->common->core_featured        = 'null';
		$category->field_mappings->common->core_metadata        = 'metadata';
		$category->field_mappings->common->core_language        = 'null';
		$category->field_mappings->common->core_images          = 'null';
		$category->field_mappings->common->core_urls            = 'null';
		$category->field_mappings->common->core_version         = 'null';
		$category->field_mappings->common->core_ordering        = 'lft';
		$category->field_mappings->common->core_metakey         = 'metakey';
		$category->field_mappings->common->core_metadesc        = 'metadesc';
		$category->field_mappings->common->core_catid           = 'null';
		$category->field_mappings->common->core_xreference      = 'null';
		$category->field_mappings->common->asset_id             = 'null';
		$category->field_mappings->special                      = new stdClass();
		$category->field_mappings                               = json_encode($category->field_mappings);
		$category->router                                       = 'BoardHelperRoute::getListRoute';
		$category->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $category, 'type_id')
			: $db->insertObject('#__content_types', $category);

		// Item
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$item                                               = new stdClass();
		$item->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$item->type_title                                   = 'Board Item';
		$item->type_alias                                   = 'com_board.item';
		$item->table                                        = new stdClass();
		$item->table->special                               = new stdClass();
		$item->table->special->dbtable                      = '#__board_items';
		$item->table->special->key                          = 'id';
		$item->table->special->type                         = 'Items';
		$item->table->special->prefix                       = 'BoardTable';
		$item->table->special->config                       = 'array()';
		$item->table->common                                = new stdClass();
		$item->table->common->dbtable                       = '#__ucm_content';
		$item->table->common->key                           = 'ucm_id';
		$item->table->common->type                          = 'Corecontent';
		$item->table->common->prefix                        = 'JTable';
		$item->table->common->config                        = 'array()';
		$item->table                                        = json_encode($item->table);
		$item->rules                                        = '';
		$item->field_mappings                               = new stdClass();
		$item->field_mappings->common                       = new stdClass();
		$item->field_mappings->common->core_content_item_id = 'id';
		$item->field_mappings->common->core_title           = 'title';
		$item->field_mappings->common->core_state           = 'state';
		$item->field_mappings->common->core_alias           = 'id';
		$item->field_mappings->common->core_created_time    = 'created';
		$item->field_mappings->common->core_modified_time   = 'null';
		$item->field_mappings->common->core_body            = 'text';
		$item->field_mappings->common->core_hits            = 'hits';
		$item->field_mappings->common->core_publish_up      = 'created';
		$item->field_mappings->common->core_publish_down    = 'publish_down';
		$item->field_mappings->common->core_access          = 'access';
		$item->field_mappings->common->core_params          = 'attribs';
		$item->field_mappings->common->core_featured        = 'null';
		$item->field_mappings->common->core_metadata        = 'metadata';
		$item->field_mappings->common->core_language        = 'null';
		$item->field_mappings->common->core_images          = 'images';
		$item->field_mappings->common->core_urls            = 'null';
		$item->field_mappings->common->core_version         = 'null';
		$item->field_mappings->common->core_ordering        = 'created';
		$item->field_mappings->common->core_metakey         = 'metakey';
		$item->field_mappings->common->core_metadesc        = 'metadesc';
		$item->field_mappings->common->core_catid           = 'null';
		$item->field_mappings->common->core_xreference      = 'null';
		$item->field_mappings->common->asset_id             = 'null';
		$item->field_mappings->special                      = new stdClass();
		$item->field_mappings->special->contacts            = 'contacts';
		$item->field_mappings->special->region              = 'region';
		$item->field_mappings                               = json_encode($item->field_mappings);
		$item->router                                       = 'BoardHelperRoute::getItemRoute';
		$item->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $item, 'type_id')
			: $db->insertObject('#__content_types', $item);
	}


	/**
	 * Move layouts folder
	 *
	 * @param string $path path to files
	 *
	 * @since  1.0.0
	 */
	protected function moveLayouts($path)
	{
		$component = JPATH_ADMINISTRATOR . $path . '/layouts';
		$layouts   = JPATH_ROOT . '/layouts' . $path;

		if (!JFolder::exists(JPATH_ROOT . '/layouts/components'))
		{
			JFolder::create(JPATH_ROOT . '/layouts/components');
		}

		if (JFolder::exists($layouts))
		{
			JFolder::delete($layouts);
		}

		JFolder::move($component, $layouts);

	}

	/**
	 *
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since  1.0.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		$db = Factory::getDbo();
		// Remove content_type
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query)->execute();

		// Remove tag_map
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query)->execute();

		// Remove ucm_content
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_board.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query)->execute();

		// Remove images
		JFolder::delete(JPATH_ROOT . '/images/board');

		// Remove layouts
		JFolder::delete(JPATH_ROOT . '/layouts/components/com_board');
	}

	/**
	 * Method to fix tables
	 *
	 * @param string $path path to component directory
	 *
	 * @since  1.0.0
	 */
	protected function fixTables($path)
	{
		$file = JPATH_ADMINISTRATOR . $path . '/sql/install.mysql.utf8.sql';
		if (!empty($file))
		{
			$sql = JFile::read($file);

			if (!empty($sql))
			{
				$db      = Factory::getDbo();
				$queries = $db->splitSql($sql);
				foreach ($queries as $query)
				{
					$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));
					try
					{
						$db->execute();
					}
					catch (JDataBaseExceptionExecuting $e)
					{
						JLog::add(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()),
							JLog::WARNING, 'jerror');
					}
				}
			}
		}
	}
}