<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/filefunc.inc.php
 * 	\ingroup	core
 *  \brief      File that include conf.php file and commons lib like functions.lib.php
 */

/**
 * @var ?string $dolibarr_font_DOL_DEFAULT_TTF
 * @var ?string $dolibarr_font_DOL_DEFAULT_TTF_BOLD
 * @var ?string $dolibarr_js_CKEDITOR
 * @var ?string $dolibarr_js_JQUERY
 * @var ?string $dolibarr_js_JQUERY_UI
 * @var ?string $dolibarr_lib_NUSOAP_PATH
 * @var ?string $dolibarr_lib_ODTPHP_PATH
 * @var ?string $dolibarr_lib_ODTPHP_PATHTOPCLZIP
 * @var ?string $dolibarr_lib_PHPEXCELNEW_PATH
 * @var ?string $dolibarr_lib_TCPDF_PATH
 * @var ?string $dolibarr_lib_TCPDI_PATH
 */
'
@phan-var-force ?string $dolibarr_font_DOL_DEFAULT_TTF
@phan-var-force ?string $dolibarr_font_DOL_DEFAULT_TTF_BOLD
@phan-var-force ?string $dolibarr_js_CKEDITOR
@phan-var-force ?string $dolibarr_js_JQUERY
@phan-var-force ?string $dolibarr_js_JQUERY_UI
@phan-var-force ?string $dolibarr_lib_NUSOAP_PATH
@phan-var-force ?string $dolibarr_lib_ODTPHP_PATH
@phan-var-force ?string $dolibarr_lib_ODTPHP_PATHTOPCLZIP
@phan-var-force ?string $dolibarr_lib_PHPEXCELNEW_PATH
@phan-var-force ?string $dolibarr_lib_TCPDF_PATH
@phan-var-force ?string $dolibarr_lib_TCPDI_PATH
';

if (!defined('DOL_APPLICATION_TITLE')) {
	define('DOL_APPLICATION_TITLE', 'Dolibarr');
}
if (!defined('DOL_VERSION')) {
	define('DOL_VERSION', '22.0.0'); // a.b.c-alpha, a.b.c-beta, a.b.c-rcX or a.b.c
}

if (!defined('EURO')) {
	define('EURO', chr(128));
}

// Define syslog constants
if (!defined('LOG_DEBUG')) {
	if (!function_exists("syslog")) {
		// For PHP versions without syslog (like running on Windows OS)
		define('LOG_EMERG', 0);
		define('LOG_ALERT', 1);
		define('LOG_CRIT', 2);
		define('LOG_ERR', 3);
		define('LOG_WARNING', 4);
		define('LOG_NOTICE', 5);
		define('LOG_INFO', 6);
		define('LOG_DEBUG', 7);
	}
}

// End of common declaration part
if (defined('DOL_INC_FOR_VERSION_ERROR')) {
	return;
}


/**
 * Replace session_start()
 *
 * @return void
 */
function dol_session_start()
{
	session_start();
}

/**
 * Replace session_regenerate_id()
 *
 * @return bool True if success, false if failed
 */
function dol_session_regenerate_id()
{
	return session_regenerate_id();
}

/**
 * Destroy and recreate a new session without losing content.
 * Not used yet.
 *
 * @param  string	$sessionname	Session name
 * @return void
 */
function dol_session_rotate($sessionname = '')
{
	$oldsessionid = session_id();

	// Backup the current session
	$session_backup = $_SESSION;

	// Set current session to expire in 1 minute
	$_SESSION['OBSOLETE'] = true;
	$_SESSION['EXPIRES'] = time() + 60;

	// Close the current session
	session_write_close();

	// Set a new session id and start the session
	session_name($sessionname);
	dol_session_start();

	// Restore the previous session backup
	$_SESSION = $session_backup;

	// Clean up
	unset($session_backup);
	unset($_SESSION['OBSOLETE']);
	unset($_SESSION['EXPIRES']);

	$newsessionid = session_id();
	//var_dump("oldsessionid=".$oldsessionid." - newsessionid=".$newsessionid);
}



