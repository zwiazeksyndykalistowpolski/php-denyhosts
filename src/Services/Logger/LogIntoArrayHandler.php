<?php declare(strict_types=1);

namespace PhpDenyhosts\Services\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Collects logs into the array to return in the end
 */
class LogIntoArrayHandler extends AbstractProcessingHandler
{
    protected $log = [];

    /**
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->log;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->log[] = $record['formatted'];
    }

    public function getLogs()
    {
        return $this->log;
    }
}
