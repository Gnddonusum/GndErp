<?php
/* Copyright (C) 2017 Laurent Destailleur	<eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/core/lib/website.lib.php
 *      \ingroup    website
 *      \brief      Library for website module
 */

/**
 * Remove PHP code part from a string.
 *
 * @param 	string	$str			String to clean
 * @param	string	$replacewith	String to use as replacement
 * @return 	string					Result string without php code
 * @see dolKeepOnlyPhpCode()
 */
function dolStripPhpCode($str, $replacewith = '')
{
	$str = str_replace('<?=', '<?php echo', $str);	// replace a bad practive

	$newstr = '';

	// Split on each opening tag
	//$parts = explode('<?php', $str);
	$parts = preg_split('/'.preg_quote('<?php', '/').'/i', $str);

	if (!empty($parts)) {
		$i = 0;
		foreach ($parts as $part) {
			if ($i == 0) { 	// The first part is never php code
				$i++;
				$newstr .= $part;
				continue;
			}
			// The second part is the php code. We split on closing tag
			$partlings = explode('?>', $part);
			if (!empty($partlings)) {
				//$phppart = $partlings[0];
				//remove content before closing tag
				if (count($partlings) > 1) {
					$partlings[0] = ''; // Todo why a count > 1 and not >= 1 ?
				}
				//append to out string
				//$newstr .= '<span class="phptag" class="tooltip" title="'.dol_escape_htmltag(dolGetFirstLineOfText($phppart).'...').'">'.$replacewith.'<!-- '.$phppart.' --></span>'.implode('', $partlings);
				//$newstr .= '<span>'.$replacewith.'<!-- '.$phppart.' --></span>'.implode('', $partlings);
				$newstr .= '<span phptag>'.$replacewith.'</span>'.implode('', $partlings);
				//$newstr .= $replacewith.implode('', $partlings);
			}
		}
	}
	return $newstr;
}

/**
 * Keep only PHP code part from a HTML string page.
 *
 * @param 	string	$str			String to clean
 * @return 	string					Result string with php code only
 * @see dolStripPhpCode(), checkPHPCode()
 */
function dolKeepOnlyPhpCode($str)
{
	$str = str_replace('<?=', '<?php echo', $str);
	$str = str_replace('<?php', '__LTINTPHP__', $str);
	$str = str_replace('<?', '<?php', $str);			// replace the short_open_tag. It is recommended to set this to Off in php.ini
	$str = str_replace('__LTINTPHP__', '<?php', $str);

	$newstr = '';

	// Split on each opening tag
	//$parts = explode('<?php', $str);
	$parts = preg_split('/'.preg_quote('<?php', '/').'/i', $str);

	if (!empty($parts)) {
		$i = 0;
		foreach ($parts as $part) {
			if ($i == 0) { 	// The first part is never php code
				$i++;
				continue;
			}
			$newstr .= '<?php';
			//split on closing tag
			$partlings = explode('?>', $part, 2);
			if (!empty($partlings)) {
				$newstr .= $partlings[0].'?>';
			} else {
				$newstr .= $part.'?>';
			}
		}
	}
	return $newstr;
}

/**
 * Convert a page content to have correct links (based on DOL_URL_ROOT) into an html content. It replaces also dynamic content with '...php...'
 * Used to output the page on the Preview from backoffice.
 *
 * @param	Website		$website			Web site object
 * @param	string		$content			Content to replace
 * @param	int			$removephppart		0=Replace PHP sections with a PHP badge. 1=Remove completely PHP sections.
 * @param	string		$contenttype		Content type
 * @param	int			$containerid 		Contenair id
 * @return	string							html content
 * @see dolWebsiteOutput() for function used to replace content in a web server context
 */
