<?php

$routesEnabled = config('musonza_chat.routes.enabled');

if (!$routesEnabled) {
    throw new Exception('Routes are not enabled. Check config: musonza_chat.routes.enabled');
}

$chatRoutesPrefix = config('musonza_chat.routes.path_prefix');
$middleware = config('musonza_chat.routes.middleware');

Route::group([
    'middleware' => $middleware,
    'namespace'  => 'Musonza\Chat\Http\Controllers',
    'prefix'     => $chatRoutesPrefix,
], function () use ($chatRoutesPrefix) {
    /* Conversation */
    Route::post('/conversations', 'ConversationController@store')->name('conversations.store');
    Route::get('/conversations/{id}', 'ConversationController@show')->name('conversations.show');
    Route::put('/conversations/{id}', 'ConversationController@update')->name('conversations.update');
    Route::delete('/conversations/{id}', 'ConversationController@destroy')->name('conversations.destroy');

    /* Conversation Participation */
    Route::post('/conversations/{id}/participation', 'ConversationParticipationController@store')
        ->name('conversations.participation.store');
    Route::delete('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@destroy')
        ->name('conversations.participation.destroy');
    Route::get('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@show')
        ->name('conversations.participation.show');
    Route::put('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@update')
        ->name('conversations.participation.update');
    Route::get('/conversations/{id}/participants', 'ConversationParticipationController@index')
        ->name('conversations.participation.index');

    /* Messaging */
    Route::post('/conversations/{id}/messages', 'ConversationMessageController@store')
        ->name('conversations.messages.store');
    Route::get('/conversations/{id}/messages', 'ConversationMessageController@index')
        ->name('conversations.messages.index');
    Route::get('/conversations/{id}participants', 'ConversationParticipationController@index');
    Route::get('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@show')
        ->name('conversations.participation.show');
    Route::put('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@update')
        ->name('conversations.participation.update');
});
