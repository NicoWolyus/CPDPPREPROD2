<?php
/**
 * Regenerate thumbnails
 *
 * Allows you to regenerate thumbnails
 * if the prestashop's method doesn't work.
 *
 * @category  Prestashop
 * @category  Module
 * @author    Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license   commercial license see license.txt
 */

class Samdha_RegenerateThumbnails_Main extends Samdha_Commons_Module
{
    public $short_name = 'regenthb';
    public $dirs = array(
            'categories' => '_PS_CAT_IMG_DIR_',
            'manufacturers' => '_PS_MANU_IMG_DIR_',
            'suppliers' => '_PS_SUPP_IMG_DIR_',
            'scenes' => '_PS_SCENE_IMG_DIR_',
            'products' => '_PS_PROD_IMG_DIR_',
            'stores' => '_PS_STORE_IMG_DIR_'
    );

    public function __construct()
    {
        if (version_compare(_PS_VERSION_, '1.4.0.0', '<')) {
            $this->tab = 'Tools';
        }

        parent::__construct();

        $this->page = $this->name;
        $this->tools = new Samdha_RegenerateThumbnails_Tools($this);
    }

    /* set default config */
    public function getDefaultConfig()
    {
        $result = array(
            'process' => 3,
            'directory' => '/modules/'.$this->name.'/datas/',
        );
        $images_types = ImageType::getImagesTypes();
        foreach ($images_types as $image_type) {
            $result['images_'.$image_type['id_image_type']] = 5;
        }

        return $result;
    }

    public function postProcess($token)
    {
        if (Tools::getValue('ajax')
            && ($action = Tools::getValue('action'))
            && method_exists($this, $action)) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            if (version_compare(_PS_VERSION_, '1.5.3.0', '>=')) {
                register_shutdown_function(array($this->tools, 'fixDisplayFatalError'));
            }
            call_user_func(array($this, $action));
            die();
        }

        return parent::postProcess($token);
    }

    public function displayForm($token)
    {
        $types = array(
            'categories' => array('name' => htmlspecialchars_decode($this->l('Categories', 'main'))),
            'manufacturers' => array('name' => htmlspecialchars_decode($this->l('Manufacturers', 'main'))),
            'suppliers' => array('name' => htmlspecialchars_decode($this->l('Suppliers', 'main'))),
            'scenes' => array('name' => htmlspecialchars_decode($this->l('Scenes', 'main'))),
            'products' => array('name' => htmlspecialchars_decode($this->l('Products', 'main'))),
            'stores' => array('name' => htmlspecialchars_decode($this->l('Stores', 'main'))),
            'nopicture' => array('name' => htmlspecialchars_decode($this->l('No picture images', 'main'))),
        );

        // remove unexisting folders
        foreach ($this->dirs as $dir => $const) {
            if (!defined($const)) {
                unset($types[$dir]);
            }
        }

        // get image types by folder
        $global_number = 0;
        $global_number_actif = 0;
        $global_current = 0;
        $formats = array();
        $types_names = array_keys($types);
        foreach ($types_names as $type) {
            $number = 0;
            $number_actif = 0;
            $current = 0;

            $formats[$type] = array();
            $temp = ImageType::getImagesTypes($type != 'nopicture'?$type:null);
            foreach ($temp as $format) {
                $datas = $this->tools->getImagesDatas($type, $format);
                if ((int)$datas['number']) {
                    $global_number += (int)$datas['number'];
                    $number += (int)$datas['number'];
                    $data_current = (int)$datas['number'] - count($datas['images']);
                    if ($data_current > 0) {
                        $global_number_actif += (int)$datas['number'];
                        $number_actif += (int)$datas['number'];
                        $global_current += $data_current;
                        $current += $data_current;
                    }
                    $formats[$type][$format['id_image_type']] = array(
                        'name' => $format['name'],
                        'number' => (int)$datas['number'],
                        'current' => (int)$datas['number'] - count($datas['images'])
                    );
                }
            }
            if (empty($formats[$type])) {
                unset($formats[$type]);
                unset($types[$type]);
            } elseif ($number_actif > 0) {
                $types[$type]['number'] = $number_actif;
                $types[$type]['current'] = $current;
            } else {
                $types[$type]['number'] = $number;
                $types[$type]['current'] = 0;
            }
        }

        $tabs = array(
            array('href' => '#tabRegenerate', 'display_name' => $this->l('Regenerate', 'main')),
            array('href' => '#tabParameters', 'display_name' => $this->l('Parameters', 'main'))
        );

        $this->smarty->assign(array(
            'tabs'    => $tabs,
            'types'   => $types,
            'formats' => $formats,
            'global_number' => ($global_number_actif > 0)?$global_number_actif:$global_number,
            'global_current' => ($global_number_actif > 0)?$global_current:0,
            'images_types' => ImageType::getImagesTypes()
        ));

        // Display Form
        return parent::displayForm($token);
    }

    public function regenerate()
    {
        header('Content-Type: application/json');
        echo $this->tools->regenerateThumbnail(
            Tools::getValue('type'),
            Tools::getValue('format'),
            Tools::getValue('restart')
        );
    }

    /**
     * used by jqueryFileTree
     * called by ajax
     * @return void
     */
    public function getFileTree()
    {
        $dir = Tools::getValue('dir');
        if ($dir == '') {
            echo '<ul class="jqueryFileTree" style="display: none;">';
            echo '<li><a class="directory '
                .(!is_writable(_PS_ROOT_DIR_)?'readonly ':'').'collapsed" href="#" rel="/">'
                .$this->l('Root').'</a></li>';
            echo '</ul>';
        } elseif (file_exists(_PS_ROOT_DIR_.$dir)) {
            $files = scandir(_PS_ROOT_DIR_.$dir);
            natcasesort($files);
            if (count($files) > 2) { /* The 2 accounts for . and .. */
                echo '<ul class="jqueryFileTree" style="display: none;">';
                // All dirs
                foreach ($files as $file) {
                    if (file_exists(_PS_ROOT_DIR_.$dir.$file)
                        && $file != '.'
                        && $file != '..'
                        && is_dir(_PS_ROOT_DIR_.$dir.$file)) {
                        echo '<li><a class="directory '
                            .(!is_writable(_PS_ROOT_DIR_.$dir.$file)?'readonly ':'').'collapsed" ';
                        echo 'href="#" rel="'.htmlentities($dir.$file).'/">'.htmlentities($file).'</a></li>';
                    }
                }
                echo '</ul>';
            }
        }
    }
}
