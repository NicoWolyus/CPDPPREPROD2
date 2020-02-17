{if isset($columns) && is_array($columns) && count($columns) >= 1}
	<select name="id_column">
		<option>-- {l s='Choose' mod='pm_advancedtopmenu'} --</option>
		{foreach from=$columns item=column name=loop}
			<option value="{$column.id_column|intval}" {if $column_selected eq $column.id_column}selected=selected{/if}>{$column.admin_name|escape:'htmlall':'UTF-8'}</option>
		{foreachelse}
			<option value="">{l s='No column' mod='pm_advancedtopmenu'}</option>
		{/foreach}
	</select>
	<script>$('input[name="submitElement"]').removeAttr('disabled').prop('disabled', false);</script>
{else}
	<div class="error inline-alert"><strong><u>{l s='Please select another parent tab!' mod='pm_advancedtopmenu'}</u></strong></div>
	<script>$('input[name="submitElement"]').attr('disabled', 'disabled').prop('disabled', true);</script>
{/if}