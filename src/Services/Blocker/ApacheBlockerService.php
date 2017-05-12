<?php declare(strict_types=1);

namespace PhpDenyhosts\Services\Blocker;
use League\Flysystem\FilesystemInterface;
use Monolog\Logger;

/**
 * Stores blocked IP addresses in the .htaccess file
 */
class ApacheBlockerService implements BlockerService
{
    const TAG_BEGIN = '# BEGIN php-denyhosts';
    const TAG_END   = '# END php-denyhosts';

    /**
     * Path to .htaccess file
     *
     * @var string $storageFilePath
     */
    protected $storageFilePath;

    /**
     * @var FilesystemInterface $filesystem
     */
    protected $filesystem;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * File divided into lines
     *
     * @var array $lines
     */
    protected $lines = [];

    public function __construct(string $storageFilePath, FilesystemInterface $filesystem, Logger $logger)
    {
        $this->storageFilePath = $storageFilePath;
        $this->filesystem      = $filesystem;
        $this->logger          = $logger;
        $this->prepareFile();
    }

    /**
     * Validates if file exists, if has a proper structure
     *
     * @throws \Exception
     */
    protected function prepareFile()
    {
        $this->logger->info('Reading "' . $this->storageFilePath . '"');
        $this->lines = explode("\n", $this->filesystem->read($this->storageFilePath));

        $tags = [
            $this->findMatch(self::TAG_BEGIN),
            $this->findMatch(self::TAG_END),
        ];

        if (($tags[0] === false || $tags[1] === false) && $tags[0] !== $tags[1]) {
            throw new \Exception('File "' . $this->storageFilePath . '" has corrupted structure, found opening tag without closing tag or closing tag without opening tag');
        }

        // insert section at the end of file
        if ($tags[0] === false && $tags[1] === false) {
            $this->lines[] = '';
            $this->lines[] = self::TAG_BEGIN;
            $this->lines[] = self::TAG_END;
            $this->lines[] = '';
        }
    }

    /**
     * Find a line in the file
     *
     * Note:
     *   It strips spaces and other blank characters from both strings and lowering their cases
     *
     * @param string $text
     * @param bool $ignoreComments
     *
     * @return false|int
     */
    protected function findMatch(string $text, $ignoreComments = false)
    {
        $text = strtolower(trim($text));

        foreach ($this->lines as $num => $line) {

            if ($ignoreComments === true) {
                $parts = explode('#', $line);
                $line = $parts[0];
            }

            if (strtolower(trim($line)) === $text) {
                return $num;
            }
        }

        return false;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function isBlockedAlready(string $ip): bool
    {
        return $this->findMatch('deny from ' . $ip, true) !== false;
    }

    /**
     * Block a single IP address
     *
     * @param string $ip
     * @param int $time How long the address would be banned?
     *
     * @throws \Exception
     */
    public function blockAddress(string $ip, int $time = 0)
    {
        $findEnd = $this->findMatch(self::TAG_END);
        $line = 'deny from ' . $ip . ' # ';

        $metadata = date('Y-m-d H:i:s');

        if ($time > 0) {
            $metadata .= ', ' . $time;
        }

        $line .= base64_encode($metadata);

        if ($findEnd === false || $findEnd === 0) {
            throw new \Exception('Corrupted structure, not ending tag found, it should not happen');
        }

        // add an IP address only in case it is not a duplicate
        if ($this->findMatch($line, true) === false) {
            array_splice($this->lines, $findEnd, 0, $line);
        }
    }

    /**
     * Unblock a IP address
     *
     * @param string $ip
     */
    public function unblockAddress(string $ip)
    {
        $position = $this->findMatch('deny from ' . $ip, true);

        if ($position !== false) {
            array_splice($this->lines, $position, 1);
        }
    }

    /**
     * Find all blocked entries that already expired
     *
     * @return array
     */
    public function findAllExpired(): array
    {
        $expired = [];

        foreach ($this->lines as $line) {
            if (substr(strtolower($line), 0, 10) !== 'deny from ') {
                continue;
            }

            // format (decoded):
            // deny from 1.2.3.4 # 2015-05-05, 60
            $parts = explode('#', $line);
            $commentParts = explode(', ', base64_decode($parts[1]) ?? '');
            $dateTime = strtotime($commentParts[0]);
            $expirationTime = (int) ($commentParts[1] ?? 0);

            if ($expirationTime === 0 || $dateTime === false) {
                continue;
            }

            if (time() >= ($dateTime + $expirationTime)) {
                $expired[] = trim(str_replace('deny from ', '', strtolower($parts[0])));
            }
        }

        return $expired;
    }

    /**
     * Save changes
     */
    public function persist()
    {
        if ($_SERVER['PDH_SIMULATE'] ?? null) {
            return;
        }

        $contents = implode("\n", $this->lines);
        $this->filesystem->update($this->storageFilePath, $contents);
    }
}
