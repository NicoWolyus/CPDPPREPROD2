{* PrestaShop 1.7 Product Template *}
{extends file="catalog/_partials/miniatures/product.tpl"}

{block name='product_name'}
	{if !empty($column.productSettings->show_title)}
		{$smarty.block.parent}
	{/if}
{/block}
{block name='product_price_and_shipping'}
	{if !empty($column.productSettings->show_price)}
		{$smarty.block.parent}
	{/if}
{/block}