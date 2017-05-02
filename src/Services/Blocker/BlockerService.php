<?php declare(strict_types=1);

namespace PhpDenyhosts\Services\Blocker;

interface BlockerService
{
    /**
     * Block a single IP address
     *
     * @param string $ip
     * @param int $time How long the address would be banned?
     *
     * @throws \Exception
     */
    public function blockAddress(string $ip, int $time = 0);

    /**
     * @param string $ip
     */
    public function unblockAddress(string $ip);

    /**
     * Save changes
     */
    public function persist();

    /**
     * Check if IP address was already blocked
     *
     * @param string $ip
     * @return bool
     */
    public function isBlockedAlready(string $ip): bool;

    /**
     * Find all addresses that were already expired
     *
     * @return array
     */
    public function findAllExpired(): array;
}
