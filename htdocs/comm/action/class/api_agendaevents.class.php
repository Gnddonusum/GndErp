<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025	William Mead			<william@m34d.com>
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

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';


/**
 * API class for Agenda Events
 *
 * @since	5.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class AgendaEvents extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'userownerid',
		'type_code'
	);

	/**
	 * @var ActionComm {@type ActionActionCom}
	 */
	public $actioncomm;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->actioncomm = new ActionComm($this->db);
	}

	/**
	 * Get agenda event
	 *
	 * Return an array with agenda event information
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int			$id			ID of Agenda Event to get
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'read')) {
			throw new RestException(403, "Insufficient rights to read an event");
		}
		if ($id === 0) {
			$result = $this->actioncomm->initAsSpecimen();
		} else {
			$result = $this->actioncomm->fetch($id);
			if ($result) {
				$this->actioncomm->fetch_optionals();
				$this->actioncomm->fetchObjectLinked();
			}
		}
		if (!$result) {
			throw new RestException(404, 'Agenda Events not found');
		}

		if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read') && $this->actioncomm->userownerid != DolibarrApiAccess::$user->id) {
			throw new RestException(403, 'Insufficient rights to read event of this owner id. Your id is '.DolibarrApiAccess::$user->id);
		}

		if (!DolibarrApi::_checkAccessToResource('agenda', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		return $this->_cleanObjectDatas($this->actioncomm);
	}

	/**
	 * List agenda events
	 *
	 * Get a list of agenda events
	 *
	 * @since	5.0.0	Initial implementation
	 * @since	21.0.0	Added data pagination
	 *
	 * @param	string	$sortfield			Sort field
	 * @param	string	$sortorder			Sort order
	 * @param	int		$limit				Limit for list
	 * @param	int		$page				Page number
	 * @param	string	$user_ids			User ids filter field (owners of event). Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param	string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'%dol%') and (t.datec:<:'20160101')"
	 * @param	string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool	$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	array						Array of order objects
	 * @phan-return ActionComm[]|array{data:ActionComm[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return ActionComm[]|array{data:ActionComm[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.id", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'read')) {
			throw new RestException(403, "Insufficient rights to read events");
		}

		// case of external user
		$socid = DolibarrApiAccess::$user->socid ?: 0;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}
		if (!isModEnabled('societe')) {
			$search_sale = 0; // If module thirdparty not enabled, sale representative is something that does not exists
		}

		$sql = "SELECT t.id";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields AS ef ON (ef.fk_object = t.id)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('agenda').')';
		if ($user_ids) {
			$sql .= " AND t.fk_user_action IN (".$this->db->sanitize($user_ids).")";
		}
		if ($socid > 0) {
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
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
		$sqlTotals = str_replace('SELECT t.id', 'SELECT count(t.id) as total', $sql);

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
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$actioncomm_static = new ActionComm($this->db);
				if ($actioncomm_static->fetch($obj->id)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($actioncomm_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve Agenda Event list : '.$this->db->lasterror());
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
				'page_count' => (int) ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create an agenda event
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of Agenda Event
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'create')) {
			throw new RestException(403, "Insufficient rights to create your Agenda Event");
		}
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'create') && DolibarrApiAccess::$user->id != $request_data['userownerid']) {
			throw new RestException(403, "Insufficient rights to create an Agenda Event for owner id ".$request_data['userownerid'].' Your id is '.DolibarrApiAccess::$user->id);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->actioncomm->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->actioncomm->$field = $this->_checkValForAPI($field, $value, $this->actioncomm);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->expensereport->lines = $lines;
		}*/

		if ($this->actioncomm->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating event", array_merge(array($this->actioncomm->error), $this->actioncomm->errors));
		}

		return $this->actioncomm->id;
	}


	/**
	 * Update an agenda event
	 *
	 * @since	11.0.0	Initial implementation
	 *
	 * @param	int			$id				ID of Agenda Event to update
	 * @param	array		$request_data	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object|false				Object with cleaned properties
	 *
	 * @throws RestException
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'create')) {
			throw new RestException(403, "Insufficient rights to create your Agenda Event");
		}
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'create') && DolibarrApiAccess::$user->id != $request_data['userownerid']) {
			throw new RestException(403, "Insufficient rights to create an Agenda Event for owner id ".$request_data['userownerid'].' Your id is '.DolibarrApiAccess::$user->id);
		}

		$result = $this->actioncomm->fetch($id);
		if ($result) {
			$this->actioncomm->fetch_optionals();
			$this->actioncomm->fetch_userassigned();
			$this->actioncomm->oldcopy = clone $this->actioncomm;  // @phan-suppress-current-line PhanTypeMismatchProperty
		}
		if (!$result) {
			throw new RestException(404, 'actioncomm not found');
		}

		if (!DolibarrApi::_checkAccessToResource('actioncomm', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->actioncomm->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->actioncomm->array_options[$index] = $this->_checkValForAPI($field, $val, $this->actioncomm);
				}
				continue;
			}
			$this->actioncomm->$field = $this->_checkValForAPI($field, $value, $this->actioncomm);
		}

		if ($this->actioncomm->update(DolibarrApiAccess::$user, 1) > 0) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete an agenda event
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id		ID of Agenda Event to delete
	 *
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'delete')) {
			throw new RestException(403, "Insufficient rights to delete your Agenda Event");
		}

		$result = $this->actioncomm->fetch($id);
		if ($result) {
			$this->actioncomm->fetch_optionals();
			$this->actioncomm->fetch_userassigned();
			$this->actioncomm->oldcopy = clone $this->actioncomm;  // @phan-suppress-current-line PhanTypeMismatchProperty
		}

		if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'delete') && DolibarrApiAccess::$user->id != $this->actioncomm->userownerid) {
			throw new RestException(403, "Insufficient rights to delete an Agenda Event of owner id ".$this->actioncomm->userownerid.' Your id is '.DolibarrApiAccess::$user->id);
		}

		if (!$result) {
			throw new RestException(404, 'Agenda Event not found');
		}

		if (!DolibarrApi::_checkAccessToResource('actioncomm', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->actioncomm->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Agenda Event : '.$this->actioncomm->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Agenda Event deleted'
			)
		);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$event = array();
		foreach (AgendaEvents::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$event[$field] = $data[$field];
		}
		return $event;
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

		unset($object->note); // already in note_private or note_public
		unset($object->usermod);
		unset($object->libelle);
		unset($object->context);
		unset($object->canvas);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->thirdparty);
		unset($object->user);
		unset($object->origin);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->state_code);
		unset($object->state_id);
		unset($object->state);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->fk_delivery_address);
		unset($object->shipping_method_id);
		unset($object->fk_account);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->contact);
		unset($object->societe);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->region_id);
		unset($object->actions);
		unset($object->lines);

		return $object;
	}
}
