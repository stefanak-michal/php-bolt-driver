<?php

namespace Bolt\protocol\v4;

use Bolt\enum\Message;
use Bolt\enum\Signature;
use Bolt\protocol\Response;
use Bolt\error\BoltException;

trait DiscardMessage
{
    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param array $extra [n::Integer, qid::Integer]
     * @throws BoltException
     */
    public function discard(array $extra = []): static
    {
        if (!array_key_exists('n', $extra)) {
            $extra['n'] = -1;
        }
        $this->write($this->packer->pack(0x2F, $extra));
        $this->pipelinedMessages[] = Message::DISCARD;
        return $this;
    }

    /**
     * Read DISCARD response
     * @return iterable
     * @throws BoltException
     */
    protected function readDiscard(): iterable
    {
        $content = $this->read($signature);
        if (!($content['has_more'] ?? false) && $this->openStreams) {
            $this->openStreams = $signature === Signature::SUCCESS ? $this->openStreams - 1 : 0;
        }
        yield new Response(Message::DISCARD, $signature, $content);
    }
}
