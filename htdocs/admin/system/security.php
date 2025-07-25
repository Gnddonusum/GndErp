<?php
/* Copyright (C) 2013-2022	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *  \file       htdocs/admin/system/security.php
 *  \brief      Page to show Security information
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string[]	$arrayofstreamtodisable // $arrayofstreamtodisable is defined into filefunc.inc.php
 * @var string 		$conffile 				// $conffile is defined into filefunc.inc.php
 * @var string 		$dolibarr_main_prod
 * @var string		$dolibarr_main_document_root
 */

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin", "errors"));

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('action', 'aZ09') == 'donothing') {
	exit;
}

$execmethod = getDolGlobalInt('MAIN_EXEC_USE_POPEN', 1);


/*
 * View
 */

$form = new Form($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_security');

print load_fiche_titre($langs->trans("Security"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("YouMayFindSecurityAdviceHere", 'hhttps://wiki.dolibarr.org/index.php/Security_information').'</span>';
print ' &nbsp; &nbsp; ';
print '<a href="'.$_SERVER["PHP_SELF"].'">';
print img_picto($langs->trans("Reload"), 'refresh').' ';
print $langs->trans("Reload");
print '</a>';
print '<br>';
print '<br>';


print '<br>';


print load_fiche_titre($langs->trans("PHPSetup"), '', 'folder');


print '<div class="divsection wordbreak">';

// Get version of PHP
$phpversion = version_php();
print "<strong>PHP</strong>: ".$langs->trans("Version").": ".$phpversion;
if (function_exists('php_ini_loaded_file')) {
	$inipath = php_ini_loaded_file();
	print " - <strong>INI</strong>: ".$inipath;
}
print "<br>\n";

// Get version of web server
print "<br><strong>Web server - ".$langs->trans("Version")."</strong>: ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";
print '<strong>'.$langs->trans("DataRootServer")."</strong>: ".DOL_DATA_ROOT."<br>\n";
// Web user group by default
$labeluser = dol_getwebuser('user');
$labelgroup = dol_getwebuser('group');
if ($labeluser && $labelgroup) {
	print '<strong>'.$langs->trans("WebUserGroup")." (env vars)</strong> : ".$labeluser.':'.$labelgroup;
	if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
		$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
		print ' <span class="opacitymedium">(POSIX '.$arrayofinfoofuser['name'].':'.$arrayofinfoofuser['gecos'].':'.$arrayofinfoofuser['dir'].':'.$arrayofinfoofuser['shell'].')</span><br>'."\n";
	}
}
// Web user group real (detected by 'id' external command)
if (function_exists('exec')) {
	$arrayout = array();
	$varout = 0;
	exec('id', $arrayout, $varout);
	if (empty($varout)) {	// Test command is ok. Work only on Linux OS.
		print '<strong>'.$langs->trans("WebUserGroup")." (real, 'id' command)</strong> : ".implode(',', $arrayout)."<br>\n";
	}
}
print '<br>';

print (ini_get('session.use_strict_mode') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP session.use_strict_mode</strong> = ".(ini_get('session.use_strict_mode') ? ini_get('session.use_strict_mode') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print (ini_get('session.use_only_cookies') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP session.use_only_cookies</strong> = ".(ini_get('session.use_only_cookies') ? ini_get('session.use_only_cookies') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print (ini_get('session.cookie_httponly') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP session.cookie_httponly</strong> = ".(ini_get('session.cookie_httponly') ? ini_get('session.cookie_httponly') : '').' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", '1').")</span><br>\n";
print (ini_get('session.cookie_samesite') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP session.cookie_samesite</strong> = ".(ini_get('session.cookie_samesite') ? ini_get('session.cookie_samesite') : 'None');
if (!ini_get('session.cookie_samesite') || ini_get('session.cookie_samesite') == 'Lax') {
	print ' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", 'Lax').")</span>";
} elseif (ini_get('session.cookie_samesite') == 'Strict') {
	print ' &nbsp; '.img_warning().' <span class="opacitymedium">'.$langs->trans("WarningPaypalPaymentNotCompatibleWithStrict")."</span>";
}
print "<br>\n";

print (ini_get('open_basedir') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP open_basedir</strong> = ".(ini_get('open_basedir') ? ini_get('open_basedir') : yn(0).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("ARestrictedPath").', '.$langs->transnoentitiesnoconv("Example").': '.$_SERVER["DOCUMENT_ROOT"].','.DOL_DATA_ROOT).')</span>')."<br>\n";

print ((empty(ini_get('short_open_tag')) || ini_get('short_open_tag') == 'Off') ? img_picto('', 'tick', 'class="pictofixedwidth"') : img_warning('', '', 'pictofixedwidth nopaddingleft'))."<strong>PHP short_open_tag</strong> = ".((empty(ini_get('short_open_tag')) || ini_get('short_open_tag') == 'Off') ? yn(0) : yn(1)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).')</span>'."<br>\n";

print (ini_get('allow_url_fopen') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning', 'class="pictofixedwidth nopaddingleft"') : img_picto('', 'tick', 'class="pictofixedwidth"'))."<strong>PHP allow_url_fopen</strong> = ".(ini_get('allow_url_fopen') ? ini_get('allow_url_fopen') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).", except if Yes is required by some external modules)</span><br>\n";

print (ini_get('allow_url_include') ? img_picto($langs->trans("YouShouldSetThisToOff"), 'warning', 'class="pictofixedwidth nopaddingleft"') : img_picto('', 'tick', 'class="pictofixedwidth"')). "<strong>PHP allow_url_include</strong> = ".(ini_get('allow_url_include') ? ini_get('allow_url_include') : yn(0)).' &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("No")).")</span><br>\n";
//print "<strong>PHP safe_mode</strong> = ".(ini_get('safe_mode') ? ini_get('safe_mode') : yn(0)).' &nbsp; <span class="opacitymedium">'.$langs->trans("Deprecated")." (removed in PHP 5.4)</span><br>\n";

if (getDolGlobalString('MAIN_SECURITY_SHOW_MORE_INFO')) {
	print "<strong>PHP auto_prepend_file</strong> = ".(ini_get('auto_prepend_file') ? ini_get('auto_prepend_file') : '')."</span><br>\n";

	print "<strong>PHP sendmail_path</strong> = ".(ini_get('sendmail_path') ? ini_get('sendmail_path') : '')."</span><br>\n";
}

print '<br>';
print "<strong>PHP disable_functions</strong>: ";
$arrayoffunctionsdisabled = explode(',', ini_get('disable_functions'));
$arrayoffunctionstodisable = explode(',', 'dl,apache_note,apache_setenv,pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_get_handler,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,pcntl_async_signals,show_source,virtual');
//$arrayoffunctionstodisable[] = 'stream_wrapper_restore';
//$arrayoffunctionstodisable[] = 'stream_wrapper_register';
if ($execmethod == 1) {
	$arrayoffunctionstodisable2 = explode(',', 'passthru,shell_exec,system,proc_open,popen');
	$functiontokeep = 'exec';
} else {
	$arrayoffunctionstodisable2 = explode(',', 'exec,passthru,shell_exec,system,proc_open');
	$functiontokeep = 'popen';
}
$i = 0;
foreach ($arrayoffunctionsdisabled as $functionkey) {
	if ($i > 0) {
		print ', ';
	}
	print '<span class="opacitymedium">'.$functionkey.'</span>';
	$i++;
}
print "<br>\n";
$todisabletext = '';
$i = 0;
foreach ($arrayoffunctionstodisable as $functiontodisable) {
	if (\function_exists($functiontodisable)) {
		if ($i > 0) {
			$todisabletext .= ', ';
		}
		$todisabletext .= ' <span class="opacitymedium">'.$functiontodisable.'</span>';
		$i++;
	}
}
if ($todisabletext) {
	print img_picto('', 'warning', 'class="pictofixedwidth nopaddingleft"').$langs->trans("YouShouldDisablePHPFunctions").': '.$todisabletext;
	print '<br>';
}
$todisabletext = '';
$i = 0;
foreach ($arrayoffunctionstodisable2 as $functiontodisable) {
	if (\function_exists($functiontodisable)) {
		if ($i > 0) {
			$todisabletext .= ', ';
		}
		$todisabletext .= ' <span class="opacitymedium">'.$functiontodisable.'</span>';
		$i++;
	}
}
if ($todisabletext) {
	print img_picto('', 'warning', 'class="pictofixedwidth nopaddingleft"').$langs->trans("IfCLINotRequiredYouShouldDisablePHPFunctions").': '.$todisabletext;
	print '<br>';
}
if (!\function_exists($functiontokeep)) {
	print img_picto($langs->trans("PHPFunctionsRequiredForCLI"), 'warning', 'class="pictofixedwidth"');
} else {
	print img_picto('', 'tick', 'class="pictofixedwidth"');
}
print $langs->trans("PHPFunctionsRequiredForCLI").': ';
print '<span class="opacitymedium">'.$functiontokeep.'</span>';
print '<br>';

print '<br>';

// JSON
$loadedExtensions    = array_map('strtolower', get_loaded_extensions(false));
$test = !in_array('json', $loadedExtensions);
if ($test || function_exists('dol_json_decode')) {
	print img_picto('', 'error', 'class="pictofixedwidth nopaddingleft"');
} else {
	print img_picto('', 'tick', 'class="pictofixedwidth"');
}
print '<strong>JSON</strong>: ';
if ($test || function_exists('dol_json_decode')) {
	print $langs->trans("NotInstalled").' - '.$langs->trans("VulnerableToRCEAttack");
} else {
	print $langs->trans("Available").' <span class="opacitymedium">(PHP native so not emulated, safe)</span>';
}
print '<br>';

// XDebug
$test = !function_exists('xdebug_is_enabled') && !extension_loaded('xdebug');
if ($test) {
	print img_picto('', 'tick', 'class="pictofixedwidth"');
} else {
	print img_picto('', 'warning', 'class="pictofixedwidth nopaddingleft"');
}
print '<strong>XDebug</strong>: ';
if ($test) {
	print $langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	print $langs->trans("ModuleActivatedMayExposeInformation", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}

print '</div>';

print '<br>';


// OS Setup - Permissions on files

print '<br>';
print load_fiche_titre($langs->trans("OSSetup").' - '.$langs->trans("PermissionsOnFiles"), '', 'folder');

print '<div class="divsection wordbreak">';

print '<strong>'.$langs->trans("PermissionsOnFilesInWebRoot").'</strong>: ';
$arrayoffilesinroot = dol_dir_list(DOL_DOCUMENT_ROOT, 'all', 1, '', array('\/custom'), 'name', SORT_ASC, 4, 1, '', 1);
$fileswithwritepermission = array();
foreach ($arrayoffilesinroot as $fileinroot) {
	// Test if there is at least one write permission file. If yes, add the entry into array $fileswithwritepermission
	if (isset($fileinroot['perm']) && ($fileinroot['perm'] & 0222)) {
		$fileswithwritepermission[] = $fileinroot['relativename'];
	}
}
if (empty($fileswithwritepermission)) {
	print img_picto('', 'tick').' '.$langs->trans("NoWritableFilesFoundIntoRootDir");
} else {
	print img_warning().' '.$langs->trans("SomeFilesOrDirInRootAreWritable");
	print ' &nbsp; - &nbsp; <span class="classlink cursorpointer" onclick="javascript: console.log(\'Toggle examples\'); jQuery(\'#examplewritablefiles\').toggle();">'.$langs->trans("Examples").'</span>';
	print '<span id="examplewritablefiles" class="hideobject"><br>'.$langs->trans("Example").': ';
	$i = 0;
	foreach ($fileswithwritepermission as $filewithwritepermission) {
		if ($i > 0) {
			print ', ';
		}
		print '<span class="opacitymedium">'.$filewithwritepermission.'</span>';
		if ($i > 20) {
			print ' ...';
			break;
		}
		$i++;
	}
	print '</span>';
}
print '<br>';
print '<br>';

print '<strong>'.$langs->trans("PermissionsOnFile", $conffile).'</strong>: ';		// $conffile is defined into filefunc.inc.php
$perms = fileperms($dolibarr_main_document_root.'/'.$conffile);
if ($perms) {
	if (($perms & 0x0004) || ($perms & 0x0002)) {
		print img_warning().' '.$langs->trans("ConfFileIsReadableOrWritableByAnyUsers");
		// Web user group by default
		$labeluser = dol_getwebuser('user');
		$labelgroup = dol_getwebuser('group');
		print ' '.$langs->trans("User").': '.$labeluser.':'.$labelgroup;
		if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
			$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
			print ' <span class="opacitymedium">(POSIX '.$arrayofinfoofuser['name'].':'.$arrayofinfoofuser['gecos'].':'.$arrayofinfoofuser['dir'].':'.$arrayofinfoofuser['shell'].')</span>';
		}
	} else {
		print img_picto('', 'tick');
	}
} else {
	print img_warning().' '.$langs->trans("FailedToReadFile", $conffile);
}
print '<br>';
print '<br>';

$installlock = DOL_DATA_ROOT.'/install.lock';
$upgradeunlock = DOL_DATA_ROOT.'/upgrade.unlock';
$installmoduleslock = DOL_DATA_ROOT.'/installmodules.lock';

// Is install (upgrade) locked
$test = file_exists($installlock);
print '<strong>'.$langs->trans("DolibarrSetup").'</strong>: ';
if ($test) {
	if (file_exists($upgradeunlock)) {
		print img_picto('', 'tick').' '.$langs->trans("InstallLockedBy", $installlock);
	} else {
		print img_picto('', 'tick').' '.$langs->trans("InstallAndUpgradeLockedBy", $installlock);
	}
} else {
	print img_warning().' '.$langs->trans("WarningLockFileDoesNotExists", DOL_DATA_ROOT);
}

print '<br>';
print '<br>';

// Is upgrade unlocked
if (file_exists($installlock)) {	// If install not locked, no need to show this.
	if (file_exists($upgradeunlock)) {
		print '<strong>'.$langs->trans("DolibarrUpgrade").'</strong>: ';
		print img_warning().' '.$langs->trans("WarningUpgradeHasBeenUnlocked", $upgradeunlock);
		print '<br>';
		print '<br>';
	}
}

// Is addon install locked ?
$test = file_exists($installmoduleslock);
print '<strong>'.$langs->trans("DolibarrAddonInstall").'</strong>: ';
if ($test) {
	print img_picto('', 'tick').' '.$langs->trans("InstallAndUpgradeLockedBy", $installmoduleslock);
} else {
	print $langs->trans("InstallOfAddonIsNotBlocked", DOL_DATA_ROOT);
}

print '</div>';



// File conf.php

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("ConfigurationFile").' ('.$conffile.')', '', 'folder');

print '<div class="divsection wordbreak">';
print '<strong>$dolibarr_main_prod</strong>: '.($dolibarr_main_prod ? $dolibarr_main_prod : '0');
if (empty($dolibarr_main_prod)) {
	print ' &nbsp; &nbsp; '.img_picto('', 'warning').' '.$langs->trans("IfYouAreOnAProductionSetThis", 1);
}
print '<br>';

print '<strong>$dolibarr_nocsrfcheck</strong>: '.(empty($dolibarr_nocsrfcheck) ? '0' : $dolibarr_nocsrfcheck);
if (!empty($dolibarr_nocsrfcheck)) {
	print ' &nbsp; &nbsp;'.img_picto('', 'error').' '.$langs->trans("IfYouAreOnAProductionSetThis", 0);
} else {
	print ' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 0)</span>';
}
print '<br>';

print '<strong>$dolibarr_main_restrict_ip</strong>: ';
if (empty($dolibarr_main_restrict_ip)) {
	print $langs->trans("None");
	print ' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("StaticIPsOfUsers")).')</span>';
} else {
	print $dolibarr_main_restrict_ip;
}
print '<br>';

print '<strong>$dolibarr_main_restrict_os_commands</strong>: ';
if (empty($dolibarr_main_restrict_os_commands)) {
	print $langs->trans("None");
} else {
	print $dolibarr_main_restrict_os_commands;
}
print ' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", 'mysqldump, mysql, pg_dump, pg_restore, mariadb, mariadb-dump, clamdscan').')</span>';
print '<br>';

if (!getDolGlobalString('SECURITY_DISABLE_TEST_ON_OBFUSCATED_CONF')) {
	print '<strong>$dolibarr_main_db_pass</strong>: ';
	if (!empty($dolibarr_main_db_pass) && empty($dolibarr_main_db_encrypted_pass)) {
		print img_picto('', 'warning').' '.$langs->trans("DatabasePasswordNotObfuscated").' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("SetOptionTo", $langs->transnoentitiesnoconv("MainDbPasswordFileConfEncrypted"), yn(1)).')</span>';
		//print ' <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", $langs->transnoentitiesnoconv("IPsOfUsers")).')</span>';
	} else {
		print img_picto('', 'tick').' '.$langs->trans("DatabasePasswordObfuscated");
	}

	print '<br>';
}

print '<strong>$dolibarr_main_stream_to_disable</strong>: ';
// $arrayofstreamtodisable is defined into filefunc.inc.php
'@phan-var-force string[] $arrayofstreamtodisable';
if (empty($dolibarr_main_stream_to_disable)) {
	print '<span class="opacitymedium">'.$langs->trans("Undefined").' = '.implode(', ', $arrayofstreamtodisable).'</span>';
} else {
	print implode(', ', $dolibarr_main_stream_to_disable);
}
print '<span class="bold"> &nbsp; -> Current PHP streams allowed = </span>';
$arrayofstreams = stream_get_wrappers();
if (!empty($arrayofstreams)) {
	sort($arrayofstreams);
	print '<span class="wordbreak">'.implode(',', $arrayofstreams).'</span>';
	print ' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("TryToKeepOnly", 'file,http,https,php,zip').')</span>'."\n";
}
print '</div>';


/*
if (!empty($dolibarr_main_stream_do_not_disable)) {
	print '<strong>$dolibarr_main_stream_do_not_disable</strong>: ';
	if (empty($dolibarr_main_stream_do_not_disable)) {
		print '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>';
	} else {
		print join(', ', $dolibarr_main_stream_do_not_disable);
	}
	print ' -> PHP stream allowed = ';
	$arrayofstreams = stream_get_wrappers();
	if (!empty($arrayofstreams)) {
		sort($arrayofstreams);
		print (join(',', $arrayofstreams)).' &nbsp; &nbsp; <span class="opacitymedium">('.$langs->trans("RecommendedValueIs", 'Undefined').')</span>'."\n";
	}

	print '<br>';
}
*/

// Menu Home - Setup - Security

print '<br>';
print '<br>';

print load_fiche_titre($langs->trans("Menu").' '.$langs->trans("SecuritySetup"), '', 'folder');

print '<div class="divsection wordbreak">';

print '<strong>'.$langs->trans("UseCaptchaCode").' - Login</strong>: ';
print !getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA') ? '' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA') ? 0 : 1);
print '<br>';
print '<br>';

