<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}
include_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";

/**
 * @var string $colorbackhmenu1
 * @var string $colorbacklinepair1
 * @var string $colortextbackhmenu
 * @var string $colortextlink
 * @var int<0,1> $disableimages
 * @var string $left
 * @var string $right
 */
'
@phan-var-force string $colorbackhmenu1
@phan-var-force string $colorbacklinepair1
@phan-var-force string $colortextbackhmenu
@phan-var-force string $colortextlink
@phan-var-force int<0,1> $disableimages
@phan-var-force string $left
@phan-var-force string $right
';

$borderradius = getDolGlobalString('THEME_ELDY_USEBORDERONTABLE') ? getDolGlobalInt('THEME_ELDY_BORDER_RADIUS', 6) : 0;
$WIDTHMENUDROPDOWN = 370;
?>

/* IDE Hack <style type="text/css"> */

/*
 * Dropdown of user popup
 */

.bookmark-footer a.top-menu-dropdown-link {
	white-space: normal;
	word-break: break-word;
}

button.dropdown-item.global-search-item {
	outline: none;
}

.open>.dropdown-search, .open>.dropdown-bookmark, .open>.dropdown-quickadd, .open>.dropdown-menu, .dropdown dd ul.open {
	display: block;
}

#topmenu-bookmark-dropdown .dropdown-menu {
	min-width: <?php echo $WIDTHMENUDROPDOWN; ?>px;
	max-width: <?php echo $WIDTHMENUDROPDOWN; ?>px;
	width: <?php echo $WIDTHMENUDROPDOWN; ?>px;
}


.dropdown-search {
	border-color: #eee;

	position: absolute;
	top: 100%;
	left: 0;
	z-index: 1000;
	display: none;
	float: left;
	min-width: 160px;
	margin: 2px 0 0;
	font-size: 14px;
	text-align: left;
	list-style: none;
	background-color: #fff;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	border: 1px solid #ccc;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: 4px;
	box-shadow: 0 6px 12px rgba(0,0,0,.175);
}
.dropdown-bookmark {
	border-color: #eee;

	position: absolute;
	top: 100%;
	left: 0;
	z-index: 1000;
	display: none;
	float: left;
	min-width: 160px;
	margin: 2px 0 0;
	font-size: 14px;
	text-align: left;
	list-style: none;
	background-color: #fff;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	border: 1px solid #ccc;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: 4px;
	box-shadow: 0 6px 12px rgba(0,0,0,.175);
}
.dropdown-quickadd {
	border-color: #eee;

	position: absolute;
	top: 100%;
	left: 0;
	z-index: 1000;
	display: none;
	float: left;
	min-width: 240px;
	margin: 2px 0 0;
	font-size: 14px;
	text-align: left;
	list-style: none;
	background-color: #fff;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	border: 1px solid #ccc;
	border: 1px solid rgba(0,0,0,.15);
	border-radius: 4px;
	box-shadow: 0 6px 12px rgba(0,0,0,.175);
}
.dropdown-menu {
	position: absolute;
	top: 100%;
	left: 0;
	z-index: 1000;
	display: none;
	float: left;
	min-width: 160px;
	margin: 2px 0 0;
	font-size: 14px;
	text-align: <?php echo $left; ?>;
	list-style: none;
	background-color: #fff;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	border: 1px solid rgba(128, 128, 128, .15);
	border-radius: 4px;
	box-shadow: 0 6px 12px rgba(0,0,0,.175);
}


.dropdown-toggle{
	text-decoration: none !important;
}


/* CSS to hide the arrow to show open/close */
div#topmenu-global-search-dropdown a::after, div#topmenu-quickadd-dropdown a::after, div#topmenu-bookmark-dropdown a::after {
	display: none;
}


.dropdown-toggle::after {
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
	font-size: 0.7em;
	font-weight: 900;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	-webkit-font-smoothing: antialiased;
	text-align:center;
	text-decoration:none;
	margin:  auto 3px;
	display: inline-block;
	content: "\f078";

	-webkit-transition: -webkit-transform .2s ease-in-out;
	-ms-transition: -ms-transform .2s ease-in-out;
	transition: transform .2s ease-in-out;
}


