// NOTE: This non-minified file is kept for reference; it is not used anywhere

/**
 * Handles adjusting and auto-submitting the form upon user interaction
 *
 * @author Brad Beebe
 * @since July 12, 2012
 */
(function($){

WhereIsMyBlock.Form = function(){
	var _this = this,
		$container = $('div.ccm-dashboard-page-container'),
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
		$refreshInput = $form.find('input[name="refresh"]'),
		$tokenInput = $form.find('input[name="ccm_token"]'),
		oQueryVars = {};


	/**
	 * Constructor
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	this.init = function(){
		// Interrupt the normal form submission so we can use our custom method
		$form.on('submit', function(e){
			$refreshInput.val(1);

			_this.submitForm();
			
			return false;
		});
		
		// Add listeners to each select element to auto-submit the form upon user interaction
		$select.each(function(){
			var $this = $(this);
			
			$this.on('change', function(e){
				if($btidSelect.find(':selected').val().length > 0){
					var iRefresh = $(this).is($btidSelect) ? 1 : 0;
					$refreshInput.val(iRefresh);

					// Reset the paginated page counter
					$pagingInput.val(1);

					_this.submitForm();
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
			
			$refreshInput.val(0);

			_this.submitForm();
		});
		
		// Interrupt the normal pagination links to adjust the appropriate form inputs
		// then submit
		$ccmFooter.on('click', 'div.ccm-pagination a', function(e){
			var $this = $(this),
				aMatch = /ccm_paging_p=(\d+)/.exec(this.href),
				iPage;
			
			// Extract the page number or find the average if clicking on a '...' link
			if((aMatch instanceof Array) && aMatch.length > 1){
				iPage = parseInt(aMatch[1]);
			}else if($this.text() == '...'){
				var iPrev = parseInt($this.parent().prev().find('a:first-child').text()),
					iNext = parseInt($this.parent().next().find('a:first-child').text());
				
				iPage = Math.floor((iPrev + iNext) / 2);
			}else{
				iPage = 1;
			}
			
			$pagingInput.val(iPage);
			
			$refreshInput.val(0);

			_this.submitForm();
			
			return false;
		});

		// IF there are any sticky form values we auto-submit the form on page load
		if($btidSelect.find(':selected').val() != ''){
			$pagingInput.val(1);

			$refreshInput.val(0);

			_this.submitForm();
		}
	};


	/**
	 * Reads all the form inputs and constructs a GET query
	 * string which is passed to our tools script via Ajax
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	this.submitForm = function(){
		$loader.show();
		
		// Clear any previous alerts/messages
		$('div#ccm-dashboard-result-message').remove();
		$ccmBody.find('.responseText').remove();
		
		// Get the form input values
		oQueryVars.btid = $btidSelect.find(':selected').val();
		oQueryVars.ipp = $ippSelect.find(':selected').val();
		oQueryVars.sort_by = $sortInput.val();
		oQueryVars.sort_dir = $dirInput.val();
		oQueryVars.ccm_paging_p = $pagingInput.val();
		oQueryVars.refresh = $refreshInput.val();
		oQueryVars.ccm_token = $tokenInput.val();
		
		// Build GET query and send Ajax request to tool script
		var sQuery = '?';
		for(var sKey in oQueryVars){
			sQuery += sKey + '=' + oQueryVars[sKey] + '&';
		}
		sQuery = sQuery.slice(0, sQuery.length - 1);
		
		$.get(WhereIsMyBlock.URL_TOOL_PAGE_BLOCK_SEARCH + sQuery, _this.parseResponse, 'json');

		//console.log(sQuery);
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
	this.parseResponse = function(oData){
		var oData = oData || {};
		
		// Remove any previous dynamic elements
		$ccmBody.find('table.ccm-results-list').remove();
		$ccmBody.find('div.ccm-paging-top').remove();
		$ccmFooter.find('div.ccm-pagination').remove();
		
		// Success
		if((oData.status === 'success' && oData.response) && oData.response.tblData && oData.response.tblData instanceof Array && oData.response.tblData.length > 0){
			var aTblData = oData.response.tblData;
			
			var sTable = '<table border="0" cellspacing="0" cellpadding="0" id="ccm-where-is-my-block" class="ccm-results-list">';
			sTable += '<thead><tr>'
			
			// Build table headings (use JS equivalent of concrete5 unhandle() text helper method)
			for(var sHeading in aTblData[0]){
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
			for(var i = 0, ii = aTblData.length; i < ii; i++){
				var oRow = aTblData[i],
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
			var sPgnInfo = oData.response.pgnInfo;
				sPgn = oData.response.pgnHtml;
			
			if(sPgnInfo && sPgnInfo.length > 0) sTable += '<div class="ccm-paging-top">' + sPgnInfo + '</div>';
			if(sPgn && sPgn.length > 0) $ccmFooter.prepend(sPgn);
			
			$ccmBody.prepend(sTable);
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
	};
};

})(jQuery);