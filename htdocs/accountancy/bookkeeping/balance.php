<?php
/* Copyright (C) 2016       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2016       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2025  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
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
 *  \file 		htdocs/accountancy/bookkeeping/balance.php
 *  \ingroup 	Accountancy (Double entries)
 *  \brief 		Balance of book keeping
 */

// Load Dolibarr environment
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "compta"));

$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optioncss', 'alpha');
$type = GETPOST('type', 'alpha');
if ($type == 'sub') {
	$context_default = 'balancesubaccountlist';
} else {
	$context_default = 'balancelist';
}
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $context_default;
$show_subgroup = GETPOST('show_subgroup', 'alpha');

$search_date_start = GETPOSTDATE('date_start', 'getpost', 'auto', 'search_date_start_accountancy');
$search_date_end = GETPOSTDATE('date_end', 'getpostend', 'auto', 'search_date_end_accountancy');

$search_ledger_code = GETPOST('search_ledger_code', 'array');
$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}
$search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") {
	$sortorder = "ASC";
}
if ($sortfield == "") {
	$sortfield = "t.numero_compte";
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new BookKeeping($db);
$hookmanager->initHooks(array($contextpage));  // Note that conf->hooks_modules contains array

$formaccounting = new FormAccounting($db);
$form = new Form($db);

if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('formfilteraction')) {
	$sql = "SELECT date_start, date_end";
	$sql .=" FROM ".MAIN_DB_PREFIX."accounting_fiscalyear ";
	if (getDolGlobalInt('ACCOUNTANCY_FISCALYEAR_DEFAULT')) {
		$sql .= " WHERE rowid = " . getDolGlobalInt('ACCOUNTANCY_FISCALYEAR_DEFAULT');
	} else {
		$sql .= " WHERE date_start < '" . $db->idate(dol_now()) . "' and date_end > '" . $db->idate(dol_now()) . "'";
	}
	$sql .= $db->plimit(1);
	$res = $db->query($sql);

	if ($db->num_rows($res) > 0) {
		$fiscalYear = $db->fetch_object($res);
		$search_date_start = strtotime($fiscalYear->date_start);
		$search_date_end = strtotime($fiscalYear->date_end);
	} else {
		$month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$year_start = (int) dol_print_date(dol_now(), '%Y');
		if (dol_print_date(dol_now(), '%m') < $month_start) {
			$year_start--; // If current month is lower that starting fiscal month, we start last year
		}
		$year_end = $year_start + 1;
		$month_end = $month_start - 1;
		if ($month_end < 1) {
			$month_end = 12;
			$year_end--;
		}
		$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
		$search_date_end = dol_get_last_day($year_end, $month_end);
	}
}

if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
	accessforbidden();
}

$permissiontoadd = $user->hasRight('accounting', 'mouvements', 'creer');


/*
 * Action
 */

$param = '';
$urlparam = '';
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$filter = array();

