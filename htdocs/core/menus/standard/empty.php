<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/core/menus/standard/empty.php
 *		\brief      This is an example of an empty top menu handler
 */

/**
 *	    Class to manage menu Empty
 *
 *	    @phan-suppress PhanRedefineClass
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
	 * @var string		Menu name
	 */
	public $name = "empty";

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>
	 */
	public $menu_array_after;

	/**
	 * @var array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>
	 */
	public $tabMenu;

	/**
	 * @var Menu
	 */
	public $topmenu;
	/**
	 * @var Menu
	 */
	public $leftmenu;

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     		Database handler
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
		// Do nothing
		$this->tabMenu = array();
	}


	/**
	 *  Output menu on screen
	 *
	 *	@param	string					$mode		'top', 'left', 'jmobile'
	 *  @param	?array<string,mixed>	$moredata	An array with more data to output
	 *  @return int<0,max>|string					0 or nb of top menu entries if $mode = 'topnb', string inc ase of bad parameter
	 */
	public function showmenu($mode, $moredata = null)
	{
		global $langs, $user, $dolibarr_main_db_name;

		$id = 'mainmenu';

		require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';
		$this->menu = new Menu();

		$noout = 0;
		//if ($mode == 'jmobile') $noout=1;

		if ($mode == 'topnb') {
			return 1;
		}

		if ($mode == 'top') {
			if (empty($noout)) {
				print_start_menu_array_empty();
			}

			$usemenuhider = 1;

			// Show/Hide vertical menu
			if ($mode != 'jmobile' && $mode != 'topnb' && $usemenuhider && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$showmode = 1;
				$classname = 'class="tmenu menuhider nohover"';
				$idsel = 'menu';

				$this->menu->add('#', '', 0, $showmode, $this->atarget, "xxx", '', 0, $id, $idsel, $classname);
			}

			// Home
			$showmode = 1;
			$classname = 'class="tmenusel"';
			$idsel = 'home';

			$this->menu->add('/index.php', $langs->trans("Home"), 0, $showmode, $this->atarget, 'home', '', 10, $id, $idsel, $classname);


			// Sort on position
			$this->menu->liste = dol_sort_array($this->menu->liste, 'position');

			// Output menu entries
			if (empty($noout)) {
				foreach ($this->menu->liste as $menkey => $menuval) {
					print_start_menu_entry_empty($menuval['idsel'], $menuval['classname'], $menuval['enabled']);

					print_text_menu_entry_empty($menuval['titre'], $menuval['enabled'], ($menuval['url'] != '#' ? DOL_URL_ROOT : '').$menuval['url'], $menuval['id'], $menuval['idsel'], $menuval['classname'], ($menuval['target'] ? $menuval['target'] : $this->atarget));

					print_end_menu_entry_empty($menuval['enabled']);
				}

				if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
					print_start_menu_entry_empty('', 'class="tmenuend"', $showmode);
				}

				print_end_menu_entry_empty($showmode);

				print_end_menu_array_empty();
			}
		}

		if ($mode == 'jmobile') {     // Used to get menu in xml ul/li
			// Home
			$showmode = 1;
			$classname = 'class="tmenusel"';
			$idsel = 'home';

			$this->menu->add('/index.php', $langs->trans("Home"), 0, $showmode, $this->atarget, 'home', '', 10, $id, $idsel, $classname);

			$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

			// $this->menu->liste is top menu
			//var_dump($this->menu->liste);exit;
			$lastlevel = array();
			$showmenu = true;  // Is current menu shown - define here to keep static code checker happy
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
					if ($val['level'] == 0 && $val['mainmenu'] == 'home') {
						print '<span class="fas fa-home fa-fw paddingright" aria-hidden="true"></span>';
					}

					print $val['titre'];
					print '</a>'."\n";

					// Search submenu for this mainmenu entry
					$tmpmainmenu = $val['mainmenu'];
					$tmpleftmenu = 'all';
					$submenu = new Menu();

					$langs->load("admin"); // Load translation file admin.lang
					$submenu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"), 0);
					$submenu->add("/admin/company.php", $langs->trans("MenuCompanySetup"), 1);
					$submenu->add("/admin/modules.php", $langs->trans("Modules"), 1);
					$submenu->add("/admin/menus.php", $langs->trans("Menus"), 1);
					$submenu->add("/admin/ihm.php", $langs->trans("GUISetup"), 1);
					$submenu->add("/admin/translation.php?mainmenu=home", $langs->trans("Translation"), 1);
					$submenu->add("/admin/defaultvalues.php?mainmenu=home", $langs->trans("DefaultValues"), 1);

					$submenu->add("/admin/boxes.php?mainmenu=home", $langs->trans("Boxes"), 1);
					$submenu->add("/admin/delais.php?mainmenu=home", $langs->trans("Alerts"), 1);
					$submenu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"), 1);
					$submenu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"), 1);
					$submenu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"), 1);
					$submenu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"), 1);
					$submenu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"), 1);
					$submenu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionarySetup"), 1);
					$submenu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"), 1);

					//if ($tmpmainmenu.'-'.$tmpleftmenu == 'home-all') {
					//var_dump($submenu); exit;
					//}
					//if ($tmpmainmenu=='accountancy') {
					//var_dump($submenu->liste); exit;
					//}
					$nexturl = dol_buildpath($submenu->liste[0]['url'], 1);

					$canonrelurl = preg_replace('/\?.*$/', '', $relurl);
					$canonnexturl = preg_replace('/\?.*$/', '', $nexturl);

					print '<ul>'."\n";
					if (($canonrelurl != $canonnexturl && !in_array($val['mainmenu'], array('tools')))
						|| (strpos($canonrelurl, '/product/index.php') !== false || strpos($canonrelurl, '/compta/bank/list.php') !== false)) {
						// We add sub entry
						print str_pad('', 1).'<li class="lilevel1 ui-btn-icon-right ui-btn">'; // ui-btn to highlight on clic
						print '<a href="'.$relurl.'">';
						if ($langs->trans(ucfirst($val['mainmenu'])."Dashboard") == ucfirst($val['mainmenu'])."Dashboard") {  // No translation
							if (in_array($val['mainmenu'], array('cashdesk', 'websites'))) {
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

					if ($val['level'] == 0) {
						if ($val['enabled']) {
							$lastlevel[0] = 'enabled';
						} elseif ($showmenu) {                 // Not enabled but visible (so greyed)
							$lastlevel[0] = 'greyed';
						} else {
							$lastlevel[0] = 'hidden';
						}
					}

					$lastlevel2 = array();
					foreach ($submenu->liste as $key2 => $val2) {		// $val['url','titre','level','enabled'=0|1|2,'target','mainmenu','leftmenu'
						$showmenu = true;
						if (getDolGlobalString('MAIN_MENU_HIDE_UNAUTHORIZED') && empty($val2['enabled'])) {
							$showmenu = false;
						}

						// If at least one parent is not enabled, we do not show any menu of all children
						if ($val2['level'] > 0) {
							$levelcursor = $val2['level'] - 1;
							while ($levelcursor >= 0) {
								// @phan-suppress-next-line PhanTypeInvalidDimOffset
								if ($lastlevel2[$levelcursor] != 'enabled') {
									$showmenu = false;
								}
								$levelcursor--;
							}
						}

						if ($showmenu) {		// Visible (option to hide when not allowed is off or allowed)
							$val2['url'] = make_substitutions($val2['url'], $substitarray);

							$relurl2 = dol_buildpath($val2['url'], 1);
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
							print str_pad('', $val2['level'] + 1);
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
								print '<i class="fa fa-does-not-exists fa-fw paddingright pictofixedwidth"></i>';
							}

							if ($relurl2) {
								print '</a>';
							}
							print '</li>'."\n";
						}
					}
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

		if ($mode == 'left') {
			// Put here left menu entries
			// ***** START *****

			$langs->load("admin"); // Load translation file admin.lang
			$this->menu->add("/admin/index.php?leftmenu=setup", $langs->trans("Setup"), 0);
			$this->menu->add("/admin/company.php", $langs->trans("MenuCompanySetup"), 1);
			$this->menu->add("/admin/modules.php", $langs->trans("Modules"), 1);
			$this->menu->add("/admin/menus.php", $langs->trans("Menus"), 1);
			$this->menu->add("/admin/ihm.php", $langs->trans("GUISetup"), 1);
			$this->menu->add("/admin/translation.php?mainmenu=home", $langs->trans("Translation"), 1);
			$this->menu->add("/admin/defaultvalues.php?mainmenu=home", $langs->trans("DefaultValues"), 1);

			$this->menu->add("/admin/boxes.php?mainmenu=home", $langs->trans("Boxes"), 1);
			$this->menu->add("/admin/delais.php?mainmenu=home", $langs->trans("Alerts"), 1);
			$this->menu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"), 1);
			$this->menu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"), 1);
			$this->menu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"), 1);
			$this->menu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"), 1);
			$this->menu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"), 1);
			$this->menu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionarySetup"), 1);
			$this->menu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"), 1);

			// ***** END *****

			$menu_array_before = array();
			$menu_array_after = array();

			// do not change code after this

			$menu_array = $this->menu->liste;
			if (is_array($menu_array_before)) {
				$menu_array = array_merge($menu_array_before, $menu_array);
			}
			if (is_array($menu_array_after)) {
				$menu_array = array_merge($menu_array, $menu_array_after);
			}
			//var_dump($menu_array);exit;
			if (!is_array($menu_array)) {
				return 0;
			}

			'@phan-var-force array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level?:int,prefix:string}> $menu_array';

			if (empty($noout)) {
				$alt = 0;
				$altok = 0;
				$blockvmenuopened = false;
				$num = count($menu_array);
				foreach (array_keys($menu_array) as $i) {
					$alt++;
					if (empty($menu_array[$i]['level'])) {
						$altok++;
						$blockvmenuopened = true;
						$lastopened = true;
						for ($j = ($i + 1); $j < $num; $j++) {
							if (empty($menu_array[$j]['level'])) {
								$lastopened = false;
							}
						}
						$alt = 0; // For menu manager "empty", we force to not have blockvmenufirst defined
						$lastopened = 1; // For menu manager "empty", we force to not have blockvmenulast defined
						if (($alt % 2 == 0)) {
							print '<div class="blockvmenub lockvmenuimpair blockvmenuunique'.($lastopened ? ' blockvmenulast' : '').($alt == 1 ? ' blockvmenufirst' : '').'">'."\n";
						} else {
							print '<div class="blockvmenu blockvmenupair blockvmenuunique'.($lastopened ? ' blockvmenulast' : '').($alt == 1 ? ' blockvmenufirst' : '').'">'."\n";
						}
					}

					// Add tabulation
					$tabstring = '';
					$tabul = ($menu_array[$i]['level'] - 1);
					if ($tabul > 0) {
						for ($j = 0; $j < $tabul; $j++) {
							$tabstring .= '&nbsp; &nbsp;';
						}
					}

					if ($menu_array[$i]['level'] == 0) {
						if ($menu_array[$i]['enabled']) {
							print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.dol_buildpath($menu_array[$i]['url'], 1).'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>'.$menu_array[$i]['titre'].'</a></div>'."\n";
						} else {
							print '<div class="menu_titre">'.$tabstring.'<span class="vmenudisabled">'.$menu_array[$i]['titre'].'</span></div>'."\n";
						}
						print '<div class="menu_top"></div>'."\n";
					}

					if ($menu_array[$i]['level'] > 0) {
						$cssmenu = '';
						if ($menu_array[$i]['url']) {
							$cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/', '', $menu_array[$i]['url']));
						}

						print '<div class="menu_contenu'.$cssmenu.'">';

						if ($menu_array[$i]['enabled']) {
							print $tabstring;
							if ($menu_array[$i]['url']) {
								print '<a class="vsmenu"  itle="'.dol_escape_htmltag($menu_array[$i]['titre']).'" href="'.dol_buildpath($menu_array[$i]['url'], 1).'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
							} else {
								print '<span class="vsmenu" title="'.dol_escape_htmltag($menu_array[$i]['titre']).'">';
							}
							if ($menu_array[$i]['url']) {
								print $menu_array[$i]['titre'].'</a>';
							} else {
								print '</span>';
							}
						} else {
							print $tabstring.'<span class="vsmenudisabled vsmenudisabledmargin">'.$menu_array[$i]['titre'].'</span>';
						}

						// If title is not pure text and contains a table, no carriage return added
						if (!strstr($menu_array[$i]['titre'], '<table')) {
							print '<br>';
						}
						print '</div>'."\n";
					}

					// If next is a new block or end
					if (empty($menu_array[$i + 1]['level'])) {
						print '<div class="menu_end"></div>'."\n";
						print "</div>\n";
					}
				}

				if ($altok) {
					print '<div class="blockvmenuend"></div>';
				}
			}

			if ($mode == 'jmobile') {
				$this->leftmenu = clone $this->menu;
				unset($menu_array);
			}
		}

		unset($this->menu);

		return 0;
	}
}


/**
 * Output menu entry
 *
 * @return	void
 */
function print_start_menu_array_empty()
{
	print '<div class="tmenudiv">';
	print '<ul role="navigation" class="tmenu"'.(getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' title="Top menu"' : '').'>';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry_empty($idsel, $classname, $showmode)
{
	if ($showmode) {
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
		//print '<div class="tmenuleft tmenusep"></div>';
		print '<div class="tmenucenter">';
	}
}

/**
 * Output menu entry
 *
 * @param	string	$text			Text
 * @param	int		$showmode		1 or 2
 * @param	string	$url			Url
 * @param	string	$id				Id
 * @param	string	$idsel			Id sel
 * @param	string	$classname		Class name
 * @param	string	$atarget		Target
 * @param	array{}|array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level?:int,prefix:string} 	$menuval		All the $menuval array
 * @return	void
 */
function print_text_menu_entry_empty($text, $showmode, $url, $id, $idsel, $classname, $atarget, $menuval = array())
{
	global $langs;

	$classnameimg = str_replace('class="', 'class="tmenuimage ', $classname);
	$classnametxt = str_replace('class="', 'class="tmenulabel ', $classname);

	if ($showmode == 1) {
		$menuval['prefix'] = 'fa-home fa-fw';

		print '<a '.$classnameimg.' tabindex="-1" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage">';
		$reg = array();
		if (!empty($menuval['prefix']) && strpos($menuval['prefix'], '<span') === 0) {
			print $menuval['prefix'];
		} elseif (!empty($menuval['prefix']) && preg_match('/^(fa[rsb]? )?fa-/', $menuval['prefix'], $reg)) {
			print '<span class="'.$id.' '.(empty($reg[1]) ? 'fa ' : '').$menuval['prefix'].'" id="mainmenuspan_'.$idsel.'"></span>';
		} else {
			print '<span class="'.$id.' tmenuimageforpngaaaaa" id="mainmenuspan_'.$idsel.'"></span>';
		}
		print '</div>';
		print '</a>';
		if (!getDolGlobalString('THEME_TOPMENU_DISABLE_TEXT')) {
			print '<a '.$classnametxt.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').'>';
			print '<span class="mainmenuaspan">';
			print $text;
			print '</span>';
			print '</a>';
		}
	}
	if ($showmode == 2) {
		print '<div '.$classnameimg.' title="'.dol_escape_htmltag($text.' - '.$langs->trans("NotAllowed")).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage tmenudisabled">';
		print '<span class="'.$id.' tmenuimageforpng tmenudisabled" id="mainmenuspan_'.$idsel.'"></span>';
		print '</div>';
		print '</div>';
		if (!getDolGlobalString('THEME_TOPMENU_DISABLE_TEXT')) {
			print '<span '.$classnametxt.' id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($text.' - '.$langs->trans("NotAllowed")).'">';
			print '<span class="mainmenuaspan">';
			print $text;
			print '</span>';
			print '</span>';
		}
	}
}

/**
 * Output end menu entry
 *
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_end_menu_entry_empty($showmode)
{
	if ($showmode) {
		print '</div></li>';
		print "\n";
	}
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_empty()
{
	print '</ul>';
	print '</div>';
	print "\n";
}
