<?php

namespace Core;

use Yaf\Registry;

class Translator
{

    private static $instances = [];

    /**
     *
     * @param string $locale
     * @return Translator
     */
    public static function getInstance($locale)
    {
        if (empty(self::$instances[$locale])) {
            self::$instances[$locale] = new Translator($locale);
        }

        return self::$instances[$locale];
    }

    public static function t($tpl, array $params = [])
    {
        $locale = Registry::get('conf')->defaults->locale;
        return Translator::getInstance($locale)->translate($tpl, $params);
    }

    private $package;

    public function __construct($locale)
    {
        $this->package = require APP_PATH . '/locale/' . $locale . '.php';
    }

    public function translate($input, array $params = [])
    {
        if (isset($this->package[$input])) {
            $info = $this->package[$input];
            if (is_array($info)) {
                $tpl = $info['tpl'];
                if (!empty($info['vars'])) {
                    $params = array_merge($info['vars'], $params);
                }
            } else {
                $tpl = $info;
            }
        } else {
            $tpl = $input;
        }

        if ($params) {
            $replace = [];
            $search = [];
            foreach ($params as $key => $val) {
                $search[] = '{{' . $key . '}}';
                $replace['{{' . $key . '}}'] = $val;
            }

            return str_replace($search, $replace, $tpl);
        }

        return $tpl;
    }
}
