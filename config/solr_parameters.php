<?php

return [

/*
|--------------------------------------------------------------------------
| Solr search configuration
|--------------------------------------------------------------------------
|
*/

    'defaults' => [
        'method' => env('SOLR_METHOD', 'textmatching'),
        'query' => 'stream.body',
        'fields' => ['title+abstract' => 'text'],
        'params' => [
            'mlt.fl' => env('SOLR_mlt.fl', 'text'),
            'mlt.qf' => env('SOLR_mlt.qf', 'text'),
            'mlt.mintf' => env('SOLR_mlt.mintf', 1),
            'mlt.mindf' => env('SOLR_mlt.mindf', 2),
            'mlt.minwl' => env('SOLR_mlt.minwl', 2),
            'mlt.maxwl' => env('SOLR_mlt.maxwl', 20),
            'mlt.maxqt' => env('SOLR_mlt.maxqt', 25),
            'mlt.maxntp' => env('SOLR_mlt.maxntp', 10000),
            'mlt.boost' => env('SOLR_mlt.boost', true),
            'fl' => env('SOLR_fl', 'id,familynumber,score'),
        ],
    ],


    'parameters' => [
        'facet' => env('SOLR_facet', false),
        'wt' => env('SOLR_wt', 'phps'),
        'requested_hits' => 25,
        'mlt.interestingTerms' => env('SOLR_mlt.interestingTerms', 'details'),
    ],

];