<?php

namespace App\Service;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\GetItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DynamoDbUserSettings implements UserSettings
{
    public function __construct(
        #[Autowire(value: '%app.dynamo.user_settings_table%')]
        private string $tableName,
        private DynamoDbClient $dynamoDb,
    ) {
    }

    public function getUserApiKey(string $userId): ?string
    {
        return $this->getString($userId, 'apiKey');
    }

    public function setUserApiKey(string $userId, ?string $apiKey): void
    {
        $this->setString($userId, 'apiKey', $apiKey);
    }

    public function getUserOrganizationId(string $userId): ?string
    {
        return $this->getString($userId, 'organizationId');
    }

    public function getUserAiModel(string $userId): ?string
    {
        return $this->getString($userId, 'aiModel');
    }

    public function setUserOrganizationId(string $userId, ?string $organizationId): void
    {
        $this->setString($userId, 'organizationId', $organizationId);
    }

    public function setUserAiModel(string $userId, ?string $aiModel): void
    {
        $this->setString($userId, 'aiModel', $aiModel);
    }

    private function setString(string $userId, string $property, ?string $value): void
    {
        $valueAsAttribute = $value === null ? new AttributeValue(['NULL' => true]) : new AttributeValue(['S' => $value]);

        $request = new PutItemInput([
            'TableName' => $this->tableName,
        ]);
        $item = $this->getItem($userId);
        $item['userId'] = new AttributeValue(['S' => $userId]);
        $item[$property] = $valueAsAttribute;
        $request->setItem($item);

        $this->dynamoDb->putItem($request);
    }

    private function getString(string $userId, string $property): ?string
    {
        $result = $this->getItem($userId);

        if (!count($result)) {
            return null;
        }

        return $result[$property]->getS();
    }

    /**
     * @return array<string, AttributeValue>
     */
    private function getItem(string $userId): array
    {
        return $this->dynamoDb->getItem(new GetItemInput([
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'Key' => [
                'userId' => new AttributeValue(['S' => $userId]),
            ],
        ]))->getItem();
    }
}
