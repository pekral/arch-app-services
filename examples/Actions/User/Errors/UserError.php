<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Errors;

/**
 * Represents expected failure reasons for user-related operations.
 */
enum UserError: string
{

    case EMAIL_ALREADY_EXISTS = 'email_already_exists';

    case INVALID_DATA = 'invalid_data';

    case NOT_FOUND = 'not_found';

}
