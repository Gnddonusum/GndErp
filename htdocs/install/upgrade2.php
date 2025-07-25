<?php
/* Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023      	Gauthier VERDOL       	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *
 * Upgrade2 scripts can be ran from command line with syntax:
 *
 * cd htdocs/install
 * php upgrade.php 3.4.0 3.5.0 [dirmodule|ignoredbversion]
 * php upgrade2.php 3.4.0 3.5.0 [MAIN_MODULE_NAME1_TO_ENABLE,MAIN_MODULE_NAME2_TO_ENABLE]
 *
 * And for final step:
 * php step5.php 3.4.0 3.5.0
 *
 * Return code is 0 if OK, >0 if error
 *
 * Note: To just enable a module from command line, use this syntax:
 * php upgrade2.php 0.0.0 0.0.0 [MAIN_MODULE_NAME1_TO_ENABLE,MAIN_MODULE_NAME2_TO_ENABLE]
 */

/**
 *	\file       htdocs/install/upgrade2.php
 *	\brief      Upgrade some data
 */

define('ALLOWED_IF_UPGRADE_UNLOCK_FOUND', 1);
include_once 'inc.php';
if (!file_exists($conffile)) {
	print 'Error: Dolibarr config file was not found. This may means that Dolibarr is not installed yet. Please call the page "/install/index.php" instead of "/install/upgrade.php").';
}
require_once $conffile;
require_once $dolibarr_main_document_root.'/compta/facture/class/facture.class.php';
require_once $dolibarr_main_document_root.'/comm/propal/class/propal.class.php';
require_once $dolibarr_main_document_root.'/contrat/class/contrat.class.php';
require_once $dolibarr_main_document_root.'/commande/class/commande.class.php';
require_once $dolibarr_main_document_root.'/fourn/class/fournisseur.commande.class.php';
require_once $dolibarr_main_document_root.'/core/lib/price.lib.php';
require_once $dolibarr_main_document_root.'/core/class/menubase.class.php';
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
require_once $dolibarr_main_document_root.'/core/lib/files.lib.php';

global $langs;

$grant_query = '';
$step = 2;
$error = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err = error_reporting();
error_reporting(0);
if (getDolGlobalString('MAIN_OVERRIDE_TIME_LIMIT')) {
	@set_time_limit((int) $conf->global->MAIN_OVERRIDE_TIME_LIMIT);
} else {
	@set_time_limit(600);
}
error_reporting($err);

$setuplang = GETPOST("selectlang", 'aZ09', 3) ? GETPOST("selectlang", 'aZ09', 3) : 'auto';
$langs->setDefaultLang($setuplang);
$versionfrom = GETPOST("versionfrom", 'alpha', 3) ? GETPOST("versionfrom", 'alpha', 3) : (empty($argv[1]) ? '' : $argv[1]);
$versionto = GETPOST("versionto", 'alpha', 3) ? GETPOST("versionto", 'alpha', 3) : (empty($argv[2]) ? '' : $argv[2]);
$enablemodules = GETPOST("enablemodules", 'alpha', 3) ? GETPOST("enablemodules", 'alpha', 3) : (empty($argv[3]) ? '' : $argv[3]);

$langs->loadLangs(array("admin", "install", "bills", "suppliers"));

if ($dolibarr_main_db_type == 'mysqli') {
	$choix = 1;
}
if ($dolibarr_main_db_type == 'pgsql') {
	$choix = 2;
}
if ($dolibarr_main_db_type == 'mssql') {
	$choix = 3;
}


dolibarr_install_syslog("--- upgrade2: entering upgrade2.php page ".$versionfrom." ".$versionto." ".$enablemodules);
if (!is_object($conf)) {
	dolibarr_install_syslog("upgrade2: conf file not initialized", LOG_ERR);
}



/*
 * View
 */

if ((!$versionfrom || preg_match('/version/', $versionfrom)) && (!$versionto || preg_match('/version/', $versionto))) {
	print 'Error: Parameter versionfrom or versionto missing or having a bad format.'."\n";
	print 'Upgrade must be ran from command line with parameters or called from page install/index.php (like a first install)'."\n";
	// Test if batch mode
	$sapi_type = php_sapi_name();
	$script_file = basename(__FILE__);
	$path = __DIR__.'/';
	if (substr($sapi_type, 0, 3) == 'cli') {
		print 'Syntax from command line: '.$script_file." x.y.z a.b.c [MAIN_MODULE_NAME1_TO_ENABLE,MAIN_MODULE_NAME2_TO_ENABLE...]\n";
		print 'Example, upgrade from 19 to 20: '.$script_file." 19.0.0 20.0.0\n";
		print 'Example, enable a module only:  '.$script_file."  0.0.0  0.0.0  MAIN_MODULE_Adherent\n";
		print "\n";
	}
	exit;
}

pHeader('', 'step5', GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'upgrade', 'versionfrom='.$versionfrom.'&versionto='.$versionto, '', 'main-inside main-inside-borderbottom');


if (!GETPOST('action', 'aZ09') || preg_match('/upgrade/i', GETPOST('action', 'aZ09'))) {
	print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/database.svg" width="20" alt="Database"> ';
	print '<span class="inline-block valignmiddle">'.$langs->trans('DataMigration').'</span></h3>';

	print '<table class="centpercent">';

	// If password is encoded, we decode it
	if ((!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) || !empty($dolibarr_main_db_encrypted_pass)) {
		require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
		if (!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
			$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
			$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
			$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this as it is used to know the password was initially encrypted
		} else {
			$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
		}
	}

	// $conf is already instantiated inside inc.php
	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

	if (!$db->connected) {
		print '<tr><td colspan="4">'.$langs->trans("ErrorFailedToConnectToDatabase", $conf->db->name).'</td><td class="right">'.$langs->trans('Error').'</td></tr>';
		dolibarr_install_syslog('upgrade2: failed to connect to database :'.$conf->db->name.' on '.$conf->db->host.' for user '.$conf->db->user, LOG_ERR);
		$error++;
	}

	if (!$error) {
		if ($db->database_selected) {
			dolibarr_install_syslog('upgrade2: database connection successful :'.$dolibarr_main_db_name);
		} else {
			$error++;
		}
	}

	if (empty($dolibarr_main_db_encryption)) {
		$dolibarr_main_db_encryption = 0;
	}
	$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
	if (empty($dolibarr_main_db_cryptkey)) {
		$dolibarr_main_db_cryptkey = '';
	}
	$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

	// Load global conf
	$conf->setValues($db);


	$listofentities = array(1);

	// Create the global $hookmanager object
	include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
	$hookmanager = new HookManager($db);
	$hookmanager->initHooks(array('upgrade2'));

	$parameters = array('versionfrom' => $versionfrom, 'versionto' => $versionto);
	$object = new stdClass();
	$action = "upgrade";
	$reshook = $hookmanager->executeHooks('doUpgradeBefore', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook >= 0 && is_array($hookmanager->resArray)) {
		// Example: $hookmanager->resArray = array(2, 3, 10);
		$listofentities = array_unique(array_merge($listofentities, $hookmanager->resArray));
	}


	/***************************************************************************************
	 *
	 * Migration of data
	 *
	 ***************************************************************************************/

	// Force to execute this at begin to avoid the new core code into Dolibarr to be broken.
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user ADD COLUMN birth date';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user ADD COLUMN dateemployment date';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user ADD COLUMN dateemploymentend date';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user ADD COLUMN default_range integer';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user ADD COLUMN default_c_exp_tax_cat integer';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'extrafields ADD COLUMN langs varchar(24)';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'extrafields ADD COLUMN fieldcomputed text';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'extrafields ADD COLUMN fielddefault varchar(255)';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX."extrafields ADD COLUMN enabled varchar(255) DEFAULT '1'";
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'extrafields ADD COLUMN help text';
	$db->query($sql, 1);
	$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'user_rights ADD COLUMN entity integer DEFAULT 1 NOT NULL';
	$db->query($sql, 1);


	$db->begin();

	foreach ($listofentities as $entity) {
		// Set $conf context for entity
		$conf->setEntityValues($db, $entity);
		// Reset forced setup after the setValues
		if (defined('SYSLOG_FILE')) {
			$conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
		}
		$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

		$versiontoarray = array();
		$versionranarray = array();

		if (!$error) {
			if (count($listofentities) > 1) {
				print '<tr><td colspan="4">*** '.$langs->trans("Entity").' '.$entity.'</td></tr>'."\n";
			}

			// Current version is $conf->global->MAIN_VERSION_LAST_UPGRADE
			// Version to install is DOL_VERSION
			$dolibarrlastupgradeversionarray = preg_split('/[\.-]/', isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : (isset($conf->global->MAIN_VERSION_LAST_INSTALL) ? $conf->global->MAIN_VERSION_LAST_INSTALL : ''));

			// Chaque action de migration doit renvoyer une ligne sur 4 colonnes avec
			// dans la 1ere colonne, la description de l'action a faire
			// dans la 4eme colonne, le texte 'OK' si fait ou 'AlreadyDone' si rien n'est fait ou 'Error'

			$versiontoarray = explode('.', $versionto);
			$versionranarray = explode('.', DOL_VERSION);


			$afterversionarray = explode('.', '2.0.0');
			$beforeversionarray = explode('.', '2.7.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				// Script pour V2 -> V2.1
				migrate_paiements($db, $langs, $conf);

				migrate_contracts_det($db, $langs, $conf);

				migrate_contracts_date1($db, $langs, $conf);

				migrate_contracts_date2($db, $langs, $conf);

				migrate_contracts_date3($db, $langs, $conf);

				migrate_contracts_open($db, $langs, $conf);

				migrate_modeles($db, $langs, $conf);

				migrate_price_propal($db, $langs, $conf);

				migrate_price_commande($db, $langs, $conf);

				migrate_price_commande_fournisseur($db, $langs, $conf);

				migrate_price_contrat($db, $langs, $conf);

				migrate_paiementfourn_facturefourn($db, $langs, $conf);


				// Script pour V2.1 -> V2.2
				migrate_paiements_orphelins_1($db, $langs, $conf);

				migrate_paiements_orphelins_2($db, $langs, $conf);

				migrate_links_transfert($db, $langs, $conf);


				// Script pour V2.2 -> V2.4
				migrate_commande_expedition($db, $langs, $conf);

				migrate_commande_livraison($db, $langs, $conf);

				migrate_detail_livraison($db, $langs, $conf);


				// Script pour V2.5 -> V2.6
				migrate_stocks($db, $langs, $conf);


				// Script pour V2.6 -> V2.7
				migrate_menus($db, $langs, $conf);

				migrate_commande_deliveryaddress($db, $langs, $conf);

				migrate_restore_missing_links($db, $langs, $conf);

				migrate_rename_directories($db, $langs, $conf, '/compta', '/banque');

				migrate_rename_directories($db, $langs, $conf, '/societe', '/mycompany');
			}

			// Script for 2.8
			$afterversionarray = explode('.', '2.7.9');
			$beforeversionarray = explode('.', '2.8.9');
			//print $versionto.' '.versioncompare($versiontoarray,$afterversionarray).' '.versioncompare($versiontoarray,$beforeversionarray);
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_price_facture($db, $langs, $conf); // Code of this function works for 2.8+ because need a field tva_tx

				migrate_relationship_tables($db, $langs, $conf, 'co_exp', 'fk_commande', 'commande', 'fk_expedition', 'shipping');

				migrate_relationship_tables($db, $langs, $conf, 'pr_exp', 'fk_propal', 'propal', 'fk_expedition', 'shipping');

				migrate_relationship_tables($db, $langs, $conf, 'pr_liv', 'fk_propal', 'propal', 'fk_livraison', 'delivery');

				migrate_relationship_tables($db, $langs, $conf, 'co_liv', 'fk_commande', 'commande', 'fk_livraison', 'delivery');

				migrate_relationship_tables($db, $langs, $conf, 'co_pr', 'fk_propale', 'propal', 'fk_commande', 'commande');

				migrate_relationship_tables($db, $langs, $conf, 'fa_pr', 'fk_propal', 'propal', 'fk_facture', 'facture');

				migrate_relationship_tables($db, $langs, $conf, 'co_fa', 'fk_commande', 'commande', 'fk_facture', 'facture');

				migrate_project_user_resp($db, $langs, $conf);

				migrate_project_task_actors($db, $langs, $conf);
			}

			// Script for 2.9
			$afterversionarray = explode('.', '2.8.9');
			$beforeversionarray = explode('.', '2.9.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_element_time($db, $langs, $conf);

				migrate_customerorder_shipping($db, $langs, $conf);

				migrate_shipping_delivery($db, $langs, $conf);

				migrate_shipping_delivery2($db, $langs, $conf);
			}

			// Script for 3.0
			$afterversionarray = explode('.', '2.9.9');
			$beforeversionarray = explode('.', '3.0.9');
			//if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
			// No particular code
			//}

			// Script for 3.1
			$afterversionarray = explode('.', '3.0.9');
			$beforeversionarray = explode('.', '3.1.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_rename_directories($db, $langs, $conf, '/rss', '/externalrss');

				migrate_actioncomm_element($db, $langs, $conf);
			}

			// Script for 3.2
			$afterversionarray = explode('.', '3.1.9');
			$beforeversionarray = explode('.', '3.2.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_price_contrat($db, $langs, $conf);

				migrate_mode_reglement($db, $langs, $conf);

				migrate_clean_association($db, $langs, $conf);
			}

			// Script for 3.3
			$afterversionarray = explode('.', '3.2.9');
			$beforeversionarray = explode('.', '3.3.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_categorie_association($db, $langs, $conf);
			}

			// Script for 3.4
			// No specific scripts

			// Tasks to do always and only into last targeted version
			$afterversionarray = explode('.', '3.6.9'); // target is after this
			$beforeversionarray = explode('.', '3.7.9'); // target is before this
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_event_assignement($db, $langs, $conf);
			}

			// Scripts for 3.9
			$afterversionarray = explode('.', '3.7.9');
			$beforeversionarray = explode('.', '3.8.9');
			//if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
			// No particular code
			//}

			// Scripts for 4.0
			$afterversionarray = explode('.', '3.9.9');
			$beforeversionarray = explode('.', '4.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_rename_directories($db, $langs, $conf, '/fckeditor', '/medias');
			}

			// Scripts for 5.0
			$afterversionarray = explode('.', '4.0.9');
			$beforeversionarray = explode('.', '5.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				// Migrate to add entity value into llx_societe_remise
				migrate_remise_entity($db, $langs, $conf);

				// Migrate to add entity value into llx_societe_remise_except
				migrate_remise_except_entity($db, $langs, $conf);
			}

			// Scripts for 6.0
			$afterversionarray = explode('.', '5.0.9');
			$beforeversionarray = explode('.', '6.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				if (isModEnabled('multicompany')) {
					global $multicompany_transverse_mode;

					// Only if the transverse mode is not used
					if (empty($multicompany_transverse_mode)) {
						// Migrate to add entity value into llx_user_rights
						migrate_user_rights_entity($db, $langs, $conf);

						// Migrate to add entity value into llx_usergroup_rights
						migrate_usergroup_rights_entity($db, $langs, $conf);
					}
				}
			}

			// Scripts for 7.0
			$afterversionarray = explode('.', '6.0.9');
			$beforeversionarray = explode('.', '7.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				// Migrate contact association
				migrate_event_assignement_contact($db, $langs, $conf);

				migrate_reset_blocked_log($db, $langs, $conf);
			}

			// Scripts for 8.0
			$afterversionarray = explode('.', '7.0.9');
			$beforeversionarray = explode('.', '8.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_rename_directories($db, $langs, $conf, '/contracts', '/contract');
			}

			// Scripts for 9.0
			$afterversionarray = explode('.', '8.0.9');
			$beforeversionarray = explode('.', '9.0.9');
			//if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
			//migrate_user_photospath();
			//}

			// Scripts for 11.0
			$afterversionarray = explode('.', '10.0.9');
			$beforeversionarray = explode('.', '11.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_users_socialnetworks();
				migrate_members_socialnetworks();
				migrate_contacts_socialnetworks();
				migrate_thirdparties_socialnetworks();
			}

			// Scripts for 14.0
			$afterversionarray = explode('.', '13.0.9');
			$beforeversionarray = explode('.', '14.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_export_import_profiles('export');
				migrate_export_import_profiles('import');
			}

			// Scripts for 16.0
			$afterversionarray = explode('.', '15.0.9');
			$beforeversionarray = explode('.', '16.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_user_photospath();
				migrate_user_photospath2();
			}

			// Scripts for 17.0
			$afterversionarray = explode('.', '16.0.9');
			$beforeversionarray = explode('.', '17.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_contractdet_rank();
			}

			// Scripts for 18.0
			$afterversionarray = explode('.', '17.0.9');
			$beforeversionarray = explode('.', '18.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_contractdet_rank();
			}

			// Scripts for 19.0
			/*
			$afterversionarray = explode('.', '18.0.9');
			$beforeversionarray = explode('.', '19.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
			}
			*/

			// Scripts for 20.0
			$afterversionarray = explode('.', '19.0.9');
			$beforeversionarray = explode('.', '20.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_invoice_export_models();
			}

			// Scripts for 21.0
			$afterversionarray = explode('.', '20.0.9');
			$beforeversionarray = explode('.', '21.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_productlot_path();
			}

			// Scripts for 22.0
			$afterversionarray = explode('.', '21.0.9');
			$beforeversionarray = explode('.', '22.0.9');
			if (versioncompare($versiontoarray, $afterversionarray) >= 0 && versioncompare($versiontoarray, $beforeversionarray) <= 0) {
				migrate_accountingbookkeeping($entity);
			}
		}


		// Code executed only if migration is LAST ONE. Must always be done.
		if (versioncompare($versiontoarray, $versionranarray) >= 0 || versioncompare($versiontoarray, $versionranarray) <= -3) {
			// Reload modules (this must be always done and only into last targeted version, because code to reload module may need table structure of last version)
			$listofmodule = array(
				'MAIN_MODULE_ACCOUNTING' => 'newboxdefonly',
				'MAIN_MODULE_AGENDA' => 'newboxdefonly',
				'MAIN_MODULE_BOM' => 'menuonly',
				'MAIN_MODULE_BANQUE' => 'menuonly',
				'MAIN_MODULE_BARCODE' => 'newboxdefonly',
				'MAIN_MODULE_CRON' => 'newboxdefonly',
				'MAIN_MODULE_COMMANDE' => 'newboxdefonly',
				'MAIN_MODULE_BLOCKEDLOG' => 'noboxes',
				'MAIN_MODULE_DEPLACEMENT' => 'newboxdefonly',
				'MAIN_MODULE_DON' => 'newboxdefonly',
				'MAIN_MODULE_ECM' => 'newboxdefonly',
				'MAIN_MODULE_EXTERNALSITE' => 'newboxdefonly',
				'MAIN_MODULE_EXPENSEREPORT' => 'newboxdefonly',
				'MAIN_MODULE_FACTURE' => 'newboxdefonly',
				'MAIN_MODULE_FOURNISSEUR' => 'newboxdefonly',
				'MAIN_MODULE_FICHEINTER' => 'newboxdefonly',
				'MAIN_MODULE_HOLIDAY' => 'newboxdefonly',
				'MAIN_MODULE_LOAN' => 'newboxdefonly',
				'MAIN_MODULE_MARGIN' => 'menuonly',
				'MAIN_MODULE_MRP' => 'menuonly',
				'MAIN_MODULE_OPENSURVEY' => 'newboxdefonly',
				'MAIN_MODULE_PAYBOX' => 'newboxdefonly',
				'MAIN_MODULE_PRINTING' => 'newboxdefonly',
				'MAIN_MODULE_PRODUIT' => 'newboxdefonly',
				'MAIN_MODULE_RECRUITMENT' => 'menuonly',
				'MAIN_MODULE_RESOURCE' => 'noboxes',
				'MAIN_MODULE_SALARIES' => 'newboxdefonly',
				'MAIN_MODULE_SERVICE' => 'newboxdefonly',
				'MAIN_MODULE_SYSLOG' => 'newboxdefonly',
				'MAIN_MODULE_SOCIETE' => 'newboxdefonly',
				'MAIN_MODULE_STRIPE' => 'menuonly',
				'MAIN_MODULE_TICKET' => 'newboxdefonly',
				'MAIN_MODULE_TAKEPOS' => 'newboxdefonly',
				'MAIN_MODULE_USER' => 'newboxdefonly', //This one must be always done and only into last targeted version)
				'MAIN_MODULE_VARIANTS' => 'newboxdefonly',
				'MAIN_MODULE_WEBSITE' => 'newboxdefonly',
			);

			$result = migrate_reload_modules($db, $langs, $conf, $listofmodule);
			if ($result < 0) {
				$error++;
			}
			// Reload menus (this must be always and only into last targeted version)
			$result = migrate_reload_menu($db, $langs, $conf);
			if ($result < 0) {
				$error++;
			}
		}

		// Can force activation of some module during migration with parameter 'enablemodules=MAIN_MODULE_XXX,MAIN_MODULE_YYY,...'
		// In most cases (online install or upgrade) $enablemodules is empty. Can be forced when ran from command line.
		if (!$error && $enablemodules) {
			// Reload modules (this must be always done and only into last targeted version)
			$listofmodules = array();
			$enablemodules = preg_replace('/enablemodules=/', '', $enablemodules);
			$tmplistofmodules = explode(',', $enablemodules);
			foreach ($tmplistofmodules as $value) {
				$listofmodules[$value] = 'forceactivate';
			}

			$resultreloadmodules = migrate_reload_modules($db, $langs, $conf, $listofmodules, 1);
			if ($resultreloadmodules < 0) {
				$error++;
			}
		}


		// Can call a dedicated external upgrade process with hook doUpgradeAfterDB()
		if (!$error) {
			$parameters = array('versionfrom' => $versionfrom, 'versionto' => $versionto, 'conf' => $conf);
			$object = new stdClass();
			$action = "upgrade";
			$reshook = $hookmanager->executeHooks('doUpgradeAfterDB', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
			if ($hookmanager->resNbOfHooks > 0) {
				if ($reshook < 0) {
					print '<tr><td colspan="4">';
					print '<b>'.$langs->trans('UpgradeExternalModule').'</b>: ';
					print $hookmanager->error;
					print "<!-- (".$reshook.") -->";
					print '</td></tr>';
				} else {
					print '<tr class="trforrunsql"><td colspan="4">';
					print '<b>'.$langs->trans('UpgradeExternalModule').' (DB)</b>: <span class="ok">OK</span>';
					print "<!-- (".$reshook.") -->";
					print '</td></tr>';
				}
			} else {
				//if (!empty($conf->modules))
				if (!empty($conf->modules_parts['hooks'])) {     // If there is at least one module with one hook, we show message to say nothing was done
					print '<tr class="trforrunsql"><td colspan="4">';
					print '<b>'.$langs->trans('UpgradeExternalModule').'</b>: '.$langs->trans("NodoUpgradeAfterDB");
					print '</td></tr>';
				}
			}
		}
	}

	print '</table>';

	if (!$error) {
		// Set constant to ask to remake a new ping to inform about upgrade (if first ping was done and OK)
		$sql = 'UPDATE '.MAIN_DB_PREFIX."const SET VALUE = 'torefresh' WHERE name = 'MAIN_FIRST_PING_OK_ID'";
		$db->query($sql, 1);
	}

	// We always commit.
	// Process is designed so we can run it several times whatever is situation.
	$db->commit();


	/***************************************************************************************
	 *
	 * Migration of files
	 *
	 ***************************************************************************************/

	foreach ($listofentities as $entity) {
		// Set $conf context for entity
		$conf->setEntityValues($db, $entity);
		// Reset forced setup after the setValues
		if (defined('SYSLOG_FILE')) {
			$conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
		}
		$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;


		// Copy directory medias
		$srcroot = DOL_DOCUMENT_ROOT.'/install/medias';
		$destroot = DOL_DATA_ROOT.'/medias';
		dolCopyDir($srcroot, $destroot, '0', 0);


		// Actions for all versions (no database change but delete some files and directories)
		migrate_delete_old_files($db, $langs, $conf);
		migrate_delete_old_dir($db, $langs, $conf);
		// Actions for all versions (no database change but create some directories)
		dol_mkdir(DOL_DATA_ROOT.'/bank');
		// Actions for all versions (no database change but rename some directories)
		migrate_rename_directories($db, $langs, $conf, '/banque/bordereau', '/bank/checkdeposits');


		$parameters = array('versionfrom' => $versionfrom, 'versionto' => $versionto, 'conf' => $conf);
		$object = new stdClass();
		$action = "upgrade";
		$reshook = $hookmanager->executeHooks('doUpgradeAfterFiles', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if ($hookmanager->resNbOfHooks > 0) {
			if ($reshook < 0) {
				print '<tr><td colspan="4">';
				print '<b>'.$langs->trans('UpgradeExternalModule').'</b>: ';
				print $hookmanager->error;
				print "<!-- (".$reshook.") -->";
				print '</td></tr>';
			} else {
				print '<tr class="trforrunsql"><td colspan="4">';
				print '<b>'.$langs->trans('UpgradeExternalModule').' (Files)</b>: <span class="ok">OK</span>';
				print "<!-- (".$reshook.") -->";
				print '</td></tr>';
			}
		} else {
			//if (!empty($conf->modules))
			if (!empty($conf->modules_parts['hooks'])) {     // If there is at least one module with one hook, we show message to say nothing was done
				print '<tr class="trforrunsql"><td colspan="4">';
				print '<b>'.$langs->trans('UpgradeExternalModule').'</b>: '.$langs->trans("NodoUpgradeAfterFiles");
				print '</td></tr>';
			}
		}
	}

	$db->close();

	$silent = 0;
	if (!$silent) {
		print '<table width="100%">';
		print '<tr><td style="width: 30%">'.$langs->trans("MigrationFinished").'</td>';
		print '<td class="right">';
		if ($error == 0) {
			//print '<span class="ok">'.$langs->trans("OK").'</span> - ';		// $error = 0 does not mean there is no error (error are not always trapped)
		} else {
			print '<span class="error">'.$langs->trans("Error").'</span> - ';
		}

		//if (!empty($conf->use_javascript_ajax)) {		// use_javascript_ajax is not defined
		print '<script type="text/javascript">
		jQuery(document).ready(function() {
			function init_trrunsql()
			{
				console.log("toggle .trforrunsql");
				jQuery(".trforrunsql").toggle();
			}
			init_trrunsql();
			jQuery(".trforrunsqlshowhide").click(function() {
				init_trrunsql();
			});
		});
		</script>';
		print '<a class="trforrunsqlshowhide" href="#">'.$langs->trans("ShowHideDetails").'</a>';
		//}

		print '</td></tr>'."\n";
		print '</table>';
	}

	//print '<div><br>'.$langs->trans("MigrationFinished").'</div>';
} else {
	print '<div class="error">'.$langs->trans('ErrorWrongParameters').'</div>';
	$error++;
}

