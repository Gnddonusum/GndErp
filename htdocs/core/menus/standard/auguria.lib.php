<?php
/* Copyright (C) 2010-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *  \file		htdocs/core/menus/standard/auguria.lib.php
 *  \brief		Library for file auguria menus
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

/** @phan-file-suppress PhanTypeInvalidDimOffset */

/**
 * Core function to output top menu auguria
 *
 * @param 	DoliDB		$db			Database handler
 * @param 	string		$atarget	Target (Example: '' or '_top')
 * @param 	int			$type_user	0=Menu for backoffice, 1=Menu for front office
 * @param	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}> $tabMenu		If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu		$menu		Object Menu to return back list of menu entries
 * @param	int<0,1>	$noout		1=Disable output (Initialise &$menu only).
 * @param	string		$mode		'top', 'topnb', 'left', 'jmobile'
 * @return	int						0
 */
function print_auguria_menu($db, $atarget, $type_user, &$tabMenu, &$menu, $noout = 0, $mode = '')
{
	global $user, $conf, $langs, $mysoc;
	global $dolibarr_main_db_name;

	$mainmenu = (empty($_SESSION["mainmenu"]) ? '' : $_SESSION["mainmenu"]);
	$leftmenu = (empty($_SESSION["leftmenu"]) ? '' : $_SESSION["leftmenu"]);

	$id = 'mainmenu';
	$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));

	// Show personalized menus
	$menuArbo = new Menubase($db, 'auguria');
	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'auguria', $tabMenu);

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

	global $usemenuhider;
	$usemenuhider = 1;

	// Show/Hide vertical menu. The hamburger icon for .menuhider action.
	if ($mode != 'jmobile' && $mode != 'topnb' && $usemenuhider && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		$showmode = 1;
		$classname = 'class="tmenu menuhider nohover"';
		$idsel = 'menu';

		$menu->add('#', (getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE') == 1 ? '<span class="fa fa-bars"></span>' : ''), 0, $showmode, $atarget, "xxx", '', 0, $id, $idsel, $classname, '<span class="fas fa-bars size12x"></span>');
	}

	$num = count($newTabMenu);
	for ($i = 0; $i < $num; $i++) {
		//var_dump($type_user.' '.$newTabMenu[$i]['url'].' '.$showmode.' '.$newTabMenu[$i]['perms']);
		$idsel = (empty($newTabMenu[$i]['mainmenu']) ? 'none' : $newTabMenu[$i]['mainmenu']);

		$shorturl = '';

		$showmode = dol_auguria_showmenu($type_user, $newTabMenu[$i], $listofmodulesforexternal);
		if ($showmode == 1) {
			$newTabMenu[$i]['url'] = make_substitutions($newTabMenu[$i]['url'], $substitarray);

			// Phan issue #4881 requires that we reforce the type
			'@phan-var-force array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level?:int,prefix:string}> $newTabMenu';

			// url = url from host, shorturl = relative path into dolibarr sources
			$url = $shorturl = $newTabMenu[$i]['url'];

			if (!preg_match("/^(http:\/\/|https:\/\/)/i", $newTabMenu[$i]['url'])) {	// Do not change url content for external links
				$tmp = explode('?', $newTabMenu[$i]['url'], 2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1]) ? $tmp[1] : '');

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($newTabMenu[$i]['url'])) {
					// @phan-suppress-next-line PhanTypeSuspiciousStringExpression,PhanTypeInvalidDimOffset
					$param .= ($param ? '&' : '').'mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
				}
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($newTabMenu[$i]['url'])) {
					$param .= ($param ? '&' : '').'leftmenu=';
				}
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
				//$shorturl = $shorturl.($param?'?'.$param:'');
				$shorturl = $url;

				if (DOL_URL_ROOT) {
					$shorturl = preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/', '', $shorturl);
				}
			}

			// Modify URL for the case we are using the option showtopmenuinframe
			'@phan-var-force array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level?:int,prefix:string}> $newTabMenu';
			// @phan-suppress-next-line PhanTypeInvalidDimOffset
			if ($newTabMenu[$i]['showtopmenuinframe']) {
				if (preg_match("/^(http:\/\/|https:\/\/)/i", $newTabMenu[$i]['url'])) {
					$url = '/core/frames.php?idmenu='.$newTabMenu[$i]['rowid'];
					$shorturl = $url;
				}
			}

			// TODO Find a generic solution
			if (preg_match('/search_project_user=__search_project_user__/', $shorturl)) {
				$search_project_user = GETPOSTINT('search_project_user');
				if ($search_project_user) {
					$shorturl = preg_replace('/search_project_user=__search_project_user__/', 'search_project_user='.$search_project_user, $shorturl);
				} else {
					$shorturl = preg_replace('/search_project_user=__search_project_user__/', '', $shorturl);
				}
			}

			// Define the class (top menu selected or not)
			if (!empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) {
				$classname = 'class="tmenusel"';
			} elseif (!empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) {
				$classname = 'class="tmenusel"';
			} else {
				$classname = 'class="tmenu"';
			}
		} elseif ($showmode == 2) {
			$classname = 'class="tmenu"';
		} else {
			$classname = '';
		}

		$menu->add($shorturl, $newTabMenu[$i]['titre'], 0, $showmode, ($newTabMenu[$i]['target'] ? $newTabMenu[$i]['target'] : $atarget), ($newTabMenu[$i]['mainmenu'] ? $newTabMenu[$i]['mainmenu'] : $newTabMenu[$i]['rowid']), ($newTabMenu[$i]['leftmenu'] ? $newTabMenu[$i]['leftmenu'] : ''), $newTabMenu[$i]['position'], $id, $idsel, $classname, $newTabMenu[$i]['prefix']);
	}

	// Sort on position
	$menu->liste = dol_sort_array($menu->liste, 'position');

	// If noout is on (for jmobile div menu for example)
	if ($noout) {
		return 0;
	}

	// Output menu entries

	print_start_menu_array_auguria();

	// Show logo company
	if (!getDolGlobalString('MAIN_MENU_INVERT') && getDolGlobalString('MAIN_SHOW_LOGO') && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		//$mysoc->logo_mini=(empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI);
		$mysoc->logo_squarred_mini = (!getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI') ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI);

		$logoContainerAdditionalClass = 'backgroundforcompanylogo';
		if (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_NO_BACKGROUND')) {
			$logoContainerAdditionalClass = '';
		}

		if (!empty($mysoc->logo_squarred_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
			/*} elseif (!empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
			{
			$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini);
			}*/
		} else {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_512x512_white.png';
			$logoContainerAdditionalClass = '';
		}

		$title = $langs->trans("GoIntoSetupToChangeLogo");

		print "\n".'<!-- Show logo on menu -->'."\n";
		print_start_menu_entry_auguria('companylogo', 'class="tmenu tmenucompanylogo nohover"', 1);

		print '<div class="center '.$logoContainerAdditionalClass.' menulogocontainer"><img class="mycompany" title="'.dol_escape_htmltag($title).'" alt="" src="'.$urllogo.'" style="max-width: 100px"></div>'."\n";

		print_end_menu_entry_auguria(4);
	}

	foreach ($menu->liste as $menuval) {
		print_start_menu_entry_auguria($menuval['idsel'], $menuval['classname'], $menuval['enabled']);
		// @phan-ignore-next-line
		// @phpstan-ignore-next-line
		print_text_menu_entry_auguria($menuval['titre'], $menuval['enabled'], ($menuval['url'] != '#' ? DOL_URL_ROOT : '').$menuval['url'], $menuval['id'], $menuval['idsel'], $menuval['classname'], ($menuval['target'] ? $menuval['target'] : $atarget), $menuval);
		print_end_menu_entry_auguria($menuval['enabled']);
	}

	if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		$showmode = 1;
		print_start_menu_entry_auguria('', 'class="tmenuend"', $showmode);
		print_end_menu_entry_auguria($showmode);
		print_end_menu_array_auguria();
	}

	return 0;
}


