<?php
class InvoiceDesignRegistry {
    private static $designs = [
        'default' => [
            'name' => 'Default Design',
            'file' => 'default.php',
            'description' => 'Clean, modern layout with bold typography'
        ],
        'print2' => [
            'name' => 'Print Design 2',
            'file' => 'print2.php',
            'description' => 'Monospace, technical layout for optimized printing'
        ],
        'print3' => [
            'name' => 'Print Design 3',
            'file' => 'print3.php',
            'description' => 'Branded layout with primary color accents'
        ]
    ];

    public static function getAll() {
        return self::$designs;
    }

    public static function get($key) {
        return self::$designs[$key] ?? null;
    }

    public static function exists($key) {
        return isset(self::$designs[$key]);
    }
}
?>
