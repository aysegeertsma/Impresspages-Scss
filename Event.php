<?php

namespace Plugin\Scss;

class Event
{
    public static function ipBeforeController()
    {

        $scssCompiler = ScssCompiler::instance();
        if (ipConfig()->isDevelopmentEnvironment()) {
            if ($scssCompiler->shouldRebuild(ipConfig()->theme())) {
                $scssCompiler->rebuild(ipConfig()->theme());
            }
        }

    }
}
