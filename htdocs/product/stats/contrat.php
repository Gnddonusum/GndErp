<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
 *       \file       htdocs/product/stats/contrat.php
 *       \ingroup    product service contrat
 *       \brief      Page des stats des contrats pour un produit
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('contracts', 'products', 'companies'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productstatscontract'));

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
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "c.date_contrat";
}

$socid = 0;

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * View
 */

$staticcontrat = new Contrat($db);
$staticcontratligne = new ContratLigne($db);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array('id' => $id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	llxHeader("", "", $langs->trans("CardProduct".$product->type), '', 0, 0, '', '', 'mod-product page-stats_contrat');

	if ($result > 0) {
		$head = product_prepare_head($product);
		$titre = $langs->trans("CardProduct".$product->type);
		$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
		print dol_get_fiche_head($head, 'referers', $titre, -1, $picto);

		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		$nboflines = show_stats_for_company($product, $socid);

		print "</table>";

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();


		$now = dol_now();

		$sql = "SELECT";
		$sql .= " sum(".$db->ifsql("cd.statut=0", '1', '0').') as nb_initial,';
		$sql .= " sum(".$db->ifsql("cd.statut=4 AND cd.date_fin_validite > '".$db->idate($now)."'", '1', '0').") as nb_running,";
		$sql .= " sum(".$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= '".$db->idate($now)."')", '1', '0').') as nb_late,';
		$sql .= " sum(".$db->ifsql("cd.statut=5", '1', '0').') as nb_closed,';
		$sql .= " c.rowid as rowid, c.ref, c.ref_customer, c.ref_supplier, c.date_contrat, c.statut as statut,";
		$sql .= " s.nom as name, s.rowid as socid, s.code_client";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= ", ".MAIN_DB_PREFIX."contrat as c";
		$sql .= ", ".MAIN_DB_PREFIX."contratdet as cd";
		$sql .= " WHERE c.rowid = cd.fk_contrat";
		$sql .= " AND c.fk_soc = s.rowid";
		$sql .= " AND c.entity IN (".getEntity('contract').")";
		$sql .= " AND cd.fk_product = ".((int) $product->id);
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($socid) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		$sql .= " GROUP BY c.rowid, c.ref, c.ref_customer, c.ref_supplier, c.date_contrat, c.statut, s.nom, s.rowid, s.code_client";
		$sql .= $db->order($sortfield, $sortorder);

		//Calcul total qty and amount for global if full scan list
		$total_ht = 0;
		$total_qty = 0;

		// Count total nb of records
		$totalofrecords = '';
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			$result = $db->query($sql);
			$totalofrecords = $db->num_rows($result);
		}

		$sql .= $db->plimit($limit + 1, $offset);

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);

			$option = '&id='.$product->id;

			if ($limit > 0 && $limit != $conf->liste_limit) {
				$option .= '&limit='.((int) $limit);
			}
			/*
			if (!empty($search_month)) {
				$option .= '&search_month='.urlencode($search_month);
			}
			if (!empty($search_year)) {
				$option .= '&search_year='.urlencode((string) ($search_year));
			}
			*/

			print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'" name="search_form">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			if (!empty($sortfield)) {
				print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
			}
			if (!empty($sortorder)) {
				print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
			}

			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			print_barre_liste($langs->trans("Contracts"), $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, '', $num, $totalofrecords, '', 0, '', '', $limit, 0, 0, 1);

			if (!empty($page)) {
				$option .= '&page='.urlencode((string) ($page));
			}

			$i = 0;
			print '<div class="div-table-responsive">';
			print '<table class="tagtable liste listwithfilterbefore" width="100%">';

			print '<tr class="liste_titre">';
			print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "c.rowid", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
			print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
			print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
			print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "c.date_contrat", "", "&amp;id=".$product->id, 'align="center"', $sortfield, $sortorder);
			//print_liste_field_titre("AmountHT"),$_SERVER["PHP_SELF"],"c.amount","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($staticcontratligne->LibStatut($staticcontratligne::STATUS_INITIAL, 3, -1, 'class="nochangebackground"'), $_SERVER["PHP_SELF"], "", '', '', 'align="center" width="16"', $sortfield, $sortorder, 'maxwidthsearch ');
			print_liste_field_titre($staticcontratligne->LibStatut($staticcontratligne::STATUS_OPEN, 3, -1, 'class="nochangebackground"'), $_SERVER["PHP_SELF"], "", '', '', 'align="center" width="16"', $sortfield, $sortorder, 'maxwidthsearch ');
			print_liste_field_titre($staticcontratligne->LibStatut($staticcontratligne::STATUS_CLOSED, 3, -1, 'class="nochangebackground"'), $_SERVER["PHP_SELF"], "", '', '', 'align="center" width="16"', $sortfield, $sortorder, 'maxwidthsearch ');
			print "</tr>\n";

			$contracttmp = new Contrat($db);

			if ($num > 0) {
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($result);

					$contracttmp->id = $objp->rowid;
					$contracttmp->ref = $objp->ref;
					$contracttmp->ref_customer = $objp->ref_customer;
					$contracttmp->ref_supplier = $objp->ref_supplier;

					print '<tr class="oddeven">';
					print '<td>';
					print $contracttmp->getNomUrl(1);
					print "</td>\n";
					print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.dol_trunc($objp->name, 44).'</a></td>';
					print "<td>".$objp->code_client."</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->date_contrat), 'dayhour')."</td>";
					//print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
					//print '<td align="right">';
					print '<td class="center">'.($objp->nb_initial > 0 ? $objp->nb_initial : '').'</td>';
					print '<td class="center">'.($objp->nb_running + $objp->nb_late > 0 ? $objp->nb_running + $objp->nb_late : '').'</td>';
					print '<td class="center">'.($objp->nb_closed > 0 ? $objp->nb_closed : '').'</td>';
					//$contratstatic->LibStatut($objp->statut,5).'</td>';
					print "</tr>\n";
					$i++;
				}
			} else {
				print '<tr><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			print '</table>';
			print '</div>';
			print '</form>';
		} else {
			dol_print_error($db);
		}
		$db->free($result);
	}
} else {
	dol_print_error();
}

// End of page
llxFooter();
$db->close();
