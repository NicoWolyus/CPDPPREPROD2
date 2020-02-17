<?php
/*
 * 2007-2016 PrestaShop
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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @version  Release: $Revision: 7060 $
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

 use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

 if (!defined('_CAN_LOAD_FILES_')) {
     exit;
 }

include_once(__DIR__ . '/src/HTMLBlockRepository.php');
include_once(__DIR__ . '/src/HTMLBlock.php');
include_once(__DIR__ . '/src/HTMLBlockPresenter.php');

class HTMLBlocks extends Module implements WidgetInterface
{
    protected $_html;
    protected $_display;
    private $htmlBlockPresenter;
    private $htmlBlockRepository;

    public $templateFile;

    public function __construct()
    {
        $this->name = 'htmlblocks';
        $this->author = 'Marek Rost, PrestaShop';
        $this->version = '0.0.1';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('HTML Blocks', array(), 'Modules.HTMLBlocks');
        $this->description = $this->trans('Adds blocks with custom HTML.', array(), 'Modules.HTMLBlocks');
        $this->secure_key = Tools::encrypt($this->name);

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->templateFile = 'module:htmlblocks/views/templates/hook/htmlblock.tpl';

        $this->htmlBlockPresenter = new HTMLBlockPresenter($this->context->language);
        $this->htmlBlockRepository = new HTMLBlockRepository(Db::getInstance(), $this->context->shop);
    }

    public function install()
    {
        return parent::install()
            && $this->installTab()
            && $this->htmlBlockRepository->createTables()
            && $this->htmlBlockRepository->installFixtures()
            && $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->htmlBlockRepository->dropTables();
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = "AdminHTMLBlocks";
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = "HTML Block";
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentThemes');
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminHTMLBlocks');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminHTMLBlocks')
        );
    }

    public function renderWidget($hookName, array $configuration)
    {
        $key = 'htmlblocks|' . $hookName;

        if (!$this->isCached($this->templateFile, $this->getCacheId($key))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId($key));
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_hook = Hook::getIdByName($hookName);

        $htmlBlocks = $this->htmlBlockRepository->getByIdHook($id_hook);

        $blocks = array();
        foreach ($htmlBlocks as $block) {
            $blocks[] = $this->htmlBlockPresenter->present($block);
        }

        return array(
            'htmlBlocks' => $blocks
        );
    }
}
