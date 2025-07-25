<?php
/* Copyright (C) 2010-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var FormProduct $formproduct
 * @var Translate $langs
 * @var Product|Entrepot|MouvementStock $object
 *
 * @var string 	$backtopage
 * @var ?int	$id
 * @var int		$d_eatby
 * @var int		$d_sellby
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force Entrepot|Product|MouvementStock $object
@phan-var-force FormProduct $formproduct
@phan-var-force string $backtopage
';

?>

<!-- BEGIN PHP TEMPLATE STOCKTRANSFER.TPL.PHP -->
<?php
$productref = '';
if ($object->element == 'product') {
	/** @var Product $object */
	$productref = $object->ref;
}

$langs->load("productbatch");

if (empty($id)) {
	$id = $object->id;
}

$pdluoid = GETPOSTINT('pdluoid');

$pdluo = new Productbatch($db);

if ($pdluoid > 0) {
	$result = $pdluo->fetch($pdluoid);
	if ($result > 0) {
		$pdluoid = $pdluo->id;
	} else {
		dol_print_error($db, $pdluo->error, $pdluo->errors);
	}
}

print load_fiche_titre($langs->trans("StockTransfer"), '', 'generic');

print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";

print dol_get_fiche_head();

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="transfert_stock">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
if ($pdluoid) {
	print '<input type="hidden" name="pdluoid" value="'.$pdluoid.'">';
}
print '<table class="border centpercent">';

// Source product or stock movement
print '<tr>';
if ($object->element == 'product') {
	/** @var Product $object */
	print '<td class="fieldrequired">'.$langs->trans("WarehouseSource").'</td>';
	print '<td>';
	print img_picto('', 'stock');
	$selected = (GETPOST("dwid") ? GETPOSTINT("dwid") : (GETPOST('id_entrepot') ? GETPOSTINT('id_entrepot') : ($object->element == 'product' && $object->fk_default_warehouse ? $object->fk_default_warehouse : 'ifone')));
	$warehousestatus = 'warehouseopen,warehouseinternal';
	print $formproduct->selectWarehouses($selected, 'id_entrepot', $warehousestatus, 1, 0, 0, '', 0, 0, array(), 'minwidth75 maxwidth300 widthcentpercentminusx');
	print '</td>';
}
if ($object->element == 'stockmouvement') {
	/** @var MouvementStock $object */
	print '<td class="fieldrequired">'.$langs->trans("Product").'</td>';
	print '<td>';
	print img_picto('', 'product');
	$form->select_produits(GETPOSTINT('product_id'), 'product_id', (!getDolGlobalString('STOCK_SUPPORTS_SERVICES') ? '0' : ''), 0, 0, -1, 2, '', 0, array(), 0, 1, 0, 'maxwidth500');
	print '</td>';
}

print '<td class="fieldrequired">'.$langs->trans("WarehouseTarget").'</td><td>';
print img_picto('', 'stock').$formproduct->selectWarehouses(GETPOST('id_entrepot_destination'), 'id_entrepot_destination', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth75 maxwidth300 widthcentpercentminusx');
print '</td></tr>';
print '<tr><td class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td colspan="3"><input type="text" name="nbpiece" class="center maxwidth75" value="'.dol_escape_htmltag(GETPOST("nbpiece")).'"></td>';
print '</tr>';

// Serial / Eat-by date
if (isModEnabled('productbatch') &&
(($object->element == 'product' && $object->hasbatch())
|| ($object->element == 'stockmouvement'))
) {
	/** @var Product|MouvementStock $object */
	print '<tr>';
	print '<td'.($object->element == 'stockmouvement' ? '' : ' class="fieldrequired"').'>'.$langs->trans("batch_number").'</td><td colspan="3">';
	if ($pdluoid > 0) {
		// If form was opened for a specific pdluoid, field is disabled
		print '<input type="text" name="batch_number_bis" size="40" disabled="disabled" value="'.(GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch).'">';
		print '<input type="hidden" name="batch_number" value="'.(GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch).'">';
	} else {
		print img_picto('', 'barcode', 'class="pictofixedwidth"').'<input type="text" name="batch_number" class="minwidth300 widthcentpercentminusx maxwidth300" value="'.(GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch).'">';
	}
	print '</td>';
	print '</tr>';

	print '<tr>';
	if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
		print '<td>'.$langs->trans("SellByDate").'</td><td>';
		print $form->selectDate((!empty($d_sellby) ? $d_sellby : $pdluo->sellby), 'sellby', 0, 0, 1, "", 1, 0, ($pdluoid > 0 ? 1 : 0)); // If form was opened for a specific pdluoid, field is disabled
		print '</td>';
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
		print '<td>'.$langs->trans("EatByDate").'</td><td>';
		print $form->selectDate((!empty($d_eatby) ? $d_eatby : $pdluo->eatby), 'eatby', 0, 0, 1, "", 1, 0, ($pdluoid > 0 ? 1 : 0)); // If form was opened for a specific pdluoid, field is disabled
		print '</td>';
	}
	print '</tr>';
}

// Label
$valformovementlabel = (GETPOST("label") ? GETPOST("label") : $langs->trans("MovementTransferStock", $productref));
print '<tr>';
print '<td>'.$langs->trans("MovementLabel").'</td>';
print '<td>';
print '<input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($valformovementlabel).'">';
print '</td>';
print '<td>'.$langs->trans("InventoryCode").'</td>';
print '<td>';
print '<input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="'.(GETPOSTISSET("inventorycode") ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S')).'">';
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
print '</div>';

print '</form>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->
