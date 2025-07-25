<?php
/* Copyright (C) 2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2014	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
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


if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/opensurvey/lib/opensurvey.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!$user->hasRight('opensurvey', 'write')) {
	accessforbidden();
}

$langs->load("opensurvey");


/*
 * View
 */

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("Survey"), '', "", 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans("CreatePoll"), '', 'poll');

print '<form name="formulaire" action="create_survey.php" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div class="center">';
print '<span class="opacitymedium">'.$langs->trans("OrganizeYourMeetingEasily").'</span><br><br>';
print '<div class="corps">';
print '<br>';
print '<div class="index_date">';
print '<div><img class="opacity imgopensurveywizard" src="../img/date.png" onclick="document.formulaire.date.click()"></div>';
print '<button id="date" name="choix_sondage" value="date" type="submit" class="button orange bigrounded">';
print '<img src="../img/calendar-32.png" alt="'.dol_escape_htmltag($langs->trans("CreateSurveyDate")).'" style="padding-right: 4px" class="inline-block valignmiddle">';
print '<div class="inline-block valignmiddle">'.dol_escape_htmltag($langs->trans("CreateSurveyDate")).'</div></button>';
print '</div>';
print '<div class="index_sondage">';
print '<div><img class="opacity imgopensurveywizard" src="../img/sondage2.png" onclick="document.formulaire.autre.click()"></div>';
print '<button id="autre" name="choix_sondage" value="autre" type="submit" class="button blue bigrounded">';
print '<img src="../img/chart-32.png" alt="'.dol_escape_htmltag($langs->trans("CreateSurveyStandard")).'" style="padding-right: 4px" class="inline-block valignmiddle">';
print '<div class="inline-block valignmiddle">'.dol_escape_htmltag($langs->trans("CreateSurveyStandard")).'</div></button>';
print '</div>';
print '<div class="clearboth"></div>';
print '<br>';
print '</div>';
print '</div></form>';

// Clean session variables

$i = 0;
unset($_SESSION["nbrecases"]);
while ($i < 100) {
	unset($_SESSION["choix".$i]);
	unset($_SESSION["typecolonne".$i]);
	$i++;
}


// End of page
llxFooter();
$db->close();
