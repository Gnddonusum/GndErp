<?php
/* Copyright (C) 2024		MDW	<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 */
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}

/**
 * @var Conf $conf
 */

// Expected to be defined by including parent
'
@phan-var-force string $right
@phan-var-force string $left
';

$borderradius = getDolGlobalString('THEME_ELDY_USEBORDERONTABLE') ? getDolGlobalInt('THEME_ELDY_BORDER_RADIUS', 6) : 0;

?>

/* IDE Hack <style type="text/css"> */


/*
 * Component: Info Box
 * -------------------
 */

<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$prefix = '';
if (getDolGlobalString('THEME_INFOBOX_COLOR_ON_BACKGROUND')) {
	$prefix = 'background-';
}

if (!isset($conf->global->THEME_SATURATE_RATIO)) {
	$conf->global->THEME_SATURATE_RATIO = 0.8;
}
if (GETPOSTISSET('THEME_SATURATE_RATIO')) {
	$conf->global->THEME_SATURATE_RATIO = GETPOSTFLOAT('THEME_SATURATE_RATIO');
}

?>

.nonature-back {
	background-color: #EEE;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.prospect-back {
	background-color: #a7c5b0 !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.customer-back {
	background-color: #65953d !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.vendor-back {
	background-color: #599caf !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.user-back {
	background-color: #79633f !important;
	color: #FFF !important;
	padding: 2px;
	margin: 2px;
	border-radius: 3px;
}
.member-company-back {
	padding: 2px;
	margin: 2px;
	background-color: #e4e4e4;
	color: #666;
	border-radius: 3px;
	white-space: nowrap;
}
.member-individual-back {
	padding: 2px;
	margin: 2px;
	background-color: #e4e4e4;
	color: #666;
	border-radius: 3px;
	white-space: nowrap;
}

.bg-infobox-project{
	<?php echo $prefix; ?>color: #6c6aa8 !important;
}
.bg-infobox-action{
	<?php echo $prefix; ?>color: #a47080  !important;
}
.bg-infobox-propal, .bg-infobox-facture, .bg-infobox-commande {
	<?php echo $prefix; ?>color: #65953d !important;
}
.bg-infobox-supplier_proposal, .bg-infobox-invoice_supplier, .bg-infobox-order_supplier {
	<?php echo $prefix; ?>color: #599caf !important;
}
.bg-infobox-contrat, .bg-infobox-ticket{
	<?php echo $prefix; ?>color: #46a676  !important;
}
.bg-infobox-bank_account{
	<?php echo $prefix; ?>color: #b0a53e  !important;
}
.bg-infobox-adherent, .bg-infobox-member{
	<?php echo $prefix; ?>color: #79633f  !important;
}
.bg-infobox-expensereport{
	<?php echo $prefix; ?>color: #79633f  !important;
}
.bg-infobox-holiday{
	<?php echo $prefix; ?>color: #755114  !important;
}
.bg-infobox-cubes{
	<?php echo $prefix; ?>color: #b0a53e  !important;
}

/* Disable colors on left vmenu */
a.vmenu span, span.vmenu, span.vmenu span {
	/* To force no color on picto in left menu */
	/* color: var(--colortextbackvmenu) !important; */
}
div.login_block_other:not(.takepos) a {
	color: var(--colortextbackvmenu);
}

.infobox-adherent, .infobox-member {
	color: #79633f;
}
.infobox-project{
	color: #6c6aa8;
}
.infobox-action{
	color: #a47080;
}
/* Color for customer object */
.infobox-propal:not(.pictotitle):not(.error),
.infobox-facture:not(.pictotitle):not(.error),
.infobox-commande:not(.pictotitle):not(.error) {
	color: #65953d;
}
/* Color for vendor object */
.infobox-supplier_proposal:not(.pictotitle):not(.error),
.infobox-invoice_supplier:not(.pictotitle):not(.error),
.infobox-order_supplier:not(.pictotitle):not(.error) {
	color: #599caf;
}

.infobox-contrat, .infobox-ticket{
	color: #46a676;
}
.infobox-bank_account{
	color: #b0a53e;
}
.infobox-adherent, .infobox-member {
	color: #79633f;
}
.infobox-expensereport{
	color: #79633f;
}
.infobox-holiday{
	color: #755114;
}


