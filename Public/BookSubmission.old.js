var contributorType = Array('BookToGraphist', 'BookToEditor', 'BookToPrinter', 'BookToBinder', 'BookToIllustrator', 'BookToAuthor', 'BookToPhotograph', 'BookToTranslator', 'BookToOther');
var all_fields = Array('CONTACTTYPE', 'EMAIL1', 'DENOMINATION', 'EMAIL2', 'FIRSTNAME', 'LASTNAME', 'PHONE1', 'MOBILEPHONE', 'POSTALCODE', 'CITY', 'COUNTRYID', 'ADDRESS');
var current_step = 1;
var step_filling = 1;
var otherContributorForm = "";
var otherBookAuthor = "";
var otherContributorIndex = 1;
var bookAuthorIndex = 1;
var bookSubmissionData = "";
var bookFinalRemarks = '';
var BookToOtherNextStepClass = '';

function moveToStep (step) {
//	alert("step:"+step+" current_step:"+current_step+" step_filling:"+step_filling);
	if (step <= step_filling) {
		if (step > step_filling) {
			step_filling = step;
		};
		index = step - 1;
		posX = 305 - step * 305;
		$('#specific_mask').animate({left:posX}, 'slow');
		$('ol.subnavigation a.active').removeClass('active');
		$('ol.subnavigation li:eq('+index+') a').addClass('active').removeClass('inactive');
		current_step = step;
		if ($("body,html").scrollTop() > 0) {
			$("body,html").scrollTop(0);
		};
		if ($('#specific_mask>div:eq('+index+')').hasClass('BookSubmissionSummary')) {
			bookFinalRemarks = $('#BIBLIO').val();
			summarizeSubmission();
		} else if ( $('#specific_mask>div:eq('+index+')').hasClass('BookRemarks') ) {
			if ( bookFinalRemarks == '') {
				c_remarks = '';
				$('textarea.c_remark').each( function(){
					v = $(this).val();
					if (v != '') {
						p = $(this).parents('div.info');
						t = '';
						if (p.children('h2').length > 0) {
							t = p.children('h2').text().toUpperCase();
						};
						f = p.children('form');
						email1 = f.find('#EMAIL1').val();
						if (email1 != undefined) {
							t += ' '+email1;
						};
						c_remarks += t+'\n'+v+'\n\n';
					};
				});
			} else {
				c_remarks = bookFinalRemarks;
			}
			if (c_remarks != '') {
				$('#BIBLIO').val( c_remarks );
			};
		}
	};
}

function detectForm (form) {
	var form_target = "";
	if (form != '') {
		form_target = '#'+form;
	}
	return form_target
}

function checkEmail (form, mailID) {
	var form_target = detectForm(form);
	email = $(form_target+" #EMAIL1").val();
	if (email != '') {
		$(form_target+' .empty').html('').addClass('loading');
		$.post("loadCheckEmail.php", {EMAIL: email, mailID:mailID}, function (data) {
			$(form_target+' .empty').removeClass('loading');		
			if (data == 1) {
				$('#specific .BookToPeopleContact .BODY').hide();
				$(form_target+' .not_vital').slideDown('fast');
				$(form_target+" #EMAIL1").attr('readonly', 'readonly');
			} else if (data == 0) {
				alert(AffGetLabel(fill_mandatory_fields));
			} else {
				$(form_target+" #EMAIL1").attr('readonly', 'readonly').removeAttr('onblur');				
				$(form_target+' .empty').html(data);
				$('#specific_mask .BookToPeopleContact').nextAll('div.preview').remove();
				$('#specific_mask .BookToPeopleContact .button_container.next').remove();
			};		
		});
	};
}

