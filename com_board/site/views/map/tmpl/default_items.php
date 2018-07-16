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

?>
<div class="items">
	<?php if ($this->items): ?>
		<?php foreach ($this->items as $item): ?>
			<div class="item" data-show="false" data-board-item="<?php echo $item->id; ?>">
				<h2><a data-board-show="<?php echo $item->id; ?>"><?php echo $item->title; ?></a></h2>

			</div>
			<hr data-board-item="<?php echo $item->id; ?>">
		<?php endforeach; ?>
	<?php endif; ?>
</div>


