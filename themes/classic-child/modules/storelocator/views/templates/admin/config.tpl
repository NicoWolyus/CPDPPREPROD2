{*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*}

<script type="text/javascript" src="{$smarty.const.__PS_BASE_URI__|escape:'htmlall':'UTF-8'}modules/storelocator/views/js/jquery.form.js"></script>
{literal}
<script type="text/javascript">
$(document).ready(function()
{
	$('#next-button').click(function()
	{
		$('#upload-store-contact').hide();
		$('#store-contacts').show();
	});
$('#upload-contacts').each(function()
{
    this.reset();
});
	$("#upload-contacts").submit(function(e)
	{
	    var formObj = $(this);
	    var formURL = formObj.attr("action");
	    if(window.FormData !== undefined)  // for HTML5 browsers
	    {
	        var formData = new FormData(this);
	        $.ajax({
	            url 		: formURL,
	            dataType 	: "json",
	            type 		: "POST",
	            contentType	: "application/json",
	            data 		: formData,
	            contentType: false,
	            cache: false,
	            processData:false,
	            success: function(data)
	            {
	            	
	            	if(data)
	            	{
	            		$('#next-button').hide();
	            		$('#uploaded-contacts').html(data);
	            	}

	            },
	            error: function(jqXHR, textStatus, errorThrown) 
	            {
	            	alert(errorThrown);
	            }           
	       });
	        e.preventDefault();
	   }
	   else  //for olden browsers
	    {
	        //generate a random id
	        var  iframeId = "unique" + (new Date().getTime());

	        //create an empty iframe
	        var iframe = $('<iframe src="javascript:false;" name="'+iframeId+'" />');

	        //hide it
	        iframe.hide();

	        //set form target to iframe
	        formObj.attr("target",iframeId);

	        //Add iframe to body
	        iframe.appendTo("body");
	        iframe.load(function(e)
	        {
	            var doc = getDoc(iframe[0]);
	            var docRoot = doc.body ? doc.body : doc.documentElement;
	            var data = docRoot.innerHTML;
	            //data return from server.
	            
	        });
	    
	    }

	});
//$("#upload-contacts").submit();
});
//checking file-type
function checkFile(filename)
 {
 	$('#success_msg').hide();
	$('#error_msg').hide();
 	var extension = filename.replace(/^.*\./, '');
    	if (extension == filename)
    		extension = '';
        else
         extension = extension.toLowerCase();

       if ( (filename) && (extension.toString() == 'csv' || extension.toString() == 'text'))
		{
			$('#error_msg').hide('slow');
			$('#next-button').show();
		}
       else
       	{
       		$('#success_msg').hide();
       		$('#error_msg').show('slow').html('<p style=\'color:red;margin-left:3px\'>Invalid File..!!<p>');
       		$('#next-button').hide('slow');
       	}
   }

</script>
{/literal}
<div id="import-store-contacts" class="panel">
	<div id="error-div">
		<div class="conf error alert alert-danger" id="error_msg" style="display:none;width:100%"></div>
		<div class="conf alert alert-success" id="success_msg" style="display:none;width:100%"></div>
	</div>
	<h2 class="panel-heading">{l s='Import Store Contacts' mod='storelocator'}</h2>
	<div class="separation"></div>
	<form id="upload-contacts" class="defaultForm" method="post" action="{$moduleLink|escape:'htmlall':'UTF-8'}&importContacts" >
		<div id="upload-store-contact">
			<label class="col-lg-3" for="contacts">{l s='Upload CSV File : ' mod='storelocator'}</label>
			<div class="form-group margin-form">		
				<div class="col-lg-9" id="contacts">
					<input id="fileupload" class="upload-csv gk-input" type="file" name="csv" style="background-color: #FFFFFF;border: 1px solid #CCCCCC;" onchange="checkFile($(this).val());" />
				</div>
			</div>
		</div>
		<div class="margin-form form-group">
			<button id="next-button" name="importContacts" class="btn btn-default pull-right" type="submit" style="display:none;"><i class="process-icon-next"></i>{l s='Next' mod='storelocator'}</button>
		</div>
		<div class="clearfix"></div>
	</form>
	<div id="store-contacts" style="display:none;">
			<div id="import-hint" class="hint alert alert-info" style="display:block;">{l s='Please match each column of your source CSV file to the destination Database columns.' mod='storelocator'}</div>
			<div id="uploaded-contacts" class="form-group margin-form">
				<!-- csv data will be injected here-->
			</div>
		</div>
	<div class="panel-footer">
		<a class="btn btn-default" href="{$url|escape:'htmlall':'UTF-8'}">
			<i class="process-icon-cancel"></i>{l s='Cancel' mod='storelocator'}
		</a>
		<!--a class="btn btn-default pull-right" href="#" style="display:none;">
			<i class="process-icon-save"></i>{l s='Import Contacts' mod='storelocator'}
		</a-->
	</div>
</div>