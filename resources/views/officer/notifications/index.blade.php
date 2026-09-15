<x-dashboard-layout
    title="Notifications"
    subtitle="Claims awaiting verification and other office updates"
    active="notifications"
>
    @if ($notifications->isEmpty())
        <div class="empty-state">
            <i class="ti ti-bell-off"></i>
            No notifications yet. We'll let you know when a claim needs verifying.
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
                        <form method="POST" action="{{ route('officer.notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="padding:6px 12px;font-size:10px;">Mark read</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-dashboard-layout>
