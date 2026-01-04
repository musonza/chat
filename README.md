<p align="left"><img src="menu.png" alt="chat" width="130px"></p>

[![Build Status](https://travis-ci.org/musonza/chat.svg?branch=master)](https://app.travis-ci.com/github/musonza/chat)
[![Downloads](https://poser.pugx.org/musonza/chat/d/total.svg)](https://packagist.org/packages/musonza/chat)
[![Packagist](https://img.shields.io/packagist/v/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)

## Chat

Create a Chat application for your multiple Models

**********************************************************
**What to learn how to make a package like this? https://leanpub.com/laravel-package-development**
***************************************************************************************************

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Adding the ability to participate to a Model](#Adding-the-ability-to-participate-to-a-Model)
  - [Enable the routes](#enable-the-routes)
  - [Get participant details](#get-participant-details)
  - [Creating a conversation](#creating-a-conversation)
  - [Get a conversation by Id](#get-a-conversation-by-id)
  - [Update conversation details](#update-conversation-details)
  - [Send a text message](#send-a-text-message)
  - [Send a message of custom type](#send-a-message-of-custom-type)
  - [Get a message by id](#get-a-message-by-id)
  - [Get message sender](#Get-message-sender)
  - [Mark a message as read](#mark-a-message-as-read)
  - [Mark whole conversation as read](#mark-whole-conversation-as-read)
  - [Unread messages count](#unread-messages-count)
  - [Delete a message](#delete-a-message)
  - [Cleanup Deleted Messages](#cleanup-deleted-messages)
  - [Clear a conversation](#clear-a-conversation)
  - [Get participant conversations](#Get-participant-conversations)
  - [Get a conversation between two participants](#get-a-conversation-between-two-participants)
  - [Get common conversations among participants](#get-common-conversations-among-participants)
  - [Remove participants from a conversation](#remove-participants-from-a-conversation)
  - [Add participants to a conversation](#add-participants-to-a-conversation)
  - [Get messages in a conversation](#get-messages-in-a-conversation)
  - [Get messages with cursor pagination](#get-messages-with-cursor-pagination)
  - [Get public conversations for discovery](#get-public-conversations-for-discovery)
  - [Get recent messages](#get-recent-messages)
  - [Get participants in a conversation](#get-participants-in-a-conversation)
  - [Get participation entry for a Model in a conversation](#Get-participation-entry-for-a-Model-in-a-conversation)
  - [Update participation settings](#Update-participation-settings)
  - [Data Transformers](#Data-Transformers)
  - [Message Encryption](#message-encryption)
- [License](#license)

Checkout a simple [Demo Application](https://github.com/musonza/chat-demo)

## Introduction

This package allows you to add a chat system to your Laravel ^5.4 application

## Installation

From the command line, run:

```
composer require musonza/chat
```

Publish the assets:

```
php artisan vendor:publish --provider="Musonza\Chat\ChatServiceProvider"
```

This will publish database migrations and a configuration file `musonza_chat.php` in the Laravel config folder.

## Configuration

See `musonza_chat.php` for configuration

Run the migrations:

```
php artisan migrate
```

## Usage

You can mix Models as participants. For instance you can have `Parents`, `Students` and `Professors` models communicating

#### Adding the ability to participate to a Model 

Add the `Musonza\Chat\Traits\Messageable` trait to any Model you want to participate in Conversations
For example, let's say we want out `Bot` model to chat with other Models:

```php

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

class Bot extends Model
{
    use Messageable;
}
```

#### Enable the routes

The package includes routes for conversations, conversation participants, and messaging. These routes are hidden by default. To enable them, change `should_load_routes` to `true`, but make sure to configure the middleware correctly. 

#### Get participant details

Since we allow Models with data that differ in structure to chat, we may want a uniform way to
represent the participant details in a uniform way.

You can get the details as follows:

```php
$participantModel->getParticipantDetails();
```
Assuming you have a column `name` for your model, this returns a default array `['name' => 'column_value']`
You can however, customize this for your needs by adding an Eloquent Accessor that returns an array
 with as much as you need to your model as follows:

```php
    public function getParticipantDetailsAttribute()
    {
        return [
            'name' => $this->someValue,
            'foo' => 'bar',
        ];
    }
```

#### Creating a conversation
You can start a conversation by passing an array of Models as participants

```php
$participants = [$model1, $model2,..., $modelN];

$conversation = Chat::createConversation($participants);
```

#### Creating a conversation of type private / public
You may want to classify conversations as private or public

```php
$participants = [$model1, $model2,..., $modelN];

// Create a private conversation
$conversation = Chat::createConversation($participants)->makePrivate();

// Create a public conversation
$conversation = Chat::createConversation($participants)->makePrivate(false);

// Create a direct message

// Make direct conversation after creation
$conversation = Chat::createConversation($participants)->makeDirect();

// Specify intent for direct conversation before creation
$conversation = Chat::makeDirect()->createConversation($participants);
```

> **Note:** You will not be able to add additional participants to a direct conversation. 
Additionally you can't remove a participant from a direct conversation.

#### Get a conversation by id
```php
$conversation = Chat::conversations()->getById($id);
```

#### Update conversation details

```php
$data = ['title' => 'PHP Channel', 'description' => 'PHP Channel Description'];
$conversation->update(['data' => $data]);
```

#### Send a text message

```php
$message = Chat::message('Hello')
            ->from($model)
            ->to($conversation)
            ->send();
```
#### Send a message of custom type

The default message type is `text`. If you want to specify custom type you can call the `type()` function as below:

```php
$message = Chat::message('http://example.com/img')
		->type('image')
		->from($model)
		->to($conversation)
		->send();
```
#### To add more details about a message

Sometimes you might want to add details about a message. For example, when the message type is an attachment, and you want to add details such as attachment's filename, and attachment's file url, you can call the `data()` function and pass your data as an array.

```php
$message = Chat::message('Attachment 1')
		->type('attachment')
		->data(['file_name' => 'post_image.jpg', 'file_url' => 'http://example.com/post_img.jpg'])
		->from($model)
		->to($conversation)
		->send();
```

### Get a message by id

```php
$message = Chat::messages()->getById($id);
```

### Get message sender

```php
$sendModel = $message->sender;
```

#### Mark a message as read

```php
Chat::message($message)->setParticipant($participantModel)->markRead();
```

#### Flag / mark a message

```php
Chat::message($message)->setParticipant($participantModel)->toggleFlag();

Chat::message($message)->setParticipant($participantModel)->flagged(); // true
```

#### Mark whole conversation as read

```php
Chat::conversation($conversation)->setParticipant($participantModel)->readAll();
```

#### Unread messages count

```php
$unreadCount = Chat::messages()->setParticipant($participantModel)->unreadCount();
```

#### Unread messages count per Conversation

```php
Chat::conversation($conversation)->setParticipant($participantModel)->unreadCount();
```

#### Delete a message

```php
Chat::message($message)->setParticipant($participantModel)->delete();
```

#### Cleanup Deleted Messages

What to cleanup when all participants have deleted a `$message` or `$conversation`?

Listen for `\Musonza\Chat\Eventing\AllParticipantsDeletedMessage` and 

`\Musonza\Chat\Eventing\AllParticipantsClearedConversation`

#### Clear a conversation

```php
Chat::conversation($conversation)->setParticipant($participantModel)->clear();
```

#### Get participant conversations

```php
Chat::conversations()->setPaginationParams(['sorting' => 'desc'])
	->setParticipant($participantModel)
	->limit(1)
	->page(1)
	->get();
```

#### Get a conversation between two participants

```php
$conversation = Chat::conversations()->between($participantModel1, $participantModel2);
```

#### Get common conversations among participants

```php
$conversations = Chat::conversations()->common($participants);
```
`$participants` is an array of participant Models

#### Remove participants from a conversation

```php
/* removing one user */
Chat::conversation($conversation)->removeParticipants([$participantModel]);
```

```php
/* removing multiple participants */
Chat::conversation($conversation)->removeParticipants([$participantModel, $participantModel2,...,$participantModelN]);
```

#### Add participants to a conversation

```php
/* add one user */
Chat::conversation($conversation)->addParticipants([$participantModel]);
```

```php
/* add multiple participants */
Chat::conversation($conversation)->addParticipants([$participantModel, $participantModel2]);
```

#### Get messages in a conversation

```php
Chat::conversation($conversation)->setParticipant($participantModel)->getMessages()
```

#### Get messages with cursor pagination

For real-time chat applications, cursor-based pagination is recommended over offset-based pagination. This prevents duplicate messages when new messages arrive between page loads.

```php
// Get first page
$messages = Chat::conversation($conversation)
    ->setParticipant($participantModel)
    ->setCursorPaginationParams([
        'perPage' => 25,
        'sorting' => 'asc',
    ])
    ->getMessagesWithCursor();

// Get next page using cursor from previous response
$nextCursor = $messages->nextCursor()?->encode();

$moreMessages = Chat::conversation($conversation)
    ->setParticipant($participantModel)
    ->setCursorPaginationParams([
        'perPage' => 25,
        'sorting' => 'asc',
        'cursor' => $nextCursor,
    ])
    ->getMessagesWithCursor();
```

**API Endpoint:** `GET /conversations/{id}/messages-cursor`

Query parameters:
- `participant_id` (required)
- `participant_type` (required)
- `perPage` (optional, default: 25)
- `sorting` (optional: `asc` or `desc`)
- `cursor` (optional - from previous response's `next_cursor`)

The response includes `next_cursor` and `prev_cursor` for navigation.

#### Get user conversations by type

```php
// private conversations
$conversations = Chat::conversations()->setParticipant($participantModel)->isPrivate()->get();

// public conversations
$conversations = Chat::conversations()->setParticipant($participantModel)->isPrivate(false)->get();

// direct conversations / messages
$conversations = Chat::conversations()->setParticipant($participantModel)->isDirect()->get();

// all conversations
$conversations = Chat::conversations()->setParticipant($participantModel)->get();
```

#### Get public conversations for discovery

You can list public conversations without being a participant. This is useful for building discovery pages where users can browse and join public chat rooms (similar to Telegram public groups).

```php
// Get all public conversations (no participant required)
$conversations = Chat::conversations()
    ->isPrivate(false)
    ->limit(10)
    ->page(1)
    ->get();
```

> **Note:** This only works for public conversations. Attempting to list private conversations without setting a participant will throw an `InvalidConversationListException`.

#### Get recent messages

```php
$messages = Chat::conversations()->setParticipant($participantModel)->limit(25)->page(1)->get();
```

#### Pagination

There are a few ways you can achieve pagination
You can specify the `limit` and `page` as above using the respective functions or as below:
```
   $paginated = Chat::conversations()->setParticipant($participant)
            ->setPaginationParams([
                'page' => 3,
                'perPage' => 10,
                'sorting' => "desc",
                'columns' => [
                    '*'
                ],
                'pageName' => 'test'
            ])
            ->get();
```
You don't have to specify all the parameters. If you leave the parameters out, default values will be used.
`$paginated` above will return `Illuminate\Pagination\LengthAwarePaginator`
To get the `conversations` simply call `$paginated->items()`

> **Tip:** For paginating messages in real-time chat applications, consider using [cursor pagination](#get-messages-with-cursor-pagination) instead. Cursor pagination prevents duplicate messages when new messages arrive between page loads.


#### Get participants in a conversation

```php
$participants = $conversation->getParticipants();
```

#### Get participation entry for a Model in a conversation

```php
Chat::conversation($conversation)->getParticipation($model);
```

#### Update participation settings

Set Conversation settings for participant (example: mute_mentions, mute_conversation)

```php
$settings = ['mute_mentions' => true];

Chat::conversation($conversation)
    ->getParticipation($this->alpha)
    ->update(['settings' => $settings]);
```

#### Data Transformers

Need to have more control on the data returned from the package routes? You can 
specify your own Model transformers and take advantage of [Fractal](http://fractal.thephpleague.com/).

All you need to do is specify the location of your transformers in the configuration
file `musonza_chat.php` as follows:

```php
/**
 * Model Transformers
 */
'transformers' => [
    'conversation' => \MyApp\Transformers\ConversationTransformer::class,
    'message' => \MyApp\Transformers\MessageTransformer::class,
    'participant' => \MyApp\Transformers\ParticipantTransformer::class,
]
```
> **Note:** This only applies to responses from package routes.

#### Message Encryption

You can optionally encrypt message bodies at rest using Laravel's built-in encryption. When enabled, messages are encrypted using AES-256-CBC via Laravel's `Crypt` facade.

To enable encryption, set the `encrypt_messages` option in your `musonza_chat.php` config file:

```php
'encrypt_messages' => true,
```

**How it works:**

- New messages are automatically encrypted before being stored in the database
- Messages are automatically decrypted when retrieved via the model
- Existing unencrypted messages remain readable (hybrid mode)
- An `is_encrypted` column tracks whether each message is encrypted

```php
// Sending works the same - encryption is transparent
$message = Chat::message('Secret message')
    ->from($user)
    ->to($conversation)
    ->send();

// Reading works the same - decryption is automatic
echo $message->body; // "Secret message"
```

> **Note:** Encryption uses your application's `APP_KEY`. If you change your `APP_KEY`, previously encrypted messages will become unreadable.

## License

Chat is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)



