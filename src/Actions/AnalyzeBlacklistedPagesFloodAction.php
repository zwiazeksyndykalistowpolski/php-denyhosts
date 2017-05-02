<?php declare(strict_types=1);

namespace PhpDenyhosts\Actions;

use Monolog\Logger;
use PhpDenyhosts\Services\Blocker\BlockerService;
use PhpDenyhosts\Services\LogsParserService;

class AnalyzeBlacklistedPagesFloodAction implements CleanUpAction
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

        foreach ($this->parser->findAll() as $entry) {
            if (!$this->matchesBlacklistedPath($entry)
                || $this->blocker->isBlockedAlready($entry['host'])) {
                continue;
            }

            $possibleFlood = $this->parser->findAllWhere(
                'host = :host AND stamp >= :stamp_begins AND stamp <= :stamp_ends',
                [
                    'host' => $entry['host'],
                    'stamp_begins' => ($entry['stamp']) - $this->getTimeInterval(),
                    'stamp_ends'  => ($entry['stamp']),
                ]
            );

            $rThis = $this;
            $possibleFlood = array_filter($possibleFlood, function ($possibleFloodEntry) use ($rThis) {
                return $rThis->matchesBlacklistedPath($possibleFloodEntry);
            });

            if (count($possibleFlood) >= $this->getMaxAllowedRequestsPerInterval()) {
                $this->logger->info('Blocking "' . $entry['host'] . '" for ' . $this->getBanTime() . ' seconds, reason: possible brute force on blacklisted pages');
                $this->blocker->blockAddress($entry['host'], $this->getBanTime());
            }
        }

        // push all changes to the file
        $this->blocker->persist();
    }

    protected function matchesBlacklistedPath(array $entry): bool
    {
        foreach ($this->getBlacklistedPaths() as $pattern) {
            if (preg_match($pattern, $entry['request'] ?? '')) {
                return true;
            }
        }

        return false;
    }

    protected function isEnabled()
    {
        return $this->config['blacklist_enabled'] ?? false;
    }

    protected function getTimeInterval(): int
    {
        return $this->config['blacklist_timeInterval'] ?? 300;
    }

    protected function getMaxAllowedRequestsPerInterval(): int
    {
        return $this->config['blacklist_retriesToBan'] ?? 8;
    }

    protected function getBlacklistedPaths(): array
    {
        return $this->config['blacklist_paths'] ?? [];
    }

    protected function getBanTime(): int
    {
        return $this->config['blacklist_banTime'] ?? 0;
    }
}
