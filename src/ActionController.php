<?php declare(strict_types=1);

namespace PhpDenyhosts;

use Monolog\Logger;
use PhpDenyhosts\Actions\AnalyzeAccessToForbiddenPagesAction;
use PhpDenyhosts\Actions\AnalyzeBlacklistedPagesFloodAction;
use PhpDenyhosts\Services\Blocker\ApacheBlockerService;
use PhpDenyhosts\Services\LogsParserService;
use PhpDenyhosts\Services\UnbanAction;

class ActionController
{
    protected $config = [];

    /**
     * @var LogsParserService $parser
     */
    protected $parser;

    /**
     * @var ApacheBlockerService $blocker
     */
    protected $blocker;

    /**
     * @var Logger $logger
     */
    protected $logger;

    public function __construct(array $configuration, Logger $logger)
    {
        $this->config  = $configuration;
        $this->parser  = new LogsParserService($configuration['accessLogPath'] ?? '', $configuration['logFormat'] ?? '');
        $this->blocker = new ApacheBlockerService($configuration['storagePath'] ?? '');
        $this->logger  = $logger;
    }

    public function cleanUpAction()
    {
        $this->parser->parseAccessLog();

        (new AnalyzeBlacklistedPagesFloodAction($this->blocker, $this->parser, $this->config, $this->logger))
            ->execute();

        (new AnalyzeAccessToForbiddenPagesAction($this->blocker, $this->parser, $this->config, $this->logger))
            ->execute();

        (new UnbanAction($this->parser, $this->blocker, $this->config, $this->logger))
            ->execute();
    }
}
