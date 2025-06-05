<?php

namespace Ratchet;

/**
 * Interface for WebSocket connections
 */
interface ConnectionInterface {
    /**
     * Send data to the connection
     * @param  string $data
     * @return ConnectionInterface
     */
    function send($data);

    /**
     * Close the connection
     */
    function close();
} 