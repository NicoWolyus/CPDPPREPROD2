{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
	{assign var='atm_base_uri' value=$urls.base_url}
{else}
	{assign var='atm_base_uri' value=$content_dir}
{/if}
<!-- MODULE PM_AdvancedTopMenu || Presta-Module.com -->
<!--[if lt IE 8]>
<script type="text/javascript" src="{$atm_base_uri|escape:'htmlall':'UTF-8'}modules/pm_advancedtopmenu/js/pm_advancedtopmenuiefix.js"></script>
<![endif]-->
<script type="text/javascript">
	{if isset($adtm_isToggleMode) && $adtm_isToggleMode}
	var adtm_isToggleMode = true;
	{else}
	var adtm_isToggleMode = false;
	{/if}
	var adtm_menuHamburgerSelector = "{$adtm_menuHamburgerSelector|escape:'htmlall':'UTF-8'}";
</script>
<!-- /MODULE PM_AdvancedTopMenu || Presta-Module.com -->
