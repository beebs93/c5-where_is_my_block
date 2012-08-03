// NOTE: This non-minified file is kept for reference; it is not used anywhere

/**
 * Handles adjusting and auto-submitting the form upon user interaction
 *
 * @author Brad Beebe
 * @since July 12, 2012
 */
(function($){
	
$(document).ready(function(){
	var $container = $('div.ccm-dashboard-page-container'),
		$ccmBody = $('div.ccm-pane-body'),
		$overlay = $('div#bodyOverlay'),
		$ccmFooter = $('div.ccm-pane-footer'),
		$form = $('div#ccm-dashboard-content form#wimb'),
		$loader = $('img#ccm-wimb-loading'),
		$select = $form.find('select'),
		$btidSelect = $select.filter('select[name="btid"]'),
		$ippSelect = $select.filter('select[name="ipp"]'),
		$sortInput = $form.find('input[name="sort_by"]'),
		$dirInput = $form.find('input[name="sort_dir"]'),
		$pagingInput = $form.find('input[name="ccm_paging_p"]'),
		oQueryVars = {};
	
	
	/**
	 * Reads all the form inputs and constructs a GET query
	 * string which is passed to our tools script via Ajax
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	function submitForm(){
		$loader.show();
		$overlay.show();
		
		// Clear any previous alerts, messages, result tables and pagination
		$('div#ccm-dashboard-result-message').remove();
		$ccmBody.find('.responseText').remove();		
		$ccmFooter.find('div.ccm-pagination').remove();
		
		// Get the form input values
		oQueryVars.btid = $btidSelect.find(':selected').val();
		oQueryVars.ipp = $ippSelect.find(':selected').val();
		oQueryVars.sort_by = $sortInput.val();
		oQueryVars.sort_dir = $dirInput.val();
		oQueryVars.ccm_paging_p = $pagingInput.val();
		
		// Build GET query and send Ajax request to tool script
		var sQuery = '?';
		for(var sKey in oQueryVars){
			sQuery += sKey + '=' + oQueryVars[sKey] + '&';
		}
		sQuery = sQuery.slice(0, sQuery.length - 1);
		
		$.get(WIMB_TOOLS_URL + sQuery, handleResponse, 'json');
	};
	
	
	/**
	 * Callback for the form submission Ajax call
	 * Parses the JSON response and builds a results table (if successful)
	 * and/or any status/error messages
	 *
	 * @param object oData - JSON callback object
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	function handleResponse(oData){
		var oData = oData || {};
		
		// Remove any previous results
		$ccmBody.find('table.ccm-results-list').remove();
		
		// Success
		if(oData.status === 'success' && oData.response && oData.response instanceof Array && oData.response.length > 0){
			var sTable = '<table border="0" cellspacing="0" cellpadding="0" id="ccm-where-is-my-block" class="ccm-results-list">';
			sTable += '<thead><tr>'
			
			// Build table headings (use JS equivalent of concrete5 unhandle() text helper method)
			for(var sHeading in oData.response[0]){
				var sHeadClass = $sortInput.val() == sHeading ? 'ccm-results-list-active-sort-' + $dirInput.val() : '',
					aHeadText = sHeading.toString().replace(/_/g, ' ').replace(/-/g, ' ').replace(/\//g, ' ').split(' '),
					sHeadText = '';
				
				for(var i = 0, ii = aHeadText.length; i < ii; i++){
					sHeadText += aHeadText[i].charAt(0).toUpperCase() + aHeadText[i].slice(1);
					if(i + 1 != ii) sHeadText += ' ';
				}
				
				sTable += '<th class="' + sHeadClass + '"><a href="javascript:{};" data-sort="' + sHeading + '">' + sHeadText + '</a></th>';
			}
			
			sTable += '</tr></thead><tbody>';
			
			// Build result rows
			for(var i = 0, ii = oData.response.length; i < ii; i++){
				var oRow = oData.response[i],
					sRowClass = i % 2 !== 0 ? ' ccm-list-record-alt' : '';

				sTable += '<tr class="ccm-list-record' + sRowClass + '">';
				
				for(var sCol in oRow){
					var sEncodedVal = oRow[sCol].toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
					
					sTable += '<td>';
					
					sTable += (sCol == 'page_path') ? '<a href="' + CCM_BASE_URL + oRow[sCol] + '" target="_blank">' + sEncodedVal + '</a>' : sEncodedVal;
					
					sTable += '</td>'
				}
				
				sTable += '</tr>';
			}
			
			sTable += '</tbody></table>';
			
			// Add results and pagination to pane body
			var $table = $(sTable);
			$ccmBody.prepend($table);
			if(oData.pagination && oData.pagination.length > 0) $ccmFooter.prepend(oData.pagination);
		// Failure
		}else{
			var $alert,
				$message;
			
			if(oData.alert && oData.alert.length > 0){
				$alert = $(oData.alert);
				$alert.css('display', 'block');
				
				$container.prepend($alert);
			}
			
			if(oData.message && oData.message.length > 0){
				$message = $('<h5 class="responseText">' + oData.message + '</h5>');
				$ccmBody.prepend($message);
			}
		}
		
		$loader.hide();
		$overlay.fadeTo(500, 0, function(){
			$overlay.css('opacity', '').hide();	
		});
	};
	
	// Interrupt the normal form submission so we can use our custom method
	$form.on('submit', function(e){
		submitForm();
		
		return false;
	});
	
	// Add listeners to each select element to auto-submit the form upon user interaction
	$select.each(function(){
		var $this = $(this);
		
		$this.on('change', function(e){
			if($btidSelect.find(':selected').val().length > 0){
				// Reset the paginated page counter
				$pagingInput.val(1);
				
				submitForm();
			} 
		});
	});
	
	// Add listeners to any table heading links that will adjust the sorting inputs
	// and re-submit the form
	$ccmBody.on('click', 'table#ccm-where-is-my-block th a', function(e){
		var sCurrentSort = $sortInput.val(),
			sCurrentDir = $dirInput.val(),
			sNewSort = $(this).get(0).getAttribute('data-sort'),
			sNewDir = sCurrentDir == 'asc' ? 'desc' : 'asc';
			
		$sortInput.val(sNewSort);
		
		if(sCurrentSort == sNewSort) $dirInput.val(sNewDir);
		
		submitForm();
	});
	
	// Interrupt the normal pagination links to adjust the appropriate form inputs
	// then auto-submit
	$ccmFooter.on('click', 'div.ccm-pagination a', function(e){
		var aMatch = /ccm_paging_p=(\d+)/.exec(this.href),
			iPage = ((aMatch instanceof Array) && aMatch.length > 1) ? parseInt(aMatch[1]) : 1;
		
		$pagingInput.val(iPage);
		
		submitForm();
		
		return false;
	});
});

})(jQuery);