<?php
/* Copyright (C) 2023       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/public/opensurvey/index.php
 *       \ingroup    opensurvey
 *       \brief      Public file to show onpen surveys
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Societe $mysoc
 * @var Translate $langs
 *
 * @var string $dolibarr_main_url_root
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/opensurvey/class/opensurveysondage.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "opensurveys"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$SECUREKEY = GETPOST("securekey");
$entity = GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity;
$backtopage = '';
$suffix = "";

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new Opensurveysondage($db);

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

// Security check
if (!isModEnabled('opensurvey')) {
	httponly_accessforbidden('Module Opensurvey not enabled');
}


/*
 * Actions
 */

// None


/*
 * View
 */

$head = '';
if (getDolGlobalString('MAIN_OPENSURVEY_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="'.getDolGlobalString('MAIN_OPENSURVEY_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$conf->global->OPENSURVEY_ENABLE_PUBLIC_INTERFACE = 1;

if (!getDolGlobalString('OPENSURVEY_ENABLE_PUBLIC_INTERFACE')) {
	$langs->load("errors");
	print '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("Surveys"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1, 1);


include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
htmlPrintOnlineHeader($mysoc, $langs, 1, getDolGlobalString('OPENSURVEY_PUBLIC_INTERFACE_TOPIC'), 'OPENSURVEY_IMAGE_PUBLIC_INTERFACE', 'ONLINE_OPENSURVEY_LOGO_'.$suffix, 'ONLINE_OPENSURVEY_LOGO');


print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dosign">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix", 'alpha').'">'."\n";
print '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print "\n";
print '<!-- Form to view survey -->'."\n";

$results = $object->fetchAll($sortorder, $sortfield, 0, 0, '(status:=:1)');
$now = dol_now();

if (is_array($results)) {
	if (empty($results)) {
		print '<br>';
		print $langs->trans("NoSurvey");
	} else {
		print '<br><br><br>';
		print '<span class="opacitymedium">'.$langs->trans("ListOfOpenSurveys").'</span>';
		print '<br><br><br>';
		print '<br class="hideonsmartphone">';

		foreach ($results as $survey) {
			$object = $survey;

			print '<table id="dolpaymenttable" summary="Job position offer" class="center centpercent">'."\n";

			// Output payment summary form
			print '<tr><td class="left">';

			print '<div class="centpercent" id="tablepublicpayment">';

			$error = 0;
			$found = true;

			// Label
			print $langs->trans("Label").' : ';
			print '<b>'.dol_escape_htmltag(empty($object->titre) ? $object->title : $object->titre).'</b><br>';

			// Date
			print  $langs->trans("DateExpected").' : ';
			print '<b>';
			if ($object->date_fin > $now) {
				print dol_print_date($object->date_fin, 'day');
			} else {
				print $langs->trans("ASAP");
			}
			print '</b><br>';

			// Description
			//print  $langs->trans("Description").' : ';
			print '<br>';
			print '<div class="opensurveydescription centpercent">';
			print dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->commentaires), 1, 1, 1));
			//print dol_escape_htmltag($object->commentaires);
			print '</div>';
			print '<br>';

			print '</div>'."\n";
			print "\n";


			if ($action != 'dosubmit') {
				if ($found && !$error) {
					// We are in a management option and no error
				} else {
					dol_print_error_email('ERRORSUBMITAPPLICATION');
				}
			} else {
				// Print
			}

			print '</td></tr>'."\n";

			print '</table>'."\n";

			print '<br><br class="hideonsmartphone"><br class="hideonsmartphone"><br class="hideonsmartphone">'."\n";
		}
	}
} else {
	dol_print_error($db, $object->error, $object->errors);
}

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
