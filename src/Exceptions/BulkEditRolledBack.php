<?php

declare(strict_types=1);

namespace PNScripts\BroadquillLite\Exceptions;

use RuntimeException;

/**
 * Thrown inside the transaction of an atomic bulk edit to undo every change of the run.
 */
final class BulkEditRolledBack extends RuntimeException {}
