<?php

return [

    /*
    | Public-site analytics only. Admin and private routes never receive this.
    | Leave disabled (or omit ANALYTICS_ID) to render no tracking code.
    */

    'enabled' => filter_var(env('ANALYTICS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'id' => env('ANALYTICS_ID'),

];
