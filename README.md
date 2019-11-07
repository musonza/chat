<p align="left"><img src="menu.png" alt="chat" width="130px"></p>

[![Build Status](https://travis-ci.org/musonza/chat.svg?branch=master)](https://travis-ci.org/musonza/chat)
[![Downloads](https://poser.pugx.org/musonza/chat/d/total.svg)](https://packagist.org/packages/musonza/chat)
[![Packagist](https://img.shields.io/packagist/v/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
## Chat

Create a Chat application for your multiple Models

## Table of Contents

<details><summary>Click to expand</summary><p>

- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Adding the ability to participate to a Model](#Adding-the-ability-to-participate-to-a-Model)
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
  - [Clear a conversation](#clear-a-conversation)
  - [Get participant conversations](#Get-participant-conversations)
  - [Get a conversation between two participants](#get-a-conversation-between-two-participants)
  - [Get common conversations among participants](#get-common-conversations-among-participants)
  - [Remove participants from a conversation](#remove-participants-from-a-conversation)
  - [Add participants to a conversation](#add-participants-to-a-conversation)
  - [Get messages in a conversation](#get-messages-in-a-conversation)
  - [Get recent messages](#get-recent-messages)
  - [Get participants in a conversation](#get-participants-in-a-conversation)
  - [Get participation entry for a Model in a conversation](#Get-participation-entry-for-a-Model-in-a-conversation)
  - [Update participation settings](#Update-participation-settings)
  - [Data Transformers](#Data-Transformers)
- [License](#license)

</details>

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
php artisan vendor:publish
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


#### Get participants in a conversation

```php
$participants = $conversation->getParticipants();
```

#### Get participation entry for a Model in a conversation

```php
Chat::conversation($conversation)->setParticipant($model)->getParticipation();
```

#### Update participation settings

Set Conversation settings for participant (example: mute_mentions, mute_conversation)

```php
$settings = ['mute_mentions' => true];

Chat::conversation($conversation)
    ->setParticipant($this->alpha)
    ->getParticipation()
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

## License

Chat is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)



