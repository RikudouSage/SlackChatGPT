# OpenAI ChatGPT for Slack

This is an **unofficial** self-hosted Slack bot for ChatGPT.

### Features:

- Reply to private messages
- Reply to a channel (you can switch between replying to every comment and to @ mentions only)
- Stream replies instead of waiting for the full message
- Bot uses all messages in a thread to maintain context
- Automatic validation of request signature — only valid requests from Slack are let through
- Global and per user limits to control costs
  - Optional command for users to provide their own API key to bypass the limits
- Support for multiple languages
- Deploys to AWS using the serverless framework
- It doesn't cost anything when not in use — everything is serverless, and you only pay as you go
  - It uses AWS Lambda, DynamoDB, SQS and Secrets Manager
  - Alternatively, Dockerfiles are provided for you to host anywhere you want

Currently supported languages:

- English (en)
- Czech (cs)

## Configuration

Configuration is done using environment variables in `.env.local` file.

> You must create the `.env.local` file manually and then put every configuration in there. You can take a look
> at `.env` file for inspiration of what it should look like.

Note that if you don't use the provided serverless configuration to deploy to AWS, you may provide the environment variables
in any way you want.

| **Environment variable**                 | **Description**                                                                                                                                                                                                                                                   |
|------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `SLACK_SIGNING_SECRET`&ast;              | Secret used for validation of incoming requests. Provided in Slack developer portal.                                                                                                                                                                              |
| `SLACK_BOT_TOKEN`&ast;                   | Token used for communication with Slack api. Provided in Slack developer portal.                                                                                                                                                                                  |
| `OPENAI_API_KEY`                         | API key for communication with GPT model.                                                                                                                                                                                                                         |
| `OPENAI_TIMEOUT`&ast;&ast;               | The timeout in seconds before the message is considered lost. If this timeout is reached the bot will send an ephemeral message to the user. Should be a number between 1 and 240 (both inclusive). Defaults to 60 seconds.                                       |
| `AWS_REGION`                             | The region you run the bot in, automatically provided by AWS Lambda, but useful to set manually for local testing.                                                                                                                                                |
| `CHAT_GPT_SYSTEM_MESSAGE`&ast;&ast;&ast; | The (invisible) instruction that is sent to the AI model at the start of each conversation. Defaults to: **You are a chatbot in a company's Slack workspace available to the company's employees to help with everything they ask.**                              |
| `DEFAULT_CHANNEL_MODE`                   | A value from the [`App\Enum\ChannelMode`](src/Enum/ChannelMode.php) enum. When set to `mentions`, bot only responds to direct mentions and threads it's already part of. When set to `all`, bot responds to all messages in the channel. Default is **mentions**. |
| `WELCOME_MESSAGE`                        | A message the bots sends upon being invited to a channel. If left empty a default message which includes information about the mode it runs in and how to change it gets sent.                                                                                    |
| `PER_USER_DAILY_LIMIT`                   | A limit for message count for every user individually. If the limit is reached, the bot lets the user know. Defaults to `-1` which means no limit.                                                                                                                |
| `TOTAL_DAILY_LIMIT`                      | Global message limit. If the limit is reached no further messages will be sent to AI model. The bot lets the user know that the limit has been reached.                                                                                                           |
| `OPENAI_MODEL`                           | The model to use. Use the command `app:openai:models`&ast;&ast;&ast;&ast; to list available models. Defaults to `gpt-3.5-turbo`                                                                                                                                   |
| `OPENAI_ORGANIZATION_ID`                 | The organization ID to use. If left empty, the default organization will be used. Defaults to empty string.                                                                                                                                                       |
| `REPLY_MODE`                             | Must be `stream` or `all-at-once`. If set to stream, one message is continuously updated as parts of it are received from the AI model. If set to `all-at-once`, the message is only sent when it's whole. Default is `all-at-once`.                              |

&ast; You won't have these before you create a Slack app, it's okay to deploy without these first and redeploy once you have them.

&ast;&ast;
If you need the limit to be greater than 240 seconds,
you must also edit the `serverless.yml` file and change the worker function timeout to some larger number.

&ast;&ast;&ast; OpenAI mentions that the GPT-3.5 model doesn't really pay strong attention to the system message,
so it may be hit-and-miss whether your instruction is respected or not.

&ast;&ast;&ast;&ast; You can run the command locally (`php bin/console app:openai:models`) or using docker
(`docker run --rm -it -v $(pwd):/SlackChatGPT -w /SlackChatGPT rikudousage/php-composer:8.2 bin/console app:openai:models`)
or on the deployed app using serverless (`serverless bref:cli --args="app:openai:models"`).

There are other environment variables which are set automatically and don't need to be changed. Some of them are provided in serverless
configuration file and thus changing them in `.env.local` has no effect.

