<?php

namespace Bolt;

use Bolt\error\ConnectException;
use Bolt\error\BoltException;
use Bolt\protocol\AProtocol;
use Bolt\enum\{Signature, ServerState};
use Bolt\connection\IConnection;
use Bolt\helpers\CacheProvider;

/**
 * Main class Bolt
 * Bolt protocol library using TCP socket connection
 * Acts as factory which provides protocol class by requested version
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt
 */
final class Bolt
{
    private array $protocolVersions = [];
    private int $packStreamVersion = 1;

    public static bool $debug = false;

    public function __construct(private IConnection $connection)
    {
        if (!getenv('BOLT_ANALYTICS_OPTOUT') && is_writable(sys_get_temp_dir() . DIRECTORY_SEPARATOR)) {
            if (!file_exists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-analytics' . DIRECTORY_SEPARATOR)) {
                mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-analytics', recursive: true);
            }
            $this->track();
        }

        $this->setProtocolVersions('5.8.8', '4.4.4');
    }

    private function track(): void
    {
        $data = (array)CacheProvider::get()->get('analytics', []);
        if (empty($data))  {
            return;
        }

        $distinctId = sha1(implode('', [php_uname(), disk_total_space('.'), filectime('/'), phpversion()]));

        $toSend = [];
        foreach ($data as $time => $counts) {
            if ($time < strtotime('today')) {
                $toSend[] = [
                    'properties' => [
                        '$insert_id' => (string) $time,
                        'distinct_id' => $distinctId,
                        'amount' => $counts['queries'] ?? 0,
                        'time' => $time
                    ],
                    'event' => 'queries'
                ];
                $toSend[] = [
                    'properties' => [
                        '$insert_id' => (string) $time,
                        'distinct_id' => $distinctId,
                        'amount' => $counts['sessions'] ?? 0,
                        'time' => $time
                    ],
                    'event' => 'sessions'
                ];
            }
        }

        if (empty($toSend)) {
            return;
        }

        //curl
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api-eu.mixpanel.com/import?strict=0&project_id=3355308',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->connection->getTimeout(),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($toSend),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'accept: application/json',
                'authorization: Basic MDJhYjRiOWE2YTM4MThmNWFlZDEzYjNiMmE5M2MxNzQ6',
            ],
        ]);

        if (curl_exec($curl) !== false) {
            curl_close($curl);
            
            // clean sent analytics data
            if (method_exists(CacheProvider::get(), 'lock')) {
                CacheProvider::get()->lock('analytics');
            }
            $data = (array)CacheProvider::get()->get('analytics');
            foreach ($data as $time => $counts) {
                if ($time < strtotime('today')) {
                    unset($data[$time]);
                }
            }
            CacheProvider::get()->set('analytics', $data);
            if (method_exists(CacheProvider::get(), 'unlock')) {
                CacheProvider::get()->unlock('analytics');
            }
        }
    }

    /**
     * Connect via Connection, execute handshake on it, create and return protocol version class
     * @throws BoltException
     */
    public function build(): AProtocol
    {
        $protocol = null;

        try {
            if (!$this->connection->connect()) {
                throw new ConnectException('Connection failed');
            }

            $protocol = $this->persistentBuild();
            if (empty($protocol)) {
                $protocol = $this->normalBuild();
            }
        } catch (BoltException $e) {
            $this->connection->disconnect();
            throw $e;
        }

        if ($this->connection instanceof \Bolt\connection\PStreamSocket) {
            CacheProvider::get()->set($this->connection->getIdentifier(), $protocol->getVersion(), strtotime('+15 minutes'));
        }

        return $protocol;
    }

    private function normalBuild(): AProtocol
    {
        $version = $this->handshake();

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
        if (!class_exists($protocolClass)) {
            throw new ConnectException('Requested Protocol version (' . $version . ') not yet implemented');
        }

        $protocol = new $protocolClass($this->packStreamVersion, $this->connection);
        $protocol->serverState = version_compare($version, '5.1', '>=') ? ServerState::NEGOTIATION : ServerState::CONNECTED;
        return $protocol;
    }

    private function persistentBuild(): ?AProtocol
    {
        if ($this->connection instanceof \Bolt\connection\PStreamSocket) {
            $version = CacheProvider::get()->get($this->connection->getIdentifier());
            if (empty($version)) {
                return null;
            }

            $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
            if (!class_exists($protocolClass)) {
                throw new ConnectException('Requested Protocol version (' . $version . ') not yet implemented');
            }

            /** @var AProtocol $protocol */
            $protocol = new $protocolClass($this->packStreamVersion, $this->connection);
            $protocol->serverState = ServerState::INTERRUPTED;

            if ($protocol->reset()->getResponse()->signature != Signature::SUCCESS) {
                $this->connection->disconnect();
                $this->connection->connect();
                return null;
            }

            return $protocol;
        }
        return null;
    }

    public function setProtocolVersions(int|float|string ...$v): Bolt
    {
        $this->protocolVersions = array_slice($v, 0, 4);
        while (count($this->protocolVersions) < 4)
            $this->protocolVersions[] = 0;
        return $this;
    }

    public function setPackStreamVersion(int $version = 1): Bolt
    {
        $this->packStreamVersion = $version;
        return $this;
    }

    /**
     * @link https://www.neo4j.com/docs/bolt/current/bolt/handshake/
     * @throws BoltException
     */
    private function handshake(): string
    {
        if (self::$debug)
            echo 'HANDSHAKE';

        $this->connection->write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17) . $this->packProtocolVersions());

        $bytes = $this->connection->read(4);
        if ($bytes == 'HTTP')
            throw new ConnectException('Cannot to connect to Bolt service on ' . $this->connection->getIp() . ':' . $this->connection->getPort() . ' (looks like HTTP)');
        $version = $this->unpackProtocolVersion($bytes);
        if (empty($version))
            throw new ConnectException('Wrong version');

        return $version;
    }

    /**
     * Read and compose selected protocol version
     */
    private function unpackProtocolVersion(string $bytes): string
    {
        $result = [];

        foreach (mb_str_split($bytes, 1, '8bit') as $ch) {
            $result[] = unpack('C', $ch)[1] ?? 0;
        }

        while (count($result) > 0 && reset($result) == 0) {
            array_shift($result);
        }

        return implode('.', array_reverse($result));
    }

    /**
     * Pack requested protocol versions
     */
    private function packProtocolVersions(): string
    {
        $versions = [];

        foreach ($this->protocolVersions as $v) {
            if (is_int($v))
                $versions[] = pack('N', $v);
            else {
                $splitted = explode('.', (string) $v);
                $splitted = array_reverse($splitted);
                while (count($splitted) < 4)
                    array_unshift($splitted, 0);
                foreach ($splitted as $s)
                    $versions[] = pack('C', $s);
            }
        }

        return implode('', $versions);
    }
}
