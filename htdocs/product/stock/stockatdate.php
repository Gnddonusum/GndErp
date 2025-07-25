<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2020	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		ATM Consulting		<support@atm-consulting.fr>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/stockatdate.php
 *  \ingroup    stock
 *  \brief      Page to list stocks at a given date
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once './lib/replenishment.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('stockatdate'));

//checks if a product has been ordered

$action = GETPOST('action', 'aZ09');
$type = GETPOSTINT('type');
$mode = GETPOST('mode', 'alpha');

$ext = (GETPOSTISSET('output') && in_array(GETPOST('output'), array('csv'))) ? GETPOST('output') : '';

$date = '';
$dateendofday = '';
if (GETPOSTISSET('dateday') && GETPOSTISSET('datemonth') && GETPOSTISSET('dateyear')) {
	$date = dol_mktime(0, 0, 0, GETPOSTINT('datemonth'), GETPOSTINT('dateday'), GETPOSTINT('dateyear'));
	$dateendofday = dol_mktime(23, 59, 59, GETPOSTINT('datemonth'), GETPOSTINT('dateday'), GETPOSTINT('dateyear'));
}

$search_ref = GETPOST('search_ref', 'alphanohtml');
$search_nom = GETPOST('search_nom', 'alphanohtml');

$now = dol_now();

$productid = GETPOSTINT('productid');
if (GETPOSTISARRAY('search_fk_warehouse')) {
	$search_fk_warehouse = GETPOST('search_fk_warehouse', 'array:int');
} else {
	$search_fk_warehouse = array(GETPOSTINT('search_fk_warehouse'));
}
// For backward compatibility
if (GETPOSTINT('fk_warehouse')) {
	$search_fk_warehouse = array(GETPOSTINT('fk_warehouse'));
}
// Clean value -1
foreach ($search_fk_warehouse as $key => $val) {
	if ($val == -1 || empty($val)) {
		unset($search_fk_warehouse[$key]);
	}
}

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$dateIsValid = true;
if ($mode == 'future') {
	if ($date && $date < $now) {
		setEventMessages($langs->trans("ErrorDateMustBeInFuture"), null, 'errors');
		$dateIsValid = false;
	}
} else {
	if ($date && $date > $now) {
		setEventMessages($langs->trans("ErrorDateMustBeBeforeToday"), null, 'errors');
		$dateIsValid = false;
	}
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'produit|service');	// Must have permission to read product
$result = restrictedArea($user, 'stock');	// Must have permission to read stock


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$date = '';
	$productid = 0;
	$search_fk_warehouse = array();
	$search_ref = '';
	$search_nom = '';
}

$warehouseStatus = array();
if (getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
	//$warehouseStatus[] = Entrepot::STATUS_CLOSED;
	$warehouseStatus[] = Entrepot::STATUS_OPEN_ALL;
	$warehouseStatus[] = Entrepot::STATUS_OPEN_INTERNAL;
}

