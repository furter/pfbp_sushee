(function($) {
	function createStatusHtml(path, name, input){
		/*
		var status = $('<div/>').append($('<a />',{
			'href':'../../system/tools/file_download.php?target=' + path,
			'title':'view/download', 
			'text':name,
			'class':'k-download'
		}));
		status.append($('<a/>', {'class':'remove-action', 'click':function(){
			input.val('');
			status.remove();
		}}));
		*/
		status = "";
		return status;
	}
	$.widget("ui.uploader", {
		getter: [],
    	options: {
			folder:'/tmp/',
			success:null,
			error:null,
			extentions: ['jpg','png','gif'] // fi ['jpg','png','gif'] todo : validate extentions before submited
		},
		_init : function() {
			var self = this;
			var status = $('<p class="status"/>');
			var input =  this.element;
			var value=input.val();
			if(value){
				status.html(createStatusHtml(value, value.split('/').pop(),input));
				// status.html($('<a />',{'href':value,'title':'view/download', 'text':value.split('/').pop()}));
				// status.append($('<button/>', {'class':'remove', 'onclick':function(){
				// 	input.val('');
				// 	status.html('');
				// }}));
			}
			var btnUpload = $('<button class="button">Parcourir</button>');
			this.element.hide();
			var container = $('<div class="uploader"/>');
			var formSumit = this.element.closest('form').find('input[type=submit]');
			this.element.parent().append(container);
			this.element.remove();
			this.element = container;
			this.element.append(btnUpload);
			this.element.append(input);
			this.element.append(status);
			//this.element.getPanel().panel('addExternalResource',this);
			
			new AjaxUpload(btnUpload, {
				action: 'file_upload.php',
				name: 'uploadfile',
				responseType:'json',
				data:{
					folder:this.options.folder
				},
				onSubmit: function(file, ext){
					if (ext == 'jpg' || ext == 'jpeg' || ext == 'gif' || ext == 'png') {
						if(btnUpload.hasClass('loading')){
							return false;
						}
						status.html('');
						btnUpload.addClass('loading');
						btnUpload.text('Chargement en cours');
						btnUpload.attr('disabled',true);
						formSumit.attr('disabled',true);
						formSumit.addClass('disabled');
					} else {
						alert('Veuillez charger une image de type jpg, gif ou png');						
						return false;
					}
				},
				onComplete: function(file, response){
					alert('complete');
					btnUpload.addClass('hidden');
					btnUpload.removeClass('loading');
					btnUpload.text('Parcourir');
					btnUpload.removeAttr('disabled');
					formSumit.removeAttr('disabled');
					formSumit.removeClass('disabled');
					if(response && response.status==='success'){
						status.html(createStatusHtml(response.path, response.name,input));
						// status.html($('<a />',{'href':value,'title':'view/download', 'text':response.name}));
						// status.append($('<button/>', {'class':'remove', 'onclick':function(){
						// 	input.val('');
						// 	status.html('');
						// }}));
						input.val(response.path);
						container.append(input);
						if($.isFunction(self.options.success)){
							self.options.success.apply(btnUpload,[response]);
						}
					} else{
						if(response.message){
							status.text(response.message);
						}else{
							status.text('Le chargement a échoué');
						}
						status.addClass('alert');
						if($.isFunction(self.options.error)){
							self.options.error.apply(btnUpload,[response]);
						}
					}
				}
			});
		}
	});
})(jQuery);
