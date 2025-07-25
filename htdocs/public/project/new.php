<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/public/project/new.php
 *	\ingroup    project
 *	\brief      Page to record a message/lead into a project/lead
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


// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Init vars
$errmsg = '';
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 */

// Load translation files
$langs->loadLangs(array("members", "companies", "install", "other", "projects"));

if (!getDolGlobalString('PROJECT_ENABLE_PUBLIC')) {
	print $langs->trans("Form for public lead registration has not been enabled");
	exit;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('publicnewleadcard', 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Project($db);

$user->loadDefaultValues();

// Security check
if (empty($conf->project->enabled)) {
	httponly_accessforbidden('Module Project not enabled');
}


/**
 * Show header for new member
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])  // @phan-suppress-current-line PhanRedefineFunction
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	htmlPrintOnlineHeader($mysoc, $langs, 1, getDolGlobalString('PROJECT_PUBLIC_INTERFACE_TOPIC'), 'PROJECT_IMAGE_PUBLIC_NEWLEAD');

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @return	void
 */
function llxFooterVierge()  // @phan-suppress-current-line PhanRedefineFunction
{
	print '</div>';

	printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
}



/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {	// Test on permission not required here. This is an anonymous public submission form. Check is done on the constant to enable feature + mitigation.
	$error = 0;
	$urlback = '';

	$db->begin();

	if (!GETPOST('lastname', 'alpha')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname', 'alpha')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (!GETPOST('email', 'alpha')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	if (!GETPOST('description', 'alpha')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Message"))."<br>\n";
	}
	if (GETPOST('email', 'alpha') && !isValidEmail(GETPOST('email', 'alpha'))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST('email', 'alpha'))."<br>\n";
	}
	// Set default opportunity status
	$defaultoppstatus = getDolGlobalInt('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD');
	if (empty($defaultoppstatus)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Project"))."<br>\n";
	}

	$visibility = getDolGlobalString('PROJET_VISIBILITY');

	$proj = new Project($db);
	$thirdparty = new Societe($db);

	if (!$error) {
		// Search thirdparty and set it if found to the new created project
		$result = $thirdparty->fetch(0, '', '', '', '', '', '', '', '', '', GETPOST('email', 'alpha'));
		if ($result > 0) {
			$proj->socid = $thirdparty->id;
		} else {
			// Create the prospect
			if (GETPOST('societe', 'alpha')) {
				$thirdparty->name =  GETPOST('societe', 'alpha');
				$thirdparty->name_alias = dolGetFirstLastname(GETPOST('firstname', 'alpha'), GETPOST('lastname', 'alpha'));
			} else {
				$thirdparty->name = dolGetFirstLastname(GETPOST('firstname', 'alpha'), GETPOST('lastname', 'alpha'));
			}
			$thirdparty->email = GETPOST('email', 'alpha');
			$thirdparty->address = GETPOST('address', 'alpha');
			$thirdparty->zip = GETPOST('zip', 'int');
			$thirdparty->town = GETPOST('town', 'alpha');
			$thirdparty->country_id = GETPOSTINT('country_id');
			$thirdparty->state_id = GETPOSTINT('state_id');
			$thirdparty->client = $thirdparty::PROSPECT;
			$thirdparty->code_client = 'auto';
			$thirdparty->code_fournisseur = 'auto';

			// Fill array 'array_options' with data from the form
			$extrafields->fetch_name_optionals_label($thirdparty->table_element);
			$ret = $extrafields->setOptionalsFromPost(null, $thirdparty, '', 1);
			if ($ret < 0) {
				$error++;
				$errmsg = ($extrafields->error ? $extrafields->error.'<br>' : '').implode('<br>', $extrafields->errors);
			}

			if (!$error) {
				$result = $thirdparty->create($user);
				if ($result <= 0) {
					$error++;
					$errmsg = ($thirdparty->error ? $thirdparty->error.'<br>' : '').implode('<br>', $thirdparty->errors);
				} else {
					$proj->socid = $thirdparty->id;
				}
			}
		}
	}

	if (!$error) {
		// Defined the ref into $defaultref
		$defaultref = '';
		$modele = getDolGlobalString('PROJECT_ADDON', 'mod_project_simple');

		// Search template files
		$file = '';
		$classname = '';
		$reldir = '';
		$filefound = 0;
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$file = dol_buildpath($reldir."core/modules/project/".$modele.'.php', 0);
			if (file_exists($file)) {
				$filefound = 1;
				$classname = $modele;
				break;
			}
		}

		if ($filefound && !empty($classname)) {
			$result = dol_include_once($reldir."core/modules/project/".$modele.'.php');
			if (class_exists($classname)) {
				$modProject = new $classname();
				'@phan-var-force ModeleNumRefProjects $modProject';

				$defaultref = $modProject->getNextValue($thirdparty, $object);
			}
		}

		if (is_numeric($defaultref) && $defaultref <= 0) {
			$defaultref = '';
		}

		if (empty($defaultref)) {
			$defaultref = 'PJ'.dol_print_date(dol_now(), 'dayrfc');
		}

		if ($visibility === "1") {
			$proj->public = 1;
		} elseif ($visibility === "0") {
			$proj->public = 0;
		} elseif (empty($visibility)) {
			$proj->public = 1;
		}

		$proj->ref         = $defaultref;
		$proj->statut      = $proj::STATUS_DRAFT;
		$proj->status      = $proj::STATUS_DRAFT;
		$proj->usage_opportunity = 1;
		$proj->title       = $langs->trans("LeadFromPublicForm");
		$proj->description = GETPOST("description", "alphanohtml");
		$proj->opp_status = $defaultoppstatus;
		$proj->fk_opp_status = $defaultoppstatus;

		$proj->ip = getUserRemoteIP();
		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(rowid) as nb_projets";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet";
			$sql .= " WHERE ip = '".$db->escape($proj->ip)."'";
			$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_projets;
				}
			}
		}

		// Fill array 'array_options' with data from the form
		$extrafields->fetch_name_optionals_label($proj->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $proj);
		if ($ret < 0) {
			$error++;
		}

		if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			$error++;
			$errmsg = $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
			array_push($proj->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		}
		// Create the project
		if (!$error) {
			$result = $proj->create($user);
			if ($result > 0) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$object = $proj;

				if ($object->email) {
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members", "projects"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = getDolGlobalString('PROJECT_EMAIL_TEMPLATE_AUTOLEAD');

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'project', $user, $outputlangs, 0, 1, $labeltouse);
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}
					if (empty($labeltosue)) {
						$appli = $mysoc->name;

						$labeltouse = '['.$appli.'] '.$langs->trans("YourMessage");
						$msg = $langs->trans("YourMessageHasBeenReceived");
					}

					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions($msg, $substitutionarray, $outputlangs);
					if ($subjecttosend && $texttosend) {
						$moreinheader = 'X-Dolibarr-Info: send_an_email by public/lead/new.php'."\r\n";

						$result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					}
					/*if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}*/
				}

				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} elseif (getDolGlobalString('PROJECT_URL_REDIRECT_LEAD')) {
					$urlback = getDolGlobalString('PROJECT_URL_REDIRECT_LEAD');
					// TODO Make replacement of __AMOUNT__, etc...
				} else {
					$urlback = $_SERVER["PHP_SELF"]."?action=added&token=".newToken();
				}

				if (!empty($entity)) {
					$urlback .= '&entity='.$entity;
				}

				dol_syslog("project lead ".$proj->ref." has been created, we redirect to ".$urlback);
			} else {
				$error++;
				$errmsg .= $proj->error.'<br>'.implode('<br>', $proj->errors);
			}
		} else {
			setEventMessage($errmsg, 'errors');
		}
	}

	if (!$error) {
		$db->commit();

		header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
	}
}

