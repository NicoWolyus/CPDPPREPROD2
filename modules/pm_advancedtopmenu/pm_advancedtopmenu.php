<?php
/**
 * Advanced Top Menu
 *
 * @author    Presta-Module.com <support@presta-module.com> - http://www.presta-module.com
 * @copyright Presta-Module 2019 - http://www.presta-module.com
 * @license   Commercial
 * @version   1.12.6
 *
 *           ____     __  __
 *          |  _ \   |  \/  |
 *          | |_) |  | |\/| |
 *          |  __/   | |  | |
 *          |_|      |_|  |_|
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuClass.php');
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuColumnWrapClass.php');
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuColumnClass.php');
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuElementsClass.php');
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuProductColumnClass.php');
include_once(_PS_ROOT_DIR_ . '/modules/pm_advancedtopmenu/AdvancedTopMenuWidgetProxy.php');
class PM_AdvancedTopMenu extends AdvancedTopMenuWidgetProxy
{
    private $_html;
    public static $_module_prefix = 'ATM';
    private $errors = array();
    private $defaultLanguage;
    private $languages;
    private $_iso_lang;
    private $current_category_product_url = false;
    private $activeAllreadySet = false;
    private $activateMenuId = false;
    private $activateMenuType = false;
    private $base_config_url;
    private $gradient_separator = '-';
    private $rebuildable_type = array(
        3,
        4,
        5,
    );
    private $font_families = array(
        "Arial, Helvetica, sans-serif",
        "'Arial Black', Gadget, sans-serif",
        "'Bookman Old Style', serif",
        "'Comic Sans MS', cursive",
        "Courier, monospace",
        "'Courier New', Courier, monospace",
        "Garamond, serif",
        "Georgia, serif",
        "Impact, Charcoal, sans-serif",
        "'Lucida Console', Monaco, monospace",
        "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
        "'MS Sans Serif', Geneva, sans-serif",
        "'MS Serif', 'New York', sans-serif",
        "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
        "Symbol, sans-serif",
        "Tahoma, Geneva, sans-serif",
        "'Times New Roman', Times, serif",
        "'Trebuchet MS', Helvetica, sans-serif",
        "Verdana, Geneva, sans-serif",
        "Webdings, sans-serif",
        "Wingdings, 'Zapf Dingbats', sans-serif",
    );
    private $allowFileExtension = array(
        'gif',
        'jpg',
        'jpeg',
        'png',
    );
    private $keepVarActif = array(
        'id_category',
        'id_product',
        'id_manufacturer',
        'id_supplier',
        'id_cms',
    );
    private $link_targets;
    protected static $_forceCompile;
    protected static $_caching;
    protected static $_compileCheck;
    protected $_copyright_link = array(
        'link'    => '',
        'img'    => '//www.presta-module.com/img/logo-module.JPG',
    );
    protected $_support_link = false;
    protected $_getting_started = false;
    private $tables = array(
        'pm_advancedtopmenu',
        'pm_advancedtopmenu_lang',
        'pm_advancedtopmenu_columns_wrap',
        'pm_advancedtopmenu_columns_wrap_lang',
        'pm_advancedtopmenu_columns',
        'pm_advancedtopmenu_columns_lang',
        'pm_advancedtopmenu_elements',
        'pm_advancedtopmenu_elements_lang',
    );
    const INSTALL_SQL_FILE = 'sql/install.sql';
    const GLOBAL_CSS_FILE = 'views/css/pm_advancedtopmenu_global.css';
    const ADVANCED_CSS_FILE = 'views/css/pm_advancedtopmenu_advanced.css';
    const ADVANCED_CSS_FILE_RESTORE = 'views/css/reset-pm_advancedtopmenu_advanced.css';
    const DYN_CSS_FILE = 'views/css/pm_advancedtopmenu.css';
    public function __construct()
    {
        $this->name = 'pm_advancedtopmenu';
        $this->author = 'Presta-Module';
        $this->tab = 'front_office_features';
        $this->module_key = '22fb589ff4648a10756b4ad805180259';
        $this->version = '1.12.6';
        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => '1.7.99.99');
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->initClassVar();
        if ($this->_onBackOffice()) {
            $this->displayName = $this->l('Advanced Top Menu');
            $this->description = $this->l('Horizontal menu with sub menu in column');
            $this->_fieldsOptions = array(
                'ATM_CONT_CLASSES' => array('title' => $this->l('Menu container (.adtm_menu_container)'), 'desc' => $this->l('On bootstrap themes, you may have to enter "container" in order to center the menu'), 'type' => 'text', 'default' => (version_compare(_PS_VERSION_, '1.7.0.0', '>=') ? 'container' : ''), 'advanced' => true),
                'ATM_RESP_CONT_CLASSES' => array('title' => $this->l('Menu (#adtm_menu)'), 'type' => 'text', 'default' => '', 'advanced' => true),
                'ATM_MENU_HAMBURGER_SELECTORS' => array('title' => $this->l('Selector of hamburger icon'), 'desc' => $this->l('On default theme, should be "#menu-icon, .menu-icon" most of the time'), 'type' => 'text', 'default' => '#menu-icon, .menu-icon', 'advanced' => true),
                'ATM_INNER_CLASSES' => array('title' => $this->l('Menu subcontainer (#adtm_menu_inner)'), 'desc' => $this->l('On bootstrap themes, you may have to enter "container" in order to center the menu when using sticky view'), 'type' => 'text', 'default' => 'clearfix', 'advanced' => true),
                'ATM_RESPONSIVE_MODE' => array('title' => $this->l('Activate responsive mode'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true, 'mobile' => true),
                'ATM_RESPONSIVE_THRESHOLD' => array('title' => $this->l('Activate mobile mode up to (px)'), 'desc' => '', 'type' => 'text', 'default' => '767', 'mobile' => true),
                'ATM_RESP_TOGGLE_ENABLED' => array('title' => $this->l('Activate menu toggle mode'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true, 'mobile' => true),
                'ATM_RESP_TOGGLE_HEIGHT' => array('title' => $this->l('Height (px)'), 'desc' => '', 'type' => 'text', 'default' => '40', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 16, 'list' => array(), 'identifier' => 'id', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_TEXT' => array('title' => $this->l('Text'), 'desc' => '', 'type' => 'textLang', 'default' => $this->l('Menu'), 'size' => 20, 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_BG_COLOR_OP' => array('title' => $this->l('Background color (open state)'), 'desc' => '', 'type' => 'gradient', 'default' => '#ffffff', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_BG_COLOR_CL' => array('title' => $this->l('Background color (close state)'), 'desc' => '', 'type' => 'gradient', 'default' => '#e5e5e5', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_COLOR_OP' => array('title' => $this->l('Text color (opened state)'), 'desc' => '', 'type' => 'color', 'default' => '#333333', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_COLOR_CL' => array('title' => $this->l('Text color (closed state)'), 'desc' => '', 'type' => 'color', 'default' => '#666666', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_TOGGLE_ICON' => array('title' => $this->l('Icon'), 'desc' => '', 'type' => 'image', 'default' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYAgMAAACdGdVrAAAACVBMVEUAAAAAAAAAAACDY+nAAAAAAnRSTlMA3Pn2U8cAAAAaSURBVAjXY4CCrFVAsJJhFRigUjA5FEBvfQDmRTo/uCG3BQAAAABJRU5ErkJggg==', 'mobile' => true, 'class' => 'resp_toggle'),
                'ATM_RESP_MENU_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '5px 10px 5px 10px', 'mobile' => true),
                'ATMR_MENU_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0', 'mobile' => true),
                'ATM_RESP_MENU_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 18, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_MENU_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true, 'mobile' => true),
                'ATMR_MENU_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'uppercase', 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_MENU_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_MENU_BGCOLOR_OP' => array('title' => $this->l('Background color (opened state)'), 'desc' => '', 'type' => 'gradient', 'default' => '#333333-#000000', 'mobile' => true),
                'ATMR_MENU_BGCOLOR_CL' => array('title' => $this->l('Background color (closed state)'), 'desc' => '', 'type' => 'gradient', 'default' => '', 'mobile' => true),
                'ATMR_MENU_COLOR' => array('title' => $this->l('Text color'), 'desc' => '', 'type' => 'color', 'default' => '#484848', 'mobile' => true),
                'ATMR_MENU_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#d6d4d4', 'mobile' => true),
                'ATMR_MENU_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 1px 1px 1px', 'mobile' => true),
                'ATMR_SUBMENU_BGCOLOR' => array('title' => $this->l('Background color'), 'desc' => '', 'type' => 'gradient', 'default' => '#ffffff-#fcfcfc', 'mobile' => true),
                'ATMR_SUBMENU_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#e5e5e5', 'mobile' => true),
                'ATMR_SUBMENU_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 1px 0 1px', 'mobile' => true),
                'ATM_RESP_SUBMENU_ICON_OP' => array('title' => $this->l('Icon for opened state'), 'desc' => '', 'type' => 'image', 'default' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAAFVBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAASAQCkAAAABnRSTlMAHiXy6t8iJwLjAAAARUlEQVQY02OgKWBUAJFMYJJB1AhEChuCOSLJCkBpNxAHRBsBRVIUIJpUkhVgEmAlIKVgAFIDUgmXgkmAzXWCMqA20hgAAI+xB05evnCbAAAAAElFTkSuQmCC', 'mobile' => true),
                'ATM_RESP_SUBMENU_ICON_CL' => array('title' => $this->l('Icon for closed state'), 'desc' => '', 'type' => 'image', 'default' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAAFVBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAASAQCkAAAABnRSTlMAHiXy6t8iJwLjAAAANUlEQVQY02MgFwgisZmMFZA4Zo5IUiLJSFKMbkZESqUoYKjDNFw5RYAYCSckW0IEULxAPgAAZQ0HP01tIysAAAAASUVORK5CYII=', 'mobile' => true),
                'ATMR_COLUMNWRAP_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0', 'mobile' => true),
                'ATMR_COLUMNWRAP_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0', 'mobile' => true),
                'ATMR_COLUMNWRAP_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#e5e5e5', 'mobile' => true),
                'ATMR_COLUMNWRAP_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 1px 0', 'mobile' => true),
                'ATMR_COLUMN_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 5px 0', 'mobile' => true),
                'ATMR_COLUMN_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 10px 5px 10px', 'mobile' => true),
                'ATMR_COLUMNTITLE_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0', 'mobile' => true),
                'ATMR_COLUMNTITLE_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '8px 10px 8px 0', 'mobile' => true),
                'ATM_RESP_COLUMN_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 18, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true, 'mobile' => true),
                'ATMR_COLUMN_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'none', 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_TITLE_COLOR' => array('title' => $this->l('Text color'), 'desc' => '', 'type' => 'color', 'default' => '#333333', 'mobile' => true),
                'ATMR_COLUMN_ITEM_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '5px 0 5px 10px', 'mobile' => true),
                'ATMR_COLUMN_ITEM_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '15px 0 15px 0', 'mobile' => true),
                'ATM_RESP_COLUMN_ITEM_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 16, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_ITEM_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false, 'mobile' => true),
                'ATMR_COLUMN_ITEM_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'none', 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_ITEM_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id', 'mobile' => true),
                'ATMR_COLUMN_ITEM_COLOR' => array('title' => $this->l('Text color'), 'desc' => '', 'type' => 'color', 'default' => '#777777', 'mobile' => true),
                'ATM_MENU_CONT_HOOK' => array('title' => $this->l('Menu position'), 'onchange' => 'setMenuContHook(this.value);', 'desc' => '', 'type' => 'select', 'default' => 'top', 'list' => array(array('id' => 'top', 'name' => 'displayTop ' . $this->l('(default)')), array('id' => 'nav', 'name' => 'displayNav')), 'identifier' => 'id', 'default' => 'top'),
                'ATM_THEME_COMPATIBILITY_MODE' => array('title' => $this->l('Activate theme compatibility mode'), 'desc' => $this->l('Enable only if theme layout is corrupted after installation'), 'cast' => 'intval', 'type' => 'bool', 'default' => true ),
                'ATM_CACHE' => array('title' => $this->l('Activate cache (faster processing)'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true ),
                'ATM_AUTOCOMPLET_SEARCH' => array('title' => $this->l('Activate autocompletion in search input'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true ),
                'ATM_MENU_CONT_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_MENU_CONT_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '20px 0 0 0' ),
                'ATM_MENU_CONT_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#333333' ),
                'ATM_MENU_CONT_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '5px 0 0 0' ),
                'ATM_MENU_CONT_POSITION' => array('title' => $this->l('Position'), 'desc' => '', 'type' => 'select', 'default' => 'relative', 'list' => array(array('id' => 'relative', 'name' => $this->l('Relative (default)') ), array('id' => 'absolute', 'name' => $this->l('Absolute') ), array('id' => 'sticky', 'name' => $this->l('Sticky') ) ), 'identifier' => 'id' ),
                'ATM_MENU_CONT_POSITION_TRBL' => array('title' => $this->l('Positioning (px)'), 'desc' => '', 'type' => '4size_position', 'default' => '' ),
                'ATM_MENU_GLOBAL_ACTIF' => array('title' => $this->l('Highlight current tab (status:active)'), 'desc' => $this->l('Background and font color values from over settings will be used'), 'cast' => 'intval', 'type' => 'bool', 'default' => true ),
                'ATM_MENU_GLOBAL_WIDTH' => array('title' => $this->l('Width (px)'), 'desc' => $this->l('Put 0 for automatic width'), 'type' => 'text', 'default' => '0' ),
                'ATM_MENU_GLOBAL_HEIGHT' => array('title' => $this->l('Height (px)'), 'desc' => '', 'type' => 'text', 'default' => '56' ),
                'ATM_MENU_GLOBAL_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_MENU_GLOBAL_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_MENU_GLOBAL_ZINDEX' => array('title' => $this->l('Z-index value (CSS)'), 'desc' => $this->l('Increase if your cart block is under the menu bar'), 'type' => 'text', 'default' => '9' ),
                'ATM_MENU_GLOBAL_BGCOLOR' => array('title' => $this->l('Background color'), 'desc' => '', 'type' => 'gradient', 'default' => '#f6f6f6-#e6e6e6' ),
                'ATM_MENU_GLOBAL_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#e9e9e9' ),
                'ATM_MENU_GLOBAL_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 3px 0' ),
                'ATM_MENU_BOX_SHADOW' => array('title' => $this->l('Drop shadow'), 'desc' => '', 'type' => 'shadow', 'default' => '0px 5px 13px 0px' ),
                'ATM_MENU_BOX_SHADOWCOLOR' => array('title' => $this->l('Drop shadow color'), 'desc' => '', 'type' => 'color', 'default' => '#000000' ),
                'ATM_MENU_BOX_SHADOWOPACITY' => array('title' => $this->l('Drop shadow opacity'), 'desc' => '', 'type' => 'slider', 'default' => 20, 'min' => 0, 'max' => 100, 'step' => 1, 'suffix' => '%'),
                'ATM_MENU_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 20px 0 20px' ),
                'ATM_MENU_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_MENU_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 18, 'list' => array(), 'identifier' => 'id' ),
                'ATM_MENU_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_MENU_FONT_UNDERLINE' => array('title' => $this->l('Underline text'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_MENU_FONT_UNDERLINEOV' => array('title' => $this->l('Underline text (on mouse over)'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_MENU_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'none', 'list' => array(), 'identifier' => 'id' ),
                'ATM_MENU_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id' ),
                'ATM_MENU_COLOR' => array('title' => $this->l('Text color'), 'desc' => '', 'type' => 'color', 'default' => '#484848' ),
                'ATM_MENU_COLOR_OVER' => array('title' => $this->l('Text color (on mouse over)'), 'desc' => '', 'type' => 'color', 'default' => '#ffffff' ),
                'ATM_MENU_BGCOLOR' => array('title' => $this->l('Background color'), 'desc' => '', 'type' => 'gradient', 'default' => '' ),
                'ATM_MENU_BGCOLOR_OVER' => array('title' => $this->l('Background color (on mouse over)'), 'desc' => '', 'type' => 'gradient', 'default' => '#333333-#000000' ),
                'ATM_MENU_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#d6d4d4' ),
                'ATM_MENU_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 1px 0 1px' ),
                'ATM_SUBMENU_WIDTH' => array('title' => $this->l('Width (px)'), 'desc' => $this->l('Put 0 for automatic width'), 'type' => 'text', 'default' => '0' ),
                'ATM_SUBMENU_HEIGHT' => array('title' => $this->l('Minimum height (px)'), 'desc' => '', 'type' => 'text', 'default' => '0' ),
                'ATM_SUBMENU_ZINDEX' => array('title' => $this->l('Z-index value (CSS)'), 'desc' => $this->l('Increase if submenus are under your main content'), 'type' => 'text', 'default' => '1000' ),
                'ATM_SUBMENU_OPEN_METHOD' => array('title' => $this->l('Opening method'), 'desc' => '', 'cast' => 'intval', 'type' => 'select', 'default' => 1, 'list' => array(array('id' => 1, 'name' => $this->l('On mouse over') ), array('id' => 2, 'name' => $this->l('On mouse click') ) ), 'identifier' => 'id' ),
                'ATM_SUBMENU_POSITION' => array('title' => $this->l('Position'), 'desc' => '', 'cast' => 'intval', 'type' => 'select', 'default' => 2, 'list' => array(array('id' => 1, 'name' => $this->l('Left-aligned current menu') ), array('id' => 2, 'name' => $this->l('Left-aligned global menu') ) ), 'identifier' => 'id' ),
                'ATM_SUBMENU_BGCOLOR' => array('title' => $this->l('Background color'), 'desc' => '', 'type' => 'gradient', 'default' => '#ffffff-#fcfcfc' ),
                'ATM_SUBMENU_BORDERCOLOR' => array('title' => $this->l('Border color'), 'desc' => '', 'type' => 'color', 'default' => '#e5e5e5' ),
                'ATM_SUBMENU_BORDERSIZE' => array('title' => $this->l('Border width (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 1px 1px 1px' ),
                'ATM_SUBMENU_BOX_SHADOW' => array('title' => $this->l('Drop shadow'), 'desc' => '', 'type' => 'shadow', 'default' => '0px 5px 13px 0px' ),
                'ATM_SUBMENU_BOX_SHADOWCOLOR' => array('title' => $this->l('Drop shadow color'), 'desc' => '', 'type' => 'color', 'default' => '#000000' ),
                'ATM_SUBMENU_BOX_SHADOWOPACITY' => array('title' => $this->l('Drop shadow opacity'), 'desc' => '', 'type' => 'slider', 'default' => 20, 'min' => 0, 'max' => 100, 'step' => 1, 'suffix' => '%'),
                'ATM_SUBMENU_OPEN_DELAY' => array('title' => $this->l('Opening delay'), 'desc' => '', 'type' => 'slider', 'default' => 0.3, 'min' => 0, 'max' => 2, 'step' => 0.1, 'suffix' => 's'),
                'ATM_SUBMENU_FADE_SPEED' => array('title' => $this->l('Fading effect duration'), 'desc' => '', 'type' => 'slider', 'default' => 0.3, 'min' => 0, 'max' => 2, 'step' => 0.1, 'suffix' => 's'),
                'ATM_COLUMNWRAP_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_COLUMN_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_COLUMN_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 10px 0 10px' ),
                'ATM_COLUMNTITLE_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_COLUMNTITLE_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 10px 0 0' ),
                'ATM_COLUMN_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 16, 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => true),
                'ATM_COLUMN_FONT_UNDERLINE' => array('title' => $this->l('Underline text'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_COLUMN_FONT_UNDERLINEOV' => array('title' => $this->l('Underline text (on mouse over)'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_COLUMN_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'none', 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_TITLE_COLOR' => array('title' => $this->l('Heading text color'), 'desc' => '', 'type' => 'color', 'default' => '#333333' ),
                'ATM_COLUMN_TITLE_COLOR_OVER' => array('title' => $this->l('Heading text color (on mouse over)'), 'desc' => '', 'type' => 'color', 'default' => '#515151' ),
                'ATM_COLUMN_ITEM_PADDING' => array('title' => $this->l('Inner spaces - padding (px)'), 'desc' => '', 'type' => '4size', 'default' => '3px 0 3px 0' ),
                'ATM_COLUMN_ITEM_MARGIN' => array('title' => $this->l('Outer spaces - margin (px)'), 'desc' => '', 'type' => '4size', 'default' => '0 0 0 0' ),
                'ATM_COLUMN_ITEM_FONT_SIZE' => array('title' => $this->l('Text size (px)'), 'desc' => '', 'type' => 'select', 'default' => 13, 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_ITEM_FONT_BOLD' => array('title' => $this->l('Text in bold'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_COLUMN_ITEM_FONT_UNDERLINE' => array('title' => $this->l('Underline text'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_COLUMN_ITEM_FONT_UNDERLINEOV' => array('title' => $this->l('Underline text (on mouse over)'), 'desc' => '', 'cast' => 'intval', 'type' => 'bool', 'default' => false),
                'ATM_COLUMN_ITEM_FONT_TRANSFORM' => array('title' => $this->l('Text transformation'), 'desc' => '', 'type' => 'select', 'default' => 'none', 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_ITEM_FONT_FAMILY' => array('title' => $this->l('Text font'), 'desc' => '', 'type' => 'select', 'default' => 0, 'list' => array(), 'identifier' => 'id' ),
                'ATM_COLUMN_ITEM_COLOR' => array('title' => $this->l('Text color'), 'desc' => '', 'type' => 'color', 'default' => '#777777' ),
                'ATM_COLUMN_ITEM_COLOR_OVER' => array('title' => $this->l('Text color (on mouse over)'), 'desc' => '', 'type' => 'color', 'default' => '#333333' ),
            );
            if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                $this->_fieldsOptions['ATM_MENU_CONT_HOOK'] = array(
                    'title' => $this->l('Menu position'),
                    'onchange' => 'setMenuContHook(this.value);',
                    'desc' => '',
                    'type' => 'select',
                    'default' => 'top',
                    'list' => array(
                        array('id' => 'top', 'name' => 'displayTop'),
                        array('id' => 'nav-full', 'name' => 'displayNavFullWidth' . ' ' . $this->l('(default)')),
                        array('id' => 'widget', 'name' => $this->l('Widget (advanced user only)')),
                    ),
                    'identifier' => 'id',
                    'default' => 'nav-full',
                );
                $this->_fieldsOptions['ATM_THEME_COMPATIBILITY_MODE']['default'] = false;
                $this->_fieldsOptions['ATM_MENU_CONT_MARGIN']['default'] = '10px 0 0 0';
                $this->_fieldsOptions['ATM_COLUMNWRAP_PADDING']['default'] = '10px 10px 10px 10px';
                $this->_fieldsOptions['ATM_COLUMN_MARGIN']['default'] = '0 10px 10px 10px';
                $this->_fieldsOptions['ATMR_MENU_BGCOLOR_CL']['default'] = '#ffffff';
                if (!preg_match('#^crea.+#', $this->context->shop->theme_name)) {
                    $this->_fieldsOptions['ATM_RESP_TOGGLE_ENABLED']['default'] = false;
                }
            }
            foreach (array_keys($this->_fieldsOptions) as $key) {
                if (strpos($key, 'FONT_TRANSFORM') !== false) {
                    $this->_fieldsOptions[$key]['list'] = array(
                        array('id' => 'none', 'name' => $this->l('Normal (inherit)')),
                        array('id' => 'lowercase', 'name' => $this->l('lowercase')),
                        array('id' => 'uppercase', 'name' => $this->l('UPPERCASE')),
                        array('id' => 'capitalize', 'name' => $this->l('Capitalize')),
                    );
                } elseif (strpos($key, 'FONT_FAMILY') !== false) {
                    $this->_fieldsOptions[$key]['list'][] = array('id' => 0, 'name' => $this->l('Use the same font family as my theme'));
                    foreach ($this->font_families as $font_family) {
                        $this->_fieldsOptions[$key]['list'][] = array('id' => $font_family, 'name' => $font_family);
                    }
                } elseif (strpos($key, 'FONT_SIZE') !== false) {
                    $this->_fieldsOptions[$key]['list'][] = array('id' => 0, 'name' => $this->l('Use the same font size as my theme'));
                    for ($i = 8; $i <= 30; $i ++) {
                        $this->_fieldsOptions[$key]['list'][] = array('id' => $i, 'name' => $i );
                    }
                } elseif ($key == 'ATM_AUTOCOMPLET_SEARCH' && version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                    unset($this->_fieldsOptions[$key]);
                }
            }
            $this->link_targets = array(
                0 => $this->l('No target. W3C compliant.'),
                '_self' => $this->l('Open document in the same frame (_self)'),
                '_blank' => $this->l('Open document in a new window (_blank)'),
                '_top' => $this->l('Open document in the same window (_top)'),
                '_parent' => $this->l('Open document in the parent frame (_parent)'),
            );
            $doc_url_tab = array();
            $doc_url_tab['fr'] = '#/fr/advancedtopmenu/';
            $doc_url_tab['en'] = '#/en/advancedtopmenu/';
            $doc_url = $doc_url_tab['en'];
            if ($this->_iso_lang == 'fr') {
                $doc_url = $doc_url_tab['fr'];
            }
            $forum_url_tab = array();
            $forum_url_tab['fr'] = 'http://www.prestashop.com/forums/topic/89128-module-pm-advancedtopmenu-menu-de-navigation-horizontal-en-colonnes/';
            $forum_url_tab['en'] = 'http://www.prestashop.com/forums/topic/89175-module-advancedtopmenu-horizontal-navigation-menu-with-columns/';
            $forum_url = $forum_url_tab['en'];
            if ($this->_iso_lang == 'fr') {
                $forum_url = $forum_url_tab['fr'];
            }
            $this->_support_link = array(
                array('link' => $forum_url, 'target' => '_blank', 'label' => $this->l('Forum topic')),
                
                array('link' => 'http://addons.prestashop.com/contact-community.php?id_product=2072', 'target' => '_blank', 'label' => $this->l('Support contact')),
            );
        }
    }
    public function install()
    {
        if (!$this->updateDB() || ! parent::install()) {
            return false;
        }
        if (!$this->registerHook('displayTop') ||
            !$this->registerHook('displayHeader') ||
            !$this->registerHook('categoryUpdate') ||
            !$this->registerHook('actionObjectLanguageAddAfter') ||
            !$this->registerHook('actionShopDataDuplication') ||
            (version_compare(_PS_VERSION_, '1.6.0.2', '>=') && !$this->registerHook('displayNav')) ||
            (version_compare(_PS_VERSION_, '1.7.0.0', '>=') && !$this->registerHook('displayNavFullWidth')) ||
            !$this->installDefaultConfig()) {
            return false;
        }
        Db::getInstance()->update('hook_module', array('position' => 255), 'id_module = ' . (int)$this->id . ' AND id_hook = ' . (int)Hook::getIdByName('top'));
        return true;
    }
    private function updateDB()
    {
        if (! file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
            return (false);
        } elseif (! $sql = Tools::file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
            return (false);
        }
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = str_replace('MYSQL_ENGINE', _MYSQL_ENGINE_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query) {
            if (! Db::getInstance()->Execute(trim($query))) {
                return (false);
            }
        }
        return true;
    }
    private function columnExists($table, $column, $createIfNotExist = false, $type = false, $insertAfter = false)
    {
        $resultset = Db::getInstance()->ExecuteS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $table . "`", true, false);
        foreach ($resultset as $row) {
            if ($row ['Field'] == $column) {
                return true;
            }
        }
        if ($createIfNotExist && Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . $table . '` ADD `' . $column . '` ' . $type . ' ' . ($insertAfter ? ' AFTER `' . $insertAfter . '`' : '') . '')) {
            return true;
        }
        return false;
    }
    public function installDefaultConfig()
    {
        foreach ($this->_fieldsOptions as $key => $field) {
            $val = $field['default'];
            if (trim($val)) {
                if (is_array($val)) {
                    $val = serialize($val);
                }
                if (Configuration::get($key) === false) {
                    if (! Configuration::updateValue($key, $val)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    public function checkIfModuleIsUpdate($updateDb = false, $displayConfirm = true)
    {
        if (! $updateDb && $this->version != Configuration::get('ATM_LAST_VERSION')) {
            return false;
        }
        $isUpdate = true;
        $this->updateDB();
        if (Shop::isFeatureActive()) {
            $nb_shop_entry = Db::getInstance()->getRow('SELECT COUNT(DISTINCT id_shop) as nb_shop_entry FROM `'._DB_PREFIX_.'pm_advancedtopmenu_shop`');
            $nb_shop_entry = $nb_shop_entry['nb_shop_entry'];
            $nb_menu_entry = Db::getInstance()->getRow('SELECT COUNT(DISTINCT id_menu) as nb_menu_entry FROM `'._DB_PREFIX_.'pm_advancedtopmenu`');
            $nb_menu_entry = $nb_menu_entry['nb_menu_entry'];
            if (!$nb_shop_entry && $nb_menu_entry) {
                $menus_id = AdvancedTopMenuClass::getMenusId();
                foreach ($menus_id as $menu) {
                    foreach (Shop::getCompleteListOfShopsID() as $id_shop) {
                        Db::getInstance()->execute('INSERT IGNORE INTO `'._DB_PREFIX_.'pm_advancedtopmenu_shop` (id_shop, id_menu)
                        VALUES ('.(int)$id_shop.', '.(int)$menu['id_menu'].')');
                    }
                }
            }
        }
        $toUpdate = array(
            array('pm_advancedtopmenu', "id_shop", 'int(10) unsigned NOT NULL DEFAULT "0"', 'id_manufacturer'),
            array('pm_advancedtopmenu', "width_submenu", 'varchar(5) NOT NULL', 'border_color_tab'),
            array('pm_advancedtopmenu', "minheight_submenu", 'varchar(5) NOT NULL', 'width_submenu'),
            array('pm_advancedtopmenu', "position_submenu", 'tinyint(3) unsigned NOT NULL', 'minheight_submenu'),
            array('pm_advancedtopmenu_elements', "active", "tinyint(4)  NOT NULL DEFAULT '1'", 'target'),
            array('pm_advancedtopmenu', "active_mobile", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_columns', "active_mobile", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_columns_wrap', "active_mobile", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_elements', "active_mobile", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_lang', "have_icon", "varchar(1) NOT NULL DEFAULT ''", 'link'),
            array('pm_advancedtopmenu_lang', "image_type", "varchar(4) NOT NULL", 'have_icon'),
            array('pm_advancedtopmenu_lang', "image_legend", "varchar(256) NOT NULL DEFAULT ''", 'image_type'),
            array('pm_advancedtopmenu_columns_lang', "have_icon", "varchar(1) NOT NULL DEFAULT ''", 'link'),
            array('pm_advancedtopmenu_columns_lang', "image_type", "varchar(4) NOT NULL", 'have_icon'),
            array('pm_advancedtopmenu_columns_lang', "image_legend", "varchar(256) NOT NULL DEFAULT ''", 'image_type'),
            array('pm_advancedtopmenu_elements_lang', "have_icon", "varchar(1) NOT NULL DEFAULT ''", 'name'),
            array('pm_advancedtopmenu_elements_lang', "image_type", "varchar(4) NOT NULL", 'have_icon'),
            array('pm_advancedtopmenu_elements_lang', "image_legend", "varchar(256) NOT NULL DEFAULT ''", 'image_type'),
            array('pm_advancedtopmenu', "chosen_groups", "text NOT NULL", 'privacy'),
            array('pm_advancedtopmenu_columns', "chosen_groups", "text NOT NULL", 'privacy'),
            array('pm_advancedtopmenu_columns_wrap', "chosen_groups", "text NOT NULL", 'privacy'),
            array('pm_advancedtopmenu_elements', "chosen_groups", "text NOT NULL", 'privacy'),
            array('pm_advancedtopmenu', "active_desktop", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_columns', "active_desktop", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_columns_wrap', "active_desktop", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu_elements', "active_desktop", "tinyint(4)  NOT NULL DEFAULT '1'", 'active'),
            array('pm_advancedtopmenu', "id_specific_page", "varchar(64)  NOT NULL", 'id_manufacturer'),
            array('pm_advancedtopmenu_columns', "id_specific_page", "varchar(64)  NOT NULL", 'id_manufacturer'),
            array('pm_advancedtopmenu_elements', "id_specific_page", "varchar(64)  NOT NULL", 'id_manufacturer'),
        );
        foreach ($toUpdate as $infos) {
            if (!$this->columnExists($infos [0], $infos [1], $updateDb, $infos [2], $infos [3])) {
                $isUpdate = false;
            }
        }
        $languages = Language::getLanguages(false);
        $iconsDatabaseUpdate = array(
            array('pm_advancedtopmenu', 'id_menu', 'menu_icons'),
            array('pm_advancedtopmenu_columns', 'id_column', 'column_icons'),
            array('pm_advancedtopmenu_elements', 'id_element', 'element_icons'),
        );
        foreach ($iconsDatabaseUpdate as $iconsUpdateRow) {
            if ($this->columnExists($iconsUpdateRow[0], 'have_icon') && $this->columnExists($iconsUpdateRow[0] . '_lang', 'have_icon')) {
                $res = true;
                $imageList = Db::getInstance()->ExecuteS('SELECT `'. $iconsUpdateRow[1] .'`, `have_icon`, `image_type` FROM `'._DB_PREFIX_ . $iconsUpdateRow[0] . '`');
                if (self::_isFilledArray($imageList)) {
                    foreach ($imageList as $imageRow) {
                        $res &= Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . $iconsUpdateRow[0] . '_lang` SET `have_icon`="'.(int)$imageRow['have_icon'].'", `image_type`="'.pSQL($imageRow['image_type']).'" WHERE `'. $iconsUpdateRow[1] .'`="'.(int)$imageRow[$iconsUpdateRow[1]].'"');
                        if (is_writable(dirname(__FILE__) . '/' . $iconsUpdateRow[2])) {
                            $imgPath = dirname(__FILE__) . '/'. $iconsUpdateRow[2] .'/' . (int)$imageRow[$iconsUpdateRow[1]] . '.' . $imageRow['image_type'];
                        }
                        if (file_exists($imgPath) && is_readable($imgPath)) {
                            foreach ($languages as $language) {
                                $imgPathLang = dirname(__FILE__) . '/'. $iconsUpdateRow[2] .'/' . (int)$imageRow[$iconsUpdateRow[1]] . '-' . $language['iso_code'] . '.' . $imageRow['image_type'];
                                file_put_contents($imgPathLang, Tools::file_get_contents($imgPath));
                            }
                        }
                    }
                }
                if ($res) {
                    $res &= Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . $iconsUpdateRow[0] . '` DROP COLUMN `have_icon`, DROP COLUMN `image_type`');
                }
            }
        }
        $toChange = array(
            array('pm_advancedtopmenu', "fnd_color_menu_tab", 'varchar(15)'),
            array('pm_advancedtopmenu', "fnd_color_menu_tab_over", 'varchar(15)'),
            array('pm_advancedtopmenu', "fnd_color_submenu", 'varchar(15)'),
            array('pm_advancedtopmenu_columns_wrap', "bg_color", 'varchar(15)'),
        );
        foreach ($toChange as $infos) {
            $resultset = Db::getInstance()->ExecuteS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $infos [0] . "` WHERE `Field` = '" . $infos [1] . "'", true, false);
            foreach ($resultset as $row) {
                if ($row ['Type'] != $infos [2]) {
                    $isUpdate = false;
                    if ($updateDb) {
                        Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . $infos [0] . '` CHANGE `' . $infos [1] . '` `' . $infos [1] . '` ' . $infos [2] . '');
                    }
                }
            }
        }
        if ($updateDb) {
            $this->installDefaultConfig();
            if (Configuration::get('ATM_LAST_VERSION') && version_compare(Configuration::get('ATM_LAST_VERSION'), '1.9.8', '<=')) {
                if (Shop::isFeatureActive()) {
                    foreach (Shop::getShops(true, null, true) as $id_shop) {
                        Configuration::updateValue('ATM_MENU_PADDING', '0 10px 0 10px', false, null, $id_shop);
                    }
                } else {
                    Configuration::updateValue('ATM_MENU_PADDING', '0 10px 0 10px');
                }
            }
            Configuration::updateValue('ATM_LAST_VERSION', $this->version);
            if (Shop::isFeatureActive()) {
                foreach (Shop::getShops(true, null, true) as $id_shop) {
                    $this->generateGlobalCss($id_shop);
                }
            } else {
                $this->generateGlobalCss();
            }
            $this->generateCss();
            $this->clearCache();
        }
        return $isUpdate;
    }
    public function checkPermissions()
    {
        $verifs = array(
            dirname(__FILE__) . '/views/css',
            dirname(__FILE__) . '/column_icons',
            dirname(__FILE__) . '/menu_icons',
            dirname(__FILE__) . '/element_icons',
        );
        if (defined('_PS_CACHE_DIR_')) {
            $verifs[] = _PS_CACHE_DIR_;
        } else {
            $verifs[] = dirname(__FILE__) . '/../../cache/smarty/cache';
        }
        $errors = array();
        foreach ($verifs as $fileOrDir) {
            if (!is_writable($fileOrDir)) {
                $errors[] = $fileOrDir;
            }
        }
        return $errors;
    }
    public function resetInstall()
    {
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_lang`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_wrap`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_wrap_lang`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_lang`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_elements`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pm_advancedtopmenu_elements_lang`');
        if (! file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
            return (false);
        } elseif (! $sql = Tools::file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
            return (false);
        }
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query) {
            if (! Db::getInstance()->Execute(trim($query))) {
                return (false);
            }
        }
    }
    public function uninstallDefaultConfig()
    {
        foreach (array_keys($this->_fieldsOptions) as $key) {
            Configuration::deleteByName($key);
        }
        return true;
    }
    public function saveConfig()
    {
        if (Tools::getValue('submitATMOptions') || Tools::getValue('submitATMMobileOptions') || Tools::getValue('submitAdvancedConfig')) {
            foreach ($this->_fieldsOptions as $key => $field) {
                if (Tools::getValue('submitATMMobileOptions') && (!isset($field['mobile']) || (isset($field['mobile']) && !$field['mobile']))) {
                    continue;
                } elseif (Tools::getValue('submitAdvancedConfig') && (!isset($field['advanced']) || (isset($field['advanced']) && !$field['advanced']))) {
                    continue;
                } elseif (Tools::getValue('submitATMOptions') && ((isset($field['mobile']) && $field['mobile']) || (isset($field['advanced']) && $field['advanced']))) {
                    continue;
                }
                if ($field ['type'] == '4size' || $field ['type'] == 'shadow') {
                    Configuration::updateValue($key, $this->getBorderSizeFromArray(Tools::getValue($key)));
                } elseif ($field ['type'] == '4size_position') {
                    Configuration::updateValue($key, $this->getPositionSizeFromArray(Tools::getValue($key), false));
                } elseif ($field ['type'] == 'gradient') {
                    $gradientValue = Tools::getValue($key);
                    $newValue = $gradientValue[0] . (Tools::getValue($key . '_gradient') && isset($gradientValue[1]) && $gradientValue[1] ? $this->gradient_separator . $gradientValue[1] : '');
                    Configuration::updateValue($key, $newValue);
                } elseif ($field ['type'] == 'textLang') {
                    $languages = Language::getLanguages(false);
                    $list = array();
                    foreach ($languages as $language) {
                        $list[(int)$language['id_lang']] = (isset($field['cast']) ? $field['cast'](Tools::getValue($key . '_' . $language ['id_lang'])) : Tools::getValue($key . '_' . $language ['id_lang']));
                    }
                    Configuration::updateValue($key, $list);
                } elseif ($field ['type'] == 'image') {
                    if (isset($_FILES[$key]) && is_array($_FILES[$key]) && isset($_FILES[$key]['size']) && $_FILES[$key]['size'] > 0 && isset($_FILES[$key]['tmp_name']) && isset($_FILES[$key]['error']) && !$_FILES[$key]['error'] && file_exists($_FILES[$key]['tmp_name']) && filesize($_FILES[$key]['tmp_name']) > 0) {
                        $val = 'data:'.(isset($_FILES[$key]['type']) && !empty($_FILES[$key]['type']) && preg_match('/image/', $_FILES[$key]['type']) ? $_FILES[$key]['type'] : 'image/jpg').';base64,'.self::getDataSerialized(Tools::file_get_contents($_FILES[$key]['tmp_name']));
                        Configuration::updateValue($key, $val);
                    } elseif (Configuration::get($key) === false && !Tools::getValue($key.'_delete')) {
                        Configuration::updateValue($key, $field['default']);
                    }
                    if (Tools::getValue($key.'_delete')) {
                        Configuration::updateValue($key, '');
                    }
                } else {
                    if (Tools::getValue('submitATMMobileOptions') && $key == 'ATM_RESP_TOGGLE_ENABLED' && !Tools::getIsset('ATM_RESP_TOGGLE_ENABLED')) {
                        $value = (isset($field ['cast']) ? $field['cast']($field['default']) : $field['default']);
                    } elseif (Tools::getValue('submitAdvancedConfig') && $key == 'ATM_MENU_HAMBURGER_SELECTORS' && !Tools::getIsset('ATM_MENU_HAMBURGER_SELECTORS')) {
                        $value = (isset($field ['cast']) ? $field['cast']($field['default']) : $field['default']);
                    } else {
                        $value = (isset($field ['cast']) ? $field['cast'](Tools::getValue($key)) : Tools::getValue($key));
                    }
                    Configuration::updateValue($key, $value);
                }
            }
            if (Shop::isFeatureActive()) {
                foreach (Shop::getShops(true, null, true) as $id_shop) {
                    $this->generateGlobalCss($id_shop);
                }
            } else {
                $this->generateGlobalCss();
            }
            $this->generateCss();
            $this->clearCache();
            $this->context->controller->confirmations[] = $this->l('Configuration updated successfully');
        }
    }
    public function saveAdvancedConfig()
    {
        if (Tools::getValue('submitAdvancedConfig')) {
            $contextShops = array_values(Shop::getContextListShopID());
            $error = false;
            foreach ($contextShops as $id_shop) {
                $advanced_css_file_shop = str_replace('.css', '-'.$id_shop.'.css', dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE);
                if (!file_put_contents($advanced_css_file_shop, Tools::getValue('advancedConfig'))) {
                    $error = $this->l('Error while saving advanced styles');
                }
            }
            if ($error) {
                $this->context->controller->errors[] = $error;
            } else {
                $this->context->controller->confirmations[] = $this->l('Styles updated successfully');
            }
        }
    }
    public function getContent()
    {
        $this->initClassVar();
        if (Tools::getValue('makeUpdate')) {
            $this->checkIfModuleIsUpdate(true);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&updateDone=1');
        } elseif (Tools::getValue('updateDone')) {
            $this->context->controller->confirmations[] = $this->l('Module updated successfully');
        }
        $moduleIsUpToDate = $this->checkIfModuleIsUpdate(false);
        $permissionsErrors = $this->checkPermissions();
        $isNativeMenuModuleEnabled = $this->nativeMenuModuleIsEnabled();
        $nativeMenuModuleDisplayName = $this->getNativeMenuModuleDisplayName();
        $vars = array(
            'module_display_name' => $this->displayName,
            'module_is_up_to_date' => $moduleIsUpToDate,
            'permissions_errors' => $permissionsErrors,
            'nativeMenuModulePresence' => $isNativeMenuModuleEnabled,
            'nativeMenuModuleDisplayName' => $nativeMenuModuleDisplayName,
            'context_is_shop' => (Shop::getContext() == Shop::CONTEXT_SHOP),
            'css_js_assets' => $this->includeAdminCssJs(),
            'rating_invite' => $this->_showRating(true),
        );
        if (!count($permissionsErrors)) {
            if ($moduleIsUpToDate) {
                $this->_postProcess();
                $vars['display_maintenance'] = $this->displayMaintenanceZone();
                $vars['display_form'] = $this->_displayForm();
                $vars['display_config'] = $this->displayConfig();
                $vars['display_mobile_config'] = $this->displayMobileConfig();
                $vars['display_advanced_styles'] = $this->displayAdvancedConfig();
            }
        }
        return $this->fetchTemplate('module/content.tpl', $vars);
    }
    private function nativeMenuModuleIsEnabled()
    {
        return (Module::isEnabled('ps_mainmenu')) || (Module::isEnabled('blocktopmenu'));
    }
    private function getNativeMenuModuleDisplayName()
    {
        $blockTopMenuModule = Module::getInstanceByName('blocktopmenu');
        $psMainMenuModule = Module::getInstanceByName('ps_mainmenu');
        if (!empty($psMainMenuModule->displayName)) {
            return $psMainMenuModule->displayName;
        } elseif (!empty($blockTopMenuModule->displayName)) {
            return $blockTopMenuModule->displayName;
        } else {
            return null;
        }
    }
    protected function displayAdvancedConfig()
    {
        $id_shop = (int)$this->context->shop->id;
        $advanced_css_file = str_replace('.css', '-'.$id_shop.'.css', dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE);
        if (!file_exists($advanced_css_file)) {
            file_put_contents($advanced_css_file, Tools::file_get_contents(dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE_RESTORE));
        }
        $fieldsOptions = $this->_fieldsOptions;
        foreach ($fieldsOptions as $key => $field) {
            if (!isset($field['advanced']) || isset($field['advanced']) && !$field['advanced']) {
                unset($fieldsOptions[$key]);
            }
        }
        $vars = array(
            'fieldsOptions' => $fieldsOptions,
            'advancedStylesContent' => Tools::file_get_contents($advanced_css_file),
        );
        return $this->fetchTemplate('module/tabs/display_advanced_styles.tpl', $vars);
    }
    protected function includeAdminCssJs()
    {
        $return = '';
        $this->context->controller->addJquery();
        $this->context->controller->addJqueryUI(array('ui.draggable', 'ui.droppable', 'ui.sortable', 'ui.widget', 'ui.dialog', 'ui.tabs', 'ui.datepicker', 'ui.slider'), 'base');
        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $this->context->controller->addjQueryPlugin(array('autocomplete'));
        }
        $isoTinyMCE = (file_exists(_PS_ROOT_DIR_ . '/js/tiny_mce/langs/' . $this->_iso_lang . '.js') ? $this->_iso_lang : 'en');
        $ad = dirname($_SERVER ["PHP_SELF"]);
        $return .= '<script type="text/javascript">
            var iso = \'' . $isoTinyMCE . '\' ;
            var pathCSS = \'' . _THEME_CSS_DIR_ . '\' ;
            var ad = \'' . $ad . '\' ;
            var defaultIdLang = \'' . $this->context->cookie->id_lang . '\' ;
         </script>';
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            if (version_compare(_PS_VERSION_, '1.6.0.12', '>=')) {
                $this->context->controller->addJS(__PS_BASE_URI__ . 'js/admin/tinymce.inc.js');
            } else {
                $this->context->controller->addJS(__PS_BASE_URI__ . 'js/tinymce.inc.js');
            }
            $this->context->controller->addJS(__PS_BASE_URI__ . 'js/tiny_mce/tiny_mce.js');
        } else {
            $this->context->controller->addJS(__PS_BASE_URI__ . 'js/tiny_mce/tiny_mce.js');
        }
        $this->context->controller->addJS($this->_path . 'views/js/pm_tinymce.inc.js');
        $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.tablednd_0_5.js');
        $this->context->controller->addJS($this->_path . 'views/js/colorpicker/colorpicker.js');
        $this->context->controller->addJS($this->_path . 'views/js/codemirror/codemirror.js');
        $this->context->controller->addJS($this->_path . 'views/js/codemirror/css.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.tipTip.js');
        $this->context->controller->addCSS($this->_path . 'views/css/admin.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/colorpicker/colorpicker.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/codemirror/codemirror.css', 'all');
        $this->context->controller->addCSS($this->_path . 'views/css/codemirror/default.css', 'all');
        $return .= '
        <script type="text/javascript">
            var id_language = Number(' . $this->defaultLanguage . ');
            var base_config_url = "' . $this->base_config_url . '";
            var atm_token = "' . Tools::getAdminTokenLite('AdminProducts') . '";
        </script>';
        return $return;
    }
    protected function displayConfig()
    {
        if (!isset($this->_fieldsOptions) or !count($this->_fieldsOptions)) {
            return;
        }
        if (version_compare(_PS_VERSION_, '1.6.0.2', '<') && isset($this->_fieldsOptions['ATM_MENU_CONT_HOOK'])) {
            unset($this->_fieldsOptions['ATM_MENU_CONT_HOOK']);
        }
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<') && isset($this->_fieldsOptions['ATM_MENU_CONT_HOOK'])) {
            unset($this->_fieldsOptions['ATM_MENU_CONT_HOOK']['list'][2]);
        }
        $fieldsOptions = $this->_fieldsOptions;
        foreach ($fieldsOptions as $key => $field) {
            if (isset($field['mobile']) && $field['mobile'] || isset($field['advanced']) && $field['advanced']) {
                unset($fieldsOptions[$key]);
            }
        }
        $vars = array(
            'fieldsOptions' => $fieldsOptions,
        );
        return $this->fetchTemplate('module/tabs/display_config.tpl', $vars);
    }
    protected function displayMobileConfig()
    {
        if (! isset($this->_fieldsOptions) or ! count($this->_fieldsOptions)) {
            return;
        }
        $fieldsOptions = $this->_fieldsOptions;
        foreach ($fieldsOptions as $key => $field) {
            if (!isset($field['mobile']) || isset($field['mobile']) && !$field['mobile']) {
                unset($fieldsOptions[$key]);
            } elseif (!empty($field['mobile']) && version_compare(_PS_VERSION_, '1.7.0.0', '<') && $key == 'ATM_RESP_TOGGLE_ENABLED') {
                unset($fieldsOptions[$key]);
            } elseif (!empty($field['advanced']) && version_compare(_PS_VERSION_, '1.7.0.0', '<') && $key == 'ATM_MENU_HAMBURGER_SELECTORS') {
                unset($fieldsOptions[$key]);
            }
        }
        $vars = array(
            'fieldsOptions' => $fieldsOptions,
        );
        return $this->fetchTemplate('module/tabs/display_mobile_config.tpl', $vars);
    }
    public function outputFormItem($key, $field)
    {
        $languages = Language::getLanguages(false);
        $val = Tools::getValue($key, Configuration::get($key));
        $vars = array(
            'val' => $val,
            'key' => $key,
            'field' => $field,
        );
        switch ($field['type']) {
            case 'select':
                foreach ($field['list'] as &$value) {
                    $value[$field['identifier']] = (isset($field['cast']) ? $field['cast']($value[$field['identifier']]) : $value[$field['identifier']]);
                    $value['is_selected'] = (($val === false && isset($field['default']) && $field['default'] === $value[$field['identifier']]) || ($val == $value [$field['identifier']]));
                }
                $vars['field'] = $field;
                return $this->fetchTemplate('core/form/select.tpl', $vars);
            case 'bool':
                return $this->fetchTemplate('core/form/bool.tpl', $vars);
            case 'textLang':
                $vars['values'] = array();
                foreach ($languages as $language) {
                    $vars['lang_values'][(int)$language['id_lang']] = Tools::getValue($key . '_' . (int)$language['id_lang'], Configuration::get($key, (int)$language['id_lang']));
                }
                return $this->fetchTemplate('core/form/input_text_lang.tpl', $vars);
            case 'color':
                return $this->fetchTemplate('core/form/input_color.tpl', $vars);
            case 'gradient':
                if (!is_array($val)) {
                    $val = explode($this->gradient_separator, $val);
                }
                $vars['color1'] = htmlentities($val[0], ENT_COMPAT, 'UTF-8');
                $vars['color2'] = null;
                if (isset($val[1])) {
                    $vars['color2'] = htmlentities($val[1], ENT_COMPAT, 'UTF-8');
                }
                return $this->fetchTemplate('core/form/input_gradient_color.tpl', $vars);
            case '4size':
                $vars['borders_size_tab'] = null;
                if ($val || (isset($field ['default']) && $field ['default'])) {
                    $borders_size_tab = ($val !== false ? $val : $field['default']);
                    if (!is_array($borders_size_tab)) {
                        $borders_size_tab = explode(' ', $borders_size_tab);
                    }
                    if (is_array($borders_size_tab)) {
                        foreach ($borders_size_tab as &$borderValue) {
                            if ($borderValue != 'auto') {
                                $borderValue = (int)preg_replace('#px#', '', $borderValue);
                            }
                        }
                    }
                    $vars['borders_size_tab'] = $borders_size_tab;
                }
                return $this->fetchTemplate('core/form/input_4size.tpl', $vars);
            case '4size_position':
                $vars['borders_size_tab'] = null;
                if ($val || (isset($field['default']) && $field['default'])) {
                    $borders_size_tab = ($val !== false ? $val : $field['default']);
                    if (!is_array($borders_size_tab)) {
                        $borders_size_tab = explode(' ', $borders_size_tab);
                    }
                    if (is_array($borders_size_tab)) {
                        foreach ($borders_size_tab as &$borderValue) {
                            if (Tools::strlen($borderValue)) {
                                $borderValue = (int)preg_replace('#px#', '', $borderValue);
                            } else {
                                $borderValue = '';
                            }
                        }
                    }
                    $vars['borders_size_tab'] = $borders_size_tab;
                }
                return $this->fetchTemplate('core/form/input_4size_position.tpl', $vars);
            case 'image':
                return $this->fetchTemplate('core/form/input_image.tpl', $vars);
            case 'shadow':
                $vars['borders_size_tab'] = null;
                if ($val || (isset($field ['default']) && $field ['default'])) {
                    $borders_size_tab = ($val !== false ? $val : @$field ['default']);
                    if (!is_array($borders_size_tab)) {
                        $borders_size_tab = explode(' ', $borders_size_tab);
                    }
                    if (is_array($borders_size_tab)) {
                        foreach ($borders_size_tab as &$borderValue) {
                            if (Tools::strlen($borderValue)) {
                                $borderValue = (int)preg_replace('#px#', '', $borderValue);
                            } else {
                                $borderValue = 0;
                            }
                        }
                    }
                    $vars['borders_size_tab'] = $borders_size_tab;
                }
                return $this->fetchTemplate('core/form/input_shadow.tpl', $vars);
            case 'slider':
                return $this->fetchTemplate('core/form/slider.tpl', $vars);
            case 'text':
            default:
                return $this->fetchTemplate('core/form/input_text.tpl', $vars);
        }
    }
    private function initClassVar()
    {
        $this->base_config_url = $_SERVER['SCRIPT_NAME'].(($controller = Tools::getValue('controller')) ? '?controller='.$controller: '') . '&configure=' . $this->name . '&token=' . Tools::getValue('token');
        $languages = Language::getLanguages(false);
        $this->defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
        $this->_iso_lang = Language::getIsoById($this->context->cookie->id_lang);
        $this->languages = $languages;
    }
    private function _displayForm()
    {
        $this->initClassVar();
        $menus = AdvancedTopMenuClass::getMenus($this->context->cookie->id_lang, false);
        if (is_array($menus) && count($menus)) {
            foreach ($menus as &$menu) {
                $menu['columnsWrap'] = AdvancedTopMenuColumnWrapClass::getMenuColumnsWrap($menu['id_menu'], $this->context->cookie->id_lang, false);
                if (count($menu['columnsWrap'])) {
                    foreach ($menu['columnsWrap'] as &$columnWrap) {
                        $columnWrap['columns'] = AdvancedTopMenuColumnClass::getMenuColums($columnWrap['id_wrap'], $this->context->cookie->id_lang, false);
                        if (count($columnWrap['columns'])) {
                            foreach ($columnWrap['columns'] as &$column) {
                                $column['columnElements'] = AdvancedTopMenuElementsClass::getMenuColumnElements($column['id_column'], $this->context->cookie->id_lang, false);
                                if ($column['type'] == 8) {
                                    $productInfos = AdvancedTopMenuProductColumnClass::getByIdColumn($column['id_column']);
                                    if (Validate::isLoadedObject($productInfos)) {
                                        $productObj = new Product($productInfos->id_product, false, $this->context->cookie->id_lang);
                                        if (Validate::isLoadedObject($productObj)) {
                                            $column['productObj'] = $productObj;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $cms = CMS::listCms((int)$this->context->cookie->id_lang);
        $manufacturer = Manufacturer::getManufacturers(false, $this->context->cookie->id_lang, true);
        $supplier = Supplier::getSuppliers(false, $this->context->cookie->id_lang, true);
        $vars = array(
            'menus' => $menus,
            'current_id_menu' => Tools::getValue('id_menu', false),
            'displayTabElement' => (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayColumnElement' => (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')),
            'displayGroupElement' => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')),
            'displayItemElement' => (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')),
            'editMenu' => (Tools::getValue('editMenu') && Tools::getValue('id_menu')),
            'editColumn' => (Tools::getValue('editColumnWrap') && Tools::getValue('id_wrap')),
            'editGroup' => (Tools::getValue('editColumn') && Tools::getValue('id_column')),
            'editElement' => (Tools::getValue('editElement') && Tools::getValue('id_element')),
            'cms' => $cms,
            'manufacturer' => $manufacturer,
            'supplier' => $supplier,
        );
        $ObjAdvancedTopMenuClass = false;
        $ObjAdvancedTopMenuColumnWrapClass = false;
        $ObjAdvancedTopMenuColumnClass = false;
        $ObjAdvancedTopMenuProductColumnClass = new AdvancedTopMenuProductColumnClass();
        $ObjAdvancedTopMenuElementsClass = false;
        if (!Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {
            if (Tools::getValue('editMenu') && Tools::getValue('id_menu')) {
                $ObjAdvancedTopMenuClass = new AdvancedTopMenuClass(Tools::getValue('id_menu'));
            }
        }
        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumn') && !Tools::getValue('editElement')) {
            if (Tools::getValue('editColumnWrap') && Tools::getValue('id_wrap')) {
                $ObjAdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass(Tools::getValue('id_wrap'));
            }
        }
        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editElement')) {
            if (Tools::getValue('editColumn') && Tools::getValue('id_column')) {
                $ObjAdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass(Tools::getValue('id_column'));
                $ObjAdvancedTopMenuProductColumnClass = AdvancedTopMenuProductColumnClass::getByIdColumn(Tools::getValue('id_column'));
            }
        }
        if (!Tools::getValue('editMenu') && !Tools::getValue('editColumnWrap') && !Tools::getValue('editColumn')) {
            if (Tools::getValue('editElement') && Tools::getValue('id_element')) {
                $ObjAdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass(Tools::getValue('id_element'));
            }
        }
        $vars['ObjAdvancedTopMenuClass'] = $ObjAdvancedTopMenuClass;
        $vars['ObjAdvancedTopMenuColumnWrapClass'] = $ObjAdvancedTopMenuColumnWrapClass;
        $vars['ObjAdvancedTopMenuColumnClass'] = $ObjAdvancedTopMenuColumnClass;
        $vars['ObjAdvancedTopMenuProductColumnClass'] = $ObjAdvancedTopMenuProductColumnClass;
        $vars['ObjAdvancedTopMenuElementsClass'] = $ObjAdvancedTopMenuElementsClass;
        return $this->fetchTemplate('module/tabs/display_form.tpl', $vars);
    }
    public function outputChosenGroups($object)
    {
        $vars = array(
            'groups' => Group::getGroups((int)$this->defaultLanguage),
            'object' => $object,
        );
        return $this->fetchTemplate('module/form_components/chosen_groups.tpl', $vars);
    }
    public function outputMenuForm($ObjAdvancedTopMenuClass)
    {
        $imgIconMenuDirIsWritable = is_writable(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/menu_icons');
        $haveDepend = false;
        $ids_lang = 'menuname¤menulink¤menu_value_over¤menu_value_under¤menuimage¤menuimagelegend';
        if ($ObjAdvancedTopMenuClass) {
            $haveDepend = AdvancedTopMenuClass::menuHaveDepend($ObjAdvancedTopMenuClass->id);
        }
        $vars = array(
            'ids_lang' => $ids_lang,
            'ObjAdvancedTopMenuClass' => $ObjAdvancedTopMenuClass,
            'rebuildable_type' => $this->rebuildable_type,
            'haveDepend' => $haveDepend,
            'imgIconMenuDirIsWritable' => $imgIconMenuDirIsWritable,
            'moduleRootDirectory' => _PS_ROOT_DIR_ . '/modules/' . $this->name,
        );
        $vars['fnd_color_menu_tab_color1'] = false;
        $vars['fnd_color_menu_tab_color2'] = false;
        if ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->fnd_color_menu_tab) {
            $val = explode($this->gradient_separator, $ObjAdvancedTopMenuClass->fnd_color_menu_tab);
            $vars['fnd_color_menu_tab_color1'] = htmlentities($val[0], ENT_COMPAT, 'UTF-8');
            if (isset($val[1])) {
                $vars['fnd_color_menu_tab_color2'] = htmlentities($val[1], ENT_COMPAT, 'UTF-8');
            }
        }
        $vars['fnd_color_menu_tab_over_color1'] = false;
        $vars['fnd_color_menu_tab_over_color2'] = false;
        if ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->fnd_color_menu_tab_over) {
            $val = explode($this->gradient_separator, $ObjAdvancedTopMenuClass->fnd_color_menu_tab_over);
            $vars['fnd_color_menu_tab_over_color1'] = htmlentities($val[0], ENT_COMPAT, 'UTF-8');
            if (isset($val[1])) {
                $vars['fnd_color_menu_tab_over_color2'] = htmlentities($val[1], ENT_COMPAT, 'UTF-8');
            }
        }
        $vars['borders_size_tab'] = null;
        if ($ObjAdvancedTopMenuClass) {
            $vars['borders_size_tab'] = explode(' ', $ObjAdvancedTopMenuClass->border_size_tab);
            if (is_array($vars['borders_size_tab'])) {
                foreach ($vars['borders_size_tab'] as &$borderValue) {
                    $borderValue = (int)preg_replace('#px#', '', $borderValue);
                }
            }
        }
        $vars['fnd_color_submenu_color1'] = false;
        $vars['fnd_color_submenu_color2'] = false;
        if ($ObjAdvancedTopMenuClass && $ObjAdvancedTopMenuClass->fnd_color_submenu) {
            $val = explode($this->gradient_separator, $ObjAdvancedTopMenuClass->fnd_color_submenu);
            $vars['fnd_color_submenu_color1'] = htmlentities($val[0], ENT_COMPAT, 'UTF-8');
            if (isset($val [1])) {
                $vars['fnd_color_submenu_color2'] = htmlentities($val [1], ENT_COMPAT, 'UTF-8');
            }
        }
        $vars['borders_size_submenu'] = null;
        if ($ObjAdvancedTopMenuClass) {
            $vars['borders_size_submenu'] = explode(' ', $ObjAdvancedTopMenuClass->border_size_submenu);
            if (is_array($vars['borders_size_submenu'])) {
                foreach ($vars['borders_size_submenu'] as &$borderValue) {
                    $borderValue = (int)preg_replace('#px#', '', $borderValue);
                }
            }
        }
        $vars['hasAdditionnalText'] = false;
        foreach ($this->languages as $language) {
            if ($ObjAdvancedTopMenuClass && isset($ObjAdvancedTopMenuClass->value_over[$language['id_lang']]) && !empty($ObjAdvancedTopMenuClass->value_over[$language['id_lang']]) || isset($ObjAdvancedTopMenuClass->value_under[$language['id_lang']]) && !empty($ObjAdvancedTopMenuClass->value_under[$language['id_lang']])) {
                $vars['hasAdditionnalText'] = true;
                break;
            }
        }
        return $this->fetchTemplate('module/tabs/display_menu_form.tpl', $vars);
    }
    public function outputColumnWrapForm($menus, $ObjAdvancedTopMenuColumnWrapClass)
    {
        $ids_lang = 'columnwrap_value_over¤columnwrap_value_under';
        $vars = array(
            'ids_lang' => $ids_lang,
            'menus' => $menus,
            'ObjAdvancedTopMenuColumnWrapClass' => $ObjAdvancedTopMenuColumnWrapClass,
        );
        $vars['bg_color_color1'] = false;
        $vars['bg_color_color2'] = false;
        if ($ObjAdvancedTopMenuColumnWrapClass && $ObjAdvancedTopMenuColumnWrapClass->bg_color) {
            $val = explode($this->gradient_separator, $ObjAdvancedTopMenuColumnWrapClass->bg_color);
            $vars['bg_color_color1'] = htmlentities($val[0], ENT_COMPAT, 'UTF-8');
            if (isset($val [1])) {
                $vars['bg_color_color2'] = htmlentities($val[1], ENT_COMPAT, 'UTF-8');
            }
        }
        $vars['hasAdditionnalText'] = false;
        foreach ($this->languages as $language) {
            if (isset($ObjAdvancedTopMenuColumnWrapClass->value_over[$language['id_lang']]) && !empty($ObjAdvancedTopMenuColumnWrapClass->value_over[$language['id_lang']]) || isset($ObjAdvancedTopMenuColumnWrapClass->value_under[$language['id_lang']]) && !empty($ObjAdvancedTopMenuColumnWrapClass->value_under[$language['id_lang']])) {
                $vars['hasAdditionnalText'] = true;
                break;
            }
        }
        return $this->fetchTemplate('module/tabs/display_columnwrap_form.tpl', $vars);
    }
    public function outputColumnForm($menus, $cms, $manufacturer, $supplier, $ObjAdvancedTopMenuColumnClass, $ObjAdvancedTopMenuProductColumnClass)
    {
        $ids_lang = 'columnname¤columnlink¤column_value_over¤column_value_under¤columnimage¤columnimagelegend';
        $imgIconColumnDirIsWritable = is_writable(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/column_icons');
        $haveDepend = false;
        if ($ObjAdvancedTopMenuColumnClass) {
            $haveDepend = AdvancedTopMenuColumnClass::columnHaveDepend($ObjAdvancedTopMenuColumnClass->id);
        }
        $currentProductName = 'N/A';
        if ($ObjAdvancedTopMenuProductColumnClass && isset($ObjAdvancedTopMenuProductColumnClass->id_product) && $ObjAdvancedTopMenuProductColumnClass->id_product) {
            $productObj = new Product($ObjAdvancedTopMenuProductColumnClass->id_product, false, $this->context->cookie->id_lang);
            if (Validate::isLoadedObject($productObj)) {
                $currentProductName = $productObj->name;
            }
        }
        $hasAdditionnalText = false;
        foreach ($this->languages as $language) {
            if (isset($ObjAdvancedTopMenuColumnClass->value_over[$language['id_lang']]) && !empty($ObjAdvancedTopMenuColumnClass->value_over[$language['id_lang']]) || isset($ObjAdvancedTopMenuColumnClass->value_under[$language['id_lang']]) && !empty($ObjAdvancedTopMenuColumnClass->value_under[$language['id_lang']])) {
                $hasAdditionnalText = true;
                break;
            }
        }
        $vars = array(
            'ids_lang' => $ids_lang,
            'haveDepend' => $haveDepend,
            'imgIconColumnDirIsWritable' => $imgIconColumnDirIsWritable,
            'moduleRootDirectory' => _PS_ROOT_DIR_ . '/modules/' . $this->name,
            'menus' => $menus,
            'cms' => $cms,
            'manufacturer' => $manufacturer,
            'supplier' => $supplier,
            'ObjAdvancedTopMenuColumnClass' => $ObjAdvancedTopMenuColumnClass,
            'ObjAdvancedTopMenuProductColumnClass' => $ObjAdvancedTopMenuProductColumnClass,
            'currentProductName' => $currentProductName,
            'productImagesTypes' => $this->getProductsImagesTypes(),
            'hasAdditionnalText' => $hasAdditionnalText,
            'rebuildable_type' => $this->rebuildable_type,
        );
        return $this->fetchTemplate('module/tabs/display_column_form.tpl', $vars);
    }
    public function outputElementForm($menus, $cms, $manufacturer, $supplier, $ObjAdvancedTopMenuElementClass)
    {
        $imgIconElementDirIsWritable = is_writable(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/element_icons');
        $ids_lang = 'elementname¤elementlink¤elementimage¤elementimagelegend';
        $vars = array(
            'ids_lang' => $ids_lang,
            'imgIconElementDirIsWritable' => $imgIconElementDirIsWritable,
            'moduleRootDirectory' => _PS_ROOT_DIR_ . '/modules/' . $this->name,
            'menus' => $menus,
            'cms' => $cms,
            'manufacturer' => $manufacturer,
            'supplier' => $supplier,
            'ObjAdvancedTopMenuElementClass' => $ObjAdvancedTopMenuElementClass,
        );
        return $this->fetchTemplate('module/tabs/display_element_form.tpl', $vars);
    }
    public static function hideCategoryPosition($name)
    {
        return preg_replace('/^[0-9]+\./', '', $name);
    }
    private function _getChildrensCategories(&$categoryList, $categoryInformations, $selected, $levelDepth = false)
    {
        if (isset($categoryInformations['children']) && self::_isFilledArray($categoryInformations['children'])) {
            foreach ($categoryInformations['children'] as $categoryInformations) {
                $categoryList[] = $categoryInformations;
                $this->_getChildrensCategories($categoryList, $categoryInformations, $selected, ($levelDepth !== false ? $levelDepth + 1 : $levelDepth));
            }
        }
    }
    private function _getNestedCategories($root_category = null, $id_lang = false)
    {
        $result = Db::getInstance()->executeS(
            'SELECT c.*, cl.*
            FROM `'._DB_PREFIX_.'category` c
            '.Shop::addSqlAssociation('category', 'c').'
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON c.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').'
            RIGHT JOIN `'._DB_PREFIX_.'category` c2 ON c2.`id_category` = '.(int)$root_category.' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`
            WHERE `id_lang` = '.(int)$id_lang . '
            ORDER BY c.`level_depth` ASC, category_shop.`position` ASC'
        );
        $categories = array();
        $buff = array();
        foreach ($result as $row) {
            $current = &$buff[$row['id_category']];
            $current = $row;
            if (!$row['active']) {
                $current['name'] .= ' '.$this->l('(disabled)');
            }
            if ($row['id_category'] == $root_category) {
                $categories[$row['id_category']] = &$current;
            } else {
                $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
            }
        }
        return $categories;
    }
    public function outputCategoriesSelect($object)
    {
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            $rootCategoryId = Category::getRootCategory()->id;
        } else {
            $rootCategoryId = Configuration::get('PS_ROOT_CATEGORY');
        }
        $selected = ($object ? $object->id_category : 0);
        $categoryList = array();
        foreach ($this->_getNestedCategories($rootCategoryId, $this->context->cookie->id_lang) as $idCategory => $categoryInformations) {
            if ($rootCategoryId != $idCategory) {
                $categoryList[] = $categoryInformations;
            }
            $this->_getChildrensCategories($categoryList, $categoryInformations, $selected);
        }
        $vars = array(
            'categoryList' => $categoryList,
            'selected' => $selected,
        );
        return $this->fetchTemplate('module/form_components/category_select.tpl', $vars);
    }
    public function outputTargetSelect($object)
    {
        $vars = array(
            'link_targets' => $this->link_targets,
            'selected' => ($object ? $object->target : 0),
        );
        return $this->fetchTemplate('module/form_components/target_select.tpl', $vars);
    }
    public function outputCmsSelect($cmss, $object)
    {
        $vars = array(
            'cmsList' => $cmss,
            'selected' => ($object ? $object->id_cms : 0),
        );
        return $this->fetchTemplate('module/form_components/cms_select.tpl', $vars);
    }
    public function outputManufacturerSelect($manufacturers, $object)
    {
        $vars = array(
            'manufacturerList' => $manufacturers,
            'selected' => ($object ? $object->id_manufacturer : 0),
        );
        return $this->fetchTemplate('module/form_components/manufacturer_select.tpl', $vars);
    }
    public function outputSupplierSelect($suppliers, $object)
    {
        $vars = array(
            'supplierList' => $suppliers,
            'selected' => ($object ? $object->id_supplier : 0),
        );
        return $this->fetchTemplate('module/form_components/supplier_select.tpl', $vars);
    }
    public function outputSpecificPageSelect($object)
    {
        $pages = Meta::getMetasByIdLang((int)$this->context->cookie->id_lang);
        $default_routes = Dispatcher::getInstance()->default_routes;
        foreach ($pages as $p => $page) {
            if (isset($default_routes[$page['page']]) && is_array($default_routes[$page['page']]['keywords']) && count($default_routes[$page['page']]['keywords'])) {
                unset($pages[$p]);
            } elseif (isset($default_routes[$page['page']])) {
                if (empty($page['title'])) {
                    $pages[$p]['title'] = $default_routes[$page['page']]['rule'];
                }
            }
        }
        $vars = array(
            'pagesList' => $pages,
            'selected' => ($object ? $object->id_specific_page : 0),
        );
        return $this->fetchTemplate('module/form_components/specific_page_select.tpl', $vars);
    }
    public function getType($type)
    {
        if ($type == 1) {
            return $this->l('CMS');
        } elseif ($type == 2) {
            return $this->l('Link');
        } elseif ($type == 3) {
            return $this->l('Category');
        } elseif ($type == 4) {
            return $this->l('Manufacturer');
        } elseif ($type == 5) {
            return $this->l('Supplier');
        } elseif ($type == 6) {
            return $this->l('Search');
        } elseif ($type == 7) {
            return $this->l('Only image or icon');
        } elseif ($type == 9) {
            return $this->l('Specific page');
        }
    }
    public function getLinkOutputValue($row, $type, $withExtra = true, $haveSub = false, $first_level = false)
    {
        $link = $this->context->link;
        $return = false;
        $name = false;
        $image_legend = false;
        $icone = false;
        $url = false;
        $linkNotClickable = false;
        if ((trim($row ['link'])) == '#') {
            $linkNotClickable = true;
        }
        $data_type = array(
            'type' => null,
            'id' => null,
        );
        if ($row ['type'] == 1) {
            if (trim($row ['name'])) {
                $name .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row ['meta_title'], ENT_COMPAT, 'UTF-8');
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            $url .= $link->getCMSLink((int)$row ['id_cms'], $row ['link_rewrite']);
            $data_type['type'] = 'cms';
            $data_type['id'] = (int)$row ['id_cms'];
        } elseif ($row ['type'] == 2) {
            if (trim($row ['name'])) {
                $name .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            if (trim($row ['link'])) {
                $url .= htmlentities($row ['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }
        } elseif ($row ['type'] == 3) {
            if (trim($row ['name'])) {
                $name .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row ['category_name'], ENT_COMPAT, 'UTF-8');
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            $url .= $link->getCategoryLink((int)$row ['id_category'], $row ['category_link_rewrite']);
            $data_type['type'] = 'category';
            $data_type['id'] = (int)$row ['id_category'];
        } elseif ($row ['type'] == 4) {
            if (trim($row ['name'])) {
                $name .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row ['manufacturer_name'], ENT_COMPAT, 'UTF-8') . '';
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            if ((int)$row['id_manufacturer']) {
                $url .= $link->getManufacturerLink((int)$row ['id_manufacturer'], Tools::link_rewrite($row['manufacturer_name']));
            } else {
                $url .= $link->getPageLink('manufacturer.php');
            }
            $data_type['type'] = 'brands';
            $data_type['id'] = (int)$row ['id_manufacturer'];
        } elseif ($row ['type'] == 5) {
            if (trim($row ['name'])) {
                $name .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $name .= htmlentities($row ['supplier_name'], ENT_COMPAT, 'UTF-8') . '';
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            if ((int)$row['id_supplier']) {
                $url .= $link->getSupplierLink((int)$row ['id_supplier'], Tools::link_rewrite($row['supplier_name']));
            } else {
                $url .= $link->getPageLink('supplier.php');
            }
            $data_type['type'] = 'supplier';
            $data_type['id'] = (int)$row ['id_supplier'];
        } elseif ($row ['type'] == 6) {
            $currentSearchQuery = trim(Tools::getValue('search_query', Tools::getValue('s')));
            $this->context->smarty->assign(array(
                'atm_form_action_link' => $link->getPageLink('search'),
                'atm_search_id' => 'search_query_atm_' . $type . '_' . $row ['id_' . $type],
                'atm_have_icon' => trim($row ['have_icon']),
                'atm_withExtra' => $withExtra,
                'atm_icon_image_source' => $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg'),
                'atm_search_value' => (Tools::strlen($currentSearchQuery) ? $currentSearchQuery : trim(htmlentities($row['name'], ENT_COMPAT, 'UTF-8'))),
                'atm_is_autocomplete_search' => Configuration::get('ATM_AUTOCOMPLET_SEARCH') && version_compare(_PS_VERSION_, '1.7.0.0', '<'),
                'atm_cookie_id_lang' => $this->context->cookie->id_lang,
                'atm_pagelink_search' => $link->getPageLink('search'),
            ));
            $cache = Configuration::get('ATM_CACHE');
            if (!Configuration::get('PS_SMARTY_CACHE')) {
                $cache = false;
            }
            if ($cache) {
                $adtmCacheId = sprintf('ADTM|%d|%s|%d|%s', $this->context->cookie->id_lang, $this->_isLogged(), (Shop::isFeatureActive() ? $this->context->shop->id : 0), implode('-', self::getCustomerGroups()));
                return $this->display(__FILE__, 'views/templates/front/pm_advancedtopmenu_search.tpl', $adtmCacheId);
            } else {
                return $this->display(__FILE__, 'views/templates/front/pm_advancedtopmenu_search.tpl');
            }
        } elseif ($row ['type'] == 7) {
            $name = '';
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
            if (trim($row ['link'])) {
                $url .= htmlentities($row ['link'], ENT_COMPAT, 'UTF-8');
            } else {
                $linkNotClickable = true;
            }
        } elseif ($row ['type'] == 9) {
            $page = Meta::getMetaByPage($row['id_specific_page'], (int)$this->context->cookie->id_lang);
            $name = (!empty($page['title']) ? $page['title'] : $page['page']);
            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9]+)$#i', $page['page'], $m)) {
                $url = $link->getModuleLink($m[1], $m[2]);
            } else {
                $url = $link->getPageLink($page['page']);
            }
            $data_type['type'] = $page['page'];
            if (trim($row ['name'])) {
                $name = htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $icone .= $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            }
        }
        $urlIsActive = false;
        if (($first_level || !$this->activeAllreadySet) && Configuration::get('ATM_MENU_GLOBAL_ACTIF')) {
            if ($id_product = Tools::getValue('id_product')) {
                if (!$this->current_category_product_url) {
                    $product = new Product($id_product, false, $this->context->cookie->id_lang);
                    if (isset($product->category) && !empty($product->category)) {
                        $this->current_category_product_url = $link->getCategoryLink($product->id_category_default, $product->category);
                    }
                }
                $curUrl = $this->current_category_product_url;
            }
            if (!isset($curUrl) || !$curUrl) {
                $curUrl = explode('?', $_SERVER ['REQUEST_URI']);
                $curUrl = $curUrl [0] . $this->getKeepVar();
            }
            $destUrl = explode('?', $url);
            $destUrl = $destUrl [0] . (isset($destUrl [1]) ? $this->getKeepVar($destUrl [1]) : '');
            $destUrl = preg_replace('#https?://' . preg_quote($_SERVER ['HTTP_HOST'], '#') . '#i', '', $destUrl);
            if ($destUrl == __PS_BASE_URI__ && Configuration::get('PS_REWRITING_SETTINGS') && Configuration::get('PS_CANONICAL_REDIRECT') && Language::countActiveLanguages() > 1) {
                $destUrl .= Language::getIsoById($this->context->cookie->id_lang).'/';
            }
            $curUrl = preg_replace('#https?://' . preg_quote($_SERVER ['HTTP_HOST'], '#') . '#i', '', $curUrl);
            $pregDestUrl = preg_quote($destUrl, '#');
            $urlIsActive = ((Tools::strlen($curUrl) <= Tools::strlen($destUrl)) ? preg_match('#' . $pregDestUrl . '#i', $curUrl) : false);
        }
        if ($url && $urlIsActive) {
            $idActif = 'advtm_menu_actif_' . uniqid();
        }
        $return .= '<a href="' . ($linkNotClickable ? '#' : $url) . '" title="' . $name . '" ' . ($row ['target'] ? 'target="' . htmlentities($row ['target'], ENT_COMPAT, 'UTF-8') . '"' : '') . ' class="' . ($linkNotClickable ? 'adtm_unclickable' : '') . (strpos($name, "\n") !== false ? ' a-multiline' : '') . ($first_level ? ' a-niveau1' : '') . ($url && $urlIsActive ? ' advtm_menu_actif ' . $idActif : '').'" '. (!empty($data_type['type']) ? ' data-type="'.$data_type['type'].'"' : '') . (isset($data_type['id']) && (int)$data_type['id'] > 0 ? ' data-id="'.(int)$data_type['id'].'"' : '') . '>';
        if ($type == 'menu') {
            $return .= '<span class="advtm_menu_span advtm_menu_span_' . (int)$row ['id_menu'] . '">';
        }
        if ($icone) {
            $iconWidth = $iconHeight = false;
            $iconPath = dirname(__FILE__) . '/' . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg');
            if (file_exists($iconPath) && is_readable($iconPath)) {
                list($iconWidth, $iconHeight) = getimagesize($iconPath);
            }
            $icone = $link->getMediaLink($icone);
            $icone = str_replace('https://', '//', $icone);
            $icone = str_replace('http://', '//', $icone);
            if (trim($row['image_legend'])) {
                $image_legend = htmlentities($row['image_legend'], ENT_COMPAT, 'UTF-8');
            } else {
                $image_legend = $name;
            }
            $return .= '<img src="' . $icone . '" alt="' . $image_legend . '" title="' . $image_legend . '" ' . ((int)$iconWidth > 0 ? 'width="'.(int)$iconWidth.'" ' : '') . ((int)$iconHeight > 0 ? 'height="'.(int)$iconHeight.'" ' : '') . 'class="adtm_menu_icon img-responsive" />';
        }
        $return .= nl2br($name);
        if ($type == 'menu') {
            $return .= '</span>';
        }
        if ($haveSub) {
            $return .= '<!--[if gte IE 7]><!-->';
        }
        $return .= '</a>';
        if ($url && $urlIsActive) {
            if (!$first_level) {
                $this->activateMenuId = $idActif;
                $this->activateMenuType = $type;
            } else {
                $this->activateMenuId = false;
                $this->activateMenuType = false;
            }
            $this->activeAllreadySet = true;
        }
        return $return;
    }
    public function getAdminOutputPrivacyValue($privacy)
    {
        $vars = array(
            'privacy' => $privacy,
        );
        return $this->fetchTemplate('module/form_components/privacy.tpl', $vars);
    }
    public function getAdminOutputNameValue($row, $withExtra = true, $type = false)
    {
        $return = '';
        if ($row ['type'] == 1) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= htmlentities($row ['meta_title'], ENT_COMPAT, 'UTF-8');
            }
        } elseif ($row ['type'] == 2) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('No label');
            }
        } elseif ($row ['type'] == 3) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= htmlentities($row ['category_name'], ENT_COMPAT, 'UTF-8');
            }
        } elseif ($row ['type'] == 4) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } elseif (! $row ['id_manufacturer'] && ! trim($row ['name'])) {
                $return .= $this->l('No label');
            } else {
                $return .= htmlentities($row ['manufacturer_name'], ENT_COMPAT, 'UTF-8') . '';
            }
        } elseif ($row ['type'] == 5) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } elseif (! $row ['id_supplier'] && ! trim($row ['name'])) {
                $return .= $this->l('No label');
            } else {
                $return .= htmlentities($row ['supplier_name'], ENT_COMPAT, 'UTF-8') . '';
            }
        } elseif ($row ['type'] == 6) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            if (trim($row ['name'])) {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            } else {
                $return .= $this->l('No label');
            }
        } elseif ($row ['type'] == 7) {
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            }
            $return .= $this->l('No label');
        } elseif ($row ['type'] == 9) {
            if (!trim($row ['name'])) {
                $page = Meta::getMetaByPage($row ['id_specific_page'], (int)$this->context->cookie->id_lang);
                $row ['name'] = (!empty($page['title']) ? $page['title'] : $page['page']);
            }
            if ($withExtra && trim($row ['have_icon'])) {
                $return .= '<img src="' . $this->_path . $type . '_icons/' . $row ['id_' . $type] . '-' . $this->_iso_lang . '.' . ($row ['image_type'] ? $row ['image_type'] : 'jpg') . '" alt="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" title="' . htmlentities($row ['name'], ENT_COMPAT, 'UTF-8') . '" />';
            } else {
                $return .= htmlentities($row ['name'], ENT_COMPAT, 'UTF-8');
            }
        }
        return $return;
    }
    protected function copyFromPost(&$object)
    {
        if (method_exists('Tools', 'getAllValues')) {
            $data = Tools::getAllValues();
        } else {
            $data = $_POST;
        }
        foreach ($data as $key => $value) {
            if ($key == 'active_column' || $key == 'active_menu' || $key == 'active_element') {
                $key = 'active';
            } elseif ($key == 'active_desktop_column' || $key == 'active_desktop_menu' || $key == 'active_desktop_element') {
                $key = 'active_desktop';
            } elseif ($key == 'active_mobile_column' || $key == 'active_mobile_menu' || $key == 'active_mobile_element') {
                $key = 'active_mobile';
            }
            if (key_exists($key, $object)) {
                $object->{$key} = $value;
            }
        }
        $rules = call_user_func(array(get_class($object), 'getValidationRules' ), get_class($object));
        if (count($rules ['validateLang'])) {
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (Tools::getIsset($field . '_' . (int)$language['id_lang'])) {
                        $object->{$field} [(int)$language['id_lang']] = Tools::getValue($field . '_' . (int)$language['id_lang']);
                    }
                }
            }
        }
    }
    private function udpdateMenuType($AdvancedTopMenuClass)
    {
        switch ($AdvancedTopMenuClass->type) {
            case 3:
                if (Tools::getValue('include_subs')) {
                    if ($AdvancedTopMenuClass->id_category) {
                        $firstChildCategories = array();
                        $firstChildCategories = $this->getSubCategoriesId($AdvancedTopMenuClass->id_category, true, true);
                        $lastChildCategories = array();
                        $columnWithNoDepth = false;
                        $columnWrapWithNoDepth = false;
                        if (count($firstChildCategories)) {
                            foreach ($firstChildCategories as $firstChildCategorie) {
                                $lastChildCategories = $this->getSubCategoriesId($firstChildCategorie ['id_category'], true, true);
                                if (count($lastChildCategories)) {
                                    $id_column = false;
                                    if (Tools::getValue('id_menu', false)) {
                                        $id_column = AdvancedTopMenuColumnClass::getIdColumnCategoryDepend($AdvancedTopMenuClass->id, $firstChildCategorie ['id_category']);
                                        if (! $id_column && ! Tools::getValue('rebuild')) {
                                            continue;
                                        }
                                    }
                                    $AdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass($id_column);
                                    if (! $id_column) {
                                        $AdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass();
                                        $AdvancedTopMenuColumnWrapClass->active = 1;
                                        $AdvancedTopMenuColumnWrapClass->id_menu = $AdvancedTopMenuClass->id;
                                        $AdvancedTopMenuColumnWrapClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                        $AdvancedTopMenuColumnWrapClass->save();
                                        $AdvancedTopMenuColumnWrapClass->internal_name = $this->l('column') . '-' . $AdvancedTopMenuColumnWrapClass->id_menu . '-' . $AdvancedTopMenuColumnWrapClass->id;
                                        if (! $AdvancedTopMenuColumnWrapClass->save()) {
                                            $this->context->controller->errors[] = $this->l('An error occurred while saving column');
                                        }
                                        $AdvancedTopMenuColumnClass->id_wrap = $AdvancedTopMenuColumnWrapClass->id;
                                    }
                                    $AdvancedTopMenuColumnClass->active = ($id_column ? $AdvancedTopMenuColumnClass->active : 1);
                                    $AdvancedTopMenuColumnClass->id_menu = $AdvancedTopMenuClass->id;
                                    $AdvancedTopMenuColumnClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                    $AdvancedTopMenuColumnClass->type = $AdvancedTopMenuClass->type;
                                    $AdvancedTopMenuColumnClass->id_category = $firstChildCategorie ['id_category'];
                                    $AdvancedTopMenuColumnClass->position = isset($firstChildCategorie ['position']) ? $firstChildCategorie ['position'] : '0';
                                    if ($AdvancedTopMenuColumnClass->save()) {
                                        $elementPosition = 0;
                                        foreach ($lastChildCategories as $lastChildCategory) {
                                            $id_element = false;
                                            if (Tools::getValue('id_menu', false)) {
                                                $id_element = AdvancedTopMenuElementsClass::getIdElementCategoryDepend($id_column, $lastChildCategory ['id_category']);
                                                if (! $id_element && ! Tools::getValue('rebuild')) {
                                                    continue;
                                                }
                                            }
                                            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                                            $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                                            $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuClass->type;
                                            $AdvancedTopMenuElementsClass->id_category = $lastChildCategory ['id_category'];
                                            $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                                            $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                                            $AdvancedTopMenuElementsClass->position = isset($lastChildCategory ['position']) ? $lastChildCategory ['position'] : '0';
                                            if (!$id_element) {
                                                $AdvancedTopMenuElementsClass->position = $elementPosition;
                                            }
                                            if (! $AdvancedTopMenuElementsClass->save()) {
                                                $this->context->controller->errors[] = $this->l('An error occurred while saving children category');
                                            }
                                            $elementPosition++;
                                        }
                                    } else {
                                        $this->context->controller->errors[] = $this->l('An error occurred while saving children category');
                                    }
                                } else {
                                    $id_column = false;
                                    $columnWithNoDepth = false;
                                    if (Tools::getValue('id_menu', false)) {
                                        $id_column = AdvancedTopMenuColumnClass::getIdColumnCategoryDependEmptyColumn($AdvancedTopMenuClass->id, $firstChildCategorie ['id_category']);
                                        if (! $id_column && ! Tools::getValue('rebuild')) {
                                            continue;
                                        }
                                        if ($id_column) {
                                            $columnWithNoDepth = $id_column;
                                        }
                                    }
                                    $AdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass($columnWithNoDepth);
                                    if (! $columnWithNoDepth) {
                                        $AdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass($columnWrapWithNoDepth);
                                        $AdvancedTopMenuColumnWrapClass->active = 1;
                                        $AdvancedTopMenuColumnWrapClass->id_menu = $AdvancedTopMenuClass->id;
                                        $AdvancedTopMenuColumnWrapClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                        $AdvancedTopMenuColumnWrapClass->save();
                                        $AdvancedTopMenuColumnWrapClass->internal_name = $this->l('column') . $AdvancedTopMenuColumnWrapClass->id_menu . '-' . $AdvancedTopMenuColumnWrapClass->id;
                                        $AdvancedTopMenuColumnWrapClass->save();
                                        $AdvancedTopMenuColumnClass->id_wrap = $AdvancedTopMenuColumnWrapClass->id;
                                    }
                                    $AdvancedTopMenuColumnClass->active = ($columnWithNoDepth ? $AdvancedTopMenuColumnClass->active : 1);
                                    $AdvancedTopMenuColumnClass->id_menu = $AdvancedTopMenuClass->id;
                                    $AdvancedTopMenuColumnClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                    $AdvancedTopMenuColumnClass->type = $AdvancedTopMenuClass->type;
                                    $AdvancedTopMenuColumnClass->id_category = $firstChildCategorie ['id_category'];
                                    $AdvancedTopMenuColumnClass->position = isset($firstChildCategorie ['position']) ? $firstChildCategorie ['position'] : '0';
                                    if ($AdvancedTopMenuColumnClass->save()) {
                                        if (! $columnWrapWithNoDepth) {
                                            $columnWrapWithNoDepth = $AdvancedTopMenuColumnClass->id_wrap;
                                        }
                                    } else {
                                        $this->context->controller->errors[] = $this->l('An error occurred while saving children category');
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 4:
                if (Tools::getValue('include_subs_manu')) {
                    $manufacturersId = $this->getManufacturersId();
                    $columnWithNoDepth = false;
                    if (count($manufacturersId)) {
                        $elementPosition = 0;
                        foreach ($manufacturersId as $manufacturerId) {
                            $id_column = false;
                            if (Tools::getValue('id_menu', false)) {
                                $id_column = AdvancedTopMenuColumnClass::getIdColumnManufacturerDependEmptyColumn($AdvancedTopMenuClass->id, $manufacturerId ['id_manufacturer']);
                                if (! $id_column && ! Tools::getValue('rebuild')) {
                                    continue;
                                }
                                if ($id_column) {
                                    $columnWithNoDepth = $id_column;
                                }
                            }
                            $AdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass($columnWithNoDepth);
                            if (! $columnWithNoDepth) {
                                $AdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass($columnWithNoDepth);
                                $AdvancedTopMenuColumnWrapClass->active = 1;
                                $AdvancedTopMenuColumnWrapClass->id_menu = $AdvancedTopMenuClass->id;
                                $AdvancedTopMenuColumnWrapClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                $AdvancedTopMenuColumnWrapClass->save();
                                $AdvancedTopMenuColumnWrapClass->internal_name = $this->l('column') . $AdvancedTopMenuColumnWrapClass->id_menu . '-' . $AdvancedTopMenuColumnWrapClass->id;
                                $AdvancedTopMenuColumnWrapClass->save();
                                $AdvancedTopMenuColumnClass->id_wrap = $AdvancedTopMenuColumnWrapClass->id;
                            }
                            $AdvancedTopMenuColumnClass->active = ($columnWithNoDepth ? $AdvancedTopMenuColumnClass->active : 1);
                            $AdvancedTopMenuColumnClass->id_menu = $AdvancedTopMenuClass->id;
                            $AdvancedTopMenuColumnClass->id_menu_depend = $AdvancedTopMenuClass->id;
                            $AdvancedTopMenuColumnClass->type = 2;
                            if ($AdvancedTopMenuColumnClass->save()) {
                                if (! $columnWithNoDepth) {
                                    $columnWithNoDepth = $AdvancedTopMenuColumnClass->id;
                                }
                                $id_element = false;
                                if (Tools::getValue('id_menu', false)) {
                                    $id_element = AdvancedTopMenuElementsClass::getIdElementManufacturerDepend($columnWithNoDepth, $manufacturerId ['id_manufacturer']);
                                    if (! $id_element && ! Tools::getValue('rebuild')) {
                                        continue;
                                    }
                                }
                                $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                                $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                                $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuClass->type;
                                $AdvancedTopMenuElementsClass->id_manufacturer = $manufacturerId ['id_manufacturer'];
                                $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                                $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                                if (!$id_element) {
                                    $AdvancedTopMenuElementsClass->position = $elementPosition;
                                }
                                if (! $AdvancedTopMenuElementsClass->save()) {
                                    $this->context->controller->errors[] = $this->l('An error occurred while saving manufacturers');
                                }
                                $elementPosition++;
                            } else {
                                $this->context->controller->errors[] = $this->l('An error occurred while saving manufacturers');
                            }
                        }
                    }
                }
                break;
            case 5:
                if (Tools::getValue('include_subs_suppl')) {
                    $suppliersId = $this->getSuppliersId();
                    $columnWithNoDepth = false;
                    if (count($suppliersId)) {
                        $elementPosition = 0;
                        foreach ($suppliersId as $supplierId) {
                            $id_column = false;
                            if (Tools::getValue('id_menu', false)) {
                                $id_column = AdvancedTopMenuColumnClass::getIdColumnSupplierDependEmptyColumn($AdvancedTopMenuClass->id, $supplierId ['id_supplier']);
                                if (! $id_column && ! Tools::getValue('rebuild')) {
                                    continue;
                                }
                                if ($id_column) {
                                    $columnWithNoDepth = $id_column;
                                }
                            }
                            $AdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass($columnWithNoDepth);
                            if (! $columnWithNoDepth) {
                                $AdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass($columnWithNoDepth);
                                $AdvancedTopMenuColumnWrapClass->active = 1;
                                $AdvancedTopMenuColumnWrapClass->id_menu = $AdvancedTopMenuClass->id;
                                $AdvancedTopMenuColumnWrapClass->id_menu_depend = $AdvancedTopMenuClass->id;
                                $AdvancedTopMenuColumnWrapClass->save();
                                $AdvancedTopMenuColumnWrapClass->internal_name = $this->l('column') . $AdvancedTopMenuColumnWrapClass->id_menu . '-' . $AdvancedTopMenuColumnWrapClass->id;
                                $AdvancedTopMenuColumnWrapClass->save();
                                $AdvancedTopMenuColumnClass->id_wrap = $AdvancedTopMenuColumnWrapClass->id;
                            }
                            $AdvancedTopMenuColumnClass->active = ($columnWithNoDepth ? $AdvancedTopMenuColumnClass->active : 1);
                            $AdvancedTopMenuColumnClass->id_menu = $AdvancedTopMenuClass->id;
                            $AdvancedTopMenuColumnClass->id_menu_depend = $AdvancedTopMenuClass->id;
                            $AdvancedTopMenuColumnClass->type = 2;
                            if ($AdvancedTopMenuColumnClass->save()) {
                                if (! $columnWithNoDepth) {
                                    $columnWithNoDepth = $AdvancedTopMenuColumnClass->id;
                                }
                                $id_element = false;
                                if (Tools::getValue('id_menu', false)) {
                                    $id_element = AdvancedTopMenuElementsClass::getIdElementSupplierDepend($columnWithNoDepth, $supplierId ['id_supplier']);
                                    if (! $id_element && ! Tools::getValue('rebuild')) {
                                        continue;
                                    }
                                }
                                $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                                $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                                $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuClass->type;
                                $AdvancedTopMenuElementsClass->id_supplier = $supplierId ['id_supplier'];
                                $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                                $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                                if (!$id_element) {
                                    $AdvancedTopMenuElementsClass->position = $elementPosition;
                                }
                                if (! $AdvancedTopMenuElementsClass->save()) {
                                    $this->context->controller->errors[] = $this->l('An error occurred while saving suppliers');
                                }
                                $elementPosition++;
                            } else {
                                $this->context->controller->errors[] = $this->l('An error occurred while saving suppliers');
                            }
                        }
                    }
                }
                break;
        }
    }
    private function udpdateColumnType($AdvancedTopMenuColumnClass)
    {
        switch ($AdvancedTopMenuColumnClass->type) {
            case 3:
                if (Tools::getValue('include_subs')) {
                    if ($AdvancedTopMenuColumnClass->id_category) {
                        $childCategories = array();
                        $childCategories = $this->getSubCategoriesId($AdvancedTopMenuColumnClass->id_category);
                        if (count($childCategories)) {
                            $elementPosition = 0;
                            foreach ($childCategories as $childCategory) {
                                $id_element = false;
                                if (Tools::getValue('id_column', false)) {
                                    $id_element = AdvancedTopMenuElementsClass::getIdElementCategoryDepend(Tools::getValue('id_column'), $childCategory ['id_category']);
                                    if (! $id_element && ! Tools::getValue('rebuild')) {
                                        continue;
                                    }
                                }
                                $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                                $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                                $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuColumnClass->type;
                                $AdvancedTopMenuElementsClass->id_category = $childCategory ['id_category'];
                                $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                                $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                                if (!$id_element) {
                                    $AdvancedTopMenuElementsClass->position = $elementPosition;
                                }
                                if (! $AdvancedTopMenuElementsClass->save()) {
                                    $this->context->controller->errors[] = $this->l('An error occurred while saving children category');
                                }
                                $elementPosition++;
                            }
                        }
                    }
                }
                break;
            case 4:
                if (Tools::getValue('include_subs_manu')) {
                    $manufacturersId = $this->getManufacturersId();
                    if (count($manufacturersId)) {
                        $elementPosition = 0;
                        foreach ($manufacturersId as $manufacturerId) {
                            $id_element = false;
                            if (Tools::getValue('id_column', false)) {
                                $id_element = AdvancedTopMenuElementsClass::getIdElementManufacturerDepend(Tools::getValue('id_column'), $manufacturerId ['id_manufacturer']);
                                if (! $id_element && ! Tools::getValue('rebuild')) {
                                    continue;
                                }
                            }
                            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                            $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                            $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuColumnClass->type;
                            $AdvancedTopMenuElementsClass->id_manufacturer = $manufacturerId ['id_manufacturer'];
                            $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                            $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                            if (!$id_element) {
                                $AdvancedTopMenuElementsClass->position = $elementPosition;
                            }
                            if (! $AdvancedTopMenuElementsClass->save()) {
                                $this->context->controller->errors[] = $this->l('An error occurred while saving manufacturers');
                            }
                            $elementPosition++;
                        }
                    }
                }
                break;
            case 5:
                if (Tools::getValue('include_subs_suppl')) {
                    $suppliersId = $this->getSuppliersId();
                    if (count($suppliersId)) {
                        $elementPosition = 0;
                        foreach ($suppliersId as $supplierId) {
                            $id_element = false;
                            if (Tools::getValue('id_column', false)) {
                                $id_element = AdvancedTopMenuElementsClass::getIdElementSupplierDepend(Tools::getValue('id_column'), $supplierId ['id_supplier']);
                                if (! $id_element && ! Tools::getValue('rebuild')) {
                                    continue;
                                }
                            }
                            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
                            $AdvancedTopMenuElementsClass->active = ($id_element ? $AdvancedTopMenuElementsClass->active : 1);
                            ;
                            $AdvancedTopMenuElementsClass->type = $AdvancedTopMenuColumnClass->type;
                            $AdvancedTopMenuElementsClass->id_supplier = $supplierId ['id_supplier'];
                            $AdvancedTopMenuElementsClass->id_column = $AdvancedTopMenuColumnClass->id;
                            $AdvancedTopMenuElementsClass->id_column_depend = $AdvancedTopMenuColumnClass->id;
                            if (!$id_element) {
                                $AdvancedTopMenuElementsClass->position = $elementPosition;
                            }
                            if (! $AdvancedTopMenuElementsClass->save()) {
                                $this->context->controller->errors[] = $this->l('An error occurred while saving suppliers');
                            }
                            $elementPosition++;
                        }
                    }
                }
                break;
        }
    }
    public function getManufacturersId()
    {
        return Db::getInstance()->ExecuteS('
    SELECT m.`id_manufacturer`
    FROM `' . _DB_PREFIX_ . 'manufacturer` m
    ORDER BY m.`name` ASC');
    }
    public function getSuppliersId()
    {
        return Db::getInstance()->ExecuteS('
    SELECT s.`id_supplier`
    FROM `' . _DB_PREFIX_ . 'supplier` s
    ORDER BY s.`name` ASC');
    }
    public function getSubCategoriesId($id_category, $active = true, $with_position = false)
    {
        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }
        if (!Validate::isBool($with_position)) {
            die(Tools::displayError());
        }
        $orderBy = 'c.`id_category`';
        $with_position_field = 'c.`id_category`';
        $orderBy = 'category_shop.`position`';
        $with_position_field = 'category_shop.`position`';
        $result = Db::getInstance()->ExecuteS('
        SELECT c.id_category'.($with_position ? ', '.$with_position_field : '').'
        FROM `' . _DB_PREFIX_ . 'category` c
        ' . Shop::addSqlAssociation('category', 'c') . '
        WHERE `id_parent` = ' . (int)$id_category . '
        ' . ($active ? 'AND `active` = 1' : '') . '
        GROUP BY c.`id_category`
        ORDER BY ' . $orderBy . ' ASC');
        return $result;
    }
    private function getFileExtension($filename)
    {
        $split = explode('.', $filename);
        $extension = end($split);
        $extension = Tools::strtolower($extension);
        return $extension;
    }
    private function _postProcessMenu()
    {
        $id_menu = Tools::getValue('id_menu', false);
        $AdvancedTopMenuClass = new AdvancedTopMenuClass($id_menu);
        $this->context->controller->errors = $AdvancedTopMenuClass->validateController();
        if (!Tools::getValue('type', 0)) {
            $this->context->controller->errors[] = $this->l('The type of the tab is required.');
        } elseif (Tools::getValue('type') == 1 && !Tools::getValue('id_cms')) {
            $this->context->controller->errors[] = $this->l('You need to select the related CMS.');
        } elseif (Tools::getValue('type') == 3 && !Tools::getValue('id_category')) {
            $this->context->controller->errors[] = $this->l('You need to select the related category.');
        } elseif (Tools::getValue('type') == 4 && !Tools::getValue('include_subs_manu') && !Tools::getValue('id_manufacturer')) {
            $this->context->controller->errors[] = $this->l('You need to select the related manufacturer.');
        } elseif (Tools::getValue('type') == 5 && !Tools::getValue('include_subs_suppl') && !Tools::getValue('id_supplier')) {
            $this->context->controller->errors[] = $this->l('You need to select the related supplier.');
        } elseif (Tools::getValue('type') == 9 && !Tools::getValue('id_specific_page')) {
            $this->context->controller->errors[] = $this->l('You need to select the related specific page.');
        }
        if (! count($this->context->controller->errors)) {
            $this->copyFromPost($AdvancedTopMenuClass);
            $AdvancedTopMenuClass->border_size_tab = $this->getBorderSizeFromArray(Tools::getValue('border_size_tab'));
            $AdvancedTopMenuClass->border_size_submenu = $this->getBorderSizeFromArray(Tools::getValue('border_size_submenu'));
            $fnd_color_menu_tab = Tools::getValue('fnd_color_menu_tab');
            $AdvancedTopMenuClass->fnd_color_menu_tab = $fnd_color_menu_tab[0] . (Tools::getValue('fnd_color_menu_tab_gradient') && isset($fnd_color_menu_tab[1]) && $fnd_color_menu_tab[1] ? $this->gradient_separator . $fnd_color_menu_tab[1] : '');
            $fnd_color_menu_tab_over = Tools::getValue('fnd_color_menu_tab_over');
            $AdvancedTopMenuClass->fnd_color_menu_tab_over = $fnd_color_menu_tab_over[0] . (Tools::getValue('fnd_color_menu_tab_over_gradient') && isset($fnd_color_menu_tab_over[1]) && $fnd_color_menu_tab_over[1] ? $this->gradient_separator . $fnd_color_menu_tab_over[1] : '');
            $fnd_color_submenu = Tools::getValue('fnd_color_submenu');
            $AdvancedTopMenuClass->fnd_color_submenu = $fnd_color_submenu[0] . (Tools::getValue('fnd_color_submenu_gradient') && isset($fnd_color_submenu[1]) && $fnd_color_submenu[1] ? $this->gradient_separator . $fnd_color_submenu[1] : '');
            $AdvancedTopMenuClass->chosen_groups = Tools::getIsset('chosen_groups') ? Tools::jsonEncode(Tools::getValue('chosen_groups')) : '';
            if (!Tools::getValue('tinymce_container_toggle_menu', 0)) {
                $AdvancedTopMenuClass->value_over = array();
                $AdvancedTopMenuClass->value_under = array();
            }
            if (($AdvancedTopMenuClass->type == 4 && Tools::getValue('include_subs_manu')) || ($AdvancedTopMenuClass->type == 5 && Tools::getValue('include_subs_suppl'))) {
                $AdvancedTopMenuClass->id_manufacturer = 0;
                $AdvancedTopMenuClass->id_supplier = 0;
                if ($AdvancedTopMenuClass->type == 4) {
                    foreach ($AdvancedTopMenuClass->name as $id_lang => $name) {
                        $title = '';
                        if (empty($name)) {
                            if (class_exists('Meta') && method_exists('Meta', 'getMetaByPage')) {
                                $title = Meta::getMetaByPage('manufacturer', $id_lang);
                                if (is_array($title) && isset($title['title']) && !empty($title['title'])) {
                                    $title = $title['title'];
                                }
                            }
                            if (empty($title)) {
                                $title = $this->l('Manufacturers');
                            }
                            $AdvancedTopMenuClass->name[$id_lang] = $title;
                        }
                    }
                } elseif ($AdvancedTopMenuClass->type == 5) {
                    foreach ($AdvancedTopMenuClass->name as $id_lang => $name) {
                        $title = '';
                        if (empty($name)) {
                            if (class_exists('Meta') && method_exists('Meta', 'getMetaByPage')) {
                                $title = Meta::getMetaByPage('supplier', $id_lang);
                                if (is_array($title) && isset($title['title']) && !empty($title['title'])) {
                                    $title = $title['title'];
                                }
                            }
                            if (empty($title)) {
                                $title = $this->l('Suppliers');
                            }
                            $AdvancedTopMenuClass->name[$id_lang] = $title;
                        }
                    }
                }
            }
            $languages = Language::getLanguages(false);
            if (! $id_menu) {
                if (! $AdvancedTopMenuClass->add()) {
                    $this->context->controller->errors[] = $this->l('An error occurred while adding the tab');
                }
            } elseif (! $AdvancedTopMenuClass->update()) {
                $this->context->controller->errors[] = $this->l('An error occurred while updating the tab');
            }
            if (! count($this->context->controller->errors)) {
                $this->udpdateMenuType($AdvancedTopMenuClass);
                if (! count($this->context->controller->errors)) {
                    foreach ($languages as $language) {
                        $fileKey = 'icon_' . $language['id_lang'];
                        if (isset($_FILES[$fileKey]['tmp_name']) and $_FILES[$fileKey]['tmp_name'] != null) {
                            $ext = $this->getFileExtension($_FILES[$fileKey]['name']);
                            if (! in_array($ext, $this->allowFileExtension) || ! getimagesize($_FILES[$fileKey]['tmp_name']) || ! move_uploaded_file($_FILES[$fileKey]['tmp_name'], _PS_ROOT_DIR_ . '/modules/' . $this->name . '/menu_icons/' . $AdvancedTopMenuClass->id . '-' . $language['iso_code'] . '.' . $ext)) {
                                $this->context->controller->errors[] = $this->l('An error occurred during the image upload');
                            } else {
                                $AdvancedTopMenuClass->image_type[$language['id_lang']] = $ext;
                                $AdvancedTopMenuClass->have_icon[$language['id_lang']] = 1;
                                $AdvancedTopMenuClass->update();
                            }
                        } elseif (Tools::getValue('unlink_icon_' . $language['id_lang'])) {
                            unlink(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/menu_icons/' . $AdvancedTopMenuClass->id . '-' . $language['iso_code'] . '.' . ($AdvancedTopMenuClass->image_type[$language['id_lang']] ? $AdvancedTopMenuClass->image_type[$language['id_lang']] : 'jpg'));
                            $AdvancedTopMenuClass->have_icon[$language['id_lang']] = '';
                            $AdvancedTopMenuClass->image_type[$language['id_lang']] = '';
                            $AdvancedTopMenuClass->image_legend[$language['id_lang']] = '';
                            $AdvancedTopMenuClass->update();
                        }
                    }
                    $this->generateCss();
                    $this->context->controller->confirmations[] = $this->l('The tab has successfully been updated');
                }
            }
            unset($_POST ['active']);
        }
    }
    private function _postProcessColumnWrap()
    {
        $id_wrap = Tools::getValue('id_wrap', false);
        $id_menu = Tools::getValue('id_menu', false);
        if (!$id_menu) {
            $this->context->controller->errors[] = $this->l('An error occurred while adding the column - Parent tab is not set');
        } else {
            $AdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass($id_wrap);
            $this->context->controller->errors = $AdvancedTopMenuColumnWrapClass->validateController();
            if (! count($this->context->controller->errors)) {
                $this->copyFromPost($AdvancedTopMenuColumnWrapClass);
                $bg_color = Tools::getValue('bg_color');
                $AdvancedTopMenuColumnWrapClass->bg_color = $bg_color[0] . (Tools::getValue('bg_color_gradient') && isset($bg_color[1]) && $bg_color[1] ? $this->gradient_separator . $bg_color[1] : '');
                $AdvancedTopMenuColumnWrapClass->chosen_groups = Tools::getIsset('chosen_groups') ? Tools::jsonEncode(Tools::getValue('chosen_groups')) : '';
                if (!Tools::getValue('tinymce_container_toggle_menu', 0)) {
                    $AdvancedTopMenuColumnWrapClass->value_over = array();
                    $AdvancedTopMenuColumnWrapClass->value_under = array();
                }
                unset($_POST ['active']);
                if (! $id_wrap) {
                    if (! $AdvancedTopMenuColumnWrapClass->add()) {
                        $this->context->controller->errors[] = $this->l('An error occurred while adding the column');
                    }
                } elseif (! $AdvancedTopMenuColumnWrapClass->update()) {
                    $this->context->controller->errors[] = $this->l('An error occurred while updating the column');
                }
                if (! count($this->context->controller->errors)) {
                    $this->generateCss();
                    $this->context->controller->confirmations[] = $this->l('The column has successfully been updated');
                }
            }
        }
    }
    private function _postProcessColumn()
    {
        $id_column = Tools::getValue('id_column', false);
        $AdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass($id_column);
        $this->context->controller->errors = $AdvancedTopMenuColumnClass->validateController();
        if (!Tools::getValue('type', 0)) {
            $this->context->controller->errors[] = $this->l('The type of the column is required.');
        } elseif (Tools::getValue('type') == 1 && !Tools::getValue('id_cms')) {
            $this->context->controller->errors[] = $this->l('You need to select the related CMS.');
        } elseif (Tools::getValue('type') == 3 && !Tools::getValue('id_category')) {
            $this->context->controller->errors[] = $this->l('You need to select the related category.');
        } elseif (Tools::getValue('type') == 4 && !Tools::getValue('include_subs_manu') && !Tools::getValue('id_manufacturer')) {
            $this->context->controller->errors[] = $this->l('You need to select the related manufacturer.');
        } elseif (Tools::getValue('type') == 5 && !Tools::getValue('include_subs_suppl') && !Tools::getValue('id_supplier')) {
            $this->context->controller->errors[] = $this->l('You need to select the related supplier.');
        } elseif (Tools::getValue('type') == 9 && !Tools::getValue('id_specific_page')) {
            $this->context->controller->errors[] = $this->l('You need to select the related specific page.');
        }
        if (! count($this->context->controller->errors)) {
            $this->copyFromPost($AdvancedTopMenuColumnClass);
            $AdvancedTopMenuColumnClass->chosen_groups = Tools::getIsset('chosen_groups') ? Tools::jsonEncode(Tools::getValue('chosen_groups')) : '';
            if (!(int)$AdvancedTopMenuColumnClass->id_wrap) {
                $this->context->controller->errors[] = $this->l('You need to choose the parent column');
            }
            if (!Tools::getValue('tinymce_container_toggle_menu', 0)) {
                $AdvancedTopMenuColumnClass->value_over = array();
                $AdvancedTopMenuColumnClass->value_under = array();
            }
            if (!count($this->context->controller->errors)) {
                if ($AdvancedTopMenuColumnClass->type == 8) {
                    $productElementsObj = false;
                    if ($id_column) {
                        $productElementsObj = AdvancedTopMenuProductColumnClass::getByIdColumn($id_column);
                    }
                    if (!$productElementsObj) {
                        $productElementsObj = new AdvancedTopMenuProductColumnClass();
                        $productElementsObj->id_column = 1;
                    }
                    $this->copyFromPost($productElementsObj);
                    $this->context->controller->errors = $productElementsObj->validateController();
                }
                if (count($this->context->controller->errors)) {
                    return;
                }
            }
            $languages = Language::getLanguages(false);
            unset($_POST ['active']);
            if (! $id_column) {
                if (! $AdvancedTopMenuColumnClass->add()) {
                    $this->context->controller->errors[] = $this->l('An error occurred while adding the group of items');
                }
            } elseif (! $AdvancedTopMenuColumnClass->update()) {
                $this->context->controller->errors[] = $this->l('An error occurred while updating the group of items');
            }
            if (! count($this->context->controller->errors)) {
                $this->udpdateColumnType($AdvancedTopMenuColumnClass);
                foreach ($languages as $language) {
                    $fileKey = 'icon_' . $language['id_lang'];
                    if (isset($_FILES [$fileKey] ['tmp_name']) and $_FILES [$fileKey] ['tmp_name'] != null) {
                        $ext = $this->getFileExtension($_FILES [$fileKey] ['name']);
                        if (! in_array($ext, $this->allowFileExtension) || ! getimagesize($_FILES [$fileKey] ['tmp_name']) || ! move_uploaded_file($_FILES [$fileKey] ['tmp_name'], _PS_ROOT_DIR_ . '/modules/' . $this->name . '/column_icons/' . $AdvancedTopMenuColumnClass->id . '-' . $language['iso_code'] . '.' . $ext)) {
                            $this->context->controller->errors[] = $this->l('An error occurred during the image upload');
                        } else {
                            $AdvancedTopMenuColumnClass->image_type[$language['id_lang']] = $ext;
                            $AdvancedTopMenuColumnClass->have_icon[$language['id_lang']] = 1;
                            $AdvancedTopMenuColumnClass->update();
                        }
                    } elseif (Tools::getValue('unlink_icon_' . $language['id_lang'])) {
                        unlink(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/column_icons/' . $AdvancedTopMenuColumnClass->id . '-' . $language['iso_code'] . '.' . ($AdvancedTopMenuColumnClass->image_type[$language['id_lang']] ? $AdvancedTopMenuColumnClass->image_type[$language['id_lang']] : 'jpg'));
                        $AdvancedTopMenuColumnClass->have_icon[$language['id_lang']] = '';
                        $AdvancedTopMenuColumnClass->image_type[$language['id_lang']] = '';
                        $AdvancedTopMenuColumnClass->image_legend[$language['id_lang']] = '';
                        $AdvancedTopMenuColumnClass->update();
                    }
                }
                if ($AdvancedTopMenuColumnClass->type == 8) {
                    $productElementsObj->id_column = $AdvancedTopMenuColumnClass->id;
                    $productElementsObj->save();
                }
                $this->context->controller->confirmations[] = $this->l('The group of items has successfully been updated');
            }
        }
    }
    private function _postProcessColumnElement()
    {
        $id_element = Tools::getValue('id_element', false);
        $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass($id_element);
        $this->context->controller->errors = $AdvancedTopMenuElementsClass->validateController();
        if (!Tools::getValue('type', 0)) {
            $this->context->controller->errors[] = $this->l('The type of the element is required.');
        } elseif (Tools::getValue('type') == 1 && !Tools::getValue('id_cms')) {
            $this->context->controller->errors[] = $this->l('You need to select the related CMS.');
        } elseif (Tools::getValue('type') == 3 && !Tools::getValue('id_category')) {
            $this->context->controller->errors[] = $this->l('You need to select the related category.');
        } elseif (Tools::getValue('type') == 4 && !Tools::getValue('include_subs_manu') && !Tools::getValue('id_manufacturer')) {
            $this->context->controller->errors[] = $this->l('You need to select the related manufacturer.');
        } elseif (Tools::getValue('type') == 5 && !Tools::getValue('include_subs_suppl') && !Tools::getValue('id_supplier')) {
            $this->context->controller->errors[] = $this->l('You need to select the related supplier.');
        } elseif (Tools::getValue('type') == 9 && !Tools::getValue('id_specific_page')) {
            $this->context->controller->errors[] = $this->l('You need to select the related specific page.');
        }
        if (! count($this->context->controller->errors)) {
            $this->copyFromPost($AdvancedTopMenuElementsClass);
            $AdvancedTopMenuElementsClass->chosen_groups = Tools::getIsset('chosen_groups') ? Tools::jsonEncode(Tools::getValue('chosen_groups')) : '';
            $languages = Language::getLanguages(false);
            if (! $id_element) {
                if (! $AdvancedTopMenuElementsClass->add()) {
                    $this->context->controller->errors[] = $this->l('An error occurred while adding the item');
                }
            } elseif (! $AdvancedTopMenuElementsClass->update()) {
                $this->context->controller->errors[] = $this->l('An error occurred while updating the item');
            }
            if (! count($this->context->controller->errors)) {
                foreach ($languages as $language) {
                    $fileKey = 'icon_' . $language['id_lang'];
                    if (isset($_FILES [$fileKey] ['tmp_name']) and $_FILES [$fileKey] ['tmp_name'] != null) {
                        $ext = $this->getFileExtension($_FILES [$fileKey] ['name']);
                        if (! in_array($ext, $this->allowFileExtension) || ! getimagesize($_FILES [$fileKey] ['tmp_name']) || ! move_uploaded_file($_FILES [$fileKey] ['tmp_name'], _PS_ROOT_DIR_ . '/modules/' . $this->name . '/element_icons/' . $AdvancedTopMenuElementsClass->id . '-' . $language['iso_code'] . '.' . $ext)) {
                            $this->context->controller->errors[] = $this->l('An error occurred during the image upload');
                        } else {
                            $AdvancedTopMenuElementsClass->image_type[$language['id_lang']] = $ext;
                            $AdvancedTopMenuElementsClass->have_icon[$language['id_lang']] = 1;
                            $AdvancedTopMenuElementsClass->update();
                        }
                    } elseif (Tools::getValue('unlink_icon_' . $language['id_lang'])) {
                        unlink(_PS_ROOT_DIR_ . '/modules/' . $this->name . '/element_icons/' . $AdvancedTopMenuElementsClass->id . '-' . $language['iso_code'] . '.' . ($AdvancedTopMenuElementsClass->image_type[$language['id_lang']] ? $AdvancedTopMenuElementsClass->image_type[$language['id_lang']] : 'jpg'));
                        $AdvancedTopMenuElementsClass->have_icon[$language['id_lang']] = '';
                        $AdvancedTopMenuElementsClass->image_type[$language['id_lang']] = '';
                        $AdvancedTopMenuElementsClass->image_legend[$language['id_lang']] = '';
                        $AdvancedTopMenuElementsClass->update();
                    }
                }
                $this->context->controller->confirmations[] = $this->l('The element has successfully been updated');
            }
        }
    }
    public function outputSelectColumns($id_menu = false, $column_selected = false)
    {
        $columns = AdvancedTopMenuColumnClass::getMenuColumsByIdMenu((int)$id_menu, $this->context->cookie->id_lang, false);
        if (is_array($columns)) {
            foreach ($columns as $k => $column) {
                $columns[$k]['admin_name'] = $this->getAdminOutputNameValue($column, false);
            }
        }
        $this->context->smarty->assign(array(
            'columns' => $columns,
            'column_selected' => $column_selected,
        ));
        return $this->context->smarty->fetch(dirname(__FILE__).'/views/templates/admin/column_select.tpl');
    }
    public function outputSelectColumnsWrap($id_menu = false, $columnWrap_selected = false)
    {
        $columnsWrap = AdvancedTopMenuColumnWrapClass::getMenuColumnsWrap((int)$id_menu, $this->context->cookie->id_lang, false);
        $this->context->smarty->assign(array(
            'columnsWrap' => $columnsWrap,
            'columnWrap_selected' => $columnWrap_selected,
        ));
        return $this->context->smarty->fetch(dirname(__FILE__).'/views/templates/admin/columnwrap_select.tpl');
    }
    private function _postProcess()
    {
        if (Tools::getIsset('dismissRating')) {
            $this->_html = '';
            self::_cleanBuffer();
            Configuration::updateGlobalValue('PM_'.self::$_module_prefix.'_DISMISS_RATING', 1);
            die;
        }
        $this->saveConfig();
        $this->saveAdvancedConfig();
        if (Tools::getValue('activeMaintenance')) {
            echo $this->_postProcessMaintenance(self::$_module_prefix);
            die();
        } elseif (Tools::getValue('actionColumn') == 'get_select_columns') {
            $id_menu = Tools::getValue('id_menu', false);
            $column_selected = Tools::getValue('column_selected', false);
            self::_cleanBuffer();
            echo $this->outputSelectColumns($id_menu, $column_selected);
            die();
        } elseif (Tools::getValue('actionColumn') == 'get_select_columnsWrap') {
            $id_menu = Tools::getValue('id_menu', false);
            $columnWrap_selected = Tools::getValue('columnWrap_selected', false);
            self::_cleanBuffer();
            echo $this->outputSelectColumnsWrap($id_menu, $columnWrap_selected);
            die();
        } elseif (Tools::getIsset('columnElementsPosition')) {
            $order = Tools::getValue('columnElementsPosition') ? explode(',', Tools::getValue('columnElementsPosition')) : array();
            foreach ($order as $position => $id_element) {
                $row = array('position' => (int)$position );
                Db::getInstance()->update('pm_advancedtopmenu_elements', $row, 'id_element =' . (int)$id_element);
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $this->l('Saved');
            die();
        } elseif (Tools::getIsset('menuPosition')) {
            $order = Tools::getValue('menuPosition') ? explode(',', Tools::getValue('menuPosition')) : array();
            foreach ($order as $position => $id_menu) {
                if (! trim($id_menu)) {
                    continue;
                }
                $row = array('position' => (int)$position );
                Db::getInstance()->update('pm_advancedtopmenu', $row, 'id_menu =' . (int)$id_menu);
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $this->l('Saved');
            die();
        } elseif (Tools::getIsset('columnPosition')) {
            $order = Tools::getValue('columnPosition') ? explode(',', Tools::getValue('columnPosition')) : array();
            foreach ($order as $position => $id_column) {
                if (! trim($id_column)) {
                    continue;
                }
                $row = array('position' => (int)$position );
                Db::getInstance()->update('pm_advancedtopmenu_columns', $row, 'id_column =' . (int)$id_column);
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $this->l('Saved');
            die();
        } elseif (Tools::getIsset('columnWrapPosition')) {
            $order = Tools::getValue('columnWrapPosition') ? explode(',', Tools::getValue('columnWrapPosition')) : array();
            foreach ($order as $position => $id_wrap) {
                if (! trim($id_wrap)) {
                    continue;
                }
                $row = array('position' => (int)$position );
                Db::getInstance()->update('pm_advancedtopmenu_columns_wrap', $row, 'id_wrap =' . (int)$id_wrap);
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $this->l('Saved');
            die();
        } elseif (Tools::getValue('activeMenu') && Tools::getValue('id_menu')) {
            $return = '';
            $ObjAdvancedTopMenuClass = new AdvancedTopMenuClass(Tools::getValue('id_menu'));
            $ObjAdvancedTopMenuClass->active = ($ObjAdvancedTopMenuClass->active ? 0 : 1);
            if ($ObjAdvancedTopMenuClass->save()) {
                $return .= '$("#imgActiveMenu' . $ObjAdvancedTopMenuClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuClass->active ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activemenu","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activemenu","' . $this->l('An error occurred while updating the tab') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeColumnWrap') && Tools::getValue('id_wrap')) {
            $return = '';
            $ObjAdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass(Tools::getValue('id_wrap'));
            $ObjAdvancedTopMenuColumnWrapClass->active = ($ObjAdvancedTopMenuColumnWrapClass->active ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnWrapClass->save()) {
                $return .= '$("#imgActiveColumnWrap' . $ObjAdvancedTopMenuColumnWrapClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnWrapClass->active ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activecolumnwrap","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activecolumnwrap","' . $this->l('An error occurred while updating the column') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeColumn') && Tools::getValue('id_column')) {
            $return = '';
            $ObjAdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass(Tools::getValue('id_column'));
            $ObjAdvancedTopMenuColumnClass->active = ($ObjAdvancedTopMenuColumnClass->active ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnClass->save()) {
                $return .= '$("#imgActiveColumn' . $ObjAdvancedTopMenuColumnClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnClass->active ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activegroup","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activegroup","' . $this->l('An error occurred while updating the group of items') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeElement') && Tools::getValue('id_element')) {
            $return = '';
            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass(Tools::getValue('id_element'));
            $AdvancedTopMenuElementsClass->active = ($AdvancedTopMenuElementsClass->active ? 0 : 1);
            if ($AdvancedTopMenuElementsClass->save()) {
                $return .= '$("#imgActiveElement' . $AdvancedTopMenuElementsClass->id . '").attr("src","../img/admin/' . ($AdvancedTopMenuElementsClass->active ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activeelement","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activeelement","' . $this->l('An error occurred while updating the item') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeDesktopMenu') && Tools::getValue('id_menu')) {
            $return = '';
            $ObjAdvancedTopMenuClass = new AdvancedTopMenuClass(Tools::getValue('id_menu'));
            $ObjAdvancedTopMenuClass->active_desktop = ($ObjAdvancedTopMenuClass->active_desktop ? 0 : 1);
            if ($ObjAdvancedTopMenuClass->save()) {
                $return .= '$("#imgActiveDesktopMenu' . $ObjAdvancedTopMenuClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuClass->active_desktop ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activedesktopmenu","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activedesktopmenu","' . $this->l('An error occurred while updating the tab') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeMobileMenu') && Tools::getValue('id_menu')) {
            $return = '';
            $ObjAdvancedTopMenuClass = new AdvancedTopMenuClass(Tools::getValue('id_menu'));
            $ObjAdvancedTopMenuClass->active_mobile = ($ObjAdvancedTopMenuClass->active_mobile ? 0 : 1);
            if ($ObjAdvancedTopMenuClass->save()) {
                $return .= '$("#imgActiveMobileMenu' . $ObjAdvancedTopMenuClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuClass->active_mobile ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activemobilemenu","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activemobilemenu","' . $this->l('An error occurred while updating the tab') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeDesktopColumnWrap') && Tools::getValue('id_wrap')) {
            $return = '';
            $ObjAdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass(Tools::getValue('id_wrap'));
            $ObjAdvancedTopMenuColumnWrapClass->active_desktop = ($ObjAdvancedTopMenuColumnWrapClass->active_desktop ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnWrapClass->save()) {
                $return .= '$("#imgActiveDesktopColumnWrap' . $ObjAdvancedTopMenuColumnWrapClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnWrapClass->active_desktop ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activedesktopcolumnwrap","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activedesktopcolumnwrap","' . $this->l('An error occurred while updating the column') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeMobileColumnWrap') && Tools::getValue('id_wrap')) {
            $return = '';
            $ObjAdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass(Tools::getValue('id_wrap'));
            $ObjAdvancedTopMenuColumnWrapClass->active_mobile = ($ObjAdvancedTopMenuColumnWrapClass->active_mobile ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnWrapClass->save()) {
                $return .= '$("#imgActiveMobileColumnWrap' . $ObjAdvancedTopMenuColumnWrapClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnWrapClass->active_mobile ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activemobilecolumnwrap","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activemobilecolumnwrap","' . $this->l('An error occurred while updating the column') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeDesktopColumn') && Tools::getValue('id_column')) {
            $return = '';
            $ObjAdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass(Tools::getValue('id_column'));
            $ObjAdvancedTopMenuColumnClass->active_desktop = ($ObjAdvancedTopMenuColumnClass->active_desktop ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnClass->save()) {
                $return .= '$("#imgActiveDesktopColumn' . $ObjAdvancedTopMenuColumnClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnClass->active_desktop ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activedesktopgroup","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activedesktopgroup","' . $this->l('An error occurred while updating the group of items') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeMobileColumn') && Tools::getValue('id_column')) {
            $return = '';
            $ObjAdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass(Tools::getValue('id_column'));
            $ObjAdvancedTopMenuColumnClass->active_mobile = ($ObjAdvancedTopMenuColumnClass->active_mobile ? 0 : 1);
            if ($ObjAdvancedTopMenuColumnClass->save()) {
                $return .= '$("#imgActiveMobileColumn' . $ObjAdvancedTopMenuColumnClass->id . '").attr("src","../img/admin/' . ($ObjAdvancedTopMenuColumnClass->active_mobile ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activemobilegroup","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activemobilegroup","' . $this->l('An error occurred while updating the group of items') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeDesktopElement') && Tools::getValue('id_element')) {
            $return = '';
            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass(Tools::getValue('id_element'));
            $AdvancedTopMenuElementsClass->active_desktop = ($AdvancedTopMenuElementsClass->active_desktop ? 0 : 1);
            if ($AdvancedTopMenuElementsClass->save()) {
                $return .= '$("#imgActiveDesktopElement' . $AdvancedTopMenuElementsClass->id . '").attr("src","../img/admin/' . ($AdvancedTopMenuElementsClass->active_desktop ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activedesktopelement","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activedesktopelement","' . $this->l('An error occurred while updating the item') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('activeMobileElement') && Tools::getValue('id_element')) {
            $return = '';
            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass(Tools::getValue('id_element'));
            $AdvancedTopMenuElementsClass->active_mobile = ($AdvancedTopMenuElementsClass->active_mobile ? 0 : 1);
            if ($AdvancedTopMenuElementsClass->save()) {
                $return .= '$("#imgActiveMobileElement' . $AdvancedTopMenuElementsClass->id . '").attr("src","../img/admin/' . ($AdvancedTopMenuElementsClass->active_mobile ? 'enabled' : 'disabled') . '.gif");';
                $return .= 'show_info("activemobileelement","' . $this->l('Saved') . '");';
            } else {
                $return .= 'show_info("activemobileelement","' . $this->l('An error occurred while updating the item') . '");';
            }
            $this->clearCache();
            self::_cleanBuffer();
            echo $return;
            die();
        } elseif (Tools::getValue('deleteMenu') && Tools::getValue('id_menu')) {
            $ObjAdvancedTopMenuClass = new AdvancedTopMenuClass(Tools::getValue('id_menu'));
            if ($ObjAdvancedTopMenuClass->delete()) {
                $this->context->controller->confirmations[] = $this->l('The tab was successfully deleted');
            } else {
                $this->context->controller->errors[] = $this->l('An error occurred while deleting the column');
            }
            $this->clearCache();
        } elseif (Tools::getValue('deleteColumnWrap') && Tools::getValue('id_wrap')) {
            $ObjAdvancedTopMenuColumnWrapClass = new AdvancedTopMenuColumnWrapClass(Tools::getValue('id_wrap'));
            if ($ObjAdvancedTopMenuColumnWrapClass->delete()) {
                $this->context->controller->confirmations[] = $this->l('The column was successfully deleted');
            } else {
                $this->context->controller->errors[] = $this->l('An error occurred while deleting the column');
            }
            $this->clearCache();
        } elseif (Tools::getValue('deleteColumn') && Tools::getValue('id_column')) {
            $ObjAdvancedTopMenuColumnClass = new AdvancedTopMenuColumnClass(Tools::getValue('id_column'));
            if ($ObjAdvancedTopMenuColumnClass->delete()) {
                $this->context->controller->confirmations[] = $this->l('The group of items was successfully deleted');
            } else {
                $this->context->controller->errors[] = $this->l('An error occurred while deleting the group of items');
            }
            $this->clearCache();
        } elseif (Tools::getValue('deleteElement') && Tools::getValue('id_element')) {
            $AdvancedTopMenuElementsClass = new AdvancedTopMenuElementsClass(Tools::getValue('id_element'));
            if ($AdvancedTopMenuElementsClass->delete()) {
                $this->context->controller->confirmations[] = $this->l('The item was successfully deleted');
            } else {
                $this->context->controller->errors[] = $this->l('An error occurred while deleting the item');
            }
            $this->clearCache();
        } elseif (Tools::isSubmit('submitMenu')) {
            $this->_postProcessMenu();
            $this->clearCache();
        } elseif (Tools::isSubmit('submitColumnWrap')) {
            $this->_postProcessColumnWrap();
            $this->clearCache();
        } elseif (Tools::isSubmit('submitColumn')) {
            $this->_postProcessColumn();
            $this->clearCache();
        } elseif (Tools::isSubmit('submitElement')) {
            $this->_postProcessColumnElement();
            $this->clearCache();
        } elseif (Tools::isSubmit('submitFastChangeColumn')) {
            $id_column = Tools::getValue('id_column');
            $id_wrap = Tools::getValue('id_wrap');
            if ($id_wrap && $id_column) {
                $row = array('id_wrap' => (int)$id_wrap );
                Db::getInstance()->update('pm_advancedtopmenu_columns', $row, 'id_column =' . (int)$id_column);
            }
            $this->clearCache();
        }
    }
    private function getKeepVar($vars = false)
    {
        if (!(int)Configuration::get('PS_REWRITING_SETTINGS')) {
            if ($vars) {
                parse_str($vars, $vars);
            } else {
                $vars = $_GET;
            }
            foreach ($this->keepVarActif as $key) {
                if (isset($vars [$key])) {
                    return '?' . $key . '=' . (int)$vars [$key];
                }
            }
        }
        return '';
    }
    private function getBorderSizeFromArray($borderArray)
    {
        if (! is_array($borderArray)) {
            return false;
        }
        $borderStr = '';
        $borderCountEmpty = 0;
        foreach ($borderArray as $border) {
            if ($border === '') {
                $borderCountEmpty ++;
            }
            if ($border == 'auto') {
                $borderStr .= 'auto ';
            } else {
                $borderStr .= (int)$border . 'px ';
            }
        }
        return ($borderCountEmpty < count($borderArray) ? Tools::substr($borderStr, 0, - 1) : 0);
    }
    private function getPositionSizeFromArray($positionArray, $toCSSString = true)
    {
        if (!is_array($positionArray) || count($positionArray) < 4) {
            return '';
        }
        $positionStr = '';
        if ($toCSSString) {
            if (Tools::strlen(trim($positionArray[0])) > 0) {
                $positionStr .= 'top:' . (int)$positionArray[0] . 'px;';
            }
            if (Tools::strlen(trim($positionArray[1])) > 0) {
                $positionStr .= 'right:' . (int)$positionArray[1] . 'px;';
            }
            if (Tools::strlen(trim($positionArray[2])) > 0) {
                $positionStr .= 'bottom:' . (int)$positionArray[2] . 'px;';
            }
            if (Tools::strlen(trim($positionArray[3])) > 0) {
                $positionStr .= 'left:' . (int)$positionArray[3] . 'px;';
            }
        } else {
            foreach ($positionArray as $position) {
                if (Tools::strlen(trim($position)) > 0) {
                    $positionStr .= (int)$position . 'px ';
                } else {
                    $positionStr .= ' ';
                }
            }
        }
        return $positionStr;
    }
    private function _getConfigKeys()
    {
        $config = $configResponsive = array();
        foreach ($this->_fieldsOptions as $key => $data) {
            if (isset($data['mobile']) && $data['mobile']) {
                $configResponsive[] = $key;
            } else {
                $config[] = $key;
            }
        }
        return array($config, $configResponsive);
    }
    protected function generateGlobalCss($id_shop = false)
    {
        list($config, $configResponsive) = $this->_getConfigKeys();
        if ($id_shop != false) {
            $configGlobalCss = Configuration::getMultiple($config, null, null, $id_shop);
            $configResponsiveCss = Configuration::getMultiple($configResponsive, null, null, $id_shop);
        } else {
            $configGlobalCss = Configuration::getMultiple($config);
            $configResponsiveCss = Configuration::getMultiple($configResponsive);
        }
        if (empty($configResponsiveCss['ATMR_MENU_BGCOLOR_OP'])) {
            $configResponsiveCss['ATMR_MENU_BGCOLOR_OP'] = $configGlobalCss['ATM_MENU_BGCOLOR_OVER'];
        }
        if (empty($configResponsiveCss['ATMR_MENU_BGCOLOR_CL'])) {
            $configResponsiveCss['ATMR_MENU_BGCOLOR_CL'] = $configGlobalCss['ATM_MENU_BGCOLOR'];
        }
        $hoverCSSselector = ':hover';
        if (!empty($configGlobalCss['ATM_SUBMENU_OPEN_METHOD']) && $configGlobalCss['ATM_SUBMENU_OPEN_METHOD'] == 2) {
            $hoverCSSselector = '.atm_clicked';
        }
        $specificDesktopCss = array();
        $css = array();
        $configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR'] = explode($this->gradient_separator, $configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR']);
        if (isset($configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR'] [1])) {
            $color1 = htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8');
            $color2 = htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR'] [1], ENT_COMPAT, 'UTF-8');
            $css [] = '#adtm_menu_inner {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
        } else {
            $css [] = '#adtm_menu_inner {background-color:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8') . ';}';
        }
        $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY'] = round($configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY'] / 100, 1);
        if ($configGlobalCss['ATM_MENU_CONT_POSITION'] == 'sticky') {
            $css [] = '#adtm_menu {position:relative;padding:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_MARGIN'], ENT_COMPAT, 'UTF-8') . ';border-color:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';-moz-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -webkit-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -o-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; filter:progid:DXImageTransform.Microsoft.Shadow(color=' . htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], ENT_COMPAT, 'UTF-8') . ', Direction=137, Strength=0);}';
        } else {
            $css [] = '#adtm_menu {position:' . htmlentities($configGlobalCss['ATM_MENU_CONT_POSITION'], ENT_COMPAT, 'UTF-8') . ';padding:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_MARGIN'], ENT_COMPAT, 'UTF-8') . ';border-color:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configGlobalCss ['ATM_MENU_CONT_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';-moz-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -webkit-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -o-box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; box-shadow: '. htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_MENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; filter:progid:DXImageTransform.Microsoft.Shadow(color=' . htmlentities($configGlobalCss['ATM_MENU_BOX_SHADOWCOLOR'], ENT_COMPAT, 'UTF-8') . ', Direction=137, Strength=0);}';
        }
        $configGlobalCss['ATM_MENU_CONT_POSITION_TRBL'] = $this->getPositionSizeFromArray(explode(' ', $configGlobalCss['ATM_MENU_CONT_POSITION_TRBL']));
        if (!empty($configGlobalCss['ATM_MENU_CONT_POSITION_TRBL'])) {
            $css [] = '#adtm_menu {' . htmlentities($configGlobalCss['ATM_MENU_CONT_POSITION_TRBL'], ENT_COMPAT, 'UTF-8') . '}';
        }
        $css [] = '#adtm_menu_inner {padding:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_MARGIN'], ENT_COMPAT, 'UTF-8') . ';border-color:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .li-niveau1 a.a-niveau1 {min-height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px;line-height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px;}';
        $css [] = '#adtm_menu .li-niveau1 a.a-niveau1.a-multiline {line-height:' . number_format((int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT']/2, 2) . 'px;}';
        if ($configGlobalCss ['ATM_MENU_GLOBAL_WIDTH']) {
            $css [] = '#adtm_menu_inner {width:' . htmlentities($configGlobalCss ['ATM_MENU_GLOBAL_WIDTH'], ENT_COMPAT, 'UTF-8') . 'px !important;}';
        }
        $css [] = '#adtm_menu .li-niveau1 {min-height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px; line-height:' . ((int)$configGlobalCss ['ATM_COLUMN_FONT_SIZE']+ 5) . 'px;}';
        $css [] = '#adtm_menu .li-niveau1 a.a-niveau1 .advtm_menu_span {min-height:' . ((int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT']) . 'px;line-height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px;}';
        $css [] = '#adtm_menu .li-niveau1 a.a-niveau1.a-multiline .advtm_menu_span {line-height:' . number_format((int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT']/2, 2) . 'px;}';
        $css [] = '#adtm_menu .li-niveau1 .searchboxATM { display: table-cell; height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px; vertical-align: middle; }';
        $css [] = '#adtm_menu .li-niveau1 .searchboxATM .adtm_search_submit_button { height:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] . 'px; }';
        $topDiff = 0;
        $atmMenuMarginTable = explode(' ', $configGlobalCss['ATM_MENU_MARGIN']);
        $atmMenuPaddingTable = explode(' ', $configGlobalCss['ATM_MENU_PADDING']);
        if (count($atmMenuMarginTable) == 4) {
            $topDiff += (int)$atmMenuMarginTable[0] + (int)$atmMenuMarginTable[2];
        }
        if (count($atmMenuPaddingTable) == 4) {
            $topDiff += (int)$atmMenuPaddingTable[0] + (int)$atmMenuPaddingTable[2];
        }
        $css [] = '#adtm_menu ul#menu li div.adtm_sub {top:' . ((int)$configGlobalCss ['ATM_MENU_GLOBAL_HEIGHT'] + $topDiff) . 'px;}';
        $css [] = '.li-niveau1 a span {padding:' . htmlentities($configGlobalCss ['ATM_MENU_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_MENU_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '.li-niveau1 .advtm_menu_span, .li-niveau1 a .advtm_menu_span {color:' . htmlentities($configGlobalCss ['ATM_MENU_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
        $configGlobalCss ['ATM_MENU_BGCOLOR'] = explode($this->gradient_separator, $configGlobalCss ['ATM_MENU_BGCOLOR']);
        if (isset($configGlobalCss ['ATM_MENU_BGCOLOR'] [1])) {
            $color1 = htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8');
            $color2 = htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR'] [1], ENT_COMPAT, 'UTF-8');
            $css [] = '.li-niveau1 a .advtm_menu_span, .li-niveau1 .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
        } else {
            $css [] = '.li-niveau1 a .advtm_menu_span, .li-niveau1 .advtm_menu_span {background-color:' . htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8') . ';}';
        }
        $configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] = explode($this->gradient_separator, $configGlobalCss ['ATM_MENU_BGCOLOR_OVER']);
        if (isset($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [1])) {
            $color1 = htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [0], ENT_COMPAT, 'UTF-8');
            $color2 = htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [1], ENT_COMPAT, 'UTF-8');
            $specificDesktopCss[] = '.li-niveau1 a:hover .advtm_menu_span, .li-niveau1 .advtm_menu_span:hover, .li-niveau1:hover > a.a-niveau1 .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            $css [] = '.li-niveau1 a.advtm_menu_actif .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            if ($hoverCSSselector != ':hover') {
                $css [] = '.li-niveau1' . $hoverCSSselector . ' a .advtm_menu_span, .li-niveau1 a.advtm_menu_actif .advtm_menu_span, .li-niveau1' . $hoverCSSselector . ' .advtm_menu_span, .li-niveau1' . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            }
        } else {
            $specificDesktopCss[] = '.li-niveau1 a:hover .advtm_menu_span, .li-niveau1 .advtm_menu_span:hover, .li-niveau1:hover > a.a-niveau1 .advtm_menu_span {background-color:' . htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [0], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '.li-niveau1 a.advtm_menu_actif .advtm_menu_span {background-color:' . htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [0], ENT_COMPAT, 'UTF-8') . ';}';
            if ($hoverCSSselector != ':hover') {
                $css [] = '.li-niveau1' . $hoverCSSselector . ' a .advtm_menu_span, .li-niveau1 a.advtm_menu_actif .advtm_menu_span, .li-niveau1' . $hoverCSSselector . ' .advtm_menu_span, .li-niveau1' . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span {background-color:' . htmlentities($configGlobalCss ['ATM_MENU_BGCOLOR_OVER'] [0], ENT_COMPAT, 'UTF-8') . ';}';
            }
        }
        $css [] = '.li-niveau1 a.a-niveau1 {border-color:' . htmlentities($configGlobalCss ['ATM_MENU_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configGlobalCss ['ATM_MENU_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';}';
        $configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY'] = round($configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY'] / 100, 1);
        $css [] = '.li-niveau1 .adtm_sub {border-color:' . htmlentities($configGlobalCss ['ATM_SUBMENU_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . '; border-width:' . htmlentities($configGlobalCss ['ATM_SUBMENU_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . '; -moz-box-shadow: '. htmlentities($configGlobalCss['ATM_SUBMENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_SUBMENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -webkit-box-shadow: '. htmlentities($configGlobalCss['ATM_SUBMENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_SUBMENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; -o-box-shadow: '. htmlentities($configGlobalCss['ATM_SUBMENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_SUBMENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; box-shadow: '. htmlentities($configGlobalCss['ATM_SUBMENU_BOX_SHADOW'], ENT_COMPAT, 'UTF-8') .' ' . htmlentities($this->_hex2rgb($configGlobalCss['ATM_SUBMENU_BOX_SHADOWCOLOR'], $configGlobalCss['ATM_SUBMENU_BOX_SHADOWOPACITY']), ENT_COMPAT, 'UTF-8') . '; filter:progid:DXImageTransform.Microsoft.Shadow(color=' . htmlentities($configGlobalCss ['ATM_SUBMENU_BOX_SHADOWCOLOR'], ENT_COMPAT, 'UTF-8') . ', Direction=137, Strength=0);}';
        $configGlobalCss ['ATM_SUBMENU_BGCOLOR'] = explode($this->gradient_separator, $configGlobalCss ['ATM_SUBMENU_BGCOLOR']);
        if (isset($configGlobalCss ['ATM_SUBMENU_BGCOLOR'] [1])) {
            $color1 = htmlentities($configGlobalCss ['ATM_SUBMENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8');
            $color2 = htmlentities($configGlobalCss ['ATM_SUBMENU_BGCOLOR'] [1], ENT_COMPAT, 'UTF-8');
            $css [] = '.li-niveau1 .adtm_sub {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
        } else {
            $css [] = '.li-niveau1 .adtm_sub {background-color:' . htmlentities($configGlobalCss ['ATM_SUBMENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8') . ';}';
        }
        if ($configGlobalCss ['ATM_SUBMENU_WIDTH']) {
            $css [] = '.li-niveau1 .adtm_sub {width:' . htmlentities($configGlobalCss ['ATM_SUBMENU_WIDTH'], ENT_COMPAT, 'UTF-8') . 'px;}';
        }
        if ($configGlobalCss ['ATM_SUBMENU_HEIGHT']) {
            $css [] = '.li-niveau1 .adtm_sub {min-height:' . htmlentities($configGlobalCss ['ATM_SUBMENU_HEIGHT'], ENT_COMPAT, 'UTF-8') . 'px;}';
            $css [] = '* html .li-niveau1 .adtm_sub {height:' . htmlentities($configGlobalCss ['ATM_SUBMENU_HEIGHT'], ENT_COMPAT, 'UTF-8') . 'px;}';
            $css [] = '#adtm_menu div.adtm_column_wrap {min-height:' . htmlentities($configGlobalCss ['ATM_SUBMENU_HEIGHT'], ENT_COMPAT, 'UTF-8') . 'px;}';
            $css [] = '* html #adtm_menu div.adtm_column_wrap {height:' . htmlentities($configGlobalCss ['ATM_SUBMENU_HEIGHT'], ENT_COMPAT, 'UTF-8') . 'px;}';
        }
        $css [] = '#adtm_menu ul#menu .li-niveau1 div.adtm_sub {opacity: 0; visibility: hidden;}';
        $openingDelay = (!empty($configGlobalCss['ATM_SUBMENU_OPEN_DELAY']) ? (float)$configGlobalCss['ATM_SUBMENU_OPEN_DELAY'] : 0);
        $fadingSpeed = (!empty($configGlobalCss['ATM_SUBMENU_FADE_SPEED']) ? (float)$configGlobalCss['ATM_SUBMENU_FADE_SPEED'] : 0);
        $css [] = '#adtm_menu ul#menu .li-niveau1' . $hoverCSSselector . ' div.adtm_sub { opacity: 1; visibility: visible; transition:visibility 0s linear '. $openingDelay .'s, opacity '. $fadingSpeed .'s linear '. $openingDelay .'s;}';
        $css [] = '.adtm_column_wrap span.column_wrap_title, .adtm_column_wrap span.column_wrap_title a {color:' . htmlentities($configGlobalCss ['ATM_COLUMN_TITLE_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '.adtm_column_wrap a {color:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column_wrap {padding:' . htmlentities($configGlobalCss ['ATM_COLUMNWRAP_PADDING'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column {padding:' . htmlentities($configGlobalCss ['ATM_COLUMN_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_COLUMN_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column ul.adtm_elements li a {padding:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column_wrap span.column_wrap_title {padding:' . htmlentities($configGlobalCss ['ATM_COLUMNTITLE_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configGlobalCss ['ATM_COLUMNTITLE_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .li-niveau1 a.a-niveau1 .advtm_menu_span {'. ($configGlobalCss['ATM_MENU_FONT_SIZE'] ? 'font-size:' . htmlentities($configGlobalCss ['ATM_MENU_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configGlobalCss['ATM_MENU_FONT_BOLD'] ? 'bold' : 'normal') . '; text-decoration:'. ($configGlobalCss['ATM_MENU_FONT_UNDERLINE'] ? 'underline' : 'none') . '; text-transform:' . htmlentities($configGlobalCss['ATM_MENU_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . ';}';
        $specificDesktopCss[] = '#adtm_menu .li-niveau1 a.a-niveau1:hover .advtm_menu_span, .li-niveau1:hover > a.a-niveau1 .advtm_menu_span {color:' . htmlentities($configGlobalCss ['ATM_MENU_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_MENU_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
        $css [] = '#adtm_menu .li-niveau1 a.advtm_menu_actif .advtm_menu_span {color:' . htmlentities($configGlobalCss ['ATM_MENU_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_MENU_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
        if ($hoverCSSselector != ':hover') {
            $css [] = '#adtm_menu .li-niveau1' . $hoverCSSselector . ' a.a-niveau1 .advtm_menu_span, #adtm_menu .li-niveau1 a.advtm_menu_actif .advtm_menu_span, .li-niveau1' . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span {color:' . htmlentities($configGlobalCss ['ATM_MENU_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_MENU_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
        }
        if ($configGlobalCss ['ATM_MENU_FONT_FAMILY']) {
            $css [] = '#adtm_menu .li-niveau1 a.a-niveau1 .advtm_menu_span {font-family:' . htmlentities($configGlobalCss ['ATM_MENU_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
        }
        $css [] = '#adtm_menu .adtm_column span.column_wrap_title, #adtm_menu .adtm_column span.column_wrap_title a {'. ($configGlobalCss['ATM_COLUMN_FONT_SIZE'] ? 'font-size:' . htmlentities($configGlobalCss ['ATM_COLUMN_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configGlobalCss['ATM_COLUMN_FONT_BOLD'] ? 'bold' : 'normal') . '; text-decoration:'. ($configGlobalCss['ATM_COLUMN_FONT_UNDERLINE'] ? 'underline' : 'none') . '; text-transform:' . htmlentities($configGlobalCss['ATM_COLUMN_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column span.column_wrap_title:hover, #adtm_menu .adtm_column span.column_wrap_title a:hover {color:' . htmlentities($configGlobalCss ['ATM_COLUMN_TITLE_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_COLUMN_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
        if ($configGlobalCss ['ATM_COLUMN_FONT_FAMILY']) {
            $css [] = '#adtm_menu .adtm_column span.column_wrap_title, #adtm_menu .adtm_column span.column_wrap_title a {font-family:' . htmlentities($configGlobalCss ['ATM_COLUMN_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
        }
        $css [] = '#adtm_menu .adtm_column ul.adtm_elements li, #adtm_menu .adtm_column ul.adtm_elements li a {'. ($configGlobalCss['ATM_COLUMN_ITEM_FONT_SIZE'] ? 'font-size:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configGlobalCss['ATM_COLUMN_ITEM_FONT_BOLD'] ? 'bold' : 'normal') . '; text-decoration:'. ($configGlobalCss['ATM_COLUMN_ITEM_FONT_UNDERLINE'] ? 'underline' : 'none') . '; text-transform:' . htmlentities($configGlobalCss['ATM_COLUMN_ITEM_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . ';}';
        $css [] = '#adtm_menu .adtm_column ul.adtm_elements li:hover, #adtm_menu .adtm_column ul.adtm_elements li a:hover {color:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_COLUMN_ITEM_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
        if ($configGlobalCss ['ATM_COLUMN_ITEM_FONT_FAMILY']) {
            $css [] = '#adtm_menu .adtm_column ul.adtm_elements li, #adtm_menu .adtm_column ul.adtm_elements li a {font-family:' . htmlentities($configGlobalCss ['ATM_COLUMN_ITEM_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
        }
        if ((int)$configGlobalCss ['ATM_SUBMENU_POSITION'] == 1) {
            $css [] = '#adtm_menu ul#menu li.li-niveau1' . $hoverCSSselector . ', #adtm_menu ul#menu li.li-niveau1 a.a-niveau1' . $hoverCSSselector . ' {position:relative;}';
        } elseif ((int)$configGlobalCss ['ATM_SUBMENU_POSITION'] == 2) {
            $css [] = '.li-niveau1 .adtm_sub {width: 100%}';
            $css [] = '#adtm_menu table.columnWrapTable {table-layout:fixed;}';
        }
        if ($configGlobalCss['ATM_MENU_GLOBAL_ZINDEX']) {
            $css [] = '#adtm_menu {z-index:' . (int)$configGlobalCss ['ATM_MENU_GLOBAL_ZINDEX'] . ';}';
        }
        if ($configGlobalCss['ATM_SUBMENU_ZINDEX']) {
            $css [] = '.li-niveau1 .adtm_sub {z-index:' . (int)$configGlobalCss ['ATM_SUBMENU_ZINDEX'] . ';}';
        }
        $css [] = '#adtm_menu .advtm_hide_desktop {display:none!important;}';
        if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
            $css [] = '@media (min-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) {';
            $css [] = implode("\n", $specificDesktopCss);
            $css [] = '}';
        } else {
            $css [] = implode("\n", $specificDesktopCss);
        }
        if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
            $css [] = 'div#adtm_menu_inner {width: inherit;}';
            $css [] = '#adtm_menu ul .advtm_menu_toggle {display: none;}';
            $css [] = '@media (max-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) {';
            $css [] = '#adtm_menu {position:relative; top:initial; left:initial; right:initial; bottom:initial;}';
            $css [] = '#adtm_menu .advtm_hide_mobile {display:none!important;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.advtm_search.advtm_hide_mobile {display:none!important;}';
            $css [] = '#adtm_menu a.a-niveau1, #adtm_menu .advtm_menu_span { height: auto !important; }';
            $css [] = '#adtm_menu ul li.li-niveau1 {display: none;}';
            if (empty($configResponsiveCss['ATM_RESP_TOGGLE_ENABLED'])) {
                $css [] = '#adtm_menu ul li.advtm_menu_toggle {width: 1px; height: 1px; visibility: hidden; min-height: 1px !important; border: none; padding: 0; margin: 0; line-height: 1px;}';
            } else {
                $css [] = '#adtm_menu ul li.advtm_menu_toggle {display: block; width: 100%;}';
            }
            $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button {width: 100%; cursor: pointer;}';
            $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-position: right 15px center; background-repeat: no-repeat;}';
            $css [] = '#adtm_menu .adtm_menu_icon { height: auto; max-width: 100%; }';
            $css [] = '#adtm_menu ul .li-niveau1 .adtm_sub {width: auto; height: auto; min-height: inherit;}';
            $css [] = '#adtm_menu ul div.adtm_column_wrap {min-height: inherit; width: 100% !important;}';
            if (isset($configResponsiveCss['ATM_RESP_TOGGLE_ICON']) && !empty($configResponsiveCss['ATM_RESP_TOGGLE_ICON'])) {
                $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-image: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . '); background-position: right 15px center; background-repeat: no-repeat;}';
            }
            $css [] = '#adtm_menu .li-niveau1 a.a-niveau1 .advtm_menu_span {'. ($configResponsiveCss['ATM_RESP_MENU_FONT_SIZE'] ? 'font-size:' . htmlentities($configResponsiveCss['ATM_RESP_MENU_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configResponsiveCss['ATMR_MENU_FONT_BOLD'] ? 'bold' : 'normal') . '; text-transform:' . htmlentities($configResponsiveCss['ATMR_MENU_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . '; font-family:' . htmlentities($configResponsiveCss['ATMR_MENU_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column span.column_wrap_title, #adtm_menu .adtm_column span.column_wrap_title a {'. ($configResponsiveCss['ATM_RESP_COLUMN_FONT_SIZE'] ? 'font-size:' . htmlentities($configResponsiveCss ['ATM_RESP_COLUMN_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configResponsiveCss['ATMR_COLUMN_FONT_BOLD'] ? 'bold' : 'normal') . '; text-transform:' . htmlentities($configResponsiveCss['ATMR_COLUMN_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . '; font-family:' . htmlentities($configResponsiveCss['ATMR_COLUMN_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column ul.adtm_elements li, #adtm_menu .adtm_column ul.adtm_elements li a {'. ($configResponsiveCss['ATM_RESP_COLUMN_ITEM_FONT_SIZE'] ? 'font-size:' . htmlentities($configResponsiveCss ['ATM_RESP_COLUMN_ITEM_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . ' font-weight:'. ($configResponsiveCss['ATMR_COLUMN_ITEM_FONT_BOLD'] ? 'bold' : 'normal') . '; text-transform:' . htmlentities($configResponsiveCss['ATMR_COLUMN_ITEM_FONT_TRANSFORM'], ENT_COMPAT, 'UTF-8') . '; font-family:' . htmlentities($configResponsiveCss['ATMR_COLUMN_ITEM_FONT_FAMILY'], ENT_COMPAT, 'UTF-8') . ';}';
            $css[] = '#adtm_menu .li-niveau1.adtm_sub_open a.a-niveau1 .advtm_menu_span, #adtm_menu .li-niveau1 a.a-niveau1:focus .advtm_menu_span, .li-niveau1:focus > a.a-niveau1 .advtm_menu_span {color:' . htmlentities($configGlobalCss ['ATM_MENU_COLOR_OVER'], ENT_COMPAT, 'UTF-8') . '; text-decoration:'. ($configGlobalCss['ATM_MENU_FONT_UNDERLINEOV'] ? 'underline' : 'none') . ';}';
            if (isset($configResponsiveCss['ATM_RESP_TOGGLE_COLOR_OP']) && !empty($configResponsiveCss['ATM_RESP_TOGGLE_COLOR_OP'])) {
                $css [] = '#adtm_menu.adtm_menu_toggle_open ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {color:' . htmlentities($configResponsiveCss ['ATM_RESP_TOGGLE_COLOR_OP'], ENT_COMPAT, 'UTF-8') . ';}';
            }
            if (isset($configResponsiveCss['ATM_RESP_TOGGLE_COLOR_CL']) && !empty($configResponsiveCss['ATM_RESP_TOGGLE_COLOR_CL'])) {
                $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {color:' . htmlentities($configResponsiveCss ['ATM_RESP_TOGGLE_COLOR_CL'], ENT_COMPAT, 'UTF-8') . ';}';
            }
            $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {'. ($configResponsiveCss['ATM_RESP_MENU_FONT_SIZE'] ? 'font-size:' . htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_FONT_SIZE'], ENT_COMPAT, 'UTF-8') . 'px;' : '') . 'min-height:' . (int)$configResponsiveCss['ATM_RESP_TOGGLE_HEIGHT'] . 'px;line-height:' . (int)$configResponsiveCss['ATM_RESP_TOGGLE_HEIGHT'] . 'px;}';
            $configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP'] = explode($this->gradient_separator, $configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP']);
            if (isset($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP'] [1])) {
                $color1 = htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP'][0], ENT_COMPAT, 'UTF-8');
                $color2 = htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP'][1], ENT_COMPAT, 'UTF-8');
                if (isset($configResponsiveCss['ATM_RESP_TOGGLE_ICON']) && !empty($configResponsiveCss['ATM_RESP_TOGGLE_ICON'])) {
                    $css [] = '#adtm_menu.adtm_menu_toggle_open li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                } else {
                    $css [] = '#adtm_menu.adtm_menu_toggle_open li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                }
            } else {
                $css [] = '#adtm_menu.adtm_menu_toggle_open li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color:' . htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_OP'][0], ENT_COMPAT, 'UTF-8') . ';}';
            }
            $configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL'] = explode($this->gradient_separator, $configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL']);
            if (isset($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL'] [1])) {
                $color1 = htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL'][0], ENT_COMPAT, 'UTF-8');
                $color2 = htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL'][1], ENT_COMPAT, 'UTF-8');
                if (isset($configResponsiveCss['ATM_RESP_TOGGLE_ICON']) && !empty($configResponsiveCss['ATM_RESP_TOGGLE_ICON'])) {
                    $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_TOGGLE_ICON'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                } else {
                    $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                }
            } else {
                $css [] = '#adtm_menu ul li.advtm_menu_toggle a.adtm_toggle_menu_button span.adtm_toggle_menu_button_text {background-color:' . htmlentities($configResponsiveCss['ATM_RESP_TOGGLE_BG_COLOR_CL'][0], ENT_COMPAT, 'UTF-8') . ';}';
            }
            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'])) {
                $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.sub a.a-niveau1 span {background-image: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . '); background-repeat: no-repeat; background-position: right 15px center;}';
            }
            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'])) {
                $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.sub.adtm_sub_open a.a-niveau1 span {background-image: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . '); background-repeat: no-repeat; background-position: right 15px center;}';
            }
            $css [] = '.li-niveau1 a span {padding:' . htmlentities($configResponsiveCss['ATM_RESP_MENU_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configResponsiveCss['ATMR_MENU_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '.li-niveau1 a.a-niveau1 {border-color:' . htmlentities($configResponsiveCss['ATMR_MENU_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configResponsiveCss['ATMR_MENU_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '.li-niveau1 .advtm_menu_span, .li-niveau1 a .advtm_menu_span {color:' . htmlentities($configResponsiveCss['ATMR_MENU_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
            $configResponsiveCss['ATMR_MENU_BGCOLOR_CL'] = explode($this->gradient_separator, $configResponsiveCss['ATMR_MENU_BGCOLOR_CL']);
            if (isset($configResponsiveCss['ATMR_MENU_BGCOLOR_CL'] [1])) {
                $color1 = htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_CL'][0], ENT_COMPAT, 'UTF-8');
                $color2 = htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_CL'][1], ENT_COMPAT, 'UTF-8');
                if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'])) {
                    $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.sub a.a-niveau1 span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                }
                $css [] = '.li-niveau1 a .advtm_menu_span, .li-niveau1 .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            } else {
                $css [] = '.li-niveau1 a .advtm_menu_span, .li-niveau1 .advtm_menu_span {background-color:' . htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_CL'][0], ENT_COMPAT, 'UTF-8') . ';}';
            }
            $configResponsiveCss['ATMR_MENU_BGCOLOR_OP'] = explode($this->gradient_separator, $configResponsiveCss['ATMR_MENU_BGCOLOR_OP']);
            if (isset($configResponsiveCss['ATMR_MENU_BGCOLOR_OP'][1])) {
                $color1 = htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_OP'][0], ENT_COMPAT, 'UTF-8');
                $color2 = htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_OP'][1], ENT_COMPAT, 'UTF-8');
                if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'])) {
                    $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.sub.adtm_sub_open a.a-niveau1 span, #adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.sub a.a-niveau1.advtm_menu_actif span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
                }
                $css [] = '#adtm_menu.adtm_menu_toggle_open .li-niveau1.sub.adtm_sub_open a .advtm_menu_span, .li-niveau1 a:focus .advtm_menu_span, .li-niveau1 a.advtm_menu_actif .advtm_menu_span, .li-niveau1 .advtm_menu_span:focus, .li-niveau1:focus > a.a-niveau1 .advtm_menu_span {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            } else {
                $css [] = '#adtm_menu.adtm_menu_toggle_open .li-niveau1.sub.adtm_sub_open a .advtm_menu_span, .li-niveau1 a:focus .advtm_menu_span, .li-niveau1 a.advtm_menu_actif .advtm_menu_span, .li-niveau1 .advtm_menu_span:focus, .li-niveau1:focus > a.a-niveau1 .advtm_menu_span {background-color:' . htmlentities($configResponsiveCss['ATMR_MENU_BGCOLOR_OP'][0], ENT_COMPAT, 'UTF-8') . ';}';
            }
            $configResponsiveCss['ATMR_SUBMENU_BGCOLOR'] = explode($this->gradient_separator, $configResponsiveCss['ATMR_SUBMENU_BGCOLOR']);
            if (isset($configResponsiveCss['ATMR_SUBMENU_BGCOLOR'] [1])) {
                $color1 = htmlentities($configResponsiveCss['ATMR_SUBMENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8');
                $color2 = htmlentities($configResponsiveCss['ATMR_SUBMENU_BGCOLOR'] [1], ENT_COMPAT, 'UTF-8');
                $css [] = '.li-niveau1 .adtm_sub {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\'); background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '));background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . '); background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ');}';
            } else {
                $css [] = '.li-niveau1 .adtm_sub {background-color:' . htmlentities($configResponsiveCss['ATMR_SUBMENU_BGCOLOR'] [0], ENT_COMPAT, 'UTF-8') . ';}';
            }
            $css [] = '.li-niveau1 .adtm_sub {border-color:' . htmlentities($configResponsiveCss['ATMR_SUBMENU_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . '; border-width:' . htmlentities($configResponsiveCss['ATMR_SUBMENU_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column_wrap {padding:' . htmlentities($configResponsiveCss['ATMR_COLUMNWRAP_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configResponsiveCss['ATMR_COLUMNWRAP_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column_wrap_td {border-color:' . htmlentities($configResponsiveCss['ATMR_COLUMNWRAP_BORDERCOLOR'], ENT_COMPAT, 'UTF-8') . ';border-width:' . htmlentities($configResponsiveCss['ATMR_COLUMNWRAP_BORDERSIZE'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column {padding:' . htmlentities($configResponsiveCss['ATMR_COLUMN_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configResponsiveCss['ATMR_COLUMN_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column_wrap span.column_wrap_title {padding:' . htmlentities($configResponsiveCss['ATMR_COLUMNTITLE_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configResponsiveCss['ATMR_COLUMNTITLE_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '.adtm_column_wrap span.column_wrap_title, .adtm_column_wrap span.column_wrap_title a {color:' . htmlentities($configResponsiveCss['ATMR_COLUMN_TITLE_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu .adtm_column ul.adtm_elements li a {padding:' . htmlentities($configResponsiveCss['ATMR_COLUMN_ITEM_PADDING'], ENT_COMPAT, 'UTF-8') . ';margin:' . htmlentities($configResponsiveCss['ATMR_COLUMN_ITEM_MARGIN'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '.adtm_column_wrap a {color:' . htmlentities($configResponsiveCss['ATMR_COLUMN_ITEM_COLOR'], ENT_COMPAT, 'UTF-8') . ';}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu .advtm_hide_desktop {display: block !important;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1 {display: block !important;float: none;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.advtm_hide_mobile {display: none !important;}';
            if (empty($configResponsiveCss['ATM_RESP_TOGGLE_ENABLED'])) {
                $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1.advtm_menu_toggle.adtm_menu_mobile_mode {display: none !important;}';
            }
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.li-niveau1 a.a-niveau1 {float: none;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li div.adtm_sub  {display: none; position: static; height: auto;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li div.adtm_sub.adtm_submenu_toggle_open  {display: block;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open table.columnWrapTable {display: table !important; width: 100% !important;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open table.columnWrapTable tr td {display: block;}';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.advtm_search .searchboxATM { display: flex; }';
            $css [] = '#adtm_menu.adtm_menu_toggle_open ul#menu li.advtm_search .searchboxATM .search_query_atm { padding: 15px 5px; width: 100%; }';
            $css [] = '#adtm_menu ul#menu .li-niveau1 div.adtm_sub {opacity: 1;visibility:visible;}';
            $css [] = '#adtm_menu ul#menu .li-niveau1:hover div.adtm_sub, #adtm_menu ul#menu .li-niveau1:focus div.adtm_sub {transition: none;}';
            $css [] = '}';
        }
        if ($id_shop != false) {
            $ids_shop = array($id_shop);
        } else {
            $ids_shop = array_values(Shop::getContextListShopID());
        }
        $global_css_file = array();
        foreach ($ids_shop as $id_shop) {
            $global_css_file[] = str_replace('.css', '-'.$id_shop.'.css', dirname(__FILE__). '/' . self::GLOBAL_CSS_FILE);
        }
        if (count($css) && count($global_css_file)) {
            foreach ($global_css_file as $value) {
                file_put_contents($value, implode("\n", $css));
            }
        }
    }
    protected function generateCss()
    {
        list($config, $configResponsive) = $this->_getConfigKeys();
        $menus = AdvancedTopMenuClass::getMenus($this->context->cookie->id_lang, true, true);
        $columnsWrap = AdvancedTopMenuColumnWrapClass::getColumnsWrap();
        $css = array();
        if (is_array($menus) && count($menus)) {
            foreach ($menus as $menu) {
                if ((int)$menu['id_shop'] != false) {
                    $configGlobalCss = Configuration::getMultiple($config, null, null, (int)$menu['id_shop']);
                    $configResponsiveCss = Configuration::getMultiple($configResponsive, null, null, (int)$menu['id_shop']);
                } else {
                    $configGlobalCss = Configuration::getMultiple($config);
                    $configResponsiveCss = Configuration::getMultiple($configResponsive);
                }
                $hoverCSSselector = ':hover';
                if (!empty($configGlobalCss['ATM_SUBMENU_OPEN_METHOD']) && $configGlobalCss['ATM_SUBMENU_OPEN_METHOD'] == 2) {
                    $hoverCSSselector = '.atm_clicked';
                }
                if ($menu ['txt_color_menu_tab']) {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a .advtm_menu_span_' . $menu ['id_menu'] . ' {color:' . htmlentities($menu ['txt_color_menu_tab'], ENT_COMPAT, 'UTF-8') . '!important;}';
                }
                if ($menu ['txt_color_menu_tab_hover']) {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ':hover > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {color:' . htmlentities($menu ['txt_color_menu_tab_hover'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', * html .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {color:' . htmlentities($menu ['txt_color_menu_tab_hover'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    if ($hoverCSSselector != ':hover') {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {color:' . htmlentities($menu ['txt_color_menu_tab_hover'], ENT_COMPAT, 'UTF-8') . '!important;}';
                        $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', * html .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {color:' . htmlentities($menu ['txt_color_menu_tab_hover'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    }
                }
                if ($menu ['fnd_color_menu_tab']) {
                    $menu ['fnd_color_menu_tab'] = explode($this->gradient_separator, $menu ['fnd_color_menu_tab']);
                    if (isset($menu ['fnd_color_menu_tab'] [1])) {
                        $color1 = htmlentities($menu ['fnd_color_menu_tab'] [0], ENT_COMPAT, 'UTF-8');
                        $color2 = htmlentities($menu ['fnd_color_menu_tab'] [1], ENT_COMPAT, 'UTF-8');
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' a .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;}';
                        if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
                            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'])) {
                                $css [] = '@media (max-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) { .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . ' a .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;} }';
                            }
                            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'])) {
                                $css [] = '@media (max-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) { .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . '.adtm_sub_open a .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;} }';
                            }
                        }
                    } else {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' a .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:' . htmlentities($menu ['fnd_color_menu_tab'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter: none!important;}';
                    }
                }
                if ($menu ['fnd_color_menu_tab_over']) {
                    $menu ['fnd_color_menu_tab_over'] = explode($this->gradient_separator, $menu ['fnd_color_menu_tab_over']);
                    if (isset($menu ['fnd_color_menu_tab_over'] [1])) {
                        $color1 = htmlentities($menu ['fnd_color_menu_tab_over'] [0], ENT_COMPAT, 'UTF-8');
                        $color2 = htmlentities($menu ['fnd_color_menu_tab_over'] [1], ENT_COMPAT, 'UTF-8');
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ':hover > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . '!important;filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;}';
                        $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', * html .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:transparent!important;background:transparent!important;filter:none!important;filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important;}';
                        if ($hoverCSSselector != ':hover') {
                            $css [] = '.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . '!important;filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;}';
                            $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', * html .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:transparent!important;background:transparent!important;filter:none!important;filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important;}';
                        }
                        if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
                            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'])) {
                                $css [] = '@media (max-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) { .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . ':hover > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_CL'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;} }';
                            }
                            if (isset($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP']) && !empty($configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'])) {
                                $css [] = '@media (max-width: ' . (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] . 'px) { .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . '.adtm_sub_open a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . '.adtm_sub_open a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .adtm_menu_toggle_open .advtm_menu_' . $menu ['id_menu'] . '.adtm_sub_open:hover > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: url(' . $configResponsiveCss['ATM_RESP_SUBMENU_ICON_OP'] . ') no-repeat right 15px center, linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;} }';
                            }
                        }
                    } else {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ':hover > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:' . htmlentities($menu ['fnd_color_menu_tab_over'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter: none!important;}';
                        $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . ' a:hover .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:' . htmlentities($menu ['fnd_color_menu_tab_over'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter:none!important;}';
                        $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . ' a:hover, .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif {filter:none!important;}';
                        if ($hoverCSSselector != ':hover') {
                            $css [] = '.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' > a.a-niveau1 .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:' . htmlentities($menu ['fnd_color_menu_tab_over'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter: none!important;}';
                            $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a .advtm_menu_span_' . $menu ['id_menu'] . ', .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif .advtm_menu_span_' . $menu ['id_menu'] . ' {background-color:' . htmlentities($menu ['fnd_color_menu_tab_over'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter:none!important;}';
                            $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a, .advtm_menu_' . $menu ['id_menu'] . ' a.advtm_menu_actif {filter:none!important;}';
                        }
                    }
                }
                if ($menu ['border_size_tab']) {
                    $css [] = 'li.advtm_menu_' . $menu ['id_menu'] . ' a.a-niveau1 {border-width:' . htmlentities($menu ['border_size_tab'], ENT_COMPAT, 'UTF-8') . '!important;}';
                }
                if ($menu ['border_color_tab']) {
                    $css [] = 'li.advtm_menu_' . $menu ['id_menu'] . ' a.a-niveau1 {border-color:' . htmlentities($menu ['border_color_tab'], ENT_COMPAT, 'UTF-8') . '!important;}';
                }
                if ($menu ['width_submenu']) {
                    if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
                        $css [] = '@media (min-width: ' . (int)($configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] + 1) . 'px) { .advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {width:' . htmlentities($menu ['width_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;} }';
                    } else {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {width:' . htmlentities($menu ['width_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                    }
                }
                if ($menu ['minheight_submenu']) {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {min-height:' . htmlentities($menu ['minheight_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                    $css [] = '* html .advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {height:' . htmlentities($menu ['minheight_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                    $css [] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' div.adtm_column_wrap {min-height:' . htmlentities($menu ['minheight_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                    $css [] = '* html #adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' div.adtm_column_wrap {height:' . htmlentities($menu ['minheight_submenu'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                } elseif ($menu ['minheight_submenu'] === '0') {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {height:auto!important;min-height:0!important;}';
                    $css [] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' div.adtm_column_wrap {height:auto!important;min-height:0!important;}';
                }
                if ($menu ['position_submenu']) {
                    if ((int)$menu ['position_submenu'] == 1 || (int)$menu ['position_submenu'] == 3) {
                        $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ':hover, #adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ' a.a-niveau1:hover {position:relative!important;}';
                    } elseif ((int)$menu ['position_submenu'] == 2) {
                        $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ':hover, #adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ' a.a-niveau1:hover {position:static!important;}';
                    }
                    if ((int)$menu ['position_submenu'] == 3) {
                        $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ':hover div.adtm_sub {left:auto!important;right:0!important;}';
                        $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . ' a:hover div.adtm_sub {left:auto!important;right:1px!important;}';
                    }
                    if ($hoverCSSselector != ':hover') {
                        if ((int)$menu ['position_submenu'] == 1 || (int)$menu ['position_submenu'] == 3) {
                            $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ', #adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a.a-niveau1 {position:relative!important;}';
                        } elseif ((int)$menu ['position_submenu'] == 2) {
                            $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ', #adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a.a-niveau1 {position:static!important;}';
                        }
                        if ((int)$menu ['position_submenu'] == 3) {
                            $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' div.adtm_sub {left:auto!important;right:0!important;}';
                            $css [] = '#adtm_menu ul#menu li.advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' a div.adtm_sub {left:auto!important;right:1px!important;}';
                        }
                    }
                }
                if ($menu ['fnd_color_submenu']) {
                    $menu ['fnd_color_submenu'] = explode($this->gradient_separator, $menu ['fnd_color_submenu']);
                    if (isset($menu ['fnd_color_submenu'] [1])) {
                        $color1 = htmlentities($menu ['fnd_color_submenu'] [0], ENT_COMPAT, 'UTF-8');
                        $color2 = htmlentities($menu ['fnd_color_submenu'] [1], ENT_COMPAT, 'UTF-8');
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;}';
                    } else {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {background-color:' . htmlentities($menu ['fnd_color_submenu'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter: none!important;}';
                    }
                }
                if ($menu ['border_color_submenu']) {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' div.adtm_sub {border-color:' . htmlentities($menu ['border_color_submenu'], ENT_COMPAT, 'UTF-8') . '!important;}';
                }
                if ($menu ['border_size_submenu']) {
                    $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' div.adtm_sub {border-width:' . htmlentities($menu ['border_size_submenu'], ENT_COMPAT, 'UTF-8') . '!important;}';
                }
                foreach ($columnsWrap as $columnWrap) {
                    if ($columnWrap['id_menu'] != $menu['id_menu']) {
                        continue;
                    }
                    if ($columnWrap ['bg_color']) {
                        $columnWrap ['bg_color'] = explode($this->gradient_separator, $columnWrap ['bg_color']);
                        if (isset($columnWrap ['bg_color'] [1])) {
                            $color1 = htmlentities($columnWrap ['bg_color'] [0], ENT_COMPAT, 'UTF-8');
                            $color2 = htmlentities($columnWrap ['bg_color'] [1], ENT_COMPAT, 'UTF-8');
                            $css [] = '.advtm_column_wrap_td_' . $columnWrap ['id_wrap'] . ' {background-color: ' . $color1 . ';filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\'' . $color1 . '\', endColorstr=\'' . $color2 . '\')!important; background: -webkit-gradient(linear, left top, left bottom, from(' . $color1 . '), to(' . $color2 . '))!important;background: -moz-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -ms-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: -o-linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important; background: linear-gradient(top, ' . $color1 . ', ' . $color2 . ')!important;}';
                        } else {
                            $css [] = '.advtm_column_wrap_td_' . $columnWrap ['id_wrap'] . ' {background-color:' . htmlentities($columnWrap ['bg_color'] [0], ENT_COMPAT, 'UTF-8') . '!important;filter: none!important;}';
                        }
                    }
                    if ($columnWrap ['width']) {
                        if (empty($menu['position_submenu']) && (int)$configGlobalCss['ATM_SUBMENU_POSITION'] == 2 || $menu['position_submenu'] == 2) {
                            if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
                                $css [] = '@media (min-width: ' . (int)($configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] + 1) . 'px) { .advtm_column_wrap_td_' . $columnWrap ['id_wrap'] . ' {width:' . htmlentities($columnWrap ['width'], ENT_COMPAT, 'UTF-8') . 'px!important;} }';
                            } else {
                                $css [] = '.advtm_column_wrap_td_' . $columnWrap ['id_wrap'] . ' {width:' . htmlentities($columnWrap ['width'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                            }
                            $css['fix_table_layout_' . $menu ['id_menu'] . '_2'] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' table.columnWrapTable {table-layout:fixed}';
                        } else {
                            if ($configResponsiveCss['ATM_RESPONSIVE_MODE'] == 1 && (int)$configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] > 0) {
                                $css [] = '@media (min-width: ' . (int)($configResponsiveCss['ATM_RESPONSIVE_THRESHOLD'] + 1) . 'px) { .advtm_column_wrap_td_' . $columnWrap ['id_wrap'] . ' {width:' . htmlentities($columnWrap ['width'], ENT_COMPAT, 'UTF-8') . 'px!important;} }';
                            } else {
                                $css [] = '.advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' {width:' . htmlentities($columnWrap ['width'], ENT_COMPAT, 'UTF-8') . 'px!important;}';
                            }
                            $css['fix_table_layout_' . $menu ['id_menu'] . '_1'] = '.li-niveau1.advtm_menu_' . $menu ['id_menu'] . ' .adtm_sub {width: auto}';
                            $css['fix_table_layout_' . $menu ['id_menu'] . '_2'] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' table.columnWrapTable {table-layout:auto}';
                        }
                    }
                    if ($columnWrap ['txt_color_column']) {
                        $css [] = '.advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' span.column_wrap_title, .advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' span.column_wrap_title a {color:' . htmlentities($columnWrap ['txt_color_column'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    }
                    if ($columnWrap ['txt_color_column_over']) {
                        $css [] = '.advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' span.column_wrap_title a:hover {color:' . htmlentities($columnWrap ['txt_color_column_over'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    }
                    if ($columnWrap ['txt_color_element']) {
                        $css [] = '.advtm_column_wrap_' . $columnWrap ['id_wrap'] . ', .advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' a {color:' . htmlentities($columnWrap ['txt_color_element'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    }
                    if ($columnWrap ['txt_color_element_over']) {
                        $css [] = '.advtm_column_wrap_' . $columnWrap ['id_wrap'] . ' a:hover {color:' . htmlentities($columnWrap ['txt_color_element_over'], ENT_COMPAT, 'UTF-8') . '!important;}';
                    }
                    if ((int)$configGlobalCss['ATM_SUBMENU_POSITION'] != 2 && $menu['position_submenu'] == 2) {
                        $css [] = '.advtm_menu_' . $menu ['id_menu'] . ' .li-niveau1 .adtm_sub {width: 100%;}';
                        $css['fix_table_layout_' . $menu ['id_menu'] . '_2'] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ' table.columnWrapTable {table-layout:fixed; width: 0}';
                        $css['fix_table_layout_' . $menu ['id_menu'] . '_3'] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . ':hover table.columnWrapTable {width: 100%}';
                        if ($hoverCSSselector != ':hover') {
                            $css['fix_table_layout_' . $menu ['id_menu'] . '_3'] = '#adtm_menu .advtm_menu_' . $menu ['id_menu'] . $hoverCSSselector . ' table.columnWrapTable {width: 100%}';
                        }
                    }
                }
            }
        }
        $advanced_css_file = dirname(__FILE__).'/'.self::ADVANCED_CSS_FILE;
        $old_advanced_css_file_exists = file_exists($advanced_css_file);
        $ids_shop = array_values(Shop::getCompleteListOfShopsID());
        foreach ($ids_shop as $id_shop) {
            $advanced_css_file_shop = str_replace('.css', '-'.$id_shop.'.css', $advanced_css_file);
            if (!$old_advanced_css_file_exists && !file_exists($advanced_css_file_shop)) {
                file_put_contents($advanced_css_file_shop, Tools::file_get_contents(dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE_RESTORE));
            } elseif ($old_advanced_css_file_exists && count($ids_shop) == 1 && !file_exists($advanced_css_file_shop)) {
                file_put_contents($advanced_css_file_shop, Tools::file_get_contents(dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE));
                @unlink(dirname(__FILE__).'/'.self::ADVANCED_CSS_FILE);
            } elseif (!file_exists($advanced_css_file_shop)) {
                file_put_contents($advanced_css_file_shop, Tools::file_get_contents(dirname(__FILE__) . '/' . self::ADVANCED_CSS_FILE_RESTORE));
            }
        }
        $ids_shop = array_values(Shop::getCompleteListOfShopsID());
        $specific_css_file = array();
        foreach ($ids_shop as $id_shop) {
            $specific_css_file[] = str_replace('.css', '-'.$id_shop.'.css', dirname(__FILE__). '/' . self::DYN_CSS_FILE);
        }
        if (count($css) && count($specific_css_file)) {
            foreach ($specific_css_file as $value) {
                file_put_contents($value, implode("\n", $css));
            }
        } elseif (!count($css) && count($specific_css_file)) {
            foreach ($specific_css_file as $value) {
                file_put_contents($value, '');
            }
        }
    }
    protected function _hex2rgb($hexstr, $opacity = false)
    {
        if (Tools::strlen($hexstr) < 7) {
            $hexstr = $hexstr.str_repeat(Tools::substr($hexstr, -1), 7-Tools::strlen($hexstr));
        }
        $int = hexdec($hexstr);
        if ($opacity === false) {
            return 'rgb(' . (0xFF & ($int >> 0x10)) . ', ' . (0xFF & ($int >> 0x8)) . ', ' . (0xFF & $int) . ')';
        } else {
            return 'rgba(' . (0xFF & ($int >> 0x10)) . ', ' . (0xFF & ($int >> 0x8)) . ', ' . (0xFF & $int) . ', ' . $opacity . ')';
        }
    }
    public function fetchWithCache($file, $template, $cacheid = null, $cache_lifetime = 0)
    {
        $previousTemplate = $this->context->smarty->currentTemplate;
        $this->context->smarty->currentTemplate = Tools::substr(basename($template), 0, - 4);
        $this->context->smarty->assign('module_dir', __PS_BASE_URI__ . 'modules/' . basename($file, '.php') . '/');
        $this->context->smarty->cache_lifetime = $cache_lifetime;
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . basename($file, '.php') . '/' . $template)) {
            $this->context->smarty->assign('module_template_dir', _THEME_DIR_ . 'modules/' . basename($file, '.php') . '/');
            $result = $this->context->smarty->fetch(_PS_THEME_DIR_ . 'modules/' . basename($file, '.php') . '/' . $template, $cacheid);
        } elseif (file_exists(dirname($file) . '/' . $template)) {
            $this->context->smarty->assign('module_template_dir', __PS_BASE_URI__ . 'modules/' . basename($file, '.php') . '/');
            $result = $this->context->smarty->fetch(dirname($file) . '/' . $template, $cacheid);
        } else {
            $result = '';
        }
        $this->context->smarty->currentTemplate = $previousTemplate;
        return $result;
    }
    private function _enableCachePM($level = 1)
    {
        if (!Configuration::get('PS_SMARTY_CACHE')) {
            return;
        }
        if ($this->context->smarty->force_compile == 0 and $this->context->smarty->compile_check == 0 and $this->context->smarty->caching == $level) {
            return;
        }
        self::$_forceCompile = ( int ) ($this->context->smarty->force_compile);
        self::$_compileCheck = ( int ) ($this->context->smarty->compile_check);
        self::$_caching = ( int ) ($this->context->smarty->caching);
        $this->context->smarty->force_compile = 0;
        $this->context->smarty->compile_check = 0;
        $this->context->smarty->caching = ( int ) ($level);
    }
    private function _restoreCacheSettingsPM()
    {
        if (isset(self::$_forceCompile)) {
            $this->context->smarty->force_compile = ( int ) (self::$_forceCompile);
        }
        if (isset(self::$_compileCheck)) {
            $this->context->smarty->compile_check = ( int ) (self::$_compileCheck);
        }
        if (isset(self::$_caching)) {
            $this->context->smarty->caching = ( int ) (self::$_caching);
        }
    }
    public function clearCache()
    {
        $this->context->smarty->clearCompiledTemplate(dirname(__FILE__) . '/pm_advancedtopmenu.tpl');
        return $this->context->smarty->clearCache(null, 'ADTM');
    }
    public function hookDisplayHeader()
    {
        if ($this->_isInMaintenance()) {
            return;
        }
        $global_css_file = __PS_BASE_URI__ . 'modules/' . $this->name . '/' . self::GLOBAL_CSS_FILE;
        $specific_css_file = __PS_BASE_URI__ . 'modules/' . $this->name . '/' . self::DYN_CSS_FILE;
        $advanced_css_file = __PS_BASE_URI__ . 'modules/' . $this->name . '/' . self::ADVANCED_CSS_FILE;
        $global_css_file_path = dirname(__FILE__).'/'.self::GLOBAL_CSS_FILE;
        $specific_css_file_path = dirname(__FILE__).'/'.self::DYN_CSS_FILE;
        $advanced_css_file_path = dirname(__FILE__).'/'.self::ADVANCED_CSS_FILE;
        $current_shop_id = (int)$this->context->shop->id;
        $global_css_file = str_replace('.css', '-'.$current_shop_id.'.css', $global_css_file);
        $global_css_file_path = str_replace('.css', '-'.$current_shop_id.'.css', $global_css_file_path);
        $advanced_css_file = str_replace('.css', '-'.$current_shop_id.'.css', $advanced_css_file);
        $advanced_css_file_path = str_replace('.css', '-'.$current_shop_id.'.css', $advanced_css_file_path);
        $specific_css_file = str_replace('.css', '-'.$current_shop_id.'.css', $specific_css_file);
        $specific_css_file_path = str_replace('.css', '-'.$current_shop_id.'.css', $specific_css_file_path);
        $advtmIsSticky = (Configuration::get('ATM_MENU_CONT_POSITION') == 'sticky');
        $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/pm_advancedtopmenu_base.css', 'all');
        $this->context->controller->addCSS(__PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/pm_advancedtopmenu_product.css', 'all');
        if (file_exists($global_css_file_path) && filesize($global_css_file_path) > 0) {
            $this->context->controller->addCSS($global_css_file, 'all');
        }
        if (file_exists($advanced_css_file_path) && filesize($advanced_css_file_path) > 0) {
            $this->context->controller->addCSS($advanced_css_file, 'all');
        }
        if (file_exists($specific_css_file_path) && filesize($specific_css_file_path) > 0) {
            $this->context->controller->addCSS($specific_css_file, 'all');
        }
        if ($advtmIsSticky) {
            $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/jquery.sticky.js');
        }
        $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/pm_advancedtopmenu.js');
        if (Configuration::get('ATM_AUTOCOMPLET_SEARCH') && version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $this->context->controller->addJqueryPlugin("autocomplete");
        }
        $this->context->smarty->assign(array(
            'adtm_isToggleMode' => (bool)Configuration::get('ATM_RESP_TOGGLE_ENABLED'),
            'adtm_menuHamburgerSelector' => Configuration::get('ATM_MENU_HAMBURGER_SELECTORS', null, null, null, '#menu-icon, .menu-icon'),
        ));
        return $this->display(__FILE__, 'views/templates/front/pm_advancedtopmenu_header.tpl');
    }
    public function hookActionObjectLanguageAddAfter($params)
    {
        $lang = $params['object'];
        if (Validate::isLoadedObject($lang)) {
            $res = Db::getInstance()->Execute('
                INSERT IGNORE INTO `' . _DB_PREFIX_ . 'pm_advancedtopmenu_elements_lang`
                (
                    SELECT `id_element`, "'. (int)$lang->id .'" AS `id_lang`, `link`, `name`, `have_icon`, `image_type`, `image_legend`
                    FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_elements_lang`
                    WHERE `id_lang` = '. (int)$this->context->cookie->id_lang .'
                )
            ');
            $res &= Db::getInstance()->Execute('
                INSERT IGNORE INTO `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_wrap_lang`
                (
                    SELECT `id_wrap`, "'. (int)$lang->id .'" AS `id_lang`, `value_over`, `value_under`
                    FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_wrap_lang`
                    WHERE `id_lang` = '. (int)$this->context->cookie->id_lang .'
                )
            ');
            $res &= Db::getInstance()->Execute('
                INSERT IGNORE INTO `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_lang`
                (
                    SELECT `id_column`, "'. (int)$lang->id .'" AS `id_lang`, `name`, `value_over`, `value_under`, `link`, `have_icon`, `image_type`, `image_legend`
                    FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_lang`
                    WHERE `id_lang` = '. (int)$this->context->cookie->id_lang .'
                )
            ');
            $res &= Db::getInstance()->Execute('
                INSERT IGNORE INTO `' . _DB_PREFIX_ . 'pm_advancedtopmenu_lang`
                (
                    SELECT `id_menu`, "'. (int)$lang->id .'" AS `id_lang`, `name`, `value_over`, `value_under`, `link`, `have_icon`, `image_type`, `image_legend`
                    FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_lang`
                    WHERE `id_lang` = '. (int)$this->context->cookie->id_lang .'
                )
            ');
            $newIsoLang = $lang->iso_code;
            $moduleRoot = _PS_ROOT_DIR_ . '/modules/' . $this->name;
            $elementsList = Db::getInstance()->ExecuteS('SELECT `id_element`, `image_type` FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_elements_lang` WHERE `have_icon`=1 AND `id_lang` = '. (int)$this->context->cookie->id_lang);
            if (self::_isFilledArray($elementsList)) {
                foreach ($elementsList as $image) {
                    $src = $moduleRoot . '/element_icons/' . $image['id_element'] . '-' . $this->_iso_lang . '.' . $image['image_type'];
                    $dest = $moduleRoot . '/element_icons/' . $image['id_element'] . '-' . $newIsoLang . '.' . $image['image_type'];
                    if (file_exists($src)) {
                        $res &= copy($src, $dest);
                    }
                }
            }
            $columnsList = Db::getInstance()->ExecuteS('SELECT `id_column`, `image_type` FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_columns_lang` WHERE `have_icon`=1 AND `id_lang` = '. (int)$this->context->cookie->id_lang);
            if (self::_isFilledArray($columnsList)) {
                foreach ($columnsList as $image) {
                    $src = $moduleRoot . '/column_icons/' . $image['id_column'] . '-' . $this->_iso_lang . '.' . $image['image_type'];
                    $dest = $moduleRoot . '/column_icons/' . $image['id_column'] . '-' . $newIsoLang . '.' . $image['image_type'];
                    if (file_exists($src)) {
                        $res &= copy($src, $dest);
                    }
                }
            }
            $menusList = Db::getInstance()->ExecuteS('SELECT `id_menu`, `image_type` FROM `' . _DB_PREFIX_ . 'pm_advancedtopmenu_lang` WHERE `have_icon`=1 AND `id_lang` = '. (int)$this->context->cookie->id_lang);
            if (self::_isFilledArray($menusList)) {
                foreach ($menusList as $image) {
                    $src = $moduleRoot . '/menu_icons/' . $image['id_menu'] . '-' . $this->_iso_lang . '.' . $image['image_type'];
                    $dest = $moduleRoot . '/menu_icons/' . $image['id_menu'] . '-' . $newIsoLang . '.' . $image['image_type'];
                    if (file_exists($src)) {
                        $res &= copy($src, $dest);
                    }
                }
            }
        }
    }
    public function hookActionShopDataDuplication($params)
    {
        if (Tools::getIsset('importData')) {
            $importData = Tools::getValue('importData');
            if (isset($importData['product'])) {
                $query = new DbQuery();
                $query->select('id_menu');
                $query->from('pm_advancedtopmenu_shop');
                $query->where('id_shop = '.(int) $params['old_id_shop']);
                $menus = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($query);
                if (is_array($menus) && count($menus)) {
                    foreach ($menus as $menu) {
                        Db::getInstance()->insert(
                            'pm_advancedtopmenu_shop',
                            array(
                                'id_menu' => (int) $menu['id_menu'],
                                'id_shop' => (int) $params['new_id_shop'],
                            )
                        );
                        $this->generateGlobalCss((int) $params['new_id_shop']);
                        $this->generateCss();
                        $this->clearCache();
                    }
                }
            }
        }
    }
    public function hookDisplayNav()
    {
        if (Configuration::get('ATM_MENU_CONT_HOOK') == 'nav') {
            return $this->outputMenuContent();
        }
    }
    public function hookDisplayNavFullWidth()
    {
        if (Configuration::get('ATM_MENU_CONT_HOOK') == 'nav-full') {
            return $this->outputMenuContent();
        }
    }
    public function hookDisplayTop()
    {
        if (version_compare(_PS_VERSION_, '1.6.0.2', '<') || Configuration::get('ATM_MENU_CONT_HOOK') == 'top') {
            return $this->outputMenuContent();
        }
    }
    public function outputMenuContent()
    {
        if ($this->_isInMaintenance()) {
            return;
        }
        $return = '';
        $cache = Configuration::get('ATM_CACHE');
        if (!Configuration::get('PS_SMARTY_CACHE')) {
            $cache = false;
        }
        if ($cache) {
            if (Configuration::get('ATM_MENU_GLOBAL_ACTIF')) {
                $curUrl = explode('?', $_SERVER ['REQUEST_URI']);
                $curUrl = $curUrl [0] . $this->getKeepVar();
                $strCacheUrl = sha1(preg_replace('#https?://' . preg_quote(htmlspecialchars($_SERVER ['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__, '#') . '#i', '', $curUrl));
            } else {
                $strCacheUrl = 'global';
            }
            $adtmCacheId = sprintf('ADTM|%s|%d|%d|%s|%d|%s', $strCacheUrl, $this->context->cookie->id_lang, $this->context->cookie->id_currency, $this->_isLogged(), (Shop::isFeatureActive() ? $this->context->shop->id : 0), implode('-', self::getCustomerGroups()));
            $this->_enableCachePM(2);
        }
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $templatePath = 'module:pm_advancedtopmenu/views/templates/front/pm_advancedtopmenu.tpl';
        } else {
            $templatePath = 'pm_advancedtopmenu.tpl';
        }
        if ((!$cache || !$this->isCached($templatePath, $adtmCacheId))) {
            $menus = AdvancedTopMenuClass::getMenus($this->context->cookie->id_lang, true, false, true);
            if (!is_array($menus) || !count($menus)) {
                $this->_restoreCacheSettingsPM();
                return;
            }
            $columnsWrap = AdvancedTopMenuColumnWrapClass::getMenusColumnsWrap($menus, $this->context->cookie->id_lang);
            $columns = AdvancedTopMenuColumnClass::getMenusColums($columnsWrap, $this->context->cookie->id_lang, true);
            $elements = AdvancedTopMenuElementsClass::getMenuColumnsElements($columns, $this->context->cookie->id_lang, true, true);
            $advtmThemeCompatibility = (bool)Configuration::get('ATM_THEME_COMPATIBILITY_MODE') && ((bool)Configuration::get('ATM_MENU_CONT_HOOK') == 'top');
            $advtmResponsiveMode = ((bool)Configuration::get('ATM_RESPONSIVE_MODE') && (int)Configuration::get('ATM_RESPONSIVE_THRESHOLD') > 0);
            $advtmResponsiveToggleText = (Configuration::get('ATM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) !== false && Configuration::get('ATM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) != '' ? Configuration::get('ATM_RESP_TOGGLE_TEXT', $this->context->cookie->id_lang) : $this->l('Menu'));
            $advtmResponsiveContainerClasses = trim(Configuration::get('ATM_RESP_CONT_CLASSES'));
            $advtmContainerClasses = trim(Configuration::get('ATM_CONT_CLASSES'));
            $advtmInnerClasses = trim(Configuration::get('ATM_INNER_CLASSES'));
            $advtmIsSticky = (Configuration::get('ATM_MENU_CONT_POSITION') == 'sticky');
            $advtmOpenMethod = (int)Configuration::get('ATM_SUBMENU_OPEN_METHOD');
            if ($advtmOpenMethod == 2) {
                $advtmInnerClasses .= ' advtm_open_on_click';
            } else {
                $advtmInnerClasses .= ' advtm_open_on_hover';
            }
            $advtmInnerClasses = trim($advtmInnerClasses);
            $customerGroups = self::getCustomerGroups();
            foreach ($menus as &$menu) {
                $menuHaveSub = count($columnsWrap[$menu['id_menu']]);
                $menu['link_output_value'] = $this->getLinkOutputValue($menu, 'menu', true, $menuHaveSub, true);
                foreach ($columnsWrap[$menu['id_menu']] as &$columnWrap) {
                    foreach ($columns[$columnWrap['id_wrap']] as &$column) {
                        $column['link_output_value'] = $this->getLinkOutputValue($column, 'column', true);
                        foreach ($elements[$column['id_column']] as &$element) {
                            $element['link_output_value'] = $this->getLinkOutputValue($element, 'element', true);
                        }
                    }
                }
            }
            $this->context->smarty->assign(array(
                'advtmIsSticky' => $advtmIsSticky,
                'advtmOpenMethod' => $advtmOpenMethod,
                'advtmInnerClasses' => $advtmInnerClasses,
                'advtmContainerClasses' => $advtmContainerClasses,
                'advtmResponsiveContainerClasses' => $advtmResponsiveContainerClasses,
                'advtmResponsiveToggleText' => $advtmResponsiveToggleText,
                'advtmResponsiveMode' => $advtmResponsiveMode,
                'advtmThemeCompatibility' => $advtmThemeCompatibility,
                'advtm_menus' => $menus,
                'advtm_columns_wrap' => $columnsWrap,
                'advtm_columns' => $columns,
                'advtm_elements' => $elements,
                'advtm_obj' => $this,
                'isLogged' => $this->_isLogged(),
                'customerGroups' => $customerGroups,
                'advtmActivatedMenuId' => $this->activateMenuId,
                'advtmActivatedMenuType' => $this->activateMenuType,
            ));
        }
        if ($cache) {
            $this->context->smarty->cache_lifetime = 3600;
            if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                $return = $this->fetch($templatePath, $adtmCacheId);
            } else {
                $return = $this->display(__FILE__, $templatePath, $adtmCacheId);
            }
            $this->_restoreCacheSettingsPM();
            return $return;
        } else {
            if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                $return = $this->fetch($templatePath);
            } else {
                $return = $this->display(__FILE__, $templatePath);
            }
            $this->context->smarty->caching = 0;
            return $return;
        }
    }
    public function hookCategoryUpdate()
    {
        $this->clearCache();
    }
    public static function _isFilledArray($array)
    {
        return ($array && is_array($array) && count($array));
    }
    public function _displayTitle($title)
    {
        $vars = array(
            'text' => $title
        );
        return $this->fetchTemplate('core/title.tpl', $vars);
    }
    
    private function _getPMdata()
    {
        $param = array();
        $param[] = 'ver-'._PS_VERSION_;
        $param[] = 'current-'.$this->name;
        
        $result = $this->getPMAddons();
        if ($result && self::_isFilledArray($result)) {
            foreach ($result as $moduleName => $moduleVersion) {
                $param[] = $moduleName . '-' . $moduleVersion;
            }
        }
        return self::getDataSerialized(implode('|', $param));
    }
    private function getPMAddons()
    {
        $pmAddons = array();
        $result = Db::getInstance()->ExecuteS('SELECT DISTINCT name FROM '._DB_PREFIX_.'module WHERE name LIKE "pm_%"');
        if ($result && self::_isFilledArray($result)) {
            foreach ($result as $module) {
                $instance = Module::getInstanceByName($module['name']);
                if ($instance && isset($instance->version)) {
                    $pmAddons[$module['name']] = $instance->version;
                }
            }
        }
        return $pmAddons;
    }
    private function doHttpRequest($data = array(), $c = 'prestashop', $s = 'api.addons')
    {
        $data = array_merge(array(
            'version' => _PS_VERSION_,
            'iso_lang' => Tools::strtolower($this->_iso_lang),
            'iso_code' => Tools::strtolower(Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'))),
            'module_key' => $this->module_key,
            'method' => 'contributor',
            'action' => 'all_products',
        ), $data);
        $postData = http_build_query($data);
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'content' => $postData,
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'timeout' => 15,
            )
        ));
        $response = Tools::file_get_contents('https://' . $s . '.' . $c . '.com', false, $context);
        if (empty($response)) {
            return false;
        }
        $responseToJson = Tools::jsonDecode($response);
        if (empty($responseToJson)) {
            return false;
        }
        return $responseToJson;
    }
    private function getAddonsModulesFromApi()
    {
        $modules = Configuration::get('PM_' . self::$_module_prefix . '_AM');
        $modules_date = Configuration::get('PM_' . self::$_module_prefix . '_AMD');
        if ($modules && strtotime('+2 day', $modules_date) > time()) {
            return Tools::jsonDecode($modules, true);
        }
        $jsonResponse = $this->doHttpRequest();
        if (empty($jsonResponse->products)) {
            return array();
        }
        $dataToStore = array();
        foreach ($jsonResponse->products as $addonsEntry) {
            $dataToStore[(int)$addonsEntry->id] = array(
                'name' => $addonsEntry->name,
                'displayName' => $addonsEntry->displayName,
                'url' => $addonsEntry->url,
                'compatibility' => $addonsEntry->compatibility,
                'version' => $addonsEntry->version,
                'description' => $addonsEntry->description,
            );
        }
        Configuration::updateValue('PM_' . self::$_module_prefix . '_AM', Tools::jsonEncode($dataToStore));
        Configuration::updateValue('PM_' . self::$_module_prefix . '_AMD', time());
        return Tools::jsonDecode(Configuration::get('PM_' . self::$_module_prefix . '_AM'), true);
    }
    private function getPMModulesFromApi()
    {
        $modules = Configuration::get('PM_' . self::$_module_prefix . '_PMM');
        $modules_date = Configuration::get('PM_' . self::$_module_prefix . '_PMMD');
        if ($modules && strtotime('+2 day', $modules_date) > time()) {
            return Tools::jsonDecode($modules, true);
        }
        $jsonResponse = $this->doHttpRequest(array('list' => $this->getPMAddons()), 'presta-module', 'api-addons');
        if (empty($jsonResponse)) {
            return array();
        }
        Configuration::updateValue('PM_' . self::$_module_prefix . '_PMM', Tools::jsonEncode($jsonResponse));
        Configuration::updateValue('PM_' . self::$_module_prefix . '_PMMD', time());
        return Tools::jsonDecode(Configuration::get('PM_' . self::$_module_prefix . '_PMM'), true);
    }
    public function _displaySupport()
    {
        $get_started_image_list = array();
        if (isset($this->_getting_started) && self::_isFilledArray($this->_getting_started)) {
            foreach ($this->_getting_started as $get_started_image) {
                $get_started_image_list[] = "{ 'href': '".$get_started_image['href']."', 'title': '".htmlentities($get_started_image['title'], ENT_QUOTES, 'UTF-8')."' }";
            }
        }
        $pm_addons_products = $this->getAddonsModulesFromApi();
        $pm_products = $this->getPMModulesFromApi();
        if (!is_array($pm_addons_products)) {
            $pm_addons_products = array();
        }
        if (!is_array($pm_products)) {
            $pm_products = array();
        }
        self::shuffleArray($pm_addons_products);
        if (self::_isFilledArray($pm_addons_products)) {
            if (!empty($pm_products['ignoreList']) && self::_isFilledArray($pm_products['ignoreList'])) {
                foreach ($pm_products['ignoreList'] as $ignoreId) {
                    if (isset($pm_addons_products[$ignoreId])) {
                        unset($pm_addons_products[$ignoreId]);
                    }
                }
            }
            $addonsList = $this->getPMAddons();
            if ($addonsList && self::_isFilledArray($addonsList)) {
                foreach (array_keys($addonsList) as $moduleName) {
                    foreach ($pm_addons_products as $k => $pm_addons_product) {
                        if ($pm_addons_product['name'] == $moduleName) {
                            unset($pm_addons_products[$k]);
                            break;
                        }
                    }
                }
            }
        }
        $vars = array(
            'support_links' => (self::_isFilledArray($this->_support_link) ? $this->_support_link : array()),
            'copyright_link' => (self::_isFilledArray($this->_copyright_link) ? $this->_copyright_link : false),
            'get_started_image_list' => (isset($this->_getting_started) && self::_isFilledArray($this->_getting_started) ? $this->_getting_started : array()),
            'pm_module_version' => $this->version,
            'pm_data' => $this->_getPMdata(),
            'pm_products' => $pm_products,
            'pm_addons_products' => $pm_addons_products,
            'html_at_end' => (method_exists($this, '_includeHTMLAtEnd') ? $this->_includeHTMLAtEnd() : ''),
        );
        return $this->fetchTemplate('core/support.tpl', $vars);
    }
    public static function shuffleArray(&$a)
    {
        if (is_array($a) && count($a)) {
            $ks = array_keys($a);
            shuffle($ks);
            $new = array();
            foreach ($ks as $k) {
                $new[$k] = $a[$k];
            }
            $a = $new;
            return true;
        }
        return false;
    }
    protected function displayMaintenanceZone()
    {
        $tab_http_key = 'controller';
        $tab_http_value = 'AdminMaintenance';
        $vars = array(
            'pm_maintenance' => Configuration::get('PM_' . self::$_module_prefix . '_MAINTENANCE'),
            'ip_maintenance' => Configuration::get('PS_MAINTENANCE_IP'),
            'maintenanceTabUrl' => $this->context->link->getAdminLink('AdminMaintenance'),
        );
        return $this->fetchTemplate('core/maintenance.tpl', $vars);
    }
    public function _postProcessMaintenance()
    {
        $return = '';
        $maintenance = Configuration::get('PM_' . self::$_module_prefix . '_MAINTENANCE');
        $maintenance = ($maintenance ? 0 : 1);
        Configuration::updateValue('PM_' . self::$_module_prefix . '_MAINTENANCE', (int)$maintenance);
        if ($maintenance) {
            $return .= '$("#pmImgMaintenance").attr("class", "ui-icon ui-icon-locked");';
            $return .= '$("#maintenanceWarning").slideDown();';
            $return .= 'show_info("' . $this->l('Your module is now in maintenance mode.') . '");';
        } else {
            $return .= '$("#pmImgMaintenance").attr("class", "ui-icon ui-icon-unlocked");';
            $return .= '$("#maintenanceWarning").slideUp();';
            $return .= 'show_info("' . $this->l('Your module is now running in normal mode.') . '");';
        }
        $this->clearCache();
        self::_cleanBuffer();
        return $return;
    }
    protected function _isInMaintenance()
    {
        if (isset($this->_cacheIsInMaintenance)) {
            return $this->_cacheIsInMaintenance;
        }
        if (Configuration::get('PM_'.self::$_module_prefix.'_MAINTENANCE')) {
            $ips = explode(',', Configuration::get('PS_MAINTENANCE_IP'));
            if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
                $this->_cacheIsInMaintenance = false;
                return false;
            }
            $this->_cacheIsInMaintenance = true;
            return true;
        }
        $this->_cacheIsInMaintenance = false;
        return false;
    }
    protected static function _cleanBuffer()
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }
    }
    private function _isLogged()
    {
        return $this->context->customer->isLogged();
    }
    protected function _showRating($show = false)
    {
        $dismiss = (int)Configuration::getGlobalValue('PM_'.self::$_module_prefix.'_DISMISS_RATING');
        if ($show && $dismiss != 1 && self::_getNbDaysModuleUsage() >= 3) {
            return $this->fetchTemplate('core/rating.tpl');
        }
        return '';
    }
    protected static function _getNbDaysModuleUsage()
    {
        $sql = 'SELECT DATEDIFF(NOW(),date_add)
                FROM '._DB_PREFIX_.'configuration
                WHERE name = \''.pSQL('ATM_LAST_VERSION').'\'
                ORDER BY date_add ASC';
        return (int)Db::getInstance()->getValue($sql);
    }
    protected function _onBackOffice()
    {
        if (isset($this->context->cookie->id_employee) && Validate::isUnsignedId($this->context->cookie->id_employee)) {
            return true;
        }
        return false;
    }
    public static function getCustomerGroups()
    {
        $groups = array();
        if (Group::isFeatureActive()) {
            if (Validate::isLoadedObject(Context::getContext()->customer)) {
                $groups = FrontController::getCurrentCustomerGroups();
            } else {
                $groups = array((int)Configuration::get('PS_UNIDENTIFIED_GROUP'));
            }
        }
        sort($groups);
        return $groups;
    }
    protected static function getProductsImagesTypes()
    {
        $a = array();
        foreach (ImageType::getImagesTypes('products') as $imageType) {
            $a[$imageType['name']] = $imageType['name'] . ' (' . $imageType['width'] .' x ' . $imageType['height'] .' pixels)';
        }
        return $a;
    }
    public static function getDataSerialized($data, $type = 'base64')
    {
        if (is_array($data)) {
            return array_map($type . '_encode', array($data));
        } else {
            return current(array_map($type . '_encode', array($data)));
        }
    }
    public function smartyNoFilterModifier($s)
    {
        return $s;
    }
    protected function registerFrontSmartyObjects()
    {
        static $registeredFO = false;
        if (!$registeredFO && !empty($this->context->smarty)) {
            $this->context->smarty->unregisterPlugin('modifier', Tools::strtolower(self::$_module_prefix) . '_nofilter');
            $this->context->smarty->registerPlugin('modifier', Tools::strtolower(self::$_module_prefix) . '_nofilter', array($this, 'smartyNoFilterModifier'));
            $registeredFO = true;
        }
    }
    protected function registerSmartyObjects()
    {
        static $registered = false;
        if (!$registered && !empty($this->context->smarty)) {
            $this->registerFrontSmartyObjects();
            $this->context->smarty->registerObject('module', $this, array(
                'getAdminOutputPrivacyValue',
                'getType',
                'getAdminOutputNameValue',
                '_displayTitle',
                'outputFormItem',
                'displayFlags',
                'outputMenuForm',
                'outputColumnWrapForm',
                'outputColumnForm',
                'outputElementForm',
                'outputTargetSelect',
                'outputChosenGroups',
                'outputCategoriesSelect',
                'outputCmsSelect',
                'outputManufacturerSelect',
                'outputSupplierSelect',
                'outputSpecificPageSelect',
                'outputSelectColumnsWrap',
                'outputSelectColumns',
                'showWarning',
                '_displaySupport',
            ), false);
            $registered = true;
        }
    }
    protected function fetchTemplate($tpl, $customVars = array(), $configOptions = array())
    {
        $this->registerSmartyObjects();
        $this->context->smarty->assign(array(
            'ps_major_version' => Tools::substr(str_replace('.', '', _PS_VERSION_), 0, 2),
            'module_name' => $this->name,
            'module_path' => $this->_path,
            'base_config_url' => $this->base_config_url,
            'current_iso_lang' => $this->_iso_lang,
            'current_id_lang' => (int)$this->context->language->id,
            'default_language' => $this->defaultLanguage,
            'languages' => $this->languages,
            'options' => $configOptions,
            'shopFeatureActive' => Shop::isFeatureActive(),
        ));
        if (is_array($customVars) && count($customVars)) {
            $this->context->smarty->assign($customVars);
        }
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/admin/' . $tpl);
    }
    public function showWarning($text)
    {
        $vars = array(
            'text' => $text
        );
        return $this->fetchTemplate('core/warning.tpl', $vars);
    }
    public function renderWidget($hookName, array $configuration)
    {
        return $this->outputMenuContent();
    }
    public function getWidgetVariables($hookName, array $configuration)
    {
        return array();
    }
}
