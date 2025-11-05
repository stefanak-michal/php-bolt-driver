<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 6
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/
 * @package Bolt\protocol
 */
class V6 extends AProtocol
{
    use \Bolt\protocol\v6\AvailableStructures;
    use \Bolt\protocol\v5_1\ServerStateTransition;

    use \Bolt\protocol\v1\ResetMessage;

    use \Bolt\protocol\v3\RunMessage;
    use \Bolt\protocol\v3\BeginMessage;
    use \Bolt\protocol\v3\CommitMessage;
    use \Bolt\protocol\v3\RollbackMessage;
    use \Bolt\protocol\v3\GoodbyeMessage;

    use \Bolt\protocol\v4\PullMessage;
    use \Bolt\protocol\v4\DiscardMessage;

    use \Bolt\protocol\v4_4\RouteMessage;

    use \Bolt\protocol\v5_1\LogonMessage;
    use \Bolt\protocol\v5_1\LogoffMessage;

    use \Bolt\protocol\v5_3\HelloMessage;

    use \Bolt\protocol\v5_4\TelemetryMessage;
}
