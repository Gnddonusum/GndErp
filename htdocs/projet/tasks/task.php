<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré		<vincent@de-grandpre.quebec>
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
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    project
 *	\brief      Page of a project task
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadlangs(array('projects', 'companies'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
//$cancel = GETPOST('cancel', 'aZ09');
//$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
//$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
//$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used

$id = GETPOSTINT('id');
$ref = GETPOST("ref", 'alpha', 1); // task ref
$taskref = GETPOST("taskref", 'alpha'); // task ref
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');
$planned_workload = ((GETPOST('planned_workloadhour') != '' || GETPOST('planned_workloadmin') != '') ? (GETPOSTINT('planned_workloadhour') > 0 ? GETPOSTINT('planned_workloadhour') * 3600 : 0) + (GETPOSTINT('planned_workloadmin') > 0 ? GETPOSTINT('planned_workloadmin') * 60 : 0) : '');
$mode = GETPOST('mode', 'alpha');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projecttaskcard', 'globalcard'));

$object = new Task($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($id > 0 || $ref) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$projectstatic->fetch($object->fk_project);
	}
}

// Security check
$socid = 0;

restrictedArea($user, 'projet', $object->fk_project, 'projet&project');



/*
 * Actions
 */
$error = 0;

if ($action == 'update' && !GETPOST("cancel") && $user->hasRight('projet', 'creer')) {
	if (empty($taskref)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	}
	if (!GETPOST("label")) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!$error) {
		$object->oldcopy = clone $object;  // @phan-suppress-current-line PhanTypeMismatchProperty

		$tmparray = explode('_', GETPOST('task_parent'));
		$task_parent = $tmparray[1];
		if (empty($task_parent)) {
			$task_parent = 0; // If task_parent is ''
		}

		$object->ref = $taskref ? $taskref : GETPOST("ref", 'alpha', 2);
		$object->label = GETPOST("label", "alphanohtml");
		if (!getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE')) {
			$object->description = GETPOST('description', "alphanohtml");
		} else {
			$object->description = GETPOST('description', "restricthtml");
		}
		$object->fk_task_parent = $task_parent;
		$object->planned_workload = $planned_workload;
		$object->date_start = dol_mktime(GETPOSTINT('date_starthour'), GETPOSTINT('date_startmin'), 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
		$object->date_end = dol_mktime(GETPOSTINT('date_endhour'), GETPOSTINT('date_endmin'), 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));
		$object->progress = price2num(GETPOST('progress', 'alphanohtml'));
		$object->budget_amount = GETPOSTFLOAT('budget_amount');
		$object->billable = (GETPOST('billable', 'aZ') == 'yes' ? 1 : 0);
		if (GETPOST('progress') == '100') {
			$object->status = $object::STATUS_CLOSED;
		} elseif (GETPOST('progress') != '0') {
			$object->status = $object::STATUS_ONGOING;
		}

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'edit';
			}
		} else {
			$action = 'edit';
		}
	} else {
		$action = 'edit';
	}
}

if ($action == 'confirm_merge' && $confirm == 'yes' && $user->hasRight('projet', 'creer')) {
	$task_origin_id = GETPOSTINT('task_origin');
	$task_origin = new Task($db);		// The Task that we will delete

	if ($task_origin_id <= 0) {
		$langs->load('errors');
		setEventMessages($langs->trans('ErrorTaskIdIsMandatory', $langs->transnoentitiesnoconv('MergeOriginTask')), null, 'errors');
	} else {
		if (!$error && $task_origin->fetch($task_origin_id) < 1) {
			setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
			$error++;
		}
		if (!$error) {
			$result = $object->mergeTask($task_origin_id);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('TaskMergeSuccess'), null, 'mesgs');
			}
		}
	}
}

