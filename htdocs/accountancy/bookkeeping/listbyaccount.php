<?php
/* Copyright (C) 2016		Neil Orley				<neil.orley@oeris.fr>
 * Copyright (C) 2013-2016	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2020	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2025	Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2018-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Nicolas Barrouillet		<nicolas@pragma-tech.fr>
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
 * \file 		htdocs/accountancy/bookkeeping/listbyaccount.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		List operation of ledger ordered by account number
 */

// Load Dolibarr environment
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/lettering.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "compta"));

$journal_code = GETPOST('code_journal', 'alpha');
$account = GETPOST("account", 'int');
$massdate = dol_mktime(0, 0, 0, GETPOSTINT('massdatemonth'), GETPOSTINT('massdateday'), GETPOSTINT('massdateyear'));

$action = GETPOST('action', 'aZ09');
$socid = GETPOSTINT('socid');
$mode = (GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : 'customer'); // Only for tab view
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$type = GETPOST('type', 'alpha');
if ($type == 'sub') {
	$context_default = 'bookkeepingbysubaccountlist';
} else {
	$context_default = 'bookkeepingbyaccountlist';
}
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $context_default;

$search_doc_date = GETPOSTDATE('doc_date', 'getpost');	// deprecated. Can use 'search_date_start/end'

$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_start = GETPOSTDATE('search_date_start', 'getpost', 'auto', 'search_date_start_accountancy');

$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_end = GETPOSTDATE('search_date_end', 'getpostend', 'auto', 'search_date_end_accountancy');

$search_date_export_startyear = GETPOSTINT('search_date_export_startyear');
$search_date_export_startmonth = GETPOSTINT('search_date_export_startmonth');
$search_date_export_startday = GETPOSTINT('search_date_export_startday');
$search_date_export_start = GETPOSTDATE('search_date_export_start', 'getpost');

$search_date_export_endyear = GETPOSTINT('search_date_export_endyear');
$search_date_export_endmonth = GETPOSTINT('search_date_export_endmonth');
$search_date_export_endday = GETPOSTINT('search_date_export_endday');
$search_date_export_end = GETPOSTDATE('search_date_export_start', 'getpostend');

$search_date_validation_startyear = GETPOSTINT('search_date_validation_startyear');
$search_date_validation_startmonth = GETPOSTINT('search_date_validation_startmonth');
$search_date_validation_startday = GETPOSTINT('search_date_validation_startday');
$search_date_validation_start = GETPOSTDATE('search_date_validation_start', 'getpost');

$search_date_validation_endyear = GETPOSTINT('search_date_validation_endyear');
$search_date_validation_endmonth = GETPOSTINT('search_date_validation_endmonth');
$search_date_validation_endday = GETPOSTINT('search_date_validation_endday');
$search_date_validation_end = GETPOSTDATE('search_date_validation_end', 'getpostend');

// Due date start
$search_date_due_start_day = GETPOSTINT('search_date_due_start_day');
$search_date_due_start_month = GETPOSTINT('search_date_due_start_month');
$search_date_due_start_year = GETPOSTINT('search_date_due_start_year');
$search_date_due_start = GETPOSTDATE('search_date_due_start_', 'getpost');

// Due date end
$search_date_due_end_day = GETPOSTINT('search_date_due_end_day');
$search_date_due_end_month = GETPOSTINT('search_date_due_end_month');
$search_date_due_end_year = GETPOSTINT('search_date_due_end_year');
$search_date_due_end = GETPOSTDATE('search_date_due_end_', 'getpostend');

$search_import_key = GETPOST("search_import_key", 'alpha');

$search_account_category = GETPOSTINT('search_account_category');