$ret = 0;
if ($error && isset($argv[1])) {
	$ret = 1;
}
dolibarr_install_syslog("Exit ".$ret);

dolibarr_install_syslog("--- upgrade2: end");
pFooter($error ? 2 : 0, $setuplang);

if ($db->connected) {
	$db->close();
}

// Return code if ran from command line
if ($ret) {
	exit($ret);
}



/**
 * Records payment links for an invoice in a join table (link n<-->n for payments<-->invoices)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement", "fk_facture");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$sql = "SELECT p.rowid, p.fk_facture, p.amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " WHERE p.fk_facture > 0";

		$resql = $db->query($sql);
		$row = array();
		$num = 0;

		dolibarr_install_syslog("upgrade2::migrate_paiements");
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$row[$i][0] = $obj->rowid;
				$row[$i][1] = $obj->fk_facture;
				$row[$i][2] = $obj->amount;
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		if ($num) {
			print $langs->trans('MigrationPaymentsNumberToUpdate', $num)."<br>\n";
			$res = 0;
			if ($db->begin()) {
				$num = count($row);
				for ($i = 0; $i < $num; $i++) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
					$sql .= " VALUES (".((int) $row[$i][1]).",".((int) $row[$i][0]).",".((float) $row[$i][2]).")";

					$res += $db->query($sql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_facture = 0 WHERE rowid = ".((int) $row[$i][0]);

					$res += $db->query($sql);

					print $langs->trans('MigrationProcessPaymentUpdate', $row[$i][0])."<br>\n";
				}
			}

			if (is_array($row) && $res == (2 * count($row))) {
				$db->commit();
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			} else {
				$db->rollback();
				print $langs->trans('MigrationUpdateFailed').'<br>';
			}
		} else {
			print $langs->trans('MigrationPaymentsNothingToUpdate')."<br>\n";
		}
	} else {
		print $langs->trans('MigrationPaymentsNothingToUpdate')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Repair orphan payments (lost relations due to bugs)
 * Query to check if there are still orphans:
 * select * from llx_paiement as p left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid WHERE pf.rowid IS NULL AND (p.fk_facture = 0 OR p.fk_facture IS NULL)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements_orphelins_1($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement", "fk_facture");
	$obj = $db->fetch_object($result);
	if ($obj) {
		// All answer of this requests should have a parent into llx_paiement_facture
		$sql = "SELECT DISTINCT p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
		$sql .= " bu2.url_id as socid";
		$sql .= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_paiement = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu2 ON (bu.fk_bank=bu2.fk_bank AND bu2.type = 'company')";
		$sql .= " WHERE pf.rowid IS NULL AND (p.rowid=bu.url_id AND bu.type='payment') AND bu.fk_bank = b.rowid";
		$sql .= " AND b.rappro = 1";
		$sql .= " AND (p.fk_facture = 0 OR p.fk_facture IS NULL)";

		$resql = $db->query($sql);

		dolibarr_install_syslog("upgrade2::migrate_paiements_orphelins_1");
		$row = array();
		if ($resql) {
			$i = $j = 0;
			$num = $db->num_rows($resql);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj->pamount == $obj->bamount && $obj->socid) {	// Pour etre sur d'avoir bon cas
					$row[$j]['paymentid'] = $obj->rowid; // paymentid
					$row[$j]['pamount'] = $obj->pamount;
					$row[$j]['fk_bank'] = $obj->fk_bank;
					$row[$j]['bamount'] = $obj->bamount;
					$row[$j]['socid'] = $obj->socid;
					$row[$j]['datec'] = $obj->datec;
					$j++;
				}
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		if (count($row)) {
			print $langs->trans('OrphelinsPaymentsDetectedByMethod', 1).': '.count($row)."<br>\n";
			$db->begin();

			$res = 0;
			$num = count($row);
			for ($i = 0; $i < $num; $i++) {
				if (getDolGlobalInt('MAIN_FEATURES_LEVEL') == 2) {
					print '* '.$row[$i]['datec'].' paymentid='.$row[$i]['paymentid'].' pamount='.$row[$i]['pamount'].' fk_bank='.$row[$i]['fk_bank'].' bamount='.$row[$i]['bamount'].' socid='.$row[$i]['socid'].'<br>';
				}

				// Look for invoices without payment relations with the same amount and same comppany
				$sql = " SELECT DISTINCT f.rowid from ".MAIN_DB_PREFIX."facture as f";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
				$sql .= " WHERE f.fk_statut in (2,3) AND fk_soc = ".((int) $row[$i]['socid'])." AND total_ttc = ".((float) $row[$i]['pamount']);
				$sql .= " AND pf.fk_facture IS NULL";
				$sql .= " ORDER BY f.fk_statut";
				//print $sql.'<br>';
				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					//print 'Nb of invoice found for this amount and company :'.$num.'<br>';
					if ($num >= 1) {
						$obj = $db->fetch_object($resql);
						$facid = $obj->rowid;

						$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
						$sql .= " VALUES (".((int) $facid).",".((int) $row[$i]['paymentid']).", ".((float) $row[$i]['pamount']).")";

						$res += $db->query($sql);

						print $langs->trans('MigrationProcessPaymentUpdate', 'facid='.$facid.'-paymentid='.$row[$i]['paymentid'].'-amount='.$row[$i]['pamount'])."<br>\n";
					}
				} else {
					print 'ERROR';
				}
			}

			if ($res > 0) {
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			} else {
				print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
			}

			$db->commit();
		} else {
			print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
		}
	} else {
		print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Repair orphan payments (lost relations due to bugs)
 * Query to check if there are still orphans:
 * select * from llx_paiement as p left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid WHERE pf.rowid IS NULL AND (p.fk_facture = 0 OR p.fk_facture IS NULL)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements_orphelins_2($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement", "fk_facture");
	$obj = $db->fetch_object($result);
	if ($obj) {
		// Tous les enregistrements qui sortent de cette requete devrait avoir un pere dans llx_paiement_facture
		$sql = "SELECT DISTINCT p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
		$sql .= " bu2.url_id as socid";
		$sql .= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_paiement = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu2 ON (bu.fk_bank = bu2.fk_bank AND bu2.type = 'company')";
		$sql .= " WHERE pf.rowid IS NULL AND (p.fk_bank = bu.fk_bank AND bu.type = 'payment') AND bu.fk_bank = b.rowid";
		$sql .= " AND (p.fk_facture = 0 OR p.fk_facture IS NULL)";

		$resql = $db->query($sql);

		dolibarr_install_syslog("upgrade2::migrate_paiements_orphelins_2");
		$row = array();
		if ($resql) {
			$i = $j = 0;
			$num = $db->num_rows($resql);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj->pamount == $obj->bamount && $obj->socid) {	// Pour etre sur d'avoir bon cas
					$row[$j]['paymentid'] = $obj->rowid; // paymentid
					$row[$j]['pamount'] = $obj->pamount;
					$row[$j]['fk_bank'] = $obj->fk_bank;
					$row[$j]['bamount'] = $obj->bamount;
					$row[$j]['socid'] = $obj->socid;
					$row[$j]['datec'] = $obj->datec;
					$j++;
				}
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		$nberr = 0;

		$num = count($row);
		if ($num) {
			print $langs->trans('OrphelinsPaymentsDetectedByMethod', 2).': '.count($row)."<br>\n";
			$db->begin();

			$res = 0;
			for ($i = 0; $i < $num; $i++) {
				if (getDolGlobalInt('MAIN_FEATURES_LEVEL') == 2) {
					print '* '.$row[$i]['datec'].' paymentid='.$row[$i]['paymentid'].' pamount='.$row[$i]['pamount'].' fk_bank='.$row[$i]['fk_bank'].' '.$row[$i]['bamount'].' socid='.$row[$i]['socid'].'<br>';
				}

				// Look for invoices without payment relations with the same amount and same comppany
				$sql = " SELECT DISTINCT f.rowid from ".MAIN_DB_PREFIX."facture as f";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
				$sql .= " WHERE f.fk_statut in (2,3) AND fk_soc = ".((int) $row[$i]['socid'])." AND total_ttc = ".((float) $row[$i]['pamount']);
				$sql .= " AND pf.fk_facture IS NULL";
				$sql .= " ORDER BY f.fk_statut";
				//print $sql.'<br>';
				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					//print 'Nb of invoice found for this amount and company :'.$num.'<br>';
					if ($num >= 1) {
						$obj = $db->fetch_object($resql);
						$facid = $obj->rowid;

						$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
						$sql .= " VALUES (".((int) $facid).",".((int) $row[$i]['paymentid']).", ".((float) $row[$i]['pamount']).")";

						$res += $db->query($sql);

						print $langs->trans('MigrationProcessPaymentUpdate', 'facid='.$facid.'-paymentid='.$row[$i]['paymentid'].'-amount='.$row[$i]['pamount'])."<br>\n";
					}
				} else {
					print 'ERROR';
					$nberr++;
				}
			}

			if ($res > 0) {
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			} else {
				print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
			}

			$db->commit();
		} else {
			print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
		}

		// Delete obsolete fields fk_facture
		$db->begin();

		$sql = "ALTER TABLE ".MAIN_DB_PREFIX."paiement DROP COLUMN fk_facture";
		$db->query($sql);

		if (!$nberr) {
			$db->commit();
		} else {
			print 'ERROR';
			$db->rollback();
		}
	} else {
		print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
	}

	print '</td></tr>';
}


/**
 * Mise a jour des contrats (gestion du contrat + detail de contrat)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_contracts_det($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	$nberr = 0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsUpdate')."</b><br>\n";

	$sql = "SELECT c.rowid as cref, c.date_contrat, c.statut, c.fk_product, c.fk_facture, c.fk_user_author,";
	$sql .= " p.ref, p.label, p.description, p.price, p.tva_tx, p.duration, cd.rowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
	$sql .= " ON c.fk_product = p.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd";
	$sql .= " ON c.rowid=cd.fk_contrat";
	$sql .= " WHERE cd.rowid IS NULL AND p.rowid IS NOT NULL";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_contracts_det");
	if ($resql) {
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num) {
			print $langs->trans('MigrationContractsNumberToUpdate', $num)."<br>\n";
			$db->begin();

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet (";
				$sql .= "fk_contrat, fk_product, statut, label, description,";
				$sql .= "date_ouverture_prevue, date_ouverture, date_fin_validite, tva_tx, qty,";
				$sql .= "subprice, price_ht, fk_user_author, fk_user_ouverture)";
				$sql .= " VALUES (";
				$sql .= ((int) $obj->cref).", ".($obj->fk_product ? ((int) $obj->fk_product) : 0).", ";
				$sql .= "0, ";
				$sql .= "'".$db->escape($obj->label)."', null, ";
				$sql .= ($obj->date_contrat ? "'".$db->idate($db->jdate($obj->date_contrat))."'" : "null").", ";
				$sql .= "null, ";
				$sql .= "null, ";
				$sql .= ((float) $obj->tva_tx).", 1, ";
				$sql .= ((float) $obj->price).", ".((float) $obj->price).", ".((int) $obj->fk_user_author).",";
				$sql .= "null";
				$sql .= ")";

				if ($db->query($sql)) {
					print $langs->trans('MigrationContractsLineCreation', $obj->cref)."<br>\n";
				} else {
					dol_print_error($db);
					$nberr++;
				}

				$i++;
			}

			if (!$nberr) {
				//      $db->rollback();
				$db->commit();
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			} else {
				$db->rollback();
				print $langs->trans('MigrationUpdateFailed').'<br>';
			}
		} else {
			print $langs->trans('MigrationContractsNothingToUpdate')."<br>\n";
		}
	} else {
		print $langs->trans('MigrationContractsFieldDontExist')."<br>\n";
		//    dol_print_error($db);
	}

	print '</td></tr>';
}

/**
 * Function to migrate links into llx_bank_url
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_links_transfert($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	$nberr = 0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationBankTransfertsUpdate')."</b><br>\n";

	$sql = "SELECT ba.rowid as barowid, bb.rowid as bbrowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as bb, ".MAIN_DB_PREFIX."bank as ba";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = ba.rowid";
	$sql .= " WHERE ba.amount = -bb.amount AND ba.fk_account <> bb.fk_account";
	$sql .= " AND ba.datev = bb.datev AND ba.datec = bb.datec";
	$sql .= " AND bu.fk_bank IS NULL";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_links_transfert");
	if ($resql) {
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num) {
			print $langs->trans('MigrationBankTransfertsToUpdate', $num)."<br>\n";
			$db->begin();

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (";
				$sql .= "fk_bank, url_id, url, label, type";
				$sql .= ")";
				$sql .= " VALUES (";
				$sql .= $obj->barowid.",".$obj->bbrowid.", '/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert'";
				$sql .= ")";

				//print $sql.'<br>';
				dolibarr_install_syslog("migrate_links_transfert");

				if (!$db->query($sql)) {
					dol_print_error($db);
					$nberr++;
				}

				$i++;
			}

			if (!$nberr) {
				//      $db->rollback();
				$db->commit();
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			} else {
				$db->rollback();
				print $langs->trans('MigrationUpdateFailed').'<br>';
			}
		} else {
			print $langs->trans('MigrationBankTransfertsNothingToUpdate')."<br>\n";
		}
	} else {
		dol_print_error($db);
	}

	print '</td></tr>';
}

/**
 * Mise a jour des date de contrats non renseignees
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_contracts_date1($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsEmptyDatesUpdate')."</b><br>\n";

	$sql = "update ".MAIN_DB_PREFIX."contrat set date_contrat=tms where date_contrat is null";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date1");
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	if ($db->affected_rows($resql) > 0) {
		print $langs->trans('MigrationContractsEmptyDatesUpdateSuccess')."<br>\n";
	} else {
		print $langs->trans('MigrationContractsEmptyDatesNothingToUpdate')."<br>\n";
	}

	$sql = "update ".MAIN_DB_PREFIX."contrat set datec=tms where datec is null";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date1");
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	if ($db->affected_rows($resql) > 0) {
		print $langs->trans('MigrationContractsEmptyCreationDatesUpdateSuccess')."<br>\n";
	} else {
		print $langs->trans('MigrationContractsEmptyCreationDatesNothingToUpdate')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Update contracts with date min real if service date is lower
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Language
 * @param	Conf		$conf	Conf
 * @return	void
 */
