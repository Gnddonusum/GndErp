<?php
/* Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
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
 * \file       htdocs/core/modules/payment/mod_payment_ant.php
 * \ingroup    payment
 * \brief      File containing class for numbering module Ant
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/payment/modules_payment.php';


/**
 *	Class to manage customer payment numbering rules Ant
 */
class mod_payment_ant extends ModeleNumRefPayments
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string Name of sub-module
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Ant';

	/**
	 * @var string Sub-module name
	 */
	public $name = 'Ant';

	/**
	 * @var int		Position
	 */
	public $position = 50;


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $db, $langs;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstpayment" value="PAYMENT_ANT_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Order"), $langs->transnoentities("Order"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskpayment" value="'.getDolGlobalString('PAYMENT_ANT_MASK').'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

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
		global $mysoc;

		$old_code_client = $mysoc->code_client;
		$mysoc->code_client = 'CCCCCCCCCC';
		$numExample = $this->getNextValue($mysoc, null);
		$mysoc->code_client = $old_code_client;

		if (!$numExample) {
			$numExample = 'NotConfigured';
		}
		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe			$objsoc     Object thirdparty
	 *  @param  ?Paiement		$object		Object we need next value for
	 *  @return string|int<-1,0>			Value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('PAYMENT_ANT_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$numFinal = get_next_value($db, $mask, 'paiement', 'ref', '', $objsoc, $object->datepaye);

		return  $numFinal;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next free value
	 *
	 *  @param	Societe			$objsoc     Object third party
	 * 	@param	?Paiement		$objforref	Object for number to search
	 *  @return string|int<-1,0>  			Next free value, <=0 if KO
	 */
	public function commande_get_num($objsoc, $objforref)
	{
		// phpcs:enable
		return $this->getNextValue($objsoc, $objforref);
	}
}
