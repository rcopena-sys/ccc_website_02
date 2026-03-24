<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in and has appropriate role (1=admin, 2=dean, 3=registrar)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Debug: Check user info
echo "<h2>User Info</h2>";
echo "User ID: $user_id<br>";
echo "Role ID: $role_id<br><br>";

// Debug: Check messages in messages_db
echo "<h2>Messages in messages_db</h2>";
$query = "SELECT m.*, s.firstname, s.lastname, s.role_id as sender_role_id 
          FROM messages_db m 
          LEFT JOIN signin_db s ON m.sender_id = s.id
          WHERE m.recipient_type = 'registrar' 
             OR m.recipient_type = 'all' 
             OR m.recipient_id = ?
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Subject</th><th>Sender</th><th>Recipient Type</th><th>Recipient ID</th><th>Status</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname'] . ' (Role: ' . $row['sender_role_id'] . ')') . "</td>";
        echo "<td>" . htmlspecialchars($row['recipient_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['recipient_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status'] ?? 'unread') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No messages found in messages_db for this user.<br><br>";
}
$stmt->close();

// Debug: Check notifications in notifications_db
echo "<h2>Notifications in notifications_db</h2>";
$query = "SELECT n.*, s.firstname, s.lastname 
          FROM notifications_db n 
          LEFT JOIN signin_db s ON n.sender_id = s.id 
          WHERE n.role_id = 3 OR n.user_id = ?
          ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Role ID</th><th>User ID</th><th>Is Read</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
        echo "<td>" . $row['role_id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No notifications found in notifications_db for this user.<br>";
}
$stmt->close();

// Debug: Check signin_db for user details
$query = "SELECT * FROM signin_db WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo "<h2>User Details</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}
$stmt->close();

$conn->close();
?>

<h2>Debug Info</h2>
<pre>
Session Data:
<?php print_r($_SESSION); ?>

POST Data:
<?php print_r($_POST); ?>

GET Data:
<?php print_r($_GET); ?>
</pre>
