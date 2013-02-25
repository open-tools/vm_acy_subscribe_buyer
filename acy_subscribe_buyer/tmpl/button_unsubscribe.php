<?php
/**
 *
 * @author Reinhold Kainhofer
 * @package VirtueMart
 * @subpackage custom
 * @copyright Copyright (C) 2013 RK - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 **/
 
defined('_JEXEC') or die();
if(JFile::exists(JPATH_SITE.DS.'plugins'.DS.'vmcustom'.DS.'acy_subscribe_buyer'.DS.'assets'.DS.'acy_subscribe_buyer.css')) {
	$doc =& JFactory::getDocument();
	$doc->addStyleSheet(JURI::root().'plugins/vmcustom/acy_subscribe_buyer/assets/acy_subscribe_buyer.css');  
}
?>
<div class="acy_subscribe_buyer">
<div style="background: red">Unsubscribe user <?php echo $viewData[1]; ?> from: <?php print_r($viewData[0]); ?></div>
</div>