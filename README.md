<p align="left"><img src="menu.png" alt="chat" width="130px"></p>

[![Build Status](https://travis-ci.org/musonza/chat.svg?branch=master)](https://travis-ci.org/musonza/chat)
[![Downloads](https://poser.pugx.org/musonza/chat/d/total.svg)](https://packagist.org/packages/musonza/chat)
[![Packagist](https://img.shields.io/packagist/v/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
## Chat

[Demo Application](https://github.com/musonza/chat-demo)

- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Creating a conversation](#creating-a-conversation)
  - [Get a conversation by Id](#get-a-conversation-by-id)
  - [Update conversation details](#update-conversation-details)
  - [Send a text message](#send-a-text-message)
  - [Send a message of custom type](#send-a-message-of-custom-type)
  - [Get a message by id](#get-a-message-by-id)
  - [Mark a message as read](#mark-a-message-as-read)
  - [Mark whole conversation as read](#mark-whole-conversation-as-read)
  - [Unread messages count](#unread-messages-count)
  - [Delete a message](#delete-a-message)
  - [Clear a conversation](#clear-a-conversation)
  - [Get a conversation between two users](#get-a-conversation-between-two-users)
  - [Get common conversations among users](#get-common-conversations-among-users)
  - [Remove users from a conversation](#remove-users-from-a-conversation)
  - [Add users to a conversation](#add-users-to-a-conversation)
  - [Get messages in a conversation](#get-messages-in-a-conversation)
  - [Get recent messages](#get-recent-messages)
  - [Get users in a conversation](#get-users-in-a-conversation)
- [License](#license)

## Introduction

This package allows you to add a chat system to your Laravel ^5.4 application

## Installation

From the command line, run:

```
composer require musonza/chat
```

Add the service provider to your `config\app.php` the providers array

```
Musonza\Chat\ChatServiceProvider::class
```

Add the Facade to your aliases:

```
'Chat' => Musonza\Chat\Facades\ChatFacade::class to your `config\app.php`
```

The class is bound to the ioC as chat

```
$chat = App::make('chat');
```

Publish the assets:

```
php artisan vendor:publish
```

This will publish database migrations and a configuration file `musonza_chat.php` in the Laravel config folder.

## Configuration

```php
return [
    /*
     * This will allow you to broadcast an event when a message is sent
     * Example:
     * Channel: mc-chat-conversation.2,
     * Event: Musonza\Chat\Eventing\MessageWasSent
     */
    'broadcasts' => false,

    /**
     * The event to fire when a message is sent
     * See Musonza\Chat\Eventing\MessageWasSent if you want to customize.
     */
    'sent_message_event' => 'Musonza\Chat\Eventing\MessageWasSent',

    /**
     * Automatically convert conversations with more than two users to public
     */
    'make_three_or_more_users_public' => true,
];


```

Run the migrations:

```
php artisan migrate
```

## Usage

By default the package assumes you have a User model in the App namespace.

However, you can update the user model in `musonza_chat.php` published in the `config` folder.

#### Creating a conversation
```php
$participants = [$model1, $model2,...];

$conversation = Chat::createConversation($participants);
```

#### Creating a conversation of type private / public
```php
$participants = [$model1, $model2,...];

// Create a private conversation
$conversation = Chat::createConversation($participants)->makePrivate();

// Create a public conversation
$conversation = Chat::createConversation($participants)->makePrivate(false);
```

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
            ->from($model1)
            ->to($conversation)
            ->send();
```
#### Send a message of custom type

The default message type is `text`. If you want to specify custom type you can call the `type()` function as below:

```php
$message = Chat::message('http://example.com/img')
		->type('image')
		->from($model1)
		->to($conversation)
		->send();
```

### Get a message by id

```php
$message = Chat::messages()->getById($id);
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

#### Get a conversation between two users

```php
$conversation = Chat::conversations()->between($participantModel1, $participantModel2);
```

#### Get common conversations among users

```php
$conversations = Chat::conversations()->common($participants);
```
`$participants` is an array of participant Models

#### Remove users from a conversation

```php
/* removing one user */
Chat::conversation($conversation)->removeParticipants([$participantModel]);
```

```php
/* removing multiple users */
Chat::conversation($conversation)->removeParticipants([$participantModel, $participantModel2,...,$participantModelN]);
```

#### Add users to a conversation

```php
/* add one user */
Chat::conversation($conversation)->addParticipants([$participantModel]);
```

```php
/* add multiple users */
Chat::conversation($conversation)->addParticipants([$participantModel, $participantModel2]);
```

<b>Note:</b> By default, a third participant will classify the conversation as not private if it was. See config on how to change this.


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

// all conversations
$conversations = Chat::conversations()->setParticipant($participantModel)->get();
```

#### Get recent messages

```php
$messages = Chat::conversations()->setParticipant($participantModel)->limit(25)->page(1)->get();
```

Example

```
[
      "id" => 1
      "private" => "1"
      "data" => []
      "created_at" => "2018-06-02 21:35:52"
      "updated_at" => "2018-06-02 21:35:52"
      "last_message" => array:13 [
        "id" => 2
        "message_id" => "2"
        "conversation_id" => "1"
        "user_id" => "1"
        "is_seen" => "1"
        "is_sender" => "1"
        "flagged" => false
        "created_at" => "2018-06-02 21:35:52"
        "updated_at" => "2018-06-02 21:35:52"
        "deleted_at" => null
        "body" => "Hello 2"
        "type" => "text"
        "sender" => array:7 [
          "id" => 1
          "name" => "Jalyn Ernser"
          "email" => "colt.howell@example.com"
        ]
      ]
    ]
```

#### Pagination

There are a few ways you can achieve pagination
You can specify the `limit` and `page` as above using the respective functions or as below:
```
   $paginated = Chat::conversations()->setParticipant($user)
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


#### Get users in a conversation

```php
$users = $conversation->users;
```

## License

Chat is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)



