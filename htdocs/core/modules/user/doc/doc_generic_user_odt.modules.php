<?php

/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
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
* or see https://www.gnu.org/
*/

/**
 *	\file       htdocs/core/modules/user/doc/doc_generic_user_odt.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_user_odt extends ModelePDFUser
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'USER_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 0; // Manage the vat option USER_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'. getCallerInfoString(), LOG_ERR);
			return;
		}

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}
	}


	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array('companies', 'errors'));

		$form = new Form($this->db);

		$odtChosen = getDolGlobalInt('MAIN_PROPAL_CHOOSE_ODT_DOCUMENT') > 0;
		$odtPath = trim(getDolGlobalString('USER_ADDON_PDF_ODT_PATH'));

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="USER_ADDON_PDF_ODT_PATH">';
		if ($odtChosen) {
			$texte .= '<input type="hidden" name="param2" value="USER_ADDON_PDF_ODT_DEFAULT">';
			$texte .= '<input type="hidden" name="param3" value="USER_ADDON_PDF_ODT_TOBILL">';
			$texte .= '<input type="hidden" name="param4" value="USER_ADDON_PDF_ODT_CLOSED">';
		}
		$texte .= '<table class="nobordernopadding centpercent">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', $odtPath));
		$listoffiles = array();
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), '');
			} else {
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) {
					$listoffiles = array_merge($listoffiles, $tmpfiles);
				}
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenODT");
		$texthelp .= '<br><br><span class="opacitymedium">'.$langs->trans("ExampleOfDirectoriesForModelGen").'</span>';
		// Add list of substitution keys
		$texthelp .= '<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		// Scan directories
		if (count($listofdir)) {
			$texte .= $langs->trans("NumberOfModelFilesFound").': <b>'.count($listoffiles).'</b>';

			if ($odtChosen) {
				// Model for creation
				$list = ModelePDFUser::liste_modeles($this->db);
				$texte .= '<table width="50%;">';
				$texte .= '<tr>';
				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelPropalCreate").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value2', $list, getDolGlobalString('USER_ADDON_PDF_ODT_DEFAULT'));
				$texte .= "</td></tr>";

				$texte .= '<tr>';
				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelPropalToBill").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value3', $list, getDolGlobalString('USER_ADDON_PDF_ODT_TOBILL'));
				$texte .= "</td></tr>";
				$texte .= '<tr>';

				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelPropalClosed").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value4', $list, getDolGlobalString('USER_ADDON_PDF_ODT_CLOSED'));
				$texte .= "</td></tr>";
				$texte .= '</table>';
			}
			$texte .= '<div id="div_'.get_class($this).'" class="hiddenx">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$texte .= '- '.$file['name'].' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=users/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a>';
				$texte .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir=USER_ADDON_PDF_ODT_PATH&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
				$texte .= '<br>';
			}
			$texte .= '</div>';
		}

		$texte .= '<br>';
		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$texte .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte .= '<textarea class="flat textareafordir" spellcheck="false" cols="60" name="value1">';
		$texte .= $odtPath;
		$texte .= '</textarea>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.dol_escape_htmltag($langs->trans("Modify")).'">';
		$texte .= '<br></div></div>';

		// Add input to upload a new template file.
		$texte .= '<div>'.$langs->trans("UploadNewTemplate");
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$texte .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		$texte .= ' <input type="file" name="uploadfile">';
		$texte .= '<input type="hidden" value="USER_ADDON_PDF_ODT_PATH" name="keyforuploaddir">';
		$texte .= '<input type="submit" class="button smallpaddingimp reposition" value="'.dol_escape_htmltag($langs->trans("Upload")).'" name="upload">';
		$texte .= '</div>';

		$texte .= '</td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param	User		$object				Object source to build document
	 *	@param	Translate	$outputlangs		Lang output object
	 *	@param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	int<0,1>	$hidedetails		Do not show line details
	 *	@param	int<0,1>	$hidedesc			Do not show desc
	 *	@param	int<0,1>	$hideref			Do not show ref
	 *	@return	int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "companies", "bills", "dict"));

		if ($conf->user->dir_output) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new User($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}

			$object->fetch_thirdparty();

			$dir = $conf->user->dir_output;
			$objectref = dol_sanitizeFileName($object->ref);
			if (!preg_match('/specimen/i', $objectref)) {
				$dir .= "/".$objectref;
			}
			$file = $dir."/".$objectref.".odt";

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile = basename($srctemplatepath);
				$newfiletmp = preg_replace('/\.od[ts]/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);

				$newfiletmp = $objectref . '_' . $newfiletmp;

				// Get extension (ods or odt)
				$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
				if (getDolGlobalString('MAIN_DOC_USE_TIMING')) {
					$format = getDolGlobalString('MAIN_DOC_USE_TIMING');
					if ($format == '1') {
						$format = '%Y%m%d%H%M%S';
					}
					$filename = $newfiletmp . '-' . dol_print_date(dol_now(), $format) . '.' . $newfileformat;
				} else {
					$filename = $newfiletmp . '.' . $newfileformat;
				}
				$file = $dir . '/' . $filename;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->user->dir_temp=".$conf->user->dir_temp;

				dol_mkdir($conf->user->dir_temp);
				if (!is_writable($conf->user->dir_temp)) {
					$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->user->dir_temp);
					dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
					return -1;
				}

				// If CUSTOMER contact defined on user, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				$contactobject = null;
				// Recipient name
				if (!empty($usecontact)) {
					if ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT'))) {
						$socobject = $object->contact;
					} else {
						$socobject = $object->thirdparty;
						// if we have a CUSTOMER contact and we don't use it as recipient we store the contact object for later use
						$contactobject = $object->contact;
					}
				} else {
					$socobject = $object->thirdparty;
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new Odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->user->dir_temp,
							'ZIP_PROXY'		  => getDolGlobalString('MAIN_ODF_ZIP_PROXY', 'PclZipProxy'), // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
							'DELIMITER_LEFT'  => '{',
							'DELIMITER_RIGHT' => '}'
						)
					);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_WARNING);
					return -1;
				}

				// Make substitutions into odt
				$array_user = $this->get_substitutionarray_user($object, $outputlangs);
				$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				$array_thirdparty = $this->get_substitutionarray_thirdparty($socobject, $outputlangs);
				$array_other = $this->get_substitutionarray_other($outputlangs);
				// retrieve contact information for use in object as contact_xxx tags
				$array_thirdparty_contact = array();
				if ($usecontact && is_object($contactobject)) {
					$array_thirdparty_contact = $this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact');
				}

				$tmparray = array_merge($array_user, $array_soc, $array_thirdparty, $array_other, $array_thirdparty_contact);
				complete_substitutions_array($tmparray, $outputlangs, $object);
				$object->fetch_optionals();
				// Call the ODTSubstitution hook
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// retrieve the constant to apply a ratio for image size or set the ratio to 1
				if (getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO')) {
					$ratio = floatval(getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO'));
				} else {
					$ratio = 1;
				}

				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) { // Image
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value, $ratio);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else { // Text
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_WARNING);
					}
				}

				// Replace labels translated
				$tmparray = $outputlangs->get_translations_for_substitutions();
				foreach ($tmparray as $key => $value) {
					try {
						$odfHandler->setVars($key, $value, true, 'UTF-8');
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_WARNING);
					}
				}

				// Call the beforeODTSave hook
				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (getDolGlobalString('MAIN_ODT_AS_PDF')) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_WARNING);
						return -1;
					}
				} else {
					try {
						$odfHandler->saveToDisk($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_WARNING);
						return -1;
					}
				}

				$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				dolChmod($file);

				$odfHandler = null; // Destroy object

				$this->result = array('fullpath' => $file);

				return 1; // Success
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * get substitution array for object
	 *
	 * @param CommonObject  $object         user
	 * @param Translate     $outputlangs    translation object
	 * @param string        $array_key      key for array
	 * @return array<string,int|string>     array of substitutions
	 */
	public function get_substitutionarray_object($object, $outputlangs, $array_key = 'object')
	{
		// phpcs:enable
		if (!$object instanceof User) {
			dol_syslog("Expected User object, got ".gettype($object), LOG_ERR);
			return array();
		}

		$array_other = array();
		foreach ($object as $key => $value) {
			if (!is_array($value) && !is_object($value)) {
				$array_other[$array_key.'_'.$key] = $value;
			}
		}

		return $array_other;
	}
}
