function addPicture () {
	path = $('#PICTURE').val();
	a = '<li>';
	a += '<img src="/Public/common/img_resize.php?width=60&path='+path+'" path="'+path+'"/>';
}
function defineUploadButton () {
	$('#PICTURE').uploader({
		success: function (response){
			path = response.path;
			$('.PADepositPictures .picture_preview').html('<img src="/Public/common/img_resize.php?width=60&path='+path+'"/>');
			$('#PICTURE').val(path);
		}
	});
}
function loadPicture () {
	// body...
}
$(document).ready( defineUploadButton );