function migrate_contracts_date2($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	$nberr = 0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsInvalidDatesUpdate')."</b><br>\n";

	$sql = "SELECT c.rowid as cref, c.datec, c.date_contrat, MIN(cd.date_ouverture) as datemin";
	$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c,";
	$sql .= " ".MAIN_DB_PREFIX."contratdet as cd";
	$sql .= " WHERE c.rowid=cd.fk_contrat AND cd.date_ouverture IS NOT NULL";
	$sql .= " GROUP BY c.rowid, c.date_contrat";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_contracts_date2");
	if ($resql) {
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num) {
			$nbcontratsmodifie = 0;
			$db->begin();

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj->date_contrat > $obj->datemin) {
					$datemin = $db->jdate($obj->datemin);

					print $langs->trans('MigrationContractsInvalidDateFix', $obj->cref, $obj->date_contrat, $obj->datemin)."<br>\n";
					$sql = "UPDATE ".MAIN_DB_PREFIX."contrat";
					$sql .= " SET date_contrat='".$db->idate($datemin)."'";
					$sql .= " WHERE rowid = ".((int) $obj->cref);
					$resql2 = $db->query($sql);
					if (!$resql2) {
						dol_print_error($db);
					}

					$nbcontratsmodifie++;
				}
				$i++;
			}

			$db->commit();

			if ($nbcontratsmodifie) {
				print $langs->trans('MigrationContractsInvalidDatesNumber', $nbcontratsmodifie)."<br>\n";
			} else {
				print  $langs->trans('MigrationContractsInvalidDatesNothingToUpdate')."<br>\n";
			}
		}
	} else {
		dol_print_error($db);
	}

	print '</td></tr>';
}

/**
 * Mise a jour des dates de creation de contrat
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_contracts_date3($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsIncoherentCreationDateUpdate')."</b><br>\n";

	$sql = "update ".MAIN_DB_PREFIX."contrat set datec=date_contrat where datec is null or datec > date_contrat";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date3");
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	if ($db->affected_rows($resql) > 0) {
		print $langs->trans('MigrationContractsIncoherentCreationDateUpdateSuccess')."<br>\n";
	} else {
		print $langs->trans('MigrationContractsIncoherentCreationDateNothingToUpdate')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Reouverture des contrats qui ont au moins une ligne non fermee
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_contracts_open($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationReopeningContracts')."</b><br>\n";

	$sql = "SELECT c.rowid as cref FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd";
	$sql .= " WHERE cd.statut = 4 AND c.statut=2 AND c.rowid=cd.fk_contrat";
	dolibarr_install_syslog("upgrade2::migrate_contracts_open");
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	if ($db->affected_rows($resql) > 0) {
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num) {
			$nbcontratsmodifie = 0;
			$db->begin();

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print $langs->trans('MigrationReopenThisContract', $obj->cref)."<br>\n";
				$sql = "UPDATE ".MAIN_DB_PREFIX."contrat";
				$sql .= " SET statut = 1";
				$sql .= " WHERE rowid = ".((int) $obj->cref);
				$resql2 = $db->query($sql);
				if (!$resql2) {
					dol_print_error($db);
				}

				$nbcontratsmodifie++;

				$i++;
			}

			$db->commit();

			if ($nbcontratsmodifie) {
				print $langs->trans('MigrationReopenedContractsNumber', $nbcontratsmodifie)."<br>\n";
			} else {
				print $langs->trans('MigrationReopeningContractsNothingToUpdate')."<br>\n";
			}
		}
	} else {
		print $langs->trans('MigrationReopeningContractsNothingToUpdate')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Factures fournisseurs
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiementfourn_facturefourn($db, $langs, $conf)
{
	global $bc;

	print '<tr><td colspan="4">';
	print '<br>';
	print '<b>'.$langs->trans('SuppliersInvoices')."</b><br>\n";
	print '</td></tr>';

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."paiementfourn", "fk_facture_fourn");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$error = 0;
		$nb = 0;

		$select_sql = 'SELECT rowid, fk_facture_fourn, amount';
		$select_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn';
		$select_sql .= ' WHERE fk_facture_fourn IS NOT NULL';

		dolibarr_install_syslog("upgrade2::migrate_paiementfourn_facturefourn");
		$select_resql = $db->query($select_sql);
		if ($select_resql) {
			$select_num = $db->num_rows($select_resql);
			$i = 0;

			// Pour chaque paiement fournisseur, on insere une ligne dans paiementfourn_facturefourn
			while (($i < $select_num) && (!$error)) {
				$select_obj = $db->fetch_object($select_resql);

				// Verifier si la ligne est deja dans la nouvelle table. On ne veut pas inserer de doublons.
				$check_sql = 'SELECT fk_paiementfourn, fk_facturefourn';
				$check_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
				$check_sql .= ' WHERE fk_paiementfourn = '.((int) $select_obj->rowid).' AND fk_facturefourn = '.((int) $select_obj->fk_facture_fourn);
				$check_resql = $db->query($check_sql);
				if ($check_resql) {
					$check_num = $db->num_rows($check_resql);
					if ($check_num == 0) {
						$db->begin();

						if ($nb == 0) {
							print '<tr><td colspan="4" class="nowrap"><b>'.$langs->trans('SuppliersInvoices').'</b></td></tr>';
							print '<tr><td>fk_paiementfourn</td><td>fk_facturefourn</td><td>'.$langs->trans('Amount').'</td><td>&nbsp;</td></tr>';
						}

						print '<tr class="oddeven">';
						print '<td>'.$select_obj->rowid.'</td><td>'.$select_obj->fk_facture_fourn.'</td><td>'.$select_obj->amount.'</td>';

						$insert_sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn SET ';
						$insert_sql .= ' fk_paiementfourn = \''.$select_obj->rowid.'\',';
						$insert_sql .= ' fk_facturefourn  = \''.$select_obj->fk_facture_fourn.'\',';
						$insert_sql .= ' amount           = \''.$select_obj->amount.'\'';
						$insert_resql = $db->query($insert_sql);

						if ($insert_resql) {
							$nb++;
							print '<td><span class="ok">'.$langs->trans("OK").'</span></td>';
						} else {
							print '<td><span class="error">Error on insert</span></td>';
							$error++;
						}
						print '</tr>';
					}
				} else {
					$error++;
				}
				$i++;
			}
		} else {
			$error++;
		}

		if (!$error) {
			if (!$nb) {
				print '<tr><td>'.$langs->trans("AlreadyDone").'</td></tr>';
			}
			$db->commit();

			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."paiementfourn DROP COLUMN fk_facture_fourn";
			$db->query($sql);
		} else {
			print '<tr><td>'.$langs->trans("Error").'</td></tr>';
			$db->rollback();
		}
	} else {
		print '<tr><td>'.$langs->trans("AlreadyDone").'</td></tr>';
	}
}

/**
 * Update total of invoice lines
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_facture($db, $langs, $conf)
{
	$err = 0;

	$tmpmysoc = new Societe($db);
	$tmpmysoc->setMysoc($conf);

	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationInvoice')."</b><br>\n";

	// List of invoice lines not up to date
	$sql = "SELECT fd.rowid, fd.qty, fd.subprice, fd.remise_percent, fd.tva_tx as vatrate, fd.total_ttc, fd.info_bits,";
	$sql .= " f.rowid as facid, f.remise_percent as remise_percent_global, f.total_ttc as total_ttc_f";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd, ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE fd.fk_facture = f.rowid";
	$sql .= " AND (((fd.total_ttc = 0 AND fd.remise_percent != 100) or fd.total_ttc IS NULL) or f.total_ttc IS NULL)";
	//print $sql;

	dolibarr_install_syslog("upgrade2::migrate_price_facture");
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$vatrate = $obj->vatrate;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$total_ttc_f = $obj->total_ttc_f;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$facligne = new FactureLigne($db);
				$facligne->fetch($rowid);

				$result = calcul_price_total($qty, $pu, $remise_percent, $vatrate, 0, 0, $remise_percent_global, 'HT', $info_bits, $facligne->product_type, $tmpmysoc);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$facligne->total_ht  = (float) $total_ht;
				$facligne->total_tva = (float) $total_tva;
				$facligne->total_ttc = (float) $total_ttc;

				dolibarr_install_syslog("upgrade2: line ".$rowid.": facid=".$obj->facid." pu=".$pu." qty=".$qty." vatrate=".$vatrate." remise_percent=".$remise_percent." remise_global=".$remise_percent_global." -> ".$total_ht.", ".$total_tva.", ".$total_ttc);
				print '. ';
				$facligne->update_total();


				/* On touche a facture mere uniquement si total_ttc = 0 */
				if (!$total_ttc_f) {
					$facture = new Facture($db);
					$facture->id = $obj->facid;

					if ($facture->fetch($facture->id) >= 0) {
						if ($facture->update_price() > 0) {
							//print $facture->id;
						} else {
							print "Error id=".$facture->id;
							$err++;
						}
					} else {
						print "Error #3";
						$err++;
					}
				}
				print " ";

				$i++;
			}
		} else {
			print $langs->trans("AlreadyDone");
		}
		$db->free($resql);

		$db->commit();
	} else {
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}

/**
 * Update total of proposal lines
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_propal($db, $langs, $conf)
{
	$tmpmysoc = new Societe($db);
	$tmpmysoc->setMysoc($conf);

	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationProposal')."</b><br>\n";

	// List of proposal lines not up to date
	$sql = "SELECT pd.rowid, pd.qty, pd.subprice, pd.remise_percent, pd.tva_tx as vatrate, pd.info_bits,";
	$sql .= " p.rowid as propalid, p.remise_percent as remise_percent_global";
	$sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."propal as p";
	$sql .= " WHERE pd.fk_propal = p.rowid";
	$sql .= " AND ((pd.total_ttc = 0 AND pd.remise_percent != 100) or pd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_propal");
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$vatrate = $obj->vatrate;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$propalligne = new PropaleLigne($db);
				$propalligne->fetch($rowid);

				$result = calcul_price_total($qty, $pu, $remise_percent, $vatrate, 0, 0, $remise_percent_global, 'HT', $info_bits, $propalligne->product_type, $tmpmysoc);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$propalligne->total_ht  = (float) $total_ht;
				$propalligne->total_tva = (float) $total_tva;
				$propalligne->total_ttc = (float) $total_ttc;

				dolibarr_install_syslog("upgrade2: Line ".$rowid.": propalid=".$obj->rowid." pu=".$pu." qty=".$qty." vatrate=".$vatrate." remise_percent=".$remise_percent." remise_global=".$remise_percent_global." -> ".$total_ht.", ".$total_tva.", ".$total_ttc);
				print '. ';
				$propalligne->update_total();

				$i++;
			}
		} else {
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		$db->commit();
	} else {
		print "Error #1 ".$db->error();

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}

/**
 * Update total of contract lines
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_contrat($db, $langs, $conf)
{
	$db->begin();

	$tmpmysoc = new Societe($db);
	$tmpmysoc->setMysoc($conf);
	if (empty($tmpmysoc->country_id)) {
		$tmpmysoc->country_id = 0; // Ti not have this set to '' or will make sql syntax error.
	}

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContract')."</b><br>\n";

	// List of contract lines not up to date
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as vatrate, cd.info_bits,";
	$sql .= " c.rowid as contratid";
	$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
	$sql .= " WHERE cd.fk_contrat = c.rowid";
	$sql .= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100 AND cd.subprice > 0) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_contrat");
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$vatrate = $obj->vatrate;
				$remise_percent = $obj->remise_percent;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$contratligne = new ContratLigne($db);
				//$contratligne->fetch($rowid); Non requis car le update_total ne met a jour que chp redefinis
				$contratligne->fetch($rowid);

				$result = calcul_price_total($qty, $pu, $remise_percent, $vatrate, 0, 0, 0, 'HT', $info_bits, $contratligne->product_type, $tmpmysoc);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$contratligne->total_ht  = (float) $total_ht;
				$contratligne->total_tva = (float) $total_tva;
				$contratligne->total_ttc = (float) $total_ttc;

				dolibarr_install_syslog("upgrade2: Line ".$rowid.": contratdetid=".$obj->rowid." pu=".$pu." qty=".$qty." vatrate=".$vatrate." remise_percent=".$remise_percent."  -> ".$total_ht.", ".$total_tva." , ".$total_ttc);
				print '. ';
				$contratligne->update_total();

				$i++;
			}
		} else {
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		$db->commit();
	} else {
		print "Error #1 ".$db->error();

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}

/**
 * Update total of sales order lines
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_commande($db, $langs, $conf)
{
	$db->begin();

	$tmpmysoc = new Societe($db);
	$tmpmysoc->setMysoc($conf);

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationOrder')."</b><br>\n";

	// List of sales orders lines not up to date
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as vatrate, cd.info_bits,";
	$sql .= " c.rowid as commandeid, c.remise_percent as remise_percent_global";
	$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."commande as c";
	$sql .= " WHERE cd.fk_commande = c.rowid";
	$sql .= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_commande");
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$vatrate = $obj->vatrate;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$commandeligne = new OrderLine($db);
				$commandeligne->fetch($rowid);

				$result = calcul_price_total($qty, $pu, $remise_percent, $vatrate, 0, 0, $remise_percent_global, 'HT', $info_bits, $commandeligne->product_type, $tmpmysoc);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$commandeligne->total_ht  = (float) $total_ht;
				$commandeligne->total_tva = (float) $total_tva;
				$commandeligne->total_ttc = (float) $total_ttc;

				dolibarr_install_syslog("upgrade2: Line ".$rowid." : commandeid=".$obj->rowid." pu=".$pu." qty=".$qty." vatrate=".$vatrate." remise_percent=".$remise_percent." remise_global=".$remise_percent_global."  -> ".$total_ht.", ".$total_tva.", ".$total_ttc);
				print '. ';
				$commandeligne->update_total();

				$i++;
			}
		} else {
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		/*
		 $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
		 $sql.= " WHERE price = 0 and total_ttc = 0 and total_tva = 0 and total_ht = 0 AND remise_percent = 0";
		 $resql=$db->query($sql);
		 if (! $resql)
		 {
		 dol_print_error($db);
		 }
		 */

		$db->commit();
	} else {
		print "Error #1 ".$db->error();

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}

