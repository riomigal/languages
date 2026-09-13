<aside id="notifications" data-url="{{ route('languages.notifications') }}" data-read-all-url="{{ route('languages.notifications.read-all') }}" class="fixed bottom-4 right-4 z-40">
    <div class="dropdown dropdown-top dropdown-end dropdown-open">
        <button type="button" data-toggle="notification-list" aria-controls="notification-list" aria-expanded="false" class="btn btn-primary">Notifications (<span data-count>0</span>)</button>
        <div id="notification-list" hidden class="dropdown-content bg-base-100 rounded-box shadow-lg p-4 w-80 max-w-[calc(100vw-2rem)] max-h-80 overflow-y-auto mb-2">
            <button type="button" data-read-all class="btn btn-ghost btn-sm text-primary">Mark all as read</button>
            <ul data-notification-items class="space-y-3"></ul>
        </div>
    </div>
</aside>
