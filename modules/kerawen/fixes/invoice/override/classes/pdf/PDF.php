<?php

class PDF extends PDFCore {

	public function getTemplateObject($object)
	{
		// First search for the template by KerAwen
		require_once(_PS_MODULE_DIR_.'kerawen/classes/pdf.php');
		$template_orig = $this->template;
		$this->template = $this->template.'Kerawen';
		$class = parent::getTemplateObject($object);
		$this->template = $template_orig;
		
		if (!$class) $class = parent::getTemplateObject($object);
		return $class;
	}
}
