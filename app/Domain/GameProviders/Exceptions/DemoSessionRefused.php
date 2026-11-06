<?php

namespace App\Domain\GameProviders\Exceptions;

use DomainException;

/**
 * A demo session could not be started. Nothing was charged.
 */
class DemoSessionRefused extends DomainException {}
