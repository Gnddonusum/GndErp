<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/admin/invoice_situation.php
 *		\ingroup    invoice
 *		\brief      Page to setup invoice module
 */

// Load Dolibarr environment
require '../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('situationinvoicesetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$scandir = GETPOST('scan_dir', 'alpha');
$type = 'invoice';

$form = new Form($db);
$formSetup = new FormSetup($db);


// Setup conf MYMODULE_MYPARAM4 : example of quick define write style
$formSetup->newItem('INVOICE_USE_SITUATION')
	->setAsYesNo()
	->nameText = $langs->trans('UseSituationInvoices');

$item = $formSetup->newItem('INVOICE_USE_SITUATION_CREDIT_NOTE')
	->setAsYesNo()
	->nameText = $langs->trans('UseSituationInvoicesCreditNote');

//$item = $formSetup->newItem('INVOICE_USE_RETAINED_WARRANTY')
//	->setAsYesNo()
//	->nameText = $langs->trans('RetainedWarranty');


$item = $formSetup->newItem('INVOICE_USE_RETAINED_WARRANTY');
$item->nameText = $langs->trans('AllowedInvoiceForRetainedWarranty');
$arrayAvailableType = array(
	Facture::TYPE_SITUATION => $langs->trans("InvoiceSituation"),
	Facture::TYPE_STANDARD.'+'.Facture::TYPE_SITUATION => $langs->trans("InvoiceSituation").' + '.$langs->trans("InvoiceStandard"),
);
$item->setAsSelect($arrayAvailableType);

//$item = $formSetup->newItem('INVOICE_RETAINED_WARRANTY_LIMITED_TO_SITUATION')->setAsYesNo();
//$item->nameText = $langs->trans('RetainedWarrantyOnlyForSituation');

$formSetup->newItem('INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION')
	->setAsYesNo()
	->nameText = $langs->trans('RetainedWarrantyOnlyForSituationFinal');


$item = $formSetup->newItem('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_PERCENT');
$item->nameText = $langs->trans('RetainedWarrantyDefaultPercent');
$item->fieldAttr = array(
	'type' => 'number',
	'step' => '0.01',
	'min' => 0,
	'max' => 100
);


// Conditions paiements
$item = $formSetup->newItem('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID');
$item->nameText = $langs->trans('PaymentConditionsShortRetainedWarranty');
$form->load_cache_conditions_paiements();
if (getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID') && isset($form->cache_conditions_paiements[getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID')]['label'])) {
	$item->fieldOutputOverride = $form->cache_conditions_paiements[getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID')]['label'];
}
$item->fieldInputOverride = $form->getSelectConditionsPaiements($conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID, 'INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID', -1, 1);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$help_url = 'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura';

llxHeader("", $langs->trans("BillsSetup"), $help_url, '', 0, 0, '', '', '', 'mod-admin page-invoice_situation');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"), $linkback, 'title_setup');

$head = invoice_admin_prepare_head();
print dol_get_fiche_head($head, 'situation', $langs->trans("InvoiceSituation"), -1, 'bill');


print '<span class="opacitymedium">'.$langs->trans("InvoiceFirstSituationDesc").'</span><br><br>';


/*
 *  Numbering module
 */

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
} else {
	print $formSetup->generateOutput();
}

if (count($formSetup->items) > 0) {
	if ($action != 'edit') {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
} else {
	print '<br>'.$langs->trans("NothingToSetup");
}


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
