<?php

namespace App\Service;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\GetItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DefaultUserSettings implements UserSettings
{
    public function __construct(
        #[Autowire(value: '%app.dynamo.user_settings_table%')]
        private string $tableName,
        private DynamoDbClient $dynamoDb,
    ) {
    }

    public function getUserApiKey(string $userId): ?string
    {
        $result = $this->dynamoDb->getItem(new GetItemInput([
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'Key' => [
                'userId' => new AttributeValue(['S' => $userId]),
            ],
        ]))->getItem();

        if (!count($result)) {
            return null;
        }

        return $result['apiKey']->getS();
    }

    public function setUserApiKey(string $userId, ?string $apiKey): void
    {
        $apiKeyAttribute = $apiKey === null ? new AttributeValue(['NULL' => true]) : new AttributeValue(['S' => $apiKey]);
        $this->dynamoDb->putItem(new PutItemInput([
            'TableName' => $this->tableName,
            'Item' => [
                'userId' => new AttributeValue(['S' => $userId]),
                'apiKey' => $apiKeyAttribute,
            ],
        ]));
    }
}
