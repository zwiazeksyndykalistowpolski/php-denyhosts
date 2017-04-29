<?php declare(strict_types=1);

namespace PhpDenyhosts\Services\Blocker;

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
     * File divided into lines
     *
     * @var array $lines
     */
    protected $lines = [];

    public function __construct(string $storageFilePath)
    {
        $this->storageFilePath = $storageFilePath;
        $this->prepareFile();
    }

    /**
     * Validates if file exists, if has a proper structure
     *
     * @throws \Exception
     */
    protected function prepareFile()
    {
        if (!is_file($this->storageFilePath)) {
            throw new \Exception('File "' . $this->storageFilePath . '" cannot be found, expected htaccess file, ');
        }

        $this->lines = file($this->storageFilePath);

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
     * Block a single IP address
     *
     * @param string $ip
     * @throws \Exception
     */
    public function blockAddress(string $ip)
    {
        $findEnd = $this->findMatch(self::TAG_END);
        $line = 'deny from ' . $ip . ' # ' . date('Y-m-d H:i:s');

        if ($findEnd === false || $findEnd === 0) {
            throw new \Exception('Corrupted structure, not ending tag found, it should not happen');
        }

        // add an IP address only in case it is not a duplicate
        if ($this->findMatch($line, true) !== false) {
            array_splice($this->lines, ($findEnd - 1), 0, $line);
        }
    }

    /**
     * Save changes
     */
    public function persist()
    {
        $contents = implode("\n", $this->lines);
        $pointer = fopen($this->storageFilePath, 'wb');
        fwrite($pointer, $contents);
        fclose($pointer);
    }
}
