CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_ftp` (
    `id_ntbr_ftp`       int(10)         unsigned    NOT NULL    auto_increment,
    `active`           	tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              varchar(255)                NOT NULL,
    `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `sftp`              tinyint(1)                  NOT NULL    DEFAULT "0",
    `ssl`               tinyint(1)                  NOT NULL    DEFAULT "0",
    `passive_mode`      tinyint(1)                  NOT NULL    DEFAULT "0",
    `server`            varchar(255)                NOT NULL,
    `login`             varchar(255)                NOT NULL,
    `password`          varchar(255)                NOT NULL,
    `port`              int(10)         unsigned    NOT NULL    DEFAULT "21",
    `directory`         varchar(255)                NOT NULL    DEFAULT "/",
    `date_add`          datetime,
    `date_upd`          datetime,
    PRIMARY KEY (`id_ntbr_ftp`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_dropbox` (
    `id_ntbr_dropbox`   int(10)         unsigned    NOT NULL    auto_increment,
    `active`           	tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              varchar(255)                NOT NULL,
    `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `directory`         varchar(255)                NOT NULL    DEFAULT "",
    `token`             text                		NOT NULL,
    `date_add`          datetime,
    `date_upd`          datetime,
    PRIMARY KEY (`id_ntbr_dropbox`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_owncloud` (
    `id_ntbr_owncloud`  int(10)         unsigned    NOT NULL    auto_increment,
    `active`           	tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              varchar(255)                NOT NULL,
    `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `login`             varchar(255)                NOT NULL,
    `password`          varchar(255)                NOT NULL,
    `server`            varchar(255)                NOT NULL,
    `directory`         varchar(255)                NOT NULL    DEFAULT "",
    `date_add`          datetime,
    `date_upd`          datetime,
    PRIMARY KEY (`id_ntbr_owncloud`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_webdav` (
    `id_ntbr_webdav`    int(10)         unsigned    NOT NULL    auto_increment,
    `active`           	tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              varchar(255)                NOT NULL,
    `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `login`             varchar(255)                NOT NULL,
    `password`          varchar(255)                NOT NULL,
    `server`            varchar(255)                NOT NULL,
    `directory`         varchar(255)                NOT NULL    DEFAULT "",
    `date_add`          datetime,
    `date_upd`          datetime,
    PRIMARY KEY (`id_ntbr_webdav`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_googledrive` (
    `id_ntbr_googledrive`   int(10)         unsigned    NOT NULL    auto_increment,
    `active`           		tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`                  varchar(255)                NOT NULL,
    `nb_backup`             int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`        int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`        int(10)         unsigned    NOT NULL    DEFAULT "0",
    `directory_key`         varchar(255)                NOT NULL,
    `directory_path`        varchar(255)                NOT NULL    DEFAULT "",
    `token`                 text                		NOT NULL,
    `date_add`              datetime,
    `date_upd`              datetime,
    PRIMARY KEY (`id_ntbr_googledrive`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_onedrive` (
    `id_ntbr_onedrive`  int(10)         unsigned    NOT NULL    auto_increment,
    `active`           	tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              varchar(255)                NOT NULL,
    `nb_backup`         int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    int(10)         unsigned    NOT NULL    DEFAULT "0",
    `directory_key`     varchar(255)                NOT NULL,
    `directory_path`    varchar(255)                NOT NULL    DEFAULT "",
    `token`             text                		NOT NULL,
    `date_add`          datetime,
    `date_upd`          datetime,
    PRIMARY KEY (`id_ntbr_onedrive`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_hubic` (
    `id_ntbr_hubic`   		int(10)         unsigned    NOT NULL    auto_increment,
    `active`           		tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              	varchar(255)                NOT NULL,
    `nb_backup`         	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `directory`         	varchar(255)                NOT NULL    DEFAULT "",
    `token`             	text                		NOT NULL,
    `credential`            text                		NOT NULL,
    `date_add`          	datetime,
    `date_upd`          	datetime,
    PRIMARY KEY (`id_ntbr_hubic`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_aws` (
    `id_ntbr_aws`   		int(10)         unsigned    NOT NULL    auto_increment,
    `active`           		tinyint(1)                  NOT NULL    DEFAULT "0",
    `name`              	varchar(255)                NOT NULL,
    `nb_backup`         	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_file`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `nb_backup_base`    	int(10)         unsigned    NOT NULL    DEFAULT "0",
    `access_key_id`         varchar(255)                NOT NULL    DEFAULT "",
    `secret_access_key`     varchar(255)                NOT NULL    DEFAULT "",
    `region`                varchar(255)                NOT NULL    DEFAULT "",
    `bucket`                varchar(255)                NOT NULL    DEFAULT "",
    `directory_key`         varchar(255)                NOT NULL    DEFAULT "",
    `directory_path`        varchar(255)                NOT NULL    DEFAULT "",
    `date_add`          	datetime,
    `date_upd`          	datetime,
    PRIMARY KEY (`id_ntbr_aws`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_ntbr_comments` (
    `id_ntbr_comments`      int(10)         unsigned    NOT NULL    auto_increment,
    `backup_name`           varchar(255)                NOT NULL    DEFAULT "",
    `comment`              	text                        NOT NULL,
    `date_add`          	datetime,
    `date_upd`          	datetime,
    PRIMARY KEY (`id_ntbr_comments`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;