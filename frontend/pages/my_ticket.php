<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_verified']) || $_SESSION['is_verified'] != 1) {
    header("Location: login.php");
    exit();
}

// Include DB config
require_once '../../backend/configure/config.php';

// Get user's email
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_email = $user_result->fetch_assoc()['email'];

// My Ticket logic
$my_tickets = [];
$stmt = $conn->prepare("SELECT q.queue_number, s.name, q.called_at, q.completed_at, r.score, r.comments FROM queue q JOIN services s ON q.service_id = s.id LEFT JOIN review r ON r.queue_id = q.id WHERE q.email = ? AND q.status = 'done' ORDER BY q.completed_at DESC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $my_tickets[] = $row;
}
$stmt->close();

// Fetch user's ticket
$ticket_query = $conn->prepare("SELECT q.queue_number, s.name FROM queue q JOIN services s ON q.service_id = s.id WHERE q.email = ? AND q.status = 'waiting'");
$ticket_query->bind_param("s", $user_email);
$ticket_query->execute();
$ticket_result = $ticket_query->get_result();
$user_ticket = $ticket_result->fetch_assoc();

// Requirements based on service
$requirements = [];
if ($user_ticket) {
    if (strtolower($user_ticket['name']) === 'grade request' || strtolower($user_ticket['name']) === 'REQUEST FOR GRADES') {
        $requirements = ['Student ID', 'Cashier Receipt'];
    } else {
        $requirements = ['No specific requirements'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ticket - ISPSC Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
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
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>My Ticket
                </h3>
            </div>
            <div class="card-body p-4">
                <?php if ($user_ticket): ?>
                    <div class="text-center mb-4">
                        <h4>Your Ticket Number: <span class="text-primary"><?php echo htmlspecialchars($user_ticket['queue_number']); ?></span></h4>
                        <p class="text-muted">Transaction Type: <?php echo htmlspecialchars($user_ticket['name']); ?></p>
                    </div>

                    <div class="mb-3">
                        <h5>Requirements:</h5>
                        <ul class="list-group">
                            <?php foreach ($requirements as $req): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($req); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>You do not have an active ticket. <a href="get_ticket.php">Get one here</a>.
                    </div>
                <?php endif; ?>

                <div class="text-center">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Completed Tickets Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-history me-2"></i>My Completed Tickets
                </h3>
            </div>
            <div class="card-body p-4">
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
                    <p>No completed tickets found.</p>
                <?php endif; ?>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
