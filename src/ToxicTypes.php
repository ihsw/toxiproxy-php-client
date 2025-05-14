<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

enum ToxicTypes: string
{
    case SLOW_CLOSE = 'slow_close';
    case SLICER = 'slicer';
    case LATENCY = 'latency';
    case LIMIT_DATA = 'limit_data';
    case TIMEOUT = 'timeout';
    case BANDWIDTH = 'bandwidth';
}
