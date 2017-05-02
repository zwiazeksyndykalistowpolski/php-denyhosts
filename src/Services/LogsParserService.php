<?php declare(strict_types=1);

namespace PhpDenyhosts\Services;

use Kassner\LogParser\FormatException;
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
     * @var string $accessLogPath
     */
    protected $accessLogPath;

    public function __construct(string $accessLogPath, string $logFormat)
    {
        $this->parser = new LogParser($logFormat);
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->accessLogPath = $accessLogPath;

        if (!is_file($this->accessLogPath)) {
            throw new \Exception('Cannot find access log under "' . $this->accessLogPath . '"');
        }

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
        $lines = file($this->accessLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

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
