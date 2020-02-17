{*
 * 2016 KerAwen
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
	#kerawen_fixes .title {
		font-size: 1.2em;
		font-weight: bold;
	}
	#kerawen_fixes .actions {
		float: right;
	}
</style>
	
<div id="kerawen_fixes">
	{foreach $fixes as $fix}
		<div class="alert alert-{if !$fix.compliant}info{elseif $fix.installed}success{else}warning{/if}">
			<div class="actions">
				{if $fix.installed == 1}
					<button class="btn btn-default" type="submit" name="uninstallfix" value="{$fix.id}">
						{l s='Uninstall'}
					</button>
				{elseif $fix.compliant}
					<button class="btn btn-default" type="submit" name="installfix" value="{$fix.id}">
						{l s='Install'}
					</button>
				{else}
					<span>{l s='Not required' mod='kerawen'}</span>
				{/if}
			</div>
			<div class="title">{$fix.title}</div>
			<div class="desc">{$fix.desc}</div>
			<div class="versions">
				<ul>
					{if isset($fix.min)}
						<li>{l s='Bug present from PrestaShop %s' sprintf=$fix.min mod='kerawen'}</li>
					{/if}
					{if isset($fix.max)}
						<li>{l s='Corrected from PrestaShop %s' sprintf=$fix.max mod='kerawen'}</li>
					{/if}
				</ul>
			</div>
		</div>
	{/foreach}
</div>

