{*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to http://doc.prestashop.com/display/PS15/Overriding+default+behaviors
* #Overridingdefaultbehaviors-Overridingamodule%27sbehavior for more information.
*
* @author Samdha <contact@samdha.net>
* @copyright  Samdha
* @license    commercial license see license.txt
*}
<div id="tabRegenerate" class="col-lg-10 col-md-9">
    <div class="panel">
        {if $version_16}<h3 class="tab"> <i class="icon-refresh"></i> {l s='Regenerate' mod='regeneratethumbnails'}</h3>{/if}

		<div class="{if $version_16}alert alert-info{else}solid_hint hint{/if}">
			{l s='Select below the images to regenerate. You can see more categories by using the button' mod='regeneratethumbnails'} <span class="ui-icon ui-icon-triangle-1-e" style="display: inline-block"></span> {l s='at the start of a line.' mod='regeneratethumbnails'}<br/>
			<br/>
			<img src="{$module_path|escape:'htmlall':'UTF-8'}views/img/help.png?v={$module_version|escape:'htmlall':'UTF-8'}" style="max-width: 100%"/>
		</div>

		<table class="table">
			<tr class="first">
				<th colspan="2">{l s='Images' mod='regeneratethumbnails'}</th>
				<th>{l s='Actions' mod='regeneratethumbnails'}</th>
				<th style="width:50%">{l s='Status' mod='regeneratethumbnails'}</th>
			</tr>
			<tr
				class="image_global"
				data-current="{$global_current|intval}"
				data-number="{$global_number|intval}"
			>
				<td colspan="2" class="global_expand not_expanded" title="{l s='Display/hide details' mod='regeneratethumbnails'}">
					<span class="ui-icon ui-icon-triangle-1-e"></span>
					<span class="ui-icon ui-icon-triangle-1-s"></span>
					{l s='All' mod='regeneratethumbnails'}
				</td>
				<td>
					<span class="samdha_button global_start ui-button-icon-only" title="{l s='Start' mod='regeneratethumbnails'}">
						<span class="ui-button-icon-primary ui-icon ui-icon-play"></span>
					</span>
					<span class="samdha_button global_stop disable" title="{l s='Stop' mod='regeneratethumbnails'}">
						<span class="ui-button-icon-primary ui-icon ui-icon-stop"></span>
					</span>
					<span class="samdha_button global_pause disable" title="{l s='Pause' mod='regeneratethumbnails'}">
						<span class="ui-button-icon-primary ui-icon ui-icon-pause"></span>
					</span>
					<span class="samdha_button global_continue disable" title="{l s='Continue' mod='regeneratethumbnails'}">
						<span class="ui-button-icon-primary ui-icon ui-icon-seek-next"></span>
					</span>
				</td>
				<td><div class="progress"><div class="text"></div></div></td>
			</tr>
			{foreach from=$types key=type_name item=type}
				<tr
					class="image_type"
					data-type="{$type_name|escape:'htmlall':'UTF-8'}"
					data-current="{$type.current|intval}"
					data-number="{$type.number|intval}"
					style="display: none"
				>
					<td colspan="2" class="type_expand not_expanded" title="{l s='Display/hide details' mod='regeneratethumbnails'}">
						<span class="ui-icon ui-icon-triangle-1-e"></span>
						<span class="ui-icon ui-icon-triangle-1-s"></span>
						{$type.name|escape:'htmlall':'UTF-8'}
					</td>
					<td>
						<span class="samdha_button type_start ui-button-icon-only" title="{l s='Start' mod='regeneratethumbnails'}">
							<span class="ui-button-icon-primary ui-icon ui-icon-play"></span>
						</span>
						<span class="samdha_button type_stop" title="{l s='Stop' mod='regeneratethumbnails'}">
							<span class="ui-button-icon-primary ui-icon ui-icon-stop"></span>
						</span>
						<span class="samdha_button type_pause disable" title="{l s='Pause' mod='regeneratethumbnails'}">
							<span class="ui-button-icon-primary ui-icon ui-icon-pause"></span>
						</span>
						<span class="samdha_button type_continue disable" title="{l s='Continue' mod='regeneratethumbnails'}">
							<span class="ui-button-icon-primary ui-icon ui-icon-seek-next"></span>
						</span>
					</td>
					<td><div class="progress"><div class="text"></div></div></td>
				</tr>
				{foreach from=$formats[$type_name] item=format key=format_name}
					<tr
						data-status="pause"
						data-format="{$format_name|escape:'htmlall':'UTF-8'}"
						data-type="{$type_name|escape:'htmlall':'UTF-8'}"
						data-current="{$format.current|intval}"
						data-number="{$format.number|intval}"
						class="image_format image_type_{$type_name|escape:'htmlall':'UTF-8'}"
						style="display: none"
					>
						<td></td>
						<td>{$format.name|escape:'htmlall':'UTF-8'}</td>
						<td>
							<span class="samdha_button format_start ui-button-icon-only" title="{l s='Start' mod='regeneratethumbnails'}">
								<span class="ui-button-icon-primary ui-icon ui-icon-play"></span>
							</span>
							<span class="samdha_button format_stop" title="{l s='Stop' mod='regeneratethumbnails'}">
								<span class="ui-button-icon-primary ui-icon ui-icon-stop"></span>
							</span>
							<span class="samdha_button format_pause disable" title="{l s='Pause' mod='regeneratethumbnails'}">
								<span class="ui-button-icon-primary ui-icon ui-icon-pause"></span>
							</span>
							<span class="samdha_button format_continue disable" title="{l s='Continue' mod='regeneratethumbnails'}">
								<span class="ui-button-icon-primary ui-icon ui-icon-seek-next"></span>
							</span>
						</td>
						<td><div class="progress"><div class="text"></div></div></td>
					</tr>
					<tr class="regeneratethumbnails_log">
						<td colspan="2"></td>
						<td colspan="2"><div class="regenerate_log"></div></td>
					</tr>
				{/foreach}
			{/foreach}
		</table>
	</div>
