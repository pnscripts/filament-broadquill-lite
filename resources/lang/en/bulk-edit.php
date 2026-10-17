<?php

return [

    'label' => 'Edit selected',

    'modal' => [

        'heading' => 'Edit selected :label',

        'description' => 'Tick each field you want to change. Fields you leave unticked keep their current values.',

        'actions' => [

            'apply' => [
                'label' => 'Apply changes',
            ],

        ],

    ],

    'fields' => [

        'keep' => 'Keeps its current value',

        'cleared' => 'The value will be removed.',

        'mode' => [
            'set' => 'Set value',
            'clear' => 'Clear',
        ],

    ],

    'notifications' => [

        'nothing' => [
            'title' => 'Nothing to change',
            'body' => 'Tick at least one field, then apply.',
        ],

        'updated' => [
            'title' => 'Changes applied',
            'changed' => '1 record updated.|:count records updated.',
            'unchanged' => '1 record already had these values.|:count records already had these values.',
        ],

        'partial' => [
            'title' => 'Applied to :count of :total',
        ],

        'none' => [
            'title' => 'No changes applied',
        ],

        'missing_authorization' => 'You are not allowed to edit 1 record.|You are not allowed to edit :count records.',

        'missing_processing' => '1 record could not be saved.|:count records could not be saved.',

        'rolled_back' => 'Nothing was changed: at least one record could not be updated, so every change was rolled back.',

    ],

];
