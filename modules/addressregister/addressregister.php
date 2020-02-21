<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AddressRegister extends Module
{
    public function __construct()
    {
        $this->name = 'addressregister';
        $this->need_instance = 0;
        $this->version = '1.0.3';
        $this->tab = 'front_office_features';
        $this->author = 'Finland Quality Design';
        $this->module_key = 'bd4afc975099530a96ea210008d8dc90';

        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Address at Registration');
        $this->description = $this->l('Add the address fields to the registration form');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() && $this->registerHook(array(
            'actionCustomerAccountAdd',
            'validateCustomerFormFields',
            'additionalCustomerFormFields'
        ));
    }

    public function hookAdditionalCustomerFormFields(array $params)
    {
        if ($this->context->controller->getPageName() == 'identity') {
            return array();
        }

        if (Tools::getIsset('id_country')) {
            $country = new Country(Tools::getValue('id_country'), $this->context->language->id);
        } else {
            $country = $this->context->country;
        }


        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $availableCountries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $availableCountries = Country::getCountries($this->context->language->id, true);
        }

        $addressFormatter = new CustomerAddressFormatter(
            $country,
            $this->getTranslator(),
            $availableCountries
        );

        $formFields = $addressFormatter->getFormat();

        $formFields['id_country']->setType('select');

        $ajax_link = $this->context->link->getModuleLink(
            'addressregister',
            'customer',
            array("ajax" => "1", "action" => "addressForm")
        );

        $formField = (new FormField())
            ->setName('change_cuntry_url')
            ->setValue($ajax_link)
            ->setType('hidden');
        $formFields[$formField->getName()] = $formField;

        // remove firstname, lastname, alias, back, id_customer, token, id_address.
        unset($formFields['firstname']);
        unset($formFields['lastname']);
        unset($formFields['alias']);
        unset($formFields['back']);
        unset($formFields['id_customer']);
        unset($formFields['id_address']);
        unset($formFields['token']);

        $this->context->controller->addJS($this->_path . '/views/js/changecountrya.js');
        return $formFields;
    }

    public function hookValidateCustomerFormFields(array $params)
    {
        $formFields = $params['fields'];
        foreach ($formFields as $field) {
            if ($field->getName() == "id_country") {
                $country = new Country($field->getValue(), $this->context->language->id);
                break;
            }
        }
        foreach ($formFields as $field) {
            if ($field->getName() == "postcode" && isset($country)) {
                if ($field->isRequired()) {
                    $country = $country;
                    if (!$country->checkZipCode($field->getValue())) {
                        $field->addError($this->trans(
                            'Invalid postcode - should look like "%zipcode%"',
                            array('%zipcode%' => $country->zip_code_format),
                            'Shop.Forms.Errors'
                        ));
                    }
                }
            }
        }
    }

    protected function makeAddressForm($customer)
    {
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $availableCountries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $availableCountries = Country::getCountries($this->context->language->id, true);
        }

        $persister = new CustomerAddressPersister(
            $customer,
            $this->context->cart,
            null // the token is not defined for new user.
        );

        $addressFormatter = new CustomerAddressFormatter(
            $this->context->country,
            $this->getTranslator(),
            $availableCountries
        );

        $form = new CustomerAddressForm(
            $this->context->smarty,
            $this->context->language,
            $this->getTranslator(),
            $persister,
            $addressFormatter
        );
        return $form;
    }

    public function hookActionCustomerAccountAdd(array $params)
    {
	$context = Context::getContext();
	if (isset($context->kerawen)) {
		return false;
	}

        $customer = $params['newCustomer'];
        //file_put_contents(DIRNAME(__FILE__).'/test_log.txt', var_export($customer, true));
        $address_form = $this->makeAddressForm($customer);
        $address_form->fillWith(Tools::getAllValues());
        return $address_form->submit();
    }
}
