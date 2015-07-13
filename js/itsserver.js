/**
 * InvisionitServer
 * 
 * I am AngularJS noob so had to do jQuery workarounds such as DOMNodeInserted, document ready etc for selection
 */
(function ($, require) {
	
	$( document ).ready(function() {
		$("#InvisionitServer a[href*='category=']").on('click', function () { console.log('categoryClick');
			// When selecting a category, eg. bind, disk, nginx etc.
			// Ensure the server URL gets updated, in case the user filters on server

			var url = $(this).attr('href');
			var category = broadcast.getParamValue('category',url);

			$('#InvisionitServer .item').each(function(i) {
				var oldHash = $(this).attr('href');
				var newHash = broadcast.updateParamValue('category='+category, oldHash);
				$(this).attr('href', newHash);
			});
		});
		
		// if page is refreshed, ensure the menu is selected if in the url hash	and category links contain selected server
		server = broadcast.getValueFromUrl('server', broadcast.getHash());

		$("#InvisionitServer a[href*='category=']").each(function(i) {
			var oldHash = $(this).attr('href');
			var newHash = broadcast.updateParamValue('server='+server, oldHash);
			$(this).attr('href', newHash);
		});
		$(document).on('DOMNodeInserted', function(e) {
		    if (e.target.tagName == 'SPAN' && e.target.className == 'title ng-binding' && e.target.innerHTML == 'Choose Server' ) {
				if (server) { 
					$("#InvisionitServer .menuDropdown span.title").text(server);
				}
		    }
		});
	});
	
	$(document).on('click','#InvisionitServer .item',function(e){ console.log('serverClick');
		
		// When selecting a server
		// Ensure the category URL gets updated to filter on server		
		url = broadcast.getHash();
		var server = broadcast.getValueFromUrl('server', url);
		
		ajaxHelper = require('ajaxHelper');
	
	    var ajax = new ajaxHelper();
	    ajax.setUrl('index.php?module=InvisionitServer&action=getCategories&server='+server);
	    ajax.setFormat('json'); // the expected response format
	    ajax.setLoadingElement('#content');
	    ajax.setCallback(function (response) { 
	    	
	    	// Hide all category menu first as not each server will have everything
	    	$("#InvisionitServer a.menuItem[href*='category=']").each(function(i) {
	    		$(this).parent().hide();
	    	});
	    	
	    	// Show each category menu, and update URL to include server
			$.each(response, function(i, item) {
				var sel = "a.menuItem[href*='category="+item+"']";
				$(sel).parent().show();
				var oldHash = $(sel).attr('href');
                var newHash = broadcast.updateParamValue('server='+server, oldHash);
                $(sel).attr('href', newHash);
            });
	    });
	    ajax.send();

		
	});

})(jQuery, require);

