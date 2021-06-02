<?php
/**
 * Description of Template
 * From CodeShack.io by David Adams
 * @author Hem Roy
 */

namespace simpletemplate;

class Template
{
    /**
     * Rememmber to update the $cache_enabled and $cache_path variables, the caching is currently disabled
     * for development purposes, you can enable this when your code is production ready.
     */
    static $blocks = array();
    static $cache_path = 'cache/';
    static $cache_enabled = false;
    /**
     *
     * @param type $file
     * @param type $data
     */
    public static function view($file, $data = array())
    {
        $cached_file = self::cache($file);
        extract($data, EXTR_SKIP);
        require $cached_file;
    }
    /**
     *
     * @param type $file
     * @return string
     */
    private static function cache($file)
    {
        if (!file_exists(self::$cache_path)) {
            mkdir(self::$cache_path, 0744); //with all read write capability
        }
        $cached_file = self::$cache_path . str_replace(array('/', '.html'), array('_', ''), $file . '.php');
        if (!self::$cache_enabled || !file_exists($cached_file) || filetime($cached_file) < filemtime($file)) {
            $code = self::includefiles($file);
            $codes = self::compileCode($code);
            file_put_contents($cached_file,
                '<?php class_exists(\''. __CLASS__ .'\') or exit;?>' . PHP_EOL . $codes);
        }
        return $cached_file;
    }
    /**
     * Timely clear cache file
     */
    private static function clearcache()
    {
        foreach (glob(self::$cache_path . '*') as $file) {
            unlink($file);
        }
    }
    /**
     * Include files that starts with either extends or include
     * for example, {% extends www/layout/layout.html %}
     */
    private static function includefiles($file)
    {
        $code = file_get_contents($file);
        $matches = null;
        preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            $code = str_replace($value[0], self::includefiles($value[2]), $code);
        }
        $code = preg_replace('/{% ?(extends|include)  ?\'?(.*?)\'? ?%}/i', '', $code);
        return $code;
    }
    /**
     *
     * @param type $code
     * @return type
     */
    private static function compileCode($code)
    {
        $code1 = self::compileBlock($code);
        $code2 = self::compileYield($code1);
        $code3 = self::compileEscaped($code2);
        $code4 = self::compileEchos($code3);
        $code5 = self::compileComments($code4);
        $code5 = self::compilePHP($code5);
        return $code5;
    }
    /**
     * Compile blocks to php
     */
    private static function compilePHP($code4)
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code4);
    }
    /**
     * Compile comments for developers only from Bary
     */
    private static function compileComments($code){
        return preg_replace('~\{#\s*(.+?)\s*\#}~is','',$code);
    }

    /**
     * Compile blocks to echo on php
     */
    private static function compileEchos($code3)
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1; ?>', $code3);
    }
    /**
     * Compile blocks with escape strings to filter less important data
     */
    private static function compileEscaped($code2)
    {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo  htmlentities($1,ENT_QUOTES,\'UTF-8\'); ?>', $code2);
    }
    /**
     * yielding blocks
     */
    private static function compileYield($code1)
    {
        foreach (self::$blocks as $block => $value) {
            $code1 = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code1);
        }
        $code2 = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code1);
        return $code2;
    }
    /**
     * compile blocks into php codes
     */
    private static function compileBlock($code)
    {
        $matches = null;
        preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            if (!array_key_exists($value[1], self::$blocks)) {
                self::$blocks[$value[1]] = '';
            }
            if (strpos($value[2], '@parent') === false) {
                self::$blocks[$value[1]] = $value[2];
            } else {
                self::$blocks[$value[1]] = str_replace('@parent', self::$blocks[$value[1]], $value[2]);
            }
            $code = str_replace($value[0], '', $code);
        }
        return $code;
    }
}