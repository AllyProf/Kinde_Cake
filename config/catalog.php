<?php

return [
    'categories' => [
        ['name' => 'Birthday Cakes', 'slug' => 'birthday-cakes', 'description' => 'Birthday celebration cakes'],
        ['name' => 'Wedding Cakes', 'slug' => 'wedding-cakes', 'description' => 'Wedding and engagement cakes'],
        ['name' => 'Custom Cakes', 'slug' => 'custom-cakes', 'description' => 'Made-to-order custom designs'],
        ['name' => 'Cupcakes', 'slug' => 'cupcakes', 'description' => 'Cupcakes and muffins'],
        ['name' => 'Pastries', 'slug' => 'pastries', 'description' => 'Pastries and baked goods'],
        ['name' => 'Bread', 'slug' => 'bread', 'description' => 'Bread and rolls'],
        ['name' => 'Ingredients', 'slug' => 'ingredients', 'description' => 'Raw ingredients and supplies'],
        ['name' => 'Decorations', 'slug' => 'decorations', 'description' => 'Cake toppers and decorations'],
    ],

    'packages' => [
        ['name' => 'Kilogram', 'symbol' => 'kg', 'description' => 'Weight in kilograms'],
        ['name' => 'Gram', 'symbol' => 'g', 'description' => 'Weight in grams'],
        ['name' => 'Piece', 'symbol' => 'pcs', 'description' => 'Individual pieces'],
        ['name' => 'Box', 'symbol' => 'box', 'description' => 'Packaged in boxes'],
        ['name' => 'Liter', 'symbol' => 'L', 'description' => 'Volume in liters'],
        ['name' => 'Milliliter', 'symbol' => 'ml', 'description' => 'Volume in milliliters'],
        ['name' => 'Dozen', 'symbol' => 'dz', 'description' => 'Twelve units'],
        ['name' => 'Pack', 'symbol' => 'pack', 'description' => 'General pack unit'],
        ['name' => 'Tray', 'symbol' => 'tray', 'description' => 'Tray or flat container'],
    ],

    'payment_providers' => [
        ['name' => 'M-Pesa', 'type' => 'mobile'],
        ['name' => 'Tigo Pesa', 'type' => 'mobile'],
        ['name' => 'Airtel Money', 'type' => 'mobile'],
        ['name' => 'Halopesa', 'type' => 'mobile'],
        ['name' => 'CRDB Bank', 'type' => 'bank'],
        ['name' => 'NMB Bank', 'type' => 'bank'],
        ['name' => 'NBC Bank', 'type' => 'bank'],
        ['name' => 'Equity Bank', 'type' => 'bank'],
    ],
];
