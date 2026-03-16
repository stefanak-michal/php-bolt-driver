<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 4.4
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-44
 * @package Bolt\protocol
 */
class V4_4 extends AProtocol
{
    use \Bolt\protocol\v4_3\AvailableStructures;
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

    use \Bolt\protocol\v4_4\RouteMessage;
}
