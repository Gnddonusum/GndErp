<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/don/stats/index.php
 *  \ingroup    donations
 *  \brief      Page with donations statistics
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/donstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

// Load translation files required by the page
$langs->loadLangs(array("donations"));

$userid = GETPOSTINT('userid');
$socid = GETPOSTINT('socid');
// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$status = GETPOSTINT('status');
$nowyear = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$typent_id = GETPOSTINT('typent_id');
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;
$mode = GETPOST("mode") ? GETPOST("mode") : 'customer';
$custcats = GETPOST('custcats', 'array');

// Security check
$result = restrictedArea($user, 'don');

/*
 * View
 */
$form = new Form($db);
$formcompany = new FormCompany($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-don page-stats_index');

$dir = $conf->don->dir_temp;

print load_fiche_titre($langs->trans("DonationsStatistics"), '', 'donation');

dol_mkdir($dir);

$stats = new DonationStats($db, $socid, '', ($userid > 0 ? $userid : 0), ($typent_id > 0 ? $typent_id : 0), ($status > 0 ? $status : 4));

if (is_array($custcats) && !empty($custcats)) {
	$stats->from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_societe as cat ON (d.fk_soc = cat.fk_soc)';
	$stats->where .= ' AND cat.fk_categorie IN ('.$db->sanitize(implode(',', $custcats)).')';
}

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);

$filenamenb = $dir."/salariesnbinyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=donationStats&amp;file=donationinyear-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NbOfDonations"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NumberByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);

$filenameamount = $dir."/donationamount-".$year.".png";
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=donationStats&amp;file=donationamoutinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$px2->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("Amount"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountTotal"));

	$px2->draw($filenameamount, $fileurlamount);
}

$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

$filename_avg = $dir."/donationaverage-".$year.".png";
$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=donationStats&file=donationaverageinyear-'.$year.'.png';

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (!$mesg) {
	$px3->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px3->SetLegend($legend);
	$px3->SetYLabel($langs->trans("AmountAverage"));
	$px3->SetMaxValue($px3->GetCeilMaxValue());
	$px3->SetMinValue((int) $px3->GetFloorMinValue());
	$px3->SetWidth($WIDTH);
	$px3->SetHeight($HEIGHT);
	$px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	$px3->mode = 'depth';
	$px3->SetTitle($langs->trans("AmountAverage"));

	$px3->draw($filename_avg, $fileurl_avg);
}

// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach ($data as $val) {
	if (!empty($val['year'])) {
		$arrayyears[$val['year']] = $val['year'];
	}
}
if (!count($arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}

$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/don/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

$type = 'donation_stats';

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

print dol_get_fiche_head($head, 'byyear', '', -1);


print '<div class="fichecenter"><div class="fichethirdleft">';

// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';

// Company
if (empty(!$conf->global->DONATION_USE_THIRDPARTIES)) {
	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print $form->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'widthcentpercentminusx maxwidth300', '');
	print '</td></tr>';
}

// ThirdParty Type
print '<tr><td>'.$langs->trans("ThirdPartyType").'</td><td>';
$sortparam_typent = (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT);
print $form->selectarray("typent_id", $formcompany->typent_array(0), $typent_id, 1, 0, 0, '', 0, 0, 0, $sortparam_typent, '', 1);
if ($user->admin) {
	print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
print '</td></tr>';

// Category
if (isModEnabled('category')) {
	$cat_type = Categorie::TYPE_CUSTOMER;
	$cat_label = $langs->trans("Category").' '.lcfirst($langs->trans("Customer"));
	print '<tr><td>'.$cat_label.'</td><td>';
	$cate_arbo = $form->select_all_categories($cat_type, '', 'parent', 0, 0, 1);
	print img_picto('', 'category', 'class="pictofixedwidth"');
	if (is_array($cate_arbo) && is_array($cate_arbo[0])) {
		print $form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), 0, 0, 'widthcentpercentminusx maxwidth300');
	}
	print '</td></tr>';
}

// User
print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid, 'userid', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

// Status
print '<tr><td>'.$langs->trans("Status").'</td><td>';
$liststatus = array(
	'2' => $langs->trans("DonationStatusPaid"),
	'0' => $langs->trans("DonationStatusPromiseNotValidated"),
	'1' => $langs->trans("DonationStatusPromiseValidated"),
	'3' => $langs->trans("Canceled")
);
print $form->selectarray('status', $liststatus, 4, 1);

// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0, 0, 0, '', 0, 0, 0, '', 'width75');

print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfDonations").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountTotal").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountAverage").'</td>';
print '<td class="right">%</td>';
print '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while (!empty($year) && $oldyear > (int) $year + 1) {
		$oldyear--;
		print '<tr class="oddeven" height="24">';
		print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'">'.$oldyear.'</a></td>';

		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right amount">0</td>';
		print '<td class="right"></td>';
		print '<td class="right amount">0</td>';
		print '<td class="right"></td>';
		print '</tr>';
	}

	$greennb = (empty($val['nb_diff']) || $val['nb_diff'] >= 0);
	$greentotal = (empty($val['total_diff']) || $val['total_diff'] >= 0);
	$greenavg = (empty($val['avg_diff']) || $val['avg_diff'] >= 0);

	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right opacitylow" style="'.($greennb ? 'color: green;' : 'color: red;').'">'.(!empty($val['nb_diff']) && $val['nb_diff'] < 0 ? '' : '+').round(!empty($val['nb_diff']) ? $val['nb_diff'] : 0).'%</td>';
	print '<td class="right"><span class="amount">'.price(price2num($val['total'], 'MT'), 1).'</span></td>';
	print '<td class="right opacitylow" style="'.($greentotal ? 'color: green;' : 'color: red;').'">'.(!empty($val['total_diff']) && $val['total_diff'] < 0 ? '' : '+').round(!empty($val['total_diff']) ? $val['total_diff'] : 0).'%</td>';
	print '<td class="right"><span class="amount">'.price(price2num($val['avg'], 'MT'), 1).'</span></td>';
	print '<td class="right opacitylow" style="'.($greenavg ? 'color: green;' : 'color: red;').'">'.(!empty($val['avg_diff']) && $val['avg_diff'] < 0 ? '' : '+').round(!empty($val['avg_diff']) ? $val['avg_diff'] : 0).'%</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright">';

// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	print $mesg;
} else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
	print "<br>\n";
	print $px3->show();
}
print '</td></tr></table>';

print '</div></div>';
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
