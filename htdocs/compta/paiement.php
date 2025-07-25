<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Teddy Andreotti         <125155@supinfo.com>
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023  		Lenin Rivas	            <lenin.rivas777@gmail.com>
 * Copyright (C) 2023       Sylvain Legrand	        <technique@infras.fr>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Josep Lluís Amador      <joseplluis@lliuretic.cat>
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
 *	\file       htdocs/compta/paiement.php
 *	\ingroup    invoice
 *	\brief      Payment page for customers invoices
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'multicurrency'));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$facid = GETPOSTINT('facid');
$accountid = GETPOSTINT('accountid');
$paymentnum	= GETPOST('num_paiement', 'alpha');
$socid      = GETPOSTINT('socid');

$sortfield	= GETPOST('sortfield', 'aZ09comma');
$sortorder	= GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");

$displayAllInvoices = getDolGlobalInt('MAIN_PAIMENTS_SHOW_ALL_INVOICE_TYPES', 0);
if (GETPOSTISSET('display-all-invoices')) {
	$displayAllInvoices = GETPOSTINT('display-all-invoices');
}


$amounts = array();
$amountsresttopay = array();
$addwarning = 0;

$multicurrency_amounts = array();
$multicurrency_amountsresttopay = array();

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

$object = new Facture($db);

// Load object
if ($facid > 0) {
	$ret = $object->fetch($facid);
}

// Initialize a technical object to manage hooks of paiements. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paiementcard', 'globalcard'));

$formquestion = array();

$usercanissuepayment = $user->hasRight('facture', 'paiement');

