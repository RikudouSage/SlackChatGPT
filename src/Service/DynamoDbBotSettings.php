<?php

namespace App\Service;

use App\Enum\ChannelMode;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\GetItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DynamoDbBotSettings implements BotSettings
{
    public function __construct(
        #[Autowire(value: '%app.dynamo.channel_mode_table%')]
        private string $tableName,
        private DynamoDbClient $dynamoDb,
    ) {
    }

    public function setChannelMode(string $channelId, ChannelMode $mode): void
    {
        $this->dynamoDb->putItem(new PutItemInput([
            'TableName' => $this->tableName,
            'Item' => [
                'channel' => new AttributeValue(['S' => $channelId]),
                'mode' => new AttributeValue(['S' => $mode->value]),
            ],
        ]));
    }

    public function getChannelMode(string $channelId): ChannelMode
    {
        $result = $this->dynamoDb->getItem(new GetItemInput([
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'Key' => [
                'channel' => new AttributeValue(['S' => $channelId]),
            ],
        ]))->getItem();

        if (!count($result)) {
            return ChannelMode::MentionsOnly;
        }

        return ChannelMode::from((string) $result['mode']->getS());
    }
}
