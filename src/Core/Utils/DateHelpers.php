<?php

namespace Xfind\Core\Utils;

use Carbon\Carbon;

class DateHelpers
{
    public static function parse($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}
