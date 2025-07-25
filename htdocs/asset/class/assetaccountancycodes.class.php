<?php
/* Copyright (C) 2021  Open-Dsi  <support@open-dsi.fr>
 * Copyright (C) 2024		MDW			<mdeweerd@users.noreply.github.com>
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
 * \file        asset/class/assetaccountancycodes.class.php
 * \ingroup     asset
 * \brief       This file is a class file for AssetAccountancyCodes
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class for AssetAccountancyCodes
 */
class AssetAccountancyCodes extends CommonObject
{
	// TODO This class and table should not exists and should be properties of llx_asset_asset.

	/**
	 * @var string 	Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'asset_accountancy_codes_economic';

	/**
	 * @var string    Field with ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_asset';

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @inheritdoc
	 * Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => 'Id'),
		//...
	);

	/**
	 * @var int ID
	 */
	public $rowid;
	// END MODULEBUILDER PROPERTIES


	/**
	 * @var array<string,array<string,string|array<string,array{label:string,columnbreak?:bool}>>>  Array with all accountancy codes info by mode.
	 *  Note : 'economic' mode is mandatory and is the primary accountancy codes
	 *         'depreciation_asset' and 'depreciation_expense' is mandatory and is used for write depreciation in bookkeeping
	 */
	public $accountancy_codes_fields = array(
		'economic' => array(
			'label' => 'AssetAccountancyCodeDepreciationEconomic',
			'table'	=> 'asset_accountancy_codes_economic',
			'depreciation_debit' => 'depreciation_asset',
			'depreciation_credit' => 'depreciation_expense',
			'fields' => array(
				'asset' => array('label' => 'AssetAccountancyCodeAsset'),
				'depreciation_asset' => array('label' => 'AssetAccountancyCodeDepreciationAsset'),
				'depreciation_expense' => array('label' => 'AssetAccountancyCodeDepreciationExpense'),
				'value_asset_sold' => array('label' => 'AssetAccountancyCodeValueAssetSold'),
				'receivable_on_assignment' => array('label' => 'AssetAccountancyCodeReceivableOnAssignment'),
				'proceeds_from_sales' => array('label' => 'AssetAccountancyCodeProceedsFromSales'),
				'vat_collected' => array('label' => 'AssetAccountancyCodeVatCollected'),
				'vat_deductible' => array('label' => 'AssetAccountancyCodeVatDeductible','column_break' => true),
			),
		),
		'accelerated_depreciation' => array(
			'label' => 'AssetAccountancyCodeDepreciationAcceleratedDepreciation',
			'table'	=> 'asset_accountancy_codes_fiscal',
			'depreciation_debit' => 'accelerated_depreciation',
			'depreciation_credit' => 'endowment_accelerated_depreciation',
			'fields' => array(
				'accelerated_depreciation' => array('label' => 'AssetAccountancyCodeAcceleratedDepreciation'),
				'endowment_accelerated_depreciation' => array('label' => 'AssetAccountancyCodeEndowmentAcceleratedDepreciation'),
				'provision_accelerated_depreciation' => array('label' => 'AssetAccountancyCodeProvisionAcceleratedDepreciation'),
			),
		),
	);

	/**
	 * @var int		ID parent asset
	 */
	public $fk_asset;

