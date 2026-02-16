<?php

namespace Bolt\helpers;

use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Bolt\enum\Signature;
use Exception;

/**
 * Class Client
 * Helper class for simplified interaction with graph database over Bolt protocol.
 * If you are in need of more complex implementation, consider using Bolt directly or building your own wrapper around it.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\helpers
 */
class Client
{
    private function __construct() {}
    
    private static $logHandler = null;
    private static $errorHandler = null;
    private static array $statistics = [];
    /**
     * Track protocol instances that have already been authenticated
     * @var \SplObjectStorage|null
     */
    private static ?\SplObjectStorage $authenticated = null;
    /**
     * Current protocol instance used for communication. Must be set via setProtocol() before any query execution.
     * @var AProtocol|null
     */
    private static ?AProtocol $protocol = null;

    /**
     * Assigned handler is called every time query is executed
     * @var callable|null (string $query, array $params, array $statistics)
     */
    public static function setLogHandler(?callable $handler = null): void
    {
        self::$logHandler = is_callable($handler) ? $handler : null;
    }

    /**
     * Provided handler is invoked on Exception instead of trigger_error
     * @var callable|null (Exception $e)
     */
    public static function setErrorHandler(?callable $handler = null): void
    {
        self::$errorHandler = is_callable($handler) ? $handler : null;
    }

    /**
     * Format failure response into exception message and throw it
      *
      * @param Response $response
      * @throws Exception
     */
    private static function failureAsException(Response $response): void
    {
        $code = '';
        foreach (($response->content ?? []) as $key => $value) {
            if (is_string($key) && stripos($key, 'code') !== false) {
                $code = (string)$value;
                break;
            }
        }

        throw new Exception(sprintf(
            '[%s] %s\r\n%s',
            $code,
            $response->content['message'] ?? '',
            $response->content['description'] ?? '',
        ));
    }

