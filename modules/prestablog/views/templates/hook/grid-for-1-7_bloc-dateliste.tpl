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
<div class="block-categories">
	<h4 class="title_block">{l s='Blog archives' mod='prestablog'}</h4>
	<div class="block_content" id="prestablog_dateliste">
		{if $ResultDateListe}
			<ul>
			{foreach from=$ResultDateListe key=KeyAnnee item=ValueAnnee name=loopAnnee}
				<li>
					<a href="#" class="prestablog_annee" {if count($ResultDateListe)<=1}style="display:none;"{/if}>{$KeyAnnee|escape:'htmlall':'UTF-8'}&nbsp;<span>({$ValueAnnee.nombre_news|intval})</span></a>
					<ul class="prestablog_mois {if (isset($prestablog_annee) && $prestablog_annee==$KeyAnnee)}prestablog_show{/if}">
				{foreach from=$ValueAnnee.mois key=KeyMois item=ValueMois name=loopMois}
						<li>
							<a href="{PrestaBlogUrl y=$KeyAnnee m=$KeyMois}">{$ValueMois.mois_value|escape:'htmlall':'UTF-8'}&nbsp;<span>({$ValueMois.nombre_news|intval})</span></a>
						</li>
				{/foreach}
					</ul>
				</li>
			{/foreach}
			</ul>
		{else}
			<p>{l s='No news' mod='prestablog'}</p>
		{/if}
		{if $prestablog_config.prestablog_datenews_showall}<a href="{PrestaBlogUrl}" class="btn-primary">{l s='See all' mod='prestablog'}</a>{/if}
	</div>
</div>
<!-- /Module Presta Blog -->
