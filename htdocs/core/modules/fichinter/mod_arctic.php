<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin                <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/modules/fichinter/mod_arctic.php
 *	\ingroup    Intervention card
 *	\brief      File with Arctic numbering module for interventions
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';

/**
 *	Class to manage numbering of intervention cards with rule Arctic.
 */
class mod_arctic extends ModeleNumRefFicheinter
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
	 * @var string Nom du modele
	 * @deprecated Use $name, getName()
	 * @see $name
	 */
	public $nom = 'arctic';

	/**
	 * @var string model name
	 */
	public $name = 'arctic';


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
		$texte .= '<input type="hidden" name="maskconst" value="FICHINTER_ARTIC_MASK">';
		$texte .= '<input type="hidden" name="page_y" value="">';

		$texte .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("InterventionCard"), $langs->transnoentities("InterventionCard"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= '<br>';
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("InterventionCard"), $langs->transnoentities("InterventionCard"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Setting the prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskvalue" value="'.getDolGlobalString("FICHINTER_ARTIC_MASK").'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans("Save").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 * Return an example of numbering
	 *
	 * @return     string      Example
	 */
	public function getExample()
	{
		global $langs, $mysoc;

		$old_code_client = $mysoc->code_client;
		$mysoc->code_client = 'CCCCCCCCCC';
		$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client = $old_code_client;

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe|string		$objsoc     Object thirdparty
	 *  @param  Fichinter|string	$object		Object we need next value for
	 *	@return string|int<-1,0>    			Next value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc = '', $object = '')
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We define the search criteria of the counter
		$mask = getDolGlobalString("FICHINTER_ARTIC_MASK");

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}
		$datec = '';
		if (!empty($object->datec)) {
			$datec = (int) $object->datec;
		}
		$numFinal = get_next_value($db, $mask, 'fichinter', 'ref', '', $objsoc, $datec);

		return  $numFinal;
	}


	/**
	 *  Return next free value
	 *
	 *  @param	Societe			$objsoc     Object third party
	 *  @param	Fichinter		$objforref	Object for number to search
	 *  @return string|int      			Next free value, 0 if KO
	 *  @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}
