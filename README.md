<p align="left"><img src="menu.png" alt="chat" width="160px"></p>

[![Build Status](https://travis-ci.org/musonza/chat.svg?branch=master)](https://travis-ci.org/musonza/chat)
[![Downloads](https://img.shields.io/packagist/dt/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
[![Packagist](https://img.shields.io/packagist/v/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
## Chat

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
[
    'user_model'            => 'App\User',

    /**
     * This will allow you to broadcast an event when a message is sent
     * Example:
     * Channel: private-mc-chat-conversation.2,
     * Event: Musonza\Chat\Messages\MessageWasSent
     */
    'broadcasts'            => false,
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
$participants = [$userId, $userId2,...];

$conversation = Chat::createConversation($participants);
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
            ->from($user)
            ->to($conversation)
            ->send();
```
#### Send a message of custom type

The default message type is `text`. If you want to specify custom type you can call the `type()` function as below:

```php
$message = Chat::message('http://example.com/img')
		->type('image')
		->from($user)
		->to($conversation)
		->send();
```

### Get a message by id

```php
$message = Chat::messages()->getById($id);
```


#### Mark a message as read

```php
Chat::message($message)->for($user)->markRead();
```

#### Mark whole conversation as read

```php
Chat::conversation($conversation)->for($user)->readAll();
```

#### Unread messages count

```php
$unreadCount = Chat::messages()->for($user)->unreadCount();
```

#### Delete a message

```php
Chat::message($message)->for($user)->delete();
```

#### Clear a conversation

```php
Chat::conversation($conversation)->for($user)->clear();
```

#### Get a conversation between two users

```php
$conversation = Chat::conversations()->between($user1, $user2);
```

#### Get common conversations among users

```php
$conversations = Chat::conversations()->common($users);
```
`$users` can be an array of user ids ex. `[1,4,6]` or a collection `(\Illuminate\Database\Eloquent\Collection)` of users

#### Remove users from a conversation

```php
/* removing one user */
Chat::conversation($conversation)->removeParticipants($user);
```

```php
/* removing multiple users */
Chat::conversation($conversation)->removeParticipants([$user1, $user2, $user3,...,$userN]);
```

#### Add users to a conversation

```php
/* add one user */
Chat::conversation($conversation)->addParticipants($user);
```

```php
/* add multiple users */
Chat::conversation($conversation)->addParticipants([$user3, $user4]);
```

<b>Note:</b> A third user will classify the conversation as not private if it was.


#### Get messages in a conversation

```php
Chat::conversation($conversation)->for($user)->getMessages()
```

#### Get recent messages

```php
$messages = Chat::conversations()->for($user)->limit(25)->page(1)->get();
```

#### Pagination

There are a few ways you can achieve pagination
You can specify the `limit` and `page` as above using the respective functions or as below:
```
   $paginated = Chat::conversations()->for($user)
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



