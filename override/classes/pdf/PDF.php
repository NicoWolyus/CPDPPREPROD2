<?php
class PDF extends PDFCore {
	/*
    * module: kerawen
    * date: 2020-01-29 13:54:14
    * version: 2.2.14
    */
    public function getTemplateObject($object)
	{
		require_once(_PS_MODULE_DIR_.'kerawen/classes/pdf.php');
		$template_orig = $this->template;
		$this->template = $this->template.'Kerawen';
		$class = parent::getTemplateObject($object);
		$this->template = $template_orig;
		
		if (!$class) $class = parent::getTemplateObject($object);
		return $class;
	}
}