// Define localization of conf file
// --- Start of part replaced by Dolibarr packager makepack-dolibarr
$conffile = "conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/dolibarr/conf.php";
//$conffiletoshow = "/etc/dolibarr/conf.php";


// Include configuration
// --- End of part replaced by Dolibarr packager makepack-dolibarr

// Include configuration
// @phpstan-ignore-next-line
$result = @include_once $conffile; // Keep @ because with some error reporting mode, this breaks the redirect done when file is not found

/**
 * @var ?string $dolibarr_main_stream_to_disable
 * @var ?string $dolibarr_main_instance_unique_id
 * @var ?string $dolibarr_strict_mode
 * @var ?string $dolibarr_main_data_root
 * @var ?string $dolibarr_main_db_prefix
 * @var ?string $dolibarr_main_db_root
 * @var ?string $dolibarr_main_db_user
 * @var ?string $dolibarr_main_db_pass
 * @var ?string $dolibarr_main_db_port
 * @var ?string $dolibarr_main_db_type
 * @var ?string $dolibarr_main_db_encryption
 * @var ?string $dolibarr_main_db_encrypted_pass
 * @var ?string $dolibarr_main_prod
 * @var ?string $dolibarr_main_dolcrypt_key
 */

// Disable some not used PHP stream
$listofwrappers = stream_get_wrappers();
// We need '.phar' for geoip2. TODO Replace phar in geoip with exploded files so we can disable phar by default.
// phar stream does not auto unserialize content (possible code execution) since PHP 8.1
// zip stream is necessary by excel import module
$arrayofstreamtodisable = array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zlib');
if (!empty($dolibarr_main_stream_to_disable) && is_array($dolibarr_main_stream_to_disable)) {
	$arrayofstreamtodisable = $dolibarr_main_stream_to_disable;
}
foreach ($arrayofstreamtodisable as $streamtodisable) {
	if (!empty($listofwrappers) && in_array($streamtodisable, $listofwrappers)) {
		/*if (!empty($dolibarr_main_stream_do_not_disable) && is_array($dolibarr_main_stream_do_not_disable) && in_array($streamtodisable, $dolibarr_main_stream_do_not_disable)) {
			continue;	// We do not disable this stream
		}*/
		stream_wrapper_unregister($streamtodisable);
	}
}

if (!$result && !empty($_SERVER["GATEWAY_INTERFACE"])) {    // If install not done and we are in a web session
	if (!empty($_SERVER["CONTEXT_PREFIX"])) {    // CONTEXT_PREFIX and CONTEXT_DOCUMENT_ROOT are not defined on all apache versions
		$path = $_SERVER["CONTEXT_PREFIX"]; // example '/dolibarr/' when using an apache alias.
		if (!preg_match('/\/$/', $path)) {
			$path .= '/';
		}
	} elseif (preg_match('/index\.php/', $_SERVER['PHP_SELF'])) {
		// When we ask index.php, we MUST BE SURE that $path is '' at the end. This is required to make install process
		// when using apache alias like '/dolibarr/' that point to htdocs.
		// Note: If calling page was an index.php not into htdocs (ie comm/index.php, ...), then this redirect will fails,
		// but we don't want to change this because when URL is correct, we must be sure the redirect to install/index.php will be correct.
		$path = '';
	} else {
		// If what we look is not index.php, we can try to guess location of root. May not work all the time.
		// There is no real solution, because the only way to know the apache url relative path is to have it into conf file.
		// If it fails to find correct $path, then only solution is to ask user to enter the correct URL to index.php or install/index.php
		$TDir = explode('/', $_SERVER['PHP_SELF']);
		$path = '';
		$i = count($TDir);
		while ($i--) {
			if (empty($TDir[$i]) || $TDir[$i] == 'htdocs') {
				break;
			}
			if ($TDir[$i] == 'dolibarr') {
				break;
			}
			if (substr($TDir[$i], -4, 4) == '.php') {
				continue;
			}

			$path .= '../';
		}
	}

	header("Location: ".$path."install/index.php");

	/*
	print '<br><center>';
	print 'The conf/conf.php file was not found or is not readable by the web server. If this is your first access, <a href="'.$path.'install/index.php">click here to start the Dolibarr installation process</a> to create it...';
	print '</center><br>';
	*/

	exit;
}

