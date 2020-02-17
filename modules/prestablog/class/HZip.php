<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

class HZip
{
    /**
    * Add files and sub-directories in a folder to zip file.
    * @param string $folder
    * @param ZipArchive $zip_file
    * @param int $exclusive_length Number of text to be exclusived from the file path.
    */
    private static function folderToZip($folder, &$zip_file, $exclusive_length)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $file_path = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $local_path = Tools::substr($file_path, $exclusive_length);
                if (is_file($file_path)) {
                    $zip_file->addFile($file_path, $local_path);
                } elseif (is_dir($file_path)) {
                    // Add sub-directory.
                    $zip_file->addEmptyDir($local_path);
                    self::folderToZip($file_path, $zip_file, $exclusive_length);
                }
            }
        }
        closedir($handle);
    }

    /**
    * Zip a folder (include itself).
    * Usage:
    *    HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
    *
    * @param string $source_path Path of directory to be zip.
    * @param string $out_zip_path Path of output zip file.
    */
    public static function zipDir($source_path, $out_zip_path)
    {
        $path_info = pathInfo($source_path);
        $parent_path = $path_info['dirname'];
        $dir_name = $path_info['basename'];

        $z = new ZipArchive();
        $z->open($out_zip_path, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dir_name);
        self::folderToZip($source_path, $z, Tools::strlen("$parent_path/"));
        $z->close();
    }
}
