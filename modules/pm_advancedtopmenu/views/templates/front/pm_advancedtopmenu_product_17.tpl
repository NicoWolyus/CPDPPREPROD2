{* PrestaShop 1.7 Product Template *}
<div class="featured-products">
	<div class="products">
		<div id="adtm_product_{$column.id_column|intval}_{$column.productInfos.id_product|intval}" class="adtm_product adtm_product-17">
		{assign var='product' value=$column.productInfos}
		{include file="module:pm_advancedtopmenu/views/templates/front/product_miniature_17.tpl" product=$product column=$column}
		{if empty($column.productSettings->show_title) && empty($column.productSettings->show_price)}
			<style type="text/css">
				#adtm_product_{$column.id_column|intval}_{$column.productInfos.id_product|intval} .product-description { display: none; }
				#adtm_product_{$column.id_column|intval}_{$column.productInfos.id_product|intval} .highlighted-informations { bottom: initial; }
			</style>
		{/if}
		</div>
	</div>
</div>