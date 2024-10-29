<?php

namespace Asiabill\Classes;


class AsiabillLogger
{
    protected $_dir;
    protected $_file;

    function __construct($dir, $file)
    {
        $this->_dir = $dir;
        $this->_file = $file;
    }

    function addLog($message)
    {
        $file = self::openFile();
        if ($file) {
            fwrite($file, date('Y-m-d H:i:s') . ' - ' . print_r($message, true) . "\n");
            fclose($file);
        }
    }

    protected function openFile()
    {
        $dir = is_dir($this->_dir) || mkdir($this->_dir, 0777, true);
        if ($dir) {
            return fopen($this->_dir . DIRECTORY_SEPARATOR . $this->_file, 'a');
        }
        return false;
    }


}
