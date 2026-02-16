<?php

namespace Bolt\enum;

/**
 * Enum ServerState
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\enum
 */
enum ServerState
{
    /**
     * No socket connection has yet been established. This is the initial state and exists only in a logical sense prior to the socket being opened.
     */
    case DISCONNECTED;

    /**
     * After a new protocol connection has been established and handshake has been completed successfully, the server enters the CONNECTED state.
     * The connection has not yet been authenticated and permits only one transition, through successful initialization, into the READY state.
     */
    case CONNECTED;

    /**
     * CONNECTED state has been renamed to NEGOTIATION in Bolt v5.1
     * @see CONNECTED
     */
    case NEGOTIATION;

    /**
     * This is not strictly a connection state, but is instead a logical state that exists after a connection has been closed. When DEFUNCT, a connection is permanently not usable.
     * This may arise due to a graceful shutdown or can occur after an unrecoverable error or protocol violation.
     * Clients and servers should clear up any resources associated with a connection on entering this state, including closing any open sockets.
     * This is a terminal state on which no further transitions may be carried out. The <DISCONNECT> signal will set the connection in the DEFUNCT server state.
     */
    case DEFUNCT;

    /**
     * The READY state can handle the request messages RUN and BEGIN and receive a query.
     */
    case READY;

    /**
     * When STREAMING, a result is available for streaming from server to client.
     * This result must be fully consumed or discarded by a client before the server can re-enter the READY state and allow any further queries to be executed.
     */
    case STREAMING;

    /**
     * When transaction started
     */
    case TX_READY;

    /**
     * When TX_STREAMING, a result is available for streaming from server to client. This result must be fully consumed or discarded by a client before the server can transition to the TX_READY state.
     */
    case TX_STREAMING;

    /**
     * When FAILED, a connection is in a temporarily unusable state. This is generally as the result of encountering a recoverable error.
     * This mode ensures that only one failure can exist at a time, preventing cascading issues from batches of work.
     */
    case FAILED;

    /**
     * This state occurs between the server receiving the jump-ahead <INTERRUPT> and the queued RESET message, (the RESET message triggers an <INTERRUPT>).
     * Most incoming messages are ignored when the server are in an INTERRUPTED state, with the exception of the RESET that allows transition back to READY.
     * The <INTERRUPT> signal will set the connection in the INTERRUPTED server state.
     */
    case INTERRUPTED;

    /**
     * Connection has been established and metadata has been sent back from the HELLO message or a LOGOFF message was received whilst in ready state. Ready to accept a LOGON message with authentication information.
     */
    case AUTHENTICATION;
}