function dolWebsiteReplacementOfLinks($website, $content, $removephppart = 0, $contenttype = 'html', $containerid = 0)
{
	$nbrep = 0;

	dol_syslog('dolWebsiteReplacementOfLinks start (contenttype='.$contenttype." containerid=".$containerid." USEDOLIBARREDITOR=".(defined('USEDOLIBARREDITOR') ? '1' : '')." USEDOLIBARRSERVER=".(defined('USEDOLIBARRSERVER') ? '1' : '').')', LOG_DEBUG);
	//if ($contenttype == 'html') { print $content;exit; }

	// Replace php code. Note $content may come from database and does not contain body tags.
	$replacewith = '...php...';
	if ($removephppart) {
		$replacewith = '';
	}
	$content = preg_replace('/value="<\?php((?!\?>).)*\?>\n*/ims', 'value="'.$replacewith.'"', $content);

	$replacewith = '"callto=#';
	if ($removephppart) {
		$replacewith = '';
	}
	$content = preg_replace('/"callto:<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith = '"mailto=#';
	if ($removephppart) {
		$replacewith = '';
	}
	$content = preg_replace('/"mailto:<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith = 'src="php';
	if ($removephppart) {
		$replacewith = '';
	}
	$content = preg_replace('/src="<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith = 'href="php';
	if ($removephppart) {
		$replacewith = '';
	}
	$content = preg_replace('/href="<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	//$replacewith='<span class="phptag">...php...</span>';
	$replacewith = '...php...';
	if ($removephppart) {
		$replacewith = '';
	}
	//$content = preg_replace('/<\?php((?!\?toremove>).)*\?toremove>\n*/ims', $replacewith, $content);
	/*if ($content === null) {
		if (preg_last_error() == PREG_JIT_STACKLIMIT_ERROR) $content = 'preg_replace error (when removing php tags) PREG_JIT_STACKLIMIT_ERROR';
	}*/
	$content = dolStripPhpCode($content, $replacewith);

	// Protect the link styles.css.php to any replacement that we make after.
	$content = str_replace('href="styles.css.php', 'href="!~!~!~styles.css.php', $content);
	$content = str_replace('src="javascript.js.php', 'src="!~!~!~javascript.js.php', $content);
	$content = str_replace('href="http', 'href="!~!~!~http', $content);
	$content = str_replace('xlink:href="', 'xlink:href="!~!~!~', $content);
	$content = str_replace('href="//', 'href="!~!~!~//', $content);
	$content = str_replace('src="//', 'src="!~!~!~//', $content);
	$content = str_replace('src="viewimage.php', 'src="!~!~!~/viewimage.php', $content);
	$content = str_replace('src="/viewimage.php', 'src="!~!~!~/viewimage.php', $content);
	$content = str_replace('src="'.DOL_URL_ROOT.'/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
	$content = str_replace('href="document.php', 'href="!~!~!~/document.php', $content);
	$content = str_replace('href="/document.php', 'href="!~!~!~/document.php', $content);
	$content = str_replace('href="'.DOL_URL_ROOT.'/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);

	// Replace relative link '/' with dolibarr URL
	$content = preg_replace('/(href=")\/(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'\2"', $content, -1, $nbrep);
	// Replace relative link /xxx.php#aaa or /xxx.php with dolibarr URL (we discard param ?...)
	$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2\3"', $content, -1, $nbrep);
	// Replace relative link /xxx.php?a=b&c=d#aaa or /xxx.php?a=b&c=d with dolibarr URL
	$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php\?([^#\"<>]*)(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2&\3\4"', $content, -1, $nbrep);

	// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
	$content = preg_replace('/url\((["\']?)\/?medias\//', 'url(\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
	$content = preg_replace('/data-slide-bg=(["\']?)\/?medias\//', 'data-slide-bg=\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	// <img src="medias/...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	// <img src="...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img[^>]*src=")\/?medias\//', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
	// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img[^>]*src=")\/?([^:\"\!]+)\"/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=\2"', $content, -1, $nbrep);
	// <img src="viewimage.php/modulepart=medias&file=image.png" => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png"
	$content = preg_replace('/(<img[^>]*src=")(\/?viewimage\.php)/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content, -1, $nbrep);

	// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
	$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

	// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  ...href="/document.php?modulepart="
	$content = preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
	$content = preg_replace('/(src=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

	// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT:  ...href="/viewimage.php?modulepart="
	$content = preg_replace('/(url\(")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

	// Fix relative URL
	$content = str_replace('src="!~!~!~/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
	$content = str_replace('href="!~!~!~/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);
	// Remove the protection tag !~!~!~
	$content = str_replace('!~!~!~', '', $content);

	dol_syslog('dolWebsiteReplacementOfLinks end', LOG_DEBUG);
	//if ($contenttype == 'html') { print $content;exit; }

	return $content;
}

/**
 * Converts smiley string into the utf8 sequence.
 * @param	string		$content			Content to replace
 * @return	string							Replacement of all smiley strings with their utf8 code
 * @see dolWebsiteOutput()
 */
function dolReplaceSmileyCodeWithUTF8($content)
{
	$map = array(
		":face_with_tears_of_joy:" => "\xF0\x9F\x98\x82",
		":grinning_face_with_smiling_eyes:" => "\xF0\x9F\x98\x81",
		":smiling_face_with_open_mouth:" => "\xF0\x9F\x98\x83",
		":smiling_face_with_open_mouth_and_cold_sweat:" => "\xF0\x9F\x98\x85",
		":smiling_face_with_open_mouth_and_tightly_closed_eyes:" => "\xF0\x9F\x98\x86",
		":winking_face:" => "\xF0\x9F\x98\x89",
		":smiling_face_with_smiling_eyes:" => "\xF0\x9F\x98\x8A",
		":face_savouring_delicious_food:" => "\xF0\x9F\x98\x8B",
		":relieved_face:" => "\xF0\x9F\x98\x8C",
		":smiling_face_with_heart_shaped_eyes:" => "\xF0\x9F\x98\x8D",
		":smiling_face_with_sunglasses:" => "\xF0\x9F\x98\x8E",
		":smirking_face:" => "\xF0\x9F\x98\x8F",
		":neutral_face:" => "\xF0\x9F\x98\x90",
		":expressionless_face:" => "\xF0\x9F\x98\x91",
		":unamused_face:" => "\xF0\x9F\x98\x92",
		":face_with_cold_sweat:" => "\xF0\x9F\x98\x93",
		":pensive_face:" => "\xF0\x9F\x98\x94",
		":confused_face:" => "\xF0\x9F\x98\x95",
		":confounded_face:" => "\xF0\x9F\x98\x96",
		":kissing_face:" => "\xF0\x9F\x98\x97",
		":face_throwing_a_kiss:" => "\xF0\x9F\x98\x98",
		":kissing_face_with_smiling_eyes:" => "\xF0\x9F\x98\x99",
		":kissing_face_with_closed_eyes:" => "\xF0\x9F\x98\x9A",
		":face_with_stuck_out_tongue:" => "\xF0\x9F\x98\x9B",
		":face_with_stuck_out_tongue_and_winking_eye:" => "\xF0\x9F\x98\x9C",
		":face_with_stuck_out_tongue_and_tightly_closed_eyes:" => "\xF0\x9F\x98\x9D",
		":disappointed_face:" => "\xF0\x9F\x98\x9E",
		":worried_face:" => "\xF0\x9F\x98\x9F",
		":angry_face:" => "\xF0\x9F\x98\xA0",
		":face_with_symbols_on_mouth:" => "\xF0\x9F\x98\xA1",
	);
	foreach ($map as $key => $value) {
		$content = str_replace($key, $value, $content);
	}
	return $content;
}


/**
 * Render a string of an HTML content and output it.
 * Used to output the page when viewed from a server (Dolibarr or Apache).
 *
 * @param   string  $content    	Content string
 * @param	string	$contenttype	Content type
 * @param	int		$containerid 	Contenair id
 * @return  void
 * @see	dolWebsiteReplacementOfLinks()  for function used to replace content in the backoffice context.
 */
function dolWebsiteOutput($content, $contenttype = 'html', $containerid = 0)
{
	global $db, $langs, $conf, $user;
	global $dolibarr_main_url_root, $dolibarr_main_data_root;
	global $website;
	global $includehtmlcontentopened;	// $includehtmlcontentopened is the level of includes (start at 0 for main page, 1 for first level include, ...)
	'@phan-var-force Website $website';

	$nbrep = 0;

	dol_syslog("dolWebsiteOutput start - contenttype=".$contenttype." containerid=".$containerid.(defined('USEDOLIBARREDITOR') ? ' USEDOLIBARREDITOR=1' : '').(defined('USEDOLIBARRSERVER') ? ' USEDOLIBARRSERVER=1' : '').' includehtmlcontentopened='.$includehtmlcontentopened);

	//print $containerid.' '.$content;

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	if (defined('USEDOLIBARREDITOR')) {		// REPLACEMENT OF LINKS When page called from Dolibarr editor
		// We remove the <head> part of content
		if ($contenttype == 'html') {
			$content = preg_replace('/<head>.*<\/head>/ims', '', $content);
			$content = preg_replace('/^.*<body(\s[^>]*)*>/ims', '', $content);
			$content = preg_replace('/<\/body(\s[^>]*)*>.*$/ims', '', $content);
		}
	} elseif (defined('USEDOLIBARRSERVER')) {	// REPLACEMENT OF LINKS When page called from Dolibarr server
		$content = str_replace('<link rel="stylesheet" href="/styles.css', '<link rel="stylesheet" href="styles.css', $content);
		$content = str_replace(' async src="/javascript.js', ' async src="javascript.js', $content);

		// Protect the link styles.css.php to any replacement that we make after.
		$content = str_replace('href="styles.css.php', 'href="!~!~!~styles.css.php', $content);
		$content = str_replace('src="javascript.css.php', 'src="!~!~!~javascript.css.php', $content);
		$content = str_replace('href="http', 'href="!~!~!~http', $content);
		$content = str_replace('xlink:href="', 'xlink:href="!~!~!~', $content);
		$content = str_replace('href="//', 'href="!~!~!~//', $content);
		$content = str_replace('src="//', 'src="!~!~!~//', $content);
		$content = str_replace(array('src="viewimage.php', 'src="/viewimage.php'), 'src="!~!~!~/viewimage.php', $content);
		$content = str_replace('src="'.DOL_URL_ROOT.'/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
		$content = str_replace(array('href="document.php', 'href="/document.php'), 'href="!~!~!~/document.php', $content);
		$content = str_replace('href="'.DOL_URL_ROOT.'/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);

		// Replace relative link / with dolibarr URL:  ...href="/"...
		$content = preg_replace('/(href=")\/\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'"', $content, -1, $nbrep);
		// Replace relative link /xxx.php#aaa or /xxx.php with dolibarr URL:  ...href="....php" (we discard param ?...)
		$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2\3"', $content, -1, $nbrep);
		// Replace relative link /xxx.php?a=b&c=d#aaa or /xxx.php?a=b&c=d with dolibarr URL
		// Warning: we may replace twice if href="..." was inside an include (dolWebsiteOutput called by include and the by final page), that's why
		// at end we replace the '!~!~!~' only if we are in final parent page.
		$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php\?([^#\"<>]*)(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2&\3\4"', $content, -1, $nbrep);
		// Replace occurrence like _service_XXX.php with dolibarr URL
		$content = preg_replace('/([\'"])_service_([^\'"]+)\.php\1/', '\1!~!~!~' . DOL_URL_ROOT . '/public/website/index.php?website=' . $website->ref . '&pageref=_service_\2\1', $content, -1, $nbrep);
		// Replace occurrence like _library_XXX.php with dolibarr URL
		$content = preg_replace('/([\'"])_library_([^\'"]+)\.php\1/', '\1!~!~!~' . DOL_URL_ROOT . '/public/website/index.php?website=' . $website->ref . '&pageref=_library_\2\1', $content, -1, $nbrep);
		// Replace relative link without .php like /xxx#aaa or /xxx with dolibarr URL:  ...href="....php"
		$content = preg_replace('/(href=")\/?([a-zA-Z0-9\-_#]+)(\"|\?)/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2\3', $content, -1, $nbrep);

		// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  href="/document.php?modulepart=" => href="/dolibarr/document.php?modulepart="
		$content = preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(src=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

		// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT: href="/viewimage.php?modulepart=" => href="/dolibarr/viewimage.php?modulepart="
		$content = preg_replace('/(href=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(src=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(url\(")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

		// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
		$content = preg_replace('/url\((["\']?)\/?medias\//', 'url(\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
		$content = preg_replace('/data-slide-bg=(["\']?)\/?medias\//', 'data-slide-bg=\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

		// <img src="medias/...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		// <img src="...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		$content = preg_replace('/(<img[^>]*src=")\/?medias\//', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
		// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		$content = preg_replace('/(<img[^>]*src=")\/?([^:\"\!]+)\"/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=\2"', $content, -1, $nbrep);
		// <img src="viewimage.php/modulepart=medias&file=image.png" => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png"
		$content = preg_replace('/(<img[^>]*src=")(\/?viewimage\.php)/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content, -1, $nbrep);

		// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
		$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

		// Fix relative URL
		$content = str_replace('src="!~!~!~/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
		$content = str_replace('href="!~!~!~/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);

		// Remove the protection tag !~!~!~, but only if this is the parent page and not an include
		if (empty($includehtmlcontentopened)) {
			$content = str_replace('!~!~!~', '', $content);
		}
	} else { // REPLACEMENT OF LINKS When page called from virtual host web server
		$symlinktomediaexists = 1;
		if ($website->virtualhost) {
			$content = preg_replace('/^(<link[^>]*rel="canonical" href=")\//m', '\1'.$website->virtualhost.'/', $content, -1, $nbrep);
		}
		//print 'rrrrrrrrr'.$website->virtualhost.$content;


		// Make a change into HTML code to allow to include images from medias directory correct with direct link for virtual server
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		if (!$symlinktomediaexists) {
			// <img src="image.png... => <img src="medias/image.png...
			$content = preg_replace('/(<img[^>]*src=")\/?image\//', '\1/wrapper.php?modulepart=medias&file=medias/image/', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)\/?image\//', '\1/wrapper.php?modulepart=medias&file=medias/image/', $content, -1, $nbrep);

			$content = preg_replace('/(<script[^>]*src=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(<a[^>]*href=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);

			$content = preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);

			$content = preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)hashp=([^\)]*)(["\']?\))/', '\1/wrapper.php\2hashp\3\4', $content, -1, $nbrep);

			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=mycompany([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=mycompany\3file=\4\5', $content, -1, $nbrep);

			// If some links to documents or viewimage remains, we replace with wrapper
			$content = preg_replace('/(<img[^>]*src=")\/?viewimage\.php/', '\1/wrapper.php', $content, -1, $nbrep);
			$content = preg_replace('/(<a[^>]*href=")\/?documents\.php/', '\1/wrapper.php', $content, -1, $nbrep);
		} else {
			// <img src="image.png... => <img src="medias/image.png...
			$content = preg_replace('/(<img[^>]*src=")\/?image\//', '\1/medias/image/', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)\/?image\//', '\1/medias/image/', $content, -1, $nbrep);

			$content = preg_replace('/(<script[^>]*src=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(<a[^>]*href=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);

			$content = preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1/medias/\4\5', $content, -1, $nbrep);

			$content = preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content = preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)hashp=([^\)]*)(["\']?\))/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);

			$content = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=mycompany([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=mycompany\3file=\4\5', $content, -1, $nbrep);

			// If some links to documents or viewimage remains, we replace with wrapper
			$content = preg_replace('/(<img[^>]*src=")\/?viewimage\.php/', '\1/wrapper.php', $content, -1, $nbrep);
			$content = preg_replace('/(<a[^>]*href=")\/?document\.php/', '\1/wrapper.php', $content, -1, $nbrep);
		}
	}

	if (!defined('USEDOLIBARREDITOR')) {
		$content = str_replace(' contenteditable="true"', ' contenteditable="false"', $content);
	}

	if (getDolGlobalString('WEBSITE_ADD_CSS_TO_BODY')) {
		$content = str_replace('<body id="bodywebsite" class="bodywebsite', '<body id="bodywebsite" class="bodywebsite ' . getDolGlobalString('WEBSITE_ADD_CSS_TO_BODY'), $content);
	}

	$content = dolReplaceSmileyCodeWithUTF8($content);

	dol_syslog("dolWebsiteOutput end");

	print $content;
}

/**
 * Increase the website counter of page access.
 *
 * @param   int		$websiteid			ID of website
 * @param	string	$websitepagetype	Type of page ('blogpost', 'page', ...)
 * @param	int		$websitepageid		ID of page
 * @return  int							Return integer <0 if KO, >0 if OK
 */
function dolWebsiteIncrementCounter($websiteid, $websitepagetype, $websitepageid)
{
	if (!getDolGlobalInt('WEBSITE_PERF_DISABLE_COUNTERS')) {
		//dol_syslog("dolWebsiteIncrementCounter websiteid=".$websiteid." websitepagetype=".$websitepagetype." websitepageid=".$websitepageid);
		if (in_array($websitepagetype, array('blogpost', 'page'))) {
			global $db;

			$tmpnow = dol_getdate(dol_now('gmt'), true, 'gmt');

			$sql = "UPDATE ".$db->prefix()."website SET ";
			$sql .= " pageviews_total = pageviews_total + 1,";
			$sql .= " pageviews_month = pageviews_month + 1,";
			// if last access was done during previous month, we save pageview_month into pageviews_previous_month
			$sql .= " pageviews_previous_month = ".$db->ifsql("lastaccess < '".$db->idate(dol_mktime(0, 0, 0, $tmpnow['mon'], 1, $tmpnow['year'], 'gmt', 0), 'gmt')."'", 'pageviews_month', 'pageviews_previous_month').",";
			$sql .= " lastaccess = '".$db->idate(dol_now('gmt'), 'gmt')."',";
			$sql .= " lastpageid = ".((int) $websitepageid);
			$sql .= " WHERE rowid = ".((int) $websiteid);

			$resql = $db->query($sql);
			if (! $resql) {
				return -1;
			}
		}
	}

	return 1;
}


/**
 * Format img tags to introduce viewimage on img src.
 *
 * @param   string  $content    Content string
 * @return  void
 * @see	dolWebsiteOutput()
 */
/*
function dolWebsiteSaveContent($content)
{
	global $db, $langs, $conf, $user;
	global $dolibarr_main_url_root, $dolibarr_main_data_root;

	//dol_syslog("dolWebsiteSaveContent start (mode=".(defined('USEDOLIBARRSERVER')?'USEDOLIBARRSERVER':'').')');

	// Define $urlwithroot
	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	//$content = preg_replace('/(<img.*src=")(?!(http|'.preg_quote(DOL_URL_ROOT,'/').'\/viewimage))/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	return $content;
}
*/


/**
 * Make a redirect to another container.
 *
 * @param 	string		$containerref			Ref of container to redirect to (Example: 'mypage' or 'mypage.php').
 * @param 	string		$containeraliasalt		Ref of alternative aliases to redirect to.
 * @param 	int			$containerid			Id of container.
 * @param	int<0,1>	$permanent				0=Use temporary redirect 302 (default), 1=Use permanent redirect 301
 * @param 	array<string,mixed>	$parameters		Array of parameters to append to the URL.
 * @param	int<0,1>	$parampropagation		0=Do not propagate query parameter in URL when doing the redirect, 1=Keep parameters (default)
 * @return  void
 */
function redirectToContainer($containerref, $containeraliasalt = '', $containerid = 0, $permanent = 0, $parameters = array(), $parampropagation = 1)
{
	global $db, $website;
	'@phan-var-force Website $website';

	$newurl = '';
	$result = 0;

	// We make redirect using the alternative alias, we must find the real $containerref
	if ($containeraliasalt) {
		include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
		$tmpwebsitepage = new WebsitePage($db);
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$result = $tmpwebsitepage->fetch(0, (string) $website->id, '', $containeraliasalt);
		if ($result > 0) {
			$containerref = $tmpwebsitepage->pageurl;
		} else {
			print "Error, page contains a redirect to the alternative alias '".$containeraliasalt."' that does not exists in web site (".$website->id." / ".$website->ref.")";
			exit;
		}
	}

	if (defined('USEDOLIBARREDITOR')) {
		/*print '<div class="margintoponly marginleftonly">';
		print "This page contains dynamic code that make a redirect to '".$containerref."' in your current context. Redirect has been canceled as it is not supported in edition mode.";
		print '</div>';*/
		$text = "This page contains dynamic code that make a redirect to '".$containerref."' in your current context. Redirect has been canceled as it is not supported in edition mode.";
		setEventMessages($text, null, 'warnings', 'WEBSITEREDIRECTDISABLED'.$containerref);
		return;
	}

	if (defined('USEDOLIBARRSERVER')) {	// When page called from Dolibarr server
		// Check new container exists
		if (!$containeraliasalt) {	// If containeraliasalt set, we already did the test
			include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
			$tmpwebsitepage = new WebsitePage($db);
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$result = $tmpwebsitepage->fetch(0, (string) $website->id, $containerref);
			unset($tmpwebsitepage);
		}
		if ($result > 0) {
			$currenturi = $_SERVER["REQUEST_URI"];	// Example: /public/website/index.php?website=mywebsite.com&pageref=mywebsite-home&cache=3600
			$regtmp = array();
			if (preg_match('/&pageref=([^&]+)/', $currenturi, $regtmp)) {
				if ($regtmp[0] == $containerref) {
					print "Error, page with uri '.$currenturi.' try a redirect to the same alias page '".$containerref."' in web site '".$website->ref."'";
					exit;
				} else {
					$newurl = preg_replace('/&pageref=([^&]+)/', '&pageref='.$containerref, $currenturi);
				}
			} else {
				$newurl = $currenturi.'&pageref='.urlencode($containerref);
			}
		}
	} else { // When page called from virtual host server
		$newurl = '/'.$containerref.'.php';
		if ($parampropagation) {
			$newurl .= (empty($_SERVER["QUERY_STRING"]) ? '' : '?'.$_SERVER["QUERY_STRING"]);
		}
	}

	if ($newurl) {
		if (!empty($parameters)) {
			$separator = (parse_url($newurl, PHP_URL_QUERY) == null) ? '?' : '&';
			$newurl = $newurl . $separator . http_build_query($parameters);
		}
		if ($permanent) {
			header("Status: 301 Moved Permanently", false, 301);
		}
		header("Location: ".$newurl);
		exit;
	} else {
		print "Error, page contains a redirect to the alias page '".$containerref."' that does not exists in web site (".$website->id." / ".$website->ref.")";
		exit;
	}
}


/**
 * Execute content of a php page and report result to be included into another page.
 * It outputs content of file where the html and body part have been removed.
 *
 * @param 	string	$containerref		Path to file to include (must be a page from website root. Example: 'mypage.php' means 'mywebsite/mypage.php')
 * @param 	int		$once				If set to 1, we use include_once.
 * @param	int		$cachedelay			A cache delay in seconds.
 * @param	string	$cachekey			Add a key into the name of the cache so the includeContainer can use different cache content for the same page.
 * @return  void
 */
function includeContainer($containerref, $once = 0, $cachedelay = 0, $cachekey = '')
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs; // Very important. Required to have var available when running included containers.
	global $includehtmlcontentopened;
	global $websitekey, $websitepagefile;
	'@phan-var-force Website $website';

	$MAXLEVEL = 20;

	if (!preg_match('/\.php$/i', $containerref)) {
		$containerref .= '.php';
	}

	$fullpathfile = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$websitekey.'/'.$containerref;
	$fullpathcache = '';
	// If we ask to use the cache delay
	if ($cachedelay > 0 && !getDolGlobalString("WEBSITE_DISABLE_CACHE_OF_CONTAINERS")) {
		$fullpathcache = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/temp/'.$websitekey.'-'.$websitepage->id.'-'.$containerref.($cachekey ? '-'.$cachekey : '').'.cache';
	}

	if (empty($includehtmlcontentopened)) {
		$includehtmlcontentopened = 0;
	}
	$includehtmlcontentopened++;
	if ($includehtmlcontentopened > $MAXLEVEL) {
		print 'ERROR: RECURSIVE CONTENT LEVEL. Depth of recursive call is more than the limit of '.((int) $MAXLEVEL).".\n";
		return;
	}

	//dol_syslog("Include container ".$containerref.' includehtmlcontentopened='.$includehtmlcontentopened);

	// We don't print info messages for pages of type library or service
	if (!empty($websitepage->type_container) && !in_array($websitepage->type_container, array('library', 'service'))) {
		print "\n".'<!-- include '.$websitekey.'/'.$containerref.($cachekey ? ' cachekey='.$cachekey : '').(is_object($websitepage) ? ' parent id='.$websitepage->id : '').' level='.$includehtmlcontentopened.' -->'."\n";
	}

	$tmpoutput = '';

	if ($cachedelay > 0 && $fullpathcache) {
		if (is_file($fullpathcache)) {
			// Get the last modification time of the file
			$lastModifiedTime = filemtime($fullpathcache);

			// Get the current time
			$currentTime = time();

			// Check if the file is not older than X seconds
			if (($currentTime - $lastModifiedTime) <= $cachedelay) {
				// The file is too recent
				$tmpoutput = file_get_contents($fullpathcache);
			}
		}
	}

	if (empty($tmpoutput)) {
		// file_get_contents is not possible because we must execute code with include
		//$content = file_get_contents($fullpathfile);
		//print preg_replace(array('/^.*<body[^>]*>/ims','/<\/body>.*$/ims'), array('', ''), $content);*/

		ob_start();
		if ($once) {
			$res = @include_once $fullpathfile;
		} else {
			$res = @include $fullpathfile;
		}
		$tmpoutput = ob_get_contents();
		ob_end_clean();

		if (!$res) {
			print 'ERROR: FAILED TO INCLUDE PAGE '.$containerref."(once=".$once.")\n";
		} else {
			$tmpoutput = preg_replace(array('/^.*<body[^>]*>/ims', '/<\/body>.*$/ims'), array('', ''), $tmpoutput);

			// Save the content into cache file if content is lower than 10M
			if ($fullpathcache && strlen($tmpoutput) < 10000000) {
				file_put_contents($fullpathcache, $tmpoutput);
				dolChmod($fullpathcache);
			}
		}
	}

	print $tmpoutput;

	$includehtmlcontentopened--;
}

/**
 * Return HTML content to add structured data for an article, news or Blog Post. Use the json-ld format.
 * Example:
 * <?php getStructureData('blogpost'); ?>
 * <?php getStructureData('software', array('name'=>'Name', 'os'=>'Windows', 'price'=>10)); ?>
 *
 * @param 	string		$type				'blogpost', 'product', 'software', 'organization', 'qa',  ...
 * @param	array<string,mixed>	$data				Array of data parameters for structured data
 * @return  string							HTML content
 */
function getStructuredData($type, $data = array())
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs, $pagelangs; // Very important. Required to have var available when running included containers.
	'@phan-var-force Website $website';

	$type = strtolower($type);

	if ($type == 'software') {
		$ret = '<!-- Add structured data for entry in a software annuary -->'."\n";
		$ret .= '<script nonce="'.getNonce().'" type="application/ld+json">'."\n";
		$ret .= '{
			"@context": "https://schema.org",
			"@type": "SoftwareApplication",
			"name": "'.dol_escape_json($data['name']).'",
			"operatingSystem": "'.dol_escape_json($data['os']).'",
			"applicationCategory": "https://schema.org/'.dol_escape_json($data['applicationCategory']).'",';
		if (!empty($data['ratingcount'])) {
			$ret .= '
				"aggregateRating": {
					"@type": "AggregateRating",
					"ratingValue": "'.dol_escape_json($data['ratingvalue']).'",
					"ratingCount": "'.dol_escape_json($data['ratingcount']).'"
				},';
		}
		$ret .= '
			"offers": {
				"@type": "Offer",
				"price": "'.dol_escape_json($data['price']).'",
				"priceCurrency": "'.dol_escape_json($data['currency'] ? $data['currency'] : $conf->currency).'"
			}
		}'."\n";
		$ret .= '</script>'."\n";
	} elseif ($type == 'organization') {
		$companyname = $mysoc->name;
		$url = $mysoc->url;

		$ret = '<!-- Add structured data for organization -->'."\n";
		$ret .= '<script nonce="'.getNonce().'" type="application/ld+json">'."\n";
		$ret .= '{
			"@context": "https://schema.org",
			"@type": "Organization",
			"name": "'.dol_escape_json(!empty($data['name']) ? $data['name'] : $companyname).'",
			"url": "'.dol_escape_json(!empty($data['url']) ? $data['url'] : $url).'",
			"logo": "'.($data['logo'] ? dol_escape_json($data['logo']) : '/wrapper.php?modulepart=mycompany&file=logos%2F'.urlencode($mysoc->logo)).'",
			"contactPoint": {
				"@type": "ContactPoint",
				"contactType": "Contact",
				"email": "'.dol_escape_json(!empty($data['email']) ? $data['email'] : $mysoc->email).'"
			}'."\n";
		if (is_array($mysoc->socialnetworks) && count($mysoc->socialnetworks) > 0) {
			$ret .= ",\n";
			$ret .= '"sameAs": [';
			$i = 0;
			foreach ($mysoc->socialnetworks as $key => $value) {
				if ($key == 'linkedin') {
					$ret .= '"https://www.'.$key.'.com/company/'.dol_escape_json($value).'"';
				} elseif ($key == 'youtube') {
					$ret .= '"https://www.'.$key.'.com/user/'.dol_escape_json($value).'"';
				} else {
					$ret .= '"https://www.'.$key.'.com/'.dol_escape_json($value).'"';
				}
				$i++;
				if ($i < count($mysoc->socialnetworks)) {
					$ret .= ', ';
				}
			}
			$ret .= ']'."\n";
		}
		$ret .= '}'."\n";
		$ret .= '</script>'."\n";
	} elseif ($type == 'blogpost') {
		if (!empty($websitepage->author_alias)) {
			//include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			//$tmpuser = new User($db);
			//$restmpuser = $tmpuser->fetch($websitepage->fk_user_creat);

			$pageurl = $websitepage->pageurl;
			$title = $websitepage->title;
			$image = getImageFromHtmlContent($websitepage->content);
			$companyname = $mysoc->name;
			$description = $websitepage->description;

			$pageurl = str_replace('__WEBSITE_KEY__', $website->ref, $pageurl);
			$title = str_replace('__WEBSITE_KEY__', $website->ref, $title);
			$imagepath = '/medias'.(preg_match('/^\//', $image) ? '' : '/').str_replace('__WEBSITE_KEY__', $website->ref, $image);
			$companyname = str_replace('__WEBSITE_KEY__', $website->ref, $companyname);
			$description = str_replace('__WEBSITE_KEY__', $website->ref, $description);

			$ret = '<!-- Add structured data for blog post -->'."\n";
			$ret .= '<script nonce="'.getNonce().'" type="application/ld+json">'."\n";
			$ret .= '{
				  "@context": "https://schema.org",
				  "@type": "NewsArticle",
				  "mainEntityOfPage": {
				    "@type": "WebPage",
				    "@id": "'.dol_escape_json($pageurl).'"
				  },
				  "headline": "'.dol_escape_json($title).'",';
			if ($image) {
				$ret .= '
				  "image": [
				    "'.dol_escape_json($imagepath).'"
				   ],';
			}
			$ret .= '
				  "dateCreated": "'.dol_print_date($websitepage->date_creation, 'dayhourrfc').'",
				  "datePublished": "'.dol_print_date($websitepage->date_creation, 'dayhourrfc').'",
				  "dateModified": "'.dol_print_date($websitepage->date_modification, 'dayhourrfc').'",
				  "author": {
				    "@type": "Person",
				    "name": "'.dol_escape_json($websitepage->author_alias).'"
				  },
				  "publisher": {
				     "@type": "Organization",
				     "name": "'.dol_escape_json($companyname).'",
				     "logo": {
				        "@type": "ImageObject",
				        "url": "/wrapper.php?modulepart=mycompany&file='.urlencode('logos/'.$mysoc->logo).'"
				     }
				   },'."\n";
			if ($websitepage->keywords) {
				$ret .= '"keywords": [';
				$i = 0;
				$arrayofkeywords = explode(',', $websitepage->keywords);
				foreach ($arrayofkeywords as $keyword) {
					$ret .= '"'.dol_escape_json($keyword).'"';
					$i++;
					if ($i < count($arrayofkeywords)) {
						$ret .= ', ';
					}
				}
				$ret .= '],'."\n";
			}
			$ret .= '"description": "'.dol_escape_json($description).'"';
			$ret .= "\n".'}'."\n";
			$ret .= '</script>'."\n";
		} else {
			$ret = '<!-- no structured data inserted inline inside blogpost because no author_alias defined -->'."\n";
		}
	} elseif ($type == 'product') {
		$ret = '<!-- Add structured data for product -->'."\n";
		$ret .= '<script nonce="'.getNonce().'" type="application/ld+json">'."\n";
		$ret .= '{
				"@context": "https://schema.org/",
				"@type": "Product",
				"name": "'.dol_escape_json($data['label']).'",
				"image": [
					"'.dol_escape_json($data['image']).'",
				],
				"description": "'.dol_escape_json($data['description']).'",
				"sku": "'.dol_escape_json($data['ref']).'",
				"brand": {
					"@type": "Thing",
					"name": "'.dol_escape_json($data['brand']).'"
				},
				"author": {
					"@type": "Person",
					"name": "'.dol_escape_json($data['author']).'"
				}
				},
				"offers": {
					"@type": "Offer",
					"url": "https://example.com/anvil",
					"priceCurrency": "'.dol_escape_json($data['currency'] ? $data['currency'] : $conf->currency).'",
					"price": "'.dol_escape_json($data['price']).'",
					"itemCondition": "https://schema.org/UsedCondition",
					"availability": "https://schema.org/InStock",
					"seller": {
						"@type": "Organization",
						"name": "'.dol_escape_json($mysoc->name).'"
					}
				}
			}'."\n";
		$ret .= '</script>'."\n";
	} elseif ($type == 'qa') {
		$ret = '<!-- Add structured data for QA -->'."\n";
		$ret .= '<script nonce="'.getNonce().'" type="application/ld+json">'."\n";
		$ret .= '{
				"@context": "https://schema.org/",
				"@type": "QAPage",
				"mainEntity": {
					"@type": "Question",
					"name": "'.dol_escape_json($data['name']).'",
					"text": "'.dol_escape_json($data['name']).'",
					"answerCount": 1,
					"author": {
						"@type": "Person",
						"name": "'.dol_escape_json($data['author']).'"
					}
					"acceptedAnswer": {
						"@type": "Answer",
						"text": "'.dol_escape_json(dol_string_nohtmltag(dolStripPhpCode($data['description']))).'",
						"author": {
							"@type": "Person",
							"name": "'.dol_escape_json($data['author']).'"
						}
					}
				}
			}'."\n";
		$ret .= '</script>'."\n";
	} else {
		$ret = '';
	}
	return $ret;
}

/**
 * Return HTML content to add as header card for an article, news or Blog Post or home page.
 *
 * @param	?array<string,mixed>	$params	Array of parameters
 * @return  string							HTML content
 */
function getSocialNetworkHeaderCards($params = null)
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs; // Very important. Required to have var available when running included containers.
	'@phan-var-force Website $website';

	$out = '';

	if ($website->virtualhost) {
		$pageurl = $websitepage->pageurl;
		$title = $websitepage->title;
		$image = $websitepage->image;
		$companyname = $mysoc->name;
		$description = $websitepage->description;

		$pageurl = str_replace('__WEBSITE_KEY__', $website->ref, $pageurl);
		$title = str_replace('__WEBSITE_KEY__', $website->ref, $title);
		$image = '/medias'.(preg_match('/^\//', $image) ? '' : '/').str_replace('__WEBSITE_KEY__', $website->ref, $image);
		$companyname = str_replace('__WEBSITE_KEY__', $website->ref, $companyname);
		$description = str_replace('__WEBSITE_KEY__', $website->ref, $description);

		$shortlangcode = '';
		if ($websitepage->lang) {
			$shortlangcode = substr($websitepage->lang, 0, 2); // en_US or en-US -> en
		}
		if (empty($shortlangcode)) {
			$shortlangcode = substr($website->lang, 0, 2); // en_US or en-US -> en
		}

		$fullurl = $website->virtualhost.'/'.$websitepage->pageurl.'.php';
		$canonicalurl = $website->virtualhost.(($websitepage->id == $website->fk_default_home) ? '/' : (($shortlangcode != substr($website->lang, 0, 2) ? '/'.$shortlangcode : '').'/'.$websitepage->pageurl.'.php'));
		$hashtags = trim(implode(' #', array_map('trim', explode(',', $websitepage->keywords))));

		// Open Graph
		$out .= '<meta name="og:type" content="website">'."\n";	// TODO If blogpost, use type article
		$out .= '<meta name="og:title" content="'.$websitepage->title.'">'."\n";
		if ($websitepage->image) {
			$out .= '<meta name="og:image" content="'.$website->virtualhost.$image.'">'."\n";
		}
		$out .= '<meta name="og:url" content="'.$canonicalurl.'">'."\n";

		// Twitter
		$out .= '<meta name="twitter:card" content="summary">'."\n";
		if (!empty($params) && !empty($params['twitter_account'])) {
			$out .= '<meta name="twitter:site" content="@'.$params['twitter_account'].'">'."\n";
			$out .= '<meta name="twitter:creator" content="@'.$params['twitter_account'].'">'."\n";
		}
		$out .= '<meta name="twitter:title" content="'.$websitepage->title.'">'."\n";
		if ($websitepage->description) {
			$out .= '<meta name="twitter:description" content="'.$websitepage->description.'">'."\n";
		}
		if ($websitepage->image) {
			$out .= '<meta name="twitter:image" content="'.$website->virtualhost.$image.'">'."\n";
		}
		//$out .= '<meta name="twitter:domain" content="'.getDomainFromURL($website->virtualhost, 1).'">';
		/*
		 $out .= '<meta name="twitter:app:name:iphone" content="">';
		 $out .= '<meta name="twitter:app:name:ipad" content="">';
		 $out .= '<meta name="twitter:app:name:googleplay" content="">';
		 $out .= '<meta name="twitter:app:url:iphone" content="">';
		 $out .= '<meta name="twitter:app:url:ipad" content="">';
		 $out .= '<meta name="twitter:app:url:googleplay" content="">';
		 $out .= '<meta name="twitter:app:id:iphone" content="">';
		 $out .= '<meta name="twitter:app:id:ipad" content="">';
		 $out .= '<meta name="twitter:app:id:googleplay" content="">';
		 */
	}

	return $out;
}

/**
 * Return HTML content to add structured data for an article, news or Blog Post.
 *
 * @param	string	$socialnetworks			'' or list of social networks
 * @return  string							HTML content
 */
function getSocialNetworkSharingLinks($socialnetworks = '')
{
	global $website, $websitepage; // Very important. Required to have var available when running included containers.
	'@phan-var-force Website $website';

	$out = '<!-- section for social network sharing of page -->'."\n";

	if ($website->virtualhost) {
		$fullurl = $website->virtualhost.'/'.$websitepage->pageurl.'.php';
		$hashtags = trim(implode(' #', array_map('trim', explode(',', $websitepage->keywords))));

		$out .= '<div class="dol-social-share">'."\n";

		// Twitter
		if (empty($socialnetworks) || preg_match('/twitter/', $socialnetworks)) {
			$out .= '<div class="dol-social-share-tw">'."\n";
			$out .= '<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$fullurl.'" data-text="'.dol_escape_htmltag($websitepage->description).'" data-lang="'.$websitepage->lang.'" data-size="small" data-related="" data-hashtags="'.preg_replace('/^#/', '', $hashtags).'" data-count="horizontal">Tweet</a>';
			$out .= '<script nonce="'.getNonce().'">!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\'://platform.twitter.com/widgets.js\';fjs.parentNode.insertBefore(js,fjs);}}(document, \'script\', \'twitter-wjs\');</script>';
			$out .= '</div>'."\n";
		}

		// Reddit
		if (empty($socialnetworks) || preg_match('/reddit/', $socialnetworks)) {
			$out .= '<div class="dol-social-share-reddit">'."\n";
			$out .= '<a href="https://www.reddit.com/submit" target="_blank" rel="noopener noreferrer external" onclick="window.location = \'https://www.reddit.com/submit?url='.urlencode($fullurl).'\'; return false">';
			$out .= '<span class="dol-social-share-reddit-span">Reddit</span>';
			$out .= '</a>';
			$out .= '</div>'."\n";
		}

		// Facebook
		if (empty($socialnetworks) || preg_match('/facebook/', $socialnetworks)) {
			$out .= '<div class="dol-social-share-fbl">'."\n";
			$out .= '<a href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($fullurl).'">';
			$out .= '<span class="dol-social-share-fbl-span">Facebook</span>';
			$out .= '</a>';
			$out .= '</div>';
		}

		$out .= "\n</div>\n";
	} else {
		$out .= '<!-- virtual host not defined in CMS. No way to add sharing buttons -->'."\n";
	}
	$out .= '<!-- section end for social network sharing of page -->'."\n";

	return $out;
}


/**
 * Return nb of images known into inde files for an object;
 *
 * @param	Object	$object		Object
 * @return  int					HTML img content or '' if no image found
 * @see getImagePublicURLOfObject()
 */
function getNbOfImagePublicURLOfObject($object)
{
	global $db;

	$nb = 0;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$regexforimg = getListOfPossibleImageExt(0);
	$regexforimg = '('.$regexforimg.')$';

	$sql = "SELECT COUNT(rowid) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql .= " WHERE entity IN (".getEntity($object->element).")";
	$sql .= " AND src_object_type = '".$db->escape($object->element)."' AND src_object_id = ".((int) $object->id);	// Filter on object
	$sql .= " AND ".$db->regexpsql('filename', $regexforimg, 1);
	$sql .= " AND share IS NOT NULL";	// Only image that are public

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nb = $obj->nb;
		}
	}

	return $nb;
}

/**
 * Return the public image URL of an object.
 * For example, you can get the public image URL of a product (image that is shared).
 *
 * @param	Object	$object			Object
 * @param	int		$no				Numero of image (if there is several images. 1st one by default)
 * @param   string  $extName        Extension to differentiate thumb file name ('', '_small', '_mini')
 * @param	int		$cover			1=Sort with cover then position, -1=Filter on cover last then position, 0=Exclude cover and filter on position first
 * @return  string					HTML img content or '' if no image found
 * @see getNbOfImagePublicURLOfObject(), getPublicFilesOfObject(), getImageFromHtmlContent()
 */
function getImagePublicURLOfObject($object, $no = 1, $extName = '', $cover = 1)
{
	global $db;

	$image_path = '';

	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$regexforimg = getListOfPossibleImageExt(0);
	$regexforimg = '('.$regexforimg.')$';

	$sql = "SELECT rowid, ref, share, filename, cover, position";
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql .= " WHERE entity IN (".getEntity($object->element).")";
	$sql .= " AND src_object_type = '".$db->escape($object->element)."' AND src_object_id = ".((int) $object->id);	// Filter on object
	$sql .= " AND ".$db->regexpsql('filename', $regexforimg, 1);
	$sql .= ($cover ? "" : " AND cover <> 1");
	if ($cover == 1) {
		$sql .= $db->order("cover,position,rowid", "ASC,ASC,ASC");
	} else {
		$sql .= $db->order("cover,position,rowid", "DESC,ASC,ASC");
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$found = 0;
		$foundnotshared = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				if (empty($obj->share)) {
					$foundnotshared++;
				} else {
					$found++;

					if (defined('USEDOLIBARRSERVER') || defined('USEDOLIBARREDITOR')) {
						$image_path = DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($obj->share);
					} else {
						$image_path = '/wrapper.php?hashp='.urlencode($obj->share);
					}

					if ($extName) {
						$image_path .= '&extname='.urlencode($extName);
					}
				}
			}
			if ($found >= $no) {
				break;
			}
			$i++;
		}
		if (!$found && $foundnotshared) {
			if (defined('USEDOLIBARRSERVER') || defined('USEDOLIBARREDITOR')) {
				$image_path = DOL_URL_ROOT.'/viewimage.php?modulepart=common&file=nophotopublic.png';
			} else {
				$image_path = '/wrapper.php?modulepart=common&file=nophotopublic.png';
			}
		}
	}

	if (empty($image_path)) {
		if (defined('USEDOLIBARRSERVER') || defined('USEDOLIBARREDITOR')) {
			$image_path = DOL_URL_ROOT.'/viewimage.php?modulepart=common&file=nophoto.png';
		} else {
			$image_path = '/wrapper.php?modulepart=common&file=nophoto.png';
		}
	}

	return $image_path;
}

/**
 * Return array with list of all public files of a given object.
 *
 * @param	Object	$object			Object
 * @return array<array{filename:string,position:int,url:string}>	List of public files of object
 * @see getImagePublicURLOfObject()
 */
function getPublicFilesOfObject($object)
{
	global $db;

	$files = array();

	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$regexforimg = getListOfPossibleImageExt(0);
	$regexforimg = '/('.$regexforimg.')$/i';

	$sql = "SELECT rowid, ref, share, filename, cover, position";
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql .= " WHERE entity IN (".getEntity($object->element).")";
	$sql .= " AND src_object_type = '".$db->escape($object->element)."' AND src_object_id = ".((int) $object->id);
	$sql .= $db->order("cover,position,rowid", "ASC,ASC,ASC");

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				if (!empty($obj->share)) {
					$files[$obj->rowid]['filename'] = $obj->filename;
					$files[$obj->rowid]['position'] = $obj->position;
					if (defined('USEDOLIBARRSERVER') || defined('USEDOLIBARREDITOR')) {
						if (preg_match($regexforimg, $obj->filename)) {
							$files[$obj->rowid]['url'] = DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($obj->share);
						} else {
							$files[$obj->rowid]['url'] = DOL_URL_ROOT.'/document.php?hashp='.urlencode($obj->share);
						}
					} else {
						$files[$obj->rowid]['url'] = '/wrapper.php?hashp='.urlencode($obj->share);
					}
				}
			}
			$i++;
		}
	}

	return $files;
}


/**
 * Return list of containers object that match a criteria.
 * WARNING: This function can be used by websites.
 *
 * @param 	string		$type				Type of container to search into (Example: '', 'page', 'blogpost', 'page,blogpost', ...)
 * @param 	string		$algo				Algorithm used for search (Example: 'meta' is searching into meta information like title and description, 'content', 'sitefiles', or any combination 'meta,content,sitefiles')
 * @param	string		$searchstring		Search string
 * @param	int			$max				Max number of answers
 * @param	string		$sortfield			Sort Fields
 * @param	'DESC'|'ASC'	$sortorder			Sort order ('DESC' or 'ASC')
 * @param	string		$langcode			Language code ('' or 'en', 'fr', 'es', ...)
 * @param	array<string,mixed>	$otherfilters		Other filters
 * @param	int<-1,1>	$status				0 or 1, or -1 for both
 * @return  array{list?:WebsitePage[],code?:string,message?:string}	Array with results of search
 */
function getPagesFromSearchCriterias($type, $algo, $searchstring, $max = 25, $sortfield = 'date_creation', $sortorder = 'DESC', $langcode = '', $otherfilters = [], $status = 1)
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs; // Very important. Required to have var available when running included containers.
	'@phan-var-force Website $website';

	$error = 0;
	$arrayresult = array('code' => '', 'list' => array());

	// Clean parameters
	if (!is_object($weblangs)) {
		$weblangs = $langs;
	}
	if (empty($algo)) {
		$algo = 'content';
	}

	/*
	if (empty($searchstring) && empty($type) && empty($langcode) && empty($otherfilters)) {
		$error++;
		$arrayresult['code'] = 'KO';
		$arrayresult['message'] = $weblangs->trans("EmptySearchString");
	} elseif ($searchstring && dol_strlen($searchstring) < 2) {
		$weblangs->load("errors");
		$error++;
		$arrayresult['code'] = 'KO';
		$arrayresult['message'] = $weblangs->trans("ErrorSearchCriteriaTooSmall");
	} else {
	*/
	$tmparrayoftype = explode(',', $type);
	/*foreach ($tmparrayoftype as $tmptype) {
		if (!in_array($tmptype, array('', 'page', 'blogpost'))) {
			$error++;
			$arrayresult['code'] = 'KO';
			$arrayresult['message'] = 'Bad value for parameter type';
			break;
		}
	}*/
	//}

	$searchdone = 0;
	$found = 0;

	if (!$error && (empty($max) || ($found < $max)) && (preg_match('/meta/', $algo) || preg_match('/content/', $algo))) {
		include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

		$sql = 'SELECT wp.rowid FROM '.MAIN_DB_PREFIX.'website_page as wp';
		if (is_array($otherfilters) && !empty($otherfilters['category'])) {
			$sql .= ', '.MAIN_DB_PREFIX.'categorie_website_page as cwp';
		}
		$sql .= " WHERE wp.fk_website = ".((int) $website->id);
		if ($status >= 0) {
			$sql .= " AND wp.status = ".((int) $status);
		}
		if ($langcode) {
			$sql .= " AND wp.lang = '".$db->escape($langcode)."'";
		}
		if ($type) {
			$tmparrayoftype = explode(',', $type);
			$typestring = '';
			foreach ($tmparrayoftype as $tmptype) {
				$typestring .= ($typestring ? ", " : "")."'".$db->escape(trim($tmptype))."'";
			}
			$sql .= " AND wp.type_container IN (".$db->sanitize($typestring, 1).")";
		}
		$sql .= " AND (";
		$searchalgo = '';
		if (preg_match('/meta/', $algo)) {
			$searchalgo .= ($searchalgo ? ' OR ' : '')."wp.title LIKE '%".$db->escape($db->escapeforlike($searchstring))."%' OR wp.description LIKE '%".$db->escape($db->escapeforlike($searchstring))."%'";
			$searchalgo .= ($searchalgo ? ' OR ' : '')."wp.keywords LIKE '".$db->escape($db->escapeforlike($searchstring)).",%' OR wp.keywords LIKE '% ".$db->escape($db->escapeforlike($searchstring))."%'"; // TODO Use a better way to scan keywords
		}
		if (preg_match('/content/', $algo)) {
			$searchalgo .= ($searchalgo ? ' OR ' : '')."wp.content LIKE '%".$db->escape($db->escapeforlike($searchstring))."%'";
		}
		$sql .= $searchalgo;
		if (is_array($otherfilters) && !empty($otherfilters['category'])) {
			$sql .= ' AND cwp.fk_website_page = wp.rowid AND cwp.fk_categorie = '.((int) $otherfilters['category']);
		}
		$sql .= ")";
		$sql .= $db->order($sortfield, $sortorder);
		$sql .= $db->plimit($max);
		//print $sql;

		$resql = $db->query($sql);

		if ($resql) {
			$i = 0;
			while (($obj = $db->fetch_object($resql)) && ($i < $max || $max == 0)) {
				if ($obj->rowid > 0) {
					$tmpwebsitepage = new WebsitePage($db);
					$tmpwebsitepage->fetch($obj->rowid);
					if ($tmpwebsitepage->id > 0) {
						$arrayresult['list'][$obj->rowid] = $tmpwebsitepage;
					}
					$found++;
				}
				$i++;
			}
		} else {
			$error++;
			$arrayresult['code'] = $db->lasterrno();
			$arrayresult['message'] = $db->lasterror();
		}

		$searchdone = 1;
	}

	if (!$error && (empty($max) || ($found < $max)) && (preg_match('/sitefiles/', $algo))) {
		global $dolibarr_main_data_root;

		$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$website->ref;
		$filehtmlheader = $pathofwebsite.'/htmlheader.html';
		$filecss = $pathofwebsite.'/styles.css.php';
		$filejs = $pathofwebsite.'/javascript.js.php';
		$filerobot = $pathofwebsite.'/robots.txt';
		$filehtaccess = $pathofwebsite.'/.htaccess';
		$filemanifestjson = $pathofwebsite.'/manifest.json.php';
		$filereadme = $pathofwebsite.'/README.md';

		$filecontent = file_get_contents($filehtmlheader);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent)) {
			$arrayresult['list'][] = array('type' => 'website_htmlheadercontent');
		}

		$filecontent = file_get_contents($filecss);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent)) {
			$arrayresult['list'][] = array('type' => 'website_csscontent');
		}

		$filecontent = file_get_contents($filejs);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent)) {
			$arrayresult['list'][] = array('type' => 'website_jscontent');
		}

		$filerobot = file_get_contents($filerobot);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent)) {
			$arrayresult['list'][] = array('type' => 'website_robotcontent');
		}

		$searchdone = 1;
	}

	if (!$error) {
		if ($searchdone) {
			$arrayresult['code'] = 'OK';
			if (empty($arrayresult['list'])) {
				$arrayresult['code'] = 'KO';
				$arrayresult['message'] = $weblangs->trans("NoRecordFound");
			}
		} else {
			$error++;
			$arrayresult['code'] = 'KO';
			$arrayresult['message'] = 'No supported algorithm found';
		}
	}

	return $arrayresult;
}

/**
 * Return the URL of an image found into a HTML content.
 * To get image from an external URL to download first, see getAllImages()
 *
 * @param	string		$htmlContent	HTML content
 * @param	int			$imageNumber	The position of image. 1 by default = first image found
 * @return	string						URL of image or '' if not foud
 * @see getImagePublicURLOfObject()
 */
function getImageFromHtmlContent($htmlContent, $imageNumber = 1)
{
	if (empty($htmlContent)) {
		return '';
	}

	$dom = new DOMDocument();

	libxml_use_internal_errors(false);	// Avoid to fill memory with xml errors
	if (LIBXML_VERSION < 20900) {
		// Avoid load of external entities (security problem).
		// Required only if LIBXML_VERSION < 20900
		// @phan-suppress-next-line PhanDeprecatedFunctionInternal
		libxml_disable_entity_loader(true);
	}

	// Load HTML content into object
	// We add the @ to avoid verbose warnings logsin the error.log file. For example:
	// "PHP message: PHP Warning:  DOMDocument::loadHTML(): Tag section invalid in Entity, line: ...", etc.
	@$dom->loadHTML($htmlContent);

	// Re-enable HTML load errors
	libxml_clear_errors();

	// Load all img tags
	$images = $dom->getElementsByTagName('img');

	// Check if nb of image is valid
	if ($imageNumber > 0 && $imageNumber <= $images->length) {
		// Récupère l'image correspondante (index - 1 car $imageNumber est 1-based)
		$img = $images->item($imageNumber - 1);
		if ($img instanceof DOMElement) {
			return $img->getAttribute('src');
		}
	}

	return '';
}

/**
 * Download all images found into an external URL.
 * It using a text regex parsing solution, not a DOM analysis.
 * If $modifylinks is set, links to images will be replace with a link to viewimage wrapper.
 * To extract an URL from a HTML text content, see instead getImageFromHtmlContent().
 *
 * @param 	Website	 	$object			Object website
 * @param 	WebsitePage	$objectpage		Object website page
 * @param 	string		$urltograb		URL to grab (example: https://www.nltechno.com/ or s://www.nltechno.com/dir1/ or https://www.nltechno.com/dir1/mapage1)
 * @param 	string		$tmp			Content to parse
 * @param 	string		$action			Var $action
 * @param	int<0,1>	$modifylinks	0=Do not modify content, 1=Replace links with a link to viewimage
 * @param	int<0,1>	$grabimages		0=Do not grab images, 1=Grab images
 * @param	'root'|'subpage'	$grabimagesinto	'root' or 'subpage'
 * @return	void
 */
function getAllImages($object, $objectpage, $urltograb, &$tmp, &$action, $modifylinks = 0, $grabimages = 1, $grabimagesinto = 'subpage')
{
	global $conf;

	$error = 0;

	dol_syslog("Call getAllImages with grabimagesinto=".$grabimagesinto);

	$alreadygrabbed = array();

	if (preg_match('/\/$/', $urltograb)) {
		$urltograb .= '.';
	}
	$urltograb = dirname($urltograb); // So urltograb is now http://www.nltechno.com or http://www.nltechno.com/dir1

	// Search X in "img...src=X"
	$regs = array();
	preg_match_all('/<img([^\.\/]+)src="([^>"]+)"([^>]*)>/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val) {
		if (preg_match('/^data:image/i', $regs[2][$key])) {
			continue; // We do nothing for such images
		}

		if (preg_match('/^\//', $regs[2][$key])) {
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
		} else {
			$urltograbbis = $urltograb.'/'.$regs[2][$key]; // We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];
		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') {
			$dirforimages = '';
		}

		// Define $filetosave and $filename
		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key]) ? '' : '/').$regs[2][$key];
		if (preg_match('/^http/', $regs[2][$key])) {
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;
		}
		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		if (empty($alreadygrabbed[$urltograbbis])) {
			if ($grabimages) {
				$tmpgeturl = getURLContent($urltograbbis, 'GET', '', 1, array(), array('http', 'https'), 0);
				if ($tmpgeturl['curl_error_no']) {
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
					$action = 'create';
				} elseif ($tmpgeturl['http_code'] != '200') {
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
					$action = 'create';
				} else {
					$alreadygrabbed[$urltograbbis] = 1; // Track that file was already grabbed.

					dol_mkdir(dirname($filetosave));

					$fp = fopen($filetosave, "w");
					if ($fp) {
						fwrite($fp, $tmpgeturl['content']);
						fclose($fp);
						dolChmod($filetosave);
					} else {
						setEventMessages('Error failed to open file '.$filetosave.' for writing', null, 'errors');
						//print 'Failed to open file '.$filetosave.' for writing.';
					}
				}
			}
		}

		if ($modifylinks) {
			$tmp = preg_replace('/'.preg_quote($regs[0][$key], '/').'/i', '<img'.$regs[1][$key].'src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'"'.$regs[3][$key].'>', $tmp);
		}
	}

	// Search X in "background...url(X)"
	preg_match_all('/background([^\.\/\(;]+)url\([\"\']?([^\)\"\']*)[\"\']?\)/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val) {
		if (preg_match('/^data:image/i', $regs[2][$key])) {
			continue; // We do nothing for such images
		}

		if (preg_match('/^\//', $regs[2][$key])) {
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
		} else {
			$urltograbbis = $urltograb.'/'.$regs[2][$key]; // We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];

		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') {
			$dirforimages = '';
		}

		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key]) ? '' : '/').$regs[2][$key];

		if (preg_match('/^http/', $regs[2][$key])) {
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;
		}

		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		if (empty($alreadygrabbed[$urltograbbis])) {
			if ($grabimages) {
				$tmpgeturl = getURLContent($urltograbbis, 'GET', '', 1, array(), array('http', 'https'), 0);
				if ($tmpgeturl['curl_error_no']) {
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
					$action = 'create';
				} elseif ($tmpgeturl['http_code'] != '200') {
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
					$action = 'create';
				} else {
					$alreadygrabbed[$urltograbbis] = 1; // Track that file was already grabbed.

					dol_mkdir(dirname($filetosave));

					$fp = fopen($filetosave, "w");
					if ($fp) {
						fwrite($fp, $tmpgeturl['content']);
						fclose($fp);
						dolChmod($filetosave);
					} else {
						$error++;
						setEventMessages('Error failed to open file '.$filetosave.' for writing', null, 'errors');
						$action = 'create';
					}
				}
			}
		}

		if ($modifylinks) {
			$tmp = preg_replace('/'.preg_quote($regs[0][$key], '/').'/i', 'background'.$regs[1][$key].'url("'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'")', $tmp);
		}
	}
}

/**
 * Retrieves the details of a news post by its ID.
 *
 * @param string $postId  The ID of the news post to retrieve.
 * @return array<string,mixed>|int<-1,-1>   Return array if OK, -1 if KO
 */
function getNewsDetailsById($postId)
{
	global $db;

	if (empty($postId)) {
		return -1;
	}

	$sql = "SELECT p.title, p.description, p.date_creation, p.image
            FROM ".MAIN_DB_PREFIX."website_page as p
            WHERE p.rowid = ".(intval($postId));

	$resql = $db->query($sql);
	if ($resql) {
		return $db->fetch_array($resql);
	} else {
		return -1;
	}
}