print '<strong>'.$langs->trans("UseCaptchaCode").' - Ticket</strong>: ';
print !getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_TICKET') ? '' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_TICKET') ? 0 : 1);
print '<br>';
print '<br>';

print '<strong>'.$langs->trans("UseCaptchaCode").' - Thirdparty public contact form</strong>: ';
print !getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_THIRPARTY') ? '' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_THIRPARTY') ? 0 : 1);
print '<br>';
print '<br>';

print '<strong>'.$langs->trans("UseCaptchaCode").' - Membership subscription</strong>: ';
print !getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_MEMBER') ? '' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_MEMBER') ? 0 : 1);
print '<br>';
print '<br>';

$sessiontimeout = ini_get("session.gc_maxlifetime");
if (!getDolGlobalString('MAIN_SESSION_TIMEOUT')) {
	$conf->global->MAIN_SESSION_TIMEOUT = $sessiontimeout;
}
print '<strong>'.$langs->trans("SessionTimeOut").'</strong>';
if (!ini_get("session.gc_probability")) {
	print $form->textwithpicto('', $langs->trans("SessionsPurgedByExternalSystem", ini_get("session.gc_maxlifetime")));
} else {
	print $form->textwithpicto('', $langs->trans("SessionExplanation", ini_get("session.gc_probability"), ini_get("session.gc_divisor"), ini_get("session.gc_maxlifetime")));
}
print ': '.getDolGlobalInt('MAIN_SESSION_TIMEOUT').' '.strtolower($langs->trans("Seconds"));
print '<br><br>';

