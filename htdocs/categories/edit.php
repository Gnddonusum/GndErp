<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
 * Copyright (C) 2020-2024	Frédéric France		<frederic.france@free.fr>
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
 *      \file       htdocs/categories/edit.php
 *      \ingroup    category
 *      \brief      Page d'edition de categorie produit
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("categories");

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alphanohtml');
$action = (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'edit');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ');

$socid = GETPOSTINT('socid');
$label = (string) GETPOST('label', 'alphanohtml');
$description = (string) GETPOST('description', 'restricthtml');
$color = preg_replace('/^#/', '', preg_replace('/[^0-9a-f#]/i', '', (string) GETPOST('color', 'alphanohtml')));
$position = GETPOSTINT('position');
$visible = GETPOSTINT('visible');
$parent = GETPOSTINT('parent');

if ($id == "") {
	dol_print_error(null, 'Missing parameter id');
	exit();
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result = $object->fetch($id, $label);
if ($result <= 0) {
	dol_print_error($db, $object->error);
	exit;
}

$type = $object->type;
if (is_numeric($type)) {
	$type = array_search($type, $object->MAP_ID);	// For backward compatibility
}

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

$error = 0;


/*
 * Actions
 */
$parameters = array('id' => $id, 'ref' => $ref, 'cancel' => $cancel, 'backtopage' => $backtopage, 'socid' => $socid, 'label' => $label, 'description' => $description, 'color' => $color, 'position' => $position, 'visible' => $visible, 'parent' => $parent);
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if ($backtopage) {
			header("Location: ".$backtopage);
			exit;
		} else {
			header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.((int) $object->id).'&type='.urlencode($type).($dol_openinpopup ? '&dol_openinpopup='.urlencode($dol_openinpopup) : ''));
			exit;
		}
	}

	// Action mise a jour d'une categorie
	if ($action == 'update' && $user->hasRight('categorie', 'creer')) {
		$object->oldcopy = dol_clone($object, 2); // @phan-suppress-current-line PhanTypeMismatchProperty

		$object->label = $label;
		$object->description    = dol_htmlcleanlastbr($description);
		$object->color          = $color;
		$object->position       = $position;
		$object->socid          = ($socid > 0 ? $socid : 0);
		$object->visible        = $visible;
		$object->fk_parent = $parent != -1 ? $parent : 0;

		if (empty($object->label)) {
			$error++;
			$action = 'edit';
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
		}
		if (!$error && empty($object->error)) {
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}

			if (!$error && $object->update($user) > 0) {
				if ($backtopage) {
					header("Location: ".$backtopage);
					exit;
				} else {
					header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.((int) $object->id).'&type='.urlencode($type).($dol_openinpopup ? '&dol_openinpopup='.urlencode($dol_openinpopup) : ''));
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader("", "", $langs->trans("Categories"));

print load_fiche_titre($langs->trans("ModifCat"));

$object->fetch($id);


print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';

print dol_get_fiche_head([]);

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="border centpercent">';

// Ref
print '<tr><td class="titlefieldcreate fieldrequired">';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="label" name ="label" value="'.$object->label.'" />';
print '</tr>';

// Description
print '<tr>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), ROWS_6, '90%');
$doleditor->Create();
print '</td></tr>';

// Color
print '<tr>';
print '<td>'.$langs->trans("Color").'</td>';
print '<td>';
print $formother->selectColor($object->color, 'color');
print '</td></tr>';

// Position
print '<tr><td>';
print $langs->trans("Position").'</td>';
print '<td><input type="text" class="width50" id="position" name ="position" value="'.$object->position.'" />';
print '</tr>';

// Parent category
print '<tr><td>'.$langs->trans("In").'</td><td>';
print img_picto('', 'category', 'class="pictofixedwidth"');
print $form->select_all_categories($type, $object->fk_parent, 'parent', 64, $object->id, 0, 0, 'widthcentpercentminusx maxwidth500');
print ajax_combobox('parent');
print '</td></tr>';

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (empty($reshook)) {
	print $object->showOptionals($extrafields, 'edit', $parameters);
}

print '</table>';
print '</div>';

print dol_get_fiche_end();


print $form->buttonsSaveCancel("Save", "Cancel");


print '</form>';

// End of page
llxFooter();
$db->close();
