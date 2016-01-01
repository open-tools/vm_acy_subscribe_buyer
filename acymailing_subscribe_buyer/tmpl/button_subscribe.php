<?php
/**
 *
 * @author Reinhold Kainhofer
 * @package VirtueMart
 * @subpackage custom
 * @copyright Copyright (C) 2013 RK - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 **/
  
// Data passed to this template (as $viewData array):
// $viewData[0] ... acy mailing form name
// $viewData[1] ... user info (id, mail, name, subscribed, notsubscribed), typically extracted from the registered user
// $viewData[2] ... list ids for this product
// $viewData[3] ... Redirect URL
// $viewData[4] ... the whole custom field

defined('_JEXEC') or die();
if(JFile::exists(JPATH_SITE.DS.'plugins'.DS.'vmcustom'.DS.'acy_subscribe_buyer'.DS.'assets'.DS.'acy_subscribe_buyer.css')) {
	$doc =& JFactory::getDocument();
	$doc->addStyleSheet(JURI::root().'plugins/vmcustom/acy_subscribe_buyer/assets/acy_subscribe_buyer.css');  
}
?>
<div class="acy_subscribe_buyer">
<form name="<?php echo $viewData[0]?>" method="post" onsubmit="return submitacymailingform('optin','<?php echo $viewData[0]?>')" action="<?php echo JRoute::_('index.php'); ?>" id="<?php echo $viewData[0]?>">
<?php if (empty($viewData[1]['name']) && empty($viewData[1]['email'])) { ?>
	<input type="text" value="Your Name" name="user[name]" id="user_name_<?php echo $viewData[0]?>">
	<input type="text" value="email@example.com" name="user[email]" id="user_email_<?php echo $viewData[0]?>">
<?php } else { ?>
	<input type="hidden" value="<?php echo $viewData[1]['name'];?>" name="user[name]" id="user_name_<?php echo $viewData[0]?>">
	<input type="hidden" value="<?php echo $viewData[1]['email'];?>" name="user[email]" id="user_email_<?php echo $viewData[0]?>">
<?php } ?>
	<input type="hidden" value="0" name="ajax">
	<input type="hidden" value="sub" name="ctrl">
	<input type="hidden" value="notask" name="task">
	<input type="hidden" value="<?php echo $viewData[3];?>" name="redirect">
	<input type="hidden" value="<?php echo $viewData[3];?>" name="redirectunsub">
	<input type="hidden" value="com_acymailing" name="option">
	<input type="hidden" value="" name="visiblelists">
	<input type="hidden" value="<?php echo implode (',', $viewData[2]); ?>" name="hiddenlists">
	<input type="hidden" value="<?php echo $viewData[0]?>" name="acyformname">
<?php if (!isset($viewData[1]['notsubscribed']) or !empty($viewData[1]['notsubscribed'])) { ?>
	<input type="submit" onclick="try{ return submitacymailingform('optin','<?php echo $viewData[0]?>'); }catch(err){alert('The form could not be submitted '+err);return false;}" name="Submit" value="Subscribe" class="button subbutton btn btn-primary">
<?php }
      if (!isset($viewData[1]['subscribed']) or !empty($viewData[1]['subscribed'])) { ?>
	<input type="button" onclick="return submitacymailingform('optout','<?php echo $viewData[0]?>')" name="Submit" value="Unsubscribe" class="button unsubbutton btn btn-inverse">
<?php } ?>
</form>
</div>