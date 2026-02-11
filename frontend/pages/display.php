<?php
session_start();
require_once '../../backend/configure/config.php';

// Fetch services
$services = [];
$result = $conn->query("SELECT id, name FROM services");
while ($row = $result->fetch_assoc()) {
    $services[$row['id']] = $row['name'];
}

// Fetch staff (assume 7 staff for 7 windows)
$staff = [];
$result = $conn->query("SELECT id, registrar_code FROM staff LIMIT 7");
while ($row = $result->fetch_assoc()) {
    $staff[$row['id']] = $row['registrar_code'];
}

// Fetch on_queue for windows
$on_queue = [];
foreach ($staff as $staff_id => $registrar_code) {
    $stmt = $conn->prepare("SELECT q.queue_number, s.name, u.firstname, u.lastname FROM queue q JOIN services s ON q.service_id = s.id LEFT JOIN users u ON q.email = u.email WHERE q.window_id = ? AND q.status = 'serving' ORDER BY q.called_at DESC LIMIT 1");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $on_queue[$staff_id] = $result->fetch_assoc();
    $stmt->close();
}

// Fetch waiting_queue for upcoming
$waiting = [];
$result = $conn->query("SELECT q.queue_number, s.name FROM queue q JOIN services s ON q.service_id = s.id WHERE q.status = 'waiting' ORDER BY q.created_at ASC");
while ($row = $result->fetch_assoc()) {
    $waiting[] = $row;
}

// Handle window click for queuing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['queue_window']) && isset($_SESSION['user_id'])) {
    $window = (int)$_POST['queue_window'];
    $service_count = count($services);
    if ($service_count > 0) {
        $service_id = (($window - 1) % $service_count) + 1; // Cycle through available services
        if (!isset($services[$service_id])) {
            $service_id = array_key_first($services);
        }

        if ($service_id !== null) {
            // Get next ticket number
            $result = $conn->query("SELECT MAX(CAST(queue_number AS UNSIGNED)) as max_ticket FROM queue");
            $row = $result->fetch_assoc();
            $next_ticket = str_pad(($row['max_ticket'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO queue (queue_number, customer_name, email, service_id, status) VALUES (?, ?, ?, ?, 'waiting')");
            $stmt->bind_param("sssi", $next_ticket, $_SESSION['firstname'], $_SESSION['email'], $service_id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: display.php");
                exit();
            } else {
                // Handle error
                $stmt->close();
            }
        }
    }
}

// My Ticket logic
$my_tickets = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT q.queue_number, s.name, q.called_at, q.completed_at, r.score, r.comments FROM queue q JOIN services s ON q.service_id = s.id LEFT JOIN review r ON r.queue_id = q.id WHERE q.email = ? AND q.status = 'done' ORDER BY q.completed_at DESC");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $my_tickets[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniQueue - University Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: var(--navy-blue);
            padding: 1rem 2rem;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .brand-icon {
            background-color: white;
            color: var(--navy-blue);
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            color: white !important;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .page-title {
            color: var(--navy-blue);
            font-size: 3.5rem;
            font-weight: 700;
            text-align: center;
            margin: 2rem 0 1rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 3rem;
        }

        .window-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 280px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .window-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .window-header {
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin: -2rem -2rem 1.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .window1 .window-header { background-color: var(--window1-blue); }
        .window2 .window-header { background-color: var(--window2-green); }
        .window3 .window-header { background-color: var(--window3-purple); }
        .window4 .window-header { background-color: var(--window4-orange); }
        .window5 .window-header { background-color: var(--window5-red); }
        .window6 .window-header { background-color: var(--window6-teal); }
        .window7 .window-header { background-color: var(--window7-pink); }

        .ticket-number {
            font-size: 5rem;
            font-weight: 700;
            color: var(--navy-blue);
            text-align: center;
            margin: 1.5rem 0;
        }

        .service-type {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--navy-blue);
            text-align: center;
            margin-bottom: 1rem;
        }

        .customer-name {
            font-size: 1rem;
            color: #6b7280;
            text-align: center;
        }

        .available-status {
            text-align: center;
            padding: 2rem 0;
        }

        .available-text {
            font-size: 1.5rem;
            color: #10b981;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .ready-text {
            color: #6b7280;
            font-size: 1rem;
        }

        .queue-btn {
            margin-top: auto;
            text-align: center;
        }

        .upcoming-section {
            background-color: var(--navy-blue);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 3rem;
            color: white;
        }

        .upcoming-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .upcoming-title {
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .waiting-badge {
            background-color: white;
            color: var(--navy-blue);
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .queue-ticket {
            background-color: #fef3c7;
            border: 3px solid #fbbf24;
            border-radius: 10px;
            padding: 1.5rem;
            max-width: 250px;
        }

        .queue-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--navy-blue);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .queue-service {
            font-size: 1rem;
            color: #6b7280;
            text-align: center;
        }

        .my-ticket-section {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .ticket-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <div class="brand-icon">U</div>
                UniQueue
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
                        <a class="nav-link" href="#my-ticket">
                            <i class="bi bi-search"></i> My Ticket
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-4 py-4">
        <h1 class="page-title">University Registrar</h1>
        <p class="page-subtitle">Queue Status Board</p>

        <!-- Window Cards -->
        <div class="row g-4 mb-5" id="queue-board">
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="window-card window<?php echo $i; ?>">
                        <div class="window-header">Window <?php echo $i; ?></div>
                        <?php if (isset($on_queue[$i])): ?>
                            <div class="ticket-number"><?php echo htmlspecialchars($on_queue[$i]['queue_number']); ?></div>
                            <div class="service-type"><?php echo htmlspecialchars($on_queue[$i]['name']); ?></div>
                            <div class="customer-name"><?php echo htmlspecialchars($on_queue[$i]['firstname'] . ' ' . $on_queue[$i]['lastname']); ?></div>
                        <?php else: ?>
                            <div class="available-status">
                                <div class="available-text">Available</div>
                                <div class="ready-text">Ready to serve</div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="queue-btn">
                                <form method="POST">
                                    <input type="hidden" name="queue_window" value="<?php echo $i; ?>">
                                    <button type="submit" class="btn btn-primary">Queue Here</button>
                                </form>
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
                        <div class="queue-ticket">
                            <div class="queue-number"><?php echo $ticket['queue_number']; ?></div>
                            <div class="queue-service"><?php echo $ticket['name']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- My Ticket Section -->
        <div class="my-ticket-section" id="my-ticket">
            <h3>My Tickets</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (count($my_tickets) > 0): ?>
                    <?php foreach ($my_tickets as $ticket): ?>
                        <div class="ticket-item">
                            <strong>Ticket: <?php echo $ticket['queue_number']; ?></strong> - <?php echo $ticket['name']; ?><br>
                            Called: <?php echo $ticket['called_at']; ?> | Completed: <?php echo $ticket['completed_at']; ?><br>
                            <?php if ($ticket['score']): ?>Rating: <?php echo $ticket['score']; ?>/5<br><?php endif; ?>
                            <?php if ($ticket['comments']): ?>Comments: <?php echo $ticket['comments']; ?><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No tickets found. Please queue first.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Please <a href="login.php">login</a> to view your tickets.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
