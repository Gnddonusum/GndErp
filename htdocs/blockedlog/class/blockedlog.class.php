<?php
/* Copyright (C) 2017       ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2020  Laurent Destailleur <eldy@destailleur.fr>
 * Copyright (C) 2022 		charlene benke		<charlene@patas-monkey.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
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
 *
 * See https://medium.com/@lhartikk/a-blockchain-in-200-lines-of-code-963cc1cc0e54
 */


/**
 *	Class to manage Blocked Log
 */
class BlockedLog
{
	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * Id of the log
	 * @var int
	 */
	public $id;

	/**
	 * Entity
	 * @var int
	 */
	public $entity;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * Unique fingerprint of the log
	 * @var string
	 */
	public $signature = '';

	/**
	 * Unique fingerprint of the line log content
	 * @var string
	 */
	public $signature_line = '';

	/**
	 * @var float|string|null
	 */
	public $amounts = null;

	/**
	 * trigger action
	 * @var string
	 */
	public $action = '';

	/**
	 * Object element
	 * @var string
	 */
	public $element = '';

	/**
	 * Object id
	 * @var int
	 */
	public $fk_object = 0;

	/**
	 * Log certified by remote authority or not
	 * @var boolean
	 */
	public $certified = false;

	/**
	 * Author
	 * @var int
	 */
	public $fk_user = 0;

	/**
	 * @var int|string
	 */
	public $date_creation;

	/**
	 * @var int|string
	 */
	public $date_modification;

	/**
	 * @var int
	 */
	public $date_object = 0;

	/**
	 * @var string
	 */
	public $ref_object = '';

	/**
	 * @var ?stdClass
	 */
	public $object_data = null;

	/**
	 * @var string
	 */
	public $object_version = '';

	/**
	 * @var string
	 */
	public $user_fullname = '';

	/**
	 * @var string
	 */
	public $debuginfo;

	/**
	 * Array of tracked event codes
	 * @var array<string,string|mixed>
	 */
	public $trackedevents = array();



