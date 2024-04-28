<?php

namespace App;

use Illuminate\Validation\Rules\Enum;



enum CountryOption: string
{
    case US = 'US';
    case Canada = 'Canada';
}
