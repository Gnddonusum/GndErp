<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley           <marc@ocebo.com>
 * Copyright (C) 2005-2013  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2022  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2022       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2022       OpenDSI                 <support@open-dsi.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/comm/propal/class/propal.class.php
 *	\ingroup    propale
 *	\brief      File of class to manage proposals
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propaleligne.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/subtotals/class/commonsubtotal.class.php';

/**
 *	Class to manage proposals
 */
class Propal extends CommonObject
{
	use CommonIncoterm, CommonSubtotal;

	/**
	 * @var string code
	 */
	public $code = "";

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'propal';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'propal';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'propaldet';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_propal';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'propal';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * ID of the client
	 * @var int
	 */
	public $socid;

	/**
	 * ID of the contact
	 * @var int
	 */
	public $contactid;

	/**
	 * Ref from thirdparty
	 * @var string
	 * @deprecated Use $ref_customer
	 * @see $ref_customer
	 */
	public $ref_client;

	/**
	 * Ref from thirdparty
	 * @var string
	 */
	public $ref_customer;

	/**
	 * Status of the quote
	 * @var int
	 * @deprecated Try to use $status now
	 * @see Propal::STATUS_DRAFT, Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED, Propal::STATUS_CANCELED
	 */
	public $statut;

	/**
	 * Status of the quote
	 * @var int
	 * @see Propal::STATUS_DRAFT, Propal::STATUS_VALIDATED, Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED, Propal::STATUS_CANCELED
	 */
	public $status;

	/**
	 * @var int|''
	 * @deprecated Use $date_creation
	 * @see $date_creation
	 */
	public $datec;

	/**
	 * @var int|''
	 * @deprecated Use $date_validation
	 * @see $date_validation
	 */
	public $datev;

	/**
	 * @var int|''
	 */
	public $date_validation;

	/**
	 * @var int|''
	 */
	public $date_signature;

	/**
	 * @var User
	 */
	public $user_signature;

	/**
	 * @var integer|'' date of the quote;
	 */
	public $date;

	/**
	 * @var int|''
	 * @deprecated
	 * @see $date
	 */
	public $datep;

	/**
	 * @var null|int|'' 	$delivery_date;
	 */
	public $delivery_date; // Date expected of shipment (date starting shipment, not the reception that occurs some days after)


	/**
	 * @var integer|'' 	end of validity
	 */
	public $fin_validite;

	/**
	 * @var int ID of user author
	 */
	public $user_author_id;

	/**
	 * @var User user identified by user_author_id
	 * @deprecated Set only $user_author_id
	 */
	public $author;

	/**
	 * @var float
	 * @deprecated See $total_ht
	 * @see $total_ht
	 */
	public $price;

	/**
	 * @var float
	 * @deprecated See $total_tva
	 * @see $total_tva
	 */
	public $tva;
	/**
	 * @var float
	 * @deprecated See $total_ttc
	 * @see $total_ttc
	 */
	public $total;

	/**
	 * @var string payment conditions code
	 */
	public $cond_reglement_code;

	/**
	 * @var string payment conditions label
	 */
	public $cond_reglement;

	/**
	 * @var string payment conditions label doc
	 */
	public $cond_reglement_doc;

	/**
	 * @var string method of payment code
	 */
	public $mode_reglement_code;

	/**
	 * @var string method of payment label
	 */
	public $mode_reglement;

	/**
	 * @var float|string	Deposit percent for payment terms.
	 *						Populated by $CommonObject->setPaymentTerms().
	 * @see setPaymentTerms()
	 */
	public $deposit_percent;

	/**
	 * @var int ID
	 * @deprecated
	 */
	public $fk_address;

	/**
	 * @var string address type
	 * @deprecated
	 */
	public $address_type;

	/**
	 * @var string address
	 * @deprecated
	 */
	public $address;

	/**
	 * @var int availability ID
	 */
	public $availability_id;

	/**
	 * @var int availability ID
	 * @deprecated
	 * @see $availability_id
	 */
	public $fk_availability;

	/**
	 * @var string availability code
	 */
	public $availability_code;

	/**
	 * @var string availability label
	 */
	public $availability;

	/**
	 * @var int|string
	 */
	public $duree_validite;

	/**
	 * @var int ID demand reason
	 */
	public $demand_reason_id;

	/**
	 * @var string ID demand reason code
	 */
	public $demand_reason_code;

	/**
	 * @var string ID demand reason label
	 */
	public $demand_reason;

	/**
	 * @var int ID of warehouse
	 */
	public $warehouse_id;

	/**
	 * @var PropaleLigne[]
	 */
	public $lines = array();

	/**
	 * @var PropaleLigne
	 */
	public $line;