/**
 * Output start menu array
 *
 * @return	void
 */
function print_start_menu_array_auguria()
{
	print '<div class="tmenudiv">';
	print '<ul role="navigation" class="tmenu"'.(getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' alt="Top menu"' : '').'>';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry_auguria($idsel, $classname, $showmode)
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
 * @param	string		$text		Text
 * @param	int<0,2>	$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @param	string		$url		Url
 * @param	string		$id			Id
 * @param	string		$idsel		Id sel
 * @param	string		$classname	Class name
 * @param	string		$atarget	Target
 * @param	array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}|array{}	$menuval	The full $menuval array
 * @return	void
 */
function print_text_menu_entry_auguria($text, $showmode, $url, $id, $idsel, $classname, $atarget, $menuval = array())
{
	global $langs;

	$classnameimg = str_replace('class="', 'class="tmenuimage ', $classname);
	$classnametxt = str_replace('class="', 'class="tmenulabel ', $classname);

	if ($showmode == 1) {
		print '<a '.$classnameimg.' tabindex="-1" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage">';
		$reg = array();
		if (!empty($menuval['prefix']) && strpos($menuval['prefix'], '<span') === 0) {
			print $menuval['prefix'];
		} elseif (!empty($menuval['prefix']) && preg_match('/^(fa[rsb]? )?fa-/', $menuval['prefix'], $reg)) {
			print '<span class="'.$id.' '.(empty($reg[1]) ? 'fa ' : '').$menuval['prefix'].'" id="mainmenuspan_'.$idsel.'"></span>';
		} else {
			print '<span class="'.$id.' tmenuimageforpng" id="mainmenuspan_'.$idsel.'"></span>';
		}
		print '</div>';
		print '</a>';
		if (!getDolGlobalString('THEME_TOPMENU_DISABLE_TEXT')) {
			print '<a '.$classnametxt.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
			print '<span class="mainmenuaspan">';
			print $text;
			print '</span>';
			print '</a>';
		}
	} elseif ($showmode == 2) {
		print '<div '.$classnameimg.' title="'.dol_escape_htmltag($text.' - '.$langs->trans("NotAllowed")).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage tmenudisabled">';
		if (!empty($menuval['prefix']) && strpos($menuval['prefix'], '<span') === 0) {
			print $menuval['prefix'];
		} else {
			print '<span class="'.$id.' tmenuimageforpng tmenudisabled" id="mainmenuspan_'.$idsel.'"></span>';
		}
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
function print_end_menu_entry_auguria($showmode)
{
	if ($showmode) {
		print '</div></li>';
	}
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_auguria()
{
	print '</ul>';
	print '</div>';
	print "\n";
}



/**
 * Core function to output left menu auguria
 * Fill &$menu (example with $forcemainmenu='home' $forceleftmenu='all', return left menu tree of Home)
 *
 * @param	DoliDB		$db                 Database handler
 * @param	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}> 	$menu_array_before  Table of menu entries to show before entries of menu handler (menu->liste filled with menu->add)
 * @param	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}>		$menu_array_after   Table of menu entries to show after entries of menu handler (menu->liste filled with menu->add)
 * @param	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string}> 	$tabMenu       		If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu		$menu				Object Menu to return back list of menu entries
 * @param	int<0,1>	$noout				Disable output (Initialise &$menu only).
 * @param	string		$forcemainmenu		'x'=Force mainmenu to mainmenu='x'
 * @param	string		$forceleftmenu		'all'=Force leftmenu to '' (= all). If value come being '', we change it to value in session and 'none' if not defined in session.
 * @param	?array<string,string>	$moredata	An array with more data to output
 * @param 	int<0,1>	$type_user     		0=Menu for backoffice, 1=Menu for front office
 * @return	int								Nb of menu entries
 */
function print_left_auguria_menu($db, $menu_array_before, $menu_array_after, &$tabMenu, &$menu, $noout = 0, $forcemainmenu = '', $forceleftmenu = '', $moredata = null, $type_user = 0)
{
	global $user, $conf, $langs, $hookmanager;
	global $dolibarr_main_db_name, $mysoc;

	$newmenu = $menu;

	$mainmenu = ($forcemainmenu ? $forcemainmenu : $_SESSION["mainmenu"]);
	$leftmenu = ($forceleftmenu ? '' : (empty($_SESSION["leftmenu"]) ? 'none' : $_SESSION["leftmenu"]));

	global $usemenuhider;
	$usemenuhider = 0;

	if (is_array($moredata) && !empty($moredata['searchform'])) {	// searchform can contains select2 code or link to show old search form or link to switch on search page
		print "\n";
		print "<!-- Begin SearchForm -->\n";
		print '<div id="blockvmenusearch" class="blockvmenusearch">'."\n";
		print $moredata['searchform'];
		print '</div>'."\n";
		print "<!-- End SearchForm -->\n";
	}

	if (is_array($moredata) && !empty($moredata['bookmarks'])) {
		print "\n";
		print "<!-- Begin Bookmarks -->\n";
		print '<div id="blockvmenubookmarks" class="blockvmenubookmarks">'."\n";
		print $moredata['bookmarks'];
		print '</div>'."\n";
		print "<!-- End Bookmarks -->\n";
	}

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

	// We update newmenu with entries found into database
	$menuArbo = new Menubase($db, 'auguria');
	$newmenu = $menuArbo->menuLeftCharger($newmenu, $mainmenu, $leftmenu, ($user->socid ? 1 : 0), 'auguria', $tabMenu);

	// We update newmenu for special dynamic menus
	if (isModEnabled('bank') && $user->hasRight('banque', 'lire') && $mainmenu == 'bank') {	// Entry for each bank account
		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // Required for to get Account::TYPE_CASH for example

		$sql = "SELECT rowid, label, courant, rappro, courant";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql .= " WHERE entity = ".$conf->entity;
		$sql .= " AND clos = 0";
		$sql .= " ORDER BY label";

		$resql = $db->query($sql);
		if ($resql) {
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) {
				$newmenu->add('/compta/bank/list.php?search_status=opened', $langs->trans("BankAccounts"), 0, $user->hasRight('banque', 'lire'));
			}

			while ($i < $numr) {
				$objp = $db->fetch_object($resql);
				$newmenu->add('/compta/bank/card.php?id='.$objp->rowid, $objp->label, 1, $user->hasRight('banque', 'lire'));
				if ($objp->rappro && $objp->courant != Account::TYPE_CASH && empty($objp->clos)) {  // If not cash account and not closed and can be reconciliate
					$newmenu->add('/compta/bank/bankentries_list.php?id='.$objp->rowid, $langs->trans("Conciliate"), 2, $user->hasRight('banque', 'consolidate'));
				}
				$i++;
			}
		} else {
			dol_print_error($db);
		}
		$db->free($resql);
	}

	if (isModEnabled('accounting') && $user->hasRight('accounting', 'comptarapport', 'lire') && $mainmenu == 'accountancy') { 	// Entry in accountancy journal for each bank account
		$newmenu->add('', $langs->trans("RegistrationInAccounting"), 1, $user->hasRight('accounting', 'comptarapport', 'lire'), '', 'accountancy', 'accountancy_journal', 10);

		// Multi journal
		$sql = "SELECT rowid, code, label, nature";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_journal";
		$sql .= " WHERE entity = ".$conf->entity;
		if (getDolGlobalString('ACCOUNTING_MODE') == 'RECETTES-DEPENSES') {
			$sql .= " AND nature = 4"; // only bank journal when using treasury accounting mode
		}
		$sql .= " AND active = 1";
		$sql .= " ORDER BY nature ASC, label DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) {
				while ($i < $numr) {
					$objp = $db->fetch_object($resql);

					$nature = '';

					// Must match array $sourceList defined into journals_list.php
					if ($objp->nature == 2 && isModEnabled('invoice') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
						$nature = "sells";
					}
					if ($objp->nature == 3
						&& isModEnabled('supplier_invoice')
						&& !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
						$nature = "purchases";
					}
					if ($objp->nature == 4 && isModEnabled('bank')) {
						$nature = "bank";
					}
					if ($objp->nature == 5 && isModEnabled('expensereport') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
						$nature = "expensereports";
					}
					if ($objp->nature == 1) {
						$nature = "various";
					}
					if ($objp->nature == 8) {
						$nature = "inventory";
					}
					if ($objp->nature == 9) {
						$nature = "hasnew";
					}

					// To enable when page exists
					if (!getDolGlobalString('ACCOUNTANCY_SHOW_DEVELOP_JOURNAL')) {
						if ($nature == 'hasnew' || $nature == 'inventory') {
							$nature = '';
						}
					}

					if ($nature) {
						$langs->load('accountancy');
						$journallabel = $langs->transnoentities($objp->label); // Labels in this table are set by loading llx_accounting_abc.sql. Label can be 'ACCOUNTING_SELL_JOURNAL', 'InventoryJournal', ...

						if (getDolGlobalString('ACCOUNTING_MODE') == 'RECETTES-DEPENSES') {
							$journalNaturePrefixUrl = 'treasury';
						} else {
							$journalNaturePrefixUrl = $nature;
						}
						$newmenu->add('/accountancy/journal/'.$journalNaturePrefixUrl.'journal.php?mainmenu=accountancy&leftmenu=accountancy_journal&id_journal='.$objp->rowid, $journallabel, 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					}
					$i++;
				}
			} else {
				// Should not happen. Entries are added
				$newmenu->add('', $langs->trans("NoJournalDefined"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
			}
		} else {
			dol_print_error($db);
		}
		$db->free($resql);
	}

	if (isModEnabled('ftp') && $mainmenu == 'ftp') {	// Entry for FTP
		$MAXFTP = 20;
		$i = 1;
		while ($i <= $MAXFTP) {
			$paramkey = 'FTP_NAME_'.$i;
			//print $paramkey;
			if (getDolGlobalString($paramkey)) {
				$link = "/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;

				$newmenu->add($link, dol_trunc($conf->global->$paramkey, 24));
			}
			$i++;
		}
	}


	// Build final $menu_array = $menu_array_before +$newmenu->liste + $menu_array_after
	//var_dump($menu_array_before);exit;
	//var_dump($menu_array_after);exit;
	$menu_array = $newmenu->liste;
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

	// Allow the $menu_array of the menu to be manipulated by modules
	$parameters = array(
		'mainmenu' => $mainmenu,
	);
	$hook_items = $menu_array;
	$reshook = $hookmanager->executeHooks('menuLeftMenuItems', $parameters, $hook_items); // Note that $action and $object may have been modified by some hooks

	if (is_numeric($reshook)) {
		if ($reshook == 0 && !empty($hookmanager->resArray)) {
			$menu_array[] = $hookmanager->resArray; // add
		} elseif ($reshook == 1) {
			$menu_array = $hookmanager->resArray; // replace
		}

		// @todo Sort menu items by 'position' value
		//      $position = array();
		//      foreach ($menu_array as $key => $row) {
		//          $position[$key] = $row['position'];
		//      }
		//		$array1_sort_order = SORT_ASC;
		//      array_multisort($position, $array1_sort_order, $menu_array);
	}

	// Phan has a hard time tracking the type, for instance because it get hookmanager->results
	// Force the typing at this point to get useful analysis below:
	'@phan-var-force array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,showtopmenuinframe:int,prefix:string,level:int}> $menu_array';

	// Show menu
	$invert = !getDolGlobalString('MAIN_MENU_INVERT') ? "" : "invert";
	if (empty($noout)) {
		$altok = 0;
		$blockvmenuopened = false;
		$lastlevel0 = '';
		$num = count($menu_array);
		foreach (array_keys($menu_array) as $i) {     // Loop on each menu entry (foreach better for static analysis)
			$showmenu = true;
			if (getDolGlobalString('MAIN_MENU_HIDE_UNAUTHORIZED') && empty($menu_array[$i]['enabled'])) {
				$showmenu = false;
			}

			// Begin of new left menu block
			if (empty($menu_array[$i]['level']) && $showmenu) {
				$altok++;
				$blockvmenuopened = true;
				$lastopened = true;
				for ($j = ($i + 1); $j < $num; $j++) {
					if (empty($menu_array[$j]['level'])) {
						$lastopened = false;
					}
				}
				if ($altok % 2 == 0) {
					print '<div class="blockvmenu blockvmenuimpair'.$invert.($lastopened ? ' blockvmenulast' : '').($altok == 1 ? ' blockvmenufirst' : '').'">'."\n";
				} else {
					print '<div class="blockvmenu blockvmenupair'.$invert.($lastopened ? ' blockvmenulast' : '').($altok == 1 ? ' blockvmenufirst' : '').'">'."\n";
				}
			}

			// Add tabulation
			$tabstring = '';
			$tabul = ($menu_array[$i]['level'] - 1);
			if ($tabul > 0) {
				for ($j = 0; $j < $tabul; $j++) {
					$tabstring .= '&nbsp;&nbsp;&nbsp;';
				}
			}

			// $menu_array[$i]['url'] can be a relative url, a full external url. We try substitution

			$menu_array[$i]['url'] = make_substitutions($menu_array[$i]['url'], $substitarray);

			$url = $shorturl = $shorturlwithoutparam = $menu_array[$i]['url'];
			if (!preg_match("/^(http:\/\/|https:\/\/)/i", $menu_array[$i]['url'])) {
				$tmp = explode('?', $menu_array[$i]['url'], 2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1]) ? $tmp[1] : ''); // params in url of the menu link

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($menu_array[$i]['mainmenu'])) {
					$param .= ($param ? '&' : '').'mainmenu='.$menu_array[$i]['mainmenu'].'&leftmenu=';
				}
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($menu_array[$i]['mainmenu'])) {
					$param .= ($param ? '&' : '').'leftmenu=';
				}
				//$url.="idmenu=".$menu_array[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
				$shorturlwithoutparam = $shorturl;
				$shorturl .= ($param ? '?'.$param : '');
			}


			print '<!-- Process menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['level'].' enabled='.$menu_array[$i]['enabled'].', position='.$menu_array[$i]['position'].' prefix='.$menu_array[$i]['prefix'].' -->'."\n";

			// Menu level 0
			if ($menu_array[$i]['level'] == 0) {
				if ($menu_array[$i]['enabled']) {     // Enabled so visible
					print '<div class="menu_titre">'.$tabstring;
					if ($shorturlwithoutparam) {
						print '<a class="vmenu" title="'.dol_escape_htmltag(dol_string_nohtmltag($menu_array[$i]['titre'])).'" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
					} else {
						print '<span class="vmenu">';
					}
					if (!empty($menu_array[$i]['prefix'])) {
						$reg = array();
						if (preg_match('/^(fa[rsb]? )?fa-/', $menu_array[$i]['prefix'], $reg)) {
							print '<span class="'.(empty($reg[1]) ? 'fa ' : '').$menu_array[$i]['prefix'].' paddingright pictofixedwidth"></span>';
						} else {
							print $menu_array[$i]['prefix'];
						}
					}

					// print ($menu_array[$i]['prefix'] ? $menu_array[$i]['prefix'] : '');
					print $menu_array[$i]['titre'];
					if ($shorturlwithoutparam) {
						print '</a>';
					} else {
						print '</span>';
					}
					print '</div>'."\n";
					$lastlevel0 = 'enabled';
				} elseif ($showmenu) {                 // Not enabled but visible (so greyed)
					print '<div class="menu_titre">'.$tabstring;
					print '<span class="vmenudisabled">';
					if (!empty($menu_array[$i]['prefix'])) {
						print $menu_array[$i]['prefix'];
					}
					print $menu_array[$i]['titre'];
					print '</span>';
					print '</div>'."\n";
					$lastlevel0 = 'greyed';
				} else {
					$lastlevel0 = 'hidden';
				}
				if ($showmenu) {
					print '<div class="menu_top"></div>'."\n";
				}
			}

			// Menu level > 0
			if ($menu_array[$i]['level'] > 0) {
				$cssmenu = '';
				if ($menu_array[$i]['url']) {
					$cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/', '', $menu_array[$i]['url']));
				}

				if ($menu_array[$i]['enabled'] && $lastlevel0 == 'enabled') {
					// Enabled so visible, except if parent was not enabled.
					print '<div class="menu_contenu'.$cssmenu.'">';
					print $tabstring;
					if ($shorturlwithoutparam) {
						print '<a class="vsmenu" title="'.dol_escape_htmltag(dol_string_nohtmltag($menu_array[$i]['titre'])).'" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
					} else {
						print '<span class="vsmenu" title="'.dol_escape_htmltag($menu_array[$i]['titre']).'">';
					}
					print $menu_array[$i]['titre'];
					if ($shorturlwithoutparam) {
						print '</a>';
					} else {
						print '</span>';
					}
					// If title is not pure text and contains a table, no carriage return added
					if (!strstr($menu_array[$i]['titre'], '<table')) {
						print '<br>';
					}
					print '</div>'."\n";
				} elseif ($showmenu && $lastlevel0 == 'enabled') {
					// Not enabled but visible (so greyed), except if parent was not enabled.
					print '<div class="menu_contenu'.$cssmenu.'">';
					print $tabstring;
					print '<span class="spanlilevel0 vsmenudisabled vsmenudisabledmargin">'.$menu_array[$i]['titre'].'</span><br>';
					print '</div>'."\n";
				}
			}

			// If next is a new block or if there is nothing after
			if (empty($menu_array[$i + 1]['level'])) {               // End menu block
				if ($showmenu) {
					print '<div class="menu_end"></div>'."\n";
				}
				if ($blockvmenuopened) {
					print '</div>'."\n";
					$blockvmenuopened = false;
				}
			}
		}

		if ($altok) {
			print '<div class="blockvmenuend"></div>'; // End menu block
		}
	}

	return count($menu_array);
}