.open>.dropdown-toggle::after {
	transform: rotate(180deg);
}

/*
 * MENU Dropdown
 */

.login_block.usedropdown .logout-btn{
	display: none;
}

.tmenu .open.dropdown, .tmenu .open.dropdown {
	background: rgba(0, 0, 0, 0.1);
}
.tmenu .dropdown-menu, .login_block .dropdown-menu, .topnav .dropdown-menu {
	position: absolute;
	right: 1px;
	<?php echo $left; ?>: auto;
	line-height:1.3em;
}
.tmenu .dropdown-menu, .login_block  .dropdown-menu .user-body {
	border-bottom-right-radius: 4px;
	border-bottom-left-radius: 4px;
}
.user-body {
	color: #333;
}
.side-nav-vert .user-menu .dropdown-menu, .topnav .user-menu .dropdown-menu {
	border-top-right-radius: 0;
	border-top-left-radius: 0;
	padding: 0 0 0 0;
	border-top-width: 0;
	width: <?php echo $WIDTHMENUDROPDOWN; ?>px;
}
.topnav .user-menu .dropdown-menu {
	top: 50px;
}
.side-nav-vert .user-menu .dropdown-menu, .topnav .user-menu .dropdown-menu {
	margin-top: 0;
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

.side-nav-vert .user-menu .dropdown-menu > .user-header, .topnav .user-menu .dropdown-menu > .user-header {
	min-height: 100px;
	padding: 10px;
	text-align: center;
	white-space: normal;
}

#topmenu-global-search-dropdown .dropdown-menu{
	width: 310px;
	max-width: 100%;
}

div#topmenu-global-search-dropdown, div#topmenu-bookmark-dropdown, div#topmenu-quickadd-dropdown {
	<?php if (!$disableimages) { ?>
		line-height: 46px;
	<?php } ?>
}
a.top-menu-dropdown-link {
	padding: 8px;
}

.dropdown-user-image {
	border-radius: 50%;
	vertical-align: middle;
	z-index: 5;
	height: 90px;
	width: 90px;
	border: 3px solid;
	border-color: transparent;
	border-color: rgba(255, 255, 255, 0.2);
	max-width: 100%;
	max-height :100%;
}

.dropdown-menu > .user-header{
	/* background: var(--colorbackhmenu1);
	color: var(--colortextbackhmenu); */
	background: #f9f9f9;
	color: #000;
}

.dropdown-menu .dropdown-header{
	padding: 8px 12px 8px 16px;
}

.dropdown-menu > .user-footer {
	border-top: 1px solid #f0f0f0;
	background-color: #f9f9f9;
	padding: 10px;
}

.user-footer:after {
	clear: both;
}

.dropdown-menu > .bookmark-footer {
	border-top: 1px solid #f0f0f0;
	background-color: #f9f9f9;
	padding: 10px;
	text-align: start;
}


.dropdown-menu > .user-body, .dropdown-body {
	padding: 15px;
	border-bottom: 1px solid #f4f4f4;
	border-top: 1px solid #f0f0f0;
	white-space: normal;
}

.dropdown-menu > .bookmark-body, .dropdown-body {
	overflow-y: auto;
	max-height: 60vh ; /* fallback for browsers without support for calc() */
	max-height: calc(90vh - 110px) ;
	white-space: normal;
}
#topmenu-quickadd-dropdown .dropdown-menu > .bookmark-body, #topmenu-quickadd-dropdown .dropdown-body,
#topmenu-bookmark-dropdown .dropdown-menu > .bookmark-body, #topmenu-bookmark-dropdown .dropdown-body {
	max-height: 60vh ; /* fallback for browsers without support for calc() */
	max-height: calc(90vh - 200px) ;
}


.dropdown-body::-webkit-scrollbar {
		width: 8px;
	}
.dropdown-body::-webkit-scrollbar-thumb {
	-webkit-border-radius: 0;
	border-radius: 0;
	/* background: rgb(<?php echo $colorbackhmenu1 ?>); */
	background: #aaa;
}
.dropdown-body::-webkit-scrollbar-track {
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
	-webkit-border-radius: 0;
	border-radius: 0;
}


