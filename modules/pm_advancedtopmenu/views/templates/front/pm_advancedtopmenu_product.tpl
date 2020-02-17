<div id="adtm_product_{$column.id_column|intval}_{$column.productInfos.id_product|intval}" class="adtm_product{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>=')} adtm_product-16{/if}{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<') && version_compare($smarty.const._PS_VERSION_, '1.5.0.0', '>=')} adtm_product-15{/if}">
	{assign var='product' value=$column.productInfos}
	{* PrestaShop 1.6 Product Template *}
	{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>=')}
		<div class="product-container" itemscope itemtype="http://schema.org/Product">
			<div class="left-block">
				<div class="product-image-container">
					<a class="product_img_link"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} href="{$product.link|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}" itemprop="url">
						<img class="replace-2x img-responsive" src="{$link->getImageLink($product.link_rewrite, $product.id_image, $column.productSettings->p_image_type)|escape:'html':'UTF-8'}" alt="{if !empty($product.legend)}{$product.legend|escape:'html':'UTF-8'}{else}{$product.name|escape:'html':'UTF-8'}{/if}" title="{if !empty($product.legend)}{$product.legend|escape:'html':'UTF-8'}{else}{$product.name|escape:'html':'UTF-8'}{/if}" itemprop="image" />
					</a>
					
					{if isset($column.productSettings->show_quick_view) && $column.productSettings->show_quick_view}
					<div class="quick-view-wrapper-mobile">
						<a class="quick-view-mobile" href="{$product.link|escape:'html':'UTF-8'}" rel="{$product.link|escape:'html':'UTF-8'}">
							<i class="icon-eye-open"></i>
						</a>
					</div>
					<a class="quick-view" href="{$product.link|escape:'html':'UTF-8'}" rel="{$product.link|escape:'html':'UTF-8'}">
						<span>{l s='Quick view' mod='pm_advancedtopmenu'}</span>
					</a>
					{/if}
					
					{if isset($column.productSettings->show_price) && $column.productSettings->show_price}
						{if (!$PS_CATALOG_MODE && ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
							<div class="content_price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
								{if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}
									<span itemprop="price" class="price product-price">
										{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}
									</span>
									<meta itemprop="priceCurrency" content="{$currency->iso_code|escape:'htmlall':'UTF-8'}}" />
									{if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction) && $product.specific_prices.reduction > 0}
										{hook h="displayProductPriceBlock" product=$product type="old_price"}
										<span class="old-price product-price">
											{displayWtPrice p=$product.price_without_reduction}
										</span>
										{if $product.specific_prices.reduction_type == 'percentage'}
											<span class="price-percent-reduction">-{($product.specific_prices.reduction * 100)|floatval}%</span>
										{/if}
									{/if}
									{hook h="displayProductPriceBlock" product=$product type="price"}
									{hook h="displayProductPriceBlock" product=$product type="unit_price"}
								{/if}
							</div>
						{/if}
					{/if}
					{if isset($product.new) && $product.new == 1}
						<a class="new-box" href="{$product.link|escape:'html':'UTF-8'}">
							<span class="new-label">{l s='New' mod='pm_advancedtopmenu'}</span>
						</a>
					{/if}
					{if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}
						<a class="sale-box" href="{$product.link|escape:'html':'UTF-8'}">
							<span class="sale-label">{l s='Sale!' mod='pm_advancedtopmenu'}</span>
						</a>
					{/if}
				</div>
				{hook h="displayProductDeliveryTime" product=$product}
				{hook h="displayProductPriceBlock" product=$product type="weight"}
			</div>
			<div class="right-block">
				{if isset($column.productSettings->show_title) && $column.productSettings->show_title}
					<h5 itemprop="name">
						{if isset($product.pack_quantity) && $product.pack_quantity}{$product.pack_quantity|intval|cat:' x '}{/if}
						<a class="product-name"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} href="{$product.link|escape:'html':'UTF-8'}" title="{$product.name|escape:'html':'UTF-8'}" itemprop="url" >
							{$product.name|truncate:45:'...'|escape:'html':'UTF-8'}
						</a>
					</h5>
				{/if}
				{*
				{hook h='displayProductListReviews' product=$product}
				*}
				{*
				<p class="product-desc" itemprop="description">
					{$product.description_short|strip_tags:'UTF-8'|truncate:360:'...'}
				</p>
				*}
				{*
				{if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
				<div class="content_price">
					{if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}
						<span class="price product-price">
							{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}
						</span>
						{if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction) && $product.specific_prices.reduction > 0}
							{hook h="displayProductPriceBlock" product=$product type="old_price"}
							<span class="old-price product-price">
								{displayWtPrice p=$product.price_without_reduction}
							</span>
							{hook h="displayProductPriceBlock" id_product=$product.id_product type="old_price"}
							{if $product.specific_prices.reduction_type == 'percentage'}
								<span class="price-percent-reduction">-{$product.specific_prices.reduction * 100}%</span>
							{/if}
						{/if}
						{hook h="displayProductPriceBlock" product=$product type="price"}
						{hook h="displayProductPriceBlock" product=$product type="unit_price"}
					{/if}
				</div>
				{/if}
				*}
				<div class="button-container">
					{if isset($column.productSettings->show_add_to_cart) && $column.productSettings->show_add_to_cart}
						{if ($product.id_product_attribute == 0 || (isset($add_prod_display) && ($add_prod_display == 1))) && $product.available_for_order && !isset($restricted_country_mode) && $product.customizable != 2 && !$PS_CATALOG_MODE}
							{if (!isset($product.customization_required) || !$product.customization_required) && ($product.allow_oosp || $product.quantity > 0)}
								{capture}add=1&amp;id_product={$product.id_product|intval}{if isset($static_token)}&amp;token={$static_token|escape:'htmlall':'UTF-8'}}{/if}{/capture}
								<a class="button ajax_add_to_cart_button btn btn-default" href="{$link->getPageLink('cart', true, NULL, $smarty.capture.default, false)|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Add to cart' mod='pm_advancedtopmenu'}" data-id-product="{$product.id_product|intval}" data-minimal_quantity="{if isset($product.product_attribute_minimal_quantity) && $product.product_attribute_minimal_quantity > 1}{$product.product_attribute_minimal_quantity|intval}{else}{$product.minimal_quantity|intval}{/if}">
									<span>{l s='Add to cart' mod='pm_advancedtopmenu'}</span>
								</a>
							{else}
								<span class="button ajax_add_to_cart_button btn btn-default disabled">
									<span>{l s='Add to cart' mod='pm_advancedtopmenu'}</span>
								</span>
							{/if}
						{/if}
					{/if}
					{if isset($column.productSettings->show_more_info) && $column.productSettings->show_more_info}
					<a class="button lnk_view btn btn-default"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} href="{$product.link|escape:'html':'UTF-8'}" title="{l s='View' mod='pm_advancedtopmenu'}">
						<span>{if (isset($product.customization_required) && $product.customization_required)}{l s='Customize' mod='pm_advancedtopmenu'}{else}{l s='More' mod='pm_advancedtopmenu'}{/if}</span>
					</a>
					{/if}
				</div>
				{*
				{if isset($product.color_list)}
					<div class="color-list-container">{$product.color_list}</div>
				{/if}
				*}
				{*
				<div class="product-flags">
					{if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
						{if isset($product.online_only) && $product.online_only}
							<span class="online_only">{l s='Online only' mod='pm_advancedtopmenu'}</span>
						{/if}
					{/if}
					{if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}
						{elseif isset($product.reduction) && $product.reduction && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}
							<span class="discount">{l s='Reduced price!' mod='pm_advancedtopmenu'}</span>
						{/if}
				</div>
				*}
				{*
				{if (!$PS_CATALOG_MODE && $PS_STOCK_MANAGEMENT && ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
					{if isset($product.available_for_order) && $product.available_for_order && !isset($restricted_country_mode)}
						<span class="availability">
							{if ($product.allow_oosp || $product.quantity > 0)}
								<span class="{if $product.quantity <= 0 && !$product.allow_oosp}out-of-stock{else}available-now{/if}">
									{if $product.quantity <= 0}{if $product.allow_oosp}{if isset($product.available_later) && $product.available_later}{$product.available_later}{else}{l s='In Stock' mod='pm_advancedtopmenu'}{/if}{else}{l s='Out of stock' mod='pm_advancedtopmenu'}{/if}{else}{if isset($product.available_now) && $product.available_now}{$product.available_now}{else}{l s='In Stock' mod='pm_advancedtopmenu'}{/if}{/if}
								</span>
							{elseif (isset($product.quantity_all_versions) && $product.quantity_all_versions > 0)}
								<span class="available-dif">
									{l s='Product available with different options' mod='pm_advancedtopmenu'}
								</span>
							{else}
								<span class="out-of-stock">
									{l s='Out of stock' mod='pm_advancedtopmenu'}
								</span>
							{/if}
						</span>
					{/if}
				{/if}
				*}
			</div>
		</div><!-- .product-container> -->
	{else}
	{* PrestaShop 1.5 Product Template *}
		<a href="{$product.link|escape:'html'}"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} title="{$product.name|escape:'htmlall':'UTF-8'}" class="product_image">
			<img src="{$link->getImageLink($product.link_rewrite, $product.id_image, $column.productSettings->p_image_type)|escape:'html'}" alt="{$product.name|escape:'htmlall':'UTF-8'}" />
			{if isset($product.new) && $product.new == 1}<span class="new">{l s='New' mod='pm_advancedtopmenu'}</span>{/if}
		</a>
		{if isset($column.productSettings->show_title) && $column.productSettings->show_title}
			<h5 class="s_title_block">
				<a href="{$product.link|escape:'html'}"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} title="{$product.name|escape:'htmlall':'UTF-8'}">{$product.name|escape:'htmlall':'UTF-8'}</a>
			</h5>
		{/if}
		<div class="adtm_product_buttons_container">
			{if isset($column.productSettings->show_price) && $column.productSettings->show_price}
				{if $product.show_price AND !isset($restricted_country_mode) AND !$PS_CATALOG_MODE}
					<p class="price_container">
						<span class="price">
							{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}
						</span>
					</p>
				{/if}
			{/if}
			{if isset($column.productSettings->show_add_to_cart) && $column.productSettings->show_add_to_cart}
				{if ($product.id_product_attribute == 0 OR (isset($add_prod_display) AND ($add_prod_display == 1))) AND $product.available_for_order AND !isset($restricted_country_mode) AND $product.minimal_quantity == 1 AND $product.customizable != 2 AND !$PS_CATALOG_MODE}
					{if ($product.quantity > 0 OR $product.allow_oosp)}
					<a class="exclusive ajax_add_to_cart_button" rel="ajax_id_product_{$product.id_product|intval}" href="{$link->getPageLink('cart')|escape:'html'}?qty=1&amp;id_product={$product.id_product|intval}&amp;token={$static_token|escape:'htmlall':'UTF-8'}&amp;add" title="{l s='Add to cart' mod='pm_advancedtopmenu'}">{l s='Add to cart' mod='pm_advancedtopmenu'}</a>
					{else}
					<span class="exclusive">{l s='Add to cart' mod='pm_advancedtopmenu'}</span>
					{/if}
				{/if}
			{/if}
			{if isset($column.productSettings->show_more_info) && $column.productSettings->show_more_info}
				<a class="exclusive" href="{$product.link|escape:'html'}"{if $column.target} target="{$column.target|escape:'html':'UTF-8'}"{/if} title="{l s='More' mod='pm_advancedtopmenu'}">{l s='More' mod='pm_advancedtopmenu'}</a>
			{/if}
		</div>
	{/if}
</div>