<?php

class AdminHTMLBlocksController extends ModuleAdminController
{
    public $className = 'HTMLBlock';
    private $name;
    private $repository;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
        $this->meta_title = $this->trans('HTML Blocks', array(), 'Modules.HTMLBlocks');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->name = 'HTMLBlocks';

        $this->repository = new HTMLBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('edit'.$this->className)) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addHTMLBlocks')) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->className)) {
            if (!$this->manageHTMLBlock()) {
                return false;
            }

            $hook_name = Hook::getNameById(Tools::getValue('id_hook'));
            if (!Hook::isModuleRegisteredOnHook($this->module, $hook_name, $this->context->shop->id)) {
                Hook::registerHook($this->module, $hook_name);
            }

            $this->module->_clearCache($this->module->templateFile);

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        } elseif (Tools::isSubmit('delete'.$this->className)) {
            if (!$this->deleteHTMLBlock()) {
                return false;
            }

            $this->module->_clearCache($this->module->templateFile);

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        }

        return parent::postProcess();
    }

    public function renderView()
    {
        $title = $this->trans('HTML Block configuration', array(), 'Modules.HTMLBlocks');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $title,
                'icon' => 'icon-list-alt'
            ),
            'input' => array(
                array(
                    'type' => 'html_blocks',
                    'label' => $this->trans('HTML Blocks', array(), 'Modules.HTMLBlocks'),
                    'name' => 'html_blocks',
                    'values' => $this->repository->getCMSBlocksSortedByHook(),
                ),
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->trans('New block', array(), 'Modules.HTMLBlocks'),
                    'href' => $this->context->link->getAdminLink('Admin'.$this->name).'&amp;addHTMLBlocks',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                ),
            ),
        );

        $this->getLanguages();

        $helper = $this->buildHelper();
        $helper->submit_action = '';
        $helper->title = $title;

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    public function renderForm()
    {
        $block = new HTMLBlock((int)Tools::getValue('id_html_block'));

        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($block) ? $this->trans('Edit the link block.', array(), 'Modules.HTMLBlocks') : $this->trans('New link block', array(), 'Modules.HTMLBlocks'),
                'icon' => isset($block) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_html_block',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Name of the HTML block', array(), 'Modules.HTMLBlocks'),
                    'name' => 'name',
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Hook', array(), 'Admin.Global'),
                    'name' => 'id_hook',
                    'class' => 'input-lg',
                    'options' => array(
                        'query' => $this->repository->getDisplayHooksForHelper(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Position', array(), 'Modules.HTMLBlocks'),
                    'name' => 'position',
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->trans('Content', array(), 'Modules.HTMLBlocks') . ' - ' . Language::getLanguage($this->default_form_language)['name'],
                    'name' => 'content',
                    'lang' => true,
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->trans('Cancel', array(), 'Admin.Actions'),
                    'href' => (Tools::safeOutput(Tools::getValue('back', false)))
                                ?: $this->context->link->getAdminLink('Admin'.$this->name),
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submit'.$this->className,
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            )
        );

        if ($id_hook = Tools::getValue('id_hook')) {
            $block->id_hook = (int)$id_hook;
        }

        if (Tools::getValue('name')) {
            $block->name = Tools::getValue('name');
        }

        $helper = $this->buildHelper();
        if (isset($id_html_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&id_html_block='.$id_html_block;
            $helper->submit_action = 'edit'.$this->className;
        } else {
            $helper->submit_action = 'addHTMLBlock';
        }

        $helper->fields_value = (array)$block;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'htmlblocks/';
        $helper->identifier = $this->className;
        $helper->token = Tools::getAdminTokenLite('Admin'.$this->name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('Admin'.$this->name);
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->trans('Themes', array(), 'Modules.HTMLBlocks');
        $this->toolbar_title[] = $this->trans('HTML Blocks', array(), 'Modules.HTMLBlocks');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryPlugin('tablednd');
        $this->addJS(_PS_JS_DIR_.'admin/dnd.js');
    }

    private function manageHTMLBlock() {
        $id_html_block = (int) Tools::getValue('id_html_block');
        $id_hook = (int) Tools::getValue('id_hook');

        if (!empty($id_hook)) {
            return $this->repository->createOrUpdateHTMLBlock(
                $id_html_block,
                $id_hook,
                Tools::getValue('name'));
        }
        return false;
    }

    private function deleteHTMLBlock()
    {
        $success = true;

        $id_html_block = (int) Tools::getValue('id_html_block');

        if (!empty($id_html_block)) {
            $success &= Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'html_block` WHERE `id_html_block` = '.$id_html_block);

            if ($success) {
                $success &= Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'html_block_lang` WHERE `id_html_block` = '.$id_html_block);
            }
        }

        return $success;
    }
}
