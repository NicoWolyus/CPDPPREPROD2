<img src="{$module_path|atm_nofilter}views/img/privacy-{$privacy}.png" title="{l s='Privacy' mod='pm_advancedtopmenu'}" />
{if !$privacy}
 {l s='For all' mod='pm_advancedtopmenu'}
{elseif $privacy == 1}
 {l s='Only for visitors' mod='pm_advancedtopmenu'}
{elseif $privacy == 2}
 {l s='Only for registered users' mod='pm_advancedtopmenu'}
{elseif $privacy == 3}
 {l s='Only for groups of customers' mod='pm_advancedtopmenu'}
{/if}