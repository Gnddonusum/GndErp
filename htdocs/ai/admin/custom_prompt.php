<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Alice Adminson				<aadminson@example.com>
 * Copyright (C) 2024-2025  Frédéric France				<frederic.france@free.fr>
 * Coryright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/ai/admin/custom_prompt.php
 * \ingroup ai
 * \brief   Ai other custom page.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/ai/lib/ai.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formai.class.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "website", "other"));

$arrayofaifeatures = getListOfAIFeatures();
$arrayofai = getListOfAIServices();

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$functioncode = GETPOST('functioncode', 'alpha');
$pre_prompt = GETPOST('prePrompt');
$post_prompt = GETPOST('postPrompt');
$blacklists = GETPOST('blacklists');
$test = GETPOST('test');
$key = (string) GETPOST('key', 'alpha');

if (empty($action)) {
	$action = 'edit';
}

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);
$aiservice = getDolGlobalString('AI_API_SERVICE', 'chatgpt');

// Setup conf for AI model
$formSetup->formHiddenInputs['action'] = "updatefeaturemodel";
foreach ($arrayofaifeatures as $featurekey => $feature) {
	$newkey = $featurekey;
	if (preg_match('/^text/', $featurekey)) {
		$newkey = 'textgeneration';
	}
	$item = $formSetup->newItem('AI_API_'.strtoupper($aiservice).'_MODEL_'.$feature["function"]);	// Name of constant must end with _KEY so it is encrypted when saved into database.
	if ($arrayofai[$aiservice][$newkey] != 'na') {
		$item->nameText = $langs->trans("AI_API_MODEL_".$feature["function"]).' <span class="opacitymedium">('.$langs->trans("Default").' = '.$arrayofai[$aiservice][$newkey].')</span>';
	} else {
		$item->nameText = $langs->trans("AI_API_MODEL_".$feature["function"]).' <span class="opacitymedium">('.$langs->trans("None").')</span>';
	}
	$item->cssClass = 'minwidth500 input';
}

$setupnotempty += count($formSetup->items);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

// get all configs in const AI

$currentConfigurationsJson = getDolGlobalString('AI_CONFIGURATIONS_PROMPT');
$currentConfigurations = json_decode($currentConfigurationsJson, true);

if ($action == 'updatefeaturemodel' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
	$action = 'edit';
}

if ($action == 'update' && $cancel) {
	$action = 'edit';
}

if ($action == 'update' && !$cancel && !$test) {
	$error = 0;
	if (empty($functioncode)) {
		$error++;
		setEventMessages($langs->trans('ErrorInputRequired'), null, 'errors');
	}
	if (!is_array($currentConfigurations)) {
		$currentConfigurations = [];
	}

	$blacklistArray = array_filter(array_map('trim', explode(',', $blacklists)));

	if (empty($functioncode) || (empty($pre_prompt) && empty($post_prompt) && empty($blacklists))) {
		if (isset($currentConfigurations[$functioncode])) {
			unset($currentConfigurations[$functioncode]);
		}
	} else {
		$currentConfigurations[$functioncode] = [
			'prePrompt' => $pre_prompt,
			'postPrompt' => $post_prompt,
			'blacklists' => $blacklistArray,
		];
	}

	$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
	$result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
	if (!$error) {
		if ($result) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
		}
	}

	$action = 'edit';
}

// Update entry
if ($action == 'updatePrompts' && !$test) {
	$blacklistArray = array_filter(array_map('trim', explode(',', $blacklists)));

	$currentConfigurations[$key] = [
		'prePrompt' => $pre_prompt,
		'postPrompt' => $post_prompt,
		'blacklists' => $blacklistArray,
	];

	$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
	$result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
	if (!$error) {
		$action = 'edit';
		if ($result) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
		}
	}
}

// Test entry
if ($action == 'updatePrompts' && $test) {
	$action = 'edit';
}

