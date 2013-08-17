<?php

namespace Ralog;
/**
 * Import class to load the SMA-Zip archives
 *
 * @author Martin Zoellner <ragchuck at gmail.com>
 */
class Import
{

    /**
     *
     * @var string default schema name
     */
    public static $DEFAULTS = array(
        'schema' => 'MeanPublic',
        'ch_filter_type' => 'black',
        'ch_filter' => array(),
        'load_logs' => FALSE,
        'overwrite' => TRUE
    );

    /**
     *
     * @var bool
     */
    public static $THROW_EXCEPTIONS = TRUE;

    protected static $_INSTANCE;

    /**
     * @var string
     */
    protected  static $_PATH = null;

    /**
     *
     * @var array
     */
    public $config = array();

    /**
     *
     */
    protected function __construct($path)
    {
        $PATH = self::getPath() . $path . DIRECTORY_SEPARATOR;
        $config = require($PATH . 'config.php');
        $this->config['bad_path'] = self::norm_path($PATH . $config['bad_path']);
        $this->config['archive'] = self::norm_path($PATH . $config['archive']);
        $this->config['workspace'] = self::norm_path($PATH . $config['workspace']);
        $this->config['load_logs'] = (bool) $config['load_logs'];
        $this->config['overwrite'] = (bool) $config['overwrite'];
        $this->config['test_load'] = (bool) $config['test_load'];
        $this->config['ch_filter_type'] = (string) $config['ch_filter_type'];
        $this->config['ch_filter'] = (array) $config['test_load'];

    }

    public static function import($file) {
        $instance = new self(dirname($file));
        return $instance->import_file($file);
    }

    function getConfig($var, $default = "")
    {
        return isset($this->config[$var])
            ? $this->config[$var]
            : $default;
    }

    public static function setPath($path)
    {
        if (!file_exists($path))
            throw new Import\Exception(sprintf("Import path '%s' doesn't exists.", $path));

        self::$_PATH = self::norm_path($path);
    }

    public static function getPath() {
        if (is_null(self::$_PATH))
            throw new Import\Exception('Static property $PATH is not set.');

        return self::$_PATH;
    }

    public function getLog() {
        global $app;
        return $app->getLog();
    }

    public static function find_files($max_files = 0)
    {
        $ds = DIRECTORY_SEPARATOR;
        $allFiles = array();
        $path = self::getPath();
        foreach (scandir($path) as $file) {
            $dir = $path . $ds . $file;
            if (is_dir($dir)) {
                if (file_exists($dir . '/config.php')) {
                    $config = require($dir . '/config.php');
                    $files = array_filter(scandir($dir),
                        $config['file_filter']);

                    array_walk($files, function (&$item, $key, $dir) {
                        $item = $dir . $item;
                    }, $file . '/');

                    $allFiles = array_merge($files, $allFiles);
                }
            }
        }
        sort($allFiles, SORT_STRING);
        if ($max_files != 0) {
            $allFiles = array_slice($allFiles, 0, $max_files);
        }
        return $allFiles;
    }


    /**
     *
     * @param string $path
     * @return string $path with DIRECTORY_SEPARATOR at the end
     */
    public static function norm_path($path)
    {
        return realpath(dirname($path . '/.')) . DIRECTORY_SEPARATOR;
    }


    /**
     * Extracts the archive,
     * Transforms the channels and
     * Loads the data
     *
     * @param string $file
     * @throws Import\Exception
     * @return array|bool $data
     */
    public function import_file($file)
    {

        $data = false;


        $file_path = self::getPath() . $file;
        $file_archive = null;
        $file_bad = null;

        $cwd = getcwd();

        try {

            $this->getLog()->debug(sprintf("Import start (%s)", $file));

            // check if the file exists
            if (!file_exists($file_path)) {
                throw new Import\Exception(sprintf("File does not exist or is not readable. (%s)", $file_path));
            }

            // Setup the workspace
            $temp_path = self::norm_path($this->getConfig('workspace', sys_get_temp_dir()));
            $file_name = basename($file);

            if (file_exists($temp_path)) {
                $workspace = $temp_path . md5($file_name);

                if (file_exists($workspace) OR mkdir($workspace)) {
                    chdir($workspace);

                    // Create a copy to the workspace
                    $working_copy = self::norm_path($workspace) . $file_name;
                    copy($file_path, $working_copy);
                    $this->getLog()->debug(sprintf("Created temp directory '%s'", $workspace));
                } else {
                    throw new Import\Exception(sprintf("Can't create temp directory '%s'", $workspace));
                }
            } else {
                throw new Import\Exception(sprintf("Workspace does not exist or is not readable. (%s)", $temp_path));
            }

            $archive = $this->getConfig('archive', FALSE);

            if ($archive) {
                $archive = self::norm_path($archive);
                if (file_exists($archive)) {
                    $file_archive = $archive . $file_name;
                } else {
                    throw new Import\Exception(sprintf("Archive does not exist or is not readable. (%s)", $archive));
                }
            }

            $bad_path = self::norm_path($this->getConfig('bad_path'));

            if ($bad_path) {
                $file_bad = $bad_path . $file_name;
            } else {
                throw new Import\Exception(sprintf("Badfile path does not exist or is not readable. (%s)", $bad_path));
            }


            // setting up Schema object
            $schema_name = $this->getConfig('schema', self::$DEFAULTS['schema']);
            $schema = Import\Schema::factory($schema_name);

            $this->getLog()->debug(sprintf("Using schema %s", $schema_name));


            ////////////////////////////////////////////////////////////////////
            // ETL Data

            $schema->ch_filter = $this->getConfig('ch_filter', self::$DEFAULTS['ch_filter']);
            $schema->ch_filter_type = $this->getConfig('ch_filter_type', self::$DEFAULTS['ch_filter_type']);
            $schema->load_logs = $this->getConfig('load_logs', self::$DEFAULTS['load_logs']);
            $schema->overwrite = $this->getConfig('overwrite', self::$DEFAULTS['overwrite']);

            $data = $schema->etl($working_copy);

            $cnt = count($data);


            // Cleaning up workspace
            $this->getLog()->debug('Cleanup temp directory.');
            $objects = scandir($workspace);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    unlink($workspace . "/" . $object);
                }
            }

            $this->getLog()->debug('Deleting temp directory.');
            chdir(dirname($workspace));
            rmdir($workspace);

            // Archive
            if ($archive AND $data !== FALSE) {
                copy($file_path, $file_archive);
            }

            if (!$this->getConfig('test_load', FALSE)) {
                unlink($file_path);
            }
        } catch (Import\Exception $e) {

            // copy file to the bad-Directory
            @copy($file_path, $file_bad);

            if (!isset($cnt)) {
                $cnt = 0;
            }

            $this->getLog()->debug(sprintf("Import aborted [%s rows affected] (%s)", $cnt, $file));

            if ($this->getConfig('throw_exceptions', self::$THROW_EXCEPTIONS))
                throw new Import\Exception(sprintf("Cannot import file '%s'. Reason: %s", $file, $e->getMessage()));

            chdir($cwd);

            return FALSE;
        }

        $this->getLog()->debug(sprintf("Import end [%s rows affected] (%s)", $cnt, $file));

        chdir($cwd);

        return $data;

    }

}