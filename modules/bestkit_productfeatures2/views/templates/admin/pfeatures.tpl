<div class="form-group">
    <label for="main_feature" class="control-label col-lg-3 ">
        {l s='Search' mod='bestkit_productfeatures'}
    </label>
    <div class="col-lg-9 ">
        <input name="search" id="bestkit_productfeatures_search" class="bestkit_productfeatures_search form-control" value="{$bestkit_productfeatures_search}" /> {*|escape:'htmlall':'UTF-8' - is not possible using there*}
    </div>
</div>

<table id="pfeatures" class="table pfeatures">
    <thead>
    <th>
        {l s='Id feature' mod='bestkit_productfeatures'}
    </th>
    <th>
        {l s='Feature name' mod='bestkit_productfeatures'}
    </th>
    <th>
        {l s='Id feature value' mod='bestkit_productfeatures'}
    </th>
    <th>
        {l s='Value name' mod='bestkit_productfeatures'}
    </th>
    <th>
        {l s='Color' mod='bestkit_productfeatures'}
    </th>
    </thead>
    <tbody>
    {foreach $color_feature_values as $color_feature_value}
        <tr>
            <td>
                {$color_feature_value.id_feature|escape:'htmlall':'UTF-8'}
            </td>
            <td>
                {$color_feature_value.feature_name|escape:'htmlall':'UTF-8'}
            </td>
            <td>
                {$color_feature_value.id_feature_value|intval}
            </td>
            <td>
                {$color_feature_value.value|escape:'htmlall':'UTF-8'}
            </td>
            <td>
                <div class="row">
                    <div class="col-lg-6">
                        <input type="text" name="color[{$color_feature_value.id_feature_value|intval}]" class="pf_input" value="{$color_feature_value.hex_value|trim}" />
                    </div>
                    <div class="col-lg-6">
                        <input type="color"
                               data-hex="true"
                               class="color mColorPickerInput"
                               name="demo_color[{$color_feature_value.id_feature_value|intval}]"
                               value="{if $color_feature_value.hex_value}{$color_feature_value.hex_value|trim|escape:'htmlall':'UTF-8'}{/if}" />
                    </div>
                </div>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>