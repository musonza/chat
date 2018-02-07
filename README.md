[![Downloads](https://img.shields.io/packagist/dt/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
[![Packagist](https://img.shields.io/packagist/v/musonza/chat.svg)](https://packagist.org/packages/musonza/chat)
[![StyleCI](https://styleci.io/repos/54214978/shield?branch=master)](https://styleci.io/repos/54214978)
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

This package allows you to add a chat system to your Laravel ^5.3 application

> **Note:** If you are using a Laravel version less than 5.3 [install the release on this branch instead](https://github.com/musonza/chat/tree/1.0).

## Installation

From the command line, run:

```
composer require musonza/chat
```

Add the service provider to your `config\app.php` the providers array

```
Musonza\Chat\ChatServiceProvider
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

> **Note:** This package takes advantage of Laravel Notifications. 
If you have already setup Laravel notifications you can delete the `2017_07_12_034227_create_notifications_table.php` migration file.

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

    /**
     * If set to true, this will use Laravel notifications table to store each
     * user message notification.
     * Otherwise it will use mc_message_notification table.
     * If your database doesn't support JSON columns you will need to set this to false.
     */
    'laravel_notifications' => true,
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
$conversation = Chat::conversation($conversation_id);
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
$message = Chat::messageById($id);
```


#### Mark a message as read

```php
Chat::messages($message)->for($user)->markRead();
```

#### Mark whole conversation as read

```php
Chat::conversations($conversation)->for($user)->readAll();
```	

#### Unread messages count

```php
$unreadCount = Chat::for($user)->unreadCount();
```

#### Delete a message

```php
Chat::messages($message)->for($user)->delete();
```

#### Clear a conversation

```php
Chat::conversations($conversation)->for($user)->clear();
```

#### Get a conversation between two users

```php
Chat::getConversationBetween($user1, $user2);
```

#### Get common conversations among users

```php
$conversations = Chat::commonConversations($users);
```
`$users` can be an array of user ids ex. `[1,4,6]` or a collection `(\Illuminate\Database\Eloquent\Collection)` of users

#### Remove users from a conversation

```php
/* removing one user */
Chat::removeParticipants($conversation, $user);
```

```php
/* removing multiple users */
Chat::removeParticipants($conversation, [$user1, $user2, $user3,...,$userN]);
```

#### Add users to a conversation

```php
/* add one user */
Chat::addParticipants($conversation, $user); 
```

```php
/* add multiple users */
Chat::addParticipants($conversation, [$user3, $user4]);
```

<b>Note:</b> A third user will classify the conversation as not private if it was.


#### Get messages in a conversation

```php
Chat::conversations($conversation)->for($user)->getMessages($perPage, $page)
```

#### Get recent messages 

```php
$messages = Chat::conversations()->for($user)->limit(25)->page(1)->get();
```

#### Get users in a conversation

```php
$users = $conversation->users;
```

## License

Chat is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)



