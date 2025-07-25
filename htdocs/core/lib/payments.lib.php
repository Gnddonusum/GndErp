<?php
/**
 * Copyright (C) 2013	    Marcos García	        <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Abbes Bahfir            <bafbes@gmail.com>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
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
 * or see https://www.gnu.org/
 */

/**
 * Returns an array with the tabs for the "Payment" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param Paiement $object Current payment object
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs for the payment section
 */
function payment_prepare_head(Paiement $object)
{
	global $langs, $conf, $db;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Payment");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment');

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->compta->payment->dir_output.'/'.$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Bannkline" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param 	int		$id		ID of bank line
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs for the Banline section
 */
function bankline_prepare_head($id)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$id;
	$head[$h][1] = $langs->trans('BankTransaction');
	$head[$h][2] = 'bankline';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'bankline');

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'bankline', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Supplier payment" section
 * It loads tabs from modules looking for the entity payment_supplier
 *
 * @param Paiement $object Current payment object
 * @return	array<array{0:string,1:string,2:string}>	Tabs for the payment section
 */
function payment_supplier_prepare_head(Paiement $object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Payment");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_supplier');

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->payment->dir_output.'/'.$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_supplier', 'remove');

	return $head;
}

/**
 * Return array of valid payment mode
 *
 * @param	string	$paymentmethod		Filter on this payment method (''=none, 'paypal', 'stripe', ...)
 * @param	int		$mode				0=Return array with key, 1=Return array with more information like label
 * @return	array<string,string>		Array of valid payment method
 */
