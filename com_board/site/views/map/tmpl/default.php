<?php
/**
 * @package    Bulletin Board Component
 * @version    1.0.7
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('script', '//api-maps.yandex.ru/2.1/?lang=ru-RU', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('script', 'media/com_board/js/map.min.js', array('version' => 'auto'));
HTMLHelper::_('stylesheet', 'media/com_board/css/map.min.css', array('version' => 'auto'));

$filters = array_keys($this->filterForm->getGroup('filter'));
?>
<div id="board" class="map">
	<div id="itemList" data-board-itemlist="container">
		<div data-board-itemlist="items"></div>
		<div>
			<a data-board-itemlist="back">
				<?php echo Text::_('JPREV'); ?>
			</a>
		</div>
		<div>
			<a data-board-itemlist="close">
				<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>
			</a>
		</div>
	</div>
	<div id="map" data-board-map>
		<form action="<?php echo htmlspecialchars(Factory::getURI()->toString()); ?>" method="get" name="adminForm"
			  data-board-filter data-afterInit="show">
			<?php foreach ($filters as $filter): ?>
				<?php echo $this->filterForm->renderField(str_replace('filter_', '', $filter), 'filter'); ?>
			<?php endforeach; ?>

			<button type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<a href="<?php echo $this->category->link; ?>"><?php echo Text::_('JCLEAR'); ?></a>
		</form>
		<div id="zoom" data-afterInit="show">
			<a data-board-map-zoom="plus">+</a>
			<span data-board-map-zoom="current"></span>
			<a data-board-map-zoom="minus">-</a>
		</div>
		<div id="counter" data-afterInit="show">
			<span data-board-counter="current">0</span>
			/
			<span data-board-counter="total">0</span>
		</div>
		<a id="geo" data-board-map-geo data-afterInit="show">
			G
		</a>
	</div>
</div>

