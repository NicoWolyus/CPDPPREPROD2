{if count($menus)}
    <div id="menu-tab">
        <ul>
        {foreach from=$menus item=menu}
            <li unique-id="{$menu.id_menu|intval}" class="{if $menu.id_menu == $current_id_menu}ui-tabs-active{/if}">
                <span class="menu-dragHandler">
                    <img src="{$module_path|atm_nofilter}views/img/ellipsis_v.png" title="{l s='Move' mod='pm_advancedtopmenu'}" />
                </span>
                <a href="#menu-tab-{$menu.id_menu|intval}">
                    {module->getAdminOutputNameValue row=$menu withExtra=false}
                </a>
            </li>
        {/foreach}
        </ul>

    {foreach from=$menus item=menu}
        <div id="menu-tab-{$menu.id_menu|intval}">
            <p>
                <strong>{module->getAdminOutputNameValue row=$menu withExtra=true type='menu'}</strong> | {module->getAdminOutputPrivacyValue privacy=$menu.privacy}
                | <a href="{$base_config_url|atm_nofilter}&editMenu=1&id_menu={$menu.id_menu|intval}#wrapFormTab" title="{l s='Edit' mod='pm_advancedtopmenu'}"><img src="../img/admin/edit.gif" /></a>
                | <a href="{$base_config_url|atm_nofilter}&deleteMenu=1&id_menu={$menu.id_menu|intval}" onclick="return confirm('{{l s='Delete item #' mod='pm_advancedtopmenu'}|addcslashes:"'"}{$menu.id_menu|intval} ?');" title="{l s='Delete' mod='pm_advancedtopmenu'}"><img src="../img/admin/delete.gif" /></a>
                | {l s='Displayed:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeMenu=1&id_menu={$menu.id_menu|intval}" class="ajax_script_load" title="{if $menu.active}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $menu.active}enabled{else}disabled{/if}.gif" id="imgActiveMenu{$menu.id_menu|intval}" /></a>
                | {l s='Displayed on desktop:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeDesktopMenu=1&id_menu={$menu.id_menu|intval}" class="ajax_script_load" title="{if $menu.active_desktop}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $menu.active_desktop}enabled{else}disabled{/if}.gif" id="imgActiveDesktopMenu{$menu.id_menu|intval}" /></a>
                | {l s='Displayed on mobile:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeMobileMenu=1&id_menu={$menu.id_menu|intval}" class="ajax_script_load" title="{if $menu.active_mobile}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $menu.active_mobile}enabled{else}disabled{/if}.gif" id="imgActiveMobileMenu{$menu.id_menu|intval}" /></a>
            </p>
            <div class="columnWrapSort">
            {if count($menu.columnsWrap)}
                {foreach from=$menu.columnsWrap item=columnWrap}
                    <div class="menuColumnWrap" unique-id="{$columnWrap.id_wrap|intval}">
                        <p>
                            <span class="dragWrap">
                                <img src="{$module_path|atm_nofilter}views/img/arrow-move.png" title="{l s='Move' mod='pm_advancedtopmenu'}" />
                                <b>{$columnWrap.internal_name|escape:'htmlall':'UTF-8'}</b>
                            </span>
                            | {module->getAdminOutputPrivacyValue privacy=$columnWrap.privacy}
                            {if $columnWrap.width}
                            | {l s='Width:' mod='pm_advancedtopmenu'} {$columnWrap.width|intval}px
                            {/if}
                            | <a href="{$base_config_url|atm_nofilter}&editColumnWrap=1&id_wrap={$columnWrap.id_wrap|intval}&id_menu={$menu.id_menu|intval}#wrapFormTab" title="{l s='Edit' mod='pm_advancedtopmenu'}"><img src="../img/admin/edit.gif" /></a>
                            | <a href="{$base_config_url|atm_nofilter}&deleteColumnWrap=1&id_wrap={$columnWrap.id_wrap|intval}&id_menu={$menu.id_menu|intval}" onclick="return confirm('{{l s='Delete item #' mod='pm_advancedtopmenu'}|addcslashes:"'"}{$columnWrap.id_menu|intval} ?');" title="{l s='Delete' mod='pm_advancedtopmenu'}"><img src="../img/admin/delete.gif" /></a>
                            | {l s='Displayed:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeColumnWrap=1&id_wrap={$columnWrap.id_wrap|intval}" class="ajax_script_load" title="{if $columnWrap.active}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnWrap.active}enabled{else}disabled{/if}.gif" id="imgActiveColumnWrap{$columnWrap.id_wrap|intval}" /></a>
                            {l s='Displayed on desktop:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeDesktopColumnWrap=1&id_wrap={$columnWrap.id_wrap|intval}" class="ajax_script_load" title="{if $columnWrap.active_desktop}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnWrap.active_desktop}enabled{else}disabled{/if}.gif" id="imgActiveDesktopColumnWrap{$columnWrap.id_wrap|intval}" /></a>
                            | {l s='Displayed on mobile:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeMobileColumnWrap=1&id_wrap={$columnWrap.id_wrap|intval}" class="ajax_script_load" title="{if $columnWrap.active_mobile}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnWrap.active_mobile}enabled{else}disabled{/if}.gif" id="imgActiveMobileColumnWrap{$columnWrap.id_wrap|intval}" /></a>
                        </p>

                        <div class="columnSort columnSort-{$columnWrap.id_wrap|intval}">
                        {if count($columnWrap.columns)}
                            {foreach from=$columnWrap.columns item=column}
                                <div class="menuColumn" id="{$column.id_column|intval}">
                                    <span class="dragColumn">
                                        <img src="{$module_path|atm_nofilter}views/img/arrow-move.png" title="{l s='Move' mod='pm_advancedtopmenu'}" />
                                        {if $column.type != 8}
                                            <strong>{module->getAdminOutputNameValue row=$column withExtra=true type='column'}</strong>
                                        {elseif $column.type == 8 && isset($column.productObj) && Validate::isLoadedObject($column.productObj)}
                                            <strong>{l s='Product:' mod='pm_advancedtopmenu'} {$column.productObj->name|escape:'htmlall':'UTF-8'}</strong> <em>(ID: {$column.productObj->id|intval})</em>
                                        {/if}
                                    </span>
                                    | {module->getAdminOutputPrivacyValue privacy=$column.privacy}
                                 | <a href="{$base_config_url|atm_nofilter}&editColumn=1&id_column={$column.id_column|intval}&id_menu={$menu.id_menu|intval}#wrapFormTab" title="{l s='Edit' mod='pm_advancedtopmenu'}"><img src="../img/admin/edit.gif" /></a>
                                 | <a href="{$base_config_url|atm_nofilter}&deleteColumn=1&id_column={$column.id_column|intval}&id_menu={$menu.id_menu|intval}" onclick="return confirm('{{l s='Delete item #' mod='pm_advancedtopmenu'}|addcslashes:"'"}{$column.id_column|intval} ?');" title="{l s='Delete' mod='pm_advancedtopmenu'}"><img src="../img/admin/delete.gif" /></a>
                                 | {l s='Displayed:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeColumn=1&id_column={$column.id_column|intval}" class="ajax_script_load" title="{if $column.active}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $column.active}enabled{else}disabled{/if}.gif" id="imgActiveColumn{$column.id_column|intval}" /></a>
                                 | {l s='Displayed on desktop:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeDesktopColumn=1&id_column={$column.id_column|intval}" class="ajax_script_load" title="{if $column.active_desktop}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $column.active_desktop}enabled{else}disabled{/if}.gif" id="imgActiveDesktopColumn{$column.id_column|intval}" /></a>
                                 | {l s='Displayed on mobile:' mod='pm_advancedtopmenu'} <a href="{$base_config_url|atm_nofilter}&activeMobileColumn=1&id_column={$column.id_column|intval}" class="ajax_script_load" title="{if $column.active_mobile}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $column.active_mobile}enabled{else}disabled{/if}.gif" id="imgActiveMobileColumn{$column.id_column|intval}" /></a>
                                {if is_array($menu.columnsWrap) && count($menu.columnsWrap) > 1}
                                     | 
                                     <form style="display:inline;" action="{$base_config_url|atm_nofilter}" method="post">
                                        <select name="id_wrap"><option>{l s='Move to Column' mod='pm_advancedtopmenu'}</option>
                                         {foreach from=$menu.columnsWrap item=columnWrap2}
                                            {if $column.id_wrap != $columnWrap2.id_wrap}
                                                <option value="{$columnWrap2.id_wrap|intval}">{$columnWrap2.internal_name|escape:'htmlall':'UTF-8'}</option>
                                            {/if}
                                         {/foreach}
                                        </select>
                                        <input type="hidden" name="id_column" value="{$column.id_column|intval}" />
                                        <input type="hidden" name="id_menu" value="{$menu.id_menu|intval}" />
                                        <input type="submit" value="{l s='OK' mod='pm_advancedtopmenu'}" name="submitFastChangeColumn" class="button" />
                                    </form>
                                {/if}
                                {if $column.type != 8}
                                    <br /><br />
                                    <table cellspacing="0" cellpadding="0" class="table table_sort" style="width:100%">
                                        <tbody>
                                            <tr class="nodrag nodrop">
                                                <th width="50">{l s='ID' mod='pm_advancedtopmenu'}</th>
                                                <th width="100">{l s='Type' mod='pm_advancedtopmenu'}</th>
                                                <th width="150">{l s='Permit' mod='pm_advancedtopmenu'}</th>
                                                <th>{l s='Value' mod='pm_advancedtopmenu'}</th>
                                                <th width="50">{l s='Actions' mod='pm_advancedtopmenu'}</th>
                                                <th width="50">{l s='Displayed' mod='pm_advancedtopmenu'}</th>
                                                <th width="80">{l s='Displayed on desktop' mod='pm_advancedtopmenu'}</th>
                                                <th width="80">{l s='Displayed on mobile' mod='pm_advancedtopmenu'}</th>
                                            </tr>
                                        {foreach from=$column.columnElements item=columnElement}
                                            <tr id="{$columnElement.id_element|intval}">
                                                <td>{$columnElement.id_element|intval}</td>
                                                <td>{module->getType type=$columnElement.type}</td>
                                                <td>{module->getAdminOutputPrivacyValue privacy=$columnElement.privacy}</td>
                                                <td>{module->getAdminOutputNameValue row=$columnElement withExtra=true type='element'}</td>
                                                <td align="center"> <a href="{$base_config_url|atm_nofilter}&editElement=1&id_element={$columnElement.id_element|intval}&id_menu={$menu.id_menu|intval}#wrapFormTab" title="{l s='Edit' mod='pm_advancedtopmenu'}"><img src="../img/admin/edit.gif" /></a> <a href="{$base_config_url|atm_nofilter}&deleteElement=1&id_element={$columnElement.id_element|intval}&id_menu={$menu.id_menu|intval}" onclick="return confirm('{{l s='Delete item #' mod='pm_advancedtopmenu'}|addcslashes:"'"}{$columnElement.id_element|intval} ?');" title="{l s='Delete' mod='pm_advancedtopmenu'}"><img src="../img/admin/delete.gif" /></a></td>
                                                <td align="center"> <a href="{$base_config_url|atm_nofilter}&activeElement=1&id_element={$columnElement.id_element|intval}" class="ajax_script_load" title="{if $columnElement.active}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnElement.active}enabled{else}disabled{/if}.gif" id="imgActiveElement{$columnElement.id_element|intval}" /></a></td>
                                                <td align="center"> <a href="{$base_config_url|atm_nofilter}&activeDesktopElement=1&id_element={$columnElement.id_element|intval}" class="ajax_script_load" title="'{if $columnElement.active_desktop}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnElement.active_desktop}enabled{else}disabled{/if}.gif" id="imgActiveDesktopElement{$columnElement.id_element|intval}" /></a></td>
                                                <td align="center"> <a href="{$base_config_url|atm_nofilter}&activeMobileElement=1&id_element={$columnElement.id_element|intval}" class="ajax_script_load" title="{if $columnElement.active_mobile}{l s='Yes' mod='pm_advancedtopmenu'}{else}{l s='No' mod='pm_advancedtopmenu'}{/if}"><img src="../img/admin/{if $columnElement.active_mobile}enabled{else}disabled{/if}.gif" id="imgActiveMobileElement{$columnElement.id_element|intval}" /></a></td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                {/if}
                                </div><!-- {$column.id_column|intval} -->
                            {/foreach}
                        {else}
                            <p style="text-align:center;">{l s='No group' mod='pm_advancedtopmenu'}</p>
                        {/if}
                            <br class="clear" />
                        </div><!-- .columnSort-{$columnWrap.id_wrap|intval} -->
                        <script stype="text/javascript">
                            $(".columnSort-{$columnWrap.id_wrap|intval}").sortable({
                                placeholder: "ui-state-highlight",
                                delay: 300,
                                handle : ".dragColumn",
                                update: function(event, ui) {
                                    var orderColumn = $(this).sortable("toArray");
                                    saveOrderColumn(orderColumn.join(","));
                                }
                            });
                        </script>
                    </div><!-- #{$columnWrap.id_wrap|intval} -->
                {/foreach}
            {else}
                <p style="text-align:center;">{l s='No column' mod='pm_advancedtopmenu'}</p>
            {/if}
            </div>
        </div><!-- #cont_{$menu.id_menu|intval} -->
    {/foreach}
    </div><!-- #tabsetMenu -->
    <br /><br />
{else}
    <p style="text-align:center;">{l s='No tab' mod='pm_advancedtopmenu'}</p>
{/if}
<div id="wrapFormTab">
    <ul style="height: 30px;" id="formTab">
    {if $displayTabElement}
        <li{if $editMenu} class="ui-tabs-selected ui-tabs-active"{/if}>
            <a href="#editMenuFormContainer">
                <span><img src="{$module_path|atm_nofilter}views/img/menu.png" />
                {if $editMenu}{l s='Edit tab' mod='pm_advancedtopmenu'}{else}{l s='Add tab' mod='pm_advancedtopmenu'}{/if}</span>
            </a>
        </li>
    {/if}
    {if isset($menus) && is_array($menus) && count($menus)}
        {if $displayColumnElement}
            <li{if $editColumn} class="ui-tabs-selected ui-tabs-active"{/if}>
                <a href="#editColumnWrapContainer">
                    <span><img src="{$module_path|atm_nofilter}views/img/column.png" />
                    {if $editColumn}{l s='Edit column' mod='pm_advancedtopmenu'}{else}{l s='Add column' mod='pm_advancedtopmenu'}{/if}</span>
                </a>
            </li>
        {/if}
        {if $displayGroupElement}
            <li{if $editGroup} class="ui-tabs-selected ui-tabs-active"{/if}>
                <a href="#editColumnContainer">
                    <span><img src="{$module_path|atm_nofilter}views/img/group.png" />
                    {if $editGroup}{l s='Edit item group' mod='pm_advancedtopmenu'}{else}{l s='Add item group' mod='pm_advancedtopmenu'}{/if}</span>
                </a>
            </li>
        {/if}
        {if $displayItemElement}
            <li{if $editElement} class="ui-tabs-selected ui-tabs-active"{/if}>
                <a href="#editElementContainer">
                    <span><img src="{$module_path|atm_nofilter}views/img/item.png" />
                    {if $editElement}{l s='Edit item' mod='pm_advancedtopmenu'}{else}{l s='Add item' mod='pm_advancedtopmenu'}{/if}</span>
                </a>
            </li>
        {/if}
    {/if}
    </ul>
{if $displayTabElement}
    <div id="editMenuFormContainer">
    {module->outputMenuForm obj=$ObjAdvancedTopMenuClass}
    </div>
{/if}
{if isset($menus) && is_array($menus) && count($menus)}
    {if $displayColumnElement}
        <div id="editColumnWrapContainer">
        {module->outputColumnWrapForm menus=$menus obj=$ObjAdvancedTopMenuColumnWrapClass}
        </div>
    {/if}
    {if $displayGroupElement}
        <div id="editColumnContainer">
        {module->outputColumnForm menus=$menus cms=$cms manufacturer=$manufacturer supplier=$supplier obj_column=$ObjAdvancedTopMenuColumnClass obj_product=$ObjAdvancedTopMenuProductColumnClass}
        </div>
    {/if}
    {if $displayItemElement}
        <div id="editElementContainer">
        {module->outputElementForm menus=$menus cms=$cms manufacturer=$manufacturer supplier=$supplier obj=$ObjAdvancedTopMenuElementsClass}
        </div>
    {/if}
{/if}
</div>