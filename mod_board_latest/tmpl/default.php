<?php
/**
 * @package    Bulletin Board - Latest Module
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;
echo '<pre>', print_r($categoryLink, true), '</pre>';
echo '<pre>', print_r($addLink, true), '</pre>';
if ($items)
{
	echo '<ul>';
	foreach ($items as $item)
	{
		echo '<li>' . $item->title . '</li>';
	}
	echo '</ul>';
}

echo '<pre>', print_r($items, true), '</pre>';

