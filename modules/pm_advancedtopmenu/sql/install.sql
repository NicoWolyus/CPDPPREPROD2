CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu` (
  `id_menu` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cms` int(10) unsigned NOT NULL DEFAULT '0',
  `id_category` int(10) unsigned NOT NULL DEFAULT '0',
  `id_supplier` int(10) unsigned NOT NULL DEFAULT '0',
  `id_manufacturer` int(10) unsigned NOT NULL DEFAULT '0',
  `id_specific_page` varchar(64) NOT NULL,
  `id_shop` int(10) unsigned NOT NULL DEFAULT '0',
  `position` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `txt_color_menu_tab` varchar(7) NOT NULL,
  `txt_color_menu_tab_hover` varchar(7) NOT NULL,
  `fnd_color_menu_tab` varchar(15) NOT NULL,
  `fnd_color_menu_tab_over` varchar(15) NOT NULL,
  `border_size_tab` varchar(24) NOT NULL,
  `border_color_tab` varchar(7) NOT NULL,
  `width_submenu` varchar(5) NOT NULL,
  `minheight_submenu` varchar(5) NOT NULL,
  `position_submenu` tinyint(3) unsigned NOT NULL,
  `fnd_color_submenu` varchar(15) NOT NULL,
  `border_color_submenu` varchar(7) NOT NULL,
  `border_size_submenu` varchar(24) NOT NULL,
  `privacy` tinyint(4)  NOT NULL DEFAULT '0',
  `chosen_groups` text NOT NULL ,
  `active` tinyint(4)  NOT NULL DEFAULT '0',
  `active_desktop` tinyint(4)  NOT NULL DEFAULT '1',
  `active_mobile` tinyint(4)  NOT NULL DEFAULT '1',
  `target` varchar(10) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_menu`),
  KEY `active` (`active`),
  KEY `id_shop` (`id_shop`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_lang` (
  `id_menu` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `name` varchar(256) NOT NULL,
  `value_over` text,
  `value_under` text,
  `link` varchar(256) NOT NULL,
  `have_icon` varchar(1) NOT NULL DEFAULT '',
  `image_type` varchar(4) NOT NULL,
  `image_legend` varchar(256) NOT NULL DEFAULT '',
  UNIQUE KEY `id_column` (`id_menu`,`id_lang`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_shop` (
  `id_menu` int(11) NOT NULL,
  `id_shop` int(11) NOT NULL,
  PRIMARY KEY (`id_menu`,`id_shop`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_columns_wrap` (
  `id_wrap` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_menu` int(10) unsigned NOT NULL,
  `id_menu_depend` int(10) unsigned NOT NULL,
  `internal_name` varchar(256) NOT NULL,
  `bg_color` varchar(15) NOT NULL,
  `txt_color_column` varchar(7) NOT NULL,
  `txt_color_column_over` varchar(7) NOT NULL,
  `txt_color_element` varchar(7) NOT NULL,
  `txt_color_element_over` varchar(7) NOT NULL,
  `position` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `width` smallint(4) unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(4)  NOT NULL DEFAULT '0',
  `chosen_groups` text NOT NULL ,
  `active` tinyint(4)  NOT NULL DEFAULT '0',
  `active_desktop` tinyint(4)  NOT NULL DEFAULT '1',
  `active_mobile` tinyint(4)  NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_wrap`),
  KEY `active` (`active`),
  KEY `id_menu` (`id_menu`),
   KEY `id_menu_depend` (`id_menu_depend`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_columns_wrap_lang` (
  `id_wrap` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `value_over` text,
  `value_under` text,
  UNIQUE KEY `id_wrap` (`id_wrap`,`id_lang`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_columns` (
  `id_column` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_menu` int(10) unsigned NOT NULL,
  `id_wrap` int(10) unsigned NOT NULL,
  `id_cms` int(10) unsigned NOT NULL DEFAULT '0',
  `id_category` int(10) unsigned NOT NULL DEFAULT '0',
  `id_supplier` int(10) unsigned NOT NULL DEFAULT '0',
  `id_manufacturer` int(10) unsigned NOT NULL DEFAULT '0',
  `id_specific_page` varchar(64) NOT NULL,
  `id_menu_depend` int(10) unsigned NOT NULL,
  `position` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(4)  NOT NULL DEFAULT '0',
  `chosen_groups` text NOT NULL ,
  `target` varchar(10) NOT NULL,
  `active` tinyint(4)  NOT NULL DEFAULT '0',
  `active_desktop` tinyint(4)  NOT NULL DEFAULT '1',
  `active_mobile` tinyint(4)  NOT NULL DEFAULT '1',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_column`),
  KEY `active` (`active`),
  KEY `id_wrap` (`id_wrap`),
  KEY `id_menu_depend` (`id_menu_depend`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_columns_lang` (
  `id_column` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `name` varchar(256) NOT NULL,
  `value_over` text,
  `value_under` text,
  `link` varchar(256) NOT NULL,
  `have_icon` varchar(1) NOT NULL DEFAULT '',
  `image_type` varchar(4) NOT NULL,
  `image_legend` varchar(256) NOT NULL DEFAULT '',
  UNIQUE KEY `id_column` (`id_column`,`id_lang`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_elements` (
  `id_element` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_column` int(10) unsigned NOT NULL,
  `id_cms` int(10) unsigned NOT NULL DEFAULT '0',
  `id_category` int(10) unsigned NOT NULL DEFAULT '0',
  `id_supplier` int(10) unsigned NOT NULL DEFAULT '0',
  `id_manufacturer` int(10) unsigned NOT NULL DEFAULT '0',
  `id_specific_page` varchar(64) NOT NULL,
  `id_column_depend` int(10) unsigned NOT NULL,
  `position` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(4)  NOT NULL DEFAULT '0',
  `chosen_groups` text NOT NULL ,
  `target` varchar(10) NOT NULL,
  `active` tinyint(4)  NOT NULL DEFAULT 1,
  `active_desktop` tinyint(4)  NOT NULL DEFAULT '1',
  `active_mobile` tinyint(4)  NOT NULL DEFAULT '1',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_element`),
  KEY `id_column` (`id_column`),
  KEY `id_column_depend` (`id_column_depend`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_elements_lang` (
  `id_element` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `link` varchar(256) NOT NULL,
  `name` varchar(256) NOT NULL,
  `have_icon` varchar(1) NOT NULL DEFAULT '',
  `image_type` varchar(4) NOT NULL,
  `image_legend` varchar(256) NOT NULL DEFAULT '',
  UNIQUE KEY `id_element` (`id_element`,`id_lang`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_pm_advancedtopmenu_prod_column` (
  `id_product_column` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_column` int(10) unsigned NOT NULL,
  `id_product` int(10) unsigned NOT NULL DEFAULT 0,
  `p_image_type` varchar(32) NOT NULL,
  `show_title` tinyint(4) NOT NULL DEFAULT 1,
  `show_price` tinyint(4) NOT NULL DEFAULT 1,
  `show_add_to_cart` tinyint(4) NOT NULL DEFAULT 1,
  `show_more_info` tinyint(4) NOT NULL DEFAULT 1,
  `show_quick_view` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_product_column`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8;