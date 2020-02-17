<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

class PopupClass extends ObjectModel
{
    public $name = 'popup';
    public $id;
    public $id_prestablog_popup;
    public $date_start;
    public $date_stop;
    public $height = 460;
    public $width = 700;
    public $delay = 500;
    public $expire = 1;
    public $expire_ratio = 86400;
    public $theme = 'colorpicker';
    public $actif = 1;
    public $content;
    public $title;
    public $restriction_rules = 0;
    public $restriction_pages;
    public $footer = 1;
    public $pop_colorpicker_content;
    public $pop_colorpicker_modal;
    public $pop_colorpicker_btn;
    public $pop_colorpicker_btn_border;
    public $pop_opacity_content = 1;
    public $pop_opacity_modal= 1;
    public $pop_opacity_btn= 1;
    public $actif_home = 0;


    protected $table = 'prestablog_popup';
    protected $identifier = 'id_prestablog_popup';

    public static $definition = array(
        'table' => 'prestablog_popup',
        'primary' => 'id_prestablog_popup',
        'multilang' => true,
        'fields' => array(
            'date_start' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
            'date_stop' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
            'height' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'width' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'delay' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'expire' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'expire_ratio' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'theme' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'size' => 255
            ),
            'restriction_rules' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isunsignedInt',
                'required' => true
            ),
            'restriction_pages' => array('type' => self::TYPE_STRING,  'validate' => 'isString'),
            'footer' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'actif' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'content' => array(
                'type' => self::TYPE_HTML,
                'validate' =>
                'isString',
                'required' => true,
                'lang' => true
            ),
            'title' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'lang' => true ,
                'size' => 255
            ),
            'pop_colorpicker_content' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_colorpicker_modal' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_colorpicker_btn' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_colorpicker_btn_border' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_opacity_content' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_opacity_modal' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'pop_opacity_btn' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => false ,
                'size' => 255
            ),
            'actif_home' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true)

        )
    );

    public function copyFromPost()
    {
        $object = $this;
        $table = $this->table;
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $object) && $key != 'id_'.$table) {
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = Tools::getValue($key);
            }
        }
        // Multilingual fields
        $rules = call_user_func(array(get_class($object), 'getValidationRules'), get_class($object));
        if (count($rules['validateLang'])) {
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (Tools::getIsset($field.'_'.(int)$language['id_lang'])) {
                        $object->{$field}[(int)$language['id_lang']] = Tools::getValue(
                            $field.'_'.(int)$language['id_lang']
                        );
                    }
                }
            }
        }
    }
    public static function popupContent($params)
    {
        return $params['return'];
    }
    public function update($null_values = false)
    {
        $id = (int)$this->id_prestablog_popup;
        // EDIT FOR SHOP
        $liste_shop = array();
        $check_shop = array();
        foreach (self::getListeIdShop((int)$id) as $value) {
            $liste_shop[$value['id_shop']] = $value['id_shop'];
        }
        if (!$check_shop = Tools::getValue('checkBoxShopAsso_prestablog')) {
            $check_shop = array(Configuration::get('PS_SHOP_DEFAULT'));
        }
        if (empty($check_shop)) {
            return false;
        }
        // a supp
        foreach (array_diff($liste_shop, $check_shop) as $id_shop) {
            if (!self::delAssoShop((int)$id, (int)$id_shop)) {
                return false;
            }
        }
        // a add
        foreach (array_diff($check_shop, $liste_shop) as $id_shop) {
            if (!self::addAssoShop((int)$id, (int)$id_shop)) {
                return false;
            }
        }
        // EDIT FOR SHOP

        // EDIT FOR GROUP
        $liste_group = array();
        $check_group = array();
        foreach (self::getListeIdGroup((int)$id) as $value) {
            $liste_group[$value['id_group']] = $value['id_group'];
        }
        if (!$check_group = Tools::getValue('groupBox')) {
            $check_group = array();
        }
        if (empty($check_group)) {
            return false;
        }
        // a supp
        foreach (array_diff($liste_group, $check_group) as $id_group) {
            if (!self::delAssoGroup((int)$id, (int)$id_group)) {
                return false;
            }
        }
        // a add
        foreach (array_diff($check_group, $liste_group) as $id_group) {
            if (!self::addAssoGroup((int)$id, (int)$id_group)) {
                return false;
            }
        }
        // EDIT FOR GROUP
        return parent::update($null_values);
    }

    public function add($autodate = true, $null_values = false)
    {

        parent::add($autodate, $null_values);
        $id = $this->id;
        // EDIT FOR SHOP
        $liste_shop = array();
        $check_shop = array();
        foreach (self::getListeIdShop((int)$id) as $value) {
            $liste_shop[$value['id_shop']] = $value['id_shop'];
        }
        if (!$check_shop = Tools::getValue('checkBoxShopAsso_prestablog')) {
            $check_shop = array(Configuration::get('PS_SHOP_DEFAULT'));
        }
        if (empty($check_shop)) {
            return false;
        }
        // a supp
        foreach (array_diff($liste_shop, $check_shop) as $id_shop) {
            if (!self::delAssoShop((int)$id, (int)$id_shop)) {
                return false;
            }
        }
        // a add
        foreach (array_diff($check_shop, $liste_shop) as $id_shop) {
            if (!self::addAssoShop((int)$id, (int)$id_shop)) {
                return false;
            }
        }
        // EDIT FOR SHOP

        // EDIT FOR GROUP
        $liste_group = array();
        $check_group = array();
        foreach (self::getListeIdGroup((int)$id) as $value) {
            $liste_group[$value['id_group']] = $value['id_group'];
        }
        if (!$check_group = Tools::getValue('groupBox')) {
            $check_group = array();
        }
        if (empty($check_group)) {
            return false;
        }
        // a supp
        foreach (array_diff($liste_group, $check_group) as $id_group) {
            if (!self::delAssoGroup((int)$id, (int)$id_group)) {
                return false;
            }
        }
        // a add
        foreach (array_diff($check_group, $liste_group) as $id_group) {
            if (!self::addAssoGroup((int)$id, (int)$id_group)) {
                return false;
            }
        }
        // EDIT FOR GROUP
        return true;
    }

    public function deletepopup()
    {

        if (parent::delete()) {
            if (!Db::getInstance()->Execute('
                DELETE ps FROM `'._DB_PREFIX_.'prestablog_popup_shop` AS ps
                WHERE ps.`id_prestablog_popup` = '.(int)$this->id)) {
                return false;
            }
            if (!Db::getInstance()->Execute('
                DELETE pg FROM `'._DB_PREFIX_.'prestablog_popup_group` AS pg
                WHERE pg.`id_prestablog_popup` = '.(int)$this->id)) {
                return false;
            }
            if (!Db::getInstance()->Execute('
                DELETE pp FROM `'._DB_PREFIX_.'prestablog_news_popuplink` AS pp
                WHERE pp.`id_prestablog_popup` = '.(int)$this->id)) {
                return false;
            }
            if (!Db::getInstance()->Execute('
                DELETE pc FROM `'._DB_PREFIX_.'prestablog_categorie_popuplink` AS pc
                WHERE pc.`id_prestablog_popup` = '.(int)$this->id)) {
                return false;
            }
            return true;
        }
    }

    public static function getListeIdShop($id)
    {
        return Db::getInstance()->ExecuteS('
            SELECT id_shop
            FROM `'._DB_PREFIX_.'prestablog_popup_shop`
            WHERE    `id_prestablog_popup`='.(int)$id);
    }

    public static function getListePopup($lid)
    {
        return Db::getInstance()->ExecuteS('
            SELECT p.`id_prestablog_popup`, pl.`title`
            FROM `'._DB_PREFIX_.'prestablog_popup` p
            LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_lang` pl ON (p.`id_prestablog_popup` = pl.`id_prestablog_popup`)
            WHERE `id_lang` = '.$lid);
    }

    public static function getListeIdGroup($id_prestablog_popup)
    {
        return Db::getInstance()->ExecuteS('
            SELECT fg.id_group
            FROM `'._DB_PREFIX_.'prestablog_popup_group` as fg
            WHERE    fg.`id_prestablog_popup`='.(int)$id_prestablog_popup);
    }

    public static function addAssoShop($id, $id_shop)
    {
        return Db::getInstance()->Execute('
            INSERT INTO `'._DB_PREFIX_.'prestablog_popup_shop` (
                `id_prestablog_popup`,
                `id_shop`
            )
            VALUES (
                '.(int)$id.',
                '.(int)$id_shop.'
            );');
    }

    public static function addAssoGroup($id, $id_group)
    {
        return Db::getInstance()->Execute('
            INSERT INTO `'._DB_PREFIX_.'prestablog_popup_group` (
                `id_prestablog_popup`,
                `id_group`
            )
            VALUES (
                '.(int)$id.',
                '.(int)$id_group.'
            );');
    }

    public static function delAssoShop($id, $id_shop)
    {
        if (!Db::getInstance()->Execute('
            DELETE   fs FROM
                `'._DB_PREFIX_.'prestablog_popup_shop` AS fs
            WHERE    fs.`id_prestablog_popup` = '.(int)$id.'
                AND   fs.`id_shop` = '.(int)$id_shop)) {
            return false;
        }
        return true;
    }
    public static function getPopupActifHome() {

              $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT `id_prestablog_popup`
            FROM `'._DB_PREFIX_.'prestablog_popup`
        WHERE `actif_home` = 1');

                    if (PrestaBlog::isNotRestrictionHome()) {
        return $return1;
    }
    return false;
}
 public static function getIdPopupActifHome() {

              $return1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT `id_prestablog_popup`
            FROM `'._DB_PREFIX_.'prestablog_popup`
        WHERE `actif_home` = 1');

        return $return1;

}
    public static function delAssoGroup($id, $id_group)
    {
        if (!Db::getInstance()->Execute('
            DELETE fg FROM
                `'._DB_PREFIX_.'prestablog_popup_group` AS fg
            WHERE    fg.`id_prestablog_popup` = '.(int)$id.'
                AND   fg.`id_group` = '.(int)$id_group)) {
            return false;
        }
        return true;
    }

    public function changeState($id)
    {
        return Db::getInstance()->Execute('
            UPDATE `'._DB_PREFIX_.'prestablog_popup`
            SET `actif`=CASE `actif` WHEN 1 THEN 0 WHEN 0 THEN 1 END
            WHERE `id_prestablog_popup`='.(int)$id);
    }

    public static function updateValuePopuphome($id)
    {

        return Db::getInstance()->Execute('
            UPDATE `'._DB_PREFIX_.'prestablog_popup`
            SET `actif_home`=CASE `actif_home` WHEN 1 THEN 0 WHEN 0 THEN 1 END
            WHERE `id_prestablog_popup`='.(int)$id);
    }
public static function deleteAllValue($id)
    {

        return Db::getInstance()->Execute('
            UPDATE `'._DB_PREFIX_.'prestablog_popup`
            SET `actif_home`= 0
            WHERE `id_prestablog_popup` !='.(int)$id);
    }
    public function displayList()
    {
        $context = Context::getContext();
        $definition_lang = $this->definitionLang();
        $content_list = self::getListContent((int)$context->language->id, (int)$context->shop->id);
        	$fields_list = array(
            'id_prestablog_popup' => array(
                'title' => $this->l('Id'),
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'title' => array(
                'title' => $this->l('Title'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'class' => 'list-strong'
            ),
            'date_start' => array(
                'title' => $this->l('Date start'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'align' => 'center',
                'badge_warning' => true
            ),
            'date_stop' => array(
                'title' => $this->l('Date stop'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'align' => 'center',
                'badge_danger' => true,
                'badge_success' => true
            ),
            'verbose_interval' => array(
                'title' => $this->l('Duration'),
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'theme' => array(
                'title' => $this->l('Theme'),
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'actif' => array(
                'title' => $this->l('Status'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center'
            )
        	);
        	$helper = new HelperList();

        	$helper->shopLinkType = '';
        	$helper->simple_header = false;
        	$helper->identifier = 'id_prestablog_popup';
        	//$helper->actions = array('edit', 'delete', 'duplicate');
        	$helper->actions = array('edit', 'delete');
        	$helper->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        	);
        	$helper->show_toolbar = true;
        	$helper->imageType = 'jpg';
        	$tk = Tools::getAdminTokenLite('AdminModules');
        	$ppc = AdminController::$currentIndex.'&configure=prestablog';
        	$helper->toolbar_btn['new'] = array(
            'href' => $ppc.'&add'.$this->name.'&token='.$tk.'&class=PopupClass',
            'desc' => $this->l('Add new')
        	);
        	$helper->title = $definition_lang['tabListName'];
        	$helper->table = $this->name;
        	$helper->module = $this;
        	//$helper->listTotal = (int)sizeof($content_list);
        	$helper->listTotal = (int)count($content_list);
        	$helper->token = $tk;
        	$helper->currentIndex = $ppc.'&class=PopupClass';

                return $helper->generateList($content_list, $fields_list).$this->displayListInfo();

    }

    public static function imgPathFO()
    {
        return '../modules/prestablog/';
    }

    public function displayListInfo()
    {

        $html = '';
        $popup = new Prestablog();
        $html .= '
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <i class="icon-question-circle"></i>
                            '.$this->l('Legend').'
                        </div>
                        <div class="row">
                            <p><span class="badge badge-success">'.$this->l('The popup is running').'</span></p>
                            <p><span class="badge badge-danger">'.$this->l('The popup is terminated').'</span></p>
                            <p>
                                <span class="badge badge-warning">
                                    '.$this->l('The popup is waiting for running').'
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
             <div class="row">
             <div class="col-sm-6">
                    <div class="panel">
                                            <div class="panel-heading">
                            '.$this->l('Infos').'
                        </div>
                        <p style="color:red; font-size:15px; font-weight: bold;">
                        '.$this->l('In order to finalize the popup addition, please go to "configuration->global" and allow popup\'s display.').'
                        </p>
                        <p style="color:red;">
                        '.$this->l('If you have proceed to an upgrade, please go to appearance->positions->hook a module on the top right.').'
                        </p>
                        <p style="color:red;">
                        '.$this->l('Select prestablog, and link it to the hook "displaybeforebodyclosingtag".').'
                        </p>
                        </div>
</div>
</div>
             </div>';

            $html.= '<div class="clearfix"></div>'."\n";
         /*$layerslider = Module::getInstanceByName('layerslider');*/

        return $html;
    }

    public function displayForm($do = 'add')
    {

        $context = Context::getContext();
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $definition_lang = $this->definitionLang();
        $popup_preview_link = '';
        $popup_preview_content = '';
        $content_restriction_pages = '';
        $helper = new HelperForm();
        $helper->id = (int)Tools::getValue('id_prestablog_popup');
        $fields_value = array();
        if (!Tools::isSubmit('editpopup')) {
            $current_class = Tools::getValue('class');
            $object_model = PrestaBlog::createDynInstance($current_class, array((int)$helper->id));
            if ((int)$helper->id > 0) {
                $popup = new PrestaBlog();
                $popup_preview_link = '<div class="input-group">';
                foreach (Language::getLanguages(true) as $language) {
                    $popup_preview_link .= '
                        <a
                            class="btn btn-default"
                            href="#"
                            data-toggle="modal"
                            data-target="#popup-content-'.(int)$language['id_lang'].'"
                        >
                            <i class="icon-eye"></i> '.$language['iso_code'].'
                        </a>
                        '.$popup->displayPopup((int)$language['id_lang'], (int)$helper->id);
                }

                $popup_preview_link .= '</div>';
                if ($object_model->restriction_pages != '') {
                    $content_restriction_pages .= '<h4>'.$this->l('Listing of urls recorded').':</h4>'."\n";
                    $urls = preg_split("/\r/", $object_model->restriction_pages);
                    foreach ($urls as $url) {
                        $url = trim($url);
                        $content_restriction_pages .= '
                            <a
                                href="'.$url.'"
                                '.(
                                    !preg_match(
                                        '/^(http|https):\/\/*/i',
                                        $url
                                    ) ? 'style="color:red;text-decoration:line-through;"' : 'style="color:green;"'
                                ).'
                            >
                                '.$url.'
                            </a><br/>'."\n";
                    }
                    $content_restriction_pages = $popup->displayInfo($content_restriction_pages);
                }
            }
            $fields_value = array(
                'id_prestablog_popup' => (int)$object_model->id_prestablog_popup,
                'date_start' => $object_model->date_start,
                'date_stop' => $object_model->date_stop,
                'height' => (int)$object_model->height,
                'width' => (int)$object_model->width,
                'delay' => (int)$object_model->delay,
                'expire' => (int)$object_model->expire,
                'expire_ratio' => (int)$object_model->expire_ratio,
                'theme' => $object_model->theme,
                'restriction_rules' => (int)$object_model->restriction_rules,
                'restriction_pages' => $object_model->restriction_pages,
                'actif' => (bool)$object_model->actif,
                'footer' => (bool)$object_model->footer,
                'pop_colorpicker_content' => $object_model->pop_colorpicker_content,
                'pop_colorpicker_modal' => $object_model->pop_colorpicker_modal,
                'pop_colorpicker_btn' => $object_model->pop_colorpicker_btn,
                'pop_colorpicker_btn_border' => $object_model->pop_colorpicker_btn_border,
                'pop_opacity_content' => $object_model->pop_opacity_content,
                'pop_opacity_modal' => $object_model->pop_opacity_modal,
                'pop_opacity_btn' => $object_model->pop_opacity_btn
            );
            foreach (Language::getLanguages(false) as $lang) {
                $fields_value['content'][(int)$lang['id_lang']] = $object_model->content[(int)$lang['id_lang']];
                $fields_value['title'][(int)$lang['id_lang']] = $object_model->title[(int)$lang['id_lang']];
            }
            $groups = array();
            foreach (self::getListeIdGroup((int)$object_model->id_prestablog_popup) as $group) {
                $groups[] = $group['id_group'];
            }
            foreach (Group::getGroups($context->language->id) as $group) {
                $fields_value['groupBox_'.$group['id_group']] = (
                    in_array(
                        $group['id_group'],
                        $groups
                    ) ? $group['id_group'] : (
                        !Tools::getValue('id_prestablog_popup') && (int)$group['id_group'] == 1 ? true : false
                    ));
            }
             } else {
            $fields_value = array(
                'id_prestablog_popup' => (int)Tools::getValue('id_prestablog_popup'),
                'date_start' => Tools::getValue('date_start'),
                'date_stop' => Tools::getValue('date_stop'),
                'height' => (int)Tools::getValue('height'),
                'width' => (int)Tools::getValue('width'),
                'delay' => (int)Tools::getValue('delay'),
                'expire' => (int)Tools::getValue('expire'),
                'expire_ratio' => (int)Tools::getValue('expire_ratio'),
                'theme' => Tools::getValue('theme'),
                'restriction_rules' => (int)Tools::getValue('restriction_rules'),
                'restriction_pages' => Tools::getValue('restriction_pages'),
                'actif' => (bool)Tools::getValue('actif'),
                'footer' => (bool)Tools::getValue('footer'),
                'pop_colorpicker_content' => Tools::getValue('pop_colorpicker_content'),
                'pop_colorpicker_modal' => Tools::getValue('pop_colorpicker_modal'),
                'pop_colorpicker_btn' => Tools::getValue('pop_colorpicker_btn'),
                'pop_colorpicker_btn_border' => Tools::getValue('pop_colorpicker_btn_border'),
                'pop_opacity_content' => Tools::getValue('pop_opacity_content'),
                'pop_opacity_modal' => Tools::getValue('pop_opacity_modal'),
                'pop_opacity_btn' => Tools::getValue('pop_opacity_btn')
            );
            foreach (Language::getLanguages(false) as $lang) {
                $fields_value['content'][(int)$lang['id_lang']] = Tools::getValue(
                    'content_'.(int)$lang['id_lang'],
                    ''
                );
                $fields_value['title'][(int)$lang['id_lang']] = Tools::getValue(
                    'title_'.(int)$lang['id_lang'],
                    ''
                );
            }
                    foreach (Group::getGroups($context->language->id) as $group) {
                $fields_value['groupBox_'.$group['id_group']] = Tools::getValue('groupBox_'.$group['id_group']);
            }
        }

        $l01 = $this->l(
            'If the content does not live in height, the body of the popup will appear a vertical scroll.'
        );
        $ppc = AdminController::$currentIndex.'&configure=prestablog';
        $tk = Tools::getAdminTokenLite('AdminModules');

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => Tools::ucfirst($do).' '.$this->l('popup'),
                    'icon' => 'icon-edit',
                    'badge' => 'icon-edit'
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_prestablog_popup'
                    ),
                    array(
                        'type' => 'html',
                        'name' =>  $popup_preview_link
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'actif',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Theme'),
                        'id' => 'select_theme',
                        'name' => 'theme',
                        'options' => array(
                            'query' => PrestaBlog::scanThemeTpl(),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                        'desc' => $this->l('If you want a full customization of your popup, please select the "colorpicker" theme.')
                    ),
                    array(
                         'type' => 'text',
                                'label' => $this->l('Color picker background'),
                                'name' => 'pop_colorpicker_content',
                                'lang' => false,
                                'id'   => 'pop_colorpicker_content',
                                'data-hex' => true,
                                'class'   => 'mColorPicker',
                                'col' => '5',
                             ),
                    array(
                             	'label' => $this->l('Background opacity'),
                        	'type' => 'select',
                            'options' => array(
                                'query' => array(
                                 array(
                                        'id' => 0,
                                        'value' => 0,
                                        'name' => '0'
                                 ),
                                 array(
                                        'id' => 0.1,
                                        'value' => 0.1,
                                        'name' => '0.1'
                                 ),
                                 array(
                                        'id' => 0.2,
                                        'value' => 0.2,
                                        'name' => '0.2'
                                ),
                                array(
                                    'id' => 0.3,
                                    'value' => 0.3,
                                    'name' => '0.3'
                                ),
                                array(
                                    'id' => 0.4,
                                    'value' => 0.4,
                                    'name' => '0.4'
                                ),
                                array(
                                    'id' => 0.5,
                                    'value' => 0.5,
                                    'name' => '0.5'
                                ),
                                array(
                                    'id' => 0.6,
                                    'value' => 0.6,
                                    'name' => '0.6'
                                ),
                                array(
                                    'id' => 0.7,
                                    'value' => 0.7,
                                    'name' => '0.7'
                                ),
                                array(
                                    'id' => 0.8,
                                    'value' => 0.8,
                                    'name' => '0.8'
                                ),
                                array(
                                    'id' => 0.9,
                                    'value' => 0.9,
                                    'name' => '0.9'
                                ),
                                array(
                                    'id' => 1,
                                    'value' => 1,
                                    'name' => '1'
                                )
                            ),
                                'id' => 'id',
                            'name' => 'name'
                            ),
                        	'id'   => 'pop_opacity_content',
                        	'name' => 'pop_opacity_content',
                        	'col' => '3',
                        	'desc' => $this->l('0 is full transparent and 1 is full colored.')
                             ),
                    array(
                         'type' => 'text',
                                'label' => $this->l('Color picker content'),
                                'name' => 'pop_colorpicker_modal',
                                'lang' => false,
                                'id'   => 'pop_colorpicker_modal',
                                'data-hex' => true,
                                'class'   => 'mColorPicker',
                                'col' => '5'
                            ),
                            array(
                             	'label' => $this->l('Content opacity'),
                        	'type' => 'select',
                            'options' => array(
                                'query' => array(
                                 array(
                                        'id' => 0,
                                        'value' => 0,
                                        'name' => '0'
                                 ),
                                 array(
                                        'id' => 0.1,
                                        'value' => 0.1,
                                        'name' => '0.1'
                                 ),
                                 array(
                                        'id' => 0.2,
                                        'value' => 0.2,
                                        'name' => '0.2'
                                ),
                                array(
                                    'id' => 0.3,
                                    'value' => 0.3,
                                    'name' => '0.3'
                                ),
                                array(
                                    'id' => 0.4,
                                    'value' => 0.4,
                                    'name' => '0.4'
                                ),
                                array(
                                    'id' => 0.5,
                                    'value' => 0.5,
                                    'name' => '0.5'
                                ),
                                array(
                                    'id' => 0.6,
                                    'value' => 0.6,
                                    'name' => '0.6'
                                ),
                                array(
                                    'id' => 0.7,
                                    'value' => 0.7,
                                    'name' => '0.7'
                                ),
                                array(
                                    'id' => 0.8,
                                    'value' => 0.8,
                                    'name' => '0.8'
                                ),
                                array(
                                    'id' => 0.9,
                                    'value' => 0.9,
                                    'name' => '0.9'
                                ),
                                array(
                                    'id' => 1,
                                    'value' => 1,
                                    'name' => '1'
                                )
                            ),
                                'id' => 'id',
                            'name' => 'name'
                            ),
                        	'id'   => 'pop_opacity_modal',
                        	'name' => 'pop_opacity_modal',
                        	'col' => '3',
                        	'desc' => $this->l('0 is full transparent and 1 is full colored.')
                             ),
                    array(
                         'type' => 'text',
                                'label' => $this->l('Color picker button'),
                                'name' => 'pop_colorpicker_btn',
                                'lang' => false,
                                'id'   => 'pop_colorpicker_btn',
                                'data-hex' => true,
                                'class'   => 'mColorPicker',
                                'col' => '5'
                            ),
                            array(
                             	'label' => $this->l('Button opacity'),
                        	'type' => 'select',
                            'options' => array(
                                'query' => array(
                                 array(
                                        'id' => 0,
                                        'value' => 0,
                                        'name' => '0'
                                 ),
                                 array(
                                        'id' => 0.1,
                                        'value' => 0.1,
                                        'name' => '0.1'
                                 ),
                                 array(
                                        'id' => 0.2,
                                        'value' => 0.2,
                                        'name' => '0.2'
                                ),
                                array(
                                    'id' => 0.3,
                                    'value' => 0.3,
                                    'name' => '0.3'
                                ),
                                array(
                                    'id' => 0.4,
                                    'value' => 0.4,
                                    'name' => '0.4'
                                ),
                                array(
                                    'id' => 0.5,
                                    'value' => 0.5,
                                    'name' => '0.5'
                                ),
                                array(
                                    'id' => 0.6,
                                    'value' => 0.6,
                                    'name' => '0.6'
                                ),
                                array(
                                    'id' => 0.7,
                                    'value' => 0.7,
                                    'name' => '0.7'
                                ),
                                array(
                                    'id' => 0.8,
                                    'value' => 0.8,
                                    'name' => '0.8'
                                ),
                                array(
                                    'id' => 0.9,
                                    'value' => 0.9,
                                    'name' => '0.9'
                                ),
                                array(
                                    'id' => 1,
                                    'value' => 1,
                                    'name' => '1'
                                )
                            ),
                                'id' => 'id',
                            'name' => 'name'
                            ),
                        	'id'   => 'pop_opacity_btn',
                        	'name' => 'pop_opacity_btn',
                        	'col' => '3',
                        	'desc' => $this->l('0 is full transparent and 1 is full colored.')
                             ),
                    array(
                         'type' => 'text',
                                'label' => $this->l('Color picker button border'),
                                'name' => 'pop_colorpicker_btn_border',
                                'lang' => false,
                                'id'   => 'pop_colorpicker_btn_border',
                                'data-hex' => true,
                                'class'   => 'mColorPicker',
                                'col' => '5'
                            ),
                    array(
                        'label' => $this->l('Date start'),
                        'type' => 'datetime',
                        'name' => 'date_start'
                    ),
                    array(
                        'label' => $this->l('Date stop'),
                        'type' => 'datetime',
                        'name' => 'date_stop'
                    ),
                    array(
                        'label' => $this->l('Width'),
                        'type' => 'text',
                        'name' => 'width',
                        'suffix' => $this->l('px'),
                        'col' => '1'
                    ),
                    array(
                        'label' => $this->l('Height'),
                        'hint' => $l01,
                        'type' => 'text',
                        'name' => 'height',
                        'suffix' => $this->l('px'),
                        'col' => '1'
                    ),
                    array(
                        'label' => $this->l('Delay'),
                        'type' => 'text',
                        'name' => 'delay',
                        'suffix' => $this->l('milliseconds'),
                        'col' => '2'
                    ),
                    array(
                        'label' => $this->l('Popup expire'),
                        'type' => 'text',
                        'name' => 'expire',
                        'suffix' => $this->l('unit(s)'),
                        'hint' => $this->l('Unit(s) related to selected expire ratio on the field below.'),
                        'col' => '2',
                        'desc' => $this->l(
                        	'For permanent popup\'s showing without expiration (always appear), just put units to \'0\''
                        )
                    ),
                    array(
                        'label' => $this->l('Expire ratio'),
                        'type' => 'select',
                        'name' => 'expire_ratio',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 60,
                                    'name' => $this->l('Minute')
                                ),
                                array(
                                    'id' => 3600,
                                    'name' => $this->l('Hour')
                                ),
                                array(
                                    'id' => 86400,
                                    'name' => $this->l('Day')
                                ),
                                array(
                                    'id' => 2678400,
                                    'name' => $this->l('Month')
                                ),
                                array(
                                    'id' => 30758400,
                                    'name' => $this->l('Year')
                                )
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'lang' => true,
                        'name' => 'title',
                        'class' => 'text-strong'
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Content'),
                        'lang' => true,
                        'name' => 'content',
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Popup footer'),
                        'hint' => $this->l(
                            'This option makes show or hide the footer popup with the close button.'
                        ).'<br />'.$this->l(
                            'The popup\'s closing is also always possible without button.'
                        ),
                        'name' => 'footer',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'footer_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'footer_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'group',
                        'label' => $this->l('Group access restrictions'),
                        'name' => 'groupBox',
                        'values' => Group::getGroups($context->language->id)
                    ),
                    array(
                        'type' => 'shop',
                        'label' => $this->l('Shop association'),
                        'name' => 'checkBoxShopAsso',
                        'values' => Shop::getTree()
                    ),
                    array(
                        'type' => 'html',
                        'name' =>  $content_restriction_pages
                    )
                ),
                'submit' => array(
                    'title' => Tools::ucfirst($do),
                    'name' => $do.'popupsubmit',
                    'class' => 'btn btn-default pull-right'
                ),
                'buttons' => array(
                    array(
                        'href' => $ppc.'&token='.$tk.'&class=PopupClass',
                        'title' => $this->l('Back to list'),
                        'icon' => 'process-icon-back'
                    )
                )
            )
        );
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $definition_lang['tabListName'];
        $helper->table = 'prestablog_popup';
        $helper->identifier = 'id_prestablog_popup';
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }
        //$helper->toolbar_scroll = true;
        //$helper->show_toolbar = true;
        $helper->submit_action = $do.'popupsubmit';
        //$helper->show_cancel_button = true;
        $helper->token = $tk;
        $cI = $ppc.'&class=PopupClass';
        $helper->currentIndex = $cI;
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $context->controller->getLanguages(),
            'id_language' => $context->language->id
        );
        return $helper->generateForm(array($fields_form)).$popup_preview_content;
    }

    public function definitionLang()
    {
        $className = $this->l('popup');
        return array (
            'className' => $className,
            'tabListName' => $this->l('List of').' '.$className,
            'tabAddName' => $this->l('Add').' '.$className,
            'tabEditName' => $this->l('Edit').' '.$className,
            'tabHelp' => $this->l('Help'),
            'tableName' => $this->name);
    }

    public static function getIdFrontPopupCatePreFiltered($categorie)
    {

        $context = Context::getContext();
        if ($categorie == NULL) {
            $categorie = 0;
        }

    if ((int)$categorie != 0) {
                $id_popup = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT p.`id_prestablog_popup`
                    FROM `'._DB_PREFIX_.'prestablog_categorie_popuplink` p
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup` pp ON (p.`id_prestablog_popup` = pp.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_lang` pl ON (p.`id_prestablog_popup` = pl.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_shop` ps ON (p.`id_prestablog_popup` = ps.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_categorie` pn ON (pn.`id_prestablog_categorie` = '.$categorie.')
                    WHERE
                    p.`id_prestablog_categorie`         = '.$categorie.'
                     AND pl.`id_lang`      = '.(int)$context->language->id.'
                        AND   ps.`id_shop`      = '.(int)$context->shop->id.'
                        AND   pp.`actif`         = 1
                        AND   NOW() BETWEEN pp.`date_start` AND pp.`date_stop`');
        }

        if (isset($id_popup[0]["id_prestablog_popup"])) {

                   // if (PrestaBlog::isNotRestrictionCate($cate)) {

                    $groups_popup_cate = array();
                    $groups_customer = $context->customer->getGroups();


                    foreach (self::getListeIdGroup((int)$id_popup[0]["id_prestablog_popup"]) as $group) {
                        $groups_popup[] = $group['id_group'];
                    }
                    if (count(array_intersect($groups_popup, $groups_customer)) > 0) {

                        return (int)$id_popup[0]["id_prestablog_popup"];
                    }
                //}

        }

        return false;
    }


    public static function getIdFrontPopupNewsPreFiltered($news)
    {
        $context = Context::getContext();
        if ((int)$news != 0) {
                $id_popup = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT p.`id_prestablog_popup`
                    FROM `'._DB_PREFIX_.'prestablog_news_popuplink` p
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup` pp ON (p.`id_prestablog_popup` = pp.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_lang` pl ON (p.`id_prestablog_popup` = pl.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_shop` ps ON (p.`id_prestablog_popup` = ps.`id_prestablog_popup`)
                    LEFT JOIN `'._DB_PREFIX_.'prestablog_news` pn ON (pn.`id_prestablog_news` = '.$news.')
                    WHERE
                    p.`id_prestablog_news`         = '.$news.'
                     AND pl.`id_lang`      = '.(int)$context->language->id.'
                        AND   ps.`id_shop`      = '.(int)$context->shop->id.'
                        AND   pp.`actif`         = 1
                        AND   NOW() BETWEEN pp.`date_start` AND pp.`date_stop`');
        }

        if (isset($id_popup[0]["id_prestablog_popup"])) {

                  //  if (PrestaBlog::isNotRestrictionNews($id)) {

                    $groups_popup_news = array();
                    $groups_customer = $context->customer->getGroups();


                    foreach (self::getListeIdGroup((int)$id_popup[0]["id_prestablog_popup"]) as $group) {
                        $groups_popup[] = $group['id_group'];
                    }
                    if (count(array_intersect($groups_popup, $groups_customer)) > 0) {

                        return (int)$id_popup[0]["id_prestablog_popup"];
                    }
                //}

        }

        return false;
    }

    public static function getIdFrontPopupPreFiltered()
    {
        $context = Context::getContext();
        $list_popup = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.`id_prestablog_popup`, p.`restriction_rules`, p.`restriction_pages`, pl.`id_lang`
            FROM `'._DB_PREFIX_.'prestablog_popup` p
            LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_lang` pl ON (p.`id_prestablog_popup` = pl.`id_prestablog_popup`)
            LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_shop` ps ON (p.`id_prestablog_popup` = ps.`id_prestablog_popup`)
            WHERE
                pl.`id_lang`      = '.(int)$context->language->id.'
                AND   ps.`id_shop`      = '.(int)$context->shop->id.'
                AND   p.`actif`         = 1
                AND   NOW() BETWEEN p.`date_start` AND p.`date_stop`
            ORDER BY p.`date_stop` ASC;');

        if (count($list_popup) > 0) {
            foreach ($list_popup as $value) {
                if (PrestaBlog::isNotRestrictionPage($value['restriction_rules'], $value['restriction_pages'])) {
                    $groups_popup = array();
                    $groups_customer = $context->customer->getGroups();
                    foreach (self::getListeIdGroup((int)$value['id_prestablog_popup']) as $group) {
                        $groups_popup[] = $group['id_group'];
                    }
                    if (count(array_intersect($groups_popup, $groups_customer)) > 0) {
                        return (int)$value['id_prestablog_popup'];
                    }
                }
            }
        }
        return false;
    }

    public static function getListContent($id_lang, $id_shop = null)
    {
        $content = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.*, pl.*, ps.*,
                    DATEDIFF(p.`date_stop`,p.`date_start`) as date_interval,
                    TIMEDIFF(p.`date_stop`,p.`date_start`) as time_interval,
                    CONCAT(
                        FLOOR(HOUR(TIMEDIFF(p.`date_stop`,p.`date_start`)) / 24), \' days \',
                        MOD(HOUR(TIMEDIFF(p.`date_stop`,p.`date_start`)), 24), \' hours \',
                        MINUTE(TIMEDIFF(p.`date_stop`,p.`date_start`)), \' minutes\'
                    ) as verbose_interval,
                    if (NOW() BETWEEN p.`date_start` AND p.`date_stop`, 1, 0) badge_success,
                    if (NOW() > p.`date_stop`, 1, 0) badge_danger,
                    if (NOW() < p.`date_start`, 1, 0) badge_warning
            FROM `'._DB_PREFIX_.'prestablog_popup` p
            LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_lang` pl ON (p.`id_prestablog_popup` = pl.`id_prestablog_popup`)
            LEFT JOIN `'._DB_PREFIX_.'prestablog_popup_shop` ps ON (p.`id_prestablog_popup` = ps.`id_prestablog_popup`)
            WHERE
                pl.`id_lang` = '.(int)$id_lang.($id_shop ? ' AND ps.`id_shop`='.bqSQL((int)$id_shop) : '').'
            ORDER BY p.`date_stop` ASC;');

        return $content;
    }

    public static function createTables()
    {
        $return = true;
        $return &= Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup` (
            `id_prestablog_popup` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_start` datetime NOT NULL,
            `date_stop` datetime NOT NULL,
            `height` int(11) NOT NULL,
            `width` int(11) NOT NULL,
            `delay` int(11) NOT NULL,
            `expire` int(11) NOT NULL,
            `expire_ratio` int(11) NOT NULL,
            `theme` varchar(255) NOT NULL,
            `restriction_rules` int(11) NOT NULL,
            `restriction_pages` text NOT NULL,
            `footer` tinyint(1) NOT NULL DEFAULT \'1\',
            `actif` tinyint(1) NOT NULL DEFAULT \'1\',
            `pop_colorpicker_content` varchar(255),
            `pop_colorpicker_modal` varchar(255),
            `pop_colorpicker_btn` varchar(255),
            `pop_colorpicker_btn_border` varchar(255),
            `pop_opacity_content` varchar(255),
            `pop_opacity_modal` varchar(255),
            `pop_opacity_btn` varchar(255),
            `actif_home` tinyint(1) NOT NULL DEFAULT \'0\',


            PRIMARY KEY (`id_prestablog_popup`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

        $return &= Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_lang` (
            `id_prestablog_popup` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_lang` int(10) unsigned NOT NULL ,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_lang`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

        $return &= Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_shop` (
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            `id_shop` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_shop`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

        $return &= Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'prestablog_popup_group` (
            `id_prestablog_popup` int(10) unsigned NOT NULL,
            `id_group` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_prestablog_popup`, `id_group`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

        return $return;
    }

    public static function dropTables()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'prestablog_popup`')
            && Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'prestablog_popup_lang`')
            && Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'prestablog_popup_shop`')
            && Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'prestablog_popup_group`');
    }

    public function l($string)
    {
        $module = new PrestaBlog();
        return Translate::getModuleTranslation($module, $string, basename(__FILE__, '.php'));
    }
}
