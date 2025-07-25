<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/commande/class/commandestats.class.php
 *  \ingroup    orders
 *  \brief      File of class to manage order statistics
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *    Class to manage order statistics (customer and supplier)
 */
class CommandeStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var int ID
	 */
	public $socid;

	/**
	 * @var int ID
	 */
	public $userid;

	/**
	 * @var string	To store the FROM part of the main table of the SQL request
	 */
	public $from;

	/**
	 * @var string	To store the FROM part of the lines table of the SQL request
	 */
	public $from_line;

	/**
	 * @var string	To store the field
	 */
	public $field;

	/**
	 * @var string	To store the field of the line table of the SQL request
	 */
	public $field_line;

	/**
	 * @var string	To store the FROM part of the categorie table of the SQL request
	 */
	public $categ_link;

	/**
	 * @var string	To store the WHERE part of the main table of the SQL request
	 */
	public $where = '';

	/**
	 * @var string	To store the join
	 */
	public $join;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		    Database handler
	 * @param 	int		$socid	    Id third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * @param 	string	$mode	    Option ('customer', 'supplier')
	 * @param   int		$userid     Id user for filter (creation user)
	 * @param	int		$typentid   Id typent of thirdpary for filter
	 * @param	int		$categid    Id category of thirdpary for filter
	 */
	public function __construct($db, $socid, $mode, $userid = 0, $typentid = 0, $categid = 0)
	{
		$this->db = $db;

		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->cachefilesuffix = $mode;
		$this->join = '';

		if ($mode == 'customer') {
			$object = new Commande($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field = 'total_ht';
			$this->field_line = 'total_ht';
			//$this->where .= " c.fk_statut > 0"; // Not draft and not cancelled
			$this->categ_link = MAIN_DB_PREFIX.'categorie_societe';
		} elseif ($mode == 'supplier') {
			$object = new CommandeFournisseur($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field = 'total_ht';
			$this->field_line = 'total_ht';
			//$this->where .= " c.fk_statut > 2"; // Only approved & ordered
			$this->categ_link = MAIN_DB_PREFIX.'categorie_fournisseur';
		}
		//$this->where.= " AND c.fk_soc = s.rowid AND c.entity = ".$conf->entity;
		$this->where .= ($this->where ? ' AND ' : '').'c.entity IN ('.getEntity('commande').')';

		if ($this->socid) {
			$this->where .= " AND c.fk_soc = ".((int) $this->socid);
		}
		if ($this->userid > 0) {
			$this->where .= ' AND c.fk_user_author = '.((int) $this->userid);
		}

		if ($typentid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = c.fk_soc';
			$this->where .= ' AND s.fk_typent = '.((int) $typentid);
		}

		if ($categid) {
			$this->where .= ' AND EXISTS (SELECT rowid FROM '.$this->categ_link.' as cats WHERE cats.fk_soc = c.fk_soc AND cats.fk_categorie = '.((int) $categid).')';
		}
	}

	/**
	 * Return orders number by month for a year
	 *
	 * @param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return orders number per year
	 *
	 * @return	array<array{0:int,1:int}>				Array of nb each year
	 *
	 */
	public function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%Y') as dm, COUNT(*) as nb, SUM(c.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the orders amount by month for a year
	 *
	 * @param	int		$year		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array with amount by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, SUM(c.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return the orders amount average by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}> 	Array with number by month
	 */
	public function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, AVG(c.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *  @return array<array{year:string,nb:string,nb_diff:float,total?:float,avg?:float,weighted?:float,total_diff?:float,avg_diff?:float,avg_weighted?:float}>    Array of values
	 */
	public function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%Y') as year, COUNT(*) as nb, SUM(c.".$this->field.") as total, AVG(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}

	/**
	 *	Return nb, amount of predefined product for year
	 *
	 *	@param	int		$year			Year to scan
	 *  @param  int     $limit      	Limit
	 *	@return	array<int<0,11>,array{0:int<1,12>,1:int|float}>		Array of values
	 */
	public function getAllByProduct($year, $limit = 10)
	{
		global $user;

		$sql = "SELECT product.ref, COUNT(product.ref) as nb, SUM(tl.".$this->field_line.") as total, AVG(tl.".$this->field_line.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " INNER JOIN ".$this->from_line." ON c.rowid = tl.fk_commande";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product as product ON tl.fk_product = product.rowid";
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= "  INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " AND c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."' AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		$sql .= " GROUP BY product.ref";
		$sql .= $this->db->order('nb', 'DESC');
		//$sql.= $this->db->plimit(20);

		return $this->_getAllByProduct($sql, $limit);
	}
}
