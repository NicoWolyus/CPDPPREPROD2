{*
 * 2014 KerAwen
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
 
 {if isset($delivery_time)}
	 <input type="hidden" id="delivery_day_base" value="{$delivery_day_base|escape}">
	<!-- Module Kerawen -->
	<p class="delivery_time_title">
		{$strings.chooseDeliveryTime|escape}
	</p>
	<table class="resume table table-bordered">
		<tr>
			<td class="delivery_time_radio">
				<input id="delivery_time_0" class="delivery_time_radio" type="radio" name="delivery_time" value="{$delivery_time_sooner.time|escape}" checked="checked" />
			</td>
			<td class="delivery_time_logo">
				{$strings.asap|escape}
			</td>
			<td id="delivery_time_sooner_txt">
				{$delivery_time_sooner.txt|escape}
			</td>
		</tr>
		<tr>
			<td class="delivery_time_radio">
				<input id="delivery_time_1" class="delivery_time_radio" type="radio" name="delivery_time" value="{$delivery_time.1.time|escape}" />
			</td>
			<td class="delivery_time_logo">
				{$strings.later|escape}
			</td>
			<td>
				<select id="delivery_day_select">
					{foreach from=$delivery_day item=day}
						<option value="{$day.time|escape}">{$day.txt|escape}</option>
					{/foreach}
				</select>
				<select id="delivery_hour_select">
					{foreach from=$delivery_hour item=hour}
						<option value="{$hour.time|escape}">{$hour.txt|escape}</option>
					{/foreach}
				</select>
				h 
				<select id="delivery_minute_select">
					{foreach from=$delivery_minute item=minute}
						<option value="{$minute.time|escape}">{$minute.txt|escape}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
	<!-- /Module Kerawen -->
{/if}