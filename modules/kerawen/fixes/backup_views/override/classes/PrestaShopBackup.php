<?php

class PrestaShopBackup extends PrestaShopBackupCore
{
	public function add()
	{
		if (!$this->psBackupAll)
			$ignore_insert_table = array(_DB_PREFIX_.'connections', _DB_PREFIX_.'connections_page', _DB_PREFIX_
				.'connections_source', _DB_PREFIX_.'guest', _DB_PREFIX_.'statssearch');
		else
			$ignore_insert_table = array();
		
		// Generate some random number, to make it extra hard to guess backup file names
		$rand = dechex ( mt_rand(0, min(0xffffffff, mt_getrandmax() ) ) );
		$date = time();
		$backupfile = $this->getRealBackupPath().$date.'-'.$rand.'.sql';

		// Figure out what compression is available and open the file
		if (function_exists('bzopen'))
		{
			$backupfile .= '.bz2';
			$fp = @bzopen($backupfile, 'w');
		}
		elseif (function_exists('gzopen'))
		{
			$backupfile .= '.gz';
			$fp = @gzopen($backupfile, 'w');
		}
		else
			$fp = @fopen($backupfile, 'w');

		if ($fp === false)
		{
			echo Tools::displayError('Unable to create backup file').' "'.addslashes($backupfile).'"';
			return false;
		}

		$this->id = realpath($backupfile);

		fwrite($fp, '-- Backup for '.Tools::getHttpHost(false, false).__PS_BASE_URI__."\n-- at ".date($date)."\n");
		fwrite($fp, "\n".'SET NAMES \'utf8\';'."\n\n");

		// Find all tables
		$tables = Db::getInstance()->executeS('SHOW TABLES');
		$found = 0;
		$create_views = '';
		foreach ($tables as $table)
		{
			$table = current($table);

			// Skip tables which do not start with _DB_PREFIX_
			if (strlen($table) < strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, strlen(_DB_PREFIX_)) != 0)
				continue;

			// Export the table schema
			$schema = Db::getInstance()->executeS('SHOW CREATE TABLE `'.$table.'`');

            if (count($schema) == 1 && isset($schema[0]['Table']) && isset($schema[0]['Create Table'])) {
                fwrite($fp, '-- Scheme for table '.$schema[0]['Table']."\n");

                if ($this->psBackupDropTable) {
                    fwrite($fp, 'DROP TABLE IF EXISTS `'.$schema[0]['Table'].'`;'."\n");
                }

                fwrite($fp, $schema[0]['Create Table'].";\n\n");

                if (!in_array($schema[0]['Table'], $ignore_insert_table)) {
                    $data = Db::getInstance()->query('SELECT * FROM `'.$schema[0]['Table'].'`', false);
                    $sizeof = DB::getInstance()->NumRows();
                    $lines = explode("\n", $schema[0]['Create Table']);

                    if ($data && $sizeof > 0) {
                        // Export the table data
                        fwrite($fp, 'INSERT INTO `'.$schema[0]['Table']."` VALUES\n");
                        $i = 1;
                        while ($row = DB::getInstance()->nextRow($data)) {
                            $s = '(';

                            foreach ($row as $field => $value) {
                                $tmp = "'".pSQL($value, true)."',";
                                if ($tmp != "'',") {
                                    $s .= $tmp;
                                } else {
                                    foreach ($lines as $line) {
                                        if (strpos($line, '`'.$field.'`') !== false) {
                                            if (preg_match('/(.*NOT NULL.*)/Ui', $line)) {
                                                $s .= "'',";
                                            } else {
                                                $s .= 'NULL,';
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                            $s = rtrim($s, ',');

                            if ($i % 200 == 0 && $i < $sizeof) {
                                $s .= ");\nINSERT INTO `".$schema[0]['Table']."` VALUES\n";
                            } elseif ($i < $sizeof) {
                                $s .= "),\n";
                            } else {
                                $s .= ");\n";
                            }

                            fwrite($fp, $s);
                            ++$i;
                        }
                    }
                }
            }
            else if (count($schema) == 1 && isset($schema[0]['View']) && isset($schema[0]['Create View'])) {
                // Do not create now, but wait for all tables to be created
                $create_views .= '-- Scheme for view '.$schema[0]['View']."\n";
                if ($this->psBackupDropTable) {
                    $create_views .= 'DROP VIEW IF EXISTS `'.$schema[0]['View'].'`;'."\n";
                }
                $create_views .= $schema[0]['Create View'].";\n\n";
            }
            else {
                fclose($fp);
                $this->delete();
                echo Tools::displayError('An error occurred while backing up. Unable to obtain the schema of').' "'.$table;
                return false;
            }

            $found++;
        }
        fwrite($fp, $create_views);

		fclose($fp);
		if ($found == 0)
		{
			$this->delete();
			echo Tools::displayError('No valid tables were found to backup.' );
			return false;
		}

		return true;
	}
}