/**
 * Update total of purchase order lines
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_commande_fournisseur($db, $langs, $conf)
{
	global $mysoc;

	$db->begin();

	$tmpmysoc = new Societe($db);
	$tmpmysoc->setMysoc($conf);

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationSupplierOrder')."</b><br>\n";

	// List of purchase order lines not up to date
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as vatrate, cd.info_bits,";
	$sql .= " c.rowid as commandeid, c.remise_percent as remise_percent_global";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd, ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sql .= " WHERE cd.fk_commande = c.rowid";
	$sql .= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_commande_fournisseur");
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$vatrate = $obj->vatrate;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$commandeligne = new CommandeFournisseurLigne($db);
				$commandeligne->fetch($rowid);

				$result = calcul_price_total($qty, $pu, $remise_percent, $vatrate, 0, 0, $remise_percent_global, 'HT', $info_bits, $commandeligne->product_type, $mysoc);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$commandeligne->total_ht  = (float) $total_ht;
				$commandeligne->total_tva = (float) $total_tva;
				$commandeligne->total_ttc = (float) $total_ttc;

				dolibarr_install_syslog("upgrade2: Line ".$rowid.": commandeid=".$obj->rowid." pu=".$pu."  qty=".$qty." vatrate=".$vatrate." remise_percent=".$remise_percent." remise_global=".$remise_percent_global." -> ".$total_ht.", ".$total_tva.", ".$total_ttc);
				print '. ';
				$commandeligne->update_total();

				$i++;
			}
		} else {
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		/*
		 $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet";
		 $sql.= " WHERE subprice = 0 and total_ttc = 0 and total_tva = 0 and total_ht = 0";
		 $resql=$db->query($sql);
		 if (! $resql)
		 {
		 dol_print_error($db);
		 }
		 */

		$db->commit();
	} else {
		print "Error #1 ".$db->error();

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}

/**
 * Mise a jour des modeles selectionnes
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_modeles($db, $langs, $conf)
{
	//print '<br>';
	//print '<b>'.$langs->trans('UpdateModelsTable')."</b><br>\n";

	dolibarr_install_syslog("upgrade2::migrate_modeles");

	if (isModEnabled('invoice')) {
		include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
		$modellist = ModelePDFFactures::liste_modeles($db);
		if (count($modellist) == 0) {
			// Aucun model par default.
			$sql = " insert into ".MAIN_DB_PREFIX."document_model(nom,type) values('crabe','invoice')";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			}
		}
	}

	if (isModEnabled('order')) {
		include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
		$modellist = ModelePDFCommandes::liste_modeles($db);
		if (count($modellist) == 0) {
			// Aucun model par default.
			$sql = " insert into ".MAIN_DB_PREFIX."document_model(nom,type) values('einstein','order')";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			}
		}
	}

	if (isModEnabled("shipping")) {
		include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
		$modellist = ModelePdfExpedition::liste_modeles($db);
		if (count($modellist) == 0) {
			// Aucun model par default.
			$sql = " insert into ".MAIN_DB_PREFIX."document_model(nom,type) values('rouget','shipping')";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			}
		}
	}

	//print $langs->trans("AlreadyDone");
}


/**
 * Correspondence des expeditions et des commandes clients dans la table llx_co_exp
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_commande_expedition($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_expedition");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationShipmentOrderMatching')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."expedition", "fk_commande");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$error = 0;

		$db->begin();

		$sql = "SELECT e.rowid, e.fk_commande FROM ".MAIN_DB_PREFIX."expedition as e";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."co_exp (fk_expedition,fk_commande)";
					$sql .= " VALUES (".((int) $obj->rowid).", ".((int) $obj->fk_commande).")";
					$resql2 = $db->query($sql);

					if (!$resql2) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			}

			if ($error == 0) {
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."expedition DROP COLUMN fk_commande";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/**
 * Correspondence des livraisons et des commandes clients dans la table llx_co_liv
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_commande_livraison($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_livraison");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryOrderMatching')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraison", "fk_commande");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$error = 0;

		$db->begin();

		$sql = "SELECT l.rowid, l.fk_commande,";
		$sql .= " c.ref_client, c.date_livraison as delivery_date";
		$sql .= " FROM ".MAIN_DB_PREFIX."livraison as l, ".MAIN_DB_PREFIX."commande as c";
		$sql .= " WHERE c.rowid = l.fk_commande";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."co_liv (fk_livraison,fk_commande)";
					$sql .= " VALUES (".((int) $obj->rowid).", ".((int) $obj->fk_commande).")";
					$resql2 = $db->query($sql);

					if ($resql2) {
						$delivery_date = $db->jdate($obj->delivery_date);

						$sqlu = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
						$sqlu .= " ref_client = '".$db->escape($obj->ref_client)."'";
						$sqlu .= ", date_livraison = '".$db->idate($delivery_date)."'";
						$sqlu .= " WHERE rowid = ".((int) $obj->rowid);
						$resql3 = $db->query($sqlu);
						if (!$resql3) {
							$error++;
							dol_print_error($db);
						}
					} else {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			}

			if ($error == 0) {
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraison DROP COLUMN fk_commande";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/**
 * Migration des details commandes dans les details livraisons
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_detail_livraison($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_detail_livraison");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryDetail')."</b><br>\n";

	// This is done if field fk_commande_ligne exists.
	// If not this means migration was already done.
	$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraisondet", "fk_commande_ligne");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$error = 0;

		$db->begin();

		$sql = "SELECT cd.rowid, cd.fk_product, cd.description, cd.subprice, cd.total_ht";
		$sql .= ", ld.fk_livraison";
		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."livraisondet as ld";
		$sql .= " WHERE ld.fk_commande_ligne = cd.rowid";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."livraisondet SET";
					$sql .= " fk_product = ".((int) $obj->fk_product);
					$sql .= ",description = '".$db->escape($obj->description)."'";
					$sql .= ",subprice = ".price2num($obj->subprice);
					$sql .= ",total_ht = ".price2num($obj->total_ht);
					$sql .= " WHERE fk_commande_ligne = ".((int) $obj->rowid);
					$resql2 = $db->query($sql);

					if ($resql2) {
						$sql = "SELECT total_ht";
						$sql .= " FROM ".MAIN_DB_PREFIX."livraison";
						$sql .= " WHERE rowid = ".((int) $obj->fk_livraison);
						$resql3 = $db->query($sql);

						if ($resql3) {
							$obju = $db->fetch_object($resql3);
							$total_ht = $obju->total_ht + $obj->total_ht;

							$sqlu = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
							$sqlu .= " total_ht = ".price2num($total_ht, 'MT');
							$sqlu .= " WHERE rowid = ".((int) $obj->fk_livraison);
							$resql4 = $db->query($sqlu);
							if (!$resql4) {
								$error++;
								dol_print_error($db);
							}
						} else {
							$error++;
							dol_print_error($db);
						}
					} else {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			}

			if ($error == 0) {
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraisondet CHANGE fk_commande_ligne fk_origin_line integer";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraisondet", "fk_origin_line");
		$obj = $db->fetch_object($result);
		if (!$obj) {
			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraisondet ADD COLUMN fk_origin_line integer after fk_livraison";
			$db->query($sql);
		}
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/**
 * Migration du champ stock dans produits
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_stocks($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_stocks");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationStockDetail')."</b><br>\n";

	$error = 0;

	$db->begin();

	$sql = "SELECT SUM(reel) as total, fk_product";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
	$sql .= " GROUP BY fk_product";
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
				$sql .= " stock = ".price2num($obj->total, 'MS');
				$sql .= " WHERE rowid = ".((int) $obj->fk_product);

				$resql2 = $db->query($sql);
				if ($resql2) {
				} else {
					$error++;
					dol_print_error($db);
				}
				print '. ';
				$i++;
			}
		}

		if ($error == 0) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}

/**
 * Migration of menus (use only 1 table instead of 3)
 * 2.6 -> 2.7
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_menus($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_menus");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationMenusDetail')."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."menu_constraint")) {
		$db->begin();

		$sql = "SELECT m.rowid, mc.action";
		$sql .= " FROM ".MAIN_DB_PREFIX."menu_constraint as mc, ".MAIN_DB_PREFIX."menu_const as md, ".MAIN_DB_PREFIX."menu as m";
		$sql .= " WHERE md.fk_menu = m.rowid AND md.fk_constraint = mc.rowid";
		$sql .= " AND m.enabled = '1'";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
					$sql .= " enabled = '".$db->escape($obj->action)."'";
					$sql .= " WHERE rowid = ".((int) $obj->rowid);
					$sql .= " AND enabled = '1'";

					$resql2 = $db->query($sql);
					if ($resql2) {
					} else {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			}

			if ($error == 0) {
				$db->commit();
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Migration du champ fk_adresse_livraison dans expedition
 * 2.6 -> 2.7
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_commande_deliveryaddress($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_deliveryaddress");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryAddress')."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."co_exp")) {
		$db->begin();

		$sql = "SELECT c.fk_adresse_livraison, ce.fk_expedition";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
		$sql .= ", ".MAIN_DB_PREFIX."co_exp as ce";
		$sql .= " WHERE c.rowid = ce.fk_commande";
		$sql .= " AND c.fk_adresse_livraison IS NOT NULL AND c.fk_adresse_livraison != 0";

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
					$sql .= " fk_adresse_livraison = '".$db->escape($obj->fk_adresse_livraison)."'";
					$sql .= " WHERE rowid = ".((int) $obj->fk_expedition);

					$resql2 = $db->query($sql);
					if (!$resql2) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			} else {
				print $langs->trans('AlreadyDone')."<br>\n";
			}

			if ($error == 0) {
				$db->commit();
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Migration du champ fk_remise_except dans llx_facturedet doit correspondre a
 * Link in `llx_societe_remise_except` to `llx_facturedet`
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	integer				Return integer <0 if KO, 0=Bad version, >0 if OK
 */
function migrate_restore_missing_links($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_restore_missing_links");

	if (($db->type == 'mysql' || $db->type == 'mysqli')) {
		if (versioncompare($db->getVersionArray(), array(4, 0)) < 0) {
			dolibarr_install_syslog("upgrade2::migrate_restore_missing_links Version of database too old to make this migrate action");
			return 0;
		}
	}
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationFixData')."</b> (1)<br>\n";

	$error = 0;


	// Restore missing link for this cross foreign key (link 1 <=> 1). Direction 1.
	$table1 = 'facturedet';
	$field1 = 'fk_remise_except';
	$table2 = 'societe_remise_except';
	$field2 = 'fk_facture_line';

	$db->begin();

	$sql = "SELECT t1.rowid, t1.".$field1." as field";
	$sql .= " FROM ".MAIN_DB_PREFIX.$table1." as t1";
	$sql .= " WHERE t1.".$field1." IS NOT NULL AND t1.".$field1." NOT IN";
	$sql .= " (SELECT t2.rowid FROM ".MAIN_DB_PREFIX.$table2." as t2";
	$sql .= " WHERE t1.rowid = t2.".$field2.")";

	dolibarr_install_syslog("upgrade2::migrate_restore_missing_links DIRECTION 1");
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print 'Line '.$obj->rowid.' in '.$table1.' is linked to record '.$obj->field.' in '.$table2.' that has no link to '.$table1.'. We fix this.<br>';

				$sql = "UPDATE ".MAIN_DB_PREFIX.$db->sanitize($table2)." SET";
				$sql .= " ".$db->sanitize($field2)." = '".$db->escape($obj->rowid)."'";
				$sql .= " WHERE rowid = ".((int) $obj->field);

				$resql2 = $db->query($sql);
				if (!$resql2) {
					$error++;
					dol_print_error($db);
				}
				//print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if ($error == 0) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';


	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationFixData')."</b> (2)<br>\n";

	// Restore missing link for this cross foreign key (link 1 <=> 1). Direction 2.
	$table2 = 'facturedet';
	$field2 = 'fk_remise_except';
	$table1 = 'societe_remise_except';
	$field1 = 'fk_facture_line';

	$db->begin();

	$sql = "SELECT t1.rowid, t1.".$field1." as field";
	$sql .= " FROM ".MAIN_DB_PREFIX.$table1." as t1";
	$sql .= " WHERE t1.".$field1." IS NOT NULL AND t1.".$field1." NOT IN";
	$sql .= " (SELECT t2.rowid FROM ".MAIN_DB_PREFIX.$table2." as t2";
	$sql .= " WHERE t1.rowid = t2.".$field2.")";

	dolibarr_install_syslog("upgrade2::migrate_restore_missing_links DIRECTION 2");
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print 'Line '.$obj->rowid.' in '.$table1.' is linked to record '.$obj->field.' in '.$table2.' that has no link to '.$table1.'. We fix this.<br>';

				$sql = "UPDATE ".MAIN_DB_PREFIX.$db->sanitize($table2)." SET";
				$sql .= " ".$db->sanitize($field2)." = '".$db->escape($obj->rowid)."'";
				$sql .= " WHERE rowid = ".((int) $obj->field);

				$resql2 = $db->query($sql);
				if (!$resql2) {
					$error++;
					dol_print_error($db);
				}
				//print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if ($error == 0) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';

	return ($error ? -1 : 1);
}

/**
 * Migration du champ fk_user_resp de llx_projet vers llx_element_contact
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_project_user_resp($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_project_user_resp");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationProjectUserResp')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."projet", "fk_user_resp");
	$obj = $db->fetch_object($result);
	if ($obj) {
		$error = 0;

		$db->begin();

		$sql = "SELECT rowid, fk_user_resp FROM ".MAIN_DB_PREFIX."projet";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."element_contact (";
					$sql2 .= "datecreate";
					$sql2 .= ", statut";
					$sql2 .= ", element_id";
					$sql2 .= ", fk_c_type_contact";
					$sql2 .= ", fk_socpeople";
					$sql2 .= ") VALUES (";
					$sql2 .= "'".$db->idate(dol_now())."'";
					$sql2 .= ", '4'";
					$sql2 .= ", ".$obj->rowid;
					$sql2 .= ", '160'";
					$sql2 .= ", ".$obj->fk_user_resp;
					$sql2 .= ")";

					if ($obj->fk_user_resp > 0) {
						$resql2 = $db->query($sql2);
						if (!$resql2) {
							$error++;
							dol_print_error($db);
						}
					}
					print '. ';

					$i++;
				}
			}

			if ($error == 0) {
				$sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."projet DROP COLUMN fk_user_resp";
				if ($db->query($sqlDrop)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/**
 * Migration de la table llx_projet_task_actors vers llx_element_contact
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_project_task_actors($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_project_task_actors");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationProjectTaskActors')."</b><br>\n";

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."projet_task_actors")) {
		$error = 0;

		$db->begin();

		$sql = "SELECT fk_projet_task as fk_project_task, fk_user FROM ".MAIN_DB_PREFIX."projet_task_actors";
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."element_contact (";
					$sql2 .= "datecreate";
					$sql2 .= ", statut";
					$sql2 .= ", element_id";
					$sql2 .= ", fk_c_type_contact";
					$sql2 .= ", fk_socpeople";
					$sql2 .= ") VALUES (";
					$sql2 .= "'".$db->idate(dol_now())."'";
					$sql2 .= ", '4'";
					$sql2 .= ", ".$obj->fk_project_task;
					$sql2 .= ", '180'";
					$sql2 .= ", ".$obj->fk_user;
					$sql2 .= ")";

					$resql2 = $db->query($sql2);

					if (!$resql2) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			}

			if ($error == 0) {
				$sqlDrop = "DROP TABLE ".MAIN_DB_PREFIX."projet_task_actors";
				if ($db->query($sqlDrop)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/**
 * Migration des tables de relation
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @param	string		$table			Table name
 * @param	string		$fk_source		Id of element source (name of field)
 * @param	string		$sourcetype		Type of element source
 * @param	string		$fk_target		Id of element target
 * @param	string		$targettype		Type of element target
 * @return	void
 */
