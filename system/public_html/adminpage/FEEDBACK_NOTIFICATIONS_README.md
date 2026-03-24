# Feedback Notification System

This system adds a notification bell to the admin dashboard that shows new feedback submissions from students.

## Features

- Real-time notification badge showing unread feedback count
- Dropdown menu showing latest feedback submissions
- Mark all as read functionality
- Dedicated feedback management page

## Files Added

- `js/feedback-notifications.js` - Handles the notification bell UI and AJAX requests
- `get_unread_feedback.php` - API endpoint to fetch unread feedback
- `mark_feedback_read.php` - API endpoint to mark feedback as read
- `mark_all_feedback_read.php` - API endpoint to mark all feedback as read
- `view_feedback.php` - Page to view all feedback submissions

## Database Changes

Run the following SQL to update your database schema:

```sql
-- Add is_read column to feedback_db table if it doesn't exist
ALTER TABLE `feedback_db` 
ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD INDEX `idx_is_read` (`is_read`);

-- Update existing records to be marked as read
UPDATE `feedback_db` SET `is_read` = 1 WHERE `is_read` = 0;
```

## How It Works

1. The notification bell in the admin dashboard checks for new feedback every 30 seconds
2. When new feedback is submitted by a student, the admin will see a notification badge
3. Clicking the bell shows a dropdown with the latest feedback items
4. Clicking "View All Feedback" takes the admin to a dedicated feedback management page
5. Feedback is automatically marked as read when the admin views it

## Customization

You can customize the following in `js/feedback-notifications.js`:

- `pollingInterval` - Change how often to check for new feedback (in milliseconds)
- Notification styles in the `createNotificationBell` method
- The number of latest feedback items to show in the dropdown (currently set to 5)

## Troubleshooting

- If notifications aren't working, check the browser console for JavaScript errors
- Ensure the database tables and columns were created correctly
- Verify that the PHP files have the correct database credentials
- Check file permissions for the JavaScript and PHP files
