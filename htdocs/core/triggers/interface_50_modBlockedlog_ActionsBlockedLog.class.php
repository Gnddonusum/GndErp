<?php
/* Copyright (C) 2017       ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018  Laurent Destailleur	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/triggers/interface_50_modBlockedlog_ActionsBlockedLog.class.php
 *  \ingroup    system
 *  \brief      Trigger file for blockedlog module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsBlockedLog extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "system";
		$this->description = "Triggers of this module add action for BlockedLog module (Module of unalterable logs).";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'technic';
	}

	/**
	 * Function called on Dolibarr payment or invoice event.
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('blockedlog')) {
			return 0; // Module not active, we do nothing
		}

		// List of mandatory logged actions
		$listofqualifiedelement = array('invoice', 'facture', 'don', 'payment', 'payment_donation', 'subscription', 'payment_various', 'cashcontrol');

		// Add custom actions to log
		if (getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED')) {
			$listofqualifiedelement = array_merge($listofqualifiedelement, explode(',', getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED')));
		}

		// Test if event/record is qualified
		// If custom actions are not set or if action not into custom actions, we can exclude action if object->element is not valid
		if (!is_object($object) || !property_exists($object, 'element') || !in_array($object->element, $listofqualifiedelement)) {
			return 1;
		}

		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);

		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($this->db);
		$b->loadTrackedEvents();			// Get the list of tracked events into $b->trackedevents

		// Tracked events
		if (!in_array($action, array_keys($b->trackedevents))) {
			return 0;
		}

		// Event/record is qualified
		$qualified = 0;
		$amounts = 0;
		if ($action === 'BILL_VALIDATE' || (($action === 'BILL_DELETE' || $action === 'BILL_SENTBYMAIL') && $object->statut != 0)
			|| $action === 'BILL_SUPPLIER_VALIDATE' || (($action === 'BILL_SUPPLIER_DELETE' || $action === 'BILL_SUPPLIER_SENTBYMAIL') && $object->statut != 0)
			|| $action === 'MEMBER_SUBSCRIPTION_CREATE' || $action === 'MEMBER_SUBSCRIPTION_MODIFY' || $action === 'MEMBER_SUBSCRIPTION_DELETE'
			|| $action === 'DON_VALIDATE' || (($action === 'DON_MODIFY' || $action === 'DON_DELETE') && $object->statut != 0)
			|| $action === 'CASHCONTROL_VALIDATE'
			|| (in_array($object->element, array('facture', 'supplier_invoice')) && $action === 'DOC_DOWNLOAD' && $object->statut != 0)
			|| (in_array($object->element, array('facture', 'supplier_invoice')) && $action === 'DOC_PREVIEW' && $object->statut != 0)
			|| (getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED') && in_array($action, explode(',', getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED'))))
		) {
			$qualified++;

			if (in_array($action, array(
				'MEMBER_SUBSCRIPTION_CREATE', 'MEMBER_SUBSCRIPTION_MODIFY', 'MEMBER_SUBSCRIPTION_DELETE',
				'DON_VALIDATE', 'DON_MODIFY', 'DON_DELETE'))) {
				$amounts = (float) $object->amount;
			} elseif ($action == 'CASHCONTROL_VALIDATE') {
				$amounts = (float) $object->cash + (float) $object->cheque + (float) $object->card;
			} elseif (property_exists($object, 'total_ttc')) {
				$amounts = (float) $object->total_ttc;
			}
		}
		/*if ($action === 'BILL_PAYED' || $action==='BILL_UNPAYED'
		 || $action === 'BILL_SUPPLIER_PAYED' || $action === 'BILL_SUPPLIER_UNPAYED')
		{
			$qualified++;
			$amounts=  (double) $object->total_ttc;
		}*/
		if ($action === 'PAYMENT_CUSTOMER_CREATE' || $action === 'PAYMENT_SUPPLIER_CREATE' || $action === 'DONATION_PAYMENT_CREATE'
			|| $action === 'PAYMENT_CUSTOMER_DELETE' || $action === 'PAYMENT_SUPPLIER_DELETE' || $action === 'DONATION_PAYMENT_DELETE') {
			$qualified++;
			$amounts = 0;
			if (!empty($object->amounts)) {
				foreach ($object->amounts as $amount) {
					$amounts += (float) $amount;
				}
			} elseif (!empty($object->amount)) {
				$amounts = $object->amount;
			}
		} elseif (strpos($action, 'PAYMENT') !== false && !in_array($action, array('PAYMENT_ADD_TO_BANK'))) {
			$qualified++;
			$amounts = (float) $object->amount;
		}

		// Another protection.
		// May be used when event is DOC_DOWNLOAD or DOC_PREVIEW and element is not an invoice
		if (!$qualified) {
			return 0; // not implemented action log
		}

		// Set field date_object, ref_object, fk_object, element, object_data
		$result = $b->setObjectData($object, $action, $amounts, $user);
		//var_dump($b); exit;

		if ($result < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return -1;
		}

		$res = $b->create($user);

		if ($res < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return -1;
		} else {
			return 1;
		}
	}
}
