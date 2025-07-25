<?php

/* Copyright (C) 2016   	Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2023   	Romain Neil             <contact@romain-neil.fr>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		William Mead			<william@m34d.com>
 * Copyright (C) 2025		Charlene Benke			<charlene@patas-monkey.com>
 *
 * This program is free software you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * API class for receive files
 *
 * @since	6.0.0	Initial implementation
 *
 * @access protected
 * @class Documents {@requires user,external}
 */
class Documents extends DolibarrApi
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}


	/**
	 * Download a document
	 *
	 * Note that, this API is similar to using the wrapper link "documents.php" to download a file (used for
	 * internal HTML links of documents into application), but with no need to have a session cookie (the token is used instead).
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param   string  $modulepart     Name of module or area concerned by file download ('facture', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
	 * @return  array                   List of documents
	 * @phan-return array{filename:string,content-type:string,filesize:false|int,content:string,encoding:string}
	 * @phpstan-return array{filename:string,content-type:string,filesize:false|int,content:string,encoding:string}
	 *
	 * @url GET /download
	 *
	 * @throws	RestException	400		Bad value for parameter modulepart or original_file
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		File not found
	 */
	public function index($modulepart, $original_file = '')
	{
		global $conf;

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		//--- Finds and returns the document
		$entity = $conf->entity;

		// Special cases that need to use get_exdir to get real dir of object
		// If future, all object should use this to define path of documents.
		/*
		$tmpreldir = '';
		if ($modulepart == 'supplier_invoice') {
			$tmpreldir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier');
		}

		$relativefile = $tmpreldir.dol_sanitizeFileName($object->ref); */
		$relativefile = $original_file;

		$check_access = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'read');
		$accessallowed = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file = $check_access['original_file'];

		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
			throw new RestException(403);
		}
		if (!$accessallowed) {
			throw new RestException(403);
		}

		$filename = basename($original_file);
		$original_file_osencoded = dol_osencode($original_file); // New file name encoded in OS encoding charset

		if (!file_exists($original_file_osencoded)) {
			dol_syslog("Try to download not found file ".$original_file_osencoded, LOG_WARNING);
			throw new RestException(404, 'File not found');
		}

		$file_content = file_get_contents($original_file_osencoded);
		return array('filename' => $filename, 'content-type' => dol_mimetype($filename), 'filesize' => filesize($original_file), 'content' => base64_encode($file_content), 'encoding' => 'base64');
	}


	/**
	 * Build a document
	 *
	 * Test sample 1: { "modulepart": "invoice", "original_file": "FA1701-001/FA1701-001.pdf", "doctemplate": "crabe", "langcode": "fr_FR" }.
	 *
	 * Supported modules: invoice, order, proposal, contract, supplier invoice, shipment, mrp
	 *
	 * @since	7.0.0	Initial implementation, support for invoice, order and proposal documents
	 * @since	18.0.0	Added support for contract and suppliers invoice documents
	 * @since	19.0.0	Added support for shipment documents
	 * @since	20.0.0	Added support for mrp documents
	 *
	 * @param   string  $modulepart		Name of module or area concerned by file download ('thirdparty', 'member', 'proposal', 'supplier_proposal', 'order', 'supplier_order', 'invoice', 'supplier_invoice', 'shipment', 'project',  ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf).
	 * @param	string	$doctemplate	Set here the doc template to use for document generation (If not set, use the default template).
	 * @param	string	$langcode		Language code like 'en_US', 'fr_FR', 'es_ES', ... (If not set, use the default language).
	 * @return  array                   List of documents
	 * @phan-return array{filename:string,content-type:string,filesize:false|int,content:string,langcode:string,template:?string,encoding:string}
	 * @phpstan-return array{filename:string,content-type:string,filesize:false|int,content:string,langcode:string,template:?string,encoding:string}
	 *
	 * @url PUT /builddoc
	 *
	 * @throws	RestException	400		Bad value for parameter modulepart or original_file
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Invoice, Order, Proposal, Contract or Shipment not found
	 * @throws	RestException	500		Error generating document
	 * @throws	RestException	501		File not found
	 */
	public function builddoc($modulepart, $original_file = '', $doctemplate = '', $langcode = '')
	{
		global $conf, $langs;

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		$outputlangs = $langs;
		if ($langcode && $langs->defaultlang != $langcode) {
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($langcode);
		}

		//--- Finds and returns the document
		$entity = $conf->entity;

		// Special cases that need to use get_exdir to get real dir of object
		// If future, all object should use this to define path of documents.
		/*
		$tmpreldir = '';
		if ($modulepart == 'supplier_invoice') {
			$tmpreldir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier');
		}

		$relativefile = $tmpreldir.dol_sanitizeFileName($object->ref); */
		$relativefile = $original_file;

		$check_access = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'write');
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
			throw new RestException(403);
		}
		if (!$accessallowed) {
			throw new RestException(403);
		}

		// --- Generates the document
		$hidedetails = !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 0 : 1;
		$hidedesc = !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 0 : 1;
		$hideref = !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 0 : 1;

		$templateused = '';

		if ($modulepart == 'facture' || $modulepart == 'invoice') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$tmpobject = new Facture($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if (!$result) {
				throw new RestException(404, 'Invoice not found');
			}

			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				throw new RestException(500, 'Error generating document');
			}
		} elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'invoice_supplier') {
			require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
			$tmpobject = new FactureFournisseur($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if (!$result) {
				throw new RestException(404, 'Supplier invoice not found');
			}

			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				throw new RestException(500, 'Error generating document');
			}
		} elseif ($modulepart == 'commande' || $modulepart == 'order') {
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$tmpobject = new Commande($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if (!$result) {
				throw new RestException(404, 'Order not found');
			}
			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				throw new RestException(500, 'Error generating document');
			}
		} elseif ($modulepart == 'propal' || $modulepart == 'proposal') {
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$tmpobject = new Propal($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if (!$result) {
				throw new RestException(404, 'Proposal not found');
			}
			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0) {
				throw new RestException(500, 'Error generating document');
			}
		} elseif ($modulepart == 'contrat' || $modulepart == 'contract') {
			require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

			$tmpobject = new Contrat($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));

			if (!$result) {
				throw new RestException(404, 'Contract not found');
			}

			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);

			if ($result <= 0) {
				throw new RestException(500, 'Error generating document missing doctemplate parameter');
			}
		} elseif ($modulepart == 'expedition' || $modulepart == 'shipment') {
			require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';

			$tmpobject = new Expedition($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));

			if (!$result) {
				throw new RestException(404, 'Shipment not found');
			}

			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);

			if ($result <= 0) {
				throw new RestException(500, 'Error generating document missing doctemplate parameter');
			}
		} elseif ($modulepart == 'mrp') {
			require_once DOL_DOCUMENT_ROOT . '/mrp/class/mo.class.php';

			$tmpobject = new Mo($this->db);
			$result = $tmpobject->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));

			if (!$result) {
				throw new RestException(404, 'MO not found');
			}

			$templateused = $doctemplate ? $doctemplate : $tmpobject->model_pdf;
			$result = $tmpobject->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);

			if ($result <= 0) {
				throw new RestException(500, 'Error generating document missing doctemplate parameter');
			}
		} else {
			throw new RestException(403, 'Generation not available for this modulepart');
		}

		$filename = basename($original_file);
		$original_file_osencoded = dol_osencode($original_file); // New file name encoded in OS encoding charset

		if (!file_exists($original_file_osencoded)) {
			throw new RestException(404, 'File not found');
		}

		$file_content = file_get_contents($original_file_osencoded);
		return array('filename' => $filename, 'content-type' => dol_mimetype($filename), 'filesize' => filesize($original_file), 'content' => base64_encode($file_content), 'langcode' => $outputlangs->defaultlang, 'template' => $templateused, 'encoding' => 'base64');
	}

	/**
	 * List documents of an element
	 *
	 * Use element ID or Ref.
	 * Supported modules: thirdparty, user, member, proposal, order, supplier_order, shipment, invoice, supplier_invoice, product, event, expensereport, knowledgemanagement, category, contract
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param   string 	$modulepart		Name of module or area concerned ('thirdparty', 'member', 'proposal', 'order', 'invoice', 'supplier_invoice', 'shipment', 'project',  ...)
	 * @param	int		$id				ID of element
	 * @param	string	$ref			Ref of element
	 * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
	 * @param	string	$sortorder		Sort order ('asc' or 'desc')
	 * @param	int		$limit			List limit
	 * @param	int		$page			Page number
	 * @param	string	$content_type	Filter on content-type (example 'application/pdf' or 'application/pdf,image/jpeg'))
	 * @param	bool	$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	array					Array of documents with path
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url GET /
	 *
	 * @throws	RestException	400		Bad value for parameter modulepart, id or ref
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Thirdparty, User, Member, Order, Invoice or Proposal not found
	 * @throws	RestException	500		Error while fetching object
	 * @throws	RestException	503		Error when retrieve ecm list
	 */
	public function getDocumentsListByElement($modulepart, $id = 0, $ref = '', $sortfield = '', $sortorder = '', $limit = 100, $page = 0, $content_type = '', $pagination_data = false)
	{
		global $conf;
		/** @var Conf $conf */

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}

		if (empty($id) && empty($ref)) {
			throw new RestException(400, 'bad value for parameter id or ref');
		}

		$id = (empty($id) ? 0 : $id);
		$recursive = 0;
		$type = 'files';

		if ($modulepart == 'societe' || $modulepart == 'thirdparty') {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

			if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
				throw new RestException(403);
			}

			$object = new Societe($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Thirdparty not found');
			}

			$upload_dir = $conf->societe->multidir_output[$object->entity]."/".$object->id;
		} elseif ($modulepart == 'user') {
			require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

			// Can get doc if has permission to read all user or if it is user itself
			if (!DolibarrApiAccess::$user->hasRight('user', 'user', 'lire') && DolibarrApiAccess::$user->id != $id) {
				throw new RestException(403);
			}

			$object = new User($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'User not found');
			}

			$upload_dir = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id;
		} elseif ($modulepart == 'adherent' || $modulepart == 'member') {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

			if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
				throw new RestException(403);
			}

			$object = new Adherent($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Member not found');
			}

			$upload_dir = $conf->adherent->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'member');
		} elseif ($modulepart == 'propal' || $modulepart == 'proposal') {
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

			if (!DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
				throw new RestException(403);
			}

			$object = new Propal($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Proposal not found');
			}

			$upload_dir = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
		} elseif ($modulepart == 'supplier_proposal') {
			require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';

			if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'read')) {
				throw new RestException(403);
			}

			$object = new Propal($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Supplier proposal not found');
			}

			$upload_dir = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
		} elseif ($modulepart == 'commande' || $modulepart == 'order') {
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

			if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
				throw new RestException(403);
			}

			$object = new Commande($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Order not found');
			}

			$upload_dir = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
		} elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order') {
			$modulepart = 'supplier_order';

			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

			if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'commande', 'lire') && !DolibarrApiAccess::$user->hasRight('supplier_order', 'lire')) {
				throw new RestException(403);
			}

			$object = new CommandeFournisseur($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Purchase order not found');
			}

			$upload_dir = $conf->fournisseur->dir_output."/commande/".dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'shipment' || $modulepart == 'expedition') {
			require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

			if (!DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
				throw new RestException(403);
			}

			$object = new Expedition($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Shipment not found');
			}

			$upload_dir = $conf->expedition->dir_output."/sending/".get_exdir(0, 0, 0, 1, $object, 'shipment');
		} elseif ($modulepart == 'facture' || $modulepart == 'invoice') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

			if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
				throw new RestException(403);
			}

			$object = new Facture($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Invoice not found');
			}

			$upload_dir = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
		} elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice') {
			$modulepart = 'supplier_invoice';

			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

			if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'facture', 'lire') && !DolibarrApiAccess::$user->hasRight('supplier_invoice', 'lire')) {
				throw new RestException(403);
			}

			$object = new FactureFournisseur($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Invoice not found');
			}

			$upload_dir = $conf->fournisseur->dir_output."/facture/".get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'produit' || $modulepart == 'product') {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

			if (!DolibarrApiAccess::$user->hasRight('produit', 'lire')) {
				throw new RestException(403);
			}

			$object = new Product($this->db);
			$result = $object->fetch($id, $ref);
			if ($result == 0) {
				throw new RestException(404, 'Product not found');
			} elseif ($result < 0) {
				throw new RestException(500, 'Error while fetching object: '.$object->error);
			}

			$upload_dir = $conf->product->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, 'product');
		} elseif ($modulepart == 'agenda' || $modulepart == 'action' || $modulepart == 'event') {
			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

			if (!DolibarrApiAccess::$user->hasRight('agenda', 'myactions', 'read') && !DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
				throw new RestException(403);
			}

			$object = new ActionComm($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Event not found');
			}

			$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'expensereport') {
			require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

			if (!DolibarrApiAccess::$user->hasRight('expensereport', 'read')) {
				throw new RestException(403);
			}

			$object = new ExpenseReport($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Expense report not found');
			}

			$upload_dir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'knowledgemanagement') {
			require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';

			if (!DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
				throw new RestException(403);
			}

			$object = new KnowledgeRecord($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'KM article not found');
			}

			$upload_dir = $conf->knowledgemanagement->dir_output.'/knowledgerecord/'.dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'categorie' || $modulepart == 'category') {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

			if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
				throw new RestException(403);
			}

			$object = new Categorie($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Category not found');
			}

			$upload_dir = $conf->categorie->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'category').$object->id."/photos/".dol_sanitizeFileName($object->ref);
		} elseif ($modulepart == 'ecm') {
			throw new RestException(500, 'Modulepart Ecm not implemented yet.');
			// require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

			// if (!DolibarrApiAccess::$user->hasRight('ecm', 'read')) {
			// 	throw new RestException(403);
			// }

			// // $object = new EcmDirectory($this->db);
			// // $result = $object->fetch($ref);
			// // if (!$result) {
			// // 	throw new RestException(404, 'EcmDirectory not found');
			// // }
			// $upload_dir = $conf->ecm->dir_output;
			// $type = 'all';
			// $recursive = 0;
		} elseif ($modulepart == 'contrat' || $modulepart == 'contract') {
			$modulepart = 'contrat';
			require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

			$object = new Contrat($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Contract not found');
			}

			$upload_dir = $conf->contract->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'contract');
		} elseif ($modulepart == 'intervention' || $modulepart == 'ficheinter') {
			$modulepart = 'ficheinter';
			require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';

			$object = new Fichinter($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Interventional not found');
			}

			$upload_dir = $conf->ficheinter->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'ficheinter');
		} elseif ($modulepart == 'projet' || $modulepart == 'project') {
			$modulepart = 'project';
			require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

			$object = new Project($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'Project not found');
			}

			$upload_dir = $conf->project->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'project');
		} elseif ($modulepart == 'mrp') {
			$modulepart = 'mrp';
			require_once DOL_DOCUMENT_ROOT . '/mrp/class/mo.class.php';

			$object = new Mo($this->db);
			$result = $object->fetch($id, $ref);
			if (!$result) {
				throw new RestException(404, 'MO not found');
			}

			$upload_dir = $conf->mrp->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'mrp');
		} else {
			throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
		}

		$objectType = $modulepart;
		if (! empty($object->id) && ! empty($object->table_element)) {
			$objectType = $object->table_element;
		}

		$filearray = dol_dir_list($upload_dir, $type, $recursive, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
		$countarray = count($filearray);
		$filearray = array_slice($filearray, $limit * $page, $limit);
		if (empty($filearray)) {
			throw new RestException(404, 'Search for modulepart '.$modulepart.' with Id '.$object->id.(!empty($object->ref) ? ' or Ref '.$object->ref : '').' does not return any document.');
		} else {
			if (($object->id) > 0 && !empty($modulepart)) {
				require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
				$ecmfile = new EcmFiles($this->db);
				$result = $ecmfile->fetchAll('', '', 0, 0, array('t.src_object_type' => $objectType, 't.src_object_id' => $object->id));
				if ($result < 0) {
					throw new RestException(503, 'Error when retrieve ecm list : '.$this->db->lasterror());
				} elseif (is_array($ecmfile->lines) && count($ecmfile->lines) > 0) {
					$count = count($filearray);
					for ($i = 0 ; $i < $count ; $i++) {
						foreach ($ecmfile->lines as $line) {
							unset($line->db);
							if ($filearray[$i]['name'] == $line->filename) {
								// Next line converts EcmFilesLine properties to array
								$filearray[$i] = array_merge($filearray[$i], (array) $line);
							}
						}
						if (isset($line->filename)) $filearray[$i]['content-type'] = dol_mimetype($line->filename);
						$arraycontenttype = explode(",", $content_type);
						if (!empty($content_type) && isset($line->filename) && !in_array(dol_mimetype($line->filename), $arraycontenttype)) {
							unset($filearray[$i]);
							$countarray -= 1;
						}
					}
				}
			}
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$tmp = $filearray;
			$filearray = [];
			$filearray['data'] = $tmp;
			$filearray['pagination'] = [
				'total' => (int) $countarray,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $countarray / $limit),
				'limit' => $limit
			];
		}

		return $filearray;
	}


	/**
	 * Return a document.
	 *
	 * @param   int         $id          ID of document
	 * @return  array                    Array with data of file
	 *
	 * @throws RestException
	 */
	/*
	public function get($id) {
		return array('note'=>'xxx');
	}*/


	/**
	 * Upload a document
	 *
	 * Test sample for invoice: { "filename": "mynewfile.txt", "modulepart": "invoice", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample for supplier invoice: { "filename": "mynewfile.txt", "modulepart": "supplier_invoice", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample for medias file: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
	 *
	 * Supported modules: invoice, order, supplier_order, task/project_task, product/service, expensereport, fichinter, member, propale, agenda, contact
	 *
	 * @since	6.0.0	Initial implementation
	 *
	 * @param   string  $filename           	Name of file to create ('FA1705-0123.txt')
	 * @param   string  $modulepart         	Name of module or area concerned by file upload ('product', 'service', 'invoice', 'proposal', 'project', 'project_task', 'supplier_invoice', 'expensereport', 'member', ...)
	 * @param   string  $ref                	Reference of object (This will define subdir automatically and store submitted file into it)
	 * @param   string  $subdir       			Subdirectory (Only if $ref is not provided)
	 * @param   string  $filecontent        	File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $fileencoding       	File encoding (''=no encoding, 'base64'=Base 64)
	 * @param   int 	$overwriteifexists  	Overwrite file if exists (1 by default)
	 * @param   int 	$createdirifnotexists  	Create subdirectories if the doesn't exists (1 by default)
	 * @param   int     $position               Position
	 * @param   string  $cover                  Cover info
	 * @param   array   $array_options          Array for extrafields of ECM index table
	 * @param	int		$generateThumbs			1=Will generate the small and mini thumbs if applicable
	 * @return  string
	 *
	 * @phan-param   array<string,string>   $array_options
	 * @phpstan-param   array<string,string>   $array_options
	 *
	 * @url POST /upload
	 *
	 * @throws	RestException	400		Bad Request
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Object not found
	 * @throws	RestException	500		Error on file operation
	 */
	public function post($filename, $modulepart, $ref = '', $subdir = '', $filecontent = '', $fileencoding = '', $overwriteifexists = 0, $createdirifnotexists = 1, $position = 0, $cover = '', $array_options = [], $generateThumbs = 0)
	{
		global $conf;

		$modulepartorig = $modulepart;

		if (empty($modulepart)) {
			throw new RestException(400, 'Modulepart not provided.');
		}

		$newfilecontent = '';
		if (empty($fileencoding)) {
			$newfilecontent = $filecontent;
		}
		if ($fileencoding == 'base64') {
			$newfilecontent = base64_decode($filecontent);
		}

		$original_file = dol_sanitizeFileName($filename);
		$relativefile = 'UNSET';

		// Define $uploadir
		$object = null;
		$entity = DolibarrApiAccess::$user->entity;
		if (empty($entity)) {
			$entity = 1;
		}

		if ($ref) {
			$tmpreldir = '';
			$fetchbyid = false;

			if ($modulepart == 'facture' || $modulepart == 'invoice') {
				$modulepart = 'facture';

				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$object = new Facture($this->db);
			} elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice') {
				$modulepart = 'supplier_invoice';

				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$object = new FactureFournisseur($this->db);
			} elseif ($modulepart == 'commande' || $modulepart == 'order') {
				$modulepart = 'commande';

				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				$object = new Commande($this->db);
			} elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order') {
				$modulepart = 'supplier_order';

				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$object = new CommandeFournisseur($this->db);
			} elseif ($modulepart == 'projet' || $modulepart == 'project') {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$object = new Project($this->db);
			} elseif ($modulepart == 'task' || $modulepart == 'project_task') {
				$modulepart = 'project_task';

				require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
				$object = new Task($this->db);

				$task_result = $object->fetch(0, $ref);

				// Fetching the tasks project is required because its out_dir might be a sub-directory of the project
				if ($task_result > 0) {
					$project_result = $object->fetchProject();

					if ($project_result >= 0) {
						$tmpreldir = dol_sanitizeFileName($object->project->ref).'/';
					}
				} else {
					throw new RestException(500, 'Error while fetching Task '.$ref);
				}
			} elseif ($modulepart == 'product' || $modulepart == 'produit' || $modulepart == 'service' || $modulepart == 'produit|service') {
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$object = new Product($this->db);
			} elseif ($modulepart == 'expensereport') {
				require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
				$object = new ExpenseReport($this->db);
			} elseif ($modulepart == 'ficheinter' || $modulepart == 'intervention') {
				require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
				$object = new Fichinter($this->db);
			} elseif ($modulepart == 'adherent' || $modulepart == 'member') {
				$modulepart = 'adherent';
				require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$object = new Adherent($this->db);
			} elseif ($modulepart == 'proposal' || $modulepart == 'propal' || $modulepart == 'propale') {
				$modulepart = 'propale';
				require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
				$object = new Propal($this->db);
			} elseif ($modulepart == 'agenda' || $modulepart == 'action' || $modulepart == 'event') {
				$modulepart = 'agenda';
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$object = new ActionComm($this->db);
			} elseif ($modulepart == 'contact' || $modulepart == 'socpeople') {
				$modulepart = 'contact';
				require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$object = new Contact($this->db);
				$fetchbyid = true;
			} elseif ($modulepart == 'contrat' || $modulepart == 'contract') {
				$modulepart = 'contrat';
				require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
				$object = new Contrat($this->db);
			} elseif ($modulepart == 'mrp') {
				$modulepart = 'mrp';
				require_once DOL_DOCUMENT_ROOT . '/mrp/class/mo.class.php';
				$object = new Mo($this->db);
			} elseif ($modulepart == 'ecm') {
				throw new RestException(500, 'Using a non empty "ref" is not compatible with using modulepart = '.$modulepart);
			} else {
				// TODO Implement additional moduleparts
				throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
			}

			if (is_object($object)) {
				if ($fetchbyid) {
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$result = $object->fetch((int) $ref);
				} else {
					$result = $object->fetch(0, $ref);
				}

				if ($result == 0) {
					throw new RestException(404, "Object with ref '".$ref."' was not found.");
				} elseif ($result < 0) {
					throw new RestException(500, 'Error while fetching object: '.$object->error);
				}
			}

			if (!($object->id > 0)) {
				throw new RestException(404, 'The object '.$modulepart." with ref '".$ref."' was not found.");
			}

			// Special cases that need to use get_exdir to get real dir of object
			// In future, all object should use this to define path of documents.
			if ($modulepart == 'supplier_invoice') {
				$tmpreldir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier');
			}

			// Test on permissions
			//if ($modulepart != 'ecm') {	// Here $modulepart is always != 'ecm'
			$relativefile = $tmpreldir.dol_sanitizeFileName($object->ref);
			$tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, $ref, 'write');
			$upload_dir = $tmp['original_file']; // No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir
			/*} else {
				if (!DolibarrApiAccess::$user->hasRight('ecm', 'upload')) {
					throw new RestException(403, 'Missing permission to upload files in ECM module');
				}
				$upload_dir = $conf->medias->multidir_output[$conf->entity];
			}*/

			if (empty($upload_dir) || $upload_dir == '/') {
				throw new RestException(500, 'This value of modulepart ('.$modulepart.') does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
			}
		} else {
			if ($modulepart == 'invoice') {
				$modulepart = 'facture';
			}
			if ($modulepart == 'member') {
				$modulepart = 'adherent';
			}

			// Test on permissions
			if ($modulepart != 'ecm') {
				$relativefile = $subdir;
				$tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'write');
				$upload_dir = $tmp['original_file']; // No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir
			} else {
				if (!DolibarrApiAccess::$user->hasRight('ecm', 'upload')) {
					throw new RestException(403, 'Missing permission to upload files in ECM module');
				}
				$upload_dir = $conf->medias->multidir_output[$conf->entity];
			}

			if (empty($upload_dir) || $upload_dir == '/') {
				if (!empty($tmp['error'])) {
					throw new RestException(403, 'Error returned by dol_check_secure_access_document: '.$tmp['error']);
				} else {
					throw new RestException(400, 'This value of modulepart ('.$modulepart.') is not allowed with this value of subdir ('.$relativefile.')');
				}
			}
		}
		// $original_file here is still value of filename without any dir.

		$upload_dir = dol_sanitizePathName($upload_dir);

		if (!empty($createdirifnotexists)) {
			if (dol_mkdir($upload_dir) < 0) { // needed by products
				throw new RestException(500, 'Error while trying to create directory '.$upload_dir);
			}
		}

		$destfile = $upload_dir.'/'.$original_file;
		$destfiletmp = DOL_DATA_ROOT.'/admin/temp/'.$original_file;
		dol_delete_file($destfiletmp);
		//var_dump($original_file);exit;

		if (!dol_is_dir(dirname($destfile))) {
			throw new RestException(400, 'Directory does not exists : '.dirname($destfile));
		}

		if (!$overwriteifexists && dol_is_file($destfile)) {
			throw new RestException(400, "File with name '".$original_file."' already exists.");
		}

		// in case temporary directory admin/temp doesn't exist
		if (!dol_is_dir(dirname($destfiletmp))) {
			dol_mkdir(dirname($destfiletmp));
		}

		$fhandle = @fopen($destfiletmp, 'w');
		if ($fhandle) {
			$nbofbyteswrote = fwrite($fhandle, $newfilecontent);
			fclose($fhandle);
			dolChmod($destfiletmp);
		} else {
			throw new RestException(500, "Failed to open file '".$destfiletmp."' for write");
		}

		$disablevirusscan = 0;
		$src_file = $destfiletmp;
		$dest_file = $destfile;

		// Security:
		// If we need to make a virus scan
		if (empty($disablevirusscan) && file_exists($src_file)) {
			$checkvirusarray = dolCheckVirus($src_file, $dest_file);
			if (count($checkvirusarray)) {
				dol_syslog('Files.lib::dol_move_uploaded_file File "'.$src_file.'" (target name "'.$dest_file.'") KO with antivirus: errors='.implode(',', $checkvirusarray), LOG_WARNING);
				throw new RestException(500, 'ErrorFileIsInfectedWithAVirus: '.implode(',', $checkvirusarray));
			}
		}

		// Security:
		// Disallow file with some extensions. We rename them.
		// Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
		if (isAFileWithExecutableContent($dest_file) && !getDolGlobalString('MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED')) {
			// $upload_dir ends with a slash, so be must be sure the medias dir to compare to ends with slash too.
			$publicmediasdirwithslash = $conf->medias->multidir_output[$conf->entity];
			if (!preg_match('/\/$/', $publicmediasdirwithslash)) {
				$publicmediasdirwithslash .= '/';
			}

			if (strpos($upload_dir, $publicmediasdirwithslash) !== 0 || !getDolGlobalInt("MAIN_DOCUMENT_DISABLE_NOEXE_IN_MEDIAS_DIR")) {	// We never add .noexe on files into media directory
				$dest_file .= '.noexe';
			}
		}

		// Security:
		// We refuse cache files/dirs, upload using .. and pipes into filenames.
		if (preg_match('/^\./', basename($src_file)) || preg_match('/\.\./', $src_file) || preg_match('/[<>|]/', $src_file)) {
			dol_syslog("Refused to deliver file ".$src_file, LOG_WARNING);
			throw new RestException(500, "Refused to deliver file ".$src_file);
		}

		// Security:
		// We refuse cache files/dirs, upload using .. and pipes into filenames.
		if (preg_match('/^\./', basename($dest_file)) || preg_match('/\.\./', $dest_file) || preg_match('/[<>|]/', $dest_file)) {
			dol_syslog("Refused to deliver file ".$dest_file, LOG_WARNING);
			throw new RestException(500, "Refused to deliver file ".$dest_file);
		}

		$moreinfo = array('note_private' => 'File uploaded using API /documents from IP '.getUserRemoteIP());
		if (!empty($object) && is_object($object) && $object->id > 0) {
			$moreinfo['src_object_type'] = $object->table_element;
			$moreinfo['src_object_id'] = $object->id;
		}
		if (!empty($array_options)) {
			$moreinfo = array_merge($moreinfo, ["array_options" => $array_options]);
		}
		if (!empty($position)) {
			$moreinfo = array_merge($moreinfo, ["position" => $position]);
		}
		if (!empty($cover)) {
			$moreinfo = array_merge($moreinfo, ["cover" => $cover]);
		}
		$moreinfo['gen_or_uploaded'] = 'api';

		// Move the temporary file at its final emplacement
		$result = dol_move($destfiletmp, $dest_file, '0', $overwriteifexists, 1, 1, $moreinfo);
		if (!$result) {
			throw new RestException(500, "Failed to move file into '".$dest_file."'");
		}

		if (is_object($object) && $generateThumbs) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			if (image_format_supported($dest_file)) {
				$object->addThumbs($dest_file);
			}
		}

		return dol_basename($destfile);
	}

	/**
	 * Delete a document
	 *
	 * @since	11.0.0	Initial implementation
	 *
	 * @param   string  $modulepart     Name of module or area concerned by file download ('product', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: PRODUCT-REF-999/IMAGE-999.jpg)
	 * @return  array                   Success code
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url DELETE /
	 *
	 * @throws	RestException	400  Bad value for parameter modulepart
	 * @throws	RestException	400  Bad value for parameter original_file
	 * @throws	RestException	403  Access denied
	 * @throws	RestException	404  File not found
	 * @throws	RestException	500  Error on file operation
	 */
	public function delete($modulepart, $original_file)
	{
		global $conf;

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		//--- Finds and returns the document
		$entity = $conf->entity;

		// Special cases that need to use get_exdir to get real dir of object
		// If future, all object should use this to define path of documents.
		/*
		$tmpreldir = '';
		if ($modulepart == 'supplier_invoice') {
			$tmpreldir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier');
		}

		$relativefile = $tmpreldir.dol_sanitizeFileName($object->ref); */
		$relativefile = $original_file;

		$check_access = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'read');
		$accessallowed = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file = $check_access['original_file'];

		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
			throw new RestException(403);
		}
		if (!$accessallowed) {
			throw new RestException(403);
		}

		$filename = basename($original_file);
		$original_file_osencoded = dol_osencode($original_file); // New file name encoded in OS encoding charset

		if (!file_exists($original_file_osencoded)) {
			dol_syslog("Try to download not found file ".$original_file_osencoded, LOG_WARNING);
			throw new RestException(404, 'File not found');
		}

		if (@unlink($original_file_osencoded)) {
			return array(
				'success' => array(
					'code' => 200,
					'message' => 'Document deleted'
				)
			);
		}

		throw new RestException(403);
	}
}
