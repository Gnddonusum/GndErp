<?php
/* Copyright (C) 2008-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/user/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array('agenda', 'admin', 'other'));

$def = array();
$actiontest = GETPOST('test', 'alpha');
$actionsave = GETPOST('save', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'useragenda'; // To manage different context of search

if (!getDolGlobalString('AGENDA_EXT_NB')) {
	$conf->global->AGENDA_EXT_NB = 5;
}
$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB');

// List of available colors
$colorlist = array('BECEDD', 'DDBECE', 'BFDDBE', 'F598B4', 'F68654', 'CBF654', 'A4A4A5');

// Security check
$id = GETPOSTINT('id');

if (!isset($id) || empty($id)) {
	accessforbidden();
}

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->loadRights();

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('usercard', 'useragenda', 'globalcard'));

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// If user is not user that read and no permission to read other users, we stop
if (($object->id != $user->id) && (!$user->hasRight('user', 'user', 'lire'))) {
	accessforbidden();
}

/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($actionsave) {
		$db->begin();

		$errorsaved = 0;
		$error = 0;
		$tabparam = array();

		// Save agendas
		$i = 1;
		while ($i <= $MAXAGENDA) {
			$name = trim(GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$i, 'alpha'));
			$src = trim(GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$i, 'alpha'));
			$offsettz = trim(GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$i, 'alpha'));
			$color = trim(GETPOST('AGENDA_EXT_COLOR_'.$id.'_'.$i, 'alpha'));
			if ($color == '-1') {
				$color = '';
			}
			$enabled = trim(GETPOST('AGENDA_EXT_ENABLED_'.$id.'_'.$i, 'alpha'));

			if (!empty($src) && !dol_is_url($src)) {
				setEventMessages($langs->trans("ErrorParamMustBeAnUrl"), null, 'errors');
				$error++;
				$errorsaved++;
				break;
			}

			$tabparam['AGENDA_EXT_NAME_'.$id.'_'.$i] = $name;
			$tabparam['AGENDA_EXT_SRC_'.$id.'_'.$i] = $src;
			$tabparam['AGENDA_EXT_OFFSETTZ_'.$id.'_'.$i] = $offsettz;
			$tabparam['AGENDA_EXT_COLOR_'.$id.'_'.$i] = $color;
			$tabparam['AGENDA_EXT_ENABLED_'.$id.'_'.$i] = $enabled;

			$i++;
		}

		if (!$error) {
			$result = dol_set_user_param($db, $conf, $object, $tabparam);
			if (!($result > 0)) {
				$error++;
			}
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
}

/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formother = new FormOther($db);

$arrayofjs = array();
$arrayofcss = array();

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('ExtSites');
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-user page-agenda_extsites');


print '<form name="extsitesconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$head = user_prepare_head($object);

print dol_get_fiche_head($head, 'extsites', $langs->trans("User"), -1, 'user');

$linkback = '';

if ($user->hasRight('user', 'user', 'lire') || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Login
print '<tr><td id="anchorforperms" class="titlefield">'.$langs->trans("Login").'</td>';
if (!empty($object->ldap_sid) && $object->status == 0) {
	print '<td class="error">';
	print $langs->trans("LoginAccountDisableInDolibarr");
	print '</td>';
} else {
	print '<td>';
	$addadmin = '';
	if (property_exists($object, 'admin')) {
		if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
			$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft valignmiddle"');
		} elseif (!empty($object->admin)) {
			$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft valignmiddle"');
		}
	}
	print showValueWithClipboardCPButton($object->login).$addadmin;
	print '</td>';
}
print '</tr>'."\n";

print '</table>';

print '</div>';

print dol_get_fiche_end();


print '<br>';
print '<span class="opacitymedium">'.$langs->trans("AgendaExtSitesDesc")."</span><br>\n";
print "<br>\n";

$selectedvalue = !getDolGlobalString('AGENDA_DISABLE_EXT') ? 0 : $conf->global->AGENDA_DISABLE_EXT;
if ($selectedvalue == 1) {
	$selectedvalue = 0;
} else {
	$selectedvalue = 1;
}


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="center">'.$langs->trans("AgendaExtNb", "")."</td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("ExtSiteUrlAgenda").'<div class="hideonsmartphone opacitymedium">'." (".$langs->trans("Example").': https://externalcalendar/agenda/agenda.ics)</div></td>';
print "<td>".$form->textwithpicto($langs->trans("FixTZ"), $langs->trans("FillFixTZOnlyIfRequired"), 1).'</td>';
print '<td class="right">'.$langs->trans("Color").'</td>';
print "</tr>";

$i = 1;
while ($i <= $MAXAGENDA) {
	$key = $i;
	$name = 'AGENDA_EXT_NAME_'.$id.'_'.$key;
	$src = 'AGENDA_EXT_SRC_'.$id.'_'.$key;
	$offsettz = 'AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key;
	$color = 'AGENDA_EXT_COLOR_'.$id.'_'.$key;

	print '<tr class="oddeven">';
	// Nb @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	print '<td class="maxwidth50onsmartphone center">';
	//print $langs->trans("AgendaExtNb", $key);
	print $key;
	print "</td>";
	// Name
	$name_value = (GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$key) ? GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$key) : (empty($object->conf->$name) ? '' : $object->conf->$name));
	print '<td><input type="text" class="flat hideifnotset minwidth100 maxwidth100onsmartphone" name="AGENDA_EXT_NAME_'.$id.'_'.$key.'" value="'.$name_value.'"></td>';
	// URL
	$src_value = (GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$key) ? GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$key) : (empty($object->conf->$src) ? '' : $object->conf->$src));
	print '<td><input type="url" class="flat hideifnotset width300" name="AGENDA_EXT_SRC_'.$id.'_'.$key.'" value="'.$src_value.'"></td>';
	// Offset TZ
	$offsettz_value = (GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key) ? GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key) : (empty($object->conf->$offsettz) ? '' : $object->conf->$offsettz));
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key.'" value="'.$offsettz_value.'" size="1"></td>';
	// Color (Possible colors are limited by Google)
	print '<td class="nowraponall right">';
	$color_value = (GETPOST("AGENDA_EXT_COLOR_".$id.'_'.$key) ? GETPOST("AGENDA_EXT_COLOR_".$id.'_'.$key) : (empty($object->conf->$color) ? 'ffffff' : $object->conf->$color));
	print $formother->selectColor($color_value, "AGENDA_EXT_COLOR_".$id.'_'.$key, '', 1, array(), 'hideifnotset');
	print '</td>';
	print "</tr>";
	$i++;
}

print '</table>';
print '</div>';

$addition_button = array(
	'name' => 'save',
	'label_key' => 'Save',
	'addclass' => 'hideifnotset',
);
print $form->buttonsSaveCancel("", "", $addition_button);

print dol_get_fiche_end();

print "</form>\n";

// End of page
llxFooter();
$db->close();
