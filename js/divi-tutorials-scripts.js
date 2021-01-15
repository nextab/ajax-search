(function($){
	// Implementation for the Ajax Custom Post Type Filter
	var url = window.location.href;
	$('p.invi input').val(url);
	// The Filter
	$('#filter').submit(nxt_tut_filter());
})(jQuery)

function nxt_tut_filter(){
	var filter = jQuery('#filter');
	jQuery.ajax({
		url:filter.attr('action'),
		type:filter.attr('method'), // POST
		data:filter.serialize(), // form data
		success:function(data){
			jQuery('#response').html(data); // insert data
		}
	});
	return false;
}
