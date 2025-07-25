<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2009-2017	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2018	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2015		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2015-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2015		Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2018-2019	Thibault FOUCART			<support@ptibogxiv.net>
 * Copyright (C) 2019		Nicolas ZABOURI 			<info@inovea-conseil.com>
 * Copyright (C) 2020		Josep Lluís Amador 			<joseplluis@lliuretic.cat>
 * Copyright (C) 2021		Waël Almoman            	<info@almoman.com>
 * Copyright (C) 2021		Philippe Grand          	<philippe.grand@atoo-net.com>
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
 *	\file       htdocs/adherents/class/adherent.class.php
 *	\ingroup    member
 *	\brief      File of class to manage members of a foundation
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';


/**
 *		Class to manage members of a foundation.
 */
class Adherent extends CommonObject
{
	use CommonPeople;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'member';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'adherent';

	/**
	 * @var string picto
	 */
	public $picto = 'member';

	/**
	 * @var string[] array of messages
	 */
	public $mesgs;

	/**
	 * @var ?string login of member
	 */
	public $login;

	/**
	 * @var ?string Clear password in memory
	 */
	public $pass;

	/**
	 * @var string Clear password in database (defined if DATABASE_PWD_ENCRYPTED=0)
	 */
	public $pass_indatabase;

	/**
	 * @var string Encrypted password in database (always defined)
	 */
	public $pass_indatabase_crypted;

	/**
	 * @var string fullname
	 */
	public $fullname;

	/**
	 * @var string
	 * @deprecated 	Use $civility_code
	 */
	public $civility_id;

	/**
	 * @var string 	The civility code, not an integer (ex: 'MR', 'MME', 'MLE', 'DR', etc.)
	 */
	public $civility_code;

	/**
	 * @var int
	 */
	public $civility;

	/**
	 * @var ?string company name
	 * @deprecated Use $company
	 * @see $company
	 */
	public $societe;

	/**
	 * @var ?string company name
	 */
	public $company;

	/**
	 * @var int Thirdparty ID
	 * @deprecated Use $socid
	 * @see $socid
	 */
	public $fk_soc;

	/**
	 * @var int socid
	 */
	public $socid;

	/**
	 * @var array<string,string> array of socialnetworks
	 */
	public $socialnetworks;

	/**
	 * @var string Phone number
	 */
	public $phone;

	/**
	 * @var string Private Phone number
	 */
	public $phone_perso;

	/**
	 * @var string Professional Phone number
	 */
	public $phone_pro;

	/**
	 * @var string Mobile phone number
	 */
	public $phone_mobile;

	/**
	 * @var string Fax number
	 */
	public $fax;

	/**
	 * @var string Function
	 */
	public $poste;

	/**
	 * @var string mor or phy
	 */
	public $morphy;

	/**
	 * @var int<0,1> Info can be public
	 */
	public $public;

	/**
	 * Default language code of member (en_US, ...)
	 * @var string
	 */
	public $default_lang;

	/**
	 * @var ?string photo of member
	 */
	public $photo;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * @var string|int
	 */
	public $datevalid;

	/**
	 * @var string gender
	 */
	public $gender;

	/**
	 * @var int|string date of birth
	 */
	public $birth;

	/**
	 * @var int id type member
	 */
	public $typeid;

	/**
	 * @var ?string label type member
	 */
	public $type;

	/**
	 * @var int need_subscription
	 */
	public $need_subscription;

	/**
	 * @var int|null user_id
	 */
	public $user_id;

	/**
	 * @var string|null user_login
	 */
	public $user_login;

	/**
	 * @var string|int
	 */
	public $datefin;


	// Fields loaded by fetch_subscriptions() from member table

	/**
	 * @var int|string|null date
	 */
	public $first_subscription_date;

	/**
	 * @var int|string|null date
	 */
	public $first_subscription_date_start;

	/**
	 * @var int|string|null date
	 */
	public $first_subscription_date_end;

	/**
	 * @var int|string|null date
	 */
	public $first_subscription_amount;

	/**
	 * @var int|string|null date
	 */
	public $last_subscription_date;

	/**
	 * @var int|string|null date
	 */
	public $last_subscription_date_start;

	/**
	 * @var int|string date
	 */
	public $last_subscription_date_end;

	/**
	 * @var int|string date
	 */
	public $last_subscription_amount;

	/**
	 * @var Subscription[]
	 */
	public $subscriptions = array();

	/**
	 * @var string ip
	 */
	public $ip;

	// Fields loaded by fetchPartnerships() from partnership table

	/**
	 * @var array<array<mixed>>
	 */
	public $partnerships = array();