print '<strong>'.$langs->trans("MaxNumberOfImagesInGetPost").'</strong>: ';
print(getDolGlobalInt('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT') ? img_picto('', 'tick').' ' : '').getDolGlobalInt('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT').' '.strtolower($langs->trans("Images"));
print '<br><br>';

print '<strong>'.$langs->trans("MaxNumberOfPostOnPublicPagesByIP").'</strong>: ';
print(getDolGlobalInt('MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS', 200) ? img_picto('', 'tick').' ' : '').getDolGlobalInt('MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS', 200).' '.strtolower($langs->trans("Posts"));
print '<br><br>';

print '<strong>'.$langs->trans("MaxNumberOfAttachementOnForms").'</strong>: ';
print(getDolGlobalInt('MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS', 10) ? img_picto('', 'tick').' ' : '').getDolGlobalInt("MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS", 10).' '.strtolower($langs->trans("Files"));
print '<br><br>';


// Clear password ?

print '<strong>'.$langs->trans("DoNotStoreClearPassword").'</strong>: ';
print !getDolGlobalString('DATABASE_PWD_ENCRYPTED') ? '' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('DATABASE_PWD_ENCRYPTED') ? 0 : 1);
if (!getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
	print ' <span class="opacitymedium">('.$langs->trans("Recommended").' '.yn(1).')</span>';
}
print '<br>';
print '<br>';


