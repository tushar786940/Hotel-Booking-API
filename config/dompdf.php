<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    'show_warnings' => env('APP_DEBUG', false),

    'public_path' => null,

    'convert_entities' => true,

    'options' => [
        /*
         * Font directory - where DomPDF looks for custom fonts
         * We'll add custom fonts later for better PDF rendering
         */
        'font_dir'    => storage_path('fonts'),
        'font_cache'  => storage_path('fonts'),

        /*
         * Temporary directory for rendering
         */
        'temp_dir'    => storage_path('app/temp'),

        /*
         * Enable remote assets (images from URLs)
         * NEEDED for hotel logos and images in PDFs
         */
        'enable_remote' => true,

        /*
         * Default font
         * 'DejaVu Sans' supports more characters than 'Helvetica'
         */
        'default_font' => 'dejavusans',

        /*
         * DPI - higher = sharper but larger file
         * 96 is good for screen, 150 for print
         */
        'dpi' => 150,

        /*
         * Enable HTML5 parser (better CSS support)
         */
        'enable_html5_parser' => true,

        /*
         * Enable CSS float (needed for complex layouts)
         */
        'enable_css_float' => true,

        /*
         * Paper size and orientation
         */
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
    ],
];
