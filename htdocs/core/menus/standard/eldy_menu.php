<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 */

/**
 *	\file       htdocs/core/menus/standard/eldy_menu.php
 *	\brief      Menu eldy manager
 */


/**
 *	Class to manage menu Eldy
 *
 *	@phan-suppress PhanRedefineClass
 */
class MenuManager
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int<0,1>	0 for internal users, 1 for external users
	 */
	public $type_user = 0;

	/**
	 * @var string 		To save the default target to use onto links
	 */
	public $atarget = "";

	/**
	 * @var string 		Menu name
	 */
	public $name = "eldy";

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>
	 */
	public $menu_array;
	/**
	 * @var array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>
	 */
	public $menu_array_after;

	/**
	 * @var array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>
	 */
	public $tabMenu;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     	Database handler
	 *  @param	int<0,1>	$type_user		Type of user
	 */
	public function __construct($db, $type_user)
	{
		$this->type_user = $type_user;
		$this->db = $db;
	}


	/**
	 * Load this->tabMenu
	 *
	 * @param	string	$forcemainmenu		To force mainmenu to load
	 * @param	string	$forceleftmenu		To force leftmenu to load
	 * @return	void
	 */
	public function loadMenu($forcemainmenu = '', $forceleftmenu = '')
	{
		// We save into session the main menu selected
		if (GETPOSTISSET("mainmenu")) {
			$_SESSION["mainmenu"] = GETPOST("mainmenu", 'aZ09');
		}
		if (GETPOSTISSET("idmenu")) {
			$_SESSION["idmenu"] = GETPOSTINT("idmenu");
		}

		// Read now mainmenu and leftmenu that define which menu to show
		if (GETPOSTISSET("mainmenu")) {
			// On sauve en session le menu principal choisi
			$mainmenu = GETPOST("mainmenu", 'aZ09');
			$_SESSION["mainmenu"] = $mainmenu;
			$_SESSION["leftmenuopened"] = "";
		} else {
			// Look for the menu in the session if not set by the link
			$mainmenu = isset($_SESSION["mainmenu"]) ? $_SESSION["mainmenu"] : '';
		}
		if (!empty($forcemainmenu)) {
			$mainmenu = $forcemainmenu;
		}

		if (GETPOSTISSET("leftmenu")) {
			// On sauve en session le menu principal choisi
			$leftmenu = GETPOST("leftmenu", 'aZ09');
			$_SESSION["leftmenu"] = $leftmenu;

			if ($_SESSION["leftmenuopened"] == $leftmenu) {	// To collapse
				//$leftmenu="";
				$_SESSION["leftmenuopened"] = "";
			} else {
				$_SESSION["leftmenuopened"] = $leftmenu;
			}
		} else {
			// Look for the menu in the session if not set by the link
			$leftmenu = isset($_SESSION["leftmenu"]) ? $_SESSION["leftmenu"] : '';
		}
		if (!empty($forceleftmenu)) {
			$leftmenu = $forceleftmenu;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
		$tabMenu = array();
		$menuArbo = new Menubase($this->db, 'eldy');
		$menuArbo->menuLoad($mainmenu, $leftmenu, $this->type_user, 'eldy', $tabMenu);
		$this->tabMenu = $tabMenu;
	}


	/**
	 *  Output menu on screen.
	 *  Menu defined in sql tables were stored into $this->tabMenu BEFORE this is called.
	 *
	 *	@param	'top'|'topnb'|'left'|'leftdropdown'|'jmobile'	$mode			'top', 'topnb', 'left', 'leftdropdown', 'jmobile' (used to get full xml ul/li menu)
	 *  @param	?array<string,string>	$moredata	An array with more data to output
	 *  @return int<0,max>				0 or nb of top menu entries if $mode = 'topnb'
	 */
	public function showmenu($mode, $moredata = null)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/eldy.lib.php';

		if ($this->type_user == 1) {
			$conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED = 1;
			$conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED = 1;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
		$this->menu = new Menu();

		if (!getDolGlobalString('MAIN_MENU_INVERT')) {
			if ($mode == 'top') {
				print_eldy_menu($this->db, $this->atarget, $this->type_user, $this->tabMenu, $this->menu, 0, $mode);
			}
			if ($mode == 'left') {
				print_left_eldy_menu($this->db, $this->menu_array, $this->menu_array_after, $this->tabMenu, $this->menu, 0, '', '', $moredata, $this->type_user);
			}
			if ($mode == 'leftdropdown') {
				//$leftmenudropdown = print_left_eldy_menu($this->db, $this->menu_array, $this->menu_array_after, $this->tabMenu, $this->menu, 0, '', '', $moredata, $this->type_user);
				$leftmenudropdown = print_left_eldy_menu($this->db, $this->menu_array, $this->menu_array_after, $this->tabMenu, $this->menu, 1, '', '', $moredata, $this->type_user);
			}
		} else {
			$conf->global->MAIN_SHOW_LOGO = 0;
			if ($mode == 'top') {
				print_left_eldy_menu($this->db, $this->menu_array, $this->menu_array_after, $this->tabMenu, $this->menu, 0, '', '', $moredata, $this->type_user);
			}
			if ($mode == 'left') {
				print_eldy_menu($this->db, $this->atarget, $this->type_user, $this->tabMenu, $this->menu, 0, $mode);
			}
		}
		if ($mode == 'topnb') {
			print_eldy_menu($this->db, $this->atarget, $this->type_user, $this->tabMenu, $this->menu, 1, $mode); // no output
			return $this->menu->getNbOfVisibleMenuEntries();
		}

		if ($mode == 'jmobile') {     // Used to get menu in xml ul/li
			print_eldy_menu($this->db, $this->atarget, $this->type_user, $this->tabMenu, $this->menu, 1, $mode); // Fill this->menu that is empty with top menu

			// $this->menu->liste is top menu
			//var_dump($this->menu->liste);exit;
			$lastlevel = array();
			print '<!-- Generate menu list from menu handler '.$this->name.' -->'."\n";
			foreach ($this->menu->liste as $key => $val) {		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
				print '<ul class="ulmenu" data-inset="true">';
				print '<li class="lilevel0">';

				if ($val['enabled'] == 1) {
					$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
					$substitarray['__USERID__'] = $user->id; // For backward compatibility
					$val['url'] = make_substitutions($val['url'], $substitarray);

					if (!preg_match('/^http/', $val['url'])) {
						$relurl = dol_buildpath($val['url'], 1);
					} else {
						$relurl = $val['url'];
					}

					$canonurl = preg_replace('/\?.*$/', '', $val['url']);

					print '<a class="alilevel0" href="#">';

					// Add font-awesome
					if ($val['level'] == 0 && !empty($val['prefix'])) {
						$reg = array();
						if (preg_match('/^(fa[rsb]? )?fa-/', $val['prefix'], $reg)) {
							print '<span class="'.(empty($reg[1]) ? 'fa ' : '').$val['prefix'].' paddingright pictofixedwidth"></span>';
						} else {
							print str_replace('<span class="', '<span class="paddingright pictofixedwidth ', $val['prefix']);
						}
					}
					print $val['titre'];
					print '</a>'."\n";

					// Search submenu for this mainmenu entry
					$tmpmainmenu = $val['mainmenu'];
					$tmpleftmenu = 'all';
					$submenu = new Menu();
					print_left_eldy_menu($this->db, $this->menu_array, $this->menu_array_after, $this->tabMenu, $submenu, 1, $tmpmainmenu, $tmpleftmenu, null, $this->type_user); // Fill $submenu (example with tmpmainmenu='home' tmpleftmenu='all', return left menu tree of Home)
					// Note: $submenu contains menu entry with substitution not yet done
					//if ($tmpmainmenu.'-'.$tmpleftmenu == 'home-all') {
					//var_dump($submenu); exit;
					//}
					//if ($tmpmainmenu=='accountancy') {
					//var_dump($submenu->liste); exit;
					//}
					$nexturl = dol_buildpath(empty($submenu->liste[0]['url']) ? '' : $submenu->liste[0]['url'], 1);

					$canonrelurl = preg_replace('/\?.*$/', '', $relurl);
					$canonnexturl = preg_replace('/\?.*$/', '', $nexturl);
					//var_dump($canonrelurl);
					//var_dump($canonnexturl);

					print '<ul>'."\n";
					if (($canonrelurl != $canonnexturl && !in_array($val['mainmenu'], array('tools')))
						|| (strpos($canonrelurl, '/product/index.php') !== false || strpos($canonrelurl, '/compta/bank/list.php') !== false)) {
						// We add sub entry
						print str_pad('', 1).'<li class="lilevel1 ui-btn-icon-right ui-btn">'; // ui-btn to highlight on clic
						print '<a href="'.$relurl.'">';

						if ($val['level'] == 0) {
							print '<span class="fas fa-home fa-fw paddingright pictofixedwidth" aria-hidden="true"></span>';
						}

						if ($langs->trans(ucfirst($val['mainmenu'])."Dashboard") == ucfirst($val['mainmenu'])."Dashboard") {  // No translation
							if (in_array($val['mainmenu'], array('cashdesk', 'externalsite', 'website', 'collab', 'takepos'))) {
								print $langs->trans("Access");
							} else {
								print $langs->trans("Dashboard");
							}
						} else {
							print $langs->trans(ucfirst($val['mainmenu'])."Dashboard");
						}
						print '</a>';
						print '</li>'."\n";
					}

					/*
					if ($val['level'] == 0) {
						if ($val['enabled']) {
							$lastlevel[0] = 'enabled';
						} elseif ($showmenu) {
							// Not enabled but visible (so greyed)
							$lastlevel[0] = 'greyed';
						} else {
							$lastlevel[0] = 'hidden';
						}
					}
					*/

					$lastlevel2 = array();
					'@phan-var-force array<string> $lastlevel2';
					foreach ($submenu->liste as $key2 => $val2) {		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu','prefix']
						$showmenu = true;
						if (getDolGlobalString('MAIN_MENU_HIDE_UNAUTHORIZED') && empty($val2['enabled'])) {
							$showmenu = false;
						}

						// If at least one parent is not enabled, we do not show any menu of all children
						if ($val2['level'] > 0) {
							$levelcursor = $val2['level'] - 1;
							while ($levelcursor >= 0) {
								if ($lastlevel2[$levelcursor] != 'enabled') {
									$showmenu = false;
								}
								$levelcursor--;
							}
						}

						if ($showmenu) {		// Visible (option to hide when not allowed is off or allowed)
							$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
							$substitarray['__USERID__'] = $user->id; // For backward compatibility
							$val2['url'] = make_substitutions($val2['url'], $substitarray); // Make also substitution of __(XXX)__ and __[XXX]__

							if (!preg_match("/^(http:\/\/|https:\/\/)/i", $val2['url'])) {
								$relurl2 = dol_buildpath($val2['url'], 1);
							} else {
								$relurl2 = $val2['url'];
							}
							$canonurl2 = preg_replace('/\?.*$/', '', $val2['url']);
							//var_dump($val2['url'].' - '.$canonurl2.' - '.$val2['level']);
							if (in_array($canonurl2, array('/admin/index.php', '/admin/tools/index.php', '/core/tools.php'))) {
								$relurl2 = '';
							}

							$disabled = '';
							if (!$val2['enabled']) {
								$disabled = " vsmenudisabled";
							}

							// @phan-suppress-next-line PhanParamSuspiciousOrder
							print str_pad('', ($val2['level'] + 1));
							print '<li class="lilevel'.($val2['level'] + 1);
							if ($val2['level'] == 0) {
								print ' ui-btn-icon-right ui-btn'; // ui-btn to highlight on clic
							}
							print $disabled.'">'; // ui-btn to highlight on clic
							if ($relurl2) {
								if ($val2['enabled']) {
									// Allowed
									print '<a href="'.$relurl2.'">';
									$lastlevel2[$val2['level']] = 'enabled';
								} else {
									// Not allowed but visible (greyed)
									print '<a href="#" class="vsmenudisabled">';
									$lastlevel2[$val2['level']] = 'greyed';
								}
							} else {
								if ($val2['enabled']) {	// Allowed
									$lastlevel2[$val2['level']] = 'enabled';
								} else {
									$lastlevel2[$val2['level']] = 'greyed';
								}
							}

							// Add font-awesome for level 0 and 1 (if $val2['level'] == 1, we are on level2, if $val2['level'] == 2, we are on level 3...)
							if ($val2['level'] == 0 && !empty($val2['prefix'])) {
								print $val2['prefix'];	// the picto must have class="pictofixedwidth paddingright"
							} else {
								print '<i class="fa fa-does-not-exists fa-fw paddingright pictofixedwidth level'.($val2['level']+1).'"></i>';
							}

							print $val2['titre'];
							if ($relurl2) {
								print '</a>';
							}
							print '</li>'."\n";
						}
					}
					//var_dump($submenu);
					print '</ul>';
				}
				if ($val['enabled'] == 2) {
					print '<span class="spanlilevel0 vsmenudisabled">';

					// Add font-awesome
					if ($val['level'] == 0 && !empty($val['prefix'])) {
						print $val['prefix'];
					}

					print $val['titre'];
					print '</span>';
				}
				print '</li>';
				print '</ul>'."\n";
			}
		}

		unset($this->menu);

		//print 'xx'.$mode;
		return 0;
	}
}
