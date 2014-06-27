function displayNext (element) {
	if ( element.next().length > 0) {
		element.hide();
		element.next().show();
	} else {
		element.hide();		
		element.parent('div').children().eq(0).show();		
	}
	$('.w2010 .books li.active').removeClass('active');
	id = $('.img_books img:visible').attr('id').substring(3);
	$('#li'+id).addClass('active');
	t=setTimeout(" 	displayNext( $('.img_books img:visible')) ",3000);
}
function resize2010 () {
	h = $(window).height() - 240;
	$('#content .w2010').css({height: h});
	wc = $('#content .w2010 .img_books').outerWidth();
	wi = $('#content .w2010 .img_books img:visible').outerWidth();
	if (wi > wc) {
		l = -1 * Math.round( (wi - wc) / 2 );
		$('#content .w2010 .img_books img').css({left:l});
	} else {
		$('#content .w2010 .img_books img').css({left:''});
	}
}
function init2010 () {
	if ( $('#content div.w2010').length > 0) {
		resize2010();
		t=setTimeout(" 	displayNext( $('.img_books img:visible')) ",3000);
	};
	$('#content').css({width:816});
	resize2010();
	$(window).resize(resize2010);
}

$(document).ready(init2010);
