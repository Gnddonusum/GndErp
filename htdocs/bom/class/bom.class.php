<?php
/* Copyright (C) 2019	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2023	Benjamin Falière	<benjamin.faliere@altairis.fr>
 * Copyright (C) 2023	Charlene Benke		<charlene@patas-monkey.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        htdocs/bom/class/bom.class.php
 * \ingroup     bom
 * \brief       This file is a CRUD class file for BOM (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bomline.class.php';

if (isModEnabled('workstation')) {
	require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
}


/**
 * Class for BOM
 */
class BOM extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'bom';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'bom';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'bom_bom';

	/**
	 * @var string String with name of icon for bom. Must be the part after the 'object_' into object_bom.png
	 */
	public $picto = 'bom';

	/**
	 * @var Product	Object product of the BOM
	 */
	public $product;

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => "Id",),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'noteditable' => 1, 'visible' => 4, 'position' => 10, 'notnull' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'comment' => "Reference of BOM", 'showoncombobox' => 1, 'csslist' => 'nowraponall'),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'notnull' => 1, 'searchall' => 1, 'showoncombobox' => 2, 'autofocusoncreate' => 1, 'css' => 'minwidth300 maxwidth400', 'csslist' => 'tdoverflowmax200'),
		'bomtype' => array('type' => 'integer', 'label' => 'Type', 'enabled' => 1, 'visible' => 1, 'position' => 33, 'notnull' => 1, 'default' => '0', 'arrayofkeyval' => array(0 => 'Manufacturing', 1 => 'Disassemble'), 'css' => 'minwidth175', 'csslist' => 'minwidth175 center'),
		//'bomtype' => array('type'=>'integer', 'label'=>'Type', 'enabled'=>1, 'visible'=>-1, 'position'=>32, 'notnull'=>1, 'default'=>'0', 'arrayofkeyval'=>array(0=>'Manufacturing')),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1:((finished:is:null) or (finished:!=:0))', 'label' => 'Product', 'picto' => 'product', 'enabled' => 1, 'visible' => 1, 'position' => 35, 'notnull' => 1, 'index' => 1, 'help' => 'ProductBOMHelp', 'css' => 'maxwidth500', 'csslist' => 'tdoverflowmax100'),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => -1, 'position' => 60, 'notnull' => -1,),
		'qty' => array('type' => 'real', 'label' => 'Quantity', 'enabled' => 1, 'visible' => 1, 'default' => '1', 'position' => 55, 'notnull' => 1, 'isameasure' => 1, 'css' => 'maxwidth50imp right'),
		//'efficiency' => array('type'=>'real', 'label'=>'ManufacturingEfficiency', 'enabled'=>1, 'visible'=>-1, 'default'=>'1', 'position'=>100, 'notnull'=>0, 'css'=>'maxwidth50imp', 'help'=>'ValueOfMeansLossForProductProduced'),
		'duration' => array('type' => 'duration', 'label' => 'EstimatedDuration', 'enabled' => 1, 'visible' => -1, 'position' => 101, 'notnull' => -1, 'css' => 'maxwidth50imp', 'help' => 'EstimatedDurationDesc'),
		'fk_warehouse' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php:0', 'label' => 'WarehouseForProduction', 'picto' => 'stock', 'enabled' => 1, 'visible' => -1, 'position' => 102, 'css' => 'maxwidth500', 'csslist' => 'tdoverflowmax100'),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => -2, 'position' => 161, 'notnull' => -1,),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => -2, 'position' => 162, 'notnull' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 300, 'notnull' => 1,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'position' => 501, 'notnull' => 1,),
		'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -2, 'position' => 502, 'notnull' => 0,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserCreation', 'picto' => 'user', 'enabled' => 1, 'visible' => -2, 'position' => 510, 'notnull' => 1, 'foreignkey' => 'user.rowid', 'csslist' => 'tdoverflowmax100'),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => 1, 'visible' => -2, 'position' => 511, 'notnull' => -1, 'csslist' => 'tdoverflowmax100'),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'picto' => 'user', 'enabled' => 1, 'visible' => -2, 'position' => 512, 'notnull' => 0, 'csslist' => 'tdoverflowmax100'),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000, 'notnull' => -1,),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 1010),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => 2, 'position' => 1000, 'notnull' => 1, 'default' => '0', 'index' => 1, 'arrayofkeyval' => array(0 => 'Draft', 1 => 'Enabled', 9 => 'Disabled')),
	);

	/**
	 * @var int rowid
	 */
	public $rowid;

	/**
	 * @var string ref
	 */
	public $ref;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var int bomtype
	 */
	public $bomtype;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int|string date_valid
	 */
	public $date_valid;

	/**
	 * @var int Id User creator
	 */
	public $fk_user_creat;

	/**
	 * @var int Id User modifying
	 */
	public $fk_user_modif;

	/**
	 * @var int Id User modifying
	 */
	public $fk_user_valid;

	/**
	 * @var int Id User modifying
	 */
	public $fk_warehouse;

	/**
	 * @var string import key
	 */
	public $import_key;

	/**
	 * @var int status
	 */
	public $status;

	/**
	 * @var int product Id
	 */
	public $fk_product;
	/**
	 * @var float
	 */
	public $qty;
	/**
	 * @var float
	 */
	public $duration;
	/**
	 * @var float
	 */
	public $efficiency;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'bom_bomline';

	/**
	 * @var string    Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_bom';

	/**
	 * @var string    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'BOMLine';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables=array();

	/**
	 * @var string[]	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('bom_bomline');

	/**
	 * @var BOMLine[]     Array of subtable lines
	 */
	public $lines = array();

	/**
	 * @var float		Calculated cost for the BOM
	 */
	public $total_cost = 0;

	/**
	 * @var float		Calculated cost for 1 unit of the product in BOM
	 */
	public $unit_cost = 0;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach ($this->fields as $key => $val) {
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
				foreach ($val['arrayofkeyval'] as $key2 => $val2) {
					$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  int 	$notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		if ($this->efficiency <= 0 || $this->efficiency > 1) {
			$this->efficiency = 1;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	BOM|int<-1,-1> 		New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $hookmanager, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Get lines so they will be clone
		//foreach ($object->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$default_ref = $this->fields['ref']['default'] ?? null;
		$object->ref = empty($default_ref) ? $langs->trans("copy_of_").$object->ref : $default_ref;
		// @phan-suppress-next-line PhanTypeInvalidDimOffset
		$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($object->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			// @phan-suppress-next-line PhanUndeclaredProperty
			if (property_exists($this, 'socid') && $this->socid == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		// If there is lines, create lines too



		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int		$id	Id object
	 * @param string	$ref	Ref
	 * @return int<-1,1>	Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);

		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		//$this->calculateCosts();		// This consume a high number of subrequests. Do not call it into fetch but when you need it.

		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}

	/**
	 * Load object lines in memory from the database by type of product
	 *
	 * @param int<0,1>	$typeproduct	0 type product, 1 type service
	 * @return int<-1,1>				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLinesbytypeproduct($typeproduct = 0)
	{
		$this->lines = array();

		$objectlineclassname = get_class($this).'Line';
		if (!class_exists($objectlineclassname)) {
			$this->error = 'Error, class '.$objectlineclassname.' not found during call of fetchLinesCommon';
			return -1;
		}

		$objectline = new $objectlineclassname($this->db);

		'@phan-var-force BOMLine $objectline';

		$sql = "SELECT ".$objectline->getFieldList('l');
		$sql .= " FROM ".$this->db->prefix().$objectline->table_element." as l";
		$sql .= " LEFT JOIN ".$this->db->prefix()."product as p ON p.rowid = l.fk_product";
		$sql .= " WHERE l.fk_".$this->db->escape($this->element)." = ".((int) $this->id);
		$sql .= " AND p.fk_product_type = ". ((int) $typeproduct);
		if (isset($objectline->fields['position'])) {
			$sql .= $this->db->order('position', 'ASC');
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_rows) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$newline = new $objectlineclassname($this->db);
					'@phan-var-force BOMLine $newline';
					$newline->setVarsFromFetchObj($obj);

					// Load also extrafields for the line
					//if (empty($noextrafields)) {
					$newline->fetch_optionals();
					//}

					$this->lines[] = $newline;
				}
				$i++;
			}

			return $num_rows;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      		$sortorder    Sort Order
	 * @param  string      		$sortfield    Sort field
	 * @param  int<0,max>  		$limit        Limit
	 * @param  int<0,max>		$offset       Offset
	 * @param  string   		$filter       Filter USF
	 * @param  string      		$filtermode   Filter mode (AND or OR)
	 * @return BOM[]|int<-1,-1>    			  int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if ($this->ismultientitymanaged) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User			$user		User that modifies
	 * @param  int<0,1> 	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return int<-1,-1>|int<1,1>		Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		if ($this->efficiency <= 0 || $this->efficiency > 1) {
			$this->efficiency = 1;
		}

		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User		$user      	User that deletes
	 * @param int<0,1>	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int<-1,-1>|int<1,1>		Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 1)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 * Add an BOM line into database (linked to BOM)
	 *
	 * @param	int			$fk_product				Id of product
	 * @param	float		$qty					Quantity
	 * @param	int<0,1> 	$qty_frozen				If the qty is Frozen
	 * @param 	int			$disable_stock_change	Disable stock change on using in MO
	 * @param	float		$efficiency				Efficiency in MO
	 * @param	int<-1,max>	$position				Position of BOM-Line in BOM-Lines
	 * @param	?int		$fk_bom_child			Id of BOM Child
	 * @param	?string		$import_key				Import Key
	 * @param	int 		$fk_unit				Unit
	 * @param	array<string,mixed>		$array_options			extrafields array
	 * @param	?int		$fk_default_workstation	Default workstation
	 * @return	int<-3,max>							Return integer <0 if KO, Id of created object if OK
	 */
	public function addLine($fk_product, $qty, $qty_frozen = 0, $disable_stock_change = 0, $efficiency = 1.0, $position = -1, $fk_bom_child = null, $import_key = null, $fk_unit = 0, $array_options = array(), $fk_default_workstation = null)
	{
		global $mysoc, $conf, $langs, $user;

		$logtext = "::addLine bomid=$this->id, qty=$qty, fk_product=$fk_product, qty_frozen=$qty_frozen, disable_stock_change=$disable_stock_change, efficiency=$efficiency";
		$logtext .= ", fk_bom_child=$fk_bom_child, import_key=$import_key";
		dol_syslog(get_class($this).$logtext, LOG_DEBUG);

		if ($this->statut == self::STATUS_DRAFT) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($qty_frozen)) {
				$qty_frozen = 0;
			}
			if (empty($disable_stock_change)) {
				$disable_stock_change = 0;
			}
			if (empty($efficiency)) {
				$efficiency = 1.0;
			}
			if (empty($fk_bom_child)) {
				$fk_bom_child = null;
			}
			if (empty($import_key)) {
				$import_key = '';
			}
			if (empty($position)) {
				$position = -1;
			}

			$qty = (float) price2num($qty);
			$efficiency = (float) price2num($efficiency);
			$position = (float) price2num($position);

			$this->db->begin();

			// Rank to use
			$rangMax = $this->line_max();
			$rankToUse = $position;
			if ($rankToUse <= 0 or $rankToUse > $rangMax) { // New line after existing lines
				$rankToUse = $rangMax + 1;
			} else { // New line between the existing lines
				foreach ($this->lines as $bl) {
					if ($bl->position >= $rankToUse) {
						$bl->position++;
						$bl->update($user);
					}
				}
			}

			// Insert line
			$line = new BOMLine($this->db);

			$line->context = $this->context;

			$line->fk_bom = $this->id;
			$line->fk_product = $fk_product;
			$line->qty = $qty;
			$line->qty_frozen = $qty_frozen;
			$line->disable_stock_change = $disable_stock_change;
			$line->efficiency = $efficiency;
			$line->fk_bom_child = $fk_bom_child;
			$line->import_key = $import_key;
			$line->position = $rankToUse;
			$line->fk_unit = $fk_unit;
			$line->fk_default_workstation = $fk_default_workstation;

			if (is_array($array_options) && count($array_options) > 0) {
				$line->array_options = $array_options;
			}

			$result = $line->create($user);

			if ($result > 0) {
				$this->calculateCosts();
				$this->db->commit();
				return $result;
			} else {
				$this->setErrorsFromObject($line);
				dol_syslog(get_class($this)."::addLine error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			dol_syslog(get_class($this)."::addLine status of BOM must be Draft to allow use of ->addLine()", LOG_ERR);
			return -3;
		}
	}

	/**
	 * Update an BOM line into database
	 *
	 * @param 	int			$rowid					Id of line to update
	 * @param	float		$qty					Quantity
	 * @param	float		$qty_frozen				Frozen quantity
	 * @param 	int			$disable_stock_change	Disable stock change on using in MO
	 * @param	float		$efficiency				Efficiency in MO
	 * @param	int<-1,max>	$position				Position of BOM-Line in BOM-Lines
	 * @param	?string		$import_key				Import Key
	 * @param	int			$fk_unit				Unit of line
	 * @param	array<string,mixed>		$array_options			extrafields array
	 * @param	?int		$fk_default_workstation	Default workstation
	 * @return	int<-3,max>						Return integer <0 if KO, Id of updated BOM-Line if OK
	 */
	public function updateLine($rowid, $qty, $qty_frozen = 0, $disable_stock_change = 0, $efficiency = 1.0, $position = -1, $import_key = null, $fk_unit = 0, $array_options = array(), $fk_default_workstation = null)
	{
		global $user;

		$logtext = "::updateLine bomid=$this->id, qty=$qty, qty_frozen=$qty_frozen, disable_stock_change=$disable_stock_change, efficiency=$efficiency";
		$logtext .= ", import_key=$import_key";
		dol_syslog(get_class($this).$logtext, LOG_DEBUG);

		if ($this->statut == self::STATUS_DRAFT) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($qty_frozen)) {
				$qty_frozen = 0;
			}
			if (empty($disable_stock_change)) {
				$disable_stock_change = 0;
			}
			if (empty($efficiency)) {
				$efficiency = 1.0;
			}
			if (empty($import_key)) {
				$import_key = '';
			}
			if (empty($position)) {
				$position = -1;
			}

			$qty = (float) price2num($qty);
			$efficiency = (float) price2num($efficiency);
			$position = (float) price2num($position);

			$this->db->begin();

			// Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new BOMLine($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			$staticLine = clone $line;
			$line->oldcopy = $staticLine;
			$line->context = $this->context;

			// Rank to use
			$rankToUse = (int) $position;
			if ($rankToUse != $line->oldcopy->position) { // check if position have a new value
				foreach ($this->lines as $bl) {
					if ($bl->position >= $rankToUse and $bl->position < ($line->oldcopy->position + 1)) { // move rank up
						$bl->position++;
						$bl->update($user);
					}
					if ($bl->position <= $rankToUse and $bl->position > ($line->oldcopy->position)) { // move rank down
						$bl->position--;
						$bl->update($user);
					}
				}
			}


			$line->fk_bom = $this->id;
			$line->qty = $qty;
			$line->qty_frozen = $qty_frozen;
			$line->disable_stock_change = $disable_stock_change;
			$line->efficiency = $efficiency;
			$line->import_key = $import_key;
			$line->position = $rankToUse;


			if (!empty($fk_unit)) {
				$line->fk_unit = $fk_unit;
			}


			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$line->array_options[$key] = $array_options[$key];
				}
			}
			if ($line->fk_default_workstation != $fk_default_workstation) {
				$line->fk_default_workstation = ($fk_default_workstation > 0 ? $fk_default_workstation : 0);
			}

			$result = $line->update($user);

			if ($result > 0) {
				$this->calculateCosts();
				$this->db->commit();
				return $result;
			} else {
				$this->setErrorsFromObject($line);
				dol_syslog(get_class($this)."::addLine error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			dol_syslog(get_class($this)."::addLine status of BOM must be Draft to allow use of ->addLine()", LOG_ERR);
			return -3;
		}
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User		$user       User that delete
	 *  @param	int			$idline		Id of line to delete
	 *  @param 	int<0,1>	$notrigger  0=launch triggers after, 1=disable triggers
	 *  @return int<-2,-1>|int<1,1>		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = 0)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		$this->db->begin();

		// Fetch current line from the database and then clone the object and set it in $oldline property
		$line = new BOMLine($this->db);
		$line->fetch($idline);
		$line->fetch_optionals();

		$staticLine = clone $line;
		$line->oldcopy = $staticLine;
		$line->context = $this->context;

		$result = $line->delete($user, $notrigger);

		//Positions (rank) reordering
		foreach ($this->lines as $bl) {
			if ($bl->position > ($line->oldcopy->position)) { // move rank down
				$bl->position--;
				$bl->update($user);
			}
		}

		if ($result > 0) {
			$this->calculateCosts();
			$this->db->commit();
			return $result;
		} else {
			$this->setErrorsFromObject($line);
			dol_syslog(get_class($this)."::addLine error=".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *  Returns the reference to the following non used BOM depending on the active numbering module
	 *  defined into BOM_ADDON
	 *
	 *  @param	Product		$prod 	Object product
	 *  @return string      		BOM free reference
	 */
	public function getNextNumRef($prod)
	{
		global $langs, $conf;
		$langs->load("mrp");

		if (getDolGlobalString('BOM_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('BOM_ADDON') . ".php";
			$classname = getDolGlobalString('BOM_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/bom/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			'@phan-var-force ModeleNumRefBoms $obj';
			$numref = $obj->getNextValue($prod, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_BOM_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 *	Validate bom
	 *
	 *	@param		User		$user     	User making status change
	 *  @param		int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return  	int<-1,1>				Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$this->fetch_product();
			$num = $this->getNextNumRef($this->product);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " status = ".self::STATUS_VALIDATED.",";
		$sql .= " date_valid='".$this->db->idate($now)."',";
		$sql .= " fk_user_valid = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('BOM_VALIDATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'bom/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'bom/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'bom/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'bom/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->bom->dir_output.'/'.$oldref;
				$dirdest = $conf->bom->dir_output.'/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->bom->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
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
	 *	Set draft status
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'BOM_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'BOM_CLOSE');
	}

	/**
	 *	Reopen if canceled
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'BOM_REOPEN');
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->loadLangs(['product', 'mrp']);

		$datas = [];

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowBillOfMaterials")];
		}
		$picto = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("BillOfMaterials").'</u>';
		if (isset($this->status)) {
			$picto .= ' '.$this->getLibStatut(5);
		}
		$datas['picto'] = $picto;
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->label)) {
			$datas['label'] = '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		}
		if (!empty($this->fk_product) && $this->fk_product > 0) {
			include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$product = new Product($this->db);
			$resultFetch = $product->fetch($this->fk_product);
			if ($resultFetch > 0) {
				$datas['product'] = "<br><b>".$langs->trans("Product").'</b>: '.$product->ref.' - '.$product->label;
			}
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *	@param	int<0,2>	$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string		$option						On what the link point to ('nolink', ...)
	 *  @param	int<0,1>	$notooltip					1=Disable tooltip
	 *  @param  string		$morecss            		Add more css on link
	 *  @param  int<-1,1>	$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/bom/bom_card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowBillOfMaterials");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('bomdao'));
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
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int			$status        Id status
	 *  @param  int<0,6>	$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus)) {
			global $langs;
			//$langs->load("mrp");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatus[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return BOMLine[]|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new BOMLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, '(fk_bom:=:'.((int) $this->id).')');

		if (is_numeric($result)) {
			$this->error = $objectline->error;
			$this->errors = $objectline->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param      int<0,1>	$hidedetails    Hide details of lines
	 *  @param      int<0,1>	$hidedesc       Hide description
	 *  @param      int<0,1>	$hideref        Hide ref
	 *  @param      ?array<string,mixed>  $moreparams     Array to provide more information
	 *  @return     int<0,1>       				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("mrp");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = '';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('BOM_ADDON_PDF')) {
				$modele = getDolGlobalString('BOM_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/bom/doc/";
		if (!empty($modele)) {
			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if at least one photo is available
	 *
	 * @param  string $sdir Directory to scan
	 * @return boolean                 True if at least one photo is available, False if not
	 */
	public function is_photo_available($sdir)
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

		$sdir .= '/'.get_exdir(0, 0, 0, 0, $this, 'bom');

		$dir_osencoded = dol_osencode($sdir);
		if (file_exists($dir_osencoded)) {
			$handle = opendir($dir_osencoded);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (!utf8_check($file)) {
						$file = mb_convert_encoding($file, 'UTF-8', 'ISO-8859-1'); // To be sure data is stored in UTF8 in memory
					}
					if (dol_is_file($sdir.$file) && image_format_supported($file) >= 0) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int<1,1>
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
		$this->ref = 'BOM-123';
		$this->date_creation = dol_now() - 20000;

		return 1;
	}


	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * BOM costs calculation based on cost_price or pmp of each BOM line.
	 * Set the property ->total_cost and ->unit_cost of BOM.
	 *
	 * @return int|string	Return integer <0 if KO, >0 if OK, or printable error result from hook
	 */
	public function calculateCosts()
	{
		global $hookmanager;

		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$this->unit_cost = 0;
		$this->total_cost = 0;

		$parameters = array();
		$reshook = $hookmanager->executeHooks('calculateCostsBom', $parameters, $this); // Note that $action and $object may have been modified by hook

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		if (is_array($this->lines) && count($this->lines)) {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			$productFournisseur = new ProductFournisseur($this->db);
			$tmpproduct = new Product($this->db);

			foreach ($this->lines as &$line) {
				$tmpproduct->cost_price = 0;
				$tmpproduct->pmp = 0;
				$result = $tmpproduct->fetch($line->fk_product, '', '', '', 0, 1, 1);	// We discard selling price and language loading

				if ($tmpproduct->type == $tmpproduct::TYPE_PRODUCT) {
					if (empty($line->fk_bom_child)) {
						if ($result < 0) {
							$this->error = $tmpproduct->error;
							return -1;
						}

						$unit_cost = (float) (is_null($tmpproduct->cost_price) ? $tmpproduct->pmp : $tmpproduct->cost_price);
						if (empty($unit_cost)) {	// @phpstan-ignore-line phpstan thinks this is always false. No,if unit_cost is 0, it is not.
							if ($productFournisseur->find_min_price_product_fournisseur($line->fk_product) > 0) {
								if ($productFournisseur->fourn_remise_percent != "0") {
									$line->unit_cost = $productFournisseur->fourn_unitprice_with_discount;
								} else {
									$line->unit_cost = $productFournisseur->fourn_unitprice;
								}
							} else {
								$line->unit_cost = 0;
							}
						} else {
							$line->unit_cost = (float) price2num($unit_cost);
						}

						$line->total_cost = (float) price2num($line->qty * $line->unit_cost, 'MT');

						$this->total_cost += $line->total_cost;
					} else {
						$bom_child = new BOM($this->db);
						$res = $bom_child->fetch((int) $line->fk_bom_child);
						if ($res > 0) {
							$bom_child->calculateCosts();
							$line->childBom[] = $bom_child;
							$this->total_cost += (float) price2num($bom_child->total_cost * $line->qty, 'MT');
							$this->total_cost += $line->total_cost;
						} else {
							$this->error = $bom_child->error;
							return -2;
						}
					}
				} else {
					// Convert qty of line into hours
					require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
					$measuringUnits = new CUnits($this->db);
					$measuringUnits->fetch($line->fk_unit);

					// The unit is a unit for time, so the $measuringUnits->scale is not a power of 10, but directly the factor to change unit into seconds
					$qtyhourforline = $line->qty * (int) $measuringUnits->scale / 3600;

					if (isModEnabled('workstation') && !empty($line->fk_default_workstation)) {
						$workstation = new Workstation($this->db);
						$res = $workstation->fetch($line->fk_default_workstation);

						if ($res > 0) {
							$line->total_cost = (float) price2num($qtyhourforline * ($workstation->thm_operator_estimated + $workstation->thm_machine_estimated), 'MT');
						} else {
							$this->error = $workstation->error;
							return -3;
						}
					} else {
						$defaultdurationofservice = $tmpproduct->duration;
						$reg = array();
						$qtyhourservice = 0;
						if (preg_match('/^(\d+)([a-z]+)$/', $defaultdurationofservice, $reg)) {
							$qtyhourservice = convertDurationtoHour((float) $reg[1], $reg[2]);
						}

						if ($qtyhourservice) {
							$line->total_cost = (float) price2num($qtyhourforline / $qtyhourservice * $tmpproduct->cost_price, 'MT');
						} else {
							$line->total_cost = (float) price2num($line->qty * $tmpproduct->cost_price, 'MT');
						}
					}

					$this->total_cost += $line->total_cost;
				}
			}

			$this->total_cost = (float) price2num($this->total_cost, 'MT');

			if ($this->qty > 0) {
				$this->unit_cost = (float) price2num($this->total_cost / $this->qty, 'MU');
			} elseif ($this->qty < 0) {
				$this->unit_cost = (float) price2num($this->total_cost * $this->qty, 'MU');
			}
		}

		return 1;
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB	$db Database handler
	 * @param int		$origin_id Old product id
	 * @param int		$dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'bom_bomline'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Get Net needs by product
	 *
	 * @param array<int,array{qty:float,fk_unit:?int}>	$TNetNeeds	Array of ChildBom and infos linked to
	 * @param float										$qty		qty needed (used as a factor to produce 1 unit)
	 * @return void
	 */
	public function getNetNeeds(&$TNetNeeds = array(), $qty = 0)
	{
		if (!empty($this->lines)) {
			foreach ($this->lines as $line) {
				if (!empty($line->childBom)) {
					foreach ($line->childBom as $childBom) {
						$childBom->getNetNeeds($TNetNeeds, $line->qty * $qty);
					}
				} else {
					if (empty($TNetNeeds[$line->fk_product]['qty'])) {
						$TNetNeeds[$line->fk_product]['qty'] = 0.0;
					}
					// When using nested level (or not), the qty for needs must always use the same unit to be able to be cumulated.
					// So if unit in bom is not the same than default, we must recalculate qty after units comparisons.
					$TNetNeeds[$line->fk_product]['fk_unit'] = $line->fk_unit;
					$TNetNeeds[$line->fk_product]['qty'] += $line->qty * $qty;
				}
			}
		}
	}

	/**
	 * Get/add Net needs Tree by product or bom
	 *
	 * @param array<int,array{product:array,bom:BOM,parentid:int,qty:float,level:int,fk_unit:?int}> 	$TNetNeeds 	Array of ChildBom and infos linked to
	 * @param float			$qty       qty needed (used as a factor to produce 1 unit)
	 * @param int<0,1000>  	$level     level of recursivity
	 * @return void
	 */
	public function getNetNeedsTree(&$TNetNeeds = array(), $qty = 0, $level = 0)
	{
		if (!empty($this->lines)) {
			foreach ($this->lines as $line) {
				if (!empty($line->childBom)) {
					foreach ($line->childBom as $childBom) {
						$TNetNeeds[$childBom->id]['bom'] = $childBom;
						$TNetNeeds[$childBom->id]['parentid'] = $this->id;
						// When using nested level (or not), the qty for needs must always use the same unit to be able to be cumulated.
						// So if unit in bom is not the same than default, we must recalculate qty after units comparisons.
						//$TNetNeeds[$childBom->id]['fk_unit'] = $line->fk_unit;
						$TNetNeeds[$childBom->id]['qty'] = $line->qty * $qty;
						$TNetNeeds[$childBom->id]['level'] = $level;
						$childBom->getNetNeedsTree($TNetNeeds, $line->qty * $qty, $level + 1);
					}
				} else {
					// When using nested level (or not), the qty for needs must always use the same unit to be able to be cumulated.
					// So if unit in bom is not the same than default, we must recalculate qty after units comparisons.
					if (!isset($TNetNeeds[$this->id]['product'])) {
						$TNetNeeds[$this->id]['product'] = array();
					}
					if (!isset($TNetNeeds[$this->id]['product'][$line->fk_product])) {
						$TNetNeeds[$this->id]['product'][$line->fk_product] = array();
					}
					$TNetNeeds[$this->id]['product'][$line->fk_product]['fk_unit'] = $line->fk_unit;
					if (!isset($TNetNeeds[$this->id]['product'][$line->fk_product]['qty'])) {
						$TNetNeeds[$this->id]['product'][$line->fk_product]['qty'] = 0.0;
					}
					$TNetNeeds[$this->id]['product'][$line->fk_product]['qty'] += $line->qty * $qty;
					$TNetNeeds[$this->id]['product'][$line->fk_product]['level'] = $level;
				}
			}
		}
	}

	/**
	 * Recursively retrieves all parent bom in the tree that leads to the $bom_id bom
	 *
	 * @param 	BOM[]		$TParentBom		We put all found parent bom in $TParentBom
	 * @param 	int			$bom_id			ID of bom from which we want to get parent bom ids
	 * @param 	int<0,1000>	$level			Protection against infinite loop
	 * @return 	void
	 */
	public function getParentBomTreeRecursive(&$TParentBom, $bom_id = 0, $level = 1)
	{

		// Protection against infinite loop
		if ($level > 1000) {
			return;
		}

		if (empty($bom_id)) {
			$bom_id = $this->id;
		}

		$sql = 'SELECT l.fk_bom, b.label
				FROM '.MAIN_DB_PREFIX.'bom_bomline l
				INNER JOIN '.MAIN_DB_PREFIX.$this->table_element.' b ON b.rowid = l.fk_bom
				WHERE fk_bom_child = '.((int) $bom_id);

		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			while ($res = $this->db->fetch_object($resql)) {
				$TParentBom[$res->fk_bom] = $res->fk_bom;
				$this->getParentBomTreeRecursive($TParentBom, $res->fk_bom, $level + 1);
			}
		}
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param	string		    $option			Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	array{prod:?Product,selected:int<-1,1>}	$arraydata	Array of data
	 *  @return	string							HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $db,$langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : '').'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		$arrayofkeyval = $this->fields['bomtype']['arrayofkeyval'] ?? null;
		if (!empty($arrayofkeyval)) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$langs->trans("Type").' : </span>';
			if ($this->bomtype == 0) {
				$return .= '<span class="info-box-label">'.$arrayofkeyval[0].'</span>';
			} else {
				$return .= '<span class="info-box-label">'.$arrayofkeyval[1].'</span>';
			}
		}
		if (!empty($arraydata['prod'])) {
			$prod = $arraydata['prod'];
			$return .= '<br><span class="info-box-label">'.$prod->getNomUrl(1).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}

		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
