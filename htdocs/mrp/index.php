<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2019	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/mrp/index.php
 *       \ingroup    bom, mrp
 *       \brief      Home page for BOM and MRP modules
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('mrpindex'));

// Load translation files required by the page
$langs->loadLangs(array("companies", "mrp"));

// Security check
$result = restrictedArea($user, 'bom|mrp');

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);


/*
 * View
 */

$staticbom = new BOM($db);
$staticmo = new Mo($db);

$title = $langs->trans('MRP');
$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-mrp page-index');

print load_fiche_titre($langs->trans("MRPArea"), '', 'mrp');


print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


/*
 * Statistics
 */

if (isModEnabled('mrp') && $conf->use_javascript_ajax) {
	$sql = "SELECT COUNT(t.rowid) as nb, status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as t";
	$sql .= " GROUP BY t.status";
	$sql .= " ORDER BY t.status ASC";
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		$totalnb = 0;
		$dataseries = array();
		$colorseries = array();
		$vals = array();

		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$vals[$obj->status] = $obj->nb;

				$totalnb += $obj->nb;
			}
			$i++;
		}
		$db->free($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("ManufacturingOrder").'</th>';
		print '</tr>'."\n";
		$listofstatus = array(0, 1, 2, 3, 9);
		foreach ($listofstatus as $status) {
			$dataseries[] = array($staticmo->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
			if ($status == Mo::STATUS_DRAFT) {
				$colorseries[$status] = '-'.$badgeStatus0;
			}
			if ($status == Mo::STATUS_VALIDATED) {
				$colorseries[$status] = $badgeStatus1;
			}
			if ($status == Mo::STATUS_INPROGRESS) {
				$colorseries[$status] = $badgeStatus4;
			}
			if ($status == Mo::STATUS_PRODUCED) {
				$colorseries[$status] = $badgeStatus6;
			}
			if ($status == Mo::STATUS_CANCELED) {
				$colorseries[$status] = $badgeStatus9;
			}

			if (empty($conf->use_javascript_ajax)) {
				print '<tr class="oddeven">';
				print '<td>'.$staticmo->LibStatut($status, 0).'</td>';
				print '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
				print "</tr>\n";
			}
		}
		if ($conf->use_javascript_ajax) {
			print '<tr><td class="center" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->SetHeight('200');
			$dolgraph->draw('idgraphstatus');
			print $dolgraph->show($totalnb ? 0 : 1);

			print '</td></tr>';
		}
		print "</table>";
		print "</div>";

		print "<br>";
	} else {
		dol_print_error($db);
	}
}

print '<br>';


print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


/*
 * Last modified BOM
 */

if (isModEnabled('bom')) {
	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status, a.fk_product";
	$sql .= " FROM ".MAIN_DB_PREFIX."bom_bom as a";
	$sql .= " WHERE a.entity IN (".getEntity('bom').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("LatestBOMModified", $max);
		$lastmodified = '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?sortfield=t.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
		$lastmodified .= '</a>';
		print $lastmodified;
		print '</th>';
		print '<th class="right">';
		//print '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?sortfield=t.tms&sortorder=DESC">'.img_picto($langs->trans("FullList"), 'bom');
		print '</th>';
		print '</tr>';

		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$staticbom->id = $obj->rowid;
				$staticbom->ref = $obj->ref;
				$staticbom->fk_product = $obj->fk_product;
				$staticbom->date_modification = $obj->datem;
				$staticbom->status = $obj->status;

				print '<tr class="oddeven">';
				print '<td>'.$staticbom->getNomUrl(1, '32').'</td>';
				print '<td>'.dol_print_date($db->jdate($obj->datem), 'dayhour').'</td>';
				print '<td class="right">'.$staticbom->getLibStatut(3).'</td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


/*
 * Last modified MOs
 */

if (isModEnabled('mrp')) {
	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as a";
	$sql .= " WHERE a.entity IN (".getEntity('mo').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as a";
	$sql .= " WHERE a.entity IN (".getEntity('mo').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("LatestMOModified", $max);
		$lastmodified = '<a href="'.DOL_URL_ROOT.'/mrp/mo_list.php?sortfield=t.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
		$lastmodified .= '</a>';
		print $lastmodified;
		print '</th>';
		print '<th class="right">';
		//print '<a href="'.DOL_URL_ROOT.'/mrp/mo_list.php?sortfield=t.tms&sortorder=DESC">'.img_picto($langs->trans("FullList"), 'mrp');
		print '</th>';
		print '</tr>';

		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$staticmo->id = $obj->rowid;
				$staticmo->ref = $obj->ref;
				$staticmo->date_modification = $obj->datem;
				$staticmo->status = $obj->status;

				print '<tr class="oddeven">';
				print '<td>'.$staticmo->getNomUrl(1, '32').'</td>';
				print '<td>'.dol_print_date($db->jdate($obj->datem), 'dayhour').'</td>';
				print '<td class="right">'.$staticmo->getLibStatut(3).'</td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}

print '</div></div></div>';

$object = new stdClass();
$parameters = array(
	//'type' => $type,
	'user' => $user,
);
$reshook = $hookmanager->executeHooks('dashboardMRP', $parameters, $object);

// End of page
llxFooter();
$db->close();
