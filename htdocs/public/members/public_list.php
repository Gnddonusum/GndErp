<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/public/members/public_list.php
 *	\ingroup    member
 *  \brief      File sample to list members
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
// if (is_numeric($entity)) { // $entity is casted to int
define("DOLENTITY", $entity);
// }

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

// Security check
if (!isModEnabled('member')) {
	httponly_accessforbidden('Module Membership not enabled');
}

$langs->loadLangs(array("main", "members", "companies", "other"));


/**
 * Show header for member list
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])  // @phan-suppress-current-line PhanRedefineFunction
{
	top_htmlhead($head, $title);

	print '<body class="public_body">'."\n";
}

/**
 * Show footer for member list
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @return	void
 */
function llxFooterVierge()  // @phan-suppress-current-line PhanRedefineFunction
{
	printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
}


$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$filter = GETPOST('filter');
$statut = GETPOST('statut');

if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "lastname";
}


/*
 * View
 */

if (!getDolGlobalString('MEMBER_PUBLIC_ENABLED')) {
	httponly_accessforbidden('Public access of list of members is not enabled. See setup of module membership to enable it.');
}

$form = new Form($db);

$morehead = '';
if (getDolGlobalString('MEMBER_PUBLIC_CSS')) {
	$morehead = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MEMBER_PUBLIC_CSS').'">';
} else {
	$morehead = '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/eldy/style.css.php">';
}

llxHeaderVierge($langs->trans("ListOfValidatedPublicMembers"), $morehead);

$sql = "SELECT rowid, firstname, lastname, societe, zip, town, email, birth, photo";

$sqlfields = $sql;

$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
$sql .= " WHERE entity = ".((int) $entity);
$sql .= " AND statut = 1";
$sql .= " AND public = 1";

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

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($conf->liste_limit + 1, $offset);


$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	$param = "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield";
	$title = $langs->trans("ListOfValidatedPublicMembers");
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');
	print '<table class="public_border centpercent">';

	print '<tr class="public_liste_titre">';
	print '<th class="left"><a href="'.$_SERVER["PHP_SELF"].'?page='.$page.'&sortorder=ASC&sortfield=firstname">'.dolGetFirstLastname($langs->trans("Firstname"), $langs->trans("Lastname")).'</a></th>';
	print '<th class="left"><a href="'.$_SERVER["PHP_SELF"].'?page='.$page.'&sortorder=ASC&sortfield=societe">'.$langs->trans("Company").'</a></th>'."\n";
	//print_liste_field_titre("DateOfBirth", $_SERVER["PHP_SELF"],"birth",'',$param,$sortfield,$sortorder); // est-ce nécessaire ??
	print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "email", '', $param, '', $sortfield, $sortorder, 'left public_');
	print_liste_field_titre("Zip", $_SERVER["PHP_SELF"], "zip", "", $param, '', $sortfield, $sortorder, 'left public_');
	print_liste_field_titre("Town", $_SERVER["PHP_SELF"], "town", "", $param, '', $sortfield, $sortorder, 'left public_');
	print_liste_field_titre("Photo", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center public_');
	print "</tr>\n";

	while ($i < $num && $i < $conf->liste_limit) {
		$objp = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print '<td><a href="public_card.php?id='.$objp->rowid.'">'.dolGetFirstLastname($objp->firstname, $objp->lastname).'</a></td>'."\n";
		print '<td>'.$objp->societe.'</td>'."\n";
		print '<td>'.$objp->email.'</td>'."\n";
		print '<td>'.$objp->zip.'</td>'."\n";
		print '<td>'.$objp->town.'</td>'."\n";
		if (isset($objp->photo) && $objp->photo != '') {
			print '<td class="center">';
			print $form->showphoto('memberphoto', $objp, 64);
			print '</td>'."\n";
		} else {
			print "<td>&nbsp;</td>\n";
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
} else {
	dol_print_error($db);
}


llxFooterVierge();

$db->close();
