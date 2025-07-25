<?php
/* Copyright (C) 2002       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2017       Alexandre Spangaro	    <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL		    <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/compta/sociales/class/chargesociales.class.php
 *		\ingroup    invoice
 *		\brief      File for the ChargesSociales class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class for managing the social charges.
 *  The collected VAT is computed only on the paid invoices/charges
 */
class ChargeSociales extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'chargesociales';

	/**
	 * @var string Name of table without prefix where object is stored
	 * @deprecated Use $table_element
	 * @see $table_element
	 */
	public $table = 'chargesociales';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'chargesociales';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'bill';

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * @var int|string
	 */
	public $date_ech;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var int
	 */
	public $type;

	/**
	 * @var string
	 */
	public $type_label;

	/**
	 * @var string
	 */
	public $type_code;

	/**
	 * @var string
	 */
	public $type_accountancy_code;

	/**
	 * @var int|string
	 */
	public $amount;

	/**
	 * @var int<0,1>
	 */
	public $paye;

	/**
	 * @deprecated Use $period
	 * @var int|string
	 */
	public $periode;

	/**
	 * @var int|string
	 */
	public $period;

	/**
	 * @var string
	 * @deprecated Use $label instead
	 */
	public $lib;

	/**
	 * @var int account ID
	 */
	public $fk_account;

	/**
	 * @var int account ID (identical to fk_account)
	 */
	public $accountid;

	/**
	 * @var int payment type (identical to mode_reglement_id in commonobject class)
	 */
	public $paiementtype;

	/**
	 * @var int ID
	 */
	public $mode_reglement_id;

	/**
	 * @var string
	 */
	public $mode_reglement_code;

	/**
	 * @var string
	 */
	public $mode_reglement;

	/**
	 * @var int ID
	 */
	public $fk_project;

	/**
	 * @var int ID
	 */
	public $fk_user;

	/**
	 * @var double total
	 */
	public $total;

	/**
	 * @var float total paid
	 */
	public $totalpaid;

	/**
	 * @var int
	 */
	const STATUS_UNPAID = 0;

	/**
	 * @var int
	 */
	const STATUS_PAID = 1;


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *  Retrouve et charge une charge sociale
	 *
	 *  @param	int     $id		Id
	 *  @param	string  $ref	Ref
	 *  @return	int Return integer <0 KO >0 OK
	 */
	public function fetch($id, $ref = '')
	{
		$sql = "SELECT cs.rowid, cs.date_ech";
		$sql .= ", cs.libelle as label, cs.fk_type, cs.amount, cs.fk_projet as fk_project, cs.paye, cs.periode as period, cs.import_key";
		$sql .= ", cs.fk_account, cs.fk_mode_reglement, cs.fk_user, note_public, note_private";
		$sql .= ", c.libelle as type_label, c.code as type_code, c.accountancy_code as type_accountancy_code";
		$sql .= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_chargesociales as c ON cs.fk_type = c.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON cs.fk_mode_reglement = p.id';
		$sql .= ' WHERE cs.entity IN ('.getEntity('tax').')';
		if ($ref) {
			$sql .= " AND cs.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= " AND cs.rowid = ".((int) $id);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref					= $obj->rowid;
				$this->date_ech = $this->db->jdate($obj->date_ech);
				$this->lib					= $obj->label;
				$this->label				= $obj->label;
				$this->type					= $obj->fk_type;
				$this->type_label			= $obj->type_label;
				$this->type_code			= $obj->type_code;
				$this->type_accountancy_code = $obj->type_accountancy_code;
				$this->fk_account			= $obj->fk_account;
				$this->mode_reglement_id = $obj->fk_mode_reglement;
				$this->mode_reglement_code = $obj->mode_reglement_code;
				$this->mode_reglement = $obj->mode_reglement_libelle;
				$this->amount = $obj->amount;
				$this->fk_project = $obj->fk_project;
				$this->fk_user = $obj->fk_user;
				$this->note_public = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->paye = $obj->paye;
				$this->periode = $this->db->jdate($obj->period);
				$this->period = $this->db->jdate($obj->period);
				$this->import_key = $obj->import_key;

				$this->db->free($resql);

				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Check if a social contribution can be created into database
	 *
	 * @return	boolean		True or false
	 */
	public function check()
	{
		$newamount = price2num($this->amount, 'MT');

		// Validation of parameters
		if ($newamount == 0 || empty($this->date_ech) || (empty($this->period) && empty($this->periode))) {
			return false;
		}

		return true;
	}

	/**
	 *      Create a social contribution into database
	 *
	 *      @param	User	$user   User making creation
	 *      @return int     		Return integer <0 if KO, id if OK
	 */
	public function create($user)
	{
		global $conf;
		$error = 0;

		$now = dol_now();

		// Nettoyage parameters
		$newamount = price2num($this->amount, 'MT');

		if (!$this->check()) {
			$this->error = "ErrorBadParameter";
			return -2;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, fk_account, fk_mode_reglement, libelle, date_ech, periode, amount, fk_projet, entity, fk_user_author, fk_user, date_creation)";
		$sql .= " VALUES (".((int) $this->type);
		$sql .= ", ".($this->fk_account > 0 ? ((int) $this->fk_account) : 'NULL');
		$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : "NULL");
		$sql .= ", '".$this->db->escape($this->label ? $this->label : $this->lib)."'";
		$sql .= ", '".$this->db->idate($this->date_ech)."'";
		$sql .= ", '".$this->db->idate($this->period)."'";
		$sql .= ", '".price2num($newamount)."'";
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : 'NULL');
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".($this->fk_user > 0 ? ((int) $this->fk_user) : 'NULL');
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."chargesociales");

			//dol_syslog("ChargesSociales::create this->id=".$this->id);
			$result = $this->call_trigger('SOCIALCONTRIBUTION_CREATE', $user);
			if ($result < 0) {
				$error++;
			}

			if (empty($error)) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      Delete a social contribution
	 *
	 *      @param		User    $user   Object user making delete
	 *      @return     		int 	Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$error = 0;

		$this->db->begin();

		// Get bank transaction lines for this social contributions
		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$account = new Account($this->db);
		$lines_url = $account->get_url(0, $this->id, 'sc');

		// Delete bank urls
		foreach ($lines_url as $line_url) {
			if (!$error) {
				$accountline = new AccountLine($this->db);
				$accountline->fetch($line_url['fk_bank']);
				$result = $accountline->delete_urls($user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		// Delete payments
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."paiementcharge WHERE fk_charge=".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales WHERE rowid=".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      Update social or fiscal contribution
	 *
	 *      @param	User	$user           User that modify
	 *      @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return int     		        Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales";
		$sql .= " SET libelle = '".$this->db->escape($this->label ? $this->label : $this->lib)."'";
		$sql .= ", date_ech = '".$this->db->idate($this->date_ech)."'";
		$sql .= ", periode = '".$this->db->idate($this->period ? $this->period : $this->periode)."'";
		$sql .= ", amount = ".((float) price2num($this->amount, 'MT'));
		$sql .= ", fk_projet=".($this->fk_project > 0 ? ((int) $this->fk_project) : "NULL");
		$sql .= ", fk_user=".($this->fk_user > 0 ? ((int) $this->fk_user) : "NULL");
		$sql .= ", fk_user_modif=".((int) $user->id);
		if ($this->type > 0) {
			$sql .= ", fk_type = ".((int) $this->type);
		}
		$sql .= ", fk_user_modif=".((int) $user->id);
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('SOCIALCONTRIBUTION_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Calculate amount remaining to pay by year
	 *
	 * @param   int			$year       Year
	 * @return  int|float 	  	        Returns -1 when error (Note: could be mistaken with an amount)
	 */
	public function solde($year = 0)
	{
		global $conf;

		$sql = "SELECT SUM(f.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as f";
		$sql .= " WHERE f.entity = ".((int) $conf->entity);
		$sql .= " AND paye = 0";

		if ($year) {	// TODO Fix to use date function
			$sql .= " AND f.datev >= '".((int) $year)."-01-01' AND f.datev <= '".((int) $year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->db->free($result);
				return $obj->amount;
			} else {
				return 0;
			}
		} else {
			print $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag social contribution as paid completely
	 *
	 *	@deprecated
	 *  @see setPaid()
	 *  @param    User    $user       Object user making change
	 *  @return   int					Return integer <0 if KO, >0 if OK
	 */
	public function set_paid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_paid is deprecated, use setPaid instead", LOG_NOTICE);
		return $this->setPaid($user);
	}

	/**
	 *    Tag social contribution as paid completely
	 *
	 *    @param    User    $user       Object user making change
	 *    @return   int					Return integer <0 if KO, >0 if OK
	 */
	public function setPaid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales SET";
		$sql .= " paye = 1";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$return = $this->db->query($sql);

		if ($return) {
			$this->paye = 1;

			return 1;
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Remove tag paid on social contribution
	 *
	 *	@deprecated
	 *  @see setUnpaid()
	 *  @param	User	$user       Object user making change
	 *  @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_unpaid is deprecated, use setUnpaid instead", LOG_NOTICE);
		return $this->setUnpaid($user);
	}

	/**
	 *    Remove tag paid on social contribution
	 *
	 *    @param	User	$user       Object user making change
	 *    @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setUnpaid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales SET";
		$sql .= " paye = 0";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$return = $this->db->query($sql);

		if ($return) {
			$this->paye = 0;

			return 1;
		} else {
			return -1;
		}
	}

	/**
	 *  Retourne le libelle du statut d'une charge (impaye, payee)
	 *
	 *  @param	int<0,6>	$mode       	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  float		$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 *  @return	string        			Label
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $mode, $alreadypaid);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  float	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 *  @return string        			Label
	 */
	public function LibStatut($status, $mode = 0, $alreadypaid = -1)
	{
		// phpcs:enable
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("customers", "bills"));

		// We reinit status array to force to redefine them because label may change according to properties values.
		$this->labelStatus = array();
		$this->labelStatusShort = array();

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('Unpaid');
			$this->labelStatus[self::STATUS_PAID] = $langs->transnoentitiesnoconv('Paid');
			if ($status == self::STATUS_UNPAID && $alreadypaid > 0) {
				$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
			$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('Unpaid');
			$this->labelStatusShort[self::STATUS_PAID] = $langs->transnoentitiesnoconv('Paid');
			if ($status == self::STATUS_UNPAID && $alreadypaid > 0) {
				$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
		}

		$statusType = 'status1';
		if ($status == 0 && $alreadypaid > 0) {
			$statusType = 'status3';
		}
		if ($status == 1) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  int		$short           			1=Return just URL
	 *  @param  int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  int     $addlinktonotes  			1=Add link to notes
	 *	@return	string								String with link
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $short = 0, $save_lastsearch_value = -1, $addlinktonotes = 0)
	{
		global $langs, $conf, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$url = DOL_URL_ROOT.'/compta/sociales/card.php?id='.$this->id;

		if ($short) {
			return $url;
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		if (empty($this->ref)) {
			$this->ref = $this->label;
		}

		$label = img_picto('', $this->picto, 'class="pictofixedwidth"').'<u class="paddingrightonly">'.$langs->trans("SocialContribution").'</u>';
		if (isset($this->paye)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		if (!empty($this->ref)) {
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->label)) {
			$label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		}
		if (!empty($this->type_label)) {
			$label .= '<br><b>'.$langs->trans('Type').':</b> '.$this->type_label;
			if (isModEnabled('accounting') || !empty($this->type_accountancy_code)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
				$label .= ' <span class="opacitymedium">('.$langs->trans('AccountancyCode').': '.(empty($this->type_accountancy_code) ? $langs->trans("Unknown") : length_accountg($this->type_accountancy_code)).')</span>';
			}
		}

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight("facture", "read")) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("SocialContribution");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ' title="'.dolPrintHTMLForAttribute($label).'"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		if ($addlinktonotes) {
			$txttoshow = ($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow) {
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.$txttoshow;
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/compta/sociales/note.php?id='.$this->id.'" class="classfortooltip" title="'.dolPrintHTMLForAttribute($notetoshow).'">';
				$result .= img_picto('', 'note');
				$result .= '</a>';
				$result .= '</span>';
			}
		}

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *	@return		int		Amount of payment already done, <0 if KO
	 */
	public function getSommePaiement()
	{
		$table = 'paiementcharge';
		$field = 'fk_charge';

		$sql = 'SELECT sum(amount) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$amount = 0;

			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$amount = $obj->amount ? $obj->amount : 0;
			}

			$this->db->free($resql);
			return $amount;
		} else {
			return -1;
		}
	}

	/**
	 * 	Charge l'information d'ordre info dans l'objet entrepot
	 *
	 *  @param	int		$id     Id of social contribution
	 *  @return	int				Return integer <0 if KO, >0 if OK
	 */
	public function info($id)
	{
		$sql = "SELECT e.rowid, e.tms as datem, e.date_creation as datec, e.date_valid as datev, e.import_key,";
		$sql .= " e.fk_user_author, e.fk_user_modif, e.fk_user_valid";
		$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as e";
		$sql .= " WHERE e.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->import_key        = $obj->import_key;
			}

			$this->db->free($result);
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int
	 */
	public function initAsSpecimen()
	{
		// Initialize parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->paye = 0;
		$this->date_creation = dol_now();
		$this->date_ech = $this->date_creation + 3600 * 24 * 30;
		$this->periode = $this->date_creation + 3600 * 24 * 30;
		$this->period = $this->date_creation + 3600 * 24 * 30;
		$this->amount = 100;
		$this->label = 'Social contribution label';
		$this->type = 1;
		$this->type_label = 'Type of social contribution';

		return 1;
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		?array<string,mixed>	$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(0) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' &nbsp; <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (!empty($arraydata['project']) && $arraydata['project'] instanceof Project && $arraydata['project']->id > 0) {
			$return .= '<br><span class="info-box-label">'.$arraydata['project']->getNomUrl(1).'</span>';
		}
		if (property_exists($this, 'date_ech')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Date").'</span> : <span class="info-box-label">'.dol_print_date($this->date_ech, 'day').'</span>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br>';
			$return .= '<span class="info-box-label amount">'.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'LibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3, $this->alreadypaid).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
