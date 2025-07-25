<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/categories/photos.php
 *       \ingroup    category
 *       \brief      Gestion des photos d'une categorie
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadlangs(array('categories', 'bills'));


$id      = GETPOSTINT('id');
$label   = GETPOST('label', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');

if ($id == '' && $label == '') {
	dol_print_error(null, 'Missing parameter id');
	exit();
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

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

$upload_dir = $conf->categorie->multidir_output[$object->entity];

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$permissiontoadd = $user->hasRight('categorie', 'creer');


/*
 * Actions
 */

$parameters = array('id' => $id,  'label' => $label, 'confirm' => $confirm, 'type' => $type, 'uploaddir' => $upload_dir, 'sendfile' => (GETPOST("sendit") ? true : false));
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (isset($_FILES['userfile']) && $_FILES['userfile']['size'] > 0 && GETPOST("sendit") && getDolGlobalString('MAIN_UPLOAD_DOC')) {
		if ($object->id) {
			$file = $_FILES['userfile'];
			if (is_array($file['name']) && count($file['name']) > 0) {
				foreach ($file['name'] as $i => $name) {
					if (empty($file['tmp_name'][$i]) || (getDolGlobalInt('MAIN_UPLOAD_DOC') * 1000) <= filesize($file['tmp_name'][$i])) {
						setEventMessage($file['name'][$i].' : '.$langs->trans(empty($file['tmp_name'][$i]) ? 'ErrorFailedToSaveFile' : 'MaxSizeForUploadedFiles'), 'errors');
						unset($file['name'][$i], $file['type'][$i], $file['tmp_name'][$i], $file['error'][$i], $file['size'][$i]);
					}
				}
			}

			if (!empty($file['tmp_name'])) {
				$object->add_photo($upload_dir, $file);
			}
		}
	}

	if ($action == 'confirm_delete' && GETPOST("file") && $confirm == 'yes' && $permissiontoadd) {
		$object->delete_photo($upload_dir."/".GETPOST("file"));
	}

	if ($action == 'addthumb' && GETPOST("file") && $permissiontoadd) {
		$object->addThumbs($upload_dir."/".GETPOST("file"));
	}
}

/*
 * View
 */

llxHeader("", "", $langs->trans("Categories"));

$form = new Form($db);
$formother = new FormOther($db);

if ($object->id) {
	$title = $langs->trans("Categories");
	$title .= ' ('.$langs->trans(empty(Categorie::$MAP_TYPE_TITLE_AREA[$type]) ? ucfirst($type) : Categorie::$MAP_TYPE_TITLE_AREA[$type]).')';

	$head = categories_prepare_head($object, $type);
	print dol_get_fiche_head($head, 'photos', $langs->trans($title), -1, 'category');

	$backtolist = (GETPOST('backtolist') ? GETPOST('backtolist') : DOL_URL_ROOT.'/categories/categorie_list.php?leftmenu=cat&type='.urlencode($type));
	$linkback = '<a href="'.dol_sanitizeUrl($backtolist).'">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = 'type:=:'.((int) $object->type);
	$object->ref = $object->label;
	$morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/categorie_list.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
	foreach ($ways as $way) {
		$morehtmlref .= $way."<br>\n";
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'label', $linkback, ($user->socid ? 0 : 1), 'label', 'label', $morehtmlref, '&type='.$type, 0, '', '', 1);

	/*
	 * Confirmation deletion of picture
	 */
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.urlencode($type).'&file='.urlencode(GETPOST("file")), $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
	}

	print '<br>';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Description
	print '<tr><td class="titlefield notopnoleft">';
	print $langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Color
	print '<tr><td class="notopnoleft">';
	print $langs->trans("Color").'</td><td>';
	print $formother->showColor($object->color);
	print '</td></tr>';

	print "</table>\n";
	print '</div>';

	print dol_get_fiche_end();



	/*
	 * Action bar
	 */
	print '<div class="tabsAction">'."\n";

	if ($action != 'ajout_photo' && $user->hasRight('categorie', 'creer')) {
		if (getDolGlobalString('MAIN_UPLOAD_DOC')) {
			print '<a class="butAction hideonsmartphone" href="'.$_SERVER['PHP_SELF'].'?action=ajout_photo&amp;id='.$object->id.'&amp;type='.$type.'">';
			print $langs->trans("AddPhoto").'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip hideonsmartphone" href="#">';
			print $langs->trans("AddPhoto").'</a>';
		}
	}

	print '</div>'."\n";

	/*
	 * Ajouter une photo
	*/
	if ($action == 'ajout_photo' && $user->hasRight('categorie', 'creer') && getDolGlobalString('MAIN_UPLOAD_DOC')) {
		// Affiche formulaire upload
		$formfile = new FormFile($db);
		$formfile->form_attach_new_file($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;type='.$type, $langs->trans("AddPhoto"), 1, 0, $user->hasRight('categorie', 'creer'), 50, $object, '', 0, '', 0);
	}

	// Affiche photos
	if ($action != 'ajout_photo') {
		$nbphoto = 0;
		$nbbyrow = 5;

		$maxWidth = 160;
		$maxHeight = 120;

		$pdir = get_exdir($object->id, 2, 0, 0, $object, 'category').$object->id."/photos/";
		$dir = $upload_dir.'/'.$pdir;

		$listofphoto = $object->liste_photos($dir);

		if (is_array($listofphoto) && count($listofphoto)) {
			print '<br>';
			print '<table width="100%" valign="top" class="center centpercent">';

			foreach ($listofphoto as $key => $obj) {
				$nbphoto++;

				if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) {
					print '<tr class"center valignmiddle" border="1">';
				}
				if ($nbbyrow) {
					print '<td width="'.ceil(100 / $nbbyrow).'%" class="photo">';
				}

				print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$obj['photo']).'" alt="Original size" target="_blank" rel="noopener noreferrer">';

				// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
				if ($obj['photo_vignette']) {
					$filename = $obj['photo_vignette'];
				} else {
					$filename = $obj['photo'];
				}

				// Nom affiche
				$viewfilename = $obj['photo'];

				// Taille de l'image
				$object->get_image_size($dir.$filename);
				$imgWidth = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
				$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

				print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$filename).'">';

				print '</a>';
				print '<br>'.$viewfilename;
				print '<br>';

				// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
				if (!$obj['photo_vignette'] && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i', $obj['photo']) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken().'&action=addthumb&type='.$type.'&file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
				}
				if ($user->hasRight('categorie', 'creer')) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&type='.$type.'&file='.urlencode($pdir.$viewfilename).'">';
					print img_delete().'</a>';
				}
				if ($nbbyrow) {
					print '</td>';
				}
				if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) {
					print '</tr>';
				}
			}

			// Ferme tableau
			while ($nbphoto % $nbbyrow) {
				print '<td width="'.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
				$nbphoto++;
			}

			print '</table>';
		}

		if ($nbphoto < 1) {
			print '<div class="opacitymedium">'.$langs->trans("NoPhotoYet")."</div>";
		}
	}
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
