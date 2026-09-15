<x-dashboard-layout
    title="Notifications"
    subtitle="Updates about matches and claims"
    active="notifications"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    @if ($unreadCount > 0)
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
            <span style="font-size:12px; color:var(--muted);">
                <i class="ti ti-mail"></i>
                {{ $unreadCount }} unread {{ Str::plural('notification', $unreadCount) }}
            </span>
            <form method="POST" action="{{ route('student.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">
                    <i class="ti ti-checks"></i> Mark all as read
                </button>
            </form>
        </div>
    @endif

    @if ($notifications->isEmpty())
        <div class="empty-state">
            <i class="ti ti-bell-off"></i>
            No notifications yet. We'll let you know when something happens with your reports or claims.
        </div>
    @else
        <div class="notifications-list">
            @foreach ($notifications as $notification)
                <div class="notif-card {{ $notification->isUnread() ? 'unread' : '' }}">
                    <div class="notif-icon">
                        <i class="ti {{ $notification->icon() }}"></i>
                    </div>
                    <div class="notif-content">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->message }}</p>
                        <div class="notif-time">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    @if ($notification->isUnread())
                        <form method="POST" action="{{ route('student.notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:10px;">Mark read</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-dashboard-layout>
