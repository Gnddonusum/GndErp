<?php
/* Copyright (C) 2012      Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2005-2015 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2024  Frédéric France        <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/boxes/box_activity.php
 *  \ingroup    societes
 *  \brief      Module to show box of bills, orders & propal of the current year
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box of customer activity (invoice, order, proposal)
 */
class box_activity extends ModeleBoxes
{
	public $boxcode = "activity";
	public $boximg = "object_bill";
	public $boxlabel = 'BoxGlobalActivity';
	public $depends = array("facture");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $conf, $user;

		$this->db = $db;

		// FIXME: Pb into some status
		$this->enabled = getDolGlobalInt('MAIN_FEATURES_LEVEL'); // Not enabled by default due to bugs (see previous comments)

		$this->hidden = !(
			(isModEnabled('invoice') && $user->hasRight('facture', 'read'))
			|| (isModEnabled('order') && $user->hasRight('commande', 'read'))
			|| (isModEnabled('propal') && $user->hasRight('propal', 'read'))
		);
	}

	/**
	 *  Charge les donnees en memoire pour affichage ulterieur
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$totalnb = 0;
		$line = 0;
		$now = dol_now();
		$nbofperiod = 3;

		// Force use of cache for this box as it has very bad performances
		$savMAIN_ACTIVATE_FILECACHE = getDolGlobalInt('MAIN_ACTIVATE_FILECACHE');
		$conf->global->MAIN_ACTIVATE_FILECACHE = 1;

		if (getDolGlobalString('MAIN_BOX_ACTIVITY_DURATION')) {
			$nbofperiod = getDolGlobalString('MAIN_BOX_ACTIVITY_DURATION');
		}

		$textHead = $langs->trans("Activity").' - '.$langs->trans("LastXMonthRolling", $nbofperiod);
		$this->info_box_head = array(
			'text' => $textHead,
			'limit' => dol_strlen($textHead),
		);

		// compute the year limit to show
		$tmpdate = dol_time_plus_duree(dol_now(), -1 * $nbofperiod, "m");


		// list the summary of the propals
		if (isModEnabled("propal") && $user->hasRight("propal", "lire")) {
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$propalstatic = new Propal($this->db);

			$data = array();

			$sql = "SELECT p.fk_statut, SUM(p.total_ttc) as mnttot, COUNT(*) as nb";
			$sql .= " FROM (".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= ")";
			$sql .= " WHERE p.entity IN (".getEntity('propal').")";
			$sql .= " AND p.fk_soc = s.rowid";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql .= " AND p.datep >= '".$this->db->idate($tmpdate)."'";
			$sql .= " AND p.date_cloture IS NULL"; // just unclosed
			$sql .= " GROUP BY p.fk_statut";
			$sql .= " ORDER BY p.fk_statut DESC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$j = 0;
				while ($j < $num) {
					$data[$j] = $this->db->fetch_object($result);

					$j++;
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			if (!empty($data)) {
				$j = 0;
				while ($j < count($data)) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'url' => DOL_URL_ROOT."/comm/propal/list.php?mainmenu=commercial&leftmenu=propals&search_status=".((int) $data[$j]->fk_statut),
						'tooltip' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
						'logo' => 'object_propal'
					);

					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="right"',
						'text' => $data[$j]->nb,
						'tooltip' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
						'url' => DOL_URL_ROOT."/comm/propal/list.php?mainmenu=commercial&leftmenu=propals&search_status=".((int) $data[$j]->fk_statut),
					);
					$totalnb += $data[$j]->nb;

					$this->info_box_contents[$line][3] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($data[$j]->mnttot, 1, $langs, 0, 0, -1, $conf->currency),
					);
					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $propalstatic->LibStatut($data[$j]->fk_statut, 3),
					);

					$line++;
					$j++;
				}
				if (count($data) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoRecordedProposals").'</span>',
					);
					$line++;
				}
			}
		}

		// list the summary of the orders
		if (isModEnabled('order') && $user->hasRight("commande", "lire")) {
			include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$commandestatic = new Commande($this->db);

			$langs->load("orders");

			$data = array();

			$sql = "SELECT c.fk_statut, sum(c.total_ttc) as mnttot, count(*) as nb";
			$sql .= " FROM (".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= ")";
			$sql .= " WHERE c.entity IN (".getEntity('commande').")";
			$sql .= " AND c.fk_soc = s.rowid";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql .= " AND c.date_commande >= '".$this->db->idate($tmpdate)."'";
			$sql .= " GROUP BY c.fk_statut";
			$sql .= " ORDER BY c.fk_statut DESC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num) {
					$data[$j] = $this->db->fetch_object($result);
					$j++;
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			if (!empty($data)) {
				$j = 0;
				while ($j < count($data)) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'url' => DOL_URL_ROOT."/commande/list.php?mainmenu=commercial&amp;leftmenu=orders&amp;search_status=".$data[$j]->fk_statut,
						'tooltip' => $langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
						'logo' => 'object_order',
					);

					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="right"',
						'text' => $data[$j]->nb,
						'tooltip' => $langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
						'url' => DOL_URL_ROOT."/commande/list.php?mainmenu=commercial&amp;leftmenu=orders&amp;search_status=".$data[$j]->fk_statut,
					);
					$totalnb += $data[$j]->nb;

					$this->info_box_contents[$line][3] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($data[$j]->mnttot, 1, $langs, 0, 0, -1, $conf->currency),
					);
					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $commandestatic->LibStatut($data[$j]->fk_statut, 0, 3),
					);

					$line++;
					$j++;
				}
				if (count($data) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedOrders"),
					);
					$line++;
				}
			}
		}


		// list the summary of the bills
		if (isModEnabled('invoice') && $user->hasRight("facture", "lire")) {
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$facturestatic = new Facture($this->db);

			// part 1
			$data = array();
			$sql = "SELECT f.fk_statut, SUM(f.total_ttc) as mnttot, COUNT(*) as nb";
			$sql .= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= ")";
			$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql .= " AND f.fk_soc = s.rowid";
			$sql .= " AND f.datef >= '".$this->db->idate($tmpdate)."' AND f.paye=1";
			$sql .= " GROUP BY f.fk_statut";
			$sql .= " ORDER BY f.fk_statut DESC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num) {
					$data[$j] = $this->db->fetch_object($result);
					$j++;
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			if (!empty($data)) {
				$j = 0;
				while ($j < count($data)) {
					$billurl = "search_status=2&paye=1";
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0),
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills",
						'logo' => 'bill',
					);

					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0),
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="right"',
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0),
						'text' => $data[$j]->nb,
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills",
					);

					$this->info_box_contents[$line][3] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($data[$j]->mnttot, 1, $langs, 0, 0, -1, $conf->currency)
					);

					// We add only for the current year
					$totalnb += $data[$j]->nb;

					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut(1, $data[$j]->fk_statut, 3),
					);
					$line++;
					$j++;
				}
				if (count($data) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedInvoices"),
					);
					$line++;
				}
			}

			// part 2
			$data = array();
			$sql = "SELECT f.fk_statut, SUM(f.total_ttc) as mnttot, COUNT(*) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
			$sql .= " AND f.fk_soc = s.rowid";
			$sql .= " AND f.datef >= '".$this->db->idate($tmpdate)."' AND f.paye=0";
			$sql .= " GROUP BY f.fk_statut";
			$sql .= " ORDER BY f.fk_statut DESC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num) {
					$data[$j] = $this->db->fetch_object($result);
					$j++;
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			if (!empty($data)) {
				$alreadypaid = -1;

				$j = 0;
				while ($j < count($data)) {
					$billurl = "search_status=".$data[$j]->fk_statut."&paye=0";
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills",
						'logo' => 'bill',
					);

					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="right"',
						'text' => $data[$j]->nb,
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&amp;mainmenu=accountancy&amp;leftmenu=customers_bills",
					);
					$totalnb += $data[$j]->nb;
					$this->info_box_contents[$line][3] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($data[$j]->mnttot, 1, $langs, 0, 0, -1, $conf->currency),
					);
					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut(0, $data[$j]->fk_statut, 3, $alreadypaid),
					);
					$line++;
					$j++;
				}
				if (count($data) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedUnpaidInvoices"),
					);
					$line++;
				}
			}
		}

		// Add the sum in the bottom of the boxes
		$this->info_box_contents[$line][0] = array('tr' => 'class="liste_total_wrap"');
		$this->info_box_contents[$line][1] = array('td' => 'class="liste_total left" ', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$line][2] = array('td' => 'class="liste_total right" ', 'text' => $totalnb);
		$this->info_box_contents[$line][3] = array('td' => 'class="liste_total right" ', 'text' => '');
		$this->info_box_contents[$line][4] = array('td' => 'class="liste_total right" ', 'text' => "");

		$conf->global->MAIN_ACTIVATE_FILECACHE = $savMAIN_ACTIVATE_FILECACHE;
	}




	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
