<?php
/* Copyright (C) - 2013-2016	Jean-François FERRY    <hello@librethic.io>
 * Copyright (C) - 2019     	Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/public/ticket/index.php
 *    \ingroup    ticket
 *    \brief      Public page to add and manage tickets
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');       // If this page is public (can be called outside logged session)
}

if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');     // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket', 'errors'));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'aZ09');
$suffix = "";

// Check access to Module(s)
if (!isModEnabled('ticket')) {
	httponly_accessforbidden('Module Ticket is not enabled');
}


/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

if (!getDolGlobalString('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	print $langs->trans('TicketPublicInterfaceForbidden');
	exit;
}

$arrayofjs = array();
$arrayofcss = array(getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', '/public/ticket/').'css/styles.css.php');

llxHeaderTicket($langs->trans('Tickets'), "", 0, 0, $arrayofjs, $arrayofcss);

print '<div class="ticketpublicarea ticketlargemargin">';

print '<p style="text-align: center">'.(getDolGlobalString("TICKET_PUBLIC_TEXT_HOME", '<span class="opacitymedium">'.$langs->trans("TicketPublicDesc")).'</span></p>').'</p>';
print '<br>';

$baseurl = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

print '<div class="ticketform">';
print '<a href="'.$baseurl . 'create_ticket.php?action=create'.(!empty($entity) && isModEnabled('multicompany')?'&entity='.$entity:'').'&token='.newToken().'" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_create bigrounded"><span class="fa fa-15 fa-plus-circle valignmiddle btnTitle-icon"></span><br>'.dol_escape_htmltag($langs->trans("CreateTicket")).'</div></a>';
print '<a href="list.php'.(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_display bigrounded"><span class="fa fa-15 fa-list-alt valignmiddle btnTitle-icon"></span><br>'.dol_escape_htmltag($langs->trans("ViewMyTicketList")).'</div></a>';
print '<a href="view.php'.(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'" rel="nofollow noopener" class="butAction marginbottomonly"><div class="index_display bigrounded">'.img_picto('', 'ticket', 'class="fa-15"').'<br>'.dol_escape_htmltag($langs->trans("ShowTicketWithTrackId")).'</div></a>';
print '<div class="clearboth"></div>';
print '</div>';  // ends '<div class="ticketform">';
print '</div>';  // ends '<div class="ticketpublicarea ticketlargemargin centpercent">';

if (getDolGlobalInt('TICKET_SHOW_COMPANY_FOOTER')) {
	// End of page
	htmlPrintOnlineFooter($mysoc, $langs, 0, $suffix, $object);
}

llxFooter('', 'public');

$db->close();
