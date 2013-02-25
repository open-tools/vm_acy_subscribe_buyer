<?php
defined('_JEXEC') or 	die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;
/**
 * A custom field plugin to automatically subscribe buyers to AcyMailing lists
 * @author Reinhold Kainhofer
 * @package VirtueMart
 * @subpackage vmcustom
 * @copyright Copyright (C) 2013 Reinhold Kainhofer
 * Some ideas are taken from the Acy VM Assign plugin by Nordmograph
 * @copyright Copyright (C) 2003-2012 Nordmograph
 * Some ideas are taken from the AcyMailing module by ACYBA
 * @copyright Copyright (C) 2009-2013 ACYBA S.A.R.L. All right reserved.
 * @license GNU/GPLv3, http://www.gnu.org/copyleft/gpl-3.0.html 
 *
 * http://kainhofer.com
 */
if (!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');

class plgVmCustomAcy_subscribe_Buyer extends vmCustomPlugin {

	function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$varsToPush = array(
			'lists'=>array(array(), 'array'),
			'subscribe_buyers'=>array(1, 'int'),
			'allow_subscribe'=>array(1, 'int'),
		);
		$this->setConfigParameterable('custom_params',$varsToPush);
	}
	function plgVmOnSelfCallFE($type,$name,&$render) {
		if ($name != $this->_name || $type != 'vmcustom') return false;
		vmDebug('plgVmOnSelfCallFE');
	}
	function plgVmOnSelfCallBE($type, $name, &$output) {
		if ($name != $this->_name || $type != 'vmcustom') return false;
		vmDebug('plgVmOnSelfCallBE');
	}

	function getAcyMailinglists() {
		$q ="SELECT `listid`, `name` FROM `#__acymailing_list` ORDER BY `name` DESC ";
		$db =& JFactory::getDBO();	
		$db->setQuery($q);
		return $db->loadAssocList('listid', 'name');
	}
	function getAcyListname($listid) {
		$q ="SELECT `name` FROM `#__acymailing_list` WHERE `listid`=".(int)$listid;
		$db =& JFactory::getDBO();	
		$db->setQuery($q);
		return $db->loadResult();
	}
	
	function getAcyUidFromUser ($userid) {
		if (!($userid>0)) return null;
		$q = "SELECT `subid` FROM `#__acymailing_subscriber` WHERE `userid`='".(int)$userid."'";
		$db = &JFactory::getDBO();
		$db->setQuery($q);
		return $db->loadResult();
	}
	function getAcyUidFromEmail ($email) {
		if (empty($email)) return null;
		$db = &JFactory::getDBO();
		$q = "SELECT `subid` FROM `#__acymailing_subscriber` WHERE `email` COLLATE utf8_general_ci = '".$db->escape($email)."'";
		$db->setQuery($q);
		return $db->loadResult();
	}
	/** Returns all listid, to which the AcyMailing user is subscribed */
	function getUserSubscriptions ($uid) {
		if (!($uid>0)) return array();
		$db = &JFactory::getDBO();
		$q = "SELECT `listid` FROM `#__acymailing_listsub` WHERE `subid` = '".(int)$uid."'";
		$db->setQuery($q);
		$subscribed = $db->loadColumn();
		return $subscribed;
	}
	/** Creates a new AcyMailing user for the given name/email/uid */
	function addAcyUser ($name, $email, $uid) {
		$db = &JFactory::getDBO();
		if ($uid>0) {
			$q = "SELECT `id`, `name`, `email` FROM `#__users` WHERE `id`=".(int)$uid;
			$db->setQuery($q);
			$userinfo = $db->loadObject();
// print("<pre>addAcyUser ($name, $email, $uid): loaded user data <br>(SQL: $q) <br/>".print_r($userinfo,1)."</pre>");
			if (empty($email)) 
				$email = $userinfo->email;
			if (empty($name))
				$name = $userinfo->name;
		}
		$q = "INSERT IGNORE INTO `#__acymailing_subscriber` (`email`, `userid`, `name`, `created`, `confirmed`, `enabled`, `accept`, `html`) 
		      VALUES ('".$db->escape($email)."', ".(int)$uid.", '".$db->escape($name)."', ".time().", 1, 1, 1, 1)";
		$db->setQuery($q);
		$db->query();
		$err = $db->getErrorMsg();
		if (!empty($err)) {
			JFactory::getApplication()->enqueueMessage(JText::sprintf('VMCUSTOM_ACYBUYER_SQL_ERROR', $err), 'error');
			print("<pre>SQL error: sql=$q, <br/>error: ".$err."</pre>");
			return 0;
		}
		return $db->insertid();
	}
	/** Adds the acy user to all the given lists (the subscriber has already been created).*/
	function subscribeUser ($acyuid, $lists) {
		$db = &JFactory::getDBO();
		foreach ($lists as $l) {
			$q = "INSERT IGNORE INTO `#__acymailing_listsub` 
			      (`listid`, `subid`, `subdate`, `status`)
			      VALUES
			      (".(int)$l.", ".(int)$acyuid.", ".time().", 1)";
			$db->setQuery($q);
			$db->query();
		}
	}
	/** Removes the user from all the given lists */
	function unsubscribeUser ($acyuid, $lists) {
		// TODO
	}

	/**
	 * @see Form displayed in the product edit page in the BE, configure the download file
	 * @author Reinhold Kainhofer
	 */
	function plgVmOnProductEdit($field, $product_id, &$row,&$retValue) {
		if ($field->custom_element != $this->_name) return '';
		
		$this->parseCustomParams($field);
		$html = '';
		$html .='<fieldset>
			<legend>'. JText::_('VMCUSTOM_ACYBUYER') .'</legend>
			<table class="admintable">
			';
		
		$lists = $this->getAcyMailinglists();
		if ($lists) {
			$html .= VmHTML::row ('select','VMCUSTOM_ACYBUYER_LIST', 'custom_param['.$row.'][lists][]', $lists, $field->lists, ' multiple', 'listid', 'name', '');
			$html .= VmHTML::row ('checkbox','VMCUSTOM_ACYBUYER_AUTO_SUBSCRIBE', 'custom_param['.$row.'][subscribe_buyers]', $field->subscribe_buyers);
			$html .= VmHTML::row ('checkbox','VMCUSTOM_ACYBUYER_ALLOW_SUBSCRIBE', 'custom_param['.$row.'][allow_subscribe]', $field->allow_subscribe);
		} else {
			// No lists found, no need to display any other option
			$html .= '<tr><td>'.JText::_('VMCUSTOM_ACYBUYER_NO_LISTS').'</td></tr>';
		}

		$html .= '</table></fieldset>';
		$retValue .= $html;
		$row++;
		return true ;
	}
	
	function displayProduct($field) {
$this->addAcyUser("Reinhold Kainhofer", "reinhold@kainhofer.com" , 673);
		$html = '';
		$this->parseCustomParams($field);
		$user = JFactory::getUser()->get('id');
		if ($user>0) {
			$acyuid = $this->getAcyUidFromUser($user);
			$allsubscriptions = $this->getUserSubscriptions($acyuid);
			$subscribed = array_intersect ($field->lists, $allsubscriptions);
			$notsubscribed = array_diff ($field->lists, $allsubscriptions);
			if (!empty($notsubscribed)) {
				$html .= $this->renderByLayout('button_subscribe', array($notsubscribed, $user, $field));
				// TODO: implement subscribe button
			}
			if (!empty($subscribed)) {
				// TODO: implement unsubscribe button
				$html .= $this->renderByLayout('button_unsubscribe', array($subscribed, $user, $field));
			}
// 			$html .= "<pre>USER: $user, field: ".print_r($field,1).", all subscriptions: ".print_r($allsubscriptions,1).", subscriptions: ".print_r($subscribed,1).", unsubscriptions: ".print_r($notsubscribed,1).", lists: ".print_r($field->lists, 1)."</pre>";
		} elseif ($field->allow_subscribe) {
			$html .= $this->renderByLayout('button_subscribe_anon', array($field->lists, $field));
			// TODO: Display anonymous subscribe button
			$html = "<pre>NO USER, SUBSCRIBE allowed</pre>";
		} else {
			// Not logged in, manual subscription not allowed
		}

		return $html;
	}

	/**
	 * plgVmOnDisplayProductVariantFE ... Called for product variant custom fields to display on the product details page
	 */
	function plgVmOnDisplayProductVariantFE($field,&$row,&$group) {
		// default return if it's not this plugin
		if ($field->custom_element != $this->_name) return '';
		$group->display .= $this->displayProduct($field);
		return true;
	}

	/**
	 * plgVmOnDisplayProductFE ... Called for NON-product variant custom fields to display on the product details page
	 */
	function plgVmOnDisplayProductFE( $product, &$idx,&$field){
		// default return if it's not this plugin
		if ($field->custom_element != $this->_name) return '';
		$field->display .= $this->displayProduct($field);
		return true;
	}

	/**
	 * We must reimplement this triggers for joomla 1.7
	 * vmplugin triggers note by Max Milbers
	 */
	public function plgVmOnStoreInstallPluginTable($psType, $name) {
		return $this->onStoreInstallPluginTable($psType, $name);
	}

	function plgVmDeclarePluginParamsCustom($psType,$name,$id, &$data){
		return $this->declarePluginParams('custom', $name, $id, $data);
	}

	function plgVmSetOnTablePluginParamsCustom($name, $id, &$table){
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	function plgVmOnDisplayEdit($virtuemart_custom_id,&$customPlugin){
		return $this->onDisplayEditBECustom($virtuemart_custom_id,$customPlugin);
	}

	public function getVmPluginCreateTableSQL() {
// 		return $this->createTableSQL('Downloads for Sale tracking');
		return false;
	}

	function getTableSQLFields() {
// 		$SQLfields = array();
// 		return $SQLfields;
		return null;
	}
	/**
	 * This function is called, when the order is confirmed by the shopper.
	 *
	 * Here are the last checks done by payment plugins.
	 * The mails are created and send to vendor and shopper
	 * will show the orderdone page (thank you page)
	 *
	 */
	function plgVmConfirmedOrder($cart, $order) {
		// Each custom field will have its own value for auto-subscribe, so we need to handle all purchased products!
		$uid = $order['details']['BT']->virtuemart_user_id;
		$email = $order['details']['BT']->email;
		$name = $order['details']['BT']->first_name . " " . $order['details']['BT']->last_name;
		$acyuid = $this->getAcyUidFromEmail($email);
		if (!($acyuid>0)&&($uid>0)) {
			$acyuid = $this->getAcyUidFromUser($uid);
		}
		$acyuid = $this->getAcyUidFromUser($uid);
		$customModel = VmModel::getModel('customfields');
		foreach ($order['items'] as $item) {
			$customs = $customModel->getproductCustomslist ($item->virtuemart_product_id);
JFactory::getApplication()->enqueueMessage("<pre>Custom fields: ".print_r($customs,1)."</pre>", 'error');
			foreach ($customs as $field) {
				if ($field->custom_element != $this->_name) continue;
				if (!$field->subscribe_buyers) continue;
				// Add the user to the lists:
				if(!($acyuid>0)) {
					$acyuid = $this->addAcyUser($name, $email, $uid);
				}
				$allsubscriptions = $this->getUserSubscriptions($acyuid);
				$notsubscribed = array_diff ($field->lists, $allsubscriptions);
				$this->subscribeUser($acyuid, $notsubscribed);
				foreach ($notsubscribed as $l) {
					$listname=$this->getAcyListname($l);
					JFactory::getApplication()->enqueueMessage(JText::sprintf('VMCUSTOM_ACYBUYER_ADDED_USER', $name, $email, $listname), 'info');
				}
			}
		}
		// TODO: If auto-subscribe 
JFactory::getApplication()->enqueueMessage("plgVmConfirmedOrder, cart: <pre>".print_r($cart,1)."</pre><br><h2>ORDER:</h2><pre>".print_r($order,1)."</pre>", 'error');
	}
}

// No closing tag