.info-box-module.--external span.info-box-icon-version {
	background: #bbb;
}

a.info-box-text.info-box-text-a {
	/* display: table-cell; */
	display: contents;
}
a.info-box-text-a i.fa.fa-exclamation-triangle {
	font-size: 0.9em;
}

.info-box {
	display: block;
	position: relative;
	min-height: 94px;
	background: var(--colorbacklineimpair2);
	width: 100%;
	/* box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); */
	border-radius: 2px;
	margin-bottom: 15px;
	border: 1px solid #e9e9e9;
	border-radius: <?php print $borderradius; ?>px;
}
.info-box.info-box-sm {
	min-height: 80px;
	margin-bottom: 10px;
}
.info-box-more {
	float: right;
	top: 4px;
	position: absolute;
	right: 10px;
}

.info-box small {
	font-size: 14px;
}
.info-box:not(.info-box-kanban) .progress {
	background: rgba(0, 0, 0, 0.2);
	margin: 5px -10px 5px -10px;
	height: 2px;
}
.info-box .progress,
.info-box .progress .progress-bar {
	border-radius: 0;
}

.info-box:not(.info-box-kanban) .progress .progress-bar {
		float: left;
		width: 0;
		height: 100%;
		font-size: 12px;
		line-height: 20px;
		color: #fff;
		text-align: center;
		background-color: #337ab7;
		box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
		-webkit-transition: width .6s ease;
		-o-transition: width .6s ease;
		transition: width .6s ease;
}
.info-box-icon {
	display: block;
	overflow: hidden;
	float: left;
	text-align: center;
	font-size: 45px;
	line-height: 94px;	/* must be same height as min-height of .info-box */
	height: 94px;		/* must be same height as min-height of .info-box */
	width: 86px;
	background: var(--colorbacktitle1) !important;
	border-top-left-radius: <?php print $borderradius; ?>px;
	border-bottom-left-radius: <?php print $borderradius; ?>px;
	<?php if (getDolGlobalString('THEME_SATURATE_RATIO')) { ?>
		filter: saturate(<?php echo getDolGlobalString('THEME_SATURATE_RATIO'); ?>);
	<?php } ?>
}

.info-box-module .info-box-icon {
	padding-top: 4px;
	padding-bottom: 4px;
}
.info-box-sm .info-box-icon, .info-box-sm .info-box-img {
	height: 96px !important;		/* must match height of info-box-sm .info-box-content */
	width: 80px;
	font-size: 25px;
	line-height: 92px;
}
.info-box-order {
	border-top-left-radius: 2px;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 2px;
	display: block;
	overflow: hidden;
	float: left;
	height: 115px;
	width: 88px;
	text-align: center;
	font-size: 2.3em;
	line-height: 115px;
	margin-right: 10px;
	background: var(--colorbacktitle1) !important;
}
.opened-dash-board-wrap .info-box .info-box-icon {
	font-size: 2em;
}
.opened-dash-board-wrap .info-box-sm .info-box-icon {
	line-height: 80px;
}
.info-box-module .info-box-icon {
	height: 98px;
}
.info-box-icon > img {
	max-width: 85%;
}
.info-box-module .info-box-icon > img {
	max-width: 55%;
}

.info-box-line {
	line-height: 1.2em;
}
.info-box-line-text {
	overflow: hidden;
	width: calc(100% - 76px);
	text-overflow: ellipsis;
}

.info-box-icon-text {
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 90px;
	bottom: 0px;
	color: #ffffff;
	background-color: rgba(0,0,0,0.1);
	cursor: default;

	font-size: 10px;
	line-height: 15px;
	padding: 0px 3px;
	text-align: center;
	opacity: 0;
	-webkit-transition: opacity 0.5s, visibility 0s 0.5s;
	transition: opacity 0.5s, visibility 0s 0.5s;
}

.info-box-icon-version {
	box-sizing: border-box;
	display: block;
	position: absolute;
	width: 90px;
	bottom: 0px;
	color: #ffffff;
	background-color: rgba(0,0,0,0.1);
	cursor: default;

	font-size: 10px;
	line-height: 1.5em;
	padding: 4px 3px;
	text-align: center;
	opacity: 1;
	-webkit-transition: opacity 0.5s, visibility 0s 0.5s;
	transition: opacity 0.5s, visibility 0s 0.5s;
}

