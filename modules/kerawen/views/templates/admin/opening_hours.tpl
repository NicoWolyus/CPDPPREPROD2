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

<form id="module_form" class="defaultForm  form-horizontal" action="index.php?controller=AdminModules&configure=kerawen&tab_module=others&module_name=kerawen&amp;token=c25d69863df1ca33443f2d036ae239fb" method="post" enctype="multipart/form-data"  novalidate>
	<input type="hidden" name="submitModule" value="1" />
	<div class="panel" id="fieldset_0">
		<div class="panel-heading">
			<i class="icon-calendar"></i>
			{$strings.title|escape}
		</div>
		{foreach $days as $day}
			<div class="form-group">
				<label class="control-label col-lg-3">{$day.label|escape}</label>
				<div class="col-lg-1">
				</div>
				<div class="col-lg-4 inline">
				
					<span class="switch prestashop-switch fixed-width-md">
						<input type="radio" name="MANUFACTURER_DISPLAY_TEXT" id="MANUFACTURER_DISPLAY_TEXT_on" value="1" checked="checked" />
						<label for="MANUFACTURER_DISPLAY_TEXT_on">Ouvert</label>
						<input type="radio" name="MANUFACTURER_DISPLAY_TEXT" id="MANUFACTURER_DISPLAY_TEXT_off" value="0" />
						<label for="MANUFACTURER_DISPLAY_TEXT_off">Fermé</label>
						<a class="slide-button btn"></a>
					</span>
					<label class="control-label">&nbsp;de&nbsp;</label>
					<input type="text" size="2" value="08" />
					<input type="text" size="2" value="00" />
					<label class="control-label">&nbsp;à&nbsp;</label>
					<input type="text" size="2" value="12" />
					<input type="text" size="2" value="30" />
				</div>
				<div class="col-lg-4 inline">
					<span class="switch prestashop-switch fixed-width-md">
						<input type="radio" name="MANUFACTURER_DISPLAY_TEXT" id="MANUFACTURER_DISPLAY_TEXT_on" value="1" checked="checked" />
						<label for="MANUFACTURER_DISPLAY_TEXT_on">Ouvert</label>
						<input type="radio" name="MANUFACTURER_DISPLAY_TEXT" id="MANUFACTURER_DISPLAY_TEXT_off" value="0" />
						<label for="MANUFACTURER_DISPLAY_TEXT_off">Fermé</label>
						<a class="slide-button btn"></a>
					</span>
					<label class="control-label">&nbsp;de&nbsp;</label>
					<input type="text" size="2" value="14" />
					<input type="text" size="2" value="00" />
					<label class="control-label">&nbsp;à&nbsp;</label>
					<input type="text" size="2" value="18" />
					<input type="text" size="2" value="30" />
				</div>
			</div>
		{/foreach}
		<div class="panel-footer">
			<button
				type="submit"
				value="1"
				id="module_form_submit_btn"
				name="submitModule"
				class="btn btn-default pull-right">
				<i class="process-icon-save"></i>
				{$strings.save|escape}
			</button>
		</div>
	</div>
</form>
