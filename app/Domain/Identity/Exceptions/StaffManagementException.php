<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

/**
 * A staff-management change was refused. The message is safe to show to
 * the staff member who attempted it.
 */
class StaffManagementException extends RuntimeException {}
