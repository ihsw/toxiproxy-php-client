<?php

namespace Ihsw\Toxiproxy;

enum StreamDirections: string
{
    case UPSTREAM = 'upstream';
    case DOWNSTREAM = 'downstream';
}