function getValidOnlinePaymentMethods($paymentmethod = '', $mode = 0)
{
	global $langs, $hookmanager, $action;

	$validpaymentmethod = array();

	if ((empty($paymentmethod) || $paymentmethod == 'paypal') && isModEnabled('paypal')) {
		$langs->load("paypal");
		if ($mode) {
			$validpaymentmethod['paypal'] = array('label' => 'PayPal', 'status' => 'valid');
		} else {
			$validpaymentmethod['paypal'] = 'valid';
		}
	}
	if ((empty($paymentmethod) || $paymentmethod == 'paybox') && isModEnabled('paybox')) {
		$langs->loadLangs(array("paybox", "stripe"));
		if ($mode) {
			$validpaymentmethod['paybox'] = array('label' => 'PayBox', 'status' => 'valid');
		} else {
			$validpaymentmethod['paybox'] = 'valid';
		}
	}
	if ((empty($paymentmethod) || $paymentmethod == 'stripe') && isModEnabled('stripe')) {
		$langs->load("stripe");
		if ($mode) {
			$validpaymentmethod['stripe'] = array('label' => 'Stripe', 'status' => 'valid');
		} else {
			$validpaymentmethod['stripe'] = 'valid';
		}
	}

	// This hook is used to complete the $validpaymentmethod array so an external payment modules
	// can add its own key (ie 'payzen' for Payzen, 'helloasso' for HelloAsso...)
	$parameters = [
		'paymentmethod' => $paymentmethod,
		'mode' => $mode,
		'validpaymentmethod' => &$validpaymentmethod
	];
	$tmpobject = new stdClass();
	$reshook = $hookmanager->executeHooks('getValidPayment', $parameters, $tmpobject, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif (!empty($hookmanager->resArray['validpaymentmethod'])) {
		if ($reshook == 0) {
			$validpaymentmethod = array_merge($validpaymentmethod, $hookmanager->resArray['validpaymentmethod']);
		} else {
			$validpaymentmethod = $hookmanager->resArray['validpaymentmethod'];
		}
	}

	return $validpaymentmethod;
}

/**
 * Return string with full online payment Url
 *
 * @param   string		$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'member' ...)
 * @param	string		$ref		Ref of object
 * @param	int|float	$amount		Amount of money to request for
 * @return	string					Url string
 */
function showOnlinePaymentUrl($type, $ref, $amount = 0)
{
	global $langs;

	// Load translation files required by the page
	$langs->loadLangs(array('payment', 'stripe'));

	$servicename = '';	// Link is a generic link for all payments services (paypal, stripe, ...)

	$out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePayment", $servicename).'</span><br>';
	$url = getOnlinePaymentUrl(0, $type, $ref, $amount);
	$out .= '<div class="urllink"><input type="text" id="onlinepaymenturl" spellcheck="false" class="quatrevingtpercentminusx" value="'.$url.'">';
	$out .= '<a class="" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	$out .= '</div>';
	$out .= ajax_autoselect("onlinepaymenturl", '');
	return $out;
}

/**
 * Return string with HTML link for online payment
 *
 * @param	string		$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'member' ...)
 * @param	string		$ref		Ref of object
 * @param	string		$label		Text or HTML tag to display, if empty it display the URL
 * @param	int|float	$amount		Amount of money to request for
 * @return	string					Url string
 */
function getHtmlOnlinePaymentLink($type, $ref, $label = '', $amount = 0)
{
	$url = getOnlinePaymentUrl(0, $type, $ref, $amount);
	$label = $label ? $label : $url;
	return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
}


/**
 * Return string with full Url
 *
 * @param   int			$mode		      0=True url, 1=Url formatted with colors
 * @param   string		$type		      Type of URL ('free', 'order', 'invoice', 'contractline', 'member', 'boothlocation', ...)
 * @param	string		$ref		      Ref of object
 * @param	int|float	$amount		      Amount of money to request for
 * @param	string		$freetag	      Free tag (required and used for $type='free' only)
 * @param   int|string 	$localorexternal  0=Url of current browsing, 1=Url for external access, or string with virtual host url
 * @return	string					      Url string
 */
function getOnlinePaymentUrl($mode, $type, $ref = '', $amount = 0, $freetag = 'your_tag', $localorexternal = 1)
{
	global $conf, $dolibarr_main_url_root;

	$out = '';

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urltouse = DOL_MAIN_URL_ROOT;						// Should be "https://www.mydomain.com/mydolibarr" for example
	//dol_syslog("getOnlinePaymentUrl DOL_MAIN_URL_ROOT=".DOL_MAIN_URL_ROOT);

	if ((string) $localorexternal == '1') {
		$urltouse = $urlwithroot;
	} elseif ((string) $localorexternal != '0') {
		$urltouse = $localorexternal;
	}

	if ($type == 'free') {
		$out = $urltouse.'/public/payment/newpayment.php?amount='.($mode ? '<span style="color: #666666">' : '').price2num($amount, 'MT').($mode ? '</span>' : '').'&tag='.($mode ? '<span style="color: #666666">' : '').$freetag.($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.urlencode(dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 'sha1md5'));
			}
		}
		//if ($mode) $out.='&noidempotency=1';
	} elseif ($type == 'order') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'order_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + order_ref)";
				}
				if ($mode == 0) {
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	} elseif ($type == 'invoice') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'invoice_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + invoice_ref)";
				}
				if ($mode == 0) {
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	} elseif ($type == 'contractline') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'contractline_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + contractline_ref)";
				}
				if ($mode == 0) {
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	} elseif ($type == 'member' || $type == 'membersubscription') {
		$newtype = 'member';
		$out = $urltouse.'/public/payment/newpayment.php?source=member';
		$out .= '&amount='.price2num($amount, 'MT');
		$out .= '&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'member_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {	// mode tuto
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$newtype."' + member_ref)";
				}
				if ($mode == 0) {	// mode real
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$newtype.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	} elseif ($type == 'donation') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'donation_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + donation_ref)";
				}
				if ($mode == 0) {
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	} elseif ($type == 'boothlocation') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'invoice_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
				$out .= '&securekey='.urlencode(getDolGlobalString('PAYMENT_SECURITY_TOKEN'));
			} else {
				$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
				if ($mode == 1) {
					$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + invoice_ref)";
				}
				if ($mode == 0) {
					$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
				}
				$out .= ($mode ? '</span>' : '');
			}
		}
	}

	// For multicompany
	if (!empty($out) && isModEnabled('multicompany')) {
		$out .= "&entity=".$conf->entity; // Check the entity because we may have the same reference in several entities
	}

	return $out;
}
