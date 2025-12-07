<?php
require_once '../include/check_auth.php';

$username = getUsername();
$email = getUserEmail();
$userId = getUserId();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Restoran - Analisis Kinerja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fb;
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 0;
            transition: margin-left 0.3s ease;
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
        }
        
        .main-content.sidebar-closed {
            margin-left: 70px;
        }

        .header-bar {
            height: 100px;
            background: linear-gradient(90deg, #1A4D80, #3B82F6);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .header-bar h1 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dashboard-container {
            flex-grow: 1;
            position: relative;
            height: calc(100vh - 100px);
            overflow: hidden;
            padding: 20px;
            background-color: #ffffff;
        }

        .dashboard-container iframe {
            width: 100%;
            height: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        /* Kelas baru untuk membuat elemen menjadi lingkaran */
        .rounded-circle {
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include '../../sidebar/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="header-bar">
            <h1>Dashboard</h1>
        </div> 

        <div class="dashboard-container">
            <iframe title="dashboard1" src="https://app.powerbi.com/view?r=eyJrIjoiYjhjM2JmZTAtYWRlOC00NGQ4LWIyMDItMzI1YjE1MjAzNjNiIiwidCI6ImE2OWUxOWU4LWYwYTQtNGU3Ny1iZmY2LTk1NjRjODgxOWIxNCJ9" allowFullScreen="true"></iframe>
        </div>
    </div>
</body>
</html>