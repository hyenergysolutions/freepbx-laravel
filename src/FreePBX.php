<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX;

use HyEnergySolutions\FreePBX\Exceptions\FreePBXException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FreePBX
{
    private string $tokenUri;

    private string $gqlUri;

    private string $restUri;

    public function __construct(
        private string $url,
        private string $clientId,
        private string $clientSecret,
    ) {
        $this->tokenUri = $this->url.'/admin/api/api/token';
        $this->gqlUri = $this->url.'/admin/api/api/gql';
        $this->restUri = $this->url.'/admin/api/api/rest';
    }

    /**
     * Get OAuth token
     */
    protected function getToken(): string
    {
        return Cache::remember('freepbx_token', 3500, function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->tokenUri, [
                    'grant_type' => 'client_credentials',
                    'scope' => 'gql rest',
                ]);

            if ($response->failed() || ! isset($response->json()['access_token'])) {
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
        $response = Http::withToken($this->getToken())
            ->post($this->gqlUri, [
                'query' => $query,
            ]);

        if ($response->failed()) {
            Cache::forget('freepbx_token');
            throw FreePBXException::graphqlError($response->body());
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
        $response = Http::withToken($this->getToken())
            ->{$method}($this->restUri.$endpoint);

        if ($response->failed()) {
            Cache::forget('freepbx_token');
            throw FreePBXException::restError($response->body());
        }

        return $response->json();
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
}
