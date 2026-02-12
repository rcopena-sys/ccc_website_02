class FeedbackNotifier {
    constructor() {
        this.notificationBell = null;
        this.notificationCount = null;
        this.notificationDropdown = null;
        this.pollingInterval = 30000; // 30 seconds
        this.initialize();
    }

    initialize() {
        this.createNotificationBell();
        this.setupEventListeners();
        this.checkForNewFeedback();
        setInterval(() => this.checkForNewFeedback(), this.pollingInterval);
    }

    createNotificationBell() {
        // Create notification bell container
        const container = document.createElement('div');
        container.className = 'fixed top-4 right-20 z-50';
        container.innerHTML = `
            <div class="relative">
                <button id="feedbackBell" class="bg-white p-2 rounded-full shadow-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span id="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                </button>
                <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
                    <div class="p-3 bg-blue-600 text-white font-semibold">
                        Feedback Notifications
                    </div>
                    <div id="notificationList" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500">No new feedback</div>
                    </div>
                    <a href="feedbackr.php" class="block text-center py-2 bg-gray-100 text-blue-600 font-medium hover:bg-gray-200">
                        View All Feedback
                    </a>
                </div>
            </div>
        `;

        // Insert the notification bell into the header
        const header = document.querySelector('header');
        if (header) {
            header.appendChild(container);
        } else {
            document.body.prepend(container);
        }

        this.notificationBell = document.getElementById('feedbackBell');
        this.notificationCount = document.getElementById('notificationCount');
        this.notificationDropdown = document.getElementById('notificationDropdown');
    }

    setupEventListeners() {
        // Toggle dropdown when clicking the bell
        this.notificationBell?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.notificationDropdown.classList.toggle('hidden');
            
            // Mark all as read when opening the dropdown
            if (!this.notificationDropdown.classList.contains('hidden')) {
                this.markAllAsRead();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.notificationBell.contains(e.target) && !this.notificationDropdown.contains(e.target)) {
                this.notificationDropdown.classList.add('hidden');
            }
        });
    }

    async checkForNewFeedback() {
        try {
            const response = await fetch('get_unread_feedback.php');
            const data = await response.json();

            if (data.success) {
                this.updateNotificationUI(data);
            }
        } catch (error) {
            console.error('Error checking for new feedback:', error);
        }
    }

    updateNotificationUI(data) {
        const { count, feedback } = data;
        const notificationList = document.getElementById('notificationList');
        
        // Update count badge
        if (count > 0) {
            this.notificationCount.textContent = count > 9 ? '9+' : count;
            this.notificationCount.classList.remove('hidden');
        } else {
            this.notificationCount.classList.add('hidden');
        }

        // Update dropdown content
        if (feedback && feedback.length > 0) {
            notificationList.innerHTML = feedback.map(item => `
                <div class="p-3 border-b border-gray-100 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="font-medium text-gray-800">New Feedback</div>
                        <div class="text-xs text-gray-500">${this.formatTimeAgo(item.created_at)}</div>
                    </div>
                    <div class="text-sm text-gray-600 mt-1 truncate">${item.email}</div>
                    <div class="text-sm text-gray-700 mt-1 line-clamp-2">${this.truncateMessage(item.message)}</div>
                </div>
            `).join('');
        } else {
            notificationList.innerHTML = '<div class="p-4 text-center text-gray-500">No new feedback</div>';
        }
    }

    async markAllAsRead() {
        try {
            // Mark all as read in the UI
            this.notificationCount.classList.add('hidden');
            
            // Update server
            const response = await fetch('mark_all_feedback_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mark_all: true })
            });
            
            const data = await response.json();
            if (!data.success) {
                console.error('Failed to mark feedback as read:', data.error);
            }
        } catch (error) {
            console.error('Error marking feedback as read:', error);
        }
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
            }
        }
        
        return 'just now';
    }

    truncateMessage(message, maxLength = 100) {
        if (message.length <= maxLength) return message;
        return message.substring(0, maxLength) + '...';
    }
}

// Initialize the notifier when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new FeedbackNotifier();
});
