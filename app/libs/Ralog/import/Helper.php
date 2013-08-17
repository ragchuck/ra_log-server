<?php

namespace Ralog\Import;

class Helper
{

    /**
     * Extracts a zip-archive and returns the extracted file names
     *
     * @param string $file
     * @throws Exception
     * @return array
     */
    public static function unzip($file)
    {

        $extracted_files = array();
        $infZipPattern = '/inflating: (.*[.](zip|xml))/';
        $dir = dirname($file);

        if (class_exists("ZipArchive")) {
            $zip = new \ZipArchive();
            $res = $zip->open($file);

            if ($res === FALSE) {
                trigger_error("Unable to zip-open file '{$file}'", E_WARNING);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                array_push($extracted_files, $dir . '/' . $zip->getNameIndex($i));
            }

            $zip->extractTo($dir);
            $zip->close();


            sort($extracted_files, SORT_STRING);

            return $extracted_files;
        } else {
            $out = shell_exec('unzip "' . $file . '" -d "' . $dir . '"');
            preg_match_all($infZipPattern, $out, $matches,
                PREG_PATTERN_ORDER);
            $extracted_files = $matches[1];
            sort($extracted_files, SORT_STRING);
            return $extracted_files;
        }
    }

}