function migrate_relationship_tables($db, $langs, $conf, $table, $fk_source, $sourcetype, $fk_target, $targettype)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationRelationshipTables', MAIN_DB_PREFIX.$table)."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX.$table)) {
		dolibarr_install_syslog("upgrade2::migrate_relationship_tables table = ".MAIN_DB_PREFIX.$table);

		$db->begin();

		$sqlSelect = "SELECT ".$fk_source.", ".$fk_target;
		$sqlSelect .= " FROM ".MAIN_DB_PREFIX.$table;

		$resql = $db->query($sqlSelect);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
					$sqlInsert .= "fk_source";
					$sqlInsert .= ", sourcetype";
					$sqlInsert .= ", fk_target";
					$sqlInsert .= ", targettype";
					$sqlInsert .= ") VALUES (";
					$sqlInsert .= $obj->$fk_source;
					$sqlInsert .= ", '".$db->escape($sourcetype)."'";
					$sqlInsert .= ", ".$obj->$fk_target;
					$sqlInsert .= ", '".$db->escape($targettype)."'";
					$sqlInsert .= ")";

					$result = $db->query($sqlInsert);
					if (!$result) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			} else {
				print $langs->trans('AlreadyDone')."<br>\n";
			}

			if ($error == 0) {
				$sqlDrop = "DROP TABLE ".MAIN_DB_PREFIX.$table;
				if ($db->query($sqlDrop)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Migrate duration in seconds
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_element_time($db, $langs, $conf)
{
	dolibarr_install_syslog("upgrade2::migrate_element_time");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationProjectTaskTime')."</b><br>\n";

	$error = 0;

	$db->begin();

	$sql = "SELECT rowid, fk_element, element_duration";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			$totaltime = array();
			$oldtime = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj->element_duration > 0) {
					// convert to second
					// only for int time and float time ex: 1.75 for 1h45
					list($hour, $min) = explode('.', $obj->element_duration);
					$hour = (int) $hour * 60 * 60;
					$min = (int) ((float) ('.'.$min)) * 3600;
					$newtime = $hour + $min;

					$sql2 = "UPDATE ".MAIN_DB_PREFIX."element_time SET";
					$sql2 .= " element_duration = ".((int) $newtime);
					$sql2 .= " WHERE rowid = ".((int) $obj->rowid);

					$resql2 = $db->query($sql2);
					if (!$resql2) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$oldtime++;
					if (!empty($totaltime[$obj->fk_element])) {
						$totaltime[$obj->fk_element] += $newtime;
					} else {
						$totaltime[$obj->fk_element] = $newtime;
					}
				} else {
					if (!empty($totaltime[$obj->fk_element])) {
						$totaltime[$obj->fk_element] += $obj->element_duration;
					} else {
						$totaltime[$obj->fk_element] = $obj->element_duration;
					}
				}

				$i++;
			}

			if ($error == 0) {
				if ($oldtime > 0) {
					foreach ($totaltime as $taskid => $total_duration) {
						$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
						$sql .= " duration_effective = ".((int) $total_duration);
						$sql .= " WHERE rowid = ".((int) $taskid);

						$resql = $db->query($sql);
						if (!$resql) {
							$error++;
							dol_print_error($db);
						}
					}
				} else {
					print $langs->trans('AlreadyDone')."<br>\n";
				}
			} else {
				dol_print_error($db);
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}
	} else {
		dol_print_error($db);
	}

	if ($error == 0) {
		$db->commit();
	} else {
		$db->rollback();
	}

	print '</td></tr>';
}

/**
 * Migrate order ref_customer and date_delivery fields to llx_expedition
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_customerorder_shipping($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationCustomerOrderShipping')."</b><br>\n";

	$error = 0;

	$result1 = $db->DDLDescTable(MAIN_DB_PREFIX."expedition", "ref_customer");
	$result2 = $db->DDLDescTable(MAIN_DB_PREFIX."expedition", "date_delivery");
	$obj1 = $db->fetch_object($result1);
	$obj2 = $db->fetch_object($result2);
	if (!$obj1 && !$obj2) {
		dolibarr_install_syslog("upgrade2::migrate_customerorder_shipping");

		$db->begin();

		$sqlAdd1 = "ALTER TABLE ".MAIN_DB_PREFIX."expedition ADD COLUMN ref_customer varchar(30) AFTER entity";
		$sqlAdd2 = "ALTER TABLE ".MAIN_DB_PREFIX."expedition ADD COLUMN date_delivery date DEFAULT NULL AFTER date_expedition";

		if ($db->query($sqlAdd1) && $db->query($sqlAdd2)) {
			$sqlSelect = "SELECT e.rowid as shipping_id, c.ref_client, c.date_livraison as delivery_date";
			$sqlSelect .= " FROM ".MAIN_DB_PREFIX."expedition as e";
			$sqlSelect .= ", ".MAIN_DB_PREFIX."element_element as el";
			$sqlSelect .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.rowid = el.fk_source AND el.sourcetype = 'commande'";
			$sqlSelect .= " WHERE e.rowid = el.fk_target";
			$sqlSelect .= " AND el.targettype = 'shipping'";

			$resql = $db->query($sqlSelect);
			if ($resql) {
				$i = 0;
				$num = $db->num_rows($resql);

				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);

						$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
						$sqlUpdate .= " ref_customer = '".$db->escape($obj->ref_client)."'";
						$sqlUpdate .= ", date_delivery = '".$db->escape($obj->delivery_date ? $obj->delivery_date : 'null')."'";
						$sqlUpdate .= " WHERE rowid = ".((int) $obj->shipping_id);

						$result = $db->query($sqlUpdate);
						if (!$result) {
							$error++;
							dol_print_error($db);
						}
						print '. ';
						$i++;
					}
				} else {
					print $langs->trans('AlreadyDone')."<br>\n";
				}

				if ($error == 0) {
					$db->commit();
				} else {
					dol_print_error($db);
					$db->rollback();
				}
			} else {
				dol_print_error($db);
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Migrate link stored into fk_expedition into llx_element_element
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_shipping_delivery($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationShippingDelivery')."</b><br>\n";

	$error = 0;

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraison", "fk_expedition");
	$obj = $db->fetch_object($result);
	if ($obj) {
		dolibarr_install_syslog("upgrade2::migrate_shipping_delivery");

		$db->begin();

		$sqlSelect = "SELECT rowid, fk_expedition";
		$sqlSelect .= " FROM ".MAIN_DB_PREFIX."livraison";
		$sqlSelect .= " WHERE fk_expedition is not null";

		$resql = $db->query($sqlSelect);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
					$sqlInsert .= "fk_source";
					$sqlInsert .= ", sourcetype";
					$sqlInsert .= ", fk_target";
					$sqlInsert .= ", targettype";
					$sqlInsert .= ") VALUES (";
					$sqlInsert .= $obj->fk_expedition;
					$sqlInsert .= ", 'shipping'";
					$sqlInsert .= ", ".$obj->rowid;
					$sqlInsert .= ", 'delivery'";
					$sqlInsert .= ")";

					$result = $db->query($sqlInsert);
					if ($result) {
						$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."livraison SET fk_expedition = NULL";
						$sqlUpdate .= " WHERE rowid = ".((int) $obj->rowid);

						$result = $db->query($sqlUpdate);
						if (!$result) {
							$error++;
							dol_print_error($db);
						}
						print '. ';
					} else {
						$error++;
						dol_print_error($db);
					}
					$i++;
				}
			} else {
				print $langs->trans('AlreadyDone')."<br>\n";
			}

			if ($error == 0) {
				$sqlDelete = "DELETE FROM ".MAIN_DB_PREFIX."element_element WHERE sourcetype = 'commande' AND targettype = 'delivery'";
				$db->query($sqlDelete);

				$db->commit();

				// DDL commands must not be inside a transaction
				$sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."livraison DROP COLUMN fk_expedition";
				$db->query($sqlDrop);
			} else {
				dol_print_error($db);
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * We try to complete field ref_customer and date_delivery that are empty into llx_livraison.
 * We set them with value from llx_expedition.
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_shipping_delivery2($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationShippingDelivery2')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_shipping_delivery2");

	$db->begin();

	$sqlSelect = "SELECT l.rowid as delivery_id, e.ref_customer, e.date_delivery";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."livraison as l,";
	$sqlSelect .= " ".MAIN_DB_PREFIX."element_element as el,";
	$sqlSelect .= " ".MAIN_DB_PREFIX."expedition as e";
	$sqlSelect .= " WHERE l.rowid = el.fk_target";
	$sqlSelect .= " AND el.targettype = 'delivery'";
	$sqlSelect .= " AND e.rowid = el.fk_source AND el.sourcetype = 'shipping'";
	$sqlSelect .= " AND (e.ref_customer IS NOT NULL OR e.date_delivery IS NOT NULL)"; // Useless to process this record if both are null
	// Add condition to know if we never migrate this record
	$sqlSelect .= " AND (l.ref_customer IS NULL".($db->type != 'pgsql' ? " or l.ref_customer = ''" : "").")";
	$sqlSelect .= " AND (l.date_delivery IS NULL".($db->type != 'pgsql' ? " or l.date_delivery = ''" : "").")";

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
				$sqlUpdate .= " ref_customer = '".$db->escape($obj->ref_customer)."',";
				$sqlUpdate .= " date_delivery = ".($obj->date_delivery ? "'".$db->escape($obj->date_delivery)."'" : 'null');
				$sqlUpdate .= " WHERE rowid = ".((int) $obj->delivery_id);

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}
				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if ($error == 0) {
			$db->commit();
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}

/**
 * Migrate link stored into fk_xxxx into fk_element and elementtype
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_actioncomm_element($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationActioncommElement')."</b><br>\n";

	$elements = array(
		'propal' => 'propalrowid',
		'order' => 'fk_commande',
		'invoice' => 'fk_facture',
		'contract' => 'fk_contract',
		'order_supplier' => 'fk_supplier_order',
		'invoice_supplier' => 'fk_supplier_invoice'
	);

	foreach ($elements as $type => $field) {
		$result = $db->DDLDescTable(MAIN_DB_PREFIX."actioncomm", $field);
		$obj = $db->fetch_object($result);
		if ($obj) {
			dolibarr_install_syslog("upgrade2::migrate_actioncomm_element field=".$field);

			$db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm SET ";
			$sql .= "fk_element = ".$db->sanitize($field).", elementtype = '".$db->escape($type)."'";
			$sql .= " WHERE ".$db->sanitize($field)." IS NOT NULL";
			$sql .= " AND fk_element IS NULL";
			$sql .= " AND elementtype IS NULL";

			$resql = $db->query($sql);
			if ($resql) {
				$db->commit();

				// DDL commands must not be inside a transaction
				// We will drop at next version because a migrate should be runnable several times if it fails.
				//$sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."actioncomm DROP COLUMN ".$field;
				//$db->query($sqlDrop);
				//print '. ';
			} else {
				dol_print_error($db);
				$db->rollback();
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}
	}

	print '</td></tr>';
}

/**
 * Migrate link stored into fk_mode_reglement
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_mode_reglement($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentMode')."</b><br>\n";

	$elements = array(
		'old_id' => array(5, 8, 9, 10, 11),
		'new_id' => array(50, 51, 52, 53, 54),
		'code' => array('VAD', 'TRA', 'LCR', 'FAC', 'PRO'),
		'tables' => array('commande_fournisseur', 'commande', 'facture_rec', 'facture', 'propal')
	);
	$count = 0;

	foreach ($elements['old_id'] as $key => $old_id) {
		$error = 0;

		dolibarr_install_syslog("upgrade2::migrate_mode_reglement code=".$elements['code'][$key]);

		$sqlSelect = "SELECT id";
		$sqlSelect .= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sqlSelect .= " WHERE id = ".((int) $old_id);
		$sqlSelect .= " AND code = '".$db->escape($elements['code'][$key])."'";

		$resql = $db->query($sqlSelect);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num) {
				$count++;

				$db->begin();

				$sqla = "UPDATE ".MAIN_DB_PREFIX."paiement SET";
				$sqla .= " fk_paiement = ".((int) $elements['new_id'][$key]);
				$sqla .= " WHERE fk_paiement = ".((int) $old_id);
				$sqla .= " AND fk_paiement IN (SELECT id FROM ".MAIN_DB_PREFIX."c_paiement WHERE id = ".((int) $old_id)." AND code = '".$db->escape($elements['code'][$key])."')";
				$resqla = $db->query($sqla);

				$sql = "UPDATE ".MAIN_DB_PREFIX."c_paiement SET";
				$sql .= " id = ".((int) $elements['new_id'][$key]);
				$sql .= " WHERE id = ".((int) $old_id);
				$sql .= " AND code = '".$db->escape($elements['code'][$key])."'";
				$resql = $db->query($sql);

				if ($resqla && $resql) {
					foreach ($elements['tables'] as $table) {
						$sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET ";
						$sql .= "fk_mode_reglement = ".((int) $elements['new_id'][$key]);
						$sql .= " WHERE fk_mode_reglement = ".((int) $old_id);

						$resql = $db->query($sql);
						if (!$resql) {
							dol_print_error($db);
							$error++;
						}
						print '. ';
					}

					if (!$error) {
						$db->commit();
					} else {
						dol_print_error($db);
						$db->rollback();
					}
				} else {
					dol_print_error($db);
					$db->rollback();
				}
			}
		}
	}

	if ($count == 0) {
		print $langs->trans('AlreadyDone')."<br>\n";
	}


	print '</td></tr>';
}


/**
 * Delete duplicates in table categorie_association
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	void
 */
function migrate_clean_association($db, $langs, $conf)
{
	$result = $db->DDLDescTable(MAIN_DB_PREFIX."categorie_association");
	if ($result) {	// result defined for version 3.2 or -
		$obj = $db->fetch_object($result);
		if ($obj) {	// It table categorie_association exists
			$couples = array();
			$children = array();
			$sql = "SELECT fk_categorie_mere, fk_categorie_fille";
			$sql .= " FROM ".MAIN_DB_PREFIX."categorie_association";
			dolibarr_install_syslog("upgrade: search duplicate");
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				while ($obj = $db->fetch_object($resql)) {
					if (!isset($children[$obj->fk_categorie_fille])) {	// Only one record as child (a child has only on parent).
						if ($obj->fk_categorie_mere != $obj->fk_categorie_fille) {
							$children[$obj->fk_categorie_fille] = 1; // Set record for this child
							$couples[$obj->fk_categorie_mere.'_'.$obj->fk_categorie_fille] = array('mere' => $obj->fk_categorie_mere, 'fille' => $obj->fk_categorie_fille);
						}
					}
				}

				dolibarr_install_syslog("upgrade: result is num=".$num." count(couples)=".count($couples));

				// If there is duplicates couples or child with two parents
				if (count($couples) > 0 && $num > count($couples)) {
					$error = 0;

					$db->begin();

					// We delete all
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
					dolibarr_install_syslog("upgrade: delete association");
					$resqld = $db->query($sql);
					if ($resqld) {
						// And we insert only each record once
						foreach ($couples as $key => $val) {
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association(fk_categorie_mere,fk_categorie_fille)";
							$sql .= " VALUES(".((int) $val['mere']).", ".((int) $val['fille']).")";
							dolibarr_install_syslog("upgrade: insert association");
							$resqli = $db->query($sql);
							if (!$resqli) {
								$error++;
							}
						}
					}

					if (!$error) {
						print '<tr><td>'.$langs->trans("MigrationCategorieAssociation").'</td>';
						print '<td class="right">'.$langs->trans("RemoveDuplicates").' '.$langs->trans("Success").' ('.$num.'=>'.count($couples).')</td></tr>';
						$db->commit();
					} else {
						print '<tr><td>'.$langs->trans("MigrationCategorieAssociation").'</td>';
						print '<td class="right">'.$langs->trans("RemoveDuplicates").' '.$langs->trans("Failed").'</td></tr>';
						$db->rollback();
					}
				}
			} else {
				print '<tr><td>'.$langs->trans("Error").'</td>';
				print '<td class="right"><div class="error">'.$db->lasterror().'</div></td></tr>';
			}
		}
	}
}