$fieldid = 'rowid';
$isdraft = (($object->status == Facture::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'facture', $object->id, '', '', 'fk_soc', $fieldid, $isdraft);


/*
 * Actions
 */
$error = 0;

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$paiement_id = 0;
if (empty($reshook)) {
	if (($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm == 'yes')) && $usercanissuepayment) {
		$datepaye = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
		$totalpayment = 0;
		$multicurrency_totalpayment = 0;
		$atleastonepaymentnotnull = 0;
		$formquestion = array();
		$i = 0;

		// Generate payment array and check if there is payment higher than invoice and payment date before invoice date
		$tmpinvoice = new Facture($db);
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 7) == 'amount_' && GETPOST($key) != '') {
				$cursorfacid = (int) substr($key, 7);
				$amounts[$cursorfacid] = price2num(GETPOST($key));
				if (!empty($amounts[$cursorfacid])) {
					$totalpayment += (float) $amounts[$cursorfacid];
					$atleastonepaymentnotnull++;
				}
				$result = $tmpinvoice->fetch($cursorfacid);
				if ($result <= 0) {
					dol_print_error($db);
				}
				$amountsresttopay[$cursorfacid] = price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement(0));
				if ($amounts[$cursorfacid]) {
					// Check amount
					if ($amounts[$cursorfacid] && (abs((float) $amounts[$cursorfacid]) > abs((float) $amountsresttopay[$cursorfacid]))) {
						$addwarning = 1;
						$formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
					}
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}

				$formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOST($key));
			} elseif (substr($key, 0, 21) == 'multicurrency_amount_') {
				$cursorfacid = (int) substr($key, 21);
				$multicurrency_amounts[$cursorfacid] = price2num(GETPOST($key));
				$multicurrency_totalpayment += (float) $multicurrency_amounts[$cursorfacid];
				if (!empty($multicurrency_amounts[$cursorfacid])) {
					$atleastonepaymentnotnull++;
				}
				$result = $tmpinvoice->fetch($cursorfacid);
				if ($result <= 0) {
					dol_print_error($db);
				}
				$multicurrency_amountsresttopay[$cursorfacid] = price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
				if ($multicurrency_amounts[$cursorfacid]) {
					// Check amount
					if ($multicurrency_amounts[$cursorfacid] && (abs((float) $multicurrency_amounts[$cursorfacid]) > abs((float) $multicurrency_amountsresttopay[$cursorfacid]))) {
						$addwarning = 1;
						$formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
					}
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}

				$formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOSTINT($key));
			}
		}

		// Check parameters
		if (!GETPOST('paiementcode')) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), null, 'errors');
			$error++;
		}

		if (isModEnabled("bank")) {
			// If bank module is on, account is required to enter a payment
			if (GETPOST('accountid') <= 0) {
				setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
				$error++;
			}
		}

		if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull)) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount')), null, 'errors');
			$error++;
		}

		if (empty($datepaye)) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Date')), null, 'errors');
			$error++;
		}

		// Check if payments in both currency
		if ($totalpayment > 0 && $multicurrency_totalpayment > 0) {
			$langs->load("errors");
			setEventMessages($langs->transnoentities('ErrorPaymentInBothCurrency'), null, 'errors');
			$error++;
		}
	}

	/*
	 * Action add_paiement
	 */
	if ($action == 'add_paiement') {
		if ($error) {
			$action = 'create';
		}
		// The next of this action is displayed at the page's bottom.
	}

	/*
	 * Action confirm_paiement
	 */
	if ($action == 'confirm_paiement' && $confirm == 'yes' && $usercanissuepayment) {
		$datepaye = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'), 'tzuser');

		$db->begin();

		$thirdparty = new Societe($db);
		if ($socid > 0) {
			$thirdparty->fetch($socid);
		}

		$multicurrency_code = array();
		$multicurrency_tx = array();

		// Clean parameters amount if payment is for a credit note
		foreach ($amounts as $key => $value) {	// How payment is dispatched
			$tmpinvoice = new Facture($db);
			$tmpinvoice->fetch($key);
			if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE) {
				$newvalue = price2num($value, 'MT');
				$amounts[$key] = - abs((float) $newvalue);
			}
			$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
			$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
		}

		foreach ($multicurrency_amounts as $key => $value) {	// How payment is dispatched
			$tmpinvoice = new Facture($db);
			$tmpinvoice->fetch((int) $key);
			if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE) {
				$newvalue = price2num($value, 'MT');
				$multicurrency_amounts[$key] = - abs((float) $newvalue);
			}
			$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
			$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
		}

		if (isModEnabled("bank")) {
			// If the bank module is active, an account is required to input a payment
			if (GETPOSTINT('accountid') <= 0) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
				$error++;
			}
		}

		// Creation of payment line
		$paiement = new Paiement($db);
		$paiement->datepaye = $datepaye;
		$paiement->amounts = $amounts; // Array with all payments dispatching with invoice id
		$paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
		$paiement->multicurrency_code = $multicurrency_code; // Array with all currency of payments dispatching
		$paiement->multicurrency_tx = $multicurrency_tx; // Array with all currency tx of payments dispatching
		$paiement->paiementid   = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);
		$paiement->num_payment  = GETPOST('num_paiement', 'alpha');
		$paiement->note_private = GETPOST('comment', 'alpha');
		$paiement->fk_account   = GETPOSTINT('accountid');

		$paiement_id = 0;
		if (!$error) {
			// Create payment and update this->multicurrency_amounts if this->amounts filled or
			// this->amounts if this->multicurrency_amounts filled.
			// This also set ->amount and ->multicurrency_amount
			$paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices') == 'on' ? 1 : 0), $thirdparty); // This include closing invoices and regenerating documents
			if ($paiement_id < 0) {
				setEventMessages($paiement->error, $paiement->errors, 'errors');
				$error++;
			}
		}
		/*
		var_dump($paiement->amount);
		var_dump($paiement->multicurrency_amount);
		var_dump($paiement->multicurrency_currency);
		*/

		if (!$error) {
			$label = '(CustomerInvoicePayment)';
			if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) {
				$label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
			}

			$result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOSTINT('accountid'), GETPOST('chqemetteur'), GETPOST('chqbank'));
			if ($result < 0) {
				setEventMessages($paiement->error, $paiement->errors, 'errors');
				$error++;
			}
		}

		if (!$error) {
			$db->commit();

			// If payment dispatching on more than one invoice, we stay on summary page, otherwise jump on invoice card
			$invoiceid = 0;
			foreach ($paiement->amounts as $key => $amount) {
				$facid = $key;
				if (is_numeric($amount) && $amount != 0) {
					if ($invoiceid != 0) {
						$invoiceid = -1; // There is more than one invoice paid by this payment
					} else {
						$invoiceid = $facid;
					}
				}
			}
			if ($invoiceid > 0) {
				$loc = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$invoiceid;
			} else {
				$loc = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$paiement_id;
			}
			header('Location: '.$loc);
			exit;
		} else {
			$db->rollback();
		}
	}
}


/*
 * View
 */

$form = new Form($db);


llxHeader('', $langs->trans("Payment"));


