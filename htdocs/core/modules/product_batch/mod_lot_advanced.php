<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2021       Christophe Battarel			<christophe@altairis.fr>
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
 * \file       htdocs/core/modules/product_batch/mod_lot_advanced.php
 * \ingroup    productbatch
 * \brief      File containing class for numbering model of Lot advanced
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';


/**
 *	Class to manage Batch numbering rules advanced
 */
class mod_lot_advanced extends ModeleNumRefBatch
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
	 * @var string name
	 */
	public $name = 'lot_advanced';


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $conf, $langs, $db;

		$langs->load("bills");

		$form = new Form($db);

		// We get cursor rule
		$mask = getDolGlobalString('LOT_ADVANCED_MASK');

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMaskLot">';
		$texte .= '<input type="hidden" name="maskconstLot" value="LOT_ADVANCED_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskLot" value="'.$mask.'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

		// Option to enable custom masks per product
		$texte .= '<td class="right">';
		if (getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS')) {
			$texte .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmaskslot&token='.newToken().'&value=0">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		} else {
			$texte .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmaskslot&token='.newToken().'&value=1">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		}
		$texte .= ' '.$langs->trans('CustomMasks')."\n";
		$texte .= '</td>';

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
		global $langs, $mysoc;

		$old_code_client = $mysoc->code_client;
		$old_code_type = $mysoc->typent_code;
		$mysoc->code_client = 'CCCCCCCCCC';
		$mysoc->typent_code = 'TTTTTTTTTT';
		$numExample = $this->getNextValue($mysoc, null);
		$mysoc->code_client = $old_code_client;
		$mysoc->typent_code = $old_code_type;

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	?Societe	$objsoc		Object thirdparty
	 *  @param  ?Productlot	$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('LOT_ADVANCED_MASK');
		$filter = '';
		if (getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && !empty($object->fk_product)) {
			$product = new Product($db);
			$res = $product->fetch($object->fk_product);
			if ($res > 0 && !empty($product->batch_mask)) {
				$mask = $product->batch_mask;
				$filter = '';
			}
		}

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = dol_now();

		$numFinal = get_next_value($db, $mask, 'product_lot', 'batch', $filter, null, $date);

		return  $numFinal;
	}
}
