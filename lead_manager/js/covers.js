jQuery(function()
{
	jQuery('.jquery-calendar').calendar();
	
	jQuery('#covers').Sortable(
	{
		accept: 'cover_box',
		opacity: 0.7,
		handle: 'h4',
		onChange: function()
		{
			serial = jQuery.SortSerialize('covers');
			
			jQuery('#covers .cover_box').each(function(i){
				jQuery(this).find('.cover_order').val(i+1);
			})
		},
		onStart : function()
		{
			jQuery.iAutoscroller.start(this, document.getElementsByTagName('body'));
		},
		onStop : function()
		{
			jQuery.iAutoscroller.stop();
		}
	});
	
	jQuery('.cover_showhide').click(function(){
		jQuery(this).siblings('.cover_details').toggle();
	});
});

function cover_ajax_search(url, target)
{
  jQuery("#cover"+target).addClass('working');
  
	jQuery.post(url, {action:"cover_ajax_search", post_id:jQuery("#post_id"+target).val()}, function(xml){
		jQuery("#post_title"+target).html(jQuery("post_title", xml).text());
		jQuery("#cover_image"+target).html(jQuery("post_image", xml).text());
		jQuery("#post_id"+target).val(jQuery("post_id", xml).text());
		jQuery("#cover_title"+target).val(jQuery("cover_title", xml).text());
		jQuery("#cover_excerpt"+target).val(jQuery("cover_excerpt", xml).text());
		jQuery("#cover_guid"+target).val(jQuery("cover_guid", xml).text());
		jQuery("#cover_in_date"+target).val(jQuery("cover_in_date", xml).text());
		jQuery("#cover_in_hour"+target).val(jQuery("cover_in_hour", xml).text());
		jQuery("#cover_out_date"+target).val(jQuery("cover_out_date", xml).text());
		jQuery("#cover_out_hour"+target).val(jQuery("cover_out_hour", xml).text());
		
		jQuery("#cover"+target).removeClass('working');
	});
}

function cover_ajax_save(url, target)
{
  jQuery("#cover"+target).addClass('working');
  
	jQuery.post(url, {
		action:"cover_ajax_save",
		post_id:jQuery("#post_id"+target).val(),
		cover_order:jQuery("#cover_order"+target).val(),
		cover_title:jQuery("#cover_title"+target).val(),
		cover_excerpt:jQuery("#cover_excerpt"+target).val(),
		cover_guid:jQuery("#cover_guid"+target).val(),
		cover_in_date:jQuery("#cover_in_date"+target).val(),
		cover_in_hour:jQuery("#cover_in_hour"+target).val(),
		cover_out_date:jQuery("#cover_out_date"+target).val(),
		cover_out_hour:jQuery("#cover_out_hour"+target).val(),
		cover_area_id:jQuery("#cover_area_id").val(),
		image_id:jQuery("input[name='image_id"+target+"']:checked").val(),
	}, function(xml){
	  //alert(xml);
	  
	  jQuery("#cover"+target).removeClass('working');
	  
		if(jQuery("status", xml).text() == 'ok')
			jQuery("#cover"+target).addClass('sucess');
		else
			jQuery("#cover"+target).addClass('fail');
	});
}

function cover_ajax_save_all(url, total)
{
	for(target = 1; target <= total; target++)
	{
		cover_ajax_save(url, target);
	}
}