function hideContributorForm (search_in, what, active_form) {
//	alert(what+" IN "+search_in+" = "+search_in.indexOf(what));
	if (search_in.indexOf(what) >= 0) {
		$('form.'+what).hide();
		$('.BookContributor.'+what+' .search_container').hide();
		$('.BookContributor.'+what+' .BODY').hide();
		$('.BookContributor.'+what+' .contributor_already_endoded').show();
		email = $(active_form+" input[name='EMAIL1']").val();
		firstname = $(active_form+" input[name='FIRSTNAME']").val();
		lastname = $(active_form+" input[name='LASTNAME']").val();
		denomination = $(active_form+" input[name='DENOMINATION']").val();
		to_append = "";
		if (denomination != '') {
			to_append += denomination;
		}
		if (denomination != '' && firstname != '' && lastname != '') {
			to_append += ", ";
		}
		if (firstname != '' && lastname != '') {
			to_append += firstname+" "+lastname;
		};
		if (email != '') {
			to_append += ' - '+email;
		};
		$('.BookContributor.'+what+' .auto_complete').html('<p><b>'+to_append+'</b></p>');
		
	};
}
function checkBookForm (form) {
	$('label.alert').removeClass('alert');
	var form_target = detectForm(form);
	valid = 1;
	$(form_target+' select.mandatory , '+form_target+' input.mandatory').map(function () {
		if ($(this).val() == "") {
			valid = 0;
			$(this).siblings('label').addClass('alert');
		}
	});
	if (valid == 1) {
		validateAndmoveToNextStep();		
	} else {
		alert(AffGetLabel(fill_mandatory_fields));
	};
}


function switch_contact_type (form) {
	var form_target = detectForm(form);
	type = $(form_target+' select[name="CONTACTTYPE"]').val();
	if (type == 'PP') {
		$(form_target+' div.FIRSTNAME label').append('<small class="mandatory>*</small>');
		$(form_target+' input[name="FIRSTNAME"]').addClass('mandatory');
		$(form_target+' div.LASTNAME label').append('<small class="mandatory>*</small>');
		$(form_target+' input[name="LASTNAME"]').addClass('mandatory');
		$(form_target+' div.DENOMINATION small').remove();
		$(form_target+' input[name="DENOMINATION"]').removeClass('mandatory');
		$(form_target+' div.DENOMINATION').hide();
		$(form_target+' div.EMAIL2').hide();
	} else if (type == 'PM') {
		$(form_target+' div.DENOMINATION').show();
		$(form_target+' div.DENOMINATION label').append('<small class="mandatory>*</small>');
		$(form_target+' input[name="DENOMINATION"]').addClass('mandatory');
		$(form_target+' div.EMAIL2').show();
		$(form_target+' div.FIRSTNAME small').remove();
		$(form_target+' input[name="FIRSTNAME"]').removeClass('mandatory');
		$(form_target+' div.LASTNAME small').remove();
		$(form_target+' input[name="LASTNAME"]').removeClass('mandatory');
	}
}
function checkContributorForm (form) {
	$('label.alert').removeClass('alert');
	var form_target = detectForm(form);
	check_form = 1;
	if ($(form_target).hasClass('BookToOther')) {
		email = $(form_target+" input[name='EMAIL1']").val();
		firstname = $(form_target+" input[name='FIRSTNAME']").val();
		lastname = $(form_target+" input[name='LASTNAME']").val();
		denomination = $(form_target+" input[name='DENOMINATION']").val();
		if (email == "" && firstname == "" && lastname == "" && denomination == "") {
			check_form = 0;
		};
	};
	if ($(form_target+':visible').length == 0) {
		check_form = 0;
	};
	if ( check_form == 0) {		
		validateAndmoveToNextStep();
	} else if (current_step <= step_filling) {	
		valid = 1;
		$(form_target+' select.mandatory , '+form_target+' input.mandatory').map(function () {
			if ($(this).val() == "") {
				valid = 0;
				$(this).siblings('label').addClass('alert');
			}
		});
		var current_contributor_type = Array();
		$(form_target+ ' div.contributorType input:checked').map(function () {
			current_contributor_type.push($(this).val());
		});
		if (current_contributor_type.length == 0) {
			valid = 0;
			$(form_target+ ' div.contributorType>label').addClass('alert');
		};
		
		if (valid == 1) {
			validateAndmoveToNextStep();
			if ( $(form_target).hasClass('BookToOther')) {			
				panelID = '#'+$(form_target).parents('div.preview').attr('id');
				addOtherContributorForm(panelID);
			};
			contributor_str = current_contributor_type.toString();
			for (var i=0; i < 4; i++) {
				contributor_str = current_contributor_type.join(',');
				if ( $(form_target).hasClass(toCheckContributorType[i]) ) {
					for (var j=i+1; j < 5; j++) {
						hideContributorForm(contributor_str, toCheckContributorType[j], form_target);
					};
				}
			};
			if (current_step < step_filling) {
				steps = new Array();
				for (var i=3; i > 0; i--) {
					to_check = contributorType[i];
					steps[to_check] = "";
					$('input[value='+to_check+']:checked').map(function() {
						in_form = $(this).parents("form").attr('id');
						in_form = in_form.replace("BookContributor_","")
						if (to_check != in_form) {
							steps[to_check] += in_form+',';
						};
					});
					if (steps[to_check] == "") {
						$('form.'+to_check).show().prev('.search_container').show();
						$('form.'+to_check).show().prev('.BODY').show();
						$('.BookContributor.'+to_check+' .contributor_already_endoded').hide();
					};
				}		
			};
		
		} else {
			alert(AffGetLabel(fill_mandatory_fields));
		};
	}
}

