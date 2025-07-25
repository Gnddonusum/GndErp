<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Cédric Salvado				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2015		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2016-2023	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2018-2023	Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2021-2024	Anthony Berton				<anthony.berton@bb2a.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Noé Cendrier				<noe.cendrier@altairis.fr>
 * Copyright (C) 2024		Benjamin Falière			<benjamin.faliere@altairis.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		William Mead			    <william.mead@manchenumerique.fr>
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
 *	\file       htdocs/commande/list.php
 *	\ingroup    order
 *	\brief      Page to list orders
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('margin')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'deliveries', 'companies', 'compta', 'bills', 'stocks', 'products'));

// Get Parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'orderlist';
$optioncss = GETPOST('optioncss', 'alpha');
$mode        = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

if (getDolGlobalInt('MAIN_SEE_SUBORDINATES')) {
	$userschilds = $user->getAllChildIds();
} else {
	$userschilds = array();
}

// Search Parameters
$search_datecloture_start = GETPOSTINT('search_datecloture_start');
if (empty($search_datecloture_start)) {
	$search_datecloture_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datecloture_startmonth'), GETPOSTINT('search_datecloture_startday'), GETPOSTINT('search_datecloture_startyear'));
}
$search_datecloture_end = GETPOSTINT('search_datecloture_end');
if (empty($search_datecloture_end)) {
	$search_datecloture_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datecloture_endmonth'), GETPOSTINT('search_datecloture_endday'), GETPOSTINT('search_datecloture_endyear'));
}
$search_dateorder_start = dol_mktime(0, 0, 0, GETPOSTINT('search_dateorder_start_month'), GETPOSTINT('search_dateorder_start_day'), GETPOSTINT('search_dateorder_start_year'));
$search_dateorder_end = dol_mktime(23, 59, 59, GETPOSTINT('search_dateorder_end_month'), GETPOSTINT('search_dateorder_end_day'), GETPOSTINT('search_dateorder_end_year'));
$search_datedelivery_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datedelivery_start_month'), GETPOSTINT('search_datedelivery_start_day'), GETPOSTINT('search_datedelivery_start_year'));
$search_datedelivery_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datedelivery_end_month'), GETPOSTINT('search_datedelivery_end_day'), GETPOSTINT('search_datedelivery_end_year'));

$socid = GETPOSTINT('socid');

