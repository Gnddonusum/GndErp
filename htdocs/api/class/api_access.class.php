<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
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

// Create the autoloader for Luracast
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/AutoLoader.php';
call_user_func(
	/**
	 * @return Luracast\Restler\AutoLoader
	 */
	static function () {
		$loader = Luracast\Restler\AutoLoader::instance();
		spl_autoload_register($loader);
		return $loader;
	}
);

require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/iAuthenticate.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/iUseAuthentication.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/Resources.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/Defaults.php';
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/RestException.php';

use Luracast\Restler\iAuthenticate;
use Luracast\Restler\Resources;
use Luracast\Restler\Defaults;
use Luracast\Restler\RestException;

/**
 * Dolibarr API access class
 */
class DolibarrApiAccess implements iAuthenticate
{
	const REALM = 'Restricted Dolibarr API';

	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * @var string[]	role required by API method		user / external / admin
	 */
	public static $requires = array('user', 'external', 'admin');

	/**
	 * @var string		user role
	 */
	public static $role = 'user';

	/**
	 * @var User		$user	Loggued user
	 */
	public static $user = null;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName
	/**
	 * Check access
	 *
	 * @return bool
	 *
	 * @throws RestException 401 Forbidden
	 * @throws RestException 503 Technical error
	 */
	public function __isAllowed()
	{
		// phpcs:enable
		global $conf, $user;

		$login = '';
		$stored_key = '';

		$userClass = Defaults::$userIdentifierClass;

		/*foreach ($_SERVER as $key => $val)
		{
			dol_syslog($key.' - '.$val);
		}*/

		// api key can be provided in url with parameter api_key=xxx or ni header with header DOLAPIKEY:xxx
		$api_key = '';
		if (isset($_GET['api_key'])) {	// For backward compatibility. Keep $_GET here.
			// TODO Add option to disable use of api key on url. Return errors if used.
			$api_key = $_GET['api_key'];
		}
		if (isset($_GET['DOLAPIKEY'])) {
			// TODO Add option to disable use of api key on url. Return errors if used.
			$api_key = $_GET['DOLAPIKEY']; // With GET method
		}

		// TODO Can filter on user agent.
		//$api_useragent = $_SERVER['HTTP_USER_AGENT'];

		if (isset($_SERVER['HTTP_DOLAPIKEY'])) {        // HTTP Header entry "DOLAPIKEY: ..." can be read with $_SERVER["HTTP_DOLAPIKEY"]
			$api_key = $_SERVER['HTTP_DOLAPIKEY']; 		// With header method (recommended)
		} elseif (empty($api_key)) {
			$headers = getallheaders();					// HTTP Header entry "Authorization: Bearer ..." can be read with getallheaders
			$api_key = preg_replace('/^Bearer\s+/i', '', empty($headers['Authorization']) ? '' : $headers['Authorization']);
		};

		$api_key = dol_string_nounprintableascii($api_key, 1);

		if (preg_match('/^dolcrypt:/i', $api_key)) {
			throw new RestException(503, 'Bad value for the API key. An API key should not start with dolcrypt:');
		}

		if ($api_key) {
			$userentity = 0;

			$sql = "SELECT u.login, u.datec, u.api_key,";
			$sql .= " u.tms as date_modification, u.entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql .= " WHERE u.api_key = '".$this->db->escape($api_key)."' OR u.api_key = '".$this->db->escape(dolEncrypt($api_key, '', '', 'dolibarr'))."'";

			$result = $this->db->query($sql);
			if ($result) {
				$nbrows = $this->db->num_rows($result);
				if ($nbrows == 1) {
					$obj = $this->db->fetch_object($result);
					$login = $obj->login;
					$stored_key = dolDecrypt($obj->api_key);
					$userentity = $obj->entity;

					if (!defined("DOLENTITY") && $conf->entity != ($obj->entity ? $obj->entity : 1)) {		// If API was not forced with HTTP_DOLENTITY, and user is on another entity, so we reset entity to entity of user
						$conf->entity = ($obj->entity ? $obj->entity : 1);
						// We must also reload global conf to get params from the entity
						dol_syslog("Entity was not set on http header with HTTP_DOLAPIENTITY (recommended for performance purpose), so we switch now on entity of user (".$conf->entity.") and we have to reload configuration.", LOG_WARNING);
						$conf->setValues($this->db);
					}
				} elseif ($nbrows > 1) {
					throw new RestException(503, 'Error when fetching user api_key : More than 1 user with this apikey');
				}
			} else {
				throw new RestException(503, 'Error when fetching user api_key :'.$this->db->error);
			}

			if ($login && $stored_key != $api_key) {		// This should not happen since we did a search on api_key
				$userClass::setCacheIdentifier($api_key);
				return false;
			}

			$genericmessageerroruser = 'Error user not valid (not found with api key or bad status or bad validity dates) (conf->entity='.$conf->entity.')';

			if (!$login) {
				dol_syslog("functions_isallowed::check_user_api_key Authentication KO for api key: Error when searching login user from api key", LOG_NOTICE);
				sleep(1); // Anti brute force protection. Must be same delay when user and password are not valid.
				throw new RestException(401, $genericmessageerroruser);
			}

			$fuser = new User($this->db);
			$result = $fuser->fetch(0, $login, '', 0, (empty($userentity) ? -1 : $conf->entity)); // If user is not entity 0, we search in working entity $conf->entity  (that may have been forced to a different value than user entity)
			if ($result <= 0) {
				dol_syslog("functions_isallowed::check_user_api_key Authentication KO for '".$login."': Failed to fetch on entity", LOG_NOTICE);
				sleep(1); // Anti brute force protection. Must be same delay when user and password are not valid.
				throw new RestException(401, $genericmessageerroruser);
			}

			// Check if user status is enabled
			if ($fuser->statut != $fuser::STATUS_ENABLED) {
				// Status is disabled
				dol_syslog("functions_isallowed::check_user_api_key Authentication KO for '".$login."': The user has been disabled", LOG_NOTICE);
				sleep(1); // Anti brute force protection. Must be same delay when user and password are not valid.
				throw new RestException(401, $genericmessageerroruser);
			}

			// Check if session was unvalidated by a password change
			if (($fuser->flagdelsessionsbefore && !empty($_SESSION["dol_logindate"]) && $fuser->flagdelsessionsbefore > $_SESSION["dol_logindate"])) {
				// Session is no more valid
				dol_syslog("functions_isallowed::check_user_api_key Authentication KO for '".$login."': The user has a date for session invalidation = ".$fuser->flagdelsessionsbefore." and a session date = ".$_SESSION["dol_logindate"].". We must invalidate its sessions.");
				sleep(1); // Anti brute force protection. Must be same delay when user and password are not valid.
				throw new RestException(401, $genericmessageerroruser);
			}

			// Check date validity
			if ($fuser->isNotIntoValidityDateRange()) {
				// User validity dates are no more valid
				dol_syslog("functions_isallowed::check_user_api_key Authentication KO for '".$login."': The user login has a validity between [".$fuser->datestartvalidity." and ".$fuser->dateendvalidity."], current date is ".dol_now());
				sleep(1); // Anti brute force protection. Must be same delay when user and password are not valid.
				throw new RestException(401, $genericmessageerroruser);
			}

			// User seems valid
			$fuser->loadRights();

			// Set the property $user to the $user of API
			static::$user = $fuser;

			// Set also the global variable $user to the $user of API
			$user = $fuser;

			if ($fuser->socid) {
				static::$role = 'external';
			}

			if ($fuser->admin) {
				static::$role = 'admin';
			}
		} else {
			throw new RestException(401, "Failed to login to API. Neither parameter 'HTTP_DOLAPIKEY' nor 'Authentication: Bearer' found on HTTP header (and no parameter DOLAPIKEY in URL).");
		}

		$userClass::setCacheIdentifier(static::$role);
		Resources::$accessControlFunction = 'DolibarrApiAccess::verifyAccess';
		$requirefortest = static::$requires;
		if (!is_array($requirefortest)) {
			$requirefortest = explode(',', $requirefortest);
		}
		return in_array(static::$role, (array) $requirefortest) || static::$role == 'admin';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName
	/**
	 * @return string string to be used with WWW-Authenticate header
	 */
	public function __getWWWAuthenticateString()
	{
		// phpcs:enable
		return '';
	}

	/**
	 * Verify access
	 *
	 * @param   array{class:array{DolibarrApiAccess:array{properties:array{requires?:bool}}}} $m Properties of method
	 *
	 * @access private
	 * @return bool
	 */
	public static function verifyAccess(array $m)
	{
		$requires = isset($m['class']['DolibarrApiAccess']['properties']['requires'])
				? $m['class']['DolibarrApiAccess']['properties']['requires']
				: false;


		return $requires
			? static::$role == 'admin' || in_array(static::$role, (array) $requires)
			: true;
	}
}
