<?php

namespace Ralog\Import;

/**
 * Description of Schema
 *
 * @author Martin Zoellner <ragchuck at gmail.com>
 */
abstract class Schema
{

    /**
     *
     * @var bool if logs should be loaded
     */
    public $load_logs = FALSE;

    /**
     *
     * @var bool if data should be updated, if already exists
     */
    public $overwrite = TRUE;

    /**
     *
     * @var string
     */
    public $ch_filter_type = 'black';

    /**
     *
     * @var array of channel-keys
     */
    public $ch_filter = array();

    /**
     * @var array of channels to extract
     */
    public $extract = array();

    /**
     * Extracts the channels from the SMA archives transforms the data from
     * the channels and loads the data into the database. (ETL)
     * Returns an array of the loaded data elements.
     *
     * @param string $filename
     * @return array
     */
    abstract public function etl($filename);

    /**
     *
     * @param string $schema_name
     * @return Schema
     * @throws Exception
     */
    final public static function factory($schema_name)
    {
        $schema_name = ucfirst($schema_name);
        $schema_class = 'Ralog\\Import\\Schema\\' . $schema_name;
        $schema = new $schema_class;

        if (!($schema instanceof Schema)) {
            throw new Exception(sprintf("%s isn't a valid import schema.", $schema_name));
        }

        return $schema;
    }

    /**
     * "Should-I-Load-This-Channel?"
     * Checks the specified channel-key, whether it should be loaded or not
     *
     * @param string $ch_key channel-key
     * @return bool
     */
    public function siltc($ch_key)
    {

        if ($this->ch_filter_type === 'none') {
            return TRUE;
        }

        $bool = ($this->ch_filter_type === 'white');
        $found = false;

        foreach ($this->ch_filter as $cf) {
            if (strstr($ch_key, $cf) !== FALSE) {
                $found = true;
                break;
            }
        }

        return (($bool AND $found) OR (!$bool AND !$found));
    }
}