if ($action == 'confirm_clone' && $confirm == 'yes') {
	//$clone_contacts = GETPOST('clone_contacts') ? 1 : 0;
	$clone_prog = GETPOST('clone_prog') ? 1 : 0;
	$clone_time = GETPOST('clone_time') ? 1 : 0;
	$clone_affectation = GETPOST('clone_affectation') ? 1 : 0;
	$clone_change_dt = GETPOST('clone_change_dt') ? 1 : 0;
	$clone_notes = GETPOST('clone_notes') ? 1 : 0;
	$clone_file = GETPOST('clone_file') ? 1 : 0;
	$result = $object->createFromClone($user, $object->id, $object->fk_project, $object->fk_task_parent, $clone_change_dt, $clone_affectation, $clone_time, $clone_file, $clone_notes, $clone_prog);
	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		// Load new object
		$newobject = new Task($db);
		$newobject->fetch($result);
		$newobject->fetch_optionals();
		$newobject->fetch_thirdparty(); // Load new object
		$object = $newobject;
		$action = '';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->hasRight('projet', 'supprimer')) {
	$result = $projectstatic->fetch($object->fk_project);
	$projectstatic->fetch_thirdparty();

	if ($object->delete($user) > 0) {
		header('Location: '.DOL_URL_ROOT.'/projet/tasks.php?restore_lastsearch_values=1&id='.$projectstatic->id.($withproject ? '&withproject=1' : ''));
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

if ($action == 'confirm_close' && $confirm == "yes" && $user->hasRight('projet', 'creer')) {
	$result = $projectstatic->fetch($object->fk_project);
	$projectstatic->fetch_thirdparty();

	if ($object->setStatusCommon($user, Task::STATUS_CLOSED) > 0) {
		header('Location: '.DOL_URL_ROOT.'/projet/tasks.php?restore_lastsearch_values=1&id='.$projectstatic->id.($withproject ? '&withproject=1' : ''));
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch(0, $project_ref) > 0) {
		$tasksarray = $object->getTasksArray(null, null, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode) ? '' : '&mode='.$mode));
		}
	}
}

// Build doc
if ($action == 'builddoc' && $user->hasRight('projet', 'creer')) {
	// Save last template used to generate document
	if (GETPOST('model')) {
		$object->setDocModel($user, GETPOST('model', 'alpha'));
	}

	$outputlangs = $langs;
	if (GETPOST('lang_id', 'aZ09')) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
	}
	$result = $object->generateDocument($object->model_pdf, $outputlangs);
	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

// Delete file in doc form
if ($action == 'remove_file' && $user->hasRight('projet', 'creer')) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$langs->load("other");
	$upload_dir = $conf->project->dir_output."/".dol_sanitizeFileName($projectstatic->ref)."/".dol_sanitizeFileName($object->ref);
	$file = $upload_dir.'/'.dol_sanitizeFileName(GETPOST('file'));

	$ret = dol_delete_file($file, 1);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
	}
}

