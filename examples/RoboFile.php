<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     *
     */
    public function test()
    {
        $this->taskServer(8080)->background()->dir('web')->run();
        $this->taskExec('java -jar ' . __DIR__ . '/selenium-server-standalone-2.45.0.jar')->background()->run();
        $this->taskCodecept()->suite('acceptance')->xml()->html()->run();
    }

    /**
     *
     */
    public function watchProjectUpdates()
    {
        if ($this->taskExec('ps aux |grep "php ./robo.phar [w]atch:project-updates" | wc -l |grep 1')->run()->wasSuccessful()) {
            $this->taskWatch()
                ->monitor('composer.json', function () {
                    $this->taskComposerUpdate()->run();
                    $this->rebuildAssets();
                })
                ->monitor('migrations', function() {
                    $this->taskExec(__DIR__ . '/yii migrate --interactive=0')->run();
                })
                ->monitor('config/rbac', function(){
                    $this->taskExec(__DIR__ . '/yii rbac/sync-deploy')->run();
                })
                ->monitor(['web/css', 'web/js'], function () {
                    $this->rebuildAssets();
                })->run();
        } else {
            $this->say('Same instance already running');
        }
    }

    /**
     * when composer.json changes `composer update` will be executed
     */
    public function watchComposer()
    {
        $this->taskWatch()->monitor('composer.json', function () {
            $this->taskComposerUpdate()->run();
            $this->rebuildAssets();
        })->run();
    }

    /**
     *
     */
    public function watchAssets()
    {
        $this->taskWatch()->monitor(['web/css', 'web/js', 'widgets/social/assets'], function () {
            $this->rebuildAssets();
        })->run();
    }

    /**
     *
     */
    public function rebuildAssets()
    {
        $this->taskExec(__DIR__ . '/yii asset config/assets.php config/assets-prod.php')->run();
        /*$files = glob((__DIR__ . "/web/cache/*"));
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 172800) {// 2 days
                    unlink($file);
                }
            }
        }*/
    }
}
