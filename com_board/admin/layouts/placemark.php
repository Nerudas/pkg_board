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

use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;

$item = new Registry($displayData);

?>
<style>
	[data-board-placemark] {
		display: block;
		position: relative;
		width: 48px;
		height: 48px;
		margin-top: -48px;
		margin-left: -24px;
		color: inherit
	}

	[data-board-placemark] .title {
		position: absolute;
		top: 0;
		left: 48px;
		background: #fff;
		line-height: 30px;
		max-width: 150px;
		text-overflow: ellipsis;
		padding: 0 5px;
		border: 1px solid #e5e5e5;
		white-space: nowrap;
		overflow: hidden;
		font-size: 14px;
		box-sizing: border-box
	}

	[data-board-placemark][data-viewed="true"] {
		width: 32px;
		height: 32px;
		margin-top: -32px;
		margin-left: -16px;
		color: inherit;
		opacity: .75
	}

	[data-board-placemark][data-viewed="true"] img {
		width: 32px;
		height: 32px
	}

	[data-board-placemark][data-viewed="true"] .title {
		left: 32px;
		line-height: 15px;
		font-size: 12px;
		padding: 0 3px
	}
</style>
<div data-board-placemark="<?php echo $item->get('id', 'x'); ?>" class="placemark" data-viewed="false"
	 data-placemark-coordinates="[[[-24, -48],[300, -48],[24, -8],[24, -8],[0, 0],[-24, -10],[-24, -10]]]">
	<img src="/media/com_board/images/placemark.png"
		 alt="<?php echo $item->get('title', Text::_('JGLOBAL_TITLE')); ?>">
	<div class="title"><?php echo $item->get('title', Text::_('JGLOBAL_TITLE')); ?></div>
</div>
