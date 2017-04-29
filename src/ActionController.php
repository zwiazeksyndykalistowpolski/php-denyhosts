<?php declare(strict_types=1);

namespace PhpDenyhosts;

use PhpDenyhosts\Services\Blocker\ApacheBlockerService;
use PhpDenyhosts\Services\LogsParserService;

class ActionController
{
    protected $config = [];

    /** @var LogsParserService $parser */
    protected $parser;

    protected $blocker;

    public function __construct(array $configuration)
    {
        $this->config = $configuration;
        $this->parser = new LogsParserService($configuration['accessLogPath'] ?? '', $configuration['logFormat'] ?? '');
        $this->blocker = new ApacheBlockerService($configuration['storagePath'] ?? '');
    }

    public function cleanUpAction()
    {
        $this->parser->parseAccessLog();
        $this->analyzeSamePageFlood();
    }

    public function analyzeSamePageFlood()
    {
        foreach ($this->parser->findAll() as $entry) {
            $flood = $this->parser->findAllWhere(
                'host = :host AND request = :request AND stamp >= :stamp_begins AND stamp <= :stamp_ends',
                [
                    'host' => $entry['host'],
                    'request' => $entry['request'],
                    'stamp_begins' => ($entry['stamp']) - $this->getTimeInterval(),
                    'stamp_ends'  => ($entry['stamp']),
                ]
            );
        }
    }

    private function getTimeInterval()
    {
        return $this->config['timeInterval'] ?? 300;
    }
}