</div>

<div id="tabParameters" class="col-lg-10 col-md-9">
    <div class="panel">
        {if $version_16}<h3 class="tab"> <i class="icon-cogs"></i> {l s='Parameters' mod='regeneratethumbnails'}</h3>{/if}
		<form action="{$module_url|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
			<div class="form-group clear">
				<label for="{$module_short_name|escape:'htmlall':'UTF-8'}directory"> {l s='Working directory' mod='regeneratethumbnails'}</label>
				<div class="margin-form">
					<input type="text" class="input_large" value="{$module_config.directory|escape:'htmlall':'UTF-8'}" name="setting[directory]" id="{$module_short_name|escape:'htmlall':'UTF-8'}directory" />
					<div id="jqueryFileTree_div"></div>
					<p {if $version_16}class="help-block"{/if}>
						{l s='This folder must be writable (not red).' mod='regeneratethumbnails'}
						<a class="module_help" href="{$documentation_url|escape:'htmlall':'UTF-8'}#directory">?</a>
					</p>
				</div>
			</div>

			<div class="form-group clear">
				<label for="{$module_short_name|escape:'htmlall':'UTF-8'}process">{l s='Simultaneous processes' mod='regeneratethumbnails'}</label>
				<div class="margin-form">
					<input class="input_tiny" type="number" min="1" step="1" name="setting[process]" id="{$module_short_name|escape:'htmlall':'UTF-8'}process" value="{$module_config.process|intval}"/>
					<a class="module_help" title="{l s='Click to see more informations about this element' mod='regeneratethumbnails'}" href="{$documentation_url|escape:'htmlall':'UTF-8'}#process">?</a>
					<p class="{if $version_16}help-block {/if}clear">{l s='Number simultaneous process used when regenerating' mod='regeneratethumbnails'}</p>
				</div>
			</div>

			<div class="form-group clear">
				<label for="{$module_short_name|escape:'htmlall':'UTF-8'}process">{l s='Simultaneous images' mod='regeneratethumbnails'}</label>
				<div class="margin-form">
					<table class="table">
						<tr class="first">
							<th>{l s='Images types' mod='regeneratethumbnails'}</th>
							<th>{l s='Number of images' mod='regeneratethumbnails'}</th>
						</tr>
						{foreach from=$images_types item=image_type}
							<tr>
								<td>
									{$image_type.name|escape:'htmlall':'UTF-8'}
									({$image_type.width|intval}×{$image_type.height|intval})
								</td>
								<td>
									{capture name=temp}images_{$image_type.id_image_type|intval}{/capture}
									<input class="input_tiny" type="number" min="1" step="1" name="setting[images_{$image_type.id_image_type|intval}]" value="{$module_config[$smarty.capture.temp]|intval}"/>
								</td>
						{/foreach}
					</table>
					<p class="{if $version_16}help-block {/if}clear">
						{l s='Number of images generated by process' mod='regeneratethumbnails'}
						<a class="module_help" title="{l s='Click to see more informations about this element' mod='regeneratethumbnails'}" href="{$documentation_url|escape:'htmlall':'UTF-8'}#images">?</a>
					</p>
				</div>
			</div>

			<div style="clear: both"></div>
			<p><input type="submit" class="samdha_button" name="saveSettings" value="{l s='Save' mod='regeneratethumbnails'}" /></p>
		</form>
	</div>
</div>
