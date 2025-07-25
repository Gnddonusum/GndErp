<?php if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
} ?>

/* IDE Hack <style type="text/css"> */

.mainmenu::before, .mainmenu span::before {
	font-weight: 900;
	font-style: normal;
	font-variant: normal;
	text-rendering: auto;
	line-height: 26px;
	font-size: 1.3em;
	-webkit-font-smoothing: antialiased;
	text-align:center;
	text-decoration:none;
}
.mainmenu:not(.fab)::before, .mainmenu:not(.fab) span:not(.fab)::before {
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
}

div.mainmenu.menu {
	background-image: none;
}

/*
div.mainmenu.menu::before {
	content: "\f0c9";
}
*/

div.mainmenu.generic1::before {
	content: "\f249";
}

div.mainmenu.generic2::before {
	content: "\f249";
}

div.mainmenu.generic3::before {
	content: "\f249";
}

div.mainmenu.generic4::before {
	content: "\f249";
}

/* Define color of some picto */

.fa-phone, .fa-mobile-alt, .fa-fax {
	opacity: 0.7;
	color: #440;
}
.fa-at, .fa-external-link-alt, .fa-share-alt {
	opacity: 0.7;
	color: #304;
}
.fa-trash {
	color: #666;
}
.fa-trash:hover:before {
	color: #800;
}
.fa-play {
	color: #444;
}
.fa-link, .fa-unlink {
	color: #555;
}
.fa-project-diagram:before {
	font-size: 0.9em;
}

/* Define square Dolibarr logo in pure CSS */

.fa-dolibarr-css{
	color: #235481;
	background: currentColor;
	height: 150px;
	width: 150px;
	position: relative;
}
.fa-dolibarr-css:before{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 25%;
	height: 25%;
	border: solid 30px white;
	border-radius: 0% 200% 200% 0% / 0% 180% 180% 0%;
}
.fa-dolibarr-css:after{
	content: '';
	position: absolute;
	left: 19%;
	top: 17%;
	width: 5px;
	height: 25%;
	border-bottom: solid 60px currentColor;
	margin-left: 30px;
}

.tmenu span.fas, .tmenu span.far {
	<?php
	if (!getDolGlobalString('THEME_MENU_COLORLOGO')) {
		echo "color: unset !important;";
	}
	?>;
	line-height: 28px;
	text-align: center;
}


.em092 {
	font-size: 0.92em;
}

.em088 {
	font-size: 0.88em;
}

.em080 {
	font-size: 0.80em;
}
