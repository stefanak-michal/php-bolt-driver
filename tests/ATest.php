<?php

namespace Bolt\tests;

use Bolt\protocol\{AProtocol, Response};
use Bolt\helpers\Auth;

/**
 * Class ATest
 * @package Bolt\tests
 */
class ATest extends \PHPUnit\Framework\TestCase
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
    protected function sayHello(AProtocol $protocol, string $name, string $password)
    {
        if (method_exists($protocol, 'init')) {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->init(Auth::$defaultUserAgent, [
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password
            ])->getSignature());
        } elseif (method_exists($protocol, 'logon')) {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello()->getSignature());
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->logon([
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password
            ])->getSignature());
        } else {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(Auth::basic($name, $password))->getSignature());
        }
    }

    /**
     * Choose the right bolt version by Neo4j version
     * Neo4j version is received by HTTP request on browser port
     * @param string|null $url
     * @return float|int
     */
    protected function getCompatibleBoltVersion(string $url = null): float|int
    {
        $json = file_get_contents($url ?? $GLOBALS['NEO_BROWSER'] ?? ('http://' . ($GLOBALS['NEO_HOST'] ?? 'localhost') . ':7474/'));
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            $this->markTestIncomplete('Not able to obtain Neo4j version through HTTP');

        $neo4jVersion = $decoded['neo4j_version'];

//        if (version_compare($neo4jVersion, '5.13', '>='))
//            return 5.4;
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
