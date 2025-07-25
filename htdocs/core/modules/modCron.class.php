<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2022		Anthony Berton	<anthony.berton@bb2a.fr>
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
 * 	\defgroup   cron     Module cron
 *  \brief      cron module descriptor.
 *  \file       htdocs/core/modules/modCron.class.php
 *  \ingroup    cron
 *  \brief      Description and activation file for the module Jobs
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe a Cron module
 */
class modCron extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 2300;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "base";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable the Dolibarr cron service";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'cron';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		//-------------
		$this->config_page_url = array("cron.php@cron");

		// Dependencies
		//-------------
		$this->hidden = getDolGlobalInt('MODULE_CRON_DISABLED'); // A condition to disable module
		$this->depends = array(); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array(); // List of modules id this module is in conflict with
		$this->langfiles = array("cron");

		// Constants
		//-----------
		$this->const = array(
				0=>array(
					'CRON_KEY',
					'chaine',
					'',
					'CRON KEY',
					0,
					'main',
					0
				),);

		// New pages on tabs
		// -----------------
		$this->tabs = array();

		// Boxes
		//------
		$this->boxes = array(
			0 => array('file' => 'box_scheduled_jobs.php', 'enabledbydefaulton' => 'Home')
		);

		// Cronjobs
		$this->cronjobs = array(
			0=>array('entity'=>0, 'label'=>'PurgeDeleteTemporaryFilesShort', 'jobtype'=>'method', 'class'=>'core/class/utils.class.php', 'objectname'=>'Utils', 'method'=>'purgeFiles', 'parameters'=>'tempfilesold+logfiles', 'comment'=>'PurgeDeleteTemporaryFiles', 'frequency'=>2, 'unitfrequency'=>3600 * 24 * 7, 'priority'=>50, 'status'=>1, 'test'=>true),
			1=>array('entity'=>0, 'label'=>'MakeLocalDatabaseDumpShort', 'jobtype'=>'method', 'class'=>'core/class/utils.class.php', 'objectname'=>'Utils', 'method'=>'dumpDatabase', 'parameters'=>'none,auto,1,auto,10,0,0', 'comment'=>'MakeLocalDatabaseDump', 'frequency'=>1, 'unitfrequency'=>3600 * 24 * 7, 'priority'=>90, 'status'=>0, 'test'=>'in_array($conf->db->type, array(\'mysql\', \'mysqli\'))'),
			2=>array('entity'=>0, 'label'=>'MakeSendLocalDatabaseDumpShort', 'jobtype'=>'method', 'class'=>'core/class/utils.class.php', 'objectname'=>'Utils', 'method'=>'sendBackup', 'parameters'=>',,,,,sql', 'comment'=>'MakeSendLocalDatabaseDump', 'frequency'=>1, 'unitfrequency'=>604800, 'priority'=>91, 'status'=>0, 'test'=>'!empty($conf->global->MAIN_ALLOW_BACKUP_BY_EMAIL) && in_array($conf->db->type, array(\'mysql\', \'mysqli\'))'),
			3=>array('entity'=>0, 'label'=>'CleanUnfinishedCronjobShort', 'jobtype'=>'method', 'class'=>'core/class/utils.class.php', 'objectname'=>'Utils', 'method'=>'cleanUnfinishedCronjob', 'parameters'=>'', 'comment'=>'CleanUnfinishedCronjob', 'frequency'=>5, 'unitfrequency'=>60, 'priority'=>10, 'status'=>0, 'test'=>'getDolGlobalInt("MAIN_FEATURES_LEVEL") >= 2'),
			// 1=>array('entity'=>0, 'label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24)
		);

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$this->rights_class = 'cron';
		$r = 0;

		$this->rights[$r][0] = 23001;
		$this->rights[$r][1] = 'Read cron jobs';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 23002;
		$this->rights[$r][1] = 'Create cron Jobs';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'create';
		$r++;

		$this->rights[$r][0] = 23003;
		$this->rights[$r][1] = 'Delete cron Jobs';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		$this->rights[$r][0] = 23004;
		$this->rights[$r][1] = 'Execute cron Jobs';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'execute';
		$r++;

		// Main menu entries
		$r = 0;
		$this->menu[$r] = array('fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left', // This is a Left menu entry
								'titre'=>'CronList',
								'url'=>'/cron/list.php?leftmenu=admintools',
								'langs'=>'cron', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>500,
								'enabled'=>'isModEnabled("cron") && preg_match(\'/^(admintools|all)/\', $leftmenu)', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'$user->hasRight("cron", "read")', // Use 'perms'=>'$user->hasRight("mymodule","level1","level2")' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2); // 0=Menu for internal users, 1=external users, 2=both
		$r++;
	}
}
