<?php

namespace Bolt\protocol\v3;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\protocol\Response;
use Bolt\error\BoltException;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropriate array
     * @throws BoltException
     */
    public function hello(array $extra): Response
    {
        $this->serverState->is(ServerState::CONNECTED);

        $this->write($this->packer->pack(0x01, $extra));
        $content = $this->read($signature);

        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Signature::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        return new Response(Message::HELLO, $signature, $content);
    }
}
