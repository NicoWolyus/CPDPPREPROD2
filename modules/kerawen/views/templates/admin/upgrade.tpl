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
#kerawen_upgrade {
	padding: 0 4em;
	text-align: center;
}
</style>

<form id="kerawen_upgrade" action="{$controller}" method="post">
	<p>
		{l s='A new version of the KerAwen module has been downloaded.' mod='kerawen'}<br>
		{l s='Click on the button below to complete the upgrade.' mod='kerawen'}
	</p>
	<button class="btn btn-default" type="submit" name="upgrade" value="1">
		{l s='Continue' mod='kerawen'}
	</button>
</form>