/**
 * Migrate categorie association
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_categorie_association($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationCategorieAssociation')."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."categorie_association")) {
		dolibarr_install_syslog("upgrade2::migrate_categorie_association");

		$db->begin();

		$sqlSelect = "SELECT fk_categorie_mere, fk_categorie_fille";
		$sqlSelect .= " FROM ".MAIN_DB_PREFIX."categorie_association";

		$resql = $db->query($sqlSelect);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."categorie SET ";
					$sqlUpdate .= "fk_parent = ".((int) $obj->fk_categorie_mere);
					$sqlUpdate .= " WHERE rowid = ".((int) $obj->fk_categorie_fille);

					$result = $db->query($sqlUpdate);
					if (!$result) {
						$error++;
						dol_print_error($db);
					}
					print '. ';
					$i++;
				}
			} else {
				print $langs->trans('AlreadyDone')."<br>\n";
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}
		} else {
			dol_print_error($db);
			$db->rollback();
		}
	} else {
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/**
 * Migrate event assignment to owner
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_event_assignement($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationEvents')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_event_assignement");

	$db->begin();

	$sqlSelect = "SELECT a.id, a.fk_user_action";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	$sqlSelect .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON ar.fk_actioncomm = a.id AND ar.element_type = 'user' AND ar.fk_element = a.fk_user_action";
	$sqlSelect .= " WHERE fk_user_action > 0 AND fk_user_action NOT IN (SELECT fk_element FROM ".MAIN_DB_PREFIX."actioncomm_resources as ar WHERE ar.fk_actioncomm = a.id AND ar.element_type = 'user')";
	$sqlSelect .= " ORDER BY a.id";
	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element) ";
				$sqlUpdate .= "VALUES(".((int) $obj->id).", 'user', ".((int) $obj->fk_user_action).")";

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}
				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}


	print '</td></tr>';
}

/**
 * Migrate event assignment to owner
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_event_assignement_contact($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationEventsContact')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_event_assignement");

	$db->begin();

	$sqlSelect = "SELECT a.id, a.fk_contact";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	$sqlSelect .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON ar.fk_actioncomm = a.id AND ar.element_type = 'socpeople' AND ar.fk_element = a.fk_contact";
	$sqlSelect .= " WHERE fk_contact > 0 AND fk_contact NOT IN (SELECT fk_element FROM ".MAIN_DB_PREFIX."actioncomm_resources as ar WHERE ar.fk_actioncomm = a.id AND ar.element_type = 'socpeople')";
	$sqlSelect .= " ORDER BY a.id";
	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element) ";
				$sqlUpdate .= "VALUES(".((int) $obj->id).", 'socpeople', ".((int) $obj->fk_contact).")";

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}
				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}


	print '</td></tr>';
}


/**
 * Migrate to reset the blocked log for V7+ algorithm
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_reset_blocked_log($db, $langs, $conf)
{
	global $user;

	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationResetBlockedLog')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_reset_blocked_log");

	$db->begin();

	$sqlSelect = "SELECT DISTINCT entity";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."blockedlog";

	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print 'Process entity '.$obj->entity;

				$sqlSearch = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."blockedlog WHERE action = 'MODULE_SET' and entity = ".((int) $obj->entity);
				$resqlSearch = $db->query($sqlSearch);
				if ($resqlSearch) {
					$objSearch = $db->fetch_object($resqlSearch);
					//var_dump($objSearch);
					if ($objSearch && $objSearch->nb == 0) {
						print ' - Record for entity must be reset...';

						$sqlUpdate = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog";
						$sqlUpdate .= " WHERE entity = ".((int) $obj->entity);
						$resqlUpdate = $db->query($sqlUpdate);
						if (!$resqlUpdate) {
							$error++;
							dol_print_error($db);
						} else {
							// Add set line
							$object = new stdClass();
							$object->id = 1;
							$object->element = 'module';
							$object->ref = 'systemevent';
							$object->entity = $obj->entity;
							$object->date = dol_now();

							$b = new BlockedLog($db);
							$b->setObjectData($object, 'MODULE_SET', 0);

							$res = $b->create($user);
							if ($res <= 0) {
								$error++;
							}
						}
					} else {
						print ' - '.$langs->trans('AlreadyInV7').'<br>';
					}
				} else {
					dol_print_error($db);
				}

				$i++;
			}
		} else {
			print $langs->trans('NothingToDo')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}


/**
 * Migrate to add entity value into llx_societe_remise
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_remise_entity($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationRemiseEntity')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_remise_entity");

	$db->begin();

	$sqlSelect = "SELECT sr.rowid, s.entity";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."societe_remise as sr, ".MAIN_DB_PREFIX."societe as s";
	$sqlSelect .= " WHERE sr.fk_soc = s.rowid and sr.entity != s.entity";

	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."societe_remise SET";
				$sqlUpdate .= " entity = ".$obj->entity;
				$sqlUpdate .= " WHERE rowid = ".((int) $obj->rowid);

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}

				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}

/**
 * Migrate to add entity value into llx_societe_remise_except
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_remise_except_entity($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationRemiseExceptEntity')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_remise_except_entity");

	$db->begin();

	$sqlSelect = "SELECT sr.rowid, sr.fk_soc, sr.fk_facture_source, sr.fk_facture, sr.fk_facture_line";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as sr";
	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if (!empty($obj->fk_facture_source) || !empty($obj->fk_facture)) {
					$fk_facture = (!empty($obj->fk_facture_source) ? $obj->fk_facture_source : $obj->fk_facture);

					$sqlSelect2 = "SELECT f.entity";
					$sqlSelect2 .= " FROM ".MAIN_DB_PREFIX."facture as f";
					$sqlSelect2 .= " WHERE f.rowid = ".((int) $fk_facture);
				} elseif (!empty($obj->fk_facture_line)) {
					$sqlSelect2 = "SELECT f.entity";
					$sqlSelect2 .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd";
					$sqlSelect2 .= " WHERE fd.rowid = ".((int) $obj->fk_facture_line);
					$sqlSelect2 .= " AND fd.fk_facture = f.rowid";
				} else {
					$sqlSelect2 = "SELECT s.entity";
					$sqlSelect2 .= " FROM ".MAIN_DB_PREFIX."societe as s";
					$sqlSelect2 .= " WHERE s.rowid = ".((int) $obj->fk_soc);
				}

				$resql2 = $db->query($sqlSelect2);
				if ($resql2) {
					if ($db->num_rows($resql2) > 0) {
						$obj2 = $db->fetch_object($resql2);

						$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."societe_remise_except SET";
						$sqlUpdate .= " entity = ".((int) $obj2->entity);
						$sqlUpdate .= " WHERE rowid = ".((int) $obj->rowid);

						$result = $db->query($sqlUpdate);
						if (!$result) {
							$error++;
							dol_print_error($db);
						}
					}
				} else {
					$error++;
					dol_print_error($db);
				}

				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}


	print '</td></tr>';
}

/**
 * Migrate to add entity value into llx_user_rights
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_user_rights_entity($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<b>'.$langs->trans('MigrationUserRightsEntity')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_user_rights_entity");

	$db->begin();

	$sqlSelect = "SELECT u.rowid, u.entity";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sqlSelect .= " WHERE u.entity > 1";
	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."user_rights SET";
				$sqlUpdate .= " entity = ".((int) $obj->entity);
				$sqlUpdate .= " WHERE fk_user = ".((int) $obj->rowid);

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}

				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}


	print '</td></tr>';
}

/**
 * Migrate to add entity value into llx_usergroup_rights
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_usergroup_rights_entity($db, $langs, $conf)
{
	print '<tr><td colspan="4">';

	print '<b>'.$langs->trans('MigrationUserGroupRightsEntity')."</b><br>\n";

	$error = 0;

	dolibarr_install_syslog("upgrade2::migrate_usergroup_rights_entity");

	$db->begin();

	$sqlSelect = "SELECT u.rowid, u.entity";
	$sqlSelect .= " FROM ".MAIN_DB_PREFIX."usergroup as u";
	$sqlSelect .= " WHERE u.entity > 1";
	//print $sqlSelect;

	$resql = $db->query($sqlSelect);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."usergroup_rights SET";
				$sqlUpdate .= " entity = ".((int) $obj->entity);
				$sqlUpdate .= " WHERE fk_usergroup = ".((int) $obj->rowid);

				$result = $db->query($sqlUpdate);
				if (!$result) {
					$error++;
					dol_print_error($db);
				}

				print '. ';
				$i++;
			}
		} else {
			print $langs->trans('AlreadyDone')."<br>\n";
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	} else {
		dol_print_error($db);
		$db->rollback();
	}


	print '</td></tr>';
}

/**
 * Migration directory
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @param	string		$oldname	Old name (relative to DOL_DATA_ROOT)
 * @param	string		$newname	New name (relative to DOL_DATA_ROOT)
 * @return	void
 */
function migrate_rename_directories($db, $langs, $conf, $oldname, $newname)
{
	dolibarr_install_syslog("upgrade2::migrate_rename_directories");

	if (is_dir(DOL_DATA_ROOT.$oldname) && !file_exists(DOL_DATA_ROOT.$newname)) {
		dolibarr_install_syslog("upgrade2::migrate_rename_directories move ".DOL_DATA_ROOT.$oldname.' into '.DOL_DATA_ROOT.$newname);
		@rename(DOL_DATA_ROOT.$oldname, DOL_DATA_ROOT.$newname);
	}
}


/**
 * Delete deprecated files
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	boolean
 */
function migrate_delete_old_files($db, $langs, $conf)
{
	$ret = true;

	dolibarr_install_syslog("upgrade2::migrate_delete_old_files");

	// List of files to delete
	$filetodeletearray = array(
		'/core/ajax/ajaxcompanies.php',
		'/core/triggers/interface_demo.class.php',
		'/core/menus/barre_left/default.php',
		'/core/menus/barre_top/default.php',
		'/core/modules/modComptabiliteExpert.class.php',
		'/core/modules/modCommercial.class.php',
		'/core/modules/modProduit.class.php',
		'/core/modules/modSkype.class.php',
		'/core/triggers/interface_modWebcalendar_Webcalsynchro.class.php',
		'/core/triggers/interface_modCommande_Ecotax.class.php',
		'/core/triggers/interface_modCommande_fraisport.class.php',
		'/core/triggers/interface_modPropale_PropalWorkflow.class.php',
		'/core/triggers/interface_99_modWebhook_WebhookTriggers.class.php',
		'/core/triggers/interface_99_modZapier_ZapierTriggers.class.php',
		'/core/menus/smartphone/iphone.lib.php',
		'/core/menus/smartphone/iphone_backoffice.php',
		'/core/menus/smartphone/iphone_frontoffice.php',
		'/core/menus/standard/auguria_backoffice.php',
		'/core/menus/standard/auguria_frontoffice.php',
		'/core/menus/standard/eldy_backoffice.php',
		'/core/menus/standard/eldy_frontoffice.php',
		'/core/modules/export/export_excel.modules.php',
		'/core/modules/export/export_csv.modules.php',
		'/core/modules/export/exportcsv.modules.php',
		'/core/modules/export/export_excel2007new.modules.php',
		'/core/modules/facture/pdf_crabe.modules.php',
		'/core/modules/facture/pdf_oursin.modules.php',
		'/core/modules/mailings/contacts2.modules.php',
		'/core/modules/mailings/contacts3.modules.php',
		'/core/modules/mailings/contacts4.modules.php',
		'/core/modules/mailings/framboise.modules.php',
		'/core/modules/mailings/dolibarr_services_expired.modules.php',
		'/core/modules/mailings/peche.modules.php',
		'/core/modules/mailings/poire.modules.php',
		'/core/modules/mailings/kiwi.modules.php',
		'/core/boxes/box_members.php',

		'/includes/restler/framework/Luracast/Restler/Data/Object.php',
		'/includes/nusoap/lib/class.*',
		'/phenix/inc/triggers/interface_modPhenix_Phenixsynchro.class.php',
		'/webcalendar/inc/triggers/interface_modWebcalendar_webcalsynchro.class.php',

		'/api/class/api_generic.class.php',
		'/asterisk/cidlookup.php',
		'/categories/class/api_category.class.php',
		'/categories/class/api_deprecated_category.class.php',
		'/compta/facture/class/api_invoice.class.php',
		'/commande/class/api_commande.class.php',
		'/partnership/class/api_partnership.class.php',
		'/product/class/api_product.class.php',
		'/recruitment/class/api_recruitment.class.php',
		'/societe/class/api_contact.class.php',
		'/societe/class/api_thirdparty.class.php',
		'/support/online.php',
		'/takepos/class/actions_takepos.class.php',
		'/user/class/api_user.class.php',

		'/install/mysql/tables/llx_c_ticketsup_category.key.sql',
		'/install/mysql/tables/llx_c_ticketsup_category.sql',
		'/install/mysql/tables/llx_c_ticketsup_severity.key.sql',
		'/install/mysql/tables/llx_c_ticketsup_severity.sql',
		'/install/mysql/tables/llx_c_ticketsup_type.key.sql',
		'/install/mysql/tables/llx_c_ticketsup_type.sql'
	);

	/*
	print '<tr><td colspan="4">';
	print '<b>'.$langs->trans('DeleteOldFiles')."</b><br>\n";
	print '</td></tr>';
	*/

	foreach ($filetodeletearray as $filetodelete) {
		//print '<b>'DOL_DOCUMENT_ROOT.$filetodelete."</b><br>\n";
		if (preg_match('/\*/', $filetodelete) || file_exists(DOL_DOCUMENT_ROOT.$filetodelete)) {
			//print "Process file ".$filetodelete."\n";
			$result = dol_delete_file(DOL_DOCUMENT_ROOT.$filetodelete, 0, (preg_match('/\*/', $filetodelete) ? 1 : 0), 0, null, true, 0);
			if (!$result) {
				$langs->load("errors");
				print '<div class="error">'.$langs->trans("Error").': '.$langs->trans("ErrorFailToDeleteFile", DOL_DOCUMENT_ROOT.$filetodelete);
				print ' '.$langs->trans("RemoveItManuallyAndPressF5ToContinue").'</div>';
			} else {
				//print $langs->trans("FileWasRemoved", $filetodelete).'<br>';
			}
		}
	}

	return $ret;
}

/**
 * Remove deprecated directories
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	boolean
 */
function migrate_delete_old_dir($db, $langs, $conf)
{
	$ret = true;

	dolibarr_install_syslog("upgrade2::migrate_delete_old_dir");

	// List of files to delete
	$filetodeletearray = array(
		DOL_DOCUMENT_ROOT.'/core/modules/facture/terre',
		DOL_DOCUMENT_ROOT.'/core/modules/facture/mercure',
	);

	// On linux, we can also removed old directory with a different case than new directory.
	if (!empty($_SERVER["WINDIR"])) {
		$filetodeletearray[] = DOL_DOCUMENT_ROOT.'/includes/phpoffice/PhpSpreadsheet';
	}

	foreach ($filetodeletearray as $filetodelete) {
		$result = 1;
		if (file_exists($filetodelete)) {
			$result = dol_delete_dir_recursive($filetodelete);
		}
		if (!$result) {
			$langs->load("errors");
			print '<div class="error">'.$langs->trans("Error").': '.$langs->trans("ErrorFailToDeleteDir", $filetodelete);
			print ' '.$langs->trans("RemoveItManuallyAndPressF5ToContinue").'</div>';
		}
	}

	return $ret;
}


/**
 * Disable/Re-enable features modules.
 * We must do this when internal menu of module or permissions has changed
 * or when triggers have moved.
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @param	array<string,'noboxes'|'newboxdefonly'|'forceactivate'>	$listofmodule	List of modules, like array('MODULE_KEY_NAME'=>$reloadmode, ...)
 * @param   int<0,1>	$force          1=Reload module even if not already loaded
 * @return	int							Return integer <0 if KO, >0 if OK
 */
function migrate_reload_modules($db, $langs, $conf, $listofmodule = array(), $force = 0)
{
	global $user;

	if (count($listofmodule) == 0) {
		return 0;
	}

	if (!is_object($user)) {
		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($db);	// To avoid error during migration
	}

	dolibarr_install_syslog("upgrade2::migrate_reload_modules force=".$force.", listofmodule=".implode(',', array_keys($listofmodule)));

	$reloadactionformodules = array(
		'MAIN_MODULE_AGENDA' => array('class' => 'modAgenda', 'remove' => 1),
		'MAIN_MODULE_API' => array('class' => 'modApi'),
		'MAIN_MODULE_BARCODE' => array('class' => 'modBarcode', 'remove' => 1),
		'MAIN_MODULE_BLOCKEDLOG' => array('class' => 'modBlockedLog', 'deleteinsertmenus' => 1),
		'MAIN_MODULE_CRON' => array('class' => 'modCron', 'remove' => 1),
		'MAIN_MODULE_EXTERNALSITE' => array('class' => 'modExternalSite', 'remove' => 1),
		'MAIN_MODULE_SOCIETE' => array('class' => 'modSociete', 'remove' => 1),
		'MAIN_MODULE_PRODUIT' => array('class' => 'modProduct'),
		'MAIN_MODULE_SERVICE' => array('class' => 'modService'),
		'MAIN_MODULE_COMMANDE' => array('class' => 'modCommande'),
		'MAIN_MODULE_DON' => array('class' => 'modDon'),
		'MAIN_MODULE_FACTURE' => array('class' => 'modFacture'),
		'MAIN_MODULE_FICHEINTER' => array('class' => 'modFicheinter'),
		'MAIN_MODULE_FOURNISSEUR' => array('class' => 'modFournisseur'),
		'MAIN_MODULE_EXPEDITION' => array('class' => 'modExpedition'),
		'MAIN_MODULE_EXPENSEREPORT' => array('class' => 'modExpenseReport'),
		'MAIN_MODULE_EVENTORGANIZATION' => array('class' => 'modEventOrganization', 'remove' => 1),
		'MAIN_MODULE_ECM' => array('class' => 'modECM', 'remove' => 1),
		'MAIN_MODULE_HOLIDAY' => array('class' => 'modHoliday', 'remove' => 1),
		'MAIN_MODULE_KNOWLEDGEMANAGEMENT' => array('class' => 'modKnowledgeManagement', 'remove' => 1),
		'MAIN_MODULE_LOAN' => array('class' => 'modLoan', 'remove' => 1),
		'MAIN_MODULE_PAYBOX' => array('class' => 'modPaybox', 'remove' => 1),
		'MAIN_MODULE_PROPAL' => array('class' => 'modPropale'),
		'MAIN_MODULE_SUPPLIERPROPOSAL' => array('class' => 'modSupplierProposal', 'remove' => 1),
		'MAIN_MODULE_OPENSURVEY' => array('class' => 'modOpenSurvey', 'remove' => 1),
		'MAIN_MODULE_PRODUCTBATCH' => array('class' => 'modProductBatch', 'remove' => 1),
		'MAIN_MODULE_TAKEPOS' => array('class' => 'modTakePos', 'remove' => 1),
		'MAIN_MODULE_VARIANTS' => array('class' => 'modVariants', 'remove' => 1),
		'MAIN_MODULE_EMAILCOLLECTOR' => array('class' => 'modEmailCollector', 'remove' => 1),
	);

	foreach ($listofmodule as $moduletoreload => $reloadmode) {	// reloadmodule can be 'noboxes', 'newboxdefonly', 'forceactivate'
		if (empty($moduletoreload) || (empty($conf->global->$moduletoreload) && !$force)) {
			continue; // Discard reload if module not enabled
		}

		$mod = null;

		if (!empty($reloadactionformodules[$moduletoreload])) {
			dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module ".$moduletoreload." with mode ".$reloadmode);

			$val = $reloadactionformodules[$moduletoreload];
			$classformodule = $val['class'];
			$res = @include_once DOL_DOCUMENT_ROOT.'/core/modules/'.$classformodule.'.class.php';
			if ($res) {
				$mod = new $classformodule($db);
				if (!empty($val['remove'])) {
					$mod->remove('noboxes');
				}
				if (!empty($val['deleteinsertmenus'])) {
					// We only reload menus
					$mod->delete_menus();
					$mod->insert_menus();
				} else {
					$mod->init($reloadmode);
				}
			}
		} else {	// Other generic cases/modules
			$reg = array();
			$tmp = preg_match('/MAIN_MODULE_([a-zA-Z0-9]+)/', $moduletoreload, $reg);
			if (!empty($reg[1])) {
				if (strtoupper($moduletoreload) == $moduletoreload) {	// If key is un uppercase
					$moduletoreloadshort = ucfirst(strtolower($reg[1]));
				} else { // If key is a mix of up and low case
					$moduletoreloadshort = $reg[1];
				}

				dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module ".$moduletoreloadshort." with mode ".$reloadmode." (generic code)");

				$res = @include_once DOL_DOCUMENT_ROOT.'/core/modules/mod'.$moduletoreloadshort.'.class.php';
				if ($res) {
					$classname = 'mod'.$moduletoreloadshort;
					$mod = new $classname($db);
					'@phan-var-force DolibarrModules $mod';

					//$mod->remove('noboxes');
					$mod->delete_menus(); // We must delete to be sure it is inserted with new values
					$mod->init($reloadmode);
				} else {
					dolibarr_install_syslog('Failed to include '.DOL_DOCUMENT_ROOT.'/core/modules/mod'.$moduletoreloadshort.'.class.php');

					$res = @dol_include_once(strtolower($moduletoreloadshort).'/core/modules/mod'.$moduletoreloadshort.'.class.php');
					if ($res) {
						$classname = 'mod'.$moduletoreloadshort;
						$mod = new $classname($db);
						'@phan-var-force DolibarrModules $mod';
						$mod->init($reloadmode);
					} else {
						dolibarr_install_syslog('Failed to include '.strtolower($moduletoreloadshort).'/core/modules/mod'.$moduletoreloadshort.'.class.php', LOG_ERR);
						print "Error, can't find module with name ".$moduletoreload."\n";
						return -1;
					}
				}
			} else {
				dolibarr_install_syslog("Error, can't find module with name ".$moduletoreload, LOG_ERR);
				print "Error, can't find module with name ".$moduletoreload."\n";
				return -1;
			}
		}

		if (!empty($mod) && is_object($mod)) {
			print '<tr class="trforrunsql"><td colspan="4">';
			print '<b>'.$langs->trans('Upgrade').'</b>: ';
			print $langs->trans('MigrationReloadModule').' '.$mod->getName(); // We keep getName outside of trans because getName is already encoded/translated
			print "<!-- (".$reloadmode.") -->";
			print "<br>\n";
			print '</td></tr>';
		}
	}

	return 1;
}



