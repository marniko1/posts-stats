jQuery(document).ready(function($){
	$.getJSON(theme.templateUrl + "/posts-stats/config.json", function(data){

		if (data.Name || data.Name != '') {
			$('#wp_site_name').val(data.Name);
		}

		if (data.URL || data.URL != '') {
			$('#url').val(data.URL);
		}

		$.each(data.cats, function(key, cat){
			$.each($('.categories'), function(k, v){
				if ($(v).val() == cat) {
					$(this).attr('checked', true);
				}
			});
		});
	});
});