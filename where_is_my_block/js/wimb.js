// NOTE: This non-minified file is kept for reference; it is not used anywhere

/**
 * Handles adjusting and auto-submitting the form upon user interaction
 *
 * @author Brad Beebe
 * @since v0.9.0
 */
(function($){

WhereIsMyBlock.Form = function(){
	var _this = this,
		$body = $('body'),
		$container = $('div#ccm-dashboard-content > div.container'),
		$ccmBody = $('div.ccm-pane-body'),
		$ccmFooter = $('div.ccm-pane-footer'),
		$form = $('div#ccm-dashboard-content form#wimb'),
		$formSubmit = $form.find('input[type="submit"]')
		$loader = undefined,
		$select = $form.find('select'),
		$btidSelect = $select.filter('select[name="btid"]'),
		$ippSelect = $select.filter('select[name="ipp"]'),
		$sortInput = $form.find('input[name="sort_by"]'),
		$dirInput = $form.find('input[name="sort_dir"]'),
		$pagingInput = $form.find('input[name="ccm_paging_p"]'),
		$refreshInput = $form.find('input[name="refresh"]'),
		$tokenInput = $form.find('input[name="ccm_token"]'),
		oQueryVars = {},
		bIsAjaxing = false,
		bResubmitFormOnCheckIn = false;


	/**
	 * Constructor
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.0
	 * @since v1.0.0.1 - Added page menu modal
	 */
	this.init = function(){
		// Setup ajax spinner
		$loader = $('<div id="ccm-dialog-loader-wrapper" class="ccm-ui"><img id="ccm-dialog-loader" src="' + CCM_IMAGE_PATH + '/throbber_white_32.gif" /></div>');

		$body.append($loader);

		// Interrupt the normal form submission so we can use our custom method
		$form.on('submit', function(e){
			$refreshInput.val(1);

			_this.submitForm();
			
			return false;
		});
		
		// Watches any Ajax requests to determine if the form needs to be
		// updated when the user interacts with a page menu modal
		$(document).ajaxSend(function(e){
			if((!arguments[2]) || !arguments[2].url){
				return false;
			}

			var sUrl = arguments[2].url,
				aToolScriptMatch = /[a-zA-Z0-9\-_\.]+(?=\?)/.exec(sUrl),
				sToolScript = '',
				oResubmittableAjaxReq = {
					edit_collection_popup: true,	// "Properties", "Full Page Caching", Delete", "Set Permissions" or "Design"
					versions: true,					// "Versions"
				};

			if(!aToolScriptMatch.length){
				return false;
			}

			sToolScript = aToolScriptMatch[0].replace(/(\.[a-zA-Z]+)$/, '');
			//console.log(sToolScript);
			
			if(oResubmittableAjaxReq[sToolScript]){
				bResubmitFormOnCheckIn = true;
			}

			if(sToolScript === 'sitemap_check_in' && bResubmitFormOnCheckIn === true && bIsAjaxing === false){
				$refreshInput.val(1);

				_this.submitForm();
			}
		});

		// Auto-submit form when any input is changed
		$select.each(function(){
			var $this = $(this);
			
			$this.on('change', function(e){
				if($btidSelect.find(':selected').val().length){
					var iRefresh = $(this).is($btidSelect) ? 1 : 0;
					$refreshInput.val(iRefresh);

					// Reset the paginated page counter
					$pagingInput.val(1);

					_this.submitForm();
				} 
			});
		});
		
		// Table heading links will adjust the sorting inputs and re-submit the form
		$ccmBody.on('click', 'table#ccm-where-is-my-block th a', function(e){
			if(bIsAjaxing !== false){
				return false;
			}

			var sCurrentSort = $sortInput.val(),
				sCurrentDir = $dirInput.val(),
				sNewSort = $(this).attr('data-sort'),
				sNewDir = sCurrentDir == 'asc' ? 'desc' : 'asc';
				
			$sortInput.val(sNewSort);
			
			if(sCurrentSort == sNewSort){
				$dirInput.val(sNewDir);
			}
			
			$refreshInput.val(0);

			_this.submitForm();
		});

		// Clicking on table rows will display the page menu modal
		$ccmBody.on('click', 'table#ccm-where-is-my-block tr', function(e){
			var $this = $(this);

			if(bIsAjaxing === true){
				return false;
			}
		
			if($this.hasClass('ccm-results-list-header')) {
				return false;
			}

			var oParams = {
				'cID': $this.attr('cID'), 
				'select_mode': $this.attr('sitemap-select-mode'), 
				'display_mode': $this.attr('sitemap-display-mode'), 
				'instance_id': $this.attr('sitemap-instance-id'),  
				'isTrash': $this.attr('tree-node-istrash'), 
				'inTrash': $this.attr('tree-node-intrash'), 
				'canCompose': $this.attr('tree-node-cancompose'), 
				'canEditProperties': $this.attr('tree-node-can-edit-properties'), 
				'canEditSpeedSettings': $this.attr('tree-node-can-edit-speed-settings'), 
				'canEditPermissions': $this.attr('tree-node-can-edit-permissions'), 
				'canEditDesign': $this.attr('tree-node-can-edit-design'), 
				'canViewVersions': $this.attr('tree-node-can-view-versions'), 
				'canDelete': $this.attr('tree-node-can-delete'), 
				'canAddSubpages': $this.attr('tree-node-can-add-subpages'), 
				'canAddExternalLinks': $this.attr('tree-node-can-add-external-links'), 
				'cNumChildren': $this.attr('cNumChildren'), 
				'cAlias': $this.attr('cAlias')
			};
			
			showPageMenu(oParams, e);
		});
		
		// Interrupt the normal pagination links to adjust the appropriate form inputs
		// then submit
		$ccmFooter.on('click', 'div.ccm-pagination a', function(e){
			var $this = $(this),
				$parent = $this.parent(),
				aMatch = /ccm_paging_p=(\d+)/.exec(this.href),
				iPage;

			// Prevent "disabled" links from firing requests OR if form is currently ajaxing
			if(($parent.hasClass('disabled') && !$parent.hasClass('ccm-pagination-ellipses')) || bIsAjaxing !== false){
				return false;
			}
			
			// Extract the page number or find the average if clicking on a '...' link
			if((aMatch instanceof Array) && aMatch.length){
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

		// If there are any sticky form values we auto-submit the form on page load
		if($btidSelect.find(':selected').val() != ''){			
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
	 * @since v0.9.0
	 */
	this.submitForm = function(){
		if(bIsAjaxing === true){
			return false;
		}

		_this.setFormStatus('busy');

		var $results = $('table#ccm-where-is-my-block');
		if($results.length){
			$results.css({opacity: 0.4});
		}
		
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
		
		$.get(WhereIsMyBlock.URL_TOOL_PAGE_BLOCK_SEARCH + sQuery, _this.parseXhrSuccess, 'json').error(_this.parseXhrError);

		//console.log(sQuery);
	};


	/**
	 * Adjusts the form and any related elements based on status
	 * 
	 * @param string sStatus - A form status
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.1.2
	 */
	this.setFormStatus = function(sStatus){
		switch(sStatus){
			case 'ready':
				$formSubmit.removeAttr('disabled');
				$btidSelect.removeAttr('disabled');
				$ippSelect.removeAttr('disabled');

				$loader.hide();

				bIsAjaxing = false;
				bResubmitFormOnCheckIn = false;

				break;

			case 'busy':
				bIsAjaxing = true;

				$formSubmit.attr('disabled', 'disabled');
				$btidSelect.attr('disabled', 'disabled');
				$ippSelect.attr('disabled', 'disabled');
				
				$loader.show();

				break;
		}
	};


	/**
	 * Callback for a successful form submission Ajax call
	 * Parses the JSON response and builds a results table (if any)
	 *
	 * @param object oData - JSON callback object
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.0
	 * @since v0.9.1.2 - Separated out the logic to display alerts/status messages
	 * @since v1.0.0.1 - Fixed table headings not being translatable
	 */
	this.parseXhrSuccess = function(oData){
		var oData = oData || {};

		//console.log(oData);
		
		// Remove any previous dynamic elements
		$ccmBody.find('table.ccm-results-list').remove();
		$ccmBody.find('div.ccm-paging-top').remove();
		$ccmFooter.find('div.ccm-pagination').remove();
		
		// Success
		if((oData.status === 'success' && oData.response) && (oData.response.tblData && oData.response.tblData instanceof Array && oData.response.tblData.length) && (typeof oData.response.permInfo === 'object')){
			CCM_SEARCH_INSTANCE_ID = oData.response.searchInstance;

			var aTblData = oData.response.tblData,
				oPagePerms = oData.response.permInfo;
			
			var sTable = '<table border="0" cellspacing="0" cellpadding="0" id="ccm-where-is-my-block" class="ccm-results-list">';
			sTable += '<thead><tr class="ccm-results-list-header">';
			
			// Build table headings (use JS equivalent of concrete5 unhandle() text helper method)
			for(var sHeading in aTblData[0]){
				// Only make columns out of headings with a translatable string
				if(typeof WhereIsMyBlock.TEXT_TABLE_COLUMNS[sHeading] !== 'string'){
					continue;
				}

				var sHeadClass = $sortInput.val() == sHeading ? 'ccm-results-list-active-sort-' + $dirInput.val() : '',
					sHeadText = WhereIsMyBlock.TEXT_TABLE_COLUMNS[sHeading];
				
				sTable += '<th class="' + sHeadClass + '"><a href="javascript:{};" data-sort="' + sHeading + '">' + sHeadText + '</a></th>';
			}
			
			sTable += '</tr></thead><tbody>';
			
			// Build result rows
			for(var i = 0, ii = aTblData.length; i < ii; i++){
				var oRow = aTblData[i],
					sRowClass = i % 2 !== 0 ? ' ccm-list-record-alt' : '',
					sPageId = oRow['xPageId'],
					sPagePerms = oPagePerms[sPageId].join(' ');

				sTable += '<tr class="ccm-list-record' + sRowClass + '" ' + sPagePerms + '>';
				
				for(var sCol in oRow){
					// Only fill columns that have a translatable string
					if(typeof WhereIsMyBlock.TEXT_TABLE_COLUMNS[sCol] !== 'string'){
						continue;
					}

					var sEncodedVal = oRow[sCol].toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
					
					sTable += '<td>' + sEncodedVal + '</td>';
				}
				
				sTable += '</tr>';
			}
			
			sTable += '</tbody></table>';
			
			// Add results and pagination to pane body
			var sPgnInfo = oData.response.pgnInfo,
				sPgn = oData.response.pgnHtml;
			
			if(sPgnInfo && sPgnInfo.length){
				sTable += '<div class="ccm-paging-top">' + sPgnInfo + '</div>';
			}
			
			if(sPgn && sPgn.length){
				$ccmFooter.prepend(sPgn);
			}
			
			$ccmBody.prepend(sTable);
		// Soft failure (e.g. Successful Ajax request, but nothing found)
		}else{
			_this.displayMessagesToUser(oData);
		}
		
		_this.setFormStatus('ready');
	};


	/**
	 * Callback for an erroneous form submission Ajax call
	 * Parses JSON response and displays any XHR errors
	 *
	 * @param object $xhr - jQuery XHR object
	 * @param string sStatus - Error text status
	 * @param object oException - Exception object
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.1.2
	 */
	this.parseXhrError = function($xhr, sStatus, oException){
		var oOpts = {
			status: 'error',
			alert: WhereIsMyBlock.TEXT_AJAX_ERROR,
			message: WhereIsMyBlock.TEXT_GENERAL_ERROR
		};

		if($xhr.responseText.length){
			oOpts.alert += $xhr.responseText.replace(/(<([^>]+)>)/ig, '');
		}

		_this.displayMessagesToUser(oOpts);

		_this.setFormStatus('ready');
	};


	/**
	 * Displays any alerts (wrapped in Twitter Bootstrap HTML) and/or
	 * status messages to the user
	 *
	 * @param object oArgs - Argument object
	 * (
	 * 		@param string alert - A message to display as an alert (optional)
	 * 		@param string message - A simple status message (optional)
	 * )
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.1.2
	 */
	this.displayMessagesToUser = function(oArgs){
		var sAlertTmpl,
			$alert,
			$message;

		if(oArgs.alert && oArgs.alert.length){
			var sAlert = '<div class="ccm-ui" id="ccm-dashboard-result-message">';
			sAlert += '<div class="row"><div class="span12">';
			sAlert += '<div class="alert alert-' + oArgs.status + '"><button type="button" class="close" data-dismiss="alert">×</button>' + oArgs.alert + '</div>';
			sAlert += '</div>';
			sAlert += '</div></div>';

			$alert = $(sAlert);
			$alert.css('display', 'block');

			$container.prepend($alert);
		}
		
		if(oArgs.message && oArgs.message.length){
			$message = $('<h5 class="responseText">' + oArgs.message + '</h5>');
			$ccmBody.prepend($message);
		}
	};
};

})(jQuery);