// Get array with current stock per product, warehouse
$stock_prod_warehouse = array();
$stock_prod = array();
if ($date && $dateIsValid) {	// Avoid heavy sql if mandatory date is not defined
	$sql = "SELECT ps.fk_product, ps.fk_entrepot as fk_warehouse,";
	$sql .= " SUM(ps.reel) AS stock";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
	$sql .= ", ".MAIN_DB_PREFIX."entrepot as w";
	$sql .= ", ".MAIN_DB_PREFIX."product as p";
	$sql .= " WHERE w.entity IN (".getEntity('stock').")";
	$sql .= " AND w.rowid = ps.fk_entrepot AND p.rowid = ps.fk_product";
	if (getDolGlobalString('ENTREPOT_EXTRA_STATUS') && count($warehouseStatus)) {
		$sql .= " AND w.statut IN (".$db->sanitize(implode(',', $warehouseStatus)).")";
	}
	if ($productid > 0) {
		$sql .= " AND ps.fk_product = ".((int) $productid);
	}
	if (! empty($search_fk_warehouse)) {
		$sql .= " AND ps.fk_entrepot IN (".$db->sanitize(implode(",", $search_fk_warehouse)).")";
	}
	if ($search_ref) {
		$sql .= natural_search("p.ref", $search_ref);
	}
	if ($search_nom) {
		$sql .= natural_search("p.label", $search_nom);
	}
	$sql .= " GROUP BY fk_product, fk_entrepot";
	//print $sql;

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$tmp_fk_product   = $obj->fk_product;
			$tmp_fk_warehouse = $obj->fk_warehouse;
			$stock = $obj->stock;

			$stock_prod_warehouse[$tmp_fk_product][$tmp_fk_warehouse] = $stock;
			$stock_prod[$tmp_fk_product] = (isset($stock_prod[$tmp_fk_product]) ? $stock_prod[$tmp_fk_product] : 0) + $stock;

			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
	//var_dump($stock_prod_warehouse);
} elseif ($action == 'filter') {	// Test on permissions not required here
	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
}

// Get array with list of stock movements between date and now (for product/warehouse=
$movements_prod_warehouse = array();
$movements_prod = array();
$movements_prod_warehouse_nb = array();
$movements_prod_nb = array();
if ($date && $dateIsValid) {
	$sql = "SELECT sm.fk_product, sm.fk_entrepot, SUM(sm.value) AS stock, COUNT(sm.rowid) AS nbofmovement";
	$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as sm";
	$sql .= ", ".MAIN_DB_PREFIX."entrepot as w";
	$sql .= ", ".MAIN_DB_PREFIX."product as p";
	$sql .= " WHERE w.entity IN (".getEntity('stock').")";
	$sql .= " AND w.rowid = sm.fk_entrepot AND p.rowid = sm.fk_product ";
	if (getDolGlobalString('ENTREPOT_EXTRA_STATUS') && count($warehouseStatus)) {
		$sql .= " AND w.statut IN (".$db->sanitize(implode(',', $warehouseStatus)).")";
	}
	if ($mode == 'future') {
		$sql .= " AND sm.datem <= '".$db->idate($dateendofday)."'";
	} else {
		$sql .= " AND sm.datem >= '".$db->idate($dateendofday)."'";
	}
	if ($productid > 0) {
		$sql .= " AND sm.fk_product = ".((int) $productid);
	}
	if (!empty($search_fk_warehouse)) {
		$sql .= " AND sm.fk_entrepot IN (".$db->sanitize(implode(",", $search_fk_warehouse)).")";
	}
	if ($search_ref) {
		$sql .= " AND p.ref LIKE '%".$db->escape($search_ref)."%' ";
	}
	if ($search_nom) {
		$sql .= " AND p.label LIKE '%".$db->escape($search_nom)."%' ";
	}
	$sql .= " GROUP BY sm.fk_product, sm.fk_entrepot";

	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$fk_product = $obj->fk_product;
			$fk_entrepot 	= $obj->fk_entrepot;
			$stock = $obj->stock;
			$nbofmovement	= $obj->nbofmovement;

			// Pour llx_product_stock.reel
			$movements_prod_warehouse[$fk_product][$fk_entrepot] = $stock;
			$movements_prod_warehouse_nb[$fk_product][$fk_entrepot] = $nbofmovement;

			// Pour llx_product.stock
			$movements_prod[$fk_product] = $stock + (array_key_exists($fk_product, $movements_prod) ? $movements_prod[$fk_product] : 0);
			$movements_prod_nb[$fk_product] = $nbofmovement + (array_key_exists($fk_product, $movements_prod_nb) ? $movements_prod_nb[$fk_product] : 0);

			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}
//var_dump($movements_prod_warehouse);
//var_dump($movements_prod);


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$num = 0;

$title = $langs->trans('StockAtDate');

