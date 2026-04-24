<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Require voter login
requireRole('voter');

$userId = getCurrentUserId();
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Get user details
$user = getUserById($userId);
$hasVoted = hasUserVoted($userId);
$electionActive = isElectionActive();
$election = getElectionStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - E-Voting System</title>
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
                <div class="navbar-user-box">
                    <?php if ($user['photo']): ?>
                        <img src="uploads/voters/<?php echo htmlspecialchars($user['photo']); ?>" alt="<?php echo htmlspecialchars($userName); ?>" class="user-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="user-avatar-placeholder" style="display:none;"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                    <?php else: ?>
                        <div class="user-avatar-placeholder"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="user-role">Voter</div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    <!-- Dashboard -->
    <div class="dashboard" style="padding-top: 3rem;">
        <div class="container">
            <!-- Header -->
            <div class="dashboard-header" style="margin-bottom: 3rem;">
                <h1 class="dashboard-title" style="display: block !important; visibility: visible !important; opacity: 1 !important;">Voter Dashboard</h1>
                <p class="dashboard-subtitle">Cast your vote and make your voice heard</p>
                <?php if ($user['department'] && $user['year']): ?>
                    <div style="margin-top: 15px; padding: 12px; background: rgba(0, 240, 255, 0.1); border-radius: 8px; border-left: 4px solid var(--primary); color: var(--text-primary);">
                        <strong>Your Eligibility:</strong> <?php echo htmlspecialchars($user['department']); ?> - <?php echo htmlspecialchars($user['year']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Elections Section -->
            <div class="stat-card" style="margin-bottom: 2rem; width: 100%;">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Your Elections</h2>
                <div id="electionsContainer">
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <span class="spinner spinner-sm"></span> Loading your eligible elections...
                    </div>
                </div>
            </div>
            
            <!-- Vote Status Banner (Only show if voted) -->
            
            <!-- Candidates Section -->
            <div class="stat-card" style="width: 100%;">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Candidates</h2>
                <div class="candidates-grid" id="candidatesGrid">
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <span class="spinner spinner-sm"></span> Loading candidates...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- OTP Verification Modal -->
    <div class="modal" id="otpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Verify Your Vote</h3>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img id="otpCandidatePhoto" src="" alt="Candidate" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h4 id="otpCandidateName"></h4>
                    <p style="color: #6c757d;" id="otpCandidateInfo"></p>
                </div>
                
                <div style="background: linear-gradient(135deg, #00f0ff 0%, #0099ff 100%); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; border: 2px solid #00d4ff;">
                    <p style="margin: 0; color: #000000; font-size: 0.875rem; font-weight: 600;">
                        📧 An OTP has been generated for verification. Check your browser console (F12) to see the OTP.
                    </p>
                </div>
                
                <div>
                    <label>Enter OTP:</label>
                    <div class="otp-container">
                        <input type="text" class="otp-input" maxlength="1" id="otp1">
                        <input type="text" class="otp-input" maxlength="1" id="otp2">
                        <input type="text" class="otp-input" maxlength="1" id="otp3">
                        <input type="text" class="otp-input" maxlength="1" id="otp4">
                        <input type="text" class="otp-input" maxlength="1" id="otp5">
                        <input type="text" class="otp-input" maxlength="1" id="otp6">
                    </div>
                </div>
                
                <div class="alert alert-warning" style="margin-top: 1rem;">
                    ⚠️ <strong>Important:</strong> You can only vote once. This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" onclick="closeOtpModal()">Cancel</button>
                <button class="btn btn-primary" id="verifyOtpBtn">Verify & Vote</button>
            </div>
        </div>
    </div>
    
    <footer style="padding: 2rem; text-align: center; background: rgba(10, 14, 39, 0.8); border-top: 1px solid rgba(0, 240, 255, 0.1); margin-top: 4rem;">
        <p style="color: var(--text-muted); font-size: 0.9rem;">© 2024 College E-Voting System. All Rights Reserved.</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        let selectedCandidateId = null;
        let activeElectionId = null;
        let generatedOtp = null;
        const hasVotedStatus = <?php echo $hasVoted ? 'true' : 'false'; ?>;
        const electionActiveStatus = <?php echo $electionActive ? 'true' : 'false'; ?>;
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            loadEligibleElections();
            loadCandidates();
            
            // Refresh every 60 seconds
            setInterval(loadEligibleElections, 60000);
            setInterval(loadCandidates, 60000);
        });

        // Load eligible elections for the voter
        async function loadEligibleElections() {
            const container = document.getElementById('electionsContainer');
            try {
                const result = await makeRequest('backend_api/vote.php', { action: 'get_eligible_elections' });
                
                if (result && result.success && result.data && result.data.elections) {
                    displayElections(result.data.elections, result.data.voter_info);
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <div class="empty-state-title">No Active Elections</div>
                            <div class="empty-state-description">${result && result.message ? result.message : 'No elections are currently active for your department/year.'}</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading elections:', error);
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--danger);">Connection error. Please try again.</div>';
            }
        }
        
        // Display elections
        function displayElections(elections, voterInfo) {
            const container = document.getElementById('electionsContainer');
            const classElections = elections.filter(e => e.election_scope === 'class');
            const instituteElections = elections.filter(e => e.election_scope === 'institute');
            
            const oldHtml = container.innerHTML;
            const newHtml = elections.length === 0 ? `
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-title">No Active Elections</div>
                    <div class="empty-state-description">There are no elections available for you at this time.</div>
                </div>
            ` : `
                ${classElections.length > 0 ? `
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: #00d4ff; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                            <span></span> Class Elections
                        </h3>
                        <div style="display: grid; gap: 1rem;">
                            ${classElections.map(election => createElectionCard(election)).join('')}
                        </div>
                    </div>
                ` : ''}
                ${instituteElections.length > 0 ? `
                    <div>
                        <h3 style="color: #00d4ff; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                            <span></span> Institute Elections
                        </h3>
                        <div style="display: grid; gap: 1rem;">
                            ${instituteElections.map(election => createElectionCard(election)).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
            
            if (oldHtml !== newHtml) {
                container.innerHTML = newHtml;
            }
        }
        
        // Create election card HTML
        function createElectionCard(election) {
            const statusColors = {
                'active': '#00ff00',
                'paused': '#ffa500',
                'completed': '#808080',
                'not_started': '#6c757d'
            };
            
            const statusIcons = {
                'active': '✅',
                'paused': '⏸️',
                'completed': '🏁',
                'not_started': '⏳'
            };
            
            const statusColor = statusColors[election.election_status] || '#6c757d';
            const statusIcon = statusIcons[election.election_status] || '⏳';
            
            const targetInfo = election.election_scope === 'class' 
                ? `${election.target_department} - ${election.target_year}`
                : 'All Students';
            
            return `
                <div style="background: rgba(15, 52, 96, 0.6); border: 1px solid rgba(0, 212, 255, 0.3); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; color: #fff;">${election.election_name}</h4>
                            <div style="color: #00d4ff; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <strong>Position:</strong> ${election.position_name}
                            </div>
                            <div style="color: #aaa; font-size: 0.85rem;">
                                <strong>Target:</strong> ${targetInfo}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="background: rgba(${statusColor === '#00ff00' ? '0,255,0' : statusColor === '#ffa500' ? '255,165,0' : '128,128,128'}, 0.2); color: ${statusColor}; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; margin-bottom: 0.5rem;">
                                ${statusIcon} ${election.election_status.replace('_', ' ').toUpperCase()}
                            </div>
                            ${election.has_voted ? `
                                <div style="background: rgba(0,255,0,0.2); color: #00ff00; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">
                                    ✅ Voted
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    ${election.description ? `
                        <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 1rem;">${election.description}</p>
                    ` : ''}
                    
                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                        <div style="color: #aaa; font-size: 0.9rem;">
                            <strong>${election.candidate_count}</strong> Candidates
                        </div>
                    </div>
                    
                    ${election.election_status === 'active' && !election.has_voted ? `
                        <button class="btn btn-primary w-100" onclick="viewElectionCandidates(${election.id}, '${election.election_name}', false)">
                            View Candidates & Vote
                        </button>
                    ` : election.has_voted ? `
                        <button class="btn btn-secondary w-100" onclick="viewElectionCandidates(${election.id}, '${election.election_name}', true)">
                            View Candidates
                        </button>
                    ` : `
                        <button class="btn btn-secondary w-100" disabled>
                            Election ${election.election_status.replace('_', ' ')}
                        </button>
                    `}
                </div>
            `;
        }
        
        // View candidates for a specific election
        async function viewElectionCandidates(electionId, electionName, hasVotedForThisElection = false) {
            activeElectionId = electionId;
            // Scroll to candidates section
            document.getElementById('candidatesGrid').scrollIntoView({ behavior: 'smooth' });
            
            // Load candidates for this election
            const result = await makeRequest('backend_api/vote.php', { 
                action: 'get_election_candidates',
                election_id: electionId
            });
            
            if (result.success) {
                displayCandidates(result.data.candidates, hasVotedForThisElection);
            } else {
                showAlert(result.message, 'danger');
            }
        }
        
        // Load all candidates (legacy function - keeping for compatibility)
        async function loadCandidates() {
            const result = await makeRequest('backend_api/vote.php', { action: 'get_candidates' });
            
            if (result.success) {
                displayCandidates(result.data.candidates);
            } else {
                showAlert(result.message, 'danger');
            }
        }
        
        // Display candidates (WITHOUT vote counts)
        function displayCandidates(candidates, hasVotedForThisElection = false) {
            const grid = document.getElementById('candidatesGrid');
            
            const newHtml = candidates.length === 0 ? 
                '<div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-title">No Candidates</div><div class="empty-state-description">There are no candidates registered yet.</div></div>' :
                candidates.map(candidate => `
                <div class="candidate-card">
                    <img src="uploads/candidates/${candidate.photo}" alt="${candidate.name}" class="candidate-card-image" onerror="this.src='uploads/candidates/default-male.png'; this.onerror=null;">
                    <div class="candidate-card-body">
                        <h3 class="candidate-name">${candidate.name}</h3>
                        <div class="candidate-info">
                            🎓 ${candidate.department} - ${candidate.year}
                        </div>
                        <div class="candidate-info">
                            🆔 ${candidate.student_id}
                        </div>
                        ${candidate.party_name ? `<div class="candidate-info">🚩 ${candidate.party_name}</div>` : ''}
                        ${candidate.manifesto ? `<p class="candidate-description">${candidate.manifesto}</p>` : candidate.address ? `<p class="candidate-description">${candidate.address}</p>` : ''}
                        
                        ${!hasVotedForThisElection && electionActiveStatus ? `
                            <button class="btn btn-primary w-100" onclick="openOtpModal(${candidate.id}, '${candidate.name}', '${candidate.photo}', '${candidate.department}', '${candidate.year}')">
                                Vote for ${candidate.name.split(' ')[0]}
                            </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');

            if (grid.innerHTML !== newHtml) {
                grid.innerHTML = newHtml;
            }
        }
        
        // Open OTP modal and generate OTP
        async function openOtpModal(id, name, photo, department, year) {
            selectedCandidateId = id;
            
            // Generate OTP
            const result = await makeRequest('backend_api/vote.php', {
                action: 'generate_vote_otp',
                candidate_id: id,
                election_id: activeElectionId
            });
            
            if (result.success) {
                generatedOtp = result.data.otp;
                console.log('='.repeat(50));
                console.log('OTP for voting:', generatedOtp);
                console.log('='.repeat(50));
                
                document.getElementById('otpCandidateName').textContent = name;
                document.getElementById('otpCandidatePhoto').src = `uploads/candidates/${photo}`;
                document.getElementById('otpCandidatePhoto').onerror = function() {
                    this.src = 'uploads/candidates/default-male.png';
                };
                document.getElementById('otpCandidateInfo').textContent = `${department} - ${year}`;
                
                // Clear OTP inputs
                for (let i = 1; i <= 6; i++) {
                    document.getElementById(`otp${i}`).value = '';
                }
                
                document.getElementById('otpModal').classList.add('active');
                document.getElementById('otp1').focus();
            } else {
                showAlert(result.message, 'danger');
            }
        }
        
        // Close OTP modal
        function closeOtpModal() {
            document.getElementById('otpModal').classList.remove('active');
            selectedCandidateId = null;
            generatedOtp = null;
        }
        
        // OTP input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Verify OTP and cast vote
        document.getElementById('verifyOtpBtn').addEventListener('click', async function() {
            if (!selectedCandidateId) return;
            
            // Get OTP from inputs
            let enteredOtp = '';
            for (let i = 1; i <= 6; i++) {
                enteredOtp += document.getElementById(`otp${i}`).value;
            }
            
            if (enteredOtp.length !== 6) {
                showAlert('Please enter complete OTP', 'warning');
                return;
            }
            
            showLoading(this);
            
            const result = await makeRequest('backend_api/vote.php', {
                action: 'cast_vote',
                candidate_id: selectedCandidateId,
                election_id: activeElectionId,
                otp: enteredOtp
            });
            
            hideLoading(this);
            closeOtpModal();
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        });
        
        
        
        // Removed redundant load calls here as they are in DOMContentLoaded
    </script>
</body>
</html>
