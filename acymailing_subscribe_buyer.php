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
if(!include_once(rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acymailing'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php')){
	JFactory::getApplication()->enqueueMessage(JText::_('VMCUSTOM_ACYBUYER_ACYMAILING_NEEDED'), 'error');
	return;
}

class plgVmCustomAcyMailing_subscribe_Buyer extends vmCustomPlugin {

	function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$varsToPush = array(
			'subscribe_buyers'=>array(-1, 'int'),
			'subscribe_buyers_default'=>array(1, 'int'),
			'allow_subscribe'=>array(-1, 'int'),
			'allow_subscribe_default'=>array(0, 'int'),
			'lists'=>array(array(), 'array'),
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
		$listClass = acymailing_get('class.list');
		$allLists = $listClass->getLists();
		return (array)$allLists;
	}
	function getAcyListname($listid) {
		$listClass = acymailing_get('class.list');
		$list = $listClass->get($listid);
		return ($list?$list->name:'');
	}
	
	function getAcyUid ($userIDorMail) {
		$subscriberClass = acymailing_get('class.subscriber');
		return $subscriberClass->subid ($userIDorMail);
	}
	/** Returns all listid, to which the AcyMailing user is subscribed */
	function getUserSubscriptions ($subid) {
		if (!($subid>0)) return array();
		$listsubClass = acymailing_get('class.listsub');
		$subscriptions = array();
		foreach ($listsubClass->getSubscription($subid) as $l) {
			if ($l->status == 1) $subscriptions[] = $l->listid;
		}
		return $subscriptions;
	}
	/** Creates a new AcyMailing user for the given name/email/uid */
	function addAcyUser ($name, $email, $uid) {
		$db = &JFactory::getDBO();
		if ($uid>0) {
			$q = "SELECT `id`, `name`, `email` FROM `#__users` WHERE `id`=".(int)$uid;
			$db->setQuery($q);
			$userinfo = $db->loadObject();
			if (empty($email)) $email = $userinfo->email;
			if (empty($name))  $name = $userinfo->name;
		}
		$myUser = array ('email' => $email, 'name' => $name, 'confirmed' => 1, 'enabled' => 1, 'accept' => 1, 'html' => 1);
		$subscriberClass = acymailing_get('class.subscriber');
		$subid = $subscriberClass->save((object)$myUser);
		return $subid;
	}
	/** Adds the acy user to all the given lists (the subscriber has already been created). If the user has unsubscribed, he will not be subscribed again! */
	function subscribeUser ($acyuid, $lists) {
		if (empty($lists)) return;
		$subscriberClass = acymailing_get('class.subscriber');
		$newSubscription=array();
		foreach ($lists as $l) {
			$newSubscription[$l] = array ('status'=>1);
		}
		$subscriberClass->checkAccess = false;
		$subscriberClass->saveSubscription($acyuid, $newSubscription);
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
		$subscribe_modes = array(
			'0' => array('id'=>'0', 'name'=>'VMCUSTOM_ACYBUYER_ALLOW_NONE'),
			'1' => array('id'=>'1', 'name'=>'VMCUSTOM_ACYBUYER_ALLOW_REGISTERED'),
			'2' => array('id'=>'2', 'name'=>'VMCUSTOM_ACYBUYER_ALLOW_ANONYMOUS'),
		);
		$autosubscribe_modes = array(
			'1' => array('id'=>'1', 'name'=>'VMCUSTOM_ACYBUYER_AUTO_YES'),
			'0' => array('id'=>'0', 'name'=>'VMCUSTOM_ACYBUYER_AUTO_NO'),
		);
		
		$lists = $this->getAcyMailinglists();
		if ($lists) {
			$html .= VmHTML::row ('select','VMCUSTOM_ACYBUYER_LIST', 'custom_param['.$row.'][lists][]', $lists, $field->lists, ' multiple', 'listid', 'name', '');
			
			$html .= VmHTML::row('select', 'VMCUSTOM_ACYBUYER_AUTO_SUBSCRIBE', 'custom_param['.$row.'][subscribe_buyers]', 
				array_merge(
					array(array('id'=>'-1', 'name'=>JText::sprintf('VMCUSTOM_ACYBUYER_AUTO_DEFAULT', JText::_($autosubscribe_modes[$field->subscribe_buyers_default]['name'])))),
					$autosubscribe_modes),
				$field->allow_subscribe,'','id', 'name', false);
			$html .= VmHTML::row('select', 'VMCUSTOM_ACYBUYER_ALLOW_SUBSCRIBE', 'custom_param['.$row.'][allow_subscribe]', 
				array_merge(
					array(array('id'=>'-1', 'name'=>JText::sprintf('VMCUSTOM_ACYBUYER_ALLOW_DEFAULT', JText::_($subscribe_modes[$field->allow_subscribe_default]['name'])))),
					$subscribe_modes),
				$field->allow_subscribe,'','id', 'name', false);
		} else {
			// No lists found, no need to display any other option
			$html .= '<tr><td>'.JText::_('VMCUSTOM_ACYBUYER_NO_LISTS').'</td></tr>';
		}

		$html .= '</table></fieldset>';
		$retValue .= $html;
		$row++;
		return true ;
	}
	
