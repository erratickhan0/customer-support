<?php

namespace App\Services\Inbox;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InboxConversationService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function listForUser(User $user, array $filters): LengthAwarePaginator
    {
        if (! $user->agency) {
            return Conversation::query()->whereRaw('1 = 0')->paginate(1);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return Conversation::query()
            ->whereBelongsTo($user->agency)
            ->when(isset($filters['status']), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(isset($filters['q']) && $filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($subQuery) use ($filters): void {
                    $subQuery->where('session_id', 'like', '%'.$filters['q'].'%')
                        ->orWhereHas('messages', function ($messageQuery) use ($filters): void {
                            $messageQuery->where('content', 'like', '%'.$filters['q'].'%');
                        });
                });
            })
            ->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }
}
