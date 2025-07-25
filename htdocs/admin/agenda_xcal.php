<?php
/* Copyright (C) 2008-2015 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/agenda_xcal.php
 *      \ingroup    agenda
 *      \brief      Page to setup miscellaneous options of agenda module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "agenda"));

$def = array();
$actionsave = GETPOST('save', 'alpha');
$MAIN_AGENDA_XCAL_EXPORTKEY = getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY');
$MAIN_AGENDA_EXPORT_PAST_DELAY = getDolGlobalString('MAIN_AGENDA_EXPORT_PAST_DELAY', 100);
$MAIN_AGENDA_EXPORT_CACHE = getDolGlobalInt('MAIN_AGENDA_EXPORT_CACHE');
$AGENDA_EXPORT_FIX_TZ = getDolGlobalString('AGENDA_EXPORT_FIX_TZ');

if (GETPOSTISSET('MAIN_AGENDA_XCAL_EXPORTKEY')) {
	$MAIN_AGENDA_XCAL_EXPORTKEY = trim(GETPOST('MAIN_AGENDA_XCAL_EXPORTKEY', 'alpha'));
}
if (GETPOSTISSET('MAIN_AGENDA_EXPORT_PAST_DELAY')) {
	$MAIN_AGENDA_EXPORT_PAST_DELAY = intval(GETPOSTINT('MAIN_AGENDA_EXPORT_PAST_DELAY'));
}
if (GETPOSTISSET('MAIN_AGENDA_EXPORT_CACHE')) {
	$MAIN_AGENDA_EXPORT_CACHE = intval(GETPOSTINT('MAIN_AGENDA_EXPORT_CACHE'));
}
if (GETPOSTISSET('AGENDA_EXPORT_FIX_TZ')) {
	$AGENDA_EXPORT_FIX_TZ = trim(GETPOST('AGENDA_EXPORT_FIX_TZ', 'alpha'));
}

// Sauvegardes parameters
if ($actionsave) {
	$i = 0;

	$db->begin();

	$i += dolibarr_set_const($db, 'MAIN_AGENDA_XCAL_EXPORTKEY', $MAIN_AGENDA_XCAL_EXPORTKEY, 'chaine', 0, '', $conf->entity);
	$i += dolibarr_set_const($db, 'MAIN_AGENDA_EXPORT_PAST_DELAY', $MAIN_AGENDA_EXPORT_PAST_DELAY, 'chaine', 0, '', $conf->entity);
	$i += dolibarr_set_const($db, 'MAIN_AGENDA_EXPORT_CACHE', $MAIN_AGENDA_EXPORT_CACHE, 'chaine', 0, '', $conf->entity);
	$i += dolibarr_set_const($db, 'AGENDA_EXPORT_FIX_TZ', $AGENDA_EXPORT_FIX_TZ, 'chaine', 0, '', $conf->entity);

	if ($i >= 4) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("SaveFailed"), null, 'errors');
	}
}



/**
 * View
 */

if (!isset($conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY)) {
	$conf->global->MAIN_AGENDA_EXPORT_PAST_DELAY = 100;
}

$wikihelp = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-agenda_xcal');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');


print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$head = agenda_prepare_head();

print dol_get_fiche_head($head, 'xcal', $langs->trans("Agenda"), -1, 'action');

print '<span class="opacitymedium">'.$langs->trans("AgendaSetupOtherDesc")."</span><br>\n";
print "<br>\n";

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td></td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("PasswordTogetVCalExport")."</td>";
print '<td><input required="required" type="text" class="flat minwidth100 maxwidth300 widthcentpercentminusx" id="MAIN_AGENDA_XCAL_EXPORTKEY" name="MAIN_AGENDA_XCAL_EXPORTKEY" value="'.dol_escape_htmltag($MAIN_AGENDA_XCAL_EXPORTKEY).'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
print '</td>';
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("PastDelayVCalExport")."</td>";
print '<td><input type="text" class="flat width50 right" name="MAIN_AGENDA_EXPORT_PAST_DELAY" value="'.$MAIN_AGENDA_EXPORT_PAST_DELAY.'"> '.$langs->trans("days")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("UseACacheDelay")."</td>";
print '<td><input type="text" class="flat width50 right" name="MAIN_AGENDA_EXPORT_CACHE" value="'.$MAIN_AGENDA_EXPORT_CACHE.'"></td>';
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';
print '</div>';

