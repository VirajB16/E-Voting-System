<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Require admin login
requireAdminLogin();

$adminName = $_SESSION['admin_name'];
$adminRole = $_SESSION['admin_role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/text-visibility-fix.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .admin-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: all var(--transition-base);
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: var(--white);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        
        .user-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-row:hover {
            background: rgba(0, 240, 255, 0.05);
        }
        
        .status-active { color: var(--success); }
        .status-inactive { color: var(--gray-500); }
        .status-suspended { color: var(--danger); }
        
        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead {
            background: rgba(0, 240, 255, 0.1);
            border-bottom: 2px solid var(--primary);
        }
        
        .table thead th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.875rem;
        }
        
        .table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table tbody tr:hover {
            background: rgba(0, 240, 255, 0.05);
        }
        
        .table tbody td {
            padding: 1rem 1.5rem;
            color: var(--white);
        }
        
        .table tbody td:first-child {
            font-weight: 600;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-content">
            <div class="navbar-brand">🗳️ E-Voting Admin</div>
            <div class="navbar-menu">
                <div class="navbar-user">
                    <div style="text-align: right; margin-right: 1rem;">
                        <div class="fw-semibold" style="color: white;"><?php echo htmlspecialchars($adminName); ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo ucfirst($adminRole); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Dashboard -->
    <div class="dashboard">
        <div class="container">
            <!-- Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">Admin Dashboard</h1>
                <p class="dashboard-subtitle">Manage elections and monitor voting activity</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value" id="totalVoters">0</div>
                            <div class="stat-label">Total Voters</div>
                        </div>
                        <div class="stat-icon">👥</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value" id="totalCandidates">0</div>
                            <div class="stat-label">Total Candidates</div>
                        </div>
                        <div class="stat-icon">🎯</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value" id="totalVotes">0</div>
                            <div class="stat-label">Votes Cast</div>
                        </div>
                        <div class="stat-icon">🗳️</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value" id="turnoutPercentage">0%</div>
                            <div class="stat-label">Turnout</div>
                        </div>
                        <div class="stat-icon">📊</div>
                    </div>
                </div>
            </div>
            
            <!-- Election Management -->
            <div class="glass-card mb-4">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="margin: 0;">📋 Election Management</h2>
                    <button class="btn btn-primary" onclick="openCreateElectionModal()">
                        ➕ Create New Election
                    </button>
                </div>
                
                <!-- Election Selector -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="electionSelector" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select Election:</label>
                    <select id="electionSelector" class="form-control" style="max-width: 500px;" onchange="loadSelectedElectionDetails()">
                        <option value="">Loading elections...</option>
                    </select>
                </div>
                
                <!-- Selected Election Details -->
                <div id="selectedElectionDetails" style="display: none; background: rgba(0, 212, 255, 0.1); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(0, 240, 255, 0.2);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem;">
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Election</div>
                            <div id="detailElectionName" style="font-weight: 700; color: white;">-</div>
                        </div>
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Position</div>
                            <div id="detailPosition" style="font-weight: 700; color: white;">-</div>
                        </div>
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Status</div>
                            <div id="detailStatus" style="font-weight: 700;">-</div>
                        </div>
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Voter Scope</div>
                            <div id="detailScope" style="font-weight: 700; color: white;">-</div>
                        </div>
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Candidates</div>
                            <div id="detailCandidates" style="font-weight: 700; color: var(--primary);">0</div>
                        </div>
                        <div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Votes Cast</div>
                            <div id="detailVotes" style="font-weight: 700; color: var(--primary);">0</div>
                        </div>
                    </div>
                </div>
                
                <!-- Election Controls -->
                <div class="admin-controls">
                    <button class="btn btn-success" onclick="updateSelectedElectionStatus('active')">
                        ▶️ Start
                    </button>
                    <button class="btn btn-warning" onclick="updateSelectedElectionStatus('paused')">
                        ⏸️ Pause
                    </button>
                    <button class="btn btn-danger" onclick="updateSelectedElectionStatus('completed')">
                        🏁 End
                    </button>
                    <button class="btn btn-info" onclick="viewElectionResults()">
                        📊 Results
                    </button>
                    <button class="btn btn-outline" onclick="refreshData()">
                        🔄 Refresh
                    </button>
                </div>
                <div id="electionStatusDisplay" class="mt-3"></div>
            </div>
            
            <!-- Tabs -->
            <div class="glass-card">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('overview')">📊 Overview</button>
                    <button class="tab-btn" onclick="switchTab('voters')">🗳️ Voters</button>
                    <button class="tab-btn" onclick="switchTab('candidates')">👥 Candidates</button>
                    <button class="tab-btn" onclick="switchTab('results')">🏆 Results</button>
                    <button class="tab-btn" onclick="switchTab('audit')">📋 Audit Log</button>
                </div>
                
                <!-- Overview Tab -->
                <div class="tab-content active" id="overview">
                    <h3 style="margin-bottom: 1rem;">Top Candidates</h3>
                    <div class="table-container" style="background: transparent; padding: 0; box-shadow: none;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Votes</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody id="topCandidatesBody">
                                <tr><td colspan="5" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Voters Tab -->
                <div class="tab-content" id="voters">
                    <h3 style="margin-bottom: 1rem;">Registered Voters</h3>
                    <div id="votersList">Loading...</div>
                </div>
                
                <!-- Candidates Tab -->
                <div class="tab-content" id="candidates">
                    <h3 style="margin-bottom: 1rem;">Registered Candidates</h3>
                    <div id="candidatesList">Loading...</div>
                </div>
                
                <!-- Results Tab -->
                <div class="tab-content" id="results">
                    <h3 style="margin-bottom: 1rem;">Election Results</h3>
                    <div id="resultsList">Loading...</div>
                </div>
                
                <!-- Audit Log Tab -->
                <div class="tab-content" id="audit">
                    <h3 style="margin-bottom: 1rem;">Recent Activity</h3>
                    <div class="table-container" style="background: transparent; padding: 0; box-shadow: none;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User Type</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="auditLogBody">
                                <tr><td colspan="4" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Election Modal -->
    <div class="modal" id="createElectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Election</h3>
                <button class="close-modal" onclick="closeCreateElectionModal()">×</button>
            </div>
            <form id="createElectionForm">
                <div class="form-group">
                    <label class="form-label">Election Name *</label>
                    <input type="text" class="form-control" name="election_name" placeholder="e.g., CR Election - CS 3rd Year" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Position Name *</label>
                    <input type="text" class="form-control" name="position_name" placeholder="e.g., Class Representative" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Election Level *</label>
                    <select class="form-control" name="election_scope" id="election_scope" onchange="toggleElectionScopeFields()" required>
                        <option value="institute">Institute Level (All Students)</option>
                        <option value="class">Class Level (Department + Year)</option>
                    </select>
                </div>
                
                <div id="classLevelFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select class="form-control" name="target_department" id="target_department">
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Artificial Intelligence-Machine Learning">Artificial Intelligence-Machine Learning</option>
                            <option value="Information Technology">Information Technology</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Year *</label>
                        <select class="form-control" name="target_year" id="target_year">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Additional details about this election"></textarea>
                </div>
                
                <div class="mt-4" style="display: flex; gap: 1rem;">
                    <button type="button" class="btn btn-light w-100" onclick="closeCreateElectionModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary w-100">Create Election</button>
                </div>
            </form>
        </div>
    </div>

    <footer style="padding: 2rem; text-align: center; margin-top: 4rem;">
        <p style="color: var(--text-muted); font-size: 0.9rem;">© 2024 College E-Voting System. All Rights Reserved.</p>
    </footer>
    <script src="js/main.js"></script>
    <script>
        let currentElectionStatus = null;
        
        // Switch tabs
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
            
            // Load data for the tab
            if (tabName === 'voters') loadUsers('voter');
            if (tabName === 'candidates') loadUsers('candidate');
            if (tabName === 'results') loadResults();
            if (tabName === 'audit') loadAuditLog();
        }
        
        // Load dashboard stats
        async function loadDashboardStats() {
            const result = await makeRequest('backend_api/admin.php', { action: 'get_dashboard_stats' });
            
            if (result.success) {
                const stats = result.data.statistics;
                const election = result.data.election;
                
                document.getElementById('totalVoters').textContent = stats.total_voters;
                document.getElementById('totalCandidates').textContent = stats.total_candidates;
                document.getElementById('totalVotes').textContent = stats.total_votes;
                document.getElementById('turnoutPercentage').textContent = stats.turnout_percentage + '%';
                
                currentElectionStatus = election;
                displayElectionStatus(election);
                
                // Display top candidates
                if (result.data.top_candidates) {
                    displayTopCandidates(result.data.top_candidates);
                }
            }
        }
        
        // Display election status
        function displayElectionStatus(election) {
            const statusDiv = document.getElementById('electionStatusDisplay');
            const statusClass = election.election_status === 'active' ? 'success' : 
                              election.election_status === 'paused' ? 'warning' : 'info';
            
            statusDiv.innerHTML = `
                <div class="alert alert-${statusClass}">
                    <strong>Current Status:</strong> ${election.election_status.toUpperCase()}<br>
                    <strong>Election:</strong> ${election.election_name}<br>
                    <strong>Results Visible:</strong> ${election.show_results == 1 ? 'Yes' : 'No'}
                </div>
            `;
        }
        
        // Display top candidates
        function displayTopCandidates(candidates) {
            const tbody = document.getElementById('topCandidatesBody');
            const totalVotes = candidates.reduce((sum, c) => sum + parseInt(c.vote_count), 0);
            
            const oldHtml = tbody.innerHTML;
            const newHtml = candidates.length === 0 ? 
                '<tr><td colspan="5" class="text-center">No candidates yet</td></tr>' :
                candidates.map((candidate, index) => {
                    const percentage = totalVotes > 0 ? ((candidate.vote_count / totalVotes) * 100).toFixed(1) : 0;
                    const rankEmoji = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
                    
                    return `
                        <tr>
                            <td>${rankEmoji} #${index + 1}</td>
                            <td>${candidate.name}</td>
                            <td>${candidate.department}</td>
                            <td><strong>${candidate.vote_count}</strong></td>
                            <td>${percentage}%</td>
                        </tr>
                    `;
                }).join('');
            
            if (oldHtml !== newHtml) {
                tbody.innerHTML = newHtml;
            }
        }
        
        // Load users
        async function loadUsers(role) {
            const result = await makeRequest('backend_api/admin.php', { action: 'get_all_users', role: role });
            
            if (result.success) {
                const listId = role === 'voter' ? 'votersList' : 'candidatesList';
                const listDiv = document.getElementById(listId);
                const oldHtml = listDiv.innerHTML;
                
                const newHtml = result.data.users.length === 0 ? 
                    '<p class="text-center text-muted">No users found</p>' :
                    result.data.users.map(user => `
                    <div class="user-row">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="uploads/${role}s/${user.photo}" alt="${user.name}" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                 onerror="this.src='uploads/${role}s/default-male.png'">
                            <div>
                                <strong>${user.name}</strong><br>
                                <small class="text-muted">${user.email} | ${user.student_id}</small><br>
                                <small>${user.department} - ${user.year}</small>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-${user.status === 'active' ? 'success' : 'danger'}">
                                ${user.status}
                            </span>
                            <button class="btn btn-sm btn-outline" onclick="toggleUserStatus(${user.id}, '${user.status}')">
                                ${user.status === 'active' ? 'Suspend' : 'Activate'}
                            </button>
                        </div>
                    </div>
                `).join('');
                
                if (oldHtml !== newHtml) {
                    listDiv.innerHTML = newHtml;
                }
            }
        }
        
        // Load results
        async function loadResults() {
            const resultsDiv = document.getElementById('resultsList');
            resultsDiv.innerHTML = '<p class="text-center">Loading results...</p>';
            
            try {
                const result = await makeRequest('backend_api/vote.php', { action: 'get_candidates' });
                
                console.log('Results API response:', result);
                
                if (result.success) {
                    const candidates = result.data.candidates.sort((a, b) => b.vote_count - a.vote_count);
                    const totalVotes = candidates.reduce((sum, c) => sum + parseInt(c.vote_count || 0), 0);
                    
                    if (candidates.length === 0) {
                        resultsDiv.innerHTML = '<p class="text-center text-muted">No candidates registered yet</p>';
                        return;
                    }
                    
                    resultsDiv.innerHTML = candidates.map((candidate, index) => {
                        const percentage = totalVotes > 0 ? ((candidate.vote_count / totalVotes) * 100).toFixed(1) : 0;
                        const rankEmoji = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
                        
                        return `
                            <div class="candidate-card" style="margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem;">
                                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">
                                        ${rankEmoji} #${index + 1}
                                    </div>
                                    <img src="uploads/candidates/${candidate.photo}" alt="${candidate.name}" 
                                         style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;"
                                         onerror="this.src='uploads/candidates/default-candidate.jpg'">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0; color: white;">${candidate.name}</h4>
                                        <p style="margin: 0; color: #aaa;">${candidate.department} - ${candidate.year}</p>
                                        <div class="progress mt-2" style="background: rgba(255,255,255,0.1); height: 20px; border-radius: 10px; overflow: hidden;">
                                            <div class="progress-bar" style="width: ${percentage}%; background: linear-gradient(90deg, #00f0ff, #0099ff); height: 100%; transition: width 0.3s;"></div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">
                                            ${candidate.vote_count || 0}
                                        </div>
                                        <div style="color: #aaa;">${percentage}%</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    resultsDiv.innerHTML = `<p class="text-center text-danger">Error: ${result.message || 'Failed to load results'}</p>`;
                }
            } catch (error) {
                console.error('Error loading results:', error);
                resultsDiv.innerHTML = '<p class="text-center text-danger">Error loading results. Please try again.</p>';
            }
        }
        
        // Load audit log
        async function loadAuditLog() {
            const result = await makeRequest('backend_api/admin.php', { action: 'get_audit_logs', limit: 50 });
            
            if (result.success) {
                const tbody = document.getElementById('auditLogBody');
                
                if (result.data.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No activity yet</td></tr>';
                    return;
                }
                
                tbody.innerHTML = result.data.logs.map(log => `
                    <tr>
                        <td>${timeAgo(log.created_at)}</td>
                        <td><span class="badge badge-primary">${log.user_type}</span></td>
                        <td>${log.action}</td>
                        <td>${log.description || '-'}</td>
                    </tr>
                `).join('');
            }
        }
        
        // Update election status
        async function updateSelectedElectionStatus(status) {
            if (!selectedElectionId) {
                showAlert('Please select an election first', 'warning');
                return;
            }
            
            const election = allElections.find(e => e.id == selectedElectionId);
            if (!election) return;
            
            if (!confirm(`Are you sure you want to ${status} the election "${election.election_name}"?`)) return;
            
            try {
                const result = await makeRequest('backend_api/admin.php', {
                    action: 'update_election',
                    election_id: selectedElectionId,
                    election_status: status
                });
                
                if (result && result.success) {
                    showAlert(result.message || 'Election status updated successfully', 'success');
                    loadElectionsDropdown();
                    loadDashboardStats();
                } else {
                    showAlert(result?.message || 'Failed to update election status', 'danger');
                }
            } catch (error) {
                console.error('Error updating election status:', error);
                showAlert('Network error. Please check your connection and try again.', 'danger');
            }
        }
        
        // Toggle results visibility
        async function toggleResults() {
            const showResults = currentElectionStatus.show_results == 1 ? 0 : 1;
            
            const result = await makeRequest('backend_api/admin.php', {
                action: 'update_election_status',
                status: currentElectionStatus.election_status,
                show_results: showResults
            });
            
            if (result.success) {
                showAlert('Results visibility updated', 'success');
                loadDashboardStats();
            } else {
                showAlert(result.message, 'danger');
            }
        }
        
        // Toggle user status
        async function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
            
            const result = await makeRequest('backend_api/admin.php', {
                action: 'update_user_status',
                user_id: userId,
                status: newStatus
            });
            
            if (result.success) {
                showAlert(result.message, 'success');
                // Reload current tab
                const activeTab = document.querySelector('.tab-content.active').id;
                if (activeTab === 'voters') loadUsers('voter');
                if (activeTab === 'candidates') loadUsers('candidate');
            } else {
                showAlert(result.message, 'danger');
            }
        }
        
        // Refresh all data
        function refreshData() {
            loadDashboardStats();
            loadElectionsDropdown();
            const activeTab = document.querySelector('.tab-content.active').id;
            if (activeTab === 'voters') loadUsers('voter');
            if (activeTab === 'candidates') loadUsers('candidate');
            if (activeTab === 'results') loadResults();
            if (activeTab === 'audit') loadAuditLog();
            showAlert('Data refreshed', 'success');
        }
        
        
        // Load elections into dropdown
        let allElections = [];
        let selectedElectionId = null;
        
        async function loadElectionsDropdown() {
            try {
                const result = await makeRequest('backend_api/admin.php?action=get_all_elections');
                
                if (result.success && result.data && result.data.elections) {
                    allElections = result.data.elections;
                    const selector = document.getElementById('electionSelector');
                    
                    if (allElections.length === 0) {
                        selector.innerHTML = '<option value="">No elections found</option>';
                        return;
                    }
                    
                    selector.innerHTML = '<option value="">-- Select an election --</option>' +
                        allElections.map(election => {
                            const scope = election.election_scope === 'class' ? '🏫' : '🎓';
                            const target = election.election_scope === 'class' 
                                ? `${election.target_department} - ${election.target_year}`
                                : 'All Students';
                            return `<option value="${election.id}">${scope} ${election.election_name} (${target})</option>`;
                        }).join('');
                    
                    // Auto-select first election
                    if (allElections.length > 0) {
                        selector.value = allElections[0].id;
                        loadSelectedElectionDetails();
                    }
                }
            } catch (error) {
                console.error('Error loading elections:', error);
            }
        }
        
        // Load selected election details
        async function loadSelectedElectionDetails() {
            const selector = document.getElementById('electionSelector');
            selectedElectionId = selector.value;
            
            if (!selectedElectionId) {
                document.getElementById('selectedElectionDetails').style.display = 'none';
                return;
            }
            
            const election = allElections.find(e => e.id == selectedElectionId);
            if (!election) return;
            
            // Get candidates and votes count
            try {
                const [candidatesResult, resultsResult] = await Promise.all([
                    makeRequest('backend_api/admin.php?action=get_election_candidates&election_id=' + selectedElectionId),
                    makeRequest('backend_api/admin.php?action=get_election_results&election_id=' + selectedElectionId)
                ]);
                
                const candidateCount = candidatesResult.success ? candidatesResult.data.candidates.length : 0;
                const voteCount = resultsResult.success ? resultsResult.data.total_votes : 0;
                
                // Update details display
                document.getElementById('detailElectionName').textContent = election.election_name;
                document.getElementById('detailPosition').textContent = election.position_name || '-';
                
                const scopeBadge = election.election_scope === 'class' ? '🏫 Class' : '🎓 Institute';
                const target = election.election_scope === 'class'
                    ? `${election.target_department} - ${election.target_year}`
                    : 'All Students';
                document.getElementById('detailScope').textContent = `${scopeBadge} (${target})`;
                
                const statusColors = {
                    'active': 'green',
                    'paused': 'orange',
                    'completed': 'gray',
                    'not_started': 'gray'
                };
                const statusColor = statusColors[election.election_status] || 'gray';
                document.getElementById('detailStatus').innerHTML = 
                    `<span style="color: ${statusColor}; font-weight: bold;">${election.election_status.toUpperCase().replace('_', ' ')}</span>`;
                
                document.getElementById('detailCandidates').textContent = candidateCount;
                document.getElementById('detailVotes').textContent = voteCount;
                
                document.getElementById('selectedElectionDetails').style.display = 'block';
            } catch (error) {
                console.error('Error loading election details:', error);
            }
        }
        
        // View election results
        function viewElectionResults() {
            if (!selectedElectionId) {
                showAlert('Please select an election first', 'warning');
                return;
            }
            
            // Switch to results tab and filter by election
            switchTab('results');
            // TODO: Filter results by selected election
        }
        
        // Create Election Functions
        function openCreateElectionModal() {
            document.getElementById('createElectionModal').classList.add('active');
        }
        
        function closeCreateElectionModal() {
            document.getElementById('createElectionModal').classList.remove('active');
            document.getElementById('createElectionForm').reset();
            toggleElectionScopeFields();
        }
        
        function toggleElectionScopeFields() {
            const scope = document.getElementById('election_scope').value;
            const classFields = document.getElementById('classLevelFields');
            const deptInput = document.getElementById('target_department');
            const yearInput = document.getElementById('target_year');
            
            if (scope === 'class') {
                classFields.style.display = 'block';
                deptInput.required = true;
                yearInput.required = true;
            } else {
                classFields.style.display = 'none';
                deptInput.required = false;
                yearInput.required = false;
            }
        }
        
        // Handle Create Election Form Submission
        document.addEventListener('DOMContentLoaded', function() {
            const createForm = document.getElementById('createElectionForm');
            if (createForm) {
                createForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'create_election');
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Creating...';
                    
                    try {
                        const result = await makeRequest('backend_api/admin.php', formData);
                        
                        if (result.success) {
                            showAlert('Election created successfully!', 'success');
                            closeCreateElectionModal();
                            loadElectionsDropdown();
                            loadDashboardStats();
                        } else {
                            showAlert(result.message || 'Failed to create election', 'danger');
                        }
                    } catch (error) {
                        console.error('Error creating election:', error);
                        showAlert('An error occurred. Please try again.', 'danger');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            }
        });
        
        // Load initial data
        loadDashboardStats();
        loadElectionsDropdown();
        
        // Auto-refresh every 30 seconds
        setInterval(loadDashboardStats, 30000);
    </script>
</body>
</html>
