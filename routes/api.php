<?php

use App\Http\Controllers\Api\AgentMessageController;
use App\Http\Controllers\Api\InboxConversationController;
use App\Http\Controllers\Api\WidgetMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('widget/messages', WidgetMessageController::class)->name('api.widget.messages.store');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('inbox')->group(function () {
    Route::get('conversations', [InboxConversationController::class, 'index'])->name('api.inbox.conversations.index');
    Route::get('conversations/{conversation}', [InboxConversationController::class, 'show'])->name('api.inbox.conversations.show');
    Route::post('conversations/{conversation}/messages', [AgentMessageController::class, 'store'])->name('api.inbox.conversations.messages.store');
});
