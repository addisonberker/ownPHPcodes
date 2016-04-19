<?php
/**
 * Created by PhpStorm.
 * User: Addison Berker
 * Date: 27.01.2015
 * Time: 20:00
 */

namespace Engine\MVC;

class Request
{
    private static $currentFileName = null;
    /**
     * check method
     * @param string $key
     * @return boolean
     * */
    public static function check($key)
    {
        return isset($_SERVER["REQUEST_METHOD"])
            ? (($_SERVER["REQUEST_METHOD"] == strtoupper($key) ? true : false))
            : false;
    }
    /**
     * post method
     * @param string
     * @return null|array
     * */
    public static function post($key = null)
    {
        if(!is_null($key))
        {
            return isset($_POST[$key]) ? self::sanitize($_POST[$key]) : null;
        }
        else
        {
            $data = array();
            foreach($_POST as $item => $value)
            {
                $data[$item] = self::sanitize($value);
            }
            return $data;
        }
        return null;
    }
    /**
     * get method
     * @param string $key
     * @return null|array
     * */
    public static function get($key = null)
    {
        if(!is_null($key))
        {
            return isset($_GET[$key]) ? self::sanitize($_GET[$key]) : null;
        }
        else
        {
            $data = array();
            foreach($_GET as $item => $value){
                $data[$item] = self::sanitize($value);
            }
            return $data;
        }
        return null;
    }
    /**
     * get method
     * @param string $key
     * @return null|array
     * */
    public static function file($key = null)
    {
        if(!is_null($key))
        {
            return isset($_FILES[$key]) ? self::sanitize($_FILES[$key]) : null;
        }
        else
        {
            return $_FILES;
        }
        return null;
    }
    /**
     * req method
     * @param string $key
     * @return null|array
     * */
    public static function req($key = null)
    {
        if(!is_null($key))
        {
            return isset($_REQUEST[$key]) ? self::sanitize($_REQUEST[$key]) : null;
        }
        else
        {
            $data = array();
            foreach($_REQUEST as $item => $value){
                $data[$item] = self::sanitize($value);
            }
            return $data;
        }
        return null;
    }
    /**
     * method
     * @return string/null
     * */
    public static function method()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            ? (isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD'])."Ajax" : null)
            : (isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null);
    }
    /**
     * getCurrentFileName method
     * @return string
     * */
    public static function getCurrentFileName()
    {
        $name = array_keys($_FILES);
        if(sizeof($name) == 1)
            self::$currentFileName = $name[0];

        return self::$currentFileName;
    }
    /**
     * cleanInput method
     * @param string $input
     * @return string
     * */
    private static function cleanInput($input)
    {

        $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
        );

        $output = preg_replace($search, '', $input);
        return $output;
    }
    /**
     * sanitize method
     * @param mixed $input
     * @return string
     * */
    private static function sanitize($input)
    {
        $input  = self::cleanInput($input);
        return $input;
    }
}