> If you don't use the provided serverless configuration, you may override these in any way you want, even in .env.local.

| **Environment variable**  | **Description**                                                                                                                                                                                                                                                                      | **Overridable in `.env.local`** |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| `APP_ENV`                 | App's environment, automatically set to `prod` on deploy.                                                                                                                                                                                                                            | No                              |
| `APP_SECRET`              | A secret string used for various security features in the underlying Symfony framework. Defaults to a random string created on first deploy.                                                                                                                                         | No                              |
| `MESSENGER_TRANSPORT_DSN` | A dsn for a queue implementation for processing jobs in the background. Defaults to AWS SQS queue created on deploy.                                                                                                                                                                 | No                              |
| `CHANNEL_MODES_TABLE`     | The name of the DynamoDB table used for storing channel mode (`mentions` or `all`) for every channel the bot is a part of. Defaults to a table created during deploy.                                                                                                                | No                              |
| `DYNAMODB_CACHE_TABLE`    | The name of the DynamoDB table used for cache. Rate limiting is also tracked in this table. Defaults to a table created during deploy.                                                                                                                                               | No                              |
| `USER_SETTINGS_TABLE`     | The name of the DynamoDB table used for user settings. Currently only custom api key for each user if they provide their own. Defaults to a table created during deploy.                                                                                                             | No                              |
| `LOCK_DSN`                | A dsn for implementation of locks. Locks are pretty much ignored because the worst that can happen is an user gains a few more messages. If you want, you can read about [`symfony/lock`](https://symfony.com/doc/current/lock.html) in the documentation and implement it yourself. | Yes                             |
| `APP_MODE`                | One of preconfigured app modes, currently either `aws-serverless` or `redis`. After changing this, you must clear the cache before anything changes. Don't deploy using serverless in any other mode than `aws-serverless`.                                                          | Yes                             |
| `REDIS_HOST`              | Ignored for serverless deployment, used when `APP_MODE` is set to `redis`                                                                                                                                                                                                            | Yes                             |

## Deploying

> Note: If you don't have php and serverless installed locally, you can use the docker image rikudousage/php-composer:8.2 instead like this:
> `docker run --rm -it -v $(pwd):/SlackChatGPT -v $HOME/.aws:/root/.aws -w /SlackChatGPT rikudousage/php-composer:8.2`

After configuring the `.env.local` according to the section above you can proceed to deploy:

1. Install dependencies
   1. `composer install --no-dev --no-scripts`
2. Warmup the production build artifacts
   1. `APP_ENV=prod php bin/console cache:warmup`
3. Deploy to serverless
   1. `serverless deploy`
   2. Make sure you have AWS cli configured before deploying using serverless, alternatively provide AWS credentials in any other way serverless supports
4. Afterward you will see URL that looks like `https://[randomString].execute-api.[region].amazonaws.com`, note it down
   1. Optionally open the URL to check that everything works
5. Continue to create a Slack app

## Create a Slack bot

> If it looks like too many steps, don't worry, it's just a really detailed guide, and the whole process can be done in
> 5 minutes.

1. Go to [`https://api.slack.com`](https://api.slack.com/apps?new_app=1) and create a new app
2. In the popup, choose "From an app manifest"
3. Select the workspace you want to use this bot in
4. Switch to the YAML tab
5. Copy the contents of [`slack-app-manifest.yaml`](slack-app-manifest.yaml)
6. Replace `[baseUrl]` with the URL you noted down when deploying
7. Replace `[language]` with language short code of your choice, see currently supported languages at the top of this README
8. You can optionally delete the part of the URL with the language to use the default English
9. You can optionally delete/modify any command you wish 
10. You can modify the `bot_events` that this app listens to:
    1. `message.channels` - messages in a public channel
    2. `message.im` - direct private message with the bot
11. You can alter the display name of the app or bot
12. You can alter whether the bot should always be online or not
13. After you've finished making changes, paste the YAML content into the window
14. Confirm creation
15. Install the app to your workspace by clicking `Install to workspace`
    1. Copy your Signing Secret from `Basic Information` -> `App Credentials` and set it in your `.env.local` file
    2. Copy your Bot User OAuth Token from `OAuth & Permissions` -> `OAuth Tokens for Your Workspace`
    3. Set Signing Secret as an environment variable `SLACK_SIGNING_SECRET` and Bot User OAuth Token as `SLACK_BOT_TOKEN`
    4. Redeploy the app by following the [`Deploying`](#deploying) procedure
16. Go to `App Home` -> `Show Tabs` and check `Allow users to send Slash commands and messages from the messages tab` if you want to allow users to send direct messages to the bot
17. Test by writing a private message to the bot (if you enabled it in step 16)
18. Invite the bot to any channel you want (by mentioning it by its name which is @ChatGPT by default)
19. Test out the commands `/chatgpt`,  `/chatgpt-api-key` and `/chatgpt-model` if you enabled them
20. Test out that the bot responds when you mention it in the channel (or to every message if you've changed the mode)
21. Do any other changes you want, like setting a logo for the bot and so on

## Troubleshooting

If the bot doesn't reply, you can check if there are any error messages in the logs:

1. Go to your AWS account
   1. Switch to the correct region if you're not already
2. Go to CloudWatch
3. Go to Log groups
4. Filter out `SlackChatGPT` in the search field
5. You should see three groups:
   1. `/aws/lambda/SlackChatGPT-prod-web`
   2. `/aws/lambda/SlackChatGPT-prod-console` (you can ignore this one)
   3. `/aws/lambda/SlackChatGPT-prod-worker`
6. Search through the two relevant groups for any errors

## Costs

This project is made in a way that reduces costs as much as possible. These resources are created in your AWS account:

- `Secret Manager` secret, costs $0.40 per month
  - You can delete the secret manually after the first deployment, though it might get recreated later
- `DynamoDB` tables which run in pay-per-request mode, meaning there are no costs just for the existence of the tables
- `Lambda` function where the code lives, it only costs you money when you're using it (making requests to this bot)
- `Log groups` where you pay for the number of logs stored, I recommend setting some data retention period to avoid accruing costs, but it should still be very cheap
- `API Gateway` acts as a webserver for the Lambda, and again you only pay for the requests you make so if you don't use it, you don't pay
- `SQS` queue for background jobs processing, you only pay for the requests you make and the first million requests every month are free

As you can see, there's nothing where you pay just for its existence (well, except the small fee of $0.40 for the secret manager). This shouldn't ever
cost more than a few dollars for heavily populated Slack workspaces.

The GPT costs $0.002 per thousand tokens (you can think of a token as a word even though it's not exactly accurate, but for simple calculations it's enough).
Both your messages to the AI model and the model's answers are counted as tokens.

## Translating

In the directory [translations](translations) you can see other languages. The format for messages is xliff, you can either find
localization software that can work with those or you can simply open them in any XML editor.

If you want to add your language, run this command (after installing dependencies using `composer install`):

> Note: If you don't have php installed locally, you can use the docker image rikudousage/php-composer:8.2 instead like this:
> `docker run --rm -it -v $(pwd):/SlackChatGPT -w /SlackChatGPT rikudousage/php-composer:8.2`

`php bin/console translation:extract [language] --force`

Replace `[language]` with your language of choice, for example for French, it would look like this:

`php bin/console translation:extract fr --force`

> You can ignore the `security.[language].xlf` file just created, it gets pulled automatically from the underlying Symfony framework.`

After translating, you can use your new language in the URL parameter, so going with the French example, the commands endpoint would look like:

*https://[randomString].execute-api.[region].amazonaws.com/**fr**/slack/commands*

And if you feel generous, you can open a pull request with your translations and let others enjoy it as well!

## Removing

If you want to remove this bot from your AWS account, it's as simple as running: `serverless remove`.

## Deploying somewhere else

If you don't want to deploy to AWS, it's a little more involved than just running one command, but I'll always try
to make it easy to deploy anywhere.

For convenience, you can switch the app between multiple modes where services will be enabled and disabled based
on the mode, you can switch the mode by setting the `APP_MODE` env variable to one of the supported values
(see [App\Enum\AppMode](src/Enum/AppMode.php) for available values).

You can also set the APP_MODE to an empty string, in which case you have to configure everything yourself, which
makes sense if your setup is more exotic.

For deploying outside AWS, I recommend setting the `APP_MODE` to `redis`, setting `REDIS_HOST` to a correct value
and either building using the provided [Dockerfiles](docker/redis-mode) or manually deploying on a server
having PHP 8.2 installed (you can check the required extensions by running `composer check-platform-reqs`).
Note that if you deploy without using Docker you also need to run the Symfony messenger consuming the `async` transport,
and ensure that ít doesn't fail.

Read more in the [Symfony documentation](https://symfony.com/doc/current/messenger.html).

### Building the docker files

There are two Dockerfiles to be built in Redis mode — the api and the worker.

API: `docker build -f docker/redis-mode/api.Dockerfile -t slack-chat-gpt-api .`

Worker: `docker build -f docker/redis-mode/worker.Dockerfile -t slack-chat-gpt-worker .`

The API image runs the webserver on port 80, map it to whatever port you want.

Note that the worker will exit from time to time when it consumes too much memory;
your orchestration tool should be prepared to restart the container.