	/**
	 * @var ?Facture	To store the created invoice into subscriptionComplementaryActions()
	 */
	public $invoice;


	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'length' the length of field. Example: 255, '24,8'
	 *  'label' the translation key.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'placeholder' to set the placeholder of a varchar field.
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'default' => '1', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 12, 'index' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => 3, 'notnull' => 1, 'position' => 15, 'index' => 1),
		'ref_ext' => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 20),
		'civility' => array('type' => 'varchar(6)', 'label' => 'Civility', 'enabled' => 1, 'visible' => -1, 'position' => 25),
		'lastname' => array('type' => 'varchar(50)', 'label' => 'Lastname', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'showoncombobox' => 1),
		'firstname' => array('type' => 'varchar(50)', 'label' => 'Firstname', 'enabled' => 1, 'visible' => 1, 'position' => 35, 'showoncombobox' => 1),
		'login' => array('type' => 'varchar(50)', 'label' => 'Login', 'enabled' => 1, 'visible' => 1, 'position' => 40),
		'pass' => array('type' => 'varchar(50)', 'label' => 'Pass', 'enabled' => 1, 'visible' => 3, 'position' => 45),
		'pass_crypted' => array('type' => 'varchar(128)', 'label' => 'Pass crypted', 'enabled' => 1, 'visible' => 3, 'position' => 50),
		'morphy' => array('type' => 'varchar(3)', 'label' => 'MemberNature', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 55),
		'fk_adherent_type' => array('type' => 'integer', 'label' => 'MemberType', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 60),
		'societe' => array('type' => 'varchar(128)', 'label' => 'Societe', 'enabled' => 1, 'visible' => 1, 'position' => 65, 'showoncombobox' => 2),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'address' => array('type' => 'text', 'label' => 'Address', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'zip' => array('type' => 'varchar(10)', 'label' => 'Zip', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'state_id' => array('type' => 'integer', 'label' => 'State', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'country' => array('type' => 'integer:Ccountry:core/class/ccountry.class.php', 'label' => 'Country', 'enabled' => 1, 'visible' => 1, 'position' => 95),
		'phone' => array('type' => 'varchar(30)', 'label' => 'Phone', 'enabled' => 1, 'visible' => -1, 'position' => 115),
		'phone_perso' => array('type' => 'varchar(30)', 'label' => 'Phone perso', 'enabled' => 1, 'visible' => -1, 'position' => 120),
		'phone_mobile' => array('type' => 'varchar(30)', 'label' => 'Phone mobile', 'enabled' => 1, 'visible' => -1, 'position' => 125),
		'email' => array('type' => 'varchar(255)', 'label' => 'Email', 'enabled' => 1, 'visible' => 1, 'position' => 126),
		'url' => array('type' => 'varchar(255)', 'label' => 'Url', 'enabled' => 1, 'visible' => -1, 'position' => 127),
		'socialnetworks' => array('type' => 'text', 'label' => 'Socialnetworks', 'enabled' => 1, 'visible' => 3, 'position' => 128),
		'birth' => array('type' => 'date', 'label' => 'DateOfBirth', 'enabled' => 1, 'visible' => -1, 'position' => 130),
		'gender' => array('type' => 'varchar(10)', 'label' => 'Gender', 'enabled' => 1, 'visible' => -1, 'position' => 132),
		'photo' => array('type' => 'varchar(255)', 'label' => 'Photo', 'enabled' => 1, 'visible' => -1, 'position' => 135),
		'public' => array('type' => 'smallint(6)', 'label' => 'Public', 'enabled' => 1, 'visible' => 3, 'notnull' => 1, 'position' => 145),
		'datefin' => array('type' => 'datetime', 'label' => 'DateEnd', 'enabled' => 1, 'visible' => 1, 'position' => 150),
		'default_lang' => array('type' => 'varchar(6)', 'label' => 'Default lang', 'enabled' => 1, 'visible' => -1, 'position' => 153),
		'note_public' => array('type' => 'text', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 155),
		'note_private' => array('type' => 'text', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 160),
		'datevalid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 165),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 170),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 175),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserCreation', 'enabled' => 1, 'visible' => 3, 'position' => 180),
		'fk_user_mod' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModification', 'enabled' => 1, 'visible' => 3, 'position' => 185),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => 3, 'position' => 190),
		'canvas' => array('type' => 'varchar(32)', 'label' => 'Canvas', 'enabled' => 1, 'visible' => 0, 'position' => 195),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 800),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 805),
		'statut' => array('type' => 'smallint(6)', 'label' => 'Statut', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 1000, 'arrayofkeyval' => array(-1 => 'Draft', 1 => 'Validated', 0 => 'MemberStatusResiliatedShort', -2 => 'MemberStatusExcludedShort'))
	);

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = -1;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Resiliated (membership end and was not renew)
	 */
	const STATUS_RESILIATED = 0;
	/**
	 * Excluded
	 */
	const STATUS_EXCLUDED = -2;


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->statut = self::STATUS_DRAFT;
		$this->status = self::STATUS_DRAFT;
		// l'adherent n'est pas public par default
		$this->public = 0;
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
		// les champs optionnels sont vides
		$this->array_options = array();

		$this->fields['ref_ext']['visible'] = getDolGlobalInt('MAIN_LIST_SHOW_REF_EXT');
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function sending an email to the current member with the text supplied in parameter.
	 *
	 *  @param	string		$text				Content of message (not html entities encoded)
	 *  @param	string		$subject			Subject of message
	 *  @param 	string[]	$filename_list      Array of attached files
	 *  @param 	string[]	$mimetype_list      Array of mime types of attached files
	 *  @param 	string[]	$mimefilename_list  Array of public names of attached files
	 *  @param 	string		$addr_cc            Email cc
	 *  @param 	string		$addr_bcc           Email bcc
	 *  @param 	int			$deliveryreceipt	Ask a delivery receipt
	 *  @param	int			$msgishtml			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *  @param	string		$errors_to			errors to
	 *  @param	string		$moreinheader		Add more html headers
	 *  @deprecated since V18
	 *  @see sendEmail()
	 *  @return	int								Return integer <0 if KO, >0 if OK
	 */
	public function send_an_email($text, $subject, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = "", $addr_bcc = "", $deliveryreceipt = 0, $msgishtml = -1, $errors_to = '', $moreinheader = '')
	{
		// phpcs:enable
		dol_syslog('Warning using deprecated Adherent::send_an_email', LOG_WARNING);

		return $this->sendEmail($text, $subject, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to, $moreinheader);
	}

	/**
	 *  Function sending an email to the current member with the text supplied in parameter.
	 *
	 *  @param	string		$text				Content of message (not html entities encoded)
	 *  @param	string		$subject			Subject of message
	 *  @param 	string[]	$filename_list      Array of attached files
	 *  @param 	string[]	$mimetype_list      Array of mime types of attached files
	 *  @param 	string[]	$mimefilename_list  Array of public names of attached files
	 *  @param 	string		$addr_cc            Email cc
	 *  @param 	string		$addr_bcc           Email bcc
	 *  @param 	int			$deliveryreceipt	Ask a delivery receipt
	 *  @param	int			$msgishtml			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *  @param	string		$errors_to			errors to
	 *  @param	string		$moreinheader		Add more html headers
	 * 	@since V18
	 *  @return	int								Return integer <0 if KO, >0 if OK
	 */
	public function sendEmail($text, $subject, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = "", $addr_bcc = "", $deliveryreceipt = 0, $msgishtml = -1, $errors_to = '', $moreinheader = '')
	{
		global $conf, $langs;

		// Detect if message is HTML
		if ($msgishtml == -1) {
			$msgishtml = 0;
			if (dol_textishtml($text, 0)) {
				$msgishtml = 1;
			}
		}

		dol_syslog('sendEmail msgishtml='.$msgishtml);

		$texttosend = $this->makeSubstitution($text);
		$subjecttosend = $this->makeSubstitution($subject);
		if ($msgishtml) {
			$texttosend = dol_htmlentitiesbr($texttosend);
		}

		// Envoi mail confirmation
		$from = $conf->email_from;
		if (getDolGlobalString('ADHERENT_MAIL_FROM')) {
			$from = getDolGlobalString('ADHERENT_MAIL_FROM');
		}

		$trackid = 'mem'.$this->id;

		// Send email (substitutionarray must be done just before this)
		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($subjecttosend, (string) $this->email, $from, $texttosend, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, '', '', $trackid, $moreinheader);
		if ($mailfile->sendfile()) {
			return 1;
		} else {
			$this->error = $langs->trans("ErrorFailedToSendMail", $from, (string) $this->email).'. '.$mailfile->error;
			return -1;
		}
	}


	/**
	 * Make substitution of tags into text with value of current object.
	 *
	 * @param	string	$text       Text to make substitution to
	 * @return  string      		Value of input text string with substitutions done
	 */
	public function makeSubstitution($text)
	{
		global $langs;

		$birthday = dol_print_date($this->birth, 'day');
		$photo = isset($this->photo) ? $this->photo : '';
		$login = isset($this->login) ? $this->login : '';
		$type = isset($this->type) ? $this->type : '';
		$pass = isset($this->pass) ? $this->pass : '';

		$msgishtml = 0;
		if (dol_textishtml($text, 1)) {
			$msgishtml = 1;
		}

		$infos = '';
		if ($this->civility_id) {
			$infos .= $langs->transnoentities("UserTitle").": ".$this->getCivilityLabel()."\n";
		}
		$infos .= $langs->transnoentities("Id").": ".$this->id."\n";
		$infos .= $langs->transnoentities("Ref").": ".$this->ref."\n";
		$infos .= $langs->transnoentities("Lastname").": ".$this->lastname."\n";
		$infos .= $langs->transnoentities("Firstname").": ".$this->firstname."\n";
		$infos .= $langs->transnoentities("Company").": ".$this->company."\n";
		$infos .= $langs->transnoentities("Address").": ".$this->address."\n";
		$infos .= $langs->transnoentities("Zip").": ".$this->zip."\n";
		$infos .= $langs->transnoentities("Town").": ".$this->town."\n";
		$infos .= $langs->transnoentities("Country").": ".$this->country."\n";
		$infos .= $langs->transnoentities("EMail").": ".$this->email."\n";
		$infos .= $langs->transnoentities("PhonePro").": ".$this->phone."\n";
		$infos .= $langs->transnoentities("PhonePerso").": ".$this->phone_perso."\n";
		$infos .= $langs->transnoentities("PhoneMobile").": ".$this->phone_mobile."\n";
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			$infos .= $langs->transnoentities("Login").": ".$login."\n";
			$infos .= $langs->transnoentities("Password").": ".$pass."\n";
		}
		$infos .= $langs->transnoentities("Birthday").": ".$birthday."\n";
		$infos .= $langs->transnoentities("Photo").": ".$photo."\n";
		$infos .= $langs->transnoentities("Public").": ".yn($this->public);

		// Substitutions
		$substitutionarray = array(
			'__ID__' => $this->id,
			'__REF__' => $this->ref,
			'__MEMBER_ID__' => $this->id,
			'__CIVILITY__' => $this->getCivilityLabel(),
			'__FIRSTNAME__' => $msgishtml ? dol_htmlentitiesbr($this->firstname) : ($this->firstname ? $this->firstname : ''),
			'__LASTNAME__' => $msgishtml ? dol_htmlentitiesbr($this->lastname) : ($this->lastname ? $this->lastname : ''),
			'__FULLNAME__' => $msgishtml ? dol_htmlentitiesbr($this->getFullName($langs)) : $this->getFullName($langs),
			'__COMPANY__' => $msgishtml ? dol_htmlentitiesbr((string) $this->company) : ($this->company ? $this->company : ''),
			'__ADDRESS__' => $msgishtml ? dol_htmlentitiesbr((string) $this->address) : ($this->address ? $this->address : ''),
			'__ZIP__' => $msgishtml ? dol_htmlentitiesbr((string) $this->zip) : ($this->zip ? $this->zip : ''),
			'__TOWN__' => $msgishtml ? dol_htmlentitiesbr((string) $this->town) : ($this->town ? $this->town : ''),
			'__COUNTRY__' => $msgishtml ? dol_htmlentitiesbr($this->country) : ($this->country ? $this->country : ''),
			'__EMAIL__' => $msgishtml ? dol_htmlentitiesbr((string) $this->email) : ($this->email ? $this->email : ''),
			'__BIRTH__' => $msgishtml ? dol_htmlentitiesbr($birthday) : ($birthday ? $birthday : ''),
			'__PHOTO__' => $msgishtml ? dol_htmlentitiesbr($photo) : $photo,
			'__LOGIN__' => $msgishtml ? dol_htmlentitiesbr($login) : $login,
			'__PASSWORD__' => $msgishtml ? dol_htmlentitiesbr($pass) : $pass,
			'__PHONE__' => $msgishtml ? dol_htmlentitiesbr($this->phone) : ($this->phone ? $this->phone : ''),
			'__PHONEPRO__' => $msgishtml ? dol_htmlentitiesbr($this->phone_perso) : ($this->phone_perso ? $this->phone_perso : ''),
			'__PHONEMOBILE__' => $msgishtml ? dol_htmlentitiesbr($this->phone_mobile) : ($this->phone_mobile ? $this->phone_mobile : ''),
			'__TYPE__' => $msgishtml ? dol_htmlentitiesbr($type) : $type,
		);

		complete_substitutions_array($substitutionarray, $langs, $this);

		return make_substitutions($text, $substitutionarray, $langs);
	}


	/**
	 *	Return translated label by the nature of a adherent (physical or moral)
	 *
	 *	@param	string		$morphy		Nature of the adherent (physical or moral)
	 *  @param	int<0,2>	$addbadge	Add badge (1=Full label, 2=First letters only)
	 *	@return	string					Label
	 */
	public function getmorphylib($morphy = '', $addbadge = 0)
	{
		global $langs;
		$s = '';

		// Clean var
		if (!$morphy) {
			$morphy = $this->morphy;
		}

		if ($addbadge) {
			$labeltoshowm = $langs->trans("Moral");
			$labeltoshowp = $langs->trans("Physical");
			if ($morphy == 'phy') {
				$labeltoshow = $labeltoshowp;
				if ($addbadge == 2) {
					$labeltoshow = dol_strtoupper(dolGetFirstLetters($labeltoshowp));
					if ($labeltoshow == dol_strtoupper(dolGetFirstLetters($labeltoshowm))) {
						$labeltoshow = dol_strtoupper(dolGetFirstLetters($labeltoshowp, 2));
					}
				}
				$s .= '<span class="member-individual-back paddingleftimp paddingrightimp" title="'.$langs->trans("Physical").'">'.$labeltoshow.'</span>';
			}
			if ($morphy == 'mor') {
				$labeltoshow = $labeltoshowm;
				if ($addbadge == 2) {
					$labeltoshow = dol_strtoupper(dolGetFirstLetters($labeltoshowm));
					if ($labeltoshow == dol_strtoupper(dolGetFirstLetters($labeltoshowp))) {
						$labeltoshow = dol_strtoupper(dolGetFirstLetters($labeltoshowm, 2));
					}
				}
				$s .= '<span class="member-company-back paddingleftimp paddingrightimp" title="'.$langs->trans("Moral").'">'.$labeltoshow.'</span>';
			}
		} else {
			if ($morphy == 'phy') {
				$s = $langs->trans("Physical");
			} elseif ($morphy == 'mor') {
				$s = $langs->trans("Moral");
			}
		}

		return $s;
	}

	/**
	 *	Create a member into database
	 *
	 *	@param	User	$user        	Object user qui demande la creation
	 *	@param  int		$notrigger		1 ne declenche pas les triggers, 0 sinon
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs, $mysoc;

		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}

		// Check parameters
		if (getDolGlobalString('ADHERENT_MAIL_REQUIRED') && !isValidEmail((string) $this->email)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail", (string) $this->email);
			return -1;
		}
		if (!$this->datec) {
			$this->datec = $now;
		}
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			if (empty($this->login)) {
				$this->error = $langs->trans("ErrorWrongValueForParameterX", "Login");
				return -1;
			}
		}

		// setEntity will set entity with the right value if empty or change it for the right value if multicompany module is active
		$this->entity = setEntity($this);

		$this->db->begin();

		// Insert member
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent";
		$sql .= " (ref, datec,login,fk_user_author,fk_user_mod,fk_user_valid,morphy,fk_adherent_type,entity,import_key, ip)";
		$sql .= " VALUES (";
		$sql .= " '(PROV)'";
		$sql .= ", '".$this->db->idate($this->datec)."'";
		$sql .= ", ".($this->login ? "'".$this->db->escape($this->login)."'" : "null");
		$sql .= ", ".($user->id > 0 ? $user->id : "null"); // Can be null because member can be created by a guest or a script
		$sql .= ", null, null, '".$this->db->escape($this->morphy)."'";
		$sql .= ", ".((int) $this->typeid);
		$sql .= ", ".((int) $this->entity);
		$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= ", ".(!empty($this->ip) ? "'".$this->db->escape($this->ip)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent");
			if ($id > 0) {
				$this->id = $id;
				if (getDolGlobalString('MEMBER_CODEMEMBER_ADDON') == '') {
					// keep old numbering
					$this->ref = (string) $id;
				} else {
					// auto code
					$modfile = dol_buildpath('core/modules/member/'.getDolGlobalString('MEMBER_CODEMEMBER_ADDON').'.php', 0);
					try {
						require_once $modfile;
						$modname = getDolGlobalString('MEMBER_CODEMEMBER_ADDON');
						$modCodeMember = new $modname();
						'@phan-var-force ModeleNumRefMembers $modCodeMember';
						/** @var ModeleNumRefMembers $modCodeMember */
						$this->ref = $modCodeMember->getNextValue($mysoc, $this);
					} catch (Exception $e) {
						dol_syslog($e->getMessage(), LOG_ERR);
						$error++;
					}
				}

				// Update minor fields
				$result = $this->update($user, 1, 1, 0, 0, 'add'); // nosync is 1 to avoid update data of user
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}

				// Add link to user
				if ($this->user_id) {
					// Add link to user
					$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
					$sql .= " fk_member = ".((int) $this->id);
					$sql .= " WHERE rowid = ".((int) $this->user_id);
					dol_syslog(get_class($this)."::create", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->error = 'Failed to update user to make link with member';
						$this->db->rollback();
						return -4;
					}
				}

				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('MEMBER_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (count($this->errors)) {
					dol_syslog(get_class($this)."::create ".implode(',', $this->errors), LOG_ERR);
					$this->db->rollback();
					return -3;
				} else {
					$this->db->commit();
					return $this->id;
				}
			} else {
				$this->error = 'Failed to get last insert id';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update a member in database (standard information and password)
	 *
	 *	@param	User	$user				User making update
	 *	@param	int		$notrigger			1=disable trigger UPDATE (when called by create)
	 *	@param	int		$nosyncuser			0=Synchronize linked user (standard info), 1=Do not synchronize linked user
	 *	@param	int		$nosyncuserpass		0=Synchronize linked user (password), 1=Do not synchronize linked user
	 *	@param	int		$nosyncthirdparty	0=Synchronize linked thirdparty (standard info), 1=Do not synchronize linked thirdparty
	 * 	@param	string	$action				Current action for hookmanager
	 * 	@return	int							Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0, $nosyncuser = 0, $nosyncuserpass = 0, $nosyncthirdparty = 0, $action = 'update')
	{
		global $langs, $hookmanager;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (empty($this->country_id) && !empty($this->country_code)) {
			$country_id = getCountry($this->country_code, '3');
			$this->country_id = is_int($country_id) ? $country_id : 0;
		}

		$nbrowsaffected = 0;
		$error = 0;

		dol_syslog(get_class($this)."::update notrigger=".$notrigger.", nosyncuser=".$nosyncuser.", nosyncuserpass=".$nosyncuserpass." nosyncthirdparty=".$nosyncthirdparty.", email=".$this->email);

		// Clean parameters
		$this->lastname = trim($this->lastname) ? trim($this->lastname) : trim($this->lastname);
		$this->firstname = trim($this->firstname) ? trim($this->firstname) : trim($this->firstname);
		$this->gender = trim($this->gender);
		// $this->address = ($this->address ? $this->address : $this->address);
		// $this->zip = ($this->zip ? $this->zip : $this->zip);
		// $this->town = ($this->town ? $this->town : $this->town);
		// $this->country_id = ($this->country_id > 0 ? $this->country_id : $this->country_id);
		// $this->state_id = ($this->state_id > 0 ? $this->state_id : $this->state_id);
		// $this->note_public = ($this->note_public ? $this->note_public : $this->note_public);
		// $this->note_private = ($this->note_private ? $this->note_private : $this->note_private);
		$this->url = $this->url ? clean_url($this->url, 0) : '';
		$this->setUpperOrLowerCase();
		// Check parameters
		if (getDolGlobalString('ADHERENT_MAIL_REQUIRED') && !isValidEmail((string) $this->email)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail", (string) $this->email);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql .= " ref = '".$this->db->escape($this->ref)."'";
		$sql .= ", civility = ".($this->civility_id ? "'".$this->db->escape($this->civility_id)."'" : "null");
		$sql .= ", firstname = ".($this->firstname ? "'".$this->db->escape($this->firstname)."'" : "null");
		$sql .= ", lastname = ".($this->lastname ? "'".$this->db->escape($this->lastname)."'" : "null");
		$sql .= ", gender = ".($this->gender != -1 ? "'".$this->db->escape($this->gender)."'" : "null"); // 'man' or 'woman'
		$sql .= ", login = ".($this->login ? "'".$this->db->escape($this->login)."'" : "null");
		$sql .= ", societe = ".($this->company ? "'".$this->db->escape($this->company)."'" : ($this->societe ? "'".$this->db->escape($this->societe)."'" : "null"));
		if ($this->socid) {
			$sql .= ", fk_soc = ".($this->socid > 0 ? (int) $this->socid : "null");	 // Must be modified only when creating from a third-party
		}
		$sql .= ", address = ".($this->address ? "'".$this->db->escape($this->address)."'" : "null");
		$sql .= ", zip = ".($this->zip ? "'".$this->db->escape($this->zip)."'" : "null");
		$sql .= ", town = ".($this->town ? "'".$this->db->escape($this->town)."'" : "null");
		$sql .= ", country = ".($this->country_id > 0 ? (int) $this->country_id : "null");
		$sql .= ", state_id = ".($this->state_id > 0 ? (int) $this->state_id : "null");
		$sql .= ", email = '".$this->db->escape((string) $this->email)."'";
		$sql .= ", url = ".(!empty($this->url) ? "'".$this->db->escape($this->url)."'" : "null");
		$sql .= ", socialnetworks = ".($this->socialnetworks ? "'".$this->db->escape(json_encode($this->socialnetworks))."'" : "null");
		$sql .= ", phone = ".($this->phone ? "'".$this->db->escape($this->phone)."'" : "null");
		$sql .= ", phone_perso = ".($this->phone_perso ? "'".$this->db->escape($this->phone_perso)."'" : "null");
		$sql .= ", phone_mobile = ".($this->phone_mobile ? "'".$this->db->escape($this->phone_mobile)."'" : "null");
		$sql .= ", note_private = ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", note_public = ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", photo = ".($this->photo ? "'".$this->db->escape($this->photo)."'" : "null");
		$sql .= ", public = ".(int) $this->public;
		$sql .= ", statut = ".(int) $this->statut;
		$sql .= ", default_lang = ".(!empty($this->default_lang) ? "'".$this->db->escape($this->default_lang)."'" : "null");
		$sql .= ", fk_adherent_type = ".(int) $this->typeid;
		$sql .= ", morphy = '".$this->db->escape($this->morphy)."'";
		$sql .= ", birth = ".($this->birth ? "'".$this->db->idate($this->birth)."'" : "null");

		if ($this->datefin) {
			$sql .= ", datefin = '".$this->db->idate($this->datefin)."'"; // Must be modified only when deleting a subscription
		}
		if ($this->datevalid) {
			$sql .= ", datevalid = '".$this->db->idate($this->datevalid)."'"; // Must be modified only when validating a member
		}
		$sql .= ", fk_user_mod = ".($user->id > 0 ? $user->id : 'null'); // Can be null because member can be create by a guest
		$sql .= " WHERE rowid = ".((int) $this->id);

		// If we change the type of membership, we set also label of new type..
		'@phan-var-force Adherent $oldcopy';
		if (!empty($this->oldcopy) && $this->typeid != $this->oldcopy->typeid) {
			$sql2 = "SELECT libelle as label";
			$sql2 .= " FROM ".MAIN_DB_PREFIX."adherent_type";
			$sql2 .= " WHERE rowid = ".((int) $this->typeid);
			$resql2 = $this->db->query($sql2);
			if ($resql2) {
				while ($obj = $this->db->fetch_object($resql2)) {
					$this->type = $obj->label;
				}
			}
		}

		dol_syslog(get_class($this)."::update update member", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			unset($this->country_code);
			unset($this->country);
			unset($this->state_code);
			unset($this->state);

			$nbrowsaffected += $this->db->affected_rows($resql);

			$action = 'update';

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// Update password
			if (!$error && $this->pass) {
				dol_syslog(get_class($this)."::update update password");
				if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted) {
					$isencrypted = getDolGlobalString('DATABASE_PWD_ENCRYPTED') ? 1 : 0;

					// If password to set differs from the one found into database
					$result = $this->setPassword($user, $this->pass, $isencrypted, $notrigger, $nosyncuserpass);
					if (!$nbrowsaffected) {
						$nbrowsaffected++;
					}
				}
			}

			// Remove links to user and replace with new one
			if (!$error) {
				dol_syslog(get_class($this)."::update update link to user");
				$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".((int) $this->id);
				dol_syslog(get_class($this)."::update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->error();
					$this->db->rollback();
					return -5;
				}
				// If there is a user linked to this member
				if ($this->user_id > 0) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".((int) $this->id)." WHERE rowid = ".((int) $this->user_id);
					dol_syslog(get_class($this)."::update", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->error = $this->db->error();
						$this->db->rollback();
						return -5;
					}
				}
			}

			if (!$error && $nbrowsaffected) { // If something has change in main data
				// Update information on linked user if it is an update
				if (!$error && $this->user_id > 0 && !$nosyncuser) {
					require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

					dol_syslog(get_class($this)."::update update linked user");

					$luser = new User($this->db);
					$result = $luser->fetch($this->user_id);

					if ($result >= 0) {
						// If option ADHERENT_LOGIN_NOT_REQUIRED is on, there is no login of member, so we do not overwrite user login to keep existing one.
						if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
							$luser->login = $this->login;
						}

						$luser->ref = $this->ref;
						$luser->civility_id = $this->civility_id;
						$luser->firstname = $this->firstname;
						$luser->lastname = $this->lastname;
						$luser->gender = $this->gender;
						$luser->pass = isset($this->pass) ? $this->pass : '';
						//$luser->socid=$this->fk_soc;		// We do not enable this. This may transform a user into an external user.

						$luser->birth = $this->birth;

						$luser->address = $this->address;
						$luser->zip = $this->zip;
						$luser->town = $this->town;
						$luser->country_id = $this->country_id;
						$luser->state_id = $this->state_id;

						$luser->email = $this->email;
						$luser->socialnetworks = $this->socialnetworks;
						$luser->office_phone = $this->phone;
						$luser->user_mobile = $this->phone_mobile;

						$luser->lang = $this->default_lang;

						$luser->fk_member = $this->id;

						$result = $luser->update($user, 0, 1, 1); // Use nosync to 1 to avoid cyclic updates
						if ($result < 0) {
							$this->error = $luser->error;
							dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
							$error++;
						}
					} else {
						$this->error = $luser->error;
						$error++;
					}
				}

				// Update information on linked thirdparty if it is an update
				if (!$error && $this->fk_soc > 0 && !$nosyncthirdparty) {
					require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

					dol_syslog(get_class($this)."::update update linked thirdparty");

					// This member is linked with a thirdparty, so we also update thirdparty information
					// if this is an update.
					$lthirdparty = new Societe($this->db);
					$result = $lthirdparty->fetch($this->fk_soc);

					if ($result > 0) {
						$lthirdparty->address = $this->address;
						$lthirdparty->zip = $this->zip;
						$lthirdparty->town = $this->town;
						$lthirdparty->email = $this->email;
						$lthirdparty->socialnetworks = $this->socialnetworks;
						$lthirdparty->phone = $this->phone;
						$lthirdparty->state_id = $this->state_id;
						$lthirdparty->country_id = $this->country_id;
						//$lthirdparty->phone_mobile=$this->phone_mobile;
						$lthirdparty->default_lang = $this->default_lang;

						$result = $lthirdparty->update($this->fk_soc, $user, 0, 1, 1, 'update'); // Use sync to 0 to avoid cyclic updates

						if ($result < 0) {
							$this->error = $lthirdparty->error;
							$this->errors = $lthirdparty->errors;
							dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
							$error++;
						}
					} elseif ($result < 0) {
						$this->error = $lthirdparty->error;
						$error++;
					}
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('MEMBER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $nbrowsaffected;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -2;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update denormalized last subscription date.
	 * 	This function is called when we delete a subscription for example.
	 *
	 *	@param	User	$user			User making change
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function update_end_date($user)
	{
		// phpcs:enable
		$this->db->begin();

		// Search for last subscription id and end date
		$sql = "SELECT rowid, datec as dateop, dateadh as datedeb, datef as datefin";
		$sql .= " FROM ".MAIN_DB_PREFIX."subscription";
		$sql .= " WHERE fk_adherent = ".((int) $this->id);
		$sql .= " ORDER by dateadh DESC"; // Sort by start subscription date

		dol_syslog(get_class($this)."::update_end_date", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$dateop = $this->db->jdate($obj->dateop);
			$datedeb = $this->db->jdate($obj->datedeb);
			$datefin = $this->db->jdate($obj->datefin);

			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
			$sql .= " datefin=".($datefin != '' ? "'".$this->db->idate($datefin)."'" : "null");
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::update_end_date", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->last_subscription_date = $dateop;
				$this->last_subscription_date_start = $datedeb;
				$this->last_subscription_date_end = $datefin;
				$this->datefin = $datefin;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Fonction to delete a member and its data
	 *
	 *	@param	User	$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int					Return integer <0 if KO, 0=nothing to do, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$result = 0;
		$error = 0;
		$errorflag = 0;

		// Check parameters
		$rowid = $this->id;

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('MEMBER_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Remove category
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_member WHERE fk_member = ".((int) $rowid);
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->error .= $this->db->lasterror();
			$errorflag = -1;
		}

		// Remove subscription
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."subscription WHERE fk_adherent = ".((int) $rowid);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -2;
			}
		}

		// Remove linked user
		if (!$error) {
			$ret = $this->setUserId(0);
			if ($ret < 0) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -3;
			}
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				$errorflag = -4;
				dol_syslog(get_class($this)."::delete erreur ".$errorflag." ".$this->error, LOG_ERR);
			}
		}

		// Remove adherent
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = ".((int) $rowid);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -5;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return $errorflag;
		}
	}


	/**
	 *    Change password of a user
	 *
	 *    @param	User	$user           Object user de l'utilisateur qui fait la modification
	 *    @param 	string	$password       New password (to generate if empty)
	 *    @param    int		$isencrypted    0 ou 1 if the password needs to be encrypted in the DB (default: 0)
	 *	  @param	int		$notrigger		1=Does not raise the triggers
	 *    @param	int		$nosyncuser		Do not synchronize linked user
	 *    @return   string|int				Clear password if change ok, 0 if no change, <0 if error
	 */
	public function setPassword($user, $password = '', $isencrypted = 0, $notrigger = 0, $nosyncuser = 0)
	{
		global $conf, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::setPassword user=".$user->id." password=".preg_replace('/./i', '*', $password)." isencrypted=".$isencrypted);

		// If new password not provided, we generate one
		if (!$password) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$password = getRandomPassword(false);
		}

		// Crypt password
		$password_crypted = dol_hash($password);

		$password_indatabase = '';
		if (!$isencrypted) {
			$password_indatabase = $password;
		}

		$this->db->begin();

		// Mise a jour
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
		$sql .= " SET pass_crypted = '".$this->db->escape($password_crypted)."'";
		if ($isencrypted) {
			$sql .= ", pass = null";
		} else {
			$sql .= ", pass = '".$this->db->escape($password_indatabase)."'";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		//dol_syslog("Adherent::Password sql=hidden");
		dol_syslog(get_class($this)."::setPassword", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$nbaffectedrows = $this->db->affected_rows($result);

			if ($nbaffectedrows) {
				$this->pass = $password;
				$this->pass_indatabase = $password_indatabase;
				$this->pass_indatabase_crypted = $password_crypted;

				if ($this->user_id && !$nosyncuser) {
					require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

					// This member is linked with a user, so we also update users information
					// if this is an update.
					$luser = new User($this->db);
					$result = $luser->fetch($this->user_id);

					if ($result >= 0) {
						$result = $luser->setPassword($user, $this->pass, 0, 0, 1);
						if (is_int($result) && $result < 0) {
							$this->error = $luser->error;
							dol_syslog(get_class($this)."::setPassword ".$this->error, LOG_ERR);
							$error++;
						}
					} else {
						$this->error = $luser->error;
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('MEMBER_NEW_PASSWORD', $user);
					if ($result < 0) {
						$error++;
						$this->db->rollback();
						return -1;
					}
					// End call triggers
				}

				$this->db->commit();
				return $this->pass;
			} else {
				$this->db->rollback();
				return 0;
			}
		} else {
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    Set link to a user
	 *
	 *    @param     int	$userid        	Id of user to link to
	 *    @return    int					1=OK, -1=KO
	 */
	public function setUserId($userid)
	{
		global $conf, $langs;

		$this->db->begin();

		// If user is linked to this member, remove old link to this member
		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".((int) $this->id);
		dol_syslog(get_class($this)."::setUserId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}

		// Set link to user
		if ($userid > 0) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".((int) $this->id);
			$sql .= " WHERE rowid = ".((int) $userid);
			dol_syslog(get_class($this)."::setUserId", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -2;
			}
		}

		$this->db->commit();

		return 1;
	}


	/**
	 *    Set link to a third party
	 *
	 *    @param     int	$thirdpartyid		Id of user to link to
	 *    @return    int						1=OK, -1=KO
	 */
	public function setThirdPartyId($thirdpartyid)
	{
		global $conf, $langs;

		$this->db->begin();

		// Remove link to third party onto any other members
		if ($thirdpartyid > 0) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = null";
			$sql .= " WHERE fk_soc = ".((int) $thirdpartyid);
			$sql .= " AND entity = ".$conf->entity;
			dol_syslog(get_class($this)."::setThirdPartyId", LOG_DEBUG);
			$resql = $this->db->query($sql);
		}

		// Add link to third party for current member
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = ".($thirdpartyid > 0 ? (int) $thirdpartyid : 'null');
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setThirdPartyId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Method to load member from its login
	 *
	 *	@param	string	$login		login of member
	 *	@return	void
	 */
	public function fetch_login($login)
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
		$sql .= " WHERE login='".$this->db->escape($login)."'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->fetch($obj->rowid);
			}
		} else {
			dol_print_error($this->db);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Method to load member from its name
	 *
	 *	@param	string	$firstname	Firstname
	 *	@param	string	$lastname	Lastname
	 *	@return	void
	 */
	public function fetch_name($firstname, $lastname)
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
		$sql .= " WHERE firstname='".$this->db->escape($firstname)."'";
		$sql .= " AND lastname='".$this->db->escape($lastname)."'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->fetch($obj->rowid);
			}
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Load member from database
	 *
	 *	@param	int		$rowid      			Id of object to load
	 * 	@param	string	$ref					To load member from its ref
	 * 	@param	int		$fk_soc					To load member from its link to third party
	 * 	@param	string	$ref_ext				External reference
	 *  @param	bool	$fetch_optionals		To load optionals (extrafields)
	 *  @param	bool	$fetch_subscriptions	To load member subscriptions
	 *	@return int								>0 if OK, 0 if not found, <0 if KO
	 */
	public function fetch($rowid, $ref = '', $fk_soc = 0, $ref_ext = '', $fetch_optionals = true, $fetch_subscriptions = true)
	{
		global $langs;

		$sql = "SELECT d.rowid, d.ref, d.ref_ext, d.civility as civility_code, d.gender, d.firstname, d.lastname,";
		$sql .= " d.societe as company, d.fk_soc, d.statut, d.public, d.address, d.zip, d.town, d.note_private,";
		$sql .= " d.note_public,";
		$sql .= " d.email, d.url, d.socialnetworks, d.phone, d.phone_perso, d.phone_mobile, d.login, d.pass, d.pass_crypted,";
		$sql .= " d.photo, d.fk_adherent_type, d.morphy, d.entity,";
		$sql .= " d.datec as datec,";
		$sql .= " d.tms as datem,";
		$sql .= " d.datefin as datefin, d.default_lang,";
		$sql .= " d.birth as birthday,";
		$sql .= " d.datevalid as datev,";
		$sql .= " d.country,";
		$sql .= " d.state_id,";
		$sql .= " d.model_pdf,";
		$sql .= " c.rowid as country_id, c.code as country_code, c.label as country,";
		$sql .= " dep.nom as state, dep.code_departement as state_code,";
		$sql .= " t.libelle as type, t.subscription as subscription,";
		$sql .= " u.rowid as user_id, u.login as user_login";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.country = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as dep ON d.state_id = dep.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.rowid = u.fk_member";
		$sql .= " WHERE d.fk_adherent_type = t.rowid";
		if ($rowid) {
			$sql .= " AND d.rowid=".((int) $rowid);
		} elseif ($ref || $fk_soc) {
			$sql .= " AND d.entity IN (".getEntity('adherent').")";
			if ($ref) {
				$sql .= " AND d.ref='".$this->db->escape($ref)."'";
			} elseif ($fk_soc > 0) {
				$sql .= " AND d.fk_soc=".((int) $fk_soc);
			}
		} elseif ($ref_ext) {
			$sql .= " AND d.ref_ext='".$this->db->escape($ref_ext)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->entity = $obj->entity;
				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->ref_ext = $obj->ref_ext;

				$this->civility_id = $obj->civility_code; // Bad. Kept for backward compatibility
				$this->civility_code = $obj->civility_code;
				$this->civility = $obj->civility_code ? ($langs->trans("Civility".$obj->civility_code) != "Civility".$obj->civility_code ? $langs->trans("Civility".$obj->civility_code) : $obj->civility_code) : '';

				$this->firstname = $obj->firstname;
				$this->lastname = $obj->lastname;
				$this->gender = $obj->gender;
				$this->login = $obj->login;
				$this->societe = $obj->company;
				$this->company = $obj->company;
				$this->socid = $obj->fk_soc;
				$this->fk_soc = $obj->fk_soc; // For backward compatibility
				$this->address = $obj->address;
				$this->zip = $obj->zip;
				$this->town = $obj->town;

				$this->pass = $obj->pass;
				$this->pass_indatabase = $obj->pass;
				$this->pass_indatabase_crypted = $obj->pass_crypted;

				$this->state_id = $obj->state_id;
				$this->state_code = $obj->state_id ? $obj->state_code : '';
				$this->state = $obj->state_id ? $obj->state : '';

				$this->country_id = $obj->country_id;
				$this->country_code = $obj->country_code;
				if ($langs->trans("Country".$obj->country_code) != "Country".$obj->country_code) {
					$this->country = $langs->transnoentitiesnoconv("Country".$obj->country_code);
				} else {
					$this->country = $obj->country;
				}

				$this->phone = $obj->phone;
				$this->phone_perso = $obj->phone_perso;
				$this->phone_mobile = $obj->phone_mobile;
				$this->email = $obj->email;
				$this->url = $obj->url;

				$this->socialnetworks = ($obj->socialnetworks ? (array) json_decode($obj->socialnetworks, true) : array());

				$this->photo = $obj->photo;
				$this->statut = $obj->statut;
				$this->status = $obj->statut;
				$this->public = $obj->public;

				$this->datec = $this->db->jdate($obj->datec);
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->datem = $this->db->jdate($obj->datem);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->datefin = $this->db->jdate($obj->datefin);
				$this->datevalid = $this->db->jdate($obj->datev);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->birth = $this->db->jdate($obj->birthday);

				$this->default_lang = $obj->default_lang;

				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->morphy = $obj->morphy;

				$this->typeid = $obj->fk_adherent_type;
				$this->type = $obj->type;
				$this->need_subscription = $obj->subscription;

				$this->user_id = $obj->user_id;
				$this->user_login = $obj->user_login;

				$this->model_pdf = $obj->model_pdf;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				if ($fetch_optionals) {
					$this->fetch_optionals();
				}

				// Load other properties
				if ($fetch_subscriptions) {
					$result = $this->fetch_subscriptions();
				}

				return $this->id;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to get member subscriptions data:
	 *  subscriptions,
	 *	first_subscription_date, first_subscription_date_start, first_subscription_date_end, first_subscription_amount
	 *	last_subscription_date, last_subscription_date_start, last_subscription_date_end, last_subscription_amount
	 *
	 *	@return		int			Return integer <0 if KO, >0 if OK
	 */
	public function fetch_subscriptions()
	{
		// phpcs:enable
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

		$sql = "SELECT c.rowid, c.fk_adherent, c.fk_type, c.subscription, c.note as note_public, c.fk_bank,";
		$sql .= " c.tms as datem,";
		$sql .= " c.datec as datec,";
		$sql .= " c.dateadh as dateh,";
		$sql .= " c.datef as datef";
		$sql .= " FROM ".MAIN_DB_PREFIX."subscription as c";
		$sql .= " WHERE c.fk_adherent = ".((int) $this->id);
		$sql .= " ORDER BY c.dateadh";
		dol_syslog(get_class($this)."::fetch_subscriptions", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->subscriptions = array();

			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				if ($i == 0) {
					$this->first_subscription_date = $this->db->jdate($obj->datec);
					$this->first_subscription_date_start = $this->db->jdate($obj->dateh);
					$this->first_subscription_date_end = $this->db->jdate($obj->datef);
					$this->first_subscription_amount = $obj->subscription;
				}
				$this->last_subscription_date = $this->db->jdate($obj->datec);
				$this->last_subscription_date_start = $this->db->jdate($obj->dateh);
				$this->last_subscription_date_end = $this->db->jdate($obj->datef);
				$this->last_subscription_amount = $obj->subscription;

				$subscription = new Subscription($this->db);
				$subscription->id = $obj->rowid;
				$subscription->fk_adherent = $obj->fk_adherent;
				$subscription->fk_type = $obj->fk_type;
				$subscription->amount = $obj->subscription;
				$subscription->note = $obj->note_public;
				$subscription->note_public = $obj->note_public;
				$subscription->fk_bank = $obj->fk_bank;
				$subscription->datem = $this->db->jdate($obj->datem);
				$subscription->datec = $this->db->jdate($obj->datec);
				$subscription->dateh = $this->db->jdate($obj->dateh);
				$subscription->datef = $this->db->jdate($obj->datef);

				$this->subscriptions[] = $subscription;

				$i++;
			}
			return 1;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			return -1;
		}
	}


	/**
	 *	Function to get partnerships array
	 *
	 *  @param		string		$mode		'member' or 'thirdparty'
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function fetchPartnerships($mode)
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';


		$this->partnerships[] = array();

		return 1;
	}


	/**
	 *	Insert subscription into database and eventually add links to banks, mailman, etc...
	 *
	 *	@param	int	        $date        		Date of effect of subscription
	 *	@param	double		$amount     		Amount of subscription (0 accepted for some members)
	 *	@param	int			$accountid			Id bank account. NOT USED.
	 *	@param	string		$operation			Code of payment mode (if Id bank account provided). Example: 'CB', ... NOT USED.
	 *	@param	string		$label				Label operation (if Id bank account provided).
	 *	@param	string		$num_chq			Numero cheque (if Id bank account provided)
	 *	@param	string		$emetteur_nom		Name of cheque writer
	 *	@param	string		$emetteur_banque	Name of bank of cheque
	 *	@param	int     	$datesubend			Date end subscription
	 *	@param	int     	$fk_type 			Member type id
	 *	@return int         					rowid of record added, <0 if KO
	 */
	public function subscription($date, $amount, $accountid = 0, $operation = '', $label = '', $num_chq = '', $emetteur_nom = '', $emetteur_banque = '', $datesubend = 0, $fk_type = null)
	{
		global $user;

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

		$error = 0;

		// Clean parameters
		if (!$amount) {
			$amount = 0;
		}

		$this->db->begin();

		if ($datesubend) {
			$datefin = $datesubend;
		} else {
			// If no end date, end date = date + 1 year - 1 day
			$datefin = dol_time_plus_duree($date, 1, 'y');
			$datefin = dol_time_plus_duree($datefin, -1, 'd');
		}

		// Create subscription
		$subscription = new Subscription($this->db);
		$subscription->fk_adherent = $this->id;
		$subscription->dateh = $date; // Date of new subscription
		$subscription->datef = $datefin; // End data of new subscription
		$subscription->amount = $amount;
		$subscription->note = $label; // deprecated
		$subscription->note_public = $label;
		$subscription->fk_type = $fk_type;

		if (empty($subscription->user_creation_id)) {
			$subscription->user_creation_id = $user->id;
		}

		$rowid = $subscription->create($user);
		if ($rowid > 0) {
			// Update denormalized subscription end date (read database subscription to find values)
			// This will also update this->datefin
			$result = $this->update_end_date($user);
			if ($result > 0) {
				// Change properties of object (used by triggers)
				$this->last_subscription_date = dol_now();
				$this->last_subscription_date_start = $date;
				$this->last_subscription_date_end = $datefin;
				$this->last_subscription_amount = $amount;
			}

			if (!$error) {
				$this->db->commit();
				return $rowid;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->setErrorsFromObject($subscription);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Do complementary actions after subscription recording.
	 *
	 *	@param	int			$subscriptionid			Id of created subscription
	 *  @param	string		$option					Which action ('bankdirect', 'bankviainvoice', 'invoiceonly', ...)
	 *	@param	int			$accountid				Id bank account
	 *	@param	int			$datesubscription		Date of subscription
	 *	@param	int			$paymentdate			Date of payment
	 *	@param	string		$operation				Code of type of operation (if Id bank account provided). Example 'CB', ...
	 *	@param	string		$label					Label operation (if Id bank account provided)
	 *	@param	double		$amount     			Amount of subscription (0 accepted for some members)
	 *	@param	string		$num_chq				Numero cheque (if Id bank account provided)
	 *	@param	string		$emetteur_nom			Name of cheque writer
	 *	@param	string		$emetteur_banque		Name of bank of cheque
	 *  @param	int			$autocreatethirdparty	Auto create new thirdparty if member not yet linked to a thirdparty and we request an option that generate invoice.
	 *  @param  string      $ext_payment_id         External id of payment (for example Stripe charge id)
	 *  @param  string      $ext_payment_site       Name of external paymentmode (for example 'StripeLive', 'StripeTest', 'paypal', ...)
	 *	@return int									Return integer <0 if KO, >0 if OK
	 */
	public function subscriptionComplementaryActions($subscriptionid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, $amount, $num_chq, $emetteur_nom = '', $emetteur_banque = '', $autocreatethirdparty = 0, $ext_payment_id = '', $ext_payment_site = '')
	{
		global $conf, $langs, $user, $mysoc;

		$error = 0;

		$this->invoice = null; // This will contains invoice if an invoice is created

		dol_syslog("subscriptionComplementaryActions subscriptionid=".$subscriptionid." option=".$option." accountid=".$accountid." datesubscription=".$datesubscription." paymentdate=".
			$paymentdate." label=".$label." amount=".$amount." num_chq=".$num_chq." autocreatethirdparty=".$autocreatethirdparty);

		// Insert into bank account directlty (if option chosen for) + link to llx_subscription if option is 'bankdirect'
		if ($option == 'bankdirect' && $accountid) {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acct = new Account($this->db);
			$result = $acct->fetch($accountid);

			$dateop = $paymentdate;

			$insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, 0, $user, $emetteur_nom, $emetteur_banque);
			if ($insertid > 0) {
				$inserturlid = $acct->add_url_line($insertid, $this->id, DOL_URL_ROOT.'/adherents/card.php?rowid=', $this->getFullName($langs), 'member');
				if ($inserturlid > 0) {
					// Update table subscription
					$sql = "UPDATE ".MAIN_DB_PREFIX."subscription SET fk_bank=".((int) $insertid);
					$sql .= " WHERE rowid=".((int) $subscriptionid);

					dol_syslog("subscription::subscription", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
						$this->errors[] = $this->error;
					}
				} else {
					$error++;
					$this->setErrorsFromObject($acct);
				}
			} else {
				$error++;
				$this->setErrorsFromObject($acct);
			}
		}

		// If option chosen, we create invoice
		if (($option == 'bankviainvoice' && $accountid) || $option == 'invoiceonly') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';

			$invoice = new Facture($this->db);
			$customer = new Societe($this->db);

			if (!$error) {
				if (!($this->fk_soc > 0)) { // If not yet linked to a company
					if ($autocreatethirdparty) {
						// Create a linked thirdparty to member
						$companyalias = '';
						$fullname = $this->getFullName($langs);

						if ($this->morphy == 'mor') {
							$companyname = $this->company;
							if (!empty($fullname)) {
								$companyalias = $fullname;
							}
						} else {
							$companyname = $fullname;
							if (!empty($this->company)) {
								$companyalias = $this->company;
							}
						}

						$result = $customer->create_from_member($this, (string) $companyname, $companyalias);
						if ($result < 0) {
							$this->error = $customer->error;
							$this->errors = $customer->errors;
							$error++;
						} else {
							$this->fk_soc = $result;
						}
					} else {
						$langs->load("errors");
						$this->error = $langs->trans("ErrorMemberNotLinkedToAThirpartyLinkOrCreateFirst");
						$this->errors[] = $this->error;
						$error++;
					}
				}
			}
			if (!$error) {
				$result = $customer->fetch($this->fk_soc);
				if ($result <= 0) {
					$this->error = $customer->error;
					$this->errors = $customer->errors;
					$error++;
				}
			}

			if (!$error) {
				// Create draft invoice
				$invoice->type = Facture::TYPE_STANDARD;
				$invoice->cond_reglement_id = $customer->cond_reglement_id;
				if (empty($invoice->cond_reglement_id)) {
					$paymenttermstatic = new PaymentTerm($this->db);
					$invoice->cond_reglement_id = $paymenttermstatic->getDefaultId();
					if (empty($invoice->cond_reglement_id)) {
						$error++;
						$this->error = 'ErrorNoPaymentTermRECEPFound';
						$this->errors[] = $this->error;
					}
				}
				$invoice->socid = $this->fk_soc;
				// set customer's payment bank account on the invoice
				if (!empty($customer->fk_account)) {
					$invoice->fk_account = $customer->fk_account;
				} elseif (getDolGlobalString('FACTURE_RIB_NUMBER')) {
					// set default bank account from invoice module settings
					$invoice->fk_account = (int) getDolGlobalString('FACTURE_RIB_NUMBER');
				}
				//set customer's payment method on the invoice
				if (!empty($customer->mode_reglement_id)) {
					$invoice->mode_reglement_id = $customer->mode_reglement_id;
				}
				//$invoice->date = $datesubscription;
				$invoice->date = dol_now();

				// Possibility to add external linked objects with hooks
				$invoice->linked_objects['subscription'] = $subscriptionid;
				if (GETPOSTISARRAY('other_linked_objects')) {
					$invoice->linked_objects = array_merge($invoice->linked_objects, GETPOST('other_linked_objects', 'array:int'));
				}

				$result = $invoice->create($user);
				if ($result <= 0) {
					$this->error = $invoice->error;
					$this->errors = $invoice->errors;
					$error++;
				} else {
					$this->invoice = $invoice;
				}
			}

			if (!$error) {
				// Add line to draft invoice
				$idprodsubscription = 0;
				if (getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && (isModEnabled("product") || isModEnabled("service"))) {
					$idprodsubscription = getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS');
				}

				$vattouse = 0;
				if (getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') == 'defaultforfoundationcountry') {
					$vattouse = get_default_tva($mysoc, $mysoc, $idprodsubscription);
				}
				//print xx".$vattouse." - ".$mysoc." - ".$customer;exit;
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				$result = $invoice->addline($label, 0, 1, $vattouse, 0, 0, $idprodsubscription, 0, $datesubscription, '', 0, 0, 0, 'TTC', $amount, 1);
				if ($result <= 0) {
					$this->error = $invoice->error;
					$this->errors = $invoice->errors;
					$error++;
				}
			}

			if (!$error) {
				// Validate invoice
				$result = $invoice->validate($user);
				if ($result <= 0) {
					$this->error = $invoice->error;
					$this->errors = $invoice->errors;
					$error++;
				}
			}

			if (!$error) {
				// TODO Link invoice with subscription ?
			}

			// Add payment onto invoice
			if (!$error && $option == 'bankviainvoice' && $accountid) {
				require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

				$amounts = array();
				$amounts[$invoice->id] = (float) price2num($amount);

				$paiement = new Paiement($this->db);
				$paiement->datepaye = $paymentdate;
				$paiement->amounts = $amounts;
				$paiement->paiementcode = $operation;
				$paiement->paiementid = dol_getIdFromCode($this->db, $operation, 'c_paiement', 'code', 'id', 1);
				$paiement->num_payment = $num_chq;
				$paiement->note_public = $label;
				$paiement->ext_payment_id = $ext_payment_id;
				$paiement->ext_payment_site = $ext_payment_site;

				if (!$error) {
					// Create payment line for invoice
					$paiement_id = $paiement->create($user);
					if (!($paiement_id > 0)) {
						$this->error = $paiement->error;
						$this->errors = $paiement->errors;
						$error++;
					}
				}

				if (!$error) {
					// Add transaction into bank account
					$bank_line_id = $paiement->addPaymentToBank($user, 'payment', '(SubscriptionPayment)', $accountid, $emetteur_nom, $emetteur_banque);
					if (!($bank_line_id > 0)) {
						$this->error = $paiement->error;
						$this->errors = $paiement->errors;
						$error++;
					}
				}

				if (!$error && !empty($bank_line_id)) {
					// Update fk_bank into subscription table
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'subscription SET fk_bank='.((int) $bank_line_id);
					$sql .= ' WHERE rowid='.((int) $subscriptionid);

					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
					}
				}

				if (!$error) {
					// Set invoice as paid
					$invoice->setPaid($user);
				}
			}

			if (!$error) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				$lang_id = GETPOST('lang_id');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && !empty($lang_id)) {
					$newlang = $lang_id;
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $customer->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				// Generate PDF (whatever is option MAIN_DISABLE_PDF_AUTOUPDATE) so we can include it into email
				//if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE'))

				$invoice->generateDocument($invoice->model_pdf, $outputlangs);
			}
		}

		if ($error) {
			return -1;
		} else {
			return 1;
		}
	}


	/**
	 *		Function that validate a member
	 *
	 *		@param	User	$user		user adherent qui valide
	 *		@return	int					Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function validate($user)
	{
		global $langs, $conf;

		$error = 0;
		$now = dol_now();

		// Check parameters
		if ($this->statut == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql .= " statut = ".self::STATUS_VALIDATED;
		$sql .= ", datevalid = '".$this->db->idate($now)."'";
		$sql .= ", fk_user_valid = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->statut = self::STATUS_VALIDATED;

			// Call trigger
			$result = $this->call_trigger('MEMBER_VALIDATE', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->datevalid = $now;

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		Fonction qui resilie un adherent
	 *
	 *		@param	User	$user		User making change
	 *		@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function resiliate($user)
	{
		global $langs, $conf;

		$error = 0;

		// Check parameters
		if ($this->statut == self::STATUS_RESILIATED) {
			dol_syslog(get_class($this)."::resiliate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql .= " statut = ".self::STATUS_RESILIATED;
		$sql .= ", fk_user_valid=".$user->id;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			$this->statut = self::STATUS_RESILIATED;

			// Call trigger
			$result = $this->call_trigger('MEMBER_RESILIATE', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *		Functiun to exclude (set adherent.status to -2) a member
	 *		TODO
	 *		A private note should be added to know why the member has been excluded
	 *		For historical purpose it add an "extra-subscription" type excluded
	 *
	 *		@param	User	$user		User making change
	 *		@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function exclude($user)
	{
		$error = 0;

		// Check parameters
		if ($this->statut == self::STATUS_EXCLUDED) {
			dol_syslog(get_class($this)."::resiliate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql .= " statut = ".self::STATUS_EXCLUDED;
		$sql .= ", fk_user_valid=".$user->id;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			$this->statut = self::STATUS_EXCLUDED;

			// Call trigger
			$result = $this->call_trigger('MEMBER_EXCLUDE', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to add member into external tools mailing-list, spip, etc.
	 *
	 *  @return		int		Return integer <0 if KO, >0 if OK
	 */
	public function add_to_abo()
	{
		// phpcs:enable
		global $langs;

		include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
		$mailmanspip = new MailmanSpip($this->db);

		$err = 0;

		// mailman
		if (getDolGlobalString('ADHERENT_USE_MAILMAN') && isModEnabled('mailmanspip')) {
			$result = $mailmanspip->add_to_mailman($this);

			if ($result < 0) {
				if (!empty($mailmanspip->error)) {
					$this->errors[] = $mailmanspip->error;
				}
				$err += 1;
			}
			foreach ($mailmanspip->mladded_ko as $tmplist => $tmpemail) {
				$langs->load("errors");
				$this->errors[] = $langs->trans("ErrorFailedToAddToMailmanList", $tmpemail, $tmplist);
			}
			foreach ($mailmanspip->mladded_ok as $tmplist => $tmpemail) {
				$langs->load("mailmanspip");
				$this->mesgs[] = $langs->trans("SuccessToAddToMailmanList", $tmpemail, $tmplist);
			}
		}

		// spip
		if (getDolGlobalString('ADHERENT_USE_SPIP') && isModEnabled('mailmanspip')) {
			$result = $mailmanspip->add_to_spip($this);
			if ($result < 0) {
				$this->errors[] = $mailmanspip->error;
				$err += 1;
			}
		}
		if ($err) {
			return -$err;
		} else {
			return 1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to delete a member from external tools like mailing-list, spip, etc.
	 *
	 *  @return     int     Return integer <0 if KO, >0 if OK
	 */
	public function del_to_abo()
	{
		// phpcs:enable
		global $conf, $langs;

		include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
		$mailmanspip = new MailmanSpip($this->db);

		$err = 0;

		// mailman
		if (getDolGlobalString('ADHERENT_USE_MAILMAN')) {
			$result = $mailmanspip->del_to_mailman($this);
			if ($result < 0) {
				if (!empty($mailmanspip->error)) {
					$this->errors[] = $mailmanspip->error;
				}
				$err += 1;
			}

			foreach ($mailmanspip->mlremoved_ko as $tmplist => $tmpemail) {
				$langs->load("errors");
				$this->errors[] = $langs->trans("ErrorFailedToRemoveToMailmanList", $tmpemail, $tmplist);
			}
			foreach ($mailmanspip->mlremoved_ok as $tmplist => $tmpemail) {
				$langs->load("mailmanspip");
				$this->mesgs[] = $langs->trans("SuccessToRemoveToMailmanList", $tmpemail, $tmplist);
			}
		}

		if (getDolGlobalString('ADHERENT_USE_SPIP') && isModEnabled('mailmanspip')) {
			$result = $mailmanspip->del_to_spip($this);
			if ($result < 0) {
				$this->errors[] = $mailmanspip->error;
				$err += 1;
			}
		}
		if ($err) {
			// error
			return -$err;
		} else {
			return 1;
		}
	}


	/**
	 *    Return civility label of a member
	 *
	 *    @return   string              	Translated name of civility (translated with transnoentitiesnoconv)
	 */
	public function getCivilityLabel()
	{
		global $langs;
		$langs->load("dict");

		$code = (empty($this->civility_id) ? '' : $this->civility_id);
		if (empty($code)) {
			return '';
		}
		return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "label", $code);
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->loadLangs(['members', 'companies']);
		$nofetch = !empty($params['nofetch']);

		$datas = array();

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$langs->load("users");
			return ['optimize' => $langs->trans("ShowUser")];
		}
		if (!empty($this->photo)) {
			$photo = '<div class="photointooltip floatright">';
			$photo .= Form::showphoto('memberphoto', $this, 80, 0, 0, 'photoref photowithmargin photologintooltip', 'small', 0, 1);
			$photo .= '</div>';
			$datas['photo'] = $photo;
		}

		$datas['divopen'] = '<div class="centpercent">';
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Member").'</u> '.$this->getLibStatut(4);
		if (!empty($this->morphy)) {
			$datas['picto'] .= '&nbsp;' . $this->getmorphylib('', 1);
		}
		if (!empty($this->ref)) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->login)) {
			$datas['login'] = '<br><b>'.$langs->trans('Login').':</b> '.$this->login;
		}
		if (!empty($this->firstname) || !empty($this->lastname)) {
			$datas['name'] = '<br><b>'.$langs->trans('Name').':</b> '.$this->getFullName($langs);
		}
		if (!empty($this->company)) {
			$datas['company'] = '<br><b>'.$langs->trans('Company').':</b> '.$this->company;
		}
		if (!empty($this->email)) {
			$datas['email'] = '<br><b>'.$langs->trans("EMail").':</b> '.$this->email;
		}
		$datas['address'] = '<br><b>'.$langs->trans("Address").':</b> '.dol_format_address($this, 1, ' ', $langs);
		// show categories for this record only in ajax to not overload lists
		if (isModEnabled('category') && !$nofetch) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_MEMBER, 1);
		}
		$datas['divclose'] = '</div>';

		return $datas;
	}

	/**
	 *  Return clickable name (with picto eventually)
	 *
	 *	@param	int		$withpictoimg				0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small, -4=???)
	 *	@param	int		$maxlen						length max label
	 *	@param	string	$option						Page for link ('card', 'category', 'subscription', ...)
	 *	@param  string  $mode           			''=Show firstname+lastname as label (using default order), 'firstname'=Show only firstname, 'lastname'=Show only lastname, 'login'=Show login, 'ref'=Show ref
	 *	@param  string  $morecss        			Add more css on link
	 *	@param  int		$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@param	int		$notooltip					1=Disable tooltip
	 *	@param  int		$addlinktonotes				1=Add link to notes
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpictoimg = 0, $maxlen = 0, $option = 'card', $mode = '', $morecss = '', $save_lastsearch_value = -1, $notooltip = 0, $addlinktonotes = 0)
	{
		global $conf, $langs, $hookmanager;

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && $withpictoimg) {
			$withpictoimg = 0;
		}

		$result = '';
		$linkstart = '';
		$linkend = '';
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
		];
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/adherents/card.php?rowid='.((int) $this->id);
		if ($option == 'subscription') {
			$url = DOL_URL_ROOT.'/adherents/subscription.php?rowid='.((int) $this->id);
		}

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

		$linkstart .= '<a href="'.$url.'"';
		$linkclose = "";
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$langs->load("users");
				$label = $langs->trans("ShowUser");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		}

		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs($withpictoimg) == 1 || abs($withpictoimg) == 4) {
				$morecss .= ' paddingrightonly';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<span class="nopadding'.($morecss ? ' userimg'.$morecss : '').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip ? '' : $dataparams), 0, 0, $notooltip ? 0 : 1).'</span>';
			} else {
				// Picto must be a photo
				$picto = '<span class="nopadding'.($morecss ? ' userimg'.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>';
				$picto .= Form::showphoto('memberphoto', $this, 0, 0, 0, 'userphoto'.(($withpictoimg == -3 || $withpictoimg == -4) ? 'small' : ''), 'mini', 0, 1);
				$picto .= '</span>';
			}
			$result .= $picto;
		}
		if (($withpictoimg > -2 && $withpictoimg != 2) || $withpictoimg == -4) {
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '<span class="nopadding valignmiddle'.((!isset($this->statut) || $this->statut) ? '' : ' strikefordisabled').
				($morecss ? ' usertext'.$morecss : '').'">';
			}
			if ($mode == 'login') {
				$result .= dol_trunc(isset($this->login) ? $this->login : '', $maxlen);
			} elseif ($mode == 'ref') {
				$result .= $this->ref;
			} else {
				$result .= $this->getFullName($langs, 0, ($mode == 'firstname' ? 2 : ($mode == 'lastname' ? 4 : -1)), $maxlen);
			}
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '</span>';
			}
		}

		$result .= $linkend;

		if ($addlinktonotes) {
			if ($this->note_private) {
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($this->note_private, 1);
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/adherents/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
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
	 *  Retourne le libelle du statut d'un adherent (brouillon, valide, resilie, exclu)
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string				Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $this->need_subscription, $this->datefin, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int			$status      			Id status
	 *	@param	int			$need_subscription		1 if member type need subscription, 0 otherwise
	 *	@param	int     	$date_end_subscription	Date fin adhesion
	 *  @param  int		    $mode                   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string      						Label
	 */
	public function LibStatut($status, $need_subscription, $date_end_subscription, $mode = 0)
	{
		// phpcs:enable
		global $langs;
		$langs->load("members");

		$statusType = '';
		$labelStatus = '';
		$labelStatusShort = '';

		if ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
			$labelStatus = $langs->trans("MemberStatusDraft");
			$labelStatusShort = $langs->trans("MemberStatusDraftShort");
		} elseif ($status >= self::STATUS_VALIDATED) {
			if ($need_subscription === 0) {
				$statusType = 'status4';
				$labelStatus = $langs->trans("Validated").' - '.$langs->trans("MemberStatusNoSubscription");
				$labelStatusShort = $langs->trans("MemberStatusNoSubscriptionShort");
			} elseif (!$date_end_subscription) {
				$statusType = 'status1';
				$labelStatus = $langs->trans("Validated").' - '.$langs->trans("WaitingSubscription");
				$labelStatusShort = $langs->trans("WaitingSubscriptionShort");
			} elseif ($date_end_subscription < dol_now()) {	// expired
				$statusType = 'status8';
				$labelStatus = $langs->trans("Validated").' - '.$langs->trans("MemberStatusActiveLate");
				$labelStatusShort = $langs->trans("MemberStatusActiveLateShort");
			} else {
				$statusType = 'status4';
				$labelStatus = $langs->trans("Validated").' - '.$langs->trans("MemberStatusPaid");
				$labelStatusShort = $langs->trans("MemberStatusPaidShort");
			}
		} elseif ($status == self::STATUS_RESILIATED) {
			$statusType = 'status6';
			$labelStatus = $langs->transnoentitiesnoconv("MemberStatusResiliated");
			$labelStatusShort = $langs->transnoentitiesnoconv("MemberStatusResiliatedShort");
		} elseif ($status == self::STATUS_EXCLUDED) {
			$statusType = 'status10';
			$labelStatus = $langs->transnoentitiesnoconv("MemberStatusExcluded");
			$labelStatusShort = $langs->transnoentitiesnoconv("MemberStatusExcludedShort");
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}


	/**
	 *      Load indicators this->nb in state board
	 *
	 *      @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $conf;

		$this->nb = array();

		$sql = "SELECT count(a.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql .= " WHERE a.statut > 0";
		$sql .= " AND a.entity IN (".getEntity('adherent').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["members"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Object user
	 *      @param  string	$mode           "expired" for membership to renew, "shift" for member to validate
	 *      @return WorkboardResponse|int 	Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT a.rowid, a.datefin, a.statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql .= ", ".MAIN_DB_PREFIX."adherent_type as t";
		$sql .= " WHERE a.fk_adherent_type = t.rowid";
		if ($mode == 'expired') {
			$sql .= " AND a.statut = ".self::STATUS_VALIDATED;
			$sql .= " AND a.entity IN (".getEntity('adherent').")";
			$sql .= " AND ((a.datefin IS NULL or a.datefin < '".$this->db->idate($now)."') AND t.subscription = '1')";
		} elseif ($mode == 'shift') {
			$sql .= " AND a.statut = ".self::STATUS_DRAFT;
			$sql .= " AND a.entity IN (".getEntity('adherent').")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("members");

			$warning_delay = 0;
			$url = '';
			$label = '';
			$labelShort = '';

			if ($mode == 'expired') {
				$warning_delay = $conf->adherent->subscription->warning_delay / 60 / 60 / 24;
				$label = $langs->trans("MembersWithSubscriptionToReceive");
				$labelShort = $langs->trans("MembersWithSubscriptionToReceiveShort");
				$url = DOL_URL_ROOT.'/adherents/list.php?mainmenu=members&amp;statut='.self::STATUS_VALIDATED.'&amp;filter=outofdate';
			} elseif ($mode == 'shift') {
				$warning_delay = $conf->adherent->subscription->warning_delay / 60 / 60 / 24;
				$url = DOL_URL_ROOT.'/adherents/list.php?mainmenu=members&amp;statut='.self::STATUS_DRAFT;
				$label = $langs->trans("MembersListToValid");
				$labelShort = $langs->trans("ToValidate");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $warning_delay;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = $url;
			$response->img = img_object('', "user");

			$adherentstatic = new Adherent($this->db);

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				$adherentstatic->datefin = $this->db->jdate($obj->datefin);
				$adherentstatic->statut = $obj->statut;
				$adherentstatic->status = $obj->statut;

				if ($adherentstatic->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	string		$modele			Force template to use ('' to not force)
	 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param	int<0,1>	$hidedetails	Hide details of lines
	 *  @param	int<0,1>	$hidedesc		Hide description
	 *  @param	int<0,1>	$hideref		Hide ref
	 *  @param	?array<string,mixed>	$moreparams		Array to provide more information
	 *  @return	int<0,1>					0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("orders");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('ADHERENT_ADDON_PDF')) {
				$modele = getDolGlobalString('ADHERENT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/member/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
		global $user, $langs;
		$now = dol_now();

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'ABC001';
		$this->entity = 1;
		$this->specimen = 1;
		$this->civility_id = 'MR';
		$this->lastname = 'DOLIBARR';
		$this->firstname = 'SPECIMEN';
		$this->gender = 'man';
		$this->login = 'dolibspec';
		$this->pass = 'dolibspec';
		$this->company = 'Societe ABC';
		$this->address = '61 jump street';
		$this->zip = '75000';
		$this->town = 'Paris';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->country = 'France';
		$this->morphy = 'mor';
		$this->email = 'specimen@specimen.com';
		$this->socialnetworks = array(
			'skype' => 'skypepseudo',
			'twitter' => 'twitterpseudo',
			'facebook' => 'facebookpseudo',
			'linkedin' => 'linkedinpseudo',
		);
		$this->phone = '0999999999';
		$this->phone_perso = '0999999998';
		$this->phone_mobile = '0999999997';
		$this->note_public = 'This is a public note';
		$this->note_private = 'This is a private note';
		$this->birth = $now;
		$this->photo = '';
		$this->public = 1;
		$this->statut = self::STATUS_DRAFT;
		$this->status = self::STATUS_DRAFT;

		$this->datefin = $now;
		$this->datevalid = $now;
		$this->default_lang = '';

		$this->typeid = 1; // Id type adherent
		$this->type = 'Type adherent'; // Libelle type adherent
		$this->need_subscription = 0;

		$this->first_subscription_date = $now;
		$this->first_subscription_date_start = $this->first_subscription_date;
		$this->first_subscription_date_end = dol_time_plus_duree($this->first_subscription_date_start, 1, 'y');
		$this->first_subscription_amount = 10;

		$this->last_subscription_date = $this->first_subscription_date;
		$this->last_subscription_date_start = $this->first_subscription_date;
		$this->last_subscription_date_end = dol_time_plus_duree($this->last_subscription_date_start, 1, 'y');
		$this->last_subscription_amount = 10;
		return 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param	array<string,mixed>	$info		Info array loaded by _load_ldap_info
	 *	@param	int<0,2>			$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *											1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *											2=Return key only (uid=qqq)
	 *	@return	string							DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		$dn = '';
		if ($mode == 0) {
			$dn = getDolGlobalString('LDAP_KEY_MEMBERS') . "=".$info[getDolGlobalString('LDAP_KEY_MEMBERS')]."," . getDolGlobalString('LDAP_MEMBER_DN');
		}
		if ($mode == 1) {
			$dn = getDolGlobalString('LDAP_MEMBER_DN');
		}
		if ($mode == 2) {
			$dn = getDolGlobalString('LDAP_KEY_MEMBERS') . "=".$info[getDolGlobalString('LDAP_KEY_MEMBERS')];
		}
		return $dn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Initialise tableau info (tableau des attributes LDAP)
	 *
	 *	@return		array<string,mixed>	Tableau info des attributes
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf, $langs;

		$info = array();
		$socialnetworks = getArrayOfSocialNetworks();
		$keymodified = false;

		// Object classes
		$info["objectclass"] = explode(',', getDolGlobalString('LDAP_MEMBER_OBJECT_CLASS'));

		$this->fullname = $this->getFullName($langs);

		// For avoid ldap error when firstname and lastname are empty
		if ($this->morphy == 'mor' && (empty($this->fullname) || $this->fullname == $this->company)) {
			$this->fullname = $this->company;
			$this->lastname = $this->company;
		}

		// Possible LDAP KEY (constname => varname)
		$ldapkey = array(
			'LDAP_MEMBER_FIELD_FULLNAME' => 'fullname',
			'LDAP_MEMBER_FIELD_NAME' => 'lastname',
			'LDAP_MEMBER_FIELD_LOGIN' => 'login',
			'LDAP_MEMBER_FIELD_LOGIN_SAMBA' => 'login',
			'LDAP_MEMBER_FIELD_MAIL' => 'email'
		);

		// Member
		foreach ($ldapkey as $constname => $varname) {
			if (!empty($this->$varname) && getDolGlobalString($constname)) {
				$info[getDolGlobalString($constname)] = $this->$varname;

				// Check if it is the LDAP key and if its value has been changed
				if (getDolGlobalString('LDAP_KEY_MEMBERS') && getDolGlobalString('LDAP_KEY_MEMBERS') == getDolGlobalString($constname)) {
					if (!empty($this->oldcopy) && $this->$varname != $this->oldcopy->$varname) {
						$keymodified = true; // For check if LDAP key has been modified
					}
				}
			}
		}
		if ($this->firstname && getDolGlobalString('LDAP_MEMBER_FIELD_FIRSTNAME')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_FIRSTNAME')] = $this->firstname;
		}
		if ($this->poste && getDolGlobalString('LDAP_MEMBER_FIELD_TITLE')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_TITLE')] = $this->poste;
		}
		if ($this->company && getDolGlobalString('LDAP_MEMBER_FIELD_COMPANY')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_COMPANY')] = $this->company;
		}
		if ($this->address && getDolGlobalString('LDAP_MEMBER_FIELD_ADDRESS')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_ADDRESS')] = $this->address;
		}
		if ($this->zip && getDolGlobalString('LDAP_MEMBER_FIELD_ZIP')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_ZIP')] = $this->zip;
		}
		if ($this->town && getDolGlobalString('LDAP_MEMBER_FIELD_TOWN')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_TOWN')] = $this->town;
		}
		if ($this->country_code && getDolGlobalString('LDAP_MEMBER_FIELD_COUNTRY')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_COUNTRY')] = $this->country_code;
		}
		foreach ($socialnetworks as $key => $value) {
			if ($this->socialnetworks[$value['label']] && getDolGlobalString('LDAP_MEMBER_FIELD_'.strtoupper($value['label']))) {
				$info[getDolGlobalString('LDAP_MEMBER_FIELD_'.strtoupper($value['label']))] = $this->socialnetworks[$value['label']];
			}
		}
		if ($this->phone && getDolGlobalString('LDAP_MEMBER_FIELD_PHONE')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_PHONE')] = $this->phone;
		}
		if ($this->phone_perso && getDolGlobalString('LDAP_MEMBER_FIELD_PHONE_PERSO')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_PHONE_PERSO')] = $this->phone_perso;
		}
		if ($this->phone_mobile && getDolGlobalString('LDAP_MEMBER_FIELD_MOBILE')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_MOBILE')] = $this->phone_mobile;
		}
		if ($this->fax && getDolGlobalString('LDAP_MEMBER_FIELD_FAX')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_FAX')] = $this->fax;
		}
		if ($this->note_private && getDolGlobalString('LDAP_MEMBER_FIELD_DESCRIPTION')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_DESCRIPTION')] = dol_string_nohtmltag($this->note_private, 2);
		}
		if ($this->note_public && getDolGlobalString('LDAP_MEMBER_FIELD_NOTE_PUBLIC')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_NOTE_PUBLIC')] = dol_string_nohtmltag($this->note_public, 2);
		}
		if ($this->birth && getDolGlobalString('LDAP_MEMBER_FIELD_BIRTHDATE')) {
			$info[getDolGlobalString('LDAP_MEMBER_FIELD_BIRTHDATE')] = dol_print_date($this->birth, 'dayhourldap');
		}
		if (isset($this->statut) && getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')] = $this->statut;
		}
		if ($this->datefin && getDolGlobalString('LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION')] = dol_print_date($this->datefin, 'dayhourldap');
		}

		// When password is modified
		if (!empty($this->pass)) {
			if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')) {
				$info[getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')] = $this->pass; // this->pass = Unencrypted password
			}
			if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')) {
				$info[getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')] = dol_hash($this->pass, 'openldap'); // Create OpenLDAP password (see LDAP_PASSWORD_HASH_TYPE)
			}
		} elseif (getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION') !== '3') {
			// Set LDAP password if possible
			// If ldap key is modified and LDAPv3 we use ldap_rename function for avoid lose encrypt password
			if (getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {	// This should be on on default installation
				// Just for the case we use old md5 encryption (deprecated, no more used, kept for compatibility)
				if (!getDolGlobalString('MAIN_SECURITY_HASH_ALGO') || getDolGlobalString('MAIN_SECURITY_HASH_ALGO') == 'md5') {
					if ($this->pass_indatabase_crypted && getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')) {
						// Create OpenLDAP MD5 password from Dolibarr MD5 password
						// Note: This suppose that "pass_indatabase_crypted" is a md5 (this should not happen anymore)"
						$info[getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')] = dolGetLdapPasswordHash($this->pass_indatabase_crypted, 'md5frommd5');
					}
				}
			} elseif (!empty($this->pass_indatabase)) {
				// Use $this->pass_indatabase value if exists
				if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')) {
					$info[getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')] = $this->pass_indatabase; // $this->pass_indatabase = Unencrypted password
				}
				if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')) {
					$info[getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD_CRYPTED')] = dol_hash($this->pass_indatabase, 'openldap'); // Create OpenLDAP password (see LDAP_PASSWORD_HASH_TYPE)
				}
			}
		}

		// Subscriptions
		if ($this->first_subscription_date && getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')] = dol_print_date($this->first_subscription_date, 'dayhourldap');
		}
		if (isset($this->first_subscription_amount) && getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT')] = $this->first_subscription_amount;
		}
		if ($this->last_subscription_date && getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE')] = dol_print_date($this->last_subscription_date, 'dayhourldap');
		}
		if (isset($this->last_subscription_amount) && getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT')) {
			$info[getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT')] = $this->last_subscription_amount;
		}

		return $info;
	}


	/**
	 *      Load type info information in the member object
	 *
	 *      @param  int		$id       Id of member to load
	 *      @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT a.rowid, a.datec as datec,';
		$sql .= ' a.datevalid as datev,';
		$sql .= ' a.tms as datem,';
		$sql .= ' a.fk_user_author, a.fk_user_valid, a.fk_user_mod';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'adherent as a';
		$sql .= ' WHERE a.rowid = '.((int) $id);

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_mod;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return number of mass Emailing received by this member with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	public function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql .= " WHERE mc.email = '".$this->db->escape($this->email)."'";
		$sql .= " AND mc.statut NOT IN (-1,0)"; // -1 erreur, 0 non envoye, 1 envoye avec success

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param 	int[]|int 	$categories 	Category or categories IDs
	 * @return 	int							Return integer <0 if KO, >0 if OK
	 */
	public function setCategories($categories)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		return parent::setCategoriesCommon($categories, Categorie::TYPE_MEMBER);
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB 	$db 			Database handler
	 * @param int 		$origin_id 		Old thirdparty id
	 * @param int 		$dest_id 		New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty($db, $origin_id, $dest_id)
	{
		$tables = array('adherent');

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Return if a member is late (subscription late) or not
	 *
	 * @return boolean     True if late, False if not late
	 */
	public function hasDelay()
	{
		global $conf;

		//Only valid members
		if ($this->statut != self::STATUS_VALIDATED) {
			return false;
		}
		if (!$this->datefin) {
			return false;
		}

		$now = dol_now();

		return $this->datefin < ($now - $conf->adherent->subscription->warning_delay);
	}


	/**
	 * Send reminders by emails before subscription end
	 * CAN BE A CRON TASK
	 *
	 * @param	string		$daysbeforeendlist		Nb of days before end of subscription (negative number = after subscription). Can be a list of delay, separated by a semicolon, for example '10;5;0;-5'
	 * @param	int			$fk_adherent_type		Type of Member (In order to restrict the sending of emails only to this type of member)
	 * @return	int									0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendReminderForExpiredSubscription($daysbeforeendlist = '10', $fk_adherent_type = 0)
	{
		global $conf, $langs, $mysoc, $user;

		$error = 0;
		$this->output = '';
		$this->error = '';

		$blockingerrormsg = '';

		if (!isModEnabled('member')) { // Should not happen. If module disabled, cron job should not be visible.
			$langs->load("agenda");
			$this->output = $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Adherent"));
			return 0;
		}
		if (!getDolGlobalString('MEMBER_REMINDER_EMAIL')) {
			$langs->load("agenda");
			$this->output = $langs->trans('EventRemindersByEmailNotEnabled', $langs->transnoentitiesnoconv("Adherent"));
			return 0;
		}

		$now = dol_now();
		$nbok = 0;
		$nbko = 0;

		$listofmembersok = array();
		$listofmembersko = array();

		$arraydaysbeforeend = explode(';', $daysbeforeendlist);
		foreach ($arraydaysbeforeend as $daysbeforeend) { // Loop on each delay
			dol_syslog(__METHOD__.' - Process delta = '.$daysbeforeend, LOG_DEBUG);

			if (!is_numeric($daysbeforeend)) {
				$blockingerrormsg = "Value for delta is not a numeric value";
				$nbko++;
				break;
			}

			$tmp = dol_getdate($now);
			$datetosearchfor = dol_time_plus_duree(dol_mktime(0, 0, 0, $tmp['mon'], $tmp['mday'], $tmp['year'], 'tzserver'), (int) $daysbeforeend, 'd');
			$datetosearchforend = dol_time_plus_duree(dol_mktime(23, 59, 59, $tmp['mon'], $tmp['mday'], $tmp['year'], 'tzserver'), (int) $daysbeforeend, 'd');

			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'adherent';
			$sql .= " WHERE entity = ".((int) $conf->entity); // Do not use getEntity('adherent').")" here, we want the batch to be on its entity only;
			$sql .= " AND statut = 1";
			$sql .= " AND datefin >= '".$this->db->idate($datetosearchfor)."'";
			$sql .= " AND datefin <= '".$this->db->idate($datetosearchforend)."'";
			if ((int) $fk_adherent_type > 0) {
				$sql .= " AND fk_adherent_type = ".((int) $fk_adherent_type);
			}
			//$sql .= " LIMIT 10000";

			$resql = $this->db->query($sql);
			if ($resql) {
				$num_rows = $this->db->num_rows($resql);

				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$adherent = new Adherent($this->db);
				$formmail = new FormMail($this->db);

				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					$adherent->fetch($obj->rowid, '', 0, '', true, true);

					if (empty($adherent->email)) {
						$nbko++;
						$listofmembersko[$adherent->id] = $adherent->id;
					} else {
						$thirdpartyres = $adherent->fetch_thirdparty();
						if ($thirdpartyres === -1) {
							$languagecodeformember = $mysoc->default_lang;
						} else {
							// Language code to use ($languagecodeformember) is default language of thirdparty, if no thirdparty, the language found from country of member then country of thirdparty, and if still not found we use the language of company.
							$languagefromcountrycode = getLanguageCodeFromCountryCode($adherent->country_code ? $adherent->country_code : $adherent->thirdparty->country_code);
							$languagecodeformember = (empty($adherent->thirdparty->default_lang) ? ($languagefromcountrycode ? $languagefromcountrycode : $mysoc->default_lang) : $adherent->thirdparty->default_lang);
						}

						// Send reminder email
						$outputlangs = new Translate('', $conf);
						$outputlangs->setDefaultLang($languagecodeformember);
						$outputlangs->loadLangs(array("main", "members"));
						dol_syslog("sendReminderForExpiredSubscription Language for member id ".$adherent->id." set to ".$outputlangs->defaultlang." mysoc->default_lang=".$mysoc->default_lang);

						$arraydefaultmessage = null;
						$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION');

						if (!empty($labeltouse)) {
							$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
						}

						if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
							$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $adherent);
							//if (is_array($adherent->thirdparty)) $substitutionarraycomp = ...
							complete_substitutions_array($substitutionarray, $outputlangs, $adherent);

							$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $outputlangs);
							$msg = make_substitutions($arraydefaultmessage->content, $substitutionarray, $outputlangs);
							$from = getDolGlobalString('ADHERENT_MAIL_FROM');
							$to = $adherent->email;
							$cc = getDolGlobalString('ADHERENT_CC_MAIL_FROM');

							$trackid = 'mem'.$adherent->id;
							$moreinheader = 'X-Dolibarr-Info: sendReminderForExpiredSubscription'."\r\n";

							include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
							$cmail = new CMailFile($subject, $to, $from, $msg, array(), array(), array(), $cc, '', 0, 1, '', '', $trackid, $moreinheader);
							$result = $cmail->sendfile();
							if (!$result) {
								$error++;
								$this->error .= $cmail->error.' ';
								if (!is_null($cmail->errors)) {
									$this->errors += $cmail->errors;
								}
								$nbko++;
								$listofmembersko[$adherent->id] = $adherent->id;
							} else {
								$nbok++;
								$listofmembersok[$adherent->id] = $adherent->id;

								$message = $msg;
								$sendto = $to;
								$sendtocc = '';
								$sendtobcc = '';
								$actioncode = 'EMAIL';
								$extraparams = array();

								$actionmsg = '';
								$actionmsg2 = $langs->transnoentities('MailSentByTo', CMailFile::getValidAddress($from, 4, 0, 1), CMailFile::getValidAddress($sendto, 4, 0, 1));
								if ($message) {
									$actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
									if ($sendtocc) {
										$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc').": ".dol_escape_htmltag($sendtocc));
									}
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic').": ".$subject);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody').":");
									$actionmsg = dol_concatdesc($actionmsg, $message);
								}

								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

								// Insert record of emails sent
								$actioncomm = new ActionComm($this->db);

								$actioncomm->type_code = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
								$actioncomm->code = 'AC_'.$actioncode;
								$actioncomm->label = $actionmsg2;
								$actioncomm->note_private = $actionmsg;
								$actioncomm->fk_project = 0;
								$actioncomm->datep = $now;
								$actioncomm->datef = $now;
								$actioncomm->percentage = -1; // Not applicable
								$actioncomm->socid = $adherent->thirdparty->id;
								$actioncomm->contact_id = 0;
								$actioncomm->authorid = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is en email (content should be added into note)
								$actioncomm->email_msgid = $cmail->msgid;
								$actioncomm->email_from = $from;
								$actioncomm->email_sender = '';
								$actioncomm->email_to = $to;
								$actioncomm->email_tocc = $sendtocc;
								$actioncomm->email_tobcc = $sendtobcc;
								$actioncomm->email_subject = $subject;
								$actioncomm->errors_to = '';

								$actioncomm->fk_element = $adherent->id;
								$actioncomm->elementid = $adherent->id;
								$actioncomm->elementtype = $adherent->element;

								$actioncomm->extraparams = $extraparams;

								$actioncomm->create($user);
							}
						} else {
							//$blockingerrormsg = "Can't find email template with label=".$labeltouse.", to use for the reminding email";

							$error++;
							$this->error .= "Can't find email template with label=".$labeltouse.", to use for the reminding email ";

							$nbko++;
							$listofmembersko[$adherent->id] = $adherent->id;

							break;
						}
					}

					$i++;
				}
			} else {
				$this->error = $this->db->lasterror();
				return 1;
			}
		}

		if ($blockingerrormsg) {
			$this->error = $blockingerrormsg;
			return 1;
		} else {
			$this->output = 'Found '.($nbok + $nbko).' members to send reminder to.';
			$this->output .= ' Send email successfully to '.$nbok.' members';
			if (is_array($listofmembersok)) {
				$listofids = '';
				$i = 0;
				foreach ($listofmembersok as $idmember) {
					if ($i > 100) {
						$listofids .= ', ...';
						break;
					}
					if (empty($listofids)) {
						$listofids .= ' [';
					} else {
						$listofids .= ', ';
					}
					$listofids .= $idmember;
					$i++;
				}
				if ($listofids) {
					$listofids .= ']';
				}

				$this->output .= ($listofids ? ' ids='.$listofids : '');
			}
			if ($nbko) {
				$this->output .= ' - Canceled for '.$nbko.' member (no email or email sending error)';
				if (is_array($listofmembersko)) {
					$listofids = '';
					$i = 0;
					foreach ($listofmembersko as $idmember) {
						if ($i > 100) {
							$listofids .= ', ...';
							break;
						}
						if (empty($listofids)) {
							$listofids .= ' [';
						} else {
							$listofids .= ', ';
						}
						$listofids .= $idmember;
						$i++;
					}
					if ($listofids) {
						$listofids .= ']';
					}
					$this->output .= ($listofids ? ' ids='.$listofids : '');
				}
			}
		}

		return $nbko;
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
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		if (property_exists($this, 'photo') || !empty($this->photo)) {
			$return .= Form::showphoto('memberphoto', $this, 0, 60, 0, 'photokanban photowithmargin photologintooltip', 'small', 0, 1);
		} else {
			$return .= img_picto('', 'user');
		}
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		$return .= '<br><span class="info-box-label paddingright">'.$this->getmorphylib('', 2).'</span>';
		$return .= '<span class="info-box-label opacitymedium">'.$this->type.'</span>';

		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status paddingtop">';
			$return .= $this->LibStatut($this->status, $this->need_subscription, $this->datefin, 5);
			$return .= '</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
