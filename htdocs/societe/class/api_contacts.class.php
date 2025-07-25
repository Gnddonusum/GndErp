<?php
/* Copyright (C) 2015       Jean-François Ferry         <jfefe@aternatik.fr>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		William Mead				<william@m34d.com>
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

//require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
//require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


/**
 * API class for contacts
 *
 * @since	3.8.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Contacts extends DolibarrApi
{
	/**
	 *
	 * @var string[]   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'lastname',
	);

	/**
	 * @var Contact $contact {@type Contact}
	 */
	public $contact;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$this->contact = new Contact($this->db);
	}

	/**
	 * Get a contact
	 *
	 * Return an array with contact information
	 *
	 * @since	3.8.0	Initial implementation
	 *
	 * @param	int		$id					ID of contact
	 * @param	int		$includecount		Include count of elements the contact is used as a link for
	 * @param	int		$includeroles		Includes roles of the contact
	 * @return	object 						Cleaned contact object
	 *
	 * @throws	RestException
	 */
	public function get($id, $includecount = 0, $includeroles = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'lire')) {
			throw new RestException(403, 'No permission to read contacts');
		}

		if ($id === 0) {
			$result = $this->contact->initAsSpecimen();
		} else {
			$result = $this->contact->fetch($id);
		}

		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includecount) {
			$this->contact->load_ref_elements();
		}

		if ($includeroles) {
			$this->contact->fetchRoles();
		}

		if (isModEnabled('mailing')) {
			$this->contact->getNoEmail();
		}

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * Get a contact by Email
	 *
	 * @since	13.0.0		Initial implementation
	 *
	 * @param	string		$email			Email of contact
	 * @param	int			$includecount	Include count of elements the contact is used as a link for
	 * @param	int			$includeroles	Includes roles of the contact
	 * @return	array|mixed					Cleaned contact object
	 *
	 * @url GET email/{email}
	 *
	 * @throws	RestException	401 Insufficient rights
	 * @throws	RestException	404 User or group not found
	 */
	public function getByEmail($email, $includecount = 0, $includeroles = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'lire')) {
			throw new RestException(403, 'No permission to read contacts');
		}

		if (empty($email)) {
			$result = $this->contact->initAsSpecimen();
		} else {
			$result = $this->contact->fetch(0, null, '', $email);
		}

		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includecount) {
			$this->contact->load_ref_elements();
		}

		if ($includeroles) {
			$this->contact->fetchRoles();
		}

		if (isModEnabled('mailing')) {
			$this->contact->getNoEmail();
		}

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * List contacts
	 *
	 * Get a list of contacts
	 *
	 * @since	3.8.0		Initial implementation
	 *
	 * @param	string		$sortfield			Sort field
	 * @param	string		$sortorder			Sort order
	 * @param	int			$limit				Limit for list
	 * @param	int			$page				Page number
	 * @param	string		$thirdparty_ids		Third party ids to filter contacts of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param	int			$category			Use this param to filter list by category
	 * @param	string		$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param	int			$includecount		Include count of elements the contact is used as a link for
	 * @param	int			$includeroles		Includes roles of the contact
	 * @param	string		$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool		$pagination_data	If this parameter is set to true, the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	Contact[]						Array of contact objects
	 *
	 * @throws	RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $category = 0, $sqlfilters = '', $includecount = 0, $includeroles = 0, $properties = '', $pagination_data = false)
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'lire')) {
			throw new RestException(403, 'No permission to read contacts');
		}

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ?: $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as te ON te.fk_object = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON t.fk_soc = s.rowid";
		$sql .= ' WHERE t.entity IN ('.getEntity('contact').')';
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
		// Select contacts of given category
		if ($category > 0) {
			// Search Contact Categories
			$searchCategoryContactList = $category ? array($category) : array();
			// $searchCategoryContactOperator = 0;
			// Search for tag/category ($searchCategoryContactList is an array of ID)
			if (!empty($searchCategoryContactList)) {
				$searchCategoryContactSqlList = array();
				// $listofcategoryid = '';
				foreach ($searchCategoryContactList as $searchCategoryContact) {
					if (intval($searchCategoryContact) == -2) {
						$searchCategoryContactSqlList[] = "NOT EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE t.rowid = ck.fk_socpeople)";
					} elseif (intval($searchCategoryContact) > 0) {
						// if ($searchCategoryContactOperator == 0) {
							$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE t.rowid = ck.fk_socpeople AND ck.fk_categorie = ".((int) $searchCategoryContact).")";
						// } else {
						// 	$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryContact);
						// }
					}
				}
				// if ($listofcategoryid) {
				// 	$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE t.rowid = ck.fk_socpeople AND ck.fk_categorie IN (".$this->db->sanitize($listofcategoryid)."))";
				// }
				// if ($searchCategoryContactOperator == 1) {
				// 	if (!empty($searchCategoryContactSqlList)) {
				// 		$sql .= " AND (".implode(' OR ', $searchCategoryContactSqlList).")";
				// 	}
				// } else {
				if (!empty($searchCategoryContactSqlList)) {
					$sql .= " AND (".implode(' AND ', $searchCategoryContactSqlList).")";
				}
				// }
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
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$contact_static = new Contact($this->db);
				if ($contact_static->fetch($obj->rowid)) {
					$contact_static->fetchRoles();
					if ($includecount) {
						$contact_static->load_ref_elements();
					}
					if ($includeroles) {
						$contact_static->fetchRoles();
					}
					if (isModEnabled('mailing')) {
						$contact_static->getNoEmail();
					}

					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($contact_static), $properties);
				}

				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve contacts : '.$sql);
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
	 * Create a contact
	 *
	 * @since	3.8.0	Initial implementation
	 *
	 * @param			array	$request_data	Request data
	 * @phan-param		?array<string,string>	$request_data
	 * @phpstan-param	?array<string,string>	$request_data
	 * @return			int						ID of contact
	 *
	 * @suppress PhanPluginUnknownArrayMethodParamType  Luracast limitation
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
			throw new RestException(403, 'No permission to create/update contacts');
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->contact->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->contact->array_options[$index] = $this->_checkValForAPI('extrafields', $val, $this->contact);
				}
				continue;
			}

			$this->contact->$field = $this->_checkValForAPI($field, $value, $this->contact);
		}
		if ($this->contact->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating contact", array_merge(array($this->contact->error), $this->contact->errors));
		}
		if (isModEnabled('mailing') && !empty($this->contact->email) && isset($this->contact->no_email)) {
			$this->contact->setNoEmail($this->contact->no_email);
		}
		return $this->contact->id;
	}

	/**
	 * Update a contact
	 *
	 * @since	3.8.0	Initial implementation
	 *
	 * @param			int		$id				ID of contact to update
	 * @param			array 	$request_data	Request data
	 * @phan-param		?array<string,string>	$request_data
	 * @phpstan-param	?array<string,string>	$request_data
	 * @return			Object|false			Updated object, false when error updating contact
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
			throw new RestException(403, 'No permission to create/update contacts');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->contact->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->contact->array_options[$index] = $this->_checkValForAPI($field, $val, $this->contact);
				}
				continue;
			}

			$this->contact->$field = $this->_checkValForAPI($field, $value, $this->contact);
		}

		if (isModEnabled('mailing') && !empty($this->contact->email) && isset($this->contact->no_email)) {
			$this->contact->setNoEmail($this->contact->no_email);
		}

		if ($this->contact->update($id, DolibarrApiAccess::$user, 0, 'update') > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->contact->error);
		}
	}

	/**
	 * Delete a contact
	 *
	 * @since	3.8.0	Initial implementation
	 *
	 * @param	int		$id		Contact ID
	 * @return	array[]
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'supprimer')) {
			throw new RestException(403, 'No permission to delete contacts');
		}
		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->contact->oldcopy = clone $this->contact; // @phan-suppress-current-line PhanTypeMismatchProperty

		if ($this->contact->delete(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error when delete contact ' . $this->contact->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact deleted'
			)
		);
	}

	/**
	 * Create a user account object from contact (external user)
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id				ID of contact
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int		ID of user
	 *
	 * @url	POST {id}/createUser
	 * @suppress PhanPluginUnknownArrayMethodParamType  Luracast limitation
	 */
	public function createUser($id, $request_data = null)
	{
		//if (!DolibarrApiAccess::$user->hasRight('user', 'user', 'creer')) {
		//throw new RestException(403);
		//}

		if (!isset($request_data["login"])) {
			throw new RestException(400, "login field missing");
		}
		if (!isset($request_data["password"])) {
			throw new RestException(400, "password field missing");
		}

		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'lire')) {
			throw new RestException(403, 'No permission to read contacts');
		}
		if (!DolibarrApiAccess::$user->hasRight('user', 'user', 'creer')) {
			throw new RestException(403, 'No permission to create user');
		}

		$contact = new Contact($this->db);
		$contact->fetch($id);
		if ($contact->id <= 0) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $contact->id, 'socpeople&societe')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check mandatory fields
		$login = $request_data["login"];
		$password = $request_data["password"];
		$useraccount = new User($this->db);
		$result = $useraccount->create_from_contact($contact, $login, $password);
		if ($result <= 0) {
			throw new RestException(500, "User not created");
		}
		// password parameter not used in create_from_contact
		$useraccount->setPassword($useraccount, $password);

		return $result;
	}

	/**
	 * Get categories of a contact
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id				ID of contact
	 * @param	string	$sortfield		Sort field
	 * @param	string	$sortorder		Sort order
	 * @param	int		$limit			Limit for list
	 * @param	int		$page			Page number
	 *
	 * @return	mixed
	 *
	 * @url GET {id}/categories
	 */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'contact', $sortfield, $sortorder, $limit, $page);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		return $result;
	}

	/**
	 * Add a category to a contact
	 *
	 * @since	11.0.0	Initial implementation
	 *
	 * @param	int		$id				ID of contact
	 * @param	int		$category_id	ID of category
	 *
	 * @return	mixed
	 *
	 * @url PUT {id}/categories/{category_id}
	 *
	 * @throws	RestException	401 Insufficient rights
	 * @throws	RestException	404 Category or contact not found
	 */
	public function addCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
			throw new RestException(403, 'Insufficient rights');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->contact, 'contact');

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * Remove the link between a category and a contact
	 *
	 * @since	11.0.0	Initial implementation
	 *
	 * @param	int		$id				ID of contact
	 * @param	int		$category_id	ID of category
	 * @return	mixed
	 *
	 * @url DELETE {id}/categories/{category_id}
	 *
	 * @throws	RestException	401 Insufficient rights
	 * @throws	RestException	404 Category or contact not found
	 */
	public function deleteCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
			throw new RestException(403, 'Insufficient rights');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->contact, 'contact');

		return $this->_cleanObjectDatas($this->contact);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object data
	 *
	 * @param	Object	$object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->note);
		unset($object->lines);
		unset($object->thirdparty);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	string[]|null	$data	Data to validate
	 * @return	string[]
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$contact = array();
		foreach (Contacts::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$contact[$field] = $data[$field];
		}

		return $contact;
	}
}
