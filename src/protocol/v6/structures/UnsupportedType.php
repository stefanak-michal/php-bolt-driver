<?php

namespace Bolt\protocol\v6\structures;

use Bolt\protocol\IStructure;

/**
 * Class UnsupportedType
 * Immutable
 * A placeholder value for the server to represent a value of a type not supported by the currently negotiated protocol version.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-unsupported-type
 * @package Bolt\protocol\v6\structures
 */
class UnsupportedType implements IStructure
{
    /**
     * @param string $name The name of the type that could not be transmitted (e.g, "QuantumFloat").
     * @param int $minimum_protocol_major The minimum major protocol version required to transmit this type (e.g., 42 if 42.21 is required protocol version).
     * @param int $minimum_protocol_minor The minimum minor protocol version required to transmit this type (e.g., 21 if 42.21 is required protocol version).
     * @param string[] $extra Contains additional information.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $minimum_protocol_major,
        public readonly int $minimum_protocol_minor,
        public readonly array $extra
    )
    {
    }

    public function __toString(): string
    {
        return (string)json_encode([
            'name' => $this->name,
            'minimum_protocol_major' => $this->minimum_protocol_major,
            'minimum_protocol_minor' => $this->minimum_protocol_minor,
            'extra' => $this->extra
        ]);
    }
}
