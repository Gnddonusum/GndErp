<?php
/* Copyright (C) 2004		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 * or see https://www.gnu.org/
 */

/**
 * 	\file       htdocs/holiday/info.php
 * 	\ingroup    holiday
 * 	\brief      Page to show leave information
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("holiday");

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');


$childids = $user->getAllChildIds(1);

$morefilter = '';
if (getDolGlobalString('HOLIDAY_HIDE_FOR_NON_SALARIES')) {
	$morefilter = 'AND employee = 1';
}

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$permissiontoapprove = $user->hasRight('holiday', 'approve');

if (($id > 0) || $ref) {
	$object->fetch($id, $ref);

	// Check current user can read this leave request
	$canread = 0;
	if ($user->hasRight('holiday', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('holiday', 'read') && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if ($permissiontoapprove && $object->fk_validator == $user->id && !getDolGlobalString('HOLIDAY_CAN_APPROVE_ONLY_THE_SUBORDINATES')) {	// TODO HOLIDAY_CAN_APPROVE_ONLY_THE_SUBORDINATES not completely implemented
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'holiday', $object->id, 'holiday');


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Leave")." - ".$langs->trans("Info");
$help_url = 'EN:Module_Holiday';

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-holiday page-card_info');

if ($id > 0 || !empty($ref)) {
	$object = new Holiday($db);
	$object->fetch($id, $ref);
	$object->info($object->id);

	$head = holiday_prepare_head($object);

	print dol_get_fiche_head($head, 'info', $langs->trans("Holiday"), -1, 'holiday');

	$linkback = '<a href="'.DOL_URL_ROOT.'/holiday/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<br>';

	print '<table width="100%"><tr><td>';
	dol_print_object_info($object);
	print '</td></tr></table>';

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
