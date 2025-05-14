<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

enum StreamDirections: string
{
    case UPSTREAM = 'upstream';
    case DOWNSTREAM = 'downstream';
}
