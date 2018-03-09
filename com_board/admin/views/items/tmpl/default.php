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


use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleForWhen', null, array('placeholder_text_multiple' => Text::_('COM_BOARD_ITEM_FOR_WHEN')));
HTMLHelper::_('formbehavior.chosen', 'select');

$app       = Factory::getApplication();
$doc       = Factory::getDocument();
$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$columns = 6;

?>

<form action="<?php echo Route::_('index.php?option=com_board&view=items'); ?>" method="post" name="adminForm"
	  id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table id="itemList" class="table table-striped">
				<thead>
				<tr>
					<th width="1%" class="center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th width="2%" style="min-width:100px" class="center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'i.state', $listDirn, $listOrder); ?>
					</th>
					<th style="min-width:100px" class="nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'i.title', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'i.access', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JAUTHOR', 'i.created_by', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_REGION', 'region_name', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'i.created', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_HITS', 'i.hits', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'i.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="<?php echo $columns; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>

				<?php foreach ($this->items as $i => $item) :
					$canEdit = $user->authorise('core.edit', '#__board_items.' . $item->id);
					$canChange = $user->authorise('core.edit.state', '#__board_items' . $item->id);
					?>
					<tr item-id="<?php echo $item->id ?>">
						<td class="center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<a class="btn btn-micro hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   href="<?php echo Route::_('index.php?option=com_board&task=item.edit&id=' . $item->id); ?>">
									<span class="icon-apply icon-white"></span>
								</a>
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'items.', $canChange, 'cb'); ?>
								<?php
								if ($canChange)
								{
									HTMLHelper::_('actionsdropdown.' . ((int) $item->state === -2 ? 'un' : '') . 'trash', 'cb' . $i, 'items');
									echo HTMLHelper::_('actionsdropdown.render', $this->escape($item->title));
								}
								?>
							</div>
						</td>
						<td>
							<div>
								<?php if ($canEdit) : ?>
									<a class="hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
									   href="<?php echo Route::_('index.php?option=com_board&task=item.edit&id=' . $item->id); ?>">
										<?php echo $this->escape($item->title); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape($item->title); ?>
								<?php endif; ?>

							</div>
							<div>
								<?php if ($item->for_when == 'today'): ?>
									<span class="label label-success" style="margin-bottom: 5px;">
										<?php echo Text::_('COM_BOARD_ITEM_FOR_WHEN_TODAY'); ?>
									</span>

								<?php elseif ($item->for_when == 'tomorrow'): ?>
									<span class="label label-info" style="margin-bottom: 5px;">
										<?php echo Text::_('COM_BOARD_ITEM_FOR_WHEN_TOMORROW'); ?>
									</span>
								<?php endif; ?>
								<?php if (!empty($item->tags->itemTags)): ?>
									<?php foreach ($item->tags->itemTags as $tag): ?>
										<span class="label label-inverse" style="margin-bottom: 5px;">
											<?php echo $tag->title; ?>
										</span>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</td>
						<td class="small hidden-phone">
							<?php echo $this->escape($item->access_level); ?>
						</td>
						<td class="small hidden-phone">
							<?php if ((int) $item->created_by != 0) : ?>
								<a class="hasTooltip"
								   href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by); ?>"
								   title="<?php echo JText::_('JAUTHOR'); ?>">
									<?php echo $this->escape($item->author_name); ?></a>
							<?php endif; ?>
						</td>
						<td class="small hidden-phone nowrap">
							<?php echo ($item->region !== '*') ? $this->escape($item->region_name) :
								Text::_('JGLOBAL_FIELD_REGIONS_ALL'); ?>
						</td>
						<td class="nowrap small hidden-phone">
							<?php echo $item->created > 0 ? HTMLHelper::_('date', $item->created,
								Text::_('DATE_FORMAT_LC2')) : '-' ?>
						</td>
						<td class="hidden-phone center">
							<span class="badge badge-info">
								<?php echo (int) $item->hits; ?>
							</span>
						</td>
						<td class="hidden-phone center">
							<?php echo $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
