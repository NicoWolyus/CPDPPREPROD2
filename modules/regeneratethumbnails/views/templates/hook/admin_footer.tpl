{*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to http://doc.prestashop.com/display/PS15/Overriding+default+behaviors
* #Overridingdefaultbehaviors-Overridingamodule%27sbehavior for more information.
*
* @author Samdha <contact@samdha.net>
* @copyright  Samdha
* @license    commercial license see license.txt
*}
<script type="text/javascript">
	module.process_count = {$module_config.process|intval};
	var messages = {ldelim}
		image: '{capture name=temp}{l s='Image' mod='regeneratethumbnails' js=1}{/capture}{$smarty.capture.temp|replace:'\\\'':'\''|escape:'javascript':'UTF-8'}',
		of: '{capture name=temp}{l s='of' mod='regeneratethumbnails' js=1}{/capture}{$smarty.capture.temp|replace:'\\\'':'\''|escape:'javascript':'UTF-8'}',
		done: '{capture name=temp}{l s='done' mod='regeneratethumbnails' js=1}{/capture}{$smarty.capture.temp|replace:'\\\'':'\''|escape:'javascript':'UTF-8'}',
		confirmation: '{capture name=temp}{l s='Are you sure?' mod='regeneratethumbnails' js=1}{/capture}{$smarty.capture.temp|replace:'\\\'':'\''|escape:'javascript':'UTF-8'}',
	{rdelim};
</script>
<link rel="stylesheet" type="text/css" href="{$module_path|escape:'htmlall':'UTF-8'}views/css/admin.css?v={$module_version|escape:'htmlall':'UTF-8'}">
<link rel="stylesheet" type="text/css" href="{$module_path|escape:'htmlall':'UTF-8'}views/css/jqueryFileTree.css?v={$module_version|escape:'htmlall':'UTF-8'}">
<script src="{$module_path|escape:'htmlall':'UTF-8'}views/js/jquery.ajaxQueue.js?v={$module_version|escape:'htmlall':'UTF-8'}" type="text/javascript"></script>
<script src="{$module_path|escape:'htmlall':'UTF-8'}views/js/jqueryFileTree.js?v={$module_version|escape:'htmlall':'UTF-8'}" type="text/javascript"></script>

