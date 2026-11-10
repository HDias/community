<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Per Page
    |--------------------------------------------------------------------------
    |
    | Number of records returned by paginated endpoints when no explicit
    | per-page value is provided.
    |
    */

    'per_page' => (int) env('PAGINATION_PER_PAGE', 50),

];
