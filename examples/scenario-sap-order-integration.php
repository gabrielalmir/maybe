<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maybe\Result\Result;
use Maybe\Schema\Schema;
use Maybe\Schema\ValidationErrorBag;

/**
 * SCENARIO: pushing a confirmed sales order into SAP (RFC/OData/BAPI-style
 * client). This is the pattern that trips up most legacy integrations: SAP
 * calls fail for structured reasons — a duplicate document, a missing cost
 * center, an expired session, a network timeout — and legacy code usually
 * collapses all of them into one of:
 *
 *   if (!$sap->post($payload)) { return false; }        // which error? unknown.
 *   try { $sap->post($payload); } catch (\Exception $e) { log($e); }   // and then? nothing.
 *
 * The real business risk here is silent data loss: the order is confirmed
 * to the customer, but never created in SAP, and nobody finds out until
 * finance reconciliation weeks later. Result forces every SAP outcome to
 * be handled explicitly, and — critically — lets us tell a *retryable*
 * connection failure apart from a *non-retryable* business error.
 */

/**
 * @template T
 * @param callable(): T $fn
 * @return Result<T,\Throwable>
 */
function tryCatch(callable $fn): Result
{
    try {
        return Result::ok($fn());
    } catch (\Throwable $e) {
        return Result::err($e);
    }
}

/** Transient failures: safe to retry (network blip, session expired). */
final class SapConnectionException extends \RuntimeException
{
}

/** Structural/business failures: retrying will not help without a data fix. */
final class SapBusinessException extends \RuntimeException
{
}

/** Simulates a SAP client posting a sales order via RFC/OData. */
final class SapClient
{
    public function postSalesOrder(array $payload): string
    {
        if ($payload['company_code'] === '9999') {
            throw new SapConnectionException('session expired, re-authenticate');
        }

        foreach ($payload['items'] as $item) {
            if ($item['material_code'] === 'UNKNOWN') {
                throw new SapBusinessException('material UNKNOWN does not exist in MARA');
            }
        }

        return 'SO-' . substr(sha1(json_encode($payload)), 0, 10);
    }
}

$orderSchema = Schema::shape([
    'company_code' => Schema::string()->trimmed()->regex('/^[0-9]{4}$/'),
    'document_type' => Schema::enumeration(['ZOR1', 'ZOR2']),
    'items' => Schema::arrayOf(
        Schema::shape([
            'material_code' => Schema::string()->trimmed()->min(3),
            'quantity' => Schema::int()->min(1),
        ])
    ),
]);

$sap = new SapClient();

/**
 * Validate the outbound payload against SAP's expected shape before
 * spending a network round-trip, then post it and classify the outcome.
 *
 * @param array $order
 * @return Result<string,array{retryable:bool,reason:string}>
 */
$pushOrderToSap = static function (array $order) use ($orderSchema, $sap): Result {
    return $orderSchema->safeParse($order)
        ->mapErr(static function (ValidationErrorBag $errors): array {
            return ['retryable' => false, 'reason' => 'invalid_payload: ' . $errors->summary()];
        })
        ->andThen(static function (array $payload) use ($sap): Result {
            return tryCatch(static function () use ($sap, $payload): string {
                return $sap->postSalesOrder($payload);
            })->mapErr(static function (\Throwable $e): array {
                return [
                    'retryable' => $e instanceof SapConnectionException,
                    'reason' => $e->getMessage(),
                ];
            });
        });
};

/**
 * The order is confirmed locally regardless of the SAP outcome. A
 * retryable SAP failure is requeued; a non-retryable one goes to a manual
 * review bucket instead of being retried forever.
 */
$requeued = [];
$manualReview = [];

$orders = [
    [
        'id' => 5001,
        'company_code' => '1000',
        'document_type' => 'ZOR1',
        'items' => [['material_code' => 'MAT-100', 'quantity' => 2]],
    ],
    [
        'id' => 5002,
        'company_code' => '9999',
        'document_type' => 'ZOR1',
        'items' => [['material_code' => 'MAT-200', 'quantity' => 1]],
    ],
    [
        'id' => 5003,
        'company_code' => '1000',
        'document_type' => 'ZOR1',
        'items' => [['material_code' => 'UNKNOWN', 'quantity' => 1]],
    ],
];

foreach ($orders as $order) {
    $sapResult = $pushOrderToSap($order);

    $status = $sapResult->match(
        static fn (string $sapDocNumber): string => "created in SAP ({$sapDocNumber})",
        static function (array $error) use ($order, &$requeued, &$manualReview): string {
            if ($error['retryable']) {
                $requeued[] = $order['id'];

                return "requeued for retry ({$error['reason']})";
            }

            $manualReview[] = $order['id'];

            return "sent to manual review ({$error['reason']})";
        }
    );

    printf("order #%d: confirmed locally | SAP: %s\n", $order['id'], $status);
}

printf("\nrequeued: %s | manual review: %s\n", implode(',', $requeued) ?: 'none', implode(',', $manualReview) ?: 'none');
