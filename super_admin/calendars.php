<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ccc_curriculum_evaluation";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Check if it's an AJAX request for events
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $sql = "SELECT * FROM calendar";
    $result = $conn->query($sql);
    $events = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = array(
                'id' => $row['id'],
                'title' => $row['title'],
                'date' => $row['event_date'],
                'description' => $row['description']
            );
        }
    }
    header('Content-Type: application/json');
    echo json_encode($events);
    exit();
}

// Handle event creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_event') {
    $title = $conn->real_escape_string($_POST['title']);
    $date = $conn->real_escape_string($_POST['date']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "INSERT INTO calendar (title, event_date, description) VALUES ('$title', '$date', '$description')";
    
    $response = array();
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = "Event created successfully";
        $response['eventId'] = $conn->insert_id;
    } else {
        $response['success'] = false;
        $response['message'] = "Error: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle event deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_event') {
    $eventId = $conn->real_escape_string($_POST['eventId']);
    
    $sql = "DELETE FROM calendar WHERE id = '$eventId'";
    
    $response = array();
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = "Event deleted successfully";
    } else {
        $response['success'] = false;
        $response['message'] = "Error: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Initial events load
$sql = "SELECT * FROM calendar";
$result = $conn->query($sql);
$events = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = array(
            'id' => $row['id'],
            'title' => $row['title'],
            'date' => $row['event_date'],
            'description' => $row['description']
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar of Events - Misd</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: white;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            width: 220px;
            height: 100vh;
            color: #fff;
            padding: 30px 16px 16px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border-top-right-radius: 32px;
            border-bottom-right-radius: 32px;
            box-shadow: 2px 0 16px rgba(31,38,135,0.10);
        }

        .profile-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #fff;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(31,38,135,0.10);
            border: 4px solid #fff;
        }

        .profile-circle img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar .role {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .sidebar .label {
            font-size: 0.98rem;
            color: #e0e0e0;
            font-weight: 400;
            margin-bottom: 18px;
        }

        .sidebar .nav-link {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin: 8px 0;
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-size: 1.08rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #fff;
            color: #2563eb;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }

        .logout-btn {
            margin-top: auto;
            background: linear-gradient(90deg, #dc2626 0%, #f87171 100%);
            color: #fff;
            padding: 10px 0;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.08rem;
            letter-spacing: 0.5px;
            border: none;
            width: 100%;
            margin-bottom: 10px;
            transition: background 0.2s;
        }

        .logout-btn:hover { background: #b91c1c; color: #fff; }

        @media (max-width: 700px) {
            .sidebar { width: 100px; padding: 10px 4px; }
            .sidebar .nav-link, .logout-btn { font-size: 0.9rem; padding: 8px 0; }
            .profile-circle { width: 60px; height: 60px; }
            .profile-circle img { width: 52px; height: 52px; }
        }

        .content {
            flex-grow: 1;
            background-color: white;
            margin: 20px;
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 40px);
            border: 2px solid purple;
            position: relative;
        }

        #calendar {
            width: 100%;
            height: 80vh;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .event-details {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 300px;
        }

        .event-details.active {
            display: block;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .event-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 300px;
        }

        .event-form.active {
            display: block;
        }

        .event-form input, .event-form textarea {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .event-form button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .event-form button:hover {
            background: #45a049;
        }

        .has-event {
            background-color: #ff9800 !important;
            color: white !important;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .has-event:hover {
            transform: scale(1.1);
        }

        .set-event-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .event-list {
            margin-top: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .event-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
        }

        .delete-event {
            background: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .save-btn:hover {
            background-color: #45a049;
        }

        .save-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
    </style>
    <link href='https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/fontawesome.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
</head>
<body>
    
        <div class="content">
            <div class="calendar-container" style="display: flex; flex-direction: column; align-items: center;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 20px;">
                    <a href="homepage.php" style="text-decoration: none; color: #2563eb; font-weight: 500; display: flex; align-items: center; gap: 5px;">
                        <span style="font-size: 1.5rem;">←</span> Back to Dashboard
                    </a>
                    <h2 style="color: #374151; margin: 0;">Calendar of Events</h2>
                    <div style="width: 120px;"></div> <!-- Spacer for alignment -->
                </div>
                
                <!-- Add Set Event Button -->
                <button class="set-event-btn" onclick="showEventForm()">Set Event</button>

                <!-- Year Navigation -->
                <div class="year-nav" style="margin-bottom: 20px;">
                    <button class="year-btn prev" onclick="changeYear(-1)">&lt;</button>
                    <span id="current-year" style="font-size: 2rem; margin: 0 20px;">2025</span>
                    <button class="year-btn next" onclick="changeYear(1)">&gt;</button>
                </div>

                <!-- Event List -->
                <div class="event-list">
                    <h3>Current Events</h3>
                    <div id="events-container"></div>
                </div>

                <!-- Month Grid -->
                <div class="month-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; width: 100%; max-width: 1200px;">
                    <?php
                    $months = array(
                        'January', 'February', 'March',
                        'April', 'May', 'June',
                        'July', 'August', 'September',
                        'October', 'November', 'December'
                    );
                    foreach ($months as $month) {
                        echo '<div class="month-box" style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <h3 style="color: #374151; margin: 0 0 15px 0;">' . $month . '</h3>
                            <div class="days-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;">
                                <div style="text-align: center; font-weight: bold;">Sun</div>
                                <div style="text-align: center; font-weight: bold;">Mon</div>
                                <div style="text-align: center; font-weight: bold;">Tue</div>
                                <div style="text-align: center; font-weight: bold;">Wed</div>
                                <div style="text-align: center; font-weight: bold;">Thu</div>
                                <div style="text-align: center; font-weight: bold;">Fri</div>
                                <div style="text-align: center; font-weight: bold;">Sat</div>
                            </div>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div class="event-details">
            <button class="close-btn" onclick="closeEventDetails()">&times;</button>
            <h3 id="event-title"></h3>
            <p id="event-date"></p>
            <div id="event-description"></div>
        </div>

        <!-- Event Form Modal -->
        <div class="event-form" id="eventForm">
            <button class="close-btn" onclick="closeEventForm()">&times;</button>
            <h3>Create New Event</h3>
            <form id="createEventForm" onsubmit="handleSubmit(event)">
                <div class="form-group">
                    <label for="eventTitle">Event Title*</label>
                    <input type="text" id="eventTitle" name="title" required>
                    <div class="error-message" id="titleError">Please enter an event title</div>
                </div>
                <div class="form-group">
                    <label for="eventDate">Event Date*</label>
                    <input type="date" id="eventDate" name="date" required>
                    <div class="error-message" id="dateError">Please select a valid date</div>
                </div>
                <div class="form-group">
                    <label for="eventDescription">Description</label>
                    <textarea id="eventDescription" name="description" rows="4"></textarea>
                </div>
                <button type="submit" class="save-btn" id="saveEventBtn">Save Event</button>
            </form>
        </div>
    </div>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    {
                        title: 'Enrollment Period',
                        start: '2025-05-29',
                        end: '2025-06-05',
                        color: '#4CAF50',
                        description: 'Annual enrollment period for new and returning students'
                    },
                    {
                        title: 'Midterm Exams',
                        start: '2025-07-15',
                        end: '2025-07-20',
                        color: '#FF9800',
                        description: 'Midterm examinations for all courses'
                    },
                    {
                        title: 'Final Exams',
                        start: '2025-11-10',
                        end: '2025-11-15',
                        color: '#F44336',
                        description: 'Final examinations for all courses'
                    },
                    {
                        title: 'Graduation',
                        start: '2025-12-15',
                        color: '#2196F3',
                        description: 'Annual graduation ceremony'
                    }
                ],
                eventClick: function(info) {
                    const details = document.querySelector('.event-details');
                    const closeBtn = document.querySelector('.close-btn');
                    const title = document.getElementById('event-title');
                    const date = document.getElementById('event-date');
                    const description = document.getElementById('event-description');

                    title.textContent = info.event.title;
                    date.textContent = `Date: ${info.event.start.toLocaleDateString()}`;
                    description.textContent = info.event.extendedProps.description;
                    details.classList.add('active');

                    closeBtn.onclick = () => {
                        details.classList.remove('active');
                    };
                }
            });
            calendar.render();
        });
    </script>
    <script>
        // Initialize year
        let currentYear = 2025;

        // Function to change year
        function changeYear(amount) {
            currentYear += amount;
            document.getElementById('current-year').textContent = currentYear;
            updateMonthDays();
        }

        // Initialize events from PHP
        let events = <?php echo json_encode($events); ?>;

        function showEventForm() {
            document.getElementById('eventForm').classList.add('active');
            document.getElementById('eventTitle').focus();
        }

        function closeEventForm() {
            document.getElementById('eventForm').classList.remove('active');
            document.getElementById('createEventForm').reset();
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        }

        function handleSubmit(event) {
            event.preventDefault();
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            
            const title = document.getElementById('eventTitle').value.trim();
            const date = document.getElementById('eventDate').value;
            const description = document.getElementById('eventDescription').value.trim();
            
            // Validate inputs
            let hasError = false;
            
            if (!title) {
                document.getElementById('titleError').style.display = 'block';
                hasError = true;
            }
            
            if (!date) {
                document.getElementById('dateError').style.display = 'block';
                hasError = true;
            }
            
            if (hasError) {
                return;
            }

            // Disable save button while processing
            const saveBtn = document.getElementById('saveEventBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData();
            formData.append('action', 'create_event');
            formData.append('title', title);
            formData.append('date', date);
            formData.append('description', description);

            fetch('calendar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Refresh the events list from the server
                    return fetch('calendar.php', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error creating event');
                }
            })
            .then(res => res.json())
            .then(newEvents => {
                events = newEvents;
                updateMonthDays();
                displayEvents();
                
                // Show success message
                alert('Event successfully created!');
                
                // Reset form
                document.getElementById('createEventForm').reset();
                
                // Close form
                closeEventForm();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating event: ' + error.message);
            })
            .finally(() => {
                // Re-enable save button
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Event';
            });
        }

        function deleteEvent(eventId) {
            if (!confirm('Are you sure you want to delete this event?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_event');
            formData.append('eventId', eventId);

            fetch('calendar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Refresh the events list from the server
                    fetch('calendar.php', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(newEvents => {
                        events = newEvents;
                        updateMonthDays();
                        displayEvents();
                        alert('Event deleted successfully!');
                    });
                } else {
                    alert('Error deleting event: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting event. Please try again.');
            });
        }

        function displayEvents() {
            const container = document.getElementById('events-container');
            container.innerHTML = '';

            events.forEach(event => {
                const eventElement = document.createElement('div');
                eventElement.className = 'event-item';
                eventElement.innerHTML = `
                    <div>
                        <strong>${event.title}</strong> - ${new Date(event.date).toLocaleDateString()}
                    </div>
                    <button class="delete-event" onclick="deleteEvent(${event.id})">Delete</button>
                `;
                container.appendChild(eventElement);
            });
        }

        // Modify updateMonthDays function to highlight days with events
        function updateMonthDays() {
            const months = [
                'January', 'February', 'March',
                'April', 'May', 'June',
                'July', 'August', 'September',
                'October', 'November', 'December'
            ];

            months.forEach((month, monthIndex) => {
                const monthBox = document.querySelector(`.month-box:nth-child(${monthIndex + 1}) .days-grid`);
                if (monthBox) {
                    // Clear existing days
                    monthBox.innerHTML = `
                        <div style="text-align: center; font-weight: bold;">Sun</div>
                        <div style="text-align: center; font-weight: bold;">Mon</div>
                        <div style="text-align: center; font-weight: bold;">Tue</div>
                        <div style="text-align: center; font-weight: bold;">Wed</div>
                        <div style="text-align: center; font-weight: bold;">Thu</div>
                        <div style="text-align: center; font-weight: bold;">Fri</div>
                        <div style="text-align: center; font-weight: bold;">Sat</div>
                    `;

                    const date = new Date(currentYear, monthIndex, 1);
                    const firstDay = date.getDay();
                    const daysInMonth = new Date(currentYear, monthIndex + 1, 0).getDate();

                    // Add empty cells for days before first day
                    for (let i = 0; i < firstDay; i++) {
                        monthBox.innerHTML += '<div style="background: #f8f9fa;"></div>';
                    }

                    // Add days of the month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayCell = document.createElement('div');
                        dayCell.textContent = day;
                        dayCell.style.textAlign = 'center';
                        
                        // Check if there are events on this day
                        const currentDate = new Date(currentYear, monthIndex, day).toISOString().split('T')[0];
                        const hasEvent = events.some(event => event.date === currentDate);
                        
                        if (hasEvent) {
                            dayCell.classList.add('has-event');
                            dayCell.onclick = () => showEventsForDay(currentDate);
                        }

                        // Highlight current date
                        const today = new Date();
                        if (today.getDate() === day && 
                            today.getMonth() === monthIndex && 
                            today.getFullYear() === currentYear) {
                            dayCell.style.backgroundColor = '#4CAF50';
                            dayCell.style.color = 'white';
                            dayCell.style.borderRadius = '50%';
                        }

                        monthBox.appendChild(dayCell);
                    }
                }
            });
        }

        function showEventsForDay(date) {
            const dayEvents = events.filter(event => event.date === date);
            if (dayEvents.length > 0) {
                const details = document.querySelector('.event-details');
                const title = document.getElementById('event-title');
                const dateEl = document.getElementById('event-date');
                const description = document.getElementById('event-description');

                title.textContent = 'Events for ' + new Date(date).toLocaleDateString();
                dateEl.textContent = dayEvents.length + ' event(s)';
                description.innerHTML = dayEvents.map(event => 
                    `<div style="margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                        <strong>${event.title}</strong><br>
                        ${event.description || 'No description'}
                    </div>`
                ).join('');

                details.classList.add('active');
            }
        }

        function closeEventDetails() {
            document.querySelector('.event-details').classList.remove('active');
        }

        // Initialize calendar and events
        document.addEventListener('DOMContentLoaded', function() {
            updateMonthDays();
            displayEvents();
        });
    </script>
</body>
</html>
