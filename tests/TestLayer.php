<?php

namespace Bolt\tests;

use Bolt\protocol\AProtocol;
use Bolt\enum\Signature;

/**
 * Class ATest
 * @package Bolt\tests
 */
abstract class TestLayer extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $user = getenv('GDB_USERNAME');
        if (!empty($user))
            $GLOBALS['NEO_USER'] = $user;
        $pwd = getenv('GDB_PASSWORD');
        if (!empty($pwd))
            $GLOBALS['NEO_PASS'] = $pwd;
        $host = getenv('GDB_HOST');
        if (!empty($host))
            $GLOBALS['NEO_HOST'] = $host;
        $port = getenv('GDB_PORT');
        if (!empty($port))
            $GLOBALS['NEO_PORT'] = $port;
    }

    /**
     * Unified way how to call init/hello/logon for tests
     * @param AProtocol $protocol
     * @param string $name
     * @param string $password
     */
    protected function sayHello(AProtocol $protocol, string $name, string $password): void
    {
        if (method_exists($protocol, 'init')) {
            $this->assertEquals(Signature::SUCCESS, $protocol->init('bolt-php', [
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password
            ])->getResponse()->signature);
        } elseif (method_exists($protocol, 'logon')) {
            $this->assertEquals(Signature::SUCCESS, $protocol->hello()->getResponse()->signature);
            $this->assertEquals(Signature::SUCCESS, $protocol->logon([
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password
            ])->getResponse()->signature);
        } else {
            $this->assertEquals(Signature::SUCCESS, $protocol->hello([
                'user_agent' => 'bolt-php',
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password,
            ])->getResponse()->signature);
        }
    }

    /**
     * Choose the right bolt version by Neo4j version
     * Neo4j version is received by HTTP request on browser port
     * @param string|null $url
     * @return float|int
     * @link https://neo4j.com/docs/http-api/current/endpoints/#discovery-api
     */
    protected function getCompatibleBoltVersion(?string $url = null): float|int
    {
        $json = file_get_contents($url ?? $GLOBALS['NEO_BROWSER'] ?? ('http://' . ($GLOBALS['NEO_HOST'] ?? 'localhost') . ':7474/'));
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            $this->markTestIncomplete('Not able to obtain Neo4j version through HTTP');

        $neo4jVersion = $decoded['neo4j_version'];

        if (version_compare($neo4jVersion, '2025.10', '>='))
            return 6;
        if (version_compare($neo4jVersion, '5.26', '>='))
            return 5.8;
        if (version_compare($neo4jVersion, '5.23', '>='))
            return 5.6;
        if (version_compare($neo4jVersion, '5.13', '>='))
            return 5.4;
        if (version_compare($neo4jVersion, '5.9', '>='))
            return 5.3;
        if (version_compare($neo4jVersion, '5.7', '>='))
            return 5.2;
        if (version_compare($neo4jVersion, '5.5', '>='))
            return 5.1;
        if (version_compare($neo4jVersion, '5.0', '>='))
            return 5;
        if (version_compare($neo4jVersion, '4.4', '>='))
            return 4.4;
        if (version_compare($neo4jVersion, '4.3', '>='))
            return 4.3;
        if (version_compare($neo4jVersion, '4.2', '>='))
            return 4.2;
        if (version_compare($neo4jVersion, '4.1', '>='))
            return 4.1;
        if (version_compare($neo4jVersion, '4', '>='))
            return 4;
        if (version_compare($neo4jVersion, '3.5', '>='))
            return 3;
        if (version_compare($neo4jVersion, '3.4', '>='))
            return 2;
        return 1;
    }
}
