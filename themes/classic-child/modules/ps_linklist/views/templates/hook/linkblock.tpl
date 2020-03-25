{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div class="col-md-12 links">
  <div class="row">
  {foreach $linkBlocks as $linkBlock}
    <div class="col-md-3 wrapper">
      <p class="h3 hidden-sm-down">{$linkBlock.title}</p>
      {assign var=_expand_id value=10|mt_rand:100000}
      <div class="title clearfix hidden-md-up" data-target="#footer_sub_menu_{$_expand_id}" data-toggle="collapse">
        <span class="h3">{$linkBlock.title}</span>
        <span class="float-xs-right">
          <span class="navbar-toggler collapse-icons">
            <i class="material-icons add">&#xE313;</i>
            <i class="material-icons remove">&#xE316;</i>
          </span>
        </span>
      </div>
      <ul id="footer_sub_menu_{$_expand_id}" class="collapse">
        {foreach $linkBlock.links as $link}
          <li>
            <a
                id="{$link.id}-{$linkBlock.id}"
                class="{$link.class}"
                href="{$link.url}"
                title="{$link.description}"
                {if !empty($link.target)} target="{$link.target}" {/if}
            >
              {$link.title}
            </a>
          </li>
        {/foreach}
      </ul>
    </div>
  {/foreach}
    <div class="" id="goleft">
      <div class="social-media">
        <a href="https://www.facebook.com/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}facebook.png" alt="facebook"> </a>
        <a href="https://www.instagram.com/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}instagram.png" alt="instagram"> </a>
        <a href="https://www.pinterest.fr/compagniedeprovence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}pinterest.png" alt="pinterest"> </a>
        <a href="https://www.linkedin.com/company/la-compagnie-de-provence/" target="_blank" class="social-folone">   <img class="social-follow" src="{$urls.img_url}linkedin.png" alt="linkedin"> </a>



      </div>
      <div class="eco-disclaim">
        <img src="{$urls.img_url}eco.png" id="ecoimg">
        <div class="eco-slaim">
          Pensez à trier vos emballages ! Pour en savoir plus sur les consignes de tri en France www.consignesdetri.fr


        </div>


      </div>
    </div>
  </div>
</div>