function skipStepAndGoFurther () {
	step_filling++,
	next_step = current_step + 1;
	moveToStep(next_step);	
}


function goFurther () {
	prev_step = current_step + 1;
	moveToStep(prev_step);	
}

function goBack () {
	prev_step = current_step - 1;
	moveToStep(prev_step);	
}

function validateAndmoveToNextStep () {
if (current_step <= step_filling) {	
	step_filling++;
	next_step = current_step + 1;
	if ($('#specific_mask>div.preview:eq('+current_step+') form').length > 0) {
		classes = $('#specific_mask>div.preview:eq('+current_step+') form').attr('class');
		classes = classes.replace('BookContributor ', '');	
		$('#specific_mask>div.preview:eq('+current_step+') form input:checked[value="'+classes+'"]').attr('disabled', 'disabled');
	};
	moveToStep(next_step);
	} else {
		alert('Veuillez remplir le formulaire dans l\'ordre');
	}
}

function get_field_label (fieldID) {
	label = $('label[for="'+fieldID+'"]').text(); 
	return label.replace('*', '');
}
function book_summarized_info (fieldID) {
	to_return = "";
	if (book[fieldID] != '') {
		to_return = get_field_label(fieldID)+': '+book[fieldID]+'<br/>';
		if (fieldID == 'book_theme') {
			to_return = get_field_label(fieldID)+': '+$('#book_theme').children('option[value='+book[fieldID]+']').text()+'<br/>';
		};
	};
	return to_return;
}

function authors_summary () {
	auteurs = "";
	if (book['BOOK_AUTHORS_firstname[]'].length > 0) {
		firstname = book['BOOK_AUTHORS_firstname[]'].split(',');
		lastname = book['BOOK_AUTHORS_lastname[]'].split(',');
		for (var i=0; i < firstname.length; i++) {
			auteurs += firstname[i]+" "+lastname[i]+'<br/>';
		};
	};
	return auteurs;
}

function book_summary () {
	summary = "<p>";
	summary += book_summarized_info('TITLE');
	if (book.HEADER != '') {
		summary += book_summarized_info('HEADER');
	};
	summary += book_summarized_info('EVENTSTART');
	summary += book_summarized_info('BOOK_ISBN');
	summary += book_summarized_info('BOOK_LEGAL_DEPOSIT');
	summary += book_summarized_info('book_theme');	
	summary += '</p>';
	return summary;
}

