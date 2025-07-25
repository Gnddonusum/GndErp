<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/projet/tasks.php
 *	\ingroup    project
 *	\brief      List all tasks of a project
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langsLoad = array('projects', 'users', 'companies');
if (isModEnabled('eventorganization')) {
	$langsLoad[] = 'eventorganization';
}

$langs->loadLangs($langsLoad);

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
//$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'projecttasklist';
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
//$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$optioncss  = GETPOST('optioncss', 'aZ');
$backtopage = GETPOST('backtopage', 'alpha');
$toselect = GETPOST('toselect', 'array');

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$taskref = GETPOST('taskref', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_user_id = GETPOSTINT('search_user_id');
$search_taskref = GETPOST('search_taskref');
$search_tasklabel = GETPOST('search_tasklabel');
$search_taskdescription = GETPOST('search_taskdescription');
$search_dtstartday = GETPOST('search_dtstartday');
$search_dtstartmonth = GETPOST('search_dtstartmonth');
$search_dtstartyear = GETPOST('search_dtstartyear');
$search_dtendday = GETPOST('search_dtendday');
$search_dtendmonth = GETPOST('search_dtendmonth');
$search_dtendyear = GETPOST('search_dtendyear');
$search_planedworkload = GETPOST('search_planedworkload');
$search_timespend = GETPOST('search_timespend');
$search_progresscalc = GETPOST('search_progresscalc');
$search_progressdeclare = GETPOST('search_progressdeclare');
$search_task_budget_amount = GETPOST('search_task_budget_amount');
$search_task_billable = GETPOST('search_task_billable');
$search_status = GETPOST('search_status');

$search_date_start_startmonth = GETPOSTINT('search_date_start_startmonth');
$search_date_start_startyear = GETPOSTINT('search_date_start_startyear');
$search_date_start_startday = GETPOSTINT('search_date_start_startday');
$search_date_start_start = dol_mktime(0, 0, 0, $search_date_start_startmonth, $search_date_start_startday, $search_date_start_startyear);	// Use tzserver
$search_date_start_endmonth = GETPOSTINT('search_date_start_endmonth');
$search_date_start_endyear = GETPOSTINT('search_date_start_endyear');
$search_date_start_endday = GETPOSTINT('search_date_start_endday');
$search_date_start_end = dol_mktime(23, 59, 59, $search_date_start_endmonth, $search_date_start_endday, $search_date_start_endyear);	// Use tzserver

$search_date_end_startmonth = GETPOSTINT('search_date_end_startmonth');
$search_date_end_startyear = GETPOSTINT('search_date_end_startyear');
$search_date_end_startday = GETPOSTINT('search_date_end_startday');
$search_date_end_start = dol_mktime(0, 0, 0, $search_date_end_startmonth, $search_date_end_startday, $search_date_end_startyear);	// Use tzserver
$search_date_end_endmonth = GETPOSTINT('search_date_end_endmonth');
$search_date_end_endyear = GETPOSTINT('search_date_end_endyear');
$search_date_end_endday = GETPOSTINT('search_date_end_endday');
$search_date_end_end = dol_mktime(23, 59, 59, $search_date_end_endmonth, $search_date_end_endday, $search_date_end_endyear);	// Use tzserver

//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
$taskstatic = new Task($db);
$extrafields = new ExtraFields($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'
if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

if ($id > 0 || !empty($ref)) {
	// fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);
}
$extrafields->fetch_name_optionals_label($taskstatic->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($taskstatic->table_element, '', 'search_');


// Default sort order (if not yet defined by previous GETPOST)
/* if (!$sortfield) {
	reset($object->fields); $sortfield="t.".key($object->fields);
}   // Set here default search field. By default 1st field in definition. Reset is required to avoid key() to return null.
if (!$sortorder) {
	$sortorder = "ASC";
} */


// Security check
$socid = 0;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projecttaskscard', 'globalcard'));

//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

$diroutputmassaction = $conf->project->dir_output.'/tasks/temp/massgeneration/'.$user->id;

$progress = GETPOSTINT('progress');
$budget_amount = GETPOSTFLOAT('budget_amount');
$billable = (GETPOST('billable', 'aZ') == 'yes' ? 1 : 0);
$label = GETPOST('label', 'alpha');
$description = GETPOST('description', 'restricthtml');
$planned_workloadhour = (GETPOSTISSET('planned_workloadhour') ? GETPOSTINT('planned_workloadhour') : '');
$planned_workloadmin = (GETPOSTISSET('planned_workloadmin') ? GETPOSTINT('planned_workloadmin') : '');
if (GETPOSTISSET('planned_workloadhour') || GETPOSTISSET('planned_workloadmin')) {
	$planned_workload = (int) $planned_workloadhour * 3600 + (int) $planned_workloadmin * 60;
} else {
	$planned_workload = '';
}

// Definition of fields for list
$arrayfields = array(
	't.ref' => array('label' => "RefTask", 'checked' => '1', 'position' => 1),
	't.label' => array('label' => "LabelTask", 'checked' => '1', 'position' => 2),
	't.description' => array('label' => "Description", 'checked' => '0', 'position' => 3),
	't.dateo' => array('label' => "DateStart", 'checked' => '1', 'position' => 4),
	't.datee' => array('label' => "Deadline", 'checked' => '1', 'position' => 5),
	't.planned_workload' => array('label' => "PlannedWorkload", 'checked' => '1', 'position' => 6),
	't.duration_effective' => array('label' => "TimeSpent", 'checked' => '1', 'position' => 7),
	't.progress_calculated' => array('label' => "ProgressCalculated", 'checked' => '1', 'position' => 8),
	't.progress' => array('label' => "ProgressDeclared", 'checked' => '1', 'position' => 9),
	't.progress_summary' => array('label' => "TaskProgressSummary", 'checked' => '1', 'position' => 10),
	't.fk_statut' => array('label' => "Status", 'checked' => '1', 'position' => 11),
	't.budget_amount' => array('label' => "Budget", 'checked' => '0', 'position' => 12),
	'c.assigned' => array('label' => "TaskRessourceLinks", 'checked' => '1', 'position' => 13),

);
if ($object->usage_bill_time) {
	$arrayfields['t.tobill'] = array('label' => $langs->trans("TimeToBill"), 'checked' => '0', 'position' => 11);
	$arrayfields['t.billed'] = array('label' => $langs->trans("TimeBilled"), 'checked' => '0', 'position' => 12);
	$arrayfields['t.billable'] = array('label' => $langs->trans("Billable"), 'checked' => '1', 'position' => 13);
}

// Extra fields
$extrafieldsobjectkey = $taskstatic->table_element;
$extrafieldsobjectprefix = 'efpt.';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$arrayfields = dol_sort_array($arrayfields, 'position');

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;


/*
 * Actions
 */
$error = 0;

if ($cancel) {
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_user_id = "";
		$search_taskref = '';
		$search_tasklabel = '';
		$search_dtstartday = '';
		$search_dtstartmonth = '';
		$search_dtstartyear = '';
		$search_dtendday = '';
		$search_dtendmonth = '';
		$search_dtendyear = '';
		$search_planedworkload = '';
		$search_timespend = '';
		$search_progresscalc = '';
		$search_progressdeclare = '';
		$search_task_budget_amount = '';
		$search_task_billable = '';
		$search_status = -1;
		$toselect = array();
		$search_array_options = array();
		$search_date_start_startmonth = "";
		$search_date_start_startyear = "";
		$search_date_start_startday = "";
		$search_date_start_start = "";
		$search_date_start_endmonth = "";
		$search_date_start_endyear = "";
		$search_date_start_endday = "";
		$search_date_start_end = "";
		$search_date_end_startmonth = "";
		$search_date_end_startyear = "";
		$search_date_end_startday = "";
		$search_date_end_start = "";
		$search_date_end_endmonth = "";
		$search_date_end_endyear = "";
		$search_date_end_endday = "";
		$search_date_end_end = "";
	}

	// Mass actions
	$objectclass = 'Task';
	$objectlabel = 'Tasks';
	$permissiontoread = $user->hasRight('projet', 'lire');
	$permissiontodelete = $user->hasRight('projet', 'supprimer');
	$uploaddir = $conf->project->dir_output.'/tasks';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

$morewherefilterarray = array();

if (!empty($search_taskref)) {
	$morewherefilterarray[] = natural_search('t.ref', $search_taskref, 0, 1);
}

if (!empty($search_tasklabel)) {
	$morewherefilterarray[] = natural_search('t.label', $search_tasklabel, 0, 1);
}

$moresql = dolSqlDateFilter('t.dateo', $search_dtstartday, $search_dtstartmonth, $search_dtstartyear, 1);
if ($moresql) {
	$morewherefilterarray[] = $moresql;
}

$moresql = dolSqlDateFilter('t.datee', $search_dtendday, $search_dtendmonth, $search_dtendyear, 1);
if ($moresql) {
	$morewherefilterarray[] = $moresql;
}

if ($search_date_start_start) {
	$morewherefilterarray[] = " t.dateo >= '".$db->idate($search_date_start_start)."'";
}
if ($search_date_start_end) {
	$morewherefilterarray[] = " t.dateo <= '".$db->idate($search_date_start_end)."'";
}

if ($search_date_end_start) {
	$morewherefilterarray[] = " t.datee >= '".$db->idate($search_date_end_start)."'";
}
if ($search_date_end_end) {
	$morewherefilterarray[] = " t.datee <= '".$db->idate($search_date_end_end)."'";
}

if (!empty($search_planedworkload)) {
	$morewherefilterarray[] = natural_search('t.planned_workload', $search_planedworkload, 1, 1);
}

if (!empty($search_timespend)) {
	$morewherefilterarray[] = natural_search('t.duration_effective', $search_timespend, 1, 1);
}

if (!empty($search_progressdeclare)) {
	$morewherefilterarray[] = natural_search('t.progress', $search_progressdeclare, 1, 1);
}
if (!empty($search_progresscalc)) {
	$morewherefilterarray[] = '(planned_workload IS NULL OR planned_workload = 0 OR '.natural_search('ROUND(100 * duration_effective / planned_workload, 2)', $search_progresscalc, 1, 1).')';
	//natural_search('round(100 * $line->duration_effective / $line->planned_workload,2)', $filterprogresscalc, 1, 1).' {return 1;} else {return 0;}';
}
if ($search_status > -1 && $search_status != '') {
	$morewherefilterarray[] = " t.fk_statut = ".((int) $search_status);
}
if ($search_task_budget_amount) {
	$morewherefilterarray[] = natural_search('t.budget_amount', $search_task_budget_amount, 1, 1);
}
if ($search_task_billable && $search_task_billable != '-1') {
	$morewherefilterarray[] = " t.billable = ".($search_task_billable == "yes" ? 1 : 0);
}
//var_dump($morewherefilterarray);

$morewherefilter = '';
if (count($morewherefilterarray) > 0) {
	$morewherefilter = ' AND '.implode(' AND ', $morewherefilterarray);
}

if ($action == 'createtask' && $user->hasRight('projet', 'creer')) {
	// If we use user timezone, we must change also view/list to use user timezone everywhere
	$date_start = dol_mktime(GETPOSTINT('date_starthour'), GETPOSTINT('date_startmin'), 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
	$date_end = dol_mktime(GETPOSTINT('date_endhour'), GETPOSTINT('date_endmin'), 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));

	if (!$cancel) {
		if (empty($taskref)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$action = 'create';
			$error++;
		}
		if (empty($label)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
			$action = 'create';
			$error++;
		} elseif (!GETPOST('task_parent')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ChildOfProjectTask")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$tmparray = explode('_', GETPOST('task_parent'));
			$projectid = empty($tmparray[0]) ? $id : (int) $tmparray[0];
			$task_parent = empty($tmparray[1]) ? 0 : $tmparray[1];

			$task = new Task($db);

			$task->fk_project = $projectid;
			$task->entity = $object->entity; // Task have the same entity of project
			$task->ref = $taskref;
			$task->label = $label;
			$task->description = $description;
			$task->planned_workload = $planned_workload;
			$task->fk_task_parent = $task_parent;
			$task->date_c = dol_now();
			$task->date_start = $date_start;
			$task->date_end = $date_end;
			$task->progress = $progress;
			$task->budget_amount = $budget_amount;
			$task->billable = $billable;
			$task->status = Task::STATUS_VALIDATED;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $task);

			$taskid = $task->create($user);

			if ($taskid > 0) {
				$result = $task->add_contact(GETPOSTINT("userid"), 'TASKEXECUTIVE', 'internal');
			} else {
				if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("projects");
					setEventMessages($langs->trans('NewTaskRefSuggested'), null, 'warnings');
					$duplicate_code_error = true;
				} else {
					setEventMessages($task->error, $task->errors, 'errors');
				}
				$action = 'create';
				$error++;
			}
		}

		if (!$error) {
			if (!empty($backtopage)) {
				header("Location: ".$backtopage);
				exit;
			} elseif (empty($projectid)) {
				header("Location: ".DOL_URL_ROOT.'/projet/tasks/list.php'.(empty($mode) ? '' : '?mode='.$mode));
				exit;
			}
			$id = $projectid;
		}
	} else {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		} elseif (empty($id)) {
			// We go back on task list
			header("Location: ".DOL_URL_ROOT.'/projet/tasks/list.php'.(empty($mode) ? '' : '?mode='.$mode));
			exit;
		}
	}
}

