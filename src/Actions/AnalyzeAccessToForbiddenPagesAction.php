<?php declare(strict_types=1);

namespace PhpDenyhosts\Actions;

use Monolog\Logger;
use PhpDenyhosts\Services\Blocker\BlockerService;
use PhpDenyhosts\Services\LogsParserService;

class AnalyzeAccessToForbiddenPagesAction implements CleanUpAction
{
    protected $blocker;
    protected $parser;
    protected $config;
    protected $logger;

    public function __construct(
        BlockerService $blocker,
        LogsParserService $parser,
        array $config,
        Logger $logger
    ) {
        $this->blocker = $blocker;
        $this->parser = $parser;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->isEnabled()) {
            $this->logger->info(get_class() . ' is disabled');
            return;
        }

        $entries = $this->parser->findAllWhere('status = 403');

        foreach ($entries as $entry)
        {
            if ($this->blocker->isBlockedAlready($entry['host'])) {
                continue;
            }

            $possibleFlood = $this->parser->findAllWhere(
                'host = :host AND stamp >= :stamp_begins AND stamp <= :stamp_ends AND status = 403',
                [
                    'host' => $entry['host'],
                    'stamp_begins' => ($entry['stamp']) - $this->getTimeInterval(),
                    'stamp_ends' => ($entry['stamp']),
                ]
            );

            if (count($possibleFlood) >= $this->getMaxAllowedRequestsPerInterval()) {
                $this->logger->info('Blocking "' . $entry['host'] . '" for ' . $this->getBanTime() . ' seconds, reason: too much 403 statuses');
                $this->blocker->blockAddress($entry['host'], $this->getBanTime());
            }
        }

        // push all changes to the file
        $this->blocker->persist();
    }

    protected function isEnabled()
    {
        return $this->config['forbiddenPages_enabled'] ?? false;
    }

    protected function getTimeInterval(): int
    {
        return $this->config['forbiddenPages_timeInterval'] ?? 300;
    }

    protected function getMaxAllowedRequestsPerInterval(): int
    {
        return $this->config['forbiddenPages_retriesToBan'] ?? 8;
    }

    protected function getBanTime(): int
    {
        return $this->config['forbiddenPages_banTime'] ?? 0;
    }
}
