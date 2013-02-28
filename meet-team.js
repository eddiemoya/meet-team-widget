function bananas(parent) {
	var widget = jQuery(parent).closest('.widget');
	jQuery('.widget-control-save', widget).trigger('click');
}

/**
 * @author Eddie Moya
 */
jQuery(document).ready(function($) { 

	$('.meet-team-widget-flush-user-cache').on('click', function(e){
 		e.preventDefault();

 		
		var data = {
			action		: 'meet_team_query_flush_cache',
			user_id		: ''
		};
		
		var container = $(this).closest('p');
		//console.log(ajaxurl)
		var clear_cache = $.ajax({
			type : 'POST',
			url  : ajaxurl,
			data : data,
			//timeout: 60*1000*3, //3 minutes
			beforeSend:function(){
				$('a', container).remove();
				$('.ajax-feedback', container).css('display', 'block');
				$('.ajax-feedback', container).css('visibility', 'visible');

			},
			error:function(x, t, m){
				console.log(m);
				container.empty();
				container.append('Woops! <br /> Timeout, something went wrong.');
			},
			success:function(results){
				container.empty();
				container.append('Done! <br /> Click Save to refresh.');
				//('.widget-control-save', widget).trigger('click');
			}
		});

		//clear_cache.abort();

 	});
});