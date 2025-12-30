<?php

declare(strict_types=1);

/**
 * Taxonomy Definitions
 *
 * Taxonomies allow content classification.
 * Hierarchical taxonomies support parent/child relationships.
 * Docs: https://ava.addy.zone/#/configuration?id=taxonomies-taxonomiesphp
 */

return [
    'category' => [
        'label' => 'Categories',
        'hierarchical' => true,
        'public' => true,
        'rewrite' => [
            'base' => '/category',
            'separator' => '/',
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,
            'hierarchy_rollup' => true,
        ],
        'ui' => [
            'show_counts' => true,
            'sort_terms' => 'name_asc',
        ],
    ],

    'tag' => [
        'label' => 'Tags',
        'hierarchical' => false,
        'public' => true,
        'rewrite' => [
            'base' => '/tag',
        ],
        'behaviour' => [
            'allow_unknown_terms' => true,
        ],
        'ui' => [
            'show_counts' => true,
            'sort_terms' => 'count_desc',
        ],
    ],
];
