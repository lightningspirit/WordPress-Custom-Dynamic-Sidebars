(function($){
	$(document).ready(function(){
		$('#addtag .form-field #tag-slug').parent().remove();
		$('#edittag .form-field #slug').parents('tr').remove();
	});
})(jQuery);