.box-flex-item.info-box-module.--disabled {
	/* opacity: 0.6; */
}

.info-box-actions {
	position: absolute;
	right: 0;
	bottom: 0;
}

/* customize section img box on list of products */
.info-box-img {
	height: 105px !important;
	width: 88px;
	border-top-left-radius: 2px;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 2px;
	display: block;
	overflow: hidden;
	float: left;
	text-align: center;
	font-size: 2.8em;
	line-height: 90px;
	margin-right: 5px;
	background: var(--colorbacktitle1) !important;
}
.info-box-img > img {
	width: 90%;
	position: relative;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
}


<?php if (!getDolGlobalString('MAIN_DISABLE_GLOBAL_BOXSTATS') && getDolGlobalString('MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD')) { ?>
.info-box-icon-text{
	opacity: 1;
}
<?php } ?>

.info-box-sm .info-box-icon-text, .info-box-sm .info-box-icon-version{
	overflow: hidden;
	width: 80px;
}
.info-box:hover .info-box-icon-text{
	opacity: 1;
}

.info-box-content {
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 10px;
	padding-right: 5px;
	margin-left: 84px;
}
.info-box-sm .info-box-content {
	margin-left: 80px;
	height: 86px;   /* 96 - margins of .info-box-sm .info-box-content */
}
.info-box-sm .info-box-module-enabled {
	/* background: linear-gradient(0.35turn, #fff, #fff, #f6faf8, #e4efe8) */
	background: var(--infoboxmoduleenabledbgcolor);
	border-radius: 6px;
}
.info-box-content-warning span.font-status4 {
	color: #bc9526 !important;
}

