<?php

namespace Bolt\protocol\v3;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\protocol\{Response, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait RunMessage
{
    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @throws BoltException
     */
    public function run(string $query, array $parameters = [], array $extra = []): V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(
            0x10,
            $query,
            (object)$parameters,
            (object)$extra
        ));

        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_STREAMING : ServerState::STREAMING);
        return $this;
    }

    /**
     * Read RUN response
     * @throws BoltException
     */
    protected function _run(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_STREAMING : ServerState::STREAMING);
        }

        yield new Response(Message::RUN, $signature, $content);
    }
}
