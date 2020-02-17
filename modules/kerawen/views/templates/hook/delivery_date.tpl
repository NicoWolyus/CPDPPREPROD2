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
 
<!-- Module Kerawen -->
<div id="kerawen-delivery-date">
	<p class="carrier_title"></p>
	<div class="delivery_options">
		<div class="delivery_option item">
			<div>
				<table class="resume table table-bordered">
					<tbody>
						<tr>
							<td class="delivery_option_radio">
								<input type="radio" name="kerawen-delivery-date" value="0" checked="checked"/>
							</td>
							<td class="delivery_option_logo">{$strings.asap|escape}</td>
							<td id="kerawen-asap"></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="delivery_option item">
			<div>
				<table class="resume table table-bordered">
					<tbody>
						<tr>
							<td class="delivery_option_radio">
								<input type="radio" name="kerawen-delivery-date" value="1"/>
							</td>
							<td class="delivery_option_logo">{$strings.later|escape}</td>
							<td>
								<select id="kerawen-delivery-day"></select>
								<select id="kerawen-delivery-hour"></select>
								<select id="kerawen-delivery-min"></select>
							</td>
				</table>
			</div>
		</div>
	</div>
</div>
{addJsDef kerawenDeliveryUrl=$url_controller|addslashes}
{addJsDef kerawenDeliveryDate=$current|escape}
{addJsDef kerawenOpeningStep=$step|escape}
{addJsDef kerawenOpeningDelay=$delay|escape}
<script>
	kerawenOpeningHours={$opening|json_encode};
	// One page controller changes carrier & dates elements 
	if (window.kerawen) kerawen.deliveryOptionsUpdated();
</script>
<!-- /Module Kerawen -->
