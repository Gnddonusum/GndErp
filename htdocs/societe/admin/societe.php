<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/societe/admin/societe.php
 *	\ingroup    company
 *	\brief      Third party module setup page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "accountancy", "companies", "other"));

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

if (!$user->admin) {
	accessforbidden();
}

$formcompany = new FormCompany($db);



/*
 * Actions
 */
$error = 0;

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'setcodeclient') {
	$result = dolibarr_set_const($db, "SOCIETE_CODECLIENT_ADDON", $value, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}

if ($action == 'setcodecompta') {
	$result = dolibarr_set_const($db, "SOCIETE_CODECOMPTA_ADDON", $value, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}

if ($action == 'updateoptions') {
	if (GETPOSTISSET('COMPANY_USE_SEARCH_TO_SELECT')) {
		$companysearch = GETPOST('activate_COMPANY_USE_SEARCH_TO_SELECT', 'alpha');
		$res = dolibarr_set_const($db, "COMPANY_USE_SEARCH_TO_SELECT", $companysearch, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	if (GETPOSTISSET('CONTACT_USE_SEARCH_TO_SELECT')) {
		$contactsearch = GETPOST('activate_CONTACT_USE_SEARCH_TO_SELECT', 'alpha');
		$res = dolibarr_set_const($db, "CONTACT_USE_SEARCH_TO_SELECT", $contactsearch, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	if (GETPOSTISSET('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT')) {
		$customertypedefault = GETPOSTINT('defaultcustomertype');
		$res = dolibarr_set_const($db, "THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT", $customertypedefault, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	if (GETPOSTISARRAY('CONTACTS_DEFAULT_ROLES')) {
		$rolessearch = GETPOST('activate_CONTACTS_DEFAULT_ROLES', 'array:aZ09');
		$res = dolibarr_set_const($db, "CONTACTS_DEFAULT_ROLES", implode(',', $rolessearch), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

// Activate a document generator module
if ($action == 'set') {
	$label = GETPOST('label', 'alpha');
	$scandir = GETPOST('scan_dir', 'alpha');

	$type = 'company';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql .= " VALUES ('".$db->escape($value)."', '".$db->escape($type)."', ".((int) $conf->entity).", ";
	$sql .= ($label ? "'".$db->escape($label)."'" : 'null').", ";
	$sql .= (!empty($scandir) ? "'".$db->escape($scandir)."'" : "null");
	$sql .= ")";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
}

// Disable a document generator module
if ($action == 'del') {
	$type = 'company';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql .= " WHERE nom='".$db->escape($value)."' AND type='".$db->escape($type)."' AND entity=".((int) $conf->entity);
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
}

// Define default generator
if ($action == 'setdoc') {
	$label = GETPOST('label', 'alpha');
	$scandir = GETPOST('scan_dir', 'alpha');

	$db->begin();

	dolibarr_set_const($db, "COMPANY_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity);

	// On active le modele
	$type = 'company';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del .= " WHERE nom = '".$db->escape(GETPOST('value', 'alpha'))."'";
	$sql_del .= " AND type = '".$db->escape($type)."'";
	$sql_del .= " AND entity = ".((int) $conf->entity);
	dol_syslog("societe.php ".$sql);
	$result1 = $db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql .= " VALUES ('".$db->escape($value)."', '".$db->escape($type)."', ".((int) $conf->entity).", ";
	$sql .= ($label ? "'".$db->escape($label)."'" : 'null').", ";
	$sql .= (!empty($scandir) ? "'".$db->escape($scandir)."'" : "null");
	$sql .= ")";
	dol_syslog("societe.php", LOG_DEBUG);
	$result2 = $db->query($sql);
	if ($result1 && $result2) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

//Activate Set accountancy code customer invoice mandatory
if ($action == "setaccountancycodecustomerinvoicemandatory") {
	$setaccountancycodecustomerinvoicemandatory = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_ACCOUNTANCY_CODE_CUSTOMER_INVOICE_MANDATORY", $setaccountancycodecustomerinvoicemandatory, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate Set ref in list
if ($action == "setaddrefinlist") {
	$setaddrefinlist = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_ADD_REF_IN_LIST", $setaddrefinlist, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate Set vat in list
if ($action == "setvatinlist") {
	$setvatinlist = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_SHOW_VAT_IN_LIST", $setvatinlist, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate Set address in list
if ($action == "setaddadressinlist") {
	$val = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "COMPANY_SHOW_ADDRESS_SELECTLIST", $val, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate Set email phone town in contact list
if ($action == "setaddemailphonetownincontactlist") {
	$val = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST", $val, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate Ask For Preferred Shipping Method
if ($action == "setaskforshippingmet") {
	$setaskforshippingmet = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_ASK_FOR_SHIPPING_METHOD", $setaskforshippingmet, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Activate "Disable prospect/customer type"
if ($action == "setdisableprospectcustomer") {
	$setdisableprospectcustomer = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_DISABLE_PROSPECTSCUSTOMERS", $setdisableprospectcustomer, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

//Activate ProfId unique
if ($action == 'setprofid') {
	$status = GETPOST('status', 'alpha');

	$idprof = "SOCIETE_".$value."_UNIQUE";
	$result = dolibarr_set_const($db, $idprof, $status, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}

//Activate ProfId mandatory
if ($action == 'setprofidmandatory') {
	$status = GETPOST('status', 'alpha');

	$idprof = "SOCIETE_".$value."_MANDATORY";
	$result = dolibarr_set_const($db, $idprof, $status, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}

//Activate ProfId invoice mandatory
if ($action == 'setprofidinvoicemandatory' || $action == 'setprofidinvoicemandatoryeeconly') {
	$status = GETPOST('status', 'alpha');
	if ($status == '1' && $action == 'setprofidinvoicemandatoryeeconly') {
		$status = 'eeconly';
	}
	$idprof = "SOCIETE_".$value."_INVOICE_MANDATORY";
	$result = dolibarr_set_const($db, $idprof, $status, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}

//Set hide closed customer into combox or select
if ($action == 'sethideinactivethirdparty') {
	$status = GETPOST('status', 'alpha');

	$result = dolibarr_set_const($db, "COMPANY_HIDE_INACTIVE_IN_COMBOBOX", $status, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
}
if ($action == 'setonsearchandlistgooncustomerorsuppliercard') {
	$setonsearchandlistgooncustomerorsuppliercard = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD", $setonsearchandlistgooncustomerorsuppliercard, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * 	View
 */

clearstatcache();

$form = new Form($db);

$help_url = 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers|ES:Configuración_del_módulo_terceros';
llxHeader('', $langs->trans("CompanySetup"), $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CompanySetup"), $linkback, 'title_setup');


$head = societe_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("ThirdParties"), -1, 'company');

$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
foreach ($conf->modules_parts['models'] as $mo) {
	$dirsociete[] = $mo.'core/modules/societe/'; //Add more models
}

// Module to manage customer/supplier code

print load_fiche_titre($langs->trans("CompanyCodeChecker"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td class="center" width="80">'.$langs->trans("Status").'</td>';
print '  <td class="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$arrayofmodules = array();

foreach ($dirsociete as $dirroot) {
	$dir = dol_buildpath($dirroot, 0);

	$handle = @opendir($dir);
	if (is_resource($handle)) {
		// Loop on each module find in opened directory
		while (($file = readdir($handle)) !== false) {
			if (substr($file, 0, 15) == 'mod_codeclient_' && substr($file, -3) == 'php') {
				$file = substr($file, 0, dol_strlen($file) - 4);

				try {
					dol_include_once($dirroot.$file.'.php');
				} catch (Exception $e) {
					dol_syslog($e->getMessage(), LOG_ERR);
				}

				/** @var ModeleThirdPartyCode $modCodeTiers */
				$modCodeTiers = new $file($db);
				'@phan-var-force ModeleThirdPartyCode $modCodeTiers';

				// Show modules according to features level
				if ($modCodeTiers->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
					continue;
				}
				if ($modCodeTiers->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
					continue;
				}

				$arrayofmodules[$file] = $modCodeTiers;
			}
		}
		closedir($handle);
	}
}

$arrayofmodules = dol_sort_array($arrayofmodules, 'position');
// '@phan-var-force array<string,ModeleThirdPartyCode> $arrayofmodules';  // Repeat type because of dol_sort_array

foreach ($arrayofmodules as $file => $modCodeTiers) {
	print '<tr class="oddeven">'."\n";
	print '<td width="140">'.$modCodeTiers->name.'</td>'."\n";
	print '<td>'.$modCodeTiers->info($langs).'</td>'."\n";
	print '<td class="nowrap">'.$modCodeTiers->getExample($langs).'</td>'."\n";

	if ($conf->global->SOCIETE_CODECLIENT_ADDON == (string) $file) {
		print '<td class="center">'."\n";
		print img_picto($langs->trans("Activated"), 'switch_on');
		print "</td>\n";
	} else {
		$disabled = (isModEnabled('multicompany') && ((is_object($mc) && !empty($mc->sharings['referent'])) && ($mc->sharings['referent'] != $conf->entity)));
		print '<td class="center">';
		if (!$disabled) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setcodeclient&token='.newToken().'&value='.urlencode($file).'">';
		}
		print img_picto($langs->trans("Disabled"), 'switch_off');
		if (!$disabled) {
			print '</a>';
		}
		print '</td>';
	}

	print '<td class="center">';
	$s = $modCodeTiers->getToolTip($langs, null, -1);
	print $form->textwithpicto('', $s, 1);
	print '</td>';

	print '</tr>';
}
print '</table>';
print '</div>';

print "<br>";


// Select accountancy code numbering module

print load_fiche_titre($langs->trans("AccountCodeManager"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td class="center" width="80">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$arrayofmodules = array();

foreach ($dirsociete as $dirroot) {
	$dir = dol_buildpath($dirroot, 0);

	$handle = @opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (substr($file, 0, 15) == 'mod_codecompta_' && substr($file, -3) == 'php') {
				$file = substr($file, 0, dol_strlen($file) - 4);

				try {
					dol_include_once($dirroot.$file.'.php');
				} catch (Exception $e) {
					dol_syslog($e->getMessage(), LOG_ERR);
				}

				$modCodeCompta = new $file();
				'@phan-var-force ModeleAccountancyCode $modCodeTiers';

				$arrayofmodules[$file] = $modCodeCompta;
			}
		}
		closedir($handle);
	}
}

$arrayofmodules = dol_sort_array($arrayofmodules, 'position');
'@phan-var-force array<string,ModeleAccountancyCode> $arrayofmodules';


foreach ($arrayofmodules as $file => $modCodeCompta) {
	print '<tr class="oddeven">';
	print '<td>'.$modCodeCompta->name."</td><td>\n";
	print $modCodeCompta->info($langs);
	print '</td>';
	print '<td class="nowrap">'.$modCodeCompta->getExample($langs)."</td>\n";

	if ($conf->global->SOCIETE_CODECOMPTA_ADDON == "$file") {
		print '<td class="center">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</td>';
	} else {
		print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setcodecompta&token='.newToken().'&value='.urlencode($file).'">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '<td class="center">';
	$s = $modCodeCompta->getToolTip($langs, null, -1);
	print $form->textwithpicto('', $s, 1);
	print '</td>';
	print "</tr>\n";
}
print "</table>\n";
print '</div>';


/*
 *  Document templates generators
 */
print '<br>';
print load_fiche_titre($langs->trans("ModelModules"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = 'company'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		if (is_array($array)) {
			array_push($def, $array[0]);
		}
		$i++;
	}
} else {
	dol_print_error($db);
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="80">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="60">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

foreach ($dirsociete as $dirroot) {
	$dir = dol_buildpath($dirroot.'doc/', 0);

	$handle = @opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (preg_match('/\.modules\.php$/i', $file)) {
				$name = substr($file, 4, dol_strlen($file) - 16);
				$classname = substr($file, 0, dol_strlen($file) - 12);

				try {
					dol_include_once($dirroot.'doc/'.$file);
				} catch (Exception $e) {
					dol_syslog($e->getMessage(), LOG_ERR);
				}

				$module = new $classname($db);
				'@phan-var-force ModeleThirdPartyDoc $module';

				$modulequalified = 1;
				if (!empty($module->version)) {
					if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
						$modulequalified = 0;
					} elseif ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
						$modulequalified = 0;
					}
				}

				if ($modulequalified) {
					print '<tr class="oddeven"><td width="100">';
					print dol_escape_htmltag($module->name);
					print "</td><td>\n";
					if (method_exists($module, 'info')) {
						print $module->info($langs);  // @phan-suppress-current-line PhanUndeclaredMethod
					} else {
						print $module->description;
					}
					print '</td>';

					// Activate / Disable
					if (in_array($name, $def)) {
						print '<td class="center">'."\n";
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&token='.newToken().'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">';
						print img_picto($langs->trans("Enabled"), 'switch_on');
						print '</a>';
						print "</td>";
					} else {
						if (versioncompare($module->phpmin, versionphparray()) > 0) {
							print '<td class="center">'."\n";
							print img_picto(dol_escape_htmltag($langs->trans("ErrorModuleRequirePHPVersion", implode('.', $module->phpmin))), 'switch_off', 'class="opacitymedium"');
							print "</td>";
						} else {
							print '<td class="center">'."\n";
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&value='.urlencode($name).'&token='.newToken().'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							print "</td>";
						}
					}

					// Info
					$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
					$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
					if ($module->type == 'pdf') {
						$htmltooltip .= '<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
					}
					$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
					$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraft").': '.yn((isset($module->option_draft_watermark) ? $module->option_draft_watermark : ''), 1, 1);

					print '<td class="center nowrap">';
					print $form->textwithpicto('', $htmltooltip, 1, 'info');
					print '</td>';

					// Preview
					print '<td class="center nowrap">';
					if ($module->type == 'pdf') {
						$linkspec = '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=specimen&token='.newToken().'&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
					} else {
						$linkspec = img_object($langs->transnoentitiesnoconv("PreviewNotAvailable"), 'generic');
					}
					print $linkspec;
					print '</td>';

					print "</tr>\n";
				}
			}
		}
		closedir($handle);
	}
}
print '</table>';
print '</div>';

print '<br>';

//IDProf
print load_fiche_titre($langs->trans("CompanyIdProfChecker"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("MustBeUnique").'</td>';
print '<td class="center">'.$langs->trans("MustBeMandatory").'</td>';
print '<td class="center">'.$langs->trans("MustBeInvoiceMandatory").'</td>';
print "</tr>\n";

$profid = array('IDPROF1' => array(), 'IDPROF2' => array(), 'IDPROF3' => array(), 'IDPROF4' => array(), 'IDPROF5' => array(),'IDPROF6' => array(), 'EMAIL' => array());
$profid['IDPROF1'][0] = $langs->trans("ProfId1");
$profid['IDPROF1'][1] = $langs->transcountry('ProfId1', $mysoc->country_code);
$profid['IDPROF2'][0] = $langs->trans("ProfId2");
$profid['IDPROF2'][1] = $langs->transcountry('ProfId2', $mysoc->country_code);
$profid['IDPROF3'][0] = $langs->trans("ProfId3");
$profid['IDPROF3'][1] = $langs->transcountry('ProfId3', $mysoc->country_code);
$profid['IDPROF4'][0] = $langs->trans("ProfId4");
$profid['IDPROF4'][1] = $langs->transcountry('ProfId4', $mysoc->country_code);
$profid['IDPROF5'][0] = $langs->trans("ProfId5");
$profid['IDPROF5'][1] = $langs->transcountry('ProfId5', $mysoc->country_code);
$profid['IDPROF6'][0] = $langs->trans("ProfId6");
$profid['IDPROF6'][1] = $langs->transcountry('ProfId6', $mysoc->country_code);
$profid['EMAIL'][0] = $langs->trans("EMail");
$profid['EMAIL'][1] = $langs->trans('Email');
if (isModEnabled('accounting')) {
	$profid['ACCOUNTANCY_CODE_CUSTOMER'] = array();
	$profid['ACCOUNTANCY_CODE_CUSTOMER'][0] = $langs->trans("CustomerAccountancyCodeShort");
	$profid['ACCOUNTANCY_CODE_CUSTOMER'][1] = $langs->trans('CustomerAccountancyCodeShort');
	$profid['ACCOUNTANCY_CODE_SUPPLIER'] = array();
	$profid['ACCOUNTANCY_CODE_SUPPLIER'][0] = $langs->trans("SupplierAccountancyCodeShort");
	$profid['ACCOUNTANCY_CODE_SUPPLIER'][1] = $langs->trans('SupplierAccountancyCodeShort');
}

$nbofloop = count($profid);
foreach ($profid as $key => $val) {
	if ($profid[$key][1] != '-') {
		print '<tr class="oddeven">';
		print '<td>'.$profid[$key][0]."</td><td>\n";
		print $profid[$key][1];
		print '</td>';

		$idprof_unique = 'SOCIETE_'.$key.'_UNIQUE';
		$idprof_mandatory = 'SOCIETE_'.$key.'_MANDATORY';
		$idprof_invoice_mandatory = 'SOCIETE_'.$key.'_INVOICE_MANDATORY';

		$verif = !empty($conf->global->$idprof_unique);
		$mandatory = !empty($conf->global->$idprof_mandatory);
		$invoice_mandatory = !empty($conf->global->$idprof_invoice_mandatory);

		if ($verif) {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofid&token='.newToken().'&value='.$key.'&status=0">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a></td>';
		} else {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofid&token='.newToken().'&value='.$key.'&status=1">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a></td>';
		}

		if ($mandatory) {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&token='.newToken().'&value='.$key.'&status=0">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a></td>';
		} else {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&token='.newToken().'&value='.$key.'&status=1">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a></td>';
		}

		if ($invoice_mandatory) {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&token='.newToken().'&value='.$key.'&status=0">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a></td>';
		} else {
			print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&token='.newToken().'&value='.$key.'&status=1">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a></td>';
		}

		print "</tr>\n";
	}
}

// VAT ID
print '<tr class="oddeven">';
print '<td colspan="2">'.$langs->trans('VATIntra')."</td>\n";
$key = 'VAT_INTRA';
if (getDolGlobalString('SOCIETE_VAT_INTRA_UNIQUE')) {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofid&token='.newToken().'&value='.$key.'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', 'class="valignmiddle"');
	print '</a></td>';
} else {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofid&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off', 'class="valignmiddle"');
	print '</a></td>';
}
if (getDolGlobalString('SOCIETE_VAT_INTRA_MANDATORY')) {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&token='.newToken().'&value='.$key.'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', 'class="valignmiddle"');
	print '</a></td>';
} else {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off', 'class="valignmiddle"');
	print '</a></td>';
}
print '<td class="center">';
if (getDolGlobalString('SOCIETE_VAT_INTRA_INVOICE_MANDATORY') == 'eeconly') {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatoryeeconly&token='.newToken().'&value='.$key.'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("SaleEEC").'<br>';
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Activated"), 'switch_off', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("AnySale");
} elseif (getDolGlobalString('SOCIETE_VAT_INTRA_INVOICE_MANDATORY')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatoryeeconly&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Activated"), 'switch_off', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("SaleEEC").'<br>';
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&token='.newToken().'&value='.$key.'&status=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("AnySale");
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatoryeeconly&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("SaleEEC").'<br>';
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&token='.newToken().'&value='.$key.'&status=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off', 'class="valignmiddle paddingrightonly"');
	print '</a>';
	print $langs->trans("AnySale");
}
print '</td>';
print "</tr>\n";

print "</table>\n";
print '</div>';

print "<br>\n";

print load_fiche_titre($langs->trans("Other"), '', '');

// Autres options
$form = new Form($db);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="action" value="updateoptions">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td class="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

// Utilisation formulaire Ajax sur choix societe

print '<tr class="oddeven">';
print '<td width="80%">'.$form->textwithpicto($langs->trans("DelaiedFullListToSelectCompany"), $langs->trans('UseSearchToSelectCompanyTooltip'), 1).' </td>';
if (!$conf->use_javascript_ajax) {
	print '<td class="nowrap right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td width="60" class="right">';
	$arrval = array('0' => $langs->trans("No"),
	'1' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 1).'</span>',
	'2' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 2).'</span>',
	'3' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 3).'</span>',
	);
	print $form->selectarray("activate_COMPANY_USE_SEARCH_TO_SELECT", $arrval, getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth400');
	print '</td><td class="right">';
	print '<input type="submit" class="button small reposition" name="COMPANY_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';


print '<tr class="oddeven">';
print '<td width="80%">'.$form->textwithpicto($langs->trans("DelaiedFullListToSelectContact"), $langs->trans('UseSearchToSelectContactTooltip'), 1).'</td>';
if (!$conf->use_javascript_ajax) {
	print '<td class="nowrap right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td width="60" class="right">';
	$arrval = array('0' => $langs->trans("No"),
	'1' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 1).'</span>',
	'2' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 2).'</span>',
	'3' => $langs->trans("Yes").' - <span class="opacitymedium">'.$langs->trans("NumberOfKeyToSearch", 3).'</span>',
	);
	print $form->selectarray("activate_CONTACT_USE_SEARCH_TO_SELECT", $arrval, getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth400');
	print '</td><td class="right">';
	print '<input type="submit" class="button small reposition" name="CONTACT_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';



print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("AddRefInList").'</td>';
print '<td>&nbsp;</td>';
print '<td class="center">';
if (getDolGlobalString('SOCIETE_ADD_REF_IN_LIST')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddrefinlist&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddrefinlist&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("AddVatInList").'</td>';
print '<td>&nbsp;</td>';
print '<td class="center">';
if (getDolGlobalString('SOCIETE_SHOW_VAT_IN_LIST')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setvatinlist&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setvatinlist&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("AddAdressInList").'</td>';
print '<td>&nbsp;</td>';
print '<td class="center">';
if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddadressinlist&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddadressinlist&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("AddEmailPhoneTownInContactList").'</td>';
print '<td>&nbsp;</td>';
print '<td class="center">';
if (getDolGlobalString('CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddemailphonetownincontactlist&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaddemailphonetownincontactlist&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</a></td>';
print '</tr>';

if (isModEnabled("shipping")) {
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') > 0) {	// Visible on experimental only because seems to not be implemented everywhere (only on proposal)
		print '<tr class="oddeven">';
		print '<td width="80%">'.$langs->trans("AskForPreferredShippingMethod").'</td>';
		print '<td>&nbsp;</td>';
		print '<td class="center">';
		if (getDolGlobalString('SOCIETE_ASK_FOR_SHIPPING_METHOD')) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaskforshippingmet&token='.newToken().'&value=0">';
			print img_picto($langs->trans("Activated"), 'switch_on');
		} else {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setaskforshippingmet&token='.newToken().'&value=1">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
		}
		print '</a></td>';
		print '</tr>';
	}
}

// Disable Prospect/Customer thirdparty type
print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("DisableProspectCustomerType").'</td>';
print '<td>&nbsp;</td>';
print '<td class="center">';
if (getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdisableprospectcustomer&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdisableprospectcustomer&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
}
print '</a></td>';
print '</tr>';

if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
	// Default Prospect/Customer thirdparty type on customer création
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("DefaultCustomerType").'</td>';
	print '<td>';
	print $formcompany->selectProspectCustomerType(getDolGlobalInt('THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT'), 'defaultcustomertype', 'defaultcustomertype', 'admin');
	print '</td>';
	print '<td class="center">';
	print '<input type="submit" class="button small reposition" name="THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT" value="'.$langs->trans("Modify").'">';
	print '</td>';
	print '</tr>';
}

if (getDolGlobalString('THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION')) {
	print '<tr class="oddeven">';
	print '<td width="80%">'.$langs->trans('ContactsDefaultRoles').'</td>';
	if (!$conf->use_javascript_ajax) {
		print '<td class="nowrap right" colspan="2">';
		print $langs->trans("NotAvailableWhenAjaxDisabled");
		print "</td>";
	} else {
		print '<td width="60" class="right">';
		$contact = new Contact($db);	// InfraS add
		$contactType = $contact->listeTypeContacts('external', 0, 1);
		$selected = explode(',', getDolGlobalString('CONTACTS_DEFAULT_ROLES'));
		print $form->multiselectarray('activate_CONTACTS_DEFAULT_ROLES', $contactType, $selected, 0, 0, 'minwidth75imp');
		print '</td><td class="right">';
		print '<input type="submit" class="button small eposition" name="CONTACTS_DEFAULT_ROLES" value="'.$langs->trans("Modify").'">';
		print "</td>";
	}
	print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
