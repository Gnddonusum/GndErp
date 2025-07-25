<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2017  Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2015       Claudio Aschieri        <c.aschieri@19.coop>
 * Copyright (C) 2016-2024	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Lenin Rivas         	<lenin@leninrivas.com>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expedition.class.php
 *  \ingroup    expedition
 *  \brief      File of class managing the shipments
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionlinebatch.class.php';

/**
 * Class to manage lines of shipment
 */
class ExpeditionLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expeditiondet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expeditiondet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'expedition';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_expedition';

	/**
	 * Id of the line. Duplicate of $id.
	 *
	 * @var int
	 * @deprecated
	 */
	public $line_id;	// deprecated

	/**
	 * @var int ID	Duplicate of origin_id (using origin_id is better)
	 */
	public $fk_element;

	/**
	 * @var int ID	Duplicate of fk_element
	 */
	public $origin_id;

	/**
	 * @var int ID	Duplicate of origin_line_id
	 */
	public $fk_elementdet;

	/**
	 * @var int ID	Duplicate of fk_elementdet
	 */
	public $origin_line_id;

	/**
	 * @var int Id of parent line for children of virtual product
	 */
	public $fk_parent;

	/**
	 * @var string		Type of object the fk_element refers to. Example: 'order'.
	 */
	public $element_type;


	/**
	 * Code of object line that is origin of the shipment line.
	 *
	 * @var string
	 * @deprecated	Use instead origin_type = element_type to guess the line of origin of the shipment line.
	 */
	public $fk_origin;			// Example: 'orderline'

	/**
	 * @var int Id of shipment
	 */
	public $fk_expedition;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var float qty asked From llx_expeditiondet
	 */
	public $qty;

	/**
	 * @var float qty shipped
	 */
	public $qty_shipped;

	/**
	 * @var int Id of product
	 */
	public $fk_product;

	/**
	 * Detail of lot and qty = array(id in llx_expeditiondet_batch, fk_expeditiondet, batch, qty, fk_origin_stock)
	 * We can use this to know warehouse planned to be used for each lot.
	 * @var stdClass|ExpeditionLineBatch[]
	 */
	public $detail_batch;

	/**
	 * Virtual products : array of total of quantities group product id and warehouse id ([id_product][id_warehouse] -> qty (int|float))
	 * @var array<int, array<int, int|float>>
	 */
	public $detail_children;

	/** detail of warehouses and qty
	 * We can use this to know warehouse when there is no lot.
	 * @var stdClass[]
	 */
	public $details_entrepot;


	/**
	 * @var int Id of warehouse
	 */
	public $entrepot_id;


	/**
	 * @var float qty asked From llx_commandedet or llx_propaldet
	 */
	public $qty_asked;

	/**
	 * @var string
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * @var string product ref
	 */
	public $product_ref;

	/**
	 * @var string
	 * @deprecated
	 * @see $product_label
	 */
	public $libelle;

	/**
	 * @var string product label
	 */
	public $product_label;

	/**
	 * @var string product description
	 * @deprecated
	 * @see $product_desc
	 */
	public $desc;

	/**
	 * @var string product description
	 */
	public $product_desc;

	/**
	 * Type of the product. 0 for product, 1 for service
	 * @var int
	 */
	public $product_type = 0;

	/**
	 * @var int rang of line
	 */
	public $rang;

	/**
	 * @var float weight
	 */
	public $weight;

	/**
	 * @var string|int
	 */
	public $weight_units;

	/**
	 * @var float length
	 */
	public $length;

	/**
	 * @var string|int
	 */
	public $length_units;

	/**
	 * @var float width
	 */
	public $width;

	/**
	 * @var int
	 */
	public $width_units;

	/**
	 * @var float height
	 */
	public $height;

	/**
	 * @var int
	 */
	public $height_units;

	/**
	 * @var float surface
	 */
	public $surface;

	/**
	 * @var string|int
	 */
	public $surface_units;

	/**
	 * @var float volume
	 */
	public $volume;

	/**
	 * @var string|int
	 */
	public $volume_units;

	/**
	 * 0=This service or product is not managed in stock, 1=This service or product is managed in stock
	 *
	 * @var int
	 */
	public $stockable_product = 1;

	/**
	 * @var float|string
	 */
	public $remise_percent;

	/**
	 * @var float|string
	 */
	public $tva_tx;

	/**
	 * @var float total without tax
	 */
	public $total_ht;

	/**
	 * @var float total with tax
	 */
	public $total_ttc;

	/**
	 * @var float total vat
	 */
	public $total_tva;

	/**
	 * @var float total localtax 1
	 */
	public $total_localtax1;

	/**
	 * @var float total localtax 2
	 */
	public $total_localtax2;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load line expedition
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT ed.rowid, ed.fk_expedition, ed.fk_entrepot, ed.fk_elementdet, ed.element_type, ed.qty, ed.rang, ed.extraparams';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as ed';
		$sql .= ' WHERE ed.rowid = '.((int) $rowid);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);
			$this->id = $objp->rowid;
			$this->fk_expedition = $objp->fk_expedition;
			$this->entrepot_id = $objp->fk_entrepot;
			$this->fk_elementdet = $objp->fk_elementdet;
			$this->element_type = $objp->element_type;
			$this->qty = $objp->qty;
			$this->rang = $objp->rang;

			$this->extraparams = !empty($objp->extraparams) ? (array) json_decode($objp->extraparams, true) : array();

			$this->db->free($result);

			return 1;
		} else {
			$this->errors[] = $this->db->lasterror();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      User	$user			User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return     int						Return integer <0 if KO, line id >0 if OK
	 */
	public function insert($user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		$skip_check_parameters = false;

		// Handling parent line subtotal line
		if (!empty($this->element_type)
			&& !empty($this->fk_elementdet)
			&& $this->element_type == 'commande') {
			$objectsrc_line = new OrderLine($this->db);
			$objectsrc_line->fetch($this->fk_elementdet);
			$skip_check_parameters = $objectsrc_line->special_code == SUBTOTALS_SPECIAL_CODE;
		}

		// Check parameters
		if ((empty($this->fk_expedition)
			|| empty($this->fk_product) // product id is mandatory
			|| (empty($this->fk_elementdet) && empty($this->fk_parent)) // at least origin line id of parent line id is set
			|| !is_numeric($this->qty))
			&& !$skip_check_parameters) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorMandatoryParametersNotProvided');
			return -1;
		}

		$this->db->begin();

		if (empty($this->rang)) {
			$this->rang = 0;
		}

		// Rank to use
		$ranktouse = $this->rang;
		if ($ranktouse == -1) {
			$rangmax = $this->line_max($this->fk_expedition);
			$ranktouse = $rangmax + 1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet (";
		$sql .= "fk_expedition";
		$sql .= ", fk_entrepot";
		$sql .= ", fk_elementdet";
		$sql .= ", fk_parent";
		$sql .= ", fk_product";
		$sql .= ", element_type";
		$sql .= ", qty";
		$sql .= ", rang";
		$sql .= ") VALUES (";
		$sql .= $this->fk_expedition;
		$sql .= ", ".(empty($this->entrepot_id) ? 'NULL' : $this->entrepot_id);
		$sql .= ", ".(empty($this->fk_elementdet) ? 'NULL' : $this->fk_elementdet);
		$sql .= ", ".(empty($this->fk_parent) ? 'NULL' : $this->fk_parent);
		$sql .= ", ".(empty($this->fk_product) ? 'NULL' : $this->fk_product);
		$sql .= ", '".(empty($this->element_type) ? 'order' : $this->db->escape($this->element_type))."'";
		$sql .= ", ".price2num($this->qty, 'MS');
		$sql .= ", ".((int) $ranktouse);
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expeditiondet");

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESHIPPING_INSERT', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
			}
		} else {
			$error++;
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Find all children
	 *
	 * @param	int			$line_id	Line id
	 * @param	stdClass[]	$list		List of sub-lines for a virtual product line (array of object with attributes : rowid, fk_product, fk_parent, qty, fk_warehouse, batch, eatby, sellby, iskit, incdec)
	 * @param	int			$mode		[=0] array of lines ids, 1 array of line object for dispatcher
	 * @return	int 	Return integer <0 if KO else >0 if OK
	 */
	public function findAllChild($line_id, &$list = array(), $mode = 0)
	{
		if ($line_id > 0) {
			// find all child
			$sql  = "SELECT ed.rowid as child_line_id";
			if ($mode == 1) {
				$sql .= ", ed.fk_product";
				$sql .= ", ed.fk_parent";
				$sql .= ", " . $this->db->ifsql('eb.rowid IS NULL', 'ed.qty', 'eb.qty') . " as qty";
				$sql .= ", " . $this->db->ifsql('eb.rowid IS NULL', 'ed.fk_entrepot', 'eb.fk_warehouse') . " as fk_warehouse";
				$sql .= ", eb.batch, eb.eatby, eb.sellby";
			}
			$sql .= " FROM " . $this->db->prefix() . $this->table_element . " as ed";
			$sql .= " LEFT JOIN " . $this->db->prefix() . "expeditiondet_batch as eb ON eb.fk_expeditiondet = " . ((int) $line_id);
			$sql .= " WHERE ed.fk_parent = " . ((int) $line_id);
			$sql .= $this->db->order('ed.fk_product,ed.rowid', 'ASC,ASC');

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$child_line_id = (int) $obj->child_line_id;
					if (!isset($list[$line_id])) {
						$list[$line_id] = array();
					}

					if ($mode == 0) {
						$list[$line_id][] = $child_line_id;
					} elseif ($mode == 1) {
						$line_obj = new stdClass();
						$line_obj->rowid = $child_line_id;
						$line_obj->fk_product = $obj->fk_product;
						$line_obj->fk_parent = $obj->fk_parent;
						$line_obj->qty = $obj->qty;
						$line_obj->fk_warehouse = $obj->fk_warehouse;
						$line_obj->batch = $obj->batch;
						$line_obj->eatby = $obj->eatby;
						$line_obj->sellby = $obj->sellby;
						$line_obj->iskit = 0;
						$line_obj->incdec = 0;
						$list[$line_id][] = $line_obj;
					}

					$this->findAllChild($child_line_id, $list, $mode);
				}
				$this->db->free($resql);
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			}
		}

		return 1;
	}

	/**
	 * 	Delete shipment line.
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		0=launch triggers after, 1=disable triggers
	 * 	@return		int		>0 if OK, <0 if KO
	 */
	public function delete($user = null, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		// virtual products : delete all children and batch
		if (getDolGlobalInt('PRODUIT_SOUSPRODUITS') && !($this->fk_parent > 0)) {
			// find all children
			$line_id_list = array();
			$result = $this->findAllChild($this->id, $line_id_list);
			if ($result) {
				$child_line_id_list = array_reverse($line_id_list, true);
				foreach ($child_line_id_list as $child_line_id_arr) {
					foreach ($child_line_id_arr as $child_line_id) {
						// delete batch expedition line
						if (isModEnabled('productbatch')) {
							$sql = "DELETE FROM " . $this->db->prefix() . "expeditiondet_batch";
							$sql .= " WHERE fk_expeditiondet = " . ((int) $child_line_id);
							if (!$this->db->query($sql)) {
								$error++;
								$this->errors[] = $this->db->lasterror() . " - sql=$sql";
							}
						}

						$sql = "DELETE FROM " . $this->db->prefix() . "expeditiondet";
						$sql .= " WHERE rowid = " . ((int) $child_line_id);
						if (!$this->db->query($sql)) {
							$error++;
							$this->errors[] = $this->db->lasterror() . " - sql=$sql";
						}

						if ($error) {
							break;
						}
					}
					if ($error) {
						break;
					}
				}
			} else {
				$error++;
			}
		}

		if (!$error) {
			// delete batch expedition line
			if (isModEnabled('productbatch')) {
				$sql = "DELETE FROM ".$this->db->prefix()."expeditiondet_batch";
				$sql .= " WHERE fk_expeditiondet = ".((int) $this->id);

				if (!$this->db->query($sql)) {
					$this->errors[] = $this->db->lasterror()." - sql=$sql";
					$error++;
				}
			}

			$sql = "DELETE FROM ".$this->db->prefix()."expeditiondet";
			$sql .= " WHERE rowid = ".((int) $this->id);

			if (!$error && $this->db->query($sql)) {
				// Remove extrafields
				if (!$error) {
					$result = $this->deleteExtraFields();
					if ($result < 0) {
						$this->errors[] = $this->error;
						$error++;
					}
				}
				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINESHIPPING_DELETE', $user);
					if ($result < 0) {
						$this->errors[] = $this->error;
						$error++;
					}
					// End call triggers
				}
			} else {
				$this->errors[] = $this->db->lasterror()." - sql=$sql";
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 *  Update a line in database
	 *
	 *	@param		User	$user			User that modify
	 *	@param		int		$notrigger		1 = disable triggers
	 *  @return		int					Return integer < 0 if KO, > 0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		dol_syslog(get_class($this)."::update id=$this->id, entrepot_id=$this->entrepot_id, product_id=$this->fk_product, qty=$this->qty");

		$this->db->begin();

		// Clean parameters
		if (empty($this->qty)) {
			$this->qty = 0;
		}
		$qty = price2num($this->qty);
		$remainingQty = 0;
		$batch = null;
		$batch_id = 0;
		$expedition_batch_id = 0;
		if (is_array($this->detail_batch)) { 	// array of ExpeditionLineBatch
			if (count($this->detail_batch) > 1) {
				dol_syslog(get_class($this).'::update only possible for one batch', LOG_ERR);
				$this->errors[] = 'ErrorBadParameters';
				$error++;
			} else {
				$batch = $this->detail_batch[0]->batch;
				$batch_id = $this->detail_batch[0]->fk_origin_stock;
				$expedition_batch_id = $this->detail_batch[0]->id;
				if ($this->entrepot_id != $this->detail_batch[0]->entrepot_id) {
					dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
					$this->errors[] = 'ErrorBadParameters';
					$error++;
				}
				$qty = price2num($this->detail_batch[0]->qty);
			}
		} elseif (!empty($this->detail_batch)) {
			$batch = $this->detail_batch->batch;
			$batch_id = $this->detail_batch->fk_origin_stock;
			$expedition_batch_id = $this->detail_batch->id;
			if ($this->entrepot_id != $this->detail_batch->entrepot_id) {
				dol_syslog(get_class($this).'::update only possible for batch of same warehouse', LOG_ERR);
				$this->errors[] = 'ErrorBadParameters';
				$error++;
			}
			$qty = price2num($this->detail_batch->qty);
		}

		// check parameters
		if (!isset($this->id) || !isset($this->entrepot_id)) {
			dol_syslog(get_class($this).'::update missing line id and/or warehouse id', LOG_ERR);
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorMandatoryParametersNotProvided');
			$error++;
			return -1;
		}

		// update lot

		if (!empty($batch) && isModEnabled('productbatch')) {
			$batch_id_str = $batch_id ?? 'null';
			dol_syslog(get_class($this)."::update expedition batch id=$expedition_batch_id, batch_id=$batch_id_str, batch=$batch");

			if (empty($batch_id) || empty($this->fk_product)) {
				dol_syslog(get_class($this).'::update missing fk_origin_stock (batch_id) and/or fk_product', LOG_ERR);
				$langs->load('errors');
				$this->errors[] = $langs->trans('ErrorMandatoryParametersNotProvided');
				$error++;
			}

			// fetch remaining lot qty
			$shipmentlinebatch = new ExpeditionLineBatch($this->db);
			$lotArray = $shipmentlinebatch->fetchAll($this->id);
			if (!$error && $lotArray < 0) {
				$this->errors[] = $this->db->lasterror()." - ExpeditionLineBatch::fetchAll";
				$error++;
			} else {
				// calculate new total line qty
				foreach ($lotArray as $lot) {
					if ($expedition_batch_id != $lot->id) {
						$remainingQty += $lot->qty;
					}
				}
				$qty += $remainingQty;

				//fetch lot details

				// fetch from product_lot
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
				$lot = new Productlot($this->db);
				if ($lot->fetch(0, $this->fk_product, $batch) < 0) {
					$this->errors = array_merge($this->errors, $lot->errors);
					$error++;
				}
				if (!$error && !empty($expedition_batch_id)) {
					// delete lot expedition line
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_batch";
					$sql .= " WHERE fk_expeditiondet = ".((int) $this->id);
					$sql .= " AND rowid = ".((int) $expedition_batch_id);

					if (!$this->db->query($sql)) {
						$this->errors[] = $this->db->lasterror()." - sql=$sql";
						$error++;
					}
				}
				if (!$error && $this->detail_batch->qty > 0) {
					// create lot expedition line
					if (isset($lot->id)) {
						$shipmentLot = new ExpeditionLineBatch($this->db);
						$shipmentLot->batch = $lot->batch;
						$shipmentLot->eatby = $lot->eatby;
						$shipmentLot->sellby = $lot->sellby;
						$shipmentLot->fk_warehouse = $this->detail_batch->entrepot_id;
						$shipmentLot->qty = $this->detail_batch->qty;
						$shipmentLot->fk_origin_stock = (int) $batch_id;
						if ($shipmentLot->create($this->id) < 0) {
							$this->errors = $shipmentLot->errors;
							$error++;
						}
					}
				}
			}
		}
		if (!$error) {
			// update line
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
			$sql .= " fk_entrepot = ".($this->entrepot_id > 0 ? $this->entrepot_id : 'null');
			$sql .= " , qty = ".((float) price2num($qty, 'MS'));
			$sql .= " WHERE rowid = ".((int) $this->id);

			if (!$this->db->query($sql)) {
				$this->errors[] = $this->db->lasterror()." - sql=$sql";
				$error++;
			}
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$this->errors[] = $this->error;
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINESHIPPING_MODIFY', $user);
			if ($result < 0) {
				$this->errors[] = $this->error;
				$error++;
			}
			// End call triggers
		}
		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}
}