	/**
	 * @var array<string,array<string,string>>  Array with all accountancy codes by mode.
	 */
	public $accountancy_codes = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int		$id	Id object
	 * @param	string	$ref	Ref
	 * @return	int<-1,max>	Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);

		return $result;
	}

	/**
	 * Delete object in database
	 *
	 * @param	User		$user		User that deletes
	 * @param	int<0,1> 	$notrigger	0=launch triggers, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}


	/**
	 *  Fill accountancy_codes property of object (using for data sent by forms)
	 *
	 *  @return	array<string,array<string,string>>		Array of values
	 */
	public function setAccountancyCodesFromPost()
	{
		$this->accountancy_codes = array();
		foreach ($this->accountancy_codes_fields as $mode_key => $mode_info) {
			$this->accountancy_codes[$mode_key] = array();
			foreach ($mode_info['fields'] as $field_key => $field_info) {
				$accountancy_code = GETPOST($mode_key . '_' . $field_key, 'aZ09');
				if (empty($accountancy_code) || $accountancy_code == '-1') {
					$accountancy_code = '';
				}
				$this->accountancy_codes[$mode_key][$field_key] = $accountancy_code;
			}
		}
		return $this->accountancy_codes;
	}

	/**
	 *  Load accountancy codes of a asset or a asset model
	 *
	 * @param	int		$asset_id			Asset ID to set
	 * @param	int		$asset_model_id		Asset model ID to set
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function fetchAccountancyCodes($asset_id = 0, $asset_model_id = 0)
	{
		global $langs, $hookmanager;
		dol_syslog(__METHOD__ . " asset_id=$asset_id, asset_model_id=$asset_model_id");

		$error = 0;
		$this->errors = array();
		$this->accountancy_codes = array();

		// Clean parameters
		$asset_id = $asset_id > 0 ? $asset_id : 0;
		$asset_model_id = $asset_model_id > 0 ? $asset_model_id : 0;

		$hookmanager->initHooks(array('assetaccountancycodesdao'));
		$parameters = array('asset_id' => $asset_id, 'asset_model_id' => $asset_model_id);
		$reshook = $hookmanager->executeHooks('fetchAccountancyCodes', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if (!empty($reshook)) {
			return $reshook;
		}

		// Check parameters
		if (empty($asset_id) && empty($asset_model_id)) {
			$this->errors[] = $langs->trans('AssetErrorAssetOrAssetModelIDNotProvide');
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Error check parameters: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$accountancy_codes = array();
		foreach ($this->accountancy_codes_fields as $mode_key => $mode_info) {
			$sql = "SELECT " . implode(',', array_keys($mode_info['fields']));
			$sql .= " FROM " . MAIN_DB_PREFIX . $mode_info['table'];
			$sql .= " WHERE " . ($asset_id > 0 ? " fk_asset = " . (int) $asset_id : " fk_asset_model = " . (int) $asset_model_id);

			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					$accountancy_codes[$mode_key] = array();
					foreach ($mode_info['fields'] as $field_key => $field_info) {
						$accountancy_codes[$mode_key][$field_key] = $obj->$field_key;
					}
				}
			} else {
				$this->errors[] = $langs->trans('AssetErrorFetchAccountancyCodesForMode', $mode_key) . ': ' . $this->db->lasterror();
				$error++;
			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . " Error fetch accountancy codes: " . $this->errorsToString(), LOG_ERR);
			return -1;
		} else {
			$this->accountancy_codes = $accountancy_codes;
			return 1;
		}
	}

	/**
	 *	Update accountancy codes of a asset or a asset model
	 *
	 * @param	User	$user				User making update
	 * @param	int		$asset_id			Asset ID to set
	 * @param	int		$asset_model_id		Asset model ID to set
	 * @param	int		$notrigger			1=disable trigger UPDATE (when called by create)
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function updateAccountancyCodes($user, $asset_id = 0, $asset_model_id = 0, $notrigger = 0)
	{
		global $langs, $hookmanager;
		dol_syslog(__METHOD__ . " user_id=".$user->id.", asset_id=".$asset_id.", asset_model_id=".$asset_model_id.", notrigger=".$notrigger);

		$error = 0;
		$this->errors = array();

		// Clean parameters
		$asset_id = $asset_id > 0 ? $asset_id : 0;
		$asset_model_id = $asset_model_id > 0 ? $asset_model_id : 0;

		$hookmanager->initHooks(array('assetaccountancycodesdao'));
		$parameters = array('user' => $user, 'asset_id' => $asset_id, 'asset_model_id' => $asset_model_id);
		$reshook = $hookmanager->executeHooks('updateAccountancyCodes', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if (!empty($reshook)) {
			return $reshook;
		}

		// Check parameters
		if (empty($asset_id) && empty($asset_model_id)) {
			$this->errors[] = $langs->trans('AssetErrorAssetOrAssetModelIDNotProvide');
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Error check parameters: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();
		$now = dol_now();

		foreach ($this->accountancy_codes_fields as $mode_key => $mode_info) {
			// Delete old accountancy codes
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $mode_info['table'];
			$sql .= " WHERE " . ($asset_id > 0 ? " fk_asset = " . (int) $asset_id : " fk_asset_model = " . (int) $asset_model_id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $langs->trans('AssetErrorDeleteAccountancyCodesForMode', $mode_key) . ': ' . $this->db->lasterror();
				$error++;
			}

			if (!$error && !empty($this->accountancy_codes[$mode_key])) {
				// Insert accountancy codes
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . $mode_info['table'] . "(";
				$sql .= $asset_id > 0 ? "fk_asset," : "fk_asset_model,";
				$sql .= implode(',', array_keys($mode_info['fields']));
				$sql .= ", tms, fk_user_modif";
				$sql .= ") VALUES(";
				$sql .= $asset_id > 0 ? $asset_id : $asset_model_id;
				foreach ($mode_info['fields'] as $field_key => $field_info) {
					$sql .= ', ' . (empty($this->accountancy_codes[$mode_key][$field_key]) ? 'NULL' : "'" . $this->db->escape($this->accountancy_codes[$mode_key][$field_key]) . "'");
				}
				$sql .= ", '" . $this->db->idate($now) . "'";
				$sql .= ", " . $user->id;
				$sql .= ")";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorInsertAccountancyCodesForMode', $mode_key) . ': ' . $this->db->lasterror();
					$error++;
				}
			}
		}

		if (!$error && $asset_id > 0) {
			// Calculation of depreciation lines (reversal and future)
			require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
			$asset = new Asset($this->db);
			$result = $asset->fetch($asset_id);
			if ($result > 0) {
				$result = $asset->calculationDepreciation();
			}
			if ($result < 0) {
				$this->errors[] = $langs->trans('AssetErrorCalculationDepreciationLines');
				$this->errors[] = $asset->errorsToString();
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ASSET_ACCOUNTANCY_CODES_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
