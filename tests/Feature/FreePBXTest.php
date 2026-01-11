<?php

use HyEnergySolutions\FreePBX\Exceptions\FreePBXException;
use HyEnergySolutions\FreePBX\Facades\FreePBX;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('getExtensions returns collection of extensions', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'data' => [
                'fetchAllExtensions' => [
                    'status' => true,
                    'message' => 'Success',
                    'totalCount' => 2,
                    'extension' => [
                        [
                            'extensionId' => '1001',
                            'tech' => 'pjsip',
                            'user' => ['name' => 'John Doe', 'outboundCid' => ''],
                        ],
                        [
                            'extensionId' => '1002',
                            'tech' => 'pjsip',
                            'user' => ['name' => 'Jane Doe', 'outboundCid' => ''],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $extensions = FreePBX::getExtensions();

    expect($extensions)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($extensions)->toHaveCount(2)
        ->and($extensions->first()['extensionId'])->toBe('1001')
        ->and($extensions->first()['user']['name'])->toBe('John Doe');
});

test('getRingGroups returns collection of ring groups', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'data' => [
                'fetchAllRingGroups' => [
                    'status' => true,
                    'message' => null,
                    'totalCount' => 2,
                    'ringgroups' => [
                        [
                            'id' => '1',
                            'groupNumber' => 600,
                            'description' => 'Sales',
                            'strategy' => 'ringall',
                        ],
                        [
                            'id' => '2',
                            'groupNumber' => 601,
                            'description' => 'Support',
                            'strategy' => 'hunt',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $ringGroups = FreePBX::getRingGroups();

    expect($ringGroups)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($ringGroups)->toHaveCount(2)
        ->and($ringGroups->first()['description'])->toBe('Sales')
        ->and($ringGroups->first()['groupNumber'])->toBe(600);
});

test('getCdrs returns collection of CDR records', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'data' => [
                'fetchAllCdrs' => [
                    'status' => true,
                    'message' => null,
                    'totalCount' => 1,
                    'cdrs' => [
                        [
                            'id' => '1',
                            'calldate' => '2025-01-11 10:00:00',
                            'src' => '1001',
                            'dst' => '1002',
                            'duration' => 120,
                            'billsec' => 115,
                            'disposition' => 'ANSWERED',
                            'uniqueid' => '1234567890.1',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $cdrs = FreePBX::getCdrs(10);

    expect($cdrs)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($cdrs)->toHaveCount(1)
        ->and($cdrs->first()['src'])->toBe('1001')
        ->and($cdrs->first()['disposition'])->toBe('ANSWERED');
});

test('throws exception on token failure', function () {
    Http::fake([
        '*/token' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    FreePBX::getExtensions();
})->throws(FreePBXException::class, 'Failed to get FreePBX token');

test('throws exception on GraphQL validation errors', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'errors' => [
                ['message' => 'Cannot query field "invalid" on type "Query".'],
            ],
        ]),
    ]);

    FreePBX::getExtensions();
})->throws(FreePBXException::class, 'FreePBX GraphQL error');

test('throws exception on GraphQL HTTP failure', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response('Internal Server Error', 500),
    ]);

    FreePBX::getExtensions();
})->throws(FreePBXException::class, 'FreePBX GraphQL error');

test('clears token cache on GraphQL HTTP failure', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response('Internal Server Error', 500),
    ]);

    // Warm up the cache
    Cache::put('freepbx_token', 'cached-token', 3500);
    expect(Cache::has('freepbx_token'))->toBeTrue();

    try {
        FreePBX::getExtensions();
    } catch (FreePBXException) {
        // Expected
    }

    expect(Cache::has('freepbx_token'))->toBeFalse();
});

test('caches token for subsequent requests', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'data' => [
                'fetchAllExtensions' => [
                    'status' => true,
                    'extension' => [],
                ],
            ],
        ]),
    ]);

    // First call
    FreePBX::getExtensions();
    // Second call
    FreePBX::getExtensions();

    // Token endpoint should only be called once
    Http::assertSentCount(3); // 1 token + 2 gql requests
});

test('returns empty collection when no data', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/gql' => Http::response([
            'data' => [
                'fetchAllExtensions' => [
                    'status' => true,
                    'extension' => null,
                ],
            ],
        ]),
    ]);

    $extensions = FreePBX::getExtensions();

    expect($extensions)->toBeEmpty();
});

test('getCallFlows returns collection of call flows', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/rest/daynight/*' => Http::response([
            ['ext' => '1', 'dest' => 'Sales Hours'],
            ['ext' => '2', 'dest' => 'Support Hours'],
        ]),
    ]);

    $callFlows = FreePBX::getCallFlows();

    expect($callFlows)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($callFlows)->toHaveCount(2)
        ->and($callFlows->first()['ext'])->toBe('1')
        ->and($callFlows->first()['dest'])->toBe('Sales Hours');
});

test('throws exception on REST API failure', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/rest/daynight/*' => Http::response('Internal Server Error', 500),
    ]);

    FreePBX::getCallFlows();
})->throws(FreePBXException::class, 'FreePBX REST API error');

test('clears token cache on REST API failure', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'fake-token']),
        '*/rest/daynight/*' => Http::response('Internal Server Error', 500),
    ]);

    Cache::put('freepbx_token', 'cached-token', 3500);
    expect(Cache::has('freepbx_token'))->toBeTrue();

    try {
        FreePBX::getCallFlows();
    } catch (FreePBXException) {
        // Expected
    }

    expect(Cache::has('freepbx_token'))->toBeFalse();
});
