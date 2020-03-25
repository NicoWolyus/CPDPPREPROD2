{*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*}

{if $FMESL_TABSTATE > 0}
  {if ($PS_VERSION >= '1.6.0' || $PS_VERSION >= '1.6.0.0')}<h3 class="page-product-heading">{else}<li><a class="idTabHrefShort" href="#idTab565">{/if}{if !empty($FMESL_TAB)}{$FMESL_TAB|escape:'htmlall':'UTF-8'}{else}{l s='Available in Stores' mod='storelocator'}{/if}</a>{if ($PS_VERSION >= '1.6.0' || $PS_VERSION >= '1.6.0.0')}</h3>{else}</li>{/if}
{/if}
