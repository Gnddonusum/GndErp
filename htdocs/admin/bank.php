<?php
/* Copyright (C) 2009       Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016  Juanjo Menent	       <jmenent@2byte.es>
 * Copyright (C) 2013-2018  Philippe Grand         <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Jean-François Ferry    <jfefe@aternatik.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/admin/bank.php
 * 		\ingroup    bank
 * 		\brief      Page to setup the bank module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "companies", "bills", "other", "banks"));

$action = GETPOST('action', 'aZ09');
$actionsave = GETPOST('save', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'bankaccount';

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if (in_array($action, array('setBANK_DISABLE_DIRECT_INPUT'))) {
	$constname = preg_replace('/^set/', '', $action);
	$constvalue = GETPOSTINT('value');
	$res = dolibarr_set_const($db, $constname, $constvalue, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

// Order display of bank account
if ($action == 'setbankorder') {
	if (dolibarr_set_const($db, "BANK_SHOW_ORDER_OPTION", GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity) > 0) {
	} else {
		dol_print_error($db);
	}
}

// Auto report last num releve on conciliate
if ($action == 'setreportlastnumreleve') {
	if (dolibarr_set_const($db, "BANK_REPORT_LAST_NUM_RELEVE", 1, 'chaine', 0, '', $conf->entity) > 0) {
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'unsetreportlastnumreleve') {
	if (dolibarr_set_const($db, "BANK_REPORT_LAST_NUM_RELEVE", 0, 'chaine', 0, '', $conf->entity) > 0) {
	} else {
		dol_print_error($db);
	}
}

// Colorize movements
if ($action == 'setbankcolorizemovement') {
	if (dolibarr_set_const($db, "BANK_COLORIZE_MOVEMENT", 1, 'chaine', 0, '', $conf->entity) > 0) {
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'unsetbankcolorizemovement') {
	if (dolibarr_set_const($db, "BANK_COLORIZE_MOVEMENT", 0, 'chaine', 0, '', $conf->entity) > 0) {
	} else {
		dol_print_error($db);
	}
}

if ($actionsave) {
	$db->begin();

	$i = 1;
	$errorsaved = 0;
	$error = 0;

	// Save colors
	while ($i <= 2) {
		$color = GETPOST('BANK_COLORIZE_MOVEMENT_COLOR'.$i, 'alpha');
		if ($color == '-1') {
			$color = '';
		}

		$res = dolibarr_set_const($db, 'BANK_COLORIZE_MOVEMENT_COLOR'.$i, $color, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$i++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		if (empty($errorsaved)) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}


if ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	if ($modele == 'sepamandate') {
		$object = new CompanyBankAccount($db);
	} else {
		$object = new Account($db);
	}
	$object->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/bank/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModeleBankAccountDoc $module';

		if ($module->write_file($object, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=bank&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
if ($action == 'set') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->BANKADDON_PDF == "$value") {
			dolibarr_del_const($db, 'BANKADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "BANKADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->BANKADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("BankSetupModule"), '', '', 0, 0, '', '', '', 'mod-admin page-bank');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BankSetupModule"), $linkback, 'title_setup');

print '<form name="bankmovementcolorconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

$head = bank_admin_prepare_head(null);
print dol_get_fiche_head($head, 'general', $langs->trans("BankSetupModule"), -1, 'account');

//Show bank account order
print load_fiche_titre($langs->trans("BankOrderShow"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="minwidth100">'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print "</tr>\n";

$bankorder = array();
$bankorder[0][0] = $langs->trans("BankOrderGlobal");
$bankorder[0][1] = $langs->trans("BankOrderGlobalDesc");
$bankorder[0][2] = 'BankCode DeskCode BankAccountNumber BankAccountNumberKey';
$bankorder[1][0] = $langs->trans("BankOrderES");
$bankorder[1][1] = $langs->trans("BankOrderESDesc");
$bankorder[1][2] = 'BankCode DeskCode BankAccountNumberKey BankAccountNumber';

$i = 0;

$nbofbank = count($bankorder);
while ($i < $nbofbank) {
	print '<tr class="oddeven">';
	print '<td>'.$bankorder[$i][0]."</td><td>\n";
	print $bankorder[$i][1];
	print '</td>';
	print '<td class="nowrap">';
	$tmparray = explode(' ', $bankorder[$i][2]);
	foreach ($tmparray as $key => $val) {
		if ($key > 0) {
			print ', ';
		}
		print $langs->trans($val);
	}
	print "</td>\n";

	if (getDolGlobalInt('BANK_SHOW_ORDER_OPTION') == $i) {
		print '<td class="center">';
		print img_picto($langs->trans("Activated"), 'on');
		print '</td>';
	} else {
		print '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setbankorder&token='.newToken().'&value='.((int) $i).'">';
		print img_picto($langs->trans("Disabled"), 'off');
		print '</a></td>';
	}
	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";
print "</div>";

print '<br><br>';


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("BankAccountModelModule"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
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
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="minwidth100">'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/bank".$valdir);

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				$filelist = array();
				while (($file = readdir($handle)) !== false) {
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							'@phan-var-force ModeleBankAccountDoc $module';

							$modulequalified = 1;
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								$modulequalified = 0;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								$modulequalified = 0;
							}

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print(empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) {
									print $module->info($langs);  // @phan-suppress-current-line PhanUndeclaredMethod
								} else {
									print $module->description;
								}
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&token='.newToken().'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if (getDolGlobalString('BANKADDON_PDF') == $name) {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								//$htmltooltip .= '<br>' . $langs->trans("PaymentMode") . ': ' . yn($module->option_modereg, 1, 1);
								//$htmltooltip .= '<br>' . $langs->trans("PaymentConditions") . ': ' . yn($module->option_condreg, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								// $htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								// $htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								//$htmltooltip .= '<br>' . $langs->trans("WatermarkOnDraftOrders") . ': ' . yn($module->option_draft_watermark, 1, 1);

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 'info');
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
								} else {
									print img_object($langs->transnoentitiesnoconv("PreviewNotAvailable"), 'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';
print '</div>';

print '<br><br>';

print load_fiche_titre($langs->trans("BankColorizeMovement"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td colspan="4">'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="75"></td>'."\n";
print "</tr>\n";

print '<tr class="oddeven"><td colspan="4">';
print $langs->trans('BankColorizeMovementDesc');
print "</td>";
// Active
if (getDolGlobalInt('BANK_COLORIZE_MOVEMENT')) {
	print '<td class="center">'."\n";
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=unsetbankcolorizemovement&token='.newToken().'">';
	print img_picto($langs->trans("Enabled"), 'switch_on');
	print '</a>';
	print '</td>';
} else {
	print '<td class="center">'."\n";
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setbankcolorizemovement&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	print "</td>";
}

print "</tr>\n";

if (getDolGlobalInt('BANK_COLORIZE_MOVEMENT')) {
	$i = 1;
	while ($i <= 2) {
		$key = $i;
		$color = 'BANK_COLORIZE_MOVEMENT_COLOR'.$key;

		print '<tr class="oddeven">';

		// Label
		print '<td colspan="4" width="180" class="nowrap">'.$langs->trans("BankColorizeMovementName".$key)."</td>";
		// Color
		print '<td class="nowrap right">';
		print $formother->selectColor((GETPOST("BANK_COLORIZE_MOVEMENT_COLOR".$key) ? GETPOST("BANK_COLORIZE_MOVEMENT_COLOR".$key) : getDolGlobalString($color)), "BANK_COLORIZE_MOVEMENT_COLOR".$key, '', 1, array(), 'right hideifnotset');
		print '</td>';
		print "</tr>";
		$i++;
	}
}
print '</table>';
print '</div>';

print '<br><br>';


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("Other"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameter").'</td>';
print "<td></td>\n";
print "</tr>\n";

// Disable direct input
print '<tr class="oddeven">';
print '<td>'.$langs->trans("BANK_DISABLE_DIRECT_INPUT").'</td>';
if (getDolGlobalString('BANK_DISABLE_DIRECT_INPUT')) {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setBANK_DISABLE_DIRECT_INPUT&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setBANK_DISABLE_DIRECT_INPUT&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

// Autofill bank statement
print '<tr class="oddeven"><td>'."\n";
print $langs->trans('AutoReportLastAccountStatement');
print '</td>';
// Active
if (getDolGlobalString('BANK_REPORT_LAST_NUM_RELEVE')) {
	print '<td class="center">'."\n";
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=unsetreportlastnumreleve&token='.newToken().'">';
	print img_picto($langs->trans("Enabled"), 'switch_on');
	print '</a>';
	print '</td>';
} else {
	print '<td class="center">'."\n";
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setreportlastnumreleve&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	print "</td>";
}
print "</tr>\n";

// Allow SEPA Mandate OnLine Sign
if (!getDolGlobalInt('SOCIETE_DISABLE_BANKACCOUNT')) {
	print '<tr class="oddeven">';
	print '<td>'.$form->textwithpicto($langs->trans("AllowOnLineSign"), $langs->trans("AllowOnLineSignDesc")).'</td>';
	print '<td class="center">';
	print ajax_constantonoff('SOCIETE_RIB_ALLOW_ONLINESIGN', array(), null, 0, 0, 0, 2, 0, 1, '', '', 'inline-block', 0, $langs->transnoentitiesnoconv("WarningOnlineSignature", "https://www.dolistore.com"));
	print '</td></tr>';
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";

// End of page
llxFooter();
$db->close();
