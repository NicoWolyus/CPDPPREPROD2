<?php

class ModuleTools extends AdminModulesControllerCore
{
	public function delete($name) {
		$module = Module::getInstanceByName($name);
		if ($module) {
			if (Module::isInstalled($name)) {
				$module->uninstall();
			}
			$this->recursiveDeleteOnDisk($module->getLocalPath());
		}
	}
}
