var AffLabelEscape = new Object();
AffLabelEscape['&#233;'] = 'é';
AffLabelEscape['&#232;'] = 'è';
AffLabelEscape['&#234;'] = 'ê';
AffLabelEscape['&#235;'] = 'ë';
AffLabelEscape['&#146;'] = '’';
AffLabelEscape['&#224;'] = 'à';
AffLabelEscape['&#8217;'] = '’';
AffLabelEscape['&#226;'] = 'â';
function AffGetLabel(l)
{
	var ret = l;
	for (var key in AffLabelEscape) 
	{
		ret = ret.replace(new RegExp(key,'g'), AffLabelEscape[key]);
	}
	return ret;
}
function search_books() {
	criterias = $('#SearchBooks #search').val();
	crit_full = '#specific table tr.Book:contains('+criterias+')';
	crit_full += ', #specific table tr.Book:contains('+criterias.toLowerCase()+')';
	crit_full += ', #specific table tr.Book:contains('+criterias.substring(0,1).toUpperCase()+criterias.substring(1).toLowerCase()+')';
	console.log(crit_full);
	if (criterias != '') {
	$('#specific table tr.Book').fadeOut();
	$(crit_full).delay(400).fadeIn();
	total = 0;
	$(crit_full).map(function() {
		total += 1;
	})
	if (total == 0) {
		text_to_total = no_book;
		text_to_end = no_book_found;
	} else if (total == 1){
		text_to_total = total+" "+one_book;
		text_to_end = one_book_found;
	} else {
		text_to_total = total+" "+many_books;
		text_to_end = many_books_found;
	};
	text_to_total = AffGetLabel(text_to_total);
	text_to_end = AffGetLabel(text_to_end);
	$('#specific h2 .total').text(text_to_total);
	$('#specific h2 .end').text(text_to_end);
	};
}
function refresh_book_listing () {
	// récupération des critères de sélection
	criterias = "";
	$('#BrowseBooks select').map(function() {
		selected_value = $(this).val();
		if (selected_value != "") {
			criterias += "."+$(this).val();
		};
	})
	$('#specific table tr.Book').fadeOut();
	$('#specific table tr.Book'+criterias).delay(400).fadeIn();
	total = 0;
	$('#specific table tr.Book'+criterias).map(function() {
		total += 1;
	})
	if (total == 0) {
		text_to_total = no_book;
		text_to_end = no_book_found;
	} else if (total == 1){
		text_to_total = total+" "+one_book;
		text_to_end = one_book_found;
	} else {
		text_to_total = total+" "+many_books;
		text_to_end = many_books_found;
	};
	text_to_total = AffGetLabel(text_to_total);
	text_to_end = AffGetLabel(text_to_end);
	$('#specific h2 .total').text(text_to_total);
	$('#specific h2 .end').text(text_to_end);
}
function resize_preview_image () {
	if(screen.availHeight <= 800) {
		$('.preview').addClass('small');
		$('#content_details').addClass('small');
		$('#content_details .detail').addClass('small');
		$('.preview').map(function(){
			width = $(this).outerWidth();
			new_width = Math.round(width / 9 * 7);
			$(this).css('width', new_width);
			});
		$('.preview img.preview_img').css('height', '420px');
	}
}
function resize_body (target) {
	if (target != '') {
		div_target = "#"+target;		
	} else {
		div_target = "#specific";
	};
	specific_positions = $(div_target).offset().left;
	space_right = document.documentElement.clientWidth - $(div_target).offset().left;
	preview_width = 0;
	margin_width = 0;
	number_items = 0;
	$('.preview').map(function(){
		preview_width += $(this).outerWidth();
		margin_width += 5;
		number_items += 1;
	});
	safety = 0;
	if (number_items > 15) {safety = number_items * 40};
	compute_width = specific_positions + preview_width + margin_width + space_right + safety;
	width = 'width:'+compute_width+'px;';
	$('body').attr('style',width);	
}
function display_content_detail (target) {
	$('#content .inside_navigation li a').removeClass('active');
	$('#content_details').removeClass('hidden');
	$('#content_details .detail').addClass('hidden');
	$('#'+target).removeClass('hidden');
}
function close_content_details () {
	$('#content .inside_navigation li a').removeClass('active');
	$('#content_details .detail').addClass('hidden');
	$('#content_details').addClass('hidden');
}