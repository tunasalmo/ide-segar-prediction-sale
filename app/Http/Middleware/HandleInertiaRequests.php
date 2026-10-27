<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'unreadNotifications' => $request->user()
                    ? AppNotification::where('user_id', $request->user()->id)->unread()->count()
                    : 0,
                'pendingOrders' => \App\Models\Order::where('status', 'menunggu_konfirmasi')->count(),
            ],
            'notifications' => $request->user()
                ? AppNotification::where('user_id', $request->user()->id)
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'type' => $n->type,
                        'title' => $n->title,
                        'body' => $n->body,
                        'link' => $n->link,
                        'read_at' => $n->read_at?->format('d M Y H:i'),
                    ])
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
