{*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*}

{extends file="helpers/form/form.tpl"}

{block name=script}
    $(document).ready(function() {
        $('#latitude, #longitude').keyup(function() {
            $(this).val($(this).val().replace(/,/g, '.'));
        });
    $('#store_form_submit_btn').addClass('btn btn-default pull-right');
    });
{/block}

{block name="input"}
  {if $input.type == 'latitude'}
    <input type="text"
    {if isset($input.size)}size="{$input.size|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($input.maxlength)}maxlength="{$input.maxlength|escape:'htmlall':'UTF-8'}"{/if}
    name="latitude"
    id="latitude"
    value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}" />
    /
    <input type="text"
    {if isset($input.size)}size="{$input.size|escape:'htmlall':'UTF-8'}"{/if}
    {if isset($input.maxlength)}maxlength="{$input.maxlength|escape:'htmlall':'UTF-8'}"{/if}
    name="longitude"
    id="longitude"
    value="{$fields_value['longitude']|escape:'htmlall':'UTF-8'}" />
  {elseif $input.type == 'storeproducts'}
      {assign var=rawArray value=$input.values}
      <div id="productArrayTable" class="panel">
          <table cellspacing="0" cellpadding="0" class="table" style="min-width:66em; max-height:120px; overflow:scroll">
              <tr>
                <th> <input type="checkbox" name="checkme" id="checkme" class="noborder" onclick="checkDelBoxes(this.form, '{$input.name|escape:'htmlall':'UTF-8'}', this.checked)" />
                </th>
                <th>{l s='ID' mod='storelocator'}</th>
                <th>{l s='Name' mod='storelocator'}</th>
              </tr>
              {foreach $rawArray as $key => $allProds}
              {if $allProds['status'] == 1}
              <tr {if $key%2}class="alt_row"{/if}>
                <td> {assign var=id_checkbox value=$allProds['id_product']}
                  {*$fields_value|@print_r*}
                  <input type="checkbox" class="{$input.name|escape:'htmlall':'UTF-8'}" name="{$input.name|escape:'htmlall':'UTF-8'}" id="{$id_checkbox|escape:'htmlall':'UTF-8'}" value="{$id_checkbox|escape:'htmlall':'UTF-8'}" {if isset($fields_value[$id_checkbox])}checked="checked"{/if} /></td>
                <td><strong>{$allProds['id_product']|escape:'htmlall':'UTF-8'}</strong></td>
                <td><label for="{$id_checkbox|escape:'htmlall':'UTF-8'}" class="t"><strong>{$allProds['name']|escape:'none'}</strong></label></td>
              </tr>
              {/if}
              {/foreach}
          </table>
      </div>
      <style type="text/css">
        #productArrayTable { width:auto; max-height:360px; overflow:scroll}
      </style>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="other_input"}
  {if $key == 'hours'}
    {if $fields_value.ps_version > 0}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Hours:' mod='storelocator'}</label>
            <div class="col-lg-9"><p class="form-control-static">{l s='e.g. 10:00AM - 9:30PM' mod='storelocator'}</p></div>
        </div>
        {foreach $fields_value.days as $k => $value}
          <div class="form-group">
            <label class="control-label col-lg-3">{$value|escape:'htmlall':'UTF-8'}</label>
            {if $languages|count > 1}
              {foreach $languages as $language}
                <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                  <div class="col-lg-7">
                    <input type="text" size="25"
                    name="hours[{$k|escape:'htmlall':'UTF-8'}][{$language.id_lang|escape:'htmlall':'UTF-8'}]"
                    value="{if isset($fields_value.hours[$language.id_lang][$k-1])}{$fields_value.hours[$language.id_lang][$k-1]|escape:'htmlall':'UTF-8'}{/if}"/>
                  </div>
                  <div class="col-lg-2">
                    <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                      {$language.iso_code|escape:'htmlall':'UTF-8'}
                      <i class="icon-caret-down"></i>
                    </button>
                    <ul class="dropdown-menu">
                      {foreach from=$languages item=language}
                        <li><a href="javascript:hideOtherLanguage({$language.id_lang|escape:'htmlall':'UTF-8'});" tabindex="-1">{$language.name|escape:'htmlall':'UTF-8'}</a></li>
                      {/foreach}
                    </ul>
                  </div>
                </div>
              {/foreach}
            {else}
              <div class="col-lg-9">
                <input type="text" size="25" name="hours[{$k|escape:'htmlall':'UTF-8'}]" value="{if isset($fields_value.hours[$k-1])}{$fields_value.hours[$k-1]|escape:'htmlall':'UTF-8'}{/if}"/>
              </div>
            {/if}
          </div>
        {/foreach}
    {else}
        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Hours:' mod='storelocator'}</label>
          <div class="col-lg-9"><p class="form-control-static">{l s='e.g. 10:00AM - 9:30PM' mod='storelocator'}</p></div>
        </div>
        {foreach $fields_value.days as $k => $value}
        <div class="form-group">
          <label class="control-label col-lg-3">{$value|escape:'htmlall':'UTF-8'}</label>
          <div class="col-lg-9"><input type="text" size="25" name="hours_{$k|escape:'htmlall':'UTF-8'}" value="{if isset($fields_value.hours[$k-1])}{$fields_value.hours[$k-1]|escape:'htmlall':'UTF-8'}{/if}" /></div>
        </div>
        {/foreach}
    {/if}
    <div class="clear"></div>
  {/if}
{/block} 