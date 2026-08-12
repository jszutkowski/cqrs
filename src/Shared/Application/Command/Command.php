<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * Marker interface: everything implementing it is routed to the asynchronous
 * command transport (see config/packages/messenger.yaml).
 */
interface Command
{
}
