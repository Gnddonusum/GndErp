<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/compta/paiement_charge.php
 *      \ingroup    tax
 *      \brief      Page to add payment of a tax
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills", "compta"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel');

$chid = GETPOSTINT("id");
$amounts = array();

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$charge = new ChargeSociales($db);


/*
 * Actions
 */

if (($action == 'add_payment' || ($action == 'confirm_paiement' && $confirm == 'yes')) && $user->hasRight('tax', 'charges', 'creer')) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/compta/sociales/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$datepaye = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));

	if (!(GETPOST("paiementtype") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if ($datepaye == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if (isModEnabled("bank") && !(GETPOST("accountid") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit")), null, 'errors');
		$error++;
		$action = 'create';
	}

	if (!$error) {
		$paymentid = 0;

		// Read possible payments
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 7) == 'amount_') {
				$other_chid = substr($key, 7);
				$amounts[$other_chid] = (float) price2num(GETPOST($key));
			}
		}

		if (empty($amounts)) {
			$error++;
			setEventMessages($langs->trans("ErrorNoPaymentDefined"), null, 'errors');
			$action = 'create';
		}

		if (!$error) {
			$db->begin();

			// Create a line of payments
			$paiement = new PaymentSocialContribution($db);
			$paiement->chid         = $chid;
			$paiement->datepaye     = $datepaye;
			$paiement->amounts      = $amounts; // Amount list
			$paiement->paiementtype = GETPOST("paiementtype", 'alphanohtml');
			$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$paiement->note         = GETPOST("note", 'restricthtml');
			$paiement->note_private = GETPOST("note", 'restricthtml');

			if (!$error) {
				$paymentid = $paiement->create($user, (GETPOST('closepaidcontrib') == 'on' ? 1 : 0));
				if ($paymentid < 0) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$result = $paiement->addPaymentToBank($user, 'payment_sc', '(SocialContributionPayment)', GETPOSTINT('accountid'), '', '');
				if ($result <= 0) {
					$error++;
					setEventMessages($paiement->error, $paiement->errors, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/compta/sociales/card.php?id='.$chid;
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);


// Form of charge payment creation
if ($action == 'create') {
	$charge->fetch($chid);
	$charge->accountid = $charge->fk_account ? $charge->fk_account : $charge->accountid;
	$charge->paiementtype = $charge->mode_reglement_id ? $charge->mode_reglement_id : $charge->paiementtype;

	$total = $charge->amount;
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';

		//Add js for AutoFill
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmount").on(\'click touchstart\', function() {
						console.log("Click on .AutoFillAmount");
                        var amount = $(this).data("value");
						document.getElementById($(this).data(\'rowid\')).value = amount ;
					});';
		print '	});'."\n";

		print '	</script>'."\n";
	}

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head([], '');

	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td><a href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$charge->label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Type")."</td><td>".$charge->type_label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Period")."</td><td>".dol_print_date($charge->period, 'day')."</td></tr>\n";
	/*print '<tr><td>'.$langs->trans("DateDue")."</td><td>".dol_print_date($charge->date_ech,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount")."</td><td>".price($charge->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	$sql = "SELECT sum(p.amount) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
	$sql .= " WHERE p.fk_charge = ".((int) $chid);
	$sumpaid = 0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free($resql);
	}
	/*print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td>'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td>'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$datepaye = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
	$datepayment = !getDolGlobalString('MAIN_AUTOFILL_DATE') ? (GETPOSTISSET("remonth") ? $datepaye : -1) : '';
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_payment", 1, 1, 0, '', '', $charge->date_ech, '', 1, $langs->trans("DateOfSocialContribution"));
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	print img_picto('', 'bank', 'class="pictofixedwidth"');
	print $form->select_types_paiements(GETPOSTISSET("paiementtype") ? GETPOST("paiementtype") : $charge->paiementtype, "paiementtype", '', 0, 1, 0, 0, 1, 'maxwidth500 widthcentpercentminusxx', 1);
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
	print '<td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	print $form->select_comptes(GETPOSTISSET("accountid") ? GETPOSTINT("accountid") : $charge->accountid, "accountid", 0, '', 2, '', 0, 'maxwidth500 widthcentpercentminusx', 1); // Show opened bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	if (empty($conf->dol_optimize_smallscreen)) {
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	}
	print '</td>';
	print '<td><input name="num_payment" class="width100" type="text" value="'.GETPOST('num_payment', 'alphanohtml').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea class="quatrevingtpercent" name="note" wrap="soft" rows="'.ROWS_3.'">'.GETPOST('note', 'alphanohtml').'</textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	/*
	  * Other unpaid charges
	 */
	$num = 1;
	$i = 0;

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	//print '<td>'.$langs->trans("SocialContribution").'</td>';
	print '<td class="left">'.$langs->trans("DateDue").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total = 0;
	$total_ttc = 0;
	$totalrecu = 0;

	while ($i < $num) {
		$objp = $charge;

		print '<tr class="oddeven">';

		if ($objp->date_ech > 0) {
			print '<td class="left">'.dol_print_date($objp->date_ech, 'day').'</td>'."\n";
		} else {
			print "<td align=\"center\"><b>!!!</b></td>\n";
		}

		print '<td class="right nowraponall"><span class="amount">'.price($objp->amount)."</span></td>";

		print '<td class="right nowraponall"><span class="amount">'.price($sumpaid)."</span></td>";

		print '<td class="right nowraponall"><span class="amount">'.price($objp->amount - $sumpaid)."</span></td>";

		print '<td class="center nowraponall">';
		if ($sumpaid < $objp->amount) {
			$namef = "amount_".$objp->id;
			$nameRemain = "remain_".$objp->id;
			if (!empty($conf->use_javascript_ajax)) {
				print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowid='".$namef."' data-value='".($objp->amount - $sumpaid)."'");
			}
			$remaintopay = $objp->amount - $sumpaid;
			print '<input type=hidden class="sum_remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
			print '<input type="text" size="8" name="'.$namef.'" id="'.$namef.'" value="'.GETPOST('amount_'.$objp->id, 'alpha').'">';
		} else {
			print '-';
		}
		print "</td>";

		print "</tr>\n";
		$total += $objp->total;
		$total_ttc += $objp->total_ttc;
		$totalrecu += $objp->amount;
		$i++;
	}
	if ($i > 1) {
		// Print total
		print '<tr class="oddeven">';
		print '<td colspan="2" class="left">'.$langs->trans("Total").':</td>';
		print '<td class="right"><b>'.price($total_ttc).'</b></td>';
		print '<td class="right"><b>'.price($totalrecu).'</b></td>';
		print '<td class="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";
	print '</div>';

	// Save payment button
	print '<br><div class="center"><input type="checkbox" checked name="closepaidcontrib" id="closepaidcontrib" class="marginrightonly">';
	print '<label for="closepaidcontrib">'.$langs->trans("ClosePaidContributionsAutomatically").'</span>';
	print '<br><input type="submit" class="button" name="save" value="'.$langs->trans('ToMakePayment').'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print "</form>\n";
}

llxFooter();
$db->close();