if (empty($reshook)) {
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$show_subgroup = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_accountancy_code_start = '';
		$search_accountancy_code_end = '';
		$search_not_reconciled = '';
		$search_ledger_code = array();
		unset($_SESSION['DOLDATE_search_date_start_accountancy_day']);
		unset($_SESSION['DOLDATE_search_date_start_accountancy_month']);
		unset($_SESSION['DOLDATE_search_date_start_accountancy_year']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_day']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_month']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_year']);
	}

	if (!empty($search_date_start)) {
		$filter['t.doc_date>='] = $search_date_start;
		$param .= '&date_startmonth=' . GETPOSTINT('date_startmonth') . '&date_startday=' . GETPOSTINT('date_startday') . '&date_startyear=' . GETPOSTINT('date_startyear');
	}
	if (!empty($search_date_end)) {
		$filter['t.doc_date<='] = $search_date_end;
		$param .= '&date_endmonth=' . GETPOSTINT('date_endmonth') . '&date_endday=' . GETPOSTINT('date_endday') . '&date_endyear=' . GETPOSTINT('date_endyear');
	}
	if (!empty($search_accountancy_code_start)) {
		if ($type == 'sub') {
			$filter['t.subledger_account>='] = $search_accountancy_code_start;
		} else {
			$filter['t.numero_compte>='] = $search_accountancy_code_start;
		}
		$param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
	}
	if (!empty($search_accountancy_code_end)) {
		if ($type == 'sub') {
			$filter['t.subledger_account<='] = $search_accountancy_code_end;
		} else {
			$filter['t.numero_compte<='] = $search_accountancy_code_end;
		}
		$param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
	}
	if (!empty($search_ledger_code)) {
		$filter['t.code_journal'] = $search_ledger_code;
		foreach ($search_ledger_code as $code) {
			$param .= '&search_ledger_code[]=' . urlencode($code);
		}
	}
	if (!empty($search_not_reconciled)) {
		$filter['t.reconciled_option'] = $search_not_reconciled;
		$param .= '&search_not_reconciled='.urlencode($search_not_reconciled);
	}
	if (!empty($show_subgroup)) {
		$param .= '&show_subgroup='.urlencode($show_subgroup);
	}

	// param with type of list
	$url_param = substr($param, 1); // remove first "&"
	if (!empty($type)) {
		$param = '&type=' . $type . $param;
	}
}

if ($action == 'export' && $user->hasRight('accounting', 'mouvements', 'lire')) {
	$exportType = GETPOST('export_type');

	if ($type == 'sub') {
		$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, 0, $filter, 'AND', 1);
	} else {
		$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, 0, $filter);
	}
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($exportType === 'csv') {
		$sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');
		$filename = 'balance';
		$type_export = 'balance';
		include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';

		foreach ($object->lines as $line) {
			if ($type == 'sub') {
				print '"' . length_accounta($line->subledger_account) . '"' . $sep;
				print '"' . $line->subledger_label . '"' . $sep;
			} else {
				print '"' . length_accountg($line->numero_compte) . '"' . $sep;
				print '"' . $object->get_compte_desc($line->numero_compte) . '"' . $sep;
			}
			print '"'.price($line->debit).'"'.$sep;
			print '"'.price($line->credit).'"'.$sep;
			print '"'.price($line->debit - $line->credit).'"'.$sep;
			print "\n";
		}
		exit;
	} else {
		require_once DOL_DOCUMENT_ROOT . '/core/modules/accountancy/doc/pdf_balance.modules.php';
		$pdf = new pdf_balance($db);
		$pdf->fromDate = dol_mktime(12, 0, 0, GETPOSTINT('search_date_startmonth'), GETPOSTINT('search_date_startday'), GETPOSTINT('search_date_startyear'));
		if (empty($pdf->fromDate)) {
			$pdf->fromDate = dol_mktime(12, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
		}
		$pdf->toDate = dol_mktime(12, 0, 0, GETPOSTINT('search_date_endmonth'), GETPOSTINT('search_date_endday'), GETPOSTINT('search_date_endyear'));
		if (empty($pdf->toDate)) {
			$pdf->toDate = dol_mktime(12, 0, 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));
		}
		$pdf->balanceType = $type;

		$result = $pdf->write_file($object, $langs);

		if ($result < 0) {
			setEventMessage($pdf->error, "errors");
		} else {
			// Generated PDF is directly sent to the browser
			exit;
		}
	}
}


/*
 * View
 */

if ($type == 'sub') {
	$title_page = $langs->trans("AccountBalanceSubAccount");
} else {
	$title_page = $langs->trans("AccountBalance");
}

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';

