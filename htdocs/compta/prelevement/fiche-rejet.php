<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2025 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024      Frédéric France      <frederic.france@free.fr>
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
 * 		\file       htdocs/compta/prelevement/fiche-rejet.php
 *      \ingroup    prelevement
 *		\brief      Debit order or credit transfer reject
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("banks", "categories", "companies", 'withdrawals', 'bills'));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}

// Get supervariables
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

$type = GETPOST('type', 'aZ09');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$object = new BonPrelevement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals

// Check if salary or invoice
$salaryBonPl = $object->checkIfSalaryBonPrelevement();

// Security check
if ($user->socid > 0) {
	accessforbidden();
}

$type = $object->type;
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}


/*
 * View
 */

$form = new Form($db);

$thirdpartystatic = new Societe($db);
$invoicestatic = new Facture($db);
$invoicesupplierstatic = new FactureFournisseur($db);
$rej = new RejetPrelevement($db, $user, $type);


llxHeader('', $langs->trans("WithdrawalsReceipts"));

if ($id > 0 || $ref) {
	if ($object->fetch($id, $ref) >= 0) {
		$head = prelevement_prepare_head($object);
		print dol_get_fiche_head($head, 'rejects', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/orders_list.php?restore_lastsearch_values=1'.($object->type != 'bank-transfer' ? '' : '&type=bank-transfer').'">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">'."\n";

		//print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec, 'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($object->amount).'</span></td></tr>';

		if (!empty($object->date_trans)) {
			$muser = new User($db);
			$muser->fetch($object->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($object->date_trans, 'day');
			print ' &nbsp; <span class="opacitymedium">'.$langs->trans("By").'</span> '.$muser->getNomUrl(-1).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $object->methodes_trans[$object->method_trans];
			print '</td></tr>';
		}
		if (!empty($object->date_credit)) {
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($object->date_credit, 'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Get bank account for the payment
		$acc = new Account($db);
		$fk_bank_account = $object->fk_bank_account;
		if (empty($fk_bank_account)) {
			$fk_bank_account = ($object->type == 'bank-transfer' ? getDolGlobalInt('PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT') : getDolGlobalInt('PRELEVEMENT_ID_BANKACCOUNT'));
		}
		if ($fk_bank_account > 0) {
			$result = $acc->fetch($fk_bank_account);
		}

		$labelofbankfield = "BankToReceiveWithdraw";
		if ($object->type == 'bank-transfer') {
			$labelofbankfield = 'BankToPayCreditTransfer';
		}

		print '<tr><td class="titlefieldcreate">';
		print $form->textwithpicto($langs->trans("BankAccount"), $langs->trans($labelofbankfield));
		print '</td>';
		print '<td>';
		if ($acc->id > 0) {
			print $acc->getNomUrl(1);
		}
		print '</td>';
		print '</tr>';

		$modulepart = 'prelevement';
		if ($object->type == 'bank-transfer') {
			$modulepart = 'paymentbybanktransfer';
		}

		print '<tr><td class="titlefieldcreate">';
		$labelfororderfield = 'WithdrawalFile';
		if ($object->type == 'bank-transfer') {
			$labelfororderfield = 'CreditTransferFile';
		}
		print $langs->trans($labelfororderfield).'</td><td>';

		if (isModEnabled('multicompany')) {
			$labelentity = $conf->entity;
			$relativepath = 'receipts/'.$object->ref.'-'.$labelentity.'.xml';

			if ($type != 'bank-transfer') {
				$dir = $conf->prelevement->dir_output;
			} else {
				$dir = $conf->paymentbybanktransfer->dir_output;
			}
			if (!dol_is_file($dir.'/'.$relativepath)) {	// For backward compatibility
				$relativepath = 'receipts/'.$object->ref.'.xml';
			}
		} else {
			$relativepath = 'receipts/'.$object->ref.'.xml';
		}

		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">'.$relativepath;
		print img_picto('', 'download', 'class="paddingleft"');
		print '</a>';
		print '</td></tr></table>';

		print '</div>';

		print dol_get_fiche_end();
	} else {
		dol_print_error($db);
	}
}


// List errors

$sql = "SELECT pl.rowid, pl.amount, pl.statut";
$sql .= " , s.rowid as socid, s.nom as name";
$sql .= " , pr.motif, pr.afacturer, pr.fk_facture";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " WHERE p.rowid=".((int) $object->id);
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND p.entity IN (".getEntity('facture').")";
$sql .= " AND pl.fk_soc = s.rowid";
$sql .= " AND pl.statut = 3";
$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";
$sql .= " ORDER BY pl.amount DESC";

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("Rejects"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');

	$param = '&id='.((int) $object->id);

	print"\n<!-- debut table -->\n";
	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Line", $_SERVER["PHP_SELF"], "pl.rowid", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre((!$salaryBonPl ? "ThirdParty" : "Employee"), $_SERVER["PHP_SELF"], "s.nom", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "pl.amount", '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Reason", $_SERVER["PHP_SELF"], "pr.motif", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ToBill", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("");
	// Invoice to charge the error. No yet implemented.
	//print $langs->trans("Invoice");
	print '</tr>';

	$total = 0;

	if ($num > 0) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$thirdpartystatic->id = $obj->socid;
			$thirdpartystatic->name = $obj->name;

			if ($obj->fk_facture > 0) {
				$invoicestatic->fetch($obj->fk_facture);
			}

			print '<tr class="oddeven">';
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/line.php?id='.$obj->rowid.'">';
			print img_picto('', 'statut'.$obj->statut).' ';
			print substr('000000'.$obj->rowid, -6);
			print '</a></td>';
			print '<td>';
			if ($type != 'bank-transfer') {
				print $thirdpartystatic->getNomUrl(1, 'customer');
			} else {
				print $thirdpartystatic->getNomUrl(1, 'supplier');
			}
			print '</td>'."\n";

			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";
			print '<td>'.dol_escape_htmltag($rej->motifs[$obj->motif]).'</td>';

			print '<td class="center">'.yn($obj->afacturer).'</td>';

			// Invoice used to charge the error
			print '<td class="center">';
			if ($obj->fk_facture > 0) {
				print $invoicestatic->getNomUrl(1);
			}
			print '</td>';

			print "</tr>\n";

			$total += $obj->amount;

			$i++;
		}
	} else {
		print '<tr><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	if ($num > 0) {
		print '<tr class="liste_total"><td>&nbsp;</td>';
		print '<td class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td class="right"><span class="amount">'.price($total)."</span></td>\n";
		print '<td colspan="3">&nbsp;</td>';
		print "</tr>\n";
	}
	print "</table>\n";
	print '</div>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
