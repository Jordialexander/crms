<?php

namespace Database\Seeders;

use App\Models\CrOption;
use Illuminate\Database\Seeder;

class CrOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            // change_type
            ['type' => 'change_type', 'value' => 'standard',      'label' => 'Standard',      'sort_order' => 1],
            ['type' => 'change_type', 'value' => 'normal',         'label' => 'Normal',         'sort_order' => 2],
            ['type' => 'change_type', 'value' => 'emergency',      'label' => 'Emergency',      'sort_order' => 3],

            // category
            ['type' => 'category',   'value' => 'infrastructure', 'label' => 'Infrastructure', 'sort_order' => 1],
            ['type' => 'category',   'value' => 'application',    'label' => 'Application',    'sort_order' => 2],
            ['type' => 'category',   'value' => 'database',       'label' => 'Database',       'sort_order' => 3],
            ['type' => 'category',   'value' => 'network',        'label' => 'Network',        'sort_order' => 4],
            ['type' => 'category',   'value' => 'security',       'label' => 'Security',       'sort_order' => 5],
            ['type' => 'category',   'value' => 'other',          'label' => 'Other',          'sort_order' => 6],

            // priority
            ['type' => 'priority',   'value' => 'low',            'label' => 'Low',            'sort_order' => 1],
            ['type' => 'priority',   'value' => 'medium',         'label' => 'Medium',         'sort_order' => 2],
            ['type' => 'priority',   'value' => 'high',           'label' => 'High',           'sort_order' => 3],
            ['type' => 'priority',   'value' => 'critical',       'label' => 'Critical',       'sort_order' => 4],
        ];

        foreach ($options as $opt) {
            CrOption::firstOrCreate(
                ['type' => $opt['type'], 'value' => $opt['value']],
                array_merge($opt, ['is_active' => true])
            );
        }
    }
}
