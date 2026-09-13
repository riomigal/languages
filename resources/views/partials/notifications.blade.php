<aside id="notifications" data-url="{{ route('languages.notifications') }}" data-read-all-url="{{ route('languages.notifications.read-all') }}" class="fixed bottom-4 right-4 z-40">
    <button type="button" data-toggle="notification-list" aria-controls="notification-list" aria-expanded="false" class="text-white bg-blue-700 rounded-lg px-4 py-2">Notifications (<span data-count>0</span>)</button>
    <div id="notification-list" hidden class="p-4 rounded-lg shadow bg-white dark:bg-gray-800 w-80 max-h-80 overflow-y-auto">
        <button type="button" data-read-all class="text-blue-700 dark:text-blue-300 p-2">Mark all as read</button>
        <ul data-notification-items class="space-y-3"></ul>
    </div>
</aside>
