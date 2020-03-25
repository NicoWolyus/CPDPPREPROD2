{if isset($error_message) && $error_message}
<div class="alert alert-danger">
    {$error_message|escape:'htmlall':'UTF-8'}
</div>
{/if}

<form {*target="_blank"*} id="bestkit_export_profile_form" class="defaultForm AdminbestkitEvents form-horizontal" action="{$bestkit_pfeatures_submit}" method="post" enctype="multipart/form-data" novalidate=""> {* |escape:'htmlall':'UTF-8' is not possibel using here *}
    <div class="panel" id="fieldset_0">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Configuration' mod='bestkit_productfeatures'}
        </div>

        <div class="form-group">
            <label for="main_feature" class="control-label col-lg-3 ">
                {l s='Main feature' mod='bestkit_productfeatures'}
            </label>

            <div class="col-lg-9 ">
                <select name="main_feature">
                    <option value="0">--</option>
                    {foreach $features as $feature}
                        <option value="{$feature.id_feature|intval}" {if ($main_feature == $feature.id_feature)} selected="selected"{/if}>{$feature.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">
                    {l s='This feature will be used for make relationship between products' mod='bestkit_productfeatures'}
                </p>
            </div>
        </div>

        <div class="form-group">
            <label for="new_feature" class="control-label col-lg-3 ">
                {l s='New feature' mod='bestkit_productfeatures'}
            </label>

            <div class="col-lg-9 ">
                <div class="row">
                    <div class="col-lg-5">
                        <select name="new_feature[id_feature]">
                            <option value="0">--</option>
                            {foreach $features as $feature}
                                <option value="{$feature.id_feature|intval}">{$feature.name|escape}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <input type="checkbox" name="new_feature[is_color]" value="1" /> {l s='Is color' mod='bestkit_productfeatures'}
                    </div>
                    {* <div class="col-lg-5">
                        <input type="text" name="new_feature[label]" value="" placeholder="{l s='Label' mod='bestkit_productfeatures'}" />
                    </div> *}
                </div>
                <p class="help-block">
                    {l s='This feature will be used for show available feature values from related products' mod='bestkit_productfeatures'}
                </p>
            </div>
        </div>

        {if count($pfeatures)}
        <div class="form-group">
            <div class="col-lg-3 "></div>
            <div class="col-lg-9 ">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-cogs"></i> {l s='Features list' mod='bestkit_productfeatures'}
                    </div>

                    <table id="pfeatures" class="table pfeatures">
                        <thead>
                            <th>
                                {l s='Id feature' mod='bestkit_productfeatures'}
                            </th>
                            <th>
                                {l s='Name' mod='bestkit_productfeatures'}
                            </th>
                            <th>
                                {l s='Is color' mod='bestkit_productfeatures'}
                            </th>
                            {* <th>
                                {l s='Label' mod='bestkit_productfeatures'}
                            </th> *}
                            <th>
                                {l s='Date add' mod='bestkit_productfeatures'}
                            </th>
                            <th>
                                {l s='Actions' mod='bestkit_productfeatures'}
                            </th>
                        </thead>
                        <tbody>
                            {foreach $pfeatures as $pfeature}
                            <tr>
                                <td>
                                    {$pfeature.id_feature|intval}
                                </td>
                                <td>
                                    {$pfeature.name|escape:'htmlall':'UTF-8'}
                                </td>
                                <td>
                                    {if $pfeature.is_color}
                                        {l s='Yes' mod='bestkit_productfeatures'}
                                    {else}
                                        {l s='No' mod='bestkit_productfeatures'}
                                    {/if}
                                </td>
                                {* <td>
                                    {$pfeature.label|escape:'htmlall':'UTF-8'}
                                </td> *}
                                <td>
                                    {$pfeature.date_add|escape:'htmlall':'UTF-8'}
                                </td>
                                <td>
                                    <button name="delete_pfeature" value="{$pfeature.id_feature|intval}" class="btn btn-primary">{l s='Delete' mod='bestkit_productfeatures'}</button>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {/if}

        {if count($color_feature_values)}
        <div class="form-group">
            <div class="col-lg-3 "></div>
            <div class="col-lg-9 ">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-cogs"></i> {l s='Colors' mod='bestkit_productfeatures'}
                    </div>

                    <div id="pfeatures_container">
                        {include file="$bestkit_pfeatures_admin_tpl./pfeatures.tpl"}
                        {include file="$bestkit_pfeatures_admin_tpl./list_footer.tpl"}
                    </div>

                </div>
            </div>
        </div>
		{else}
			<div class="alert alert-info">
				{l s='Features list is empty. Please choose the "Main feature" and at least one "New feature".' mod='bestkit_productfeatures'}
			</div>
        {/if}

        <div class="panel-footer">
            <button type="submit" value="1" id="configuration_form_submit_btn" name="submitUpdate" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='bestkit_productfeatures'}
            </button>
            <button type="submit" name="submitUpdateAndStay" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save and stay' mod='bestkit_productfeatures'}
            </button>
        </div>
    </div>
</form>

{literal}
<style>
    #pfeatures .pf_input {width: 100px;}
</style>
{/literal}
