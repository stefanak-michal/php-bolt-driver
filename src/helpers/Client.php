<?php

namespace Bolt\helpers;

use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Bolt\enum\Signature;
use Exception;
use Closure;

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
    private array $statistics = [];

    /**
     * Client constructor.
     * Authentication is performed automatically based on protocol version.
     * 
     * @param AProtocol $protocol Protocol instance to use for communication. Must be already built via Bolt::build().
     * @param array $auth Authentication parameters. Default is ['scheme' => 'none'] for no authentication.
     * @param Closure|null $logHandler Optional handler for logging executed queries.
     * @param Closure|null $errorHandler Optional handler for exceptions. If not set, exceptions are thrown normally.
     */
    public function __construct(
        public readonly AProtocol $protocol, 
        private array $auth = ['scheme' => 'none'], 
        private ?Closure $logHandler = null, 
        private ?Closure $errorHandler = null
    ) {
        try {
            $version = $protocol->getVersion();

            if (version_compare($version, '3', '<')) {
                // Bolt 1, 2: use INIT
                $response = $protocol->init('bolt-php', $auth)->getResponse();
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
            } elseif (version_compare($version, '5.1', '<')) {
                // Bolt 3 - 5.0: use HELLO with auth
                $response = $protocol->hello($auth)->getResponse();
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
            } else {
                // Bolt 5.1+: HELLO without auth, then LOGON with auth
                $response = $protocol->hello()->getResponse();
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
                $response = $protocol->logon($auth)->getResponse();
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
            }

            if (is_callable($this->logHandler)) {
                call_user_func($this->logHandler, 'AUTH bolt v' . $version, [], []);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Query the database and return full output as array
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return array
     */
    public function query(string $query, array $params = [], array $extra = []): array
    {
        $run = $all = [];
        try {
            /** @var Response $runResponse */
            $runResponse = $this->protocol->run($query, $params, $extra)->getResponse();
            if ($runResponse->signature === Signature::FAILURE) {
                $this->failureAsException($runResponse);
            }
            $run = $runResponse->content;

            /** @var Response $response */
            $pullMethod = version_compare($this->protocol->getVersion(), '4', '>=') ? 'pull' : 'pullAll';
            foreach ($this->protocol->{$pullMethod}()->getResponses() as $response) {
                if ($response->signature === Signature::IGNORED) {
                    continue;
                }
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
                $all[] = $response->content;
            }
        } catch (Exception $e) {
            $this->reset();
            $this->handleException($e);
            return [];
        }

        $last = array_pop($all);

        $this->statistics = $last['stats'] ?? [];
        $this->statistics['rows'] = count($all);

        if (is_callable($this->logHandler)) {
            call_user_func($this->logHandler, $query, $params, $this->statistics);
        }

        return !empty($all) ? array_map(function (array $element) use ($run): array {
            return array_combine($run['fields'], $element);
        }, $all) : [];
    }

    /**
     * Query the database and get first value from first row
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return mixed
     */
    public function queryFirstField(string $query, array $params = [], array $extra = []): mixed
    {
        $data = $this->query($query, $params, $extra);
        if (empty($data)) {
            return null;
        }
        return reset($data[0]);
    }

    /**
     * Query the database and get first values from all rows
     *
     * @param string $query
     * @param array $params
     * @param array $extra
     * @return array
     */
    public function queryFirstColumn(string $query, array $params = [], array $extra = []): array
    {
        $data = $this->query($query, $params, $extra);
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
    public function begin(array $extra = []): bool
    {
        try {
            if (version_compare($this->protocol->getVersion(), '3', '<')) {
                return false;
            }

            /** @var Response $response */
            $response = $this->protocol->begin($extra)->getResponse();
            if ($response->signature === Signature::FAILURE) {
                $this->failureAsException($response);
            }
            if (is_callable($this->logHandler)) {
                call_user_func($this->logHandler, 'BEGIN TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            $this->reset();
            $this->handleException($e);
        }
        return false;
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        try {
            if (version_compare($this->protocol->getVersion(), '3', '<')) {
                return false;
            }

            /** @var Response $response */
            $response = $this->protocol->commit()->getResponse();
            if ($response->signature === Signature::FAILURE) {
                $this->failureAsException($response);
            }
            if (is_callable($this->logHandler)) {
                call_user_func($this->logHandler, 'COMMIT TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            $this->reset();
            $this->handleException($e);
        }
        return false;
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        try {
            if (version_compare($this->protocol->getVersion(), '3', '<')) {
                return false;
            }

            /** @var Response $response */
            $response = $this->protocol->rollback()->getResponse();
            if ($response->signature === Signature::FAILURE) {
                $this->failureAsException($response);
            }
            if (is_callable($this->logHandler)) {
                call_user_func($this->logHandler, 'ROLLBACK TRANSACTION', [], []);
            }
            return true;
        } catch (Exception $e) {
            $this->reset();
            $this->handleException($e);
        }
        return false;
    }

    /**
     * Return statistics info from last executed query.
     * Available keys depend on the database ecosystem (e.g. Neo4j, Memgraph).
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Send RESET message to the server to recover from failure state.
     */
    private function reset(): void
    {
        try {
            if ($this->protocol !== null) {
                /** @var Response $response */
                $response = $this->protocol->reset()->getResponse();
                if ($response->signature === Signature::FAILURE) {
                    $this->failureAsException($response);
                }
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Format failure response into exception message and throw it
     *
     * @param Response $response
     * @throws Exception
     */
    private function failureAsException(Response $response): void
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
     * @param Exception $e
     * @throws Exception
     */
    private function handleException(Exception $e): void
    {
        if (is_callable($this->errorHandler)) {
            call_user_func($this->errorHandler, $e);
            return;
        }

        throw $e;
    }
}
