{*
* 2015 KerAwen
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
 *  @copyright 2015 KerAwen
 *  @license   http://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}

<div id="kerawen_wm_gift" class="panel product-tab">
	<h3>{l s='Gift card' mod='kerawen'}</h3>
	{* include file="controllers/products/multishop/check_fields.tpl" product_tab="KerAwen" *}
    
	<div class="form-group">
		<div class="col-lg-1">
			<span class="pull-right">
				{* include file="controllers/products/multishop/checkbox.tpl"
					field="kerawen_wm_measured" type="radio" *}
			</span>
		</div>
		<label class="control-label col-lg-2">
			<span>
				{l s='Is gift card' mod='kerawen'}
			</span>
		</label>
		<div class="col-lg-3">
          <div class="row">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="kerawen_wm_gift_card" id="kerawen_wm_gift_card_on"
                        value="1" {if $wm.is_gift_card} checked="checked" {/if} />
                    <label for="kerawen_wm_gift_card_on" class="radioCheck">{l s='Yes' mod='kerawen'}</label>
                    <input type="radio" name="kerawen_wm_gift_card" id="kerawen_wm_gift_card_off"
                        value="0" {if !$wm.is_gift_card} checked="checked" {/if} />
                    <label for="kerawen_wm_gift_card_off" class="radioCheck">{l s='No' mod='kerawen'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
            <div class="row">
            	{l s='Please put VAT to 0' mod='kerawen'}
			</div>
        </div>
	</div>

	<div class="panel-footer">
		<a href="{$link->getAdminLink('KerawenProduct')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='kerawen'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" {$submit_config}><i class="{$submit_icon}"></i> {l s='Save' mod='kerawen'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" {$submit_config}><i class="{$submit_icon}"></i> {l s='Save and stay' mod='kerawen'}</button>
	</div>    
</div>    


<div class="panel product-tab">
	<input type="hidden" id="kerawen_wm" name="kerawen_wm" value="0"/>
	<h3>{l s='Weights and Measures' mod='kerawen'}</h3>
	{* include file="controllers/products/multishop/check_fields.tpl" product_tab="KerAwen" *}
	
	<div class="form-group">
		<div class="col-lg-1">
			<span class="pull-right">
				{* include file="controllers/products/multishop/checkbox.tpl"
					field="kerawen_wm_measured" type="radio" *}
			</span>
		</div>
		<label class="control-label col-lg-2">
			<span>
				{l s='Weighed or measured' mod='kerawen'}
			</span>
		</label>
		<div class="col-lg-3">
			<span class="switch prestashop-switch fixed-width-lg">
				<input type="radio" name="kerawen_wm_measured" id="kerawen_wm_measured_on"
					value="1" {if $wm.measured} checked="checked" {/if} />
				<label for="kerawen_wm_measured_on" class="radioCheck">{l s='Yes' mod='kerawen'}</label>
				<input type="radio" name="kerawen_wm_measured" id="kerawen_wm_measured_off"
					value="0" {if !$wm.measured} checked="checked" {/if} />
				<label for="kerawen_wm_measured_off" class="radioCheck">{l s='No' mod='kerawen'}</label>
				<a class="slide-button btn"></a>
			</span>
		</div>
	</div>

	<div id="kerawen_wm_inputs">
		<div class="form-group">
			<div class="col-lg-1">
				<span class="pull-right">
					{* include file="controllers/products/multishop/checkbox.tpl"
						field="kerawen_wm_unit" type="radio" *}
				</span>
			</div>
			<label class="col-lg-2 control-label" for="kerawen_wm_unit">
				<span>
					{* $bullet_common_field *}
					{l s='Measurement unit' mod='kerawen'}
				</span>
			</label>
			<div class="col-lg-2">
				<input type="text" id="kerawen_wm_unit" name="kerawen_wm_unit"
					value="{$wm.unit|htmlentitiesUTF8}" />
			</div>
		</div>
	
		<div class="form-group">
			<div class="col-lg-1">
				<span class="pull-right">
					{* include file="controllers/products/multishop/checkbox.tpl"
						field="kerawen_wm_precision" type="radio" *}
				</span>
			</div>
			<label class="col-lg-2 control-label" for="kerawen_wm_precision">
				<span>
					{* $bullet_common_field *}
					{l s='Precision (number of decimals)' mod='kerawen'}
				</span>
			</label>
			<div class="col-lg-2">
				<input type="text" id="kerawen_wm_precision" name="kerawen_wm_precision"
					value="{$wm.precision|htmlentitiesUTF8}" />
			</div>
		</div>
		
		<div class="panel col-lg12">
			<div id="kerawen_wm_code_header" class="form-group">
				<div class="col-lg-2 control-label" style="text-align:center">
					{l s='Scale code' mod='kerawen'}
				</div>
				<div class="col-lg-2 control-label" style="text-align:center">
					{l s='Unit price (tax excl.)' mod='kerawen'}
				</div>
				<div class="col-lg-2 control-label" style="text-align:center">
					{l s='Unit price (tax incl.)' mod='kerawen'}
				</div>
				<div class="col-lg-2 control-label" style="text-align:center">
					{l s='Combination' mod='kerawen'}
				</div>
				<div class="col-lg-2"></div>
			</div>
			<div id="kerawen_wm_code_list">
				<div class="form-group">
					<input type="hidden" name="kerawen_wm_id"/>
					<div class="col-lg-2">
						<input type="text" name="kerawen_wm_code"/>
					</div>
					<div class="col-lg-2">
						<div class="input-group">
							<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
							<input type="text" name="kerawen_wm_unit_price"/>
						</div>
					</div>
					<div class="col-lg-2">
						<div class="input-group">
							<span class="input-group-addon">{$currency->prefix}{$currency->suffix}</span>
							<input type="text" name="kerawen_wm_unit_price_ti"/>
						</div>
					</div>
					<div class="col-lg-2">
						<select name="kerawen_wm_combination">
							<option value="-1">{l s='None' mod='kerawen'}</option>
							{foreach $combinations as $comb}
								<option value="{$comb.id}">{$comb.name}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-lg-2">
						<input type="button" class="btn btn-default" style="width:100%"
							value="{l s='Delete' mod='kerawen'}">
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-2">
					<input type="button" id="kerawen_wm_addcode"
						class="btn btn-default" style="width:100%"
						value="{l s='Add' mod='kerawen'}">
				</div>
				<div class="col-lg-2"></div>
				<div class="col-lg-2"></div>
				<div class="col-lg-2"></div>
				<div class="col-lg-2"></div>
			</div>
		</div>
	</div>

	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='kerawen'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" {$submit_config}><i class="{$submit_icon}"></i> {l s='Save' mod='kerawen'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" {$submit_config}><i class="{$submit_icon}"></i> {l s='Save and stay' mod='kerawen'}</button>
	</div>
</div>

<script>
	(function() {
		$("[name=kerawen_wm_measured]").on("change", function() {
			$("#kerawen_wm_inputs").toggle(!!+$("[name=kerawen_wm_measured]:checked").val())
		}).trigger("change");
		
		// Prices retro-compatibility
		if (!(window.addTaxes instanceof Function))
			addTaxes = function(te) {
				return te*(1 + getTax()/100);
			};
		if (!(window.removeTaxes instanceof Function))
			removeTaxes = function(ti) {
				return ti/(1 + getTax()/100);
			};
		priceDisplayPrecision = window.priceDisplayPrecision || 2;
		
		function compute_price(v, t, p) {
			return isNaN(v=+v) ? null : t(v).toFixed(p);
		};

		var price_tab = $("#product-tab-content-Prices");
		
		var price_inputs = [];
		function register_prices(te, ti) {
			price_inputs.push({ te:te, ti:ti });
			if (!price_tab.hasClass("not-loaded")) link_prices(te, ti);
		}
		
		function link_prices(te, ti) {
			te.on("input", function() {
				ti.val(compute_price(te.val(), addTaxes, 6));
			});
			ti.on("input", function() {
				te.val(compute_price(ti.val(), removeTaxes, 6));
			});
		};
		
		function link_tax() {
			if (price_tab.hasClass("not-loaded")) {
				price_tab.on("loaded", link_tax);
			}
			else {
				$.each(price_inputs, function(index, group) {
					link_prices(group.te, group.ti);
				});
				$("#id_tax_rules_group").on("change", function() {
					$.each(price_inputs, function(index, group) {
						group.te.trigger("input");
					});
				}).trigger("change");
			}
		};
		link_tax();

		var code_tpl = $("#kerawen_wm_code_list>div").detach();
		var code_count = 0;
		
		function new_code(value) {
			var index = "[" + code_count++ + "]";
			var group = code_tpl.clone();
			var getInput = function(name) {
				return group.find("[name="+name+"]").prop("name", name + index);
			}
			
			var id = getInput("kerawen_wm_id");
			var code = getInput("kerawen_wm_code");
			var unit_te = getInput("kerawen_wm_unit_price");
			var unit_ti = getInput("kerawen_wm_unit_price_ti");
			var comb = getInput("kerawen_wm_combination");
			
			register_prices(unit_te, unit_ti);
			if (value) {
				id.val(value.id);
				code.val(value.code);
				unit_te.val(value.unit);
				unit_te.trigger("input");
				comb.val(value.id_product_attribute || 0);
			}
			group.find("input[type=button]").click(function() { group.remove(); });
			$("#kerawen_wm_code_list").append(group);
			if (!value) code.focus();
		};
		$("#kerawen_wm_addcode").click(function() { new_code(); });

		{foreach from=$wm.codes item=code}
			new_code({
				id: {$code.id_code},
				code: "{$code.code}",
				unit: {$code.unit_price},
				{if isset($code.id_product_attribute)}
					id_product_attribute: {$code.id_product_attribute},
				{/if}
			});
		{/foreach}
		$("#kerawen_wm").val(1);
	})();
</script>
