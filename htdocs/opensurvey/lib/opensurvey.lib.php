<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/opensurvey/lib/opensurvey.lib.php
 *	\ingroup    opensurvey
 *	\brief      Functions for module
 */

/**
 * Returns an array with the tabs for the "Opensurvey poll" section
 * It loads tabs from modules looking for the entity Opensurveyso
 *
 * @param Opensurveysondage $object Current viewing poll
 * @return	array<array{0:string,1:string,2:string}>	Tabs for the opensurvey section
 */
function opensurvey_prepare_head(Opensurveysondage $object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[0][0] = 'card.php?id='.$object->id_sondage;
	$head[0][1] = $langs->trans("Survey");
	$head[0][2] = 'general';
	$h++;

	$head[1][0] = 'results.php?id='.$object->id_sondage;
	$head[1][1] = $langs->trans("SurveyResults");
	$nbVotes = $object->countVotes();
	if ($nbVotes > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbVotes).'</span>';
	}
	$head[1][2] = 'preview';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'opensurveypoll');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'opensurveypoll', 'remove');

	return $head;
}

/**
 * Show header for new member
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @param	string		$numsondage			Num survey
 * @return	void
 */
function llxHeaderSurvey($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [], $numsondage = '')
{
	global $langs, $mysoc;

	// $replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, 1); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	htmlPrintOnlineHeader($mysoc, $langs, 'OPENSURVEY_IMAGE_PUBLIC_INTERFACE');

	print '<span id="dolpaymentspan"></span>'."\n";
	print '<div class="center">'."\n";

	print '<form name="formulaire" action="studs.php?sondage='.urlencode($numsondage).'#bas" method="POST">'."\n";
	print '<input type="hidden" name="sondage" value="'.$numsondage.'"/>';
	print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
	print "\n";

	print '<div class="survey_borders"><br><br>';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterSurvey()
{
	print '</div>';
	print '</form>';
	print '</div>';

	printCommonFooter('public');

	dol_htmloutput_events();

	print "</body>\n";
	print "</html>\n";
}


/**
 * get_server_name
 *
 * @return	string		URL to use
 */
function get_server_name()
{
	global $dolibarr_main_url_root;

	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$url = $urlwithouturlroot.dol_buildpath('/opensurvey/', 1);

	if (!preg_match("|/$|", $url)) {
		$url .= "/";
	}

	return $url;
}

/**
 * Fonction vérifiant l'existance et la valeur non vide d'une clé d'un tableau
 *
 * @param   string  $name       Key to test
 * @param   ?array<string,null|mixed|mixed[]>   $tableau    Array in which searching key ($_POST by default)
 * @return  bool                True if key exists and return a non empty value
 */
function issetAndNoEmpty($name, $tableau = null)
{
	if ($tableau === null) {
		$tableau = $_POST;
	}

	return (isset($tableau[$name]) && !empty($tableau[$name]));
}


/**
 * Fonction permettant de générer les URL pour les sondage
 *
 * @param   string    $id     L'identifiant du sondage
 * @param   bool      $admin  True pour générer une URL pour l'administration d'un sondage, False pour un URL publique
 * @return  string            L'url pour le sondage
 */
function getUrlSondage($id, $admin = false)
{
	if ($admin === true) {
		$url = get_server_name().'results.php?id='.$id;
	} else {
		$url = get_server_name().'/public/studs.php?sondage='.$id;
	}

	return $url;
}


/**
 * 	Generate a random id
 *
 *	@param	int		$car	Length of string to generate key
 * 	@return	string
 */
function dol_survey_random($car)
{
	$string = "";
	$chaine = "abcdefghijklmnopqrstuvwxyz123456789";
	mt_srand((int) ((float) microtime() * 1000000));
	for ($i = 0; $i < $car; $i++) {
		$string .= $chaine[mt_rand() % strlen($chaine)];
	}
	return $string;
}

/**
 * Add a poll
 *
 * @return	void
 */
function ajouter_sondage()
{
	global $db, $user;

	require_once DOL_DOCUMENT_ROOT.'/opensurvey/class/opensurveysondage.class.php';

	$sondage = dol_survey_random(16);

	$allow_comments = empty($_SESSION['allow_comments']) ? 0 : 1;
	$allow_spy = empty($_SESSION['allow_spy']) ? 0 : 1;

	// Insert survey
	$opensurveysondage = new Opensurveysondage($db);
	$opensurveysondage->id_sondage = $sondage;
	$opensurveysondage->description = $_SESSION['description'];
	$opensurveysondage->mail_admin = $_SESSION['adresse'];
	$opensurveysondage->nom_admin = $_SESSION['nom'];
	$opensurveysondage->title = $_SESSION['title'];
	$opensurveysondage->date_fin = $_SESSION['champdatefin'];
	$opensurveysondage->format = $_SESSION['formatsondage'];
	$opensurveysondage->mailsonde = $_SESSION['mailsonde'];
	$opensurveysondage->allow_comments = $allow_comments;
	$opensurveysondage->allow_spy = $allow_spy;
	$opensurveysondage->sujet = $_SESSION['toutchoix'];

	$res = $opensurveysondage->create($user);

	if ($res < 0) {
		dol_print_error($db);
	}

	unset($_SESSION["title"]);
	unset($_SESSION["nom"]);
	unset($_SESSION["adresse"]);
	unset($_SESSION["description"]);
	unset($_SESSION["mailsonde"]);
	unset($_SESSION['allow_comments']);
	unset($_SESSION['allow_spy']);
	unset($_SESSION['toutchoix']);
	unset($_SESSION['totalchoixjour']);
	unset($_SESSION['champdatefin']);

	$urlback = dol_buildpath('/opensurvey/card.php', 1).'?id='.$sondage;

	header("Location: ".$urlback);
	exit();
}