print '<br>';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "</tr>";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("FixTZ")."</td>";
print "<td>";
print '<input class="flat" type="text" size="4" name="AGENDA_EXPORT_FIX_TZ" value="'.dol_escape_htmltag($AGENDA_EXPORT_FIX_TZ).'">';
print ' &nbsp; '.$langs->trans("FillThisOnlyIfRequired");
print "</td>";
print "</tr>";

print '</table>';
print '</div>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";


clearstatcache();


print "<br><br>";


// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
$getentity = ($conf->entity > 1 ? "&entity=".$conf->entity : "");

// Show message
$message = '';

$urlvcal = '<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=vcal'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'" target="_blank" rel="noopener noreferrer">';
$urlvcal .= $urlwithroot.'/public/agenda/agendaexport.php?format=vcal'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', 'KEYNOTDEFINED')).'</a>';
$message .= img_picto('', 'globe').' '.str_replace('{url}', $urlvcal, '<span class="opacitymedium">'.$langs->trans("WebCalUrlForVCalExport", 'vcal', '').'</span>');
$message .= '<div class="urllink">';
$message .= '<input type="text" id="onlinepaymenturl1" class="quatrevingtpercent" spellcheck="false" value="'.$urlwithroot.'/public/agenda/agendaexport.php?format=vcal'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">';
if (getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) {
	$message .= ' <a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=vcal'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">'.img_picto('', 'download').'</a>';
}
$message .= '</div>';
$message .= ajax_autoselect('onlinepaymenturl1');
$message .= '<br>';

$urlical = '<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical&type=event'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'" target="_blank" rel="noopener noreferrer">';
$urlical .= $urlwithroot.'/public/agenda/agendaexport.php?format=ical&type=event'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'</a>';
$message .= img_picto('', 'globe').' '.str_replace('{url}', $urlical, '<span class="opacitymedium">'.$langs->trans("WebCalUrlForVCalExport", 'ical/ics', '').'</span>');
$message .= '<div class="urllink">';
$message .= '<input type="text" id="onlinepaymenturl2" class="quatrevingtpercent" spellcheck="false" value="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">';
if (getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) {
	$message .= ' <a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">'.img_picto('', 'download').'</a>';
}
$message .= '</div>';
$message .= ajax_autoselect('onlinepaymenturl2');
$message .= '<br>';

$urlrss = '<a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=rss'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'" target="_blank" rel="noopener noreferrer">';
$urlrss .= $urlwithroot.'/public/agenda/agendaexport.php?format=rss'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'</a>';
$message .= img_picto('', 'globe').' '.str_replace('{url}', $urlrss, '<span class="opacitymedium">'.$langs->trans("WebCalUrlForVCalExport", 'rss', '').'</span>');
$message .= '<div class="urllink">';
$message .= '<input type="text" id="onlinepaymenturl3" class="quatrevingtpercent" spellcheck="false" value="'.$urlwithroot.'/public/agenda/agendaexport.php?format=rss'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">';
if (getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) {
	$message .= ' <a href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=rss'.$getentity.'&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...')).'">'.img_picto('', 'download').'</a>';
}
$message .= '</div>';
$message .= ajax_autoselect('onlinepaymenturl3');
$message .= '<br>';

print $message;

$message = $langs->trans("AgendaUrlOptions1", $user->login, $user->login).'<br>';
$message .= $langs->trans("AgendaUrlOptions3", $user->login, $user->login, $user->login).'<br>';
$message .= $langs->trans("AgendaUrlOptions4", $user->login, $user->login).'<br>';
$message .= $langs->trans("AgendaUrlOptionsProject", $user->login, $user->login).'<br>';
$message .= $langs->trans("AgendaUrlOptionsType", 'systemauto|system').'<br>';
$message .= $langs->trans("AgendaUrlOptionsCode", 'AC_COMPANY_CREATE,AC_PROPAL_VALIDATE,AC_CODE...').'<br>';
$message .= $langs->trans("AgendaUrlOptionsIncludeHolidays", '1', '1').'<br>';
//$defaultnotolderthan = getDolGlobalString('MAIN_AGENDA_EXPORT_PAST_DELAY', 100);
//$message .= $langs->trans("AgendaUrlOptionsLimitDays", $defaultnotolderthan, $defaultnotolderthan, $defaultnotolderthan).'<br>';
$message .= $langs->trans("AgendaUrlOptionsLimit", '1000').'<br>';

print info_admin($message);

$constname = 'MAIN_AGENDA_XCAL_EXPORTKEY';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword($constname);

// End of page
llxFooter();
$db->close();
