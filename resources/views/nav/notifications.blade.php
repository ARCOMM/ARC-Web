@php
    $notifications = auth()->user()->unreadNotifications->filter(function($notification) {
        return $notification->type::exists($notification);
    });
@endphp

<li id="nav-notifications" class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-bell"></i>
        @unless ($notifications->isEmpty())
            <span class="notifications-has-unread pulse pulse-info"></span>
        @endunless
    </a>

    <div class="dropdown-menu" style="width: 25rem;max-height: 40rem;overflow-y: auto;max-width: calc(100vw - 3rem);">
        @include('nav.notifications-list', ['notifications' => $notifications])
    </div>
</li>
