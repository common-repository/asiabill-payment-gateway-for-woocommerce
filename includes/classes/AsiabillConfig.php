<?php

namespace Asiabill\Classes;

class AsiabillConfig
{

    private $_file = __DIR__ . '/../config/config.php';
    private $_data = [];

    public function load()
    {
        $this->_data = include $this->_file;
    }

    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param string|null $name 配置参数名（支持多级配置 .号分割）
     * @return string | array | null
     */
    public function get(string $name = null)
    {
        // 无参数时获取所有
        if (empty($name)) {
            return $this->_data;
        }

        if (false === strpos($name, '.')) {
            return $this->_data[$name] ?? [];
        }

        $name = explode('.', $name);;
        $config = $this->_data;

        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            }
            else {
                return null;
            }
        }

        return $config;
    }

    /**
     * 设置配置参数 name为数组则为批量设置
     * @access public
     * @param string $name 配置名
     * @param $config //配置参数
     */
    public function set(string $name, $config)
    {
        $arr = [];
        if (!empty($name)) {
            $parts = explode('.', $name);
            $lastIndex = count($parts) - 1;
            $currentArray = &$arr;
            foreach ($parts as $index => $part) {
                if ($index === $lastIndex) {
                    $currentArray[$part] = $config;
                } else {
                    $currentArray[$part] = [];
                    $currentArray = &$currentArray[$part];
                }
            }
        }
        elseif(is_array($config)) {
            $arr = $config;
        }

        $this->_data =  self::arrayMergeRecursiveDistinct($this->_data,$arr);

    }

    private static function arrayMergeRecursiveDistinct(array $array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }


}