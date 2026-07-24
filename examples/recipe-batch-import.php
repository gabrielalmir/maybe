<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

/**
 * Validates a batch of rows (e.g. a CSV import) in one pass. Schema::arrayOf
 * reports errors with the row index in the path (e.g. "$[1].email"), so a
 * single ValidationErrorBag can be turned into a precise, per-row report
 * without a manual loop.
 */
$rowSchema = Schema::shape([
    'email' => Schema::string()->trimmed()->min(5),
    'age' => Schema::int()->min(18),
]);

$batchSchema = Schema::arrayOf($rowSchema);

$rows = [
    ['email' => 'ana@example.com', 'age' => 23],
    ['email' => 'invalid', 'age' => 23],
    ['email' => 'bruno@example.com', 'age' => 15],
];

$result = $batchSchema->safeParse($rows);

echo $result->match(
    static function (array $validRows): string {
        return sprintf('Imported %d row(s) successfully.', count($validRows));
    },
    static function (ValidationErrorBag $errors): string {
        $lines = ['Import failed with ' . $errors->count() . ' error(s):'];

        foreach ($errors->all() as $error) {
            $lines[] = sprintf('  row %s: %s', $error->path(), $error->message());
        }

        return implode("\n", $lines);
    }
) . "\n";

/**
 * When partial success is acceptable (import the valid rows, report the
 * rest), validate row by row instead and keep both buckets.
 */
$imported = [];
$rejected = [];

foreach ($rows as $index => $row) {
    $rowSchema->safeParse($row)->match(
        static function (array $validRow) use (&$imported): void {
            $imported[] = $validRow;
        },
        static function (ValidationErrorBag $errors) use (&$rejected, $index): void {
            $rejected[] = ['row' => $index, 'reason' => $errors->summary()];
        }
    );
}

printf("\nPartial import: %d imported, %d rejected\n", count($imported), count($rejected));

foreach ($rejected as $entry) {
    printf('  row %d rejected: %s' . "\n", $entry['row'], $entry['reason']);
}
