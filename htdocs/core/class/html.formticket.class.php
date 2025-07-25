<?php
/* Copyright (C) 2013-2015 Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel     <christophe@altairis.fr>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021      Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2021      Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2023-2025 Charlene Benke	       <charlene.r@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024	   Irvine FLEITH		   <irvine.fleith@atm-consulting.fr>
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
 *    \file       htdocs/core/class/html.formticket.class.php
 *    \ingroup    ticket
 *    \brief      File of class to generate the form for creating a new ticket.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

if (!class_exists('FormCompany')) {
	include DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
}

/**
 * Class to generate the form for creating a new ticket.
 * Usage: 	$formticket = new FormTicket($db)
 * 			$formticket->proprietes = 1 or string or array of values
 * 			$formticket->show_form()  shows the form
 *
 * @package Ticket
 */
class FormTicket
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string		A hash value of the ticket. Duplicate of ref but for public purposes.
	 */
	public $track_id;

	/**
	 * @var string 		Email $trackid. Used also for the $keytoavoidconflict to name session vars to upload files.
	 */
	public $trackid;

	/**
	 * @var int ID
	 */
	public $fk_user_create;

	/**
	 * @var string
	 */
	public $message;
	/**
	 * @var string
	 */
	public $topic_title;

	/**
	 * @var string
	 */
	public $action;

	/**
	 * @var int<0,1>
	 */
	public $withtopic;
	/**
	 * @var int<0,1>
	 */
	public $withemail;

	/**
	 * @var int<0,1> $withsubstit Show substitution array
	 */
	public $withsubstit;

	/**
	 * @var int<0,2>
	 */
	public $withfile;
	/**
	 * @var int<0,1>
	 */
	public $withfilereadonly;

	/**
	 * @var string
	 */
	public $backtopage;

	/**
	 * @var int<0,1>
	 */
	public $ispublic;  // to show information or not into public form

	/**
	 * @var int<0,1>
	 */
	public $withtitletopic;
	/**
	 * @var int<0,1>
	 */
	public $withtopicreadonly;
	/**
	 * @var int<0,1>
	 */
	public $withreadid;

	/**
	 * @var int<0,1>
	 */
	public $withcompany;  // to show company drop-down list
	/**
	 * @var int<0,1>
	 */
	public $withfromsocid;
	/**
	 * @var int<0,1>
	 */
	public $withfromcontactid;
	/**
	 * @var int<0,1>
	 */
	public $withnotifytiersatcreate;
	/**
	 * @var int<0,1>
	 */
	public $withusercreate;  // to show name of creating user in form
	/**
	 * @var int<0,1>
	 */
	public $withcreatereadonly;

	/**
	 * @var int<0,1> withextrafields
	 */
	public $withextrafields;

	/**
	 * @var int<0,1>
	 */
	public $withref;  // to show ref field
	/**
	 * @var int<0,1>
	 */
	public $withcancel;

	/**
	 * @var string
	 */
	public $type_code;
	/**
	 * @var string
	 */
	public $category_code;
	/**
	 * @var string
	 */
	public $severity_code;


	/**
	 *
	 * @var array<string,string> $substit Substitutions
	 */
	public $substit = array();
	/**
	 * @var array<string,mixed|array>
	 */
	public $param = array();

	/**
	 * @var string Error code (or message)
	 */
	public $error;
	/**
	 * @var string[]
	 */
	public $errors = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->action = 'add';

		$this->withcompany = (int) (!getDolGlobalInt("TICKETS_NO_COMPANY_ON_FORM") && isModEnabled("societe"));
		$this->withfromsocid = 0;
		$this->withfromcontactid = 0;
		$this->withreadid = 0;
		//$this->withtitletopic='';
		$this->withnotifytiersatcreate = 0;
		$this->withusercreate = 1;
		$this->withcreatereadonly = 1;
		$this->withemail = 0;
		$this->withref = 0;
		$this->withextrafields = 0;  // to show extrafields or not
		//$this->withtopicreadonly=0;
	}

	/**
	 *
	 * Check required fields
	 *
	 * @param array<string, array<string, string>> $fields Array of fields to check
	 * @param int $errors Reference of errors variable
	 * @return void
	 */
	public static function checkRequiredFields(array $fields, int &$errors)
	{
		global $langs;

		foreach ($fields as $field => $type) {
			if (!GETPOST($field, $type['check'])) {
				$errors++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities($type['langs'])), null, 'errors');
			}
		}
	}

	/**
	 * Show the form to input ticket
	 *
	 * @param  	int	 		$withdolfichehead		With dol_get_fiche_head() and dol_get_fiche_end()
	 * @param	'create'|'edit'	$mode				Mode ('create' or 'edit')
	 * @param	int<0,1>	$public					1=If we show the form for the public interface
	 * @param	?Contact	$with_contact			[=NULL] Contact to link to this ticket if it exists
	 * @param	string		$action					[=''] Action in card
	 * @param	?Ticket		$object					[=NULL] Ticket object
	 * @return 	void
	 */
	public function showForm($withdolfichehead = 0, $mode = 'edit', $public = 0, $with_contact = null, $action = '', $object = null)
	{
		global $conf, $langs, $user, $hookmanager;

		// Load translation files required by the page
		$langs->loadLangs(array('other', 'mails', 'ticket'));

		if ($mode == 'create') {
			$ref = GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : '';
			$type_code = GETPOSTISSET('type_code') ? GETPOST('type_code', 'alpha') : '';
			$category_code = GETPOSTISSET('category_code') ? GETPOST('category_code', 'alpha') : '';
			$severity_code = GETPOSTISSET('severity_code') ? GETPOST('severity_code', 'alpha') : '';
			$subject = GETPOSTISSET('subject') ? GETPOST('subject', 'alpha') : '';
			$email = GETPOSTISSET('email') ? GETPOST('email', 'alpha') : '';
			$msg = GETPOSTISSET('message') ? GETPOST('message', 'restricthtml') : '';
			$projectid = GETPOSTISSET('projectid') ? GETPOST('projectid', 'int') : '';
			$user_assign = GETPOSTISSET('fk_user_assign') ? GETPOSTINT('fk_user_assign') : $this->fk_user_create;
		} else {
			$ref = GETPOSTISSET("ref") ? GETPOST("ref", 'alpha') : $object->ref;
			$type_code = GETPOSTISSET('type_code') ? GETPOST('type_code', 'alpha') : $object->type_code;
			$category_code = GETPOSTISSET('category_code') ? GETPOST('category_code', 'alpha') : $object->category_code;
			$severity_code = GETPOSTISSET('severity_code') ? GETPOST('severity_code', 'alpha') : $object->severity_code;
			$subject = GETPOSTISSET('subject') ? GETPOST('subject', 'alpha') : $object->subject;
			$email = GETPOSTISSET('email') ? GETPOST('email', 'alpha') : $object->email_from;
			$msg = GETPOSTISSET('message') ? GETPOST('message', 'restricthtml') : $object->message;
			$projectid = GETPOSTISSET('projectid') ? GETPOST('projectid', 'int') : $object->fk_project;
			$user_assign = GETPOSTISSET('fk_user_assign') ? GETPOSTINT('fk_user_assign') : $object->fk_user_assign;
		}

		$form = new Form($this->db);
		$formcompany = new FormCompany($this->db);
		$ticketstatic = new Ticket($this->db);

		$soc = new Societe($this->db);
		if (!empty($this->withfromsocid) && $this->withfromsocid > 0) {
			$soc->fetch($this->withfromsocid);
		}

		$ticketstat = new Ticket($this->db);

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label($ticketstat->table_element);

		print "\n<!-- Begin form TICKET -->\n";

		if ($withdolfichehead) {
			print dol_get_fiche_head([], 'card', '', 0, '');
		}

		print '<form method="POST" '.($withdolfichehead ? '' : 'style="margin-bottom: 30px;" ').'name="ticket" id="form_create_ticket" enctype="multipart/form-data" action="'.(!empty($this->param["returnurl"]) ? $this->param["returnurl"] : $_SERVER['PHP_SELF']).'">';

		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$this->action.'">';
		if (!empty($object->id)) {
			print '<input type="hidden" name="id" value="'. $object->id .'">';
		}
		print '<input type="hidden" name="trackid" value="'.$this->trackid.'">';
		foreach ($this->param as $key => $value) {
			print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
		}
		print '<input type="hidden" name="fk_user_create" value="'.$this->fk_user_create.'">';

		print '<table class="border centpercent">';

		// Ref
		if ($this->withref) {
			$defaultref = $ticketstat->getDefaultRef();

			if ($mode == 'edit') {
				$defaultref = $object->ref;
			}
			print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
			print '<input type="text" name="ref" value="'.dol_escape_htmltag($defaultref).'">';
			print '</td></tr>';
		}

		// Title
		if ($this->withemail) {
			print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("Email").'</span></label></td><td>';
			print '<input class="text minwidth200" id="email" name="email" value="'.$email.'" autofocus>';	// Do not use "required", it breaks button cancel
			print '</td></tr>';

			if ($with_contact) {
				// contact search and result
				$html_contact_search = '';
				$html_contact_search .= '<tr id="contact_search_line">';
				$html_contact_search .= '<td class="titlefield">';
				$html_contact_search .= '<label for="contact"><span class="fieldrequired">' . $langs->trans('Contact') . '</span></label>';
				$html_contact_search .= '<input type="hidden" id="contact_id" name="contact_id" value="" />';
				$html_contact_search .= '</td>';
				$html_contact_search .= '<td id="contact_search_result"></td>';
				$html_contact_search .= '</tr>';
				print $html_contact_search;
				// contact lastname
				$html_contact_lastname = '';
				$html_contact_lastname .= '<tr id="contact_lastname_line" class="contact_field"><td class="titlefield"><label for="contact_lastname"><span class="fieldrequired">' . $langs->trans('Lastname') . '</span></label></td><td>';
				$html_contact_lastname .= '<input type="text" id="contact_lastname" name="contact_lastname" value="' . dol_escape_htmltag(GETPOSTISSET('contact_lastname') ? GETPOST('contact_lastname', 'alphanohtml') : '') . '" />';
				$html_contact_lastname .= '</td></tr>';
				print $html_contact_lastname;
				// contact firstname
				$html_contact_firstname = '';
				$html_contact_firstname .= '<tr id="contact_firstname_line" class="contact_field"><td class="titlefield"><label for="contact_firstname"><span class="fieldrequired">' . $langs->trans('Firstname') . '</span></label></td><td>';
				$html_contact_firstname .= '<input type="text" id="contact_firstname" name="contact_firstname" value="' . dol_escape_htmltag(GETPOSTISSET('contact_firstname') ? GETPOST('contact_firstname', 'alphanohtml') : '') . '" />';
				$html_contact_firstname .= '</td></tr>';
				print $html_contact_firstname;
				// company name
				$html_company_name = '';
				$html_company_name .= '<tr id="contact_company_name_line" class="contact_field"><td><label for="company_name"><span>' . $langs->trans('Company') . '</span></label></td><td>';
				$html_company_name .= '<input type="text" id="company_name" name="company_name" value="' . dol_escape_htmltag(GETPOSTISSET('company_name') ? GETPOST('company_name', 'alphanohtml') : '') . '" />';
				$html_company_name .= '</td></tr>';
				print $html_company_name;
				// contact phone
				$html_contact_phone = '';
				$html_contact_phone .= '<tr id="contact_phone_line" class="contact_field"><td><label for="contact_phone"><span>' . $langs->trans('Phone') . '</span></label></td><td>';
				$html_contact_phone .= '<input type="text" id="contact_phone" name="contact_phone" value="' . dol_escape_htmltag(GETPOSTISSET('contact_phone') ? GETPOST('contact_phone', 'alphanohtml') : '') . '" />';
				$html_contact_phone .= '</td></tr>';
				print $html_contact_phone;

				// search contact form email
				$langs->load('errors');
				print '<script nonce="'.getNonce().'" type="text/javascript">
                    jQuery(document).ready(function() {
                        var contact = jQuery.parseJSON("'.dol_escape_js(json_encode($with_contact), 2).'");
                        jQuery("#contact_search_line").hide();
                        if (contact) {
                        	if (contact.id > 0) {
                        		jQuery("#contact_search_line").show();
                        		jQuery("#contact_id").val(contact.id);
								jQuery("#contact_search_result").html(contact.firstname+" "+contact.lastname);
								jQuery(".contact_field").hide();
                        	} else {
                        		jQuery(".contact_field").show();
                        	}
                        }

                    	jQuery("#email").change(function() {
                            jQuery("#contact_search_line").show();
                            jQuery("#contact_search_result").html("'.dol_escape_js($langs->trans('Select2SearchInProgress')).'");
                            jQuery("#contact_id").val("");
                            jQuery("#contact_lastname").val("");
                            jQuery("#contact_firstname").val("");
                            jQuery("#company_name").val("");
                            jQuery("#contact_phone").val("");

                            jQuery.getJSON(
                                "'.dol_escape_js(dol_buildpath('/public/ticket/ajax/ajax.php', 1)).'",
								{
									action: "getContacts",
									email: jQuery("#email").val()
								},
								function(response) {
									if (response.error) {
                                        jQuery("#contact_search_result").html("<span class=\"error\">"+response.error+"</span>");
									} else {
                                        var contact_list = response.contacts;
										if (contact_list.length == 1) {
                                            var contact = contact_list[0];
											jQuery("#contact_id").val(contact.id);
											jQuery("#contact_search_result").html(contact.firstname+" "+contact.lastname);
                                            jQuery(".contact_field").hide();
										} else if (contact_list.length <= 0) {
                                            jQuery("#contact_search_line").hide();
                                            jQuery(".contact_field").show();
										}
									}
								}
                            ).fail(function(jqxhr, textStatus, error) {
    							var error_msg = "'.dol_escape_js($langs->trans('ErrorAjaxRequestFailed')).'"+" ["+textStatus+"] : "+error;
                                jQuery("#contact_search_result").html("<span class=\"error\">"+error_msg+"</span>");
                            });
                        });
                    });
                    </script>';
			}
		}

		// If ticket created from another object
		$subelement = '';
		if (isset($this->param['origin']) && $this->param['originid'] > 0) {
			// Parse element/subelement (ex: project_task)
			$element = $subelement = $this->param['origin'];
			$regs = array();
			if (preg_match('/^([^_]+)_([^_]+)/i', $this->param['origin'], $regs)) {
				$element = $regs[1];
				$subelement = $regs[2];
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
			$classname = ucfirst($subelement);
			$objectsrc = new $classname($this->db);
			'@phan-var-force CommonObject $objectsrc';
			$objectsrc->fetch(GETPOSTINT('originid'));

			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
				$objectsrc->fetch_lines();
			}

			$objectsrc->fetch_thirdparty();
			$newclassname = $classname;
			print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2"><input name="'.$subelement.'id" value="'.GETPOST('originid').'" type="hidden" />'.$objectsrc->getNomUrl(1).'</td></tr>';
		}

		// Type of Ticket
		print '<tr><td class="titlefield"><span class="fieldrequired"><label for="selecttype_code">'.$langs->trans("TicketTypeRequest").'</span></label></td><td>';
		$this->selectTypesTickets($type_code, 'type_code', '', 2, 'ifone', 0, 0, 'minwidth200 maxwidth500');
		print '</td></tr>';

		// Group => Category
		print '<tr><td><span class="fieldrequired"><label for="selectcategory_code">'.$langs->trans("TicketCategory").'</span></label></td><td>';
		$filter = '';
		if ($public) {
			$filter = '(public:=:1)';
		}
		$this->selectGroupTickets($category_code, 'category_code', $filter, 2, 'ifone', 0, 0, 'minwidth200 maxwidth500');
		print '</td></tr>';

		// Severity => Priority
		print '<tr><td><span class="fieldrequired"><label for="selectseverity_code">'.$langs->trans("TicketSeverity").'</span></label></td><td>';
		$this->selectSeveritiesTickets($severity_code, 'severity_code', '', 2, 'ifone', 0, 0, 'minwidth200 maxwidth500');
		print '</td></tr>';

		if (isModEnabled('knowledgemanagement')) {
			// KM Articles
			print '<tr id="KWwithajax" class="hidden"><td></td></tr>';
			print '<!-- Script to manage change of ticket group -->
			<script nonce="'.getNonce().'">
			jQuery(document).ready(function() {
				function groupticketchange() {
					console.log("We called groupticketchange, so we try to load list KM linked to event");
					$("#KWwithajax").html("");
					idgroupticket = $("#selectcategory_code").val();

					console.log("We have selected id="+idgroupticket);

					if (idgroupticket != "") {
						$.ajax({ url: \''.DOL_URL_ROOT.'/core/ajax/fetchKnowledgeRecord.php\',
							 data: { action: \'getKnowledgeRecord\', idticketgroup: idgroupticket, token: \''.newToken().'\', lang:\''.$langs->defaultlang.'\', public:'.($public).' },
							 type: \'GET\',
							 success: function(response) {
								var urllist = \'\';
								console.log("We received response "+response);
								if (typeof response == "object") {
									console.log("response is already type object, no need to parse it");
								} else {
									console.log("response is type "+(typeof response));
									response = JSON.parse(response);
								}
								for (key in response) {
									answer = response[key].answer;
									urllist += \'<li><a href="#" title="\'+response[key].title+\'" class="button_KMpopup" data-html="\'+answer+\'">\' +response[key].title+\'</a></li>\';
								}
								if (urllist != "") {
									$("#KWwithajax").html(\'<td>'.$langs->trans("KMFoundForTicketGroup").'</td><td><ul>\'+urllist+\'</ul></td>\');
									$("#KWwithajax").show();
									$(".button_KMpopup").on("click",function(){
										console.log("Open popup with jQuery(...).dialog() with KM article")
										var $dialog = $("<div></div>").html($(this).attr("data-html"))
											.dialog({
												autoOpen: false,
												modal: true,
												height: (window.innerHeight - 150),
												width: "80%",
												title: $(this).attr("title"),
											});
										$dialog.dialog("open");
										console.log($dialog);
									})
								}
							 },
							 error : function(output) {
								console.error("Error on Fetch of KM articles");
							 },
						});
					}
				};
				$("#selectcategory_code").on("change",function() { groupticketchange(); });
				if ($("#selectcategory_code").val() != "") {
					groupticketchange();
				}
			});
			</script>'."\n";
		}

		// Subject
		if ($this->withtitletopic) {
			print '<tr><td><label for="subject"><span class="fieldrequired">'.$langs->trans("Subject").'</span></label></td><td>';
			// Answer to a ticket : display of the thread title in readonly
			if ($this->withtopicreadonly) {
				print $langs->trans('SubjectAnswerToTicket').' '.$this->topic_title;
			} else {
				if (isset($this->withreadid) && $this->withreadid > 0) {
					$subject = $langs->trans('SubjectAnswerToTicket').' '.$this->withreadid.' : '.$this->topic_title;
				}
				print '<input class="text minwidth500" id="subject" name="subject" value="'.$subject.'"'.(empty($this->withemail) ? ' autofocus' : '').' />';
			}
			print '</td></tr>';
		}

		// Message
		print '<tr><td><label for="message"><span class="fieldrequired">'.$langs->trans("Message").'</span></label></td><td>';

		// If public form, display more information
		$toolbarname = 'dolibarr_notes';
		if ($this->ispublic) {
			$toolbarname = 'dolibarr_details';	// TODO Allow image so use can do paste of image into content but disallow file manager
			print '<div class="warning hideonsmartphone">'.(getDolGlobalString("TICKET_PUBLIC_TEXT_HELP_MESSAGE", $langs->trans('TicketPublicPleaseBeAccuratelyDescribe'))).'</div>';
		}
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$uselocalbrowser = true;
		$doleditor = new DolEditor('message', $msg, '100%', 230, $toolbarname, 'In', true, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_8, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Categories
		if (isModEnabled('category') && !$public) {
			// Categories
			print '<tr><td class="wordbreak"></td><td>';
			print $form->selectCategories(Categorie::TYPE_TICKET, 'categories', $object);
			print "</td></tr>";
		}

		// Attached files
		if (!empty($this->withfile)) {
			// Define list of attached files
			$listofpaths = array();
			$listofnames = array();
			$listofmimes = array();
			if (!empty($_SESSION["listofpaths"])) {
				$listofpaths = explode(';', $_SESSION["listofpaths"]);
			}

			if (!empty($_SESSION["listofnames"])) {
				$listofnames = explode(';', $_SESSION["listofnames"]);
			}

			if (!empty($_SESSION["listofmimes"])) {
				$listofmimes = explode(';', $_SESSION["listofmimes"]);
			}

			$out = '<tr>';
			$out .= '<td></td>';
			$out .= '<td>';
			// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
			$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
			$out .= '<script nonce="'.getNonce().'" type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '    jQuery(".removedfile").click(function() {';
			$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
			$out .= '    });';
			$out .= '})';
			$out .= '</script>'."\n";
			if (count($listofpaths)) {
				foreach ($listofpaths as $key => $val) {
					$out .= '<div id="attachfile_'.$key.'">';
					$out .= img_mime($listofnames[$key]).' '.$listofnames[$key];
					if (!$this->withfilereadonly) {
						$out .= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key + 1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
					}
					$out .= '<br></div>';
				}
			}
			if ($this->withfile == 2) { // Can add other files
				$maxfilesizearray = getMaxFileSizeArray();
				$maxmin = $maxfilesizearray['maxmin'];
				if ($maxmin > 0) {
					$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
				}
				$out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
				$out .= ' ';
				$out .= '<input type="submit" class="button smallpaddingimp reposition" id="addfile" name="addfile" value="'.$langs->trans("MailingAddFile").'" />';
			}
			$out .= "</td></tr>\n";

			print $out;
		}

		// User of creation
		if ($this->withusercreate > 0 && $this->fk_user_create) {
			print '<tr><td class="titlefield">'.$langs->trans("CreatedBy").'</td><td>';
			$langs->load("users");
			$fuser = new User($this->db);

			if ($this->withcreatereadonly) {
				if ($res = $fuser->fetch($this->fk_user_create)) {
					print $fuser->getNomUrl(1);
				}
			}
			print ' &nbsp; ';
			print "</td></tr>\n";
		}

		// Customer or supplier
		if ($this->withcompany) {
			// force company and contact id for external user
			if (empty($user->socid)) {
				// Company
				print '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>';
				$events = array();
				$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
				print img_picto('', 'company', 'class="paddingright"');
				print $form->select_company($this->withfromsocid, 'socid', '', 1, 1, 0, $events, 0, 'minwidth200');
				print '</td></tr>';
				if (!empty($conf->use_javascript_ajax) && getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT')) {
					$htmlname = 'socid';
					print '<script nonce="'.getNonce().'" type="text/javascript">
                    $(document).ready(function () {
                        jQuery("#'.$htmlname.'").change(function () {
                            var obj = '.json_encode($events).';
                            $.each(obj, function(key,values) {
                                if (values.method.length) {
                                    runJsCodeForEvent'.$htmlname.'(values);
                                }
                            });
                        });

                        function runJsCodeForEvent'.$htmlname.'(obj) {
                            console.log("Run runJsCodeForEvent'.$htmlname.'");
                            var id = $("#'.$htmlname.'").val();
                            var method = obj.method;
                            var url = obj.url;
                            var htmlname = obj.htmlname;
                            var showempty = obj.showempty;
                            $.getJSON(url,
                                    {
                                        action: method,
                                        id: id,
                                        htmlname: htmlname,
                                        showempty: showempty
                                    },
                                    function(response) {
                                        $.each(obj.params, function(key,action) {
                                            if (key.length) {
                                                var num = response.num;
                                                if (num > 0) {
                                                    $("#" + key).removeAttr(action);
                                                } else {
                                                    $("#" + key).attr(action, action);
                                                }
                                            }
                                        });
                                        $("select#" + htmlname).html(response.value);
                                        if (response.num) {
                                            var selecthtml_str = response.value;
                                            var selecthtml_dom=$.parseHTML(selecthtml_str);
											if (typeof(selecthtml_dom[0][0]) !== \'undefined\') {
                                            	$("#inputautocomplete"+htmlname).val(selecthtml_dom[0][0].innerHTML);
											}
                                        } else {
                                            $("#inputautocomplete"+htmlname).val("");
                                        }
                                        $("select#" + htmlname).change();	/* Trigger event change */
                                    }
                            );
                        }
                    });
                    </script>';
				}
				if ($mode == 'create') {
					// Contact and type
					print '<tr><td>'.$langs->trans("Contact").'</td><td>';
					// If no socid, set to -1 to avoid full contacts list
					$selectedCompany = ($this->withfromsocid > 0) ? $this->withfromsocid : -1;
					print img_picto('', 'contact', 'class="paddingright"');
					// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
					print $form->select_contact($selectedCompany, $this->withfromcontactid, 'contactid', 3, '', '', 1, 'maxwidth300 widthcentpercentminusx', true);

					print ' ';
					$formcompany->selectTypeContact($ticketstatic, '', 'type', 'external', '', 0, 'maginleftonly');
					print '</td></tr>';
				}
			} else {
				print '<tr><td class="titlefield"><input type="hidden" name="socid" value="'.$user->socid.'"/></td>';
				print '<td><input type="hidden" name="contactid" value="'.$user->contact_id.'"/></td>';
				print '<td><input type="hidden" name="type" value="Z"/></td></tr>';
			}

			// Notify thirdparty at creation
			if (empty($this->ispublic) && $action == 'create') {
				print '<tr><td><label for="notify_tiers_at_create">'.$langs->trans("TicketNotifyTiersAtCreation").'</label></td><td>';
				print '<input type="checkbox" id="notify_tiers_at_create" name="notify_tiers_at_create"'.($this->withnotifytiersatcreate ? ' checked="checked"' : '').'>';
				print '</td></tr>';
			}

			// User assigned
			print '<tr><td>';
			print $langs->trans("AssignedTo");
			print '</td><td>';
			print img_picto('', 'user', 'class="pictofixedwidth"');
			print $form->select_dolusers($user_assign, 'fk_user_assign', 1);
			print '</td>';
			print '</tr>';
		}

		if ($subelement != 'project') {
			if (isModEnabled('project') && !$this->ispublic) {
				$formproject = new FormProjets($this->db);
				print '<tr><td><label for="project"><span class="">'.$langs->trans("Project").'</span></label></td><td>';
				print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(-1, $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
				print '</td></tr>';
			}
		}

		if ($subelement != 'contract' && $subelement != 'contrat') {
			if (isModEnabled('contract') && !$this->ispublic) {
				$langs->load('contracts');
				$formcontract = new FormContract($this->db);
				print '<tr><td><label for="contract"><span class="">'.$langs->trans("Contract").'</span></label></td><td>';
				print img_picto('', 'contract', 'class="pictofixedwidth"');
				// socid is for internal users null and not 0 or -1
				print $formcontract->select_contract($user->socid ?? -1, GETPOSTINT('contractid'), 'contractid', 0, 1, 1, 1);
				print '</td></tr>';
			}
		}

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $ticketstat, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			if ($mode == 'create') {
				print $object->showOptionals($extrafields, 'create');
			} else {
				print $object->showOptionals($extrafields, 'edit');
			}
		}

		// Show line with Captcha
		$captcha = '';
		if ($public && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_TICKET')) {
			print '<tr><td class="titlefield"></td><td><br>';

			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$captcha = getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_HANDLER', 'standard');

			// List of directories where we can find captcha handlers
			$dirModCaptcha = array_merge(array('main' => '/core/modules/security/captcha/'), is_array($conf->modules_parts['captcha']) ? $conf->modules_parts['captcha'] : array());

			$fullpathclassfile = '';
			foreach ($dirModCaptcha as $dir) {
				$fullpathclassfile = dol_buildpath($dir."modCaptcha".ucfirst($captcha).'.class.php', 0, 2);
				if ($fullpathclassfile) {
					break;
				}
			}

			if ($fullpathclassfile) {
				include_once $fullpathclassfile;
				$captchaobj = null;

				// Charging the numbering class
				$classname = "modCaptcha".ucfirst($captcha);
				if (class_exists($classname)) {
					/** @var ModeleCaptcha $captchaobj */
					$captchaobj = new $classname($this->db, $conf, $langs, $user);
					'@phan-var-force ModeleCaptcha $captchaobj';

					if (is_object($captchaobj) && method_exists($captchaobj, 'getCaptchaCodeForForm')) {
						print $captchaobj->getCaptchaCodeForForm('');  // @phan-suppress-current-line PhanUndeclaredMethod
					} else {
						print 'Error, the captcha handler '.get_class($captchaobj).' does not have any method getCaptchaCodeForForm()';
					}
				} else {
					print 'Error, the captcha handler class '.$classname.' was not found after the include';
				}
			} else {
				print 'Error, the captcha handler '.$captcha.' has no class file found modCaptcha'.ucfirst($captcha);
			}

			print '<br></td></tr>';
		}

		print '</table>';

		if ($withdolfichehead) {
			print dol_get_fiche_end();
		}

		print '<br><br>';

		if ($mode == 'create') {
			print $form->buttonsSaveCancel(((isset($this->withreadid) && $this->withreadid > 0) ? "SendResponse" : "CreateTicket"), ($this->withcancel ? "Cancel" : ""));
		} else {
			print $form->buttonsSaveCancel(((isset($this->withreadid) && $this->withreadid > 0) ? "SendResponse" : "Save"), ($this->withcancel ? "Cancel" : ""));
		}

		print '<br>';

		/*
		print '<div class="center">';
		print '<input type="submit" class="button" name="add" value="'.$langs->trans(($this->withreadid > 0 ? "SendResponse" : "CreateTicket")).'" />';
		if ($this->withcancel) {
			print " &nbsp; &nbsp; &nbsp;";
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</div>';
		*/

		print '<input type="hidden" name="page_y">'."\n";

		print "</form>\n";
		print "<!-- End form TICKET -->\n";
	}

	/**
	 *      Return html list of tickets type
	 *
	 *      @param  string|int[]	$selected		Id of preselected field or array of Ids
	 *      @param  string			$htmlname		Nom de la zone select
	 *      @param  string			$filtertype		To filter on field type in llx_c_ticket_type (array('code'=>xx,'label'=>zz))
	 *      @param  int				$format			0=id+label, 1=code+code, 2=code+label, 3=id+code
	 *      @param  int|string		$empty      	1 = can be empty or 'string' to show the string as the empty value, 0 = can't be empty, 'ifone' = can be empty but autoselected if there is one only
	 *      @param  int				$noadmininfo	0=Add admin info, 1=Disable admin info
	 *      @param  int				$maxlength		Max length of label
	 *      @param	string			$morecss		More CSS
	 *      @param  int				$multiselect	Is multiselect ?
	 *      @return void
	 */
	public function selectTypesTickets($selected = '', $htmlname = 'tickettype', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '', $multiselect = 0)
	{
		global $langs, $user;

		$selected = is_array($selected) ? $selected : (!empty($selected) ? explode(',', $selected) : array());
		$ticketstat = new Ticket($this->db);

		dol_syslog(get_class($this) . "::select_types_tickets " . implode(';', $selected) . ", " . $htmlname . ", " . $filtertype . ", " . $format . ", " . $multiselect, LOG_DEBUG);

		$filterarray = array();

		if ($filtertype != '' && $filtertype != '-1') {
			$filterarray = explode(',', $filtertype);
		}

		$ticketstat->loadCacheTypesTickets();

		print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.($multiselect ? '[]' : '').'"'.($multiselect ? ' multiple' : '').'>';
		if ($empty && !$multiselect) {
			print '<option value="">'.((is_numeric($empty) || $empty == 'ifone') ? '&nbsp;' : $empty).'</option>';
		}

		if (is_array($ticketstat->cache_types_tickets) && count($ticketstat->cache_types_tickets)) {
			foreach ($ticketstat->cache_types_tickets as $id => $arraytypes) {
				// On passe si on a demande de filtrer sur des modes de paiments particuliers
				if (count($filterarray) && !in_array($arraytypes['type'], $filterarray)) {
					continue;
				}

				// If 'showempty' is enabled we discard empty line because an empty line has already been output.
				if ($empty && empty($arraytypes['code'])) {
					continue;
				}

				if ($format == 0) {
					print '<option value="'.$id.'"';
				}

				if ($format == 1) {
					print '<option value="'.$arraytypes['code'].'"';
				}

				if ($format == 2) {
					print '<option value="'.$arraytypes['code'].'"';
				}

				if ($format == 3) {
					print '<option value="'.$id.'"';
				}

				// If text is selected, we compare with code, otherwise with id
				if (in_array($arraytypes['code'], $selected)) {
					print ' selected="selected"';
				} elseif (in_array($id, $selected)) {
					print ' selected="selected"';
				} elseif ($arraytypes['use_default'] == "1" && empty($selected) && !$multiselect) {
					print ' selected="selected"';
				} elseif (count($ticketstat->cache_types_tickets) == 1 && (!$empty || $empty == 'ifone')) {	// If only 1 choice, we autoselect it
					print ' selected="selected"';
				}

				print '>';

				$value = '&nbsp;';
				if ($format == 0) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 1) {
					$value = $arraytypes['code'];
				} elseif ($format == 2) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 3) {
					$value = $arraytypes['code'];
				}

				print $value ? $value : '&nbsp;';
				print '</option>';
			}
		}
		print '</select>';
		if (isset($user->admin) && $user->admin && !$noadmininfo) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}

		print ajax_combobox('select'.$htmlname);
	}

	/**
	 *      Return html list of ticket analytic codes
	 *
	 *      @param  string 		$selected   		Id pre-selected category
	 *      @param  string 		$htmlname   		Name of select component
	 *      @param  string 		$filtertype 		To filter on some properties in llx_c_ticket_category ('public = 1'). This parameter must not come from input of users.
	 *      @param  int    		$format     		0 = id+label, 1 = code+code, 2 = code+label, 3 = id+code
	 *      @param  int|string	$empty      		1 = can be empty or 'string' to show the string as the empty value, 0 = can't be empty, 'ifone' = can be empty but autoselected if there is one only
	 *      @param  int    		$noadmininfo		0 = ddd admin info, 1 = disable admin info
	 *      @param  int    		$maxlength  		Max length of label
	 *      @param	string		$morecss			More CSS
	 * 		@param	int 		$use_multilevel		If > 0 create a multilevel select which use $htmlname example: $use_multilevel = 1 permit to have 2 select boxes.
	 * 		@param	Translate	$outputlangs		Output language
	 *      @return string|void						String of HTML component
	 */
	public function selectGroupTickets($selected = '', $htmlname = 'ticketcategory', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '', $use_multilevel = 0, $outputlangs = null)
	{
		global $conf, $langs, $user;

		dol_syslog(get_class($this)."::selectCategoryTickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		if (is_null($outputlangs) || !is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->load("ticket");

		$publicgroups = ($filtertype == 'public=1' || $filtertype == '(public:=:1)');

		$ticketstat = new Ticket($this->db);
		$ticketstat->loadCacheCategoriesTickets($publicgroups ? 1 : -1);	// get list of active ticket groups

		if ($use_multilevel <= 0) {	// Only one combo list to select the group of ticket (default)
			print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
			if ($empty) {
				print '<option value="">'.((is_numeric($empty) || $empty == 'ifone') ? '&nbsp;' : $empty).'</option>';
			}

			if (is_array($conf->cache['category_tickets']) && count($conf->cache['category_tickets'])) {
				foreach ($conf->cache['category_tickets'] as $id => $arraycategories) {
					// Exclude some record
					if ($publicgroups) {
						if (empty($arraycategories['public'])) {
							continue;
						}
					}

					// We discard empty line if showempty is on because an empty line has already been output.
					if ($empty && empty($arraycategories['code'])) {
						continue;
					}

					$label = ($arraycategories['label'] != '-' ? $arraycategories['label'] : '');
					if ($outputlangs->trans("TicketCategoryShort".$arraycategories['code']) != "TicketCategoryShort".$arraycategories['code']) {
						$label = $outputlangs->trans("TicketCategoryShort".$arraycategories['code']);
					} elseif ($outputlangs->trans($arraycategories['code']) != $arraycategories['code']) {
						$label = $outputlangs->trans($arraycategories['code']);
					}

					if ($format == 0) {
						print '<option value="'.$id.'"';
					}

					if ($format == 1) {
						print '<option value="'.$arraycategories['code'].'"';
					}

					if ($format == 2) {
						print '<option value="'.$arraycategories['code'].'"';
					}

					if ($format == 3) {
						print '<option value="'.$id.'"';
					}

					// If selected is text, we compare with code, otherwise with id
					if (isset($selected) && preg_match('/[a-z]/i', $selected) && $selected == $arraycategories['code']) {
						print ' selected="selected"';
					} elseif (isset($selected) && $selected == $id) {
						print ' selected="selected"';
					} elseif ($arraycategories['use_default'] == "1" && empty($selected) && (!$empty || $empty == 'ifone')) {
						print ' selected="selected"';
					} elseif (count($conf->cache['category_tickets']) == 1 && (!$empty || $empty == 'ifone')) {	// If only 1 choice, we autoselect it
						print ' selected="selected"';
					}

					print '>';

					$value = '';
					if ($format == 0) {
						$value = ($maxlength ? dol_trunc($label, $maxlength) : $label);
					}

					if ($format == 1) {
						$value = $arraycategories['code'];
					}

					if ($format == 2) {
						$value = ($maxlength ? dol_trunc($label, $maxlength) : $label);
					}

					if ($format == 3) {
						$value = $arraycategories['code'];
					}

					print $value ? $value : '&nbsp;';
					print '</option>';
				}
			}
			print '</select>';
			if (isset($user->admin) && $user->admin && !$noadmininfo) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			print ajax_combobox('select'.$htmlname);
		} elseif ($htmlname != '') {	// complexe mode using selection of group using a combo for each level (when using a hierarchy of groups).
			$selectedgroups = array();
			$groupvalue = "";
			$groupticket = GETPOST($htmlname, 'aZ09');
			$child_id = GETPOST($htmlname.'_child_id', 'aZ09') ? GETPOST($htmlname.'_child_id', 'aZ09') : 0;
			if (!empty($groupticket)) {
				$tmpgroupticket = $groupticket;
				$sql = "SELECT ctc.rowid, ctc.fk_parent, ctc.code";
				$sql .= " FROM ".$this->db->prefix()."c_ticket_category as ctc WHERE ctc.code = '".$this->db->escape($tmpgroupticket)."'";
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$selectedgroups[] = $obj->code;
					while ($obj->fk_parent > 0) {
						$sql = "SELECT ctc.rowid, ctc.fk_parent, ctc.code FROM ".$this->db->prefix()."c_ticket_category as ctc WHERE ctc.rowid ='".$this->db->escape($obj->fk_parent)."'";
						$resql = $this->db->query($sql);
						if ($resql) {
							$obj = $this->db->fetch_object($resql);
							$selectedgroups[] = $obj->code;
						}
					}
				}
			}

			$arrayidused = array();
			$arrayidusedconcat = array();
			$arraycodenotparent = array();
			$arraycodenotparent[] = "";

			$stringtoprint = '<span class="supportemailfield bold">'.$langs->trans("GroupOfTicket").'</span> ';
			$stringtoprint .= '<select id="'.$htmlname.'" class="minwidth500" child_id="0">';
			$stringtoprint .= '<option value="">&nbsp;</option>';

			$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ctc.public, ";
			$sql .= $this->db->ifsql("ctc.rowid NOT IN (SELECT ctcfather.rowid FROM ".MAIN_DB_PREFIX."c_ticket_category as ctcfather JOIN ".MAIN_DB_PREFIX."c_ticket_category as ctcjoin ON ctcfather.rowid = ctcjoin.fk_parent WHERE ctcjoin.active > 0)", "'NOTPARENT'", "'PARENT'")." as isparent";
			$sql .= " FROM ".$this->db->prefix()."c_ticket_category as ctc";
			$sql .= " WHERE ctc.active > 0 AND ctc.entity = ".((int) $conf->entity);
			$public = ($filtertype == 'public=1' || $filtertype == '(public:=:1)');
			if ($public) {
				$sql .= " AND ctc.public = 1";
			}
			$sql .= " AND ctc.fk_parent = 0";
			$sql .= $this->db->order('ctc.pos', 'ASC');

			$resql = $this->db->query($sql);
			if ($resql) {
				$num_rows_level0 = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num_rows_level0) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$label = ($obj->label != '-' ? $obj->label : '');
						if ($outputlangs->trans("TicketCategoryShort".$obj->code) != "TicketCategoryShort".$obj->code) {
							$label = $outputlangs->trans("TicketCategoryShort".$obj->code);
						} elseif ($outputlangs->trans($obj->code) != $obj->code) {
							$label = $outputlangs->trans($obj->code);
						}

						$grouprowid = $obj->rowid;
						$groupvalue = $obj->code;
						$grouplabel = $label;

						$isparent = $obj->isparent;
						if (is_array($selectedgroups)) {
							$iselected = in_array($obj->code, $selectedgroups) ? 'selected' : '';
						} else {
							$iselected = $groupticket == $obj->code ? 'selected' : '';
						}
						$stringtoprint .= '<option '.$iselected.' class="'.$htmlname.dol_escape_htmltag($grouprowid).'" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
						if ($isparent == 'NOTPARENT') {
							$arraycodenotparent[] = $groupvalue;
						}
						$arrayidused[] = $grouprowid;
						$arrayidusedconcat[] = $grouprowid;
					}
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			if (count($arrayidused) == 1) {
				return '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.dol_escape_htmltag($groupvalue).'">';
			} else {
				$stringtoprint .= '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'_select" class="maxwidth500 minwidth400" value="'.GETPOST($htmlname).'">';
				$stringtoprint .= '<input type="hidden" name="'.$htmlname.'_child_id" id="'.$htmlname.'_select_child_id" class="maxwidth500 minwidth400" '.GETPOST($htmlname).' value="'.GETPOST($htmlname."_child_id").'">';
			}
			$stringtoprint .= '</select>&nbsp;';

			$levelid = 1;	// The first combobox
			while ($levelid <= $use_multilevel) {	// Loop to take the child of the combo
				$tabscript = array();
				$stringtoprint .= '<select id="'.$htmlname.'_child_'.$levelid.'" class="maxwidth500 minwidth400 groupticketchild" child_id="'.$levelid.'">';
				$stringtoprint .= '<option value="">&nbsp;</option>';

				$sql = "SELECT ctc.rowid, ctc.code, ctc.label, ctc.fk_parent, ctc.public, ctcjoin.code as codefather";
				$sql .= " FROM ".$this->db->prefix()."c_ticket_category as ctc";
				$sql .= " JOIN ".$this->db->prefix()."c_ticket_category as ctcjoin ON ctc.fk_parent = ctcjoin.rowid";
				$sql .= " WHERE ctc.active > 0 AND ctc.entity = ".((int) $conf->entity);
				$sql .= " AND ctc.rowid NOT IN (".$this->db->sanitize(implode(',', $arrayidusedconcat)).")";

				$public = ($filtertype == 'public=1' || $filtertype == '(public:=:1)');
				if ($public) {
					$sql .= " AND ctc.public = 1";
				}
				// Add a test to take only record that are direct child
				if (!empty($arrayidused)) {
					$sql .= " AND ctc.fk_parent IN ( ";
					foreach ($arrayidused as $idused) {
						$sql .= $idused.", ";
					}
					$sql = substr($sql, 0, -2);
					$sql .= ")";
				}
				$sql .= $this->db->order('ctc.pos', 'ASC');

				$resql = $this->db->query($sql);
				if ($resql) {
					$num_rows = $this->db->num_rows($resql);
					$i = 0;
					$arrayidused = array();
					while ($i < $num_rows) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$label = ($obj->label != '-' ? $obj->label : '');
							if ($outputlangs->trans("TicketCategoryShort".$obj->code) != "TicketCategoryShort".$obj->code) {
								$label = $outputlangs->trans("TicketCategoryShort".$obj->code);
							} elseif ($outputlangs->trans($obj->code) != $obj->code) {
								$label = $outputlangs->trans($obj->code);
							}

							$grouprowid = $obj->rowid;
							$groupvalue = $obj->code;
							$grouplabel = $label;
							$isparent = $obj->isparent;
							$fatherid = $obj->fk_parent;
							$arrayidused[] = $grouprowid;
							$arrayidusedconcat[] = $grouprowid;
							$groupcodefather = $obj->codefather;
							if ($isparent == 'NOTPARENT') {
								$arraycodenotparent[] = $groupvalue;
							}
							if (is_array($selectedgroups)) {
								$iselected = in_array($obj->code, $selectedgroups) ? 'selected' : '';
							} else {
								$iselected = $groupticket == $obj->code ? 'selected' : '';
							}
							$stringtoprint .= '<option '.$iselected.' class="'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'" value="'.dol_escape_htmltag($groupvalue).'" data-html="'.dol_escape_htmltag($grouplabel).'">'.dol_escape_htmltag($grouplabel).'</option>';
							if (empty($tabscript[$groupcodefather])) {
								$tabscript[$groupcodefather] = 'if ($("#'.$htmlname.($levelid > 1 ? '_child_'.($levelid - 1) : '').'").val() == "'.dol_escape_js($groupcodefather).'"){
									$(".'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'").show()
									console.log("We show child tickets of '.$groupcodefather.' group ticket")
								}else{
									$(".'.$htmlname.'_'.dol_escape_htmltag($fatherid).'_child_'.$levelid.'").hide()
									console.log("We hide child tickets of '.$groupcodefather.' group ticket")
								}';
							}
						}
						$i++;
					}
				} else {
					dol_print_error($this->db);
				}
				$stringtoprint .= '</select>';

				$stringtoprint .= '<script nonce="'.getNonce().'">';
				$stringtoprint .= 'arraynotparents = '.json_encode($arraycodenotparent).';';	// when the last visible combo list is number x, this is the array of group
				$stringtoprint .= 'if (arraynotparents.includes($("#'.$htmlname.($levelid > 1 ? '_child_'.($levelid - 1) : '').'").val())){
					console.log("'.$htmlname.'_child_'.$levelid.'")
					if($("#'.$htmlname.'_child_'.$levelid.'").val() == "" && ($("#'.$htmlname.'_child_'.$levelid.'").attr("child_id")>'.$child_id.')){
						$("#'.$htmlname.'_child_'.$levelid.'").hide();
						console.log("We hide '.$htmlname.'_child_'.$levelid.' input")
					}
					if(arraynotparents.includes("'.$groupticket.'") && '.$child_id.' == 0){
						$("#ticketcategory_select_child_id").val($("#'.$htmlname.'").attr("child_id"))
						$("#ticketcategory_select").val($("#'.$htmlname.'").val()) ;
						console.log("We choose '.$htmlname.' input and reload hidden input");
					}
				}
				$("#'.$htmlname.($levelid > 1 ? '_child_'.($levelid - 1) : '').'").change(function() {
					child_id = $("#'.$htmlname.($levelid > 1 ? '_child_'.$levelid : '').'").attr("child_id");

					/* Change of value to select this value*/
					if (arraynotparents.includes($(this).val()) || $(this).attr("child_id") == '.$use_multilevel.') {
						$("#ticketcategory_select").val($(this).val());
						$("#ticketcategory_select_child_id").val($(this).attr("child_id")) ;
						console.log("We choose to select "+ $(this).val());
					}else{
						if ($("#'.$htmlname.'_child_'.$levelid.' option").length <= 1) {
							$("#ticketcategory_select").val($(this).val());
							$("#ticketcategory_select_child_id").val($(this).attr("child_id"));
							console.log("We choose to select "+ $(this).val() + " and next combo has no item, so we keep this selection");
						} else {
							console.log("We choose to select "+ $(this).val() + " but next combo has some item, so we clean selected item");
							$("#ticketcategory_select").val("");
							$("#ticketcategory_select_child_id").val("");
						}
					}

					console.log("We select a new value into combo child_id="+child_id);

					// Hide all selected box that are child of the one modified
					$(".groupticketchild").each(function(){
						if ($(this).attr("child_id") > child_id) {
							console.log("hide child_id="+$(this).attr("child_id"));
							$(this).val("");
							$(this).hide();
						}
					})

					// Now we enable the next combo
					$("#'.$htmlname.'_child_'.$levelid.'").val("");
					if (!arraynotparents.includes($(this).val()) && $("#'.$htmlname.'_child_'.$levelid.' option").length > 1) {
						console.log($("#'.$htmlname.'_child_'.$levelid.' option").length);
						$("#'.$htmlname.'_child_'.$levelid.'").show()
					} else {
						$("#'.$htmlname.'_child_'.$levelid.'").hide()
					}
				';
				$levelid++;
				foreach ($tabscript as $script) {
					$stringtoprint .= $script;
				}
				$stringtoprint .= '})';
				$stringtoprint .= '</script>';
			}
			$stringtoprint .= '<script nonce="'.getNonce().'">';
			$stringtoprint .= '$("#'.$htmlname.'_child_'.$use_multilevel.'").change(function() {
				$("#ticketcategory_select").val($(this).val());
				$("#ticketcategory_select_child_id").val($(this).attr("child_id"));
				tmpvalselect = $("#ticketcategory_select").val();
				if(tmpvalselect == "" && $("#ticketcategory_select_child_id").val() >= 1){
					$("#ticketcategory_select_child_id").val($(this).attr("child_id")-1);
				}
				console.log($("#ticketcategory_select").val());
			})';
			$stringtoprint .= '</script>';
			$stringtoprint .= ajax_combobox($htmlname);

			return $stringtoprint;
		}
	}

	/**
	 *      Return html list of ticket severitys (priorities)
	 *
	 *      @param  string  	$selected    	Id severity pre-selected
	 *      @param  string  	$htmlname    	Name of the select area
	 *      @param  string  	$filtertype  	To filter on field type in llx_c_ticket_severity (array('code'=>xx,'label'=>zz))
	 *      @param  int     	$format      	0 = id+label, 1 = code+code, 2 = code+label, 3 = id+code
	 *      @param  int|string	$empty      	1 = can be empty or 'string' to show the string as the empty value, 0 = can't be empty, 'ifone' = can be empty but autoselected if there is one only
	 *      @param  int     	$noadmininfo 	0 = add admin info, 1 = disable admin info
	 *      @param  int     	$maxlength   	Max length of label
	 *      @param  string  	$morecss     	More CSS
	 *      @return void
	 */
	public function selectSeveritiesTickets($selected = '', $htmlname = 'ticketseverity', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $morecss = '')
	{
		global $conf, $langs, $user;

		$ticketstat = new Ticket($this->db);

		dol_syslog(get_class($this)."::selectSeveritiesTickets ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		$filterarray = array();

		if ($filtertype != '' && $filtertype != '-1') {
			$filterarray = explode(',', $filtertype);
		}

		$ticketstat->loadCacheSeveritiesTickets();

		print '<select id="select'.$htmlname.'" class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
		if ($empty) {
			print '<option value="">'.((is_numeric($empty) || $empty == 'ifone') ? '&nbsp;' : $empty).'</option>';
		}

		if (is_array($conf->cache['severity_tickets']) && count($conf->cache['severity_tickets'])) {
			foreach ($conf->cache['severity_tickets'] as $id => $arrayseverities) {
				// On passe si on a demande de filtrer sur des modes de paiments particuliers
				if (count($filterarray) && !in_array($arrayseverities['type'], $filterarray)) {
					continue;
				}

				// We discard empty line if showempty is on because an empty line has already been output.
				if ($empty && empty($arrayseverities['code'])) {
					continue;
				}

				if ($format == 0) {
					print '<option value="'.$id.'"';
				}

				if ($format == 1) {
					print '<option value="'.$arrayseverities['code'].'"';
				}

				if ($format == 2) {
					print '<option value="'.$arrayseverities['code'].'"';
				}

				if ($format == 3) {
					print '<option value="'.$id.'"';
				}

				// If text is selected, we compare with code, otherwise with id
				if (isset($selected) && preg_match('/[a-z]/i', $selected) && $selected == $arrayseverities['code']) {
					print ' selected="selected"';
				} elseif (isset($selected) && $selected == $id) {
					print ' selected="selected"';
				} elseif ($arrayseverities['use_default'] == "1" && empty($selected) && (!$empty || $empty == 'ifone')) {
					print ' selected="selected"';
				} elseif (count($conf->cache['severity_tickets']) == 1 && (!$empty || $empty == 'ifone')) {	// If only 1 choice, we autoselect it
					print ' selected="selected"';
				}

				print '>';

				$value = '';
				if ($format == 0) {
					$value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
				}

				if ($format == 1) {
					$value = $arrayseverities['code'];
				}

				if ($format == 2) {
					$value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
				}

				if ($format == 3) {
					$value = $arrayseverities['code'];
				}

				print $value ? $value : '&nbsp;';
				print '</option>';
			}
		}
		print '</select>';
		if (isset($user->admin) && $user->admin && !$noadmininfo) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}

		print ajax_combobox('select'.$htmlname);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Clear list of attached files in send mail form (also stored in session)
	 *
	 * @return	void
	 */
	public function clear_attached_files()
	{
		// phpcs:enable
		global $conf, $user;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Set tmp user directory
		$vardir = $conf->user->dir_output."/".$user->id;
		$upload_dir = $vardir.'/temp/'; // TODO Add $keytoavoidconflict in upload_dir path
		if (is_dir($upload_dir)) {
			dol_delete_dir_recursive($upload_dir);
		}

		if (!empty($this->trackid)) { // TODO Always use trackid (ticXXX) instead of track_id (abcd123)
			$keytoavoidconflict = '-'.$this->trackid;
		} else {
			$keytoavoidconflict = empty($this->track_id) ? '' : '-'.$this->track_id;
		}
		unset($_SESSION["listofpaths".$keytoavoidconflict]);
		unset($_SESSION["listofnames".$keytoavoidconflict]);
		unset($_SESSION["listofmimes".$keytoavoidconflict]);
	}

	/**
	 * Show the form to add message on ticket
	 *
	 * @param  	string  $width      	Width of form
	 * @return 	void
	 */
	public function showMessageForm($width = '40%')
	{
		global $conf, $langs, $user, $hookmanager, $form;

		$formmail = new FormMail($this->db);
		$addfileaction = 'addfile';

		if (!is_object($form)) {
			$form = new Form($this->db);
		}

		// Load translation files required by the page
		$langs->loadLangs(array('other', 'mails', 'ticket'));

		// Clear temp files. Must be done at beginning, before call of triggers
		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelselected') && GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1')) {
			$this->clear_attached_files();
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($this->param['langsmodels'])) {
			$newlang = $this->param['langsmodels'];
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('other');
		}

		// Get message template for $this->param["models"] into c_email_templates
		$arraydefaultmessage = -1;
		if (isset($this->param['models']) && $this->param['models'] != 'none') {
			$model_id = 0;
			if (array_key_exists('models_id', $this->param)) {
				$model_id = (int) $this->param["models_id"];
			}

			// If $model_id is empty, preselect the first one
			$usedefault = ($model_id ? -1 : 1);
			$arraydefaultmessage = $formmail->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id, 1, '', $usedefault);
			if (isset($arraydefaultmessage->id) && empty($model_id)) {
				$model_id = $arraydefaultmessage->id;
				$this->param['models_id'] = $model_id;
			}
		}

		// Define list of attached files
		$listofpaths = array();
		$listofnames = array();
		$listofmimes = array();

		if (!empty($this->trackid)) {
			$keytoavoidconflict = '-'.$this->trackid;
		} else {
			$keytoavoidconflict = empty($this->track_id) ? '' : '-'.$this->track_id; // track_id instead of trackid
		}
		//var_dump($keytoavoidconflict);
		if (GETPOST('mode', 'alpha') == 'init' || (GETPOST('modelselected') && GETPOST('modelmailselected', 'alpha') && GETPOST('modelmailselected', 'alpha') != '-1')) {
			if (!empty($arraydefaultmessage->joinfiles) && !empty($this->param['fileinit']) && is_array($this->param['fileinit'])) {
				foreach ($this->param['fileinit'] as $path) {
					$formmail->add_attached_files($path, basename($path), dol_mimetype($path));
				}
			}
		}
		//var_dump($_SESSION);
		//var_dump($_SESSION["listofpaths".$keytoavoidconflict]);
		if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) {
			$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
		}
		if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
			$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
		}
		if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) {
			$listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($this->param['langsmodels'])) {
			$newlang = $this->param['langsmodels'];
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('other');
		}

		print "\n<!-- Begin message_form TICKET -->\n";

		$send_email = GETPOSTINT('send_email') ? GETPOSTINT('send_email') : 0;

		// Example 1 : Adding jquery code
		print '<script nonce="'.getNonce().'" type="text/javascript">
		jQuery(document).ready(function() {
			send_email='.((int) $send_email).';
			if (send_email) {
				if (!jQuery("#send_msg_email").is(":checked")) {
					jQuery("#send_msg_email").prop("checked", true).trigger("change");
				}
				jQuery(".email_line").show();
			} else {
				if (!jQuery("#private_message").is(":checked")) {
					jQuery("#private_message").prop("checked", true).trigger("change");
				}
				jQuery(".email_line").hide();
			}
		';

		// If constant set, allow to send private messages as email
		if (!getDolGlobalString('TICKET_SEND_PRIVATE_EMAIL')) {
			print 'jQuery("#send_msg_email").click(function() {
					console.log("Click send_msg_email");
					if(jQuery(this).is(":checked")) {
						if (jQuery("#private_message").is(":checked")) {
							jQuery("#private_message").prop("checked", false).trigger("change");
						}
						jQuery(".email_line").show();
					}
					else {
						jQuery(".email_line").hide();
					}
				});

				jQuery("#private_message").click(function() {
					console.log("Click private_message");
					if (jQuery(this).is(":checked")) {
						if (jQuery("#send_msg_email").is(":checked")) {
							jQuery("#send_msg_email").prop("checked", false).trigger("change");
						}
						jQuery(".email_line").hide();
					}
				});';
		}

		print '});
		</script>';


		print '<form method="post" name="ticket" id="ticket" enctype="multipart/form-data" action="'.$this->param["returnurl"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.$this->action.'">';
		print '<input type="hidden" name="actionbis" value="add_message">';
		print '<input type="hidden" name="backtopage" value="'.$this->backtopage.'">';
		if (!empty($this->trackid)) {
			print '<input type="hidden" name="trackid" value="'.$this->trackid.'">';
		} else {
			print '<input type="hidden" name="trackid" value="'.(empty($this->track_id) ? '' : $this->track_id).'">';
			$keytoavoidconflict = empty($this->track_id) ? '' : '-'.$this->track_id; // track_id instead of trackid
		}
		foreach ($this->param as $key => $value) {
			print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
		}

		// Get message template
		$model_id = 0;
		if (array_key_exists('models_id', $this->param)) {
			$model_id = $this->param["models_id"];
			$usedefault = ($model_id ? -1 : 1);
			$arraydefaultmessage = $formmail->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id, 1, '', $usedefault);
		}

		$result = $formmail->fetchAllEMailTemplate(!empty($this->param["models"]) ? $this->param["models"] : "", $user, $outputlangs);
		if ($result < 0) {
			setEventMessages($this->error, $this->errors, 'errors');
		}
		$modelmail_array = array();
		foreach ($formmail->lines_model as $line) {
			$modelmail_array[$line->id] = $line->label;
		}

		$ticketstat = new Ticket($this->db);
		$res = $ticketstat->fetch(0, '', $this->track_id);

		print '<table class="border" width="'.$width.'">';

		// External users can't send message email
		if ($user->hasRight("ticket", "write") && !$user->socid) {
			print '<tr><td class="width200"></td><td>';
			$checkbox_selected = (GETPOST('send_email') == "1" ? ' checked' : (getDolGlobalInt('TICKETS_MESSAGE_FORCE_MAIL') ? 'checked' : ''));
			print '<input type="checkbox" name="send_email" value="1" id="send_msg_email" '.$checkbox_selected.'/> ';
			print '<label for="send_msg_email">'.$langs->trans('SendMessageByEmail').'</label>';
			$texttooltip = $langs->trans("TicketMessageSendEmailHelp");
			if (!getDolGlobalString('TICKET_SEND_PRIVATE_EMAIL')) {
				$texttooltip .= ' '.$langs->trans("TicketMessageSendEmailHelp2b");
			} else {
				$texttooltip .= ' '.$langs->trans("TicketMessageSendEmailHelp2a", '{s1}');
			}
			$texttooltip = str_replace('{s1}', $langs->trans('MarkMessageAsPrivate'), $texttooltip);
			print ' '.$form->textwithpicto('', $texttooltip, 1, 'help');

			// Section to selection email template
			if (count($modelmail_array) > 0) {
				print ' &nbsp; <span class="email_line">';
				print $formmail->selectarray('modelmailselected', $modelmail_array, $this->param['models_id'], $langs->trans('SelectMailModel'), 0, 0, "", 0, 0, 0, '', 'minwidth200');
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv("Tools").' - '.$langs->transnoentitiesnoconv("EMailTemplates")), 1);
				}
				print ' &nbsp; ';
				print '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
				print '</span>';
			}

			print '</td></tr>';

			// Private message (not visible by customer/external user)
			if (!$user->socid) {
				print '<tr><td></td><td>';
				$checkbox_selected = (GETPOST('private_message', 'alpha') == "1" ? ' checked' : '');
				print '<input type="checkbox" name="private_message" value="1" id="private_message" '.$checkbox_selected.'/> ';
				print '<label for="private_message">'.$langs->trans('MarkMessageAsPrivate').'</label>';
				print ' '.$form->textwithpicto('', $langs->trans("TicketMessagePrivateHelp"), 1, 'help');
				print '</td></tr>';
			}

			// Zone to select its email template
			/*
			if (count($modelmail_array) > 0) {
				print '<tr class="email_line"><td></td><td colspan="2"><div style="padding: 3px 0 3px 0">'."\n";
				print $formmail->selectarray('modelmailselected', $modelmail_array, $this->param['models_id'], $langs->trans('SelectMailModel'), 0, 0, "", 0, 0, 0, '', 'minwidth200');
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
				print ' &nbsp; ';
				print '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans('Apply').'" name="modelselected" id="modelselected">';
				print '</div></td>';
			}
			*/

			// From
			$from = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM');
			print '<tr class="email_line"><td class="width200"><span class="">'.$langs->trans("MailFrom").'</span></td>';
			print '<td><span class="">'.img_picto('', 'email', 'class="pictofixedwidth"').$from.'</span></td></tr>';

			// Recipients / adressed-to
			print '<tr class="email_line"><td>'.$langs->trans('MailRecipients');
			print ' '.$form->textwithpicto('', $langs->trans("TicketMessageRecipientsHelp"), 1, 'help');
			print '</td><td>';
			if ($res) {
				// Retrieve email of all contacts (internal and external)
				$contacts = $ticketstat->getInfosTicketInternalContact(1);
				$contacts = array_merge($contacts, $ticketstat->getInfosTicketExternalContact(1));

				$sendto = array();

				// Build array to display recipient list
				if (is_array($contacts) && count($contacts) > 0) {
					foreach ($contacts as $key => $info_sendto) {
						if ($info_sendto['email'] != '') {
							$sendto[] = dol_escape_htmltag(trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">").' <small class="opacitymedium">('.dol_escape_htmltag($info_sendto['libelle']).")</small>";
						}
					}
				}

				if (!empty($ticketstat->origin_replyto) && !in_array($ticketstat->origin_replyto, $sendto)) {
					$sendto[] = dol_escape_htmltag($ticketstat->origin_replyto).' <small class="opacitymedium">('.$langs->trans("TicketEmailOriginIssuer").")</small>";
				} elseif ($ticketstat->origin_email && !in_array($ticketstat->origin_email, $sendto)) {
					$sendto[] = dol_escape_htmltag($ticketstat->origin_email).' <small class="opacitymedium">('.$langs->trans("TicketEmailOriginIssuer").")</small>";
				}

				if ($ticketstat->fk_soc > 0) {
					$ticketstat->socid = $ticketstat->fk_soc;
					$ticketstat->fetch_thirdparty();

					if (!empty($ticketstat->thirdparty->email) && !in_array($ticketstat->thirdparty->email, $sendto)) {
						$sendto[] = $ticketstat->thirdparty->email.' <small class="opacitymedium">('.$langs->trans('Customer').')</small>';
					}
				}

				if (getDolGlobalInt('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS')) {
					$sendto[] = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO').' <small class="opacitymedium">(generic email)</small>';
				}

				// Print recipient list
				if (is_array($sendto) && count($sendto) > 0) {
					print img_picto('', 'email', 'class="pictofixedwidth"');
					print implode(', ', $sendto);
				} else {
					print '<div class="warning">'.$langs->trans('WarningNoEMailsAdded').' '.$langs->trans('TicketGoIntoContactTab').'</div>';
				}
			}
			print '</td></tr>';

			// Send to CC
			$sendtocc = getDolGlobalString('TICKET_SEND_INTERNAL_CC');
			if ($sendtocc) {
				print '<tr class="email_line"><td><span class="">'.$langs->trans("MailCC").'</span></td>';
				print '<td><span class="">'.img_picto('', 'email', 'class="pictofixedwidth"').$sendtocc.'</span></td></tr>';
			}
		}

		$uselocalbrowser = false;

		// Intro
		// External users can't send message email
		/*
		if ($user->rights->ticket->write && !$user->socid && !empty($conf->global->TICKET_MESSAGE_MAIL_INTRO)) {
			$mail_intro = GETPOST('mail_intro') ? GETPOST('mail_intro') : $conf->global->TICKET_MESSAGE_MAIL_INTRO;
			print '<tr class="email_line"><td><label for="mail_intro">';
			print $form->textwithpicto($langs->trans("TicketMessageMailIntro"), $langs->trans("TicketMessageMailIntroHelp"), 1, 'help');
			print '</label>';

			print '</td><td>';
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

			$doleditor = new DolEditor('mail_intro', $mail_intro, '100%', 90, 'dolibarr_details', '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_2, 70);

			$doleditor->Create();
			print '</td></tr>';
		}
		*/

		// Subject/topic
		$topic = "";
		foreach ($formmail->lines_model as $line) {
			if (!empty($this->substit) && $this->param['models_id'] == $line->id) {
				$topic = make_substitutions($line->topic, $this->substit);
				break;
			}
		}
		print '<tr class="email_line"><td class="fieldrequired">'.$langs->trans('MailTopic').'</td>';
		if (empty($topic)) {
			print '<td><input type="text" class="text minwidth500" name="subject" value="['.getDolGlobalString('MAIN_INFO_SOCIETE_NOM').' - '.$langs->trans("Ticket").' '.$ticketstat->ref.'] '. $ticketstat->subject .'" />';
		} else {
			print '<td><input type="text" class="text minwidth500" name="subject" value="'.make_substitutions($topic, $this->substit).'" />';
		}
		print '</td></tr>';

		// Attached files
		if (!empty($this->withfile)) {
			$out = '<tr>';
			$out .= '<td>'.$langs->trans("MailFile").'</td>';
			$out .= '<td>';
			// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
			$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
			$out .= '<script nonce="'.getNonce().'" type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '    jQuery("#'.$addfileaction.'").prop("disabled", true);';
			$out .= '    jQuery("#addedfile").on("change", function() {';
			$out .= '        if (jQuery(this).val().length) {';
			$out .= '            jQuery("#'.$addfileaction.'").prop("disabled", false);';
			$out .= '        } else {';
			$out .= '            jQuery("#'.$addfileaction.'").prop("disabled", true);';
			$out .= '        }';
			$out .= '    });';
			$out .= '    jQuery(".removedfile").click(function() {';
			$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
			$out .= '    });';
			$out .= '})';
			$out .= '</script>'."\n";

			if (count($listofpaths)) {
				foreach ($listofpaths as $key => $val) {
					$out .= '<div id="attachfile_'.$key.'">';
					$out .= img_mime($listofnames[$key]).' '.$listofnames[$key];
					if (!$this->withfilereadonly) {
						$out .= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key + 1).'" class="removedfile reposition" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
					}
					$out .= '<br></div>';
				}
			} else {
				//$out .= $langs->trans("NoAttachedFiles").'<br>';
			}
			if ($this->withfile == 2) { // Can add other files
				$out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
				$out .= ' ';
				$out .= '<input type="submit" class="button smallpaddingimp reposition" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
			}
			$out .= "</td></tr>\n";

			print $out;
		}

		// MESSAGE
		$defaultmessage = "";
		if (is_object($arraydefaultmessage) && $arraydefaultmessage->content) {
			$defaultmessage = (string) $arraydefaultmessage->content;
		}
		$defaultmessage = str_replace('\n', "\n", $defaultmessage);

		// Deal with format differences between message and signature (text / HTML)
		if (dol_textishtml($defaultmessage) && !dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
			$this->substit['__USER_SIGNATURE__'] = dol_nl2br($this->substit['__USER_SIGNATURE__']);
		} elseif (!dol_textishtml($defaultmessage) && isset($this->substit['__USER_SIGNATURE__']) && dol_textishtml($this->substit['__USER_SIGNATURE__'])) {
			$defaultmessage = dol_nl2br($defaultmessage);
		}
		if (GETPOSTISSET("message") && !GETPOST('modelselected')) {
			$defaultmessage = GETPOST('message', 'restricthtml');
		} else {
			$defaultmessage = make_substitutions($defaultmessage, $this->substit);
			// Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
			$defaultmessage = preg_replace("/^(<br>)+/", "", $defaultmessage);
			$defaultmessage = preg_replace("/^\n+/", "", $defaultmessage);
		}

		print '<tr><td colspan="2"><label for="message"><span class="fieldrequired">'.$langs->trans("Message").'</span>';
		if ($user->hasRight("ticket", "write") && !$user->socid) {
			$texttooltip = $langs->trans("TicketMessageHelp");
			if (getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO') || getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE')) {
				$texttooltip .= '<br><br>'.$langs->trans("ForEmailMessageWillBeCompletedWith").'...';
			}
			if (getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO')) {
				$mail_intro = make_substitutions(getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO'), $this->substit);
				print '<input type="hidden" name="mail_intro" value="'.dolPrintHTMLForAttribute($mail_intro).'">';
				$texttooltip .= '<br><u>'.$langs->trans("TicketMessageMailIntro").'</u><br>'.$mail_intro;
			}
			if (getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE')) {
				$mail_signature = make_substitutions(getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE'), $this->substit);
				print '<input type="hidden" name="mail_signature" value="'.dolPrintHTMLForAttribute($mail_signature).'">';
				$texttooltip .= '<br><br><u>'.$langs->trans("TicketMessageMailFooter").'</u><br>'.$mail_signature;
			}
			print $form->textwithpicto('', $texttooltip, 1, 'help');
		}
		print '</label></td></tr>';


		print '<tr><td colspan="2">';
		//$toolbarname = 'dolibarr_details';
		$toolbarname = 'dolibarr_notes';
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('message', $defaultmessage, '100%', 200, $toolbarname, '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_5, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Footer
		// External users can't send message email
		/*if ($user->rights->ticket->write && !$user->socid && !empty($conf->global->TICKET_MESSAGE_MAIL_SIGNATURE)) {
			$mail_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE;
			print '<tr class="email_line"><td><label for="mail_intro">'.$langs->trans("TicketMessageMailFooter").'</label>';
			print $form->textwithpicto('', $langs->trans("TicketMessageMailFooterHelp"), 1, 'help');
			print '</td><td>';
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor = new DolEditor('mail_signature', $mail_signature, '100%', 90, 'dolibarr_details', '', false, $uselocalbrowser, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_2, 70);
			$doleditor->Create();
			print '</td></tr>';
		}
		*/

		print '</table>';

		print '<br><center>';
		print '<input type="submit" class="button" name="btn_add_message" value="'.$langs->trans("Add").'"';
		// Add a javascript test to avoid to forget to submit file before sending email
		if ($this->withfile == 2 && !empty($conf->use_javascript_ajax)) {
			print ' onClick="if (document.ticket.addedfile.value != \'\') { alert(\''.dol_escape_js($langs->trans("FileWasNotUploaded")).'\'); return false; } else { return true; }"';
		}
		print ' />';
		if (!empty($this->withcancel)) {
			print " &nbsp; &nbsp; ";
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print "</center>\n";

		print '<input type="hidden" name="page_y">'."\n";

		print "</form><br>\n";

		// Disable enter key if option MAIN_MAILFORM_DISABLE_ENTERKEY is set
		if (getDolGlobalString('MAIN_MAILFORM_DISABLE_ENTERKEY')) {
			print '<script type="text/javascript">';
			print 'jQuery(document).ready(function () {';
			print '		$(document).on("keypress", \'#ticket\', function (e) {		/* Note this is called at every key pressed ! */
	    					var code = e.keyCode || e.which;
	    					if (code == 13) {
								console.log("Enter was intercepted and blocked");
	        					e.preventDefault();
	        					return false;
	    					}
						});';
			print '})';
			print '</script>';
		}

		print "<!-- End form TICKET -->\n";
	}
}