llxHeader('', $title_page, $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-consultation page-'.(($type == 'sub') ? 'sub' : '').'balance');


if ($action != 'export') {
	// List
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		if ($type == 'sub') {
			$nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter, 'AND', 1);
		} else {
			$nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter);
		}

		if ($nbtotalofrecords < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($type == 'sub') {
		$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);
	} else {
		$result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter);
	}

	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" id="action" value="list">';
	print '<input type="hidden" name="export_type" id="export_type" value="">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	$url_param = '';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

	if (empty($reshook)) {
		$newcardbutton = '<input type="button" id="exportcsvbutton" name="exportcsvbutton" class="butAction" value="'.$langs->trans("Export").' (' . getDolGlobalString('ACCOUNTING_EXPORT_FORMAT').')" />';

		print '<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#exportcsvbutton, #exportpdfbutton").click(function(event) {
				event.preventDefault();
				const exportType = this.id === "exportcsvbutton" ? "csv" : "pdf";
				console.log("Set action to export, export_type to " + exportType);
				jQuery("#action").val("export");
				jQuery("#export_type").val(exportType);
				jQuery("#searchFormList").submit();
				jQuery("#action").val("list");
			});
		});
		</script>';

		if ($type == 'sub') {
			$newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance')." - ".$langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
			$newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance')." - ".$langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?type=sub' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
		} else {
			$newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance')." - ".$langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
			$newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance')." - ".$langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?type=sub' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
		}

		$newcardbutton .= dolGetButtonTitle($langs->trans('ExportToPdf'), '', 'fa fa-file-pdf paddingleft', $_SERVER['PHP_SELF'], 'exportpdfbutton', 1, array('morecss' => 'marginleftonly'));

		$newcardbutton .= dolGetButtonTitleSeparator();
		$newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?action=create'.(!empty($type)?'&type=sub':'').'&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);
	}
	if ($contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}

	print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$selectedfields = '';

	// Warning to explain why list of record is not consistent with the other list view (missing a lot of lines)
	if ($type == 'sub') {
		print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));
	}

	$moreforfilter = '';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateStart').': ';
	$moreforfilter .= $form->selectDate($search_date_start ? $search_date_start : -1, 'date_start', 0, 0, 1, '', 1, 0);
	$moreforfilter .= $langs->trans('DateEnd').': ';
	$moreforfilter .= $form->selectDate($search_date_end ? $search_date_end : -1, 'date_end', 0, 0, 1, '', 1, 0);
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<label for="show_subgroup">'.$langs->trans('ShowSubtotalByGroup').'</label>: ';
	$moreforfilter .= '<input type="checkbox" name="show_subgroup" id="show_subgroup" value="show_subgroup"'.($show_subgroup == 'show_subgroup' ? ' checked' : '').'>';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans("Journals").': ';
	$moreforfilter .= $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1);
	$moreforfilter .= '</div>';

	//$moreforfilter .= '<br>';
	$moreforfilter .= '<div class="divsearchfield">';
	// Accountancy account
	$moreforfilter .= $langs->trans('AccountAccounting').': ';
	if ($type == 'sub') {
		if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
			$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), 'maxwidth200');
		} else {
			$moreforfilter .= '<input type="text" class="maxwidth150" name="search_accountancy_code_start" value="'.dol_escape_htmltag($search_accountancy_code_start).'" placeholder="'.$langs->trans('From').'">';
		}
	} else {
		$moreforfilter .= $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth200', 'accounts');
	}
	$moreforfilter .= ' ';
	if ($type == 'sub') {
		if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
			$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), 'maxwidth200');
		} else {
			$moreforfilter .= '<input type="text" class="maxwidth150" name="search_accountancy_code_end" value="'.dol_escape_htmltag($search_accountancy_code_end).'" placeholder="'.$langs->trans('to').'">';
		}
	} else {
		$moreforfilter .= $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth200', 'accounts');
	}
	$moreforfilter .= '</div>';

	if (getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= '<label for="notreconciled">'.$langs->trans('NotReconciled').'</label>: ';
		$moreforfilter .= '<input type="checkbox" name="search_not_reconciled" id="notreconciled" value="notreconciled"'.($search_not_reconciled == 'notreconciled' ? ' checked' : '').'>';
		$moreforfilter .= '</div>';
	}

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';


	$colspan = (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE') ? 5 : 4);

	print '<table class="liste '.($moreforfilter ? "listwithfilterbefore" : "").'">';

	print '<tr class="liste_titre_filter">';

	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print '<td class="liste_titre" colspan="'.$colspan.'">';
	print '</td>';

	// Fields from hook
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print '</tr>'."\n";

	print '<tr class="liste_titre">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	}
	print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
	// TODO : Retrieve the type of third party: Customer / Supplier / Employee
	//if ($type == 'sub') {
	//	print_liste_field_titre("Type", $_SERVER['PHP_SELF'], "t.type", "", $param, "", $sortfield, $sortorder);
	//}
	if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
		print_liste_field_titre("OpeningBalance", $_SERVER['PHP_SELF'], "", $param, "", 'class="right"', $sortfield, $sortorder);
	}
	print_liste_field_titre("AccountingDebit", $_SERVER['PHP_SELF'], "t.debit", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("AccountingCredit", $_SERVER['PHP_SELF'], "t.credit", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Balance", $_SERVER["PHP_SELF"], "", $param, "", 'class="right"', $sortfield, $sortorder);

	// Hook fields
	$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	}
	print '</tr>'."\n";

	$total_debit = 0;
	$total_credit = 0;
	$sous_total_debit = 0;
	$sous_total_credit = 0;
	$total_opening_balance = 0;
	$sous_total_opening_balance = 0;
	$displayed_account = "";

	$accountingaccountstatic = new AccountingAccount($db);

	// TODO Debug - This feature is dangerous, it takes all the entries and adds all the accounts
	// without time and class limits (Class 6 and 7 accounts ???) and does not take into account the "a-nouveau" journal.
	if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
		$sql = "SELECT t.numero_compte, (SUM(t.debit) - SUM(t.credit)) as opening_balance";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as t";
		$sql .= " WHERE t.entity = " . $conf->entity;        // Never do sharing into accounting features
		$sql .= " AND t.doc_date < '" . $db->idate($search_date_start) . "'";
		$sql .= " GROUP BY t.numero_compte";

		$resql = $db->query($sql);
		$opening_balances = array();
		if ($resql) {
			$nrows = $db->num_rows($resql);
			for ($i = 0; $i < $nrows; $i++) {
				$arr = $db->fetch_array($resql);
				if (is_array($arr)) {
					$opening_balances["'" . $arr['numero_compte'] . "'"] = $arr['opening_balance'];
				}
			}
		} else {
			dol_print_error($db);
		}
	}

	foreach ($object->lines as $line) {
		// reset before the fetch (in case of the fetch fails)
		$accountingaccountstatic->id = 0;
		$accountingaccountstatic->account_number = '';
		$accounting_account = '';

		if ($type != 'sub') {
			$accountingaccountstatic->fetch(0, $line->numero_compte, true);
			if (!empty($accountingaccountstatic->account_number)) {
				$accounting_account = $accountingaccountstatic->getNomUrl(0, 1, 1);
			} else {
				$accounting_account = length_accountg($line->numero_compte);
			}
		}

		$link = '';
		$total_debit += $line->debit;
		$total_credit += $line->credit;
		$opening_balance = isset($opening_balances["'".$line->numero_compte."'"]) ? $opening_balances["'".$line->numero_compte."'"] : 0;
		$total_opening_balance += $opening_balance;

		$tmparrayforrootaccount = $object->getRootAccount($line->numero_compte);
		$root_account_description = $tmparrayforrootaccount['label'];
		$root_account_number = $tmparrayforrootaccount['account_number'];

		//var_dump($tmparrayforrootaccount);
		//var_dump($accounting_account);
		//var_dump($accountingaccountstatic);
		if (empty($accountingaccountstatic->label) && $accountingaccountstatic->id > 0) {
			$link = '<a class="editfielda reposition" href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=update&token=' . newToken() . '&id=' . $accountingaccountstatic->id . '">' . img_edit() . '</a>';
		} elseif ($accounting_account == 'NotDefined') {
			$link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=create&token=' . newToken() . '&accountingaccount=' . length_accountg($line->numero_compte) . '">' . img_edit_add() . '</a>';
		} /* elseif (empty($tmparrayforrootaccount['label'])) {
			// $tmparrayforrootaccount['label'] not defined = the account has not parent with a parent.
			// This is useless, we should not create a new account when an account has no parent, we must edit it to fix its parent.
			// BUG 1: Accounts on level root or level 1 must not have a parent 2 level higher, so should not show a link to create another account.
			// BUG 2: Adding a link to create a new accounting account here is useless because it is not add as parent of the orphelin.
			//$link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=create&token=' . newToken() . '&accountingaccount=' . length_accountg($line->numero_compte) . '">' . img_edit_add() . '</a>';
		} */

		if (!empty($show_subgroup)) {
			// Show accounting account
			if (empty($displayed_account) || $root_account_number != $displayed_account) {
				// Show subtotal per accounting account
				if ($displayed_account != "") {
					print '<tr class="liste_total">';
					print '<td class="right">'.$langs->trans("SubTotal").':</td>';
					if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '<td></td>';
					}
					if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
						print '<td class="right nowraponall amount">'.price($sous_total_opening_balance).'</td>';
					}
					print '<td class="right nowraponall amount">'.price($sous_total_debit).'</td>';
					print '<td class="right nowraponall amount">'.price($sous_total_credit).'</td>';
					if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
						print '<td class="right nowraponall amount">'.price(price2num($sous_total_opening_balance + $sous_total_debit - $sous_total_credit)).'</td>';
					} else {
						print '<td class="right nowraponall amount">'.price(price2num($sous_total_debit - $sous_total_credit)).'</td>';
					}
					if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print "<td></td>\n";
					}
					print '</tr>';
				}

				// Show first line of a break
				print '<tr class="trforbreak">';
				print '<td colspan="'.($colspan + 1).'" class="tdforbreak">'.$root_account_number.($root_account_description ? ' - '.$root_account_description : '').'</td>';
				print '</tr>';

				$displayed_account = $root_account_number;
				$sous_total_debit = 0;
				$sous_total_credit = 0;
				$sous_total_opening_balance = 0;
			}
		}

		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print $link;
			print '</td>';
		}

		// Accounting account
		if ($type == 'sub') {
			print '<td>'.$line->subledger_account.' <span class="opacitymedium">('.$line->subledger_label.')</span></td>';
		} else {
			print '<td>'.$accounting_account.'</td>';
		}

		// Type
		// TODO Retrieve the type of third party: Customer / Supplier / Employee
		//if ($type == 'sub') {
		//	print '<td></td>';
		//}

		if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
			print '<td class="right nowraponall amount">'.price(price2num($opening_balance, 'MT')).'</td>';
		}

		$urlzoom = '';
		if ($type == 'sub') {
			if ($line->subledger_account) {
				$urlzoom = DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&search_accountancy_code_start=' . urlencode($line->subledger_account) . '&search_accountancy_code_end=' . urlencode($line->subledger_account);
				if (GETPOSTISSET('date_startmonth')) {
					$urlzoom .= '&search_date_startmonth=' . GETPOSTINT('date_startmonth') . '&search_date_startday=' . GETPOSTINT('date_startday') . '&search_date_startyear=' . GETPOSTINT('date_startyear');
				}
				if (GETPOSTISSET('date_endmonth')) {
					$urlzoom .= '&search_date_endmonth=' . GETPOSTINT('date_endmonth') . '&search_date_endday=' . GETPOSTINT('date_endday') . '&search_date_endyear=' . GETPOSTINT('date_endyear');
				}
			}
		} else {
			if ($line->numero_compte) {
				$urlzoom = DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?search_accountancy_code_start=' . urlencode($line->numero_compte) . '&search_accountancy_code_end=' . urlencode($line->numero_compte);
				if (GETPOSTISSET('date_startmonth')) {
					$urlzoom .= '&search_date_startmonth=' . GETPOSTINT('date_startmonth') . '&search_date_startday=' . GETPOSTINT('date_startday') . '&search_date_startyear=' . GETPOSTINT('date_startyear');
				}
				if (GETPOSTISSET('date_endmonth')) {
					$urlzoom .= '&search_date_endmonth=' . GETPOSTINT('date_endmonth') . '&search_date_endday=' . GETPOSTINT('date_endday') . '&search_date_endyear=' . GETPOSTINT('date_endyear');
				}
			}
		}
		// Debit
		print '<td class="right nowraponall amount"><a href="'.$urlzoom.'">'.price(price2num($line->debit, 'MT')).'</a></td>';
		// Credit
		print '<td class="right nowraponall amount"><a href="'.$urlzoom.'">'.price(price2num($line->credit, 'MT')).'</a></td>';

		if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
			print '<td class="right nowraponall amount">'.price(price2num($opening_balance + $line->debit - $line->credit, 'MT')).'</td>';
		} else {
			print '<td class="right nowraponall amount">'.price(price2num($line->debit - $line->credit, 'MT')).'</td>';
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print $link;
			print '</td>';
		}

		print "</tr>\n";

		// Records the sub-total
		$sous_total_debit += $line->debit;
		$sous_total_credit += $line->credit;
		$sous_total_opening_balance += $opening_balance;
	}

	if (!empty($show_subgroup)) {
		print '<tr class="liste_total">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print "<td></td>\n";
		}
		print '<td class="right">'.$langs->trans("SubTotal").':</td>';
		if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
			print '<td class="right nowraponall amount">'.price(price2num($sous_total_opening_balance, 'MT')).'</td>';
		}
		print '<td class="right nowraponall amount">'.price(price2num($sous_total_debit, 'MT')).'</td>';
		print '<td class="right nowraponall amount">'.price(price2num($sous_total_credit, 'MT')).'</td>';
		if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
			print '<td class="right nowraponall amount">' . price(price2num($sous_total_opening_balance + $sous_total_debit - $sous_total_credit, 'MT')) . '</td>';
		} else {
			print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit - $sous_total_credit, 'MT')) . '</td>';
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print "<td></td>\n";
		}
		print '</tr>';
	}

	print '<tr class="liste_total">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print "<td></td>\n";
	}
	print '<td class="right">'.$langs->trans("AccountBalance").':</td>';
	if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
		print '<td class="nowrap right">'.price(price2num($total_opening_balance, 'MT')).'</td>';
	}
	print '<td class="right nowraponall amount">'.price(price2num($total_debit, 'MT')).'</td>';
	print '<td class="right nowraponall amount">'.price(price2num($total_credit, 'MT')).'</td>';
	if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
		print '<td class="right nowraponall amount">' . price(price2num($total_opening_balance + $total_debit - $total_credit, 'MT')) . '</td>';
	} else {
		print '<td class="right nowraponall amount">' . price(price2num($total_debit - $total_credit, 'MT')) . '</td>';
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print "<td></td>\n";
	}
	print '</tr>';

	// Accounting result
	if (getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')) {
		print '<tr class="liste_total">';
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print "<td></td>\n";
		}
		print '<td class="right">' . $langs->trans("AccountingResult") . ':</td>';
		if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
			print '<td></td>';
		}

		$accountingResult = $object->accountingResult($search_date_start, $search_date_end);
		if ($accountingResult < 0) {
			$accountingResultDebit = price(abs((float) price2num($accountingResult, 'MT')));
			$accountingResultCredit = '';
			$accountingResultClassCSS = ' error';
		} else {
			$accountingResultDebit = '';
			$accountingResultCredit = price(price2num($accountingResult, 'MT'));
			$accountingResultClassCSS = ' green';
		}
		print '<td class="right nowraponall amount' . $accountingResultClassCSS . '">' . $accountingResultDebit . '</td>';
		print '<td class="right nowraponall amount' . $accountingResultClassCSS . '">' . $accountingResultCredit . '</td>';

		print '<td></td>';
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print "<td></td>\n";
		}
		print '</tr>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";
	print '</form>';
}

// End of page
llxFooter();
$db->close();