// Mask by default in upload
$umask = getDolGlobalString('MAIN_UMASK');

print '<strong>'.$langs->trans("UMask").'</strong>: ';
if (! in_array($umask, array('600', '660', '0600', '0660'))) {
	print img_warning().' ';
}
print $umask;
if (! in_array($umask, array('600', '660', '0600', '0660'))) {
	print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 0600 | 0660)</span>';
}
print '<br>';
print '<br>';


/* Already into section conf file */
/*
$usepassinconfencrypted = 0;
global $dolibarr_main_db_pass, $dolibarr_main_db_encrypted_pass;
if (preg_match('/crypted:/i', $dolibarr_main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
	$usepassinconfencrypted = 1;
}
print '<strong>'.$langs->trans("MainDbPasswordFileConfEncrypted").'</strong>: ';
print $usepassinconfencrypted ? img_picto('', 'tick').' ' : img_warning().' ';
print yn($usepassinconfencrypted);
if (empty($usepassinconfencrypted)) {
	print ' <span class="opacitymedium">('.$langs->trans("Recommended").' '.yn(1).')</span>';
}
print '<br>';
print '<br>';
*/

/* Password length

// Stored into $tabconf[0] if module generator is "Perso" or specific to the module generator.
$tabConf = explode(";", getDolGlobalString('USER_PASSWORD_PATTERN'));

print '<strong>'.$langs->trans("PasswordLength").'</strong>: ';
print empty($conf->global->DATABASE_PWD_ENCRYPTED) ? '' : img_picto('', 'tick').' ';
print yn(empty($conf->global->DATABASE_PWD_ENCRYPTED) ? 0 : 1);
if (empty($conf->global->DATABASE_PWD_ENCRYPTED)) {
	print ' <span class="opacitymedium">('.$langs->trans("Recommended").' '.yn(1).')</span>';
}
print '<br>';
print '<br>';
*/