$search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
if ($search_accountancy_code_start == - 1) {
	$search_accountancy_code_start = '';
}
$search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
if ($search_accountancy_code_end == - 1) {
	$search_accountancy_code_end = '';
}
$search_doc_ref = GETPOST('search_doc_ref', 'alpha');
$search_label_operation = GETPOST('search_label_operation', 'alpha');
$search_mvt_num = GETPOST('search_mvt_num', 'alpha');
$search_direction = GETPOST('search_direction', 'alpha');
$search_ledger_code = GETPOST('search_ledger_code', 'array');
$search_debit = GETPOST('search_debit', 'alpha');
$search_credit = GETPOST('search_credit', 'alpha');
$search_lettering_code = GETPOST('search_lettering_code', 'alpha');
$search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
	$action = 'delbookkeepingyear';
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$optioncss = GETPOST('optioncss', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") {
	$sortorder = "ASC";
}
if ($sortfield == "") {
	$sortfield = "t.doc_date,t.rowid";
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new BookKeeping($db);
$formfile = new FormFile($db);
$hookmanager->initHooks(array($context_default));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('search_date_startday') && !GETPOSTISSET('search_date_startmonth') && !GETPOSTISSET('search_date_starthour')) {
	$sql = "SELECT date_start, date_end";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_fiscalyear ";
	if (getDolGlobalInt('ACCOUNTANCY_FISCALYEAR_DEFAULT')) {
		$sql .= " WHERE rowid = " . getDolGlobalInt('ACCOUNTANCY_FISCALYEAR_DEFAULT');
	} else {
		$sql .= " WHERE date_start < '" . $db->idate(dol_now()) . "' and date_end > '" . $db->idate(dol_now()) . "'";
	}
	$sql .= $db->plimit(1);
	$res = $db->query($sql);

	if ($res !== false && $db->num_rows($res) > 0) {
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

$arrayfields = array(
	// 't.subledger_account'=>array('label'=>$langs->trans("SubledgerAccount"), 'checked'=>1),
	't.piece_num' => array('label' => "TransactionNumShort", 'checked' => '1'),
	't.code_journal' => array('label' => "Codejournal", 'checked' => '1'),
	't.doc_date' => array('label' => "Docdate", 'checked' => '1'),
	't.doc_ref' => array('label' => "Piece", 'checked' => '1'),
	't.label_operation' => array('label' => "Label", 'checked' => '1'),
	't.lettering_code' => array('label' => "Lettering", 'checked' => '1'),
	't.debit' => array('label' => "AccountingDebit", 'checked' => '1'),
	't.credit' => array('label' => "AccountingCredit", 'checked' => '1'),
	't.balance' => array('label' => "Balance", 'checked' => '1'),
	't.date_export' => array('label' => "DateExport", 'checked' => '-1'),
	't.date_validated' => array('label' => "DateValidation", 'checked' => '-1', 'enabled' => (string) (int) !getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")),
	't.date_lim_reglement' => array('label' => "DateDue", 'checked' => '0'),
	't.import_key' => array('label' => "ImportId", 'checked' => '-1', 'position' => 1100),
);

if (!getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
	unset($arrayfields['t.lettering_code']);
}

if ($search_date_start && empty($search_date_startyear)) {
	$tmparray = dol_getdate($search_date_start);
	$search_date_startyear = $tmparray['year'];
	$search_date_startmonth = $tmparray['mon'];
	$search_date_startday = $tmparray['mday'];
}
if ($search_date_end && empty($search_date_endyear)) {
	$tmparray = dol_getdate($search_date_end);
	$search_date_endyear = $tmparray['year'];
	$search_date_endmonth = $tmparray['mon'];
	$search_date_endday = $tmparray['mday'];
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

$error = 0;
$result = -1; // For static analysis
$documentlink = ''; // For static analysis

$permissiontoadd = $user->hasRight('accounting', 'mouvements', 'creer');


/*
 * Action
 */

$filter = array();
$param = '';
$url_param = '';

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'preunletteringauto' && $massaction != 'preunletteringmanual' && $massaction != 'predeletebookkeepingwriting') {
	$massaction = '';
}

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_doc_date = '';
		$search_account_category = '';
		$search_accountancy_code_start = '';
		$search_accountancy_code_end = '';
		$search_label_account = '';
		$search_doc_ref = '';
		$search_label_operation = '';
		$search_mvt_num = '';
		$search_direction = '';
		$search_ledger_code = array();
		$search_date_start = '';
		$search_date_end = '';
		$search_date_startyear = '';
		$search_date_startmonth = '';
		$search_date_startday = '';
		$search_date_endyear = '';
		$search_date_endmonth = '';
		$search_date_endday = '';
		$search_date_export_start = '';
		$search_date_export_end = '';
		$search_date_export_startyear = '';
		$search_date_export_startmonth = '';
		$search_date_export_startday = '';
		$search_date_export_endyear = '';
		$search_date_export_endmonth = '';
		$search_date_export_endday = '';
		$search_date_validation_start = '';
		$search_date_validation_end = '';
		$search_date_validation_startyear = '';
		$search_date_validation_startmonth = '';
		$search_date_validation_startday = '';
		$search_date_validation_endyear = '';
		$search_date_validation_endmonth = '';
		$search_date_validation_endday = '';
		// Due date start
		$search_date_due_start_day = '';
		$search_date_due_start_month = '';
		$search_date_due_start_year = '';
		$search_date_due_start = '';
		// Due date end
		$search_date_due_end_day = '';
		$search_date_due_end_month =  '';
		$search_date_due_end_year = '';
		$search_date_due_end = '';
		$search_lettering_code = '';
		$search_debit = '';
		$search_credit = '';
		$search_not_reconciled = '';
		$search_import_key = '';
		$toselect = array();
		unset($_SESSION['DOLDATE_search_date_start_accountancy_day']);
		unset($_SESSION['DOLDATE_search_date_start_accountancy_month']);
		unset($_SESSION['DOLDATE_search_date_start_accountancy_year']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_day']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_month']);
		unset($_SESSION['DOLDATE_search_date_end_accountancy_year']);
	}

	if (!empty($socid)) {
		$param = '&socid='.$socid;
	}
	if (!empty($search_date_start)) {
		$filter['t.doc_date>='] = $search_date_start;
		$param .= '&search_date_startmonth='.$search_date_startmonth.'&search_date_startday='.$search_date_startday.'&search_date_startyear='.$search_date_startyear;
	}
	if (!empty($search_date_end)) {
		$filter['t.doc_date<='] = $search_date_end;
		$param .= '&search_date_endmonth='.$search_date_endmonth.'&search_date_endday='.$search_date_endday.'&search_date_endyear='.$search_date_endyear;
	}
	if (!empty($search_doc_date)) {
		$filter['t.doc_date'] = $search_doc_date;
		$param .= '&doc_datemonth='.GETPOSTINT('doc_datemonth').'&doc_dateday='.GETPOSTINT('doc_dateday').'&doc_dateyear='.GETPOSTINT('doc_dateyear');
	}
	if ($search_account_category != '-1' && !empty($search_account_category)) {
		require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
		$accountingcategory = new AccountancyCategory($db);

		$listofaccountsforgroup = $accountingcategory->getCptsCat(0, 'fk_accounting_category = '.((int) $search_account_category));
		$listofaccountsforgroup2 = array();
		if (is_array($listofaccountsforgroup)) {
			foreach ($listofaccountsforgroup as $tmpval) {
				$listofaccountsforgroup2[] = "'".$db->escape((string) $tmpval['account_number'])."'";
			}
		}
		$filter['t.search_accounting_code_in'] = implode(',', $listofaccountsforgroup2);
		$param .= '&search_account_category='.urlencode((string) ($search_account_category));
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
	if (!empty($search_label_account)) {
		$filter['t.label_compte'] = $search_label_account;
		$param .= '&search_label_compte='.urlencode($search_label_account);
	}
	if (!empty($search_mvt_num)) {
		$filter['t.piece_num'] = $search_mvt_num;
		$param .= '&search_mvt_num='.urlencode((string) ($search_mvt_num));
	}
	if (!empty($search_doc_ref)) {
		$filter['t.doc_ref'] = $search_doc_ref;
		$param .= '&search_doc_ref='.urlencode($search_doc_ref);
	}
	if (!empty($search_label_operation)) {
		$filter['t.label_operation'] = $search_label_operation;
		$param .= '&search_label_operation='.urlencode($search_label_operation);
	}
	if (!empty($search_direction)) {
		$filter['t.sens'] = $search_direction;
		$param .= '&search_direction='.urlencode($search_direction);
	}
	if (!empty($search_ledger_code)) {
		$filter['t.code_journal'] = $search_ledger_code;
		foreach ($search_ledger_code as $code) {
			$param .= '&search_ledger_code[]='.urlencode($code);
		}
	}
	if (!empty($search_lettering_code)) {
		$filter['t.lettering_code'] = $search_lettering_code;
		$param .= '&search_lettering_code='.urlencode($search_lettering_code);
	}
	if (!empty($search_debit)) {
		$filter['t.debit'] = $search_debit;
		$param .= '&search_debit='.urlencode($search_debit);
	}
	if (!empty($search_credit)) {
		$filter['t.credit'] = $search_credit;
		$param .= '&search_credit='.urlencode($search_credit);
	}
	if (!empty($search_not_reconciled)) {
		$filter['t.reconciled_option'] = $search_not_reconciled;
		$param .= '&search_not_reconciled='.urlencode($search_not_reconciled);
	}
	if (!empty($search_date_export_start)) {
		$filter['t.date_export>='] = $search_date_export_start;
		$param .= '&search_date_export_startmonth='.$search_date_export_startmonth.'&search_date_export_startday='.$search_date_export_startday.'&search_date_export_startyear='.$search_date_export_startyear;
	}
	if (!empty($search_date_export_end)) {
		$filter['t.date_export<='] = $search_date_export_end;
		$param .= '&search_date_export_endmonth='.$search_date_export_endmonth.'&search_date_export_endday='.$search_date_export_endday.'&search_date_export_endyear='.$search_date_export_endyear;
	}
	if (!empty($search_date_validation_start)) {
		$filter['t.date_validated>='] = $search_date_validation_start;
		$param .= '&search_date_validation_startmonth='.$search_date_validation_startmonth.'&search_date_validation_startday='.$search_date_validation_startday.'&search_date_validation_startyear='.$search_date_validation_startyear;
	}
	if (!empty($search_date_validation_end)) {
		$filter['t.date_validated<='] = $search_date_validation_end;
		$param .= '&search_date_validation_endmonth='.$search_date_validation_endmonth.'&search_date_validation_endday='.$search_date_validation_endday.'&search_date_validation_endyear='.$search_date_validation_endyear;
	}
	// Due date start
	if (!empty($search_date_due_start)) {
		$filter['t.date_lim_reglement>='] = $search_date_due_start;
		$param .= '&search_date_due_start_day='.$search_date_due_start_day.'&search_date_due_start_month='.$search_date_due_start_month.'&search_date_due_start_year='.$search_date_due_start_year;
	}
	// Due date end
	if (!empty($search_date_due_end)) {
		$filter['t.date_lim_reglement<='] = $search_date_due_end;
		$param .= '&search_date_due_end_day='.$search_date_due_end_day.'&search_date_due_end_month='.$search_date_due_end_month.'&search_date_due_end_year='.$search_date_due_end_year;
	}
	if (!empty($search_import_key)) {
		$filter['t.import_key'] = $search_import_key;
		$param .= '&search_import_key='.urlencode($search_import_key);
	}
	// param with type of list
	$url_param = substr($param, 1); // remove first "&"
	if (!empty($type)) {
		$param = '&type='.$type.$param;
	}

	// Permissions
	$permissiontoread = $user->hasRight('societe', 'lire');
	$permissiontodelete = $user->hasRight('societe', 'supprimer');
	$permissiontoadd = $user->hasRight('societe', 'creer');

	// Actions
	if ($action === 'exporttopdf' && $permissiontoadd) {
		$object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter);
		require_once DOL_DOCUMENT_ROOT . '/core/modules/accountancy/doc/pdf_ledger.modules.php';
		$pdf = new pdf_ledger($db);
		$pdf->fromDate = $search_date_start;
		$pdf->toDate = $search_date_end;
		$result = $pdf->write_file($object, $langs);

		if ($result < 0) {
			setEventMessage($pdf->error, "errors");
		} else {
			// Generated PDF is directly sent to the browser
			exit;
		}
	}

	// Mass actions
	$objectclass = 'Bookkeeping';
	$objectlabel = 'Bookkeeping';
	$uploaddir = $conf->societe->dir_output;

	global $error;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if (!$error && $action == 'deletebookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'supprimer')) {
		$db->begin();

		if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
			$lettering = new Lettering($db);
			$nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
			if ($nb_lettering < 0) {
				setEventMessages('', $lettering->errors, 'errors');
				$error++;
			}
		}

		$nbok = 0;
		if (!$error) {
			foreach ($toselect as $toselectid) {
				$result = $object->fetch($toselectid);
				if ($result > 0 && (!isset($object->date_validation) || $object->date_validation === '')) {
					$result = $object->deleteMvtNum($object->piece_num);
					if ($result > 0) {
						$nbok++;
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
						break;
					}
				} elseif ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
					break;
				} elseif (isset($object->date_validation) && $object->date_validation != '') {
					setEventMessages($langs->trans("ValidatedRecordWhereFound"), null, 'errors');
					$error++;
					break;
				}
			}
		}

		if (!$error) {
			$db->commit();

			// Message for elements well deleted
			if ($nbok > 1) {
				setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
			} elseif ($nbok > 0) {
				setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
			} elseif (!$error) {
				setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
			}

			header("Location: ".$_SERVER["PHP_SELF"]."?noreset=1".($param ? '&'.$param : ''));
			exit;
		} else {
			$db->rollback();
		}
	}

	// massaction cloning
	if (!$error && $action == 'clonebookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'creer')) {
		$result = $object->newCloneMass($toselect, $journal_code, $massdate);
		if ($result == -1) {
			$error++;
		}
		if ($error) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]."?noreset=1".($param ? '&'.$param : ''));
			exit;
		} else {
			$db->rollback();
		}
	}

	// massaction assign new account
	if (!$error && $action == 'assignaccountbookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'creer')) {
		$result = $object->assignAccountMass($toselect, (int) $account);
		if ($result == -1) {
			$error++;
		}
		if (!$error) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]."?noreset=1".($param ? '&'.$param : ''));
			exit();
		} else {
			$db->rollback();
		}
	}

	// mass action return account
	if (!$error && $action == 'returnaccountbookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'creer')) {
		$result = $object->newReturnAccount($toselect, $journal_code, $massdate);
		if ($result == -1) {
			$error++;
		}
		if (!$error) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]."?noreset=1".($param ? '&'.$param : ''));
			exit();
		} else {
			$db->rollback();
		}
	}

	// others mass actions
	if (!$error && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
		if ($massaction == 'letteringauto' && $permissiontoadd) {
			$lettering = new Lettering($db);
			$nb_lettering = $lettering->bookkeepingLetteringAll($toselect);
			if ($nb_lettering < 0) {
				setEventMessages('', $lettering->errors, 'errors');
				$error++;
				$nb_lettering = max(0, abs($nb_lettering) - 2);
			} elseif ($nb_lettering == 0) {
				$nb_lettering = 0;
				setEventMessages($langs->trans('AccountancyNoLetteringModified'), array(), 'mesgs');
			}
			if ($nb_lettering == 1) {
				setEventMessages($langs->trans('AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
			} elseif ($nb_lettering > 1) {
				setEventMessages($langs->trans('AccountancyLetteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
			}

			if (!$error) {
				header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
				exit();
			}
		} elseif ($massaction == 'letteringmanual' && $permissiontoadd) {
			$lettering = new Lettering($db);
			$result = $lettering->updateLettering($toselect);
			if ($result < 0) {
				setEventMessages('', $lettering->errors, 'errors');
			} else {
				setEventMessages($langs->trans($result == 0 ? 'AccountancyNoLetteringModified' : 'AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
				header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
				exit();
			}
		} elseif ($type == 'sub' && $massaction == 'letteringpartial') {
			$lettering = new Lettering($db);
			$result = $lettering->updateLettering($toselect, 0, true);
			if ($result < 0) {
				setEventMessages('', $lettering->errors, 'errors');
			} else {
				setEventMessages($langs->trans($result == 0 ? 'AccountancyNoLetteringModified' : 'AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
				header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
				exit();
			}
		} elseif ($action == 'unletteringauto' && $confirm == "yes" && $permissiontoadd) {
			$lettering = new Lettering($db);
			$nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
			if ($nb_lettering < 0) {
				setEventMessages('', $lettering->errors, 'errors');
				$error++;
				$nb_lettering = max(0, abs($nb_lettering) - 2);
			} elseif ($nb_lettering == 0) {
				$nb_lettering = 0;
				setEventMessages($langs->trans('AccountancyNoUnletteringModified'), array(), 'mesgs');
			}
			if ($nb_lettering == 1) {
				setEventMessages($langs->trans('AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
			} elseif ($nb_lettering > 1) {
				setEventMessages($langs->trans('AccountancyUnletteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
			}

			if (!$error) {
				header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
				exit();
			}
		} elseif ($action == 'unletteringmanual' && $confirm == "yes" && $permissiontoadd) {
			$lettering = new Lettering($db);
			$nb_lettering = $lettering->deleteLettering($toselect);
			if ($result < 0) {
				setEventMessages('', $lettering->errors, 'errors');
			} else {
				setEventMessages($langs->trans($result == 0 ? 'AccountancyNoUnletteringModified' : 'AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
				header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
				exit();
			}
		}
	}
}


/*
 * View
 */

$formaccounting = new FormAccounting($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$form = new Form($db);

$title_page = $langs->trans("Operations").' - '.$langs->trans("VueByAccountAccounting").' (';
if ($type == 'sub') {
	$title_page .= $langs->trans("BookkeepingSubAccount");
} else {
	$title_page .= $langs->trans("Bookkeeping");
}
$title_page .= ')';
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
llxHeader('', $title_page, $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-consultation page-'.(($type == 'sub') ? 'sub' : '').'ledger');

if (!empty($socid)) {
	$companystatic = new Societe($db);
	$res = $companystatic->fetch($socid);
	if ($res > 0) {
		$tmpobject = $object;
		$object = $companystatic; // $object must be of type Societe when calling societe_prepare_head
		$head = societe_prepare_head($companystatic);
		$object = $tmpobject;

		print dol_get_fiche_head($head, 'accounting', $langs->trans("ThirdParty"), -1, 'company');

		$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($companystatic, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Type Prospect/Customer/Supplier
		print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
		print $companystatic->getTypeUrl(1);
		print '</td></tr>';

		// Customer code
		if ($companystatic->client && !empty($companystatic->code_client)) {
			print '<tr><td class="titlefield">';
			print $langs->trans('CustomerCode').'</td><td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($companystatic->code_client));
			$tmpcheck = $companystatic->check_codeclient();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
			}
			print '</td>';
			print '</tr>';
		}
		// Supplier code
		if ($companystatic->fournisseur && !empty($companystatic->code_fournisseur)) {
			print '<tr><td class="titlefield">';
			print $langs->trans('SupplierCode').'</td><td>';
			print showValueWithClipboardCPButton(dol_escape_htmltag($companystatic->code_fournisseur));
			$tmpcheck = $companystatic->check_codefournisseur();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
			}
			print '</td>';
			print '</tr>';
		}

		print '</table>';
		print '</div>';
		print dol_get_fiche_end();

		print info_admin($langs->trans("WarningThisPageContainsOnlyEntriesTransferredInAccounting")).'';

		// Choice of mode (customer / supplier)
		if (!empty($conf->dol_use_jmobile)) {
			print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";
		}

		if ($companystatic->client && !empty($companystatic->code_compta_client)) {
			if ($mode != 'customer') {
				if (!empty($companystatic->code_compta_client)) {
					$subledger_start_account = $subledger_end_account = $companystatic->code_compta_client;
				} else {
					$subledger_start_account = $subledger_end_account = '';
				}
				print '<a class="a-mesure-disabled marginleftonly marginrightonly reposition" href="' . $_SERVER["PHP_SELF"] . '?mode=customer&socid='.$socid.'&type=sub&search_accountancy_code_start='.$subledger_start_account.'&search_accountancy_code_end='.$subledger_end_account.'">';
			} else {
				print '<span class="a-mesure marginleftonly marginrightonly">';
			}

			print $langs->trans("CustomerAccountancyCodeShort");
			if ($mode != 'customer') {
				print '</a>';
			} else {
				print '</span>';
			}
		}

		if ($companystatic->fournisseur && !empty($companystatic->code_compta_fournisseur)) {
			if ($mode != 'supplier') {
				if (!empty($companystatic->code_compta_fournisseur)) {
					$subledger_start_account = $subledger_end_account = $companystatic->code_compta_fournisseur;
				} else {
					$subledger_start_account = $subledger_end_account = '';
				}
				print '<a class="a-mesure-disabled marginleftonly marginrightonly reposition" href="' . $_SERVER["PHP_SELF"] . '?mode=supplier&socid='.$socid.'&type=sub&search_accountancy_code_start='.$subledger_start_account.'&search_accountancy_code_end='.$subledger_end_account.'">';
			} else {
				print '<span class="a-mesure marginleftonly marginrightonly">';
			}
			print $langs->trans("SupplierAccountancyCodeShort");
			if ($mode != 'supplier') {
				print '</a>';
			} else {
				print '</span>';
			}
		}

		if (!empty($conf->dol_use_jmobile)) {
			print '</div></div>';
		} else {
			print '<br>';
		}
		print '<br>';
	}
}

// List
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	// TODO Perf Replace this by a count
	if ($type == 'sub') {
		$nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 1, 1);
	} else {
		$nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 0, 1);
	}

	if ($nbtotalofrecords < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$error++;
	}
}

$num = 0;
if (!$error) {
	if ($type == 'sub') {
		$result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);
	} else {
		$result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 0);
	}
	//$num = count($object->lines);
	$num = $result;						// $result is total nb of lines, or limit + 1, but $object->lines is always limited to $limit

	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$arrayofselected = is_array($toselect) ? $toselect : array();

// Print form confirm
$formconfirm = '';
print $formconfirm;

// List of mass actions available
$arrayofmassactions = array();
if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
	$arrayofmassactions['letteringauto'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringAuto');
	$arrayofmassactions['preunletteringauto'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringAuto');
	$arrayofmassactions['letteringmanual'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringManual');
	if ($type == 'sub') {
		$arrayofmassactions['letteringpartial'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringPartial');
	}
	$arrayofmassactions['preunletteringmanual'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringManual');
}
if ($user->hasRight('accounting', 'mouvements', 'creer')) {
	$arrayofmassactions['preclonebookkeepingwriting'] = img_picto('', 'clone', 'class="pictofixedwidth"').$langs->trans("Clone");
}
if ($user->hasRight('accounting', 'mouvements', 'creer')) {
	$arrayofmassactions['preassignaccountbookkeepingwriting'] = img_picto('', 'fa-exchange-alt', 'class="pictofixedwidth"').$langs->trans("AssignAccount");
}
if ($user->hasRight('accounting', 'mouvements', 'creer')) {
	$arrayofmassactions['prereturnaccountbookkeepingwriting'] = img_picto('', 'undo', 'class="pictofixedwidth"').$langs->trans("ReturnAccount");
}
if ($user->hasRight('accounting', 'mouvements', 'supprimer')) {
	$arrayofmassactions['predeletebookkeepingwriting'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}

if (GETPOSTINT('nomassaction') || in_array($massaction, array('preunletteringauto', 'preunletteringmanual', 'predeletebookkeepingwriting', 'preclonebookkeepingwriting', 'preassignaccountbookkeepingwriting', 'prereturnaccountbookkeepingwriting'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="type" value="'.$type.'">';
if (!empty($socid)) {
	print '<input type="hidden" name="socid" value="' . $socid . '">';
}
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$parameters = array('param' => $param);
$reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

if (empty($reshook)) {
	// Remove navigation buttons if in thirdparty tab mode, except for PDF printing
	if (empty($socid)) {
		$newcardbutton = dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?' . $param);
		if ($type == 'sub') {
			$newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
			$newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
		} else {
			$newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
			$newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
		}
	}
	$newcardbutton .= dolGetButtonTitle($langs->trans('ExportToPdf'), '', 'fa fa-file-pdf paddingleft', $_SERVER['PHP_SELF'] . '?action=exporttopdf&' . $url_param, '', 1, array('morecss' => 'marginleftonly'));

	$newcardbutton .= dolGetButtonTitleSeparator();

	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?action=create'.(!empty($type) ? '&type=sub' : '').'&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);
}

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}

print_barre_liste($title_page, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($massaction == 'preunletteringauto') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassUnletteringAuto"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringauto", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'preunletteringmanual') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassUnletteringManual"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringmanual", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'predeletebookkeepingwriting') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeleteBookkeepingWriting"), $langs->trans("ConfirmMassDeleteBookkeepingWritingQuestion", count($toselect)), "deletebookkeepingwriting", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'preassignaccountbookkeepingwriting') {
	$input = $formaccounting->select_account('', 'account', 1);
	$formquestion = array(array('type' => 'other', 'name' => 'account', 'label' => '<span class="fieldrequired">' . $langs->trans("AccountAccountingShort") . '</span>', 'value' => $input),);
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("confirmMassAssignAccountBookkeepingWritingConfirm"), $langs->trans("ConfirmMassAssignAccountBookkeepingWritingQuestion", count($toselect)), "assignaccountbookkeepingwriting", $formquestion, '', 0, 200, 500, 1);
} elseif ($massaction == 'preclonebookkeepingwriting') {
	$input1 = $form->selectDate('', 'massdate', 0, 0, 0, "create_mvt", 1, 1);
	$input2 = $formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1).'</td>';
	$formquestion = array(
		array(
			'type' => 'other',
			'name' => 'massdate',
			'label' => '<span class="fieldrequired">' . $langs->trans("Docdate") . '</span>',
			'value' => $input1
		)
	);

	if (getDolGlobalString('ACCOUNTING_CLONING_ENABLE_INPUT_JOURNAL')) {
		$formquestion[] = array(
			'type' => 'text',
			'name' => 'code_journal',
			'label' => '<span class="fieldrequired">' . $langs->trans("Codejournal") . '</span>',
			'value' => $input2
		);
	}

	print $form->formconfirm(
		$_SERVER["PHP_SELF"],
		$langs->trans("ConfirmMassCloneBookkeepingWriting"),
		$langs->trans("ConfirmMassCloneBookkeepingWritingQuestion", count($toselect)),
		"clonebookkeepingwriting",
		$formquestion,
		'', 0, 200, 500, 1
	);
} elseif ($massaction == 'prereturnaccountbookkeepingwriting') {
	$input1 = $form->selectDate('', 'massdate', 0, 0, 0, "create_mvt", 1, 1);
	$formquestion = array(array('type' => 'other', 'name' => 'massdate', 'label' => '<span class="fieldrequired">' . $langs->trans("Docdate") . '</span>', 'value' => $input1));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassReturnAccountBookkeepingWriting"), $langs->trans("ConfirmMassReturnAccountBookkeepingWritingQuestion", count($toselect)), "returnaccountbookkeepingwriting", $formquestion, '', 0, 200, 500, 1);
}

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
if ($massactionbutton && $contextpage != 'poslist') {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

// Reverse sort order
if (preg_match('/^asc/i', $sortorder)) {
	$sortorder = "asc";
} else {
	$sortorder = "desc";
}

// Warning to explain why the list of record is not consistent with the other list view (missing a lot of lines)
if ($type == 'sub' && !$socid) {
	print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));
	print '<br>';
}

$moreforfilter = '';

// Search on accountancy custom groups or account
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('AccountAccounting').': ';
$moreforfilter .= '<div class="nowrap inline-block">';
if ($type == 'sub') {
	if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
		$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), 'maxwidth200');
	} else {
		$moreforfilter .= '<input type="text" class="maxwidth150" name="search_accountancy_code_start" value="'.dol_escape_htmltag($search_accountancy_code_start).'" placeholder="'.$langs->trans('From').'">';
	}
} else {
	$moreforfilter .= $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth200');
}
$moreforfilter .= ' ';
if ($type == 'sub') {
	if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
		$moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), 'maxwidth200');
	} else {
		$moreforfilter .= '<input type="text" class="maxwidth150" name="search_accountancy_code_end" value="'.dol_escape_htmltag($search_accountancy_code_end).'" placeholder="'.$langs->trans('to').'">';
	}
} else {
	$moreforfilter .= $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth200');
}

if (empty($socid)) {
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->name == 'chrome') {
		$stringforfirstkey .= ' ALT +';
	} elseif ($conf->browser->name == 'firefox') {
		$stringforfirstkey .= ' ALT + SHIFT +';
	} else {
		$stringforfirstkey .= ' CTL +';
	}
	$moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="previous_account" accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="#"><i class="fa fa-chevron-left"></i></a>';
	$moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="next_account" accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="#"><i class="fa fa-chevron-right"></i></a>';
	$moreforfilter .= <<<SCRIPT
<script type="text/javascript">
	jQuery(document).ready(function() {
		var searchFormList = $('#searchFormList');
		var searchAccountancyCodeStart = $('#search_accountancy_code_start');
		var searchAccountancyCodeEnd = $('#search_accountancy_code_end');
		jQuery('#previous_account').on('click', function() {
			var previousOption = searchAccountancyCodeStart.find('option:selected').prev('option');
			if (previousOption.length == 1) searchAccountancyCodeStart.val(previousOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('#next_account').on('click', function() {
			var nextOption = searchAccountancyCodeStart.find('option:selected').next('option');
			if (nextOption.length == 1) searchAccountancyCodeStart.val(nextOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('input[name="search_mvt_num"]').on("keypress", function(event) {
			console.log(event);
		});
	});
</script>
SCRIPT;
}
$moreforfilter .= '</div>';
$moreforfilter .= '</div>';

if (empty($socid)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('AccountingCategory') . ': ';
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
	$moreforfilter .= '</div>';
	$moreforfilter .= '</div>';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print $moreforfilter;
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent listwithfilterbefore">';

// Filters lines
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Movement number
if (!empty($arrayfields['t.piece_num']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_mvt_num" class="width50" value="'.dol_escape_htmltag($search_mvt_num).'"></td>';
}
// Code journal
if (!empty($arrayfields['t.code_journal']['checked'])) {
	print '<td class="liste_titre center">';
	print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'maxwidth75');
	print '</td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_doc_ref" value="'.dol_escape_htmltag($search_doc_ref).'"/></td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked'])) {
	print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_operation" value="'.dol_escape_htmltag($search_label_operation).'"/></td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input type="text" size="3" class="flat" name="search_lettering_code" value="'.$search_lettering_code.'"/>';
	print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"'.($search_not_reconciled == 'notreconciled' ? ' checked' : '').'>'.$langs->trans("NotReconciled").'</span>';
	print '</td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked'])) {
	print '<td class="liste_titre right"><input type="text" class="flat" name="search_debit" size="4" value="'.dol_escape_htmltag($search_debit).'"></td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked'])) {
	print '<td class="liste_titre right"><input type="text" class="flat" name="search_credit" size="4" value="'.dol_escape_htmltag($search_credit).'"></td>';
}
// Balance
if (!empty($arrayfields['t.balance']['checked'])) {
	print '<td></td>';
}
// Date export
if (!empty($arrayfields['t.date_export']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Date validation
if (!empty($arrayfields['t.date_validated']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
// Due date start and end
if (!empty($arrayfields['t.date_lim_reglement']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_due_start, 'search_date_due_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_due_end, 'search_date_due_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['t.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
if (!empty($arrayfields['t.piece_num']['checked'])) {
	print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, '', $sortfield, $sortorder, 'tdoverflowmax80imp ');
}
if (!empty($arrayfields['t.code_journal']['checked'])) {
	print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_date']['checked'])) {
	print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_ref']['checked'])) {
	print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.label_operation']['checked'])) {
	print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.lettering_code']['checked'])) {
	print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.debit']['checked'])) {
	print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.credit']['checked'])) {
	print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.balance']['checked'])) {
	print_liste_field_titre($arrayfields['t.balance']['label'], "", "", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.date_export']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_validated']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Due date
if (!empty($arrayfields['t.date_lim_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['t.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], 't.date_lim_reglement', '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER["PHP_SELF"], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";

$displayed_account_number = null; // Start with undefined to be able to distinguish with empty

$objectstatic = null;  // Init for static analysis
$objectlink = '';  // Init for static analysis
$result = -1;  // Init for static analysis

// Loop on record
// --------------------------------------------------------------------
$i = 0;

$totalarray = array();
$totalarray['nbfield'] = 0;
$sous_total_debit = 0;
$sous_total_credit = 0;
$totalarray['val'] = array();
$totalarray['val']['totaldebit'] = 0;
$totalarray['val']['totalcredit'] = 0;
$totalarray['val']['totalbalance'] = 0;

// Init for static analysis
$colspan = 0;			// colspan before field 'label of operation'
$colspanend = 0;		// colspan after debit/credit
$accountg = '-';

$colspan = 0;			// colspan before field 'label of operation'
$colspanend = 3;		// colspan after debit/credit
if (!empty($arrayfields['t.piece_num']['checked'])) { $colspan++; }
if (!empty($arrayfields['t.code_journal']['checked'])) { $colspan++; }
if (!empty($arrayfields['t.doc_date']['checked'])) { $colspan++; }
if (!empty($arrayfields['t.doc_ref']['checked'])) { $colspan++; }
if (!empty($arrayfields['t.label_operation']['checked'])) { $colspan++; }
if (!empty($arrayfields['t.date_export']['checked'])) { $colspanend++; }
if (!empty($arrayfields['t.date_validated']['checked'])) { $colspanend++; }
if (!empty($arrayfields['t.lettering_code']['checked'])) { $colspanend++; }
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	$colspan++;
	$colspanend--;
}

while ($i < min($num, $limit)) {
	$line = $object->lines[$i];

	if ($type == 'sub') {
		$accountg = length_accounta($line->subledger_account);
	} else {
		$accountg = length_accountg($line->numero_compte);
	}
	//if (empty($accountg)) $accountg = '-';

	$colspan = 0;			// colspan before field 'label of operation'
	$colspanend = 0;		// colspan after debit/credit
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$colspan++;
	}
	if (!empty($arrayfields['t.piece_num']['checked'])) {
		$colspan++;
	}
	if (!empty($arrayfields['t.code_journal']['checked'])) {
		$colspan++;
	}
	if (!empty($arrayfields['t.doc_date']['checked'])) {
		$colspan++;
	}
	if (!empty($arrayfields['t.doc_ref']['checked'])) {
		$colspan++;
	}
	if (!empty($arrayfields['t.label_operation']['checked'])) {
		$colspan++;
	}
	if (!empty($arrayfields['t.lettering_code']['checked'])) {
		$colspan++;
	}

	if (!empty($arrayfields['t.balance']['checked'])) {
		$colspanend++;
	}
	if (!empty($arrayfields['t.date_export']['checked'])) {
		$colspanend++;
	}
	if (!empty($arrayfields['t.date_validated']['checked'])) {
		$colspanend++;
	}
	// Due date
	if (!empty($arrayfields['t.date_lim_reglement']['checked'])) {
		$colspanend++;
	}
	if (!empty($arrayfields['t.lettering_code']['checked'])) {
		$colspanend++;
	}
	if (!empty($arrayfields['t.import_key']['checked'])) {
		$colspanend++;
	}
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		$colspan++;
		$colspanend--;
	}

	// Is it a break ?
	if ($accountg != $displayed_account_number || !isset($displayed_account_number)) {
		// Show a subtotal by accounting account
		if (isset($displayed_account_number)) {
			print '<tr class="liste_total">';
			if ($type == 'sub') {
				print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accounta($displayed_account_number) . ':</td>';
			} else {
				print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accountg($displayed_account_number) . ':</td>';
			}
			print '<td class="nowrap right">'.price(price2num($sous_total_debit, 'MT')).'</td>';
			print '<td class="nowrap right">'.price(price2num($sous_total_credit, 'MT')).'</td>';
			if ($colspanend > 0) {
				print '<td colspan="'.$colspanend.'"></td>';
			}
			print '</tr>';
			// Show balance of last shown account
			$balance = $sous_total_debit - $sous_total_credit;
			print '<tr class="liste_total">';
			print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("Balance").':</td>';
			if ($balance > 0) {
				print '<td class="nowraponall right">';
				print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
				print '</td>';
				print '<td></td>';
			} else {
				print '<td></td>';
				print '<td class="nowraponall right">';
				print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
				print '</td>';
			}
			if ($colspanend > 0) {
				print '<td colspan="'.$colspanend.'"></td>';
			}
			print '</tr>';
		}

		// Show the break account
		print '<tr class="trforbreaknobg">';
		print '<td colspan="'.($totalarray['nbfield'] ? $totalarray['nbfield'] : count($arrayfields) + 1).'" class="tdforbreak">';
		if ($type == 'sub') {
			if ($line->subledger_account != "" && $line->subledger_account != '-1') {
				print empty($line->subledger_label) ? '<span class="error">'.$langs->trans("Unknown").'</span>' : $line->subledger_label;
				print ' : ';
				print length_accounta($line->subledger_account);
			} else {
				// Should not happen: subledger account must be null or a non empty value
				print '<span class="error">' . $langs->trans("Unknown");
				if ($line->subledger_label) {
					print ' (' . $line->subledger_label . ')';
					$htmltext = 'EmptyStringForSubledgerAccountButSubledgerLabelDefined';
				} else {
					$htmltext = 'EmptyStringForSubledgerAccountAndSubledgerLabel';
				}
				print $form->textwithpicto('', $htmltext);
				print '</span>';
			}
		} else {
			if ($line->numero_compte != "" && $line->numero_compte != '-1') {
				print length_accountg($line->numero_compte) . ' : ' . $object->get_compte_desc($line->numero_compte);
			} else {
				print '<span class="error">' . $langs->trans("Unknown") . '</span>';
			}
		}
		print '</td>';
		print '</tr>';

		$displayed_account_number = $accountg;
		//if (empty($displayed_account_number)) $displayed_account_number='-';
		$sous_total_debit = 0;
		$sous_total_credit = 0;
	}

	print '<tr class="oddeven">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowraponall center">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($line->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Piece number
	if (!empty($arrayfields['t.piece_num']['checked'])) {
		print '<td>';
		$object->id = $line->id;
		$object->piece_num = $line->piece_num;
		$object->ref = $line->ref;
		print $object->getNomUrl(1, '', 0, '', 1);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Journal code
	if (!empty($arrayfields['t.code_journal']['checked'])) {
		$accountingjournal = new AccountingJournal($db);
		$result = $accountingjournal->fetch(0, $line->code_journal);
		$journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
		print '<td class="center tdoverflowmax80">'.$journaltoshow.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Document date
	if (!empty($arrayfields['t.doc_date']['checked'])) {
		print '<td class="center">'.dol_print_date($line->doc_date, 'day').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Document ref
	$modulepart = '';
	if (!empty($arrayfields['t.doc_ref']['checked'])) {
		if ($line->doc_type == 'customer_invoice') {
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$objectstatic = new Facture($db);
			$objectstatic->fetch($line->fk_doc);
			//$modulepart = 'facture';

			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		} elseif ($line->doc_type == 'supplier_invoice') {
			$langs->loadLangs(array('bills'));

			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$objectstatic = new FactureFournisseur($db);
			$objectstatic->fetch($line->fk_doc);

			$modulepart = 'invoice_supplier';
			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart).dol_sanitizeFileName($line->doc_ref);
			$subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart).dol_sanitizeFileName($line->doc_ref);
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
		} elseif ($line->doc_type == 'expense_report') {
			$langs->loadLangs(array('trips'));

			require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
			$objectstatic = new ExpenseReport($db);
			$objectstatic->fetch($line->fk_doc);
			//$modulepart = 'expensereport';

			$filename = dol_sanitizeFileName($line->doc_ref);
			$filedir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($line->doc_ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$objectstatic->id;
			$documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		} elseif ($line->doc_type == 'bank') {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$objectstatic = new AccountLine($db);
			$objectstatic->fetch($line->fk_doc);
		} else {
			// Other type
		}

		print '<td class="tdoverflowmax250">';

		// Picto + Ref
		if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
			print $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
			print $documentlink;
		} elseif ($line->doc_type == 'bank') {
			print $objectstatic->getNomUrl(1);
			$bank_ref = strstr($line->doc_ref, '-');
			print " " . $bank_ref;
		} else {
			print $line->doc_ref;
		}

		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Label operation
	if (!empty($arrayfields['t.label_operation']['checked'])) {
		// Show a link to the customer/supplier invoice
		$doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
		if (strlen(length_accounta($line->subledger_account)) == 0) {
			print '<td class="small tdoverflowmax350 classfortooltip" title="'.dol_escape_htmltag($line->label_operation).'">'.dol_escape_htmltag($line->label_operation).'</td>';
		} else {
			print '<td class="small tdoverflowmax350 classfortooltip" title="'.dol_escape_htmltag($line->label_operation.($line->label_operation ? '<br>' : '').'<span style="font-size:0.8em">('.length_accounta($line->subledger_account).')').'">'.dol_escape_htmltag($line->label_operation).($line->label_operation ? '<br>' : '').'<span style="font-size:0.8em">('.dol_escape_htmltag(length_accounta($line->subledger_account)).')</span></td>';
		}
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Lettering code
	if (!empty($arrayfields['t.lettering_code']['checked'])) {
		print '<td class="center">'.dol_escape_htmltag((string) $line->lettering_code).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Amount debit
	if (!empty($arrayfields['t.debit']['checked'])) {
		print '<td class="right nowraponall amount">'.($line->debit != 0 ? price($line->debit) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
		}
		$totalarray['val']['totaldebit'] += (float) $line->debit;
	}

	// Amount credit
	if (!empty($arrayfields['t.credit']['checked'])) {
		print '<td class="right nowraponall amount">'.($line->credit != 0 ? price($line->credit) : '').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
		}
		$totalarray['val']['totalcredit'] += (float) $line->credit;
	}

	// Amount balance
	if (!empty($arrayfields['t.balance']['checked'])) {
		print '<td class="right nowraponall amount">'.price(price2num($sous_total_debit + $line->debit - $sous_total_credit - $line->credit, 'MT')).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totalbalance';
		};
		$totalarray['val']['totalbalance'] += $line->debit - $line->credit;
	}

	// Exported operation date
	if (!empty($arrayfields['t.date_export']['checked'])) {
		print '<td class="center">'.dol_print_date($line->date_export, 'dayhour').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Validated operation date
	if (!empty($arrayfields['t.date_validated']['checked'])) {
		print '<td class="center">'.dol_print_date($line->date_validation, 'dayhour').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Due date
	if (!empty($arrayfields['t.date_lim_reglement']['checked'])) {
		print '<td class="center">'.dol_print_date($line->date_lim_reglement, 'day').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['t.import_key']['checked'])) {
		print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($line->import_key).'">'.dol_escape_htmltag($line->import_key)."</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'obj' => $line);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowraponall center">';
		if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($line->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Comptabilise le sous-total
	$sous_total_debit += $line->debit;
	$sous_total_credit += $line->credit;

	print "</tr>\n";

	$i++;
}

if ($num > 0 && $colspan > 0) {
	print '<tr class="liste_total">';
	print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("TotalForAccount").' '.$accountg.':</td>';
	print '<td class="nowrap right">'.price(price2num($sous_total_debit, 'MT')).'</td>';
	print '<td class="nowrap right">'.price(price2num($sous_total_credit, 'MT')).'</td>';
	if ($colspanend > 0) {
		print '<td colspan="'.$colspanend.'"></td>';
	}
	print '</tr>';

	// Show balance of last shown account
	$balance = $sous_total_debit - $sous_total_credit;
	print '<tr class="liste_total">';
	print '<td class="right" colspan="'.$colspan.'">'.$langs->trans("Balance").':</td>';
	if ($balance > 0) {
		print '<td class="nowraponall right">';
		print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
		print '</td>';
		print '<td></td>';
	} else {
		print '<td></td>';
		print '<td class="nowraponall right">';
		print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
		print '</td>';
	}
	if ($colspanend > 0) {
		print '<td colspan="'.$colspanend.'"></td>';
	}
	print '</tr>';
}


// Clean total values to round them
if (!empty($totalarray['val']['totaldebit'])) {
	$totalarray['val']['totaldebit'] = (float) price2num($totalarray['val']['totaldebit'], 'MT');
}
if (!empty($totalarray['val']['totalcredit'])) {
	$totalarray['val']['totalcredit'] = (float) price2num($totalarray['val']['totalcredit'], 'MT');
}
if (!empty($totalarray['val']['totalbalance'])) {
	$totalarray['val']['totalbalance'] = (float) price2num($totalarray['val']['totaldebit'] - $totalarray['val']['totalcredit'], 'MT');
}

// Show total line
$trforbreaknobg = 1;	// used in list_print_total.tpl.php
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