$search_all = trim(GETPOST('search_all', 'alphanohtml'));
$searchCategoryOrderOperator = 0;
if (GETPOSTISSET('formfilteraction')) {
	$searchCategoryOrderOperator = GETPOSTINT('search_category_order_operator');
} elseif (getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT')) {
	$searchCategoryOrderOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
}
$searchCategoryOrderList = GETPOST('search_category_order_list', 'array');
$search_product_category = GETPOST('search_product_category', 'intcomma');
$search_id = GETPOST('search_id', 'int');
$search_ref = GETPOST('search_ref', 'alpha') != '' ? GETPOST('search_ref', 'alpha') : GETPOST('sref', 'alpha');
$search_ref_ext = GETPOST('search_ref_ext', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_company_alias = GETPOST('search_company_alias', 'alpha');
$search_parent_name = trim(GETPOST('search_parent_name', 'alphanohtml'));
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST('search_state', 'alpha');
$search_country = GETPOST('search_country', 'aZ09');
$search_type_thirdparty = GETPOST('search_type_thirdparty', 'intcomma');
$search_user = GETPOST('search_user', 'intcomma');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_total_ht  = GETPOST('search_total_ht', 'alpha');
$search_total_vat = GETPOST('search_total_vat', 'alpha');
$search_total_ttc = GETPOST('search_total_ttc', 'alpha');
$search_warehouse = GETPOST('search_warehouse', 'intcomma');
$search_note_public = GETPOST('search_note_public', 'alphanohtml');
$search_note_private = GETPOST('search_note_private', 'alphanohtml');

$search_module_source = GETPOST('search_module_source', 'alphanohtml');
$search_pos_source = GETPOST('search_pos_source', 'alphanohtml');

$search_multicurrency_code = GETPOST('search_multicurrency_code', 'alpha');
$search_multicurrency_tx = GETPOST('search_multicurrency_tx', 'alpha');
$search_multicurrency_montant_ht  = GETPOST('search_multicurrency_montant_ht', 'alpha');
$search_multicurrency_montant_vat = GETPOST('search_multicurrency_montant_vat', 'alpha');
$search_multicurrency_montant_ttc = GETPOST('search_multicurrency_montant_ttc', 'alpha');

$search_login = GETPOST('search_login', 'alpha');
$search_categ_cus = GETPOST("search_categ_cus", 'intcomma');
$search_billed = GETPOST('search_billed', 'intcomma');
$search_status = GETPOST('search_status', 'intcomma');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_shippable = GETPOST('search_shippable', 'aZ09');

$search_fk_cond_reglement = GETPOST('search_fk_cond_reglement', 'intcomma');
$search_fk_shipping_method = GETPOST('search_fk_shipping_method', 'intcomma');
$search_fk_mode_reglement = GETPOST('search_fk_mode_reglement', 'intcomma');
$search_fk_input_reason = GETPOST('search_fk_input_reason', 'intcomma');

$search_option = GETPOST('search_option', 'alpha');
if ($search_option == 'late') {
	$search_status = '-2';
}
$search_orderday = '';
$search_ordermonth = '';
$search_orderyear = '';
$search_deliveryday = '';
$search_deliverymonth = '';
$search_deliveryyear = '';

$search_import_key  = trim(GETPOST("search_import_key", "alpha"));

$diroutputmassaction = $conf->order->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'c.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$show_shippable_command = GETPOST('show_shippable_command', 'aZ09');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Commande($db);
$hookmanager->initHooks(array('orderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref' => 'Ref',
	'c.ref_client' => 'RefCustomerOrder',
	'pd.description' => 'Description',
	's.nom' => "ThirdParty",
	's.name_alias' => "AliasNameShort",
	's.zip' => "Zip",
	's.town' => "Town",
	'c.note_public' => 'NotePublic',
);
if (empty($user->socid)) {
	$fieldstosearchall["c.note_private"] = "NotePrivate";
}

$checkedtypetiers = '0';
$arrayfields = array(
	'c.rowid' => array('label' => "ID", 'checked' => '1', 'enabled' => (string) getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID'), 'position' => 1),
	'c.ref' => array('label' => "Ref", 'checked' => '1', 'position' => 5, 'searchall' => 1),
	'c.ref_ext' => array('label' => "RefExt", 'checked' => '1', 'position' => 5, 'visible' => 0, 'searchall' => 1),
	'c.ref_client' => array('label' => "RefCustomerOrder", 'checked' => '-1', 'position' => 10, 'searchall' => 1),
	'p.ref' => array('label' => "ProjectRef", 'langfile' => 'projects', 'checked' => '-1', 'enabled' => (!isModEnabled('project') ? '0' : '1'), 'position' => 20),
	'p.title' => array('label' => "ProjectLabel", 'langfile' => 'projects', 'checked' => '0', 'enabled' => (!isModEnabled('project') ? '0' : '1'), 'position' => 25),
	's.nom' => array('label' => "ThirdParty", 'checked' => '1', 'position' => 30, 'searchall' => 1),
	's.name_alias' => array('label' => "AliasNameShort", 'checked' => '-1', 'position' => 31, 'searchall' => 1),
	's2.nom' => array('label' => 'ParentCompany', 'position' => 32, 'checked' => '0'),
	's.town' => array('label' => "Town", 'checked' => '-1', 'position' => 35, 'searchall' => 1),
	's.zip' => array('label' => "Zip", 'checked' => '-1', 'position' => 40, 'searchall' => 1),
	'state.nom' => array('label' => "StateShort", 'checked' => '0', 'position' => 45),
	'country.code_iso' => array('label' => "Country", 'checked' => '0', 'position' => 50),
	'typent.code' => array('label' => "ThirdPartyType", 'checked' => (string) $checkedtypetiers, 'position' => 55),
	'c.date_commande' => array('label' => "OrderDateShort", 'checked' => '1', 'position' => 60, 'csslist' => 'nowraponall'),
	'c.delivery_date' => array('label' => "DateDeliveryPlanned", 'checked' => '1', 'enabled' => (string) (int) !getDolGlobalString('ORDER_DISABLE_DELIVERY_DATE'), 'position' => 65, 'csslist' => 'nowraponall'),
	'c.fk_shipping_method' => array('label' => "SendingMethod", 'checked' => '-1', 'position' => 66 , 'enabled' => (string) (int) isModEnabled("shipping")),
	'c.fk_cond_reglement' => array('label' => "PaymentConditionsShort", 'checked' => '-1', 'position' => 67),
	'c.fk_mode_reglement' => array('label' => "PaymentMode", 'checked' => '-1', 'position' => 68),
	'c.fk_input_reason' => array('label' => "Origin", 'checked' => '-1', 'position' => 69),
	'c.module_source' => array('label' => "POSModule", 'langfile' => 'cashdesk', 'checked' => ($contextpage == 'poslist' ? '1' : '0'), 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('ORDER_SHOW_POS'))", 'position' => 90),
	'c.pos_source' => array('label' => "POSTerminal", 'langfile' => 'cashdesk', 'checked' => ($contextpage == 'poslist' ? '1' : '0'), 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('ORDER_SHOW_POS'))", 'position' => 91),
	'c.total_ht' => array('label' => "AmountHT", 'checked' => '1', 'position' => 75),
	'c.total_vat' => array('label' => "AmountVAT", 'checked' => '0', 'position' => 80),
	'c.total_ttc' => array('label' => "AmountTTC", 'checked' => '0', 'position' => 85),
	'c.multicurrency_code' => array('label' => 'Currency', 'checked' => '0', 'enabled' => (!isModEnabled("multicurrency") ? '0' : '1'), 'position' => 92),
	'c.multicurrency_tx' => array('label' => 'CurrencyRate', 'checked' => '0', 'enabled' => (!isModEnabled("multicurrency") ? '0' : '1'), 'position' => 95),
	'c.multicurrency_total_ht' => array('label' => 'MulticurrencyAmountHT', 'checked' => '0', 'enabled' => (!isModEnabled("multicurrency") ? '0' : '1'), 'position' => 100),
	'c.multicurrency_total_vat' => array('label' => 'MulticurrencyAmountVAT', 'checked' => '0', 'enabled' => (!isModEnabled("multicurrency") ? '0' : '1'), 'position' => 105),
	'c.multicurrency_total_ttc' => array('label' => 'MulticurrencyAmountTTC', 'checked' => '0', 'enabled' => (!isModEnabled("multicurrency") ? '0' : '1'), 'position' => 110),
	'u.login' => array('label' => "Author", 'checked' => '-1', 'position' => 115),
	'sale_representative' => array('label' => "SaleRepresentativesOfThirdParty", 'checked' => '0', 'position' => 116),
	'total_pa' => array('label' => (getDolGlobalString('MARGIN_TYPE') == '1' ? 'BuyingPrice' : 'CostPrice'), 'checked' => '0', 'position' => 300, 'enabled' => (string) (int) (!isModEnabled('margin') || !$user->hasRight("margins", "liretous") ? 0 : 1)),
	'total_margin' => array('label' => 'Margin', 'checked' => '0', 'position' => 301, 'enabled' => (string) (int) (!isModEnabled('margin') || !$user->hasRight("margins", "liretous") ? 0 : 1)),
	'total_margin_rate' => array('label' => 'MarginRate', 'checked' => '0', 'position' => 302, 'enabled' => (string) (int) (!isModEnabled('margin') || !$user->hasRight("margins", "liretous") || !getDolGlobalString('DISPLAY_MARGIN_RATES') ? 0 : 1)),
	'total_mark_rate' => array('label' => 'MarkRate', 'checked' => '0', 'position' => 303, 'enabled' => (string) (int) (!isModEnabled('margin') || !$user->hasRight("margins", "liretous") || !getDolGlobalString('DISPLAY_MARK_RATES') ? 0 : 1)),
	'c.datec' => array('label' => "DateCreation", 'checked' => '0', 'position' => 120),
	'c.tms' => array('label' => "DateModificationShort", 'checked' => '0', 'position' => 125),
	'c.date_cloture' => array('label' => "DateClosing", 'checked' => '0', 'position' => 130),
	'c.note_public' => array('label' => 'NotePublic', 'checked' => '0', 'enabled' => (string) (int) (!getDolGlobalInt('MAIN_LIST_HIDE_PUBLIC_NOTES')), 'position' => 135, 'searchall' => 1),
	'c.note_private' => array('label' => 'NotePrivate', 'checked' => '0', 'enabled' => (string) (int) (!getDolGlobalInt('MAIN_LIST_HIDE_PRIVATE_NOTES')), 'position' => 140),
	'shippable' => array('label' => "Shippable", 'checked' => '1','enabled' => (string) (int) (isModEnabled("shipping")), 'position' => 990),
	'c.facture' => array('label' => "Billed", 'checked' => '1', 'enabled' => (string) (int) (!getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT')), 'position' => 995),
	'c.import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'visible' => -2, 'position' => 999),
	'c.fk_statut' => array('label' => "Status", 'checked' => '1', 'position' => 1000)
);

$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('completeFieldsToSearchAll', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
	$fieldstosearchall = empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall'];
} elseif ($reshook == 0) {
	$fieldstosearchall = array_merge($fieldstosearchall, empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall']);
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');
$arrayfields = dol_sort_array($arrayfields, 'position');


// Security check
$id = GETPOSTINT('orderid', GETPOSTINT('id'));
if ($user->socid) {
	$socid = $user->socid;
}

$permissiontoreadallthirdparty = $user->hasRight('societe', 'client', 'voir');
$permissiontoread = false;
$permissiontovalidate = false;
$permissiontoclose = false;
$permissiontocancel = false;
$permissiontosendbymail = false;
$objectclass = null;


$result = restrictedArea($user, 'commande', $id, '');

$error = 0;


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') {
	$massaction = '';
}

$parameters = array('socid' => $socid, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$searchCategoryOrderList = array();
		$search_id = '';
		$search_ref = '';
		$search_ref_ext = '';
		$search_ref_customer = '';
		$search_company = '';
		$search_company_alias = '';
		$search_parent_name = '';
		$search_module_source = '';
		$search_pos_source = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_total_ht = '';
		$search_total_vat = '';
		$search_total_ttc = '';
		$search_warehouse = '';
		$search_note_public = '';
		$search_note_private = '';
		$search_multicurrency_code = '';
		$search_multicurrency_tx = '';
		$search_multicurrency_montant_ht = '';
		$search_multicurrency_montant_vat = '';
		$search_multicurrency_montant_ttc = '';
		$search_login = '';
		$search_dateorder_start = '';
		$search_dateorder_end = '';
		$search_datedelivery_start = '';
		$search_datedelivery_end = '';
		$search_project_ref = '';
		$search_project = '';
		$search_status = '';
		$search_billed = '';
		$search_datecloture_start = '';
		$search_datecloture_end = '';
		$search_fk_cond_reglement = '';
		$search_fk_shipping_method = '';
		$search_fk_mode_reglement = '';
		$search_fk_input_reason = '';
		$search_option = '';
		$search_import_key = '';
		$search_categ_cus = 0;

		$search_all = '';
		$toselect = array();
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Commande';
	$objectlabel = 'Orders';
	$permissiontoread = $user->hasRight("commande", "lire");
	$permissiontoadd = $user->hasRight("commande", "creer");
	$permissiontodelete = $user->hasRight("commande", "supprimer");
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$permissiontovalidate = $user->hasRight("commande", "order_advance", "validate");
		$permissiontoclose = $user->hasRight("commande", "order_advance", "close");
		$permissiontocancel = $user->hasRight("commande", "order_advance", "annuler");
		$permissiontosendbymail = $user->hasRight("commande", "order_advance", "send");
	} else {
		$permissiontovalidate = $user->hasRight("commande", "creer");
		$permissiontoclose = $user->hasRight("commande", "creer");
		$permissiontocancel = $user->hasRight("commande", "creer");
		$permissiontosendbymail = $user->hasRight("commande", "creer");
	}
	$uploaddir = $conf->order->multidir_output[$conf->entity];
	$triggersendname = 'ORDER_SENTBYMAIL';
	$year = "";
	$month = "";
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($massaction == 'confirm_createbills') {   // Create bills from orders.
		$orders = GETPOST('toselect', 'array');
		$createbills_onebythird = GETPOSTINT('createbills_onebythird');
		$validate_invoices = GETPOSTINT('validate_invoices');

		$errors = array();

		$TFact = array();
		$TFactThird = array();
		$TFactThirdNbLines = array();

		$nb_bills_created = 0;
		$lastid = 0;
		$lastref = '';

		$db->begin();

		$nbOrders = is_array($orders) ? count($orders) : 1;

		$currentIndex = 0;
		foreach ($orders as $id_order) {
			$cmd = new Commande($db);
			if ($cmd->fetch($id_order) <= 0) {
				continue;
			}
			$cmd->fetch_thirdparty();

			$objecttmp = new Facture($db);
			if (!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) {
				// If option "one bill per third" is set, and an invoice for this thirdparty was already created, we reuse it.
				$currentIndex++;
				$objecttmp = $TFactThird[$cmd->socid];
			} else {
				// If we want one invoice per order or if there is no first invoice yet for this thirdparty.
				$objecttmp->socid = $cmd->socid;
				$objecttmp->thirdparty = $cmd->thirdparty;

				$objecttmp->type = $objecttmp::TYPE_STANDARD;
				$objecttmp->cond_reglement_id = !empty($cmd->cond_reglement_id) ? $cmd->cond_reglement_id : $cmd->thirdparty->cond_reglement_id;
				$objecttmp->mode_reglement_id = !empty($cmd->mode_reglement_id) ? $cmd->mode_reglement_id : $cmd->thirdparty->mode_reglement_id;
				$objecttmp->demand_reason_id = !empty($cmd->demand_reason_id) ? $cmd->demand_reason_id : $cmd->thirdparty->demand_reason_id;

				$objecttmp->fk_project = $cmd->fk_project;
				$objecttmp->multicurrency_code = $cmd->multicurrency_code;
				if (empty($createbills_onebythird)) {
					$objecttmp->ref_client = $cmd->ref_client;
				}

				if (empty($objecttmp->note_public)) {
					$objecttmp->note_public =  $langs->transnoentities("Orders");
				}

				$datefacture = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
				if (empty($datefacture)) {
					$datefacture = dol_now();
				}

				$objecttmp->date = $datefacture;
				$objecttmp->origin    = 'commande';
				$objecttmp->origin_id = $id_order;

				$objecttmp->array_options = $cmd->array_options; // Copy extrafields

				$res = $objecttmp->create($user);

				if ($res > 0) {
					$nb_bills_created++;
					$lastref = $objecttmp->ref;
					$lastid = $objecttmp->id;

					$TFactThird[$cmd->socid] = $objecttmp;
					$TFactThirdNbLines[$cmd->socid] = 0; //init nblines to have lines ordered by expedition and rang
				} else {
					$langs->load("errors");
					$errors[] = $cmd->ref.' : '.$langs->trans($objecttmp->errors[0]);
					$error++;
				}
			}

			if ($objecttmp->id > 0) {
				$res = $objecttmp->add_object_linked($objecttmp->origin, $id_order);

				if ($res == 0) {
					$errors[] = $cmd->ref.' : '.$langs->trans($objecttmp->errors[0]);
					$error++;
				}

				if (!$error) {
					$lines = $cmd->lines;
					if (empty($lines) && method_exists($cmd, 'fetch_lines')) {
						$cmd->fetch_lines();
						$lines = $cmd->lines;
					}

					$fk_parent_line = 0;
					$num = count($lines);
					$array_options = array();

					for ($i = 0; $i < $num; $i++) {
						$desc = ($lines[$i]->desc ? $lines[$i]->desc : '');
						// If we build one invoice for several orders, we must put the ref of order on the invoice line
						if (!empty($createbills_onebythird)) {
							$desc = dol_concatdesc($desc, $langs->trans("Order").' '.$cmd->ref.' - '.dol_print_date($cmd->date, 'day'));
						}

						if ($lines[$i]->subprice < 0 && !getDolGlobalString('INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN')) {
							// Negative line, we create a discount line
							$discount = new DiscountAbsolute($db);
							$discount->fk_soc = $objecttmp->socid;
							$discount->socid = $objecttmp->socid;
							$discount->amount_ht = abs($lines[$i]->total_ht);
							$discount->amount_tva = abs($lines[$i]->total_tva);
							$discount->amount_ttc = abs($lines[$i]->total_ttc);
							$discount->tva_tx = $lines[$i]->tva_tx;
							$discount->fk_user = $user->id;
							$discount->description = $desc;
							$discountid = $discount->create($user);
							if ($discountid > 0) {
								$result = $objecttmp->insert_discount($discountid);
								//$result=$discount->link_to_invoice($lineid,$id);
							} else {
								setEventMessages($discount->error, $discount->errors, 'errors');
								$error++;
								break;
							}
						} else {
							// Positive line
							$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
							// Date start
							$date_start = false;
							if ($lines[$i]->date_debut_prevue) {
								$date_start = $lines[$i]->date_debut_prevue;
							}
							if ($lines[$i]->date_debut_reel) {
								$date_start = $lines[$i]->date_debut_reel;
							}
							if ($lines[$i]->date_start) {
								$date_start = $lines[$i]->date_start;
							}
							//Date end
							$date_end = false;
							if ($lines[$i]->date_fin_prevue) {
								$date_end = $lines[$i]->date_fin_prevue;
							}
							if ($lines[$i]->date_fin_reel) {
								$date_end = $lines[$i]->date_fin_reel;
							}
							if ($lines[$i]->date_end) {
								$date_end = $lines[$i]->date_end;
							}
							// Reset fk_parent_line for no child products and special product
							if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
								$fk_parent_line = 0;
							}

							// Extrafields
							if (method_exists($lines[$i], 'fetch_optionals')) {
								$lines[$i]->fetch_optionals();
								$array_options = $lines[$i]->array_options;
							}

							$objecttmp->context['createfromclone'] = 'createfromclone';

							$rang = ($nbOrders > 1) ? -1 : $lines[$i]->rang;
							//there may already be rows from previous orders
							if (!empty($createbills_onebythird)) {
								$TFactThirdNbLines[$cmd->socid]++;
								$rang = $TFactThirdNbLines[$cmd->socid];
							}

							$result = $objecttmp->addline(
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->qty,
								$lines[$i]->tva_tx,
								$lines[$i]->localtax1_tx,
								$lines[$i]->localtax2_tx,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								$date_start,
								$date_end,
								0,
								$lines[$i]->info_bits,
								$lines[$i]->fk_remise_except,
								'HT',
								0,
								$product_type,
								$rang,
								$lines[$i]->special_code,
								$objecttmp->origin,
								$lines[$i]->rowid,
								$fk_parent_line,
								$lines[$i]->fk_fournprice,
								$lines[$i]->pa_ht,
								$lines[$i]->label,
								$array_options,
								100,
								0,
								$lines[$i]->fk_unit
							);
							if ($result > 0) {
								$lineid = $result;
							} else {
								$lineid = 0;
								$error++;
								$errors[] = $objecttmp->error;
								break;
							}
							// Defined the new fk_parent_line
							if ($result > 0 && $lines[$i]->product_type == 9) {
								$fk_parent_line = $result;
							}
						}
					}
				}
			}

			if ($currentIndex <= getDolGlobalInt("MAXREFONDOC", 10)) {
				$objecttmp->note_public = dol_concatdesc($objecttmp->note_public, $langs->transnoentities($cmd->ref).(empty($cmd->ref_client) ? '' : ' ('.$cmd->ref_client.')'));
				$objecttmp->update($user);
			}

			//$cmd->classifyBilled($user);        // Disabled. This behavior must be set or not using the workflow module.

			if (!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) {
				$TFactThird[$cmd->socid] = $objecttmp;
			} else {
				$TFact[$objecttmp->id] = $objecttmp;
			}
		}

		// Build doc with all invoices
		$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
		$toselect = array();

		if (!$error && $validate_invoices) {
			$massaction = $action = 'builddoc';

			foreach ($TAllFact as &$objecttmp) {
				$result = $objecttmp->validate($user);
				if ($result <= 0) {
					$error++;
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					break;
				}

				$id = $objecttmp->id; // For builddoc action

				// Builddoc
				$donotredirect = 1;
				$upload_dir = $conf->facture->dir_output;
				$permissiontoadd = $user->hasRight('facture', 'creer');

				// Call action to build doc
				$savobject = $object;
				$object = $objecttmp;
				include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
				$object = $savobject;
			}

			$massaction = $action = 'confirm_createbills';
		}

		if (!$error) {
			$db->commit();

			if ($nb_bills_created == 1) {
				if (getDolGlobalInt('MAIN_MASSACTION_CREATEBILLS_REDIRECT_IF_ONE') == 1) {
					// Redirect to generated invoice if unique
					header('Location: '.DOL_URL_ROOT.'/compta/facture/card.php?id='.urlencode((string) $lastid));
					exit;
				}
				$texttoshow = $langs->trans('BillXCreated', '{s1}');
				$texttoshow = str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.urlencode((string) ($lastid)).'">'.$lastref.'</a>', $texttoshow);
				setEventMessages($texttoshow, null, 'mesgs');
			} else {
				if (getDolGlobalInt('MAIN_MASSACTION_CREATEBILLS_REDIRECT_IF_MANY') == 1) {
					// Redirect to invoice list
					header("Location: ".DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=billing&leftmenu=customers_bills');
					exit;
				}
				setEventMessages($langs->trans('BillCreated', $nb_bills_created), null, 'mesgs');
			}

			// Make a redirect to avoid to bill twice if we make a refresh or back
			$param = '';
			if (!empty($mode)) {
				$param .= '&mode='.urlencode($mode);
			}
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
				$param .= '&contextpage='.urlencode($contextpage);
			}
			if ($limit > 0 && $limit != $conf->liste_limit) {
				$param .= '&limit='.((int) $limit);
			}
			if ($optioncss != '') {
				$param .= '&optioncss='.urlencode($optioncss);
			}
			if ($search_all) {
				$param .= '&search_all='.urlencode($search_all);
			}
			if ($show_files) {
				$param .= '&show_files='.urlencode((string) ($show_files));
			}
			if ($socid > 0) {
				$param .= '&socid='.urlencode((string) ($socid));
			}
			if ($search_status != '') {
				$param .= '&search_status='.urlencode($search_status);
			}
			if ($search_option) {
				$param .= "&search_option=".urlencode($search_option);
			}
			if ($search_orderday) {
				$param .= '&search_orderday='.urlencode($search_orderday);
			}
			if ($search_ordermonth) {
				$param .= '&search_ordermonth='.urlencode($search_ordermonth);
			}
			if ($search_orderyear) {
				$param .= '&search_orderyear='.urlencode($search_orderyear);
			}
			if ($search_deliveryday) {
				$param .= '&search_deliveryday='.urlencode($search_deliveryday);
			}
			if ($search_deliverymonth) {
				$param .= '&search_deliverymonth='.urlencode($search_deliverymonth);
			}
			if ($search_deliveryyear) {
				$param .= '&search_deliveryyear='.urlencode($search_deliveryyear);
			}
			if ($search_id) {
				$param .= '&search_id='.urlencode((string) $search_id);
			}
			if ($search_ref) {
				$param .= '&search_ref='.urlencode($search_ref);
			}
			if ($search_ref_ext) {
				$param .= '&search_ref_ext='.urlencode($search_ref_ext);
			}
			if ($search_company) {
				$param .= '&search_company='.urlencode($search_company);
			}
			if ($search_ref_customer) {
				$param .= '&search_ref_customer='.urlencode($search_ref_customer);
			}
			if ($search_user > 0) {
				$param .= '&search_user='.urlencode((string) ($search_user));
			}
			if ($search_sale > 0) {
				$param .= '&search_sale='.urlencode((string) ($search_sale));
			}
			if ($search_total_ht != '') {
				$param .= '&search_total_ht='.urlencode($search_total_ht);
			}
			if ($search_total_vat != '') {
				$param .= '&search_total_vat='.urlencode($search_total_vat);
			}
			if ($search_total_ttc != '') {
				$param .= '&search_total_ttc='.urlencode($search_total_ttc);
			}
			if ($search_project_ref >= 0) {
				$param .= "&search_project_ref=".urlencode($search_project_ref);
			}
			if ($search_project != '') {
				$param .= "&search_project=".urlencode($search_project);
			}
			if ($search_billed != '') {
				$param .= '&search_billed='.urlencode($search_billed);
			}

			header("Location: ".$_SERVER['PHP_SELF'].'?'.$param);
			exit;
		} else {
			$db->rollback();

			$action = 'create';
			$_GET["origin"] = $_POST["origin"];		// Keep GET and POST here ?
			$_GET["originid"] = $_POST["originid"]; // Keep GET and POST here ?
			if (!empty($errors)) {
				setEventMessages(null, $errors, 'errors');
			} else {
				setEventMessages("Error", null, 'errors');
			}
			$error++;
		}
	}
}

if ($action == 'validate' && $permissiontoadd && $objectclass !== null) {
	if (GETPOST('confirm') == 'yes') {
		/** @var Commande $objecttmp */
		$objecttmp = new $objectclass($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($objecttmp->fetch($checked)) {
				if ($objecttmp->status == $objecttmp::STATUS_DRAFT) {
					if (!empty($objecttmp->fk_warehouse)) {
						$idwarehouse = $objecttmp->fk_warehouse;
					} else {
						$idwarehouse = 0;
					}
					if ($objecttmp->valid($user, $idwarehouse)) {
						setEventMessages($langs->trans('hasBeenValidated', $objecttmp->ref), null, 'mesgs');
					} else {
						setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
						$error++;
					}
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorIsNotADraft', $objecttmp->ref), null, 'errors');
					$error++;
				}
			} else {
				dol_print_error($db);
				$error++;
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}
if ($action == 'shipped' && $permissiontoadd && $objectclass !== null) {
	if (GETPOST('confirm') == 'yes') {
		/** @var Commande $objecttmp */
		$objecttmp = new $objectclass($db);
		$db->begin();
		$error = 0;
		foreach ($toselect as $checked) {
			if ($objecttmp->fetch($checked)) {
				if ($objecttmp->status == $objecttmp::STATUS_VALIDATED || $objecttmp->status == $objecttmp::STATUS_SHIPMENTONPROCESS || $objecttmp->status == $objecttmp::STATUS_CLOSED) {
					$result = $objecttmp->cloture($user);
					if ($result > 0) {
						setEventMessages($langs->trans('StatusOrderDelivered', $objecttmp->ref), null, 'mesgs');
					} elseif ($result < 0) {
						setEventMessages($langs->trans('ErrorOrderStatusCantBeSetToDelivered'), null, 'errors');
						$error++;
					}
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorObjectHasWrongStatus', $objecttmp->ref), null, 'errors');
					$error++;
				}
			} else {
				dol_print_error($db);
				$error++;
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}
}

// Closed records
if (!$error && $massaction === 'setbilled' && $permissiontoclose && $objectclass !== null) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			$result = $objecttmp->classifyBilled($user, 0);
			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		$db->commit();
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = null;
if (isModEnabled('margin')) {
	$formmargin = new FormMargin($db);
}
$companystatic = new Societe($db);
$company_url_list = array();
$formcompany = new FormCompany($db);
$projectstatic = new Project($db);

$now = dol_now();

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$title = $langs->trans("Orders");
$help_url = "EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT';
if ($search_all) {
	$sql = 'SELECT DISTINCT';
}
$sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client,';
$sql .= " s.parent as fk_parent,";
$sql .= " s2.nom as name2,";
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " country.code as country_code,";
$sql .= ' c.rowid, c.ref, c.ref_ext, c.total_ht, c.total_tva, c.total_ttc, c.ref_client, c.fk_user_author,';
$sql .= ' c.fk_multicurrency, c.multicurrency_code, c.multicurrency_tx, c.multicurrency_total_ht, c.multicurrency_total_tva as multicurrency_total_vat, c.multicurrency_total_ttc,';
$sql .= ' c.date_valid, c.date_commande, c.note_public, c.note_private, c.date_livraison as delivery_date, c.fk_statut, c.facture as billed,';
$sql .= ' c.date_creation as date_creation, c.tms as date_modification, c.date_cloture as date_cloture,';
$sql .= ' c.fk_cond_reglement,c.deposit_percent,c.fk_mode_reglement,c.fk_shipping_method,';
$sql .= ' c.fk_input_reason, c.import_key,';
$sql .= " c.module_source, c.pos_source,";
$sql .= ' p.rowid as project_id, p.ref as project_ref, p.title as project_label,';
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s2 ON s2.rowid = s.parent";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= ', '.MAIN_DB_PREFIX.'commande as c';
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ef on (c.rowid = ef.fk_object)";
}
if ($search_all) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid = pd.fk_commande';
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = c.fk_projet";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON c.fk_user_author = u.rowid';
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE c.fk_soc = s.rowid';
$sql .= ' AND c.entity IN ('.getEntity('commande').')';
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
// Restriction on sale representative
if (empty($user->socid) && !$permissiontoreadallthirdparty) {
	$sql .= " AND (EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $user->id).")";
	if (getDolGlobalInt('MAIN_SEE_SUBORDINATES') && $userschilds) {
		$sql .= " OR EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user IN (".$db->sanitize(implode(',', $userschilds))."))";
	}
	$sql .= ")";
}

if ($search_id > 0) {
	$sql .= natural_search('c.rowid', $search_id);
}
if ($search_ref) {
	$sql .= natural_search('c.ref', $search_ref);
}
if ($search_ref_ext) {
	$sql .= natural_search('c.ref_ext', $search_ref_ext);
}
if ($search_ref_customer) {
	$sql .= natural_search('c.ref_client', $search_ref_customer);
}
if ($search_billed != '' && $search_billed >= 0) {
	$sql .= ' AND c.facture = '.((int) $search_billed);
}
if ($search_status != '') {
	if ($search_status <= 3 && $search_status >= -1) {	// status from -1 to 3 are real status (other are virtual combination)
		if ($search_status == 1 && !isModEnabled('shipping')) {
			$sql .= ' AND c.fk_statut IN (1,2)'; // If module expedition disabled, we include order with status "sent" into "validated"
		} else {
			$sql .= ' AND c.fk_statut = '.((int) $search_status); // draft, validated, in process or canceled
		}
	}
	if ($search_status == -2) {	// "validated + in progress"
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql .= " AND (c.fk_statut IN (1,2))";
	}
	if ($search_status == -3) {	// "validated + in progress + shipped"
		//$sql.= ' AND c.fk_statut in (1,2,3)';
		//$sql.= ' AND c.facture = 0'; // invoice not created
		$sql .= ' AND (c.fk_statut IN (1,2,3))'; // validated, in process or closed
	}
}
if ($search_option == 'late') {
	$sql .= " AND c.date_commande < '".$db->idate(dol_now() - $conf->order->client->warning_delay)."'";
}
if ($search_datecloture_start) {
	$sql .= " AND c.date_cloture >= '".$db->idate($search_datecloture_start)."'";
}
if ($search_datecloture_end) {
	$sql .= " AND c.date_cloture <= '".$db->idate($search_datecloture_end)."'";
}
if ($search_dateorder_start) {
	$sql .= " AND c.date_commande >= '".$db->idate($search_dateorder_start)."'";
}
if ($search_dateorder_end) {
	$sql .= " AND c.date_commande <= '".$db->idate($search_dateorder_end)."'";
}
if ($search_datedelivery_start) {
	$sql .= " AND c.date_livraison >= '".$db->idate($search_datedelivery_start)."'";
}
if ($search_datedelivery_end) {
	$sql .= " AND c.date_livraison <= '".$db->idate($search_datedelivery_end)."'";
}
if ($search_town) {
	$sql .= natural_search('s.town', $search_town);
}
if ($search_zip) {
	$sql .= natural_search("s.zip", $search_zip);
}
if ($search_state) {
	$sql .= natural_search("state.nom", $search_state);
}
if ($search_country) {
	$sql .= " AND s.fk_pays IN (".$db->sanitize($search_country).')';
}
if ($search_type_thirdparty && $search_type_thirdparty != '-1') {
	$sql .= " AND s.fk_typent IN (".$db->sanitize($search_type_thirdparty).')';
}
if (empty($arrayfields['s.name_alias']['checked']) && $search_company) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_company);
} else {
	if ($search_company) {
		$sql .= natural_search('s.nom', $search_company);
	}
	if ($search_company_alias) {
		$sql .= natural_search('s.name_alias', $search_company_alias);
	}
}
if ($search_parent_name) {
	$sql .= natural_search('s2.nom', $search_parent_name);
}
if ($search_total_ht != '') {
	$sql .= natural_search('c.total_ht', $search_total_ht, 1);
}
if ($search_total_vat != '') {
	$sql .= natural_search('c.total_tva', $search_total_vat, 1);
}
if ($search_total_ttc != '') {
	$sql .= natural_search('c.total_ttc', $search_total_ttc, 1);
}
if ($search_warehouse != '' && $search_warehouse > 0) {
	$sql .= natural_search('c.fk_warehouse', $search_warehouse, 1);
}
if ($search_note_public != '') {
	$sql .= natural_search('c.note_public', $search_note_public);
}
if ($search_note_private != '') {
	$sql .= natural_search('c.note_private', $search_note_private);
}
if ($search_multicurrency_code != '') {
	$sql .= " AND c.multicurrency_code = '".$db->escape($search_multicurrency_code)."'";
}
if ($search_multicurrency_tx != '') {
	$sql .= natural_search('c.multicurrency_tx', $search_multicurrency_tx, 1);
}
if ($search_multicurrency_montant_ht != '') {
	$sql .= natural_search('c.multicurrency_total_ht', $search_multicurrency_montant_ht, 1);
}
if ($search_multicurrency_montant_vat != '') {
	$sql .= natural_search('c.multicurrency_total_tva', $search_multicurrency_montant_vat, 1);
}
if ($search_multicurrency_montant_ttc != '') {
	$sql .= natural_search('c.multicurrency_total_ttc', $search_multicurrency_montant_ttc, 1);
}
if ($search_login) {
	$sql .= natural_search(array("u.login", "u.firstname", "u.lastname"), $search_login);
}
if ($search_project_ref != '') {
	$sql .= natural_search("p.ref", $search_project_ref);
}
if ($search_project != '') {
	$sql .= natural_search("p.title", $search_project);
}
if ($search_fk_cond_reglement > 0) {
	$sql .= " AND c.fk_cond_reglement = ".((int) $search_fk_cond_reglement);
}
if ($search_fk_shipping_method > 0) {
	$sql .= " AND c.fk_shipping_method = ".((int) $search_fk_shipping_method);
}
if ($search_fk_mode_reglement > 0) {
	$sql .= " AND c.fk_mode_reglement = ".((int) $search_fk_mode_reglement);
}
if ($search_fk_input_reason > 0) {
	$sql .= " AND c.fk_input_reason = ".((int) $search_fk_input_reason);
}
if ($search_module_source) {
	$sql .= natural_search("c.module_source", $search_module_source);
}
if ($search_pos_source) {
	$sql .= natural_search("c.pos_source", $search_pos_source);
}
if ($search_import_key) {
	$sql .= natural_search("s.import_key", $search_import_key);
}
// Search on user
if ($search_user > 0) {
	$sql .= " AND EXISTS (";
	$sql .= " SELECT ec.fk_c_type_contact, ec.element_id, ec.fk_socpeople";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= " INNER JOIN  ".MAIN_DB_PREFIX."c_type_contact as tc";
	$sql .= " ON ec.fk_c_type_contact = tc.rowid AND tc.element = 'commande' AND tc.source = 'internal'";
	$sql .= " WHERE ec.element_id = c.rowid AND ec.fk_socpeople = ".((int) $search_user).")";
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}

// Search for tag/category ($searchCategoryOrderList is an array of ID)
if (!empty($searchCategoryOrderList)) {
	$searchCategoryOrderSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryOrderList as $searchCategoryOrder) {
		if (intval($searchCategoryOrder) == -2) {
			$searchCategoryOrderSqlList[] = "NOT EXISTS (SELECT ck.fk_order FROM ".MAIN_DB_PREFIX."categorie_order as ck WHERE c.rowid = ck.fk_order)";
		} elseif (intval($searchCategoryOrder) > 0) {
			if ($searchCategoryOrderOperator == 0) {
				$searchCategoryOrderSqlList[] = " EXISTS (SELECT ck.fk_order FROM ".MAIN_DB_PREFIX."categorie_order as ck WHERE c.rowid = ck.fk_order AND ck.fk_categorie = ".((int) $searchCategoryOrder).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryOrder);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryOrderSqlList[] = " EXISTS (SELECT ck.fk_order FROM ".MAIN_DB_PREFIX."categorie_order as ck WHERE c.rowid = ck.fk_order AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryOrderOperator == 1) {
		if (!empty($searchCategoryOrderSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryOrderSqlList).")";
		}
	} else {
		if (!empty($searchCategoryOrderSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryOrderSqlList).")";
		}
	}
}

// Search for tag/category ($searchCategoryCustomerList is an array of ID)
$searchCategoryCustomerOperator = GETPOSTINT('search_category_customer_operator');
$searchCategoryCustomerList = array($search_categ_cus);
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT cs.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as cs WHERE s.rowid = cs.fk_soc AND cs.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryCustomerOperator == 1) {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
		}
	} else {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
		}
	}
}
// Search for tag/category ($searchCategoryProductList is an array of ID)
$searchCategoryProductOperator = GETPOSTINT('search_category_product_operator');
$searchCategoryProductList = array($search_product_category);
if (!empty($searchCategoryProductList)) {
	$searchCategoryProductSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commandedet as cd WHERE cd.fk_commande = c.rowid AND cd.fk_product = ck.fk_product)";
		} elseif (intval($searchCategoryProduct) > 0) {
			if ($searchCategoryProductOperator == 0) {
				$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commandedet as cd WHERE cd.fk_commande = c.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie = ".((int) $searchCategoryProduct).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProduct);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProductSqlList[] = " EXISTS (SELECT ck.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as ck, ".MAIN_DB_PREFIX."commandedet as cd WHERE cd.fk_commande = c.rowid AND cd.fk_product = ck.fk_product AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryProductOperator == 1) {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
		}
	} else {
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
		}
	}
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add HAVING from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;
//print $sql;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print $sql;

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title = $langs->trans('CustomersOrders').' - '.$soc->name;
	if (empty($search_company)) {
		$search_company = $soc->name;
	}
} else {
	$title = $langs->trans('CustomersOrders');
}
if (strval($search_status) == '0') {
	$title .= ' - '.$langs->trans('StatusOrderDraftShort');
}
if ($search_status == 1) {
	$title .= ' - '.$langs->trans('StatusOrderValidatedShort');
}
if ($search_status == 2) {
	$title .= ' - '.$langs->trans('StatusOrderSentShort');
}
if ($search_status == 3) {
	$title .= ' - '.$langs->trans('StatusOrderToBillShort');
}
if ($search_status == -1) {
	$title .= ' - '.$langs->trans('StatusOrderCanceledShort');
}
if ($search_status == -2) {
	$title .= ' - '.$langs->trans('StatusOrderToProcessShort');
}
if ($search_status == -3) {
	$title .= ' - '.$langs->trans('StatusOrderValidated').', '.(!isModEnabled('shipping') ? '' : $langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill');
}
if ($search_status == -4) {
	$title .= ' - '.$langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort");
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/commande/card.php?id='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-order page-list');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($show_files) {
	$param .= '&show_files='.urlencode((string) ($show_files));
}
if ($search_all) {
	$param .= '&search_all='.urlencode($search_all);
}
if ($socid > 0) {
	$param .= '&socid='.((int) $socid);
}
if ($search_status != '') {
	$param .= '&search_status='.urlencode($search_status);
}
if ($search_option) {
	$param .= "&search_option=".urlencode($search_option);
}
if ($search_datecloture_start) {
	$param .= '&search_datecloture_startday='.dol_print_date($search_datecloture_start, '%d').'&search_datecloture_startmonth='.dol_print_date($search_datecloture_start, '%m').'&search_datecloture_startyear='.dol_print_date($search_datecloture_start, '%Y');
}
if ($search_datecloture_end) {
	$param .= '&search_datecloture_endday='.dol_print_date($search_datecloture_end, '%d').'&search_datecloture_endmonth='.dol_print_date($search_datecloture_end, '%m').'&search_datecloture_endyear='.dol_print_date($search_datecloture_end, '%Y');
}
if ($search_dateorder_start) {
	$param .= '&search_dateorder_start_day='.dol_print_date($search_dateorder_start, '%d').'&search_dateorder_start_month='.dol_print_date($search_dateorder_start, '%m').'&search_dateorder_start_year='.dol_print_date($search_dateorder_start, '%Y');
}
if ($search_dateorder_end) {
	$param .= '&search_dateorder_end_day='.dol_print_date($search_dateorder_end, '%d').'&search_dateorder_end_month='.dol_print_date($search_dateorder_end, '%m').'&search_dateorder_end_year='.dol_print_date($search_dateorder_end, '%Y');
}
if ($search_datedelivery_start) {
	$param .= '&search_datedelivery_start_day='.dol_print_date($search_datedelivery_start, '%d').'&search_datedelivery_start_month='.dol_print_date($search_datedelivery_start, '%m').'&search_datedelivery_start_year='.dol_print_date($search_datedelivery_start, '%Y');
}
if ($search_datedelivery_end) {
	$param .= '&search_datedelivery_end_day='.dol_print_date($search_datedelivery_end, '%d').'&search_datedelivery_end_month='.dol_print_date($search_datedelivery_end, '%m').'&search_datedelivery_end_year='.dol_print_date($search_datedelivery_end, '%Y');
}
if ($search_id) {
	$param .= '&search_id='.urlencode((string) $search_id);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_ref_ext) {
	$param .= '&search_ref_ext='.urlencode($search_ref_ext);
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_company_alias) {
	$param .= '&search_company_alias='.urlencode($search_company_alias);
}
if ($search_parent_name != '') {
	$param .= '&search_parent_name='.urlencode($search_parent_name);
}
if ($search_ref_customer) {
	$param .= '&search_ref_customer='.urlencode($search_ref_customer);
}
if ($search_user > 0) {
	$param .= '&search_user='.urlencode((string) ($search_user));
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode((string) ($search_sale));
}
if ($search_total_ht != '') {
	$param .= '&search_total_ht='.urlencode($search_total_ht);
}
if ($search_total_vat != '') {
	$param .= '&search_total_vat='.urlencode($search_total_vat);
}
if ($search_total_ttc != '') {
	$param .= '&search_total_ttc='.urlencode($search_total_ttc);
}
if ($search_warehouse != '') {
	$param .= '&search_warehouse='.urlencode((string) ($search_warehouse));
}
if ($search_login) {
	$param .= '&search_login='.urlencode($search_login);
}
if ($search_multicurrency_code != '') {
	$param .= '&search_multicurrency_code='.urlencode($search_multicurrency_code);
}
if ($search_multicurrency_tx != '') {
	$param .= '&search_multicurrency_tx='.urlencode($search_multicurrency_tx);
}
if ($search_multicurrency_montant_ht != '') {
	$param .= '&search_multicurrency_montant_ht='.urlencode($search_multicurrency_montant_ht);
}
if ($search_multicurrency_montant_vat != '') {
	$param .= '&search_multicurrency_montant_vat='.urlencode($search_multicurrency_montant_vat);
}
if ($search_multicurrency_montant_ttc != '') {
	$param .= '&search_multicurrency_montant_ttc='.urlencode($search_multicurrency_montant_ttc);
}
if ($search_project_ref >= 0) {
	$param .= "&search_project_ref=".urlencode($search_project_ref);
}
if ($search_project != '') {
	$param .= "&search_project=".urlencode($search_project);
}
if ($search_town != '') {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_zip != '') {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($search_state != '') {
	$param .= '&search_state='.urlencode($search_state);
}
if ($search_country != '') {
	$param .= '&search_country='.urlencode((string) ($search_country));
}
if ($search_type_thirdparty && $search_type_thirdparty != '-1') {
	$param .= '&search_type_thirdparty='.urlencode((string) ($search_type_thirdparty));
}
if ($searchCategoryOrderOperator == 1) {
	$param .= "&search_category_order_operator=".urlencode((string) ($searchCategoryOrderOperator));
}
foreach ($searchCategoryOrderList as $searchCategoryOrder) {
	$param .= "&search_category_order_list[]=".urlencode($searchCategoryOrder);
}
if ($search_product_category != '') {
	$param .= '&search_product_category='.urlencode((string) ($search_product_category));
}
if (($search_categ_cus > 0) || ($search_categ_cus == -2)) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}
if ($search_billed != '') {
	$param .= '&search_billed='.urlencode($search_billed);
}
if ($search_fk_cond_reglement > 0) {
	$param .= '&search_fk_cond_reglement='.urlencode((string) ($search_fk_cond_reglement));
}
if ($search_fk_shipping_method > 0) {
	$param .= '&search_fk_shipping_method='.urlencode((string) ($search_fk_shipping_method));
}
if ($search_fk_mode_reglement > 0) {
	$param .= '&search_fk_mode_reglement='.urlencode((string) ($search_fk_mode_reglement));
}
if ($search_fk_input_reason > 0) {
	$param .= '&search_fk_input_reason='.urlencode((string) ($search_fk_input_reason));
}
if ($search_module_source) {
	$param .= '&search_module_source='.urlencode($search_module_source);
}
if ($search_pos_source) {
	$param .= '&search_pos_source='.urlencode($search_pos_source);
}
if ($search_import_key != '') {
	$param .= '&search_import_key='.urlencode($search_import_key);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	'builddoc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
if ($permissiontovalidate) {
	$arrayofmassactions['prevalidate'] = img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate");
}
if ($permissiontoclose) {
	$arrayofmassactions['preshipped'] = img_picto('', 'dolly', 'class="pictofixedwidth"').$langs->trans("ClassifyShipped");
}
if (isModEnabled('invoice') && $user->hasRight("facture", "creer")) {
	$arrayofmassactions['createbills'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("CreateInvoiceForThisCustomer");
}
if ($permissiontoclose) {
	$arrayofmassactions['setbilled'] = img_picto('', 'bill', 'class="pictofixedwidth"').$langs->trans("ClassifyBilled");
}
if ($permissiontocancel) {
	$arrayofmassactions['cancelorders'] = img_picto('', 'close_title', 'class="pictofixedwidth"').$langs->trans("CancelOrder");
}
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if ($permissiontosendbymail) {
	$arrayofmassactions['presend'] = img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail");
}
if (in_array($massaction, array('presend', 'predelete', 'createbills'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/commande/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewOrder'), '', 'fa fa-plus-circle', $url, '', (int) (($contextpage == 'orderlist' || $contextpage == 'billableorders') && $permissiontoadd));

// Lines of title fields
print '<form method="POST" id="searchFormList" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="search_status" value="'.$search_status.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'order', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "SendOrderRef";
$modelmail = "order_send";
$objecttmp = new Commande($db);
$trackid = 'ord'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'prevalidate') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassValidation"), $langs->trans("ConfirmMassValidationQuestion"), "validate", null, '', 0, 200, 500, 1);
}
if ($massaction == 'preshipped') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("CloseOrder"), $langs->trans("ConfirmCloseOrder"), "shipped", null, '', 0, 200, 500, 1);
}

if ($massaction == 'createbills') {
	print '<input type="hidden" name="massaction" value="confirm_createbills">';

	print '<table class="noborder centpercent">';
	print '<tr>';
	print '<td>';
	print $langs->trans('DateInvoice');
	print '</td>';
	print '<td>';
	print $form->selectDate('', '', 0, 0, 0, '', 1, 1);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print $langs->trans('CreateOneBillByThird');
	print '</td>';
	print '<td>';
	print $form->selectyesno('createbills_onebythird', getDolGlobalString('MAIN_ORDERLIST_CREATEBILLS_ONEBYTHIRD', 'no'), 1);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print $langs->trans('ValidateInvoices');
	print '</td>';
	print '<td>';
	if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
		print $form->selectyesno('validate_invoices', 0, 1, 1);
		$langs->load("errors");
		print ' ('.$langs->trans("WarningAutoValNotPossibleWhenStockIsDecreasedOnInvoiceVal").')';
	} else {
		print $form->selectyesno('validate_invoices', 0, 1);
	}
	if (!empty($conf->workflow->enabled) && getDolGlobalString('WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER')) {
		print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("IfValidateInvoiceIsNoOrderStayUnbilled").'</span>';
	} else {
		print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("OptionToSetOrderBilledNotEnabled").'</span>';
	}
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
	print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '<br><br>';
}

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

if (isModEnabled('category') && $user->hasRight('categorie', 'read')) {
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_ORDER, $searchCategoryOrderList, 'minwidth300', $searchCategoryOrderOperator ? $searchCategoryOrderOperator : 0);
}