#topmenu-global-search-dropdown,
#topmenu-quickadd-dropdown,
#topmenu-bookmark-dropdown,
#topmenu-uploadfile-dropdown,
#topmenu-login-dropdown {
	padding: 0 5px 0 5px;
}
#topmenu-login-dropdown a:hover{
	text-decoration: none;
}

#topmenuloginmoreinfo-btn, #topmenulogincompanyinfo-btn {
	display: block;
	text-align: start;
	color:#666;
	cursor: pointer;
}

#topmenuloginmoreinfo, #topmenulogincompanyinfo {
	display: none;
	clear: both;
	font-size: 0.95em;
}

a.dropdown-item {
	text-align: start;
}

.button-top-menu-dropdown {
	display: inline-block;
	padding: 6px 12px;
	margin-bottom: 0;
	font-size: 14px;
	font-weight: 400;
	line-height: 1.42857143;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	-ms-touch-action: manipulation;
	touch-action: manipulation;
	cursor: pointer;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	background-image: none;
	border: 1px solid transparent;
	border-radius: 3px;
}

.user-footer .button-top-menu-dropdown {
	color: #666666;
	box-shadow: none;
	border-width: 1px;
	background-color: #f4f4f4;
	border-color: #ddd;
}

.dropdown-menu a.top-menu-dropdown-link {
	color: rgb(<?php print $colortextlink; ?>) !important;
	box-shadow: none;
	display: block;
	margin: 5px 0px;
}

.dropdown-item {
	display: block !important;
	box-sizing: border-box;
	width: 100%;
	padding: .5em 1.5em .5em 1em;
	clear: both;
	font-weight: 400;
	color: #212529  !important;
	text-align: inherit;
	background-color: transparent;
	border: 0;
	box-shadow: none;
}
.dropdown-item.bookmark-item {
	padding-left: 0;
	padding-right: 0;
}
.dropdown-item.bookmark-item:before {
	width: 20px;
	padding-left: 2px;
}


.dropdown-item::before {
	/* font part */
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
	font-weight: 900;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	-webkit-font-smoothing: antialiased;
	text-align:center;
	text-decoration:none;
	margin-<?php echo $right; ?>: 5px;
	display: inline-block;
	content: "\f0da";
	/* color: rgba(0,0,0,0.3); */
}
.multicompany-item::before {
	content: none !important;
}

.dropdown-item.bookmark-item-external::before {
	content: "\f35d";
}

.dropdown-item.active, .dropdown-item:hover, .dropdown-item:hover span::before, .dropdown-item:focus, .dropdown-item:focus span::before {
	color: #<?php echo $colortextbackhmenu; ?> !important;
	text-decoration: none;
	background: rgb(<?php echo $colorbackhmenu1 ?>);
}


/*
 * SELECT FIELDS
 */

li.liinputsearch {
	position: sticky;
	display: block;
	top: 0;
	background: var(--colorbackbody);
	z-index: 1;
}



/*
 * QUICK ADD
 */

#topmenu-quickadd-dropdown .dropdown-menu {
	width: <?php echo $WIDTHMENUDROPDOWN; ?>px;
	color: #444;
}

.quickadd-body.dropdown-body {
	padding: unset;
	padding-top: 10px;
	padding-bottom: 10px;
}

.quickadd-item {
	font-size: 1.1em;
}

.quickadd-item:before {
	content: none;
}

.quickadd-header {
	color: #444 !important;
}

div.quickadd {
	display: -ms-flexbox;
	display: -webkit-flex;
	display: flex;
	-webkit-flex-direction: row;
	-ms-flex-direction: row;
	flex-direction: row;
	-webkit-flex-wrap: wrap;
	-ms-flex-wrap: wrap;
	flex-wrap: wrap;
	-webkit-justify-content: center;
	-ms-flex-pack: center;
	justify-content: center;
	-webkit-align-content: center;
	-ms-flex-line-pack: center;
	align-content: center;
	-webkit-align-items: flex-start;
	-ms-flex-align: start;
	align-items: flex-start;
}

