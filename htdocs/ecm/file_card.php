<?php
/* Copyright (C) 2008-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file      	htdocs/ecm/file_card.php
 *	\ingroup   	ecm
 *	\brief     	Card of a file for ECM module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by page
$langs->loadLangs(array('ecm', 'companies', 'other', 'users', 'orders', 'propal', 'bills', 'contracts', 'categories'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Get parameters
$socid = GETPOSTINT("socid");

// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "label";
}

$section = GETPOST("section", 'alpha');
if (!$section) {
	dol_print_error(null, 'Error, section parameter missing');
	exit;
}
$urlfile = (string) dol_sanitizePathName(GETPOST("urlfile"), '_', 0);
if (!$urlfile) {
	dol_print_error(null, "ErrorParamNotDefined");
	exit;
}

// Load ecm object
$ecmdir = new EcmDirectory($db);
$result = $ecmdir->fetch(GETPOSTINT("section"));
if (!($result > 0)) {
	dol_print_error($db, $ecmdir->error);
	exit;
}
$relativepath = $ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

$fullpath = $conf->ecm->dir_output.'/'.$relativepath.$urlfile;

$relativetodocument = 'ecm/'.$relativepath; // $relativepath is relative to ECM dir, we need relative to document
$filepath = $relativepath.$urlfile;
$filepathtodocument = $relativetodocument.$urlfile;

// Try to load object from index
$object = new EcmFiles($db);
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$result = $object->fetch(0, '', $filepathtodocument);
if ($result < 0) {
	dol_print_error($db, $object->error, $object->errors);
	exit;
}

// Permissions
$permissiontoread = $user->hasRight('ecm', 'read');
$permissiontoadd = $user->hasRight('ecm', 'setup');
$permissiontoupload = $user->hasRight('ecm', 'upload');

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

if ($cancel) {
	$action = '';
	if ($backtopage) {
		header("Location: ".$backtopage);
		exit;
	} else {
		header('Location: '.$_SERVER["PHP_SELF"].'?urlfile='.urlencode($urlfile).'&section='.urlencode($section).($module ? '&module='.urlencode($module) : ''));
		exit;
	}
}

// Rename file
if ($action == 'update' && $permissiontoadd) {
	$error = 0;

	$oldlabel = GETPOST('urlfile', 'alpha');
	$newlabel = dol_sanitizeFileName(GETPOST('label', 'alpha'), '_', 0);
	$shareenabled = GETPOST('shareenabled', 'alpha');

	//$db->begin();

	$olddir = $ecmdir->getRelativePath(0); // Relative to ecm
	$olddirrelativetodocument = 'ecm/'.$olddir; // Relative to document
	$newdirrelativetodocument = 'ecm/'.$olddir;
	$olddir = $conf->ecm->dir_output.'/'.$olddir;
	$newdir = $olddir;

	$oldfile = $olddir.$oldlabel;
	$newfile = $newdir.$newlabel;
	$newfileformove = $newfile;
	// If old file end with .noexe, new file must also end with .noexe
	if (preg_match('/\.noexe$/', $oldfile) && !preg_match('/\.noexe$/', $newfileformove)) {
		$newfileformove .= '.noexe';
	}
	//var_dump($oldfile);var_dump($newfile);exit;

	// Now we update index of file
	$db->begin();
	//print $oldfile.' - '.$newfile;
	if ($newlabel != $oldlabel) {
		$result = dol_move($oldfile, $newfileformove); // This include update of database
		if (!$result) {
			$langs->load('errors');
			setEventMessages($langs->trans('ErrorFailToRenameFile', $oldfile, $newfile), null, 'errors');
			$error++;
		}

		// Reload object after the move
		$result = $object->fetch(0, '', $newdirrelativetodocument.$newlabel);
		if ($result < 0) {
			dol_print_error($db, $object->error, $object->errors);
			exit;
		}
	}

	if (!$error) {
		if ($shareenabled) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$object->share = getRandomPassword(true);
		} else {
			$object->share = '';
		}

		if ($object->id > 0) {
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
			if (!$error) {
				// Actions on extra fields
				$result = $object->insertExtraFields();
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}
			// Call update to set the share key
			$result = $object->update($user);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
		} else {
			// Call create to insert record
			$object->entity = $conf->entity;
			$object->filepath = preg_replace('/[\\/]+$/', '', $newdirrelativetodocument);
			$object->filename = $newlabel;
			$object->label = md5_file(dol_osencode($newfileformove)); // hash of file content
			$object->fullpath_orig = '';
			$object->gen_or_uploaded = 'unknown';
			$object->description = ''; // indexed content
			$object->keywords = ''; // keyword content
			$result = $object->create($user);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
		}
	}

	if (!$error) {
		$db->commit();

		$urlfile = $newlabel;
		// If old file end with .noexe, new file must also end with .noexe
		if (preg_match('/\.noexe$/', $newfileformove)) {
			$urlfile .= '.noexe';
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?urlfile='.urlencode($urlfile).'&section='.urlencode($section));
		exit;
	} else {
		$db->rollback();
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-ecm page-file_card');

$object->section_id = $ecmdir->id;
$object->label = $urlfile;
$head = ecm_file_prepare_head($object);

if ($action == 'edit') {
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="section" value="'.$section.'">';
	print '<input type="hidden" name="urlfile" value="'.$urlfile.'">';
	print '<input type="hidden" name="module" value="'.$module.'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
}

print dol_get_fiche_head($head, 'card', $langs->trans("File"), -1, 'generic');


$s = '';
$tmpecmdir = new EcmDirectory($db); // Need to create a new one
$tmpecmdir->fetch($ecmdir->id);
$result = 1;
$i = 0;
while ($tmpecmdir && $result > 0) {
	$tmpecmdir->ref = $tmpecmdir->label;
	$s = $tmpecmdir->getNomUrl(1).$s;
	if ($tmpecmdir->fk_parent) {
		$s = ' -> '.$s;
		$result = $tmpecmdir->fetch($tmpecmdir->fk_parent);
	} else {
		$tmpecmdir = 0;
	}
	$i++;
}

$urlfiletoshow = preg_replace('/\.noexe$/', '', $urlfile);

$s = img_picto('', 'object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> '.$s.' -> ';
if ($action == 'edit') {
	$s .= '<input type="text" name="label" class="quatrevingtpercent" value="'.$urlfiletoshow.'">';
} else {
	$s .= $urlfiletoshow;
}

$linkback = '';
if ($backtopage) {
	$linkback = '<a href="'.$backtopage.'">'.$langs->trans("BackToTree").'</a>';
}

$object->ref = ''; // Force to hide ref
dol_banner_tab($object, '', $linkback, 0, '', '', $s);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefieldcreate">'.$langs->trans("ECMCreationDate").'</td><td>';
print dol_print_date(dol_filemtime($fullpath), 'dayhour');
print '</td></tr>';
/*print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
print '/ecm/'.$relativepath;
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
print count($filearray);
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
*/