// Force PHP error_reporting setup (Dolibarr may report warning without this)
if (version_compare(phpversion(), '8.4', '<')) {
	if (!empty($dolibarr_strict_mode)) {
		error_reporting(E_ALL | E_STRICT);
	} else {
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
	}
} else {
	// E_STRICT is deprecated since PHP 8.4
	if (!empty($dolibarr_strict_mode)) {
		error_reporting(E_ALL);
	} else {
		error_reporting(E_ALL & ~(E_NOTICE | E_DEPRECATED));
	}
}

// Disable php display errors
if (!empty($dolibarr_main_prod)) {
	ini_set('display_errors', 'Off');
}

// Clean parameters
$dolibarr_main_data_root = (empty($dolibarr_main_data_root) ? '' : trim($dolibarr_main_data_root));
$dolibarr_main_url_root = trim(preg_replace('/\/+$/', '', empty($dolibarr_main_url_root) ? '' : $dolibarr_main_url_root));
$dolibarr_main_url_root_alt = (empty($dolibarr_main_url_root_alt) ? '' : trim($dolibarr_main_url_root_alt));
$dolibarr_main_document_root = (empty($dolibarr_main_document_root) ? '' : trim($dolibarr_main_document_root));
$dolibarr_main_document_root_alt = (empty($dolibarr_main_document_root_alt) ? '' : trim($dolibarr_main_document_root_alt));

if (!isset($dolibarr_main_db_port)) {
	$dolibarr_main_db_port = 3306; // For compatibility with old configs, if not defined, we take 'mysql' type
}
if (empty($dolibarr_main_db_type)) {
	$dolibarr_main_db_type = 'mysqli'; // For compatibility with old configs, if not defined, we take 'mysql' type
}

// Mysql driver support has been removed in favor of mysqli
if ($dolibarr_main_db_type == 'mysql') {
	$dolibarr_main_db_type = 'mysqli';
}
if (empty($dolibarr_main_db_prefix)) {
	$dolibarr_main_db_prefix = 'llx_';
}
if (empty($dolibarr_main_db_character_set)) {
	$dolibarr_main_db_character_set = ($dolibarr_main_db_type == 'mysqli' ? 'utf8' : ''); // Old installation
}
if (empty($dolibarr_main_db_collation)) {
	$dolibarr_main_db_collation = ($dolibarr_main_db_type == 'mysqli' ? 'utf8_unicode_ci' : ''); // Old installation
}
if (empty($dolibarr_main_db_encryption)) {
	$dolibarr_main_db_encryption = 0;
}
if (empty($dolibarr_main_db_cryptkey)) {
	$dolibarr_main_db_cryptkey = '';
}
if (empty($dolibarr_main_limit_users)) {
	$dolibarr_main_limit_users = 0;
}
if (empty($dolibarr_mailing_limit_sendbyweb)) {
	$dolibarr_mailing_limit_sendbyweb = 0;
}
if (empty($dolibarr_mailing_limit_sendbycli)) {
	$dolibarr_mailing_limit_sendbycli = 0;
}
if (empty($dolibarr_mailing_limit_sendbyday)) {
	$dolibarr_mailing_limit_sendbyday = 0;
}
if (empty($dolibarr_strict_mode)) {
	$dolibarr_strict_mode = 0; // For debug in php strict mode
}

define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root); // Filesystem core php (htdocs)

if (!file_exists(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php")) {
	print "Error: Dolibarr config file content seems to be not correctly defined (file ".DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php not found).<br>\n";
	print "Please run dolibarr setup by calling page <b>/install</b>.<br>\n";
	exit(1);
}


// Included by default (must be before the CSRF check so wa can use the dol_syslog)
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
//print memory_get_usage();


