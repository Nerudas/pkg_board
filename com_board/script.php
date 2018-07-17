<?php
/**
 * @package    Bulletin Board Component
 * @version    1.1.0
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
		$this->createImageFolders();
		$this->moveLayouts($path);
		return true;
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
		// Item
		$db = Factory::getDbo();
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
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query)->execute();

		// Remove tag_map
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_board.item'));
		$db->setQuery($query)->execute();

		// Remove ucm_content
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