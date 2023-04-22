<?php

namespace App\Enum;

enum AppMode: string
{
    /**
     * Uses AWS services like DynamoDB and SQS for data storage and queue
     */
    case AwsServerless = 'aws-serverless';

    /**
     * Uses Redis for data storage and queue
     */
    case Redis = 'redis';
}