function summarizeSubmission () {
	$('input').removeAttr('disabled');
	bookSubmissionData = "fromEmail="+fromEmail+"&";
	autoComplete = '.BookSubmissionSummary .autoComplete';
	bookForm = '#BookCandidate';
	contibutorForm = 'form.BookContributor:visible';
	//book
	book = new Array;
	book['BOOK_AUTHORS_firstname[]'] = "";
	book['BOOK_AUTHORS_lastname[]'] = "";
	$.each( $(bookForm).serializeArray() , function(i, val) {
		name = val.name;
		value = val.value;
		if ( name == 'BOOK_AUTHORS_firstname[]' || name == 'BOOK_AUTHORS_lastname[]' ) {
			if (book[name]) {
				book[name] += ','+value;
			} else {
				book[name] = value;
			}
		} else {
			book[name] = value;	
		}
		bookSubmissionData += "book_"+name+"="+value+"&";
	});
	//contributors
	contributors = new Array();
	i = 1;
	to_alert = "";
	$(contibutorForm).map(function() {
		current = "c"+i;
		contributor_raw = $(this).serializeArray();
		contributor = new Array();
		contributor["contributorType[]"] = "";
		$.each(contributor_raw, function(i, val) {
			name = val.name;
			value = val.value;
			data_name = current+"_"+name;
			bookSubmissionData += data_name+"="+value+"&";			
			if ( name == 'contributorType[]' ) {
				if (contributor[name]) {
					contributor[name] += ','+value;
				} else {
					contributor[name] = value;
				}
			} else {
				contributor[name] = value;				
			}
		});	
		contributors[current] = contributor;	
		i++;
	});
	book['BIBLIO'] = $('#BIBLIO').val();
	bookSubmissionData += "book_BIBLIO="+book['BIBLIO']+"&";	
	bookSubmissionData += "ID="+$('body').attr('mID')+"&";
	//autoComplete
	book_html = book_summary();
	$(autoComplete+" .book").html(book_html);
	total = i-1;
	bookSubmissionData += "c_total="+total+"&";
	for (var i=0; i < contributorType.length; i++) {
		type = contributorType[i];
		$(autoComplete+" div."+type).html('');		
		type_fill = 0;
		for (var j=1; j <= total; j++) {
			var c = "c"+j;
			current_type = "";
			current_type = contributors[c]["contributorType[]"];
//			alert(current_type);
			if (current_type.indexOf(type) >= 0) {
				to_append = "";
				if (contributors[c]["DENOMINATION"] != '') {
					to_append += contributors[c]["DENOMINATION"];
				}
				if (contributors[c]["DENOMINATION"] != '' && contributors[c]["FIRSTNAME"] != '' && contributors[c]["LASTNAME"] != '') {
					to_append += ", ";
				}
				if (contributors[c]["FIRSTNAME"] != '' && contributors[c]["LASTNAME"] != '') {				
					to_append += contributors[c]["FIRSTNAME"]+" "+contributors[c]["LASTNAME"];
				};
				$(autoComplete+" h4."+type).show();
				$(autoComplete+" div."+type).append('<p>'+to_append+'</p>').show();
				type_fill = 1;
			};
		};
		if (type == 'BookToAuthor') {
			to_append_also = authors_summary();			
			$(autoComplete+" div."+type).append('<p>'+to_append_also+'</p>');
			if (to_append_also != '') {
				type_fill = 1;
			};
		};
		if (type_fill == 0) {
			$(autoComplete+" h4."+type).hide();
			$(autoComplete+" div."+type).hide();
		};
	};
	if (book['BIBLIO'] != '') {
		$(autoComplete+' div.BookRemarks').html('<p>'+book['BIBLIO']+'</p>');		
		$(autoComplete).find('div.BookRemarks,h4.BookRemarks').show();
	} else {
		$(autoComplete).find('div.BookRemarks,h4.BookRemarks').hide();
	}
	$('.BookSubmissionSummary .button_container').show();
}
function addOtherContributorForm (from) {
//	alert( $(from).next().attr('class') );
	if ($(from).next().hasClass( BookToOtherNextStepClass )) {
		otherContributorIndex++;
		otherContributorFormID = $('#specific .BookContributor.BookToOther').attr('id');
		newContributorFormID = 'id="BookContributor_BookToOther_form_'+otherContributorIndex+'"';
		newContributorSearchID = 'id="SEARCH_BookContributor_BookToOther_form_'+otherContributorIndex+'"';
		newContributorDivID = 'otherContributor_'+otherContributorIndex;
		checkContactToReplace = "checkContact('BookContributor_BookToOther');";
		checkContactNew = "checkContact('BookContributor_BookToOther_form_"+otherContributorIndex+"');";
		var re1 = new RegExp("BookContributor_BookToOther", "g");
		var checkContributor = "BookContributor_BookToOther_form_"+otherContributorIndex;
		var re2 = new RegExp(otherContributorFormID, "g");
		var re3 = new RegExp('_form_'+otherContributorIndex+'_form', "g");
		to_insert = '<div id="'+newContributorDivID+'" class="BookContributor preview gouttiere">';
		to_insert += otherContributorForm;
		to_insert += '</div>';
		to_insert = to_insert.replace('id="BookContributor_BookToOther', newContributorFormID);
		to_insert = to_insert.replace(re2, newContributorDivID);
		to_insert = to_insert.replace('id="SEARCH_BookContributor_BookToOther"', newContributorSearchID);
		to_insert = to_insert.replace(checkContactToReplace, checkContactNew);
		to_insert = to_insert.replace(re1, checkContributor);
		to_insert = to_insert.replace(re3, '_form');
		$('#specific_mask>div.'+BookToOtherNextStepClass).before(to_insert);
		width = $('#specific_mask').width() + 305;
		$('#specific_mask').css('width', width);
		$('#'+newContributorDivID+' label').removeAttr('for');
		$('#'+newContributorDivID+' input:checkbox').map(function() {
			id = $(this).attr('id');
			id = id+'_'+otherContributorIndex;
			$(this).attr('id', id);
			$(this).next('label').attr('for', id);
		});
//		$('#'+newContributorDivID+' input.search').focusin().autocomplete( function() {auto_complete_contacts(); });
	}
}
function displayOtherContributorForm (div) {
	$(div+' .search_container').show();
	$(div+' form').show();
	$(div+' form input, '+div+' form select').removeAttr('disabled');
	$(div+' .other_contributor').hide();
}

