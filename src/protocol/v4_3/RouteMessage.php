<?php

namespace Bolt\protocol\v4_3;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait RouteMessage
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-route
     * @throws BoltException
     */
    public function route(array $routing, array $bookmarks = [], ?string $db = null): static
    {
        $this->write($this->packer->pack(0x66, (object) $routing, $bookmarks, $db));
        $this->pipelinedMessages[] = Message::ROUTE;
        return $this;
    }
}
