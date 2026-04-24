<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Require candidate login
requireRole('candidate');

$userId = getCurrentUserId();
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Get candidate details
$candidate = getUserById($userId);
$election = getElectionStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/text-visibility-fix.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-content">
            <div class="navbar-brand">🗳️ E-Voting</div>
            <div class="navbar-menu">
                <div class="navbar-user">
                    <?php echo createAvatarPlaceholder($userName); ?>
                    <div>
                        <div style="font-weight: 600; color: white;"><?php echo htmlspecialchars($userName); ?></div>
                        <div style="font-size: 0.75rem; color: #6c757d;">Candidate</div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Dashboard -->
    <div class="dashboard">
        <div class="container">
            <!-- Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">Candidate Dashboard</h1>
                <p class="dashboard-subtitle">Your campaign profile</p>
            </div>
            
            <!-- Election Status Banner -->
            <?php if ($election): ?>
                <div class="election-status-banner election-status-<?php echo $election['election_status']; ?>">
                    <?php if ($election['election_status'] === 'active'): ?>
                        ✅ Election is currently active - Voting is in progress!
                    <?php elseif ($election['election_status'] === 'paused'): ?>
                        ⏸️ Election is temporarily paused
                    <?php elseif ($election['election_status'] === 'completed'): ?>
                        🏁 Election has been completed
                    <?php else: ?>
                        ⏳ Election has not started yet
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-header">
                    <?php if ($candidate['photo']): ?>
                        <img src="uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                             class="profile-photo"
                             onerror="this.src='uploads/candidates/default-male.png'">
                    <?php else: ?>
                        <div class="profile-photo" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 700;">
                            <?php echo strtoupper(substr($candidate['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($candidate['name']); ?></h2>
                        <p>🎓 <?php echo htmlspecialchars($candidate['department']); ?> - <?php echo htmlspecialchars($candidate['year']); ?></p>
                        <p>🆔 <?php echo htmlspecialchars($candidate['student_id']); ?></p>
                        <p>📧 <?php echo htmlspecialchars($candidate['email']); ?></p>
                        <p>📱 <?php echo htmlspecialchars($candidate['mobile']); ?></p>
                    </div>
                </div>
                
                <?php if ($candidate['address']): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <h3 style="margin-bottom: 0.5rem; color: white;">Campaign Statement</h3>
                        <p style="color: #ccc;"><?php echo nl2br(htmlspecialchars($candidate['address'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Election Information -->
            <div class="glass-card">
                <h2 style="margin-bottom: 1.5rem;">Election Information</h2>
                
                <div style="display: grid; gap: 1rem;">
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-weight: 600; color: white; margin-bottom: 0.25rem;">Election Status</div>
                        <div style="color: #ccc;">
                            <?php 
                            if ($election) {
                                switch($election['election_status']) {
                                    case 'active':
                                        echo '🟢 Active - Voting in progress';
                                        break;
                                    case 'paused':
                                        echo '🟡 Paused';
                                        break;
                                    case 'completed':
                                        echo '🔴 Completed';
                                        break;
                                    default:
                                        echo '⚪ Not started';
                                }
                            } else {
                                echo 'No active election';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-weight: 600; color: white; margin-bottom: 0.25rem;">Your Status</div>
                        <div style="color: #ccc;">
                            <?php echo $candidate['status'] === 'active' ? '✅ Active Candidate' : '⏸️ Inactive'; ?>
                        </div>
                    </div>
                    
                    <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">
                        <div style="font-weight: 600; color: white; margin-bottom: 0.25rem;">Account Created</div>
                        <div style="color: #ccc;">
                            <?php echo date('F j, Y', strtotime($candidate['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Important Notice -->
            <div class="glass-card" style="border-left: 4px solid #00d4ff;">
                <h3 style="color: white; margin-bottom: 1rem;">📢 Important Information</h3>
                <ul style="color: #ccc; line-height: 1.8;">
                    <li>Vote counts and rankings are kept private during the election</li>
                    <li>Results will be announced after the election is completed</li>
                    <li>Ensure your profile information is accurate and up-to-date</li>
                    <li>Contact the election administrator for any concerns</li>
                </ul>
            </div>
        </div>
    </div>
    
    <footer style="padding: 2rem; text-align: center; margin-top: 4rem;">
        <p style="color: var(--text-muted); font-size: 0.9rem;">© 2024 College E-Voting System. All Rights Reserved.</p>
    </footer>
    <script src="js/main.js"></script>
</body>
</html>
