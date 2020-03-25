{*
* Modulo Product Combinations
*
* @author    Giuseppe Tripiciano <admin@areaunix.org>
* @copyright Copyright (c) 2018 Giuseppe Tripiciano
* @license   You cannot redistribute or resell this code.
*
*}

<input type="hidden" name="submitted_tabs[]" value="productcombinations" />
<h4>{l s='Product Combinations' mod='productascombinations'}</h4>
<div class="col-md-12">
    <div class="row">
        <label for="productcomb">{l s='Choose products:' mod='productascombinations'}</label>
    </div>
    <div class="row productcomb">
        <select id="pc_combs" name="pc_combs[]" multiple="multiple">
            {foreach $products as $product}
                <option value="{$product.id_product}">{$product.name}</option>
            {/foreach}
        </select>
    </div>
    <div class="row">
        <label for="pc_image"><input name="pc_image" type="checkbox" value="1" {if $pc_image}checked="checked"{/if} /> {l s='If unticked the default cover image for products is used. If ticked the last product image is used.' mod='productascombinations'}</label>
    </div>
</div>
{literal}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#pc_combs').select2({
                placeholder: 'Select products:',
                width: '100%'
            });
{/literal}
        {if $pc_combs}
            $('#pc_combs').val([{$pc_combs}]);
            $('#pc_combs').trigger('change');
        {/if}
{literal}
        });
    </script>
{/literal}