function cancelOtherContributor (div) {
	$(div+' form').hide();
	$(div+' .search_container').hide();
	$(div+' .other_contributor').show();
}

function sendBookSubmission (mailContentID) {
	$('#specific .info .button_container, #specific .BookSubmissionSummary .button_container.next').html('');
	$('#specific .BookSubmissionConfirm .autoComplete').html('');
	
	validateAndmoveToNextStep();		
	$('#specific .BookSubmissionConfirm .autoComplete').addClass('loading');
	if (bookSubmissionData.indexOf('mailID') == -1) {
		bookSubmissionData += 'mailID='+mailContentID;
	};
	$.post("loadBookSubmission.php", bookSubmissionData, function (data) {
		$('#specific .BookSubmissionConfirm .autoComplete').removeClass('loading');
		$('#specific .BookSubmissionConfirm .autoComplete').html(data);
	});	
}

function addBookAuthor () {
	bookAuthorIndex++;
	to_insert = '<div class="book_author"><input type="text" class="texte" name="BOOK_AUTHORS_firstname[]" id="BOOK_AUTHORS_firstname_'+bookAuthorIndex+'"/><input type="text" class="texte" name="BOOK_AUTHORS_lastname[]" id="BOOK_AUTHORS_lastname_'+bookAuthorIndex+'"/></div>';
//	to_insert = '<input class="texte" type="text" name="BOOK_AUTHORS[]" id="BOOK_AUTHORS_'+bookAuthorIndex+'" /><br class="break"/>';
	$('#addBookAuthor').append(to_insert);
}

function autoCompleteContact(cID) {
	if (cID != '' && cID != undefined) {
		$.get("http://www.prixfernandbaudinprijs.be/common/scripts/SearchContacts_JSON.php", { cID:cID },
		  function(data){
			var reponse = eval('(' + data + ')'); 
			auto_complete_contacts = reponse.reponse;
			$("input.search").focusin().autocomplete( auto_complete_contacts );
		});
	};
}