// Security: CSRF protection
// This test check if referrer ($_SERVER['HTTP_REFERER']) is same web site than Dolibarr ($_SERVER['HTTP_HOST'])
// when we post forms (we allow GET and HEAD to accept direct link from a particular page).
// Note about $_SERVER[HTTP_HOST/SERVER_NAME]: http://shiflett.org/blog/2006/mar/server-name-versus-http-host
// See also CSRF protections done into main.inc.php
if (!defined('NOCSRFCHECK') && isset($dolibarr_nocsrfcheck) && $dolibarr_nocsrfcheck == 1) {    // If $dolibarr_nocsrfcheck is 0, there is a strict CSRF test with token in main
	if (!empty($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')) && !empty($_SERVER['HTTP_HOST'])) {
		$csrfattack = false;
		if (empty($_SERVER['HTTP_REFERER'])) {
			$csrfattack = true; // An evil browser was used
		} else {
			$tmpa = parse_url($_SERVER['HTTP_HOST']);
			$tmpb = parse_url($_SERVER['HTTP_REFERER']);
			if ((empty($tmpa['host']) ? $tmpa['path'] : $tmpa['host']) != (empty($tmpb['host']) ? $tmpb['path'] : $tmpb['host'])) {
				$csrfattack = true;
			}
		}
		if ($csrfattack) {
			//print 'NOCSRFCHECK='.defined('NOCSRFCHECK').' REQUEST_METHOD='.$_SERVER['REQUEST_METHOD'].' HTTP_HOST='.$_SERVER['HTTP_HOST'].' HTTP_REFERER='.$_SERVER['HTTP_REFERER'];
			// Note: We can't use dol_escape_htmltag here to escape output because lib functions.lib.ph is not yet loaded.
			dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused by CSRF protection (Bad referrer).", LOG_WARNING);
			print "Access refused by CSRF protection in main.inc.php. Referrer of form (".htmlentities(empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'], ENT_COMPAT, 'UTF-8').") is outside the server that serve this page (with method = ".htmlentities($_SERVER['REQUEST_METHOD'], ENT_COMPAT, 'UTF-8').").\n";
			print "If you access your server behind a proxy using url rewriting, you might check that all HTTP headers are propagated (or add the line \$dolibarr_nocsrfcheck=1 into your conf.php file to remove this security check).\n";
			die;
		}
	}
	// Another test is done later on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on.
}
if (empty($dolibarr_main_db_host) && !defined('NOREQUIREDB')) {
	print '<div class="center">Dolibarr setup is not yet complete.<br><br>'."\n";
	print '<a href="install/index.php">Click here to finish Dolibarr install process</a> ...</div>'."\n";
	die;
}
if (empty($dolibarr_main_url_root) && !defined('NOREQUIREVIRTUALURL')) {
	print 'Value for parameter \'dolibarr_main_url_root\' is not defined in your \'htdocs\conf\conf.php\' file.<br>'."\n";
	print 'You must add this parameter with your full Dolibarr root Url (Example: http://myvirtualdomain/ or http://mydomain/mydolibarrurl/)'."\n";
	die;
}

if (empty($dolibarr_main_document_root_alt)) {
	$dolibarr_main_document_root_alt = $dolibarr_main_document_root.'/custom';
}

if (empty($dolibarr_main_data_root)) {
	// If directory not defined, we use the default hardcoded value
	$dolibarr_main_data_root = str_replace("/htdocs", "", $dolibarr_main_document_root);
	$dolibarr_main_data_root .= "/documents";
}

// Define some constants
define('DOL_CLASS_PATH', 'class/'); // Filesystem path to class dir (defined only for some code that want to be compatible with old versions without this parameter)
define('DOL_DATA_ROOT', $dolibarr_main_data_root); // Filesystem data (documents)
// Try to autodetect DOL_MAIN_URL_ROOT and DOL_URL_ROOT when root is not directly the main domain.
// Note: autodetect works only in case 1, 2, 3 and 4 of phpunit test CoreTest.php. For case 5, 6, only setting value into conf.php will works.
$tmp = '';
$found = 0;
$real_dolibarr_main_document_root = str_replace('\\', '/', realpath($dolibarr_main_document_root)); // A) Value found into config file, to say where are store htdocs files. Ex: C:/xxx/dolibarr, C:/xxx/dolibarr/htdocs
if (!empty($_SERVER["DOCUMENT_ROOT"])) {
	$pathroot = $_SERVER["DOCUMENT_ROOT"]; // B) Value reported by web server setup (not defined on CLI mode), to say where is root of web server instance. Ex: C:/xxx/dolibarr, C:/xxx/dolibarr/htdocs
} else {
	$pathroot = 'NOTDEFINED';
}
$paths = explode('/', str_replace('\\', '/', $_SERVER["SCRIPT_NAME"])); // C) Value reported by web server, to say full path on filesystem of a file. Ex: /dolibarr/htdocs/admin/system/phpinfo.php
// Try to detect if $_SERVER["DOCUMENT_ROOT"]+start of $_SERVER["SCRIPT_NAME"] is $dolibarr_main_document_root. If yes, relative url to add before dol files is this start part.
$concatpath = '';
$tmp3 = '';
foreach ($paths as $tmppath) {	// We check to find (B+start of C)=A
	if (empty($tmppath)) {
		continue;
	}
	$concatpath .= '/'.$tmppath;
	//if ($tmppath) $concatpath.='/'.$tmppath;
	//print $_SERVER["SCRIPT_NAME"].'-'.$pathroot.'-'.$concatpath.'-'.$real_dolibarr_main_document_root.'-'.realpath($pathroot.$concatpath).'<br>';
	if ($real_dolibarr_main_document_root == @realpath($pathroot.$concatpath)) {    // @ avoid warning when safe_mode is on.
		//print "Found relative url = ".$concatpath;
		$tmp3 = $concatpath;
		$found = 1;
		break;
	}
	//else print "Not found yet for concatpath=".$concatpath."<br>\n";
}
//print "found=".$found." dolibarr_main_url_root=".$dolibarr_main_url_root."\n";
if (!$found) {
	// There is no subdir that compose the main url root or autodetect fails (Ie: when using apache alias that point outside default DOCUMENT_ROOT).
	$tmp = $dolibarr_main_url_root;
} else {
	$tmp = 'http'.((!isHTTPS() && (empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] != 443)) ? '' : 's').'://'.$_SERVER["SERVER_NAME"].((empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] == 80 || $_SERVER["SERVER_PORT"] == 443) ? '' : ':'.$_SERVER["SERVER_PORT"]).($tmp3 ? (preg_match('/^\//', $tmp3) ? '' : '/').$tmp3 : '');
}