    /**
     * Set protocol instance to use for communication.
     * The protocol must be already built via Bolt::build().
     * Authentication is performed automatically on first use.
     *
     * Authentication strategy based on bolt protocol version:
     * - Bolt 1, 2: INIT (auth included)
     * - Bolt 3+: HELLO (auth included)
     * - Bolt 5.1+: HELLO (without auth) + LOGON (auth)
     *
     * @param AProtocol $protocol
     * @param array $auth Authentication parameters
     * @link https://github.com/stefanak-michal/php-bolt-driver?tab=readme-ov-file#authentication
     * @throws Exception
     */
    public static function setProtocol(AProtocol $protocol, array $auth = ['scheme' => 'none']): void
    {
        if (self::$authenticated === null) {
            self::$authenticated = new \SplObjectStorage();
        }

        self::$protocol = $protocol;

        if (self::$authenticated->offsetExists($protocol)) {
            return;
        }

        try {
            $version = $protocol->getVersion();

            if (version_compare($version, '3', '<')) {
                // Bolt 1, 2: use INIT
                $response = $protocol->init('bolt-php', $auth)->getResponse();
                if ($response->signature != Signature::SUCCESS) {
                    self::failureAsException($response);
                }
            } elseif (version_compare($version, '5.1', '<')) {
                // Bolt 3 - 5.0: use HELLO with auth
                $response = $protocol->hello($auth)->getResponse();
                if ($response->signature != Signature::SUCCESS) {
                    self::failureAsException($response);
                }
            } else {
                // Bolt 5.1+: HELLO without auth, then LOGON with auth
                $response = $protocol->hello()->getResponse();
                if ($response->signature != Signature::SUCCESS) {
                    self::failureAsException($response);
                }
                $response = $protocol->logon($auth)->getResponse();
                if ($response->signature != Signature::SUCCESS) {
                    self::failureAsException($response);
                }
            }
            self::$authenticated->offsetSet($protocol);

            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'AUTH bolt v' . $version, [], []);
            }
        } catch (Exception $e) {
            self::$protocol = null;
            self::handleException($e);
        }
    }

    /**
     * Get current protocol instance
     *
     * @return AProtocol
     * @throws Exception
     */
    public static function getProtocol(): AProtocol
    {
        if (self::$protocol === null) {
            throw new Exception('No protocol instance set. Call Client::setProtocol() first.');
        }
        return self::$protocol;
    }

    /**
     * Return full output as array
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return array
     */
    public static function query(string $query, array $params = [], array $extra = []): array
    {
        $run = $all = [];
        try {
            $protocol = self::getProtocol();

            /** @var Response $runResponse */
            $runResponse = $protocol->run($query, $params, $extra)->getResponse();
            if ($runResponse->signature != Signature::SUCCESS) {
                self::failureAsException($runResponse);
            }
            $run = $runResponse->content;

            /** @var Response $response */
            $pullMethod = version_compare($protocol->getVersion(), '4', '>=') ? 'pull' : 'pullAll';
            foreach ($protocol->{$pullMethod}()->getResponses() as $response) {
                if ($response->signature == Signature::IGNORED || $response->signature == Signature::FAILURE) {
                    self::failureAsException($response);
                }
                $all[] = $response->content;
            }
        } catch (Exception $e) {
            self::reset();
            self::handleException($e);
            return [];
        }

        $last = array_pop($all);

        self::$statistics = $last['stats'] ?? [];
        self::$statistics['rows'] = count($all);

        if (is_callable(self::$logHandler)) {
            call_user_func(self::$logHandler, $query, $params, self::$statistics);
        }

        return !empty($all) ? array_map(function (array $element) use ($run): array {
            return array_combine($run['fields'], $element);
        }, $all) : [];
    }

    /**
     * Get first value from first row
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return mixed
     */
    public static function queryFirstField(string $query, array $params = [], array $extra = []): mixed
    {
        $data = self::query($query, $params, $extra);
        if (empty($data)) {
            return null;
        }
        return reset($data[0]);
    }

    /**
     * Get first values from all rows
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return array
     */
    public static function queryFirstColumn(string $query, array $params = [], array $extra = []): array
    {
        $data = self::query($query, $params, $extra);
        if (empty($data)) {
            return [];
        }
        $key = key($data[0]);
        return array_map(function (array $element) use ($key): mixed {
            return $element[$key];
        }, $data);
    }

    /**
     * Begin transaction
     *
     * @param array $extra
     * @return bool
     */
    public static function begin(array $extra = []): bool
    {
        if (version_compare(self::getProtocol()->getVersion(), '3', '<')) {
            return false;
        }

        try {
            /** @var Response $response */
            $response = self::getProtocol()->begin($extra)->getResponse();
            if ($response->signature != Signature::SUCCESS) {
                self::failureAsException($response);
            }
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'BEGIN TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            self::reset();
            self::handleException($e);
        }
        return false;
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public static function commit(): bool
    {
        if (version_compare(self::getProtocol()->getVersion(), '3', '<')) {
            return false;
        }

        try {
            /** @var Response $response */
            $response = self::getProtocol()->commit()->getResponse();
            if ($response->signature != Signature::SUCCESS) {
                self::failureAsException($response);
            }
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'COMMIT TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            self::reset();
            self::handleException($e);
        }
        return false;
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public static function rollback(): bool
    {
        if (version_compare(self::getProtocol()->getVersion(), '3', '<')) {
            return false;
        }
        
        try {
            /** @var Response $response */
            $response = self::getProtocol()->rollback()->getResponse();
            if ($response->signature != Signature::SUCCESS) {
                self::failureAsException($response);
            }
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'ROLLBACK TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            self::reset();
            self::handleException($e);
        }
        return false;
    }

    /**
     * Return statistics info from last executed query.
     * Available keys depend on the database ecosystem (e.g. Neo4j, Memgraph).
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return self::$statistics;
    }

    /**
     * Send RESET message to the server to recover from failure state.
     */
    private static function reset(): void
    {
        try {
            if (self::$protocol !== null) {
                /** @var Response $response */
                $response = self::$protocol->reset()->getResponse();
                if ($response->signature != Signature::SUCCESS) {
                    self::failureAsException($response);
                }
            }
        } catch (Exception $e) {
            self::handleException($e);
        }
    }

    /**
     * @param Exception $e
     * @throws Exception
     */
    private static function handleException(Exception $e): void
    {
        if (is_callable(self::$errorHandler)) {
            call_user_func(self::$errorHandler, $e);
            return;
        }

        throw $e;
    }
}
