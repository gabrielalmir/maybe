<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Result\Result;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationError;
use Maybe\Schema\ValidationErrorBag;

/**
 * SCENARIO: validating a contract before it is stored and executed.
 *
 * Legacy code usually scatters this across a controller as a chain of
 * `if` statements, which makes two things easy to get wrong: a contract
 * can be half-saved in an invalid state, and the error messages aren't
 * structured enough for a legal/ops review screen to point at the exact
 * offending field.
 *
 * This also demonstrates a real limitation worth knowing: Schema has no
 * built-in cross-field validation (e.g. "end date must be after start
 * date") or conditional required-list checks (e.g. "these clauses must
 * all be present"). The idiomatic way to add that is NOT to invent a
 * bigger schema API — it's to chain a plain Result-returning business-rule
 * step with Result::andThen() right after Schema::safeParse(), reusing the
 * same ValidationErrorBag so the caller handles one uniform error shape
 * regardless of which stage rejected the contract.
 */

$partySchema = Schema::shape([
    'name' => Schema::string()->trimmed()->min(3),
    // Simplified format check only (14 digits) — not a full Brazilian
    // CNPJ check-digit validation, which is outside Schema's scope.
    'tax_id' => Schema::string()->trimmed()->regex('/^\d{14}$/'),
]);

$contractSchema = Schema::shape([
    'buyer' => $partySchema,
    'seller' => $partySchema,
    'value_in_cents' => Schema::int()->min(1),
    'starts_at' => Schema::date(),
    'ends_at' => Schema::date(),
    'clauses' => Schema::arrayOf(Schema::string()->trimmed()->min(3)),
]);

/** Clauses legal requires on every contract, regardless of type. */
const MANDATORY_CLAUSES = ['confidentiality', 'termination', 'liability'];

/**
 * Cross-field and cross-record rules that Schema alone cannot express.
 * Returns the same ValidationErrorBag error type as safeParse(), so the
 * two stages compose into one Result chain.
 *
 * @param array $contract
 * @return Result<array,ValidationErrorBag>
 */
function checkBusinessRules(array $contract): Result
{
    $errors = new ValidationErrorBag();

    if ($contract['ends_at'] <= $contract['starts_at']) {
        $errors = $errors->withError(
            new ValidationError('$.ends_at', 'End date must be after the start date', 'contract.invalid_period')
        );
    }

    $missingClauses = array_diff(MANDATORY_CLAUSES, $contract['clauses']);

    foreach ($missingClauses as $clause) {
        $errors = $errors->withError(
            new ValidationError('$.clauses', "Missing mandatory clause: {$clause}", 'contract.missing_clause')
        );
    }

    return $errors->isEmpty() ? Result::ok($contract) : Result::err($errors);
}

/**
 * @param array $input
 * @return Result<array,ValidationErrorBag>
 */
$validateContract = static function (array $input) use ($contractSchema): Result {
    return $contractSchema->safeParse($input)->andThen('checkBusinessRules');
};

$contracts = [
    [
        'buyer' => ['name' => 'Acme Ltda', 'tax_id' => '12345678000199'],
        'seller' => ['name' => 'Fornecedora XPTO', 'tax_id' => '98765432000188'],
        'value_in_cents' => 5_000_000,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-12-31',
        'clauses' => ['confidentiality', 'termination', 'liability', 'jurisdiction'],
    ],
    [
        'buyer' => ['name' => 'Acme Ltda', 'tax_id' => '12345678000199'],
        'seller' => ['name' => 'Fornecedora XPTO', 'tax_id' => '98765432000188'],
        'value_in_cents' => 5_000_000,
        'starts_at' => '2026-06-01',
        'ends_at' => '2026-01-01',
        'clauses' => ['confidentiality'],
    ],
    [
        'buyer' => ['name' => 'Ac', 'tax_id' => 'invalid'],
        'seller' => ['name' => 'Fornecedora XPTO', 'tax_id' => '98765432000188'],
        'value_in_cents' => 0,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-12-31',
        'clauses' => ['confidentiality', 'termination', 'liability'],
    ],
];

foreach ($contracts as $index => $contract) {
    $result = $validateContract($contract);

    echo $result->match(
        static function (array $valid) use ($index): string {
            return sprintf('contract #%d: approved (value: %d cents)', $index, $valid['value_in_cents']);
        },
        static function (ValidationErrorBag $errors) use ($index): string {
            $lines = [sprintf('contract #%d: rejected with %d issue(s)', $index, $errors->count())];

            foreach ($errors->all() as $error) {
                $lines[] = sprintf('  %s: %s', $error->path(), $error->message());
            }

            return implode("\n", $lines);
        }
    ) . "\n";
}
