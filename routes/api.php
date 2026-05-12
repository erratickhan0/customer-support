<?php

use App\Http\Controllers\Api\AgentMessageController;
use App\Http\Controllers\Api\InboxConversationController;
use App\Http\Controllers\Api\KnowledgeDocumentController;
use App\Http\Controllers\Api\WidgetConversationController;
use App\Http\Controllers\Api\WidgetMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::post('widget/messages', WidgetMessageController::class)->name('api.widget.messages.store');
    Route::get('widget/conversations/{conversation}', WidgetConversationController::class)->name('api.widget.conversations.show');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('inbox')->group(function () {
    Route::get('conversations', [InboxConversationController::class, 'index'])->name('api.inbox.conversations.index');
    Route::get('conversations/{conversation}', [InboxConversationController::class, 'show'])->name('api.inbox.conversations.show');
    Route::post('conversations/{conversation}/messages', [AgentMessageController::class, 'store'])->name('api.inbox.conversations.messages.store');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('knowledge')->group(function () {
    Route::get('documents', [KnowledgeDocumentController::class, 'index'])->name('api.knowledge.documents.index');
    Route::post('documents', [KnowledgeDocumentController::class, 'store'])->name('api.knowledge.documents.store');
});