$facture = new Facture($db);
$result = $facture->fetch($facid);

if ($result >= 0) {
	$facture->fetch_thirdparty();

	$title = '';
	if ($facture->type != Facture::TYPE_CREDIT_NOTE) {
		$title .= $langs->trans("EnterPaymentReceivedFromCustomer");
	}
	if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
		$title .= $langs->trans("EnterPaymentDueToCustomer");
	}
	print load_fiche_titre($title);

	// Initialize data for confirmation (this is used because data can be change during confirmation)
	if ($action == 'add_paiement') {
		$i = 0;

		$formquestion[$i++] = array('type' => 'hidden', 'name' => 'facid', 'value' => $facture->id);
		$formquestion[$i++] = array('type' => 'hidden', 'name' => 'socid', 'value' => $facture->socid);
		$formquestion[$i++] = array('type' => 'hidden', 'name' => 'type', 'value' => $facture->type);
	}

	// Invoice with Paypal transaction
	// @TODO add hook here
	if (isModEnabled('paypalplus') && getDolGlobalString('PAYPAL_ENABLE_TRANSACTION_MANAGEMENT') && !empty($facture->ref_ext)) {
		if (getDolGlobalString('PAYPAL_BANK_ACCOUNT')) {
			$accountid = getDolGlobalString('PAYPAL_BANK_ACCOUNT');
		}
		$paymentnum = $facture->ref_ext;
	}

	// Add realtime total information
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
            			setPaiementCode();

            			$("#selectpaiementcode").change(function() {
            				setPaiementCode();
            			});

            			function setPaiementCode()
            			{
            				var code = $("#selectpaiementcode option:selected").val();
							console.log("setPaiementCode code="+code);

                            if (code == \'CHQ\' || code == \'VIR\')
            				{
            					if (code == \'CHQ\')
			                    {
			                        $(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
			                    }
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = ('.$facture->type.' == '.Facture::TYPE_CREDIT_NOTE.') ? \''.dol_escape_js(dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_NOM'))).'\' : jQuery(\'#thirdpartylabel\').val();
            						$(\'#fieldchqemetteur\').val(emetteur);
            					}
            				}
            				else
            				{
            					$(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
            					$(\'#fieldchqemetteur\').val(\'\');
            				}
            			}

						function _elemToJson(selector)
						{
							var subJson = {};
							$.map(selector.serializeArray(), function(n,i)
							{
								subJson[n["name"]] = n["value"];
							});

							return subJson;
						}
						function callForResult(imgId)
						{
							var json = {};
							var form = $("#payment_form");

							json["invoice_type"] = $("#invoice_type").val();
            				json["amountPayment"] = $("#amountpayment").attr("value");
							json["amounts"] = _elemToJson(form.find("input.amount"));
							json["remains"] = _elemToJson(form.find("input.remain"));
							json["token"] = "'.currentToken().'";
							if (imgId != null) {
								json["imgClicked"] = imgId;
							}

							$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
							{
								json = $.parseJSON(data);

								form.data(json);

								for (var key in json)
								{
									if (key == "result")	{
										if (json["makeRed"]) {
											$("#"+key).addClass("error");
										} else {
											$("#"+key).removeClass("error");
										}
										json[key]=json["label"]+" "+json[key];
										$("#"+key).text(json[key]);
									} else {console.log(key);
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						$("#payment_form").find("input.amount").change(function() {
							callForResult();
						});
						$("#payment_form").find("input.amount").keyup(function() {
							callForResult();
						});
			';

		print '	});'."\n";

		//Add js for AutoFill
		print ' $(document).ready(function () {';
			print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(e){
							e.preventDefault();
							$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
						});';
		print '	});'."\n";

		print '	</script>'."\n";
	}

	print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add_paiement">';
		print '<input type="hidden" name="display-all-invoices" value="'.(int) $displayAllInvoices.'">';
	print '<input type="hidden" name="facid" value="'.$facture->id.'">';
	print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
	print '<input type="hidden" name="type" id="invoice_type" value="'.$facture->type.'">';
	print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($facture->thirdparty->name).'">';
	print '<input type="hidden" name="page_y" value="">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Third party
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('Company').'</span></td><td>'.$facture->thirdparty->getNomUrl(4)."</td></tr>\n";

	// Date payment
	print '<tr><td><span class="fieldrequired">'.$langs->trans('Date').'</span></td><td>';
	$datepayment = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
	$datepayment = ($datepayment == '' ? (!getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '') : $datepayment);
	$adddateof = array(array('adddateof'=>$facture->date));
	$adddateof[] = array('adddateof'=>$facture->date_lim_reglement, 'labeladddateof'=>$langs->transnoentities('DateDue'));
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_paiement", 1, 1, 0, '', '', $adddateof);
	print '</td></tr>';

	// Payment mode
	print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
	$form->select_types_paiements((GETPOST('paiementcode') ? GETPOST('paiementcode') : $facture->mode_reglement_code), 'paiementcode', '', 2);
	print "</td>\n";
	print '</tr>';

	// Bank account
	print '<tr>';
	if (isModEnabled("bank")) {
		if ($facture->type != 2) {
			print '<td><span class="fieldrequired">'.$langs->trans('AccountToCredit').'</span></td>';
		}
		if ($facture->type == 2) {
			print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
		}

		print '<td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		print $form->select_comptes($accountid, 'accountid', 0, '', 2, '', (isModEnabled('multicurrency') ? 1 : 0), 'widthcentpercentminusx maxwidth500', 1);
		print '</td>';
	} else {
		print '<td>&nbsp;</td>';
	}
	print "</tr>\n";

	// Bank check number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em class="opacitymedium">('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td><input name="num_paiement" type="text" class="maxwidth200" value="'.$paymentnum.'"></td></tr>';

	// Check transmitter
	print '<tr><td class="'.(GETPOST('paiementcode') == 'CHQ' ? 'fieldrequired ' : '').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
	print ' <em class="opacitymedium">('.$langs->trans("ChequeMaker").')</em>';
	print '</td>';
	print '<td><input id="fieldchqemetteur" class="maxwidth300" name="chqemetteur" type="text" value="'.GETPOST('chqemetteur', 'alphanohtml').'"></td></tr>';

	// Bank name
	print '<tr><td>'.$langs->trans('Bank');
	print ' <em class="opacitymedium">('.$langs->trans("ChequeBank").')</em>';
	print '</td>';
	print '<td><input name="chqbank" class="maxwidth300" type="text" value="'.GETPOST('chqbank', 'alphanohtml').'"></td></tr>';

	// Comments
	print '<tr><td>'.$langs->trans('Comments').'</td>';
	print '<td class="tdtop">';
	print '<textarea name="comment" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.GETPOST('comment', 'restricthtml').'</textarea>';
	print '</td></tr>';

	// Go Source Invoice (useful when there are many invoices)
	if ($action != 'add_paiement' && getDolGlobalString('FACTURE_PAYMENTS_SHOW_LINK_TO_INPUT_ORIGIN_IS_MORE_THAN')) {
		print '<tr><td></td>';
		print '<td class="tdtop right">';
		print '<a class="right" href="#amount_'.$facid.'">'.$langs->trans("GoSourceInvoice").'</a>';
		print '</td></tr>';
	}

	print '</table>';

	print dol_get_fiche_end();


	/*
	 * List of unpaid invoices
	 */

	$sql = "SELECT f.rowid as facid, f.ref, f.total_ht, f.total_tva, f.total_ttc, f.multicurrency_code, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc, f.type,";
	$sql .= " f.datef as df, f.fk_soc as socid, f.date_lim_reglement as dlr";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.entity IN (".getEntity('facture').")";
	$sql .= " AND (f.fk_soc = ".((int) $facture->socid);
	// Can pay invoices of all child of parent company
	if (getDolGlobalString('FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS') && !empty($facture->thirdparty->parent)) {
		$sql .= " OR f.fk_soc IN (SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE parent = ".((int) $facture->thirdparty->parent).")";
	}
	// Can pay invoices of all child of myself
	if (getDolGlobalString('FACTURE_PAYMENTS_ON_SUBSIDIARY_COMPANIES')) {
		$sql .= " OR f.fk_soc IN (SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE parent = ".((int) $facture->thirdparty->id).")";
	}
	$sql .= ") AND f.paye = 0";
	$sql .= " AND f.fk_statut = 1"; // Statut=0 => not validated, Statut=2 => canceled

	if (!$displayAllInvoices) {
		if ($facture->type != Facture::TYPE_CREDIT_NOTE) {
			$sql .= " AND type IN (0,1,3,5)"; // Standard invoice, replacement, deposit, situation
		} else {
			$sql .= " AND type = 2"; // If paying back a credit note, we show all credit notes
		}
	}
	if (!getDolGlobalInt('FACTURE_PAYMENTS_INVOICE_REQUESTED_SORT_FIRST')) {
		// Sort invoices by date and serial number: the older one comes first
		$sql .= " ORDER BY f.datef ASC, f.ref ASC";
	} else {
		// The requested invoice sort first
		$sql .= " ORDER BY f.rowid = ".((int) $facid)." DESC, f.datef ASC, f.ref ASC";
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$totalnboflines = $num = $db->num_rows($resql);
		if ($num > 0) {
			$arraytitle = $langs->trans('Invoice');
			if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
				$arraytitle = $langs->trans("CreditNotes");
			}
			$alreadypayedlabel = $langs->trans('Received');
			$multicurrencyalreadypayedlabel = $langs->trans('MulticurrencyReceived');
			if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
				$alreadypayedlabel = $langs->trans("PaidBack");
				$multicurrencyalreadypayedlabel = $langs->trans("MulticurrencyPaidBack");
			}
			$remaindertopay = $langs->trans('RemainderToTake');
			$multicurrencyremaindertopay = $langs->trans('MulticurrencyRemainderToTake');
			if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
				$remaindertopay = $langs->trans("RemainderToPayBack");
				$multicurrencyremaindertopay = $langs->trans("MulticurrencyRemainderToPayBack");
			}

			$i = 0;
			//print '<tr><td colspan="3">';
			print '<br>';

			$moreHtmlRight = '';
			if ($action=='create') {
				$urlToggleDisplayMod = $_SERVER["PHP_SELF"].'?facid='.$facid.'&action='.$action.'&accountid='.$accountid.'&display-all-invoices=' . (intval(!$displayAllInvoices));

				if (empty($displayAllInvoices)) {
					$btnTitle = $langs->trans('DisplayOtherInvoicesToo');
					if ($object->type != Facture::TYPE_CREDIT_NOTE) {
						$btnTitle = $langs->trans('DisplayCreditNotesToo');
					}
				} else {
					$btnTitle = $langs->trans('HideOtherInvoices');
					if ($object->type != Facture::TYPE_CREDIT_NOTE) {
						$btnTitle = $langs->trans('HideCreditNotes');
					}
				}

				$btnIcon = empty($displayAllInvoices) ? 'fa fa-eye' : 'fa fa-eye-slash';
				$moreHtmlRight.= dolGetButtonTitle($btnTitle, '', $btnIcon, $urlToggleDisplayMod);
			}

			print_barre_liste($langs->trans('Invoices'), 0, $_SERVER["PHP_SELF"], '', '', '', '', $num, $totalnboflines, 'bill', 0, $moreHtmlRight, '', 0, 0, 0, 1);

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table id="customer-invoices-paiments-list" class="noborder centpercent" data-display-all-invoices="' . (int) $displayAllInvoices . '" >';
			print '<thead>';

			print '<tr class="liste_titre">';
			print '<td>'.$arraytitle.'</td>';
			if ($displayAllInvoices) {
				print '<td>' . $langs->trans('Type') . '</td>';
			}
			print '<td class="center">'.$langs->trans('Date').'</td>';
			print '<td class="center">'.$langs->trans('DateMaxPayment').'</td>';
			if (isModEnabled('multicurrency')) {
				print '<td>'.$langs->trans('Currency').'</td>';
				print '<td class="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
				print '<td class="right">'.$multicurrencyalreadypayedlabel.'</td>';
				print '<td class="right">'.$multicurrencyremaindertopay.'</td>';
				print '<td class="right">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
			}
			print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
			print '<td class="right">'.$alreadypayedlabel.'</td>';
			print '<td class="right">'.$remaindertopay.'</td>';
			print '<td class="right">'.$langs->trans('PaymentAmount').'</td>';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $facture, $action); // Note that $action and $object may have been modified by hook

			print '<td align="right">&nbsp;</td>';
			print "</tr>\n";
			print '</thead>';
			$total_ttc = 0;
			$totalrecu = 0;
			$totalrecucreditnote = 0;
			$totalrecudeposits = 0;
			$sign = 1;

			print '<tbody>';
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$sign = 1;
				if ($facture->type == Facture::TYPE_CREDIT_NOTE && !$displayAllInvoices) {
					$sign = -1;
				}

				$soc = new Societe($db);
				$soc->fetch($objp->socid);

				$invoice = new Facture($db);
				$invoice->fetch($objp->facid);
				$paiement = $invoice->getSommePaiement();
				$creditnotes = $invoice->getSumCreditNotesUsed();
				$deposits = $invoice->getSumDepositsUsed();
				$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
				$remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

				// Multicurrency Price
				$tooltiponmulticurrencyfullamount = '';
				$multicurrency_remaintopay = '';
				$multicurrency_payment = 0;
				$multicurrency_creditnotes = 0;
				$multicurrency_deposits = 0;
				if (isModEnabled('multicurrency')) {
					$multicurrency_payment = $invoice->getSommePaiement(1);
					$multicurrency_creditnotes = $invoice->getSumCreditNotesUsed(1);
					$multicurrency_deposits = $invoice->getSumDepositsUsed(1);
					$multicurrency_alreadypayed = price2num($multicurrency_payment + $multicurrency_creditnotes + $multicurrency_deposits, 'MT');
					$multicurrency_remaintopay = price2num($invoice->multicurrency_total_ttc - $multicurrency_payment - $multicurrency_creditnotes - $multicurrency_deposits, 'MT');
					// Multicurrency full amount tooltip
					$tooltiponmulticurrencyfullamount = $langs->trans('AmountHT') . ": " . price($objp->multicurrency_total_ht, 0, $langs, 0, -1, -1, $objp->multicurrency_code) . "<br>";
					$tooltiponmulticurrencyfullamount .= $langs->trans('AmountVAT') . ": " . price($objp->multicurrency_total_tva, 0, $langs, 0, -1, -1, $objp->multicurrency_code) . "<br>";
					$tooltiponmulticurrencyfullamount .= $langs->trans('AmountTTC') . ": " . price($objp->multicurrency_total_ttc, 0, $langs, 0, -1, -1, $objp->multicurrency_code) . "<br>";
				}

				// Full amount tooltip
				$tooltiponfullamount = $langs->trans('AmountHT') . ": " . price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . "<br>";
				$tooltiponfullamount .= $langs->trans('AmountVAT') . ": " . price($objp->total_tva, 0, $langs, 0, -1, -1, $conf->currency) . "<br>";
				$tooltiponfullamount .= $langs->trans('AmountTTC') . ": " . price($objp->total_ttc, 0, $langs, 0, -1, -1, $conf->currency) . "<br>";

				print '<tr data-row-type="'.$objp->type.'" class="oddeven'.(($invoice->id == $facid) ? ' highlight' : '').'">';

				print '<td class="nowraponall">';
				print $invoice->getNomUrl(1, '');
				if ($objp->socid != $facture->thirdparty->id) {
					print ' - '.$soc->getNomUrl(1).' ';
				}
				print "</td>\n";

				// type
				if ($displayAllInvoices) {
					$typearray = [
						Facture::TYPE_STANDARD => $langs->trans("InvoiceStandard"),
						Facture::TYPE_REPLACEMENT => $langs->trans("InvoiceReplacement"),
						Facture::TYPE_CREDIT_NOTE => $langs->trans("InvoiceAvoir"),
						Facture::TYPE_DEPOSIT => $langs->trans("InvoiceDeposit"),
					];
					// Primary Secondary Success Danger Warning Info Light Dark status0 status1 status2 status3 status4 status5 status6 status7 status8 status9
					print '<td class="center nowraponall">' . $typearray[$objp->type] . '</td>';
				}

				// Date
				print '<td class="center">'.dol_print_date($db->jdate($objp->df), 'day')."</td>\n";

				// Due date
				if ($objp->dlr > 0) {
					print '<td class="nowraponall center">';
					print dol_print_date($db->jdate($objp->dlr), 'day');

					if ($invoice->hasDelay()) {
						print img_warning($langs->trans('Late'));
					}

					print '</td>';
				} else {
					print '<td align="center"></td>';
				}

				// Currency
				if (isModEnabled('multicurrency')) {
					print '<td class="center">'.$objp->multicurrency_code."</td>\n";
				}

				// Multicurrency full amount
				if (isModEnabled('multicurrency')) {
					print '<td class="right">';
					if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
						print '<span class="amount classfortooltip" title="'.$tooltiponmulticurrencyfullamount.'">' . price($sign * $objp->multicurrency_total_ttc);
					}
					print '</span></td>';

					// Multicurrency Price
					print '<td class="right">';
					if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
						print price($sign * $multicurrency_payment);
						if ($multicurrency_creditnotes) {
							print '+'.price($multicurrency_creditnotes);
						}
						if ($multicurrency_deposits) {
							print '+'.price($multicurrency_deposits);
						}
					}
					print '</td>';

					// Multicurrency remain to pay
					print '<td class="right">';
					if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
						print price($sign * (float) $multicurrency_remaintopay);
					}
					print '</td>';

					print '<td class="right nowraponall">';

					// Add remind multicurrency amount
					$namef = 'multicurrency_amount_'.$objp->facid;
					$nameRemain = 'multicurrency_remain_'.$objp->facid;
					$min = $max = '';
					if ($displayAllInvoices) {
						if ($objp->type == Facture::TYPE_CREDIT_NOTE) {
							$max = ' max="0" ';
						} else {
							$min = ' min="0" ';
						}
					}

					if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
						if ($action != 'add_paiement') {
							if (!empty($conf->use_javascript_ajax)) {
								print '<button class="btn-low-emphasis --btn-icon AutoFillAmount" data-rowname="'.$namef.'" data-value="'.($sign * (float) $multicurrency_remaintopay).'">'.img_picto("Auto fill", 'rightarrow');
							}
							print '<input '.$min.' '.$max.' type="text" class="multicurrency_amount" name="'.$namef.'" value="'.GETPOST($namef).'">';
							print '<input type="hidden" class="multicurrency_remain" name="'.$nameRemain.'" value="'.$multicurrency_remaintopay.'">';
						} else {
							print '<input type="text" class="maxwidth75" name="'.$namef.'_disabled" value="'.(GETPOST($namef) != '0' ? GETPOST($namef) : '').'" disabled>';
							print '<input type="hidden" name="'.$namef.'" value="'.GETPOST($namef).'">';
						}
					}
					print "</td>";
				}

				// Full amount
				print '<td class="right"><span class="amount classfortooltip" title="'.$tooltiponfullamount.'">'.price($sign * $objp->total_ttc).'</span></td>';

				// Received + already paid
				print '<td class="right"><span class="amount">'.price($sign * $paiement);
				if ($creditnotes) {
					print '<span class="opacitymedium">+'.price($creditnotes).'</span>';
				}
				if ($deposits) {
					print '<span class="opacitymedium">+'.price($deposits).'</span>';
				}
				print '</span></td>';

				// Remain to take or to pay back
				print '<td class="right">';
				print price($sign * (float) $remaintopay);
				if (isModEnabled('prelevement')) {
					$numdirectdebitopen = 0;
					$totaldirectdebit = 0;
					$sql = "SELECT COUNT(pfd.rowid) as nb, SUM(pfd.amount) as amount";
					$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
					$sql .= " WHERE fk_facture = ".((int) $objp->facid);
					$sql .= " AND pfd.traite = 0";
					$sql .= " AND pfd.ext_payment_id IS NULL";

					$result_sql = $db->query($sql);
					if ($result_sql) {
						$obj = $db->fetch_object($result_sql);
						$numdirectdebitopen = $obj->nb;
						$totaldirectdebit = $obj->amount;
					} else {
						dol_print_error($db);
					}
					if ($numdirectdebitopen) {
						$langs->load("withdrawals");
						print img_warning($langs->trans("WarningSomeDirectDebitOrdersAlreadyExists", $numdirectdebitopen, price(price2num($totaldirectdebit, 'MT'), 0, $langs, 1, -1, -1, $conf->currency)), '', 'classfortooltip');
					}
				}
				print '</td>';
				//$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

				// Amount
				print '<td class="right nowraponall">';

				// Add remind amount
				$namef = 'amount_'.$objp->facid;
				$nameRemain = 'remain_'.$objp->facid;

				$min = $max = '';
				if ($displayAllInvoices) {
					if ($objp->type == Facture::TYPE_CREDIT_NOTE) {
						$max = ' max="0" ';
					} else {
						$min = ' min="0" ';
					}
				}

				if ($action != 'add_paiement') {
					if (!empty($conf->use_javascript_ajax)) {
						print '<button  class="btn-low-emphasis --btn-icon AutoFillAmount" data-rowname="'.$namef.'" data-value="'.($sign * (float) $remaintopay).'">'.img_picto("Auto fill", 'rightarrow').'</button>';
					}
					print '<input '.$max.' '.$min.' type="text" size="8" class="amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">'; // class is required to be used by javascript callForResult();
					print '<input type="hidden" class="remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
				} else {
					print '<input type="text" class="maxwidth75" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
					print '<input type="hidden" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
				}
				print "</td>";

				$parameters = array();
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action); // Note that $action and $object may have been modified by hook

				// Warning
				print '<td align="center" width="16">';
				//print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
				if (!empty($amounts[$invoice->id]) && (abs((float) $amounts[$invoice->id]) > abs((float) $amountsresttopay[$invoice->id]))
					|| !empty($multicurrency_amounts[$invoice->id]) && (abs((float) $multicurrency_amounts[$invoice->id]) > abs((float) $multicurrency_amountsresttopay[$invoice->id]))) {
					print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
				}
				print '</td>';

				print "</tr>\n";

				$total_ttc += $objp->total_ttc;
				$totalrecu += $paiement;
				$totalrecucreditnote += $creditnotes;
				$totalrecudeposits += $deposits;
				$i++;
			}
			print '</tbody>';

			if ($i > 1) {
				$colspan = 3;

				// type
				if ($displayAllInvoices) {
					$colspan++;
				}

				// Print total

				print '<tfoot>';
				print '<tr class="liste_total">';
				print '<td colspan="'.$colspan.'" class="left">'.$langs->trans('TotalTTC').'</td>';
				if (isModEnabled('multicurrency')) {
					print '<td></td>';
					print '<td></td>';
					print '<td></td>';
					print '<td></td>';
					print '<td class="right" id="multicurrency_result" style="font-weight: bold;"></td>';
				}
				print '<td class="right"><b>'.price($sign * $total_ttc).'</b></td>';
				print '<td class="right"><b>'.price($sign * $totalrecu);
				if ($totalrecucreditnote) {
					print '+'.price($totalrecucreditnote);
				}
				if ($totalrecudeposits) {
					print '+'.price($totalrecudeposits);
				}
				print '</b></td>';
				print '<td class="right"><b>'.price($sign * (float) price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits, 'MT')).'</b></td>';
				print '<td class="right" id="result" style="font-weight: bold;"></td>'; // Autofilled
				print '<td align="center">&nbsp;</td>';
				print "</tr>\n";
				print '</tfoot>';
			}
			print "</table>";
			print "</div>\n";
		}
		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	$formconfirm = '';

	// Save button
	if ($action != 'add_paiement') {
		$checkboxlabel = $langs->trans("ClosePaidInvoicesAutomatically");
		if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
			$checkboxlabel = $langs->trans("ClosePaidCreditNotesAutomatically");
		}
		$buttontitle = $langs->trans('ToMakePayment');
		if ($facture->type == Facture::TYPE_CREDIT_NOTE) {
			$buttontitle = $langs->trans('ToMakePaymentBack');
		}

		print '<br><div class="center">';
		print '<input type="checkbox" checked name="closepaidinvoices" id="closepaidinvoices"><label for="closepaidinvoices"> '.$checkboxlabel.'</label>';
		/*if (isModEnabled('prelevement')) {
			$langs->load("withdrawals");
			if (getDolGlobalString('WITHDRAW_DISABLE_AUTOCREATE_ONPAYMENTS')) {
				print '<br>'.$langs->trans("IfInvoiceNeedOnWithdrawPaymentWontBeClosed");
			}
		}*/
		print '<br><input type="submit" class="button reposition" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
		print '</div>';
	}

	// Form to confirm payment
	if ($action == 'add_paiement') {
		$preselectedchoice = $addwarning ? 'no' : 'yes';

		print '<br>';
		$text = '';
		if (!empty($totalpayment)) {
			$text = $langs->trans('ConfirmCustomerPayment', (string) $totalpayment, $langs->transnoentitiesnoconv("Currency".$conf->currency));
		}
		if (!empty($multicurrency_totalpayment)) {
			$text .= '<br>'.$langs->trans('ConfirmCustomerPayment', (string) $multicurrency_totalpayment, $langs->transnoentitiesnoconv("paymentInInvoiceCurrency"));
		}
		if (GETPOST('closepaidinvoices')) {
			$text .= '<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
			print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
		}
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type, $langs->trans('ReceivedCustomersPayments'), $text, 'confirm_paiement', $formquestion, $preselectedchoice);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	print "</form>\n";
}


llxFooter();

$db->close();