print '<strong>'.$langs->trans("AntivirusEnabledOnUpload").'</strong>: ';
print !getDolGlobalString('MAIN_ANTIVIRUS_COMMAND') ? img_warning().' ' : img_picto('', 'tick').' ';
print yn(!getDolGlobalString('MAIN_ANTIVIRUS_COMMAND') ? 0 : 1);
if (!getDolGlobalString('MAIN_ANTIVIRUS_COMMAND')) {
	print ' - <span class="opacitymedium">'.$langs->trans("Recommended").': '.$langs->trans("DefinedAPathForAntivirusCommandIntoSetup", $langs->transnoentitiesnoconv("Home")." - ".$langs->transnoentitiesnoconv("Setup")." - ".$langs->transnoentitiesnoconv("Security")).'</span>';
} else {
	print ' &nbsp; - ' . getDolGlobalString('MAIN_ANTIVIRUS_COMMAND');
	if (defined('MAIN_ANTIVIRUS_COMMAND') && !defined('MAIN_ANTIVIRUS_BYPASS_COMMAND_AND_PARAM')) {
		print ' - <span class="opacitymedium">'.$langs->trans("ValueIsForcedBySystem").'</span>';
	}
}
print '<br>';
print '<br>';

// File extension locked in upload by default

print '<strong>'.$langs->trans("UploadExtensionRestriction").'</strong>: ';
print implode(', ', explode(',', getDolGlobalString('MAIN_FILE_EXTENSION_UPLOAD_RESTRICTION')));
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.implode(', ', getExecutableContent()).')</span>';
print '<br>';
print '<br>';


print '<strong>'.$langs->trans("MAIN_SECURITY_MAXFILESIZE_DOWNLOADED").'</strong> = '.getDolGlobalString('MAIN_SECURITY_MAXFILESIZE_DOWNLOADED', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': < 100000)</span>')."<br>";
print '<br>';



$securityevent = new Events($db);
$eventstolog = $securityevent->eventstolog;

print '<strong>'.$langs->trans("AuditedSecurityEvents").'</strong>: ';
$out = '';
if (!empty($eventstolog) && is_array($eventstolog)) {
	// Loop on each event type
	$i = 0;
	foreach ($eventstolog as $key => $arr) {
		if ($arr['id']) {
			$key = 'MAIN_LOGEVENTS_'.$arr['id'];
			$value = getDolGlobalString($key);
			if ($value) {
				if ($i > 0) {
					$out .= ', ';
				}
				$out .= '<span class="opacitymedium">'.$key.'</span>';
				$i++;
			}
		}
	}
	print $out;
}

