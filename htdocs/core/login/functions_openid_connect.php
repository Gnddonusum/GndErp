<?php
/* Copyright (C) 2022		Jeritiana Ravelojaona	<jeritiana.rav@smartone.ai>
 * Copyright (C) 2023-2024	Solution Libre SAS		<contact@solution-libre.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Maximilien Rozniecki	<mrozniecki@easya.solutions>
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
 *      \file       htdocs/core/login/functions_openid_connect.php
 *      \ingroup    openid_connect
 *      \brief      OpenID Connect: Authorization Code flow authentication
 *
 *      See https://github.com/Dolibarr/dolibarr/issues/22740 for more information about setup openid_connect
 */

include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/openid_connect.lib.php';

/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest	Number of instance (always 1 if module multicompany not enabled)
 * @return	string|false			Login if OK, false if KO
 */
function check_user_password_openid_connect($usertotest, $passwordtotest, $entitytotest)
{
	global $db;

	if (getDolGlobalInt('MAIN_MODULE_OPENIDCONNECT', 0) <= 0) {
		$_SESSION["dol_loginmesg"] = "OpenID Connect is disabled";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect Module disabled");
		return false;
	}

	// Force master entity in transversal mode
	$entity = $entitytotest;
	if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
		$entity = 1;
	}

	dol_syslog("functions_openid_connect::check_user_password_openid_connect usertotest=".$usertotest." passwordtotest=".preg_replace('/./', '*', $passwordtotest)." entitytotest=".$entitytotest);

	// Step 1 is done by user: request an authorization code

	if (GETPOSTISSET('username')) {
		// OIDC does not require credentials here: pass on to next auth handler
		$_SESSION["dol_loginmesg"] = "Not an OpenID Connect flow";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::not an OIDC flow");
		return false;
	} elseif (!GETPOSTISSET('state')) {
		// No state received
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (no state received)";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::no state received", LOG_ERR);
		return false;
	} elseif (!GETPOSTISSET('code')) {
		// No code received
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (no code received)";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::no code received", LOG_ERR);
		return false;
	}

	$auth_code = GETPOST('code', 'aZ09');
	$state = GETPOST('state', 'aZ09');
	dol_syslog('functions_openid_connect::check_user_password_openid_connect code='.$auth_code.' state='.$state);

	if ($state !== openid_connect_get_state()) {
		// State does not match
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (state does not match)";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::state does not match", LOG_ERR);
		return false;
	}

	// Step 2: turn the authorization code into an access token, using client_secret
	$auth_param = [
		'grant_type'    => 'authorization_code',
		'client_id'     => getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLIENT_ID'),
		'client_secret' => getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLIENT_SECRET'),
		'code'          => $auth_code,
		'redirect_uri'  => openid_connect_get_redirect_url()
	];

	$token_response = getURLContent(getDolGlobalString('MAIN_AUTHENTICATION_OIDC_TOKEN_URL'), 'POST', http_build_query($auth_param), 1, array(), array('https'), 2);
	$token_content = json_decode($token_response['content']);
	dol_syslog("functions_openid_connect::check_user_password_openid_connect /token=".print_r($token_response, true), LOG_DEBUG);

	if ($token_response['curl_error_no']) {
		// Token request error
		$_SESSION["dol_loginmesg"] = "Network error: ".$token_response['curl_error_msg']." (".$token_response['curl_error_no'].")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$_SESSION["dol_loginmesg"], LOG_ERR);
		return false;
	} elseif ($token_response['http_code'] >= 400 && $token_response['http_code'] < 500) {
		// HTTP Error
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (".$token_response['content'].")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$token_response['content'], LOG_ERR);
		return false;
	} elseif ($token_content->error) {
		// Got token response but content is an error
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (".$token_content->error_description.")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$token_content->error_description, LOG_ERR);
		return false;
	} elseif (!property_exists($token_content, 'access_token')) {
		// Other token request error
		$_SESSION["dol_loginmesg"] = "Token request error (".$token_response['http_code'].")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$_SESSION["dol_loginmesg"], LOG_ERR);
		return false;
	}

	// Step 3: retrieve user info (login, email, ...) from OIDC server using token
	$userinfo_headers = array('Authorization: Bearer '.$token_content->access_token);
	$userinfo_response = getURLContent(getDolGlobalString('MAIN_AUTHENTICATION_OIDC_USERINFO_URL'), 'GET', '', 1, $userinfo_headers, array('https'), 2);
	$userinfo_content = json_decode($userinfo_response['content']);

	dol_syslog("functions_openid_connect::check_user_password_openid_connect /userinfo=".print_r($userinfo_response, true), LOG_DEBUG);

	// Get the user attribute (claim) matching the Dolibarr login
	$login_claim = 'email'; // default
	if (getDolGlobalString('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM')) {
		$login_claim = getDolGlobalString('MAIN_AUTHENTICATION_OIDC_LOGIN_CLAIM');
	}

	if ($userinfo_response['curl_error_no']) {
		// User info request error
		$_SESSION["dol_loginmesg"] = "Network error: ".$userinfo_response['curl_error_msg']." (".$userinfo_response['curl_error_no'].")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$_SESSION["dol_loginmesg"], LOG_ERR);
		return false;
	} elseif ($userinfo_response['http_code'] >= 400 && $userinfo_response['http_code'] < 500) {
		// HTTP Error
		$_SESSION["dol_loginmesg"] = "OpenID Connect user info error: " . $userinfo_response['content'];
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$userinfo_response['content'], LOG_ERR);
		return false;
	} elseif ($userinfo_content->error) {
		// Got user info response but content is an error
		$_SESSION["dol_loginmesg"] = "Error in OAuth 2.0 flow (".$userinfo_content->error_description.")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$userinfo_content->error_description, LOG_ERR);
		return false;
	} elseif (!property_exists($userinfo_content, $login_claim)) {
		// Other user info request error
		$_SESSION["dol_loginmesg"] = "Userinfo request error (".$userinfo_response['http_code'].")";
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::".$_SESSION["dol_loginmesg"], LOG_ERR);
		return false;
	}

	// Success: retrieve claim to return to Dolibarr as login
	$sql = 'SELECT login, entity, datestartvalidity, dateendvalidity';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'user';
	if ($login_claim === 'email') {
		// If login claim is email, check both login and email fields
		$sql .= " WHERE (login = '".$db->escape($userinfo_content->$login_claim)."' OR email = '".$db->escape($userinfo_content->$login_claim)."')";
	} else {
		$sql .= " WHERE login = '".$db->escape($userinfo_content->$login_claim)."'";
	}
	$sql .= ' AND entity IN (0,'.(array_key_exists('dol_entity', $_SESSION) ? ((int) $_SESSION["dol_entity"]) : 1).')';

	dol_syslog("functions_openid::check_user_password_openid", LOG_DEBUG);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::Error with sql query (".$db->error().")");
		return false;
	}
	$numres = $db->num_rows($resql);
	if ($numres > 1) {
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::Error more than 1 result from the query");
		return false;
	}
	$obj = $db->fetch_object($resql);
	if (!$obj) {
		dol_syslog("functions_openid_connect::check_user_password_openid_connect::Error no result from the query");
		return false;
	}

	$_SESSION['OPENID_CONNECT'] = true;

	// Note: Test on date validity is done later natively with isNotIntoValidityDateRange() by core after calling checkLoginPassEntity() that call this method
	dol_syslog("functions_openid_connect::check_user_password_openid_connect END");
	return $obj->login;
}
