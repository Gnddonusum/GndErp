<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2017		Ferran Marcet			<fmarcet@2byte.es.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Set Stripe environment: set the ApiKey and AppInfo
 */

/**
*  \file       htdocs/stripe/config.php
*  \ingroup    Stripe
*  \brief      Page to move config in api
*/

require_once DOL_DOCUMENT_ROOT.'/includes/stripe/stripe-php/init.php';
require_once DOL_DOCUMENT_ROOT.'/includes/stripe/stripe-php/lib/Stripe.php';

//global $stripe;
global $conf;
global $stripearrayofkeysbyenv;

$stripearrayofkeysbyenv = array(
	array(
		"secret_key"      => getDolGlobalString('STRIPE_TEST_SECRET_KEY'),
		"publishable_key" => getDolGlobalString('STRIPE_TEST_PUBLISHABLE_KEY')
	),
	array(
		"secret_key"      => getDolGlobalString('STRIPE_LIVE_SECRET_KEY'),
		"publishable_key" => getDolGlobalString('STRIPE_LIVE_PUBLISHABLE_KEY')
	)
);

$stripearrayofkeys = array();
if (!getDolGlobalString('STRIPE_LIVE')/* || GETPOST('forcesandbox', 'alpha') */) {
	$stripearrayofkeys = $stripearrayofkeysbyenv[0]; // Test
} else {
	$stripearrayofkeys = $stripearrayofkeysbyenv[1]; // Live
}

\Stripe\Stripe::setApiKey($stripearrayofkeys['secret_key']);
\Stripe\Stripe::setAppInfo("Dolibarr Stripe", DOL_VERSION, "https://www.dolibarr.org"); // add dolibarr version
\Stripe\Stripe::setApiVersion(getDolGlobalString('STRIPE_FORCE_VERSION', "2022-11-15")); // force version API
