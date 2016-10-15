<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 15:57
 */

namespace AppBundle\Composer;

use Composer\Script\Event;

class ScriptHandler extends \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
{
    public static function initialize(Event $event)
    {
        $options = self::getOptions($event);
        $consoleDir = self::getConsoleDir($event, 'initialize');

        if (null === $consoleDir) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'app:initialize', $options['process-timeout']);
    }
}