/*
 * View
 */

$now = dol_now();
$form = new Form($db);
$formother = new FormOther($db);
$socstatic = new Societe($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);
$userstatic = new User($db);

$title = $langs->trans("Tasks").' - '.$object->ref.' '.$object->name;
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/projectnameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->ref.' '.$object->name.' - '.$langs->trans("Tasks");
}
if ($action == 'create') {
	$title = $langs->trans("NewTask");
}
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-project page-card_tasks');

$arrayofselected = is_array($toselect) ? $toselect : array();
$param = '';
$userWrite = 0;

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	}
	$result = $object->fetch_optionals();
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	}


	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite = $object->restrictedProjectArea($user, 'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


	$tab = (GETPOSTISSET('tab') ? GETPOST('tab') : 'tasks');

	$head = project_prepare_head($object);
	print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));

	$param = '&id='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($search_user_id) {
		$param .= '&search_user_id='.urlencode((string) ($search_user_id));
	}
	if ($search_taskref) {
		$param .= '&search_taskref='.urlencode($search_taskref);
	}
	if ($search_tasklabel) {
		$param .= '&search_tasklabel='.urlencode($search_tasklabel);
	}
	if ($search_taskdescription) {
		$param .= '&search_taskdescription='.urlencode($search_taskdescription);
	}
	if ($search_dtstartday) {
		$param .= '&search_dtstartday='.urlencode($search_dtstartday);
	}
	if ($search_dtstartmonth) {
		$param .= '&search_dtstartmonth='.urlencode($search_dtstartmonth);
	}
	if ($search_dtstartyear) {
		$param .= '&search_dtstartyear='.urlencode($search_dtstartyear);
	}
	if ($search_dtendday) {
		$param .= '&search_dtendday='.urlencode($search_dtendday);
	}
	if ($search_dtendmonth) {
		$param .= '&search_dtendmonth='.urlencode($search_dtendmonth);
	}
	if ($search_dtendyear) {
		$param .= '&search_dtendyear='.urlencode($search_dtendyear);
	}
	if ($search_date_start_startmonth) {
		$param .= '&search_date_start_startmonth='.urlencode((string) ($search_date_start_startmonth));
	}
	if ($search_date_start_startyear) {
		$param .= '&search_date_start_startyear='.urlencode((string) ($search_date_start_startyear));
	}
	if ($search_date_start_startday) {
		$param .= '&search_date_start_startday='.urlencode((string) ($search_date_start_startday));
	}
	if ($search_date_start_start) {
		$param .= '&search_date_start_start='.urlencode((string) $search_date_start_start);
	}
	if ($search_date_start_endmonth) {
		$param .= '&search_date_start_endmonth='.urlencode((string) ($search_date_start_endmonth));
	}
	if ($search_date_start_endyear) {
		$param .= '&search_date_start_endyear='.urlencode((string) ($search_date_start_endyear));
	}
	if ($search_date_start_endday) {
		$param .= '&search_date_start_endday='.urlencode((string) ($search_date_start_endday));
	}
	if ($search_date_start_end) {
		$param .= '&search_date_start_end='.urlencode((string) $search_date_start_end);
	}
	if ($search_date_end_startmonth) {
		$param .= '&search_date_end_startmonth='.urlencode((string) ($search_date_end_startmonth));
	}
	if ($search_date_end_startyear) {
		$param .= '&search_date_end_startyear='.urlencode((string) ($search_date_end_startyear));
	}
	if ($search_date_end_startday) {
		$param .= '&search_date_end_startday='.urlencode((string) ($search_date_end_startday));
	}
	if ($search_date_end_start) {
		$param .= '&search_date_end_start='.urlencode((string) $search_date_end_start);
	}
	if ($search_date_end_endmonth) {
		$param .= '&search_date_end_endmonth='.urlencode((string) ($search_date_end_endmonth));
	}
	if ($search_date_end_endyear) {
		$param .= '&search_date_end_endyear='.urlencode((string) ($search_date_end_endyear));
	}
	if ($search_date_end_endday) {
		$param .= '&search_date_end_endday='.urlencode((string) ($search_date_end_endday));
	}
	if ($search_date_end_end) {
		$param .= '&search_date_end_end=' . urlencode((string) $search_date_end_end);
	}
	if ($search_planedworkload) {
		$param .= '&search_planedworkload='.urlencode($search_planedworkload);
	}
	if ($search_timespend) {
		$param .= '&search_timespend='.urlencode($search_timespend);
	}
	if ($search_progresscalc) {
		$param .= '&search_progresscalc='.urlencode($search_progresscalc);
	}
	if ($search_progressdeclare) {
		$param .= '&search_progressdeclare='.urlencode($search_progressdeclare);
	}
	if ($search_status) {
		$param .= '&search_status='.urlencode((string) ($search_status));
	}
	if ($search_task_budget_amount) {
		$param .= '&search_task_budget_amount='.urlencode($search_task_budget_amount);
	}
	if ($search_task_billable) {
		$param .= '&search_task_billable='.urlencode($search_task_billable);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$arrayofmassactions = array();
	if ($user->hasRight('projet', 'creer')) {
		$arrayofmassactions['preclonetasks'] = img_picto('', 'clone', 'class="pictofixedwidth"').$langs->trans("Clone");
	}
	if ($permissiontodelete) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Project card

	if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
		$tmpurl = $_SESSION['pageforbacktolist']['project'];
		$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
		$linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	} else {
		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= $object->title;
	// Thirdparty
	if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (!$user->hasRight('projet', 'all', 'lire')) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
			print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowOpportunity");
			print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowTasks");
			print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
			print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectBillTimeDescription");
			print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
			print '<br>';
		}
		if (isModEnabled('eventorganization')) {
			print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("EventOrganizationDescriptionLong");
			print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
		}
		print '</td></tr>';
	}

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
		print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
	}
	print '</td></tr>';

	// Date start - end project
	print '<tr><td>'.$langs->trans("Dates").'</td><td>';
	$start = dol_print_date($object->date_start, 'day');
	print($start ? $start : '?');
	$end = dol_print_date($object->date_end, 'day');
	print ' - ';
	print($end ? $end : '?');
	if ($object->hasDelay()) {
		print img_warning("Late");
	}
	print '</td></tr>';

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) {
		print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
		print $langs->trans('SharedProject');
	} else {
		print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
		print $langs->trans('PrivateProject');
	}
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Categories
	if (isModEnabled('category')) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
		print "</td></tr>";
	}

	// Description
	print '<tr><td class="titlefield'.($object->description ? ' noborderbottom' : '').'" colspan="2">'.$langs->trans("Description").'</td></tr>';
	if ($object->description) {
		print '<tr><td class="nottitleforfield" colspan="2">';
		print '<div class="longmessagecut">';
		print dolPrintHTML($object->description);
		print '</div>';
		print '</td></tr>';
	}

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	print dol_get_fiche_end();
}


