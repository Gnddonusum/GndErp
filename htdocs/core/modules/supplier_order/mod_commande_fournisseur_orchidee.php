<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/supplier_order/mod_commande_fournisseur_orchidee.php
 *	\ingroup    order
 *	\brief      File for class for 'orchidee' type numbering the supplier orders
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';


/**
 *	Class providing the 'Orchidee' numbering models for supplier orders
 */
class mod_commande_fournisseur_orchidee extends ModeleNumRefSuppliersOrders
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Orchidee';

	/**
	 * @var string model name
	 */
	public $name = 'Orchidee';


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $db, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("bills", "admin"));

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstorder" value="COMMANDE_FOURNISSEUR_ORCHIDEE_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskorder" value="'.getDolGlobalString("COMMANDE_FOURNISSEUR_ORCHIDEE_MASK").'">', $tooltip, 1, 'help', '', 0, 3, 'tooltiporchidee').'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$supplierorder = new CommandeFournisseur($db);
		$supplierorder->initAsSpecimen();
		$thirdparty = new Societe($db);
		$thirdparty->initAsSpecimen();

		$numExample = $this->getNextValue($thirdparty, $supplierorder);

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * 	Return next value
	 *
	 *  @param	Societe|string		$objsoc		Object third party
	 *  @param  CommandeFournisseur	$object		Object
	 *  @return string|int<-1,0>				Value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// On defini critere recherche compteur
		$mask = getDolGlobalString("COMMANDE_FOURNISSEUR_ORCHIDEE_MASK");

		if (!$mask) {
			$this->error = $langs->trans('NotConfigured');
			return 0;
		}

		$numFinal = get_next_value($db, $mask, 'commande_fournisseur', 'ref', '', $objsoc, $object->date_commande);

		return  $numFinal;
	}
}
