<?php declare(strict_types=1);

namespace PhpDenyhosts\Services;

use Kassner\LogParser\FormatException;
use League\Flysystem\FilesystemInterface;
use Monolog\Logger;
use PhpDenyhosts\LogParser;

/**
 * Parses logs into a readable set of metadata
 */
class LogsParserService
{
    /**
     * @var LogParser $parser
     */
    protected $parser;

    /**
     * @var \PDO $pdo
     */
    protected $pdo;

    /**
     * @var FilesystemInterface $filesystem
     */
    protected $filesystem;

    /**
     * @var string $accessLogPath
     */
    protected $accessLogPath;

    /**
     * @var Logger $logger
     */
    protected $logger;

    public function __construct(
        string $accessLogPath,
        string $logFormat,
        FilesystemInterface $filesystem,
        Logger $logger
    ) {
        $this->parser = new LogParser($logFormat);
        $this->pdo    = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->accessLogPath = $accessLogPath;
        $this->filesystem    = $filesystem;
        $this->logger        = $logger;

        $this->prepareStructure();
    }

    /**
     * Parse the access log file into arrays
     *
     * @throws FormatException
     * @return array
     */
    public function parseAccessLog()
    {
        $this->logger->info('Reading "' . $this->accessLogPath . '"');
        $raw = $this->filesystem->read($this->accessLogPath);

        // handle gzipped files
        if (substr(strtolower($this->accessLogPath), -3) === '.gz') {
            $this->logger->info('Got a gzipped log file, uncompressing');
            $raw = gzdecode($raw);
        }

        $lines = explode("\n", $raw);
        $lines = array_filter($lines);
        $this->logger->info('Got ' . count($lines) . ' lines to parse');

        foreach ($lines as $line) {
            try {
                $log = (array)$this->parser->parse($line);
                $this->insertLog($log);

            } catch (FormatException $e) {
                // nothing
            }
        }
    }

    /**
     * Find all entries by where clause (SQL where clause)
     *
     * Available columns to query:
     *     - int stamp
     *     - datetime time
     *     - string request
     *     - int status
     *     - string login
     *
     * @param string $queryString
     * @param array $values
     * @return array
     */
    public function findAllWhere(string $queryString, array $values = [])
    {
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE ' . $queryString);

        foreach ($values as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find all log entries
     *
     * @return array
     */
    public function findAll()
    {
        $stmt = $this->pdo->query('SELECT * FROM logs');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function insertLog(array $log): bool
    {
        $stmt = $this->pdo->query('INSERT INTO logs (host, stamp, time, request, status, login) VALUES (:host, :stamp, :time, :request, :status, :login);');
        $result = $stmt->execute([
            'host'    => $log['host'],
            'stamp'   => $log['stamp'] ?? 0,
            'time'    => date('Y-m-d H:i:s', strtotime($log['time'])),
            'request' => $log['request'] ?? '',
            'status'  => $log['status'] ?? 200,
            'login'   => $log['login'] ?? '',
        ]);

        return $result;
    }

    protected function prepareStructure()
    {
        $this->pdo->exec('CREATE TABLE logs (
              id integer primary key,
              host text,
              stamp integer,
              time datetime,
              request text,
              status integer,
              login text
        );');
    }
}