.info-box-number {
	display: block;
	font-weight: bold;
	font-size: 18px;
}
.progress-description,
.info-box-text,
.info-box-title{
	display: block;
	font-size: 12px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.info-box-title{
	text-transform: uppercase;
	font-weight: bold;
	margin-bottom: 6px;
}
.info-box-title {
	width: calc(100% - 20px);
}
.info-box-text{
	font-size: 0.90em;
}
/* Force values for small screen 480 */
/*
@media only screen and (max-width: 480px)
{
	.info-box-text {
		font-size: 0.85em;
	}
}
*/
.info-box-text:first-letter{text-transform: uppercase}
a.info-box-text{ text-decoration: none;}


.info-box-more {
	display: block;
}
.progress-description {
	margin: 0;
}



/* ICONS INFO BOX */
<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$prefix = '';
if (getDolGlobalString('THEME_INFOBOX_COLOR_ON_BACKGROUND')) {
	$prefix = 'background-';
}

if (!isset($conf->global->THEME_SATURATE_RATIO)) {
	$conf->global->THEME_SATURATE_RATIO = 0.8;
}
if (GETPOSTISSET('THEME_SATURATE_RATIO')) {
	$conf->global->THEME_SATURATE_RATIO = GETPOSTFLOAT('THEME_SATURATE_RATIO');
}
?>
.bg-infobox-project i.fa{
	color: #605ca8 !important;
}
.bg-infobox-action i.fa{
	color: #d84b80  !important;
}
.bg-infobox-propal i.fa,
.bg-infobox-facture i.fa,
.bg-infobox-commande i.fa{
	color: #abb87b  !important;
}
.bg-infobox-supplier_proposal i.fa,
.bg-infobox-invoice_supplier i.fa,
.bg-infobox-order_supplier i.fa{
	color: #40b0cf  !important;
}
.bg-infobox-contrat i.fa, .bg-infobox-ticket i.fa{
	color: #20a68a  !important;
}
.bg-infobox-bank_account i.fa{
	color: #b0a53e  !important;
}
.bg-infobox-adherent i.fa, .bg-infobox-member i.fa{
	color: #755114  !important;
}
.bg-infobox-expensereport i.fa{
	color: #755114  !important;
}
.bg-infobox-holiday i.fa{
	color: #755114  !important;
}
.bg-infobox-cubes i.fa{
	color: #b0a53e  !important;
}


.fa-dol-action:before {
	content: "\f073";
}
.fa-dol-propal:before,
.fa-dol-supplier_proposal:before {
	content: "\f573";
}
.fa-dol-facture:before,
.fa-dol-invoice_supplier:before {
	content: "\f571";
}
.fa-dol-project:before {
	content: "\f542";
}
.fa-dol-commande:before,
.fa-dol-order_supplier:before {
	content: "\f570";
}
.fa-dol-contrat:before {
	content: "\f1e6";
}
.fa-dol-ticket:before {
	content: "\f3ff";
}
.fa-dol-bank_account:before {
	content: "\f19c";
}
.fa-dol-member:before {
	content: "\f0c0";
}
.fa-dol-expensereport:before {
	content: "\f555";
}
.fa-dol-holiday:before {
	content: "\f5ca";
}
.fa-dol-cubes:before {
	content: "\f1b3";
}


/* USING FONTAWESOME FOR WEATHER */
.info-box-weather .info-box-icon{
	background: var(--colorbacktitle1) !important;
}
.fa-weather-level0:before{
	content: "\f185";
	color : #cfbf00;
}
.fa-weather-level1:before{
	content: "\f6c4";
	color : #bc9526;
}
.fa-weather-level2:before{
	content: "\f743";
	color : #b16000;
}
.fa-weather-level3:before{
	content: "\f740";
	color : #b04000;
}
.fa-weather-level4:before{
	content: "\f0e7";
	color : #b01000;
}





.box-flex-container{
	display: flex; /* or inline-flex */
	flex-direction: row;
	flex-wrap: wrap;
	width: 100%;
	margin: 0 0 0 -10px;
	/* justify-content: space-between; Do not use this: If there is 3 elements on last line and previous has 4, then the 3 are centered */
}
.box-flex-container-columns {
	display: flex; /* or inline-flex */
	flex-direction: row;
	flex-wrap: nowrap;
	justify-content: space-between;
}
.box-flex-container-column {
	flex-grow: 1;
}
.box-flex-container-column:not(:last-of-type) {
	border-right: 1px solid #AAA;
}

.box-flex-container-column.kanban {
	flex: 1;
}
.kanban.kanbancollapsed {
	flex: unset;
	width: 80px;
	max-width: 80px;
	overflow: hidden;
}
.kanban.kanbancollapsed .kanbanlabel, .text-vertical {
	writing-mode: vertical-rl;
}

.box-flex-grow-zero{
	flex-grow: 0 !important;
}

.box-flex-item {
	flex-grow : 1;
	flex-shrink: 1;
	flex-basis: auto;
	width: 300px;
}
.box-flex-item.filler{
	height: 0;
}
.box-flex-item, .kanbanlabel  {
	margin-top: 5px;
	margin-<?php echo $right; ?>: 10px;
	margin-bottom: 0px;
	margin-<?php echo $left; ?>: 10px;
}
.kanbanlabel {
	background: var(--colorbacktitle1);
	padding: 5px;
	margin-bottom: 10px;
	border-radius: 5px;
}
.kanban .box-flex-item {
	line-height: 1.4em;
}
.kanban .box-flex-item-5lines {
	line-height: 1.18em;
}

/* css for small kanban */
.box-flex-item-small {
	width: 200px !important;
}
.box-flex-item-small .info-box-sm .info-box-content {
	margin-left: 0;
}
.box-flex-item-small .info-box-icon.bg-infobox-action {
	display: none;
}


@media only screen and (max-width: 767px)
{
	.box-flex-container {
		margin: 0 0 0 0 !important;
	}
}

.info-box-title {
	width: calc(100% - 20px);
}
.info-box-module {
	min-width: 350px;
	max-width: 350px;
}
.info-box-module .info-box-content {
	height: 94px;
}
.fright {
	float:right;
}

@media only screen and (max-width: 1740px) {
	.info-box-module {
		min-width: 315px;
		max-width: 315px;
	}
}
@media only screen and (max-width: 768px) {
	.info-box-module {
		min-width: 260px;
	}
	.info-box-sm .info-box-icon {
		width: 60px;
	}
	.info-box-sm .info-box-content {
		margin-left: 60px;
	}
	.info-box-content {
		padding-top: 5px;
		padding-bottom: 5px;
		padding-left: 10px;
		padding-right: 2px;
	}
	/*
	.info-box-line-text {
		width: calc(100% - 92px);
		max-width: calc(100% - 82px);
	}
	*/
}

@media only screen and (max-width: 480px) {
	.info-box-module {
		min-width: 250px;
	}
	.box-flex-item {
		width: 250px;
	}
}
