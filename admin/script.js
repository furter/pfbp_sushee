function toggle_display (classname) {
	if($('#d_o_'+classname).attr('checked') == true) {
		$('#listing .'+classname).removeClass('hidden');
	} else {
		$('#listing .'+classname).addClass('hidden');
	}
}

function tool_nav_toggle_display (id) {
	var to_display = 0;
	if ($('#'+id).hasClass('hidden')) {
		to_display = 1;
	};
	$('#tools a.active').removeClass('active');
	$('#tools .tool').removeClass('display');
	$('#tools .tool').addClass('hidden');
	if (to_display == 1) {
		$('#'+id).removeClass('hidden');
		$('#'+id).addClass('display');
		$(this).addClass('active');
	}

}

function launch_lightbox () {
	$('body').css('overflow', 'hidden');
	$("#lightbox").removeClass('hidden');
}
function close_lightbox () {
	$("#lightbox").addClass('hidden');
	$('.lb_hide').removeClass('hidden');
	$("#lightbox_container").empty();
}


function in_display_get_datas (form) {
	datas = $(form+" input[type!=button], "+form+" select").map(
	function(){
		return $(this).attr('name')+"="+$(this).val()+"&";
		}
		).get();
	data_to_send = datas.join('');
	return data_to_send;
}

function display_book (ID) {
	launch_lightbox();
	$.get("Book.php", {ID:ID}, function(data){
			$('#lightbox_container').append(data);
		});			
}

function update_book (ID) {
	data_to_send = in_display_get_datas('#display_book_form');
	$.get("BookUpdate.php", data_to_send, function(data){
		if (data == 0) {
			$("#lightbox_message").html('<p class="confirm">Operation réussie</p>');
			update_display_book(ID);
		} else {
			alert('Une erreur s’est produite.');
		};
	});			
}

function update_display_book (ID) {
	update_tr = "#m"+ID;
	//mise à jour du tableau
	$("#display_book_form input[type!=button], #display_book_form select").map(
	function(){
		$(update_tr+" ."+$(this).attr('name')).text($(this).val());				
		}
		).get();
}

function display_contact (ID) {
	launch_lightbox();
	$.get("Contact.php", {ID:ID}, function(data){
			$('#lightbox_container').append(data);
		});			
}

function update_contact (ID) {
	data_to_send = in_display_get_datas('#display_contact_form');
	$.get("ContactUpdate.php", data_to_send, function(data){
		if (data == 0) {
			$("#lightbox_message").html('<p class="confirm">Operation réussie</p>');
			update_display_contact(ID);
		} else {
			alert('Une erreur s’est produite.');
		};
		});			
}
function update_display_contact (ID) {
	update_tr = "#c"+ID;
	//mise à jour du tableau
	$("#display_contact_form input[type!=button], #display_contact_form select").map(
	function(){
		$(update_tr+" ."+$(this).attr('name')).text($(this).val());				
		}
		).get();
}

function CSV_elements_to_display () {
	var to_display;
	var to_search;
	datas = $("#display input[type=checkbox]").map(
	function(){
		if ($(this).attr('checked')) {
			var name = $(this).attr('name');
			name = name.replace('d_o_', 'return[]=');
			return name;
			};
		}
	).get();
	to_display = datas.join('&');
	datas = $("#browse select").map(
	function(){
			var name = $(this).attr('name');
			var value = $(this).val();
			return name+"="+value+"&";
		}
	).get();
	to_search = datas.join('');
	to_return = to_search+to_display;
	return to_return;
}

function generate_CONTACT_CSV () {
	to_display = CSV_elements_to_display();
	window.location.href = 'ContactsGenerateCSV.php?'+to_display;
}

function generate_Book_CSV () {
	to_display = CSV_elements_to_display();
	if (confirm( to_display )) {
		window.location.href = 'BooksGenerateCSV.php?'+to_display;
	};
}

