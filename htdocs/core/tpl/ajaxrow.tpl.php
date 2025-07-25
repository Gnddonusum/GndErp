<?php
/* Copyright (C) 2010-2012 	Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2025 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024      	Frédéric France    	<frederic.france@free.fr>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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
 * Javascript code to activate the drag and drop on lines
 * You can use this if you want to be able to drag and drop rows of a HTML table.
 * You must add id="tablelines" on table level tag
 * $object and $object->id must be defined
 * $object->fk_element or $fk_element must be defined
 * you must have ($nboflines or count($object->lines) or count($taskarray) > 0)
 * you must have $table_element_line = 'tablename' or $object->table_element_line with line to move
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 *
 * @var ?string $filepath
 * @var ?string $fk_element
 * @var ?int 	$nboflines
 * @var ?string $tagidfortablednd
 * @var	?string	$table_element_line
 * @var ?Task[]	$tasksarray
 * @var ?string	$urltorefreshaftermove
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page ".basename(__FILE__)." can't be called with no object defined.";
	exit;
}
'
@phan-var-force ?string $fk_element
@phan-var-force ?Task[] $tasksarray
';

?>

<!-- BEGIN PHP TEMPLATE AJAXROW.TPL.PHP - Script to enable drag and drop on lines of a table -->
<?php
$id = $object->id;
$fk_element = empty($object->fk_element) ? $fk_element : $object->fk_element;
$table_element_line = (empty($table_element_line) ? $object->table_element_line : $table_element_line);
$nboflines = (isset($object->lines) ? count($object->lines) : (isset($tasksarray) ? count($tasksarray) : (empty($nboflines) ? 0 : $nboflines)));
$forcereloadpage = getDolGlobalInt('MAIN_FORCE_RELOAD_PAGE');
$tagidfortablednd = (empty($tagidfortablednd) ? 'tablelines' : $tagidfortablednd);
$filepath = (empty($filepath) ? '' : $filepath);

if (GETPOST('action', 'aZ09') != 'editline' && $nboflines > 1 && $conf->browser->layout != 'phone') { ?>
<script>
$(document).ready(function(){
	$(".imgupforline").hide();
	$(".imgdownforline").hide();
	$(".lineupdown").removeAttr('href');
	$(".tdlineupdown").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
	$(".tdlineupdown").css("background-repeat","no-repeat");
	$(".tdlineupdown").css("background-position","center center");

	console.log("Prepare tableDnd for #<?php echo $tagidfortablednd; ?>");
	$("#<?php echo $tagidfortablednd; ?>").tableDnD({
		onDrop: function(table, row) {
			var page_y = jQuery(document).scrollTop();
			var reloadpage = "<?php echo $forcereloadpage; ?>";
			console.log("tableDND onDrop");
			console.log(decodeURI($("#<?php echo $tagidfortablednd; ?>").tableDnDSerialize()));
			$('#<?php echo $tagidfortablednd; ?> tr[data-element=extrafield]').attr('id', '');	// Set extrafields id to empty value in order to ignore them in tableDnDSerialize function
			$('#<?php echo $tagidfortablednd; ?> tr[data-ignoreidfordnd=1]').attr('id', '');	// Set id to empty value in order to ignore them in tableDnDSerialize function
			var roworder = cleanSerialize(decodeURI($("#<?php echo $tagidfortablednd; ?>").tableDnDSerialize()));
			var table_element_line = "<?php echo $table_element_line; ?>";
			var fk_element = "<?php echo $fk_element; ?>";
			var element_id = "<?php echo $id; ?>";
			var filepath = "<?php echo urlencode($filepath); ?>";
			var token = "<?php echo currentToken(); ?>";	// We use old 'token' and not 'newtoken' for Ajax call because the ajax page has the NOTOKENRENEWAL constant set.
			$.post("<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php",
					{
						roworder: roworder,
						table_element_line: table_element_line,
						fk_element: fk_element,
						element_id: element_id,
						filepath: filepath,
						token: token
					},
					function() {
						console.log("tableDND end of ajax call, reloadpage = " + reloadpage);
						if (reloadpage == 1) {
							<?php
							$redirectURL = empty($urltorefreshaftermove) ? ($_SERVER['PHP_SELF'].'?'.dol_escape_js($_SERVER['QUERY_STRING'])) : $urltorefreshaftermove;
							// remove some parameters from URL
							$redirectURL = preg_replace('/(&|\?)action=[^&#]*/', '', $redirectURL);
							$redirectURL = preg_replace('/(&|\?)page_y=[^&#]*/', '', $redirectURL);
							?>
							location.href = '<?php echo dol_escape_js($redirectURL); ?>&page_y='+page_y;
						} else {
							$("#<?php echo $tagidfortablednd; ?> .drag").each(
									function( intIndex ) {
										// $(this).removeClass("pair impair");
										//if (intIndex % 2 == 0) $(this).addClass('impair');
										//if (intIndex % 2 == 1) $(this).addClass('pair');
									});
						}
					});
		},
		onDragClass: "dragClass",
		dragHandle: "td.tdlineupdown"
	});
	$(".tdlineupdown").hover( function() { $(this).addClass('showDragHandle'); },
		function() { $(this).removeClass('showDragHandle'); }
	);
});
</script>
<?php } else { ?>
<script>
$(document).ready(function(){
	$(".imgupforline").hide();
	$(".imgdownforline").hide();
	$(".lineupdown").removeAttr('href');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE AJAXROW.TPL.PHP -->
