<?php declare(strict_types=1);

namespace Tests\Blocker;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use PhpDenyhosts\Services\Blocker\ApacheBlockerService;
use Tests\TestCase;

class ApacheBlockerServiceTest extends TestCase
{
    protected function createService(): ApacheBlockerService
    {
        return new ApacheBlockerService(
            __DIR__ . '/../../example/structure/.htaccess',
            new Filesystem(
                new Local('/')
            ),
            new Logger('logger')
        );
    }

    /**
     * @see ApacheBlockerService::blockAddress()
     * @see ApacheBlockerService::isBlockedAlready()
     */
    public function testBlockAddress()
    {
        $service = $this->createService();
        $this->assertFalse($service->isBlockedAlready('8.8.8.8'));

        // block 8.8.8.8
        $service->blockAddress('8.8.8.8');
        $this->assertTrue($service->isBlockedAlready('8.8.8.8'));
    }

    /**
     * @see ApacheBlockerService::unblockAddress()
     * @see ApacheBlockerService::blockAddress()
     * @see ApacheBlockerService::isBlockedAlready()
     */
    public function testUnblockAddress()
    {
        $service = $this->createService();

        $service->blockAddress('8.8.8.8');
        $this->assertTrue($service->isBlockedAlready('8.8.8.8'));

        $service->unblockAddress('8.8.8.8');
        $this->assertFalse($service->isBlockedAlready('8.8.8.8'));
    }
}
