<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016       Pierre-Henry Favre      <phf@atm-consulting.fr>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * 	\defgroup   multicurrency     Module MultiCurrency
 *  \brief      Handle multiple currencies on company/propal/orders ...
 *  \file       htdocs/core/modules/modMultiCurrency.class.php
 *  \ingroup    multicurrency
 *  \brief      Description and activation file for the module MultiCurrency
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module MyModule
 */
class modMultiCurrency extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 40000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'multicurrency';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "technic";
		// Module position in the family
		$this->module_position = '40';

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module to manage several foreign currencies in prices and documents";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'multicurrency';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /multicurrency/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /multicurrency/core/modules/barcode)
		// for specific css file (eg: /multicurrency/css/multicurrency.css.php)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/multicurrency/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into multicurrency/admin directory, to use to setup module.
		$this->config_page_url = array("multicurrency.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array(); // List of modules id this module is in conflict with
		$this->langfiles = array("multicurrency");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@multicurrency:$user->rights->multicurrency->read:/multicurrency/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
		//                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@multicurrency:$user->rights->othermodule->read:/multicurrency/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		//                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sales order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
		$this->tabs = array();

		// Dictionaries
		if (!isset($conf->multicurrency->enabled)) {
			$conf->multicurrency = new stdClass();
			$conf->multicurrency->enabled = 0;
		}
		$this->dictionaries = array();


		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // List of boxes

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$statusatinstall=1;
		$arraydate=dol_getdate(dol_now());
		$datestart=dol_mktime(21, 15, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
		$langs->load('multicurrency');

		$this->cronjobs = array(
			0 => array(
				'priority' => 61,
				'label' => $langs->trans('MutltiCurrencyAutoUpdateCurrencies'),
				'jobtype' => 'method',
				'class' => 'multicurrency/class/multicurrency.class.php',
				'objectname' => 'MultiCurrency',
				'method' => 'syncRates',
				'parameters' => '0,0,cron',
				'comment' => 'Update all the currencies using the currencylayer API. An API key needs to be given in the multi-currency module config page to have this job working.<br>First param is not used, Second parameter is 0 to update only already existing currency defined into the Multicurrency module or 1 to add any currency. Third parameter must be "cron".',
				'frequency' => 1,
				'unitfrequency' => 2678400,
				'status' => $statusatinstall,
				'test' => 'isModEnabled("cron")',
				'datestart' => $datestart
			),
		);

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		$this->rights[$r][0] = 40001;
		$this->rights[$r][1] = 'Read currencies and their rates';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'currency';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = 40002;
		$this->rights[$r][1] = 'Create/Update currencies and their rates';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'currency';
		$this->rights[$r][5] = 'write';
		$r++;

		$this->rights[$r][0] = 40003;
		$this->rights[$r][1] = 'Delete currencies and their rates';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'currency';
		$this->rights[$r][5] = 'delete';
		$r++;

		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		// Exports
		$r = 1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='MyModule';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_icon[$r]='generic:MyModule';
		// $this->export_permission[$r]=array(array("multicurrency","level1","level2"));
		// $this->export_fields_array[$r]=array(
		//	's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone',
		//	's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode',
		//	's.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",
		//	'f.datef'=>"DateInvoice",'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.total_tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus',
		//	'f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",
		//	'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",
		//	'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef'
		//);
		// $this->export_TypeFields_array[$r]=array(
		//	't.date'=>'Date', 't.qte'=>'Numeric', 't.poids'=>'Numeric', 't.fad'=>'Numeric', 't.paq'=>'Numeric', 't.stockage'=>'Numeric', 't.fadparliv'=>'Numeric',
		//	't.livau100'=>'Numeric', 't.forfait'=>'Numeric', 's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text',
		//	's.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text',
		//	'f.ref'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",
		//	'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'fd.description'=>"Text",'fd.subprice'=>"Numeric",
		//	'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_tva'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.date_start'=>"Date",
		//	'fd.date_end'=>"Date",'fd.special_code'=>'Numeric','fd.product_type'=>"Numeric",'fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text',
		//	'p.accountancy_code_sell'=>'Text'
		//);
		// $this->export_entities_array[$r]=array(
		//	's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company',
		//	's.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company',
		//	'f.rowid'=>"invoice",'f.ref'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total_ht'=>"invoice",'f.total_ttc'=>"invoice",
		//	'f.total_tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",
		//	'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",
		//	'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product'
		//);
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		$res = $this->_init($sql, $options);

		if ($res) {
			$this->createFirstCurrency();
		}

		return $res;
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Function called when module is enabled
	 * Create the currency from general setting
	 *
	 * @return 	int		1 if OK, 0 if KO
	 */
	private function createFirstCurrency()
	{
		global $conf, $user, $langs;

		$multicurrency = new MultiCurrency($this->db);

		if (! $multicurrency->checkCodeAlreadyExists($conf->currency)) {
			$langs->loadCacheCurrencies('');

			$multicurrency->code = $conf->currency;
			$multicurrency->name = $langs->cache_currencies[$conf->currency]['label'].' ('.$langs->getCurrencySymbol($conf->currency).')';
			$r = $multicurrency->create($user);

			if ($r > 0) {
				$multicurrency->addRate(1);
			} else {
				return 0;
			}
		}
		return 1;
	}
}
