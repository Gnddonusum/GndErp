<?php
/* Copyright (C) 2015   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Orders extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
		'date'
	);

	/**
	 * @var Commande {@type Commande}
	 */
	public $commande;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->commande = new Commande($this->db);
	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order information
	 *
	 * @param       int         $id            ID of order
	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	array|mixed data without useless information
	 *
	 * @throws	RestException
	 */
	public function get($id, $contact_list = -1)
	{
		return $this->_fetch($id, '', '', $contact_list);
	}

	/**
	 * Get properties of an order object by ref
	 *
	 * Return an array with order information
	 *
	 * @param       string		$ref			Ref of object
	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	array|mixed data without useless information
	 *
	 * @url GET    ref/{ref}
	 *
	 * @throws	RestException
	 */
	public function getByRef($ref, $contact_list = -1)
	{
		return $this->_fetch(0, $ref, '', $contact_list);
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @param       string		$ref_ext			External reference of object
	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	array|mixed data without useless information
	 *
	 * @url GET    ref_ext/{ref_ext}
	 *
	 * @throws	RestException
	 */
	public function getByRefExt($ref_ext, $contact_list = -1)
	{
		return $this->_fetch(0, '', $ref_ext, $contact_list);
	}

	/**
	 * Get properties of an order object
	 *
	 * Return an array with order information
	 *
	 * @param       int         $id				ID of order
	 * @param		string		$ref			Ref of object
	 * @param		string		$ref_ext		External reference of object
	 * @param       int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return		Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id, $ref = '', $ref_ext = '', $contact_list = -1)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($contact_list > -1) {
			// Add external contacts ids
			$tmparray = $this->commande->liste_contact(-1, 'external', $contact_list);
			if (is_array($tmparray)) {
				$this->commande->contacts_ids = $tmparray;
			}
			$tmparray = $this->commande->liste_contact(-1, 'internal', $contact_list);
			if (is_array($tmparray)) {
				$this->commande->contacts_ids_internal = $tmparray;
			}
		}

		$this->commande->fetchObjectLinked();

		// Add online_payment_url, cf #20477
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		$this->commande->online_payment_url = getOnlinePaymentUrl(0, 'order', $this->commande->ref);

		return $this->_cleanObjectDatas($this->commande);
	}

	/**
	 * List orders
	 *
	 * Get a list of orders
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string		   $thirdparty_ids		Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string           $sqlfilterlines      Other criteria to filter answers separated by a comma. Syntax example "(tl.fk_product:=:'17') and (tl.price:<:'250')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @param int			   $loadlinkedobjects	Load also linked object
	 * @return  array                               Array of order objects
	 * @phan-return Commande[]|array{data:Commande[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Commande[]|array{data:Commande[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException 404 Not found
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $sqlfilterlines = '', $properties = '', $pagination_data = false, $loadlinkedobjects = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ?: $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('commande').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
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
		// Add sql filters for lines
		if ($sqlfilterlines) {
			$errormessage = '';
			$sql .= " AND EXISTS (SELECT tl.rowid FROM ".MAIN_DB_PREFIX."commandedet AS tl WHERE tl.fk_commande = t.rowid";
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilterlines, $errormessage);
			$sql .=	")";
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilterlines -> '.$errormessage);
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

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$commande_static = new Commande($this->db);
				if ($commande_static->fetch($obj->rowid) > 0) {
					// Add external contacts ids
					$tmparray = $commande_static->liste_contact(-1, 'external', 1);
					if (is_array($tmparray)) {
						$commande_static->contacts_ids = $tmparray;
					}

					if ($loadlinkedobjects) {
						// retrieve linked objects
						$commande_static->fetchObjectLinked();
					}

					// Add online_payment_url, cf #20477
					require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
					$commande_static->online_payment_url = getOnlinePaymentUrl(0, 'order', $commande_static->ref);

					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($commande_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve commande list : '.$this->db->lasterror());
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
	 * Create a sale order
	 *
	 * Example: { "socid": 2, "date": 1595196000, "type": 0, "lines": [{ "fk_product": 2, "qty": 1 }] }
	 *
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  int     ID of order
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->commande->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->commande->$field = $this->_checkValForAPI($field, $value, $this->commande);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->commande->lines = $lines;
		}*/

		if ($this->commande->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating order", array_merge(array($this->commande->error), $this->commande->errors));
		}

		return ((int) $this->commande->id);
	}

	/**
	 * Get lines of an order
	 *
	 * @param int   $id             Id of order
	 *
	 * @url	GET {id}/lines
	 *
	 * @return array
	 * @phan-return OrderLine[]
	 * @phpstan-return OrderLine[]
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->commande->getLinesArray();
		$result = array();
		foreach ($this->commande->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Get properties of a line of an order object by id
	 *
	 * @param int   $id             Id of order
	 * @param int   $lineid         Id of line
	 * @param string                $properties     Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 *
	 * @url    GET {id}/lines/{lineid}
	 *
	 * @return Object
	 */
	public function getLine($id, $lineid, $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->commande->fetch_lines();
		foreach ($this->commande->lines as $line) {
			if ($line->id == $lineid) {
				return $this->_filterObjectProperties($this->_cleanObjectDatas($line), $properties);
			}
		}
		throw new RestException(404, 'Line not found');
	}

	/**
	 * Add a line to given order
	 *
	 * @param int   $id             Id of order to update
	 * @param array $request_data   OrderLine data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->commande->addline(
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->fk_product,
			$request_data->remise_percent,
			$request_data->info_bits,
			$request_data->fk_remise_except,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->subprice,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->product_type,
			$request_data->rang,
			$request_data->special_code,
			$request_data->fk_parent_line,
			$request_data->fk_fournprice,
			$request_data->pa_ht,
			$request_data->label,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->origin,
			$request_data->origin_id,
			$request_data->multicurrency_subprice,
			$request_data->ref_ext
		);

		if ($updateRes > 0) {
			return $updateRes;
		} else {
			throw new RestException(400, $this->commande->error);
		}
	}

	/**
	 * Update a line to given order
	 *
	 * @param	int   $id             Id of order to update
	 * @param	int   $lineid         Id of line to update
	 * @param	array $request_data   OrderLine data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object|false		  Object with cleaned properties
	 *
	 * @url	PUT {id}/lines/{lineid}
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->commande->updateline(
			$lineid,
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->remise_percent,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->info_bits,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->product_type,
			$request_data->fk_parent_line,
			0,
			$request_data->fk_fournprice,
			$request_data->pa_ht,
			$request_data->label,
			$request_data->special_code,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->multicurrency_subprice,
			0,
			$request_data->ref_ext,
			$request_data->rang
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
		return false;
	}

	/**
	 * Delete a line of a given order
	 *
	 * @param	int		$id             Id of order to update
	 * @param	int		$lineid         Id of line to delete
	 * @return	Object					Object with cleaned properties
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$updateRes = $this->commande->deleteLine(DolibarrApiAccess::$user, $lineid, $id);
		if ($updateRes > 0) {
			return $this->get($id);
		} else {
			throw new RestException(405, $this->commande->error);
		}
	}

	/**
	 * Get contacts of given order
	 *
	 * Return an array with contact information
	 *
	 * @param	int		$id			ID of order
	 * @param	string	$type		Type of the contact (BILLING, SHIPPING, CUSTOMER)
	 * @return	Object				Object with cleaned properties
	 *
	 * @url	GET {id}/contacts
	 *
	 * @throws	RestException
	 */
	public function getContacts($id, $type = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$contacts = $this->commande->liste_contact(-1, 'external', 0, $type);

		return $this->_cleanObjectDatas($contacts);
	}

	/**
	 * Add a contact type of given order
	 *
	 * @param int    $id             Id of order to update
	 * @param int    $contactid      Id of contact to add
	 * @param string $type           Type of the contact (BILLING, SHIPPING, CUSTOMER)
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url	POST {id}/contact/{contactid}/{type}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function postContact($id, $contactid, $type)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->commande->add_contact($contactid, $type, 'external');

		if ($result < 0) {
			throw new RestException(500, 'Error when added the contact');
		}

		if ($result == 0) {
			throw new RestException(304, 'contact already added');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact linked to the order'
			)
		);
	}

	/**
	 * Unlink a contact type of given order
	 *
	 * @param int    $id             Id of order to update
	 * @param int    $contactid      Id of contact
	 * @param string $type           Type of the contact (BILLING, SHIPPING, CUSTOMER).
	 *
	 * @url	DELETE {id}/contact/{contactid}/{type}
	 *
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteContact($id, $contactid, $type)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$contacts = $this->commande->liste_contact();

		foreach ($contacts as $contact) {
			if ($contact['id'] == $contactid && $contact['code'] == $type) {
				$result = $this->commande->delete_contact($contact['rowid']);

				if (!$result) {
					throw new RestException(500, 'Error when deleted the contact');
				}
			}
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact unlinked from order'
			)
		);
	}

	/**
	 * Update order general fields (won't touch lines of order)
	 *
	 * @param	int		$id             Id of order to update
	 * @param	array	$request_data   Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object					Object with cleaned properties
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->commande->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->commande->array_options[$index] = $this->_checkValForAPI($field, $val, $this->commande);
				}
				continue;
			}

			$this->commande->$field = $this->_checkValForAPI($field, $value, $this->commande);
		}

		// Update availability
		if (!empty($this->commande->availability_id)) {
			if ($this->commande->availability($this->commande->availability_id) < 0) {
				throw new RestException(400, 'Error while updating availability');
			}
		}

		if ($this->commande->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->commande->error);
		}
	}

	/**
	 * Delete order
	 *
	 * @param   int     $id         Order ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->commande->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting order : '.$this->commande->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order deleted'
			)
		);
	}

	/**
	 * Validate an order
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "idwarehouse": 0,
	 *   "notrigger": 0
	 * }
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 * @return  Object              Object with cleaned properties
	 *
	 * @url POST    {id}/validate
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function validate($id, $idwarehouse = 0, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		$result = $this->commande->fetch_thirdparty(); // do not check result, as failure is not fatal (used only for mail notification substitutes)

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->commande->valid(DolibarrApiAccess::$user, $idwarehouse, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Order: '.$this->commande->error);
		}
		$result = $this->commande->fetch($id);

		$this->commande->fetchObjectLinked();

		//fix #20477 : add online_payment_url
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		$this->commande->online_payment_url = getOnlinePaymentUrl(0, 'order', $this->commande->ref);

		return $this->_cleanObjectDatas($this->commande);
	}

	/**
	 *  Tag the order as validated (opened)
	 *
	 *  Function used when order is reopend after being closed.
	 *
	 * @param int   $id       Id of the order
	 *
	 * @url     POST {id}/reopen
	 *
	 * @return int
	 *
	 * @throws RestException 304
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function reopen($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Order ID is mandatory');
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		$result = $this->commande->set_reopen(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(405, $this->commande->error);
		} elseif ($result == 0) {
			throw new RestException(304);
		}

		return $result;
	}

	/**
	 * Classify the order as invoiced. Could be also called setbilled
	 *
	 * @param	int   $id           Id of the order
	 * @return	Object					Object with cleaned properties
	 *
	 * @url     POST {id}/setinvoiced
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function setinvoiced($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Order ID is mandatory');
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		$result = $this->commande->classifyBilled(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(400, $this->commande->error);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->commande->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->commande);
	}

	/**
	 * Close an order (Classify it as "Delivered")
	 *
	 * @param   int     $id             Order ID
	 * @param   int     $notrigger      Disabled triggers
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/close
	 */
	public function close($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->commande->cloture(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Order: '.$this->commande->error);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->commande->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->commande);
	}

	/**
	 * Set an order to draft
	 *
	 * @param   int     $id             Order ID
	 * @param   int		$idwarehouse    Warehouse ID to use for stock change (Used only if option STOCK_CALCULATE_ON_VALIDATE_ORDER is on)
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/settodraft
	 */
	public function settodraft($id, $idwarehouse = -1)
	{
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->commande->setDraft(DolibarrApiAccess::$user, $idwarehouse);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Order: '.$this->commande->error);
		}

		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->commande->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->commande->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->commande);
	}


	/**
	 * Create an order using an existing proposal.
	 *
	 * @param int   $proposalid       Id of the proposal
	 * @return	Object					Object with cleaned properties
	 *
	 * @url     POST /createfromproposal/{proposalid}
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function createOrderFromProposal($proposalid)
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
			throw new RestException(403);
		}
		if (!DolibarrApiAccess::$user->hasRight('commande', 'creer')) {
			throw new RestException(403);
		}
		if (empty($proposalid)) {
			throw new RestException(400, 'Proposal ID is mandatory');
		}

		$propal = new Propal($this->db);
		$result = $propal->fetch($proposalid);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		$result = $this->commande->createFromProposal($propal, DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(405, $this->commande->error);
		}
		$this->commande->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->commande);
	}

	/**
	 * Get the shipments of an order
	 *
	 * @param int   $id       Id of the order
	 *
	 * @url     GET {id}/shipment
	 *
	 * @return array
	 * @phan-return Expedition[]
	 * @phpstan-return Expedition[]
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function getOrderShipments($id)
	{
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
			throw new RestException(403);
		}
		$obj_ret = array();
		$sql = "SELECT e.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
		$sql .= " JOIN ".MAIN_DB_PREFIX."expeditiondet as edet";
		$sql .= " ON e.rowid = edet.fk_expedition";
		$sql .= " JOIN ".MAIN_DB_PREFIX."commandedet as cdet";
		$sql .= " ON edet.fk_elementdet = cdet.rowid";
		$sql .= " JOIN ".MAIN_DB_PREFIX."commande as c";
		$sql .= " ON cdet.fk_commande = c.rowid";
		$sql .= " WHERE c.rowid = ".((int) $id);
		$sql .= " GROUP BY e.rowid";
		$sql .= $this->db->order("e.rowid", "ASC");

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num <= 0) {
				throw new RestException(404, 'Shipments not found ');
			}
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$shipment_static = new Expedition($this->db);
				if ($shipment_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($shipment_static);
				}
				$i++;
			}
		} else {
			throw new RestException(500, 'Error when retrieve shipment list : '.$this->db->lasterror());
		}
		return $obj_ret;
	}

	/**
	 * Create the shipment of an order
	 *
	 * @param int   $id       Id of the order
	 * @param int	$warehouse_id Id of a warehouse
	 *
	 * @url     POST {id}/shipment/{warehouse_id}
	 *
	 * @return int
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function createOrderShipment($id, $warehouse_id)
	{
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}
		if ($warehouse_id <= 0) {
			throw new RestException(404, 'Warehouse not found');
		}
		$result = $this->commande->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}
		$shipment = new Expedition($this->db);
		$shipment->socid = $this->commande->socid;
		$shipment->origin_id = $this->commande->id;
		$shipment->origin = $this->commande->element;
		$result = $shipment->create(DolibarrApiAccess::$user);
		if ($result <= 0) {
			throw new RestException(500, 'Error on creating expedition :'.$this->db->lasterror());
		}
		foreach ($this->commande->lines as $line) {
			$result = $shipment->create_line($warehouse_id, $line->id, $line->qty);
			if ($result <= 0) {
				throw new RestException(500, 'Error on creating expedition lines:'.$this->db->lasterror());
			}
		}
		return $shipment->id;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object			Object to clean
	 * @return  Object					Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		return $object;
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
		$commande = array();
		foreach (Orders::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$commande[$field] = $data[$field];
		}
		return $commande;
	}
}
