<?php

class ARSU_VA_Plugin
{
    const DIR_PUBLIC = 'public';

    /** @var string */
    private $directory;

    /** @var array */
    private $classMap = array();

    /** @var ARSU_VA_Util */
    private $util;

    public function load_module($directory, $urlToRoot)
    {
        $this->prepareClassAutoloader();

        $this->directory = $directory;

        $this->util = new ARSU_VA_Util($directory, $urlToRoot, ARSU_VA_Constants::PLUGIN_ID, self::DIR_PUBLIC);
    }

    /**
     * Initialize the class map array and prepare the autoload function
     */
    private function prepareClassAutoloader()
    {
        $this->classMap = array(
            'ARSU_VA_Plugin' => 'ARSU_VA_Plugin.php',
            'ARSU_VA_Constants' => 'ARSU_VAC_Constants.php',
            'ARSU_VA_Util' => 'ARSU_VA_Util.php',
        );

        spl_autoload_extensions('.php');
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Autoload function for the spl_autoload_register() function
     *
     * @param string $className
     */
    private function autoload($className)
    {
        if (isset($this->classMap[$className])) {
            require_once $this->directory . $this->classMap[$className];
        }
    }

    /**
     * @return ARSU_VA_Util
     */
    public function util()
    {
        return $this->util;
    }
}