/**
 * Reload SQL menu file (if dynamic menus, if modified by version)
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	int						Return integer <0 if KO, >0 if OK
 */
function migrate_reload_menu($db, $langs, $conf)
{
	global $conf;
	dolibarr_install_syslog("upgrade2::migrate_reload_menu");

	// Define list of menu handlers to initialize
	$listofmenuhandler = array();
	if (getDolGlobalString('MAIN_MENU_STANDARD') == 'auguria_menu' || getDolGlobalString('MAIN_MENU_SMARTPHONE') == 'auguria_menu'
		|| getDolGlobalString('MAIN_MENUFRONT_STANDARD') == 'auguria_menu' || getDolGlobalString('MAIN_MENUFRONT_SMARTPHONE') == 'auguria_menu') {
		$listofmenuhandler['auguria'] = 1; // We set here only dynamic menu handlers
	}

	foreach ($listofmenuhandler as $key => $val) {
		print '<tr class="trforrunsql"><td colspan="4">';

		//print "x".$key;
		print '<br>';
		print '<b>'.$langs->trans('Upgrade').'</b>: '.$langs->trans('MenuHandler')." ".$key."<br>\n";

		// Load sql ini_menu_handler.sql file
		$dir = DOL_DOCUMENT_ROOT."/core/menus/";
		$file = 'init_menu_'.$key.'.sql';
		if (file_exists($dir.$file)) {
			$result = run_sql($dir.$file, 1, 0, 1, $key);
		}

		print '</td></tr>';
	}

	return 1;
}

/**
 * Migrate file from old path to new one for lot path
 *
 * @return    void
 */
function migrate_productlot_path()
{
	global $conf, $db, $langs, $user;

	if (!is_object($user)) {
		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($db);	// To avoid error during migration
	}

	print '<tr><td colspan="4">';

	print '<b>'.$langs->trans('MigrationProductLotPath')."</b><br>\n";

	$sql = "SELECT rowid , entity, batch, fk_product from ".MAIN_DB_PREFIX."product_lot";
	$resql = $db->query($sql);
	if ($resql) {
		$modulepart="product_batch";
		while ($obj = $db->fetch_object($resql)) {
			$entity = (empty($obj->entity) ? 1 : $obj->entity);
			if ($entity > 1) {
				$dir = DOL_DATA_ROOT.'/'.$entity.'/'.$conf->productbatch->multidir_output[$entity];
			} else {
				$dir = $conf->productbatch->multidir_output[$entity];
			}

			$lot = new Productlot($db);
			$res = $lot->fetch($obj->rowid, $obj->fk_product, $obj->batch);

			if ($dir && $res > 0) {
				$lot->ref = $obj->batch;
				$origin = $dir . '/' . get_exdir(0, 0, 0, 1, $lot, $modulepart);

				$lot->fetch($obj->rowid, $obj->fk_product, $obj->batch);
				$destin = $dir . '/' . get_exdir(0, 0, 0, 1, $lot, $modulepart);

				if (dol_is_dir($origin) && !dol_is_dir($destin)) {
					dol_move_dir($origin, $destin, 0);
				}
			}
		}
	}
	print '</td></tr>';
}

/**
 * Migrate file from old path to new one for users
 *
 * @return	void
 */
function migrate_user_photospath()
{
	global $conf, $db, $langs, $user;

	print '<tr><td colspan="4">';

	print '<b>'.$langs->trans('MigrationUserPhotoPath')."</b><br>\n";

	include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	$fuser = new User($db);
	if (!is_object($user)) {
		$user = $fuser; // To avoid error during migration
	}

	$sql = "SELECT rowid as uid, entity from ".MAIN_DB_PREFIX."user"; // Get list of all users
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			//$fuser->fetch($obj->uid);
			$fuser->id = $obj->uid;
			$fuser->entity = $obj->entity;

			//echo '<hr>'.$fuser->id.' -> '.$fuser->entity;
			$entity = (empty($fuser->entity) ? 1 : $fuser->entity);
			if ($entity > 1) {
				$dir = DOL_DATA_ROOT.'/'.$entity.'/users';
			} else {
				$dir = $conf->user->multidir_output[$entity]; // $conf->user->multidir_output[] for each entity is construct by the multicompany module
			}

			if ($dir) {
				//print "Process user id ".$fuser->id."<br>\n";
				$origin = $dir.'/'.get_exdir($fuser->id, 2, 0, 1, $fuser, 'user'); // Use old behaviour to get x/y path
				$destin = $dir.'/'.$fuser->id;

				$origin_osencoded = dol_osencode($origin);

				dol_mkdir($destin);

				//echo '<hr>'.$origin.' -> '.$destin;
				if (dol_is_dir($origin)) {
					$handle = opendir($origin_osencoded);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							if ($file == '.' || $file == '..') {
								continue;
							}

							if (dol_is_dir($origin.'/'.$file)) {	// it is a dir (like 'thumbs')
								$thumbs = opendir($origin_osencoded.'/'.$file);
								if (is_resource($thumbs)) {
									dol_mkdir($destin.'/'.$file);
									while (($thumb = readdir($thumbs)) !== false) {
										if (!dol_is_file($destin.'/'.$file.'/'.$thumb)) {
											if ($thumb == '.' || $thumb == '..') {
												continue;
											}

											//print $origin.'/'.$file.'/'.$thumb.' -> '.$destin.'/'.$file.'/'.$thumb.'<br>'."\n";
											print '.';
											dol_copy($origin.'/'.$file.'/'.$thumb, $destin.'/'.$file.'/'.$thumb, '0', 0);
											//var_dump('aaa');exit;
										}
									}
									// dol_delete_dir($origin.'/'.$file);
								}
							} else { // it is a file
								if (!dol_is_file($destin.'/'.$file)) {
									//print $origin.'/'.$file.' -> '.$destin.'/'.$file.'<br>'."\n";
									print '.';
									dol_copy($origin.'/'.$file, $destin.'/'.$file, '0', 0);
									//var_dump('eee');exit;
								}
							}
						}
					}
				}
			}
		}
	}

	print '</td></tr>';
}

/**
 * Migrate file from old path users/99/file.jpg into users/99/photos/file.jpg
 *
 * @return	void
 */
function migrate_user_photospath2()
{
	global $db, $langs, $user;

	print '<tr><td colspan="4">';

	print '<b>'.$langs->trans('MigrationUserPhotoPath')."</b><br>\n";

	include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	$fuser = new User($db);
	if (!is_object($user)) {
		$user = $fuser; // To avoid error during migration
	}

	$sql = "SELECT rowid as uid, entity, photo from ".MAIN_DB_PREFIX."user"; // Get list of all users
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			//$fuser->fetch($obj->uid);
			$fuser->id = $obj->uid;
			$fuser->entity = $obj->entity;
			$fuser->photo = $obj->photo;

			//echo '<hr>'.$fuser->id.' -> '.$fuser->entity;
			$entity = (empty($fuser->entity) ? 1 : $fuser->entity);
			if ($entity > 1) {
				$dir = DOL_DATA_ROOT.'/'.$entity.'/users';
			} else {
				$dir = DOL_DATA_ROOT.'/users';
			}

			if ($dir) {
				//print "Process user id ".$fuser->id."<br>\n";
				$origin = $dir.'/'.$fuser->id;
				$destin = $dir.'/'.$fuser->id.'/photos';

				$origin_osencoded = dol_osencode($origin);

				dol_mkdir($destin);

				//echo '<hr>'.$origin.' -> '.$destin;
				if (dol_is_dir($origin)) {
					$handle = opendir($origin_osencoded);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							if ($file == '.' || $file == '..' || $file == 'photos') {
								continue;
							}
							if (!empty($fuser->photo) && ($file != $fuser->photo && $file != 'thumbs')) {
								continue;
							}

							if (dol_is_dir($origin.'/'.$file)) {	// it is a dir (like 'thumbs')
								$thumbs = opendir($origin_osencoded.'/'.$file);
								if (is_resource($thumbs)) {
									dol_mkdir($destin.'/'.$file);
									while (($thumb = readdir($thumbs)) !== false) {
										if (!dol_is_file($destin.'/'.$file.'/'.$thumb)) {
											if ($thumb == '.' || $thumb == '..') {
												continue;
											}

											//print $origin.'/'.$file.'/'.$thumb.' -> '.$destin.'/'.$file.'/'.$thumb.'<br>'."\n";
											print '.';
											dol_copy($origin.'/'.$file.'/'.$thumb, $destin.'/'.$file.'/'.$thumb, '0', 0);
										}
									}
									// dol_delete_dir($origin.'/'.$file);
								}
							} else { // it is a file
								if (!dol_is_file($destin.'/'.$file)) {
									//print $origin.'/'.$file.' -> '.$destin.'/'.$file.'<br>'."\n";
									print '.';
									dol_copy($origin.'/'.$file, $destin.'/'.$file, '0', 0);
								}
							}
						}
					}
				}
			}
		}
	}

	print '</td></tr>';
}


/* A faire egalement: Modif statut paye et fk_facture des factures payes completement

On recherche facture incorrecte:
select f.rowid, f.total_ttc as t1, sum(pf.amount) as t2 from llx_facture as f, llx_paiement_facture as pf where pf.fk_facture=f.rowid and f.fk_statut in(2,3) and paye=0 and close_code is null group by f.rowid
having  f.total_ttc = sum(pf.amount)

On les corrige:
update llx_facture set paye=1, fk_statut=2 where close_code is null
and rowid in (...)
*/

/**
 * Migrate users fields facebook and co to socialnetworks.
 * Can be called only when version is 10.0.* or lower. Fields does not exists after.
 *
 * @return  void
 */
function migrate_users_socialnetworks()
{
	global $db, $langs;
	// skype,twitter,facebook,linkedin,instagram,snapchat,googleplus,youtube,whatsapp
	$error = 0;
	$db->begin();
	print '<tr><td colspan="4">';
	$sql = 'SELECT rowid, socialnetworks';
	$sql .= ', skype, twitter, facebook, linkedin, instagram, snapchat, googleplus, youtube, whatsapp FROM '.MAIN_DB_PREFIX.'user WHERE';
	$sql .= " skype IS NOT NULL OR skype <> ''";
	$sql .= " OR twitter IS NOT NULL OR twitter <> ''";
	$sql .= " OR facebook IS NOT NULL OR facebook <> ''";
	$sql .= " OR linkedin IS NOT NULL OR linkedin <> ''";
	$sql .= " OR instagram IS NOT NULL OR instagram <> ''";
	$sql .= " OR snapchat IS NOT NULL OR snapchat <> ''";
	$sql .= " OR googleplus IS NOT NULL OR googleplus <> ''";
	$sql .= " OR youtube IS NOT NULL OR youtube <> ''";
	$sql .= " OR whatsapp IS NOT NULL OR whatsapp <> ''";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$arraysocialnetworks = array();
			if (!empty($obj->skype)) {
				$arraysocialnetworks['skype'] = $obj->skype;
			}
			if (!empty($obj->twitter)) {
				$arraysocialnetworks['twitter'] = $obj->twitter;
			}
			if (!empty($obj->facebook)) {
				$arraysocialnetworks['facebook'] = $obj->facebook;
			}
			if (!empty($obj->linkedin)) {
				$arraysocialnetworks['linkedin'] = $obj->linkedin;
			}
			if (!empty($obj->instagram)) {
				$arraysocialnetworks['instagram'] = $obj->instagram;
			}
			if (!empty($obj->snapchat)) {
				$arraysocialnetworks['snapchat'] = $obj->snapchat;
			}
			if (!empty($obj->googleplus)) {
				$arraysocialnetworks['googleplus'] = $obj->googleplus;
			}
			if (!empty($obj->youtube)) {
				$arraysocialnetworks['youtube'] = $obj->youtube;
			}
			if (!empty($obj->whatsapp)) {
				$arraysocialnetworks['whatsapp'] = $obj->whatsapp;
			}
			if ($obj->socialnetworks == '' || is_null($obj->socialnetworks)) {
				$obj->socialnetworks = '[]';
			}
			$socialnetworks = array_merge($arraysocialnetworks, json_decode($obj->socialnetworks, true));
			$sqlupd = 'UPDATE '.MAIN_DB_PREFIX."user SET socialnetworks='".$db->escape(json_encode($socialnetworks))."'";
			$sqlupd .= ', skype=null';
			$sqlupd .= ', twitter=null';
			$sqlupd .= ', facebook=null';
			$sqlupd .= ', linkedin=null';
			$sqlupd .= ', instagram=null';
			$sqlupd .= ', snapchat=null';
			$sqlupd .= ', googleplus=null';
			$sqlupd .= ', youtube=null';
			$sqlupd .= ', whatsapp=null';
			$sqlupd .= ' WHERE rowid = '.((int) $obj->rowid);
			//print $sqlupd."<br>";
			$resqlupd = $db->query($sqlupd);
			if (!$resqlupd) {
				dol_print_error($db);
				$error++;
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		dol_print_error($db);
		$db->rollback();
	}
	print '<b>'.$langs->trans('MigrationFieldsSocialNetworks', 'Users')."</b><br>\n";
	print '</td></tr>';
}

/**
 * Migrate members fields facebook and co to socialnetworks
 * Can be called only when version is 10.0.* or lower. Fields does not exists after.
 *
 * @return  void
 */
function migrate_members_socialnetworks()
{
	global $db, $langs;

	print '<tr><td colspan="4">';
	$error = 0;
	$db->begin();
	print '<tr><td colspan="4">';
	$sql = 'SELECT rowid, socialnetworks';
	$sql .= ', skype, twitter, facebook, linkedin, instagram, snapchat, googleplus, youtube, whatsapp FROM '.MAIN_DB_PREFIX.'adherent WHERE ';
	$sql .= " skype IS NOT NULL OR skype <> ''";
	$sql .= " OR twitter IS NOT NULL OR twitter <> ''";
	$sql .= " OR facebook IS NOT NULL OR facebook <> ''";
	$sql .= " OR linkedin IS NOT NULL OR linkedin <> ''";
	$sql .= " OR instagram IS NOT NULL OR instagram <> ''";
	$sql .= " OR snapchat IS NOT NULL OR snapchat <> ''";
	$sql .= " OR googleplus IS NOT NULL OR googleplus <> ''";
	$sql .= " OR youtube IS NOT NULL OR youtube <> ''";
	$sql .= " OR whatsapp IS NOT NULL OR whatsapp <> ''";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$arraysocialnetworks = array();
			if (!empty($obj->skype)) {
				$arraysocialnetworks['skype'] = $obj->skype;
			}
			if (!empty($obj->twitter)) {
				$arraysocialnetworks['twitter'] = $obj->twitter;
			}
			if (!empty($obj->facebook)) {
				$arraysocialnetworks['facebook'] = $obj->facebook;
			}
			if (!empty($obj->linkedin)) {
				$arraysocialnetworks['linkedin'] = $obj->linkedin;
			}
			if (!empty($obj->instagram)) {
				$arraysocialnetworks['instagram'] = $obj->instagram;
			}
			if (!empty($obj->snapchat)) {
				$arraysocialnetworks['snapchat'] = $obj->snapchat;
			}
			if (!empty($obj->googleplus)) {
				$arraysocialnetworks['googleplus'] = $obj->googleplus;
			}
			if (!empty($obj->youtube)) {
				$arraysocialnetworks['youtube'] = $obj->youtube;
			}
			if (!empty($obj->whatsapp)) {
				$arraysocialnetworks['whatsapp'] = $obj->whatsapp;
			}
			if ($obj->socialnetworks == '' || is_null($obj->socialnetworks)) {
				$obj->socialnetworks = '[]';
			}
			$socialnetworks = array_merge($arraysocialnetworks, json_decode($obj->socialnetworks, true));
			$sqlupd = 'UPDATE '.MAIN_DB_PREFIX."adherent SET socialnetworks='".$db->escape(json_encode($socialnetworks))."'";
			$sqlupd .= ', skype=null';
			$sqlupd .= ', twitter=null';
			$sqlupd .= ', facebook=null';
			$sqlupd .= ', linkedin=null';
			$sqlupd .= ', instagram=null';
			$sqlupd .= ', snapchat=null';
			$sqlupd .= ', googleplus=null';
			$sqlupd .= ', youtube=null';
			$sqlupd .= ', whatsapp=null';
			$sqlupd .= ' WHERE rowid = '.((int) $obj->rowid);
			//print $sqlupd."<br>";
			$resqlupd = $db->query($sqlupd);
			if (!$resqlupd) {
				dol_print_error($db);
				$error++;
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		dol_print_error($db);
		$db->rollback();
	}
	print '<b>'.$langs->trans('MigrationFieldsSocialNetworks', 'Members')."</b><br>\n";
	print '</td></tr>';
}

