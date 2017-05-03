<?php declare(strict_types=1);

namespace Tests\Blocker;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use PhpDenyhosts\Services\Blocker\ApacheBlockerService;
use Tests\TestCase;

class ApacheBlockerServiceTest extends TestCase
{
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

    /**
     * @see ApacheBlockerService::findAllExpired()
     */
    public function testFindAllExpired()
    {
        $service = $this->createService();

        // assert the state is clean
        $this->assertNotContains('8.8.8.8', $service->findAllExpired());

        // block for 1 second
        $service->blockAddress('8.8.8.8', 1);

        // wait 2 seconds
        sleep(2);
        $this->assertContains('8.8.8.8', $service->findAllExpired());
    }

    /**
     * Case: Persist to filesystem
     *
     * @see ApacheBlockerService::persist()
     */
    public function testPersist()
    {
        $adapter = $this->createFakeService();
        $adapter->expects($this->once())
            ->method('update');

        $service = new ApacheBlockerService(__DIR__ . '/../../example/structure/.htaccess', $adapter, new Logger('logger'));
        $service->persist();
    }

    /**
     * Case: Do not persist to filesystem when in simulation mode
     *
     * @see ApacheBlockerService::persist()
     */
    public function testPersistFake()
    {
        $_SERVER['PDH_SIMULATE'] = true;
        $adapter = $this->createFakeService();
        $adapter->expects($this->never())
            ->method('update');

        $service = new ApacheBlockerService(__DIR__ . '/../../example/structure/.htaccess', $adapter, new Logger('logger'));
        $service->persist();

        // clean up
        unset($_SERVER['PDH_SIMULATE']);
    }

    public function brokenContentsProvider(): array
    {
        return [
            'only beginning' => [
                "# BEGIN php-denyhosts\n\n",
            ],

            'only ending' => [
                "# END php-denyhosts",
            ],
        ];
    }

    /**
     * @dataProvider brokenContentsProvider
     * @param string $brokenContents
     */
    public function testPrepareFile(string $brokenContents)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/found opening tag without closing tag or closing tag without opening tag/i');

        $adapter = $this->createMock(Filesystem::class);
        $adapter->method('read')
            ->willReturn($brokenContents);

        new ApacheBlockerService(__DIR__ . '/../../example/structure/.htaccess', $adapter, new Logger('logger'));
    }

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
     * @return \PHPUnit_Framework_MockObject_MockObject|Filesystem
     */
    protected function createFakeService()
    {
        $adapter = $this->createMock(Filesystem::class);
        $adapter->method('read')
            ->willReturn("deny from 1.2.3.4\n\n");

        return $adapter;
    }
}
