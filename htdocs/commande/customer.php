<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       		<regis.houssin@inodbox.com>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya	<jove@bisquerra.com>
 * Copyright (C) 2012	   David Rodriguez Martinez <davidrm146@gmail.com>
 * Copyright (C) 2012	   Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/commande/customer.php
 *	\ingroup    compta
 *	\brief      Show list of customers to add an new invoice from orders
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');

// Secrutiy check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

if (!$user->hasRight('facture', 'creer')) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "orders"));

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
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "nom";
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-commande page-customer');

// Mode List

$sql = "SELECT s.rowid, s.nom as name, s.client, s.town, s.datec,";
$sql .= " st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta as code_compta_client";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st, ".MAIN_DB_PREFIX."commande as c";
$sql .= " WHERE s.fk_stcomm = st.id AND c.fk_soc = s.rowid";
$sql .= " AND s.entity IN (".getEntity('societe').")";
if (GETPOST("search_nom")) {
	$sql .= natural_search("s.nom", GETPOST("search_nom"));
}
if (GETPOST("search_compta")) {
	$sql .= natural_search("s.code_compta", GETPOST("search_compta"));
}
if (GETPOST("search_code_client")) {
	$sql .= natural_search("s.code_client", GETPOST("search_code_client"));
}
// If the internal user must only see his customers, force searching by him
$search_sale = 0;
if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search on socid
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
$sql .= " AND c.fk_statut in (1, 2) AND c.facture = 0";
$sql .= " GROUP BY s.nom, s.rowid";
$sql .= $db->order($sortfield, $sortorder);

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
//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print_barre_liste($langs->trans("MenuOrdersToBill"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';

	print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "", 'valign="center"', $sortfield, $sortorder);
	print_liste_field_titre("Town", $_SERVER["PHP_SELF"], "s.town", "", "", 'valign="center"', $sortfield, $sortorder);
	print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("AccountancyCode", $_SERVER["PHP_SELF"], "s.code_compta", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "datec", "", "", 'class="right"', $sortfield, $sortorder);
	print "</tr>\n";

	// Fields title search
	print '<tr class="liste_titre">';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" name="search_nom" value="'.dol_escape_htmltag(GETPOST("search_nom")).'"></td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_code_client" value="'.dol_escape_htmltag(GETPOST("search_code_client")).'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_compta" value="'.dol_escape_htmltag(GETPOST("search_compta")).'">';
	print '</td>';

	print '<td colspan="2" class="liste_titre right">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"), 'search.png', '', 0, 1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';

	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td class="tdoverflowmax150">';

		$result = '';
		$link = $linkend = '';
		$link = '<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$obj->rowid.'">';
		$linkend = '</a>';
		$name = $obj->name;
		$result .= ($link.img_object($langs->trans("ShowCompany").': '.$name, 'company').$linkend);
		$result .= $link.$name.$linkend;

		print $result;
		print '</td>';
		print '<td>'.dolPrintLabel($obj->town).'</td>';
		print '<td class="left">'.dolPrintLabel($obj->code_client).'</td>';
		print '<td class="left">'.dolPrintLabel($obj->code_compta_client).'</td>';
		print '<td class="right">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