	function addAcyStuff () {
		acymailing_initModule('header',null);
	}
	function getThisURL() {
		if (isset ($_SERVER['REQUEST_URI'])) {
			$request_uri = $_SERVER['REQUEST_URI'];
		} else {
			$request_uri = $_SERVER['PHP_SELF'];
			if (!empty($_SERVER['QUERY_STRING'])) $request_uri .= '?' . $_SERVER['QUERY_STRING'];
		}
		return ((empty($_SERVER['HTTPS']) OR strtolower($_SERVER['HTTPS']) != "on") ? "http://" : "https://") . $_SERVER['HTTP_HOST'].$request_uri;
	}
	
	
	function displayProduct($field) {
		$html = '';
		$this->parseCustomParams($field);
		$user = JFactory::getUser();
		$uid = $user->get('id');
		$allow = ($field->allow_subscribe>=0)?($field->allow_subscribe):($field->allow_subscribe_default);
		
		if ($uid>0 && $allow>=1) {
			$acyuid = $this->getAcyUid($uid);
			$allsubscriptions = $this->getUserSubscriptions($acyuid);
			$uinfo = array(
				'id' => $uid,
				'name' => $user->get('name'), 
				'email' => $user->get('email'),
				'subscribed' => array_intersect ($field->lists, $allsubscriptions),
				'notsubscribed' => array_diff ($field->lists, $allsubscriptions),
			);
			$this->addAcyStuff();
			$html .= $this->renderByLayout('button_subscribe', array(acymailing_getModuleFormName(), $uinfo, $field->lists, $this->getThisURL(), $field));
		} elseif ($allow>=2) {
			$this->addAcyStuff();
			$html .= $this->renderByLayout('button_subscribe', array(acymailing_getModuleFormName(), array(), $field->lists, $this->getThisURL(), $field));
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
		return false;
	}

	function getTableSQLFields() {
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
		$acyuid = $this->getAcyUid($email);
		if (!($acyuid>0)&&($uid>0)) {
			$acyuid = $this->getAcyUid($uid);
		}
		$customModel = VmModel::getModel('customfields');
		foreach ($order['items'] as $item) {
			$customs = $customModel->getproductCustomslist ($item->virtuemart_product_id);
			foreach ($customs as $field) {
				if ($field->custom_element != $this->_name) continue;
				$subscribe = ($field->subscribe_buyers>=0)?($field->subscribe_buyers):($field->subscribe_buyers_default);
				if (!$field->subscribe_buyers) continue;
				// Add the user to the lists:
				if(!($acyuid>0)) {
					$acyuid = $this->addAcyUser ($name, $email, $uid);
				}
				$allsubscriptions = $this->getUserSubscriptions ($acyuid);
				$notsubscribed = array_diff ($field->lists, $allsubscriptions);
				$this->subscribeUser($acyuid, $notsubscribed);
				// TODO: Shall we display an infor message about the subscription?
				// foreach ($notsubscribed as $l) {
				//	$listname=$this->getAcyListname($l);
				//	JFactory::getApplication()->enqueueMessage(JText::sprintf('VMCUSTOM_ACYBUYER_ADDED_USER', $name, $email, $listname), 'info');
				// }
			}
		}
	}
}

// No closing tag