$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price, p.pmp,';
$sql .= ' p.price_ttc, p.price_base_type, p.fk_product_type, p.desiredstock, p.seuil_stock_alerte,';
$sql .= ' p.tms as datem, p.duration, p.tobuy, p.stock, ';
if (!empty($search_fk_warehouse)) {
	$sql .= " SUM(p.pmp * ps.reel) as currentvalue, SUM(p.price * ps.reel) as sellvalue";
	$sql .= ', SUM(ps.reel) as stock_reel';
} else {
	$sql .= " SUM(p.pmp * p.stock) as currentvalue, SUM(p.price * p.stock) as sellvalue";
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
if (!empty($search_fk_warehouse)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot IN ('.$db->sanitize(implode(",", $search_fk_warehouse)).")";
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
if ($productid > 0) {
	$sql .= " AND p.rowid = ".((int) $productid);
}
if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
	$sql .= " AND p.fk_product_type = 0";
}
if (!empty($canvas)) {
	$sql .= " AND p.canvas = '".$db->escape($canvas)."'";
}
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_nom) {
	$sql .= natural_search('p.label', $search_nom);
}
$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price, p.pmp, p.price_ttc, p.price_base_type, p.fk_product_type, p.desiredstock, p.seuil_stock_alerte,';
$sql .= ' p.tms, p.duration, p.tobuy, p.stock';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($sortfield == 'stock_reel' && empty($search_fk_warehouse)) {
	$sortfield = 'stock';
}
if ($sortfield == 'stock' && !empty($search_fk_warehouse)) {
	$sortfield = 'stock_reel';
}
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if ($date && $dateIsValid) {	// We avoid a heavy sql if mandatory parameter date not yet defined
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords || $ext == 'csv') {	// if total resultset is smaller then paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}
	}

	//print $sql;
	if ($ext != 'csv') {
		$sql .= $db->plimit($limit + 1, $offset);
		$resql = $db->query($sql);
	} else {
		$resql = $result;
		$limit = 0;
	}
	if (empty($resql)) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

$i = 0;

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
$helpurl .= 'ES:M&oacute;dulo_Stocks';

$stocklabel = $langs->trans('StockAtDate');
if ($mode == 'future') {
	$stocklabel = $langs->trans("VirtualStockAtDate");
}

// TODO Move this action into a separated files: We should not mix output with MIME type HTML and MIME type CSV in the same file.
if ($ext == 'csv') {
	top_httphead("text/csv");
	//header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=stock".($date ? '-'.date("Y-m-d", $date) : '').".csv");

	// Lines of title
	print implode(";", ($mode == 'future') ?
		array('"Product Reference"', '"Label"', '"Current Stock"', '"'.$stocklabel.'"', '"Virtual Stock"') :
		array('"Product Reference"', '"Label"', '"'.$stocklabel.'"', '"Estimated Stock Value"', '"Estimate Sell Value"', '"Movements"', '"Current Stock"'))."\r\n";
} else {
	llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-product page-stock_stockatdate');

	$head = array();

	$head[0][0] = DOL_URL_ROOT.'/product/stock/stockatdate.php';
	$head[0][1] = $langs->trans("StockAtDateInPast");
	$head[0][2] = 'stockatdatepast';

	$head[1][0] = DOL_URL_ROOT.'/product/stock/stockatdate.php?mode=future';
	$head[1][1] = $langs->trans("StockAtDateInFuture");
	$head[1][2] = 'stockatdatefuture';


	print load_fiche_titre($langs->trans('StockAtDate'), '', 'stock');

	print dol_get_fiche_head($head, ($mode == 'future' ? 'stockatdatefuture' : 'stockatdatepast'), '', -1, '');

	$desc = $langs->trans("StockAtDatePastDesc");
	if ($mode == 'future') {
		$desc = $langs->trans("StockAtDateFutureDesc");
	}
	print '<span class="opacitymedium">'.$desc.'</span><br>'."\n";
	print '<br>'."\n";

	print '<form name="formFilterWarehouse" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="filter">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
	print '<span class="fieldrequired">'.$langs->trans('Date').'</span> '.$form->selectDate(($date ? $date : -1), 'date');

	print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddingrightonly">&nbsp;</span> ';
	print img_picto('', 'product', 'class="pictofixedwidth"').' ';
	print '</span> ';
	print $form->select_produits($productid, 'productid', '', 0, 0, -1, 2, '', 0, array(), 0, $langs->trans('Product'), 0, 'maxwidth300', 0, '', null, 1);

	if ($mode != 'future') {
		// A virtual stock in future has no sense on a per warehouse view, so no filter on warehouse is available for stock at date in future
		print ' <span class="clearbothonsmartphone marginleftonly paddingleftonly marginrightonly paddingrightonly">&nbsp;</span> ';
		print img_picto('', 'stock', 'class="pictofixedwidth"').$langs->trans("Warehouse").' :';
		print '</span> ';
		$selected = ((GETPOSTISSET('search_fk_warehouse') || GETPOSTISSET('fk_warehouse')) ? $search_fk_warehouse : 'ifonenodefault');
		print $formproduct->selectWarehouses($selected, 'search_fk_warehouse', '', 1, 0, 0, $langs->trans('Warehouse'), 0, 0, array(), 'minwidth200', array(), 1, false, 'e.ref', 1);
	}

	print '</div>';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		print $hookmanager->resPrint;
	}

	print '<div class="inline-block valignmiddle">';
	print '<input type="submit" class="button" name="valid" value="'.$langs->trans('Refresh').'">';
	print '</div>';

	//print '</form>';

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	$param .= '&mode='.$mode;
	$param_warehouse = '';
	if (!empty($search_fk_warehouse)) {
		foreach ($search_fk_warehouse as $val) {
			$param_warehouse .= '&search_fk_warehouse[]='.$val;
		}
		$param .= $param_warehouse;
	}
	if ($productid > 0) {
		$param .= '&productid='.(int) $productid;
	}
	if (GETPOSTINT('dateday') > 0) {
		$param .= '&dateday='.GETPOSTINT('dateday');
	}
	if (GETPOSTINT('datemonth') > 0) {
		$param .= '&datemonth='.GETPOSTINT('datemonth');
	}
	if (GETPOSTINT('dateyear') > 0) {
		$param .= '&dateyear='.GETPOSTINT('dateyear');
	}

	// TODO Move this into the title line ?
	print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'stock', 0, '', '', $limit, 0, 0, 1);

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	if ($num) {
		print '<p>';
		print '<a href="stockatdate.php?output=csv&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).'&type='.((int) $type).'&mode='.urlencode($mode).
			(($productid > 0) ? "&productid=".((int) $productid) : '').
			$param_warehouse.
			"&search_ref=".dol_escape_htmltag($search_ref).
			"&search_nom=".dol_escape_htmltag($search_nom).
			(GETPOSTISSET('dateday') ? "&dateday=".GETPOSTINT('dateday') : '').
			(GETPOSTISSET('datemonth') ? "&datemonth=".GETPOSTINT('datemonth') : '').
			(GETPOSTISSET('dateyear') ? "&dateyear=".GETPOSTINT('dateyear') : '').
			'" title="Download CSV" />';
		print img_picto('', 'download', 'class="pictofixedwidth"');
		print 'Download CSV';
		print '</a>';
		print '</p>';
	}
	print '<table class="liste centpercent">';

	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'"></td>';
	print '<td class="liste_titre"><input class="flat" type="text" name="search_nom" size="8" value="'.dol_escape_htmltag($search_nom).'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	if ($mode == 'future') {
		print '<td class="liste_titre"></td>';
	} else {
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
	}
	// Fields from hook
	$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	$fieldtosortcurrentstock = 'stock';
	if (!empty($search_fk_warehouse)) {
		$fieldtosortcurrentstock = 'stock_reel';
	}

	// Lines of title
	print '<tr class="liste_titre">';
	print_liste_field_titre('ProductRef', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
	print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);

	if ($mode == 'future') {
		print_liste_field_titre('CurrentStock', $_SERVER["PHP_SELF"], $fieldtosortcurrentstock, $param, '', '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre('', $_SERVER["PHP_SELF"]);
		print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ', 'VirtualStockAtDateDesc');
		print_liste_field_titre('VirtualStock', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ', 'VirtualStockDesc');
	} else {
		print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
		$tooltiptext = $langs->trans("QtyAtDate").' x '.$langs->trans("AverageUnitPricePMPShort").' ('.$langs->trans("Currently").')';
		print_liste_field_titre("EstimatedStockValue", $_SERVER["PHP_SELF"], "currentvalue", '', $param, '', $sortfield, $sortorder, 'right ', $tooltiptext, 1);
		$tooltiptext = $langs->trans("QtyAtDate").' x '.$langs->trans("SellingPrice").' ('.$langs->trans("Currently").')';
		print_liste_field_titre("EstimatedStockValueSell", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', $tooltiptext, 1);
		$tooltiptext = $langs->trans("MovementsSinceDate");
		print_liste_field_titre('', $_SERVER["PHP_SELF"], '', '', $param, '', '', '', 'right ', $tooltiptext, 1);
		print_liste_field_titre('CurrentStock', $_SERVER["PHP_SELF"], $fieldtosortcurrentstock, $param, '', '', $sortfield, $sortorder, 'right ');
	}

	// Hook fields
	$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');

	print "</tr>\n";
}

$totalbuyingprice = 0;
$totalsellingprice = 0;
$totalcurrentstock = 0;
$totalvirtualstock = 0;

$i = 0;
while ($i < ($limit ? min($num, $limit) : $num)) {
	$objp = $db->fetch_object($resql);

	if (getDolGlobalString('STOCK_SUPPORTS_SERVICES') || $objp->fk_product_type == 0) {
		$prod->fetch($objp->rowid);

		// Multilangs
		/*if (getDolGlobalInt('MAIN_MULTILANGS'))
		{
			$sql = 'SELECT label,description';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'product_lang';
			$sql .= ' WHERE fk_product = '.((int) $objp->rowid);
			$sql .= " AND lang = '".$db->escape($langs->getDefaultLang())."'";
			$sql .= ' LIMIT 1';

			$resqlm = $db->query($sql);
			if ($resqlm)
			{
				$objtp = $db->fetch_object($resqlm);
				if (!empty($objtp->description)) $objp->description = $objtp->description;
				if (!empty($objtp->label)) $objp->label = $objtp->label;
			}
		}*/

		$currentstock = '';
		if (!empty($search_fk_warehouse)) {
			//if ($productid > 0) {
			foreach ($search_fk_warehouse as $val) {
				if (!is_numeric($currentstock)) {
					$currentstock = 0;
				}
				$currentstock += empty($stock_prod_warehouse[$objp->rowid][$val]) ? 0 : $stock_prod_warehouse[$objp->rowid][$val];
			}
			//} else {
			//	$currentstock = $objp->stock_reel;
			//}
		} else {
			//if ($productid > 0) {
			$currentstock = empty($stock_prod[$objp->rowid]) ? 0 : $stock_prod[$objp->rowid];
			//} else {
			//	$currentstock = $objp->stock;
			//}
		}

		$nbofmovement = 0;
		$virtualstock = 0;
		if ($mode == 'future') {
			$prod->load_stock('warehouseopen,warehouseinternal,nobatch', 0, $dateendofday);
			$stock = $prod->stock_theorique;		// virtual stock at a date
			$prod->load_stock('warehouseopen,warehouseinternal,nobatch', 0);
			$virtualstock = $prod->stock_theorique;	// virtual stock in infinite future
		} else {
			$stock = $currentstock;
			if (!empty($search_fk_warehouse)) {
				foreach ($search_fk_warehouse as $val) {
					$stock -= empty($movements_prod_warehouse[$objp->rowid][$val]) ? 0 : $movements_prod_warehouse[$objp->rowid][$val];
					$nbofmovement += empty($movements_prod_warehouse_nb[$objp->rowid][$val]) ? 0 : $movements_prod_warehouse_nb[$objp->rowid][$val];
				}
			} else {
				$stock -= empty($movements_prod[$objp->rowid]) ? 0 : $movements_prod[$objp->rowid];
				$nbofmovement += empty($movements_prod_nb[$objp->rowid]) ? 0 : $movements_prod_nb[$objp->rowid];
			}
		}


		if ($ext == 'csv') {
			if ($mode == 'future') {
				print implode(";", array(
					'"'.$objp->ref.'"',
					'"'.$objp->label.'"',
					'"'.price2num($currentstock, 'MS').'"',
					'"'.price2num($stock, 'MS').'"',
					'"'.price2num($virtualstock, 'MS').'"'))."\r\n";
				$totalvirtualstock += $virtualstock;
			} else {
				print implode(";", array(
					'"'.$objp->ref.'"',
					'"'.$objp->label.'"',
					'"'.price(price2num($stock, 'MS')).'"',
					price2num($stock * $objp->pmp, 'MT') ? '"'.price2num($stock * $objp->pmp, 'MT').'"' : '',
					!getDolGlobalString('PRODUIT_MULTIPRICES') ? '"'.price2num($stock * $objp->price, 'MT').'"' : '"'.$langs->trans("Variable").'('.$langs->trans("OptionMULTIPRICESIsOn").')"',
					"$nbofmovement",
					'"'.price2num($currentstock, 'MS').'"'))."\r\n";
				$totalbuyingprice += $stock * $objp->pmp;
				$totalsellingprice += $stock * $objp->price;
			}
			$totalcurrentstock += $currentstock;
		} else {
			print '<tr class="oddeven">';

			// Product ref
			print '<td class="nowrap">'.$prod->getNomUrl(1, '').'</td>';

			// Product label
			print '<td>';
			print dol_escape_htmltag($objp->label);
			print '</td>';

			if ($mode == 'future') {
				// Current stock
				print '<td class="right">'.price(price2num($currentstock, 'MS')).'</td>';
				//$totalcurrentstock += $currentstock;

				print '<td class="right"></td>';

				// Virtual stock at date
				print '<td class="right">'.price(price2num($stock, 'MS')).'</td>';

				// Final virtual stock
				print '<td class="right">'.price(price2num($virtualstock, 'MS')).'</td>';
				$totalvirtualstock += $virtualstock;
			} else {
				// Stock at date
				print '<td class="right">'.($stock ? price(price2num($stock, 'MS')) : ('<span class="opacitymedium">0</span>')).'</td>';

				// PMP value
				$estimatedvalue = $stock * $objp->pmp;
				print '<td class="right" title="'.dolPrintHTMLForAttribute($langs->trans("AverageUnitPricePMPShort").' ('.$langs->trans("Currently").'): '.price(price2num($objp->pmp, 'MU'), 1)).'">';
				if (price2num($estimatedvalue, 'MT')) {
					print '<span class="amount">'.price(price2num($estimatedvalue, 'MT'), 1).'</span>';
				} else {
					print '';
				}
				$totalbuyingprice += $estimatedvalue;
				print '</td>';

				// Selling value
				print '<td class="right"';
				if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
					print ' title="'.dolPrintHTMLForAttribute($langs->trans("SellingPrice").' ('.$langs->trans("Currently").'): '.price(price2num($objp->price, 'MU'), 1));
				}
				print '">';
				if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
					print '<span class="amount">';
					if ($stock || (float) ($stock * $objp->price)) {
						print price(price2num($stock * $objp->price, 'MT'), 1);
					}
					print '</span>';
					$totalsellingprice += $stock * $objp->price;
				} else {
					$htmltext = $langs->trans("OptionMULTIPRICESIsOn");
					print $form->textwithtooltip('<span class="opacitymedium">'.$langs->trans("Variable").'</span>', $htmltext);
				}
				print '</td>';

				// Movements
				print '<td class="right">';
				if ($nbofmovement > 0) {
					$url = DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$objp->rowid;
					if (GETPOSTISSET('datemonth')) {
						$url .= '&search_date_startday='.GETPOSTINT('dateday');
						$url .= '&search_date_startmonth='.GETPOSTINT('datemonth');
						$url .= '&search_date_startyear='.GETPOSTINT('dateyear');
					}
					if (count($search_fk_warehouse) > 1) {
						$url = '';	// Do not show link, multi warehouse as filter not managed yet by target page
					} else {
						foreach ($search_fk_warehouse as $val) {
							$url .= ($val > 0 ? '&search_warehouse='.((int) $val) : '');
						}
					}
					if ($url) {
						print '<a href="'.$url.'">';
					}
					print $langs->trans("Movements");
					print '<span class="tabs paddingleft"><span class="badge">'.$nbofmovement.'</span></span>';
					if ($url) {
						print '</a>';
					}
				}
				print '</td>';

				// Current stock
				print '<td class="right">'.($currentstock ? price(price2num($currentstock, 'MS')) : '<span class="opacitymedium">0</span>').'</td>';
			}
			$totalcurrentstock += $currentstock;

			// Fields from hook
			$parameters = array('objp' => $objp);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			// Action
			print '<td class="right"></td>';

			print '</tr>'."\n";
		}
	}
	$i++;
}

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
if ($ext != 'csv') {
	print $hookmanager->resPrint;
}

