<?php

// config for blemli/softrequired-for-filament
return [

    /*
     * Force ->live(onBlur: true) on soft-required fields so the field warning
     * updates while the user works. Fields that are already live keep their
     * own settings. Set to false to leave reactivity untouched.
     */
    'live' => true,

    /*
     * What happens when a record is saved while soft-required fields are
     * still empty: 'notify' saves and shows a warning notification,
     * 'confirm' asks before saving, 'none' stays silent. Fields marked
     * ->softRequired(warn: false) never trigger either.
     */
    'on_incomplete_save' => 'notify',

    'table_filter' => [
        // Automatically add the "Incomplete" filter to tables of resources
        // whose form has soft-required fields and whose model is Completable.
        'enabled' => true,

        // Resource classes that must not get the automatic filter.
        'except' => [],
    ],

    'widget' => [
        // Show the incomplete-records dashboard widget (it hides itself
        // whenever there is nothing left to complete).
        'enabled' => true,

        // Widget sort order within the dashboard. null keeps Filament's default.
        'sort' => null,

        // How many incomplete records the widget lists per model.
        'records_limit' => 5,

        // Completable models WITHOUT their own Filament resource (edited via
        // relation managers, for example) that the dashboard widget should
        // track too. They need a `protected array $completable` declaration;
        // override completionLabel()/completionUrl() on the model to control
        // how their stat reads and where it links.
        'models' => [],
    ],

    'resource_widget' => [
        // Render an incomplete-count stat above the table of every list page
        // whose resource has soft-required fields (hidden at zero).
        'enabled' => true,
    ],
];
