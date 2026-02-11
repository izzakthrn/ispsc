<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_verified']) || $_SESSION['is_verified'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Include DB config
require_once '../../backend/configure/config.php';

$conn = getDBConnection();

// Get user's email
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_email = $user_result->fetch_assoc()['email'];

// Fetch services
$services = [];
$result = $conn->query("SELECT id, name FROM services");
while ($row = $result->fetch_assoc()) {
    $services[$row['id']] = $row['name'];
}

// Fetch windows (assume 7 windows)
$windows = [];
$result = $conn->query("SELECT id FROM windows LIMIT 7");
while ($row = $result->fetch_assoc()) {
    $windows[] = $row['id'];
}

// Fetch $status for windows
$status = [];
foreach ($windows as $window_id) {
    $stmt = $conn->prepare("SELECT q.queue_number, s.name, q.customer_name FROM queue q JOIN services s ON q.service_id = s.id WHERE q.window_id = ? AND q.status = 'serving' ORDER BY q.called_at DESC LIMIT 1");
    $stmt->bind_param("i", $window_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $status[$window_id] = $result->fetch_assoc();
    $stmt->close();
}

// Fetch waiting_queue for upcoming
$waiting = [];
$result = $conn->query("SELECT q.queue_number, s.name, q.email FROM queue q JOIN services s ON q.service_id = s.id WHERE q.status = 'waiting' ORDER BY q.created_at ASC");
while ($row = $result->fetch_assoc()) {
    $waiting[] = $row;
}

// Fetch current user's serving status
$user_serving = null;
$serving_query = $conn->prepare("SELECT q.queue_number, s.name as service_name, w.name as window_name FROM queue q JOIN services s ON q.service_id = s.id JOIN windows w ON q.window_id = w.id WHERE q.email = ? AND q.status = 'serving' LIMIT 1");
$serving_query->bind_param("s", $user_email);
$serving_query->execute();
$user_serving = $serving_query->get_result()->fetch_assoc();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISPSC Queue - University Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-blue: #1e3a5f;
            --window1-blue: #2563eb;
            --window2-green: #10b981;
            --window3-purple: #a855f7;
            --window4-orange: #f97316;
            --window5-red: #ef4444;
            --window6-teal: #14b8a6;
            --window7-pink: #ec4899;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --danger-gradient: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-top: 80px;
            margin: 0;
            line-height: 1.6;
        }

        .navbar {
            background: var(--primary-gradient);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
        }

        .brand-icon {
            background-color: white;
            color: var(--navy-blue);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .nav-link {
            color: white !important;
            margin: 0 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .page-title {
            color: var(--navy-blue);
            font-size: 3.5rem;
            font-weight: 800;
            text-align: center;
            margin: 2rem 0 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 3rem;
            font-weight: 400;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            background: white;
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 2rem;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 20px 20px 0 0;
        }

        .card-header h3 {
            position: relative;
            z-index: 1;
            margin: 0;
        }

        .btn-logout {
            background: #FFD700; /* ISPSC Yellow */
            color: #800000;
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        .btn-logout:hover {
            background: #FFC107;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        .window-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            min-height: 320px;
            display: flex;
            flex-direction: column;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .window-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .window-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .window-header {
            color: white;
            padding: 1.25rem;
            border-radius: 15px 15px 0 0;
            margin: -2rem -2rem 2rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            position: relative;
        }

        .window1 .window-header { background: linear-gradient(135deg, var(--window1-blue) 0%, #1d4ed8 100%); }
        .window2 .window-header { background: linear-gradient(135deg, var(--window2-green) 0%, #047857 100%); }
        .window3 .window-header { background: linear-gradient(135deg, var(--window3-purple) 0%, #7c3aed 100%); }
        .window4 .window-header { background: linear-gradient(135deg, var(--window4-orange) 0%, #ea580c 100%); }
        .window5 .window-header { background: linear-gradient(135deg, var(--window5-red) 0%, #dc2626 100%); }
        .window6 .window-header { background: linear-gradient(135deg, var(--window6-teal) 0%, #0d9488 100%); }
        .window7 .window-header { background: linear-gradient(135deg, var(--window7-pink) 0%, #db2777 100%); }

        .ticket-number {
            font-size: 4.5rem;
            font-weight: 800;
            color: var(--navy-blue);
            text-align: center;
            margin: 2rem 0 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .service-type {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--navy-blue);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .customer-name {
            font-size: 1.1rem;
            color: #6b7280;
            text-align: center;
            font-weight: 500;
        }

        .available-status {
            text-align: center;
            padding: 3rem 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex: 1;
        }

        .available-text {
            font-size: 1.8rem;
            color: #10b981;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .ready-text {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .upcoming-section {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 4rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .upcoming-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
        }

        .upcoming-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .upcoming-title {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .waiting-badge {
            background-color: white;
            color: var(--navy-blue);
            padding: 0.75rem 2rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .queue-ticket {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 3px solid #fbbf24;
            border-radius: 15px;
            padding: 2rem;
            max-width: 280px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .queue-ticket:hover {
            transform: translateY(-5px);
        }

        .queue-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--navy-blue);
            text-align: center;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-queue-ticket .queue-number {
            color: #800000;
        }

        .queue-service {
            font-size: 1.1rem;
            color: #6b7280;
            text-align: center;
            font-weight: 600;
        }

        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--success-gradient);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .navbar {
                padding: 0.75rem 1rem;
            }

            .navbar-brand {
                font-size: 1.25rem;
            }

            .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }

            .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }

            .page-title {
                font-size: 2.5rem;
                margin: 1.5rem 0 0.75rem;
            }

            .page-subtitle {
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .dashboard-container {
                padding: 0 0.5rem;
            }

            .window-card {
                padding: 1.5rem;
                min-height: 280px;
            }

            .window-header {
                padding: 1rem;
                font-size: 1.25rem;
            }

            .ticket-number {
                font-size: 3.5rem;
                margin: 1.5rem 0 0.75rem;
            }

            .service-type {
                font-size: 1rem;
            }

            .upcoming-section {
                padding: 2rem 1.5rem;
                margin-top: 3rem;
            }

            .upcoming-title {
                font-size: 1.5rem;
            }

            .waiting-badge {
                padding: 0.5rem 1.25rem;
                font-size: 1rem;
            }

            .queue-ticket {
                padding: 1.5rem;
                max-width: 100%;
            }

            .queue-number {
                font-size: 2.5rem;
            }

            .row.g-4 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .page-title {
                font-size: 3rem;
            }

            .window-card {
                min-height: 300px;
            }

            .ticket-number {
                font-size: 4rem;
            }

            .upcoming-section {
                padding: 2.25rem;
            }
        }

        @media (min-width: 1025px) {
            .dashboard-container {
                padding: 0 2rem;
            }

            .window-card {
                min-height: 340px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .window-card, .upcoming-section, .alert {
            animation: fadeInUp 0.6s ease-out;
        }

        .window-card:nth-child(odd) {
            animation-delay: 0.1s;
        }

        .window-card:nth-child(even) {
            animation-delay: 0.2s;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <div class="brand-icon">I</div>
                ISPSC QUEUE
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#queue-board">
                            <i class="bi bi-grid-3x3-gap"></i> Queue Board
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="get_ticket.php">
                            <i class="bi bi-plus-circle"></i> Get Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_ticket.php">
                            <i class="bi bi-search"></i> My Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="logout" class="btn btn-link nav-link">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-4 py-4">
        <h1 class="page-title">University Registrar</h1>
        <p class="page-subtitle">Queue Status Board</p>

        <div class="dashboard-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4>Welcome, <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?>!</h4>
                        <p class="text-muted">Student ID: <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                    </div>

                    <!-- Current Serving Status -->
                    <?php if ($user_serving): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h5 class="alert-heading mb-1">You are currently being served!</h5>
                                <p class="mb-1">
                                    <strong>Ticket:</strong> <?php echo htmlspecialchars($user_serving['queue_number']); ?> |
                                    <strong>Service:</strong> <?php echo htmlspecialchars($user_serving['service_name']); ?> |
                                    <strong>Window:</strong> <?php echo htmlspecialchars($user_serving['window_name']); ?>
                                </p>
                                <small class="text-muted">Please proceed to your assigned window.</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Window Cards -->
                    <div class="row g-4 mb-5">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="col-lg-3 col-md-6">
                                <div class="window-card window<?php echo $i; ?>">
                                    <div class="window-header">Window <?php echo $i; ?></div>
                                    <?php if (isset($status[$i])): ?>
                                        <div class="ticket-number"><?php echo htmlspecialchars($status[$i]['queue_number']); ?></div>
                                        <div class="service-type"><?php echo htmlspecialchars($status[$i]['name']); ?></div>
                                    <?php else: ?>
                                        <div class="available-status">
                                            <div class="available-text">Available</div>
                                            <div class="ready-text">Ready to serve</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Upcoming Queue Section -->
                    <div class="upcoming-section">
                        <div class="upcoming-header">
                            <div class="upcoming-title">
                                <i class="bi bi-people-fill"></i>
                                Upcoming Queue
                            </div>
                            <div class="waiting-badge"><?php echo count($waiting); ?> waiting</div>
                        </div>
                        <div class="row">
                            <?php foreach ($waiting as $ticket): ?>
                                <div class="col-auto">
                                    <div class="queue-ticket <?php echo ($ticket['email'] == $user_email) ? 'user-queue-ticket' : ''; ?>">
                                        <div class="queue-number"><?php echo $ticket['queue_number']; ?></div>
                                        <div class="queue-service"><?php echo $ticket['name']; ?></div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Function to update queue display
        function updateQueueDisplay() {
            fetch('../../staff/ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_dashboard_queue'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    for (let i = 1; i <= 7; i++) {
                        const windowCard = document.querySelector(`.window${i} .window-card`);
                        const ticketInfo = windowCard.querySelector('.ticket-number, .available-status');
                        if (ticketInfo) ticketInfo.remove();

                        if (data.on_queue[i]) {
                            const ticketDiv = document.createElement('div');
                            ticketDiv.className = 'ticket-number';
                            ticketDiv.textContent = data.on_queue[i].queue_number;
                            const serviceDiv = document.createElement('div');
                            serviceDiv.className = 'service-type';
                            serviceDiv.textContent = data.on_queue[i].name;
                            windowCard.appendChild(ticketDiv);
                            windowCard.appendChild(serviceDiv);
                        } else {
                            const availableDiv = document.createElement('div');
                            availableDiv.className = 'available-status';
                            availableDiv.innerHTML = '<div class="available-text">Available</div><div class="ready-text">Ready to serve</div>';
                            windowCard.appendChild(availableDiv);
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating queue:', error));
        }

        // Update every 5 seconds
        setInterval(updateQueueDisplay, 5000);

        // Initial update on page load
        updateQueueDisplay();
    </script>
</body>
</html>
