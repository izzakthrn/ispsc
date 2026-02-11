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

// Fetch services
$services_query = $conn->query("SELECT id, name FROM services");
$services = [];
while ($row = $services_query->fetch_assoc()) {
    $services[] = $row;
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_ticket'])) {
    $service_id = $_POST['service_id'];

    // Check if user already has a ticket (waiting or serving)
    $check_query = $conn->prepare("SELECT id FROM queue WHERE email = ? AND status IN ('waiting', 'serving')");
    $check_query->bind_param("s", $user_email);
    $check_query->execute();
    if ($check_query->get_result()->num_rows > 0) {
        $message = 'You already have an active ticket in the queue. Please wait for your turn or complete your current service.';
    } else {
        // Generate ticket number (e.g., A001, A002, etc.)
        $last_ticket_query = $conn->query("SELECT queue_number FROM queue ORDER BY id DESC LIMIT 1");
        $last_ticket = $last_ticket_query->fetch_assoc();
        if ($last_ticket) {
            $num = intval(substr($last_ticket['queue_number'], 1)) + 1;
            $ticket_number = 'A' . str_pad($num, 3, '0', STR_PAD_LEFT);
        } else {
            $ticket_number = 'A001';
        }

        // Insert into queue
        $insert_query = $conn->prepare("INSERT INTO queue (queue_number, customer_name, email, service_id, status) VALUES (?, ?, ?, ?, 'waiting')");
        $insert_query->bind_param("sssi", $ticket_number, $_SESSION['firstname'], $user_email, $service_id);
        if ($insert_query->execute()) {
            $message = 'Ticket generated successfully! Your ticket number is ' . $ticket_number;
        } else {
            $message = 'Error generating ticket.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Queue Number</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .queue-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            margin: 0 auto;
        }
        .icon-container {
            width: 70px;
            height: 70px;
            background: #1e3a8a;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .icon-container i {
            font-size: 35px;
            color: white;
        }
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #718096;
            font-size: 15px;
            margin-bottom: 30px;
        }
        label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        label i {
            margin-right: 8px;
        }
        .form-select, .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-select:focus, .form-control:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        .btn-primary {
            background: #1e3a8a;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 58, 138, 0.3);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="queue-card">
            <div class="icon-container">
                <i class="bi bi-ticket-perforated"></i>
            </div>

            <h1>Get Queue Number</h1>
            <p class="subtitle">Fill in your details to receive a queue ticket</p>

            <?php
            // Display success message if exists
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['success_message']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }

            // Display error message if exists
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['error_message']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="service_id">
                        <i class="bi bi-ticket"></i>
                        Transaction Type *
                    </label>
                    <select class="form-select" id="service_id" name="service_id" required onchange="showRequirements()">
                        <option value="" selected disabled>Select transaction type</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="requirements" class="mb-4" style="display: none;">
                    <label>
                        <i class="bi bi-file-text"></i>
                        Requirements
                    </label>
                    <div id="requirements-list" class="form-control" style="min-height: 100px; padding: 12px 15px; background-color: #f8f9fa; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <!-- Requirements will be populated here -->
                    </div>
                </div>

                <button type="submit" name="get_ticket" class="btn btn-primary">
                    <i class="bi bi-ticket-perforated me-2"></i>
                    Get Queue Number
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showRequirements() {
            const serviceSelect = document.getElementById('service_id');
            const requirementsDiv = document.getElementById('requirements');
            const requirementsList = document.getElementById('requirements-list');
            const selectedService = serviceSelect.value;

            if (selectedService) {
                // Fetch service requirements via AJAX
                fetch('get_service_requirements.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'service_id=' + selectedService
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const requirements = data.description.split(/\r?\n/).filter(req => req.trim() !== '');
                        requirementsList.innerHTML = requirements.map(req => `<li>${req.trim()}</li>`).join('');
                        requirementsDiv.style.display = 'block';
                    } else {
                        requirementsList.innerHTML = '<li>No requirements specified</li>';
                        requirementsDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching requirements:', error);
                    requirementsList.innerHTML = '<li>Error loading requirements</li>';
                    requirementsDiv.style.display = 'block';
                });
            } else {
                requirementsDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
