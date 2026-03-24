<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        @keyframes slideFadeInLeft {
            0% {
                opacity: 0;
                transform: translateX(-50px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }
    
        @keyframes slideFadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            animation: fadeInBody 1s ease-in-out;
        }
    
        @keyframes fadeInBody {
            from { opacity: 0; }
            to { opacity: 1; }
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
            animation: slideFadeInLeft 1s ease forwards;
        }
    
        .sidebar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: gray;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
    
        .sidebar img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.7);
        }
    
        .sidebar h3 {
            margin: 10px 0;
        }
    
        .nav-item {
            width: 100%;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
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
            animation: fadeInBody 1.5s ease;
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
            animation: slideFadeInUp 1s ease forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
    
        .student-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
    
        .student-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
    
        .student-card img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.6);
        }
    
        .icons {
            display: flex;
            justify-content: center;
            margin-top: 5px;
            font-size: 18px;
        }
    
        .icons span, .icons a {
            margin: 0 5px;
            cursor: pointer;
            text-decoration: none;
            color: #000;
            transition: transform 0.2s ease, color 0.3s ease;
        }
    
        .icons span:hover,
        .icons a:hover {
            transform: scale(1.2);
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="arlou.jpeg.png" alt="Profile">
        <p>Arlou H. Fernando</p>
        <p>Dean</p>
        <div class="nav-item">Dashboard</div>
        <div class="nav-item" style="background-color: #2563eb;">Student</div>
        <div class="nav-item">Curriculum</div>
    </div>
    <div class="content">
         
        <div class="student-card">
            <img src="kisses.jpeg.jpg" alt="Saez Kisses Anne">
            <p>Saez Kisses Anne</p>
            <div class="icons">🔍 <a href="kisses.html"> ✏️ </a> </div>
        </div>
        <div class="student-card">
            <img src="nicole.jpeg.png" alt="Guevarra Nicole">
            <p>Guevarra Nicole</p>
            <div class="icons">🔍 <a href="nicole.html"> ✏️ </a> </div>
        </div>
        <div class="student-card">
            <img src="philip.jpeg.jpg" alt="Dirige Philip Joshua">
            <p>Dirige Philip Joshua</p>
            <div class="icons">🔍 <a href="philip.html"> ✏️ </a> </div>
        </div>
        <div class="student-card">
            <img src="rozz.jpeg.jpg" alt="2024-2025 ">
            <p>Opena Rozz Allein</p>
            <div class="icons">🔍 <a href="rozzopena.html"> ✏️ </a> </div>
        </div>
    </div>
</body>
</html>