// Action called after a submitted was send and member created successfully
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && $action == 'added') {	// Test on permission not required here
	llxHeaderVierge($langs->trans("NewLeadForm"));

	// Si on a pas ete redirige
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewLeadbyWeb");
	print '</div>';

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels

llxHeaderVierge($langs->trans("NewContact"));

print '<br>';

print load_fiche_titre($langs->trans("NewContact"), '', '', 0, '', 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext opacitymedium justify">';
if (getDolGlobalString('PROJECT_NEWFORM_TEXT')) {
	print $langs->trans(getDolGlobalString('PROJECT_NEWFORM_TEXT'))."<br>\n";
} else {
	print $langs->trans("FormForNewLeadDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL"))."<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newlead">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';

print '<br>';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head();

print '<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).ready(function () {
        jQuery("#selectcountry_id").change(function() {
           document.newlead.action.value="create";
           document.newlead.submit();
        });
    });
});
</script>';


print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

// Lastname
print '<tr><td>'.$langs->trans("Lastname").' <span class="star">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'" required></td></tr>'."\n";
// Firstname
print '<tr><td>'.$langs->trans("Firstname").' <span class="star">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'" required></td></tr>'."\n";
// EMail
print '<tr><td>'.$langs->trans("Email").' <span class="star">*</span></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'" required></td></tr>'."\n";
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
// Address
print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_2.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";
// Zip / Town
print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6, 1);
print ' / ';
print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
print '</td></tr>';
// Country
print '<tr><td>'.$langs->trans('Country').'</td><td>';
$country_id = GETPOST('country_id');
if (!$country_id && getDolGlobalString('PROJECT_NEWFORM_FORCECOUNTRYCODE')) {
	$country_id = getCountry($conf->global->PROJECT_NEWFORM_FORCECOUNTRYCODE, '2', $db, $langs);
}
if (!$country_id && !empty($conf->geoipmaxmind->enabled)) {
	$country_code = dol_user_country();
	//print $country_code;
	if ($country_code) {
		$new_country_id = getCountry($country_code, '3', $db, $langs);
		//print 'xxx'.$country_code.' - '.$new_country_id;
		if ($new_country_id) {
			$country_id = $new_country_id;
		}
	}
}
$country_code = getCountry($country_id, '2', $db, $langs);
print $form->select_country($country_id, 'country_id');
print '</td></tr>';
// State
if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
	print '<tr><td>'.$langs->trans('State').'</td><td>';
	if ($country_code) {
		print $formcompany->select_state(GETPOSTINT("state_id"), $country_code);
	} else {
		print '';
	}
	print '</td></tr>';
}

// Other attributes
$parameters['tpl_context'] = 'public';	// define template context to public
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
// Comments
print '<tr>';
print '<td class="tdtop">'.$langs->trans("Message").' <span class="star">*</span></td>';
print '<td class="tdtop"><textarea name="description" id="description" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_5.'" required>'.dol_escape_htmltag(GETPOST('description', 'restricthtml'), 0, 1).'</textarea></td>';
print '</tr>'."\n";

print "</table>\n";

print dol_get_fiche_end();

// Save
print '<div class="center">';
print '<input type="submit" value="'.$langs->trans("Submit").'" id="submitsave" class="button">';
if (!empty($backtopage)) {
	print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
}
print '</div>';


print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