	/**
	 *      Constructor
	 *
	 *      @param		DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Load list of tracked events into $this->trackedevents.
	 *
	 * @return int<1,1>		Always 1
	 */
	public function loadTrackedEvents()
	{
		global $langs;

		$this->trackedevents = array();

		// Customer Invoice/Facture / Payment
		if (isModEnabled('invoice')) {
			$this->trackedevents['BILL_VALIDATE'] = array('id' => 'BILL_VALIDATE', 'label' => 'logBILL_VALIDATE', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logBILL_VALIDATE'));
			//$this->trackedevents['BILL_UPDATE'] = array('id' => 'BILL_VALIDATE', 'label' => 'logBILL_UPDATE', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logBILL_UPDATE'));
			$this->trackedevents['BILL_SENTBYMAIL'] = array('id' => 'BILL_SENTBYMAIL', 'label' => 'logBILL_SENTBYMAIL', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logBILL_SENTBYMAIL'));
			$this->trackedevents['DOC_DOWNLOAD'] = array('id' => 'DOC_DOWNLOAD', 'label' => 'BlockedLogBillDownload', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('BlockedLogBillDownload'));
			$this->trackedevents['DOC_PREVIEW'] = array('id' => 'DOC_PREVIEW', 'label' => 'BlockedLogBillPreview', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('BlockedLogBillPreview'));
			$this->trackedevents['PAYMENT_CUSTOMER_CREATE'] = array('id' => 'PAYMENT_CUSTOMER_CREATE', 'label' => 'logPAYMENT_CUSTOMER_CREATE', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logPAYMENT_CUSTOMER_CREATE'));
			$this->trackedevents['PAYMENT_CUSTOMER_DELETE'] = array('id' => 'PAYMENT_CUSTOMER_DELETE', 'label' => 'logPAYMENT_CUSTOMER_DELETE', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logPAYMENT_CUSTOMER_DELETE'));
		}

		/* Supplier
		// Supplier Invoice / Payment
		if (isModEnabled("fournisseur")) {
			$this->trackedevents['BILL_SUPPLIER_VALIDATE']='BlockedLogSupplierBillValidate';
			$this->trackedevents['BILL_SUPPLIER_DELETE']='BlockedLogSupplierBillDelete';
			$this->trackedevents['BILL_SUPPLIER_SENTBYMAIL']='BlockedLogSupplierBillSentByEmail'; // Trigger key does not exists, we want just into array to list it as done
			$this->trackedevents['SUPPLIER_DOC_DOWNLOAD']='BlockedLogSupplierBillDownload';		// Trigger key does not exists, we want just into array to list it as done
			$this->trackedevents['SUPPLIER_DOC_PREVIEW']='BlockedLogSupplierBillPreview';		// Trigger key does not exists, we want just into array to list it as done
			$this->trackedevents['PAYMENT_SUPPLIER_CREATE']='BlockedLogSupplierBillPaymentCreate';
			$this->trackedevents['PAYMENT_SUPPLIER_DELETE']='BlockedLogsupplierBillPaymentCreate';
		}
		 */

		// Donation
		if (isModEnabled('don')) {
			$this->trackedevents['DON_VALIDATE'] = 'logDON_VALIDATE';
			$this->trackedevents['DON_DELETE'] = 'logDON_DELETE';
			//$this->trackedevents['DON_SENTBYMAIL']='logDON_SENTBYMAIL';
			$this->trackedevents['DONATION_PAYMENT_CREATE'] = 'logDONATION_PAYMENT_CREATE';
			$this->trackedevents['DONATION_PAYMENT_DELETE'] = 'logDONATION_PAYMENT_DELETE';
		}

		/*
		// Salary
		if (isModEnabled('salary')) {
			$this->trackedevents['PAYMENT_SALARY_CREATE']='BlockedLogSalaryPaymentCreate';
			$this->trackedevents['PAYMENT_SALARY_MODIFY']='BlockedLogSalaryPaymentCreate';
			$this->trackedevents['PAYMENT_SALARY_DELETE']='BlockedLogSalaryPaymentCreate';
		}
		 */

		// Members
		if (isModEnabled('member')) {
			$this->trackedevents['MEMBER_SUBSCRIPTION_CREATE'] = 'logMEMBER_SUBSCRIPTION_CREATE';
			$this->trackedevents['MEMBER_SUBSCRIPTION_MODIFY'] = 'logMEMBER_SUBSCRIPTION_MODIFY';
			$this->trackedevents['MEMBER_SUBSCRIPTION_DELETE'] = 'logMEMBER_SUBSCRIPTION_DELETE';
		}

		// Bank
		if (isModEnabled("bank")) {
			$this->trackedevents['PAYMENT_VARIOUS_CREATE'] = 'logPAYMENT_VARIOUS_CREATE';
			$this->trackedevents['PAYMENT_VARIOUS_MODIFY'] = 'logPAYMENT_VARIOUS_MODIFY';
			$this->trackedevents['PAYMENT_VARIOUS_DELETE'] = 'logPAYMENT_VARIOUS_DELETE';
		}

		// Cashdesk
		// $conf->global->BANK_ENABLE_POS_CASHCONTROL must be set to 1 by all external POS modules
		$moduleposenabled = (isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalString('BANK_ENABLE_POS_CASHCONTROL'));
		if ($moduleposenabled) {
			$this->trackedevents['CASHCONTROL_VALIDATE'] = 'logCASHCONTROL_VALIDATE';
		}

		// Add more action to track from a conf variable
		// For example: STOCK_MOVEMENT,...
		if (getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED')) {
			$tmparrayofmoresupportedevents = explode(',', getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED'));
			foreach ($tmparrayofmoresupportedevents as $val) {
				$this->trackedevents[$val] = 'log'.$val;
			}
		}

		$this->trackedevents['BLOCKEDLOG_EXPORT'] = 'logBLOCKEDLOG_EXPORT';

		return 1;
	}

	/**
	 * Try to retrieve source object (it it still exists).
	 *
	 * @return string		URL string of source object
	 */
	public function getObjectLink()
	{
		global $langs;

		if ($this->element === 'facture') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

			$object = new Facture($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		}
		if ($this->element === 'invoice_supplier') {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

			$object = new FactureFournisseur($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'payment') {
			require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

			$object = new Paiement($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'payment_supplier') {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

			$object = new PaiementFourn($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'payment_donation') {
			require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';

			$object = new PaymentDonation($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'payment_various') {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';

			$object = new PaymentVarious($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'don' || $this->element === 'donation') {
			require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

			$object = new Don($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'subscription') {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

			$object = new Subscription($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'cashcontrol') {
			require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';

			$object = new CashControl($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'stockmouvement') {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$object = new MouvementStock($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->element === 'project') {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

			$object = new Project($this->db);
			if ($object->fetch($this->fk_object) > 0) {
				return $object->getNomUrl(1);
			} else {
				$this->error = (string) (((int) $this->error) + 1);
			}
		} elseif ($this->action == 'BLOCKEDLOG_EXPORT') {
			return '<i class="opacitymedium">'.$langs->trans("logBLOCKEDLOG_EXPORT").'</i>';
		} elseif ($this->action == 'MODULE_SET') {
			return '<i class="opacitymedium">'.$langs->trans("BlockedLogEnabled").'</i>';
		} elseif ($this->action == 'MODULE_RESET') {
			if ($this->signature == '0000000000') {
				return '<i class="opacitymedium">'.$langs->trans("BlockedLogDisabled").'</i>';
			} else {
				return '<i class="opacitymedium">'.$langs->trans("BlockedLogDisabledBis").'</i>';
			}
		}

		return '<i class="opacitymedium">'.$langs->trans('ImpossibleToReloadObject', $this->element, $this->fk_object).'</i>';
	}

	/**
	 * Try to retrieve user author
	 *
	 * @return string
	 */
	public function getUser()
	{
		global $langs, $cachedUser;

		if (empty($cachedUser)) {
			$cachedUser = array();
		}

		if (empty($cachedUser[$this->fk_user])) {
			$u = new User($this->db);
			if ($u->fetch($this->fk_user) > 0) {
				$cachedUser[$this->fk_user] = $u;
			}
		}

		if (!empty($cachedUser[$this->fk_user])) {
			return $cachedUser[$this->fk_user]->getNomUrl(1);
		}

		return $langs->trans('ImpossibleToRetrieveUser', $this->fk_user);
	}

	/**
	 *	Populate properties of log from object data
	 *
	 *	@param	CommonObject|stdClass	$object		object to store
	 *	@param	string					$action		action
	 *	@param	float|int				$amounts	amounts
	 *	@param	?User					$fuser		User object (forced)
	 *	@return	int<-1,-1>|int<1,1>					>0 if OK, <0 if KO
	 */
	public function setObjectData(&$object, $action, $amounts, $fuser = null)
	{
		global $langs, $user, $mysoc;

		if (is_object($fuser)) {
			$user = $fuser;
		}

		// Generic fields

		// action
		$this->action = $action;
		// amount
		$this->amounts = $amounts;
		// date
		if ($object->element == 'payment' || $object->element == 'payment_supplier') {
			'@phan-var-force Paiement|PaiementFourn $object';
			$this->date_object = empty($object->datepaye) ? $object->date : $object->datepaye;
		} elseif ($object->element == 'payment_salary') {
			'@phan-var-force PaymentSalary $object';
			$this->date_object = $object->datev;
		} elseif ($object->element == 'payment_donation' || $object->element == 'payment_various') {
			'@phan-var-force PaymentDonation $object';
			$this->date_object = empty($object->datepaid) ? $object->datep : $object->datepaid;
		} elseif ($object->element == 'subscription') {
			'@phan-var-force Subscription $object';
			$this->date_object = $object->dateh;
		} elseif ($object->element == 'cashcontrol') {
			'@phan-var-force CashControl $object';
			$this->date_object = $object->date_creation;
		} elseif (property_exists($object, 'date')) {
			// Generic case
			$this->date_object = $object->date; // @phan-suppress-current-line PhanUndeclaredProperty
		} elseif (property_exists($object, 'datem')) {
			// Generic case (second chance, for example for stock movement)
			$this->date_object = $object->datem; // @phan-suppress-current-line PhanUndeclaredProperty
		}

		// ref
		$this->ref_object = ((!empty($object->newref)) ? $object->newref : $object->ref); // newref is set when validating a draft, ref is set in other cases
		// type of object
		$this->element = $object->element;
		// id of object
		$this->fk_object = $object->id;


		// Set object_data
		$this->object_data = new stdClass();
		// Add fields to exclude
		$arrayoffieldstoexclude = array(
			'table_element', 'fields', 'ref_previous', 'ref_next', 'origin', 'origin_id', 'oldcopy', 'picto', 'error', 'errors', 'model_pdf', 'modelpdf', 'last_main_doc', 'civility_id', 'contact', 'contact_id',
			'table_element_line', 'ismultientitymanaged', 'isextrafieldmanaged',
			'array_languages',
			'childtables',
			'contact_ids',
			'context',
			'labelStatus',
			'labelStatusShort',
			'linkedObjectsIds',
			'linkedObjects',
			'fk_delivery_address',
			'projet',          // There is already ->fk_project
			'restrictiononfksoc',
			'specimen',
		);
		// Add more fields to exclude depending on object type
		if ($this->element == 'cashcontrol') {
			$arrayoffieldstoexclude = array_merge($arrayoffieldstoexclude, array(
				'name', 'lastname', 'firstname', 'region', 'region_id', 'region_code', 'state', 'state_id', 'state_code', 'country', 'country_id', 'country_code',
				'total_ht', 'total_tva', 'total_ttc', 'total_localtax1', 'total_localtax2',
				'barcode_type', 'barcode_type_code', 'barcode_type_label', 'barcode_type_coder', 'mode_reglement_id', 'cond_reglement_id', 'mode_reglement', 'cond_reglement', 'shipping_method_id',
				'fk_incoterms', 'label_incoterms', 'location_incoterms', 'lines'));
		}

		// Add thirdparty info
		if (empty($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if (!empty($object->thirdparty)) {
			$this->object_data->thirdparty = new stdClass();

			foreach ($object->thirdparty as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!in_array($key, array(
				'name', 'name_alias', 'ref_ext', 'address', 'zip', 'town', 'state_code', 'country_code', 'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6', 'phone', 'fax', 'email', 'barcode',
				'tva_intra', 'localtax1_assuj', 'localtax1_value', 'localtax2_assuj', 'localtax2_value', 'managers', 'capital', 'typent_code', 'forme_juridique_code', 'code_client', 'code_fournisseur'
				))) {
					continue; // Discard if not into a dedicated list
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->thirdparty->$key = $value;
				}
			}
		}

		// Add company info
		if (!empty($mysoc)) {
			$this->object_data->mycompany = new stdClass();

			foreach ($mysoc as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!in_array($key, array(
				'name', 'name_alias', 'ref_ext', 'address', 'zip', 'town', 'state_code', 'country_code', 'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6', 'phone', 'fax', 'email', 'barcode',
				'tva_intra', 'localtax1_assuj', 'localtax1_value', 'localtax2_assuj', 'localtax2_value', 'managers', 'capital', 'typent_code', 'forme_juridique_code', 'code_client', 'code_fournisseur'
				))) {
					continue; // Discard if not into a dedicated list
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->mycompany->$key = $value;
				}
			}
		}

		// Add user info
		if (!empty($user)) {
			$this->fk_user = $user->id;
			$this->user_fullname = $user->getFullName($langs);
		}

		// Field specific to object
		if ($this->element == 'facture') {
			'@phan-var-force Facture $object';
			foreach ($object as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!in_array($key, array(
					'ref', 'ref_client', 'ref_supplier', 'date', 'datef', 'datev', 'type', 'total_ht', 'total_tva', 'total_ttc', 'localtax1', 'localtax2', 'revenuestamp', 'datepointoftax', 'note_public', 'lines',
					'module_source', 'pos_source'
				))) {
					continue; // Discard if not into a dedicated list
				}
				if ($key == 'lines') {
					$lineid = 0;
					foreach ($value as $tmpline) {	// $tmpline is object FactureLine
						$lineid++;
						foreach ($tmpline as $keyline => $valueline) {
							if (!in_array($keyline, array(
								'ref', 'product_type', 'product_label',
								'qty',
								'subprice',
								'vat_src_code', 'tva_tx', 'localtax1_tx', 'localtax2_tx',
								'total_ht', 'total_tva', 'total_ttc', 'total_localtax1', 'total_localtax2',
								'multicurrency_code', 'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc',
								'info_bits', 'special_code',
							))) {
								continue; // Discard if not into a dedicated list
							}

							if (empty($this->object_data->invoiceline[$lineid]) || !is_object($this->object_data->invoiceline[$lineid])) {		// To avoid warning
								$this->object_data->invoiceline[$lineid] = new stdClass();
							}

							if (!is_object($valueline) && !is_null($valueline) && $valueline !== '') {
								$this->object_data->invoiceline[$lineid]->$keyline = $valueline;
							}
						}
					}
				} elseif (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->$key = $value;
				}
			}

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		} elseif ($this->element == 'invoice_supplier') {
			'@phan-var-force FactureFournisseur $object';
			foreach ($object as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!in_array($key, array(
				'ref', 'ref_client', 'ref_supplier', 'date', 'datef', 'type', 'total_ht', 'total_tva', 'total_ttc', 'localtax1', 'localtax2', 'revenuestamp', 'datepointoftax', 'note_public'
				))) {
					continue; // Discard if not into a dedicated list
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->$key = $value;
				}
			}

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		} elseif ($this->element == 'payment' || $this->element == 'payment_supplier' || $this->element == 'payment_donation' || $this->element == 'payment_various') {
			'@phan-var-force Paiement|PaiementFourn|PaymentDonation|PaymentVarious $object';
			$datepayment = $object->datepaye ? $object->datepaye : ($object->datepaid ? $object->datepaid : $object->datep);
			$paymenttypeid = $object->paiementid ? $object->paiementid : ($object->paymenttype ? $object->paymenttype : $object->type_payment);

			$this->object_data->ref = $object->ref;
			$this->object_data->date = $datepayment;
			$this->object_data->type_code = dol_getIdFromCode($this->db, $paymenttypeid, 'c_paiement', 'id', 'code');

			if (!empty($object->num_payment)) {
				$this->object_data->payment_num = $object->num_payment;
			}
			if (!empty($object->note_private)) {
				$this->object_data->note_private = $object->note_private;
			}
			//$this->object_data->fk_account = $object->fk_account;
			//var_dump($this->object_data);exit;

			$totalamount = 0;

			// Loop on each invoice payment amount (payment_part)
			if (is_array($object->amounts) && !empty($object->amounts)) {
				$paymentpartnumber = 0;
				foreach ($object->amounts as $objid => $amount) {
					if (empty($amount)) {
						continue;
					}

					$totalamount += $amount;

					$tmpobject = null;
					if ($this->element == 'payment_supplier') {
						include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
						$tmpobject = new FactureFournisseur($this->db);
					} elseif ($this->element == 'payment') {
						include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
						$tmpobject = new Facture($this->db);
					} elseif ($this->element == 'payment_donation') {
						include_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
						$tmpobject = new Don($this->db);
					} elseif ($this->element == 'payment_various') {
						include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
						$tmpobject = new PaymentVarious($this->db);
					}

					if (!is_object($tmpobject)) {
						continue;
					}

					$result = $tmpobject->fetch($objid);

					if ($result <= 0) {
						$this->error = $tmpobject->error;
						$this->errors = $tmpobject->errors;
						dol_syslog("Failed to fetch object with id ".$objid, LOG_ERR);
						return -1;
					}

					$paymentpart = new stdClass();
					$paymentpart->amount = $amount;

					if (!in_array($this->element, array('payment_donation', 'payment_various'))) {
						$result = $tmpobject->fetch_thirdparty();
						if ($result == 0) {
							$this->error = 'Failed to fetch thirdparty for object with id '.$tmpobject->id;
							$this->errors[] = $this->error;
							dol_syslog("Failed to fetch thirdparty for object with id ".$tmpobject->id, LOG_ERR);
							return -1;
						} elseif ($result < 0) {
							$this->error = $tmpobject->error;
							$this->errors = $tmpobject->errors;
							return -1;
						}

						$paymentpart->thirdparty = new stdClass();
						foreach ($tmpobject->thirdparty as $key => $value) {
							if (in_array($key, $arrayoffieldstoexclude)) {
								continue; // Discard some properties
							}
							if (!in_array($key, array(
							'name', 'name_alias', 'ref_ext', 'address', 'zip', 'town', 'state_code', 'country_code', 'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6', 'phone', 'fax', 'email', 'barcode',
							'tva_intra', 'localtax1_assuj', 'localtax1_value', 'localtax2_assuj', 'localtax2_value', 'managers', 'capital', 'typent_code', 'forme_juridique_code', 'code_client', 'code_fournisseur'
							))) {
								continue; // Discard if not into a dedicated list
							}
							if (!is_object($value) && !is_null($value) && $value !== '') {
								$paymentpart->thirdparty->$key = $value;
							}
						}
					}

					// Init object to avoid warnings
					if ($this->element == 'payment_donation') {
						$paymentpart->donation = new stdClass();
					} else {
						$paymentpart->invoice = new stdClass();
					}

					if ($this->element != 'payment_various') {
						foreach ($tmpobject as $key => $value) {
							if (in_array($key, $arrayoffieldstoexclude)) {
								continue; // Discard some properties
							}
							if (!in_array($key, array(
							'ref', 'ref_client', 'ref_supplier', 'date', 'datef', 'type', 'total_ht', 'total_tva', 'total_ttc', 'localtax1', 'localtax2', 'revenuestamp', 'datepointoftax', 'note_public'
							))) {
								continue; // Discard if not into a dedicated list
							}
							if (!is_object($value) && !is_null($value) && $value !== '') {
								if ($this->element == 'payment_donation') {
									$paymentpart->donation->$key = $value;
								} elseif ($this->element == 'payment_various') {
									$paymentpart->various->$key = $value;
								} else {
									$paymentpart->invoice->$key = $value;
								}
							}
						}

						$paymentpartnumber++; // first payment will be 1
						$this->object_data->payment_part[$paymentpartnumber] = $paymentpart;
					}
				}
			} elseif (!empty($object->amount)) {
				$totalamount = $object->amount;
			}

			$this->object_data->amount = $totalamount;

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		} elseif ($this->element == 'payment_salary') {
			'@phan-var-force PaymentSalary $object';
			$this->object_data->amounts = array($object->amount);

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		} elseif ($this->element == 'subscription') {
			'@phan-var-force Subscription $object';
			foreach ($object as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!in_array($key, array(
					'id', 'datec', 'dateh', 'datef', 'fk_adherent', 'amount', 'import_key', 'statut', 'note'
				))) {
					continue; // Discard if not into a dedicated list
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->$key = $value;
				}
			}

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		} elseif ($this->element == 'stockmouvement') {
			'@phan-var-force StockTransfer $object';
			foreach ($object as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->$key = $value;
				}
			}
		} else {
			// Generic case
			foreach ($object as $key => $value) {
				if (in_array($key, $arrayoffieldstoexclude)) {
					continue; // Discard some properties
				}
				if (!is_object($value) && !is_null($value) && $value !== '') {
					$this->object_data->$key = $value;
				}
			}

			if (!empty($object->newref)) {
				$this->object_data->ref = $object->newref;
			}
		}

		// A trick to be sure all the object_data is an associative array
		// json_encode and json_decode are not able to manage mixed object (with array/object, only full arrays or full objects)
		$this->object_data = json_decode(json_encode($this->object_data, JSON_FORCE_OBJECT), false);

		return 1;
	}

	/**
	 *	Get object from database
	 *
	 *	@param      int		$id       	Id of object to load
	 *	@return     int<-1,1>			>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id)
	{
		global $langs;

		if (empty($id)) {
			$this->error = 'BadParameter';
			return -1;
		}

		$sql = "SELECT b.rowid, b.date_creation, b.signature, b.signature_line, b.amounts, b.action, b.element, b.fk_object, b.entity,";
		$sql .= " b.certified, b.tms, b.fk_user, b.user_fullname, b.date_object, b.ref_object, b.object_data, b.object_version";
		$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog as b";
		if ($id) {
			$sql .= " WHERE b.rowid = ".((int) $id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->id 				= $obj->rowid;
				$this->entity 			= $obj->entity;

				$this->date_creation 	= $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->tms);

				$this->amounts			= (float) $obj->amounts;
				$this->action 			= $obj->action;
				$this->element			= $obj->element;

				$this->fk_object = $obj->fk_object;
				$this->date_object = $this->db->jdate($obj->date_object);
				$this->ref_object = $obj->ref_object;

				$this->fk_user = $obj->fk_user;
				$this->user_fullname = $obj->user_fullname;

				$this->object_data = $this->dolDecodeBlockedData($obj->object_data);
				$this->object_version = $obj->object_version;

				$this->signature		= $obj->signature;
				$this->signature_line 	= $obj->signature_line;
				$this->certified		= ($obj->certified == 1);

				return 1;
			} else {
				$langs->load("errors");
				$this->error = $langs->trans("ErrorRecordNotFound");
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 * Encode data
	 *
	 * @param	?stdClass	$data	Data to serialize
	 * @param	int<0,1>	$mode	0=serialize, 1=json_encode
	 * @return 	string				Value serialized, an object (stdClass)
	 */
	public function dolEncodeBlockedData($data, $mode = 0)
	{
		$aaa = '';
		try {
			$aaa = json_encode($data);
		} catch (Exception $e) {
			// print $e->getErrs);
		}

		return $aaa;
	}


	/**
	 * Decode data
	 *
	 * @param	string	$data	Data to unserialize
	 * @param	int		$mode	0=unserialize, 1=json_decode
	 * @return 	Object			Value unserialized, an object (stdClass)
	 */
	public function dolDecodeBlockedData($data, $mode = 0)
	{
		$aaa = null;
		try {
			$aaa = (object) jsonOrUnserialize($data);
		} catch (Exception $e) {
			// print $e->getErrs);
		}

		return $aaa;
	}


	/**
	 *	Set block certified by authority
	 *
	 *	@return	boolean
	 */
	public function setCertified()
	{
		$res = $this->db->query("UPDATE ".MAIN_DB_PREFIX."blockedlog SET certified=1 WHERE rowid=".((int) $this->id));
		if (!$res) {
			return false;
		}

		return true;
	}

	/**
	 *	Create blocked log in database.
	 *
	 *	@param	User	$user      			Object user that create
	 *  @param	string	$forcesignature		Force signature (for example '0000000000' when we disabled the module)
	 *	@return	int<-3,-1>|int<1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $forcesignature = '')
	{
		global $conf, $langs;

		$langs->load('blockedlog');

		// Clean data
		$this->amounts = (float) $this->amounts;

		dol_syslog(get_class($this).'::create action='.$this->action.' fk_user='.$this->fk_user.' user_fullname='.$this->user_fullname, LOG_DEBUG);

		// Check parameters/properties
		if (!isset($this->amounts)) {	// amount can be 0 for some events (like when module is disabled)
			$this->error = $langs->trans("BlockLogNeedAmountsValue");
			dol_syslog($this->error, LOG_WARNING);
			return -1;
		}

		if (empty($this->element)) {
			$this->error = $langs->trans("BlockLogNeedElement");
			dol_syslog($this->error, LOG_WARNING);
			return -2;
		}

		if (empty($this->action)) {
			$this->error = $langs->trans("BadParameterWhenCallingCreateOfBlockedLog");
			dol_syslog($this->error, LOG_WARNING);
			return -3;
		}
		if (empty($this->fk_user)) {
			$this->user_fullname = '(Anonymous)';
		}

		$this->date_creation = dol_now();

		$this->object_version = DOL_VERSION;


		$this->db->begin();

		$previoushash = $this->getPreviousHash(1, 0); // This get last record and lock database until insert is done and transaction closed

		$keyforsignature = $this->buildKeyForSignature();	// All the information for the hash (meta data + data saved)

		$this->debuginfo = $this->buildFirstPartOfKeyForSignature();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

		$this->signature_line = dol_hash($keyforsignature, '5'); // Not really useful
		$this->signature = dol_hash($previoushash.$keyforsignature, '5');
		if ($forcesignature) {
			$this->signature = $forcesignature;
		}
		//var_dump($keyforsignature);var_dump($previoushash);var_dump($this->signature_line);var_dump($this->signature);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."blockedlog (";
		$sql .= " date_creation,";
		$sql .= " action,";
		$sql .= " amounts,";
		$sql .= " signature,";
		$sql .= " signature_line,";
		$sql .= " element,";
		$sql .= " fk_object,";
		$sql .= " date_object,";
		$sql .= " ref_object,";
		$sql .= " object_data,";
		$sql .= " object_version,";
		$sql .= " certified,";
		$sql .= " fk_user,";
		$sql .= " user_fullname,";
		$sql .= " entity,";
		$sql .= " debuginfo";	// Only stored
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($this->date_creation)."',";
		$sql .= "'".$this->db->escape($this->action)."',";
		$sql .= $this->amounts.",";
		$sql .= "'".$this->db->escape($this->signature)."',";
		$sql .= "'".$this->db->escape($this->signature_line)."',";
		$sql .= "'".$this->db->escape($this->element)."',";
		$sql .= (int) $this->fk_object.",";
		$sql .= "'".$this->db->idate($this->date_object)."',";
		$sql .= "'".$this->db->escape($this->ref_object)."',";
		$sql .= "'".$this->db->escape($this->dolEncodeBlockedData($this->object_data))."',";
		$sql .= "'".$this->db->escape($this->object_version)."',";
		$sql .= "0,";
		$sql .= $this->fk_user.",";
		$sql .= "'".$this->db->escape($this->user_fullname)."',";
		$sql .= ($this->entity ? $this->entity : $conf->entity).",";
		$sql .= "'".$this->db->escape($this->debuginfo)."'";
		$sql .= ")";

		/*
		$a = serialize($this->object_data); $a2 = unserialize($a); $a4 = print_r($a2, true);
		$b = json_encode($this->object_data); $b2 = json_decode($b); $b4 = print_r($b2, true);
		var_dump($a4 == print_r($this->object_data, true) ? 'a=a' : 'a not = a');
		var_dump($b4 == print_r($this->object_data, true) ? 'b=b' : 'b not = b');
		exit;
		*/

		$res = $this->db->query($sql);
		if ($res) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."blockedlog");

			if ($id > 0) {
				$this->id = $id;

				$this->db->commit();

				return $this->id;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}

		// The commit will release the lock so we can insert nex record
	}

	/**
	 *	Check if current signature still correct compared to the value in chain
	 *
	 *	@param	string			$previoushash		If previous signature hash is known, we can provide it to avoid to make a search of it in database.
	 *  @param	int<0,2>		$returnarray		1=Return array of details, 2=Return array of details including keyforsignature, 0=Boolean
	 *	@return	boolean|array{checkresult:bool,calculatedsignature:string,previoushash:string,keyforsignature?:string}	True if OK, False if KO
	 */
	public function checkSignature($previoushash = '', $returnarray = 0)
	{
		if (empty($previoushash)) {
			$previoushash = $this->getPreviousHash(0, $this->id);
		}

		// Build the string for the signature
		$keyforsignature = $this->buildKeyForSignature();

		//$signature_line = dol_hash($keyforsignature, '5'); // Not really useful
		$signature = dol_hash($previoushash.$keyforsignature, 'sha256');
		//var_dump($previoushash); var_dump($keyforsignature); var_dump($signature_line); var_dump($signature);

		$res = ($signature === $this->signature);

		if (!$res) {
			$this->error = 'Signature KO';
		}

		if ($returnarray) {
			if ($returnarray == 1) {
				unset($keyforsignature);
				return array('checkresult' => $res, 'calculatedsignature' => $signature, 'previoushash' => $previoushash);
			} else {	// Consume much memory ($keyforsignature is a large var)
				return array('checkresult' => $res, 'calculatedsignature' => $signature, 'previoushash' => $previoushash, 'keyforsignature' => $keyforsignature);
			}
		} else {
			unset($keyforsignature);
			return $res;
		}
	}

	/**
	 * Return a string for signature.
	 * Note: rowid of line not included as it is not a business data and this allow to make backup of a year
	 * and restore it into another database with different ids without comprimising checksums
	 *
	 * @return string		Key for signature
	 */
	private function buildKeyForSignature()
	{
		//print_r($this->object_data);
		if (((int) $this->object_version) >= 18) {
			// Note: $this->amounts can be '0', '1.1', '1.123';  // All 0 at end should have been removed already
			return $this->buildFirstPartOfKeyForSignature().'|'.json_encode($this->object_data, JSON_FORCE_OBJECT);
		} else {
			return $this->buildFirstPartOfKeyForSignature().'|'.print_r($this->object_data, true);
		}
	}

	/**
	 * Return first part of string for signature.
	 * Note: rowid of line not included as it is not a business data and this allow to make backup of a year
	 * and restore it into another database with different ids without comprimising checksums
	 *
	 * @return string		First part of key for signature
	 */
	private function buildFirstPartOfKeyForSignature()
	{
		// Note: $this->amounts can be '0', '1.1', '1.123';  // All 0 at end should have been removed already
		//if (((int) $this->object_version) >= 18) {
		return $this->date_creation.'|'.$this->action.'|'.$this->amounts.'|'.$this->ref_object.'|'.$this->date_object.'|'.$this->user_fullname;
	}


	/**
	 *	Get previous signature/hash in chain
	 *
	 *	@param int<0,1>	$withlock		1=With a lock
	 *	@param int		$beforeid		ID of a record
	 *  @return	string					Hash of previous record (if beforeid is defined) or hash of last record (if beforeid is 0)
	 */
	public function getPreviousHash($withlock = 0, $beforeid = 0)
	{
		global $conf;

		$previoussignature = '';

		// Fast search of previous record by searching with beforeid - 1. This is very fast and will work 99% of time.
		if ($beforeid) {
			$sql = "SELECT rowid, signature FROM ".MAIN_DB_PREFIX."blockedlog";
			$sql .= " WHERE entity = ".((int) $conf->entity);
			$sql .= " AND rowid = ".((int) $beforeid - 1);
			$sql .= ($withlock ? " FOR UPDATE " : "");

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$previoussignature = $obj->signature;
				}
			} else {
				dol_print_error($this->db);
				exit;
			}
		}

		if (empty($previoussignature)) {
			$sql = "SELECT rowid, signature FROM ".MAIN_DB_PREFIX."blockedlog";
			if ($beforeid) {
				$sql .= $this->db->hintindex('entity_rowid', 1);
			}
			$sql .= " WHERE entity = ".((int) $conf->entity);
			if ($beforeid) {
				$sql .= " AND rowid < ".(int) $beforeid;
			}
			$sql .= " ORDER BY rowid DESC LIMIT 1";
			$sql .= ($withlock ? " FOR UPDATE " : "");

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$previoussignature = $obj->signature;
				}
			} else {
				dol_print_error($this->db);
				exit;
			}
		}

		if (empty($previoussignature)) {
			// First signature line (line 0)
			$previoussignature = $this->getSignature();
		}

		return $previoussignature;
	}

	/**
	 *	Return array of log objects (with criteria)
	 *
	 *	@param	string 					$element      		Element to search
	 *	@param	int		 				$fk_object			Id of object to search
	 *	@param	int<0,max> 				$limit      		Max number of element, 0 for all
	 *	@param	string 					$sortfield     		Sort field
	 *	@param	string 					$sortorder     		Sort order
	 *	@param	int 					$search_fk_user 	Id of user(s)
	 *	@param	int 					$search_start   	Start time limit
	 *	@param	int 					$search_end     	End time limit
	 *  @param	string					$search_ref			Search ref
	 *  @param	string					$search_amount		Search amount
	 *  @param	string|string[]	        $search_code		Search code
	 *  @param	string			        $search_signature	Search signature
	 *	@return	BlockedLog[]|int<-2,-1>						Array of object log or <0 if error
	 */
	public function getLog($element, $fk_object, $limit = 0, $sortfield = '', $sortorder = '', $search_fk_user = -1, $search_start = -1, $search_end = -1, $search_ref = '', $search_amount = '', $search_code = '', $search_signature = '')
	{
		global $conf;
		//global $cachedlogs;

		/* $cachedlogs allow fastest search */
		//if (empty($cachedlogs)) $cachedlogs = array();

		if ($element == 'all') {
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
			 WHERE entity = ".$conf->entity;
		} elseif ($element == 'not_certified') {
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
			 WHERE entity = ".$conf->entity." AND certified = 0";
		} elseif ($element == 'just_certified') {
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
			 WHERE entity = ".$conf->entity." AND certified = 1";
		} else {
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog
			 WHERE entity = ".$conf->entity." AND element = '".$this->db->escape($element)."'";
		}

		if ($fk_object) {
			$sql .= natural_search("rowid", (string) $fk_object, 1);
		}
		if ($search_fk_user > 0) {
			$sql .= natural_search("fk_user", (string) $search_fk_user, 2);
		}
		if ($search_start > 0) {
			$sql .= " AND date_creation >= '".$this->db->idate($search_start)."'";
		}
		if ($search_end > 0) {
			$sql .= " AND date_creation <= '".$this->db->idate($search_end)."'";
		}
		if ($search_ref != '') {
			$sql .= natural_search("ref_object", $search_ref);
		}
		if ($search_amount != '') {
			$sql .= natural_search("amounts", $search_amount, 1);
		}
		if ($search_signature != '') {
			$sql .= natural_search("signature", $search_signature, 0);
		}
		if (is_array($search_code)) {
			if (!empty($search_code)) {
				$sql .= natural_search("action", implode(',', $search_code), 3);
			}
		} else {
			if ($search_code != '' && $search_code != '-1') {
				$sql .= natural_search("action", $search_code, 3);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit + 1); // We want more, because we will stop into loop later with error if we reach max

		$res = $this->db->query($sql);
		if ($res) {
			$results = array();

			$i = 0;
			while ($obj = $this->db->fetch_object($res)) {
				$i++;
				if ($i > $limit) {
					// Too many record, we will consume too much memory
					return -2;
				}

				//if (!isset($cachedlogs[$obj->rowid]))
				//{
				$b = new BlockedLog($this->db);
				$b->fetch($obj->rowid);
				//$b->loadTrackedEvents();
				//$cachedlogs[$obj->rowid] = $b;
				//}

				//$results[] = $cachedlogs[$obj->rowid];
				$results[] = $b;
			}

			return $results;
		}

		return -1;
	}

	/**
	 *	Return the signature (hash) of the "genesis-block" (Block 0).
	 *
	 *	@return	string					Signature of genesis-block for current conf->entity
	 */
	public function getSignature()
	{
		global $db, $conf, $mysoc;

		if (!getDolGlobalString('BLOCKEDLOG_ENTITY_FINGERPRINT')) { // creation of a unique fingerprint
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

			$fingerprint = dol_hash(print_r($mysoc, true).getRandomPassword(true), '5');

			dolibarr_set_const($db, 'BLOCKEDLOG_ENTITY_FINGERPRINT', $fingerprint, 'chaine', 0, 'Numeric Unique Fingerprint', $conf->entity);

			$conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT = $fingerprint;
		}

		return $conf->global->BLOCKEDLOG_ENTITY_FINGERPRINT;
	}


	/**
	 * Check if module was already used or not for at least one recording.
	 *
	 * @param   int<0,1>	$ignoresystem       Ignore system events for the test
	 * @return  bool
	 */
	public function alreadyUsed($ignoresystem = 0)
	{
		global $conf;

		$result = false;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".$conf->entity;
		if ($ignoresystem) {
			$sql .= " AND action not in ('MODULE_SET','MODULE_RESET')";
		}
		$sql .= $this->db->plimit(1);

		$res = $this->db->query($sql);
		if ($res !== false) {
			$obj = $this->db->fetch_object($res);
			if ($obj) {
				$result = true;
			}
		} else {
			dol_print_error($this->db);
		}

		dol_syslog("Module Blockedlog alreadyUsed(ignoresystem=".$ignoresystem.") returns ".json_encode($result));

		return $result;
	}
}
