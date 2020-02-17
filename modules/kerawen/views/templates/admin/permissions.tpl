{*
 * 2016 KerAwen
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@kerawen.com so we can send you a copy immediately.
 *
 *  @author    KerAwen <contact@kerawen.com>
 *  @copyright 2014 KerAwen
 *  @license   http://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}
<script type="text/javascript">
	$(document).ready(function() {
	
		$('.nav-profile').on( 'click', function() {
		
  			$('.nav-profile').removeClass('active');
  			$(this).addClass('active');
  			$('.permform').hide();
  			$('#form-' + $(this).attr('rel')).show();
  				
  			return false;
  			
		}).first().trigger('click');
		
		
		$('#kerawen_perms input').on( 'change', function() {						
			do_action();
		});
		
		
		function do_action($data) {

				var $data = $('#kerawen_perms form').serialize();

				$.ajax({
					url: "{$link->getAdminLink('KerawenPermissions')|addslashes}",
					cache: false,
					method: 'POST',
					data : $data + "&ajaxMode=1&token={getAdminToken tab='KerawenPermissions'}",
					success : function(res,textStatus,jqXHR)
					{					
						try {
							if (res == 'ok') {
								showSuccessMessage("{l s='Update successful'}");
							} else {
								console.log(res);
								showErrorMessage("{l s='Update error'}");
							}
						} catch(e) {
							jAlert('Technical error');
						}
					}
				});	

		}
		
	})
</script>
 <style>
 #kerawen_perms label.disabled {
   color:#ccc;
 }
 </style>
 
<div class="row" id="kerawen_perms">

	<div class="productTabs col-lg-2">
		<div class="tab list-group">
		{foreach $profiles as $profile}
			<a class="list-group-item nav-profile" rel="{$profile.id_profile}" href="#">{$profile.name}</a>
		{/foreach}
		</div>
	</div>
	
	<div class="defaultForm form-horizontal col-lg-10">
	
	{*repeat for each profile*}
	{foreach $profiles as $profile}
	
	<form class="permform" id="form-{$profile.id_profile}" rel="{$profile.id_profile}" style="display:none;">
	
	  	{*<div>{$profile.name}</div>*}
	  	<div class="panel">
	  		
	  		{foreach from=$forms key=k item=form}

	  		<h3>{$k}</h3>
	  		<table class="perm-main-table">
	  			{cycle values="" reset=true print=false}
	  			{foreach from=$form key=c item=item}
	  			<tr class="{cycle values="perm-row,perm-rowalt"}">
	  				<td class="perm-col-title">{$item.label}</td>
	  				<td>
	  					<table class="perm-sub-table">
	  						<tr>
	  				{foreach from=$item.items key=q item=row}
	  					{assign var="option" value=""}  
	  					{assign var="class" value=""}						
	  					{if $item.type=='radio'}
	  							<td><input type="radio" id="{$c}-{$profile.id_profile}-{$row.value}" name="{$c}-{$profile.id_profile}" value="{$row.value}" {if isset($formsdata.{$profile.id_profile}.{$c})} {if $formsdata.{$profile.id_profile}.{$c} == $row.value } checked="checked" {/if} {/if} /></td>
	  					{elseif $item.type=='checkbox'}
	  							{assign var="checked" value=0}
	  							{if isset($formsdata.{$profile.id_profile}.{$c})}
	  								{assign var="strval" value=","|cat:$formsdata.{$profile.id_profile}.{$c}|cat:","}
	  								{assign var="strvalue" value=","|cat:$row.value|cat:"," }
	  								{if $strval|strpos:$strvalue !== false}
	  									{assign var="checked" value=1}
	  								{/if}
	  								{if isset($row.option)}
	  									{if ($row.option == "disabled")}
	  										{assign var="checked" value=0}
	  										{assign var="option" value="disabled=\"disabled\""}
	  										{assign var="class" value="disabled"}
	  									{/if}
	  								{/if}
	  							{/if}
	  							<td><input name="{$c}-{$profile.id_profile}[]" type="checkbox" id="{$c}-{$profile.id_profile}-{$row.value}" value="{$row.value}" {if $checked == 1}checked="checked"{/if} {$option} /></td>
	  					{else}
	  							<td>Type not defined please check permission.tpl</td>	
	  					{/if}
	  							<td class="perm-col-subtitle"><label for="{$c}-{$profile.id_profile}-{$row.value}" class="{$class}">{$row.label}</label></td>
	  				{/foreach}
	  						</tr>
	  					</table>
	  				</td>
				</tr>			
	  			{/foreach} 
 			</table>
	  		{/foreach} 
	  		<br/><hr/><br/>
	  	</div>
	  	</form>
	 {/foreach} 	
	 	
	</div>
{*
	<pre>
	{$formsdata|@print_r} 
	</pre>
*}
</div>	
	