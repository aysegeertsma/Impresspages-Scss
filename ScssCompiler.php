<?php

namespace Plugin\Scss;

//use Ip\Internal\Design\LessCompiler as LessCompiler;
use Ip\Internal\Design\Model as Model;


/**
 * Compiles, serves and caches *.less files
 *
 * @package Plugin\Scss
 */
class ScssCompiler
{

    private static $instance = NULL;

    /**
     * @return self
     */
    public static function instance()
    {

        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @param string $themeName
     * @param string $scssFile
     * @return string
     */
    public function compileFile($themeName, $scssFile)
    {

        $model = Model::instance();

        $theme = $model->getTheme($themeName);
        $options = $theme->getOptionsAsArray();

//        $configModel = ConfigModel::instance();
//        $config = $configModel->getAllConfigValues($themeName);

        $scss = "@import '{$scssFile}';";
//        $scss .= $this->generateScssVariables($options, $config);


        $css = '';

        try {

            if(ipGetOption('Scss.includeMethod')=='relative path') {
                $leafoPath = ipGetOption('Scss.leafoPath');
                require_once ipFile($leafoPath.'/scss.inc.php');
            }

            $themeDir = ipFile('Theme/' . $themeName . '/assets/');
            $ipContentDir = ipFile('Ip/Internal/Core/assets/ipContent/');

            $compiler = new \Leafo\ScssPhp\Compiler();

            // todo: make optional
            if (ipGetOption('Scss.compressOutput')) {
                $compiler->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
            }

            $directories = array(
                $themeDir, $ipContentDir,
            );
            $compiler->setImportPaths($directories);
            $css .= "/* Edit {$scssFile}, not this file. */" . "\n";
            $css .= $compiler->compile($scss);

        } catch (\Exception $e) {
            ipLog()->error('Scss compilation error: Theme - ' . $e->getMessage());
        }

        return $css;
    }



    public function shouldRebuild($themeName)
    {
        $items = $this->globRecursive(ipFile('Theme/' . $themeName . '/') . '*.scss');
        if (!$items) {
            return FALSE;
        }

        $lastBuildTime = $this->getLastBuildTime($themeName);

        foreach ($items as $path) {
            if (filemtime($path) > $lastBuildTime) {
                return TRUE;
            }
        }

        return FALSE;
    }

    protected function getLastBuildTime($themeName)
    {
        $scssFiles = $this->getScssFiles($themeName);
        $lastBuild = 0;
        foreach ($scssFiles as $file) {
            $cssFile = substr($file, 0, -4) . 'css';
            if (!file_exists($cssFile)) {
                return 0; //we have no build or it is not completed!
            }
            $lastBuild = filemtime($cssFile);
        }

        return $lastBuild;
    }

    protected function getScssFiles($themeName)
    {
        $scssFiles = glob(ipFile('Theme/' . $themeName . '/' . \Ip\Application::ASSETS_DIR . '/') . '*.scss');
        if (!is_array($scssFiles)) {
            return array();
        }

        return $scssFiles;
    }

    /**
     * Rebuilds compiled css files.
     *
     * @param string $themeName
     */
    public function rebuild($themeName)
    {
        $scssFiles = $this->getScssFiles($themeName);
        foreach ($scssFiles as $file) {
            $scssFile = basename($file);
            $css = $this->compileFile($themeName, basename($scssFile));
            file_put_contents(
                ipFile(
                    'Theme/' . $themeName . '/' . \Ip\Application::ASSETS_DIR . '/' . substr($scssFile, 0, -4) . 'css'
                ),
                $css
            );
        }
    }

    /**
     * Recursive glob function from PHP manual (http://php.net/manual/en/function.glob.php)
     */
    protected function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        if (!is_array($files)) {
            //some systems return false instead of empty array if no matches found in glob function
            $files = array();
        }

        $dirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (!is_array($dirs)) {
            return $files;
        }
        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

}