/**
 * Migrate contacts fields facebook and co to socialnetworks
 * Can be called only when version is 10.0.* or lower. Fields does not exists after.
 *
 * @return  void
 */
function migrate_contacts_socialnetworks()
{
	global $db, $langs;
	// jabberid,skype,twitter,facebook,linkedin,instagram,snapchat,googleplus,youtube,whatsapp
	$error = 0;
	$db->begin();
	print '<tr><td colspan="4">';
	$sql = 'SELECT rowid, socialnetworks';
	$sql .= ', jabberid, skype, twitter, facebook, linkedin, instagram, snapchat, googleplus, youtube, whatsapp FROM '.MAIN_DB_PREFIX.'socpeople WHERE';
	$sql .= " jabberid IS NOT NULL OR jabberid <> ''";
	$sql .= " OR skype IS NOT NULL OR skype <> ''";
	$sql .= " OR twitter IS NOT NULL OR twitter <> ''";
	$sql .= " OR facebook IS NOT NULL OR facebook <> ''";
	$sql .= " OR linkedin IS NOT NULL OR linkedin <> ''";
	$sql .= " OR instagram IS NOT NULL OR instagram <> ''";
	$sql .= " OR snapchat IS NOT NULL OR snapchat <> ''";
	$sql .= " OR googleplus IS NOT NULL OR googleplus <> ''";
	$sql .= " OR youtube IS NOT NULL OR youtube <> ''";
	$sql .= " OR whatsapp IS NOT NULL OR whatsapp <> ''";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$arraysocialnetworks = array();
			if (!empty($obj->jabberid)) {
				$arraysocialnetworks['jabber'] = $obj->jabberid;
			}
			if (!empty($obj->skype)) {
				$arraysocialnetworks['skype'] = $obj->skype;
			}
			if (!empty($obj->twitter)) {
				$arraysocialnetworks['twitter'] = $obj->twitter;
			}
			if (!empty($obj->facebook)) {
				$arraysocialnetworks['facebook'] = $obj->facebook;
			}
			if (!empty($obj->linkedin)) {
				$arraysocialnetworks['linkedin'] = $obj->linkedin;
			}
			if (!empty($obj->instagram)) {
				$arraysocialnetworks['instagram'] = $obj->instagram;
			}
			if (!empty($obj->snapchat)) {
				$arraysocialnetworks['snapchat'] = $obj->snapchat;
			}
			if (!empty($obj->googleplus)) {
				$arraysocialnetworks['googleplus'] = $obj->googleplus;
			}
			if (!empty($obj->youtube)) {
				$arraysocialnetworks['youtube'] = $obj->youtube;
			}
			if (!empty($obj->whatsapp)) {
				$arraysocialnetworks['whatsapp'] = $obj->whatsapp;
			}
			if ($obj->socialnetworks == '' || is_null($obj->socialnetworks)) {
				$obj->socialnetworks = '[]';
			}
			$socialnetworks = array_merge($arraysocialnetworks, json_decode($obj->socialnetworks, true));
			$sqlupd = 'UPDATE '.MAIN_DB_PREFIX."socpeople SET socialnetworks='".$db->escape(json_encode($socialnetworks))."'";
			$sqlupd .= ', jabberid=null';
			$sqlupd .= ', skype=null';
			$sqlupd .= ', twitter=null';
			$sqlupd .= ', facebook=null';
			$sqlupd .= ', linkedin=null';
			$sqlupd .= ', instagram=null';
			$sqlupd .= ', snapchat=null';
			$sqlupd .= ', googleplus=null';
			$sqlupd .= ', youtube=null';
			$sqlupd .= ', whatsapp=null';
			$sqlupd .= ' WHERE rowid = '.((int) $obj->rowid);
			//print $sqlupd."<br>";
			$resqlupd = $db->query($sqlupd);
			if (!$resqlupd) {
				dol_print_error($db);
				$error++;
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		dol_print_error($db);
		$db->rollback();
	}
	print '<b>'.$langs->trans('MigrationFieldsSocialNetworks', 'Contacts')."</b><br>\n";
	print '</td></tr>';
}

/**
 * Migrate thirdparties fields facebook and co to socialnetworks
 * Can be called only when version is 10.0.* or lower. Fields does not exists after.
 *
 * @return  void
 */
function migrate_thirdparties_socialnetworks()
{
	global $db, $langs;
	// skype,twitter,facebook,linkedin,instagram,snapchat,googleplus,youtube,whatsapp
	$error = 0;
	$db->begin();
	print '<tr><td colspan="4">';
	$sql = 'SELECT rowid, socialnetworks';
	$sql .= ', skype, twitter, facebook, linkedin, instagram, snapchat, googleplus, youtube, whatsapp FROM '.MAIN_DB_PREFIX.'societe WHERE ';
	$sql .= " skype IS NOT NULL OR skype <> ''";
	$sql .= " OR twitter IS NOT NULL OR twitter <> ''";
	$sql .= " OR facebook IS NOT NULL OR facebook <> ''";
	$sql .= " OR linkedin IS NOT NULL OR linkedin <> ''";
	$sql .= " OR instagram IS NOT NULL OR instagram <> ''";
	$sql .= " OR snapchat IS NOT NULL OR snapchat <> ''";
	$sql .= " OR googleplus IS NOT NULL OR googleplus <> ''";
	$sql .= " OR youtube IS NOT NULL OR youtube <> ''";
	$sql .= " OR whatsapp IS NOT NULL OR whatsapp <> ''";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$arraysocialnetworks = array();
			if (!empty($obj->skype)) {
				$arraysocialnetworks['skype'] = $obj->skype;
			}
			if (!empty($obj->twitter)) {
				$arraysocialnetworks['twitter'] = $obj->twitter;
			}
			if (!empty($obj->facebook)) {
				$arraysocialnetworks['facebook'] = $obj->facebook;
			}
			if (!empty($obj->linkedin)) {
				$arraysocialnetworks['linkedin'] = $obj->linkedin;
			}
			if (!empty($obj->instagram)) {
				$arraysocialnetworks['instagram'] = $obj->instagram;
			}
			if (!empty($obj->snapchat)) {
				$arraysocialnetworks['snapchat'] = $obj->snapchat;
			}
			if (!empty($obj->googleplus)) {
				$arraysocialnetworks['googleplus'] = $obj->googleplus;
			}
			if (!empty($obj->youtube)) {
				$arraysocialnetworks['youtube'] = $obj->youtube;
			}
			if (!empty($obj->whatsapp)) {
				$arraysocialnetworks['whatsapp'] = $obj->whatsapp;
			}
			if ($obj->socialnetworks == '' || is_null($obj->socialnetworks)) {
				$obj->socialnetworks = '[]';
			}
			$socialnetworks = array_merge($arraysocialnetworks, json_decode($obj->socialnetworks, true));
			$sqlupd = 'UPDATE '.MAIN_DB_PREFIX."societe SET socialnetworks='".$db->escape(json_encode($socialnetworks))."'";
			$sqlupd .= ', skype=null';
			$sqlupd .= ', twitter=null';
			$sqlupd .= ', facebook=null';
			$sqlupd .= ', linkedin=null';
			$sqlupd .= ', instagram=null';
			$sqlupd .= ', snapchat=null';
			$sqlupd .= ', googleplus=null';
			$sqlupd .= ', youtube=null';
			$sqlupd .= ', whatsapp=null';
			$sqlupd .= ' WHERE rowid = '.((int) $obj->rowid);
			//print $sqlupd."<br>";
			$resqlupd = $db->query($sqlupd);
			if (!$resqlupd) {
				dol_print_error($db);
				$error++;
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		dol_print_error($db);
		$db->rollback();
	}
	print '<b>'.$langs->trans('MigrationFieldsSocialNetworks', 'Thirdparties')."</b><br>\n";
	print '</td></tr>';
}


/**
 * Migrate export and import profiles to fix field name that was renamed
 *
 * @param	string		$mode		'export' or 'import'
 * @return  void
 */
function migrate_export_import_profiles($mode = 'export')
{
	global $db, $langs;

	$error = 0;
	$resultstring = '';

	$db->begin();

	print '<tr class="trforrunsql"><td colspan="4">';
	$sql = 'SELECT rowid, field';
	if ($mode == 'export') {
		$sql .= ', filter';
	}
	$sql .= ' FROM '.MAIN_DB_PREFIX.$mode.'_model WHERE';
	$sql .= " type LIKE 'propale_%' OR type LIKE 'commande_%' OR type LIKE 'facture_%'";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$oldfield = $obj->field;
			$newfield = str_replace(array(',f.facnumber', 'f.facnumber,', 'f.total,', 'f.tva,'), array(',f.ref', 'f.ref,', 'f.total_ht,', 'f.total_tva,'), $oldfield);

			if ($mode == 'export') {
				$oldfilter = $obj->filter;
				$newfilter = str_replace(array('f.facnumber=', 'f.total=', 'f.tva='), array('f.ref=', 'f.total_ht=', 'f.total_tva='), $oldfilter);
			} else {
				$oldfilter = '';
				$newfilter = '';
			}

			if ($oldfield != $newfield || $oldfilter != $newfilter) {
				$sqlupd = 'UPDATE '.MAIN_DB_PREFIX.$mode."_model SET field = '".$db->escape($newfield)."'";
				if ($mode == 'export') {
					$sqlupd .= ", filter = '".$db->escape($newfilter)."'";
				}
				$sqlupd .= ' WHERE rowid = '.((int) $obj->rowid);
				$resultstring .= '<tr class="trforrunsql" style=""><td class="wordbreak" colspan="4">'.$sqlupd."</td></tr>\n";
				$resqlupd = $db->query($sqlupd);
				if (!$resqlupd) {
					dol_print_error($db);
					$error++;
				}
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		dol_print_error($db);
		$db->rollback();
	}
	print '<b>'.$langs->trans('MigrationImportOrExportProfiles', $mode)."</b><br>\n";
	print '</td></tr>';

	if ($resultstring) {
		print $resultstring;
	} else {
		print '<tr class="trforrunsql" style=""><td class="wordbreak" colspan="4">'.$langs->trans("NothingToDo")."</td></tr>\n";
	}
}

/**
 * Migrate Rank into contract  line
 *
 * @return  void
 */
function migrate_contractdet_rank()
{
	global $db, $langs;

	$error = 0;
	$resultstring = '';

	$db->begin();
	print '<tr class="trforrunsql"><td colspan="4">';
	print '<b>'.$langs->trans('MigrationContractLineRank')."</b><br>\n";

	$sql = "SELECT c.rowid as cid ,cd.rowid as cdid,cd.rang FROM ".$db->prefix()."contratdet as cd INNER JOIN ".$db->prefix()."contrat as c ON c.rowid=cd.fk_contrat AND cd.rang=0";
	$sql .= " ORDER BY c.rowid,cd.rowid";

	$resql = $db->query($sql);
	if ($resql) {
		$currentRank = 0;
		$current_contract = 0;
		while ($obj = $db->fetch_object($resql)) {
			if (empty($current_contract) || $current_contract == $obj->cid) {
				$currentRank++;
			} else {
				$currentRank = 1;
			}

			$sqlUpd = "UPDATE ".$db->prefix()."contratdet SET rang=".(int) $currentRank." WHERE rowid=".(int) $obj->cdid;
			$resultstring = '.';
			print $resultstring;
			$resqlUpd = $db->query($sqlUpd);
			if (!$resqlUpd) {
				dol_print_error($db);
				$error++;
			}

			$current_contract =  $obj->cid;
		}
	} else {
		$error++;
	}
	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}

	print '</td></tr>';

	if (!$resultstring) {
		print '<tr class="trforrunsql" style=""><td class="wordbreak" colspan="4">'.$langs->trans("NothingToDo")."</td></tr>\n";
	}
}

/**
 * Invoice exports been shifted (facture_1 => facture_0, facture_2 => facture_1) in version 20, shift export models accordingly
 *
 * @return  void
 */
function migrate_invoice_export_models()
{
	global $db, $langs;

	$lock = getDolGlobalInt('MIGRATION_FLAG_INVOICE_MODELS_V20');

	$firstInstallVersion = getDolGlobalString('MAIN_VERSION_FIRST_INSTALL', DOL_VERSION);
	$migrationNeeded = (versioncompare(explode('.', $firstInstallVersion, 3), array(20, 0, -5)) < 0 && !$lock);

	print '<tr class="trforrunsql"><td colspan="4">';
	print '<b>'.$langs->trans('InvoiceExportModelsMigration')."</b>: \n";

	if (! $migrationNeeded) {
		print $langs->trans("AlreadyDone");
		print '</td></tr>';
		dolibarr_set_const($db, 'MIGRATION_FLAG_INVOICE_MODELS_V20', 1, 'chaine', 0, 'To flag the upgrade of invoice template has been set', 0);
		return;
	}


	$db->begin();

	$sql1 = "UPDATE ".$db->prefix()."export_model SET type = 'facture_0' WHERE type = 'facture_1'";

	$resql1 = $db->query($sql1);

	if (! $resql1) {
		dol_print_error($db);
		$db->rollback();
		print '</td></tr>';
		return;
	}

	$modified1 = $db->affected_rows($resql1);

	print str_repeat('.', $modified1);

	$db->free($resql1);

	$sql2 = "UPDATE ".$db->prefix()."export_model SET type = 'facture_1' WHERE type = 'facture_2'";

	$resql2 = $db->query($sql2);

	if (! $resql2) {
		dol_print_error($db);
		$db->rollback();
		print '</td></tr>';
		return;
	}

	$modified2 = $db->affected_rows($resql2);

	print str_repeat('.', $modified2);

	$db->free($resql2);

	if (empty($modified1 + $modified2)) {
		print $langs->trans('NothingToDo');
	}

	$db->commit();

	dolibarr_set_const($db, 'MIGRATION_FLAG_INVOICE_MODELS_V20', 1, 'chaine', 0, 'To flag the upgrade of invoice template has been set', 0);

	echo '</td></tr>';
}

/**
 * Migrate Ref in bookkeeping lines
 *
 * @param int $entity Entity id
 * @return  void
 */
function migrate_accountingbookkeeping(int $entity)
{
	global $db, $langs;

	$error = 0;
	$resultstring = '';
	$bookKeepingAddon = '';

	// For the moment we set the numbering rule to neon (the rule argon has a lot of critical bugs to fix first).
	if (getDolGlobalString('BOOKKEEPING_ADDON') == '') {
		dolibarr_set_const($db, 'BOOKKEEPING_ADDON', 'mod_bookkeeping_neon', 'chaine', 0, '', $entity);
		$bookKeepingAddon = 'mod_bookkeeping_neon';
	}

	print '<tr class="trforrunsql"><td colspan="4">';
	print '<b>'.$langs->trans('MigrationAccountancyBookkeeping')."</b><br>\n";

	// TODO
	if ($bookKeepingAddon === 'mod_bookkeeping_argon') {
		$db->begin();

		$sql = "SELECT DISTINCT YEAR(doc_date) as doc_year, MONTH(doc_date) as doc_month, code_journal, piece_num FROM ".$db->prefix()."accounting_bookkeeping";
		$sql .= " WHERE ref IS NULL AND entity = ".((int) $entity);
		$sql .= " ORDER BY doc_year, doc_month, code_journal, piece_num";

		$resql = $db->query($sql);

		require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
		$bookkeeping = new BookKeeping($db);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$bookkeeping->doc_date = dol_mktime(0, 0, 0, $obj->doc_month, 1, $obj->doc_year);
				$bookkeeping->code_journal = $obj->code_journal;
				$ref = $bookkeeping->getNextNumRef();

				$sqlUpd = "UPDATE ".$db->prefix()."accounting_bookkeeping SET ref = '".$db->escape($ref)."' WHERE piece_num = '".$db->escape($obj->piece_num)."' AND entity = ".((int) $entity);
				$resultstring = '.';
				print $resultstring;
				$resqlUpd = $db->query($sqlUpd);
				if (!$resqlUpd) {
					dol_print_error($db);
					$error++;
				}
			}
		} else {
			$error++;
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	print '</td></tr>';

	if (!$resultstring) {
		print '<tr class="trforrunsql" style=""><td class="wordbreak" colspan="4">'.$langs->trans("NothingToDo")."</td></tr>\n";
	}
}