/**
 * Function to test if an entry is enabled or not
 *
 * @param	int		$type_user					0=We need backoffice menu, 1=We need frontoffice menu
 * @param	array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,positionfull:int|string,showtopmenuinframe:int,level:int,prefix:string,module:string}	$menuentry	Array for menu entry
 * @param	string[]	$listofmodulesforexternal	Array with list of modules allowed to external users
 * @return	int<0,2>								0=Hide, 1=Show, 2=Show gray
 */
function dol_auguria_showmenu($type_user, &$menuentry, &$listofmodulesforexternal)
{
	//print 'type_user='.$type_user.' module='.$menuentry['module'].' enabled='.$menuentry['enabled'].' perms='.$menuentry['perms'];
	//print 'ok='.in_array($menuentry['module'], $listofmodulesforexternal);
	if (empty($menuentry['enabled'])) {
		return 0; // Entry disabled by condition
	}
	if ($type_user && $menuentry['module']) {
		$tmploops = explode('|', (string) $menuentry['module']);
		$found = 0;
		foreach ($tmploops as $tmploop) {
			if (in_array($tmploop, $listofmodulesforexternal)) {
				$found++;
				break;
			}
		}
		if (!$found) {
			return 0; // Entry is for menus all excluded to external users
		}
	}
	if (!$menuentry['perms'] && $type_user) {
		return 0; // No permissions and user is external
	}
	if (!$menuentry['perms'] && getDolGlobalString('MAIN_MENU_HIDE_UNAUTHORIZED')) {
		return 0; // No permissions and option to hide when not allowed, even for internal user, is on
	}
	if (!$menuentry['perms']) {
		return 2; // No permissions and user is external
	}
	return 1;
}
