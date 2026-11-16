<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\Response;
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
    public function run(string $query, array $parameters = [], array $extra = []): static
    {
        $this->write($this->packer->pack(
            0x10,
            $query,
            (object) $parameters,
            (object) $extra,
        ));
        $this->pipelinedMessages[] = Message::RUN;
        return $this;
    }

    /**
     * Read RUN response
     * @return iterable
     * @throws BoltException
     */
    protected function readRun(): iterable
    {
        $content = $this->read($signature);
        if (array_key_exists('qid', $content)) {
            $this->openStreams++;
        }
        yield new Response(Message::RUN, $signature, $content);
    }
}
