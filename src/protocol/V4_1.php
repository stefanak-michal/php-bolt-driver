<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-41
 * @package Bolt\protocol
 */
class V4_1 extends AProtocol
{
    use \Bolt\protocol\v1\AvailableStructures;
    use \Bolt\protocol\v4\ServerStateTransition;

    use \Bolt\protocol\v1\ResetMessage;

    use \Bolt\protocol\v3\RunMessage;
    use \Bolt\protocol\v3\BeginMessage;
    use \Bolt\protocol\v3\CommitMessage;
    use \Bolt\protocol\v3\RollbackMessage;
    use \Bolt\protocol\v3\GoodbyeMessage;

    use \Bolt\protocol\v4\PullMessage;
    use \Bolt\protocol\v4\DiscardMessage;

    use \Bolt\protocol\v4_1\HelloMessage;
}
