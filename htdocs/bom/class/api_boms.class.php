<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 Maxime Kohlhaas <maxime@atm-consulting.fr>
 * Copyright (C) 2020-2024  Frédéric France		<frederic.france@free.fr>
 * Copyright (C) 2022		Christian Humpel		<christian.humpel@live.com>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';


/**
 * \file    htdocs/bom/class/api_boms.class.php
 * \ingroup bom
 * \brief   File for API management of BOM.
 */

/**
 * API class for BOM
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Boms extends DolibarrApi
{
	/**
	 * @var BOM {@type BOM}
	 */
	public $bom;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->bom = new BOM($this->db);
	}

	/**
	 * Get properties of a bom object
	 *
	 * Return an array with bom information
	 *
	 * @param	int		$id				ID of bom
	 * @return  Object					Object with cleaned properties
	 * @phan-return BOM
	 * @phpstan-return BOM
	 *
	 * @url	GET {id}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'read')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom', $this->bom->id, 'bom_bom')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->bom);
	}


	/**
	 * List boms
	 *
	 * Get a list of boms
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of order objects
	 * @phan-return BOM[]
	 * @phpstan-return BOM[]
	 *
	 * @throws	RestException	400		Bad sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error retrieving list of boms
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();
		$tmpobject = new BOM($this->db);

		$socid = DolibarrApiAccess::$user->socid ?: '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= " WHERE 1 = 1";
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$bom_static = new BOM($this->db);
				if ($bom_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($bom_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve bom list');
		}

		return $obj_ret;
	}

	/**
	 * Create bom object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  				ID of bom
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	500		Error retrieving list of boms
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->bom->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->bom->$field = $this->_checkValForAPI($field, $value, $this->bom);
		}

		$this->checkRefNumbering();

		if (!$this->bom->create(DolibarrApiAccess::$user)) {
			throw new RestException(500, "Error creating BOM", array_merge(array($this->bom->error), $this->bom->errors));
		}
		return $this->bom->id;
	}

	/**
	 * Update bom
	 *
	 * @param 	int   		$id             Id of bom to update
	 * @param 	array 		$request_data   Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return 	Object						Object after update
	 * @phan-return BOM
	 * @phpstan-return BOM
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 * @throws	RestException	500		Error updating bom
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'write')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom', $this->bom->id, 'bom_bom')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->bom->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->bom->array_options[$index] = $this->_checkValForAPI($field, $val, $this->bom);
				}
				continue;
			}
			$this->bom->$field = $this->_checkValForAPI($field, $value, $this->bom);
		}

		$this->checkRefNumbering();

		if ($this->bom->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->bom->error);
		}
	}

	/**
	 * Delete bom
	 *
	 * @param   int     $id   BOM ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 * @throws	RestException	500		Error deleting bom
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom', $this->bom->id, 'bom_bom')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->bom->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting BOM : '.$this->bom->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'BOM deleted'
			)
		);
	}

	/**
	 * Get lines of an BOM
	 *
	 * @param int   $id             Id of BOM
	 *
	 * @url	GET {id}/lines
	 *
	 * @return array
	 * @phan-return BOMLine[]
	 * @phpstan-return BOMLine[]
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'read')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom_bom', $this->bom->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->bom->getLinesArray();
		$result = array();
		foreach ($this->bom->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Add a line to given BOM
	 *
	 * @param int   $id             Id of BOM to update
	 * @param array $request_data   BOMLine data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 * @throws	RestException	500		Error adding bom line
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'write')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom_bom', $this->bom->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$updateRes = $this->bom->addLine(
			$request_data->fk_product,
			$request_data->qty,
			$request_data->qty_frozen,
			$request_data->disable_stock_change,
			$request_data->efficiency,
			$request_data->position,
			$request_data->fk_bom_child,
			$request_data->import_key,
			$request_data->fk_unit,
			$request_data->array_options,
			$request_data->fk_default_workstation
		);

		if ($updateRes > 0) {
			return $updateRes;
		} else {
			throw new RestException(500, $this->bom->error);
		}
	}

	/**
	 * Update a line to given BOM
	 *
	 * @param int   $id             Id of BOM to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   BOMLine data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return object|bool
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'write')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom_bom', $this->bom->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$updateRes = $this->bom->updateLine(
			$lineid,
			$request_data->qty,
			$request_data->qty_frozen,
			$request_data->disable_stock_change,
			$request_data->efficiency,
			$request_data->position,
			$request_data->import_key,
			$request_data->fk_unit,
			$request_data->array_options,
			$request_data->fk_default_workstation
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
		return false;
	}

	/**
	 * Delete a line to given BOM
	 *
	 *
	 * @param int   $id             Id of BOM to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		BOM not found
	 * @throws	RestException	500		Error deleting bom line
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('bom', 'write')) {
			throw new RestException(403);
		}

		$result = $this->bom->fetch($id);
		if (!$result) {
			throw new RestException(404, 'BOM not found');
		}

		if (!DolibarrApi::_checkAccessToResource('bom_bom', $this->bom->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		//Check the rowid is a line of current bom object
		$lineIdIsFromObject = false;
		foreach ($this->bom->lines as $bl) {
			if ($bl->id == $lineid) {
				$lineIdIsFromObject = true;
				break;
			}
		}
		if (!$lineIdIsFromObject) {
			throw new RestException(500, 'Line to delete (rowid: '.$lineid.') is not a line of BOM (id: '.$this->bom->id.')');
		}

		$updateRes = $this->bom->deleteLine(DolibarrApiAccess::$user, $lineid);
		if ($updateRes > 0) {
			return array(
				'success' => array(
					'code' => 200,
					'message' => 'line ' .$lineid. ' deleted'
				)
			);
		} else {
			throw new RestException(500, $this->bom->error);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);


		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	?array<string,string>		$data   Array of data to validate
	 * @return	array<string,string>
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$myobject = array();
		foreach ($this->bom->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || empty($propfield['notnull']) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$myobject[$field] = $data[$field];
		}
		return $myobject;
	}

	/**
	 * Validate the ref field and get the next Number if it's necessary.
	 *
	 * @return void
	 */
	private function checkRefNumbering()
	{
		$ref = substr($this->bom->ref, 1, 4);
		if ($this->bom->status > 0 && $ref == 'PROV') {
			throw new RestException(400, "Wrong naming scheme '(PROV%)' is only allowed on 'DRAFT' status. For automatic increment use 'auto' on the 'ref' field.");
		}

		if (strtolower($this->bom->ref) == 'auto') {
			if (empty($this->bom->id) && $this->bom->status == 0) {
				$this->bom->ref = ''; // 'ref' will auto incremented with '(PROV' + newID + ')'
			} else {
				$this->bom->fetch_product();
				$numref = $this->bom->getNextNumRef($this->bom->product);
				$this->bom->ref = $numref;
			}
		}
	}
}
