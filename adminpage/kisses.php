<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 200px;
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: gray;
        }
        .sidebar h3 {
            margin: 10px 0;
        }
        .nav-item {
            width: 100%;
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }
        .nav-item:hover {
            background-color: #2563eb;
        }
        .content {
            flex: 1;
            background: url('https://www.ccc.edu.ph/images/2022/01/02/ccc-vid-overlaay.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .student-card {
            width: 150px;
            background: rgba(255, 255, 255, 0.30);
            backdrop-filter: blur(10px);
            padding: 10px;
            margin: 10px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .student-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #3b82f6;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
        }
        .back-button:hover {
            background-color: #2563eb;
        }
        .icons {
            display: flex;
            justify-content: center;
            margin-top: 5px;
        }
        .icons span {
            margin: 0 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="arlou.jpeg.png" alt="Profile">
        <p>Arlou H. Fernando</p>
        <a href="Dean.php" class="back-button">Back to Dean</a>
        <p>Dean</p>
        <div class="nav-item">Dashboard</div>
        <div class="nav-item" style="background-color: #2563eb;">Student</div>
        <div class="nav-item">Curriculum</div>
    </div>
    <div class="content">
 
        <div class="student-card">
            <img src="kisses.jpeg.jpg" alt="Saez Kisses Anne">
            <p>Saez Kisses Anne</p>
            <p>2022-2023</p>
            <p><b>Units Earned:</b> **<br>
               <b>Available Units:</b> **<br>
               <b>Status:</b> Regular</p>
            <a href="#" class="edit-btn">✎</a>
        </div>
    </div>
</body>
</html>