$colspan = 8;
if ($mode == 'future') {
	$colspan++;
}

if ($ext == 'csv') {
	print implode(
		";",
		($mode == 'future') ? array(
		'"'.$langs->trans("Totalforthispage").'"',
		'',
		$productid > 0 ? price2num($totalcurrentstock, 'MS') : '',
		'',
		price(price2num($totalvirtualstock, 'MS'))) :
		array(
		'"'.$langs->trans("Totalforthispage").'"',
		'',
		'',
		'"'.price2num($totalbuyingprice, 'MT').'"',
		!getDolGlobalString('PRODUIT_MULTIPRICES') ? '"'.price2num($totalsellingprice, 'MT').'"' : '',
		'',
		$productid > 0 ? price2num($totalcurrentstock, 'MS') : '')
	);
} else {
	if (empty($date) || !$dateIsValid) {
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("EnterADateCriteria").'</span></td></tr>';
	} else {
		print '<tr class="liste_total">';
		print '<td>'.$langs->trans("Totalforthispage").'</td>';
		print '<td></td>';
		if ($mode == 'future') {
			print '<td class="right">'.price(price2num($totalcurrentstock, 'MS')).'</td>';
			print '<td></td>';
			print '<td></td>';
			print '<td class="right">'.price(price2num($totalvirtualstock, 'MS')).'</td>';
		} else {
			print '<td></td>';
			print '<td class="right">'.price(price2num($totalbuyingprice, 'MT')).'</td>';
			if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
				print '<td class="right">'.price(price2num($totalsellingprice, 'MT')).'</td>';
			} else {
				print '<td></td>';
			}
			print '<td></td>';
			print '<td class="right">'.($productid > 0 ? price(price2num($totalcurrentstock, 'MS')) : '').'</td>';
		}
		print '<td></td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print '</form>';

	llxFooter();
}

if (!empty($resql)) {
	$db->free($resql);
}

$db->close();