	public $labelStatus = array();
	public $labelStatusShort = array();


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 15, 'index' => 1),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 20),
		'ref_client' => array('type' => 'varchar(255)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => -1, 'position' => 22),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 40),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'position' => 23),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Fk projet', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 24),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 25),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'datep' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'fin_validite' => array('type' => 'datetime', 'label' => 'DateEnd', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 70),
		'date_cloture' => array('type' => 'datetime', 'label' => 'DateClosing', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fk user author', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 85),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'fk_user_cloture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fk user cloture', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'price' => array('type' => 'double', 'label' => 'Price', 'enabled' => 1, 'visible' => -1, 'position' => 105),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => -1, 'position' => 125, 'isameasure' => 1),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'VAT', 'enabled' => 1, 'visible' => -1, 'position' => 130, 'isameasure' => 1),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'LocalTax1', 'enabled' => 1, 'visible' => -1, 'position' => 135, 'isameasure' => 1),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'LocalTax2', 'enabled' => 1, 'visible' => -1, 'position' => 140, 'isameasure' => 1),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'TotalTTC', 'enabled' => 1, 'visible' => -1, 'position' => 145, 'isameasure' => 1),
		'fk_account' => array('type' => 'integer', 'label' => 'BankAccount', 'enabled' => 'isModEnabled("bank")', 'visible' => -1, 'position' => 150),
		'fk_currency' => array('type' => 'varchar(3)', 'label' => 'Currency', 'enabled' => 1, 'visible' => -1, 'position' => 155),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'PaymentTerm', 'enabled' => 1, 'visible' => -1, 'position' => 160),
		'deposit_percent' => array('type' => 'varchar(63)', 'label' => 'DepositPercent', 'enabled' => 1, 'visible' => -1, 'position' => 161),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'PaymentMode', 'enabled' => 1, 'visible' => -1, 'position' => 165),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 170),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 175),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'PDFTemplate', 'enabled' => 1, 'visible' => 0, 'position' => 180),
		'date_livraison' => array('type' => 'date', 'label' => 'DateDeliveryPlanned', 'enabled' => 1, 'visible' => -1, 'position' => 185),
		'fk_shipping_method' => array('type' => 'integer', 'label' => 'ShippingMethod', 'enabled' => 1, 'visible' => -1, 'position' => 190),
		'fk_warehouse' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php', 'label' => 'Fk warehouse', 'enabled' => 'isModEnabled("stock")', 'visible' => -1, 'position' => 191),
		'fk_availability' => array('type' => 'integer', 'label' => 'Availability', 'enabled' => 1, 'visible' => -1, 'position' => 195),
		'fk_delivery_address' => array('type' => 'integer', 'label' => 'DeliveryAddress', 'enabled' => 1, 'visible' => 0, 'position' => 200), // deprecated
		'fk_input_reason' => array('type' => 'integer', 'label' => 'InputReason', 'enabled' => 1, 'visible' => -1, 'position' => 205),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 215),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'IncotermCode', 'enabled' => 'isModEnabled("incoterm")', 'visible' => -1, 'position' => 220),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'IncotermLabel', 'enabled' => 'isModEnabled("incoterm")', 'visible' => -1, 'position' => 225),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'MulticurrencyID', 'enabled' => 1, 'visible' => -1, 'position' => 230),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'MulticurrencyCurrency', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 235),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyRate', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 240, 'isameasure' => 1),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 245, 'isameasure' => 1),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 250, 'isameasure' => 1),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 255, 'isameasure' => 1),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -1, 'position' => 260),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 500),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 900),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * Canceled status
	 */
	const STATUS_CANCELED = -1;
	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Signed quote
	 */
	const STATUS_SIGNED = 2;
	/**
	 * Not signed quote
	 */
	const STATUS_NOTSIGNED = 3;
	/**
	 * Billed or processed quote
	 */
	const STATUS_BILLED = 4; // Todo rename into STATUS_CLOSE ?


	/**
	 *	Constructor
	 *
	 *	@param      DoliDB	$db         Database handler
	 *	@param      int		$socid		Id third party
	 *	@param      int		$propalid   Id proposal
	 */
	public function __construct($db, $socid = 0, $propalid = 0)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->socid = $socid;
		$this->id = $propalid;

		$this->duree_validite = getDolGlobalInt('PROPALE_VALIDITY_DURATION');

		$this->fields['ref_ext']['visible'] = getDolGlobalInt('MAIN_LIST_SHOW_REF_EXT');
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add line into array ->lines
	 *  $this->thirdparty should be loaded
	 *
	 * 	@param  int		$idproduct       	Product Id to add
	 * 	@param  float	$qty             	Quantity
	 * 	@param  float	$remise_percent  	Discount effected on Product
	 *  @return	int							Return integer <0 if KO, >0 if OK
	 *
	 *	TODO	Replace calls to this function by generation object Ligne
	 */
	public function add_product($idproduct, $qty, $remise_percent = 0)
	{
		// phpcs:enable
		global $conf, $mysoc;

		if (!$qty) {
			$qty = 1;
		}

		dol_syslog(get_class($this)."::add_product $idproduct, $qty, $remise_percent");
		if ($idproduct > 0) {
			$prod = new Product($this->db);
			$prod->fetch($idproduct);

			$productdesc = $prod->description;

			$tva_tx = (string) get_default_tva($mysoc, $this->thirdparty, $prod->id);
			$tva_npr = get_default_npr($mysoc, $this->thirdparty, $prod->id);
			if (empty($tva_tx)) {
				$tva_npr = 0;
			}
			$vat_src_code = ''; // May be defined into tva_tx

			$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $this->thirdparty, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $this->thirdparty, $tva_npr);

			// multiprices
			if (getDolGlobalString('PRODUIT_MULTIPRICES') && $this->thirdparty->price_level) {
				$price = $prod->multiprices[$this->thirdparty->price_level];
			} else {
				$price = $prod->price;
			}

			$line = new PropaleLigne($this->db);

			$line->fk_product = $idproduct;
			$line->desc = $productdesc;
			$line->qty = $qty;
			$line->subprice = $price;
			$line->remise_percent = $remise_percent;
			$line->vat_src_code = $vat_src_code;
			$line->tva_tx = $tva_tx;
			$line->fk_unit = $prod->fk_unit;
			if ($tva_npr) {
				$line->info_bits = 1;
			}

			$this->lines[] = $line;
		}

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add a discount line into an proposal (as a proposal line) using an existing absolute discount (Consume the discount)
	 *
	 *	@param     int		$idremise			Id of fixed discount from table llx_societe_remise_except
	 *  @return    int          				>0 if OK, <0 if KO
	 */
	public function insert_discount($idremise)
	{
		// phpcs:enable
		global $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$this->db->begin();

		$remise = new DiscountAbsolute($this->db);
		$result = $remise->fetch($idremise);

		if ($result > 0) {
			if ($remise->fk_facture) {	// Protection against multiple submission
				$this->error = $langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}

			$line = new PropaleLigne($this->db);

			$line->context = $this->context;

			$line->fk_propal = $this->id;
			$line->fk_remise_except = $remise->id;
			$line->desc = $remise->description; // Description ligne
			$line->vat_src_code = $remise->vat_src_code;
			$line->tva_tx = $remise->tva_tx;
			$line->subprice = -(float) $remise->amount_ht;
			$line->fk_product = 0; // Id produit predefined
			$line->qty = 1;
			$line->remise_percent = 0;
			$line->rang = -1;
			$line->info_bits = 2;

			// TODO deprecated
			$line->price = -(float) $remise->amount_ht;

			$line->total_ht  = -(float) $remise->amount_ht;
			$line->total_tva = -(float) $remise->amount_tva;
			$line->total_ttc = -(float) $remise->amount_ttc;

			$result = $line->insert();
			if ($result > 0) {
				$result = $this->update_price(1);
				if ($result > 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $line->error;
				$this->errors = $line->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *    	Add a proposal line into database (linked to product/service or not)
	 *      The parameters are already supposed to be appropriate and with final values to the call
	 *      of this method. Also, for the VAT rate, it must have already been defined
	 *      by whose calling the method get_default_tva (societe_vendeuse, societe_acheteuse, '' product)
	 *      and desc must already have the right value (it's up to the caller to manage multilanguage)
	 *
	 * 		@param    	string		$desc				Description of line
	 * 		@param    	float		$pu_ht				Unit price
	 * 		@param    	float		$qty             	Quantity
	 * 		@param    	float|string	$txtva           	Force Vat rate, -1 for auto (Can contain the vat_src_code too with syntax '9.9 (CODE)')
	 * 		@param		float		$txlocaltax1		Local tax 1 rate (deprecated, use instead txtva with code inside)
	 *  	@param		float		$txlocaltax2		Local tax 2 rate (deprecated, use instead txtva with code inside)
	 *		@param    	int			$fk_product      	Product/Service ID predefined
	 * 		@param    	float		$remise_percent  	Pourcentage de remise de la ligne
	 * 		@param    	string		$price_base_type	HT or TTC
	 * 		@param    	float		$pu_ttc             Prix unitaire TTC
	 * 		@param    	int			$info_bits			Bits for type of lines
	 *      @param      int			$type               Type of line (0=product, 1=service). Not used if fk_product is defined, the type of product is used.
	 *      @param      int			$rang               Position of line
	 *      @param		int			$special_code		Special code (also used by externals modules!)
	 *      @param		int			$fk_parent_line		Id of parent line
	 *      @param		int			$fk_fournprice		Id supplier price
	 *      @param		float|string	$pa_ht			Buying price without tax ('' to keep PMP unchanged or a float)
	 *      @param		string		$label				???
	 *		@param      int|string	$date_start       	Start date of the line
	 *		@param      int|string	$date_end         	End date of the line
	 *      @param		array<string,mixed>	$array_options	extrafields array
	 * 		@param 		int|null	$fk_unit 			Code of the unit to use. Null to use the default one
	 *      @param		string		$origin				Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be 'orderdet', 'propaldet'..., else 'order','propal,'....
	 *      @param		int			$origin_id			Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be Id of origin object (aka line id), else object id
	 * 		@param		float		$pu_ht_devise		Unit price in currency
	 * 		@param		int    		$fk_remise_except	Id discount if line is from a discount
	 *  	@param		int			$noupdateafterinsertline	No update after insert of line
	 *    	@return    	int         	    			>0 if OK, <0 if KO
	 *    	@see       	add_product()
	 */
	public function addline(
		$desc,
		$pu_ht,
		$qty,
		$txtva,
		$txlocaltax1 = 0.0,
		$txlocaltax2 = 0.0,
		$fk_product = 0,
		$remise_percent = 0.0,
		$price_base_type = 'HT',
		$pu_ttc = 0.0,
		$info_bits = 0,
		$type = 0,
		$rang = -1,
		$special_code = 0,
		$fk_parent_line = 0,
		$fk_fournprice = 0,
		$pa_ht = 0,
		$label = '',
		$date_start = '',
		$date_end = '',
		$array_options = array(),
		$fk_unit = null,
		$origin = '',
		$origin_id = 0,
		$pu_ht_devise = 0,
		$fk_remise_except = 0,
		$noupdateafterinsertline = 0
	) {
		global $mysoc, $langs;

		dol_syslog(get_class($this)."::addline propalid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_except=$remise_percent, price_base_type=$price_base_type, pu_ttc=$pu_ttc, info_bits=$info_bits, type=$type, fk_remise_except=".$fk_remise_except);

		if ($this->status == self::STATUS_DRAFT) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters
			if (empty($remise_percent)) {
				$remise_percent = 0;
			}
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($info_bits)) {
				$info_bits = 0;
			}
			if (empty($rang)) {
				$rang = 0;
			}
			if (empty($fk_parent_line) || $fk_parent_line < 0) {
				$fk_parent_line = 0;
			}

			$remise_percent = price2num($remise_percent);
			$qty = (float) price2num($qty, 'MS');
			$pu_ht = price2num($pu_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			$pu_ttc = price2num($pu_ttc);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
			}
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);
			$pa_ht = price2num($pa_ht);  // do not convert to float here, it breaks the functioning of $pa_ht is empty string
			if ($price_base_type == 'HT') {
				$pu = $pu_ht;
			} else {
				$pu = $pu_ttc;
			}

			// Check parameters
			if ($type < 0) {
				return -1;
			}

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$this->db->begin();

			$product_type = $type;
			if (!empty($fk_product) && $fk_product > 0) {
				$product = new Product($this->db);
				$result = $product->fetch($fk_product);
				$product_type = $product->type;

				if (getDolGlobalString('STOCK_MUST_BE_ENOUGH_FOR_PROPOSAL') && $product_type == 0 && $product->stock_reel < $qty) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnProposal', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			if (getDolGlobalString('PRODUCT_USE_CUSTOMER_PACKAGING')) {
				$tmpproduct = new Product($this->db);
				$result = $tmpproduct->fetch($fk_product);
				if (abs($qty) < $tmpproduct->packaging) {
					$qty = (float) $tmpproduct->packaging;
					setEventMessages($langs->trans('QtyRecalculatedWithPackaging'), null, 'mesgs');
				} else {
					if (!empty($tmpproduct->packaging) && $qty > $tmpproduct->packaging) {
						$coeff = intval(abs($qty) / $tmpproduct->packaging) + 1;
						$qty = price2num((float) $tmpproduct->packaging * $coeff, 'MS');
						setEventMessages($langs->trans('QtyRecalculatedWithPackaging'), null, 'mesgs');
					}
				}
			}

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			$tabprice = calcul_price_total($qty, (float) $pu, (float) $remise_percent, $txtva, (float) $txlocaltax1, (float) $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, (float) $pu_ht_devise);

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Rang to use
			$ranktouse = $rang;
			if ($ranktouse == -1) {
				$rangmax = $this->line_max($fk_parent_line);
				$ranktouse = $rangmax + 1;
			}

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ((float) $remise_percent > 0) {
				$remise = round(((float) $pu * (float) $remise_percent / 100), 2);
				$price = (float) $pu - $remise;
			}

			// Insert line
			$this->line = new PropaleLigne($this->db);

			$this->line->context = $this->context;

			$this->line->fk_propal = $this->id;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = (float) $qty;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$this->line->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->fk_product = $fk_product;
			$this->line->product_type = $type;
			$this->line->fk_remise_except = $fk_remise_except;
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice = (float) $pu_ht;
			$this->line->rang = $ranktouse;
			$this->line->info_bits = $info_bits;
			$this->line->total_ht = (float) $total_ht;
			$this->line->total_tva = (float) $total_tva;
			$this->line->total_localtax1 = (float) $total_localtax1;
			$this->line->total_localtax2 = (float) $total_localtax2;
			$this->line->total_ttc = (float) $total_ttc;
			$this->line->special_code = $special_code;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->fk_unit = $fk_unit;

			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			$this->line->origin_id = $origin_id;
			$this->line->origin = $origin;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice		= (float) $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= (float) $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= (float) $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= (float) $multicurrency_total_ttc;

			// Mise en option de la ligne
			if (empty($qty) && empty($special_code)) {
				$this->line->special_code = 3;
			}

			// TODO deprecated
			$this->line->price = $price;

			if (is_array($array_options) && count($array_options) > 0) {
				$this->line->array_options = $array_options;
			}

			$result = $this->line->insert();
			if ($result > 0) {
				if (!isset($this->context['createfromclone'])) {
					if (!empty($fk_parent_line)) {
						// Always reorder if child line
						$this->line_order(true, 'DESC');
					} elseif ($ranktouse > 0 && $ranktouse <= count($this->lines)) {
						// Update all rank of all other lines starting from the same $ranktouse
						$linecount = count($this->lines);
						for ($ii = $ranktouse; $ii <= $linecount; $ii++) {
							$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
						}
					}

					$this->lines[] = $this->line;
				} else {
					foreach ($this->lines as $line) {
						if ($line->id == $origin_id) {
							$this->line->extraparams = $line->extraparams;
							$this->line->setExtraParameters();
						}
					}
				}

				// Update denormalized fields at the order level
				if (empty($noupdateafterinsertline)) {
					$result = $this->update_price(1, 'auto', 0, $mysoc); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.
				}

				if ($result > 0) {
					$this->db->commit();
					return $this->line->id;
				} else {
					$this->error = $this->db->error();
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			dol_syslog(get_class($this)."::addline status of proposal must be Draft to allow use of ->addline()", LOG_ERR);
			return -3;
		}
	}


	/**
	 *  Update a proposal line
	 *
	 *  @param      int			$rowid           	Id of line
	 *  @param      float		$pu		     	  	Unit price (HT or TTC depending on price_base_type)
	 *  @param      float		$qty            	Quantity
	 *  @param      float		$remise_percent  	Discount on line
	 *  @param      float|string	$txtva	          	VAT Rate (Can be '1.23' or '1.23 (ABC)')
	 * 	@param	  	float		$txlocaltax1		Local tax 1 rate
	 *  @param	  	float		$txlocaltax2		Local tax 2 rate
	 *  @param      string		$desc            	Description
	 *	@param	  	string		$price_base_type	HT or TTC
	 *	@param      int			$info_bits        	Miscellaneous information
	 *	@param		int			$special_code		Special code (also used by externals modules!)
	 * 	@param		int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 *  @param		int			$fk_fournprice		Id of origin supplier price
	 *  @param		float|string	$pa_ht			Price (without tax) of product when it was bought (Can be '' to keep AWP unchanged or a float value)
	 *  @param		string		$label				???
	 *  @param		int			$type				0/1=Product/service
	 *	@param      int|string	$date_start       	Start date of the line
	 *	@param      int|string	$date_end         	End date of the line
	 *  @param		array<string,mixed>	$array_options	extrafields array
	 * 	@param 		int|null	$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param		float		$pu_ht_devise		Unit price in currency
	 * 	@param		int			$notrigger			Disable line update trigger
	 *	@param      int			$rang   			Line rank
	 *  @return     int     		        		0 if OK, <0 if KO
	 */
	public function updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $desc = '', $price_base_type = 'HT', $info_bits = 0, $special_code = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = 0, $pa_ht = 0, $label = '', $type = 0, $date_start = '', $date_end = '', $array_options = array(), $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0, $rang = 0)
	{
		global $mysoc, $langs;

		dol_syslog(get_class($this)."::updateLine rowid=$rowid, pu=$pu, qty=$qty, remise_percent=$remise_percent,
        txtva=$txtva, desc=$desc, price_base_type=$price_base_type, info_bits=$info_bits, special_code=$special_code, fk_parent_line=$fk_parent_line, pa_ht=$pa_ht, type=$type, date_start=$date_start, date_end=$date_end");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		$remise_percent = price2num($remise_percent);
		$qty = (float) price2num($qty);
		$pu = price2num($pu);
		$pu_ht_devise = price2num($pu_ht_devise);
		if (!preg_match('/\((.*)\)/', (string) $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);
		$pa_ht = price2num($pa_ht);  // do not convert to float here, it breaks the functioning of $pa_ht_isemptystring
		if (empty($qty) && empty($special_code)) {
			$special_code = 3; // Set option tag
		}
		if (!empty($qty) && $special_code == 3) {
			$special_code = 0; // Remove option tag
		}
		if (empty($type)) {
			$type = 0;
		}

		if ($date_start && $date_end && $date_start > $date_end) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorStartDateGreaterEnd');
			return -1;
		}

		if ($this->status == self::STATUS_DRAFT) {
			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			// TODO Implement  if (getDolGlobalInt('MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES')) ?

			$tabprice = calcul_price_total($qty, (float) $pu, (float) $remise_percent, $txtva, (float) $txlocaltax1, (float) $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, (float) $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ((float) $remise_percent > 0) {
				$remise = round(((float) $pu * (float) $remise_percent / 100), 2);
				$price = (float) $pu - $remise;
			}

			// Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new PropaleLigne($this->db);
			$line->fetch($rowid);

			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
			$this->line->context = $this->context;
			$this->line->rang = $rang;

			// Reorder if fk_parent_line change
			if (!empty($fk_parent_line) && !empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line) {
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			if (getDolGlobalString('PRODUCT_USE_CUSTOMER_PACKAGING')) {
				if ($qty < $this->line->packaging) {
					$qty = $this->line->packaging;
				} else {
					if (!empty($this->line->packaging) && ($qty % $this->line->packaging) > 0) {
						$coeff = intval($qty / $this->line->packaging) + 1;
						$qty = $this->line->packaging * $coeff;
						setEventMessage($langs->trans('QtyRecalculatedWithPackaging'), 'mesgs');
					}
				}
			}

			$this->line->id = $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = $qty;
			$this->line->product_type		= $type;
			$this->line->vat_src_code		= $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type 	= empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type 	= empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= (float) $pu_ht;
			$this->line->info_bits			= $info_bits;

			$this->line->total_ht			= (float) $total_ht;
			$this->line->total_tva			= (float) $total_tva;
			$this->line->total_localtax1	= (float) $total_localtax1;
			$this->line->total_localtax2	= (float) $total_localtax2;
			$this->line->total_ttc			= (float) $total_ttc;
			$this->line->special_code = $special_code;
			$this->line->fk_parent_line		= $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->fk_unit = $fk_unit;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			// Multicurrency
			$this->line->multicurrency_subprice		= (float) $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= (float) $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= (float) $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= (float) $multicurrency_total_ttc;

			$result = $this->line->update($notrigger);
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				}

				$this->update_price(1, 'auto');

				// $this is Propal
				// $this->fk_propal = $this->id;
				// $this->rowid = $rowid;

				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::updateline Erreur -2 Propal en mode incompatible pour cette action");
			return -2;
		}
	}


	/**
	 *  Delete detail line
	 *
	 *  @param		int		$lineid			Id of line to delete
	 *  @param		int		$id				Id of object (for a check)
	 *  @return     int         			>0 if OK, <0 if KO
	 */
	public function deleteLine($lineid, $id = 0)
	{
		global $user;

		if ($this->status == self::STATUS_DRAFT) {
			$this->db->begin();

			$line = new PropaleLigne($this->db);

			$line->context = $this->context;

			// Load data
			$line->fetch($lineid);

			if ($id > 0 && $line->fk_propal != $id) {
				$this->error = 'ErrorLineIDDoesNotMatchWithObjectID';
				return -1;
			}

			// Memorize previous line for triggers
			$staticline = clone $line;
			$line->oldline = $staticline;

			if ($line->delete($user) > 0) {
				$this->update_price(1);

				$this->db->commit();
				return 1;
			} else {
				$this->error = $line->error;
				$this->errors = $line->errors;
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}
	}


	/**
	 *  Create commercial proposal into database
	 * 	this->ref can be set or empty. If empty, we will use "(PROVid)"
	 *
	 * 	@param		User	$user		User that create
	 * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return     int     			Return integer <0 if KO, >=0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $hookmanager, $mysoc;
		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (empty($this->date)) {
			$this->date = $this->datep;
		}
		$this->fin_validite = $this->date + ($this->duree_validite * 24 * 3600);
		if (empty($this->availability_id)) {
			$this->availability_id = 0;
		}
		if (empty($this->demand_reason_id)) {
			$this->demand_reason_id = 0;
		}

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $this->date);
		} else {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}
		// setEntity will set entity with the right value if empty or change it for the right value if multicompany module is active
		$this->entity = setEntity($this);

		// Set tmp vars
		$delivery_date = $this->delivery_date;

		dol_syslog(get_class($this)."::create");

		// Check parameters
		$result = $this->fetch_thirdparty();
		if ($result < 0) {
			$this->error = "Failed to fetch company";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -3;
		}

		// Check parameters
		if (!empty($this->ref)) {	// We check that ref is not already used
			$result = self::isExistingObject($this->element, 0, $this->ref); // Check ref is not yet used
			if ($result > 0) {
				$this->error = 'ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}

		if (empty($this->date)) {
			$this->error = "Date of proposal is required";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -4;
		}


		$this->db->begin();

		// Insert into database
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "fk_soc";
		$sql .= ", price";
		$sql .= ", total_tva";
		$sql .= ", total_ttc";
		$sql .= ", datep";
		$sql .= ", datec";
		$sql .= ", ref";
		$sql .= ", fk_user_author";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", model_pdf";
		$sql .= ", fin_validite";
		$sql .= ", fk_cond_reglement";
		$sql .= ", deposit_percent";
		$sql .= ", fk_mode_reglement";
		$sql .= ", fk_account";
		$sql .= ", ref_client";
		$sql .= ", ref_ext";
		$sql .= ", date_livraison";
		$sql .= ", fk_shipping_method";
		$sql .= ", fk_warehouse";
		$sql .= ", fk_availability";
		$sql .= ", fk_input_reason";
		$sql .= ", fk_projet";
		$sql .= ", fk_incoterms";
		$sql .= ", location_incoterms";
		$sql .= ", entity";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= $this->socid;
		$sql .= ", 0";
		$sql .= ", 0";
		$sql .= ", 0";
		$sql .= ", '".$this->db->idate($this->date)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '(PROV)'";
		$sql .= ", ".($user->id > 0 ? ((int) $user->id) : "NULL");
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", '".$this->db->escape($this->model_pdf)."'";
		$sql .= ", ".($this->fin_validite != '' ? "'".$this->db->idate($this->fin_validite)."'" : "NULL");
		$sql .= ", ".($this->cond_reglement_id > 0 ? ((int) $this->cond_reglement_id) : 'NULL');
		$sql .= ", ".(!empty($this->deposit_percent) ? "'".$this->db->escape($this->deposit_percent)."'" : 'NULL');
		$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : 'NULL');
		$sql .= ", ".($this->fk_account > 0 ? ((int) $this->fk_account) : 'NULL');
		$sql .= ", '".$this->db->escape($this->ref_client)."'";
		$sql .= ", '".$this->db->escape($this->ref_ext)."'";
		$sql .= ", ".(!isDolTms($delivery_date) ? "NULL" : "'".$this->db->idate($delivery_date)."'");
		$sql .= ", ".($this->shipping_method_id > 0 ? $this->shipping_method_id : 'NULL');
		$sql .= ", ".($this->warehouse_id > 0 ? $this->warehouse_id : 'NULL');
		$sql .= ", ".$this->availability_id;
		$sql .= ", ".$this->demand_reason_id;
		$sql .= ", ".($this->fk_project ? $this->fk_project : "null");
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ", ".(int) $this->entity;
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".(float) $this->multicurrency_tx;
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if ($this->id) {
				$this->ref = '(PROV'.$this->id.')';
				$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element." SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".((int) $this->id);

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
				}

				if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
					$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
					foreach ($this->linked_objects as $origin => $tmp_origin_id) {
						if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
							foreach ($tmp_origin_id as $origin_id) {
								$ret = $this->add_object_linked($origin, $origin_id);
								if (!$ret) {
									$this->error = $this->db->lasterror();
									$error++;
								}
							}
						} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
							$origin_id = $tmp_origin_id;
							$ret = $this->add_object_linked($origin, $origin_id);
							if (!$ret) {
								$this->error = $this->db->lasterror();
								$error++;
							}
						}
					}
				}

				/*
				 *  Insertion du detail des produits dans la base
				 *  Insert products detail in database
				 */
				if (!$error) {
					$fk_parent_line = 0;
					$num = count($this->lines);

					for ($i = 0; $i < $num; $i++) {
						if (!is_object($this->lines[$i])) {	// If this->lines is not array of objects, coming from REST API
							// Convert into object this->lines[$i].
							$line = (object) $this->lines[$i];
						} else {
							$line = $this->lines[$i];
						}
						// Reset fk_parent_line for line that are not child lines or special product
						if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
							$fk_parent_line = 0;
						}
						// Complete vat rate with code
						$vatrate = $line->tva_tx;
						if ($line->vat_src_code && !preg_match('/\(.*\)/', $vatrate)) {
							$vatrate .= ' ('.$line->vat_src_code.')';
						}

						if (getDolGlobalString('MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION')) {
							$originid = $line->origin_id;
							$origintype = $line->origin;
						} else {
							$originid = $line->id;
							$origintype = $this->element;
						}

						$result = $this->addline(
							$line->desc,
							$line->subprice,
							$line->qty,
							$vatrate,
							$line->localtax1_tx,
							$line->localtax2_tx,
							$line->fk_product,
							$line->remise_percent,
							'HT',
							0,
							$line->info_bits,
							$line->product_type,
							$line->rang,
							$line->special_code,
							$fk_parent_line,
							$line->fk_fournprice,
							$line->pa_ht,
							$line->label,
							$line->date_start,
							$line->date_end,
							$line->array_options,
							$line->fk_unit,
							(string) $origintype,
							$originid,
							0,
							0,
							1
						);

						if ($result < 0) {
							$error++;
							$this->error = $this->db->error;
							dol_print_error($this->db);
							break;
						}

						// Set the id on created row
						$line->id = $result;

						// Defined the new fk_parent_line
						if ($result > 0 && $line->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
				}

				// Set delivery address
				/*if (! $error && $this->fk_delivery_address)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
					$sql.= " SET fk_delivery_address = ".((int) $this->fk_delivery_address);
					$sql.= " WHERE ref = '".$this->db->escape($this->ref)."'";
					$sql.= " AND entity = ".setEntity($this);

					$result=$this->db->query($sql);
				}*/

				if (!$error) {
					// Mise a jour infos denormalisees
					$resql = $this->update_price(1, 'auto', 0, $mysoc);
					if ($resql) {
						$action = 'update';

						// Actions on extra fields
						if (!$error) {
							$result = $this->insertExtraFields();
							if ($result < 0) {
								$error++;
							}
						}

						if (!$error && !$notrigger) {
							// Call trigger
							$result = $this->call_trigger('PROPAL_CREATE', $user);
							if ($result < 0) {
								$error++;
							}
							// End call triggers
						}
					} else {
						$this->error = $this->db->lasterror();
						$error++;
					}
				}
			} else {
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				$this->db->commit();
				dol_syslog(get_class($this)."::create done id=".$this->id);
				return $this->id;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *		Load an object from its id and create a new one in database
	 *
	 *      @param	    User	$user		    User making the clone
	 *		@param		int		$socid			Id of thirdparty
	 *		@param		int		$forceentity	Entity id to force
	 *		@param		bool	$update_prices	[=false] Update prices if true
	 *		@param		bool	$update_desc	[=false] Update description if true
	 * 	 	@return		int						New id of clone
	 */
	public function createFromClone(User $user, $socid = 0, $forceentity = null, $update_prices = false, $update_desc = false)
	{
		global $conf, $hookmanager, $mysoc;

		dol_include_once('/projet/class/project.class.php');

		$error = 0;
		$now = dol_now();

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($this->id);

		$objsoc = new Societe($this->db);

		// Change socid if needed
		if (!empty($socid) && $socid != $object->socid) {
			if ($objsoc->fetch($socid) > 0) {
				$object->socid = $objsoc->id;
				$object->cond_reglement_id	= (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$object->deposit_percent = (!empty($objsoc->deposit_percent) ? $objsoc->deposit_percent : null);
				$object->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$object->fk_delivery_address = 0;

				/*if (isModEnabled('project'))
				{
					$project = new Project($db);
					if ($this->fk_project > 0 && $project->fetch($this->fk_project)) {
						if ($project->socid <= 0) $clonedObj->fk_project = $this->fk_project;
						else $clonedObj->fk_project = '';
					} else {
						$clonedObj->fk_project = '';
					}
				}*/
				$object->fk_project = 0; // A cloned proposal is set by default to no project.
			}

			// reset ref_client
			if (!getDolGlobalString('MAIN_KEEP_REF_CUSTOMER_ON_CLONING')) {
				$object->ref_client = '';
				$object->ref_customer = '';
			}

			// TODO Change product price if multi-prices
		} else {
			$objsoc->fetch($object->socid);
		}

		// update prices
		if ($update_prices === true || $update_desc === true) {
			if ($objsoc->id > 0 && !empty($object->lines)) {
				if ($update_prices === true && getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
					// If price per customer
					require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';
				}

				foreach ($object->lines as $line) {
					$line->id = 0;

					if ($line->fk_product > 0) {
						$prod = new Product($this->db);
						$res = $prod->fetch($line->fk_product);
						if ($res > 0) {
							if ($update_prices === true) {
								$pu_ht = $prod->price;
								$tva_tx = (string) get_default_tva($mysoc, $objsoc, $prod->id);
								$remise_percent = $objsoc->remise_percent;

								if (getDolGlobalString('PRODUIT_MULTIPRICES') && $objsoc->price_level > 0) {
									$pu_ht = $prod->multiprices[$objsoc->price_level];
									if (getDolGlobalString('PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL')) {  // using this option is a bug. kept for backward compatibility
										if (isset($prod->multiprices_tva_tx[$objsoc->price_level])) {
											$tva_tx = (string) $prod->multiprices_tva_tx[$objsoc->price_level];
										}
									}
								} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
									$prodcustprice = new ProductCustomerPrice($this->db);
									$filter = array('t.fk_product' => (string) $prod->id, 't.fk_soc' => (string) $objsoc->id);
									$result = $prodcustprice->fetchAll('', '', 0, 0, $filter);
									if ($result) {
										// If there is some prices specific to the customer
										if (count($prodcustprice->lines) > 0) {
											$date_now = (int) floor(dol_now() / 86400) * 86400; // date without hours
											foreach ($prodcustprice->lines as $k => $custprice_line) {
												if ($custprice_line->date_begin <= $date_now && (empty($custprice_line->date_end) || $date_now <= $custprice_line->date_end)) {
													$pu_ht = price($custprice_line->price);
													$tva_tx = ($custprice_line->default_vat_code ? $custprice_line->tva_tx . ' (' . $custprice_line->default_vat_code . ' )' : $custprice_line->tva_tx);
													if ($custprice_line->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) {
														$tva_tx .= ' (' . $custprice_line->default_vat_code . ')';
													}
													$remise_percent = $custprice_line->discount_percent;
													break;
												}
											}
										}
									}
								}

								$line->subprice = $pu_ht;
								$line->tva_tx = $tva_tx;
								$line->remise_percent = $remise_percent;
							}
							if ($update_desc === true) {
								$line->desc = $prod->description;
							}
						}
					}
				}
			}
		}

		$object->id = 0;
		$object->ref = '';
		$object->entity = (!empty($forceentity) ? $forceentity : $object->entity);
		$object->statut = self::STATUS_DRAFT;
		$object->status = self::STATUS_DRAFT;

		// Clear fields
		$object->user_creation_id = $user->id;
		$object->user_validation_id = 0;
		$object->date = $now;
		$object->datep = $now; // deprecated
		$object->fin_validite = $object->date + ($object->duree_validite * 24 * 3600);
		if (!getDolGlobalString('MAIN_KEEP_REF_CUSTOMER_ON_CLONING')) {
			$object->ref_client = '';
			$object->ref_customer = '';
		}
		if (getDolGlobalInt('MAIN_DONT_KEEP_NOTE_ON_CLONING') == 1) {
			$object->note_private = '';
			$object->note_public = '';
		}
		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$this->error = $object->error;
			$this->errors = array_merge($this->errors, $object->errors);
			$error++;
		}

		if (!$error && !getDolGlobalInt('MAIN_IGNORE_CONTACTS_ON_CLONING')) {
			// copy internal contacts
			if ($object->copy_linked_contact($this, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if ($this->socid == $object->socid) {
				if ($object->copy_linked_contact($this, 'external') < 0) {
					$error++;
				}
			}
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom' => $this, 'clonedObj' => $object);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Load a proposal from database. Get also lines.
	 *
	 *	@param      int			$rowid			Id of object to load
	 *	@param		string		$ref			Ref of proposal
	 *	@param		string		$ref_ext		Ref ext of proposal
	 *	@param		int			$forceentity	Entity id to force when searching on ref or ref_ext
	 *	@return     int         				>0 if OK, <0 if KO
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $forceentity = 0)
	{
		$sql = "SELECT p.rowid, p.ref, p.entity, p.fk_soc";
		$sql .= ", p.total_ttc, p.total_tva, p.localtax1, p.localtax2, p.total_ht";
		$sql .= ", p.datec";
		$sql .= ", p.date_signature as dates";
		$sql .= ", p.date_valid as datev";
		$sql .= ", p.datep as dp";
		$sql .= ", p.fin_validite as dfv";
		$sql .= ", p.date_livraison as delivery_date";
		$sql .= ", p.model_pdf, p.last_main_doc, p.ref_client, ref_ext, p.extraparams";
		$sql .= ", p.note_private, p.note_public";
		$sql .= ", p.fk_projet as fk_project, p.fk_statut";
		$sql .= ", p.fk_user_author, p.fk_user_valid, p.fk_user_cloture";
		$sql .= ", p.fk_delivery_address";
		$sql .= ", p.fk_availability";
		$sql .= ", p.fk_input_reason";
		$sql .= ", p.fk_cond_reglement";
		$sql .= ", p.fk_mode_reglement";
		$sql .= ', p.fk_account';
		$sql .= ", p.fk_shipping_method";
		$sql .= ", p.fk_warehouse";
		$sql .= ", p.fk_incoterms, p.location_incoterms";
		$sql .= ", p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva, p.multicurrency_total_ttc";
		$sql .= ", p.tms as date_modification";
		$sql .= ", i.libelle as label_incoterms";
		$sql .= ", c.label as statut_label";
		$sql .= ", ca.code as availability_code, ca.label as availability";
		$sql .= ", dr.code as demand_reason_code, dr.label as demand_reason";
		$sql .= ", cr.code as cond_reglement_code, cr.libelle as cond_reglement, cr.libelle_facture as cond_reglement_libelle_doc, p.deposit_percent";
		$sql .= ", cp.code as mode_reglement_code, cp.libelle as mode_reglement";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_propalst as c ON p.fk_statut = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_mode_reglement = cp.id AND cp.entity IN ('.getEntity('c_paiement').')';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON p.fk_cond_reglement = cr.rowid AND cr.entity IN ('.getEntity('c_payment_term').')';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_availability as ca ON p.fk_availability = ca.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_input_reason as dr ON p.fk_input_reason = dr.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON p.fk_incoterms = i.rowid';

		if (!empty($ref)) {
			if (!empty($forceentity)) {
				$sql .= " WHERE p.entity = ".(int) $forceentity; // Check only the current entity because we may have the same reference in several entities
			} else {
				$sql .= " WHERE p.entity IN (".getEntity('propal').")";
			}
			$sql .= " AND p.ref='".$this->db->escape($ref)."'";
		} else {
			// Don't use entity if you use rowid
			$sql .= " WHERE p.rowid = ".((int) $rowid);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id                   = $obj->rowid;
				$this->entity               = $obj->entity;

				$this->ref                  = $obj->ref;
				$this->ref_client           = $obj->ref_client;
				$this->ref_customer         = $obj->ref_client;
				$this->ref_ext              = $obj->ref_ext;

				$this->total                = $obj->total_ttc;			// TODO deprecated
				$this->total_ttc            = $obj->total_ttc;
				$this->total_ht             = $obj->total_ht;
				$this->total_tva            = $obj->total_tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;

				$this->socid = $obj->fk_soc;
				$this->thirdparty = null; // Clear if another value was already set by fetch_thirdparty

				$this->fk_project = $obj->fk_project;
				$this->project = null; // Clear if another value was already set by fetchProject

				$this->model_pdf            = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;
				$this->note                 = $obj->note_private; // TODO deprecated
				$this->note_private         = $obj->note_private;
				$this->note_public          = $obj->note_public;

				$this->status               = (int) $obj->fk_statut;
				$this->statut               = $this->status; // deprecated

				$this->datec                = $this->db->jdate($obj->datec); // TODO deprecated
				$this->datev                = $this->db->jdate($obj->datev); // TODO deprecated
				$this->date_creation = $this->db->jdate($obj->datec); //Creation date
				$this->date_validation = $this->db->jdate($obj->datev); //Validation date
				$this->date_modification = $this->db->jdate($obj->date_modification); // tms
				$this->date_signature = $this->db->jdate($obj->dates); // Signature date
				$this->date                 = $this->db->jdate($obj->dp); // Proposal date
				$this->datep                = $this->db->jdate($obj->dp); // deprecated
				$this->fin_validite         = $this->db->jdate($obj->dfv);
				$this->delivery_date        = $this->db->jdate($obj->delivery_date);
				$this->shipping_method_id   = ($obj->fk_shipping_method > 0) ? $obj->fk_shipping_method : null;
				$this->warehouse_id         = ($obj->fk_warehouse > 0) ? $obj->fk_warehouse : null;
				$this->availability_id      = $obj->fk_availability;
				$this->availability_code    = $obj->availability_code;
				$this->availability         = $obj->availability;
				$this->demand_reason_id     = $obj->fk_input_reason;
				$this->demand_reason_code   = $obj->demand_reason_code;
				$this->demand_reason        = $obj->demand_reason;
				$this->fk_address = $obj->fk_delivery_address;

				$this->mode_reglement_id    = $obj->fk_mode_reglement;
				$this->mode_reglement_code  = $obj->mode_reglement_code;
				$this->mode_reglement       = $obj->mode_reglement;
				$this->fk_account           = ($obj->fk_account > 0) ? $obj->fk_account : null;
				$this->cond_reglement_id    = $obj->fk_cond_reglement;
				$this->cond_reglement_code  = $obj->cond_reglement_code;
				$this->cond_reglement       = $obj->cond_reglement;
				$this->cond_reglement_doc   = $obj->cond_reglement_libelle_doc;
				$this->deposit_percent      = $obj->deposit_percent;

				$this->extraparams = !empty($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

				$this->user_author_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_closing_id = $obj->fk_user_cloture;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva = $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($resql);

				$this->lines = array();

				// Lines
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}

				return 1;
			}

			$this->error = "Record Not Found";
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *      Update database
	 *
	 *      @param      User	$user        	User that modify
	 *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int      			   	Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_client)) {
			$this->ref_client = trim($this->ref_client);
		}
		if (isset($this->note) || isset($this->note_private)) {
			$this->note_private = (isset($this->note_private) ? trim($this->note_private) : trim($this->note));
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (!empty($this->duree_validite) && is_numeric($this->duree_validite)) {
			$this->fin_validite = $this->date + ($this->duree_validite * 24 * 3600);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql = "UPDATE ".MAIN_DB_PREFIX."propal SET";
		$sql .= " ref = ".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_client = ".(isset($this->ref_client) ? "'".$this->db->escape($this->ref_client)."'" : "null").",";
		$sql .= " ref_ext = ".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " fk_soc = ".(!empty($this->socid) ? (int) $this->socid : "null").",";
		$sql .= " datep = ".(strval($this->date) != '' ? "'".$this->db->idate($this->date)."'" : 'null').",";
		if (!empty($this->fin_validite)) {
			$sql .= " fin_validite = ".(strval($this->fin_validite) != '' ? "'".$this->db->idate($this->fin_validite)."'" : 'null').",";
		}
		$sql .= " date_valid = ".(strval($this->date_validation) != '' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql .= " total_tva = ".(isset($this->total_tva) ? (float) $this->total_tva : "null").",";
		$sql .= " localtax1 = ".(isset($this->total_localtax1) ? (float) $this->total_localtax1 : "null").",";
		$sql .= " localtax2 = ".(isset($this->total_localtax2) ? (float) $this->total_localtax2 : "null").",";
		$sql .= " total_ht = ".(isset($this->total_ht) ? (float) $this->total_ht : "null").",";
		$sql .= " total_ttc = ".(isset($this->total_ttc) ? (float) $this->total_ttc : "null").",";
		$sql .= " fk_statut = ".(isset($this->status) ? (int) $this->status : "null").",";
		$sql .= " fk_user_author = ".(!empty($this->user_author_id) ? (int) $this->user_author_id : "null").",";
		$sql .= " fk_user_valid = ".(!empty($this->user_validation_id) ? (int) $this->user_validation_id : "null").",";
		$sql .= " fk_projet = ".(!empty($this->fk_project) ? (int) $this->fk_project : "null").",";
		$sql .= " fk_cond_reglement = ".(!empty($this->cond_reglement_id) ? (int) $this->cond_reglement_id : "null").",";
		$sql .= " deposit_percent = ".(!empty($this->deposit_percent) ? "'".$this->db->escape($this->deposit_percent)."'" : "null").",";
		$sql .= " fk_mode_reglement = ".(!empty($this->mode_reglement_id) ? (int) $this->mode_reglement_id : "null").",";
		$sql .= " fk_input_reason = ".(!empty($this->demand_reason_id) ? (int) $this->demand_reason_id : "null").",";
		$sql .= " note_private = ".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public = ".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf = ".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " import_key = ".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PROPAL_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load array lines
	 *
	 *	@param		int			$only_product			Return only physical products
	 *	@param		int			$loadalsotranslation	Return translation for products
	 *	@param		string		$sqlforgedfilters		Filter on other fields
	 *	@return		int									Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines($only_product = 0, $loadalsotranslation = 0, $sqlforgedfilters = '')
	{
		// phpcs:enable
		$this->lines = array();

		$sql = 'SELECT d.rowid, d.fk_propal, d.fk_parent_line, d.label as custom_label, d.description, d.price, d.vat_src_code, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.localtax1_type, d.localtax2_type, d.qty, d.fk_remise_except, d.remise_percent, d.subprice, d.fk_product,';
		$sql .= ' d.info_bits, d.total_ht, d.total_tva, d.total_localtax1, d.total_localtax2, d.total_ttc, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht, d.special_code, d.rang, d.product_type,';
		$sql .= ' d.fk_unit,';
		$sql .= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label, p.tobatch as product_tobatch, p.barcode as product_barcode,';
		$sql .= ' p.weight, p.weight_units, p.volume, p.volume_units,';
		$sql .= ' d.date_start, d.date_end, d.extraparams,';
		$sql .= ' d.fk_multicurrency, d.multicurrency_code, d.multicurrency_subprice, d.multicurrency_total_ht, d.multicurrency_total_tva, d.multicurrency_total_ttc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as d';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (d.fk_product = p.rowid)';
		$sql .= ' WHERE d.fk_propal = '.((int) $this->id);
		if ($only_product) {
			$sql .= ' AND p.fk_product_type = 0';
		}
		if ($sqlforgedfilters) {
			$sql .= $sqlforgedfilters;
		}
		$sql .= ' ORDER by d.rang';

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

			$num = $this->db->num_rows($result);

			$i = 0;
			while ($i < $num) {
				$objp                   = $this->db->fetch_object($result);

				$line                   = new PropaleLigne($this->db);

				$line->rowid = $objp->rowid; //Deprecated
				$line->id = $objp->rowid;
				$line->fk_propal = $objp->fk_propal;
				$line->fk_parent_line = $objp->fk_parent_line;
				$line->product_type     = $objp->product_type;
				$line->label            = $objp->custom_label;
				$line->desc             = $objp->description; // Description ligne
				$line->description      = $objp->description; // Description ligne
				$line->qty              = $objp->qty;
				$line->vat_src_code     = $objp->vat_src_code;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx		= $objp->localtax1_tx;
				$line->localtax2_tx		= $objp->localtax2_tx;
				$line->localtax1_type	= $objp->localtax1_type;
				$line->localtax2_type	= $objp->localtax2_type;
				$line->subprice         = $objp->subprice;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->remise_percent   = $objp->remise_percent;
				$line->price            = $objp->price; // TODO deprecated

				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_localtax1	= $objp->total_localtax1;
				$line->total_localtax2	= $objp->total_localtax2;
				$line->total_ttc        = $objp->total_ttc;
				$line->fk_fournprice = $objp->fk_fournprice;
				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht = $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->special_code     = $objp->special_code;
				$line->rang             = $objp->rang;

				$line->fk_product       = $objp->fk_product;

				$line->ref = $objp->product_ref; // deprecated
				$line->libelle = $objp->product_label; // deprecated

				$line->product_ref = $objp->product_ref;
				$line->product_label = $objp->product_label;
				$line->product_desc     = $objp->product_desc; // Description produit
				$line->product_tobatch  = $objp->product_tobatch;
				$line->product_barcode  = $objp->product_barcode;

				$line->fk_product_type  = $objp->fk_product_type; // deprecated
				$line->fk_unit          = $objp->fk_unit;
				$line->weight = $objp->weight;
				$line->weight_units = $objp->weight_units;
				$line->volume = $objp->volume;
				$line->volume_units = $objp->volume_units;

				$line->date_start = $this->db->jdate($objp->date_start);
				$line->date_end = $this->db->jdate($objp->date_end);

				$line->extraparams = !empty($objp->extraparams) ? (array) json_decode($objp->extraparams, true) : array();

				// Multicurrency
				$line->fk_multicurrency = $objp->fk_multicurrency;
				$line->multicurrency_code = $objp->multicurrency_code;
				$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				$line->fetch_optionals();

				// multilangs
				if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($objp->fk_product) && !empty($loadalsotranslation)) {
					$tmpproduct = new Product($this->db);
					$tmpproduct->fetch($objp->fk_product);
					$tmpproduct->getMultiLangs();

					$line->multilangs = $tmpproduct->multilangs;
				}

				$this->lines[$i] = $line;

				$i++;
			}

			$this->db->free($result);

			return $num;
		} else {
			$this->error = $this->db->lasterror();
			return -3;
		}
	}

	/**
	 *  Set status to validated
	 *
	 *  @param	User	$user       Object user that validate
	 *  @param	int		$notrigger	1=Does not execute triggers, 0=execute triggers
	 *  @return int         		Return integer <0 if KO, 0=Nothing done, >=0 if OK
	 */
	public function valid($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::valid action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		if (!((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal', 'creer'))
		|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal', 'propal_advance', 'validate')))) {
			$this->error = 'ErrorPermissionDenied';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$now = dol_now();

		$this->db->begin();

		// Numbering module definition
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef($soc);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " fk_statut = ".self::STATUS_VALIDATED.", date_valid='".$this->db->idate($now)."', fk_user_valid=".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = ".self::STATUS_DRAFT;

		dol_syslog(get_class($this)."::valid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$error++;
		}

		// Trigger calls
		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PROPAL_VALIDATE', $user);
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
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'propale/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'propale/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'propale/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'propale/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->propal->multidir_output[$this->entity].'/'.$oldref;
				$dirdest = $conf->propal->multidir_output[$this->entity].'/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);
					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($dirdest, 'files', 1, '^'.preg_quote($oldref, '/'));
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

			$this->ref = $num;
			$this->statut = self::STATUS_VALIDATED;
			$this->status = self::STATUS_VALIDATED;
			$this->user_validation_id = $user->id;
			$this->datev = $now;
			$this->date_validation = $now;

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Define proposal date
	 *
	 *  @param  User		$user      	Object user that modify
	 *  @param  int			$date		Date
	 *  @param  int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int         			Return integer <0 if KO, >0 if OK
	 */
	public function set_date($user, $date, $notrigger = 0)
	{
		// phpcs:enable
		if (empty($date)) {
			$this->error = 'ErrorBadParameter';
			dol_syslog(get_class($this)."::set_date ".$this->error, LOG_ERR);
			return -1;
		}

		if ($user->hasRight('propal', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET datep = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->date = $date;
				$this->datep = $date; // deprecated
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define end validity date
	 *
	 *	@param		User	$user        		Object user that modify
	 *	@param      int		$date_end_validity	End of validity date
	 *  @param  	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				Return integer <0 if KO, >0 if OK
	 */
	public function set_echeance($user, $date_end_validity, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('propal', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fin_validite = ".($date_end_validity != '' ? "'".$this->db->idate($date_end_validity)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}


			if (!$error) {
				$this->oldcopy = clone $this;
				$this->fin_validite = $date_end_validity;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set delivery date
	 *
	 *	@param      User 	$user        		Object user that modify
	 *	@param      int		$delivery_date		Delivery date
	 *  @param  	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				Return integer <0 if ko, >0 if ok
	 *	@deprecated Use  setDeliveryDate
	 */
	public function set_date_livraison($user, $delivery_date, $notrigger = 0)
	{
		// phpcs:enable
		return $this->setDeliveryDate($user, $delivery_date, $notrigger);
	}

	/**
	 *	Set delivery date
	 *
	 *	@param      User 	$user        		Object user that modify
	 *	@param      int		$delivery_date     Delivery date
	 *  @param  	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				Return integer <0 if ko, >0 if ok
	 */
	public function setDeliveryDate($user, $delivery_date, $notrigger = 0)
	{
		if ($user->hasRight('propal', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET date_livraison = ".(isDolTms($delivery_date) ? "'".$this->db->idate($delivery_date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->delivery_date = $delivery_date;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set delivery
	 *
	 *  @param		User	$user		  	Object user that modify
	 *  @param      int		$id				Availability id
	 *  @param  	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *  @return     int           			Return integer <0 if KO, >0 if OK
	 */
	public function set_availability($user, $id, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('propal', 'creer') && $this->status >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fk_availability = ".((int) $id);
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__.' availability('.$id.')', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->fk_availability = $id;
				$this->availability_id = $id;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'Propal status do not meet requirement '.$this->status;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set source of demand
	 *
	 *  @param		User	$user		Object user that modify
	 *  @param      int		$id			Input reason id
	 *  @param  	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return     int           		Return integer <0 if KO, >0 if OK
	 */
	public function set_demand_reason($user, $id, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('propal', 'creer') && $this->status >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fk_input_reason = ".((int) $id);
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}


			if (!$error) {
				$this->oldcopy = clone $this;

				$this->demand_reason_id = $id;
			}


			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'Propal status do not meet requirement '.$this->status;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set customer reference number
	 *
	 *  @param      User	$user			Object user that modify
	 *  @param      string	$ref_client		Customer reference
	 *  @param  	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *  @return     int						Return integer <0 if ko, >0 if ok
	 */
	public function set_ref_client($user, $ref_client, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('propal', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref_client = ".(empty($ref_client) ? 'NULL' : "'".$this->db->escape($ref_client)."'");
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__.' $this->id='.$this->id.', ref_client='.$ref_client, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->ref_client = $ref_client;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return -1;
	}


	/**
	 *	Reopen the commercial proposal
	 *
	 *	@param      User	$user		Object user that close
	 *	@param      int		$status		Status
	 *	@param      string	$note		Comment
	 *  @param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function reopen($user, $status, $note = '', $notrigger = 0)
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET fk_statut = ".((int) $status).",";
		if (!empty($note)) {
			$sql .= " note_private = '".$this->db->escape($note)."',";
		}
		$sql .= " date_cloture=NULL, fk_user_cloture=NULL";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::reopen", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}
		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_REOPEN', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			if (!empty($this->errors)) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->statut = $status;
			$this->status = $status;

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Close/set the commercial proposal to status signed or refused (fill also date signature)
	 *
	 *	@param      User	$user		Object user that close
	 *	@param      int		$status		Status (self::STATUS_BILLED or self::STATUS_REFUSED)
	 *	@param      string	$note_private		Complete private note with this note
	 *  @param		int		$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@param      string	$note_public		Complete public note with this note
	 *	@return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function closeProposal($user, $status, $note_private = '', $notrigger = 0, $note_public = '')
	{
		global $langs,$conf;

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		$newprivatenote = dol_concatdesc($this->note_private, $note_private);
		$newpublicnote = dol_concatdesc($this->note_public, $note_public);

		if (!getDolGlobalString('PROPALE_KEEP_OLD_SIGNATURE_INFO')) {
			$date_signature = $now;
			$fk_user_signature = $user->id;
		} else {
			$this->info($this->id);
			if (!isset($this->date_signature) || $this->date_signature == '') {
				$date_signature = $now;
				$fk_user_signature = $user->id;
			} else {
				$date_signature = $this->date_signature;
				$fk_user_signature = $this->user_signature->id;
			}
		}

		$sql  = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET fk_statut = ".((int) $status).", note_private = '".$this->db->escape($newprivatenote)."', note_public = '".$this->db->escape($newpublicnote)."'";
		if ($status == self::STATUS_SIGNED) {
			$sql .= ", date_signature='".$this->db->idate($now)."', fk_user_signature = ".($fk_user_signature);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			// Status self::STATUS_REFUSED by default
			$modelpdf = getDolGlobalString('PROPALE_ADDON_PDF_ODT_CLOSED', $this->model_pdf);
			$triggerName = 'PROPAL_CLOSE_REFUSED';		// used later in call_trigger()

			if ($status == self::STATUS_SIGNED) {			// Status self::STATUS_SIGNED
				$triggerName = 'PROPAL_CLOSE_SIGNED';	// used later in call_trigger()
				$modelpdf = getDolGlobalString('PROPALE_ADDON_PDF_ODT_TOBILL', $this->model_pdf);

				// The connected company is classified as a client
				$soc = new Societe($this->db);
				$soc->id = $this->socid;
				$result = $soc->setAsCustomer();

				if ($result < 0) {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -2;
				}
			}

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE') && !getDolGlobalInt('PROPAL_DISABLE_AUTOUPDATE_ON_CLOSE')) {
				// Define output language
				$outputlangs = $langs;
				if (getDolGlobalInt('MAIN_MULTILANGS')) {
					$outputlangs = new Translate("", $conf);
					$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $this->thirdparty->default_lang);
					$outputlangs->setDefaultLang($newlang);
				}

				// PDF
				$hidedetails = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0);
				$hidedesc = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0);
				$hideref = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0);

				//$ret=$object->fetch($id);    // Reload to get new records
				$this->generateDocument($modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->statut = $status;	// deprecated
				$this->status = $status;
				$this->date_signature = $date_signature;
				$this->note_private = $newprivatenote;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger($triggerName, $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->statut = $this->oldcopy->status;	// deprecated
				$this->status = $this->oldcopy->status;
				$this->date_signature = $this->oldcopy->date_signature;
				$this->note_private = $this->oldcopy->note_private;

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
	 *	Classify the proposal to status Billed
	 *
	 *	@param  	User	$user    	Object user
	 *  @param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@param      string	$note		Complete private note with this note
	 *	@return     int     			Return integer <0 if KO, 0 = nothing done, >0 if OK
	 */
	public function classifyBilled(User $user, $notrigger = 0, $note = '')
	{
		global $conf, $langs;

		$error = 0;

		$now = dol_now();
		$num = 0;

		$triggerName = 'PROPAL_CLASSIFY_BILLED';

		$this->db->begin();

		$newprivatenote = dol_concatdesc($this->note_private, $note);

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET fk_statut = ".self::STATUS_BILLED.", ";
		$sql .= " note_private = '".$this->db->escape($newprivatenote)."', date_cloture='".$this->db->idate($now)."', fk_user_cloture=".((int) $user->id);
		$sql .= ' WHERE rowid = '.((int) $this->id).' AND fk_statut = '.((int) self::STATUS_SIGNED);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->error();
			$error++;
		} else {
			$num = $this->db->affected_rows($resql);
		}

		if (!$error) {
			$modelpdf = getDolGlobalString('PROPALE_ADDON_PDF_ODT_CLOSED', $this->model_pdf);

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				if (getDolGlobalInt('MAIN_MULTILANGS')) {
					$outputlangs = new Translate("", $conf);
					$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $this->thirdparty->default_lang);
					$outputlangs->setDefaultLang($newlang);
				}

				// PDF
				$hidedetails = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0);
				$hidedesc = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0);
				$hideref = (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0);

				//$ret=$object->fetch($id);    // Reload to get new records
				$this->generateDocument($modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			$this->oldcopy = clone $this;
			$this->statut = self::STATUS_BILLED;
			$this->status = self::STATUS_BILLED;
			$this->date_cloture = $now;
			$this->note_private = $newprivatenote;
		}

		if (!$notrigger && empty($error)) {
			// Call trigger
			$result = $this->call_trigger($triggerName, $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return $num;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 *	Cancel the proposal
	 *
	 *	@param  	User	$user	Object user
	 *	@return		int				Return integer if KO <0 , if OK >0
	 */
	public function setCancel(User $user)
	{
		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ". MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET fk_statut = " . self::STATUS_CANCELED . ",";
		$sql .= " fk_user_modif = " . ((int) $user->id);
		$sql .= " WHERE rowid = " . ((int) $this->id);

		dol_syslog(get_class($this)."::cancel", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_CANCEL', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->statut = self::STATUS_CANCELED;	// deprecated
				$this->status = self::STATUS_CANCELED;
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::cancel ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set draft status
	 *
	 *	@param		User	$user		Object user that modify
	 *  @param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;

		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		dol_syslog(get_class($this)."::setDraft", LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= ",  online_sign_ip = NULL , online_sign_name = NULL";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->error();
			$error++;
		}

		if (!$error) {
			$this->oldcopy = clone $this;
		}

		if (!$notrigger && empty($error)) {
			// Call trigger
			$result = $this->call_trigger('PROPAL_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->statut = self::STATUS_DRAFT;	// deprecated
			$this->status = self::STATUS_DRAFT;

			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of proposal (eventually filtered on user) into an array
	 *
	 *    @param	int		$shortlist			0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
	 *    @param	int		$draft				0=not draft, 1=draft
	 *    @param	int		$notcurrentuser		0=all user, 1=not current user
	 *    @param    int		$socid				Id third party
	 *    @param    int		$limit				For pagination
	 *    @param    int		$offset				For pagination
	 *    @param    string	$sortfield			Sort criteria
	 *    @param    string	$sortorder			Sort order
	 *    @return	array|int		       		-1 if KO, array with result if OK
	 */
	public function liste_array($shortlist = 0, $draft = 0, $notcurrentuser = 0, $socid = 0, $limit = 0, $offset = 0, $sortfield = 'p.datep', $sortorder = 'DESC')
	{
		// phpcs:enable
		global $user;

		$ga = array();

		$sql = "SELECT s.rowid, s.nom as name, s.client,";
		$sql .= " p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql .= " p.datep as dp, p.fin_validite as datelimite";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX.$this->table_element." as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		$sql .= " AND p.fk_soc = s.rowid";
		$sql .= " AND p.fk_statut = c.id";

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Search on socid
		if ($socid) {
			$sql .= " AND p.fk_soc = ".((int) $socid);
		}
		if ($draft) {
			$sql .= " AND p.fk_statut = ".((int) self::STATUS_DRAFT);
		}
		if ($notcurrentuser > 0) {
			$sql .= " AND p.fk_user_author <> ".((int) $user->id);
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit, $offset);

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);

					if ($shortlist == 1) {
						$ga[$obj->propalid] = $obj->ref;
					} elseif ($shortlist == 2) {
						$ga[$obj->propalid] = $obj->ref.' ('.$obj->name.')';
					} else {
						$ga[$i]['id'] = $obj->propalid;
						$ga[$i]['ref'] 	= $obj->ref;
						$ga[$i]['name'] = $obj->name;
					}

					$i++;
				}
			}
			return $ga;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Returns an array with the numbers of related invoices
	 *
	 *	@return	CommonInvoice[]		Array of invoices
	 */
	public function getInvoiceArrayList()
	{
		return $this->InvoiceArrayList($this->id);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Returns an array with id and ref of related invoices
	 *
	 *	@param		int			$id			Id propal
	 *	@return		CommonInvoice[]|int		Array of invoices id
	 */
	public function InvoiceArrayList($id)
	{
		// phpcs:enable
		$ga = array();
		$linkedInvoices = array();

		$this->fetchObjectLinked($id, $this->element);
		foreach ($this->linkedObjectsIds as $objecttype => $objectid) {
			// Nouveau système du common object renvoi des rowid et non un id linéaire de 1 à n
			// On parcourt donc une liste d'objets en tant qu'objet unique
			foreach ($objectid as $key => $object) {
				// Cas des factures liees directement
				if ($objecttype == 'facture') {
					$linkedInvoices[] = $object;
				} else {
					// Cas des factures liees par un autre object (ex: commande)
					$this->fetchObjectLinked($object, $objecttype);
					foreach ($this->linkedObjectsIds as $subobjecttype => $subobjectid) {
						foreach ($subobjectid as $subkey => $subobject) {
							if ($subobjecttype == 'facture') {
								$linkedInvoices[] = $subobject;
							}
						}
					}
				}
			}
		}

		if (count($linkedInvoices) > 0) {
			$sql = "SELECT rowid as facid, ref, total_ht as total, datef as df, fk_user_author, fk_statut, paye";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture";
			$sql .= " WHERE rowid IN (".$this->db->sanitize(implode(',', $linkedInvoices)).")";

			dol_syslog(get_class($this)."::InvoiceArrayList", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$tab_sqlobj = array();
				$nump = $this->db->num_rows($resql);
				for ($i = 0; $i < $nump; $i++) {
					$sqlobj = $this->db->fetch_object($resql);
					$tab_sqlobj[] = $sqlobj;
				}
				$this->db->free($resql);

				$nump = count($tab_sqlobj);

				if ($nump) {
					$i = 0;
					while ($i < $nump) {
						$obj = array_shift($tab_sqlobj);

						$ga[$i] = $obj;

						$i++;
					}
				}
				return $ga;
			} else {
				return -1;
			}
		} else {
			return $ga;
		}
	}

	/**
	 *	Delete proposal
	 *
	 *	@param	User	$user        	Object user that delete
	 *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						>0 if OK, <=0 if KO
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PROPAL_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Delete extrafields of lines and lines
		if (!$error && !empty($this->table_element_line)) {
			$tabletodelete = $this->table_element_line;
			$sqlef = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete."_extrafields WHERE fk_object IN (SELECT rowid FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".((int) $this->id).")";
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".((int) $this->id);
			if (!$this->db->query($sqlef) || !$this->db->query($sql)) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				$error++;
			}
		}

		// Removed extrafields of object
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		// Delete main record
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		// Delete record into ECM index and physically
		if (!$error) {
			$res = $this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
			$res = $this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive
			if (!$res) {
				$error++;
			}
		}

		if (!$error) {
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->propal->multidir_output[$this->entity] && !empty($this->ref)) {
				$dir = $conf->propal->multidir_output[$this->entity]."/".$ref;
				$file = $dir."/".$ref.".pdf";
				if (file_exists($file)) {
					dol_delete_preview($this);

					if (!dol_delete_file($file, 0, 0, 0, $this)) {
						$this->error = 'ErrorFailToDeleteFile';
						$this->errors[] = $this->error;
						$this->db->rollback();
						return 0;
					}
				}
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);		// delete files physically + into ecm tables
					if (!$res) {
						$this->error = 'ErrorFailToDeleteDir';
						$this->errors[] = $this->error;
						$this->db->rollback();
						return 0;
					}
				}
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Change the delivery time
	 *
	 *  @param	int	$availability_id	Id of new delivery time
	 * 	@param	int	$notrigger			1=Does not execute triggers, 0= execute triggers
	 *  @return int                  	>0 if OK, <0 if KO
	 *  @deprecated  use set_availability
	 */
	public function availability($availability_id, $notrigger = 0)
	{
		global $user;

		if ($this->status >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' SET fk_availability = '.((int) $availability_id);
			$sql .= ' WHERE rowid='.((int) $this->id);

			dol_syslog(__METHOD__.' availability('.$availability_id.')', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->availability_id = $availability_id;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'Propal status do not meet requirement '.$this->status;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Change source demand
	 *
	 *	@param	int $demand_reason_id 	Id of new source demand
	 * 	@param	int	$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return int						>0 si ok, <0 si ko
	 *	@deprecated use set_demand_reason
	 */
	public function demand_reason($demand_reason_id, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		if ($this->status >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' SET fk_input_reason = '.((int) $demand_reason_id);
			$sql .= ' WHERE rowid='.((int) $this->id);

			dol_syslog(__METHOD__.' demand_reason('.$demand_reason_id.')', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->demand_reason_id = $demand_reason_id;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('PROPAL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'Propal status do not meet requirement '.$this->status;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}


	/**
	 *	Object Proposal Information
	 *
	 * 	@param	int		$id		Proposal id
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT c.rowid, ";
		$sql .= " c.datec, c.date_valid as datev, c.date_signature, c.date_cloture,";
		$sql .= " c.fk_user_author, c.fk_user_valid, c.fk_user_signature, c.fk_user_cloture";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		$sql .= " WHERE c.rowid = ".((int) $id);

		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id                = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->date_signature    = $this->db->jdate($obj->date_signature);
				$this->date_cloture      = $this->db->jdate($obj->date_cloture);

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;

				if ($obj->fk_user_signature) {
					$user_signature = new User($this->db);
					$user_signature->fetch($obj->fk_user_signature);
					$this->user_signature = $user_signature;
				}

				$this->user_closing_id = $obj->fk_user_cloture;
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *    	Return label of status of proposal (draft, validated, ...)
	 *
	 *    	@param      int			$mode        0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *    	@return     string		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Return label of a status (draft, validated, ...)
	 *
	 *    	@param      int			$status		Id status
	 *    	@param      int			$mode      	0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *    	@return     string		Label
	 */
	public function LibStatut($status, $mode = 1)
	{
		// phpcs:enable
		global $hookmanager;

		// Init/load array of translation of status
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("propal");
			$this->labelStatus[-1] = $langs->transnoentitiesnoconv("PropalStatusCanceled");
			$this->labelStatus[0] = $langs->transnoentitiesnoconv("PropalStatusDraft");
			$this->labelStatus[1] = $langs->transnoentitiesnoconv("PropalStatusValidated");
			$this->labelStatus[2] = $langs->transnoentitiesnoconv("PropalStatusSigned");
			$this->labelStatus[3] = $langs->transnoentitiesnoconv("PropalStatusNotSigned");
			$this->labelStatus[4] = $langs->transnoentitiesnoconv("PropalStatusBilled");
			$this->labelStatusShort[-1] = $langs->transnoentitiesnoconv("PropalStatusCanceledShort");
			$this->labelStatusShort[0] = $langs->transnoentitiesnoconv("PropalStatusDraftShort");
			$this->labelStatusShort[1] = $langs->transnoentitiesnoconv("PropalStatusValidatedShort");
			$this->labelStatusShort[2] = $langs->transnoentitiesnoconv("PropalStatusSignedShort");
			$this->labelStatusShort[3] = $langs->transnoentitiesnoconv("PropalStatusNotSignedShort");
			$this->labelStatusShort[4] = $langs->transnoentitiesnoconv("PropalStatusBilledShort");
		}

		$statusType = '';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		} elseif ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
		} elseif ($status == self::STATUS_VALIDATED) {
			$statusType = 'status1';
		} elseif ($status == self::STATUS_SIGNED) {
			$statusType = 'status4';
		} elseif ($status == self::STATUS_NOTSIGNED) {
			$statusType = 'status9';
		} elseif ($status == self::STATUS_BILLED) {
			$statusType = 'status6';
		}

		$parameters = array('status' => $status, 'mode' => $mode);
		$reshook = $hookmanager->executeHooks('LibStatut', $parameters, $this); // Note that $action and $object may have been modified by hook

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param          User	$user   Object user
	 *      @param          string	$mode   "opened" for proposal to close, "signed" for proposal to invoice
	 *      @return WorkboardResponse|int Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $langs;

		$clause = " WHERE";

		$sql = "SELECT p.rowid, p.ref, p.datec as datec, p.fin_validite as datefin, p.total_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
		$sql .= $clause." p.entity IN (".getEntity('propal').")";
		if ($mode == 'opened') {
			$sql .= " AND p.fk_statut = ".self::STATUS_VALIDATED;
		}
		if ($mode == 'signed') {
			$sql .= " AND p.fk_statut = ".self::STATUS_SIGNED;
		}
		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		if ($user->socid) {
			$sql .= " AND p.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("propal");
			$now = dol_now();

			$delay_warning = 0;
			$status = 0;
			$label = $labelShort = '';
			if ($mode == 'opened') {
				$delay_warning = $conf->propal->cloture->warning_delay;
				$status = self::STATUS_VALIDATED;
				$label = $langs->transnoentitiesnoconv("PropalsToClose");
				$labelShort = $langs->transnoentitiesnoconv("ToAcceptRefuse");
			}
			if ($mode == 'signed') {
				$delay_warning = $conf->propal->facturation->warning_delay;
				$status = self::STATUS_SIGNED;
				$label = $langs->trans("PropalsToBill"); // We set here bill but may be billed or ordered
				$labelShort = $langs->trans("ToBill");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $delay_warning / 60 / 60 / 24;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = DOL_URL_ROOT.'/comm/propal/list.php?search_status='.$status.'&mainmenu=commercial&leftmenu=propals';
			$response->url_late = DOL_URL_ROOT.'/comm/propal/list.php?search_option=late&mainmenu=commercial&leftmenu=propals&sortfield=p.datep&sortorder=asc';
			$response->img = img_object('', "propal");

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
				$response->total += $obj->total_ht;

				if ($mode == 'opened') {
					$datelimit = $this->db->jdate($obj->datefin);
					if ($datelimit < ($now - $delay_warning)) {
						$response->nbtodolate++;
					}
				}
				// TODO Definir regle des propales a facturer en retard
				// if ($mode == 'signed' && ! count($this->FactureListeArray($obj->rowid))) $this->nbtodolate++;
			}

			return $response;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
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
		global $conf, $langs;

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->ref_client = 'NEMICEPS';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = time();
		$this->fin_validite = $this->date + 3600 * 24 * 30;
		$this->cond_reglement_id   = 1;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_id   = 7;
		$this->mode_reglement_code = 'CHQ';
		$this->availability_id     = 1;
		$this->availability_code   = 'AV_NOW';
		$this->demand_reason_id    = 1;
		$this->demand_reason_code  = 'SRC_00';
		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->multicurrency_tx = 1;
		$this->multicurrency_code = $conf->currency;

		// Lines
		$nbp = min(1000, GETPOSTINT('nblines') ? GETPOSTINT('nblines') : 5);	// We can force the nb of lines to test from command line (but not more than 1000)
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new PropaleLigne($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->qty = 1;
			$line->subprice = 100;
			$line->price = 100;
			$line->tva_tx = 20;
			$line->localtax1_tx = 0;
			$line->localtax2_tx = 0;
			if ($xnbp == 2) {
				$line->total_ht = 50;
				$line->total_ttc = 60;
				$line->total_tva = 10;
				$line->remise_percent = 50;
			} else {
				$line->total_ht = 100;
				$line->total_ttc = 120;
				$line->total_tva = 20;
				$line->remise_percent = 00;
			}

			if ($num_prods > 0) {
				$prodid = mt_rand(1, $num_prods);
				$line->fk_product = $prodids[$prodid];
				$line->product_ref = 'SPECIMEN';
			}

			$this->lines[$xnbp] = $line;

			$this->total_ht       += $line->total_ht;
			$this->total_tva      += $line->total_tva;
			$this->total_ttc      += $line->total_ttc;

			$xnbp++;
		}

		return 1;
	}

	/**
	 *      Load the indicators this->nb for the state board
	 *
	 *      @return     int         Return integer <0 if ko, >0 if ok
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql .= " ".$clause." p.entity IN (".getEntity('propal').")";

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["proposals"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 *  Returns the reference to the following non used Proposal used depending on the active numbering module
	 *  defined into PROPALE_ADDON
	 *
	 *  @param	?Societe	$soc  	Object thirdparty
	 *  @return string      		Reference libre pour la propale
	 */
	public function getNextNumRef($soc)
	{
		global $conf, $langs;
		$langs->load("propal");

		$classname = getDolGlobalString('PROPALE_ADDON');

		if (!empty($classname)) {
			$mybool = false;

			$file = $classname.".php";

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/propale/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			'@phan-var-force ModeleNumRefPropales $obj';

			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($db,"Propale::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Proposal"));
			return "";
		}
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

		$langs->load('propal');
		$datas = [];
		$nofetch = !empty($params['nofetch']);

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("Proposal")];
		}
		if ($user->hasRight('propal', 'lire')) {
			$datas['picto'] = img_picto('', $this->picto, '', 0, 0, 0, '', 'paddingrightonly').'<u>'.$langs->trans("Proposal").'</u>';
			if (isset($this->status)) {
				$datas['status'] = ' '.$this->getLibStatut(5);
			}
			if (!empty($this->ref)) {
				$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
			}
			if (!$nofetch) {
				$langs->load('companies');
				if (empty($this->thirdparty)) {
					$this->fetch_thirdparty();
				}
				$datas['customer'] = '<br><b>'.$langs->trans('Customer').':</b> '.$this->thirdparty->getNomUrl(1, '', 0, 1);
			}
			if (!empty($this->ref_customer)) {
				$datas['refcustomer'] = '<br><b>'.$langs->trans('RefCustomer').':</b> '.$this->ref_customer;
			}
			if (!$nofetch) {
				$langs->load('project');
				if (is_null($this->project) || (is_object($this->project) && $this->project->isEmpty())) {
					$res = $this->fetchProject();
					if ($res > 0 && $this->project instanceof Project) {
						$datas['project'] = '<br><b>'.$langs->trans('Project').':</b> '.$this->project->getNomUrl(1, '', 0, '1');
					}
				}
			}
			if (!empty($this->total_ht)) {
				$datas['amountht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_tva)) {
				$datas['vat'] = '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_ttc)) {
				$datas['amountttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->date)) {
				$datas['date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
			}
			if (!empty($this->date_signature)) {
				$datas['datesignature'] = '<br><b>'.$langs->trans('DateSigning').':</b> '.dol_print_date($this->date_signature, 'day');
			}
			if (!empty($this->delivery_date)) {
				$datas['deliverydate'] = '<br><b>'.$langs->trans('DeliveryDate').':</b> '.dol_print_date($this->delivery_date, 'dayhour');
			}
		}

		return $datas;
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      int		$withpicto		          Add picto into link
	 *	@param      string	$option			          Where point the link ('expedition', 'document', ...)
	 *	@param      string	$get_params    	          Parameters added to url
	 *  @param	    int   	$notooltip		          1=Disable tooltip
	 *  @param      int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param      int     $addlinktonotes           -1=Disable, 0=Just add label show notes, 1=Add private note (only internal user), 2=Add public note (internal or external user), 3=Add private (internal user) and public note (internal and external user)
	 *	@return     string          		          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $get_params = '', $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = -1)
	{
		global $langs, $conf, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
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

		$url = '';
		if ($user->hasRight('propal', 'lire')) {
			if ($option == '') {
				$url = DOL_URL_ROOT.'/comm/propal/card.php?id='.$this->id.$get_params;
			} elseif ($option == 'compta') {  // deprecated
				$url = DOL_URL_ROOT.'/comm/propal/card.php?id='.$this->id.$get_params;
			} elseif ($option == 'expedition') {
				$url = DOL_URL_ROOT.'/expedition/propal.php?id='.$this->id.$get_params;
			} elseif ($option == 'document') {
				$url = DOL_URL_ROOT.'/comm/propal/document.php?id='.$this->id.$get_params;
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
		}

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight('propal', 'lire')) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("Proposal");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
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

		if ($addlinktonotes >= 0) {
			$txttoshow = '';

			if ($addlinktonotes == 0) {
				if (!empty($this->note_private) || !empty($this->note_public)) {
					$txttoshow = $langs->trans('ViewPrivateNote');
				}
			} elseif ($addlinktonotes == 1) {
				if (!empty($this->note_private)) {
					$txttoshow .= ($user->socid > 0 ? '' : dol_string_nohtmltag($this->note_private, 1));
				}
			} elseif ($addlinktonotes == 2) {
				if (!empty($this->note_public)) {
					$txttoshow .= dol_string_nohtmltag($this->note_public, 1);
				}
			} elseif ($addlinktonotes == 3) {
				if ($user->socid > 0) {
					if (!empty($this->note_public)) {
						$txttoshow .= dol_string_nohtmltag($this->note_public, 1);
					}
				} else {
					if (!empty($this->note_public)) {
						$txttoshow .= dol_string_nohtmltag($this->note_public, 1);
					}
					if (!empty($this->note_private)) {
						if (!empty($txttoshow)) {
							$txttoshow .= '<br><br>';
						}
						$txttoshow .= dol_string_nohtmltag($this->note_private, 1);
					}
				}
			}

			if ($txttoshow) {
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/comm/propal/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($txttoshow).'">';
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
	 * 	Retrieve an array of proposal lines
	 *
	 *	@param  string	$sqlforgedfilters       Filter on other fields
	 * 	@return int								>0 if OK, <0 if KO
	 */
	public function getLinesArray($sqlforgedfilters = '')
	{
		return $this->fetch_lines(0, 0, $sqlforgedfilters);
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int<0,1>	$hidedetails    Hide details of lines
	 *  @param      int<0,1>	$hidedesc       Hide description
	 *  @param      int<0,1>	$hideref        Hide ref
	 *  @param   	?array<string,mixed>  $moreparams     Array to provide more information
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("propale");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'azur';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('PROPALE_ADDON_PDF')) {
				$modele = getDolGlobalString('PROPALE_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/propale/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'propal'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'propaldet'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
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
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<div class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</div>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['projectlink'])) {
			$return .= '<span class="info-box-ref"> | '.$arraydata['projectlink'].'</span>';
		}
		$return .= '<br>';
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'total_ht')) {
			$return .= '<span class="info-box-label amount" title="'.$langs->trans("AmountHT").'">'.price($this->total_ht).'</span>';
		}
		if (!empty($arraydata['authorlink'])) {
			$return .= ' &nbsp; <span class="info-box-label">'.$arraydata['authorlink'].'</span>';
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