// If the user can view prospects? sales other than his own
if ($user->hasRight("user", "user", "lire")) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}
// If the user can view other users
if ($user->hasRight("user", "user", "lire")) {
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('LinkedToSpecificUsers');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_user, 'search_user', $tmptitle, null, 0, '', '', '0', 0, 0, '', 0, '', 'maxwidth250 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}

// If the user can view other products/services than his own
if (isModEnabled('category') && $user->hasRight("categorie", "lire") && ($user->hasRight("produit", "lire") || $user->hasRight("service", "lire"))) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('IncludingProductWithTag');
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 0, array(), 1);
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_product_category', $cate_arbo, $search_product_category, $tmptitle, 0, 0, '', 0, 0, 0, '', 'maxwidth300 widthcentpercentminusx', 1);
	$moreforfilter .= '</div>';
}
// If Categories are enabled & user has rights to see
if (isModEnabled('category') && $user->hasRight("categorie", "lire")) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('CustomersProspectsCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1, $tmptitle, 'maxwidth300 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}
// If Stock is enabled
if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	$formproduct = new FormProduct($db);
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('Warehouse');
	$moreforfilter .= img_picto($tmptitle, 'stock', 'class="pictofixedwidth"').$formproduct->selectWarehouses($search_warehouse, 'search_warehouse', '', 1, 0, 0, $tmptitle, 0, 0, array(), 'maxwidth250 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

if (GETPOSTINT('autoselectall')) {
	$selectedfields .= '<script>';
	$selectedfields .= '   $(document).ready(function() {';
	$selectedfields .= '        console.log("Autoclick on checkforselects");';
	$selectedfields .= '   		$("#checkforselects").click();';
	$selectedfields .= '        $("#massaction").val("createbills").change();';
	$selectedfields .= '   });';
	$selectedfields .= '</script>';
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch actioncolumn">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

// Line numbering
if (!empty($arrayfields['c.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_id" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}

// Ref
if (!empty($arrayfields['c.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
// Ref ext
if (!empty($arrayfields['c.ref_ext']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50imp" type="text" name="search_ref_ext" value="'.dol_escape_htmltag($search_ref_ext).'">';
	print '</td>';
}
// Ref customer
if (!empty($arrayfields['c.ref_client']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="6" name="search_ref_customer" value="'.dol_escape_htmltag($search_ref_customer).'">';
	print '</td>';
}
// Project ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
}
// Project label
if (!empty($arrayfields['p.title']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project" value="'.dol_escape_htmltag($search_project).'"></td>';
}
// Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75imp" type="text" name="search_company" value="'.dol_escape_htmltag((string) $search_company).'"'.(!empty($user->socid) ? " disabled" : "").'>';
	print '</td>';
}
// Alias
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75imp" type="text" name="search_company_alias" value="'.dol_escape_htmltag($search_company_alias).'">';
	print '</td>';
}
// Parent company
if (!empty($arrayfields['s2.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_parent_name" value="'.dol_escape_htmltag($search_parent_name).'">';
	print '</td>';
}
// Town
if (!empty($arrayfields['s.town']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth75imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}
// Zip
if (!empty($arrayfields['s.zip']['checked'])) {
	print '<td class="liste_titre"><input class="flat maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}
// State
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50imp" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre" align="center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
// Company type
if (!empty($arrayfields['typent.code']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone" align="center">';
	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 1, 0, 0, '', 0, 0, 0, (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), '', 1);
	print '</td>';
}
// Date order
if (!empty($arrayfields['c.date_commande']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_dateorder_start ? $search_dateorder_start : -1, 'search_dateorder_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_dateorder_end ? $search_dateorder_end : -1, 'search_dateorder_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['c.delivery_date']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datedelivery_start ? $search_datedelivery_start : -1, 'search_datedelivery_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datedelivery_end ? $search_datedelivery_end : -1, 'search_datedelivery_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Shipping Method
if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
	print '<td class="liste_titre">';
	$form->selectShippingMethod($search_fk_shipping_method, 'search_fk_shipping_method', '', 1, '', 1);
	print '</td>';
}
// Payment term
if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
	print '<td class="liste_titre">';
	print $form->getSelectConditionsPaiements((int) $search_fk_cond_reglement, 'search_fk_cond_reglement', 1, 1, 1);
	print '</td>';
}
// Payment mode
if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_types_paiements($search_fk_mode_reglement, 'search_fk_mode_reglement', '', 0, 1, 1, 0, -1, '', 1);
	print '</td>';
}
// Channel
if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
	print '<td class="liste_titre">';
	$form->selectInputReason($search_fk_input_reason, 'search_fk_input_reason', '', 1, '', 1);
	print '</td>';
}
// Module source
if (!empty($arrayfields['c.module_source']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_module_source" value="'.dol_escape_htmltag($search_module_source).'">';
	print '</td>';
}
// POS Terminal
if (!empty($arrayfields['c.pos_source']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_pos_source" value="'.dol_escape_htmltag($search_pos_source).'">';
	print '</td>';
}
// Amount HT / net
if (!empty($arrayfields['c.total_ht']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_total_ht" value="'.dol_escape_htmltag($search_total_ht).'">';
	print '</td>';
}
// Amount of VAT
if (!empty($arrayfields['c.total_vat']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_total_vat" value="'.dol_escape_htmltag($search_total_vat).'">';
	print '</td>';
}
// Total Amount (TTC / gross)
if (!empty($arrayfields['c.total_ttc']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
	print '</td>';
}
// Currency
if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
	print '<td class="liste_titre">';
	print $form->selectMultiCurrency($search_multicurrency_code, 'search_multicurrency_code', 1);
	print '</td>';
}
// Currency rate
if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_tx" value="'.dol_escape_htmltag($search_multicurrency_tx).'">';
	print '</td>';
}
// Amount HT/net in foreign currency
if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_ht" value="'.dol_escape_htmltag($search_multicurrency_montant_ht).'">';
	print '</td>';
}
// VAT in foreign currency
if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_multicurrency_montant_vat" value="'.dol_escape_htmltag($search_multicurrency_montant_vat).'">';
	print '</td>';
}
// Amount/Total (TTC / gross) in foreign currency
if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat width75" type="text" name="search_multicurrency_montant_ttc" value="'.dol_escape_htmltag($search_multicurrency_montant_ttc).'">';
	print '</td>';
}
// Author
if (!empty($arrayfields['u.login']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat width75" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
	print '</td>';
}
// Sales Representative
if (!empty($arrayfields['sale_representative']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['total_pa']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_margin']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_margin_rate']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}
if (!empty($arrayfields['total_mark_rate']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}

// Date creation
if (!empty($arrayfields['c.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['c.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date closing
if (!empty($arrayfields['c.date_cloture']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datecloture_start ? $search_datecloture_start : -1, 'search_datecloture_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datecloture_end ? $search_datecloture_end : -1, 'search_datecloture_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Note public
if (!empty($arrayfields['c.note_public']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat width75" type="text" name="search_note_public" value="'.dolPrintHTMLForAttribute($search_note_public).'">';
	print '</td>';
}
// Note private
if (!empty($arrayfields['c.note_private']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat width75" type="text" name="search_note_private" value="'.dolPrintHTMLForAttribute($search_note_private).'">';
	print '</td>';
}
// Shippable
if (!empty($arrayfields['shippable']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone" align="center">';
	//print $form->selectyesno('search_shippable', $search_shippable, 1, 0, 1, 1);
	if (getDolGlobalString('ORDER_SHIPABLE_STATUS_DISABLED_BY_DEFAULT')) {
		print '<input type="checkbox" name="show_shippable_command" value="1"'.($show_shippable_command ? ' checked' : '').'>';
		print $langs->trans('ShowShippableStatus');
	} else {
		$show_shippable_command = 1;
	}
	print '</td>';
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Status billed
if (!empty($arrayfields['c.facture']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print $form->selectyesno('search_billed', $search_billed, 1, 0, 1, 1);
	print '</td>';
}

// Import key
if (!empty($arrayfields['c.import_key']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}

// Status
if (!empty($arrayfields['c.fk_statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$liststatus = array(
		Commande::STATUS_DRAFT => $langs->trans("StatusOrderDraftShort"),
		Commande::STATUS_VALIDATED => $langs->trans("StatusOrderValidated"),
		Commande::STATUS_SHIPMENTONPROCESS => $langs->trans("StatusOrderSentShort"),
		-2 => $langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort"),
		-3 => $langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort").'+'.$langs->trans("StatusOrderDelivered"),
		Commande::STATUS_CLOSED => $langs->trans("StatusOrderDelivered"),
		Commande::STATUS_CANCELED => $langs->trans("StatusOrderCanceledShort")
	);
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray('search_status', $liststatus, $search_status, -5, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage', 1);
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array(
	'nbfield' => 0,
	'val' => array(
		'c.total_ht' => 0,
		'c.total_tva' => 0,
		'c.total_ttc' => 0,
	),
	'pos' => array(),
);


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['c.rowid']['label'], $_SERVER["PHP_SELF"], 'c.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.ref']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], 'c.ref', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.ref_ext']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref_ext']['label'], $_SERVER["PHP_SELF"], 'c.ref_ext', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.ref_client']['checked'])) {
	print_liste_field_titre($arrayfields['c.ref_client']['label'], $_SERVER["PHP_SELF"], 'c.ref_client', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.title']['checked'])) {
	print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	// @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], 's.name_alias', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s2.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s2.nom']['label'], $_SERVER['PHP_SELF'], 's2.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.town']['checked'])) {
	print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.zip']['checked'])) {
	print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['typent.code']['checked'])) {
	print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.date_commande']['checked'])) {
	print_liste_field_titre($arrayfields['c.date_commande']['label'], $_SERVER["PHP_SELF"], 'c.date_commande', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.delivery_date']['checked'])) {
	print_liste_field_titre($arrayfields['c.delivery_date']['label'], $_SERVER["PHP_SELF"], 'c.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
	print_liste_field_titre($arrayfields['c.fk_shipping_method']['label'], $_SERVER["PHP_SELF"], "c.fk_shipping_method", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['c.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "c.fk_cond_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['c.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "c.fk_mode_reglement", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
	print_liste_field_titre($arrayfields['c.fk_input_reason']['label'], $_SERVER["PHP_SELF"], "c.fk_input_reason", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.module_source']['checked'])) {
	print_liste_field_titre($arrayfields['c.module_source']['label'], $_SERVER["PHP_SELF"], "f.module_source", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.pos_source']['checked'])) {
	print_liste_field_titre($arrayfields['c.pos_source']['label'], $_SERVER["PHP_SELF"], "f.pos_source", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['c.total_ht']['label'], $_SERVER["PHP_SELF"], 'c.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['c.total_vat']['label'], $_SERVER["PHP_SELF"], 'c.total_tva', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['c.total_ttc']['label'], $_SERVER["PHP_SELF"], 'c.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
	print_liste_field_titre($arrayfields['c.multicurrency_code']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_code', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
	print_liste_field_titre($arrayfields['c.multicurrency_tx']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_tx', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
	print_liste_field_titre($arrayfields['c.multicurrency_total_ht']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
	print_liste_field_titre($arrayfields['c.multicurrency_total_vat']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_tva', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
	print_liste_field_titre($arrayfields['c.multicurrency_total_ttc']['label'], $_SERVER['PHP_SELF'], 'c.multicurrency_total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['sale_representative']['checked'])) {
	print_liste_field_titre($arrayfields['sale_representative']['label'], $_SERVER["PHP_SELF"], "", "", "$param", '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_pa']['checked'])) {
	print_liste_field_titre($arrayfields['total_pa']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_margin']['checked'])) {
	print_liste_field_titre($arrayfields['total_margin']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_margin_rate']['checked'])) {
	print_liste_field_titre($arrayfields['total_margin_rate']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['total_mark_rate']['checked'])) {
	print_liste_field_titre($arrayfields['total_mark_rate']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}

if (!empty($arrayfields['c.datec']['checked'])) {
	print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "c.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.tms']['checked'])) {
	print_liste_field_titre($arrayfields['c.tms']['label'], $_SERVER["PHP_SELF"], "c.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.date_cloture']['checked'])) {
	print_liste_field_titre($arrayfields['c.date_cloture']['label'], $_SERVER["PHP_SELF"], "c.date_cloture", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.note_public']['checked'])) {
	print_liste_field_titre($arrayfields['c.note_public']['label'], $_SERVER["PHP_SELF"], "c.note_public", "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.note_private']['checked'])) {
	print_liste_field_titre($arrayfields['c.note_private']['label'], $_SERVER["PHP_SELF"], "c.note_private", "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['shippable']['checked'])) {
	print_liste_field_titre($arrayfields['shippable']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Status billed
if (!empty($arrayfields['c.facture']['checked'])) {
	print_liste_field_titre($arrayfields['c.facture']['label'], $_SERVER["PHP_SELF"], 'c.facture', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Import key
if (!empty($arrayfields['c.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['c.import_key']['label'], $_SERVER["PHP_SELF"], "c.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Status
if (!empty($arrayfields['c.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['c.fk_statut']['label'], $_SERVER["PHP_SELF"], "c.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$total = 0;
$subtotal = 0;
$productstat_cache = array();
$productstat_cachevirtual = array();
$getNomUrl_cache = array();

$generic_commande = new Commande($db);
$generic_product = new Product($db);
$userstatic = new User($db);

$with_margin_info = false;
if (isModEnabled('margin') && (
	!empty($arrayfields['total_pa']['checked'])
	|| !empty($arrayfields['total_margin']['checked'])
	|| !empty($arrayfields['total_margin_rate']['checked'])
	|| !empty($arrayfields['total_mark_rate']['checked'])
)
) {
	$with_margin_info = true;
}

$total_ht = 0;
$total_margin = 0;

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$typenArray = $formcompany->typent_array(1);

	$notshippable = 0;
	$warning = 0;
	$text_info = '';
	$text_warning = '';
	$nbprod = 0;

	$companystatic->id = $obj->socid;
	$companystatic->name = $obj->name;
	$companystatic->name_alias = $obj->alias;
	$companystatic->client = $obj->client;
	$companystatic->fournisseur = $obj->fournisseur;
	$companystatic->code_client = $obj->code_client;
	$companystatic->email = $obj->email;
	$companystatic->phone = $obj->phone;
	$companystatic->address = $obj->address;
	$companystatic->zip = $obj->zip;
	$companystatic->town = $obj->town;
	$companystatic->country_code = $obj->country_code;
	if (!isset($getNomUrl_cache[$obj->socid])) {
		$getNomUrl_cache[$obj->socid] = $companystatic->getNomUrl(1, 'customer', 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
	}

	$generic_commande->id = $obj->rowid;
	$generic_commande->ref = $obj->ref;
	$generic_commande->status = $obj->fk_statut;
	$generic_commande->statut = $obj->fk_statut;
	$generic_commande->status = $obj->fk_statut;
	$generic_commande->billed = $obj->billed;
	$generic_commande->date = $db->jdate($obj->date_commande);
	$generic_commande->delivery_date = $db->jdate($obj->delivery_date);
	$generic_commande->ref_client = $obj->ref_client;
	$generic_commande->total_ht = $obj->total_ht;
	$generic_commande->total_tva = $obj->total_tva;
	$generic_commande->total_ttc = $obj->total_ttc;
	$generic_commande->note_public = $obj->note_public;
	$generic_commande->note_private = $obj->note_private;

	$generic_commande->thirdparty = $companystatic;


	$projectstatic->id = $obj->project_id;
	$projectstatic->ref = $obj->project_ref;
	$projectstatic->title = $obj->project_label;

	$marginInfo = array();
	if ($with_margin_info) {
		$generic_commande->fetch_lines();
		$marginInfo = $formmargin->getMarginInfosArray($generic_commande);
		$total_ht += $obj->total_ht;
		$total_margin += $marginInfo['total_margin'];
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}

		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		print $generic_commande->getKanbanView('', array('selected' => $selected));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven status'.$generic_commande->status.((getDolGlobalInt('MAIN_FINISHED_LINES_OPACITY') == 1 && $obj->status > 1) ? ' opacitymedium' : '').'">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Technical ID
		if (!empty($arrayfields['c.rowid']['checked'])) {
			print '<td class="center" data-key="id">'.$obj->rowid.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref
		if (!empty($arrayfields['c.ref']['checked'])) {
			print '<td class="nowraponall">';
			$getNomUrlOption = $search_status != 2 ? 0 : $obj->fk_statut;
			if (getDolGlobalInt('MAIN_LIST_ORDER_LINK_DONT_USE_STATUS')) {
				// TODO : This hidden conf must be added to the user's individual conf.
				//  The user must be able to manage this behavior to adapt it to his use of the software, because depending on the employee's workstation, his use differs.
				//  This hidden configuration ensures that users are not confused by keeping the same behavior of click, whatever the current filter.
				//  If the aim is to use a different url when the filter is applied via the link in the left-hand menu, then this detection should be adapted instead.
				$getNomUrlOption = '';
			}
			print $generic_commande->getNomUrl(1, $getNomUrlOption, 0, 0, 0, 1, 1);

			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->order->multidir_output[$conf->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref customer
		if (!empty($arrayfields['c.ref_ext']['checked'])) {
			print '<td class="nowrap tdoverflowmax75" title="'.dol_escape_htmltag($obj->ref_ext).'">';
			print dol_escape_htmltag($obj->ref_ext);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref customer
		if (!empty($arrayfields['c.ref_client']['checked'])) {
			print '<td class="nowrap tdoverflowmax150" title="'.dol_escape_htmltag($obj->ref_client).'">';
			print dol_escape_htmltag($obj->ref_client);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project ref
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nocellnopadd nowraponall">';
			if ($obj->project_id > 0) {
				print $projectstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project label
		if (!empty($arrayfields['p.title']['checked'])) {
			print '<td class="nowraponall">';
			if ($obj->project_id > 0) {
				print $projectstatic->title;
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
				print $companystatic->getNomUrl(1, 'customer', 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
			} else {
				print $getNomUrl_cache[$obj->socid];
			}

			// If module invoices enabled and user with invoice creation permissions
			if (isModEnabled('invoice') && getDolGlobalString('ORDER_BILLING_ALL_CUSTOMER')) {
				if ($user->hasRight('facture', 'creer')) {
					if (($obj->fk_statut > 0 && $obj->fk_statut < 3) || ($obj->fk_statut == 3 && $obj->billed == 0)) {
						print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$companystatic->id.'&search_billed=0&autoselectall=1">';
						print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
					}
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Alias name
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="nocellnopadd tdoverflowmax100" title="'.dolPrintHTMLForTextArea($obj->alias).'">';
			print dolPrintLabel($obj->alias);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Parent company
		if (!empty($arrayfields['s2.nom']['checked'])) {
			print '<td class="tdoverflowmax200">';
			if ($obj->fk_parent > 0) {
				if (!isset($company_url_list[$obj->fk_parent])) {
					$companyparent = new Societe($db);
					$res = $companyparent->fetch($obj->fk_parent);
					if ($res > 0) {
						$company_url_list[$obj->fk_parent] = $companyparent->getNomUrl(1);
					}
				}
				if (isset($company_url_list[$obj->fk_parent])) {
					print $company_url_list[$obj->fk_parent];
				}
			}
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Town
		if (!empty($arrayfields['s.town']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dolPrintHTMLForTextArea($obj->town).'">';
			print dolPrintLabel($obj->town);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Zip
		if (!empty($arrayfields['s.zip']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dolPrintHTMLForTextArea($obj->zip).'">';
			print dolPrintLabel($obj->zip);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// State
		if (!empty($arrayfields['state.nom']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dolPrintHTMLForTextArea($obj->state_name).'">'.dolPrintLabel($obj->state_name)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Type ent
		if (!empty($arrayfields['typent.code']['checked'])) {
			if (!is_array($typenArray) || count($typenArray) == 0) {
				$typenArray = $formcompany->typent_array(1);
			}
			print '<td class="center tdoverflowmax100" title="'.dolPrintHTMLForAttribute($typenArray[$obj->typent_code]).'">';
			if (!empty($obj->typent_code)) {
				print $typenArray[$obj->typent_code];
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Order date
		if (!empty($arrayfields['c.date_commande']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_commande), 'day');
			// Warning late icon and note
			if ($generic_commande->hasDelay()) {
				print img_picto($langs->trans("Late").' : '.$generic_commande->showDelay(), "warning");
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Plannned date of delivery
		if (!empty($arrayfields['c.delivery_date']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->delivery_date), 'dayhour');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Shipping Method
		if (!empty($arrayfields['c.fk_shipping_method']['checked'])) {
			print '<td>';
			$form->formSelectShippingMethod('', $obj->fk_shipping_method, 'none', 1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Payment terms
		if (!empty($arrayfields['c.fk_cond_reglement']['checked'])) {
			print '<td>';
			$form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none', 0, '', 1, $obj->deposit_percent);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Payment mode
		if (!empty($arrayfields['c.fk_mode_reglement']['checked'])) {
			print '<td>';
			$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Channel
		if (!empty($arrayfields['c.fk_input_reason']['checked'])) {
			print '<td>';
			$form->formInputReason($_SERVER['PHP_SELF'], $obj->fk_input_reason, 'none', 0);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Module Source
		if (!empty($arrayfields['c.module_source']['checked'])) {
			print '<td>';
			print dol_escape_htmltag($obj->module_source);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// POS Terminal
		if (!empty($arrayfields['c.pos_source']['checked'])) {
			print '<td>';
			print dol_escape_htmltag($obj->pos_source);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Amount HT/net
		if (!empty($arrayfields['c.total_ht']['checked'])) {
			print '<td class="nowraponall right"><span class="amount">'.price($obj->total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_ht';
			}
			if (isset($totalarray['val']['c.total_ht'])) {
				$totalarray['val']['c.total_ht'] += $obj->total_ht;
			} else {
				$totalarray['val']['c.total_ht'] = $obj->total_ht;
			}
		}

		// Amount VAT
		if (!empty($arrayfields['c.total_vat']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_tva)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_tva';
			}
			if (isset($totalarray['val']['c.total_tva'])) {
				$totalarray['val']['c.total_tva'] += $obj->total_tva;
			} else {
				$totalarray['val']['c.total_tva'] = $obj->total_tva;
			}
		}

		// Amount TTC / gross
		if (!empty($arrayfields['c.total_ttc']['checked'])) {
			print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'c.total_ttc';
			}
			if (isset($totalarray['val']['c.total_ttc'])) {
				$totalarray['val']['c.total_ttc'] += $obj->total_ttc;
			} else {
				$totalarray['val']['c.total_ttc'] = $obj->total_ttc;
			}
		}

		// Currency
		if (!empty($arrayfields['c.multicurrency_code']['checked'])) {
			print '<td class="nowrap">'.$obj->multicurrency_code.' - '.$langs->trans('Currency'.$obj->multicurrency_code)."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Currency rate
		if (!empty($arrayfields['c.multicurrency_tx']['checked'])) {
			print '<td class="nowrap">';
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$obj->rowid, $obj->multicurrency_tx, 'none', $obj->multicurrency_code);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Amount HT/net in foreign currency
		if (!empty($arrayfields['c.multicurrency_total_ht']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ht)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount VAT in foreign currency
		if (!empty($arrayfields['c.multicurrency_total_vat']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_vat)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Amount TTC / gross in foreign currency
		if (!empty($arrayfields['c.multicurrency_total_ttc']['checked'])) {
			print '<td class="right nowrap"><span class="amount">'.price($obj->multicurrency_total_ttc)."</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		$userstatic->id = $obj->fk_user_author;
		$userstatic->login = $obj->login;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->email = $obj->user_email;
		$userstatic->status = $obj->user_statut;
		$userstatic->entity = $obj->entity;
		$userstatic->photo = $obj->photo;
		$userstatic->office_phone = $obj->office_phone;
		$userstatic->office_fax = $obj->office_fax;
		$userstatic->user_mobile = $obj->user_mobile;
		$userstatic->job = $obj->job;
		$userstatic->gender = $obj->gender;

		// Author
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td class="tdoverflowmax125">';
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1);
			} else {
				print '&nbsp;';
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Sales representatives
		if (!empty($arrayfields['sale_representative']['checked'])) {
			print '<td>';
			if ($obj->socid > 0) {
				$listsalesrepresentatives = $companystatic->getSalesRepresentatives($user);
				if ($listsalesrepresentatives < 0) {
					dol_print_error($db);
				}
				$nbofsalesrepresentative = count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 6) {
					// We print only number
					print $nbofsalesrepresentative;
				} elseif ($nbofsalesrepresentative > 0) {
					$j = 0;
					foreach ($listsalesrepresentatives as $val) {
						$userstatic->id = $val['id'];
						$userstatic->lastname = $val['lastname'];
						$userstatic->firstname = $val['firstname'];
						$userstatic->email = $val['email'];
						$userstatic->status = $val['statut'];
						$userstatic->entity = $val['entity'];
						$userstatic->photo = $val['photo'];
						$userstatic->login = $val['login'];
						$userstatic->office_phone = $val['office_phone'];
						$userstatic->office_fax = $val['office_fax'];
						$userstatic->user_mobile = $val['user_mobile'];
						$userstatic->job = $val['job'];
						$userstatic->gender = $val['gender'];
						//print '<div class="float">':
						print ($nbofsalesrepresentative < 2) ? $userstatic->getNomUrl(-1, '', 0, 0, 12) : $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) {
							print ' ';
						}
						//print '</div>';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Total buying or cost price
		if (!empty($arrayfields['total_pa']['checked'])) {
			print '<td class="right nowrap">'.price($marginInfo['pa_total']).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Total margin
		if (!empty($arrayfields['total_margin']['checked'])) {
			print '<td class="right nowrap">'.price($marginInfo['total_margin']).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_margin';
			}

			if (!isset($totalarray['val']['total_margin'])) {
				$totalarray['val']['total_margin'] = 0;
			}

			$totalarray['val']['total_margin'] += $marginInfo['total_margin'];
		}

		// Total margin rate
		if (!empty($arrayfields['total_margin_rate']['checked'])) {
			print '<td class="right nowrap">'.(($marginInfo['total_margin_rate'] == '') ? '' : price($marginInfo['total_margin_rate'], 0, '', 0, 0, 2).'%').'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Total mark rate
		if (!empty($arrayfields['total_mark_rate']['checked'])) {
			print '<td class="right nowrap">'.(($marginInfo['total_mark_rate'] == '') ? '' : price($marginInfo['total_mark_rate'], 0, '', 0, 0, 2).'%').'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_mark_rate';
			}
			if ($i >= $imaxinloop - 1) {
				if (!empty($total_ht)) {
					$totalarray['val']['total_mark_rate'] = price2num($total_margin * 100 / $total_ht, 'MT');
				} else {
					$totalarray['val']['total_mark_rate'] = '';
				}
			}
		}

		// Date creation
		if (!empty($arrayfields['c.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuserrel');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date modification
		if (!empty($arrayfields['c.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuserrel');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date cloture
		if (!empty($arrayfields['c.date_cloture']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_cloture), 'dayhour', 'tzuserrel');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Note public
		if (!empty($arrayfields['c.note_public']['checked'])) {
			print '<td class="flat maxwidth250imp">';
			print '<div class="small lineheightsmall">'.dolPrintHTML(dolGetFirstLineOfText($obj->note_public), 5).'</div>';
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Note private
		if (!empty($arrayfields['c.note_private']['checked'])) {
			print '<td class="flat maxwidth250imp">';
			print '<div class="small lineheightsmall">'.dolPrintHTML(dolGetFirstLineOfText($obj->note_private), 5).'</div>';
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Show shippable Icon (this creates subloops, so may be slow)
		if (!empty($arrayfields['shippable']['checked'])) {
			print '<td class="center">';
			if (!empty($show_shippable_command) && isModEnabled('stock')) {
				$text_icon = '';
				if (($obj->fk_statut > $generic_commande::STATUS_DRAFT) && ($obj->fk_statut < $generic_commande::STATUS_CLOSED)) {
					$generic_commande->getLinesArray(); 	// Load array ->lines
					$generic_commande->loadExpeditions();	// Load array ->expeditions

					$numlines = count($generic_commande->lines); // Loop on each line of order
					for ($lig = 0; $lig < $numlines; $lig++) {
						$orderLine = $generic_commande->lines[$lig];
						'@phan-var-force OrderLine $orderLine';
						if (isset($generic_commande->expeditions[$orderLine->id])) {
							$reliquat =  $orderLine->qty - $generic_commande->expeditions[$orderLine->id];
						} else {
							$reliquat = $orderLine->qty;
						}
						if ($orderLine->product_type == 0 && $orderLine->fk_product > 0) {  // If line is a product and not a service
							$nbprod++; // order contains real products
							$generic_product->id = $orderLine->fk_product;

							// Get local and virtual stock and store it into cache
							if (empty($productstat_cache[$orderLine->fk_product])) {
								$generic_product->load_stock('nobatch,warehouseopen'); // ->load_virtual_stock() is already included into load_stock()
								$productstat_cache[$orderLine->fk_product]['stock_reel'] = $generic_product->stock_reel;
								$productstat_cachevirtual[$orderLine->fk_product]['stock_reel'] = $generic_product->stock_theorique;
							} else {
								$generic_product->stock_reel = $productstat_cache[$orderLine->fk_product]['stock_reel'];
								// @phan-suppress-next-line PhanTypeInvalidDimOffset
								$generic_product->stock_theorique = $productstat_cachevirtual[$orderLine->fk_product]['stock_reel'];
							}

							if ($reliquat > $generic_product->stock_reel) {
								$notshippable++;
							}
							if (!getDolGlobalString('SHIPPABLE_ORDER_ICON_IN_LIST')) {  // Default code. Default should be this case.
								$text_info .= $reliquat.' x '.$orderLine->product_ref.'&nbsp;'.dol_trunc($orderLine->product_label, 20);
								$text_info .= ' - '.$langs->trans("Stock").': <span class="'.($generic_product->stock_reel > 0 ? 'ok' : 'error').'">'.$generic_product->stock_reel.'</span>';
								$text_info .= ' - '.$langs->trans("VirtualStock").': <span class="'.($generic_product->stock_theorique > 0 ? 'ok' : 'error').'">'.$generic_product->stock_theorique.'</span>';
								$text_info .= ($reliquat != $orderLine->qty ? ' <span class="opacitymedium">('.$langs->trans("QtyInOtherShipments").' '.($orderLine->qty - $reliquat).')</span>' : '');
								$text_info .= '<br>';
							} else {  // BUGGED CODE.
								// DOES NOT TAKE INTO ACCOUNT MANUFACTURING. THIS CODE SHOULD BE USELESS. PREVIOUS CODE SEEMS COMPLETE.
								// COUNT STOCK WHEN WE SHOULD ALREADY HAVE VALUE
								// Detailed virtual stock, looks bugged, incomplete and need heavy load.
								// stock order and stock order_supplier
								$stock_order = 0;
								$stock_order_supplier = 0;
								if (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT') || getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')) {    // What about other options ?
									if (isModEnabled('order')) {
										if (empty($productstat_cache[$orderLine->fk_product]['stats_order_customer'])) {
											$generic_product->load_stats_commande(0, '1,2');
											$productstat_cache[$orderLine->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
										} else {
											// @phan-suppress-next-line PhanTypeInvalidDimOffset
											$generic_product->stats_commande['qty'] = $productstat_cache[$orderLine->fk_product]['stats_order_customer'];
										}
										$stock_order = $generic_product->stats_commande['qty'];
									}
									if (isModEnabled("supplier_order")) {
										if (empty($productstat_cache[$orderLine->fk_product]['stats_order_supplier'])) {
											$generic_product->load_stats_commande_fournisseur(0, '3');
											$productstat_cache[$orderLine->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
										} else {
											// @phan-suppress-next-line PhanTypeInvalidDimOffset
											$generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$orderLine->fk_product]['stats_order_supplier'];
										}
										$stock_order_supplier = $generic_product->stats_commande_fournisseur['qty'];
									}
								}
								$text_info .= $reliquat.' x '.$orderLine->ref.'&nbsp;'.dol_trunc($orderLine->product_label, 20);
								$text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
								if ($stock_order > $generic_product->stock_reel && !($generic_product->stock_reel < $orderLine->qty)) {
									$warning++;
									$text_warning .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if ($reliquat > $generic_product->stock_reel) {
									$text_info .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								} else {
									$text_info .= '<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
								}
								if (isModEnabled("supplier_order")) {
									$text_info .= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier;
								}
								$text_info .= ($reliquat != $orderLine->qty ? ' <span class="opacitymedium">('.$langs->trans("QtyInOtherShipments").' '.($orderLine->qty - $reliquat).')</span>' : '');
								$text_info .= '<br>';
							}
						}
					}
					if ($notshippable == 0) {
						$text_icon = img_picto('', 'dolly', '', 0, 0, 0, '', 'green paddingleft');
						$text_info = $text_icon.' '.$langs->trans('Shippable').'<br>'.$text_info;
					} else {
						$text_icon = img_picto('', 'dolly', '', 0, 0, 0, '', 'error paddingleft');
						$text_info = $text_icon.' '.$langs->trans('NonShippable').'<br>'.$text_info;
					}
				}

				if ($nbprod) {	// If there is at least one product to ship, we show the shippable icon
					print '<a href="'.DOL_URL_ROOT.'/expedition/shipment.php?id='.((int) $obj->rowid).'">';
					print $form->textwithtooltip('', $text_info, 2, 1, $text_icon, '', 2);
					print '</a>';
				}
				if ($warning) {     // Always false in default mode
					print $form->textwithtooltip('', $langs->trans('NotEnoughForAllOrders').'<br>'.$text_warning, 2, 1, img_picto('', 'error'), '', 2);
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Billed
		if (!empty($arrayfields['c.facture']['checked'])) {
			print '<td class="center">';
			if ($obj->billed) {
				print yn($obj->billed, $langs->trans("Billed"));
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Import key
		if (!empty($arrayfields['c.import_key']['checked'])) {
			print '<td class="nowrap center">'.dol_escape_htmltag($obj->import_key).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Status
		if (!empty($arrayfields['c.fk_statut']['checked'])) {
			print '<td class="nowrap center">'.$generic_commande->LibStatut($obj->fk_statut, $obj->billed, 5, 1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print "</tr>\n";

		$total += $obj->total_ht;
		$subtotal += $obj->total_ht;
	}
	$i++;
}

// Show total line
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

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_orders', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
