/**
* 2019 Finland Quality Design
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Finland Quality Design <info@finlandquality.com>
*  @copyright  2019 Finland Quality Design
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
**/

$(document).ready(() => {
    var selector_country = '.js-customer-form select[name="id_country"]';
    $('body').on('change', selector_country, () => {
        var requestData = {
            id_country: $(selector_country).val()
        };
        var changeCountryUrl = $('[name="change_cuntry_url"]').val();
        var selector_address = '.js-customer-form';

        var formFieldsSelector = selector_address + ' input';

        $.post(changeCountryUrl, requestData).then(function (resp) {
            var inputs = [];

            // Store fields values before updating form
            $(formFieldsSelector).each(function () {
                inputs[$(this).prop('name')] = $(this).val();
            });

            var action = $(selector_address).prop("action");
            var id = $(selector_address).prop("id");
            var class_ = $(selector_address).prop("class");
            $(selector_address).replaceWith(resp.address_form);

            // Restore fields values
            $(formFieldsSelector).each(function () {
                $(this).val(inputs[$(this).prop('name')]);
            });

            $(selector_address).prop("action", action);
            $(selector_address).prop("id", id);
            $(selector_address).prop("class", class_);

            prestashop.emit('updatedAddressForm', {target: $(selector_address), resp: resp});
        }).fail((resp) => {
            prestashop.emit('handleError', {eventType: 'updateAddressForm', resp: resp});
        });
    });
});