// Reopen task
if ($action == 'reopen' && $user->hasRight('projet', 'creer')) {
	$result = $object->setStatusCommon($user, Task::STATUS_VALIDATED);
	if ($result >= 0) {
		setEventMessages($langs->trans("TaskReopened"), null, 'mesgs');
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $object->ref;
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '')  ;
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project project-tasks page-task');


if ($id > 0 || !empty($ref)) {
	$res = $object->fetch_optionals();
	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_TASK') && method_exists($object, 'fetchComments') && empty($object->comments)) {
		$object->fetchComments();
	}


	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
		$projectstatic->fetchComments();
	}
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}

	$object->project = clone $projectstatic;

	//$userWrite = $projectstatic->restrictedProjectArea($user, 'write');

	if (!empty($withproject)) {
		// Tabs for project
		$tab = 'tasks';
		$head = project_prepare_head($projectstatic);
		print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'), 0, '', '');

		$param = ($mode == 'mine' ? '&mode=mine' : '');

		// Project card

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $projectstatic->title;
		// Thirdparty
		if (!empty($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->hasRight('projet', 'all', 'lire')) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Usage
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}
			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
			}
			print '</td></tr>';
		}

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (isset($projectstatic->budget_amount) && strcmp($projectstatic->budget_amount, '')) {
			print '<span class="amount">'.price($projectstatic->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		}
		print '</td></tr>';

		// Date start - end project
		print '<tr><td>'.$langs->trans("Dates").'</td><td>';
		$start = dol_print_date($projectstatic->date_start, 'day');
		print($start ? $start : '?');
		$end = dol_print_date($projectstatic->date_end, 'day');
		print ' - ';
		print($end ? $end : '?');
		if ($projectstatic->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($projectstatic->public) {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans('SharedProject');
		} else {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans('PrivateProject');
		}
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		$savobject = $object;
		$object = $projectstatic;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
		$object = $savobject;

		print '</table>';

		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Categories
		if (isModEnabled('category')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($projectstatic->id, 'project', 1);
			print "</td></tr>";
		}

		// Description
		print '<tr><td class="titlefield'.($projectstatic->description ? ' noborderbottom' : '').'" colspan="2">'.$langs->trans("Description").'</td></tr>';
		if ($projectstatic->description) {
			print '<tr><td class="nottitleforfield" colspan="2">';
			print '<div class="longmessagecut">';
			print dolPrintHTML($projectstatic->description);
			print '</div>';
			print '</td></tr>';
		}

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		print '<br>';
	}

	/*
	 * Actions
	*/
	/*print '<div class="tabsAction">';

	if ($user->rights->projet->all->creer || $user->rights->projet->creer)
	{
	if ($projectstatic->public || $userWrite > 0)
	{
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create'.$param.'">'.$langs->trans('AddTask').'</a>';
	}
	else
	{
	print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
	}
	}
	else
	{
	print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';
	*/

	// To verify role of users
	//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
	//$arrayofuseridoftask=$object->getListContactId('internal');


	$head = task_prepare_head($object);

	if ($action == 'edit' && $user->hasRight('projet', 'creer')) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';

		print dol_get_fiche_head($head, 'task_task', $langs->trans("Task"), 0, 'projecttask', 0, '', '');

		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Ref").'</td>';
		print '<td><input class="minwidth100" name="taskref" value="'.$object->ref.'"></td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
		print '<td><input class="minwidth500" name="label" value="'.$object->label.'"></td></tr>';

		// Project
		if (empty($withproject)) {
			print '<tr><td>'.$langs->trans("Project").'</td><td>';
			print $projectstatic->getNomUrl(1);
			print '</td></tr>';

			// Third party
			print '<td>'.$langs->trans("ThirdParty").'</td><td>';
			if ($projectstatic->thirdparty->id) {
				print $projectstatic->thirdparty->getNomUrl(1);
			} else {
				print '&nbsp;';
			}
			print '</td></tr>';
		}

		// Task parent
		print '<tr><td>'.$langs->trans("ChildOfProjectTask").'</td><td>';
		print img_picto('', 'projecttask');
		$formother->selectProjectTasks($object->fk_task_parent, $projectstatic->id, 'task_parent', ($user->admin ? 0 : 1), 0, 0, 0, $object->id, '', 'minwidth100 widthcentpercentminusxx maxwidth500');
		print '</td></tr>';

		// Date start
		print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
		print $form->selectDate($object->date_start, 'date_start', 1, 1, 0, '', 1, 0);
		print '</td></tr>';

		// Date end
		print '<tr><td>'.$langs->trans("Deadline").'</td><td>';
		print $form->selectDate($object->date_end ? $object->date_end : -1, 'date_end', 1, 1, 0, '', 1, 0);
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
		print $form->select_duration('planned_workload', $object->planned_workload, 0, 'text');
		print '</td></tr>';

		// Progress declared
		print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td>';
		print $formother->select_percent($object->progress, 'progress', 0, 5, 0, 100, 1);
		print '</td></tr>';

		// Billable
		print '<tr><td>'.$langs->trans("Billable").'</td><td>';
		print $form->selectyesno('billable', $object->billable);
		print '</td></tr>';

		// Description

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
		print '<td>';

		// WYSIWYG editor
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = getDolGlobalInt('MAIN_INPUT_DESC_HEIGHT', 0);
		$doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_details', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), $nbrows, '90%');
		print $doleditor->Create();

		print '</td></tr>';


		print '<tr><td>'.$langs->trans("Budget").'</td>';
		print '<td><input class="width75" type="text" name="budget_amount" value="'.dol_escape_htmltag(GETPOSTISSET('budget_amount') ? GETPOST('budget_amount') : price2num($object->budget_amount)).'"></td>';
		print '</tr>';

		// Other options
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit');
		}

		print '</table>';

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Modify");

		print '</form>';
	} else {
		/*
		 * Fiche tache en mode visu
		 */
		$param = ($withproject ? '&withproject=1' : '');
		$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'&restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>' : '';

		print dol_get_fiche_head($head, 'task_task', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

		if ($action == 'clone') {
			$formquestion = array(
				'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_contacts', 'label' => $langs->trans("CloneContacts"), 'value' => true),
				0 => array('type' => 'checkbox', 'name' => 'clone_change_dt', 'label' => $langs->trans("CloneChanges"), 'value' => true),
				1 => array('type' => 'checkbox', 'name' => 'clone_affectation', 'label' => $langs->trans("CloneAffectation"), 'value' => true),
				2 => array('type' => 'checkbox', 'name' => 'clone_prog', 'label' => $langs->trans("CloneProgression"), 'value' => true),
				3 => array('type' => 'checkbox', 'name' => 'clone_time', 'label' => $langs->trans("CloneTimes"), 'value' => true),
				4 => array('type' => 'checkbox', 'name' => 'clone_file', 'label' => $langs->trans("CloneFile"), 'value' => true),
			);

			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ToClone"), $langs->trans("ConfirmCloneTask"), "confirm_clone", $formquestion, '', 1, 300, 590);
		}

		if ($action == 'close') {
			$formquestion = array(
				'text' => $langs->trans("ConfirmClosed"),
			);
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ToClose"), $langs->trans("ConfirmCloseTask"), "confirm_close", $formquestion, '', 1, 300, 590);
		}


		if ($action == 'merge') {
			$formquestion = array(
				array(
					'name' => 'task_origin',
					'label' => $langs->trans('MergeOriginTask'),
					'type' => 'other',
					'value' => $formproject->selectTasks(-1, 0, 'task_origin', 24, 0, $langs->trans('SelectTask'), 0, 0, 0, 'maxwidth500 minwidth200', '', '', null, 1)
				)
			);
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id.(GETPOST('withproject') ? "&withproject=1" : ""), $langs->trans("MergeTasks"), $langs->trans("ConfirmMergeTasks"), "confirm_merge", $formquestion, 'yes', 1, 250);
		}

		if ($action == 'delete') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".GETPOSTINT("id").'&withproject='.$withproject, $langs->trans("DeleteATask"), $langs->trans("ConfirmDeleteATask"), "confirm_delete");
		}

		if (!GETPOST('withproject') || empty($projectstatic->id)) {
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = "fk_projet:IN:".$db->sanitize($projectsListId);
		} else {
			$object->next_prev_filter = "fk_projet:=:".((int) $projectstatic->id);
		}

		$morehtmlref = '';

		// Project
		if (empty($withproject)) {
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $langs->trans("Project").': ';
			$morehtmlref .= $projectstatic->getNomUrl(1);
			$morehtmlref .= '<br>';

			// Third party
			$morehtmlref .= $langs->trans("ThirdParty").': ';
			if (!empty($projectstatic->thirdparty) && is_object($projectstatic->thirdparty)) {
				$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
			}
			$morehtmlref .= '</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Task parent
		print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
		if ($object->fk_task_parent > 0) {
			$tasktmp = new Task($db);
			$tasktmp->fetch($object->fk_task_parent);
			print $tasktmp->getNomUrl(1);
		}
		print '</td></tr>';

		// Date start - Date end task
		print '<tr><td class="titlefield">'.$langs->trans("DateStart").' - '.$langs->trans("Deadline").'</td><td colspan="3">';
		$start = dol_print_date($object->date_start, 'dayhour');
		print($start ? $start : '?');
		$end = dol_print_date($object->date_end, 'dayhour');
		print ' - ';
		print($end ? $end : '?');
		if ($object->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td colspan="3">';
		if ($object->planned_workload != '') {
			print convertSecondToTime($object->planned_workload, 'allhourmin');
		}
		print '</td></tr>';

		// Description
		print '<td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';
		print dol_htmlentitiesbr($object->description);
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Progress declared
		print '<tr><td class="titlefield">'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
		if ($object->progress != '' && $object->progress != '-1') {
			print $object->progress.' %';
		}
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td colspan="3">';
		if ($object->planned_workload != '') {
			$tmparray = $object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0 && !empty($object->planned_workload)) {
				print round($tmparray['total_duration'] / $object->planned_workload * 100, 2).' %';
			} else {
				print '0 %';
			}
		} else {
			print '<span class="opacitymedium">'.$langs->trans("WorkloadNotDefined").'</span>';
		}
		print '</td></tr>';

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (!is_null($object->budget_amount) && strcmp((string) $object->budget_amount, '')) {
			print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		}
		print '</td></tr>';

		// Billable
		print '<tr><td>'.$langs->trans("Billable").'</td><td>';
		print '<span>'.($object->billable ? $langs->trans('Yes') : $langs->trans('No')).'</span>';
		print '</td></tr>';


		// Other attributes
		$cols = 3;
		$parameters = array('socid' => $socid);
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();
	}


	if ($action != 'edit') {
		/*
		 * Actions
		  */

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		// modified by hook
		if (empty($reshook)) {
			// Modify
			if ($user->hasRight('projet', 'creer')) {
				if ($object->status != $object::STATUS_CLOSED) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken().'&withproject='.((int) $withproject).'">'.$langs->trans('Modify').'</a>';
				}
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken().'&withproject='.((int) $withproject).'">'.$langs->trans('Clone').'</a>';
				print '<a class="butActionDelete classfortooltip" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=merge&token='.newToken().'&withproject='.((int) $withproject).'" title="'.$langs->trans("MergeTasks").'">'.$langs->trans('Merge').'</a>';

				if ($object->status != $object::STATUS_CLOSED) {
					print '<a class="butAction classfortooltip" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken().'&withproject='.((int) $withproject).'" title="'.$langs->trans("Close").'">'.$langs->trans('Close').'</a>';
				}
				if ($object->status == $object::STATUS_CLOSED) {
					print '<a class="butAction classfortooltip" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken().'&withproject='.((int) $withproject).'" title="'.$langs->trans("ReOpen").'">'.$langs->trans('ReOpen').'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';
			}

			// Delete
			$permissiontodelete = $user->hasRight('projet', 'supprimer');
			if ($permissiontodelete) {
				if (!$object->hasChildren() && !$object->hasTimeSpent()) {
					print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&withproject='.((int) $withproject), 'delete', $permissiontodelete);
				} else {
					print dolGetButtonAction($langs->trans("TaskHasChild"), $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&withproject='.((int) $withproject), 'delete', 0);
				}
			} else {
				print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&withproject='.((int) $withproject), 'delete', $permissiontodelete);
			}

			print '</div>';
		}

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		/*
		 * Generated documents
		 */
		$filename = '';
		$filedir = $conf->project->dir_output."/".dol_sanitizeFileName($projectstatic->ref)."/".dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = ($user->hasRight('projet', 'lire'));
		$delallowed = ($user->hasRight('projet', 'creer'));

		print $formfile->showdocuments('project_task', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf);

		// Show links to link elements
		$tmparray = $form->showLinkToObjectBlock($object, array(), array('project_task'), 1);
		$linktoelem = $tmparray['linktoelem'];
		$htmltoenteralink = $tmparray['htmltoenteralink'];
		print $htmltoenteralink;

		$compatibleImportElementsList = array();
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

		print '</div><div class="fichehalfright">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$formactions->showactions($object, 'project_task', 0, 1, '', 10, 'withproject='.$withproject);

		print '</div></div>';
	}
}

// End of page
llxFooter();
$db->close();
