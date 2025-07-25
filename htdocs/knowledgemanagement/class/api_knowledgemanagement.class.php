<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2021 	SuperAdmin 				<test@dolibarr.com>
 * Copyright (C) 2025 	Charlene Benke 			<charlent@patas-monkey.com>
 * Copyright (C) 2025	MDW						<mdeweerd@users.noreply.github.com>
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

dol_include_once('/knowledgemanagement/class/knowledgerecord.class.php');
dol_include_once('/categories/class/categorie.class.php');



/**
 * \file    knowledgemanagement/class/api_knowledgemanagement.class.php
 * \ingroup knowledgemanagement
 * \brief   File for API management of knowledgerecord.
 */

/**
 * API class for knowledgemanagement knowledgerecord
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class KnowledgeManagement extends DolibarrApi
{
	/**
	 * @var KnowledgeRecord {@type KnowledgeRecord}
	 */
	public $knowledgerecord;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->knowledgerecord = new KnowledgeRecord($this->db);
	}

	/**
	 * Get properties of a knowledgerecord object
	 *
	 * Return an array with knowledgerecord information
	 *
	 * @param	int		$id				ID of knowledgerecord
	 * @return  Object					Object with cleaned properties
	 *
	 * @url	GET knowledgerecords/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
			throw new RestException(403);
		}

		$result = $this->knowledgerecord->fetch($id);
		if (!$result) {
			throw new RestException(404, 'KnowledgeRecord not found');
		}

		if (!DolibarrApi::_checkAccessToResource('knowledgerecord', $this->knowledgerecord->id, 'knowledgemanagement_knowledgerecord')) {
			throw new RestException(403, 'Access to instance id='.$this->knowledgerecord->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->knowledgerecord);
	}

	/**
	 * Get categories for a knowledgerecord object
	 *
	 * @param int    $id        ID of knowledgerecord object
	 * @param string $sortfield Sort field
	 * @param string $sortorder Sort order
	 * @param int    $limit     Limit for list
	 * @param int    $page      Page number
	 *
	 * @return mixed
	 *
	 * @url GET /knowledgerecords/{id}/categories
	 */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'knowledgemanagement', $sortfield, $sortorder, $limit, $page);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.implode(',', array_merge(array($categories->error), $categories->errors)));
		}

		return $result;
	}

	/**
	 * List knowledgerecords
	 *
	 * Get a list of knowledgerecords
	 *
	 * @param string			$sortfield			Sort field
	 * @param string			$sortorder			Sort order
	 * @param int				$limit				Limit for list
	 * @param int				$page				Page number
	 * @param int				$category			Use this param to filter list by category
	 * @param string			$sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string			$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                               Array of order objects
	 * @phan-return KnowledgeRecord[]|array{data:KnowledgeRecord[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return KnowledgeRecord[]|array{data:KnowledgeRecord[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException
	 *
	 * @url	GET /knowledgerecords/
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $category = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();
		$tmpobject = new KnowledgeRecord($this->db);

		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ?: 0;

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		if ($category > 0) {
			$sql .= ", ".$this->db->prefix()."categorie_knowledgemanagement as c";
		}
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
		// Select products of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category);
			$sql .= " AND c.fk_knowledgemanagement = t.rowid";
		}
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new KnowledgeRecord($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving knowledgerecord list: '.$this->db->lasterror());
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create knowledgerecord object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  ID of knowledgerecord
	 *
	 * @throws RestException
	 *
	 * @url	POST knowledgerecords/
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->knowledgerecord->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->knowledgerecord->$field = $this->_checkValForAPI($field, $value, $this->knowledgerecord);
		}

		// Clean data
		// $this->knowledgerecord->abc = sanitizeVal($this->knowledgerecord->abc, 'alphanohtml');

		if ($this->knowledgerecord->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating KnowledgeRecord", array_merge(array($this->knowledgerecord->error), $this->knowledgerecord->errors));
		}
		return $this->knowledgerecord->id;
	}

	/**
	 * Update knowledgerecord
	 *
	 * @param 	int   	$id             	Id of knowledgerecord to update
	 * @param 	array 	$request_data  		Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return 	Object						Updated object
	 *
	 * @throws RestException
	 *
	 * @url	PUT knowledgerecords/{id}
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'write')) {
			throw new RestException(403);
		}

		$result = $this->knowledgerecord->fetch($id);
		if (!$result) {
			throw new RestException(404, 'KnowledgeRecord not found');
		}

		if (!DolibarrApi::_checkAccessToResource('knowledgerecord', $this->knowledgerecord->id, 'knowledgemanagement_knowledgerecord')) {
			throw new RestException(403, 'Access to instance id='.$this->knowledgerecord->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->knowledgerecord->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->knowledgerecord->array_options[$index] = $this->_checkValForAPI($field, $val, $this->knowledgerecord);
				}
				continue;
			}

			$this->knowledgerecord->$field = $this->_checkValForAPI($field, $value, $this->knowledgerecord);
		}

		// Clean data
		// $this->knowledgerecord->abc = sanitizeVal($this->knowledgerecord->abc, 'alphanohtml');

		if ($this->knowledgerecord->update(DolibarrApiAccess::$user, 0) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->knowledgerecord->error);
		}
	}

	/**
	 * Delete knowledgerecord
	 *
	 * @param   int     $id   KnowledgeRecord ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 *
	 * @url	DELETE knowledgerecords/{id}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->knowledgerecord->fetch($id);
		if (!$result) {
			throw new RestException(404, 'KnowledgeRecord not found');
		}

		if (!DolibarrApi::_checkAccessToResource('knowledgerecord', $this->knowledgerecord->id, 'knowledgemanagement_knowledgerecord')) {
			throw new RestException(403, 'Access to instance id='.$this->knowledgerecord->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->knowledgerecord->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting KnowledgeRecord : '.$this->knowledgerecord->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'KnowledgeRecord deleted'
			)
		);
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

		/*unset($object->name);
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
		*/

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
	 * Validate a knowledge
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @param   int		$id             knowledge ID
	 * @param   int		$notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  Object
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'write')) {
			throw new RestException(403, "Insuffisant rights");
		}
		$result = $this->knowledgerecord->fetch($id);
		if (!$result) {
			throw new RestException(404, 'knowledgerecord not found');
		}


		$result = $this->knowledgerecord->validate(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating knowledgerecord: '.$this->knowledgerecord->error);
		}

		return $this->_cleanObjectDatas($this->knowledgerecord);
	}

	/**
	 * Cancel a knowledge
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @param   int		$id             knowledge ID
	 * @param   int		$notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/cancel
	 *
	 * @return  Object
	 */
	public function cancel($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'write')) {
			throw new RestException(403, "Insuffisant rights");
		}
		$result = $this->knowledgerecord->fetch($id);
		if (!$result) {
			throw new RestException(404, 'knowledgerecord not found');
		}


		$result = $this->knowledgerecord->cancel(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating knowledgerecord: '.$this->knowledgerecord->error);
		}

		return $this->_cleanObjectDatas($this->knowledgerecord);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array of data to validate
	 * @return array<string,string>
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$knowledgerecord = array();
		foreach ($this->knowledgerecord->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'ref', 'date_creation', 'tms', 'fk_user_creat')) || empty($propfield['notnull']) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$knowledgerecord[$field] = $data[$field];
		}
		return $knowledgerecord;
	}
}
