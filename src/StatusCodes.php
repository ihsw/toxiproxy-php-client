<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

enum StatusCodes: int
{
    case CONFLICT = 409;
    case CREATED = 201;
    case BAD_REQUEST = 400;
    case NOT_FOUND = 404;
    case NO_CONTENT = 204;
    case OK = 200;
}