// Delete entry
if ($action == 'confirm_deleteproperty' && GETPOST('confirm') == 'yes') {
	if (isset($currentConfigurations[$key])) {
		unset($currentConfigurations[$key]);

		$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
		$res = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
		if ($res) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("NoRecordDeleted"), null, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formai = new FormAI($db);

$help_url = '';
$title = "AiSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-ai page-admin_custom_prompt');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'custom', $langs->trans($title), -1, "ai");

$newcardbutton = dolGetButtonTitle($langs->trans('NewCustomPrompt'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=create', '', 1);
/*
$newbutton = '<a href="'.$_SERVER["PHP_SELF"].'?action=create" title="'.$langs->trans("NewCustomPrompt").'">';
$newbutton .= img_picto('', 'add');
$newbutton .= '</a>';
*/

print load_fiche_titre($langs->trans("AIPromptForFeatures", $arrayofai[$aiservice]['label']), $newcardbutton, '');


if ($action == 'deleteproperty') {
	$formconfirm = $form->formconfirm(
		$_SERVER["PHP_SELF"].'?key='.urlencode(GETPOST('key', 'alpha')),
		$langs->trans('Delete'),
		$langs->trans('ConfirmDeleteSetup', GETPOST('key', 'alpha')),
		'confirm_deleteproperty',
		'',
		0,
		1
	);
	print $formconfirm;
}

print '<br>';

if ($action == 'create') {
	$out = '<div class="addcustomprompt">';

	$out .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	$out .= '<input type="hidden" name="token" value="'.newToken().'">';
	$out .= '<input type="hidden" name="action" value="update">';


	$out .= '<table class="noborder centpercent">';
	$out .= '<thead>';
	$out .= '<tr class="liste_titre">';
	$out .= '<td>'.$langs->trans('NewCustomPrompt').'</td>';
	$out .= '<td></td>';
	$out .= '</tr>';
	$out .= '</thead>';

	$out .= '<tbody>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title titlefield">';
	$out .= '<span id="module" class="spanforparamtooltip">'.$langs->trans("Feature").'</span>';
	$out .= '</td>';
	$out .= '<td>';
	// Combo list of AI features
	$out .= '<select name="functioncode" id="functioncode" class="flat minwidth500">';
	$out .= '<option>&nbsp;</option>';
	foreach ($arrayofaifeatures as $featurekey => $feature) {
		$labelhtml = $langs->trans($arrayofaifeatures[$featurekey]['label']).($arrayofaifeatures[$featurekey]['status'] == 'notused' ? ' <span class="opacitymedium">('.$langs->trans("NotYetAvailable").')</span>' : "");
		$labeltext = $langs->trans($arrayofaifeatures[$featurekey]['label']);
		$out .= '<option value="'.dol_escape_js($featurekey).'" data-html="'.dol_escape_htmltag($labelhtml).'">'.dol_escape_htmltag($labeltext).'</option>';
	}
	$out .= '</select>';
	$out .= ajax_combobox("functioncode");
	$out .= '<script type="text/javascript">
    	jQuery(document).ready(function() {
			jQuery("#functioncode").on("change", function() {
				console.log("We change value of ai function");
 				var changedValue = $(this).val();
				console.log(changedValue);
				var arrayplaceholder = {';
	foreach ($arrayofaifeatures as $featurekey => $feature) {
		$out .= dol_escape_js($featurekey).': \''.dol_escape_js(empty($feature['placeholder']) ? '' : $feature['placeholder']).'\',';
	}
	$out .= '}
				jQuery("#prePromptInput'.dol_escape_js($key).'").val(arrayplaceholder[changedValue]);
			});
		});
		</script>
	';

	$out .= '</td>';
	$out .= '</tr>';

	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="prePrompt" class="spanforparamtooltip">';
	$out .= $form->textwithpicto($langs->trans("Pre-Prompt"), $langs->trans("Pre-PromptHelp"));
	$out .= '</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="prePromptInput'.$key.'" name="prePrompt" rows="2"></textarea>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="postPrompt" class="spanforparamtooltip">';
	$out .= $form->textwithpicto($langs->trans("Post-Prompt"), $langs->trans("Post-PromptHelp"));
	$out .= '</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="postPromptInput" name="postPrompt" rows="2"></textarea>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="blacklists" class="spanforparamtooltip">';
	$out .= $form->textwithpicto($langs->trans("BlackListWords"), $langs->trans("BlackListWordsAIHelp").'.<br>'.$langs->trans("BlackListWordsHelp"));
	$out .= '</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<input type="text" class="flat minwidth500 quatrevingtpercent" id="blacklistsInput" name="blacklists">';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '</tbody>';
	$out .= '</table>';

	$out .= $form->buttonsSaveCancel("Add", "");
	$out .= '</form>';

	$out .= '<br><br><br>';
	$out .= '</div>';

	print $out;
}


if ($action == 'edit' || $action == 'create' || $action == 'deleteproperty') {
	$out = '';

	if (!empty($currentConfigurations)) {
		foreach ($currentConfigurations as $confkey => $config) {
			if (!empty($confkey) && !preg_match('/^[a-z]+$/i', $confkey)) {	// Ignore empty saved setup
				continue;
			}

			$out .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';
			$out .= '<input type="hidden" name="key" value="'.$confkey.'" />';
			$out .= '<input type="hidden" name="action" value="updatePrompts">';
			$out .= '<input type="hidden" name="page_y" value="">';

			$out .= '<table class="noborder centpercent">';
			$out .= '<thead>';
			$out .= '<tr class="liste_titre">';
			$out .= '<td class="titlefield">'.$arrayofaifeatures[$confkey]['picto'].' '.$langs->trans($arrayofaifeatures[$confkey]['label']);
			$out .= '<a class="deletefielda reposition marginleftonly right" href="'.$_SERVER["PHP_SELF"].'?action=deleteproperty&token='.newToken().'&key='.urlencode($confkey).'">'.img_delete().'</a>';
			$out .= '</td>';
			$out .= '<td></td>';
			$out .= '</tr>';
			$out .= '</thead>';
			$out .= '<tbody>';

			$out .= '<tr class="oddeven">';
			$out .= '<td class="col-setup-title">';
			$out .= '<span id="prePrompt" class="spanforparamtooltip">'.$langs->trans("Pre-Prompt").'</span>';
			$out .= '</td>';
			$out .= '<td>';
			$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="prePromptInput_'.$confkey.'" name="prePrompt" rows="2">'.$config['prePrompt'].'</textarea>';
			$out .= '</td>';
			$out .= '</tr>';

			$out .= '<tr class="oddeven">';
			$out .= '<td class="col-setup-title">';
			$out .= '<span id="postPrompt" class="spanforparamtooltip">'.$langs->trans("Post-Prompt").'</span>';
			$out .= '</td>';
			$out .= '<td>';
			$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="postPromptInput_'.$confkey.'" name="postPrompt" rows="2">'.$config['postPrompt'].'</textarea>';
			$out .= '</td>';
			$out .= '</tr>';

			$out .= '<tr id="fichetwothirdright-'.$confkey.'" class="oddeven">';
			$out .= '<td>'.$form->textwithpicto($langs->trans("BlackListWords"), $langs->trans("BlackListWordsHelp")).'</td>';
			$out .= '<td>';
			$out .= '<input type="text" class="flat minwidth500 quatrevingtpercent" id="blacklist_'.$confkey.'" name="blacklists" value="'.(isset($config['blacklists']) ? implode(', ', (array) $config['blacklists']) : '').'">';
			$out .= '</td>';
			$out .= '</tr>';

			$out .= '<tr>';
			$out .= '<td>'.$langs->trans("Test").'</td>';
			$out .= '<td>';

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->withaiprompt = 'html';		// set format

			$showlinktoai = $confkey;		// 'textgenerationemail', 'textgenerationwebpage', 'imagegeneration', ...
			$showlinktoailabel = $langs->trans("ToTest");
			$htmlname = $confkey;
			$onlyenhancements = $confkey;
			$showlinktolayout = 0;

			// Fill $out
			include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';

			$out .= '<div id="'.$htmlname.'"></div>';

			$out .= '</td>';
			$out .= '</tr>';

			$out .= '</tbody>';
			$out .= '</table>';

			$out .= '<center><input type="submit" class="button small submitBtn reposition" name="modify" data-index="'.$confkey.'" value="'.dol_escape_htmltag($langs->trans("Save")).'"/></center>';

			$out .= '</form>';

			$out .= '<br><br>';
		}
	}

	print $out;

	print '<br>';
}


if ($action == 'edit' || $action == 'create' || $action == 'deleteproperty') {
	print load_fiche_titre($langs->trans("AIModelForFeature", $arrayofai[$aiservice]['label']), '', '');
	print $formSetup->generateOutput(true);
}


if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
