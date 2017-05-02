<?php declare(strict_types=1);

namespace PhpDenyhosts;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
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
     * @var FilesystemInterface[] $filesystem
     */
    protected $filesystem = [];

    /**
     * @var Logger $logger
     */
    protected $logger;

    public function __construct(array $configuration, Logger $logger)
    {
        $this->config  = $configuration;
        $this->logger  = $logger;

        $this->parser  = new LogsParserService(
            $configuration['accessLogPath'] ?? '',
            $configuration['logFormat'] ?? '',
            $this->getFilesystem('parser'),
            $logger
        );

        $this->blocker = new ApacheBlockerService(
            $configuration['storagePath'] ?? '',
            $this->getFilesystem('blocker'),
            $logger
        );
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

    /**
     * @param string $type Values: blocker, parser
     * @return FilesystemInterface
     */
    protected function getFilesystem(string $type): FilesystemInterface
    {
        if (!isset($this->filesystem[$type]) || !$this->filesystem[$type] instanceof FilesystemInterface) {
            if ($this->config['filesystem_' . $type] ?? false) {

                $this->logger->info('Setting up filesystem for "' . $type . '"');
                $this->filesystem[$type] = $this->config['filesystem_' . $type]();
                $this->logger->info('Done setting up the filesystem');

                return $this->filesystem[$type];
            }

            $this->filesystem[$type] = new Filesystem(
                new Local($this->config['filesystem_' . $type . '_root'] ?? '/')
            );
        }

        return $this->filesystem[$type];
    }
}
