<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.8
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

class BoardControllerMap extends AdminController
{

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $default_view = 'map';

	/**
	 * Method to items total
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getItemsTotal()
	{
		$app = Factory::getApplication();

		$model   = $this->getModel('Items');
		$total   = ($total = $model->getTotal()) ? $total : 0;
		$success = ($total || $total > 0);

		echo new JsonResponse($total, '', !$success);
		$app->close();

	}

	/**
	 * Method to items total
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$app = Factory::getApplication();
		$doc = Factory::getDocument();

		$success  = false;
		$response = '';
		if ($items = $this->getModel('Items')->getItems())
		{
			$success              = true;
			$response             = new stdClass();
			$response->count      = count($items);
			$response->placemarks = array();
			foreach ($items as $id => $item)
			{
				if ($item->placemark)
				{
					$response->placemarks[$id] = $item->placemark;
				}
			}

		}

		// Get view
		$name   = $this->input->get('view', $this->default_view);
		$type   = $doc->getType();
		$path   = $this->basePath;
		$layout = $this->input->get('layout', 'default', 'string');
		$view   = $this->getView($name, $type, '', array('base_path' => $path, 'layout' => $layout));

		$view->setModel($this->getModel($name), true);
		$view->document = Factory::getDocument();
		$view->items    = $items;

		$response->html = $view->loadTemplate('items');

		echo new JsonResponse($response, '', !$success);
		$app->close();
	}

}