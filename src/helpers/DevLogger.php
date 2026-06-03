<?php

namespace Bolt\helpers;

use Psr\Log\AbstractLogger;

/**
 * Class DevLogger
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\helpers
 */
class DevLogger extends AbstractLogger
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        echo sprintf('[%s] %s', $level, $message) . PHP_EOL;
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->printHex($message, $context['prefix'] ?? 'C: ');
    }

    protected function printHex(string $str, string $prefix = 'C: '): void
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>' . $prefix;
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>' . PHP_EOL;
    }
}