if (empty($out)) {
	print img_warning().' '.$langs->trans("NoSecurityEventsAreAduited", $langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Security").' - '.$langs->transnoentities("Audit")).'<br>';
} else {
	$s = $langs->trans("SeeSetupPage", ' {s1}'.$langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Security").' - '.$langs->transnoentities("Audit").'{s2}');
	print ' - '.str_replace('{s2}', '</a>', str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/admin/events.php" target="_blank">'.img_picto('', 'url', 'class="pictofixedwidth"'), $s));
}

print '</div>';

print '<br>';


// Modules/Applications

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("Modules"), '', 'folder');

print '<div class="divsection wordbreak">';

// Module log
print '<strong>'.$langs->trans("Syslog").'</strong>: ';
$test = isModEnabled('syslog');
if (!$test) {
	print img_picto('', 'tick').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	if (getDolGlobalInt('SYSLOG_LEVEL') > LOG_NOTICE) {
		print img_picto('', 'warning').' '.$langs->trans("ModuleActivatedWithTooHighLogLevel", $langs->transnoentities("Syslog"));
	} else {
		print img_picto('', 'tick').' '.$langs->trans("ModuleSyslogActivatedButLevelNotTooVerbose", $langs->transnoentities("Syslog"), getDolGlobalInt('SYSLOG_LEVEL'));
	}
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

print '<br>';

// Module debugbar
print '<strong>'.$langs->trans("DebugBar").'</strong>: ';
$test = isModEnabled('debugbar');
if (!$test) {
	print img_picto('', 'tick').' '.$langs->trans("NotInstalled").' - '.$langs->trans("NotRiskOfLeakWithThis");
} else {
	print img_picto('', 'error').' '.$langs->trans("ModuleActivatedDoNotUseInProduction", $langs->transnoentities("DebugBar"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

// Modules for Payments
$test = isModEnabled('stripe');
if ($test) {
	print '<br>';

	print '<strong>'.$langs->trans("Stripe").'</strong>: ';
	if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		print img_picto('', 'error').' '.$langs->trans("OptionXShouldBeEnabledInModuleY", $langs->transnoentities("SecurityTokenIsUnique"), $langs->transnoentities("Stripe"));
	} else {
		print img_picto('', 'tick').' '.$langs->trans("OptionXIsCorrectlyEnabledInModuleY", $langs->transnoentities("SecurityTokenIsUnique"), $langs->transnoentities("Stripe"));
	}
	print '<br>';
} else {
	$test = isModEnabled('paypal');
	if ($test) {
		print '<br>';

		print '<strong>'.$langs->trans("Paypal").'</strong>: ';
		if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
			print img_picto('', 'error').' '.$langs->trans("OptionXShouldBeEnabledInModuleY", $langs->transnoentities("SecurityTokenIsUnique"), $langs->transnoentities("Paypal"));
		} else {
			print img_picto('', 'tick').' '.$langs->trans("OptionXIsCorrectlyEnabledInModuleY", $langs->transnoentities("SecurityTokenIsUnique"), $langs->transnoentities("Paypal"));
		}
		print '<br>';
	}
}

print '</div>';


// APIs

print '<br>';
print '<br>';
print load_fiche_titre($langs->trans("API"), '', 'folder');

print '<div class="divsection wordbreak">';

if (!isModEnabled('api') && !isModEnabled('webservices')) {
	print $langs->trans("APIsAreNotEnabled");
} else {
	if (isModEnabled('webservices')) {
		print img_picto('', 'warning').' '.$langs->trans('YouEnableDeprecatedWSAPIsUseRESTAPIsInstead')."<br>\n";
		print '<br>';
	}
	if (isModEnabled('api')) {
		print '<strong>API_ENDPOINT_RULES</strong> = '.getDolGlobalString('API_ENDPOINT_RULES', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Example").': login:0,users:0,setup:1,status:1,tickets:1,...)</span>')."<br>\n";
	}
}

print '<br>';

print '<strong>API_DISABLE_LOGIN_API</strong> = '.getDolGlobalString('API_DISABLE_LOGIN_API', '0').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 1)</span><br>';

print '</div>';


print '<br>';
print '<br>';


// Other setup

print load_fiche_titre($langs->trans("OtherSetup"), '', 'folder');

print '<div class="divsection wordbreak">';

print '<strong>MAIN_ALLOW_SVG_FILES_AS_IMAGES</strong> = '.getDolGlobalString('MAIN_ALLOW_SVG_FILES_AS_IMAGES', '0').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 0)</span><br>';
print '<br>';

print '<strong>MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE</strong> = '.getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 1)</span><br>';
print '<br>';

print '<strong>MAIN_SECURITY_ANTI_SSRF_SERVER_IP</strong> = '.getDolGlobalString('MAIN_SECURITY_ANTI_SSRF_SERVER_IP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span> &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': List of static IPs of server separated with coma - '.$langs->trans("Note").': common loopback ip like 127.*.*.*, [::1] are already added)</span>')."<br>";
print '<br>';

print '<strong>MAIN_SECURITY_CSRF_WITH_TOKEN</strong> = '.getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 2)</span>'."<br>";

print '</div>';

print '<br>';
print '<br>';



// Database encryption

print load_fiche_titre($langs->trans("DatabaseEncryption"), '', 'folder');

print '<div class="divsection wordbreak">';

//print '<strong>'.$langs->trans("PasswordEncryption").'</strong>: ';
print '<strong>'.$langs->trans("AlgorithmFor", $langs->transnoentitiesnoconv("Passwords"));
print $form->textwithpicto('', 'non reversible encryption, defined into MAIN_SECURITY_HASH_ALGO');
print '</strong>: '.getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>')." &nbsp; ";
if (!getDolGlobalString('MAIN_SECURITY_HASH_ALGO')) {
	print '<span class="opacitymedium"> &nbsp; &nbsp; (If unset: \'md5\')</span>';
}
if (getDolGlobalString('MAIN_SECURITY_HASH_ALGO') != 'password_hash') {
	print '<br><strong>MAIN_SECURITY_SALT</strong> = '.getDolGlobalString('MAIN_SECURITY_SALT', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').'<br>';
} else {
	print '<span class="opacitymedium">('.$langs->trans("Recommended").': password_hash)</span>';
	print '<br>';
}
if (getDolGlobalString('MAIN_SECURITY_HASH_ALGO') != 'password_hash') {
	print '<div class="info">The recommended value for MAIN_SECURITY_HASH_ALGO is now \'password_hash\' but setting it now will make ALL existing passwords of all users not valid, so update is not possible.<br>';
	print 'If you really want to switch, you must:<br>';
	print '- Go on home - setup - other and add constant MAIN_SECURITY_HASH_ALGO to value \'password_hash\'<br>';
	print '- In same session, WITHOUT LOGGING OUT, go into your admin user record and set a new password<br>';
	print '- You can now logout and login with this new password. You must now reset password of all other users.<br>';
	print '</div>';
}
print '<br>';

$action = GETPOST('action');
$exampletodecrypt = GETPOST('exampletodecrypt', 'password');

print '<strong>'.$langs->trans("AlgorithmFor", $langs->transnoentitiesnoconv("SensitiveData"));
print $form->textwithpicto('', 'reversible encryption done with dolEncrypt/dolDecrypt');
print '</strong>: '.constant('MAIN_SECURITY_REVERSIBLE_ALGO').' with key defined into conf.php file in $dolibarr_main_dolcrypt_key (or $dolibarr_main_instance_unique_id)<br>';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="action" value="doldecrypt">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="page_y" value="">';
print $langs->trans("ToolToDecryptAString").': ';
print '<input type="text" class="minwidth300" name="exampletodecrypt" placeholder="dolcrypt:ALGOXXXX:ABCDFEF1234" value="'.$exampletodecrypt.'">';
print '<input type="submit" class="reposition button small smallpaddingimp" name="submit" value="'.$langs->transnoentitiesnoconv("Decrypt").'">';
if ($action == 'doldecrypt' && $user->admin && $exampletodecrypt) {
	usleep(200);
	print ' => <textarea rows="'.ROWS_1.'" class="valignmiddle">'.dolPrintHTMLForTextArea(dolDecrypt($exampletodecrypt)).'</textarea>';
}
print '</form>';

print '</div>';


print '<br>';
print '<br>';


// Websites

if (isModEnabled('website')) {
	print load_fiche_titre($langs->trans("Website"), '', 'website');
	print '<div class="divsection wordbreak">';

	$sql = "SELECT w.rowid as id, w.ref";
	$sql .= " FROM ".MAIN_DB_PREFIX."website as w";
	$sql .= " WHERE w.entity = ".((int) $conf->entity);
	$sql .= " AND w.status = 1";

	$resql = $db->query($sql);
	if ($resql) {
		$num_rows = $db->num_rows($resql);
		if ($num_rows > 0) {
			$i = 0;
			while ($obj = $db->fetch_object($resql)) {
				print "<strong>".$langs->trans("RefWebsite").": ".$obj->ref."</strong>";
				print'<br><br>';
				print '<strong>WEBSITE_'.$obj->id.'_SECURITY_FORCERP</strong> = '.getDolGlobalString('WEBSITE_'.$obj->id.'_SECURITY_FORCERP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
				print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "';
				$examplecsprule = "default-src 'self' 'unsafe-inline' matomo.".getDomainFromURL($_SERVER["SERVER_NAME"], 1)." *.transifex.net *.transifex.com *.cloudflare.com *.cloudflareinsights.com *.google-analytics.com *.googletagmanager.com *.google.com *.gstatic.com *.googleapis.com *.googleadservices.com *.ads-twitter.com *.doubleclick.net; frame-ancestors 'self'; object-src *.youtube.com; frame-src 'self' *.twitter.com *.facebook.com *.youtube.com; img-src * data:;";
				print $examplecsprule;
				print '")</span><br>';

				print '<strong>WEBSITE_'.$obj->id.'_SECURITY_FORCECSP</strong> = '.getDolGlobalString('WEBSITE_'.$obj->id.'_SECURITY_FORCECSP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
				print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "';
				$examplecsprule = "default-src 'self' 'unsafe-inline' matomo.".getDomainFromURL($_SERVER["SERVER_NAME"], 1)." *.transifex.net *.transifex.com *.cloudflare.com *.cloudflareinsights.com *.google-analytics.com *.googletagmanager.com *.google.com *.gstatic.com *.googleapis.com *.googleadservices.com *.ads-twitter.com *.doubleclick.net; frame-ancestors 'self'; object-src *.youtube.com; frame-src 'self' *.twitter.com *.facebook.com *.youtube.com; img-src * data:;";
				print $examplecsprule;
				print '")</span><br>';

				print '<strong>WEBSITE_'.$obj->id.'_SECURITY_FORCERP</strong> = '.getDolGlobalString('WEBSITE_'.$obj->id.'_SECURITY_FORCERP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("Undefined").'="strict-origin-when-cross-origin" '.$langs->trans("or").' "same-origin"=more secured)</span><br>';

				print '<strong>WEBSITE_'.$obj->id.'_SECURITY_FORCESTS</strong> = '.getDolGlobalString('WEBSITE_'.$obj->id.'_SECURITY_FORCESTS', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").": \"max-age=31536000; includeSubDomains\")</span><br>";

				print '<strong>WEBSITE_'.$obj->id.'_SECURITY_FORCEPP</strong> = '.getDolGlobalString('WEBSITE_'.$obj->id.'_SECURITY_FORCEPP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").": \"camera=(), microphone=(), geolocation=*\")</span><br>";
				$i++;
				if ($i != $num_rows) {
					print '<br>';
				}
			}
		} else {
			print '<span class="opacity">'.$langs->trans("NoWebsite").'</span>';
		}
	} else {
		dol_print_error($db);
	}
	print '</div>';


	print '<br>';
}


// Other - experimental

print load_fiche_titre($langs->trans("OtherSetup").' ('.$langs->trans("Experimental").')', '', 'folder');

print '<div class="divsection wordbreak">';
print '<strong>MAIN_EXEC_USE_POPEN</strong> = ';
if (!getDolGlobalString('MAIN_EXEC_USE_POPEN')) {
	print '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>';
} else {
	print getDolGlobalString('MAIN_EXEC_USE_POPEN');
}
if ($execmethod == 1) {
	print '<span class="opacitymedium"> &nbsp; &nbsp; "exec" PHP method will be used for shell commands';
	print ' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 1)';
	print '</span>';
}
if ($execmethod == 2) {
	print '<span class="opacitymedium"> &nbsp; &nbsp; "popen" PHP method will be used for shell commands';
	print ' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 1)';
	print '</span>';
}
print '<br>';
print '<br>';

print '<strong>MAIN_RESTRICTHTML_ONLY_VALID_HTML</strong> = '.(getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML') ? '1' : '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").": 1 - does not work on HTML5 with some old libxml libs)</span>";

// Test compatibility of MAIN_RESTRICTHTML_ONLY_VALID_HTML
$savMAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = getDolGlobalString('MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES');
$savMAIN_RESTRICTHTML_ONLY_VALID_HTML = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML');
$savMAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY');
$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;
$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
$result = dol_htmlwithnojs('<img onerror<=alert(document.domain)> src=>0xbeefed');
$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = $savMAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES;
$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $savMAIN_RESTRICTHTML_ONLY_VALID_HTML;
$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $savMAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY;

if ($result == 'InvalidHTMLStringCantBeCleaned') {
	print ' &nbsp; - &nbsp; '.img_warning().' Your libxml seems to old to work correctly with this option. Disable it !';
} else {
	print ' &nbsp; - &nbsp; Test of compatibility with this option seems ok';
}
print '<br>';

print '<br>';

print '<strong>MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY</strong> = '.(getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY') ? '1' : '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': 1)</span> &nbsp; - &nbsp; Module "php-tidy" must be enabled (currently: '.((extension_loaded('tidy') && class_exists("tidy")) ? 'Enabled' : img_picto('', 'warning').' Not available').")";
if (extension_loaded('tidy') && class_exists("tidy")) {
	// Test compatibility of MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY
	$savMAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = getDolGlobalString('MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES');
	$savMAIN_RESTRICTHTML_ONLY_VALID_HTML = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML');
	$savMAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY');
	$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;
	$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
	$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
	$result = dol_htmlwithnojs('<img onerror<=alert(document.domain)> src=>0xbeefed');
	$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = $savMAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES;
	$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $savMAIN_RESTRICTHTML_ONLY_VALID_HTML;
	$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $savMAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY;

	if ($result == 'InvalidHTMLStringCantBeCleaned') {
		print ' &nbsp; - &nbsp; '.img_warning().' Your libxml seems to old to work correctly with this option. Disable it !';
	} else {
		print ' &nbsp; - &nbsp; Test of compatibility with this option seems ok';
	}
}
print '<br>';

print '<br>';

print '<strong>MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES</strong> = '.(getDolGlobalString('MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES') ? '1' : '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").": 1 - does not work on HTML5 with some old libxml libs)</span><br>";
print '<br>';

// MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1, disallow url links except if on /medias
// MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 2, disallow all external urls link
print '<strong>MAIN_DISALLOW_URL_INTO_DESCRIPTIONS</strong> = '.getDolGlobalString('MAIN_DISALLOW_URL_INTO_DESCRIPTIONS', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': 1=only local links allowed or 2=no links at all)</span>')."<br>";
print '<br>';

print '<strong>MAIN_ALLOW_SVG_FILES_AS_EXTERNAL_LINKS</strong> = '.getDolGlobalString('MAIN_ALLOW_SVG_FILES_AS_EXTERNAL_LINKS', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 0)</span>')."<br>";
print '<br>';

print '<strong>MAIN_DISALLOW_STRING_OBFUSCATION_IN_DOL_EVAL</strong> = '.(getDolGlobalString('MAIN_DISALLOW_STRING_OBFUSCATION_IN_DOL_EVAL') ? '1' : '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").": 1 - may break use of concatenation function like . or dol_concatdesc into extra fields conditions or formula)</span><br>";
print '<br>';


// MAIN_ALLOW_LOCAL_LINKS_AS_EXTERNAL_LINKS

print '<strong>MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL</strong> = '.getDolGlobalString('MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 0)</span>')."<br>";
print '<br>';

print '<strong>MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED</strong> = '.getDolGlobalString('MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED', '<span class="opacitymedium">'.$langs->trans("Undefined").' &nbsp; ('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or").' 0)</span>')."<br>";
print '<br>';

$examplecsprule = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com *.dolistore.com *.githubusercontent.com";
print '<strong>MAIN_SECURITY_FORCECSPRO</strong> = '.getDolGlobalString('MAIN_SECURITY_FORCECSPRO', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "'.$examplecsprule.'")</span><br>';
print '<br>';

$examplecsprule = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com *.dolistore.com *.githubusercontent.com";
print '<strong>MAIN_SECURITY_FORCECSP</strong> = '.getDolGlobalString('MAIN_SECURITY_FORCECSP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "'.$examplecsprule.'")</span><br>';
print '<br>';

print '<strong>MAIN_SECURITY_FORCERP</strong> = '.getDolGlobalString('MAIN_SECURITY_FORCERP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("Undefined").' '.$langs->trans("or")." \"strict-origin-when-cross-origin\" so browser doesn't send any referrer when going into another web site domain)</span><br>";
print '<br>';

print '<strong>MAIN_SECURITY_FORCE_ACCESS_CONTROL_ALLOW_ORIGIN</strong> = '.getDolGlobalString('MAIN_SECURITY_FORCE_ACCESS_CONTROL_ALLOW_ORIGIN', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").": 1)</span><br>";


/* Removed, already in the dedicated section Websites.
print '<br>';

print '<strong>WEBSITE_MAIN_SECURITY_FORCECSPRO</strong> = '.getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCECSPRO', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "';
$examplecsprule = "default-src 'self' 'unsafe-inline' matomo.".getDomainFromURL($_SERVER["SERVER_NAME"], 1)." *.transifex.net *.transifex.com *.cloudflare.com *.cloudflareinsights.com *.google-analytics.com *.googletagmanager.com *.google.com *.gstatic.com *.googleapis.com *.googleadservices.com *.ads-twitter.com *.doubleclick.net; frame-ancestors 'self'; object-src *.youtube.com; frame-src 'self' *.twitter.com *.facebook.com *.youtube.com; img-src * data:;";
print $examplecsprule;
print '")</span><br>';
print '<br>';

print '<strong>WEBSITE_MAIN_SECURITY_FORCECSP</strong> = '.getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCECSP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>');
print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").': "';
$examplecsprule = "default-src 'self' 'unsafe-inline' matomo.".getDomainFromURL($_SERVER["SERVER_NAME"], 1)." *.transifex.net *.transifex.com *.cloudflare.com *.cloudflareinsights.com *.google-analytics.com *.googletagmanager.com *.google.com *.gstatic.com *.googleapis.com *.googleadservices.com *.ads-twitter.com *.doubleclick.net; frame-ancestors 'self'; object-src *.youtube.com; frame-src 'self' *.twitter.com *.facebook.com *.youtube.com; img-src * data:;";
print $examplecsprule;
print '")</span><br>';
print '<br>';

print '<strong>WEBSITE_MAIN_SECURITY_FORCERP</strong> = '.getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCERP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Recommended").': '.$langs->trans("Undefined").'="strict-origin-when-cross-origin" '.$langs->trans("or").' "same-origin"=more secured)</span><br>';
print '<br>';

print '<strong>WEBSITE_MAIN_SECURITY_FORCESTS</strong> = '.getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCESTS', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").": \"max-age=31536000; includeSubDomains\")</span><br>";
print '<br>';

print '<strong>WEBSITE_MAIN_SECURITY_FORCEPP</strong> = '.getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCEPP', '<span class="opacitymedium">'.$langs->trans("Undefined").'</span>').' &nbsp; <span class="opacitymedium">('.$langs->trans("Example").": \"camera=(), microphone=(), geolocation=*\")</span><br>";
*/

print '</div>';


print '<br>';
print '<br>';


print load_fiche_titre($langs->trans("LimitsAndMitigation"), '', 'folder');

print '<div class="divsection wordbreak">';

print '<span class="opacitymedium">';
print $langs->trans("RecommendMitigationOnURL").'<br>';
print '</span>';

print '<br>';
$urlexamplebase = 'https://github.com/Dolibarr/dolibarr/blob/develop/dev/setup/fail2ban/filter.d/';
print '<span class="fas fa-shield-alt"></span> Login or API authentication (see <a target="_blank" rel="noopener" href="'.$urlexamplebase.'web-dolibarr-rulesbruteforce.conf">fail2ban example on GitHub</a>)<br>';
print '<span class="fas fa-shield-alt"></span> '.DOL_URL_ROOT.'/passwordforgotten.php (see <a target="_blank" rel="noopener" href="'.$urlexamplebase.'web-dolibarr-rulespassforgotten.conf">fail2ban example on GitHub</a>)<br>';
print '<span class="fas fa-shield-alt"></span> '.DOL_URL_ROOT.'/public/* (see <a target="_blank" rel="noopener" href="'.$urlexamplebase.'web-dolibarr-limitpublic.conf">fail2ban example on GitHub</a>)<br>';
print '<br>';
$urlexamplebase = 'https://github.com/Dolibarr/dolibarr/blob/develop/dev/setup/apache/';
print '<span class="fas fa-shield-alt"></span> You can also protect the application using a HTTP Basic authentication layer (see <a target="_blank" rel="noopener" href="'.$urlexamplebase.'virtualhost">apache2 virtualhost example on GitHub</a>)<br>';

print '</div>';

// End of page
llxFooter();
$db->close();
