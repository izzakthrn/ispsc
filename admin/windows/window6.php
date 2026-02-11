<?php
// admin.php - Main Queue Management Interface

require_once '../../backend/configure/config.php';
require_once '../functions.php';

$conn = getDBConnection();
$windowsId = 6; // Default window

// Get data
$windows = getWindow($conn, $windowsId);
$services = getServicesForWindow($conn, $windowsId);
$enabledCount = getEnabledServicesCount($conn, $windowsId);
$totalCount = getTotalServicesCount($conn, $windowsId);
$currentServing = getCurrentlyServing($conn, $windowsId);
$nextEligible = getNextEligible($conn, $windowsId);
$eligibleCount = countEligible($conn, $windowsId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Management - <?= htmlspecialchars($windows['name']) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-blue: #1e3a5f;
            --primary-blue-dark: #16293d;
            --success-green: #28a745;
            --light-green-bg: #e8f5e9;
            --light-beige-bg: #fef8f0;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .window-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
        }
        
        .window-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .window-container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .window-body {
            padding: 2rem;
        }
        
        .services-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .services-section:hover {
            background: #f9f9f9;
        }
        
        .services-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .services-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .service-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .service-item:hover {
            background: #e9ecef;
        }
        
        .service-name {
            font-size: 0.95rem;
            color: #333;
        }
        
        .service-toggle {
            transform: scale(1.2);
            cursor: pointer;
        }
        
        .section-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .serving-card {
            background: var(--light-green-bg);
            border: 2px solid #c8e6c9;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .serving-badge {
            background: var(--success-green);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .queue-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e3a5f;
            margin: 0;
            line-height: 1;
        }
        
        .customer-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0.5rem 0 0.25rem 0;
        }
        
        .service-label {
            color: #666;
            font-weight: 500;
        }
        
        .btn-done {
            background: var(--success-green);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-done:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-call-next {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-call-next:hover {
            background: var(--primary-blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
        }
        
        .eligible-item {
            background: var(--light-beige-bg);
            border: 1px solid #f0e5d8;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .eligible-item:hover {
            background: #fdf3e7;
            border-color: #e8d5c0;
            transform: translateX(5px);
        }
        
        .eligible-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }
        
        .eligible-name {
            font-weight: 500;
            color: #666;
            margin-left: 1rem;
        }
        
        .eligible-service {
            color: #2196F3;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .badge-count {
            background: white;
            color: var(--primary-blue);
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
        
        .collapse-icon {
            transition: transform 0.3s;
        }
        
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
    </style>
</head>
<body>
    <div class="window-container">
        <!-- Header -->
        <div class="window-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="window-title">
                    <i class="bi bi-person"></i>
                    <?= htmlspecialchars($windows['name']) ?>
                </h1>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-count">
                        <i class="bi bi-list"></i> <span id="serving-count">0</span>/5
                    </span>
                    <span class="badge-count">
                        <i class="bi bi-people"></i> <span id="eligible-count-badge"><?= $eligibleCount ?></span> eligible
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="window-body">
            <!-- Services Section -->
            <div class="services-section" data-bs-toggle="collapse" data-bs-target="#servicesCollapse">
                <div class="services-header">
                    <div class="services-title">
                        <i class="bi bi-gear"></i>
                        Services (<span id="enabled-count"><?= $enabledCount ?></span>/<span id="total-count"><?= $totalCount ?></span> enabled)
                    </div>
                    <i class="bi bi-chevron-down collapse-icon"></i>
                </div>
                <div class="collapse show" id="servicesCollapse">
                    <div class="services-grid" id="services-list">
                        <?php foreach($services as $service): ?>
                        <div class="service-item">
                            <span class="service-name"><?= htmlspecialchars($service['name']) ?></span>
                            <div class="form-check form-switch">
                                <input 
                                    class="form-check-input service-toggle" 
                                    type="checkbox" 
                                    <?= $service['is_enabled'] ? 'checked' : '' ?>
                                    data-service-id="<?= $service['id'] ?>"
                                    onchange="toggleService(this, <?= $service['id'] ?>)">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Currently Serving -->
            <div class="section-label">
                <i class="bi bi-people"></i>
                Serving 1 Customer
            </div>
            
            <div id="serving-section">
                <?php if($currentServing && count($currentServing) > 0): ?>
                    <?php foreach($currentServing as $serving): ?>
                    <div class="serving-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="serving-badge">Now Serving</span>
                                <h2 class="queue-number"><?= htmlspecialchars($serving['queue_number']) ?></h2>
                                <p class="customer-name"><?= htmlspecialchars($serving['customer_name']) ?></p>
                                <p class="service-label"><?= htmlspecialchars($serving['service_name']) ?></p>
                            </div>
                            <button class="btn btn-done" onclick="markDone(<?= $serving['id'] ?>)">
                                <i class="bi bi-check-circle"></i>
                                Done
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p>No customer currently being served</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Call Next Button -->
            <?php if($eligibleCount > 0): ?>
            <button class="btn btn-call-next" onclick="callNext(<?= $nextEligible[0]['id'] ?? 0 ?>)">
                <i class="bi bi-telephone"></i>
                Call Next (<span id="eligible-count"><?= $eligibleCount ?></span> eligible)
            </button>
            <?php endif; ?>
            
            <!-- Next Eligible -->
            <div class="section-label">
                <i class="bi bi-clock-history"></i>
                Next Eligible
            </div>
            
            <div id="eligible-list">
                <?php if(count($nextEligible) > 0): ?>
                    <?php foreach($nextEligible as $queue): ?>
                    <div class="eligible-item" onclick="callNext(<?= $queue['id'] ?>)">
                        <div class="d-flex align-items-center">
                            <span class="eligible-number"><?= htmlspecialchars($queue['queue_number']) ?></span>
                            <span class="eligible-name"><?= htmlspecialchars($queue['customer_name']) ?></span>
                        </div>
                        <span class="eligible-service"><?= htmlspecialchars($queue['service_name']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p>No eligible customers in queue</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const windowId = <?= $windowsId ?>;
        
        // Toggle collapse icon
        document.getElementById('servicesCollapse').addEventListener('shown.bs.collapse', function() {
            document.querySelector('.services-section').classList.remove('collapsed');
        });
        
        document.getElementById('servicesCollapse').addEventListener('hidden.bs.collapse', function() {
            document.querySelector('.services-section').classList.add('collapsed');
        });
        
        // Toggle service on/off
        function toggleService(checkbox, serviceId) {
            fetch('../ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_service&service_id=${serviceId}&window_id=${windowId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    refreshStatus();
                } else {
                    alert('Error toggling service');
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                checkbox.checked = !checkbox.checked;
            });
        }
        
        // Mark current customer as done
        function markDone(queueId) {
            fetch('../ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_done&queue_id=${queueId}&window_id=${windowId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    refreshStatus();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Call next customer
        function callNext(queueId) {
            fetch('../ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=call_next&queue_id=${queueId}&window_id=${windowId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    refreshStatus();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Refresh status
        function refreshStatus() {
            fetch('../ajax_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_status&window_id=${windowId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    updateUI(data);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Update UI with new data
        function updateUI(data) {
            // Update counts
            document.getElementById('enabled-count').textContent = data.enabled_services;
            document.getElementById('total-count').textContent = data.total_services;
            document.getElementById('eligible-count-badge').textContent = data.eligible_count;
            
            if(document.getElementById('eligible-count')) {
                document.getElementById('eligible-count').textContent = data.eligible_count;
            }
            
            // Update serving section
            const servingSection = document.getElementById('serving-section');
            if(data.current_serving) {
                servingSection.innerHTML = `
                    <div class="serving-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="serving-badge">Now Serving</span>
                                <h2 class="queue-number">${escapeHtml(data.current_serving.queue_number)}</h2>
                                <p class="customer-name">${escapeHtml(data.current_serving.customer_name)}</p>
                                <p class="service-label">${escapeHtml(data.current_serving.service_name)}</p>
                            </div>
                            <button class="btn btn-done" onclick="markDone(${data.current_serving.id})">
                                <i class="bi bi-check-circle"></i>
                                Done
                            </button>
                        </div>
                    </div>
                `;
            } else {
                servingSection.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p>No customer currently being served</p>
                    </div>
                `;
            }
            
            // Update call next button
            const servingCard = servingSection.querySelector('.serving-card');
            if(servingCard && data.eligible_count > 0 && !document.querySelector('.btn-call-next')) {
                const callNextBtn = document.createElement('button');
                callNextBtn.className = 'btn btn-call-next';
                callNextBtn.onclick = () => callNext(data.next_eligible[0].id);
                callNextBtn.innerHTML = `
                    <i class="bi bi-telephone"></i>
                    Call Next (<span id="eligible-count">${data.eligible_count}</span> eligible)
                `;
                servingCard.parentElement.insertAdjacentElement('afterend', callNextBtn);
            } else if(data.eligible_count === 0) {
                const existingBtn = document.querySelector('.btn-call-next');
                if(existingBtn) existingBtn.remove();
            } else if(data.eligible_count > 0 && document.querySelector('.btn-call-next')) {
                document.querySelector('.btn-call-next').onclick = () => callNext(data.next_eligible[0].id);
            }
            
            // Update eligible list
            const eligibleList = document.getElementById('eligible-list');
            if(data.next_eligible && data.next_eligible.length > 0) {
                eligibleList.innerHTML = data.next_eligible.map(queue => `
                    <div class="eligible-item" onclick="callNext(${queue.id})">
                        <div class="d-flex align-items-center">
                            <span class="eligible-number">${escapeHtml(queue.queue_number)}</span>
                            <span class="eligible-name">${escapeHtml(queue.customer_name)}</span>
                        </div>
                        <span class="eligible-service">${escapeHtml(queue.service_name)}</span>
                    </div>
                `).join('');
            } else {
                eligibleList.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p>No eligible customers in queue</p>
                    </div>
                `;
            }
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Auto-refresh every 5 seconds
        setInterval(refreshStatus, 5000);
    </script>
</body>
</html>