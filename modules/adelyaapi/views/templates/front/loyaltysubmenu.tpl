{extends file='page.tpl'}
{block name='content'}
  <section id="main">

    {block name='header'}
		<header class="page-header">
			<h1>{$HOOK_CUSTOMERACCOUNT_TITLE}</h1>
        </header>
    {/block}

    {block name='main_block'}
		<section id="content" class="page-content" style="box-shadow: 2px 2px 8px 0 rgba(0,0,0,.2);background: #fff;padding: 1rem;font-size: .875rem;color: #7a7a7a;">
			<section>
				{if $FRONT_LOYALTYSUBMENU_HTMLTEXT}
					<div>
						{$FRONT_LOYALTYSUBMENU_HTMLTEXT nofilter}
					</div>
				{/if}
				{if $FRONT_LOYALTYSUBMENU_NBPOINT && $FRONT_LOYALTYSUBMENU_NBCREDIT}
					<div style="text-align:center;">	
						<div>{l s='You have' mod='adelyaapi'} :</div>
						<div style="float:left;width:50%;">{$FRONT_LOYALTYSUBMENU_NBPOINT} {l s='points' mod='adelyaapi'}</div>
						<div style="float:right;width:50%;">{$FRONT_LOYALTYSUBMENU_NBCREDIT} {l s='credits' mod='adelyaapi'}</div>	
					</div>
				{elseif $FRONT_LOYALTYSUBMENU_NBPOINT}
					<div>{l s='You have' mod='adelyaapi'} {$FRONT_LOYALTYSUBMENU_NBPOINT} {l s='points' mod='adelyaapi'}</div>
				{elseif $FRONT_LOYALTYSUBMENU_NBCREDIT}	
					<div>{l s='You have' mod='adelyaapi'} {$FRONT_LOYALTYSUBMENU_NBCREDIT} {l s='credits' mod='adelyaapi'}</div>
				{/if}
				<div class="subpage_footer" style="height:10px;"></div>
			</section>
		</section>
    {/block}

  </section>
{/block}