// Hash of file content
print '<tr><td>'.$langs->trans("HashOfFileContent").'</td><td>';
$object = new EcmFiles($db);
$object->fetch(0, '', $filepathtodocument);
if (!empty($object->label)) {
	print $object->label;
} else {
	print img_warning().' '.$langs->trans("FileNotYetIndexedInDatabase");
}
print '</td></tr>';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Link for internal download
print '<tr><td>';
print $form->textwithpicto($langs->trans("DirectDownloadInternalLink"), $langs->trans("PrivateDownloadLinkDesc"));
print '</td><td>';
$modulepart = 'ecm';
$forcedownload = 1;
$rellink = '/document.php?modulepart='.$modulepart;
if ($forcedownload) {
	$rellink .= '&attachment=1';
}
if (!empty($object->entity)) {
	$rellink .= '&entity='.$object->entity;
}
$rellink .= '&file='.urlencode($filepath);
$fulllink = $urlwithroot.$rellink;
print img_picto('', 'globe').' ';
if ($action != 'edit') {
	print '<input type="text" class="maxquatrevingtpercent widthcentpercentminusxx small" id="downloadinternallink" name="downloadinternellink" value="'.dol_escape_htmltag($fulllink).'">';
} else {
	print $fulllink;
}
if ($action != 'edit') {
	print ' <a href="'.$fulllink.'">'.img_picto($langs->trans("Download"), 'download', 'class="opacitymedium paddingrightonly"').'</a>'; // No target here.
}
print '</td></tr>';

// Link for direct external download
print '<tr><td>';
if ($action != 'edit') {
	print $form->textwithpicto($langs->trans("DirectDownloadLink"), $langs->trans("PublicDownloadLinkDesc"));
} else {
	print $form->textwithpicto($langs->trans("FileSharedViaALink"), $langs->trans("PublicDownloadLinkDesc"));
}
print '</td><td>';
if (!empty($object->share)) {
	if ($action != 'edit') {
		$forcedownload = 0;

		$paramlink = '';
		if (!empty($object->share)) {
			$paramlink .= ($paramlink ? '&' : '').'hashp='.$object->share; // Hash for public share
		}
		if ($forcedownload) {
			$paramlink .= ($paramlink ? '&' : '').'attachment=1';
		}

		$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');
		//if (!empty($object->ref))       $fulllink.='&hashn='.$object->ref;		// Hash of file path
		//elseif (!empty($object->label)) $fulllink.='&hashc='.$object->label;		// Hash of file content

		print img_picto('', 'globe').' ';
		if ($action != 'edit') {
			print '<input type="text" class="maxquatrevingtpercent widthcentpercentminusxx nopadding small" id="downloadlink" name="downloadexternallink" value="'.dol_escape_htmltag($fulllink).'">';
		} else {
			print $fulllink;
		}
		if ($action != 'edit') {
			print ' <a href="'.$fulllink.'">'.img_picto($langs->trans("Download"), 'download', 'class="opacitymedium paddingrightonly"').'</a>'; // No target here
		}
	} else {
		print '<input type="checkbox" name="shareenabled"'.($object->share ? ' checked="checked"' : '').' /> ';
	}
} else {
	if ($action != 'edit') {
		print '<span class="opacitymedium">'.$langs->trans("FileNotShared").'</span>';
	} else {
		print '<input type="checkbox" name="shareenabled"'.($object->share ? ' checked="checked"' : '').' /> ';
	}
}
print '</td>';
print '</tr>';
print $object->showOptionals($extrafields, ($action == 'edit' ? 'edit' : 'view'));
print '</table>';
print '</div>';

print ajax_autoselect('downloadinternallink');
print ajax_autoselect('downloadlink');

print dol_get_fiche_end();

if ($action == 'edit') {
	print $form->buttonsSaveCancel();

	print '</form>';
}


// Confirm deletion of a file
if ($action == 'deletefile') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.urlencode($section), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile', $urlfile), 'confirm_deletefile', '', 1, 1);
}

if ($action != 'edit') {
	// Actions buttons
	print '<div class="tabsAction">';

	if ($user->hasRight('ecm', 'setup')) {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&section='.urlencode($section).'&urlfile='.urlencode($urlfile).'">'.$langs->trans('Edit').'</a>';
	}

	print '</div>';
}


// End of page
llxFooter();
$db->close();
