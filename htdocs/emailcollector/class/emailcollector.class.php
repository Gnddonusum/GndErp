<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW				<mdeweerd@users.noreply.github.com>
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
 *    \file        htdocs/emailcollector/class/emailcollector.class.php
 *    \ingroup     emailcollector
 *    \brief       This file is a CRUD class file for EmailCollector (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
include_once DOL_DOCUMENT_ROOT .'/emailcollector/lib/emailcollector.lib.php';

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

require_once DOL_DOCUMENT_ROOT .'/comm/propal/class/propal.class.php';                   // Customer Proposal
require_once DOL_DOCUMENT_ROOT .'/commande/class/commande.class.php';                    // Sale Order
require_once DOL_DOCUMENT_ROOT .'/compta/facture/class/facture.class.php';               // Customer Invoice
require_once DOL_DOCUMENT_ROOT .'/contact/class/contact.class.php';                      // Contact / Address
require_once DOL_DOCUMENT_ROOT .'/expedition/class/expedition.class.php';                // Shipping / Delivery
require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.commande.class.php';           // Purchase Order
require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.facture.class.php';            // Purchase Invoice
require_once DOL_DOCUMENT_ROOT .'/projet/class/project.class.php';                       // Project
require_once DOL_DOCUMENT_ROOT .'/reception/class/reception.class.php';                  // Reception
require_once DOL_DOCUMENT_ROOT .'/recruitment/class/recruitmentcandidature.class.php';   // Recruiting
require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';                      // Third-Party
require_once DOL_DOCUMENT_ROOT .'/supplier_proposal/class/supplier_proposal.class.php';  // Supplier Proposal
require_once DOL_DOCUMENT_ROOT .'/ticket/class/ticket.class.php';                        // Ticket
//require_once DOL_DOCUMENT_ROOT .'/expensereport/class/expensereport.class.php';        // Expense Report
//require_once DOL_DOCUMENT_ROOT .'/holiday/class/holiday.class.php';                    // Holidays (leave request)


use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidWhereQueryCriteriaException;
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

/**
 * Class for EmailCollector
 */
class EmailCollector extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'emailcollector';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'emailcollector_emailcollector';

	/**
	 * @var string String with name of icon for emailcollector. Must be the part after the 'object_' into object_emailcollector.png
	 */
	public $picto = 'email';

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_emailcollector';

	/**
	 * @var array<string, array<string>>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var string[]	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('emailcollector_emailcollectorfilter', 'emailcollector_emailcollectoraction');


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'visible' => 2, 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'index' => 1),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => '1', 'notnull' => 1, 'index' => 1, 'position' => 20),
		'ref'           => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1, 'help' => 'Example: MyCollector1', 'csslist' => 'tdoverflowmax200'),
		'label'         => array('type' => 'varchar(255)', 'label' => 'Label', 'visible' => 1, 'enabled' => 1, 'position' => 30, 'notnull' => -1, 'searchall' => 1, 'help' => 'Example: My Email collector', 'csslist' => 'tdoverflowmax150', 'tdcss' => 'titlefieldmiddle'),
		'description'   => array('type' => 'text', 'label' => 'Description', 'visible' => -1, 'enabled' => 1, 'position' => 60, 'notnull' => -1, 'cssview' => 'small', 'csslist' => 'small tdoverflowmax200'),
		'host'          => array('type' => 'varchar(255)', 'label' => 'EMailHost', 'visible' => 1, 'enabled' => 1, 'position' => 90, 'notnull' => 1, 'searchall' => 1, 'comment' => "IMAP server", 'help' => 'Example: imap.gmail.com', 'csslist' => 'tdoverflowmax125'),
		'port'          => array('type' => 'varchar(10)', 'label' => 'EMailHostPort', 'visible' => 1, 'enabled' => 1, 'position' => 91, 'notnull' => 1, 'searchall' => 0, 'comment' => "IMAP server port", 'help' => 'Example: 993', 'csslist' => 'tdoverflowmax50', 'default' => '993'),
		'imap_encryption'  => array('type' => 'varchar(16)', 'label' => 'ImapEncryption', 'visible' => -1, 'enabled' => 1, 'position' => 92, 'searchall' => 0, 'comment' => "IMAP encryption", 'help' => 'ImapEncryptionHelp', 'arrayofkeyval' => array('ssl' => 'SSL', 'tls' => 'TLS', 'notls' => 'NOTLS'), 'default' => 'ssl'),
		'hostcharset'   => array('type' => 'varchar(16)', 'label' => 'HostCharset', 'visible' => -1, 'enabled' => 1, 'position' => 93, 'notnull' => 0, 'searchall' => 0, 'comment' => "IMAP server charset", 'help' => 'Example: "UTF-8" (May be "US-ASCII" with some Office365)', 'default' => 'UTF-8'),
		'norsh'  => array('type' => 'integer', 'label' => 'NoRSH', 'visible' => -1, 'enabled' => "!getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')", 'position' => 94, 'searchall' => 0, 'help' => 'NoRSHHelp', 'arrayofkeyval' => array(0 => 'No', 1 => 'Yes'), 'default' => '0'),
		'acces_type'     => array('type' => 'integer', 'label' => 'AuthenticationMethod', 'visible' => -1, 'enabled' => "getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')", 'position' => 101, 'notnull' => 1, 'index' => 1, 'comment' => "IMAP login type", 'arrayofkeyval' => array(0 => 'loginPassword', 1 => 'oauthToken'), 'default' => '0', 'help' => ''),
		'login'         => array('type' => 'varchar(128)', 'label' => 'Login', 'visible' => -1, 'enabled' => 1, 'position' => 102, 'notnull' => -1, 'index' => 1, 'comment' => "IMAP login", 'help' => 'Example: myaccount@gmail.com'),
		'password'      => array('type' => 'password', 'label' => 'Password', 'visible' => -1, 'enabled' => "1", 'position' => 103, 'notnull' => -1, 'comment' => "IMAP password", 'help' => 'WithGMailYouCanCreateADedicatedPassword'),
		'oauth_service' => array('type' => 'varchar(128)', 'label' => 'oauthService', 'visible' => -1, 'enabled' => "getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')", 'position' => 104, 'notnull' => 0, 'index' => 1, 'comment' => "IMAP login oauthService", 'arrayofkeyval' => array(), 'help' => 'TokenMustHaveBeenCreated'),
		'source_directory' => array('type' => 'varchar(255)', 'label' => 'MailboxSourceDirectory', 'visible' => -1, 'enabled' => 1, 'position' => 109, 'notnull' => 1, 'default' => 'Inbox', 'csslist' => 'tdoverflowmax100', 'help' => 'Example: INBOX, [Gmail]/Spam, [Gmail]/Draft, [Gmail]/Brouillons, [Gmail]/Sent Mail, [Gmail]/Messages envoyés, ...'),
		'target_directory' => array('type' => 'varchar(255)', 'label' => 'MailboxTargetDirectory', 'visible' => 1, 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'csslist' => 'tdoverflowmax100', 'help' => "EmailCollectorTargetDir"),
		'maxemailpercollect' => array('type' => 'integer', 'label' => 'MaxEmailCollectPerCollect', 'visible' => -1, 'enabled' => 1, 'position' => 111, 'default' => '50'),
		'datelastresult' => array('type' => 'datetime', 'label' => 'DateLastCollectResult', 'visible' => 1, 'enabled' => '$action != "create" && $action != "edit"', 'position' => 121, 'notnull' => -1, 'csslist' => 'nowraponall'),
		'codelastresult' => array('type' => 'varchar(16)', 'label' => 'CodeLastResult', 'visible' => 1, 'enabled' => '$action != "create" && $action != "edit"', 'position' => 122, 'notnull' => -1,),
		'lastresult' => array('type' => 'varchar(255)', 'label' => 'LastResult', 'visible' => 1, 'enabled' => '$action != "create" && $action != "edit"', 'position' => 123, 'notnull' => -1, 'cssview' => 'small', 'csslist' => 'small tdoverflowmax200'),
		'datelastok' => array('type' => 'datetime', 'label' => 'DateLastcollectResultOk', 'visible' => 1, 'enabled' => '$action != "create"', 'position' => 125, 'notnull' => -1, 'csslist' => 'nowraponall'),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'visible' => 0, 'enabled' => 1, 'position' => 61, 'notnull' => -1,),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'visible' => 0, 'enabled' => 1, 'position' => 62, 'notnull' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'visible' => -2, 'enabled' => 1, 'position' => 500, 'notnull' => 1,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'visible' => -2, 'enabled' => 1, 'position' => 501, 'notnull' => 1,),
		//'date_validation' => array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'visible' => -2, 'enabled' => 1, 'position' => 510, 'notnull' => 1,),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'visible' => -2, 'enabled' => 1, 'position' => 511, 'notnull' => -1,),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'visible' => -2, 'enabled' => 1, 'position' => 1000, 'notnull' => -1,),
		'status' => array('type' => 'integer', 'label' => 'Status', 'visible' => 1, 'enabled' => 1, 'position' => 1000, 'notnull' => 1, 'default' => '0', 'index' => 1, 'arrayofkeyval' => array(0 => 'Inactive', 1 => 'Active'))
	);


	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var string import key
	 */
	public $import_key;

	/**
	 * @var string
	 */
	public $host;
	/**
	 * @var string
	 */
	public $port;
	/**
	 * @var string
	 */
	public $hostcharset;
	/**
	 * @var string
	 */
	public $login;
	/**
	 * @var string
	 */
	public $password;
	/**
	 * @var int
	 */
	public $acces_type;
	/**
	 * @var string
	 */
	public $oauth_service;
	/**
	 * @var string
	 */
	public $imap_encryption;
	/**
	 * @var int<0,1>
	 */
	public $norsh;
	/**
	 * @var string
	 */
	public $source_directory;
	/**
	 * @var string
	 */
	public $target_directory;
	/**
	 * @var int
	 */
	public $maxemailpercollect;

	/**
	 * @var int|string
	 */
	public $datelastresult;

	/**
	 * @var string
	 */
	public $codelastresult;
	/**
	 * @var string
	 */
	public $lastresult;
	/**
	 * @var int|string
	 */
	public $datelastok;
	// END MODULEBUILDER PROPERTIES

	/**
	 * @var array<array{id:int,status:int,rulevalue:string,type:'to'|'from'|'bcc'|'cc'|'subject'|'body'|'header'|'seene'|'unseen'|'unanswered'|'answered'|'smaller'|'larger'|'withtrackingidinmsgid'|'withouttrackingidinmsgid'|'withtrackingid'|'withouttrackingid'|'isanwser'|'isnotanswer'|'replyto'}>
	 */
	public $filters;
	/**
	 * @var array<array{type:string,actionparam:string,status:int,position:int}>
	 */
	public $actions;

	/**
	 * @var string
	 */
	public $debuginfo;

	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;


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
		$this->isextrafieldmanaged = 0;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// List of oauth services
		$oauthservices = array();

		foreach ($conf->global as $key => $val) {
			if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
				$key = preg_replace('/^OAUTH_/', '', $key);
				$key = preg_replace('/_ID$/', '', $key);
				if (preg_match('/^.*-/', $key)) {
					$name = preg_replace('/^.*-/', '', $key);
				} else {
					$name = $langs->trans("NoName");
				}
				$provider = preg_replace('/-.*$/', '', $key);
				$provider = ucfirst(strtolower($provider));

				$oauthservices[$key] = $name." (".$provider.")";
			}
		}

		$this->fields['oauth_service']['arrayofkeyval'] = $oauthservices;

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
	 * @param  User		$user		User that creates
	 * @param  int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return int					Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs;

		// Check parameters
		if ($this->host && preg_match('/^http:/i', trim($this->host))) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorHostMustNotStartWithHttp", $this->host);
			return -1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		$this->password = dolEncrypt($this->password);

		$id = $this->createCommon($user, $notrigger);

		$this->password = dolDecrypt($this->password);

		if (is_array($this->filters) && count($this->filters)) {
			$emailcollectorfilter = new EmailCollectorFilter($this->db);

			foreach ($this->filters as $filter) {
				$emailcollectorfilter->type = $filter['type'];
				$emailcollectorfilter->rulevalue = $filter['rulevalue'];
				$emailcollectorfilter->fk_emailcollector = $this->id;
				$emailcollectorfilter->status = $filter['status'];

				$emailcollectorfilter->create($user);
			}
		}

		if (is_array($this->actions) && count($this->actions)) {
			$emailcollectoroperation = new EmailCollectorAction($this->db);

			foreach ($this->actions as $operation) {
				$emailcollectoroperation->type = $operation['type'];
				$emailcollectoroperation->actionparam = $operation['actionparam'];
				$emailcollectoroperation->fk_emailcollector = $this->id;
				$emailcollectoroperation->status = $operation['status'];
				$emailcollectoroperation->position = $operation['position'];

				$emailcollectoroperation->create($user);
			}
		}

		return $id;
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user				User that creates
	 * @param  	int 	$fromid				Id of object to clone
	 * @return 	EmailCollector|int<-1,-1>	New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		$object->fetchFilters(); // Rules
		$object->fetchActions(); // Operations

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);
		unset($object->password);
		unset($object->lastresult);
		unset($object->codelastresult);
		unset($object->datelastresult);
		unset($object->datelastok);
		unset($object->debuginfo);

		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		$object->label = $langs->trans("CopyOf")." ".$object->label;
		if (empty($object->host)) {
			$object->host = 'imap.example.com';
		}
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
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
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

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
	 * @param int		$id		Id object
	 * @param ?string	$ref	Ref
	 * @return int				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		$this->password = dolDecrypt($this->password);

		//if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	/*
	 public function fetchLines()
	 {
	 $this->lines=array();

	 // Load lines with object EmailCollectorLine

	 return count($this->lines)?1:0;
	 }
	 */

	/**
	 * Fetch all account and load objects into an array
	 *
	 * @param   User    $user           User
	 * @param   int     $activeOnly     filter if active
	 * @param   string  $sortfield      field for sorting
	 * @param   string  $sortorder      sorting order
	 * @param   int     $limit          sort limit
	 * @param   int     $page           page to start on
	 * @return  EmailCollector[]		Array with key => EmailCollector object
	 */
	public function fetchAll(User $user, $activeOnly = 0, $sortfield = 's.rowid', $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		global $langs;

		$obj_ret = array();

		$sql = "SELECT s.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector as s";
		$sql .= ' WHERE s.entity IN ('.getEntity('emailcollector').')';
		if ($activeOnly) {
			$sql .= " AND s.status = 1";
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
				$emailcollector_static = new EmailCollector($this->db);
				if ($emailcollector_static->fetch($obj->rowid)) {
					$obj_ret[] = $emailcollector_static;
				}
				$i++;
			}
		} else {
			$this->errors[] = 'EmailCollector::fetchAll Error when retrieve emailcollector list';
			dol_syslog('EmailCollector::fetchAll Error when retrieve emailcollector list', LOG_ERR);
			$ret = -1;
		}
		if (!count($obj_ret)) {
			dol_syslog('EmailCollector::fetchAll No emailcollector found', LOG_DEBUG);
		}

		return $obj_ret;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $langs;

		// Check parameters
		if ($this->host && preg_match('/^http:/i', trim($this->host))) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorHostMustNotStartWithHttp", $this->host);
			return -1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		$this->password = dolEncrypt($this->password);

		$result = $this->updateCommon($user, $notrigger);

		$this->password = dolDecrypt($this->password);

		return $result;
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user       User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $action, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = '<u>'.$langs->trans("EmailCollector").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = DOL_URL_ROOT.'/admin/emailcollector_card.php?id='.$this->id;

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
				$label = $langs->trans("ShowEmailCollector");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ' title="'.dolPrintHTMLForAttribute($label).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
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
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		$hookmanager->initHooks(array('emailcollectordao'));
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
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status5';
		if ($status == self::STATUS_ENABLED) {
			$statusType = 'status4';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Charge les information d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		$this->host = 'localhost';
		$this->login = 'alogin';

		return $this->initAsSpecimenCommon();
	}

	/**
	 * Fetch filters
	 *
	 * @return 	int		Return integer <0 if KO, >0 if OK
	 * @see fetchActions()
	 */
	public function fetchFilters()
	{
		$this->filters = array();

		$sql = 'SELECT rowid, type, rulevalue, status';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectorfilter';
		$sql .= ' WHERE fk_emailcollector = '.((int) $this->id);
		//$sql.= ' ORDER BY position';

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->filters[$obj->rowid] = array('id' => $obj->rowid, 'type' => $obj->type, 'rulevalue' => $obj->rulevalue, 'status' => $obj->status);
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		return 1;
	}

	/**
	 * Fetch actions
	 *
	 * @return 	int		Return integer <0 if KO, >0 if OK
	 * @see fetchFilters()
	 */
	public function fetchActions()
	{
		$this->actions = array();

		$sql = 'SELECT rowid, type, actionparam, status';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectoraction';
		$sql .= ' WHERE fk_emailcollector = '.((int) $this->id);
		$sql .= ' ORDER BY position';

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->actions[$obj->rowid] = array('id' => $obj->rowid, 'type' => $obj->type, 'actionparam' => $obj->actionparam, 'status' => $obj->status);
				$i++;
			}
			$this->db->free($resql);

			return 1;
		} else {
			dol_print_error($this->db);

			return -1;
		}
	}


	/**
	 * Return the connectstring to use with IMAP connection function
	 *
	 * @return string
	 */
	public function getConnectStringIMAP()
	{
		// Connect to IMAP
		$flags = '/service=imap'; // IMAP
		if (getDolGlobalString('IMAP_FORCE_TLS')) {
			$flags .= '/tls';
		} elseif (empty($this->imap_encryption) || ($this->imap_encryption == 'ssl' && getDolGlobalString('IMAP_FORCE_NOSSL'))) {
			$flags .= '';
		} else {
			$flags .= '/' . $this->imap_encryption;
		}

		$flags .= '/novalidate-cert';
		//$flags.='/readonly';
		//$flags.='/debug';
		if (!empty($this->norsh) || getDolGlobalString('IMAP_FORCE_NORSH')) {
			$flags .= '/norsh';
		}
		//Used in shared mailbox from Office365
		if (!empty($this->login) && strpos($this->login, '/') != false) {
			$partofauth = explode('/', $this->login);
			$flags .= '/authuser='.$partofauth[0].'/user='.$partofauth[1];
		}

		$connectstringserver = '{'.$this->host.':'.$this->port.$flags.'}';

		return $connectstringserver;
	}

	/**
	 * Convert str to UTF-7 imap. Used to forge mailbox names.
	 *
	 * @param 	string $str			String to encode
	 * @return 	string|false		Encoded string, or false if error
	 */
	public function getEncodedUtf7($str)
	{
		if (function_exists('mb_convert_encoding')) {
			// change spaces by entropy because mb_convert fail with spaces
			$str = preg_replace("/ /", "xxxSPACExxx", $str);		// the replacement string must be valid in utf7 so _ can't be used
			$str = preg_replace("/_/", "xxxUNDERSCORExxx", $str); // encode underscore to avoid encoding issues with mb_convert
			$str = preg_replace("/\[Gmail\]/", "xxxGMAILxxx", $str);	// the replacement string must be valid in utf7 so _ can't be used
			// if mb_convert work
			if ($str = mb_convert_encoding($str, "UTF-7")) {
				// change characters
				$str = preg_replace("/\+A/", "&A", $str);
				$str = preg_replace("/xxxUNDERSCORExxx/", "_", $str);
				// change to spaces again
				$str = preg_replace("/xxxSPACExxx/", " ", $str);
				// change to [Gmail] again
				$str = preg_replace("/xxxGMAILxxx/", "[Gmail]", $str);
				return $str;
			} else {
				// print error and return false
				$this->error = "error: is not possible to encode this string '".$str."'";
				return false;
			}
		} else {
			return $str;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doCollect()
	{
		global $user;

		$nberror = 0;

		$arrayofcollectors = $this->fetchAll($user, 1);

		// Loop on each collector
		foreach ($arrayofcollectors as $emailcollector) {
			$result = $emailcollector->doCollectOneCollector(0);
			dol_syslog("doCollect result = ".$result." for emailcollector->id = ".$emailcollector->id);

			$this->error .= 'EmailCollector ID '.$emailcollector->id.':'.$emailcollector->error.'<br>';
			if (!empty($emailcollector->errors)) {
				$this->error .= implode('<br>', $emailcollector->errors);
			}
			$this->output .= 'EmailCollector ID '.$emailcollector->id.': '.$emailcollector->lastresult.'<br>';
		}

		return $nberror;
	}

	/**
	 * overwitePropertiesOfObject
	 *
	 * @param	Object	$object	Current object (stdClass) we will set ->properties
	 * @param	string	$actionparam	Action parameters
	 * @param	string	$messagetext	Body
	 * @param	string	$subject		Subject
	 * @param   string  $header         Header
	 * @param	string	$operationslog	String with logs of operations done
	 * @return	int						0=OK, Nb of error if error
	 */
	private function overwritePropertiesOfObject(&$object, $actionparam, $messagetext, $subject, $header, &$operationslog)
	{
		global $conf, $langs;

		$errorforthisaction = 0;

		// set output lang
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') /* && empty($newlang) */ && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = !empty($object->thirdparty->default_lang) ? $object->thirdparty->default_lang : $newlang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// Overwrite values with values extracted from source email
		// $this->actionparam = 'opportunity_status=123;abc=EXTRACT:BODY:....'
		$arrayvaluetouse = dolExplodeIntoArray($actionparam, '(\n\r|\r|\n|;)', '=');

		$tmp = array();

		// Loop on each property set into actionparam
		foreach ($arrayvaluetouse as $propertytooverwrite => $valueforproperty) {
			$tmpclass = '';
			$tmpproperty = '';
			$tmparray = explode('.', $propertytooverwrite);
			if (count($tmparray) == 2) {
				$tmpclass = $tmparray[0];
				$tmpproperty = $tmparray[1];
			} else {
				$tmpproperty = $tmparray[0];
			}
			if ($tmpclass && ($tmpclass != $object->element)) {
				continue; // Property is for another type of object
			}

			//if (property_exists($object, $tmpproperty) || preg_match('/^options_/', $tmpproperty))
			if ($tmpproperty) {
				$sourcestring = '';
				$sourcefield = '';
				$regexstring = '';
				//$transformationstring='';
				$regforregex = array();
				if (preg_match('/^EXTRACT:([a-zA-Z0-9_]+):(.*):([^:])$/', $valueforproperty, $regforregex)) {
					$sourcefield = $regforregex[1];
					$regexstring = $regforregex[2];
					//$transofrmationstring=$regforregex[3];
				} elseif (preg_match('/^EXTRACT:([a-zA-Z0-9_]+):(.*)$/', $valueforproperty, $regforregex)) {
					$sourcefield = $regforregex[1];
					$regexstring = $regforregex[2];
				}

				if (!empty($sourcefield) && !empty($regexstring)) {
					if (strtolower($sourcefield) == 'body') {
						$sourcestring = $messagetext;
					} elseif (strtolower($sourcefield) == 'subject') {
						$sourcestring = $subject;
					} elseif (strtolower($sourcefield) == 'header') {
						$sourcestring = $header;
					}

					if ($sourcestring) {
						$regforval = array();
						$regexoptions = '';
						if (strtolower($sourcefield) == 'body') {
							$regexoptions = 'ms'; // The m means ^ and $ char is valid at each new line. The s means the char '.' is valid for new lines char too
						}
						if (strtolower($sourcefield) == 'header') {
							$regexoptions = 'm'; // The m means ^ and $ char is valid at each new line.
						}

						//var_dump($tmpproperty.' - '.$regexstring.' - '.$regexoptions.' - '.$sourcestring);
						if (preg_match('/'.$regexstring.'/'.$regexoptions, $sourcestring, $regforval)) {
							// Overwrite param $tmpproperty
							$valueextracted = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;
							if (strtolower($sourcefield) == 'header') {		// extract from HEADER
								if (preg_match('/^options_/', $tmpproperty)) {
									$object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $this->decodeSMTPSubject($valueextracted);
								} else {
									if (property_exists($object, $tmpproperty)) {
										$object->$tmpproperty = $this->decodeSMTPSubject($valueextracted);
									} else {
										$tmp[$tmpproperty] = $this->decodeSMTPSubject($valueextracted);
									}
								}
							} else {	// extract from BODY
								if (preg_match('/^options_/', $tmpproperty)) {
									$object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $this->decodeSMTPSubject($valueextracted);
								} else {
									if (property_exists($object, $tmpproperty)) {
										$object->$tmpproperty = $this->decodeSMTPSubject($valueextracted);
									} else {
										$tmp[$tmpproperty] = $this->decodeSMTPSubject($valueextracted);
									}
								}
							}
							if (preg_match('/^options_/', $tmpproperty)) {
								$operationslog .= '<br>Regex /'.dol_escape_htmltag($regexstring).'/'.dol_escape_htmltag($regexoptions).' into '.strtolower($sourcefield).' -> found '.dol_escape_htmltag(dol_trunc($object->array_options[preg_replace('/^options_/', '', $tmpproperty)], 128));
							} else {
								if (property_exists($object, $tmpproperty)) {
									$operationslog .= '<br>Regex /'.dol_escape_htmltag($regexstring).'/'.dol_escape_htmltag($regexoptions).' into '.strtolower($sourcefield).' -> found '.dol_escape_htmltag(dol_trunc($object->$tmpproperty, 128));
								} else {
									$operationslog .= '<br>Regex /'.dol_escape_htmltag($regexstring).'/'.dol_escape_htmltag($regexoptions).' into '.strtolower($sourcefield).' -> found '.dol_escape_htmltag(dol_trunc($tmp[$tmpproperty], 128));
								}
							}
						} else {
							// Regex not found
							if (property_exists($object, $tmpproperty)) {
								$object->$tmpproperty = null;
							} else {
								$tmp[$tmpproperty] = null;
							}

							$operationslog .= '<br>Regex /'.dol_escape_htmltag($regexstring).'/'.dol_escape_htmltag($regexoptions).' into '.strtolower($sourcefield).' -> not found, so property '.dol_escape_htmltag($tmpproperty).' is set to null.';
						}
					} else {
						// Nothing can be done for this param
						$errorforthisaction++;
						$this->error = 'The extract rule to use to overwrite properties has on an unknown source (must be HEADER, SUBJECT or BODY)';
						$this->errors[] = $this->error;

						$operationslog .= '<br>'.$this->error;
					}
				} elseif (preg_match('/^(SET|SETIFEMPTY):(.*)$/', $valueforproperty, $regforregex)) {
					$valuecurrent = '';
					if (preg_match('/^options_/', $tmpproperty)) {
						$valuecurrent = $object->array_options[preg_replace('/^options_/', '', $tmpproperty)];
					} else {
						if (property_exists($object, $tmpproperty)) {
							$valuecurrent = $object->$tmpproperty;
						} else {
							// False positive @phan-suppress-next-line PhanTypeInvalidDimOffset
							$valuecurrent = $tmp[$tmpproperty];
						}
					}

					if ($regforregex[1] == 'SET' || empty($valuecurrent)) {
						$valuetouse = $regforregex[2];
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
						complete_substitutions_array($substitutionarray, $outputlangs, $object);
						$matcharray = array();
						preg_match_all('/__([a-z0-9]+(?:_[a-z0-9]+)?)__/i', $valuetouse, $matcharray);
						//var_dump($tmpproperty.' - '.$object->$tmpproperty.' - '.$valuetouse); var_dump($matcharray);
						if (is_array($matcharray[1])) {    // $matcharray[1] is an array with the list of substitution key found without the __X__ syntax into the SET entry
							foreach ($matcharray[1] as $keytoreplace) {
								if ($keytoreplace) {
									if (preg_match('/^options_/', $keytoreplace)) {
										$substitutionarray['__'.$keytoreplace.'__'] = $object->array_options[preg_replace('/^options_/', '', $keytoreplace)];
									} else {
										if (property_exists($object, $keytoreplace)) {
											$substitutionarray['__'.$keytoreplace.'__'] = $object->$keytoreplace;
										} else {
											// False positive @phan-suppress-next-line PhanTypeInvalidDimOffset
											$substitutionarray['__'.$keytoreplace.'__'] = $tmp[$keytoreplace];
										}
									}
								}
							}
						}
						//var_dump($substitutionarray);
						//dol_syslog('substitutionarray='.var_export($substitutionarray, true));

						$valuetouse = make_substitutions($valuetouse, $substitutionarray);
						if (preg_match('/^options_/', $tmpproperty)) {
							$object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $valuetouse;

							$operationslog .= '<br>Set value '.dol_escape_htmltag($valuetouse).' into object->array_options['.dol_escape_htmltag(preg_replace('/^options_/', '', $tmpproperty)).']';
						} else {
							if (property_exists($object, $tmpproperty)) {
								$object->$tmpproperty = $valuetouse;
							} else {
								$tmp[$tmpproperty] = $valuetouse;
							}

							$operationslog .= '<br>Set value '.dol_escape_htmltag($valuetouse).' into object->'.dol_escape_htmltag($tmpproperty);
						}
					}
				} else {
					$errorforthisaction++;
					$this->error = 'Bad syntax for description of action parameters: '.$actionparam;
					$this->errors[] = $this->error;
				}
			}
		}

		return $errorforthisaction;
	}

	/**
	 * Execute collect for current collector loaded previously with fetch.
	 *
	 * @param	int<0,2>	$mode	0=Mode production, 1=Mode test (read IMAP and try SQL update then rollback), 2=Mode test with no SQL updates
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function doCollectOneCollector($mode = 0)
	{
		global $db, $conf, $langs, $user;
		global $hookmanager;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			require_once DOL_DOCUMENT_ROOT.'/includes/webklex/php-imap/vendor/autoload.php';
		}

		dol_syslog("EmailCollector::doCollectOneCollector start for id=".$this->id." - ".$this->ref, LOG_INFO);

		$langs->loadLangs(array("project", "companies", "mails", "errors", "ticket", "agenda", "commercial"));

		$error = 0;
		$this->output = '';
		$this->error = '';
		$this->debuginfo = '';

		$search = '';
		$searchhead = '';
		$searchfilterdoltrackid = 0;
		$searchfilternodoltrackid = 0;
		$searchfilterisanswer = 0;
		$searchfilterisnotanswer = 0;
		$searchfilterreplyto = 0;
		$searchfilterexcludebodyarray = array();
		$searchfilterexcludesubjectarray = array();
		$operationslog = '';
		$rulesreplyto = array();
		$connectstringsource = '';
		$connectstringtarget = '';
		$connection = false;
		$arrayofemail = array();

		$now = dol_now();
		$datelastok = $now;

		if (empty($this->host)) {
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('EMailHost'));
			return -1;
		}
		if (empty($this->login)) {
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Login'));
			return -1;
		}
		if (empty($this->source_directory)) {
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailboxSourceDirectory'));
			return -1;
		}

		$client = null;

		$sourcedir = $this->source_directory;
		$targetdir = ($this->target_directory ? $this->target_directory : ''); // Can be '[Gmail]/Trash' or 'mytag'

		$this->fetchFilters();
		$this->fetchActions();

		$sourcedir = $this->source_directory;
		$targetdir = ($this->target_directory ? $this->target_directory : ''); // Can be '[Gmail]/Trash' or 'mytag'

		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			if ($this->acces_type == 1) {
				// Mode OAUth2 (access_type == 1) with PHP-IMAP
				$this->debuginfo .= 'doCollectOneCollector is using method MAIN_IMAP_USE_PHPIMAP=1, access_type=1 (OAUTH2)<br>';

				require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';

				$supportedoauth2array = getSupportedOauth2Array();

				$keyforsupportedoauth2array = $this->oauth_service;
				if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
					$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
				} else {
					$keyforprovider = '';
				}
				$keyforsupportedoauth2array = preg_replace('/-.*$/', '', strtoupper($keyforsupportedoauth2array));
				$keyforsupportedoauth2array = 'OAUTH_'.$keyforsupportedoauth2array.'_NAME';

				if (!empty($supportedoauth2array)) {
					$nameofservice = ucfirst(strtolower(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']));
					$nameofservice .= ($keyforprovider ? '-'.$keyforprovider : '');
					$OAUTH_SERVICENAME = $nameofservice;
				} else {
					$OAUTH_SERVICENAME = 'Unknown';
				}

				$keyforparamtenant = 'OAUTH_'.strtoupper(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']).($keyforprovider ? '-'.$keyforprovider : '').'_TENANT';

				require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
				//$debugtext = "Host: ".$this->host."<br>Port: ".$this->port."<br>Login: ".$this->login."<br>Password: ".$this->password."<br>access type: ".$this->acces_type."<br>oauth service: ".$this->oauth_service."<br>Max email per collect: ".$this->maxemailpercollect;
				//dol_syslog($debugtext);

				$token = '';

				$storage = new DoliStorage($db, $conf, $keyforprovider, getDolGlobalString($keyforparamtenant));

				try {
					$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);

					$expire = true;
					// TODO
					// Is token expired or will token expire in the next 30 seconds
					// if (is_object($tokenobj)) {
					// 	$expire = ($tokenobj->getEndOfLife() !== -9002 && $tokenobj->getEndOfLife() !== -9001 && time() > ($tokenobj->getEndOfLife() - 30));
					// }
					// Token expired so we refresh it
					if (is_object($tokenobj) && $expire) {
						$this->debuginfo .= 'Refresh token '.$OAUTH_SERVICENAME.'<br>';
						$credentials = new Credentials(
							getDolGlobalString('OAUTH_'.$this->oauth_service.'_ID'),
							getDolGlobalString('OAUTH_'.$this->oauth_service.'_SECRET'),
							getDolGlobalString('OAUTH_'.$this->oauth_service.'_URLCALLBACK')
						);
						$serviceFactory = new \OAuth\ServiceFactory();
						$oauthname = explode('-', $OAUTH_SERVICENAME);
						// ex service is Google-Emails we need only the first part Google

						$scopes = array();
						if (preg_match('/^Microsoft/', $OAUTH_SERVICENAME)) {
							//$extraparams = $tokenobj->getExtraParams();
							$tmp = explode('-', $OAUTH_SERVICENAME);
							$scopes = explode(',', getDolGlobalString('OAUTH_'.strtoupper($tmp[0]).(empty($tmp[1]) ? '' : '-'.$tmp[1]).'_SCOPE'));
						}

						$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, $scopes);

						'@phan-var-force  OAuth\OAuth2\Service\AbstractService|OAuth\OAuth1\Service\AbstractService $apiService'; // createService is only ServiceInterface

						$refreshtoken = $tokenobj->getRefreshToken();
						$tokenobj = $apiService->refreshAccessToken($tokenobj);

						// We have to save the token because answer give it only once
						$tokenobj->setRefreshToken($refreshtoken);
						$storage->storeAccessToken($OAUTH_SERVICENAME, $tokenobj);
					}
					$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
					if (is_object($tokenobj)) {
						$token = $tokenobj->getAccessToken();
					} else {
						$this->error = "Token not found";
						return -1;
					}
				} catch (Exception $e) {
					// Return an error if token not found
					$this->error = $e->getMessage();
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					return -1;
				}

				$cm = new ClientManager();
				$client = $cm->make([
					'host'           => $this->host,
					'port'           => $this->port,
					'encryption'     => !empty($this->imap_encryption) ? $this->imap_encryption : false,
					'validate_cert'  => true,
					'protocol'       => 'imap',
					'username'       => $this->login,
					'password'       => $token,
					'authentication' => "oauth",
				]);
			} else {
				// Mode LOGIN (login/pass) with PHP-IMAP
				$this->debuginfo .= 'doCollectOneCollector is using method MAIN_IMAP_USE_PHPIMAP=1, access_type=0 (LOGIN)<br>';

				$cm = new ClientManager();
				$client = $cm->make([
					'host'           => $this->host,
					'port'           => $this->port,
					'encryption'     => !empty($this->imap_encryption) ? $this->imap_encryption : false,
					'validate_cert'  => true,
					'protocol'       => 'imap',
					'username'       => $this->login,
					'password'       => $this->password,
					'authentication' => "login",
				]);
			}

			try {
				$client->connect();

				$connection = true;
			} catch (ConnectionFailedException $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_ERR);
				return -1;
			}

			$host = dol_getprefix('email');
		} else {
			// Use native IMAP functions
			$this->debuginfo .= 'doCollectOneCollector is using method MAIN_IMAP_USE_PHPIMAP=0 (native PHP imap, LOGIN)<br>';

			if (!function_exists('imap_open')) {
				$this->error = 'IMAP function not enabled on your PHP';
				return -2;
			}

			$connectstringserver = $this->getConnectStringIMAP();
			if (!getDolGlobalString('MAIL_DISABLE_UTF7_ENCODE_OF_DIR')) {
				$connectstringsource = $connectstringserver.$this->getEncodedUtf7($sourcedir);
				$connectstringtarget = $connectstringserver.$this->getEncodedUtf7($targetdir);
			} else {
				$connectstringsource = $connectstringserver.$sourcedir;
				$connectstringtarget = $connectstringserver.$targetdir;
			}

			$this->debuginfo .= 'connectstringsource = '.$connectstringsource.', $connectstringtarget='.$connectstringtarget.'<br>';

			$connection = imap_open($connectstringsource, $this->login, $this->password);
			if ($connection === false) {
				$this->error = 'Failed to open IMAP connection '.$connectstringsource.' '.imap_last_error();
				return -3;
			}
			'@phan-var-force resource|IMAP\Connection $connection';
			imap_errors(); // Clear stack of errors.

			$host = dol_getprefix('email');
			//$host = '123456';

			// Define the IMAP search string
			// See https://tools.ietf.org/html/rfc3501#section-6.4.4 for IMAPv4 (PHP not yet compatible)
			// See https://tools.ietf.org/html/rfc1064 page 13 for IMAPv2
			//$search='ALL';
		}

		$criteria = array();
		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			// Use PHPIMAP external library
			$criteria = array(array('UNDELETED')); // Seems not supported by some servers
			foreach ($this->filters as $rule) {
				if (empty($rule['status'])) {
					continue;
				}

				$not = '';
				if (strpos($rule['rulevalue'], '!') === 0) {
					// The value start with !, so we exclude the criteria
					$not = 'NOT ';
					// Then remove the ! from the string for next filters
					$rule['rulevalue'] = substr($rule['rulevalue'], 1);
				}

				if ($rule['type'] == 'from') {
					$tmprulevaluearray = explode('*', $rule['rulevalue']);
					if (count($tmprulevaluearray) >= 2) {
						foreach ($tmprulevaluearray as $tmprulevalue) {
							array_push($criteria, array($not."FROM" => $tmprulevalue));
						}
					} else {
						array_push($criteria, array($not."FROM" => $rule['rulevalue']));
					}
				}
				if ($rule['type'] == 'to') {
					$tmprulevaluearray = explode('*', $rule['rulevalue']);
					if (count($tmprulevaluearray) >= 2) {
						foreach ($tmprulevaluearray as $tmprulevalue) {
							array_push($criteria, array($not."TO" => $tmprulevalue));
						}
					} else {
						array_push($criteria, array($not."TO" => $rule['rulevalue']));
					}
				}
				if ($rule['type'] == 'bcc') {
					array_push($criteria, array($not."BCC" => $rule['rulevalue']));
				}
				if ($rule['type'] == 'cc') {
					array_push($criteria, array($not."CC" => $rule['rulevalue']));
				}
				if ($rule['type'] == 'subject') {
					if (strpos($rule['rulevalue'], '!') === 0) {
						//array_push($criteria, array("NOT SUBJECT" => $rule['rulevalue']));
						$searchfilterexcludesubjectarray[] = preg_replace('/^!/', '', $rule['rulevalue']);
					} else {
						array_push($criteria, array("SUBJECT" => $rule['rulevalue']));
					}
				}
				if ($rule['type'] == 'body') {
					if (strpos($rule['rulevalue'], '!') === 0) {
						//array_push($criteria, array("NOT BODY" => $rule['rulevalue']));
						$searchfilterexcludebodyarray[] = preg_replace('/^!/', '', $rule['rulevalue']);
					} else {
						array_push($criteria, array("BODY" => $rule['rulevalue']));
					}
				}
				if ($rule['type'] == 'header') {
					array_push($criteria, array($not."HEADER" => $rule['rulevalue']));
				}

				/* seems not used */
				/*
				 if ($rule['type'] == 'notinsubject') {
				 array_push($criteria, array($not."SUBJECT NOT" => $rule['rulevalue']));
				 }
				 if ($rule['type'] == 'notinbody') {
				 array_push($criteria, array($not."BODY NOT" => $rule['rulevalue']));
				 }*/

				if ($rule['type'] == 'seen') {
					array_push($criteria, array($not."SEEN"));
				}
				if ($rule['type'] == 'unseen') {
					array_push($criteria, array($not."UNSEEN"));
				}
				if ($rule['type'] == 'unanswered') {
					array_push($criteria, array($not."UNANSWERED"));
				}
				if ($rule['type'] == 'answered') {
					array_push($criteria, array($not."ANSWERED"));
				}
				if ($rule['type'] == 'smaller') {
					array_push($criteria, array($not."SMALLER"));
				}
				if ($rule['type'] == 'larger') {
					array_push($criteria, array($not."LARGER"));
				}

				// Rules to filter after the search imap
				if ($rule['type'] == 'withtrackingidinmsgid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withouttrackingidinmsgid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withtrackingid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/References.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withouttrackingid') {
					$searchfilternodoltrackid++;
					$searchhead .= '! /References.*@'.preg_quote($host, '/').'/';
				}

				if ($rule['type'] == 'isanswer') {
					$searchfilterisanswer++;
					$searchhead .= '/References.*@.*/';
				}
				if ($rule['type'] == 'isnotanswer') {
					$searchfilterisnotanswer++;
					$searchhead .= '! /References.*@.*/';
				}

				if ($rule['type'] == 'replyto') {
					$searchfilterreplyto++;
					$rulesreplyto[] = $rule['rulevalue'];
					$searchhead .= '/Reply-To.*'.preg_quote($rule['rulevalue'], '/').'/';
				}
			}

			if (empty($targetdir) || !getDolGlobalString('EMAILCOLLECTOR_NO_FILTER_ON_DATE_IF_THERE_IS_A_TARGETDIR')) {	// Use the last date of successful check as a filter if there is no targetdir defined.
				$fromdate = 0;
				if ($this->datelastok) {
					$fromdate = $this->datelastok;
				}
				if ($fromdate > 0) {
					// $search .= ($search ? ' ' : '').'SINCE '.date('j-M-Y', $fromdate - 1); // SENTSINCE not supported. Date must be X-Abc-9999 (X on 1 digit if < 10)
					array_push($criteria, array("SINCE" => date('j-M-Y', $fromdate - 1)));	// -1 is to add a security to no forgot some email
				}
				//$search.=($search?' ':'').'SINCE 8-Apr-2022';
			}

			dol_syslog("IMAP search string = ".var_export($criteria, true));
			$search = var_export($criteria, true);
		} else {
			// Use native IMAP functions
			$search = 'UNDELETED'; // Seems not supported by some servers
			foreach ($this->filters as $rule) {
				if (empty($rule['status'])) {
					continue;
				}

				// Forge the IMAP search string.
				// See https://www.rfc-editor.org/rfc/rfc3501

				$not = '';
				if (!empty($rule['rulevalue']) && strpos($rule['rulevalue'], '!') === 0) {
					// The value start with !, so we exclude the criteria
					$not = 'NOT ';
					// Then remove the ! from the string for next filters
					$rule['rulevalue'] = substr($rule['rulevalue'], 1);
				}

				if ($rule['type'] == 'from') {
					$tmprulevaluearray = explode('*', $rule['rulevalue']);	// Search on abc*def means searching on 'abc' and on 'def'
					if (count($tmprulevaluearray) >= 2) {
						foreach ($tmprulevaluearray as $tmprulevalue) {
							$search .= ($search ? ' ' : '').$not.'FROM "'.str_replace('"', '', $tmprulevalue).'"';
						}
					} else {
						$search .= ($search ? ' ' : '').$not.'FROM "'.str_replace('"', '', $rule['rulevalue']).'"';
					}
				}
				if ($rule['type'] == 'to') {
					$tmprulevaluearray = explode('*', $rule['rulevalue']);	// Search on abc*def means searching on 'abc' and on 'def'
					if (count($tmprulevaluearray) >= 2) {
						foreach ($tmprulevaluearray as $tmprulevalue) {
							$search .= ($search ? ' ' : '').$not.'TO "'.str_replace('"', '', $tmprulevalue).'"';
						}
					} else {
						$search .= ($search ? ' ' : '').$not.'TO "'.str_replace('"', '', $rule['rulevalue']).'"';
					}
				}
				if ($rule['type'] == 'bcc') {
					$search .= ($search ? ' ' : '').$not.'BCC';
				}
				if ($rule['type'] == 'cc') {
					$search .= ($search ? ' ' : '').$not.'CC';
				}
				if ($rule['type'] == 'subject') {
					if ($not) {
						//$search .= ($search ? ' ' : '').'NOT BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
						$searchfilterexcludesubjectarray[] = $rule['rulevalue'];
					} else {
						$search .= ($search ? ' ' : '').'SUBJECT "'.str_replace('"', '', $rule['rulevalue']).'"';
					}
				}
				if ($rule['type'] == 'body') {
					if ($not) {
						//$search .= ($search ? ' ' : '').'NOT BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
						$searchfilterexcludebodyarray[] = $rule['rulevalue'];
					} else {
						// Warning: Google doesn't implement IMAP properly, and only matches whole words,
						$search .= ($search ? ' ' : '').'BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
					}
				}
				if ($rule['type'] == 'header') {
					$search .= ($search ? ' ' : '').$not.'HEADER '.$rule['rulevalue'];
				}

				/* seems not used */
				/*
				 if ($rule['type'] == 'notinsubject') {
				 $search .= ($search ? ' ' : '').'NOT SUBJECT "'.str_replace('"', '', $rule['rulevalue']).'"';
				 }
				 if ($rule['type'] == 'notinbody') {
				 $search .= ($search ? ' ' : '').'NOT BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
				 }*/

				if ($rule['type'] == 'seen') {
					$search .= ($search ? ' ' : '').$not.'SEEN';
				}
				if ($rule['type'] == 'unseen') {
					$search .= ($search ? ' ' : '').$not.'UNSEEN';
				}
				if ($rule['type'] == 'unanswered') {
					$search .= ($search ? ' ' : '').$not.'UNANSWERED';
				}
				if ($rule['type'] == 'answered') {
					$search .= ($search ? ' ' : '').$not.'ANSWERED';
				}
				if ($rule['type'] == 'smaller') {
					$search .= ($search ? ' ' : '').$not.'SMALLER "'.str_replace('"', '', $rule['rulevalue']).'"';
				}
				if ($rule['type'] == 'larger') {
					$search .= ($search ? ' ' : '').$not.'LARGER "'.str_replace('"', '', $rule['rulevalue']).'"';
				}

				// Rules to filter after the search imap
				if ($rule['type'] == 'withtrackingidinmsgid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withouttrackingidinmsgid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withtrackingid') {
					$searchfilterdoltrackid++;
					$searchhead .= '/References.*@'.preg_quote($host, '/').'/';
				}
				if ($rule['type'] == 'withouttrackingid') {
					$searchfilternodoltrackid++;
					$searchhead .= '! /References.*@'.preg_quote($host, '/').'/';
				}

				if ($rule['type'] == 'isanswer') {
					$searchfilterisanswer++;
					$searchhead .= '/References.*@.*/';
				}
				if ($rule['type'] == 'isnotanswer') {
					$searchfilterisnotanswer++;
					$searchhead .= '! /References.*@.*/';
				}

				if ($rule['type'] == 'replyto') {
					$searchfilterreplyto++;
					$rulesreplyto[] = $rule['rulevalue'];
					$searchhead .= '/Reply-To.*'.preg_quote($rule['rulevalue'], '/').'/';
				}
			}

			if (empty($targetdir)) {	// Use last date as filter if there is no targetdir defined.
				$fromdate = 0;
				if ($this->datelastok) {
					$fromdate = $this->datelastok;
				}
				if ($fromdate > 0) {
					$search .= ($search ? ' ' : '').'SINCE '.date('j-M-Y', $fromdate - 1); // SENTSINCE not supported. Date must be X-Abc-9999 (X on 1 digit if < 10)
				}
				//$search.=($search?' ':'').'SINCE 8-Apr-2018';
			}

			dol_syslog("IMAP search string = ".$search);
			//var_dump($search);
		}

		$nbemailprocessed = 0;
		$nbemailok = 0;
		$nbactiondone = 0;
		$charset = ($this->hostcharset ? $this->hostcharset : "UTF-8");
		$arrayofemail = array();

		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP') && is_object($client)) {
			try {
				// Uncomment this to output debug info
				//$client->getConnection()->enableDebug();

				$tmpsourcedir = $sourcedir;
				if (!getDolGlobalString('MAIL_DISABLE_UTF7_ENCODE_OF_DIR')) {
					$tmpsourcedir = $this->getEncodedUtf7($sourcedir);
				}

				$f = $client->getFolders(false, $tmpsourcedir);	// Note the search of directory do a search on sourcedir*
				if ($f) {
					$folder = $f[0];
					if ($folder instanceof Webklex\PHPIMAP\Folder) {
						$Query = $folder->messages()->where($criteria); // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
					} else {
						$error++;
						$this->error = "Source directory ".$sourcedir." not found";
						$this->errors[] = $this->error;
						dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_WARNING);
						return -1;
					}
				} else {
					$error++;
					$this->error = "Failed to execute getfolders";
					$this->errors[] = $this->error;
					dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_ERR);
					return -1;
				}
			} catch (InvalidWhereQueryCriteriaException $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_ERR);
				return -1;
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_ERR);
				return -1;
			}

			'@phan-var-force Webklex\PHPIMAP\Query\Query $Query';
			try {
				//var_dump($Query->count());
				if ($mode > 0) {
					$Query->leaveUnread();
				}
				$arrayofemail = $Query->limit($this->maxemailpercollect)->setFetchOrder("asc")->get();
				dol_syslog("EmailCollector::doCollectOneCollector nb arrayofemail ".(is_array($arrayofemail) ? count($arrayofemail) : 'Not array'));	// @phpstan-ignore-line
				//var_dump($arrayofemail);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog("EmailCollector::doCollectOneCollector ".$this->error, LOG_ERR);
				return -1;
			}
		} elseif ($connection !== false) {
			// Scan IMAP dir (for native IMAP, the source dir is inside the $connection variable)
			$arrayofemail = imap_search($connection, $search, SE_UID, $charset);

			if ($arrayofemail === false) {
				// Nothing found or search string not understood
				$mapoferrrors = imap_errors();
				if ($mapoferrrors !== false) {
					$error++;
					$this->error = "Search string not understood - ".implode(',', $mapoferrrors);
					$this->errors[] = $this->error;
				}
			}
		}

		$arrayofemailtodelete = array();	// Track email to delete to make the deletion at end.

		// Loop on each email found
		if (!$error && !empty($arrayofemail) && count($arrayofemail) > 0 && $connection !== false) {
			// Loop to get part html and plain
			/*
			 0 multipart/mixed
			 1 multipart/alternative
			 1.1 text/plain
			 1.2 text/html
			 2 message/rfc822
			 2 multipart/mixed
			 2.1 multipart/alternative
			 2.1.1 text/plain
			 2.1.2 text/html
			 2.2 message/rfc822
			 2.2 multipart/alternative
			 2.2.1 text/plain
			 2.2.2 text/html
			 */
			dol_syslog("Start of loop on email", LOG_INFO, 1);

			$richarrayofemail = array();

			foreach ($arrayofemail as $imapemail) {
				if ($nbemailprocessed > 1000) {
					break; // Do not process more than 1000 email per launch (this is a different protection than maxnbcollectedpercollect)
				}

				// GET header and overview datas
				if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
					'@phan-var-force Webklex\PHPIMAP\Message $imapemail';
					$header = $imapemail->getHeader()->raw;  // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall  // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
					$overview = $imapemail->getAttributes();
				} else {
					$header = imap_fetchheader($connection, $imapemail, FT_UID);
					$overview = imap_fetch_overview($connection, $imapemail, FT_UID);
				}

				$header = preg_replace('/\r\n\s+/m', ' ', $header); // When a header line is on several lines, merge lines

				$matches = array();
				preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)(\r\n|\s$)/m', $header, $matches);
				$headers = array_combine($matches[1], $matches[2]);


				$richarrayofemail[] = array('imapemail' => $imapemail, 'header' => $header, 'headers' => $headers, 'overview' => $overview, 'date' => strtotime($headers['Date']));
			}


			// Sort email found by ascending date
			$richarrayofemail = dol_sort_array($richarrayofemail, 'date', 'asc');


			$iforemailloop = 0;
			foreach ($richarrayofemail as $tmpval) {
				$iforemailloop++;

				$imapemail = $tmpval['imapemail'];
				$header = $tmpval['header'];
				$overview = $tmpval['overview'];
				$headers = $tmpval['headers'];

				if (!empty($headers['in-reply-to']) && empty($headers['In-Reply-To'])) {
					$headers['In-Reply-To'] = $headers['in-reply-to'];
				}
				if (!empty($headers['references']) && empty($headers['References'])) {
					$headers['References'] = $headers['references'];
				}
				if (!empty($headers['message-id']) && empty($headers['Message-ID'])) {
					$headers['Message-ID'] = $headers['message-id'];
				}
				if (!empty($headers['subject']) && empty($headers['Subject'])) {
					$headers['Subject'] = $headers['subject'];
				}

				$headers['Subject'] = $this->decodeSMTPSubject($headers['Subject']);

				if (getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')) {
					$emailto = (string) $overview['to'];
				} else {
					$emailto = $this->decodeSMTPSubject($overview[0]->to);
				}
				//var_dump($headers);
				//var_dump($overview);exit;

				$operationslog .= '<br>** Process email #'.dol_escape_htmltag((string) $iforemailloop);

				if (getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')) {
					/** @var Webklex\PHPIMAP\Message $imapemail */
					'@phan-var-force Webklex\PHPIMAP\Message $imapemail';
					// $operationslog .= " - ".dol_escape_htmltag((string) $imapemail);
					$msgid = str_replace(array('<', '>'), '', $overview['message_id']);
				} else {
					$operationslog .= " - ".dol_escape_htmltag((string) $imapemail);
					$msgid = str_replace(array('<', '>'), '', $overview[0]->message_id);
				}
				$operationslog .= " - MsgId: ".$msgid;
				$operationslog .= " - Date: ".($headers['Date'] ?? $langs->transnoentitiesnoconv("NotFound"));
				$operationslog .= " - References: ".dol_escape_htmltag($headers['References'] ?? $langs->transnoentitiesnoconv("NotFound"))." - Subject: ".dol_escape_htmltag($headers['Subject']);

				dol_syslog("-- Process email #".$iforemailloop.", MsgId: ".$msgid.", Date: ".($headers['Date'] ?? '').", References: ".($headers['References'] ?? '').", Subject: ".$headers['Subject']);


				$trackidfoundintorecipienttype = '';
				$trackidfoundintorecipientid = 0;
				$reg = array();
				// See also later list of all supported tags...
				// Note: "th[i]" to avoid matching a codespell suggestion to convert to "this".
				// TODO Add host after the @'.preg_quote($host, '/')
				if (preg_match('/\+(th[i]|ctc|use|mem|sub|proj|tas|con|tic|pro|ord|inv|spro|sor|sin|leav|stockinv|job|surv|salary)([0-9]+)@/', $emailto, $reg)) {
					$trackidfoundintorecipienttype = $reg[1];
					$trackidfoundintorecipientid = $reg[2];
				} elseif (preg_match('/\+emailing-(\w+)@/', $emailto, $reg)) {	// Can be 'emailing-test' or 'emailing-IdMailing-IdRecipient'
					$trackidfoundintorecipienttype = 'emailing';
					$trackidfoundintorecipientid = $reg[1];
				}

				$trackidfoundintomsgidtype = '';
				$trackidfoundintomsgidid = 0;
				$reg = array();
				// See also later list of all supported tags...
				// Note: "th[i]" to avoid matching a codespell suggestion to convert to "this".
				// TODO Add host after the @
				if (preg_match('/(?:[\+\-])(th[i]|ctc|use|mem|sub|proj|tas|con|tic|pro|ord|inv|spro|sor|sin|leav|stockinv|job|surv|salary)([0-9]+)@/', $msgid, $reg)) {
					$trackidfoundintomsgidtype = $reg[1];
					$trackidfoundintomsgidid = $reg[2];
				} elseif (preg_match('/(?:[\+\-])emailing-(\w+)@/', $msgid, $reg)) {	// Can be 'emailing-test' or 'emailing-IdMailing-IdRecipient'
					$trackidfoundintomsgidtype = 'emailing';
					$trackidfoundintomsgidid = $reg[1];
				}

				// If there is an emailcollecter filter on trackid
				if ($searchfilterdoltrackid > 0) {
					if (empty($trackidfoundintorecipienttype) && empty($trackidfoundintomsgidtype)) {
						if (empty($headers['References']) || !preg_match('/@'.preg_quote($host, '/').'/', $headers['References'])) {
							$nbemailprocessed++;
							dol_syslog(" Discarded - No suffix in email recipient and no Header References found matching the signature of the application, so with a trackid coming from the application");
							continue; // Exclude email
						}
					}
				}
				if ($searchfilternodoltrackid > 0) {
					if (!empty($trackidfoundintorecipienttype) || !empty($trackidfoundintomsgidtype) || (!empty($headers['References']) && preg_match('/@'.preg_quote($host, '/').'/', $headers['References']))) {
						$nbemailprocessed++;
						dol_syslog(" Discarded - Suffix found into email or Header References found and matching signature of application so with a trackid");
						continue; // Exclude email
					}
				}

				if ($searchfilterisanswer > 0) {
					if (empty($headers['In-Reply-To'])) {
						$nbemailprocessed++;
						dol_syslog(" Discarded - Email is not an answer (no In-Reply-To header)");
						continue; // Exclude email
					}
					$isanswer = 0;
					if (preg_match('/^(Re|AW)\s*:\s+/i', $headers['Subject'])) {
						$isanswer = 1;
					}
					if (getDolGlobalString('EMAILCOLLECTOR_USE_IN_REPLY_TO_TO_DETECT_ANSWERS')) {
						// Note: "In-Reply-To" to detect if mail is an answer of another mail is not reliable because we can have:
						// Message-ID=A, In-Reply-To=B, References=B and message can BE an answer but may be NOT (for example a transfer of an email rewritten)
						if (!empty($headers['In-Reply-To'])) {
							$isanswer = 1;
						}
					}
					//if ($headers['In-Reply-To'] != $headers['Message-ID'] && empty($headers['References'])) $isanswer = 1;	// If in-reply-to differs of message-id, this is a reply
					//if ($headers['In-Reply-To'] != $headers['Message-ID'] && !empty($headers['References']) && strpos($headers['References'], $headers['Message-ID']) !== false) $isanswer = 1;

					if (!$isanswer) {
						$nbemailprocessed++;
						dol_syslog(" Discarded - Email is not an answer (no RE prefix in subject)");
						continue; // Exclude email
					}
				}
				if ($searchfilterisnotanswer > 0) {
					if (!empty($headers['In-Reply-To'])) {
						// Note: we can have
						// Message-ID=A, In-Reply-To=B, References=B and message can BE an answer or NOT (a transfer rewritten)
						$isanswer = 0;
						if (preg_match('/^(回复|回覆|SV|Antw|VS|RE|Re|AW|Aw|ΑΠ|השב| תשובה | הועבר|Vá|R|RIF|BLS|Atb|RES|Odp|பதில்|YNT|ATB)\s*:\s+/i', $headers['Subject'])) {
							$isanswer = 1;
						}
						//if ($headers['In-Reply-To'] != $headers['Message-ID'] && empty($headers['References'])) $isanswer = 1;	// If in-reply-to differs of message-id, this is a reply
						//if ($headers['In-Reply-To'] != $headers['Message-ID'] && !empty($headers['References']) && strpos($headers['References'], $headers['Message-ID']) !== false) $isanswer = 1;
						if ($isanswer) {
							$nbemailprocessed++;
							dol_syslog(" Discarded - Email is an answer");
							continue; // Exclude email
						}
					}
				}
				if ($searchfilterreplyto > 0) {
					if (!empty($headers['Reply-To'])) {
						$isreplytook = 0;
						foreach ($rulesreplyto as $key => $rulereplyto) {
							if (preg_match('/'.preg_quote($rulereplyto, '/').'/', $headers['Reply-To'])) {
								$isreplytook++;
							}
						}

						if (!$isreplytook || $isreplytook != count($rulesreplyto)) {
							$nbemailprocessed++;
							dol_syslog(" Discarded - Reply-to does not match");
							continue; // Exclude email
						}
					}
				}

				//print "Process mail ".$iforemailloop." Subject: ".dol_escape_htmltag($headers['Subject'])." selected<br>\n";

				$thirdpartystatic = new Societe($this->db);
				$contactstatic = new Contact($this->db);
				$projectstatic = new Project($this->db);

				$nbactiondoneforemail = 0;
				$errorforemail = 0;
				$errorforactions = 0;
				$thirdpartyfoundby = '';
				$contactfoundby = '';
				$projectfoundby = '';
				$ticketfoundby = '';
				$candidaturefoundby = '';


				if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
					dol_syslog("msgid=".$overview['message_id']." date=".dol_print_date($overview['date'], 'dayrfc', 'gmt')." from=".$overview['from']." to=".$overview['to']." subject=".$overview['subject']);

					// Removed emojis
					$overview['subject'] = removeEmoji($overview['subject'], getDolGlobalInt('MAIN_EMAIL_COLLECTOR_ACCEPT_EMOJIS', 1));
				} else {
					dol_syslog("msgid=".$overview[0]->message_id." date=".dol_print_date($overview[0]->udate, 'dayrfc', 'gmt')." from=".$overview[0]->from." to=".$overview[0]->to." subject=".$overview[0]->subject);

					$overview[0]->subject = $this->decodeSMTPSubject($overview[0]->subject);

					$overview[0]->from = $this->decodeSMTPSubject($overview[0]->from);

					// Removed emojis
					$overview[0]->subject = removeEmoji($overview[0]->subject, getDolGlobalInt('MAIN_EMAIL_COLLECTOR_ACCEPT_EMOJIS', 1));
				}
				// GET IMAP email structure/content
				global $htmlmsg, $plainmsg, $charset, $attachments;

				if (getDolGlobalInt('MAIN_IMAP_USE_PHPIMAP')) {
					/** @var Webklex\PHPIMAP\Message $imapemail */
					'@phan-var-force Webklex\PHPIMAP\Message $imapemail';
					if ($imapemail->hasHTMLBody()) {
						$htmlmsg = $imapemail->getHTMLBody();
					}
					if ($imapemail->hasTextBody() && $imapemail->getTextBody() != "\n") {
						$plainmsg = $imapemail->getTextBody();
					}
					if ($imapemail->hasAttachments()) {
						$attachments = $imapemail->getAttachments()->all();
					} else {
						$attachments = [];
					}
				} else {
					$this->getmsg($connection, $imapemail);	// This set global var $charset, $htmlmsg, $plainmsg, $attachments
				}
				'@phan-var-force Webklex\PHPIMAP\Attachment[] $attachments';

				//print $plainmsg;
				//var_dump($plainmsg); exit;

				//$htmlmsg,$plainmsg,$charset,$attachments
				$messagetext = $plainmsg ? $plainmsg : dol_string_nohtmltag((string) $htmlmsg, 0);
				// Removed emojis

				if (utf8_valid($messagetext)) {
					$messagetext = removeEmoji($messagetext, getDolGlobalInt('MAIN_EMAIL_COLLECTOR_ACCEPT_EMOJIS', 1));
				} else {
					$operationslog .= '<br>Discarded - Email body is not valid utf8';
					dol_syslog(" Discarded - Email body is not valid utf8");
					continue; // Exclude email
				}

				if (!empty($searchfilterexcludebodyarray)) {
					foreach ($searchfilterexcludebodyarray as $searchfilterexcludebody) {
						if (preg_match('/'.preg_quote($searchfilterexcludebody, '/').'/ms', $messagetext)) {
							$nbemailprocessed++;
							$operationslog .= '<br>Discarded - Email body contains string '.$searchfilterexcludebody;
							dol_syslog(" Discarded - Email body contains string ".$searchfilterexcludebody);
							continue 2; // Exclude email
						}
					}
				}

				//var_dump($plainmsg);
				//var_dump($htmlmsg);
				//var_dump($messagetext);
				//var_dump($charset);
				//var_dump($attachments);
				//exit;

				// Parse IMAP email structure
				/*
				 $structure = imap_fetchstructure($connection, $imapemail, FT_UID);

				 $partplain = $parthtml = -1;
				 $encodingplain = $encodinghtml = '';

				 $result = createPartArray($structure, '');

				 foreach($result as $part)
				 {
				 // $part['part_object']->type seems 0 for content
				 // $part['part_object']->type seems 5 for attachment
				 if (empty($part['part_object'])) continue;
				 if ($part['part_object']->subtype == 'HTML')
				 {
				 $parthtml=$part['part_number'];
				 if ($part['part_object']->encoding == 4)
				 {
				 $encodinghtml = 'aaa';
				 }
				 }
				 if ($part['part_object']->subtype == 'PLAIN')
				 {
				 $partplain=$part['part_number'];
				 if ($part['part_object']->encoding == 4)
				 {
				 $encodingplain = 'rr';
				 }
				 }
				 }
				 //var_dump($result);
				 //var_dump($partplain);
				 //var_dump($parthtml);

				 //var_dump($structure);
				 //var_dump($parthtml);
				 //var_dump($partplain);

				 $messagetext = imap_fetchbody($connection, $imapemail, ($parthtml != '-1' ? $parthtml : ($partplain != '-1' ? $partplain : 1)), FT_PEEK|FTP_UID);
				 */

				//var_dump($messagetext);
				//var_dump($structure->parts[0]->parts);
				//print $header;
				//print $messagetext;
				//exit;

				$fromstring = '';
				$replytostring = '';

				if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
					$fromstring = $overview['from'];
					$replytostring = empty($overview['in_reply-to']) ? $headers['Reply-To'] : $overview['in_reply-to'];

					$sender = $overview['sender'];
					$to = $overview['to'];
					$sendtocc = empty($overview['cc']) ? '' : $overview['cc'];
					$sendtobcc = empty($overview['bcc']) ? '' : $overview['bcc'];

					$tmpdate = $overview['date']->toDate();  // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
					$tmptimezone = $tmpdate->getTimezone()->getName();  // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall

					$dateemail = dol_stringtotime((string) $overview['date'], 'gmt');    // if $overview['timezone'] is "+00:00"
					if (preg_match('/^([+\-])(\d\d):(\d\d)/', $tmptimezone, $reg)) {
						if ($reg[1] == '+' && ($reg[2] != '00' || $reg[3] != '00')) {
							$dateemail -= (3600 * (int) $reg[2]);
							$dateemail -= (60 * (int) $reg[3]);
						}
						if ($reg[1] == '-' && ($reg[2] != '00' || $reg[3] != '00')) {
							$dateemail += (3600 * (int) $reg[2]);
							$dateemail += (60 * (int) $reg[3]);
						}
					}
					$subject = $overview['subject'];
				} else {
					$fromstring = $overview[0]->from;
					$replytostring = (!empty($overview['in_reply-to']) ? $overview['in_reply-to'] : (!empty($headers['Reply-To']) ? $headers['Reply-To'] : "")) ;

					$sender = !empty($overview[0]->sender) ? $overview[0]->sender : '';
					$to = $overview[0]->to;
					$sendtocc = !empty($overview[0]->cc) ? $overview[0]->cc : '';
					$sendtobcc = !empty($overview[0]->bcc) ? $overview[0]->bcc : '';
					$dateemail = dol_stringtotime((string) $overview[0]->udate, 'gmt');
					$subject = $overview[0]->subject;
					//var_dump($msgid);exit;
				}

				if (!empty($searchfilterexcludesubjectarray)) {
					foreach ($searchfilterexcludesubjectarray as $searchfilterexcludesubject) {
						if (preg_match('/'.preg_quote($searchfilterexcludesubject, '/').'/ms', $subject)) {
							$nbemailprocessed++;
							$operationslog .= '<br>Discarded - Email subject contains string '.$searchfilterexcludesubject;
							dol_syslog(" Discarded - Email subject contains string ".$searchfilterexcludesubject);
							continue 2; // Exclude email
						}
					}
				}

				$reg = array();
				if (preg_match('/^(.*)<(.*)>$/', $fromstring, $reg)) {
					$from = $reg[2];
					$fromtext = $reg[1];
				} else {
					$from = $fromstring;
					$fromtext = '';
				}
				if (preg_match('/^(.*)<(.*)>$/', $replytostring, $reg)) {
					$replyto = $reg[2];
					$replytotext = $reg[1];
				} else {
					$replyto = $replytostring;
					$replytotext = '';
				}
				$fk_element_id = 0;
				$fk_element_type = '';


				$this->db->begin();

				$contactid = 0;
				$thirdpartyid = 0;
				$projectid = 0;
				$ticketid = 0;

				// Analyze TrackId in field References (already analyzed previously into the "To:" and "Message-Id").
				// For example:
				// References: <1542377954.SMTPs-dolibarr-thi649@8f6014fde11ec6cdec9a822234fc557e>
				// References: <1542377954.SMTPs-dolibarr-tic649@8f6014fde11ec6cdec9a822234fc557e>
				// References: <1542377954.SMTPs-dolibarr-abc649@8f6014fde11ec6cdec9a822234fc557e>
				$trackid = '';
				$objectid = 0;
				$objectemail = null;

				$reg = array();
				$arrayofreferences = array();
				if (!empty($headers['References'])) {
					$arrayofreferences = preg_split('/(,|\s+)/', $headers['References']);
				}
				if (!in_array('<'.$msgid.'>', $arrayofreferences)) {
					$arrayofreferences = array_merge($arrayofreferences, array('<'.$msgid.'>'));
				}
				// var_dump($headers['References']);
				// var_dump($arrayofreferences);

				foreach ($arrayofreferences as $reference) {
					//print "Process mail ".$iforemailloop." email_msgid ".$msgid.", date ".dol_print_date($dateemail, 'dayhour', 'gmt').", subject ".$subject.", reference ".dol_escape_htmltag($reference)."<br>\n";
					if (!empty($trackidfoundintorecipienttype)) {
						$resultsearchtrackid = -1;		// trackid found
						$reg[1] = $trackidfoundintorecipienttype;
						$reg[2] = $trackidfoundintorecipientid;
					} elseif (!empty($trackidfoundintomsgidtype)) {
						$resultsearchtrackid = -1;		// trackid found
						$reg[1] = $trackidfoundintomsgidtype;
						$reg[2] = $trackidfoundintomsgidid;
					} else {
						$resultsearchtrackid = preg_match('/dolibarr-([a-z]+)([0-9]+)@'.preg_quote($host, '/').'/', $reference, $reg);	// trackid found or not
						if (empty($resultsearchtrackid) && getDolGlobalString('EMAIL_ALTERNATIVE_HOST_SIGNATURE')) {
							$resultsearchtrackid = preg_match('/dolibarr-([a-z]+)([0-9]+)@'.preg_quote(getDolGlobalString('EMAIL_ALTERNATIVE_HOST_SIGNATURE'), '/').'/', $reference, $reg);	// trackid found
						}
					}

					if (!empty($resultsearchtrackid)) {
						// We found a tracker (in recipient email or msgid or into a Reference matching the Dolibarr server)
						$trackid = $reg[1].$reg[2];

						$objectid = $reg[2];
						// See also list into interface_50_modAgenda_ActionsAuto
						if ($reg[1] == 'thi') {   // Third-party
							$objectemail = new Societe($this->db);
						}
						if ($reg[1] == 'ctc') {   // Contact
							$objectemail = new Contact($this->db);
						}
						if ($reg[1] == 'inv') {   // Customer Invoice
							$objectemail = new Facture($this->db);
						}
						if ($reg[1] == 'sinv') {   // Supplier Invoice
							$objectemail = new FactureFournisseur($this->db);
						}
						if ($reg[1] == 'pro') {   // Customer Proposal
							$objectemail = new Propal($this->db);
						}
						if ($reg[1] == 'ord') {   // Sale Order
							$objectemail = new Commande($this->db);
						}
						if ($reg[1] == 'shi') {   // Shipment
							$objectemail = new Expedition($this->db);
						}
						if ($reg[1] == 'spro') {   // Supplier Proposal
							$objectemail = new SupplierProposal($this->db);
						}
						if ($reg[1] == 'sord') {   // Supplier Order
							$objectemail = new CommandeFournisseur($this->db);
						}
						if ($reg[1] == 'rec') {   // Reception
							$objectemail = new Reception($this->db);
						}
						if ($reg[1] == 'proj') {   // Project
							$objectemail = new Project($this->db);
							$projectfoundby = 'TrackID dolibarr-'.$trackid.'@...';
						}
						if ($reg[1] == 'tas') {   // Task
							$objectemail = new Task($this->db);
						}
						if ($reg[1] == 'con') {   // Contact
							$objectemail = new Contact($this->db);
						}
						if ($reg[1] == 'use') {   // User
							$objectemail = new User($this->db);
						}
						if ($reg[1] == 'tic') {   // Ticket
							$objectemail = new Ticket($this->db);
							$ticketfoundby = 'TrackID dolibarr-'.$trackid.'@...';
						}
						if ($reg[1] == 'recruitmentcandidature') {   // Recruiting Candidate
							$objectemail = new RecruitmentCandidature($this->db);
							$candidaturefoundby = 'TrackID dolibarr-'.$trackid.'@...';
						}
						if ($reg[1] == 'mem') {   // Member
							$objectemail = new Adherent($this->db);
						}
						/*if ($reg[1] == 'leav') {   // Leave / Holiday
							$objectemail = new Holiday($db);
						}
						if ($reg[1] == 'exp') {   // ExpenseReport
							$objectemail = new ExpenseReport($db);
						}*/
					} elseif (preg_match('/<(.*@.*)>/', $reference, $reg)) {
						// This is an external reference, we check if we have it in our database
						if (is_null($objectemail) && isModEnabled('ticket')) {
							$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."ticket";
							$sql .= " WHERE email_msgid = '".$this->db->escape($reg[1])."' OR origin_references LIKE '%".$this->db->escape($this->db->escapeforlike($reg[1]))."%'";
							$resql = $this->db->query($sql);
							if ($resql) {
								$obj = $this->db->fetch_object($resql);
								if ($obj) {
									$objectid = $obj->rowid;
									$objectemail = new Ticket($this->db);
									$ticketfoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
								}
							} else {
								$errorforemail++;
							}
						}

						if (!is_object($objectemail) && isModEnabled('project')) {
							$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."projet where email_msgid = '".$this->db->escape($reg[1])."'";
							$resql = $this->db->query($sql);
							if ($resql) {
								$obj = $this->db->fetch_object($resql);
								if ($obj) {
									$objectid = $obj->rowid;
									$objectemail = new Project($this->db);
									$projectfoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
								}
							} else {
								$errorforemail++;
							}
						}

						if (!is_object($objectemail) && isModEnabled('recruitment')) {
							$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature where email_msgid = '".$this->db->escape($reg[1])."'";
							$resql = $this->db->query($sql);
							if ($resql) {
								$obj = $this->db->fetch_object($resql);
								if ($obj) {
									$objectid = $obj->rowid;
									$objectemail = new RecruitmentCandidature($this->db);
									$candidaturefoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
								}
							} else {
								$errorforemail++;
							}
						}
					}

					// Load object linked to email
					if (is_object($objectemail)) {
						$result = $objectemail->fetch($objectid);
						if ($result > 0) {
							$fk_element_id = $objectemail->id;
							$fk_element_type = $objectemail->element;
							// Fix fk_element_type
							if ($fk_element_type == 'facture') {
								$fk_element_type = 'invoice';
							}

							if (get_class($objectemail) != 'Societe') {
								$thirdpartyid = $objectemail->fk_soc ?? $objectemail->socid;
							} else {
								$thirdpartyid = $objectemail->id;
							}

							if (get_class($objectemail) != 'Contact') {
								$contactid = $objectemail->fk_socpeople;
							} else {
								$contactid = $objectemail->id;
							}

							if (get_class($objectemail) != 'Project') {
								$projectid = isset($objectemail->fk_project) ? $objectemail->fk_project : $objectemail->fk_projet;
							} else {
								$projectid = $objectemail->id;
							}

							if ($objectemail instanceof Ticket) {
								$ticketid = $objectemail->id;

								$changeonticket_references = false;
								if (empty($trackid)) {
									$trackid = $objectemail->track_id;
								}
								if (empty($objectemail->origin_references)) {
									$objectemail->origin_references = !empty($headers['References']) ? $headers['References'] : null;
									$changeonticket_references = true;
								} else {
									foreach ($arrayofreferences as $key => $referencetmp) {
										if (!str_contains($objectemail->origin_references, $referencetmp)) {
											$objectemail->origin_references .= " ".$referencetmp;
											$changeonticket_references = true;
										}
									}
								}
								if ($changeonticket_references) {
									$objectemail->update($user, 1);		// We complete the references field, that is a field for technical tracking purpose, not a user field, so no need to execute triggers
								}
							}
						}
					}

					// Project
					if ($projectid > 0) {
						$result = $projectstatic->fetch($projectid);
						if ($result <= 0) {
							$projectstatic->id = 0;
						} else {
							$projectid = $projectstatic->id;
							if ($trackid) {
								$projectfoundby = 'trackid ('.$trackid.')';
							}
							if (empty($contactid)) {
								$contactid = $projectstatic->fk_contact;
							}
							if (empty($thirdpartyid)) {
								$thirdpartyid = $projectstatic->fk_soc;
							}
						}
					}
					// Contact
					if ($contactid > 0) {
						$result = $contactstatic->fetch($contactid);
						if ($result <= 0) {
							$contactstatic->id = 0;
						} else {
							$contactid = $contactstatic->id;
							if ($trackid) {
								$contactfoundby = 'trackid ('.$trackid.')';
							}
							if (empty($thirdpartyid)) {
								$thirdpartyid = $contactstatic->fk_soc;
							}
						}
					}
					// Thirdparty
					if ($thirdpartyid > 0) {
						$result = $thirdpartystatic->fetch($thirdpartyid);
						if ($result <= 0) {
							$thirdpartystatic->id = 0;
						} else {
							$thirdpartyid = $thirdpartystatic->id;
							if ($trackid) {
								$thirdpartyfoundby = 'trackid ('.$trackid.')';
							}
						}
					}

					if (is_object($objectemail)) {
						break; // Exit loop of references. We already found an accurate reference
					}
				}

				if (empty($contactid)) {		// Try to find contact using email
					$result = $contactstatic->fetch(0, null, '', $from);

					if ($result > 0) {
						dol_syslog("We found a contact with the email ".$from);
						$contactid = $contactstatic->id;
						$contactfoundby = 'email of contact ('.$from.')';
						if (empty($thirdpartyid) && $contactstatic->socid > 0) {
							$result = $thirdpartystatic->fetch($contactstatic->socid);
							if ($result > 0) {
								$thirdpartyid = $thirdpartystatic->id;
								$thirdpartyfoundby = 'email of contact ('.$from.')';
							}
						}
					}
				}

				if (empty($thirdpartyid)) {		// Try to find thirdparty using email
					$result = $thirdpartystatic->fetch(0, '', '', '', '', '', '', '', '', '', $from);
					if ($result > 0) {
						dol_syslog("We found a thirdparty with the email ".$from);
						$thirdpartyid = $thirdpartystatic->id;
						$thirdpartyfoundby = 'email ('.$from.')';
					}
				}

				/*
				 if ($replyto) {
				 if (empty($contactid)) {		// Try to find contact using email
				 $result = $contactstatic->fetch(0, null, '', $replyto);

				 if ($result > 0) {
				 dol_syslog("We found a contact with the email ".$replyto);
				 $contactid = $contactstatic->id;
				 $contactfoundby = 'email of contact ('.$replyto.')';
				 if (empty($thirdpartyid) && $contactstatic->socid > 0) {
				 $result = $thirdpartystatic->fetch($contactstatic->socid);
				 if ($result > 0) {
				 $thirdpartyid = $thirdpartystatic->id;
				 $thirdpartyfoundby = 'email of contact ('.$replyto.')';
				 }
				 }
				 }
				 }

				 if (empty($thirdpartyid)) {		// Try to find thirdparty using email
				 $result = $thirdpartystatic->fetch(0, '', '', '', '', '', '', '', '', '', $replyto);
				 if ($result > 0) {
				 dol_syslog("We found a thirdparty with the email ".$replyto);
				 $thirdpartyid = $thirdpartystatic->id;
				 $thirdpartyfoundby = 'email ('.$replyto.')';
				 }
				 }
				 }
				 */

				// Do operations (extract variables and creating data)
				if ($mode < 2) {	// 0=Mode production, 1=Mode test (read IMAP and try SQL update then rollback), 2=Mode test with no SQL updates
					foreach ($this->actions as $operation) {
						$errorforthisaction = 0;
						$ticketalreadyexists = 0;
						if ($errorforactions) {
							break;
						}
						if (empty($operation['status'])) {
							continue;
						}

						$operationslog .= '<br>* Process operation '.$operation['type'];

						// Make Operation
						dol_syslog("Execute action ".$operation['type']." actionparam=".$operation['actionparam'].' thirdpartystatic->id='.$thirdpartystatic->id.' contactstatic->id='.$contactstatic->id.' projectstatic->id='.$projectstatic->id);
						dol_syslog("Execute action fk_element_id=".$fk_element_id." fk_element_type=".$fk_element_type);	// If a Dolibarr tracker id is found, we should now the id of object

						// Try to guess if this is an email in or out.
						$actioncode = 'EMAIL_IN';
						// If we scan the Sent box, we use the code for out email
						if (preg_match('/Sent$/', $sourcedir) || preg_match('/envoyés$/i', $sourcedir)) {
							$actioncode = 'EMAIL';
						}
						// If sender is in the list MAIL_FROM_EMAILS_TO_CONSIDER_SENDING
						$arrayofemailtoconsideresender = explode(',', getDolGlobalString('MAIL_FROM_EMAILS_TO_CONSIDER_SENDING'));
						foreach ($arrayofemailtoconsideresender as $emailtoconsidersender) {
							if (preg_match('/'.preg_quote($emailtoconsidersender, '/').'/', $fromstring)) {
								$actioncode = 'EMAIL';
							}
						}
						$operationslog .= '<br>Email will have actioncode='.$actioncode;

						$description = $descriptiontitle = $descriptionmeta = $descriptionfull = '';

						$descriptiontitle = $langs->transnoentitiesnoconv("RecordCreatedByEmailCollector", $this->ref);

						$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("EmailMsgID").' : '.dol_escape_htmltag($msgid));
						$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailTopic").' : '.dol_escape_htmltag($subject));
						$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailDate").($langs->trans("MailDate") != 'Date' ? ' (Date)' : '').' : '.dol_escape_htmltag(dol_print_date($dateemail, "dayhourtext", "gmt")));
						$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailFrom").($langs->trans("MailFrom") != 'From' ? ' (From)' : '').' : '.dol_escape_htmltag($fromstring));
						if ($sender) {
							$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("Sender").($langs->trans("Sender") != 'Sender' ? ' (Sender)' : '').' : '.dol_escape_htmltag($sender));
						}
						$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailTo").($langs->trans("MailTo") != 'To' ? ' (To)' : '').' : '.dol_escape_htmltag($to));
						if ($replyto) {
							$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailReply").($langs->trans("MailReply") != 'Reply to' ? ' (Reply to)' : '').' : '.dol_escape_htmltag($replyto));
						}
						if ($sendtocc) {
							$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailCC").($langs->trans("MailCC") != 'CC' ? ' (CC)' : '').' : '.dol_escape_htmltag($sendtocc));
						}

						if ($operation['type'] == 'ticket') {
							// Verify if ticket already exists to fall back on the right operation
							$tickettocreate = new Ticket($this->db);
							$errorfetchticket = 0;
							$alreadycreated = 0;
							if (!empty($trackid)) {
								$alreadycreated = $tickettocreate->fetch(0, '', $trackid);
							}
							if ($alreadycreated == 0 && !empty($msgid)) {
								$alreadycreated = $tickettocreate->fetch(0, '', '', $msgid);
							}
							if ($alreadycreated < 0) {
								$errorfetchticket++;
							}
							if (empty($errorfetchticket)) {
								if ($alreadycreated == 0) {
									$operationslog .= '<br>Ticket not found using trackid='.$trackid.' or msgid='.$msgid;
									$ticketalreadyexists = 0;
								} else {
									$operationslog .= '<br>Ticket already found using trackid='.$trackid.' or msgid='.$msgid;	// We change the operation type to do
									$ticketalreadyexists = 1;
									$operation['type'] = 'recordevent';
								}
							} else {
								$ticketalreadyexists = -1;
							}
						}

						// Process now the operation type

						// Search and create thirdparty
						if ($operation['type'] == 'loadthirdparty' || $operation['type'] == 'loadandcreatethirdparty') {
							if (empty($operation['actionparam'])) {
								$errorforactions++;
								$this->error = "Action loadthirdparty or loadandcreatethirdparty has empty parameter. Must be a rule like 'name=HEADER:^From:(.*);' or 'name=SET:xxx' or 'name=EXTRACT:(body|subject):regex where 'name' can be replaced with 'id' or 'email' to define how to set or extract data. More properties can also be set, for example client=SET:2;";
								$this->errors[] = $this->error;
							} else {
								$actionparam = $operation['actionparam'];
								$idtouseforthirdparty = '';
								$nametouseforthirdparty = '';
								$emailtouseforthirdparty = '';
								$namealiastouseforthirdparty = '';

								$operationslog .= '<br>Loop on each property to set into actionparam';

								// $actionparam = 'param=SET:aaa' or 'param=EXTRACT:BODY:....'
								$arrayvaluetouse = dolExplodeIntoArray($actionparam, '(\n\r|\r|\n|;)', '=');
								foreach ($arrayvaluetouse as $propertytooverwrite => $valueforproperty) {
									$sourcestring = '';
									$sourcefield = '';
									$regexstring = '';
									$regforregex = array();

									if (preg_match('/^EXTRACT:([a-zA-Z0-9_]+):(.*)$/', $valueforproperty, $regforregex)) {
										$sourcefield = $regforregex[1];
										$regexstring = $regforregex[2];
									}

									if (!empty($sourcefield) && !empty($regexstring)) {
										if (strtolower($sourcefield) == 'body') {
											$sourcestring = $messagetext;
										} elseif (strtolower($sourcefield) == 'subject') {
											$sourcestring = $subject;
										} elseif (strtolower($sourcefield) == 'header') {
											$sourcestring = $header;
										}

										if ($sourcestring) {
											$regforval = array();
											//var_dump($regexstring);var_dump($sourcestring);
											if (preg_match('/'.$regexstring.'/ms', $sourcestring, $regforval)) {
												//var_dump($regforval[count($regforval)-1]);exit;
												// Overwrite param $tmpproperty
												if ($propertytooverwrite == 'id') {
													$idtouseforthirdparty = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;

													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Found idtouseforthirdparty='.dol_escape_htmltag($idtouseforthirdparty);
												} elseif ($propertytooverwrite == 'email') {
													$emailtouseforthirdparty = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;

													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Found emailtouseforthirdparty='.dol_escape_htmltag($emailtouseforthirdparty);
												} elseif ($propertytooverwrite == 'name') {
													$nametouseforthirdparty = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;

													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Found nametouseforthirdparty='.dol_escape_htmltag($nametouseforthirdparty);
												} elseif ($propertytooverwrite == 'name_alias') {
													$namealiastouseforthirdparty = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;

													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Found namealiastouseforthirdparty='.dol_escape_htmltag($namealiastouseforthirdparty);
												} else {
													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> We discard this, not a field used to search an existing thirdparty';
												}
											} else {
												// Regex not found
												if (in_array($propertytooverwrite, array('id', 'email', 'name', 'name_alias'))) {
													$idtouseforthirdparty = null;
													$nametouseforthirdparty = null;
													$emailtouseforthirdparty = null;
													$namealiastouseforthirdparty = null;

													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Not found. Property searched is critical so we cancel the search.';
												} else {
													$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' Regex /'.dol_escape_htmltag($regexstring).'/ms into '.strtoupper($sourcefield).' -> Not found';
												}
											}
											//var_dump($object->$tmpproperty);exit;
										} else {
											// Nothing can be done for this param
											$errorforactions++;
											$this->error = 'The extract rule to use to load thirdparty for email '.$msgid.' has an unknown source (must be HEADER, SUBJECT or BODY)';
											$this->errors[] = $this->error;

											$operationslog .= '<br>'.$this->error;
										}
									} elseif (preg_match('/^(SET|SETIFEMPTY):(.*)$/', $valueforproperty, $reg)) {
										//if (preg_match('/^options_/', $tmpproperty)) $object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $reg[1];
										//else $object->$tmpproperty = $reg[1];
										// Example: id=SETIFEMPTY:123
										if ($propertytooverwrite == 'id') {
											$idtouseforthirdparty = $reg[2];

											$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' We set property idtouseforthrdparty='.dol_escape_htmltag($idtouseforthirdparty);
										} elseif ($propertytooverwrite == 'email') {
											$emailtouseforthirdparty = $reg[2];

											$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' We set property emailtouseforthrdparty='.dol_escape_htmltag($emailtouseforthirdparty);
										} elseif ($propertytooverwrite == 'name') {
											$nametouseforthirdparty = $reg[2];

											$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' We set property nametouseforthirdparty='.dol_escape_htmltag($nametouseforthirdparty);
										} elseif ($propertytooverwrite == 'name_alias') {
											$namealiastouseforthirdparty = $reg[2];

											$operationslog .= '<br>propertytooverwrite='.$propertytooverwrite.' We set property namealiastouseforthirdparty='.dol_escape_htmltag($namealiastouseforthirdparty);
										}
									} else {
										$errorforactions++;
										$this->error = 'Bad syntax for description of action parameters: '.$actionparam;
										$this->errors[] = $this->error;
										break;
									}
								}

								if (!$errorforactions && ($idtouseforthirdparty || $emailtouseforthirdparty || $nametouseforthirdparty || $namealiastouseforthirdparty)) {
									// We make another search on thirdparty
									$operationslog .= '<br>We have this initial main data to search thirdparty: id='.$idtouseforthirdparty.', email='.$emailtouseforthirdparty.', name='.$nametouseforthirdparty.', name_alias='.$namealiastouseforthirdparty.'.';

									$tmpobject = new stdClass();
									$tmpobject->element = 'generic';
									$tmpobject->id = $idtouseforthirdparty;
									$tmpobject->name = $nametouseforthirdparty;
									$tmpobject->name_alias = $namealiastouseforthirdparty;
									$tmpobject->email = $emailtouseforthirdparty;

									$this->overwritePropertiesOfObject($tmpobject, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

									$idtouseforthirdparty = $tmpobject->id;
									$nametouseforthirdparty = $tmpobject->name;
									$namealiastouseforthirdparty = $tmpobject->name_alias;
									$emailtouseforthirdparty = $tmpobject->email;

									$operationslog .= '<br>We try to search existing thirdparty with idtouseforthirdparty='.$idtouseforthirdparty.' emailtouseforthirdparty='.$emailtouseforthirdparty.' nametouseforthirdparty='.$nametouseforthirdparty.' namealiastouseforthirdparty='.$namealiastouseforthirdparty;

									// Try to find the thirdparty that match the most the information we have
									$result = $thirdpartystatic->findNearest((int) $idtouseforthirdparty, (string) $nametouseforthirdparty, '', '', '', '', '', '', '', '', (string) $emailtouseforthirdparty, (string) $namealiastouseforthirdparty);

									if ($result < 0) {
										$errorforactions++;
										$this->error = 'Error when getting thirdparty with name '.$nametouseforthirdparty.' (may be 2 record exists with same name ?)';
										$this->errors[] = $this->error;
										break;
									} elseif ($result == 0) {	// No thirdparty found
										if ($operation['type'] == 'loadthirdparty') {
											dol_syslog("Third party with id=".$idtouseforthirdparty." email=".$emailtouseforthirdparty." name=".$nametouseforthirdparty." name_alias=".$namealiastouseforthirdparty." was not found");

											// Search into contacts of thirdparties to try to guess the thirdparty to use
											$resultContact = $contactstatic->findNearest(0, '', '', '', (string) $emailtouseforthirdparty, '', 0);
											if ($resultContact > 0) {
												$contactstatic->fetch($resultContact);
												$idtouseforthirdparty = $contactstatic->socid;
												$result = $thirdpartystatic->fetch($idtouseforthirdparty);
												if ($result > 0) {
													dol_syslog("Third party with id=".$idtouseforthirdparty." email=".$emailtouseforthirdparty." name=".$nametouseforthirdparty." name_alias=".$namealiastouseforthirdparty." was found thanks to linked contact search");
												} else {
													$errorforactions++;
													$langs->load("errors");
													$this->error = $langs->trans('ErrorFailedToLoadThirdParty', $idtouseforthirdparty, (string) $emailtouseforthirdparty, (string) $nametouseforthirdparty, (string) $namealiastouseforthirdparty);
													$this->errors[] = $this->error;
												}
											} else {
												$errorforactions++;
												$langs->load("errors");
												$this->error = $langs->trans('ErrorFailedToLoadThirdParty', $idtouseforthirdparty, (string) $emailtouseforthirdparty, (string) $nametouseforthirdparty, (string) $namealiastouseforthirdparty);
												$this->errors[] = $this->error;
											}
										} elseif ($operation['type'] == 'loadandcreatethirdparty') {
											dol_syslog("Third party with id=".$idtouseforthirdparty." email=".$emailtouseforthirdparty." name=".$nametouseforthirdparty." name_alias=".$namealiastouseforthirdparty." was not found. We try to create it.");

											// Create thirdparty
											$thirdpartystatic = new Societe($db);
											$thirdpartystatic->name = (string) $nametouseforthirdparty;
											if (!empty($namealiastouseforthirdparty)) {
												if ($namealiastouseforthirdparty != $nametouseforthirdparty) {
													$thirdpartystatic->name_alias = $namealiastouseforthirdparty;
												}
											} else {
												$thirdpartystatic->name_alias = (empty($replytostring) ? (empty($fromtext) ? '' : $fromtext) : $replytostring);
											}
											$thirdpartystatic->email = (empty($emailtouseforthirdparty) ? (empty($replyto) ? (empty($from) ? '' : $from) : $replyto) : $emailtouseforthirdparty);

											// Overwrite values with values extracted from source email
											$errorforthisaction = $this->overwritePropertiesOfObject($thirdpartystatic, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

											if ($thirdpartystatic->client && empty($thirdpartystatic->code_client)) {
												$thirdpartystatic->code_client = 'auto';
											}
											if ($thirdpartystatic->fournisseur && empty($thirdpartystatic->code_fournisseur)) {
												$thirdpartystatic->code_fournisseur = 'auto';
											}

											if ($errorforthisaction) {
												$errorforactions++;
											} else {
												$result = $thirdpartystatic->create($user);
												if ($result <= 0) {
													$errorforactions++;
													$this->error = $thirdpartystatic->error;
													$this->errors = $thirdpartystatic->errors;
												} else {
													$operationslog .= '<br>Thirdparty created -> id = '.dol_escape_htmltag((string) $thirdpartystatic->id);
												}
											}
										}
									} else {	// $result > 0 is ID of thirdparty
										dol_syslog("One and only one existing third party has been found");

										$thirdpartystatic->fetch($result);

										$operationslog .= '<br>Thirdparty already exists with id = '.dol_escape_htmltag((string) $thirdpartystatic->id)." and name ".dol_escape_htmltag($thirdpartystatic->name);
									}
								}
							}
						} elseif ($operation['type'] == 'loadandcreatecontact') { // Search and create contact
							if (empty($operation['actionparam'])) {
								$errorforactions++;
								$this->error = "Action loadandcreatecontact has empty parameter. Must be 'SET:xxx' or 'EXTRACT:(body|subject):regex' to define how to extract data";
								$this->errors[] = $this->error;
							} else {
								$contact_static = new Contact($this->db);
								// Overwrite values with values extracted from source email
								$errorforthisaction = $this->overwritePropertiesOfObject($contact_static, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);
								if ($errorforthisaction) {
									$errorforactions++;
								} else {
									if (!empty($contact_static->email) && $contact_static->email != $from) {
										$from = $contact_static->email;
									}

									$result = $contactstatic->fetch(0, null, '', $from);
									if ($result < 0) {
										$errorforactions++;
										$this->error = 'Error when getting contact with email ' . $from;
										$this->errors[] = $this->error;
										break;
									} elseif ($result == 0) {
										dol_syslog("Contact with email " . $from . " was not found. We try to create it.");
										$contactstatic = new Contact($this->db);

										// Create contact
										$contactstatic->email = $from;
										$operationslog .= '<br>We set property email='.dol_escape_htmltag($from);

										// Overwrite values with values extracted from source email
										$errorforthisaction = $this->overwritePropertiesOfObject($contactstatic, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

										if ($errorforthisaction) {
											$errorforactions++;
										} else {
											// Search country by name or code
											if (!empty($contactstatic->country)) {
												require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
												$result = getCountry('', '3', $this->db, null, 1, $contactstatic->country);
												if ($result == 'NotDefined') {
													$errorforactions++;
													$this->error = "Error country not found by this name '" . $contactstatic->country . "'";
												} elseif (!($result > 0)) {
													$errorforactions++;
													$this->error = "Error when search country by this name '" . $contactstatic->country . "'";
													$this->errors[] = $this->db->lasterror();
												} else {
													$contactstatic->country_id = $result;
													$operationslog .= '<br>We set property country_id='.dol_escape_htmltag($result);
												}
											} elseif (!empty($contactstatic->country_code)) {
												require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
												$result = getCountry($contactstatic->country_code, '3', $this->db);
												if ($result == 'NotDefined') {
													$errorforactions++;
													$this->error = "Error country not found by this code '" . $contactstatic->country_code . "'";
												} elseif (!($result > 0)) {
													$errorforactions++;
													$this->error = "Error when search country by this code '" . $contactstatic->country_code . "'";
													$this->errors[] = $this->db->lasterror();
												} else {
													$contactstatic->country_id = $result;
													$operationslog .= '<br>We set property country_id='.dol_escape_htmltag($result);
												}
											}

											if (!$errorforactions) {
												// Search state by name or code (for country if defined)
												if (!empty($contactstatic->state)) {
													require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
													$result = dol_getIdFromCode($this->db, $contactstatic->state, 'c_departements', 'nom', 'rowid');
													if (empty($result)) {
														$errorforactions++;
														$this->error = "Error state not found by this name '" . $contactstatic->state . "'";
													} elseif (!($result > 0)) {
														$errorforactions++;
														$this->error = "Error when search state by this name '" . $contactstatic->state . "'";
														$this->errors[] = $this->db->lasterror();
													} else {
														$contactstatic->state_id = $result;
														$operationslog .= '<br>We set property state_id='.dol_escape_htmltag($result);
													}
												} elseif (!empty($contactstatic->state_code)) {
													require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
													$result = dol_getIdFromCode($this->db, $contactstatic->state_code, 'c_departements', 'code_departement', 'rowid');
													if (empty($result)) {
														$errorforactions++;
														$this->error = "Error state not found by this code '" . $contactstatic->state_code . "'";
													} elseif (!($result > 0)) {
														$errorforactions++;
														$this->error = "Error when search state by this code '" . $contactstatic->state_code . "'";
														$this->errors[] = $this->db->lasterror();
													} else {
														$contactstatic->state_id = $result;
														$operationslog .= '<br>We set property state_id='.dol_escape_htmltag($result);
													}
												}
											}

											if (!$errorforactions) {
												$result = $contactstatic->create($user);
												if ($result <= 0) {
													$errorforactions++;
													$this->error = $contactstatic->error;
													$this->errors = $contactstatic->errors;
												} else {
													$operationslog .= '<br>Contact created -> id = '.dol_escape_htmltag((string) $contactstatic->id);
												}
											}
										}
									}
								}
							}
						} elseif ($operation['type'] == 'recordevent') {
							// Create event
							$actioncomm = new ActionComm($this->db);

							$alreadycreated = $actioncomm->fetch(0, '', '', $msgid);
							if ($alreadycreated == 0) {
								$operationslog .= '<br>We did not find existing actionmail with msgid='.$msgid;

								if ($projectstatic->id > 0) {
									if ($projectfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Project found from '.$projectfoundby);
									}
								}
								if ($thirdpartystatic->id > 0) {
									if ($thirdpartyfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
									}
								}
								if ($contactstatic->id > 0) {
									if ($contactfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
									}
								}

								$description = $descriptiontitle;

								$description = dol_concatdesc($description, $descriptionmeta);
								$description = dol_concatdesc($description, "-----");
								$description = dol_concatdesc($description, $messagetext);

								$descriptionfull = $description;
								if (!getDolGlobalString('MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER')) {
									$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
									$descriptionfull = dol_concatdesc($descriptionfull, $header);
								}

								// Insert record of emails sent
								$actioncomm->type_code   = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
								$actioncomm->code        = 'AC_'.$actioncode;
								$actioncomm->label       = $langs->trans("ActionAC_".$actioncode).' - '.$langs->trans("MailFrom").' '.$from;
								$actioncomm->note_private = $descriptionfull;
								$actioncomm->fk_project  = $projectstatic->id;
								$actioncomm->datep       = $dateemail;	// date of email
								$actioncomm->datef       = $dateemail;	// date of email
								$actioncomm->percentage  = -1; // Not applicable
								$actioncomm->socid       = $thirdpartystatic->id;
								$actioncomm->contact_id = $contactstatic->id;
								$actioncomm->socpeopleassigned = (!empty($contactstatic->id) ? array($contactstatic->id) : array());
								$actioncomm->authorid    = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is an email (content should be added into note)
								$actioncomm->email_msgid = $msgid;
								$actioncomm->email_from  = $fromstring;
								$actioncomm->email_sender = $sender;
								$actioncomm->email_to    = $to;
								$actioncomm->email_tocc  = $sendtocc;
								$actioncomm->email_tobcc = $sendtobcc;
								$actioncomm->email_subject = $subject;
								$actioncomm->errors_to   = '';

								if (!in_array($fk_element_type, array('societe', 'contact', 'project', 'user'))) {
									$actioncomm->fk_element  = $fk_element_id;
									$actioncomm->elementid = $fk_element_id;
									$actioncomm->elementtype = $fk_element_type;
									if (is_object($objectemail) && $objectemail->module) {
										$actioncomm->elementtype .= '@'.$objectemail->module;
									}
								}

								//$actioncomm->extraparams = $extraparams;

								// Overwrite values with values extracted from source email
								$errorforthisaction = $this->overwritePropertiesOfObject($actioncomm, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

								if ($errorforthisaction) {
									$errorforactions++;
								} else {
									$result = $actioncomm->create($user);
									if ($result <= 0) {
										$errorforactions++;
										$this->errors = $actioncomm->errors;
									} else {
										if ($fk_element_type == "ticket" && is_object($objectemail)) {
											if ($objectemail->status == Ticket::STATUS_CLOSED || $objectemail->status == Ticket::STATUS_CANCELED || $objectemail->status == Ticket::STATUS_NEED_MORE_INFO || $objectemail->status == Ticket::STATUS_WAITING) {
												if ($objectemail->fk_user_assign != null) {
													$res = $objectemail->setStatut(Ticket::STATUS_ASSIGNED);
												} else {
													$res = $objectemail->setStatut(Ticket::STATUS_NOT_READ);
												}

												if ($res) {
													$operationslog .= '<br>Ticket Re-Opened successfully -> ref='.$objectemail->ref;
												} else {
													$errorforactions++;
													$this->error = 'Error while changing the ticket status -> ref='.$objectemail->ref;
													$this->errors[] = $this->error;
												}
											}
											if (!empty($attachments)) {
												// There is an attachment for the ticket -> store attachment
												$ticket = new Ticket($this->db);
												$ticket->fetch($fk_element_id);
												$destdir = $conf->ticket->dir_output.'/'.$ticket->ref;
												if (!dol_is_dir($destdir)) {
													dol_mkdir($destdir);
												}
												if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
													foreach ($attachments as $attachment) {
														$attachment->save($destdir.'/');
													}
												} else {
													$this->getmsg($connection, $imapemail, $destdir);
												}
											}
										}

										$operationslog .= '<br>Event created -> id='.dol_escape_htmltag((string) $actioncomm->id);
									}
								}
							}
						} elseif ($operation['type'] == 'recordjoinpiece') {
							$data = [];
							if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
								foreach ($attachments as $attachment) {
									if ($attachment->getName() === 'undefined') {
										continue;
									}
									$data[$attachment->getName()] = $attachment->getContent();
								}
							} else {
								$pj = getAttachments($imapemail, $connection);
								foreach ($pj as $key => $val) {
									$data[$val['filename']] = getFileData($imapemail, (string) $val['pos'], $val['type'], $connection);
								}
							}
							if (count($data) > 0) {
								$sql = "SELECT rowid as id FROM ".MAIN_DB_PREFIX."user WHERE email LIKE '%".$this->db->escape($from)."%'";
								$resql = $this->db->query($sql);
								if ($this->db->num_rows($resql) == 0) {
									$this->errors[] = "User Not allowed to add documents ({$from})";
								}
								$arrayobject = array(
									'propale' => array('table' => 'propal',
										'fields' => array('ref'),
										'class' => 'comm/propal/class/propal.class.php',
										'object' => 'Propal'),
									'holiday' => array('table' => 'holiday',
										'fields' => array('ref'),
										'class' => 'holiday/class/holiday.class.php',
										'object' => 'Holiday'),
									'expensereport' => array('table' => 'expensereport',
										'fields' => array('ref'),
										'class' => 'expensereport/class/expensereport.class.php',
										'object' => 'ExpenseReport'),
									'recruitment/recruitmentjobposition' => array('table' => 'recruitment_recruitmentjobposition',
										'fields' => array('ref'),
										'class' => 'recruitment/class/recruitmentjobposition.class.php',
										'object' => 'RecruitmentJobPosition'),
									'recruitment/recruitmentcandidature' => array('table' => 'recruitment_recruitmentcandidature',
										'fields' => array('ref'),
										'class' => 'recruitment/class/recruitmentcandidature.class.php',
										'object' => 'RecruitmentCandidature'),
									'societe' => array('table' => 'societe',
										'fields' => array('code_client', 'code_fournisseur'),
										'class' => 'societe/class/societe.class.php',
										'object' => 'Societe'),
									'commande' => array('table' => 'commande',
										'fields' => array('ref'),
										'class' => 'commande/class/commande.class.php',
										'object' => 'Commande'),
									'expedition' => array('table' => 'expedition',
										'fields' => array('ref'),
										'class' => 'expedition/class/expedition.class.php',
										'object' => 'Expedition'),
									'contract' => array('table' => 'contrat',
										'fields' => array('ref'),
										'class' => 'contrat/class/contrat.class.php',
										'object' => 'Contrat'),
									'fichinter' => array('table' => 'fichinter',
										'fields' => array('ref'),
										'class' => 'fichinter/class/fichinter.class.php',
										'object' => 'Fichinter'),
									'ticket' => array('table' => 'ticket',
										'fields' => array('ref'),
										'class' => 'ticket/class/ticket.class.php',
										'object' => 'Ticket'),
									'knowledgemanagement' => array('table' => 'knowledgemanagement_knowledgerecord',
										'fields' => array('ref'),
										'class' => 'knowledgemanagement/class/knowledgemanagement.class.php',
										'object' => 'KnowledgeRecord'),
									'supplier_proposal' => array('table' => 'supplier_proposal',
										'fields' => array('ref'),
										'class' => 'supplier_proposal/class/supplier_proposal.class.php',
										'object' => 'SupplierProposal'),
									'fournisseur/commande' => array('table' => 'commande_fournisseur',
										'fields' => array('ref', 'ref_supplier'),
										'class' => 'fourn/class/fournisseur.commande.class.php',
										'object' => 'SupplierProposal'),
									'facture' => array('table' => 'facture',
										'fields' => array('ref'),
										'class' => 'compta/facture/class/facture.class.php',
										'object' => 'Facture'),
									'fournisseur/facture' => array('table' => 'facture_fourn',
										'fields' => array('ref', 'ref_client'),
										'class' => 'fourn/class/fournisseur.facture.class.php',
										'object' => 'FactureFournisseur'),
									'produit' => array('table' => 'product',
										'fields' => array('ref'),
										'class' => 'product/class/product.class.php',
										'object' => 'Product'),
									'productlot' => array('table' => 'product_lot',
										'fields' => array('batch'),
										'class' => 'product/stock/class/productlot.class.php',
										'object' => 'Productlot'),
									'projet' => array('table' => 'projet',
										'fields' => array('ref'),
										'class' => 'projet/class/projet.class.php',
										'object' => 'Project'),
									'projet_task' => array('table' => 'projet_task',
										'fields' => array('ref'),
										'class' => 'projet/class/task.class.php',
										'object' => 'Task'),
									'ressource' => array('table' => 'resource',
										'fields' => array('ref'),
										'class' => 'ressource/class/dolressource.class.php',
										'object' => 'Dolresource'),
									'bom' => array('table' => 'bom_bom',
										'fields' => array('ref'),
										'class' => 'bom/class/bom.class.php',
										'object' => 'BOM'),
									'mrp' => array('table' => 'mrp_mo',
										'fields' => array('ref'),
										'class' => 'mrp/class/mo.class.php',
										'object' => 'Mo'),
								);

								if (!is_object($hookmanager)) {
									include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
									$hookmanager = new HookManager($this->db);
								}
								$hookmanager->initHooks(array('emailcolector'));
								$parameters = array('arrayobject' => $arrayobject);
								$reshook = $hookmanager->executeHooks('addmoduletoeamailcollectorjoinpiece', $parameters);    // Note that $action and $object may have been modified by some hooks
								if ($reshook > 0) {
									$arrayobject = $hookmanager->resArray;
								}

								$resultobj = array();

								foreach ($arrayobject as $key => $objectdesc) {
									$sql = 'SELECT DISTINCT t.rowid ';
									$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->db->sanitize($objectdesc['table']) . ' AS t';
									$sql .= ' WHERE ';
									foreach ($objectdesc['fields'] as $field) {
										$sql .= "('" .$this->db->escape($subject) . "'  LIKE CONCAT('%',  t." . $this->db->sanitize($field) . ", '%') AND t." . $this->db->sanitize($field) . " <> '') OR ";
									}
									$sql = substr($sql, 0, -4);

									$ressqlobj = $this->db->query($sql);
									if ($ressqlobj) {
										while ($obj = $this->db->fetch_object($ressqlobj)) {
											$resultobj[$key][] = $obj->rowid;
										}
									}
								}
								$dirs = array();
								foreach ($resultobj as $mod => $ids) {
									$moddesc = $arrayobject[$mod];
									$elementpath = $mod;
									dol_include_once($moddesc['class']);
									$objectmanaged = new $moddesc['object']($this->db);
									'@phan-var-force CommonObject $objectmanaged';
									foreach ($ids as $val) {
										$res = $objectmanaged->fetch($val);
										if ($res) {
											$path = ($objectmanaged->entity > 1 ? "/" . $objectmanaged->entity : '');
											$dirs[] = DOL_DATA_ROOT . $path . "/" . $elementpath . '/' . dol_sanitizeFileName($objectmanaged->ref) . '/';
										} else {
											$this->errors[] = 'object not found';
										}
									}
								}
								foreach ($dirs as $target) {
									$prefix = $this->actions[$this->id]['actionparam'];
									foreach ($data as $filename => $content) {
										$resr = saveAttachment($target, $prefix . '_' . $filename, $content);
										if ($resr == -1) {
											$this->errors[] = 'Doc not saved';
										}
									}
								}

								$operationslog .= '<br>Save attachment files on disk';
							} else {
								$this->errors[] = 'no joined piece';

								$operationslog .= '<br>No joinded files';
							}
						} elseif ($operation['type'] == 'project') {
							// Create project / lead
							$projecttocreate = new Project($this->db);
							$alreadycreated = $projecttocreate->fetch(0, '', '', $msgid);
							if ($alreadycreated == 0) {
								if ($thirdpartystatic->id > 0) {
									$projecttocreate->socid = $thirdpartystatic->id;
									if ($thirdpartyfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
									}
								}
								if ($contactstatic->id > 0) {
									$projecttocreate->contact_id = $contactstatic->id;
									if ($contactfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
									}
								}

								$description = $descriptiontitle;

								$description = dol_concatdesc($description, $descriptionmeta);
								$description = dol_concatdesc($description, "-----");
								$description = dol_concatdesc($description, $messagetext);

								$descriptionfull = $description;
								if (!getDolGlobalString('MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER')) {
									$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
									$descriptionfull = dol_concatdesc($descriptionfull, $header);
								}

								$id_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'rowid');
								$percent_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'percent');

								$projecttocreate->title = $subject;
								$projecttocreate->date_start = $dateemail;	// date of email
								$projecttocreate->date_end = 0;
								$projecttocreate->opp_status = $id_opp_status;
								$projecttocreate->opp_percent = $percent_opp_status;
								$projecttocreate->description = dol_concatdesc(dolGetFirstLineOfText(dol_string_nohtmltag($description, 2), 10), '...'.$langs->transnoentities("SeePrivateNote").'...');
								$projecttocreate->note_private = $descriptionfull;
								$projecttocreate->entity = $conf->entity;
								// Fields when action is an email (content should be added into agenda event)
								$projecttocreate->email_date    = $dateemail;
								$projecttocreate->email_msgid   = $msgid;
								$projecttocreate->email_from    = $fromstring;
								$projecttocreate->email_sender  = $sender;
								$projecttocreate->email_to      = $to;
								$projecttocreate->email_tocc    = $sendtocc;
								$projecttocreate->email_tobcc   = $sendtobcc;
								$projecttocreate->email_subject = $subject;
								$projecttocreate->errors_to     = '';

								$savesocid = $projecttocreate->socid;

								// Overwrite values with values extracted from source email.
								// This may overwrite any $projecttocreate->xxx properties.
								$errorforthisaction = $this->overwritePropertiesOfObject($projecttocreate, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);
								$modele = null;

								// Set project ref if not yet defined
								if (empty($projecttocreate->ref)) {
									// Get next Ref
									$defaultref = '';
									$modele = getDolGlobalString('PROJECT_ADDON', 'mod_project_simple');

									// Search template files
									$file = '';
									$classname = '';
									$reldir = '';
									$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
									foreach ($dirmodels as $reldir) {
										$file = dol_buildpath($reldir."core/modules/project/".$modele.'.php', 0);
										if (file_exists($file)) {
											$classname = $modele;
											break;
										}
									}

									if ($classname !== '') {
										if ($savesocid > 0) {
											if ($savesocid != $projecttocreate->socid) {
												$errorforactions++;
												setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$projecttocreate->socid.') by setting socid in operation with a different value', null, 'errors');
											}
										} else {
											if ($projecttocreate->socid > 0) {
												$thirdpartystatic->fetch($projecttocreate->socid);
											}
										}

										$result = dol_include_once($reldir."core/modules/project/".$modele.'.php');
										$modModuleToUseForNextValue = new $classname();
										'@phan-var-force ModeleNumRefProjects $modModuleToUseForNextValue';
										$defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $projecttocreate);
									}
									$projecttocreate->ref = $defaultref;
								}


								if ($errorforthisaction) {
									$errorforactions++;
								} else {
									if (empty($projecttocreate->ref) || (is_numeric($projecttocreate->ref) && $projecttocreate->ref <= 0)) {
										$errorforactions++;
										$this->error = 'Failed to create project: Can\'t get a valid value for the field ref with numbering template = '.$modele.', thirdparty id = '.$thirdpartystatic->id;

										$operationslog .= '<br>'.$this->error;
									} else {
										// Create project
										$result = $projecttocreate->create($user);
										if ($result <= 0) {
											$errorforactions++;
											$this->error = 'Failed to create project: '.$langs->trans($projecttocreate->error);
											$this->errors = $projecttocreate->errors;

											$operationslog .= '<br>'.$this->error;
										} else {
											if ($attachments) {
												$destdir = $conf->project->dir_output.'/'.$projecttocreate->ref;
												if (!dol_is_dir($destdir)) {
													dol_mkdir($destdir);
												}
												if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
													foreach ($attachments as $attachment) {
														// $attachment->save($destdir.'/');
														$typeattachment = (string) $attachment->getDisposition();
														$filename = $attachment->getFilename();
														$content = $attachment->getContent();
														$this->saveAttachment($destdir, $filename, $content);
													}
												} else {
													$this->getmsg($connection, $imapemail, $destdir);
												}

												$operationslog .= '<br>Project created with attachments -> id='.dol_escape_htmltag((string) $projecttocreate->id);
											} else {
												$operationslog .= '<br>Project created without attachments -> id='.dol_escape_htmltag((string) $projecttocreate->id);
											}
										}
									}
								}
							} else {
								dol_syslog("Project already exists for msgid = ".dol_escape_htmltag($msgid).", so we do not recreate it.");

								$operationslog .= '<br>Project already exists for msgid ='.dol_escape_htmltag($msgid);
							}
						} elseif ($operation['type'] == 'ticket') {
							// Create ticket
							$tickettocreate = new Ticket($this->db);
							if ($ticketalreadyexists == 0) {
								if ($thirdpartystatic->id > 0) {
									$tickettocreate->socid = $thirdpartystatic->id;
									$tickettocreate->fk_soc = $thirdpartystatic->id;
									if ($thirdpartyfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
									}
								}
								if ($contactstatic->id > 0) {
									$tickettocreate->contact_id = $contactstatic->id;
									if ($contactfoundby) {
										$descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
									}
								}

								$description = $descriptiontitle;

								$description = dol_concatdesc($description, $descriptionmeta);
								$description = dol_concatdesc($description, "-----");
								$description = dol_concatdesc($description, $messagetext);

								$descriptionfull = $description;
								if (!getDolGlobalString('MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER')) {
									$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
									$descriptionfull = dol_concatdesc($descriptionfull, $header);
								}

								$tickettocreate->subject = $subject;
								$tickettocreate->message = $description;
								$tickettocreate->type_code = (getDolGlobalString('MAIN_EMAILCOLLECTOR_TICKET_TYPE_CODE', dol_getIdFromCode($this->db, 1, 'c_ticket_type', 'use_default', 'code', 1)));
								$tickettocreate->category_code = (getDolGlobalString('MAIN_EMAILCOLLECTOR_TICKET_CATEGORY_CODE', dol_getIdFromCode($this->db, 1, 'c_ticket_category', 'use_default', 'code', 1)));
								$tickettocreate->severity_code = (getDolGlobalString('MAIN_EMAILCOLLECTOR_TICKET_SEVERITY_CODE', dol_getIdFromCode($this->db, 1, 'c_ticket_severity', 'use_default', 'code', 1)));
								$tickettocreate->origin_email = $from;
								$tickettocreate->origin_replyto = (!empty($replyto) ? $replyto : null);
								$tickettocreate->origin_references = (!empty($headers['References']) ? $headers['References'] : null);
								$tickettocreate->fk_user_create = $user->id;
								$tickettocreate->datec = dol_now();
								$tickettocreate->fk_project = $projectstatic->id;
								$tickettocreate->notify_tiers_at_create = getDolGlobalInt('TICKET_CHECK_NOTIFY_THIRDPARTY_AT_CREATION');
								$tickettocreate->note_private = $descriptionfull;
								$tickettocreate->entity = $conf->entity;
								// Fields when action is an email (content should be added into agenda event)
								$tickettocreate->email_date    = $dateemail;
								$tickettocreate->email_msgid   = $msgid;
								$tickettocreate->email_from    = $fromstring;
								$tickettocreate->email_sender  = $sender;
								$tickettocreate->email_to      = $to;
								$tickettocreate->email_tocc    = $sendtocc;
								$tickettocreate->email_tobcc   = $sendtobcc;
								$tickettocreate->email_subject = $subject;
								$tickettocreate->errors_to     = '';

								//$tickettocreate->fk_contact = $contactstatic->id;

								$savesocid = $tickettocreate->socid;

								// Overwrite values with values extracted from source email.
								// This may overwrite any $projecttocreate->xxx properties.
								$errorforthisaction = $this->overwritePropertiesOfObject($tickettocreate, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

								$modele = 'UNDEFINED';
								// Set ticket ref if not yet defined
								if (empty($tickettocreate->ref)) {
									// Get next Ref
									$defaultref = '';
									$modele = getDolGlobalString('TICKET_ADDON', 'mod_ticket_simple');

									// Search template files
									$file = '';
									$classname = '';
									$reldir = '';
									$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
									foreach ($dirmodels as $reldir) {
										$file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
										if (file_exists($file)) {
											$classname = $modele;
											break;
										}
									}

									if ($classname !== '') {
										if ($savesocid > 0) {
											if ($savesocid != $tickettocreate->socid) {
												$errorforactions++;
												setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$tickettocreate->socid.') by setting socid in operation with a different value', null, 'errors');
											}
										} else {
											if ($tickettocreate->socid > 0) {
												$thirdpartystatic->fetch($tickettocreate->socid);
											}
										}

										$result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
										$modModuleToUseForNextValue = new $classname();
										'@phan-var-force ModeleNumRefTicket $modModuleToUseForNextValue';
										$defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $tickettocreate);
									}
									$tickettocreate->ref = $defaultref;
								}

								if ($errorforthisaction) {
									$errorforactions++;
								} else {
									if (is_numeric($tickettocreate->ref) && $tickettocreate->ref <= 0) {
										$errorforactions++;
										$this->error = 'Failed to create ticket: Can\'t get a valid value for the field ref with numbering template = '.$modele.', thirdparty id = '.$thirdpartystatic->id;
									} else {
										// Create ticket
										$tickettocreate->context['actionmsg2'] = $langs->trans("ActionAC_EMAIL_IN").' - '.$langs->trans("TICKET_CREATEInDolibarr");
										$tickettocreate->context['actionmsg'] = $langs->trans("ActionAC_EMAIL_IN").' - '.$langs->trans("TICKET_CREATEInDolibarr");
										//$tickettocreate->email_fields_no_propagate_in_actioncomm = 0;

										// Add sender to context array to make sure that confirmation e-mail can be sent by trigger script
										$sender_contact = new Contact($this->db);
										$sender_contact->fetch(0, null, '', $from);
										if (!empty($sender_contact->id)) {
											$tickettocreate->context['contactid'] = $sender_contact->id;
										}

										$result = $tickettocreate->create($user);
										if ($result <= 0) {
											$errorforactions++;
											$this->error = 'Failed to create ticket: '.$langs->trans($tickettocreate->error);
											$this->errors = $tickettocreate->errors;
										} else {
											if ($attachments) {
												$destdir = $conf->ticket->dir_output.'/'.$tickettocreate->ref;
												if (!dol_is_dir($destdir)) {
													dol_mkdir($destdir);
												}
												if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
													foreach ($attachments as $attachment) {
														// $attachment->save($destdir.'/');
														$typeattachment = (string) $attachment->getDisposition();
														$filename = $attachment->getName();
														$content = $attachment->getContent();
														$this->saveAttachment($destdir, $filename, $content);
													}
												} else {
													$this->getmsg($connection, $imapemail, $destdir);
												}

												$operationslog .= '<br>Ticket created with attachments -> id='.dol_escape_htmltag((string) $tickettocreate->id);
											} else {
												$operationslog .= '<br>Ticket created without attachments -> id='.dol_escape_htmltag((string) $tickettocreate->id);
											}
										}
									}
								}
							}
						} elseif ($operation['type'] == 'candidature') {
							// Create candidature
							$candidaturetocreate = new RecruitmentCandidature($this->db);

							$alreadycreated = $candidaturetocreate->fetch(0, '', $msgid);
							if ($alreadycreated == 0) {
								$description = $descriptiontitle;
								$description = dol_concatdesc($description, "-----");
								$description = dol_concatdesc($description, $descriptionmeta);
								$description = dol_concatdesc($description, "-----");
								$description = dol_concatdesc($description, $messagetext);

								$descriptionfull = $description;
								$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
								$descriptionfull = dol_concatdesc($descriptionfull, $header);

								$candidaturetocreate->subject = $subject;
								$candidaturetocreate->message = $description;
								$candidaturetocreate->type_code = 0;
								$candidaturetocreate->category_code = null;
								$candidaturetocreate->severity_code = null;
								$candidaturetocreate->email = $from;
								//$candidaturetocreate->lastname = $langs->trans("Anonymous").' - '.$from;
								$candidaturetocreate->fk_user_creat = $user->id;
								$candidaturetocreate->date_creation = dol_now();
								$candidaturetocreate->fk_project = $projectstatic->id;
								$candidaturetocreate->description = $description;
								$candidaturetocreate->note_private = $descriptionfull;
								$candidaturetocreate->entity = $conf->entity;
								$candidaturetocreate->email_msgid = $msgid;
								$candidaturetocreate->email_date = $dateemail;		// date of email
								$candidaturetocreate->status = $candidaturetocreate::STATUS_DRAFT;
								//$candidaturetocreate->fk_contact = $contactstatic->id;

								// Overwrite values with values extracted from source email.
								// This may overwrite any $projecttocreate->xxx properties.
								$errorforthisaction = $this->overwritePropertiesOfObject($candidaturetocreate, $operation['actionparam'], $messagetext, $subject, $header, $operationslog);

								// Set candidature ref if not yet defined
								/*if (empty($candidaturetocreate->ref))				We do not need this because we create object in draft status
								 {
								 // Get next Ref
								 $defaultref = '';
								 $modele = empty($conf->global->CANDIDATURE_ADDON) ? 'mod_candidature_simple' : $conf->global->CANDIDATURE_ADDON;

								 // Search template files
								 $file = ''; $classname = ''; $filefound = 0; $reldir = '';
								 $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
								 foreach ($dirmodels as $reldir)
								 {
								 $file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
								 if (file_exists($file)) {
								 $filefound = 1;
								 $classname = $modele;
								 break;
								 }
								 }

								 if ($filefound) {
								 if ($savesocid > 0) {
								 if ($savesocid != $candidaturetocreate->socid) {
								 $errorforactions++;
								 setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$candidaturetocreate->socid.') by setting socid in operation with a different value', null, 'errors');
								 }
								 } else {
								 if ($candidaturetocreate->socid > 0)
								 {
								 $thirdpartystatic->fetch($candidaturetocreate->socid);
								 }
								 }

								 $result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
								 $modModuleToUseForNextValue = new $classname;
								'@phan-var-force ModeleNumRefTicket $modModuleToUseForNextValue';
								 $defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $tickettocreate);
								 }
								 $candidaturetocreate->ref = $defaultref;
								 }*/

								if ($errorforthisaction) {
									$errorforactions++;
								} else {
									// Create project
									$result = $candidaturetocreate->create($user);
									if ($result <= 0) {
										$errorforactions++;
										$this->error = 'Failed to create candidature: '.implode(', ', $candidaturetocreate->errors);
										$this->errors = $candidaturetocreate->errors;
									}

									$operationslog .= '<br>Candidature created without attachments -> id='.dol_escape_htmltag((string) $candidaturetocreate->id);
								}
							}
						} elseif (substr($operation['type'], 0, 4) == 'hook') {
							// Create event specific on hook
							// this code action is hook..... for support this call
							if (!is_object($hookmanager)) {
								include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
								$hookmanager = new HookManager($this->db);
							}
							$hookmanager->initHooks(['emailcolector']);

							$parameters = array(
								'connection' =>  $connection,
								'imapemail' => $imapemail,
								'overview' => $overview,

								'from' => $from,
								'fromtext' => $fromtext,

								'actionparam' =>  $operation['actionparam'],

								'thirdpartyid' => $thirdpartyid,
								'objectid' => $objectid,
								'objectemail' => $objectemail,

								'messagetext' => $messagetext,
								'subject' => $subject,
								'header' => $header,
								'attachments' => $attachments,
							);
							$reshook = $hookmanager->executeHooks('doCollectImapOneCollector', $parameters, $this, $operation['type']);

							if ($reshook < 0) {
								$errorforthisaction++;
								$this->error = $hookmanager->resPrint;
							}
							if ($errorforthisaction) {
								$errorforactions++;
								$operationslog .= '<br>Hook doCollectImapOneCollector executed with error';
							} else {
								$operationslog .= '<br>Hook doCollectImapOneCollector executed without error';
							}
						}

						if (!$errorforactions) {
							$nbactiondoneforemail++;
						}
					}
				}

				// Error for email or not ?
				if (!$errorforactions) {
					if (!empty($targetdir)) {
						if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
							// Move mail using PHP-IMAP
							dol_syslog("EmailCollector::doCollectOneCollector move message ".($imapemail->getHeader()->get('subject'))." to ".$targetdir, LOG_DEBUG);
							$operationslog .= '<br>Move mail '.($this->uidAsString($imapemail)).' - '.$msgid.' - '.$imapemail->getHeader()->get('subject').' to '.$targetdir;

							$arrayofemailtodelete[$this->uidAsString($imapemail)] = $imapemail;
							// Note: Real move is done later using $arrayofemailtodelete
						} else {
							dol_syslog("EmailCollector::doCollectOneCollector move message ".($this->uidAsString($imapemail))." to ".$connectstringtarget, LOG_DEBUG);
							$operationslog .= '<br>Move mail '.($this->uidAsString($imapemail)).' - '.$msgid;

							$arrayofemailtodelete[$imapemail] = $msgid;
							// Note: Real move is done later using $arrayofemailtodelete
						}
					} else {
						if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
							dol_syslog("EmailCollector::doCollectOneCollector message ".($this->uidAsString($imapemail))." '".($imapemail->getHeader()->get('subject'))."' using this->host=".$this->host.", this->access_type=".$this->acces_type." was set to read", LOG_DEBUG);
						} else {
							dol_syslog("EmailCollector::doCollectOneCollector message ".($this->uidAsString($imapemail))." to ".$connectstringtarget." was set to read", LOG_DEBUG);
						}
					}
				} else {
					$errorforemail++;
				}


				unset($objectemail);
				unset($projectstatic);
				unset($thirdpartystatic);
				unset($contactstatic);

				$nbemailprocessed++;

				if (!$errorforemail) {
					$nbactiondone += $nbactiondoneforemail;
					$nbemailok++;

					if (empty($mode)) {
						$this->db->commit();
					} else {
						$this->db->rollback();
					}

					// Stop the loop to process email if we reach maximum collected per collect
					if ($this->maxemailpercollect > 0 && $nbemailok >= $this->maxemailpercollect) {
						dol_syslog("EmailCollect::doCollectOneCollector We reach maximum of ".$nbemailok." collected with success, so we stop this collector now.");
						$datelastok = strtotime($headers['Date']); // Set datetime
						break;
					}
				} else {
					$error++;

					$this->db->rollback();
				}
			}

			$output = $langs->trans('XEmailsDoneYActionsDone', $nbemailprocessed, $nbemailok, $nbactiondone);

			dol_syslog("End of loop on emails", LOG_INFO, -1);
		} else {
			$langs->load("admin");
			$output = $langs->trans('NoNewEmailToProcess');
			$output .= ' (defaultlang='.$langs->defaultlang.')';
		}

		// Disconnect
		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			// We sort to move/delete array with the more recent first (with higher number) so renumbering does not affect number of others to delete
			krsort($arrayofemailtodelete, SORT_NUMERIC);

			foreach ($arrayofemailtodelete as $imapemailnum => $imapemail) {
				dol_syslog("EmailCollect::doCollectOneCollector delete email ".$imapemailnum);

				$operationslog .= "<br> move email ".$imapemailnum.($mode > 0 ? ' (test)' : '');

				if (empty($mode) && empty($error)) {
					$tmptargetdir = $targetdir;
					if (!getDolGlobalString('MAIL_DISABLE_UTF7_ENCODE_OF_DIR')) {
						$tmptargetdir = $this->getEncodedUtf7($targetdir);
					}

					$result = 0;
					try {
						$result = $imapemail->move($tmptargetdir, false);
					} catch (Exception $e) {
						// Nothing to do. $result will remain 0
						$operationslog .= '<br>Exception !!!! '.$e->getMessage();
					}
					if (empty($result)) {
						dol_syslog("Failed to move email into target directory ".$targetdir);
						$operationslog .= '<br>Failed to move email into target directory '.$targetdir;
						$error++;
					}
				}
			}

			if (empty($mode) && empty($error)) {
				dol_syslog("Expunge", LOG_DEBUG);
				$operationslog .= "<br>Expunge";

				$client->expunge(); // To validate all moves
			}

			$client->disconnect();
		} else {
			foreach ($arrayofemailtodelete as $imapemail => $msgid) {
				dol_syslog("EmailCollect::doCollectOneCollector delete email ".$imapemail." ".$msgid);

				$operationslog .= "<br> delete email ".$imapemail." ".$msgid.($mode > 0 ? ' (test)' : '');

				if (empty($mode) && empty($error)) {
					$res = imap_mail_move($connection, $imapemail, $targetdir, CP_UID);
					if (!$res) {
						// $errorforemail++;  // Not in loop, not needed, not initialised
						$this->error = imap_last_error();
						$this->errors[] = $this->error;

						$operationslog .= '<br>Error in move '.$this->error;

						dol_syslog(imap_last_error());
					}
				}
			}

			if (empty($mode) && empty($error)) {
				dol_syslog("Expunge", LOG_DEBUG);
				$operationslog .= "<br>Expunge";

				imap_expunge($connection); // To validate all moves
			}
			imap_close($connection);
		}

		$this->datelastresult = $now;
		$this->lastresult = $output;
		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			$this->debuginfo .= 'IMAP search array used : '.$search;
		} else {
			$this->debuginfo .= 'IMAP search string used : '.$search;
		}
		if ($searchhead) {
			$this->debuginfo .= '<br>Then search string into email header : '.dol_escape_htmltag($searchhead);
		}
		if ($operationslog) {
			$this->debuginfo .= $operationslog;
		}

		if (empty($error) && empty($mode)) {
			$this->datelastok = $datelastok;
		}

		if (!empty($this->errors)) {
			$this->lastresult .= "<br>".implode("<br>", $this->errors);
		}
		$this->codelastresult = ($error ? 'KO' : 'OK');

		if (empty($mode)) {
			$this->update($user);
		}

		dol_syslog("EmailCollector::doCollectOneCollector end", LOG_INFO);

		return $error ? -1 : 1;
	}



	// Loop to get part html and plain. Code found on PHP imap_fetchstructure documentation

	/**
	 * getmsg
	 *
	 * @param 	IMAP\Connection|resource $mbox   	Structure
	 * @param 	int				$mid		Message Id / Message Number  Email
	 * @param 	string			$destdir    Target dir for attachments. Leave blank to parse without writing to disk.
	 * @return 	void
	 */
	private function getmsg($mbox, $mid, $destdir = '')
	{
		// input $mbox = IMAP stream, $mid = message id
		// output all the following:
		global $charset, $htmlmsg, $plainmsg, $attachments;
		$htmlmsg = $plainmsg = $charset = '';
		$attachments = array();

		// HEADER
		//$h = imap_header($mbox,$mid);
		// add code here to get date, from, to, cc, subject...

		// BODY
		$s = imap_fetchstructure($mbox, $mid, FT_UID);


		if (!$s->parts) {
			// simple
			$this->getpart($mbox, $mid, $s, '0'); // pass '0' as part-number
		} else {
			// multipart: cycle through each part
			foreach ($s->parts as $partno0 => $p) {
				$this->getpart($mbox, $mid, $p, (string) ($partno0 + 1), $destdir);
			}
		}
	}

	/* partno string
	 0 multipart/mixed
	 1 multipart/alternative
	 1.1 text/plain
	 1.2 text/html
	 2 message/rfc822
	 2 multipart/mixed
	 2.1 multipart/alternative
	 2.1.1 text/plain
	 2.1.2 text/html
	 2.2 message/rfc822
	 2.2 multipart/alternative
	 2.2.1 text/plain
	 2.2.2 text/html
	 */

	/**
	 * Sub function for getpart(). Only called by createPartArray() and itself.
	 *
	 * @param 	IMAP\Connection|resource	$mbox	Structure
	 * @param 	int				$mid			Message Id / Message Number
	 * @param 	Object			$p              Object p
	 * @param   string			$partno			Partno / Section
	 * @param 	string			$destdir	    Target dir for attachments. Leave blank to parse without writing to disk.
	 * @return	void
	 */
	private function getpart($mbox, $mid, $p, $partno, $destdir = '')
	{
		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
		global $htmlmsg, $plainmsg, $charset, $attachments;

		// DECODE DATA
		$data = ($partno) ?
		imap_fetchbody($mbox, $mid, $partno, FT_UID) : // multipart
		imap_body($mbox, $mid, FT_UID); // simple
		// Any part may be encoded, even plain text messages, so check everything.
		if ($p->encoding == 4) {
			$data = quoted_printable_decode($data);
		} elseif ($p->encoding == 3) {
			$data = base64_decode($data);
		}

		// PARAMETERS
		// get all parameters, like charset, filenames of attachments, etc.
		$params = array();
		if ($p->parameters) {
			foreach ($p->parameters as $x) {
				$params[strtolower($x->attribute)] = $x->value;
			}
		}
		if (!empty($p->dparameters)) {
			foreach ($p->dparameters as $x) {
				$params[strtolower($x->attribute)] = $x->value;
			}
		}
		'@phan-var-force array{filename?:string,name?:string,charset?:string} $params';

		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		if (!empty($params['filename']) || !empty($params['name'])) {
			// filename may be given as 'Filename' or 'Name' or both
			$filename = $params['filename'] ?? $params['name'];
			// filename may be encoded, so see imap_mime_header_decode()
			$attachments[$filename] = $data; // this is a problem if two files have same name

			if (strlen($destdir)) {
				if (substr($destdir, -1) != '/') {
					$destdir .= '/';
				}

				// Get file name (with extension)
				$file_name_complete = $filename;
				$destination = $destdir.$file_name_complete;

				// Extract file extension
				$extension = pathinfo($file_name_complete, PATHINFO_EXTENSION);

				// Extract file name without extension
				$file_name = pathinfo($file_name_complete, PATHINFO_FILENAME);

				// Save an original file name variable to track while renaming if file already exists
				$file_name_original = $file_name;

				// Increment file name by 1
				$num = 1;

				/**
				 * Check if the same file name already exists in the upload folder,
				 * append increment number to the original filename
				 */
				while (file_exists($destdir.$file_name.".".$extension)) {
					$file_name = $file_name_original . ' (' . $num . ')';
					$file_name_complete = $file_name . "." . $extension;
					$destination = $destdir.$file_name_complete;
					$num++;
				}

				$destination = dol_sanitizePathName($destination);

				file_put_contents($destination, $data);
			}
		}

		// TEXT
		if ($p->type == 0 && $data) {
			if (!empty($params['charset'])) {
				$data = $this->convertStringEncoding($data, $params['charset']);
			}
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if (strtolower($p->subtype) == 'plain') {
				$plainmsg .= trim($data)."\n\n";
			} else {
				$htmlmsg .= $data."<br><br>";
			}
			$charset = $params['charset']; // assume all parts are same charset
		} elseif ($p->type == 2 && $data) {
			// EMBEDDED MESSAGE
			// Many bounce notifications embed the original message as type 2,
			// but AOL uses type 1 (multipart), which is not handled here.
			// There are no PHP functions to parse embedded messages,
			// so this just appends the raw source to the main message.
			if (!empty($params['charset'])) {
				$data = $this->convertStringEncoding($data, $params['charset']);
			}
			$plainmsg .= $data."\n\n";
		}

		// SUBPART RECURSION
		if (!empty($p->parts)) {
			foreach ($p->parts as $partno0 => $p2) {
				$this->getpart($mbox, $mid, $p2, $partno.'.'.($partno0 + 1), $destdir); // 1.2, 1.2.1, etc.
			}
		}
	}

	/**
	 * Converts a string from one encoding to another.
	 *
	 * @param  string 	$string			String to convert
	 * @param  string 	$fromEncoding	String encoding
	 * @param  string 	$toEncoding		String return encoding
	 * @return string 					Converted string if conversion was successful, or the original string if not
	 * @throws Exception
	 */
	protected function convertStringEncoding($string, $fromEncoding, $toEncoding = 'UTF-8')
	{
		if (!$string || $fromEncoding == $toEncoding) {
			return $string;
		}
		$convertedString = function_exists('iconv') ? @iconv($fromEncoding, $toEncoding.'//IGNORE', $string) : null;
		if (!$convertedString && extension_loaded('mbstring')) {
			$convertedString = @mb_convert_encoding($string, $toEncoding, $fromEncoding);
		}
		if (!$convertedString) {
			throw new Exception('Mime string encoding conversion failed');
		}
		return $convertedString;
	}

	/**
	 * Decode a subject string according to RFC2047
	 * Example: '=?Windows-1252?Q?RE=A0:_ABC?=' => 'RE : ABC...'
	 * Example: '=?UTF-8?Q?A=C3=A9B?=' => 'AéB'
	 * Example: '=?UTF-8?B?2KLYstmF2KfbjNi0?=' =>
	 * Example: '=?utf-8?B?UkU6IG1vZHVsZSBkb2xpYmFyciBnZXN0aW9ubmFpcmUgZGUgZmljaGllcnMg?= =?utf-8?B?UsOpZsOpcmVuY2UgZGUgbGEgY29tbWFuZGUgVFVHRURJSklSIOKAkyBwYXNz?= =?utf-8?B?w6llIGxlIDIyLzA0LzIwMjA=?='
	 *
	 * @param 	string	$subject		Subject
	 * @return 	string					Decoded subject (in UTF-8)
	 */
	protected function decodeSMTPSubject($subject)
	{
		// Decode $overview[0]->subject according to RFC2047
		// Can use also imap_mime_header_decode($str)
		// Can use also mb_decode_mimeheader($str)
		// Can use also iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8')
		if (function_exists('imap_mime_header_decode') && function_exists('iconv_mime_decode')) {
			$elements = imap_mime_header_decode($subject);
			$newstring = '';
			if (!empty($elements)) {
				$num = count($elements);
				for ($i = 0; $i < $num; $i++) {
					$stringinutf8 = (in_array(strtoupper($elements[$i]->charset), array('DEFAULT', 'UTF-8')) ? $elements[$i]->text : iconv_mime_decode($elements[$i]->text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, $elements[$i]->charset));
					$newstring .= $stringinutf8;
				}
				$subject = $newstring;
			}
		} elseif (!function_exists('mb_decode_mimeheader')) {
			$subject = mb_decode_mimeheader($subject);
		} elseif (function_exists('iconv_mime_decode')) {
			$subject = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
		}

		return $subject;
	}

	/**
	 * saveAttachment
	 *
	 * @param  string $destdir	destination
	 * @param  string $filename filename
	 * @param  string $content  content
	 * @return void
	 */
	private function saveAttachment($destdir, $filename, $content)
	{
		require_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';

		$tmparraysize = getDefaultImageSizes();
		$maxwidthsmall = $tmparraysize['maxwidthsmall'];
		$maxheightsmall = $tmparraysize['maxheightsmall'];
		$maxwidthmini = $tmparraysize['maxwidthmini'];
		$maxheightmini = $tmparraysize['maxheightmini'];
		$quality = $tmparraysize['quality'];

		file_put_contents($destdir.'/'.$filename, $content);
		if (image_format_supported($filename) == 1) {
			// Create thumbs
			vignette($destdir.'/'.$filename, $maxwidthsmall, $maxheightsmall, '_small', $quality, "thumbs");
			// Create mini thumbs for image (Ratio is near 16/9)
			vignette($destdir.'/'.$filename, $maxwidthmini, $maxheightmini, '_mini', $quality, "thumbs");
		}
		addFileIntoDatabaseIndex($destdir, $filename);
	}

	/**
	 * Get UID of message as a string
	 *
	 * @param int|Webklex\PHPIMAP\Message	$imapemail		UID as int (if native IMAP) or as object (if external library)
	 * @return string						UID as string
	 */
	protected function uidAsString($imapemail)
	{
		if (is_object($imapemail)) {
			return $imapemail->getAttributes()["uid"];
		} else {
			return (string) $imapemail;
		}
	}
}
