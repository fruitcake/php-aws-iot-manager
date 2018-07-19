<?php

namespace Fruitcake\AwsIot;

use Aws\Sdk;

class ThingManager
{
    /** @var \Aws\Iot\IotClient */
    private $client;

    public function __construct(Sdk $sdk)
    {
        $this->client = $sdk->createIot();
    }

    public function createThing($thingName, $typeName, array $attributes)
    {
        $result = $this->client->createThing([
            'thingName' => $thingName,
            'thingTypeName' => $typeName,
            'attributePayload' => [
                'attributes' => $attributes,
            ]
        ]);

        return $result;
    }

    public function updateThing($thingName, $typeName, $attributes)
    {
        return $this->client->updateThing([
            'thingName' => $thingName,
            'thingTypeName' => $typeName,
            'attributePayload' => [
                'attributes' => $attributes,
                'merge' => true,
            ]
        ]);
    }

    public function createKeysAndCertificate($active = true)
    {
        return $this->client->createKeysAndCertificate([
            'setAsActive' => $active,
        ]);
    }

    public function createCertificateFromCsr($csr, $active = true)
    {
        return $this->client->createCertificateFromCsr([
            'certificateSigningRequest' => $csr,
            'setAsActive' => true,
        ]);
    }

    public function attachCertificate($thingName, $certificateArn, $policyName = null)
    {
        $this->client->attachThingPrincipal([
            'principal' => $certificateArn,
            'thingName' => $thingName,
        ]);

        if ($policyName) {
            $this->client->attachPrincipalPolicy([
                'policyName' => $policyName,
                'principal' => $certificateArn,
            ]);
        }
    }

    public function deactiveCertificate($certificateId)
    {
        return $this->client->updateCertificate([
            'certificateId' => $certificateId,
            'newStatus' => 'INACTIVE',
        ]);
    }

    public function deleteCertificate($certificateId)
    {
        return $this->client->deleteCertificate([
            'certificateId' => $certificateId,
        ]);
    }

    public function deleteThing($thingName)
    {
        $principals = $this->client->listThingPrincipals([
            'thingName' => $thingName,
        ]);

        foreach ($principals['principals'] as $principal) {
            // Parse the full name
            $principalParts = explode(':', $principal);
            list($type, $id) = explode('/', array_pop($principalParts), 2);

            $policies = $this->client->listPrincipalPolicies([
                'principal' => $principal,
            ]);

            // Delete the policies
            foreach ($policies['policies'] as $policy) {
                $this->client->detachPrincipalPolicy([
                    'policyName' => $policy['policyName'],
                    'principal' => $principal,
                ]);
            }

            // Detach the principals
            $this->client->detachThingPrincipal([
                'principal' => $principal,
                'thingName' => $thingName,
            ]);

            // Deactive the certificates and delete it
            if ($type === 'cert') {
                $this->deactiveCertificate($id);
                $this->deleteCertificate($id);
            }
        }

        return $this->client->deleteThing([
            'thingName' => $thingName,
        ]);
    }
}