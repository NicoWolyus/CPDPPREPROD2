{*
 * 2014 KerAwen
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
 *  @copyright 2014 KerAwen
 *  @license   http://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}
 
<style>
#kerawen_warnings .alert-info,
#kerawen_warnings .alert-success,
#kerawen_fixes .alert-info,
#kerawen_fixes .alert-success {
	display: none;
}
#kerawen_ignore {
	padding: 0 4em;
	text-align: center;
}
</style>

<form method="POST">
	{include file='./warning_list.tpl'}
	{include file='./fix_list.tpl'}
	
	<div id="kerawen_ignore">
		<input type="checkbox" name="warn_no_more"/>
		<label for="no_more_warn">
			{l s='Do not warn again' mod='kerawen'}
		</label>
		<br>
		<button class="btn btn-default" type="submit" name="warn_ignore" value="1">
			{l s='Ignore & Start' mod='kerawen'}
		</button>
	</div>
</form>
