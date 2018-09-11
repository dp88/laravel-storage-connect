<?php

namespace STS\StorageConnect\Events;

use STS\StorageConnect\Connections\AbstractConnection;

/**
 * Class RetryingUpload
 * @package STS\StorageConnect\Events
 */
class RetryingUpload
{
    /**
     * @var AbstractConnection
     */
    public $connection;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $sourcePath;

    /**
     * @var \Exception
     */
    public $exception;

    /**
     * RetryingUpload constructor.
     *
     * @param AbstractConnection $connection
     * @param                    $message
     * @param $exception
     * @param                    $sourcePath
     */
    public function __construct( AbstractConnection $connection, $message, $exception, $sourcePath )
    {
        $this->connection = $connection;
        $this->message = $message;
        $this->sourcePath = $sourcePath;
        $this->exception = $exception;
    }
}