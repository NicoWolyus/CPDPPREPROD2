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
 <div id="kerawen_wm_gift" class="card">
     <h3 class="card-header">
         {l s='Gift card' mod='kerawen'}
     </h3>
     <div class="card-block row">
         <div class="card-text">
             <div class="form-group row">
                 <label class="form-control-label">
                         {l s='Is gift card' mod='kerawen'}
                 </label>
                 <div class="col-sm">
                     <div class="input-group">    
                         <span class="ps-switch">
                             <input id="kerawen_wm_gift_card_off" class="ps-switch" name="kerawen_wm_gift_card" value="0" type="radio" {if !$wm.is_gift_card} checked="checked" {/if} />
                             <label for="kerawen_wm_gift_card_off">{l s='No' mod='kerawen'}</label>
                             <input id="kerawen_wm_gift_card_on" class="ps-switch" name="kerawen_wm_gift_card" value="1" type="radio" {if $wm.is_gift_card} checked="checked" {/if} />
                             <label for="kerawen_wm_gift_card_on">{l s='Yes' mod='kerawen'}</label><span class="slide-button">

                             </span>

                         </span>
                     </div>
                 </div>
                 <div class="row">
                     {l s='Please put VAT to 0' mod='kerawen'}
                 </div>
             </div>
         </div>
     </div>    
 </div>    


<div class="card">
    <input type="hidden" id="kerawen_wm" name="kerawen_wm" value="0"/>
    <h3 class="card-header">{l s='Weights and Measures' mod='kerawen'}</h3>
    <div class="card-block row">
        <div class="card-text">
            <div class="form-group row">
                <label class="form-control-label col-lg-2">
                    {l s='Weighed or measured' mod='kerawen'}
                </label>
                <div class="col-sm">
                    <div class="input-group">    
                        <span class="ps-switch">
                            <input id="kerawen_wm_measured_off" class="ps-switch" name="kerawen_wm_measured" value="0" type="radio" {if !$wm.measured} checked="checked" {/if} />
                            <label for="kerawen_wm_measured_off">{l s='No' mod='kerawen'}</label>
                            <input id="kerawen_wm_measured_on" class="ps-switch" name="kerawen_wm_measured" value="1" type="radio" {if $wm.measured} checked="checked" {/if} />
                            <label for="kerawen_wm_measured_on">{l s='Yes' mod='kerawen'}</label><span class="slide-button"></span>
                        </span>
                    </div>
                </div>
            </div>

            <div id="kerawen_wm_inputs">
                <div class="form-group row">
                    <label class="form-control-label col-lg-2">
                        {l s='Measurement unit' mod='kerawen'}
                    </label>
                    <div class="col-sm">
                        <input type="text" id="kerawen_wm_unit" name="kerawen_wm_unit" value="{$wm.unit|htmlentitiesUTF8}" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="form-control-label col-lg-2" for="kerawen_wm_precision">
                        {l s='Precision (number of decimals)' mod='kerawen'}
                    </label>
                    <div class="col-sm">
                        <input type="number" id="kerawen_wm_precision" name="kerawen_wm_precision" value="{$wm.precision|htmlentitiesUTF8}" class="form-control" />
                    </div>
                </div>
                <div class="col-md-12">
                    <table class="table">
                        <thead class="thead-default">
                            <tr>
                                <th>{l s='Scale code' mod='kerawen'}</th>
                                <th>{l s='Unit price (tax excl.)' mod='kerawen'}</th>
                                <th>{l s='Unit price (tax incl.)' mod='kerawen'}</th>
                                <th>{l s='Combination' mod='kerawen'}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="js-combinations-list panel-group accordion" id="kerawen_wm_code_list">
                            <tr>
                                <td>
                                    <input type="hidden" name="kerawen_wm_id"/>
                                    <input type="text" name="kerawen_wm_code" class="form-control"/>
                                </td>                                
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{$currency->prefix}{$currency->sign}{$currency->suffix}</span>
                                        </div>
                                        <input type="text" class="form-control text-sm-right" name="kerawen_wm_unit_price"/>
                                    </div>
                                </td>                                
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{$currency->prefix}{$currency->sign}{$currency->suffix}</span>
                                        </div>
                                        <input type="text" class="form-control text-sm-right" name="kerawen_wm_unit_price_ti"/>
                                    </div>
                                </td>                                
                                <td>
                                    <select name="kerawen_wm_combination" class="form-control">
                                        <option value="-1">{l s='None' mod='kerawen'}</option>
                                        {foreach $combinations as $comb}
                                            <option value="{$comb.id}">{$comb.name}</option>
                                        {/foreach}
                                    </select>
                                </td>                                
                                <td>
                                    <input type="button" class="btn btn-default delete" style="width:100%" value="{l s='Delete' mod='kerawen'}" />
                                </td>                                
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" id="kerawen_wm_addcode" class="btn btn-outline-primary sensitive add" style="width:100%">
                            <i class="material-icons">add_circle</i> {l s='Add' mod='kerawen'}
                    </button>
                </div>    
            </div>
        </div>
    </div>
</div>

<script>
	(function() {
		$("[name=kerawen_wm_measured]").on("change", function() {
			$("#kerawen_wm_inputs").toggle(!!+$("[name=kerawen_wm_measured]:checked").val())
		}).trigger("change");
                if (!(window.getTax instanceof Function))
		getTax = function (){
                        var selectedTax = $('#form_step2_id_tax_rules_group > option[selected]');
                        return selectedTax.data('rates');
                };
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

		var price_tab = $("div.form-contenttab#step2");
		
		var price_inputs = [];
		function register_prices(te, ti) {
			price_inputs.push({ te:te, ti:ti });
			if (!price_tab.hasClass("active")) link_prices(te, ti);
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
			if (price_tab.hasClass("active")) {
				price_tab.on("loaded", link_tax);
			}
			else {
				$.each(price_inputs, function(index, group) {
					link_prices(group.te, group.ti);
				});
				$("#form_step2_id_tax_rules_group").on("change", function() {
					$.each(price_inputs, function(index, group) {
						group.te.trigger("input");
					});
				}).trigger("change");
			}
		};
		link_tax();

		var code_tpl = $("#kerawen_wm_code_list>tr").detach();
		var code_count = 0;
		
		function new_code(value) {
			var index = "[" + code_count++ + "]";
			var group = code_tpl.clone();
			var getInput = function(name) {
				return group.find("[name="+name+"]").prop("name", name + index);
			};
			
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
			group.find("input[type=button].delete").click(function() { group.remove(); });
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
