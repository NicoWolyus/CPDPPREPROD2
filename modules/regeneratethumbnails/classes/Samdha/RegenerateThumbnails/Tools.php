<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://doc.prestashop.com/display/PS15/Overriding+default+behaviors
 * #Overridingdefaultbehaviors-Overridingamodule%27sbehavior for more information.
 *
 * @category  Prestashop
 * @category  Module
 * @author    Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license   commercial license see license.txt
 */

class Samdha_RegenerateThumbnails_Tools
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;

        if (file_exists(_PS_ROOT_DIR_.'/images.inc.php')) {
            require_once(_PS_ROOT_DIR_.'/images.inc.php');
        }
    }

    public function regenerateThumbnail($type, $id_image_type, $restart)
    {
        $modules = null;

        /* check parameters validity */
        if ($type == 'nopicture') {
            return $this->regenerateNoPictureImages($id_image_type, $restart);
        }

        if (!isset($this->module->dirs[$type])) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Type unknowed').' '.$type,
                'current' => 0,
                'number' => 0,
                'finish' => true
            ));
        }

        $images_types = ImageType::getImagesTypes($type);
        $image_type = null;
        foreach ($images_types as $i) {
            if ($i['id_image_type'] == $id_image_type) {
                $image_type = $i;
                break;
            }
        }
        if (!$image_type) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Format unknowed').' '.$id_image_type,
                'current' => 0,
                'number' => 0,
                'finish' => true
            ));
        }

        if ($restart) {
            $this->deleteImagesDatas($type, $image_type);
        }

        // let's begin
        $images_datas = $this->getImagesDatas($type, $image_type);
        if ($restart) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => '',
                'current' => 0,
                'number' => $images_datas['number'],
                'finish' => empty($images_datas['images'])
            ));
        }
        if (empty($images_datas['images'])) {
            $this->deleteImagesDatas($type, $image_type);
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Nothing to regenerate'),
                'current' => $images_datas['number'],
                'number' => $images_datas['number'],
                'finish' => true
            ));
        }

        $key = 'images_'.$id_image_type;
        $images_to_do = max((int)$this->module->config->$key, 1);
        do {
            $image = array_shift($images_datas['images']);
            $this->updateImagesDatas($type, $image_type, $images_datas);

            if (($type == 'products')
                && method_exists('Image', 'getImgFolder')) {
                $result = $this->regenerateProducts($image, $image_type, $images_datas);
                if ($result !== true) {
                    return $result;
                }
            } else {
                if (!getimagesize($image['original'])) {
                    return $this->module->samdha_tools->jsonEncode(array(
                        'error' => $this->module->l('Image invalid:').' '.$image['original'],
                        'current' => $images_datas['number'] - count($images_datas['images']),
                        'number' => $images_datas['number'],
                        'finish' => empty($images_datas['images'])
                    ));
                }

                // delete old images
                if (file_exists($image['generated'])) {
                    unlink($image['generated']);
                }

                // regenerate images
                if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                    $result = ImageManager::resize(
                        $image['original'],
                        $image['generated'],
                        (int) $image_type['width'],
                        (int) $image_type['height']
                    );
                } else {
                    $result = imageResize(
                        $image['original'],
                        $image['generated'],
                        (int) $image_type['width'],
                        (int) $image_type['height']
                    );
                }

                if (!$result) {
                    return $this->module->samdha_tools->jsonEncode(array(
                        'error' => $this->module->l('Can\'t regenerate image')
                            .' '.$image['generated'].' '.$this->module->l('from')
                            .' '.$image['original'],
                        'current' => $images_datas['number'] - count($images_datas['images']),
                        'number' => $images_datas['number'],
                        'finish' => empty($images_datas['images'])
                    ));
                }

                // regenerate watermarks
                if ($type == 'products' && is_numeric($image['id_object'][0])) {
                    if (!is_array($modules)) {
                        $modules = Db::getInstance()->ExecuteS('
							SELECT m.`name` FROM `'._DB_PREFIX_.'module` m
							LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_module` = m.`id_module`
							LEFT JOIN `'._DB_PREFIX_.'hook` h ON hm.`id_hook` = h.`id_hook`
							WHERE h.`name` = \'watermark\' AND m.`active` = 1');
                    }
                    if ($modules && count($modules)) {
                        foreach ($modules as $module) {
                            $module_instance = Module::getInstanceByName($module['name']);
                            if (Validate::isLoadedObject($module_instance)
                                && $module->active
                                && is_callable(array($module_instance, 'hookwatermark'))
                            ) {
                                list($id_product, $id_image) = explode('-', $image['id_object']);
                                call_user_func(
                                    array($module_instance, 'hookwatermark'),
                                    array('id_image' => $id_image, 'id_product' => $id_product)
                                );
                            }
                        }
                    }
                }
            }

            $images_to_do--;
        } while ($images_to_do > 0 && !empty($images_datas['images']));

        return $this->module->samdha_tools->jsonEncode(array(
            'error' => '',
            'current' => $images_datas['number'] - count($images_datas['images']),
            'number' => $images_datas['number'],
            'finish' => empty($images_datas['images'])
        ));
    }

    public function regenerateProducts($image, $image_type, $images_datas)
    {
        static $modules = null;

        // ignore images from deleted products
        if (method_exists('Product', 'existsInDatabase')) {
            if (!Product::existsInDatabase($image['id_product'], 'product')) {
                return true;
            }
        } else {
            if (!ObjectModel::existsInDatabase($image['id_product'], 'product')) {
                return true;
            }
        }

        $dir = constant($this->module->dirs['products']);

        $image_obj = new Image((int)$image['id_image']);
        $image_obj->id_product = (int)$image['id_product'];

        $original = $dir.$image_obj->getExistingImgPath().'.jpg';
        $generated = $dir.$image_obj->getExistingImgPath().'-'
            .$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg';

        if (!file_exists($original)) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Image not found:').' '.$original
                    .' '.$this->module->l('Product #').' '.$image_obj->id_product,
                'current' => $images_datas['number'] - count($images_datas['images']),
                'number' => $images_datas['number'],
                'finish' => empty($images_datas['images'])
            ));
        }

        if (!getimagesize($original)) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Image invalid:').' '.$original
                    .' '.$this->module->l('Product #').' '.$image_obj->id_product,
                'current' => $images_datas['number'] - count($images_datas['images']),
                'number' => $images_datas['number'],
                'finish' => empty($images_datas['images'])
            ));
        }

        // delete old images
        $old_files = array(
            $generated,
            $dir.$image_obj->getImgFolder().DIRECTORY_SEPARATOR.$image_obj->id.'\-'
                .$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg',
            $dir.$image_obj->getImgFolder().DIRECTORY_SEPARATOR.$image_obj->id_product.'\-'
                .$image_obj->id.'\-'.$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg',
            $dir.DIRECTORY_SEPARATOR.$image_obj->id_product.'\-'.$image_obj->id.'\-'
                .$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg'
        );
        foreach ($old_files as $old_file) {
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $result = ImageManager::resize($original, $generated, $image_type['width'], $image_type['height']);
        } else {
            $result = imageResize($original, $generated, $image_type['width'], $image_type['height']);
        }

        if (!$result) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Can\'t regenerate image')
                    .' '.$generated.' '.$this->module->l('from').' '.$original,
                'current' => $images_datas['number'] - count($images_datas['images']),
                'number' => $images_datas['number'],
                'finish' => empty($images_datas['images'])
            ));
        }

        if (!is_array($modules)) {
            $modules = Db::getInstance()->ExecuteS('
				SELECT m.`name` FROM `'._DB_PREFIX_.'module` m
				LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_module` = m.`id_module`
				LEFT JOIN `'._DB_PREFIX_.'hook` h ON hm.`id_hook` = h.`id_hook`
				WHERE (h.`name` = \'actionWatermark\' OR h.`name` = \'watermark\') AND m.`active` = 1');
        }
        if ($modules && count($modules)) {
            foreach ($modules as $module) {
                if ($module_instance = Module::getInstanceByName($module['name'])) {
                    if (is_callable(array($module_instance, 'hookActionWatermark'))) {
                        call_user_func(
                            array($module_instance, 'hookActionWatermark'),
                            array('id_image' => $image_obj->id, 'id_product' => $image_obj->id_product)
                        );
                    } elseif (is_callable(array($module_instance, 'hookwatermark')))
                        call_user_func(
                            array($module_instance, 'hookwatermark'),
                            array('id_image' => $image_obj->id, 'id_product' => $image_obj->id_product)
                        );
                }
            }
        }

        return true;
    }

    /**
    * Regenerate no-pictures images
    */
    public function regenerateNoPictureImages($id_image_type, $restart)
    {
        $type = new ImageType($id_image_type);
        if (!Validate::isLoadedObject($type)) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Format unknowed').' '.$id_image_type,
                'current' => 0,
                'number' => 0,
                'finish' => true
            ));
        }
        $image_type = (array)$type;

        if ($restart) {
            $this->deleteImagesDatas('nopicture', $image_type);
        }

        // let's begin
        $images_datas = $this->getImagesDatas('nopicture', $image_type);
        if ($restart) {
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => '',
                'current' => 0,
                'number' => $images_datas['number'],
                'finish' => empty($images_datas['images'])
            ));
        }
        if (empty($images_datas['images'])) {
            $this->deleteImagesDatas('nopicture', (array)$image_type);
            return $this->module->samdha_tools->jsonEncode(array(
                'error' => $this->module->l('Nothing to regenerate'),
                'current' => $images_datas['number'],
                'number' => $images_datas['number'],
                'finish' => true
            ));
        }

        $key = 'images_'.$id_image_type;
        $images_to_do = max((int)$this->module->config->$key, 1);
        do {
            $image = array_shift($images_datas['images']);
            $this->updateImagesDatas('nopicture', $image_type, $images_datas);

            if (!getimagesize($image['original'])) {
                return $this->module->samdha_tools->jsonEncode(array(
                    'error' => $this->module->l('Image invalid:').' '.$image['original'],
                    'current' => $images_datas['number'] - count($images_datas['images']),
                    'number' => $images_datas['number'],
                    'finish' => empty($images_datas['images'])
                ));
            }

            // delete old images
            if (file_exists($image['generated'])) {
                unlink($image['generated']);
            }

            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                $result = ImageManager::resize(
                    $image['original'],
                    $image['generated'],
                    (int) $image_type['width'],
                    (int) $image_type['height']
                );
            } else {
                $result = imageResize(
                    $image['original'],
                    $image['generated'],
                    (int) $image_type['width'],
                    (int) $image_type['height']
                );
            }

            if (!$result) {
                return $this->module->samdha_tools->jsonEncode(array(
                    'error' => $this->module->l('Can\'t regenerate image').' '
                        .$image['generated'].' '.$this->module->l('from').' '.$image['original'],
                    'current' => $images_datas['number'] - count($images_datas['images']),
                    'number' => $images_datas['number'],
                    'finish' => empty($images_datas['images'])
                ));
            }

            $images_to_do--;
        } while ($images_to_do > 0 && !empty($images_datas['images']));

        return $this->module->samdha_tools->jsonEncode(array(
            'error' => '',
            'current' => $images_datas['number'] - count($images_datas['images']),
            'number' => $images_datas['number'],
            'finish' => empty($images_datas['images'])
        ));
    }

    /**
     * remove error message added by displayFatalError()
     * in Prestashop 1.5.3.x
     *
     * @since 1.3.4.0
     */
    public function fixDisplayFatalError()
    {
        $buffer = ob_get_contents();
        $position = strpos($buffer, '}[PrestaShop] Fatal error in module ');
        if ($position !== false) {
            ob_clean();
            $buffer = Tools::substr($buffer, 0, $position + 1);
            echo $buffer;
        }
    }

    public function getImagesDatas($type, $image_type)
    {
        $filename = $this->getImagesDatasFilename($type, $image_type);
        if (file_exists($filename)) {
            $result = $this->module->samdha_tools->jsonDecode(
                $this->module->samdha_tools->fileGetContents($filename),
                true
            );
        } else {
            if ($type == 'nopicture') {
                $images = array();
                $languages = Language::getLanguages(false);
                $default_iso = Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT'));
                foreach ($this->module->dirs as $type => $dir_name) {
                    if (isset($image_type[$type]) && $image_type[$type]) {
                        $dir = constant($dir_name);
                        foreach ($languages as $language) {
                            $original_filename = $dir.$language['iso_code'].'.jpg';
                            if (!file_exists($original_filename)) {
                                $original_filename = _PS_PROD_IMG_DIR_.$default_iso.'.jpg';
                            }
                            $generated_filename = $dir.$language['iso_code'].'-default-'
                                .$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg';
                            $images[] = array(
                                'original' => $original_filename,
                                'generated' => $generated_filename
                            );
                        }
                    }
                }
            } elseif (($type == 'products') && method_exists('Image', 'getImgFolder'))
                $images = Image::getAllImages();
            else {
                $dir_name = constant($this->module->dirs[$type]);
                $files = scandir($dir_name);
                $images = array();
                foreach ($files as $file) {
                    $matches = array();
                    if (preg_match('/^([0-9]+'.($type == 'products'?'\-[0-9]+':'').')\.jpg$/', $file, $matches)) {
                        $id_object = $matches[1];
                        $original_filename = $dir_name.$id_object.'.jpg';
                        $generated_filename = $dir_name.$id_object.'-'.(is_numeric($id_object[0])?'':'default-')
                            .$this->module->samdha_tools->stripSlashes($image_type['name']).'.jpg';
                        $images[$id_object] = array(
                            'original' => $original_filename,
                            'generated' => $generated_filename,
                            'id_object' => $id_object
                        );
                    }
                }
                ksort($images);
            }

            $result = array(
                'number' => count($images),
                'images' => $images
            );
        }

        return $result;
    }

    public function updateImagesDatas($type, $image_type, $datas)
    {
        if (empty($datas['images'])) {
            $this->deleteImagesDatas($type, $image_type);
        } else {
            file_put_contents(
                $this->getImagesDatasFilename($type, $image_type),
                $this->module->samdha_tools->jsonEncode($datas)
            );
        }
    }

    public function deleteImagesDatas($type, $image_type)
    {
        $filename = $this->getImagesDatasFilename($type, $image_type);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function getImagesDatasFilename($type, $image_type)
    {
        $special_chars = array('?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',',
            '\'', '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', chr(0));

        $filename = $type.'_'.$image_type['name'].'.json';
        $filename = preg_replace('#\x{00a0}#siu', ' ', $filename);
        $filename = str_replace($special_chars, '', $filename);
        $filename = str_replace(array( '%20', '+'), '-', $filename);
        $filename = preg_replace('/[\r\n\t -]+/', '-', $filename);
        $filename = trim($filename, '.-_');

        return _PS_ROOT_DIR_.$this->module->config->directory.$filename;
    }
}