div.quickadd a {
	color: #444;
}

div.quickadd a:hover, div.quickadd a:active {
	color: #000000;
}

div.quickaddblock {
	width: 95px;
	height: 80px;
}

div.quickaddblock:hover,
div.quickaddblock:active,
div.quickaddblock:focus {
	background: <?php print "#".colorArrayToHex(colorStringToArray($colorbacklinepair1)); ?>;
}


/* for the dropdown on action buttons */
.dropdown-holder {
	position: relative;
	display: inline-block;
}

.dropdown-content {
	display: none;
	position: absolute;
	z-index: 5;
	width: 300px;
	right:0;	/* will be set with js */
	bottom: 0;
	transform: translateY(100%);

	background: #fff;
	border: 1px solid #bbb;
	text-align: <?php echo $left; ?>;
	box-shadow: 5px 5px 0px rgba(0,0,0,0.1);
}

/* dropdown --up variant */
.dropdown-holder.--up .dropdown-content{
	bottom: auto;
	top: 0;
	transform: translateY(-100%);
}

/* dropdown --left variant */
.dropdown-holder.--left .dropdown-content{
	right: auto;
	left: 12px;
}


.dropdown-content a {
	margin-right: auto !important;
	margin-left: auto !important;
}
.dropdown-content .butAction {
	background: none;
	color: #333 !important;
}
.dropdown-content a:is(.butAction,.butActionDelete,.butActionRefused) {
	display: flex;
	border-radius: 0;
}

.dropdown-content .butAction:hover {
	box-shadow: none;
	background-color: var(--butactionbg);
	color: var(--textbutaction) !important;
	text-decoration: none;
}

.dropdown-content .butActionDelete{
	background-color: transparent !important;
	color: #633 !important;
}
.dropdown-content .butActionDelete:hover {
	box-shadow: none;
	background-color: var(--butactiondeletebg) !important;
	color: #633 !important;
	text-decoration: none;
}

.dropdown-content .butActionRefused {
	margin-left: 0;
	margin-right: 0;
	border: none;
}

.dropdown-holder.open .dropdown-content {
	display: block;
}

/** dropdown arrow used to clearly identify parent button of dropdown*/
.dropdown-holder.open .dropdown-content::before {
	--triangleBorderSize : 5px;
	position: absolute;
	content: "";
	top: calc(var(--triangleBorderSize) * -1);
	right: 12px;
	width: 0px;
	height: 0px;
	border-style: solid;
	border-width: 0 var(--triangleBorderSize) var(--triangleBorderSize) var(--triangleBorderSize);
	border-color: transparent transparent #ffff transparent;
	transform: rotate(0deg);
}

/* dropdown --up variant*/
.dropdown-holder.--up.open .dropdown-content::before{
	top: auto;
	bottom: calc(var(--triangleBorderSize) * -1);
	border-width: 0 var(--triangleBorderSize) var(--triangleBorderSize) var(--triangleBorderSize);
	transform: rotate(180deg);
}

/* dropdown --left variant*/
.dropdown-holder.--left.open .dropdown-content::before{
	right: auto;
	left: 12px;
}

.dropdown-search-input {
	border-radius: <?php print $borderradius; ?>px;
}

/* smartphone */
@media only screen and (max-width: 767px)
{
	.dropdown-search-input,.search-tool-input {
		width: 100%;
	}

	.tmenu .dropdown-menu, .login_block .dropdown-menu, .topnav .dropdown-menu {
		margin-left: 8px;
		right: 0;
	}

	#topmenu-bookmark-dropdown .dropdown-menu, #topmenu-quickadd-dropdown .dropdown-menu {
		min-width: 220px;
		max-width: 360px;
	}

	.side-nav-vert .user-menu .dropdown-menu, .topnav .user-menu .dropdown-menu {
		width: 300px;
	}
	.dropdown-menu:not(.ai_dropdown) {
		border: none;
		box-shadow: none;
		border-bottom: 1px solid #888;
	}

}