function display_and_active_form (form) {
	var form_target = detectForm(form);	
	$(form_target).show();
	$('.'+form+' .search_container').show();
	$(form_target+' input, '+form_target+' select').removeAttr('disabled');
	classes = $(form_target).attr('class');
	classes = classes.replace('BookContributor ', '');	
	$(form_target+' input:checked[value="'+classes+'"]').attr('disabled', 'disabled');
}

function checkContact (form, name, cID) {
	var form_target = detectForm(form);	
	$(form_target+' .message').html('');
	data_contact = '';
	if (cID != undefined && name != undefined) {
		contact = name;	
		data_contact = 'contact='+name+'&cID='+cID;
	} else if ( $('#SEARCH_'+form).val() != '' ){
		data_contact = 'contact='+$('#SEARCH_'+form).val();
	}		
	if (data_contact != '') {
		$(form_target+' input:text, '+form_target+' select').val('');
		to_loading = '.input_text_container.SEARCH_'+form;
		button = $(to_loading+' div.button_container').html();
		$(to_loading+' div.button_container').html('').addClass('loading');
		$(to_loading+' div.message').html('');
		$.post('loadCheckContact.php', data_contact, function (data) {
			$(to_loading+' div.button_container').html(button).removeClass('loading');			
			display_and_active_form(form);
			$(form_target+' .input_text_container, '+form_target+' .select_container, '+form_target+' .checkboxes_container').hide();
			$(form_target+' .contributorType').show();
			var reponse = eval('(' + data + ')');
			result = reponse.reponse;
			if ( result == 0 ) {
				$(to_loading+' div.message').removeClass('multichoice').html(AffGetLabel(BookSubmission_contributor_not_found));
				$(form_target+' .input_text_container, '+form_target+' .select_container, '+form_target+' .checkboxes_container').show();
			} else if ( result == 2 ) {
				// ajouter un bypass pour encoder une fiche si le contact cherché n'existe pas
				$('#SEARCH_'+form).nextAll('.message').addClass('multichoice').html(reponse.contacts);
				$('#SEARCH_'+form).nextAll('.message').addClass('multichoice').find('li>a').each( function(){
					$(this).click( function(){
						checkContact(form, false, $(this).attr('val'));
					});
				});
				$('#SEARCH_'+form).nextAll('.message').addClass('multichoice').find('a.newInsert').click( function(){
					$(this).parents('div.message').removeClass('multichoice').html('').parent().parent().next('form').find('.input_text_container, .select_container, .checkboxes_container').show();
				});
			} else {
				$(to_loading+' div.message').removeClass('multichoice').html(AffGetLabel(BookSubmission_contributor_found));
//				$(to_loading+' div.message').append('<textarea class="c_remark" name="remark_step_'+current_step+'" id="remark_step_'+current_step+'"></textarea>');
				cID = reponse.cID;
				$(form_target+' input:hidden[name="cID"]').val(cID);
				$(form_target+' .input_text_container, '+form_target+' .select_container, '+form_target+' .checkboxes_container').show();
				for (var i=0; i < all_fields.length; i++) {
					field = all_fields[i];
					if (reponse[field]) {
						$(form_target+' .'+field).show();
						$(form_target+' #'+field).val(AffGetLabel(reponse[field]));
						if (reponse[field] != '' && reponse[field] != 'und') {
							$(form_target+' #'+field).attr('disabled', 'disabled');
						};
					};
				};
				switch_contact_type(form);
			}
		});
	} else {
		$(to_loading+' div.message').html('');
		$(form_target+' .input_text_container, '+form_target+' .select_container, '+form_target+' .checkboxes_container').show();
	}
}

function initBookSubmission () {
	BookToOtherNextStepClass = $('#specific_mask>div.BookToOther.BookContributor').next().find('form').attr('class');
	autoCompleteContact();
	otherContributorForm = $('#specific .BookContributor.BookToOther').html();
//	$('form input, form select').attr('disabled', 'disabled');
	if (fromEmail != '') {
		validateAndmoveToNextStep();		
	};
}


$(document).ready(function(){initBookSubmission();});




