//print "tmp1=".$tmp1." tmp2=".$tmp2." tmp3=".$tmp3." tmp=".$tmp."\n";
if (!empty($dolibarr_main_force_https)) {
	$tmp = preg_replace('/^http:/i', 'https:', $tmp);
}
define('DOL_MAIN_URL_ROOT', $tmp); // URL absolute root (https://sss/dolibarr, ...)
$uri = preg_replace('/^http(s?):\/\//i', '', constant('DOL_MAIN_URL_ROOT')); // $uri contains url without http*
$suburi = strstr($uri, '/'); // $suburi contains url without domain:port
if (empty($suburi) || $suburi === '/') {
	$suburi = ''; // If $suburi is null or /, it is now ''
}
if (!defined('DOL_URL_ROOT')) {
	define('DOL_URL_ROOT', $suburi); // URL relative root ('', '/dolibarr', ...)
}
//print DOL_MAIN_URL_ROOT.'-'.DOL_URL_ROOT."\n";

// Define prefix MAIN_DB_PREFIX
define('MAIN_DB_PREFIX', $dolibarr_main_db_prefix);


/*
 * Define PATH to external libraries
 * To use other version than embedded libraries, define here constant to path. Use '' to use include class path autodetect.
 */
// Path to root libraries
if (!defined('TCPDF_PATH')) {
	define('TCPDF_PATH', (empty($dolibarr_lib_TCPDF_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/' : $dolibarr_lib_TCPDF_PATH.'/');
}
if (!defined('TCPDI_PATH')) {
	define('TCPDI_PATH', (empty($dolibarr_lib_TCPDI_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/tcpdi/' : $dolibarr_lib_TCPDI_PATH.'/');
}
if (!defined('NUSOAP_PATH')) {
	define('NUSOAP_PATH', (!isset($dolibarr_lib_NUSOAP_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/nusoap/lib/' : (empty($dolibarr_lib_NUSOAP_PATH) ? '' : $dolibarr_lib_NUSOAP_PATH.'/'));
}
if (!defined('PHPEXCELNEW_PATH')) {
	define('PHPEXCELNEW_PATH', (!isset($dolibarr_lib_PHPEXCELNEW_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/PhpSpreadsheet/' : (empty($dolibarr_lib_PHPEXCELNEW_PATH) ? '' : $dolibarr_lib_PHPEXCELNEW_PATH.'/'));
}
if (!defined('ODTPHP_PATH')) {
	define('ODTPHP_PATH', (!isset($dolibarr_lib_ODTPHP_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/odtphp/' : (empty($dolibarr_lib_ODTPHP_PATH) ? '' : $dolibarr_lib_ODTPHP_PATH.'/'));
}
if (!defined('ODTPHP_PATHTOPCLZIP')) {
	define('ODTPHP_PATHTOPCLZIP', (!isset($dolibarr_lib_ODTPHP_PATHTOPCLZIP)) ? DOL_DOCUMENT_ROOT.'/includes/odtphp/zip/pclzip/' : (empty($dolibarr_lib_ODTPHP_PATHTOPCLZIP) ? '' : $dolibarr_lib_ODTPHP_PATHTOPCLZIP.'/'));
}
if (!defined('JS_CKEDITOR')) {
	define('JS_CKEDITOR', (!isset($dolibarr_js_CKEDITOR)) ? '' : (empty($dolibarr_js_CKEDITOR) ? '' : $dolibarr_js_CKEDITOR.'/'));
}
if (!defined('JS_JQUERY')) {
	define('JS_JQUERY', (!isset($dolibarr_js_JQUERY)) ? '' : (empty($dolibarr_js_JQUERY) ? '' : $dolibarr_js_JQUERY.'/'));
}
if (!defined('JS_JQUERY_UI')) {
	define('JS_JQUERY_UI', (!isset($dolibarr_js_JQUERY_UI)) ? '' : (empty($dolibarr_js_JQUERY_UI) ? '' : $dolibarr_js_JQUERY_UI.'/'));
}
// Other required path
if (!defined('DOL_DEFAULT_TTF')) {
	define('DOL_DEFAULT_TTF', (!isset($dolibarr_font_DOL_DEFAULT_TTF)) ? DOL_DOCUMENT_ROOT.'/includes/fonts/Aerial.ttf' : (empty($dolibarr_font_DOL_DEFAULT_TTF) ? '' : $dolibarr_font_DOL_DEFAULT_TTF));
}
if (!defined('DOL_DEFAULT_TTF_BOLD')) {
	define('DOL_DEFAULT_TTF_BOLD', (!isset($dolibarr_font_DOL_DEFAULT_TTF_BOLD)) ? DOL_DOCUMENT_ROOT.'/includes/fonts/AerialBd.ttf' : (empty($dolibarr_font_DOL_DEFAULT_TTF_BOLD) ? '' : $dolibarr_font_DOL_DEFAULT_TTF_BOLD));
}


/*
 * Include functions
 */

// If password is encoded, we decode it. Note: When page is called for install, $dolibarr_main_db_pass may not be defined yet.
if ((!empty($dolibarr_main_db_pass) && preg_match('/(dolcrypt|crypted):/i', $dolibarr_main_db_pass)) || !empty($dolibarr_main_db_encrypted_pass)) {
	if (!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
		$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this so we can use it later to know the password was initially encrypted
	} elseif (!empty($dolibarr_main_db_pass) && preg_match('/dolcrypt:/i', $dolibarr_main_db_pass)) {
		$dolibarr_main_db_pass = dolDecrypt($dolibarr_main_db_pass, (empty($dolibarr_main_dolcrypt_key) ? (empty($dolibarr_main_instance_unique_id) ? '' : $dolibarr_main_instance_unique_id) : $dolibarr_main_dolcrypt_key));
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this so we can use it later to know the password was initially encrypted
	} else {
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}
}
