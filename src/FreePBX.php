<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX;

use HyEnergySolutions\FreePBX\Exceptions\FreePBXException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FreePBX
{
    public function __construct(
        private string $url,
        private string $clientId,
        private string $clientSecret,
    ) {}

    /**
     * Create HTTP client with common configuration
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->timeout(30)
            ->retry(3, 100, throw: false)
            ->withToken($this->getToken())
            ->throw();
    }

    /**
     * Get OAuth token
     */
    protected function getToken(): string
    {
        return Cache::remember('freepbx_token', 3500, function () {
            try {
                $response = Http::baseUrl($this->url)
                    ->timeout(30)
                    ->retry(3, 100)
                    ->asForm()
                    ->withBasicAuth($this->clientId, $this->clientSecret)
                    ->throw()
                    ->post('/admin/api/api/token', [
                        'grant_type' => 'client_credentials',
                        'scope' => 'gql rest',
                    ]);
            } catch (RequestException $e) {
                throw FreePBXException::tokenFailed($e->response->body());
            }

            if (! isset($response->json()['access_token'])) {
                throw FreePBXException::tokenFailed($response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Execute GraphQL query
     */
    protected function graphql(string $query): ?array
    {
        try {
            $response = $this->http()
                ->post('/admin/api/api/gql', ['query' => $query]);
        } catch (RequestException $e) {
            Cache::forget('freepbx_token');
            throw FreePBXException::graphqlError($e->response->body());
        }

        $json = $response->json();

        if (isset($json['errors'])) {
            throw FreePBXException::graphqlValidationErrors($json['errors']);
        }

        return $json['data'] ?? null;
    }

    /**
     * Execute REST API request
     */
    protected function rest(string $method, string $endpoint): mixed
    {
        try {
            return $this->http()
                ->{$method}('/admin/api/api/rest'.$endpoint)
                ->json();
        } catch (RequestException $e) {
            Cache::forget('freepbx_token');
            throw FreePBXException::restError($e->response->body());
        }
    }

    /**
     * Get all extensions
     */
    public function getExtensions(): Collection
    {
        $query = 'query {
            fetchAllExtensions {
                status
                message
                totalCount
                extension {
                    extensionId
                    tech
                    user {
                        name
                        voicemail
                        ringtimer
                        noanswer
                        recording
                        outboundCid
                        sipname
                        noanswerCid
                        busyCid
                        chanunavailCid
                        noanswerDestination
                        busyDestination
                        chanunavailDestination
                        mohclass
                        callwaiting
                    }
                }
            }
        }';

        $result = $this->graphql($query);

        return collect($result['fetchAllExtensions']['extension'] ?? []);
    }

    /**
     * Get all ring groups
     */
    public function getRingGroups(): Collection
    {
        $query = 'query {
            fetchAllRingGroups {
                status
                message
                totalCount
                ringgroups {
                    id
                    groupNumber
                    description
                    groupList
                    groupTime
                    strategy
                    needConf
                    callRecording
                }
            }
        }';

        $result = $this->graphql($query);

        return collect($result['fetchAllRingGroups']['ringgroups'] ?? []);
    }

    /**
     * Get all CDR (Call Detail Records)
     */
    public function getCdrs(int $first = 100): Collection
    {
        $query = 'query {
            fetchAllCdrs(first: '.$first.') {
                status
                message
                totalCount
                cdrs {
                    id
                    calldate
                    src
                    dst
                    duration
                    billsec
                    disposition
                    uniqueid
                }
            }
        }';

        $result = $this->graphql($query);

        return collect($result['fetchAllCdrs']['cdrs'] ?? []);
    }

    /**
     * Get all call flows (day/night mode)
     */
    public function getCallFlows(): Collection
    {
        return collect($this->rest('get', '/daynight/') ?? []);
    }

    /**
     * Get the current state of a call flow
     */
    public function getCallFlowState(string $id): ?string
    {
        $result = $this->rest('get', '/daynight/'.$id);

        return $result['state'] ?? null;
    }
}
