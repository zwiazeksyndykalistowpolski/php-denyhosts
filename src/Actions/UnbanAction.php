<?php declare(strict_types=1);

namespace PhpDenyhosts\Services;

use Monolog\Logger;
use PhpDenyhosts\Actions\CleanUpAction;
use PhpDenyhosts\Services\Blocker\BlockerService;

class UnbanAction implements CleanUpAction
{
    /**
     * @var BlockerService $blocker
     */
    protected $blocker;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @var LogsParserService $parser
     */
    protected $parser;

    public function __construct(LogsParserService $parser, BlockerService $blocker, array $config, Logger $logger)
    {
        $this->parser  = $parser;
        $this->blocker = $blocker;
        $this->config  = $config;
        $this->logger  = $logger;
    }

    public function execute()
    {
        foreach ($this->blocker->findAllExpired() as $address) {
            $this->logger->info('Unblocking "' . $address . '" after expiration time');
            $this->blocker->unblockAddress($address);
        }

        $this->blocker->persist();
    }
}
