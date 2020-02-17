<!-- MODULE PM_AdvancedTopMenu || Presta-Module.com -->
{if !isset($advtmThemeCompatibility) || (isset($advtmThemeCompatibility) && $advtmThemeCompatibility)}</div><div class="clear"></div>{/if}
<div id="_desktop_top_menu" class="adtm_menu_container {$advtmContainerClasses|escape:'htmlall':'UTF-8'} col-md-12 p-0">
	<div id="adtm_menu"{if $advtmActivatedMenuId && $advtmActivatedMenuType} data-activate-menu-id=".{$advtmActivatedMenuId|escape:'htmlall':'UTF-8'}" data-activate-menu-type="{$advtmActivatedMenuType|escape:'htmlall':'UTF-8'}"{/if}{if isset($advtmOpenMethod) && $advtmOpenMethod} data-open-method="{$advtmOpenMethod|intval}"{/if}{if isset($advtmIsSticky) && $advtmIsSticky} data-sticky="1"{/if}{if isset($advtmResponsiveContainerClasses) && !empty($advtmResponsiveContainerClasses)} class="{$advtmResponsiveContainerClasses|escape:'htmlall':'UTF-8'}"{/if}>
		<div id="adtm_menu_inner"{if isset($advtmInnerClasses) && !empty($advtmInnerClasses)} class="{$advtmInnerClasses|escape:'htmlall':'UTF-8'}"{/if}>
			<ul id="menu">
				{if isset($advtmResponsiveMode) && $advtmResponsiveMode}
				<li class="li-niveau1 advtm_menu_toggle">
					<a class="a-niveau1 adtm_toggle_menu_button"><span class="advtm_menu_span adtm_toggle_menu_button_text">{if isset($advtmResponsiveToggleText) && !empty($advtmResponsiveToggleText)}{$advtmResponsiveToggleText|escape:'htmlall':'UTF-8'}{else}Menu{/if}</span></a>
				</li>
				{/if}
				{foreach from=$advtm_menus item=menu name=loop}
					{assign var='menuIsInChosenGroups' value=1}
					{if $menu.privacy eq 3}
						{assign var='menuIsInChosenGroups' value=0}
						{assign var='menuChosenGroups' value=$menu.chosen_groups|json_decode}
						{if $menuChosenGroups|is_array}
							{foreach from=$menuChosenGroups item=menuChosenGroup name=loopprivacy}
								{if $menuChosenGroup|in_array:$customerGroups}
									{assign var='menuIsInChosenGroups' value=1}
									{break}
								{/if}
							{/foreach}
						{/if}
					{/if}

					{if ($menu.privacy eq 2 && $isLogged) || ($menu.privacy eq 1 && !$isLogged) || (!$menu.privacy) || ($menu.privacy eq 3 && $menuIsInChosenGroups)}
						{assign var='menuHaveSub' value=$advtm_columns_wrap[$menu.id_menu]|count}
						{assign var='menuIsSearchBox' value=($menu.type == 6)}
						{assign var='menuHaveAtLeastOneMobileSubMenu' value=0}
						{foreach from=$advtm_columns_wrap[$menu.id_menu] item=column_wrap name=loop2}
							{if $column_wrap.active_mobile|intval}
								{assign var='menuHaveAtLeastOneMobileSubMenu' value=1}
								{break}
							{/if}
						{/foreach}
				<li class="li-niveau1 advtm_menu_{$menu.id_menu|intval}{if $menuHaveSub} sub{/if}{if $menuIsSearchBox} advtm_search{/if}{if !$menu.active_desktop|intval} advtm_hide_desktop{/if}{if !$menu.active_mobile|intval} advtm_hide_mobile{/if}{if !$menuHaveAtLeastOneMobileSubMenu} menuHaveNoMobileSubMenu{/if}">
						{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
							{$menu.link_output_value nofilter}{* HTML *}
						{else}
							{$menu.link_output_value}{* HTML *}
						{/if}
						{if $menuHaveSub}
					<!--<![endif]--><!--[if lte IE 6]><table><tr><td><![endif]-->
					<div class="adtm_sub">
							{if $menu.value_over}
								{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
									{$menu.value_over nofilter}{* HTML *}
								{else}
									{$menu.value_over}{* HTML *}
								{/if}
							{/if}
						<table class="columnWrapTable">
							<tr>
							{foreach from=$advtm_columns_wrap[$menu.id_menu] item=column_wrap name=loop2}
								{assign var='columnWrapIsInChosenGroups' value=1}
								{if $column_wrap.privacy eq 3}
									{assign var='columnWrapIsInChosenGroups' value=0}
									{assign var='columnWrapChosenGroups' value=$column_wrap.chosen_groups|json_decode}
									{if $columnWrapChosenGroups|is_array}
										{foreach from=$columnWrapChosenGroups item=columnWrapChosenGroup name=loopprivacy}
											{if $columnWrapChosenGroup|in_array:$customerGroups}
												{assign var='columnWrapIsInChosenGroups' value=1}
												{break}
											{/if}
										{/foreach}
									{/if}
								{/if}

								{if ($column_wrap.privacy eq 2 && $isLogged) || ($column_wrap.privacy eq 1 && !$isLogged) || (!$column_wrap.privacy) || ($column_wrap.privacy eq 3 && $columnWrapIsInChosenGroups)}
								<td class="adtm_column_wrap_td advtm_column_wrap_td_{$column_wrap.id_wrap|intval}{if !$column_wrap.active_desktop|intval} advtm_hide_desktop{/if}{if !$column_wrap.active_mobile|intval} advtm_hide_mobile{/if}">
									<div class="adtm_column_wrap advtm_column_wrap_{$column_wrap.id_wrap|intval}">
									{if $column_wrap.value_over}
										{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
											{$column_wrap.value_over nofilter}{* HTML *}
										{else}
											{$column_wrap.value_over}{* HTML *}
										{/if}
									{/if}
										<div class="adtm_column_wrap_sizer">&nbsp;</div>
									{foreach from=$advtm_columns[$column_wrap.id_wrap] item=column name=loop3}
										{assign var='columnIsInChosenGroups' value=1}
										{if $column.privacy eq 3}
											{assign var='columnIsInChosenGroups' value=0}
											{assign var='columnChosenGroups' value=$column.chosen_groups|json_decode}
											{if $columnChosenGroups|is_array}
												{foreach from=$columnChosenGroups item=columnChosenGroup name=loopprivacy}
													{if $columnChosenGroup|in_array:$customerGroups}
														{assign var='columnIsInChosenGroups' value=1}
														{break}
													{/if}
												{/foreach}
											{/if}
										{/if}
										{if ($column.privacy eq 2 && $isLogged) || ($column.privacy eq 1 && !$isLogged) || (!$column.privacy) || ($column.privacy eq 3 && $columnIsInChosenGroups)}
											{if $column.value_over}
												{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
													{$column.value_over nofilter}{* HTML *}
												{else}
													{$column.value_over}{* HTML *}
												{/if}
											{/if}
										<div class="adtm_column adtm_column_{$column.id_column|intval}{if !$column.active_desktop|intval} advtm_hide_desktop{/if}{if !$column.active_mobile|intval} advtm_hide_mobile{/if}">
											{if $column.type == 8}
												{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
													{include file="module:pm_advancedtopmenu/views/templates/front/pm_advancedtopmenu_product_17.tpl" products=$column.productInfos}
												{else}
													{include file="./pm_advancedtopmenu_product.tpl" products=$column.productInfos}
												{/if}
											{else}
												{if $column.link_output_value}
											<span class="column_wrap_title">
												{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
													{$column.link_output_value nofilter}{* HTML *}
												{else}
													{$column.link_output_value}{* HTML *}
												{/if}
											</span>
												{/if}
												{assign var='columnHaveElement' value=$advtm_elements[$column.id_column]|count}
												{if $columnHaveElement}
											<ul class="adtm_elements adtm_elements_{$column.id_column|intval}">
													{foreach from=$advtm_elements[$column.id_column] item=element name=loop2}
														{assign var='elementIsInChosenGroups' value=1}
														{if $element.privacy eq 3}
															{assign var='elementIsInChosenGroups' value=0}
															{assign var='elementChosenGroups' value=$element.chosen_groups|json_decode}
															{if $elementChosenGroups|is_array}
																{foreach from=$elementChosenGroups item=elementChosenGroup name=loopprivacy}
																	{if $elementChosenGroup|in_array:$customerGroups}
																		{assign var='elementIsInChosenGroups' value=1}
																		{break}
																	{/if}
																{/foreach}
															{/if}
														{/if}
														{if ($element.privacy eq 2 && $isLogged) || ($element.privacy eq 1 && !$isLogged) || (!$element.privacy) || ($element.privacy eq 3 && $elementIsInChosenGroups)}
												<li class="{if !$element.active_desktop|intval} advtm_hide_desktop{/if}{if !$element.active_mobile|intval} advtm_hide_mobile{/if}">
															{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
																{$element.link_output_value nofilter}{* HTML *}
															{else}
																{$element.link_output_value}{* HTML *}
															{/if}
												</li>
														{/if}
													{/foreach}
											</ul>
												{/if}
											{/if}
										</div>
											{if $column.value_under}
												{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
													{$column.value_under nofilter}{* HTML *}
												{else}
													{$column.value_under}{* HTML *}
												{/if}
											{/if}
										{/if}
									{/foreach}
									{if $column_wrap.value_under}
										{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
											{$column_wrap.value_under nofilter}{* HTML *}
										{else}
											{$column_wrap.value_under}{* HTML *}
										{/if}
									{/if}
									</div>
								</td>
								{/if}
							{/foreach}
							</tr>
						</table>
							{if $menu.value_under}
								{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
									{$menu.value_under nofilter}{* HTML *}
								{else}
									{$menu.value_under}{* HTML *}
								{/if}
							{/if}
					</div>
					<!--[if lte IE 6]></td></tr></table></a><![endif]-->
						{/if}
				</li>
					{/if}
				{/foreach}
			</ul>
		</div>
	</div>
</div>
{if !isset($advtmThemeCompatibility) || (isset($advtmThemeCompatibility) && $advtmThemeCompatibility)}<div>{/if}
<!-- /MODULE PM_AdvancedTopMenu || Presta-Module.com -->