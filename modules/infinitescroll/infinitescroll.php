<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 * We offer the best and most useful modules PrestaShop and modifications for your online store.
 *
 * @author    knowband.com <support@knowband.com>
 * @copyright 2017 Knowband
 * @license   see file: LICENSE.txt
 * @category  PrestaShop Module
 *
 *
 * Description
 *
 * Updates quantity in the cart
 */

class Infinitescroll extends Module
{
    private $my_module_settings = array();
    public $controller_type;

    public function __construct()
    {
        $this->name = 'infinitescroll';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'knowband';
        $this->need_instance = 0;
        $this->module_key = '0a3a73fd2b3d9e7e3ec53fa59c601dc6';
        $this->author_address = '0x2C366b113bd378672D4Ee91B75dC727E857A54A6';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Infinite Scroll');
        $this->description = $this->l('Is an integration of scrolling of product on product listing page in place of pagination.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('infinite_scroll')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
            if (!parent::install() ||
                !$this->registerHook('displayheader') ||
                !$this->registerHook('displaymobileheader')) {
                return false;
            }
        } else {
            if (!parent::install() || !$this->registerHook('displayheader')) {
                return false;
            }
        }

        $defaultsettings = $this->getDefaultSettings();
        $defaultsettings = serialize($defaultsettings);
        Configuration::updateValue('infinite_scroll', $defaultsettings);
        /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
        Configuration::updateValue('infinite_scroll_custom_js', '');
        Configuration::updateValue('infinite_scroll_custom_css', '');
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $output = null;
        $error = null;

        $config = Configuration::get('infinite_scroll');
        $this->my_module_settings = Tools::unSerialize($config);

        if (Tools::isSubmit('infinite_scroll')) {
            $formvalue = Tools::getvalue('infinite_scroll');
            $error = 0;

            $formvalue = array_map('trim', $formvalue);

            /* Knowband validation start */
            if (Tools::strlen($formvalue['custom_css']) > 10000) {
                $this->context->controller->errors[] = $this->l('Maximum 10000 characters allowed at Custom CSS.');
                $error = 1;
            }
            /* Knowband validation end */
            /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
            /* Knowband validation start */
            if (Tools::strlen($formvalue['custom_js']) > 10000) {
                $this->context->controller->errors[] = $this->l('Maximum 10000 characters allowed at Custom JS.');
                $error = 1;
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['scroll_type'] == 1) {
                if (empty($formvalue['load_more_link_frequency']) ||
                        !Validate::isInt($formvalue['load_more_link_frequency'])) {
                    $this->context->controller->errors[] = $this->l('Load frequency entered is not valid.');
                    $error = 1;
                } elseif ($formvalue['load_more_link_frequency'] <= 0) {
                    $this->context->controller->errors[] = $this->l('Load frequency must be greater than 0.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['enable_sandbox_setting'] == 0) {
                $formvalue['add_ip'] = '';
            } else {
                $formvalue['add_ip'] = trim($formvalue['add_ip'], ",");
                if (empty($formvalue['add_ip'])) {
                    $this->context->controller->errors[] = $this->l('Please enter at least one IP address.');
                    $error = 1;
                } else {
                    $add_ips = explode(",", $formvalue['add_ip']);
                    $add_ips = array_map('trim', $add_ips);
                    $unique_ips = array_unique($add_ips);
                    $unique_ips = array_filter($unique_ips, 'strlen');

                    foreach ($unique_ips as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
                            $this->context->controller->errors[] = $this->l('Invalid IP address at Add IP.');
                            $error = 1;
                            break;
                        }
                    }
                    $formvalue['add_ip'] = implode(",", $unique_ips);
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['background_color'] == '') {
                $this->context->controller->errors[] = $this->l('Please enter Background Color');
                $error = 1;
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['text_color'] == '') {
                $this->context->controller->errors[] = $this->l('Please enter Background Color');
                $error = 1;
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['border_color'] == '') {
                $this->context->controller->errors[] = $this->l('Please enter Background Color');
                $error = 1;
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if ($formvalue['background_color_top_link'] == '') {
                $this->context->controller->errors[] = $this->l('Please enter Background Color');
                $error = 1;
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_item'])) {
                if (Tools::strlen($formvalue['selector_item']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Item.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_container'])) {
                if (Tools::strlen($formvalue['selector_container']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Container.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_next'])) {
                if (Tools::strlen($formvalue['selector_next']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Next.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_pagination'])) {
                if (Tools::strlen($formvalue['selector_pagination']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Pagination.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_item_mobile'])) {
                if (Tools::strlen($formvalue['selector_item_mobile']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Item.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_container_mobile'])) {
                if (Tools::strlen($formvalue['selector_container_mobile']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Container.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_next_mobile'])) {
                if (Tools::strlen($formvalue['selector_next_mobile']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Next.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            /* Knowband validation start */
            if (isset($formvalue['selector_pagination_mobile'])) {
                if (Tools::strlen($formvalue['selector_pagination_mobile']) > 1000) {
                    $this->context->controller->errors[] = $this->l('Maximum 1000 characters allowed at Selector Pagination.');
                    $error = 1;
                }
            }
            /* Knowband validation end */

            if ($error == 0) {
                /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
                Configuration::updateValue('infinite_scroll_custom_js', htmlentities($formvalue['custom_js']), true);
                Configuration::updateValue('infinite_scroll_custom_css', htmlentities($formvalue['custom_css']), true);
                unset($formvalue['custom_js']);
                unset($formvalue['custom_css']);
                $content = serialize($formvalue);
                
                Configuration::updateValue('infinite_scroll', $content, true);
                
                $output .= $this->displayConfirmation($this->l('Configuration has been saved successfully.'));
            }
            $this->my_module_settings = $formvalue;
        }

        $action = AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $this->context->controller->addJs($this->_path . 'views/js/velovalidation.js');
        if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
            $version = 5;
            $this->context->controller->addJs($this->_path . 'views/js/admin/infinitescroll5.js');
        } else {
            $version = 6;
            $this->context->controller->addJs($this->_path . 'views/js/admin/infinitescroll.js');
        }
        $this->context->controller->addJs($this->_path . 'views/js/admin/jscolor.js');

        $this->context->controller->addCSS($this->_path . 'views/css/admin/infinitescroll.css');

        $this->available_tabs_lang = array(
            'General_Settings' => $this->l('General Settings'),
            'Advance_Settings' => $this->l('Advance Settings'),
            'Display_Settings' => $this->l('Display Settings'),
            'Selector_Settings' => $this->l('Selector Settings'),
        );

        $this->available_tabs = array('General_Settings', 'Advance_Settings', 'Display_Settings', 'Selector_Settings');

        $this->tab_display = 'General_Settings';
        $product_tabs = array();

        foreach ($this->available_tabs as $product_tab) {
            $product_tabs[$product_tab] = array(
                'id' => $product_tab,
                'selected' => (Tools::strtolower($product_tab) == Tools::strtolower($this->tab_display) ||
                (isset($this->tab_display_module) && 'module' .
                $this->tab_display_module == Tools::strtolower($product_tab))),
                'name' => $this->available_tabs_lang[$product_tab],
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            );
        }

        $this->context->smarty->assign('show_toolbar', false);

        $options_scroll = array(
            array(
                'id_scroll_type' => '0',
                'name' => $this->l('Infinite Scroll')
            ),
            array(
                'id_scroll_type' => '1',
                'name' => $this->l('Load More Products Link')
            ),
        );

//        $options_layout = array(
//            array(
//                'id_layout_type' => '0',
//                'name' => $this->l('Grid')
//            ),
//            array(
//                'id_layout_type' => '1',
//                'name' => $this->l('List')
//            ),
//        );

        $file = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'infinitescroll/views/templates/admin/_configure/helpers/view/note.tpl'
        );

        if ($version == 5) {
            $add_ip_suffix = '';
            $enable_disable = array(
                'label' => $this->l('Enable/Disable'),
                'type' => 'radio',
                'hint' => $this->l('Enable/Disable this plugin'),
                'class' => 'optn_general t',
                'name' => 'infinite_scroll[enable]',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_end_page_message = array(
                'label' => $this->l('Display End Page Message'),
                'type' => 'radio',
                'hint' => $this->l('This setting enables the module to display a message when all the products are loaded'),
                'class' => 'optn_advance t',
                'name' => 'infinite_scroll[display_end_message]',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_top_link = array(
                'label' => $this->l('Display Go To Top Link'),
                'type' => 'radio',
                'hint' => $this->l('Displays a link at the bottom right of the page to scroll the customer back to the top of the page'),
                'class' => 'optn_advance t',
                'name' => 'infinite_scroll[display_top_link]',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_loading_message = array(
                'label' => $this->l('Display Loader While Loading'),
                'type' => 'radio',
                'hint' => $this->l('Displays a loader when page is loading'),
                'class' => 'optn_advance t',
                'name' => 'infinite_scroll[display_loading_message]',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $enable_sandbox_setting = array(
                'label' => $this->l('Sandbox Setting'),
                'type' => 'radio',
                'hint' => $this->l('Enables the test mode for the module'),
                'class' => 'optn_advance t',
                'name' => 'infinite_scroll[enable_sandbox_setting]',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
        } else {
            $add_ip_suffix = $this->l('+ Add Ip');
            $enable_disable = array(
                'label' => $this->l('Enable/Disable'),
                'type' => 'switch',
                'hint' => $this->l('Enable/Disable this plugin'),
                'class' => 'optn_general',
                'name' => 'infinite_scroll[enable]',
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_end_page_message = array(
                'label' => $this->l('Display End Page Message'),
                'type' => 'switch',
                'hint' => $this->l('This setting enables the module to display a message when all the products are loaded'),
                'class' => 'optn_advance',
                'name' => 'infinite_scroll[display_end_message]',
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_top_link = array(
                'label' => $this->l('Display Go To Top Link'),
                'type' => 'switch',
                'hint' => $this->l('Displays a link at the bottom right of the page to scroll the customer back to the top of the page'),
                'class' => 'optn_advance',
                'name' => 'infinite_scroll[display_top_link]',
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $display_loading_message = array(
                'label' => $this->l('Display Loader While Loading'),
                'type' => 'switch',
                'hint' => $this->l('Displays a loader when page is loading'),
                'class' => 'optn_advance',
                'name' => 'infinite_scroll[display_loading_message]',
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
            $enable_sandbox_setting = array(
                'label' => $this->l('Sandbox Setting'),
                'type' => 'switch',
                'hint' => $this->l('Enables the test mode for the module'),
                'class' => 'optn_advance',
                'name' => 'infinite_scroll[enable_sandbox_setting]',
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    ),
                ),
            );
        }

        if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
            $selector_mobile = array(
                'type' => 'text',
                'label' => $this->l('Selector Item Mobile'),
                'name' => 'infinite_scroll[selector_item_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the item for mobile theme'),
                'size' => 100,
            );
            $selector_container_mobile = array(
                'type' => 'text',
                'label' => $this->l('Selector Container Mobile'),
                'name' => 'infinite_scroll[selector_container_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l(
                    'Enter the selector name of the '
                    . 'container of products for mobile theme'
                ),
                'size' => 100,
            );
            $selector_next_mobile = array(
                'type' => 'text',
                'label' => $this->l('Selector Next Mobile'),
                'name' => 'infinite_scroll[selector_next_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the link containing next page href for mobile theme'),
                'size' => 100,
            );
            $selector_pagination_mobile = array(
                'type' => 'text',
                'label' => $this->l('Selector Pagination Mobile'),
                'name' => 'infinite_scroll[selector_pagination_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the div containing pagination block for mobile theme'),
                'size' => 100,
            );
        } else {
            $selector_mobile = array(
                'type' => 'hidden',
                'label' => $this->l('Selector Item Mobile'),
                'name' => 'infinite_scroll[selector_item_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the item for mobile theme'),
                'size' => 100,
            );
            $selector_container_mobile = array(
                'type' => 'hidden',
                'label' => $this->l('Selector Container Mobile'),
                'name' => 'infinite_scroll[selector_container_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l(
                    'Enter the selector name of the '
                    . 'container of products for mobile theme'
                ),
                'size' => 100,
            );
            $selector_next_mobile = array(
                'type' => 'hidden',
                'label' => $this->l('Selector Next Mobile'),
                'name' => 'infinite_scroll[selector_next_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the link containing next page href for mobile theme'),
                'size' => 100,
            );
            $selector_pagination_mobile = array(
                'type' => 'hidden',
                'label' => $this->l('Selector Pagination Mobile'),
                'name' => 'infinite_scroll[selector_pagination_mobile]',
                'class' => 'optn_selector',
                'hint' => $this->l('Enter the selector name of the div containing pagination block for mobile theme'),
                'size' => 100,
            );
        }

        $this->fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General Settings'),
                ),
                'input' => array(
                    $enable_disable,
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom CSS'),
                        'name' => 'infinite_scroll[custom_css]',
                        'hint' => $this->l('Enter the CSS to customize your module'),
                        'class' => 'optn_general vss-textarea',
                        'cols' => 100,
                        'rows' => 5
                    ),
                    /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Custom JS'),
                        'name' => 'infinite_scroll[custom_js]',
                        'hint' => $this->l('Enter the JS to customize your module'),
                        'class' => 'optn_general vss-textarea',
                        'cols' => 100,
                        'rows' => 5
                    ),
                    $display_end_page_message,
                    $display_top_link,
                    $display_loading_message,
                    array(
                        'type' => 'select',
                        'label' => $this->l('Scroll Type'),
                        'name' => 'infinite_scroll[scroll_type]',
                        'onchange' => 'getscrolltype(this)',
                        'hint' => $this->l('Select the scroll type which you want to use'),
                        'class' => 'optn_advance',
                        'is_bool' => true,
                        'options' => array(
                            'query' => $options_scroll,
                            'id' => 'id_scroll_type',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Show Load More Link After'),
                        'name' => 'infinite_scroll[load_more_link_frequency]',
                        'hint' => $this->l(
                            'This is the frequency according '
                            . 'to which load more link will be shown'
                        ),
                        'suffix' => $this->l('pages'),
                        'class' => 'optn_advance',
                        'required' => true
                    ),
                    $enable_sandbox_setting,
                    array(
                        'type' => 'text',
                        'label' => $this->l('Add IP'),
                        'name' => 'infinite_scroll[add_ip]',
                        'hint' => $this->l('The module will work only for those IP which are entered in this field if sandbox setting is enabled'),
                        'class' => 'optn_advance',
                        'suffix' => $add_ip_suffix,
                        'desc' => $this->l('The IP which will be added in this field should be comma(,) seprated'),
                        'size' => 100,
                        'required' => true
                    ),
//                    array(
//                        'type' => 'select',
//                        'label' => $this->l('Select Layout Type'),
//                        'name' => 'infinite_scroll[layout_type]',
//                        'hint' => $this->l('Select the layout type which you want to display in front'),
//                        'class' => 'optn_display',
//                        'is_bool' => true,
//                        'options' => array(
//                            'query' => $options_layout,
//                            'id' => 'id_layout_type',
//                            'name' => 'name',
//                        ),
//                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Background Color of Message Box'),
                        'name' => 'infinite_scroll[background_color]',
                        'class' => 'optn_display color_field',
                        'hint' => $this->l('This is the background color of your message box.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Text Color of Message Box'),
                        'name' => 'infinite_scroll[text_color]',
                        'class' => 'optn_display color_field',
                        'hint' => $this->l('This is the text color of your message box.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Border Color of Message Box'),
                        'name' => 'infinite_scroll[border_color]',
                        'class' => 'optn_display color_field',
                        'hint' => $this->l('This is the border color of your message box.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Background Color Of Top Link'),
                        'name' => 'infinite_scroll[background_color_top_link]',
                        'class' => 'optn_display color_field',
                        'hint' => $this->l('This is the background color of top link.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Selector Item'),
                        'name' => 'infinite_scroll[selector_item]',
                        'class' => 'optn_selector',
                        'hint' => $this->l('Enter the selector name of the item'),
                        'size' => 100,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Selector Container'),
                        'name' => 'infinite_scroll[selector_container]',
                        'class' => 'optn_selector',
                        'hint' => $this->l('Enter the selector name of the container of products'),
                        'size' => 100,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Selector Next'),
                        'name' => 'infinite_scroll[selector_next]',
                        'class' => 'optn_selector',
                        'hint' => $this->l('Enter the selector name of the link containing next page href'),
                        'size' => 100,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Selector Pagination'),
                        'name' => 'infinite_scroll[selector_pagination]',
                        'class' => 'optn_selector',
                        'hint' => $this->l('Enter the selector name of the div containing pagination block'),
                        'size' => 100,
                    ),
                    $selector_mobile,
                    $selector_container_mobile,
                    $selector_next_mobile,
                    $selector_pagination_mobile,
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right validation_infinite_scroll vss_save_button5'
                ),
                'desc' => array(
                    'text' => $file,
                ),
            ),
        );

        $field_value = array(
            'infinite_scroll[enable]' => $this->my_module_settings['enable'],
            'infinite_scroll[custom_css]' => html_entity_decode(Configuration::get('infinite_scroll_custom_css')),
            /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
            'infinite_scroll[custom_js]' => html_entity_decode(Configuration::get('infinite_scroll_custom_js')),
            'infinite_scroll[display_end_message]' => $this->my_module_settings['display_end_message'],
            'infinite_scroll[display_top_link]' => $this->my_module_settings['display_top_link'],
            'infinite_scroll[display_loading_message]' => $this->my_module_settings['display_loading_message'],
            'infinite_scroll[scroll_type]' => $this->my_module_settings['scroll_type'],
            'infinite_scroll[load_more_link_frequency]' => $this->my_module_settings['load_more_link_frequency'],
            'infinite_scroll[enable_sandbox_setting]' => $this->my_module_settings['enable_sandbox_setting'],
            'infinite_scroll[add_ip]' => $this->my_module_settings['add_ip'],
//            'infinite_scroll[layout_type]' => $this->my_module_settings['layout_type'],
            'infinite_scroll[background_color]' => $this->my_module_settings['background_color'],
            'infinite_scroll[text_color]' => $this->my_module_settings['text_color'],
            'infinite_scroll[border_color]' => $this->my_module_settings['border_color'],
            'infinite_scroll[background_color_top_link]' => $this->my_module_settings['background_color_top_link'],
            'infinite_scroll[selector_item]' => $this->my_module_settings['selector_item'],
            'infinite_scroll[selector_container]' => $this->my_module_settings['selector_container'],
            'infinite_scroll[selector_next]' => $this->my_module_settings['selector_next'],
            'infinite_scroll[selector_pagination]' => $this->my_module_settings['selector_pagination'],
        );
        if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
            $field_value_1 = array(
                'infinite_scroll[selector_item_mobile]' => $this->my_module_settings['selector_item_mobile'],
                'infinite_scroll[selector_container_mobile]' => $this->my_module_settings['selector_container_mobile'],
                'infinite_scroll[selector_next_mobile]' => $this->my_module_settings['selector_next_mobile'],
                'infinite_scroll[selector_pagination_mobile]' =>
                $this->my_module_settings['selector_pagination_mobile'],
            );
        } else {
            $field_value_1 = array(
                'infinite_scroll[selector_item_mobile]' => '',
                'infinite_scroll[selector_container_mobile]' => '',
                'infinite_scroll[selector_next_mobile]' => '',
                'infinite_scroll[selector_pagination_mobile]' => '',
            );
        }
        $field_value = array_merge($field_value, $field_value_1);
        $languages = Language::getLanguages();
        foreach ($languages as $k => $language) {
            $languages[$k]['is_default'] = ((int) ($language['id_lang'] == $this->context->language->id));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->table = 'configuration';
        $helper->fields_value = $field_value;
        $helper->name_controller = $this->name;
        $helper->languages = $languages;
        $helper->default_form_language = $this->context->language->id;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->show_cancel_button = false;
        $helper->submit_action = $action;
        $form = $helper->generateForm(array($this->fields_form));

        $helper = new HelperView();
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->current = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->override_folder = 'helpers/';
        $helper->base_folder = 'view/';
        $helper->base_tpl = 'view_custom.tpl';

        $config = Configuration::get('infinite_scroll');
        $this->my_module_settings = Tools::unSerialize($config);

        $this->context->smarty->assign('background_color', $this->my_module_settings['background_color']);
        $this->context->smarty->assign('border_color', $this->my_module_settings['border_color']);
        $this->context->smarty->assign('text_color', $this->my_module_settings['text_color']);
        $this->context->smarty->assign(
            'background_color_top_link',
            $this->my_module_settings['background_color_top_link']
        );
        $this->context->smarty->assign('img_path', $this->getPath() . 'infinitescroll/views/img/front/');
        $this->context->smarty->assign('version', $version);

        $view = $helper->generateView();

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        } else {
            $custom_ssl_var = 0;
        }

        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $ps_base_url = _PS_BASE_URL_SSL_;
        } else {
            $ps_base_url = _PS_BASE_URL_;
        }

        $this->context->smarty->assign('form', $form);
        $this->context->smarty->assign('view', $view);
        $this->context->smarty->assign(
            'path',
            $ps_base_url . __PS_BASE_URI__ . str_replace(_PS_ROOT_DIR_ . '/', '', _PS_MODULE_DIR_) . $this->name . '/'
        );
        $this->context->smarty->assign('product_tabs', $product_tabs);
        $this->context->smarty->assign('firstCall', false);
        $this->context->smarty->assign('general_settings', $this->l('General Settings'));
        $this->context->smarty->assign('advance_settings', $this->l('Advance Settings'));
        $this->context->smarty->assign('display_settings', $this->l('Display Settings'));
        $this->context->smarty->assign('selector_settings', $this->l('Selector Settings'));
        $this->context->smarty->assign('tab', $this->l($this->tab_display));
        $this->context->smarty->assign('version', $version);
        $this->context->smarty->assign('display_end_page_message', $this->my_module_settings['display_end_message']);
        $this->context->smarty->assign(
            'display_loading_message',
            $this->my_module_settings['display_loading_message']
        );
        $this->context->smarty->assign('enable_sandbox_setting', $this->my_module_settings['enable_sandbox_setting']);
        $this->context->smarty->assign('scroll_type', $this->my_module_settings['scroll_type']);
        $this->context->smarty->assign('my_ip_address', Tools::getRemoteAddr());

        $tpl = 'Form_custom.tpl';
        $helper = new Helper();
        $helper->module = $this;
        $helper->override_folder = 'helpers/';
        $helper->base_folder = 'form/';
        $helper->setTpl($tpl);
        $tpl = $helper->generate();

        $output = $output . $tpl;
        return $output;
    }

    private function getDefaultSettings()
    {
        $settings = array(
            'enable' => 0,
            'scroll_type' => 1,
            'load_more_link_frequency' => 1,
            'display_top_link' => 1,
            'display_end_message' => 1,
            'display_loading_message' => 1,
            'enable_sandbox_setting' => 0,
            'add_ip' => '',
//            'layout_type' => 0,
            'border_color' => '#cccccc',
            'background_color' => '#F4F4F4',
            'text_color' => '#636363',
            'background_color_top_link' => '#D8D8D8',
            'selector_item' => 'article.product-miniature',
            'selector_container' => '#js-product-list',
            'selector_next' => 'nav.pagination a.next',
            'selector_pagination' => 'nav.pagination',
        );
        if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
            $settings['selector_pagination'] = '#pagination, #pagination_bottom';
            $settings['selector_item_mobile'] = 'li.product-list-row';
            $settings['selector_container_mobile'] = '#category-list';
            $settings['selector_next_mobile'] = '.pagination_next';
            $settings['selector_pagination_mobile'] = '.pagination_mobile';
        }

        return $settings;
    }

    public function getPath()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        } else {
            $custom_ssl_var = 0;
        }
        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $module_dir = _PS_BASE_URL_SSL_ . __PS_BASE_URI__ . str_replace(_PS_ROOT_DIR_ . '/', '', _PS_MODULE_DIR_);
        } else {
            $module_dir = _PS_BASE_URL_ . __PS_BASE_URI__ . str_replace(_PS_ROOT_DIR_ . '/', '', _PS_MODULE_DIR_);
        }

        return $module_dir;
    }

    public function getTemplate()
    {
        $config = Configuration::get('infinite_scroll');
        $this->my_module_settings = Tools::unSerialize($config);
        $test_ips = explode(',', $this->my_module_settings['add_ip']);
        $my_ip = Tools::getRemoteAddr();
        if (in_array($my_ip, $test_ips)) {
            $test_mode = true;
        } else {
            $test_mode = false;
        }
        $flag = true;
        if ($this->my_module_settings['enable_sandbox_setting'] == 1 && $test_mode) {
            $flag = true;
        } elseif ($this->my_module_settings['enable_sandbox_setting'] == 1 && !$test_mode) {
            $flag = false;
        }
        if ($flag) {
            $this->context->smarty->assign('display_end_message', $this->my_module_settings['display_end_message']);
            $this->context->smarty->assign(
                'display_loading_message',
                $this->my_module_settings['display_loading_message']
            );
            $this->context->smarty->assign('scroll_type', $this->my_module_settings['scroll_type']);
            $this->context->smarty->assign(
                'load_more_link_frequency',
                $this->my_module_settings['load_more_link_frequency']
            );
//            $this->context->smarty->assign('layout_type', $this->my_module_settings['layout_type']);
            $this->context->smarty->assign('display_top_link', $this->my_module_settings['display_top_link']);
            $this->context->smarty->assign(
                'background_color_top_link',
                $this->my_module_settings['background_color_top_link']
            );
            $this->context->smarty->assign('img_path', $this->getPath() . 'infinitescroll/views/img/front/');
            $this->context->smarty->assign(
                'background_color_message_box',
                $this->my_module_settings['background_color']
            );
            $this->context->smarty->assign('text_color_message_box', $this->my_module_settings['text_color']);
            $this->context->smarty->assign('border_color_message_box', $this->my_module_settings['border_color']);
            $this->context->smarty->assign('custom_css', html_entity_decode(Configuration::get('infinite_scroll_custom_css')));
            /* Ujjwal Joshi made changes on 18-Aug-2017 to add Custom JS Field */
            $this->context->smarty->assign('custom_js', html_entity_decode(Configuration::get('infinite_scroll_custom_js')));
            if (version_compare(_PS_VERSION_, '1.6.0.1', '<')) {
                $version = 5;
                $this->context->smarty->assign('version', 5);
            } else {
                $version = 6;
                $this->context->smarty->assign('version', 6);
            }
            $this->mobile = new Mobile_Detect();
            if ($this->mobile->isMobile()) {
                $ismobile = 1;
            } else {
                $ismobile = 0;
            }
            if ($ismobile == 1 && $version == 5) {
                $this->context->smarty->assign('selector_item', $this->my_module_settings['selector_item_mobile']);
                $this->context->smarty->assign(
                    'selector_container',
                    $this->my_module_settings['selector_container_mobile']
                );
                $this->context->smarty->assign('selector_next', $this->my_module_settings['selector_next_mobile']);
                $this->context->smarty->assign(
                    'selector_pagination',
                    $this->my_module_settings['selector_pagination_mobile']
                );
            } else {
                $this->context->smarty->assign('selector_item', $this->my_module_settings['selector_item']);
                $this->context->smarty->assign('selector_container', $this->my_module_settings['selector_container']);
                $this->context->smarty->assign('selector_next', $this->my_module_settings['selector_next']);
                $this->context->smarty->assign('selector_pagination', $this->my_module_settings['selector_pagination']);
            }
            $this->context->controller->addJs($this->_path . 'views/js/velovalidation.js');
            $this->context->smarty->assign('ismobile', $ismobile);
            $this->context->controller->addJs($this->_path . 'views/js/front/jquery-ias.min.js');
            $this->context->controller->addJs($this->_path . 'views/js/front/infinitescroll.js');
            
            $this->context->controller->addCSS($this->_path . 'views/css/front/infinitescroll.css');
            return $this->display(__FILE__, 'views/templates/front/front.tpl');
        }
    }

    public function hookdisplaymobileheader()
    {
        if (Module::isInstalled('infinitescroll')) {
            $config = Configuration::get('infinite_scroll');
            $this->my_module_settings = Tools::unSerialize($config);
            if (isset($this->my_module_settings['enable']) && $this->my_module_settings['enable'] == 1) {
                return $this->getTemplate();
            }
        }
    }

    public function hookdisplayheader()
    {
        if (Module::isInstalled('infinitescroll')) {
            $config = Configuration::get('infinite_scroll');
            $this->my_module_settings = Tools::unSerialize($config);
            if (isset($this->my_module_settings['enable']) && $this->my_module_settings['enable'] == 1) {
                return $this->getTemplate();
            }
        }
    }
}