if ($action == 'create' && $user->hasRight('projet', 'creer') && (empty($object->thirdparty->id) || $userWrite > 0)) {
	if ($id > 0 || !empty($ref)) {
		print '<br>';
	}

	print load_fiche_titre($langs->trans("NewTask"), '', 'projecttask');

	$projectoktoentertime = 1;
	if ($object->id > 0 && $object->status == Project::STATUS_CLOSED) {
		$projectoktoentertime = 0;
		print '<div class="warning">';
		$langs->load("errors");
		print $langs->trans("WarningProjectClosed");
		print '</div>';
	}

	if ($object->id > 0 && $object->status == Project::STATUS_DRAFT) {
		$projectoktoentertime = 0;
		print '<div class="warning">';
		$langs->load("errors");
		print $langs->trans("WarningProjectDraft");
		print '</div>';
	}

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="createtask">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if (!empty($object->id)) {
		print '<input type="hidden" name="id" value="'.$object->id.'">';
	}

	print dol_get_fiche_head();

	print '<div class="div-table-responsive-no-min">';
	print '<table class="border centpercent">';

	$defaultref = '';
	$classnamemodtask = getDolGlobalString('PROJECT_TASK_ADDON', 'mod_task_simple');
	if (getDolGlobalString('PROJECT_TASK_ADDON') && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').".php")) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').'.php';
		$modTask = new $classnamemodtask();
		'@phan-var-force ModeleNumRefTask $modTask';
		$defaultref = $modTask->getNextValue($object->thirdparty, $object);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) {
		$defaultref = '';
	}

	// Ref
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
	if (empty($duplicate_code_error)) {
		print(GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : $defaultref);
	} else {
		print $defaultref;
	}
	print '<input type="hidden" name="taskref" value="'.(GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : $defaultref).'">';
	print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input type="text" name="label" autofocus class="minwidth500" value="'.$label.'">';
	print '</td></tr>';

	// Project
	print '<tr><td class="fieldrequired">'.$langs->trans("ChildOfProjectTask").'</td><td>';
	print img_picto('', 'project', 'class="pictofixedwidth"');
	if ($projectoktoentertime) {
		$formother->selectProjectTasks(GETPOSTINT('task_parent'), empty($projectid) ? $object->id : $projectid, 'task_parent', 0, 0, 1, 1, 0, '0,1', 'maxwidth500 widthcentpercentminusxx');
	} else {
		$formother->selectProjectTasks(GETPOSTINT('task_parent'), empty($projectid) ? $object->id : $projectid, 'task_parent', 0, 0, 1, 1, 0, '', 'maxwidth500 widthcentpercentminusxx');
	}
	print '</td></tr>';

	$contactsofproject = (empty($object->id) ? '' : $object->getListContactId('internal'));

	// Assigned to
	print '<tr><td>'.$langs->trans("AffectedTo").'</td><td>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	if (is_array($contactsofproject) && count($contactsofproject)) {
		print $form->select_dolusers($user->id, 'userid', 0, null, 0, '', $contactsofproject, '0', 0, 0, '', 0, '', 'maxwidth500 widthcentpercentminusx');
	} else {
		if ((isset($projectid) && $projectid > 0) || $object->id > 0) {
			print '<span class="opacitymedium">'.$langs->trans("NoUserAssignedToTheProject").'</span>';
		} else {
			print $form->select_dolusers($user->id, 'userid', 0, null, 0, '', '', '0', 0, 0, '', 0, '', 'maxwidth500 widthcentpercentminusx');
		}
	}
	print '</td></tr>';

	// Billable
	print '<tr><td>'.$langs->trans("Billable").'</td><td>';
	print $form->selectyesno('billable');
	print '</td></tr>';

	// Date start task
	print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
	print img_picto('', 'action', 'class="pictofixedwidth"');
	print $form->selectDate((!empty($date_start) ? $date_start : ''), 'date_start', 1, 1, 0, '', 1, 1);
	print '</td></tr>';

	// Date end task
	print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
	print img_picto('', 'action', 'class="pictofixedwidth"');
	print $form->selectDate((!empty($date_end) ? $date_end : -1), 'date_end', -1, 1, 0, '', 1, 1);
	print '</td></tr>';

	// Planned workload
	print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
	print img_picto('', 'clock', 'class="pictofixedwidth"');
	print $form->select_duration('planned_workload', $planned_workload, 0, 'text');
	print '</td></tr>';

	// Progress
	print '<tr><td>'.$langs->trans("ProgressDeclared").'</td><td colspan="3">';
	print img_picto('', 'fa-percent', 'class="pictofixedwidth"');
	print $formother->select_percent($progress, 'progress', 0, 5, 0, 100, 1);
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
	print '<td>';

	// WYSIWYG editor
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$nbrows = 0;
	if (getDolGlobalString('MAIN_INPUT_DESC_HEIGHT')) {
		$nbrows = getDolGlobalString('MAIN_INPUT_DESC_HEIGHT');
	}
	$doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_details', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), $nbrows, '90%');
	print $doleditor->Create();

	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	print img_picto('', 'currency', 'class="pictofixedwidth"');
	print '<input size="8" type="text" name="budget_amount" value="'.dol_escape_htmltag(GETPOSTISSET('budget_amount') ? GETPOST('budget_amount') : '').'"></td>';
	print '</tr>';

	// Other options
	$parameters = array('arrayfields' => &$arrayfields);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $taskstatic, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (empty($reshook) && !empty($extrafields->attributes[$taskstatic->table_element]['label'])) {
		print $taskstatic->showOptionals($extrafields, 'edit'); // Do not use $object here that is object of project but use $taskstatic
	}

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Add");

	print '</form>';
} elseif ($id > 0 || !empty($ref)) {
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields

	// Projet card in view mode

	print '<br>';

	// Link to create task
	$linktocreatetaskParam = array();
	$linktocreatetaskUserRight = false;
	if ($user->hasRight('projet', 'all', 'creer') || $user->hasRight('projet', 'creer')) {
		if ($object->public || $userWrite > 0) {
			$linktocreatetaskUserRight = true;
		} else {
			$linktocreatetaskParam['attr']['title'] = $langs->trans("NotOwnerOfProject");
		}
	}

	$linktocreatetask = dolGetButtonTitle($langs->trans('AddTask'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/tasks.php?action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id), '', (int) $linktocreatetaskUserRight, $linktocreatetaskParam);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$title = $langs->trans("ListOfTasks");
	$linktotasks = dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id, '', 1, array('morecss' => 'reposition btnTitleSelected'));
	$linktotasks .= dolGetButtonTitle($langs->trans('ViewGantt'), '', 'fa fa-stream imgforviewmode', DOL_URL_ROOT.'/projet/ganttview.php?id='.$object->id.'&withproject=1', '', 1, array('morecss' => 'reposition marginleftonly'));

	//print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $linktotasks, $num, $totalnboflines, 'generic', 0, '', '', 0, 1);
	print load_fiche_titre($title, $linktotasks.' &nbsp; '.$linktocreatetask, 'projecttask', 0, '', '', $massactionbutton);

	$objecttmp = new Task($db);
	$trackid = 'task'.$taskstatic->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	// Get list of tasks in tasksarray and taskarrayfiltered
	// We need all tasks (even not limited to a user because a task to user can have a parent that is not affected to him).
	$filteronthirdpartyid = $socid;
	$tasksarray = $taskstatic->getTasksArray(null, null, $object->id, $filteronthirdpartyid, 0, '', '-1', $morewherefilter, 0, 0, $extrafields, 1, $search_array_options, 1, 1, $sortfield, $sortorder);

	// We load also tasks limited to a particular user
	$tmpuser = new User($db);
	if ($search_user_id > 0) {
		$tmpuser->fetch($search_user_id);
	}

	$tasksrole = ($tmpuser->id > 0 ? $taskstatic->getUserRolesForProjectsOrTasks(null, $tmpuser, (string) $object->id, 0) : '');
	//var_dump($tasksarray);
	//var_dump($tasksrole);

	if (!empty($conf->use_javascript_ajax)) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	// Filter on assigned users
	$moreforfilter = '';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= img_picto('', 'user', 'class="pictofixedwidth"');
	$moreforfilter .= $form->select_dolusers($tmpuser->id > 0 ? $tmpuser->id : '', 'search_user_id', $langs->trans("TasksAssignedTo"), null, 0, '', '');
	$moreforfilter .= '</div>';
	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	// Show the massaction checkboxes only when this page is not opened from the Extended POS
	if ($massactionbutton && $contextpage != 'poslist') {
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}

	print '<div class="div-table-responsive">';
	print '<table id="tablelines" class="tagtable nobottom liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	if (!empty($arrayfields['t.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth50" type="text" name="search_taskref" value="'.dol_escape_htmltag($search_taskref).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.label']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth100" type="text" name="search_tasklabel" value="'.dol_escape_htmltag($search_tasklabel).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.description']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat searchstring maxwidth100" type="text" name="search_taskdescription" value="'.dol_escape_htmltag($search_taskdescription).'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.dateo']['checked'])) {
		print '<td class="liste_titre center">';
		/*print '<span class="nowraponall"><input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtstartday" value="'.$search_dtstartday.'">';
		print '<input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtstartmonth" value="'.$search_dtstartmonth.'"></span>';
		print $formother->selectyear($search_dtstartyear ? $search_dtstartyear : -1, 'search_dtstartyear', 1, 20, 5);*/
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_start_start ? $search_date_start_start : -1, 'search_date_start_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_start_end ? $search_date_start_end : -1, 'search_date_start_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	if (!empty($arrayfields['t.datee']['checked'])) {
		print '<td class="liste_titre center">';
		/*print '<span class="nowraponall"><input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtendday" value="'.$search_dtendday.'">';
		print '<input class="flat valignmiddle width20" type="text" maxlength="2" name="search_dtendmonth" value="'.$search_dtendmonth.'"></span>';
		print $formother->selectyear($search_dtendyear ? $search_dtendyear : -1, 'search_dtendyear', 1, 20, 5);*/
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_end_start ? $search_date_end_start : -1, 'search_date_end_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_end_end ? $search_date_end_end : -1, 'search_date_end_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	if (!empty($arrayfields['t.planned_workload']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_planedworkload" value="'.$search_planedworkload.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.duration_effective']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_timespend" value="'.$search_timespend.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.progress_calculated']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_progresscalc" value="'.$search_progresscalc.'">';
		print '</td>';
	}

	if (!empty($arrayfields['t.progress']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_progressdeclare" value="'.$search_progressdeclare.'">';
		print '</td>';
	}

	// progress resume not searchable
	if (!empty($arrayfields['t.progress_summary']['checked'])) {
		print '<td class="liste_titre right"></td>';
	}

	if (!empty($arrayfields['t.fk_statut']['checked'])) {
		print '<td class="liste_titre center">';
		$arrayofstatus = array();
		foreach ($taskstatic->labelStatusShort as $key => $val) {
			$arrayofstatus[$key] = $langs->trans($val);
		}
		print $form->selectarray('search_status', $arrayofstatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
		print '</td>';
	}

	if ($object->usage_bill_time) {
		if (!empty($arrayfields['t.tobill']['checked'])) {
			print '<td class="liste_titre right">';
			print '</td>';
		}

		if (!empty($arrayfields['t.billed']['checked'])) {
			print '<td class="liste_titre right">';
			print '</td>';
		}
	}

	if (!empty($arrayfields['t.budget_amount']['checked'])) {
		print '<td class="liste_titre center">';
		print '<input type="text" class="flat" name="search_task_budget_amount" value="'.$search_task_budget_amount.'" size="4">';
		print '</td>';
	}

	if (!empty($arrayfields['c.assigned']['checked'])) {
		print '<td class="liste_titre right">';
		print '</td>';
	}

	if (!empty($arrayfields['t.billable']['checked'])) {
		print '<td class="liste_titre center">';
		print $form->selectyesno('search_task_billable', $search_task_billable, 0, false, 1);
		print '</td>';
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '<td class="liste_titre maxwidthsearch">&nbsp;</td>';

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print "</tr>\n";

	print '<tr class="liste_titre nodrag nodrop">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}
	// print '<td>'.$langs->trans("Project").'</td>';
	if (!empty($arrayfields['t.ref']['checked'])) {
		// @phan-suppress-next-line PhanTypeInvalidDimOffset
		print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], 't.ref', '', $param, '', $sortfield, $sortorder, '');
	}
	if (!empty($arrayfields['t.label']['checked'])) {
		print_liste_field_titre($arrayfields['t.label']['label'], $_SERVER["PHP_SELF"], "t.label", '', $param, '', $sortfield, $sortorder, '');
	}
	if (!empty($arrayfields['t.description']['checked'])) {
		print_liste_field_titre($arrayfields['t.description']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, '');
	}
	if (!empty($arrayfields['t.dateo']['checked'])) {
		print_liste_field_titre($arrayfields['t.dateo']['label'], $_SERVER["PHP_SELF"], "t.dateo", '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['t.datee']['checked'])) {
		print_liste_field_titre($arrayfields['t.datee']['label'], $_SERVER["PHP_SELF"], "t.datee", '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['t.planned_workload']['checked'])) {
		print_liste_field_titre($arrayfields['t.planned_workload']['label'], $_SERVER["PHP_SELF"], "t.planned_workload", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	}
	if (!empty($arrayfields['t.duration_effective']['checked'])) {
		print_liste_field_titre($arrayfields['t.duration_effective']['label'], $_SERVER["PHP_SELF"], "t.duration_effective", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	}
	if (!empty($arrayfields['t.progress_calculated']['checked'])) {
		print_liste_field_titre($arrayfields['t.progress_calculated']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	}
	if (!empty($arrayfields['t.progress']['checked'])) {
		print_liste_field_titre($arrayfields['t.progress']['label'], $_SERVER["PHP_SELF"], "t.progress", '', $param, '', $sortfield, $sortorder, 'right ', '', 1);
	}
	if (!empty($arrayfields['t.progress_summary']['checked'])) {
		print_liste_field_titre($arrayfields['t.progress_summary']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '', 1);
	}
	if (!empty($arrayfields['t.fk_statut']['checked'])) {
		print_liste_field_titre($arrayfields['t.fk_statut']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '');
	}
	if ($object->usage_bill_time) {
		if (!empty($arrayfields['t.tobill']['checked'])) {
			print_liste_field_titre($arrayfields['t.tobill']['label'], $_SERVER["PHP_SELF"], "t.tobill", '', $param, '', $sortfield, $sortorder, 'right ');
		}
		if (!empty($arrayfields['t.billed']['checked'])) {
			print_liste_field_titre($arrayfields['t.billed']['label'], $_SERVER["PHP_SELF"], "t.billed", '', $param, '', $sortfield, $sortorder, 'right ');
		}
	}

	if (!empty($arrayfields['t.budget_amount']['checked'])) {
		print_liste_field_titre($arrayfields['t.budget_amount']['label'], $_SERVER["PHP_SELF"], "t.budget_amount", "", $param, '', $sortfield, $sortorder, 'center ');
	}

	if (!empty($arrayfields['c.assigned']['checked'])) {
		print_liste_field_titre($arrayfields['c.assigned']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '');
	}

	if (!empty($arrayfields['t.billable']['checked'])) {
		print_liste_field_titre($arrayfields['t.billable']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '');
	}

	// Extra fields
	$disablesortlink = 1;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '<td></td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}
	print "</tr>\n";

	$nboftaskshown = 0;
	if (count($tasksarray) > 0) {
		// Show all lines in taskarray (recursive function to go down on tree)
		$j = 0;
		$level = 0;
		$nboftaskshown = projectLinesa($j, 0, $tasksarray, $level, '', 0, $tasksrole, (string) $object->id, 1, $object->id, '', ($object->usage_bill_time ? 1 : 0), $arrayfields, $arrayofselected);
	} else {
		$colspan = count($arrayfields);
		if ($object->usage_bill_time) {
			$colspan += 2;
		}
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoTasks").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	print '</form>';


	// Test if database is clean. If not we clean it.
	//print 'mode='.$_REQUEST["mode"].' $nboftaskshown='.$nboftaskshown.' count($tasksarray)='.count($tasksarray).' count($tasksrole)='.count($tasksrole).'<br>';
	if ($user->hasRight('projet', 'all', 'lire')) {	// We make test to clean only if user has permission to see all (test may report false positive otherwise)
		if ($search_user_id == $user->id) {
			if ($nboftaskshown < count($tasksrole)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		} else {
			if ($nboftaskshown < count($tasksarray) && !GETPOSTINT('search_user_id')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				cleanCorruptedTree($db, 'projet_task', 'fk_task_parent');
			}
		}
	}
}

// End of page
llxFooter();
$db->close();
