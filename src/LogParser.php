<?php declare(strict_types=1);

namespace PhpDenyhosts;

class LogParser extends \Kassner\LogParser\LogParser
{
    public function __construct($format = null)
    {
        $this->patterns['%O'] = '(?P<sentBytes>[0-9\-]+)';
        parent::__construct($format);
    }
}
