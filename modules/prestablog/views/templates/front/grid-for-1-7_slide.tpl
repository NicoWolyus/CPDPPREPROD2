{*
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 *}

<!-- Module Presta Blog -->

<div class="prestablog_slide">
	<div class="sliders_prestablog">
	{foreach from=$ListeBlogNews item=slide name=slides}

			<img src="{$prestablog_theme_upimg|escape:'html':'UTF-8'}slide_{$slide.id_prestablog_news|intval}.jpg?{$md5pic|escape:'htmlall':'UTF-8'}" class="visu" alt="{$slide.title|escape:'htmlall':'UTF-8'}" title="{$slide.title|escape:'htmlall':'UTF-8'}" />


	{/foreach}
    </div>
</div>
<div class="clearfix"></div>
<!-- /Module Presta Blog -->
