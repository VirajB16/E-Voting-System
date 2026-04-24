<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    header("Location: " . ($role === 'voter' ? 'voter-dashboard.php' : 'candidate-dashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5rem 2rem 2rem 2rem;
        }
        
        .register-card {
            max-width: 700px;
            width: 100%;
        }
        
        .portal-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .portal-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .role-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-option {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(0, 240, 255, 0.2);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-base);
            position: relative;
        }

        .role-option:hover {
            border-color: var(--primary);
            background: rgba(0, 240, 255, 0.1);
        }

        .role-option.selected {
            border-color: var(--primary);
            background: rgba(0, 240, 255, 0.15);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .role-title {
            font-weight: 700;
            color: var(--white);
        }

        .grid {
            display: grid;
            gap: 1rem;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 640px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card glass-card">
            <div class="portal-header">
                <h2>Create Account</h2>
                <p style="color: var(--text-secondary);">Join the digital voting community</p>
            </div>
            
            <form id="registerForm" enctype="multipart/form-data">
                <div class="role-selector">
                    <label class="role-option selected" for="roleVoter">
                        <input type="radio" name="role" id="roleVoter" value="voter" checked>
                        <div class="role-icon">🗳️</div>
                        <div class="role-title">Voter</div>
                    </label>
                    
                    <label class="role-option" for="roleCandidate">
                        <input type="radio" name="role" id="roleCandidate" value="candidate">
                        <div class="role-icon">👤</div>
                        <div class="role-title">Candidate</div>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="your.email@college.edu" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mobile Number *</label>
                    <input type="tel" class="form-control" name="mobile" id="mobile" placeholder="10-digit mobile number" maxlength="10" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Student ID *</label>
                    <input type="text" class="form-control" name="student_id" id="student_id" placeholder="e.g., CS2023001" maxlength="9" required>
                    <small class="text-muted">Format: Department Code (2 letters) + Year (4 digits) + Number (3 digits)</small>
                </div>
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select class="form-control form-select" name="department" id="department" required>
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
                        <select class="form-control form-select" name="year" id="year" required>
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>
                
                <!-- Election Selection (for candidates only) -->
                <div class="form-group" id="electionGroup" style="display: none;">
                    <label class="form-label">Register for Election *</label>
                    <select class="form-control form-select" name="election_id" id="election_id">
                        <option value="">Loading elections...</option>
                    </select>
                    <small class="text-muted">Select which election you want to run for</small>
                </div>
                
                <div class="form-group" id="addressGroup" style="display: none;">
                    <label class="form-label">Campaign Statement</label>
                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Describe your vision and goals as a candidate"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
                    <img id="photoPreview" class="photo-preview" alt="Photo Preview">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Minimum 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    Register
                </button>
                
                <p class="text-center mt-3">
                    Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600;">Login here</a>
                </p>
            </form>
        </div>
    </div>
    
    <footer style="padding: 2rem; text-align: center; margin-top: 4rem;">
        <p style="color: var(--text-muted); font-size: 0.9rem;">© 2024 College E-Voting System. All Rights Reserved.</p>
    </footer>
    <script src="js/main.js"></script>
    <script>
        // Role selector
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                const role = this.querySelector('input').value;
                const addressGroup = document.getElementById('addressGroup');
                const electionGroup = document.getElementById('electionGroup');
                
                if (role === 'candidate') {
                    addressGroup.style.display = 'block';
                    electionGroup.style.display = 'block';
                    loadActiveElections();
                } else {
                    addressGroup.style.display = 'none';
                    electionGroup.style.display = 'none';
                }
            });
        });
        
        // Load active elections for candidate registration
        async function loadActiveElections() {
            try {
                const result = await makeRequest('backend_api/elections.php?action=get_active_elections', {}, 'GET');
                
                console.log('Elections API response:', result);
                
                if (result.success && result.data && result.data.elections) {
                    const elections = result.data.elections;
                    const selector = document.getElementById('election_id');
                    
                    if (elections.length === 0) {
                        selector.innerHTML = '<option value="">No active elections available</option>';
                        return;
                    }
                    
                    selector.innerHTML = '<option value="">-- Select an election --</option>' +
                        elections.map(election => {
                            const scope = election.election_scope === 'class' ? '🏫' : '🎓';
                            const target = election.election_scope === 'class' 
                                ? `${election.target_department} - ${election.target_year}`
                                : 'All Students';
                            return `<option value="${election.id}">${scope} ${election.election_name} (${election.position_name}) - ${target}</option>`;
                        }).join('');
                } else {
                    console.error('Failed to load elections:', result);
                    document.getElementById('election_id').innerHTML = '<option value="">Error loading elections</option>';
                }
            } catch (error) {
                console.error('Error loading elections:', error);
                document.getElementById('election_id').innerHTML = '<option value="">Error loading elections</option>';
            }
        }
        
        // Photo preview
        setupFileUpload('photo', 'photoPreview');
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            
            // Validate
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match!', 'danger');
                return;
            }
            
            if (password.length < 6) {
                showAlert('Password must be at least 6 characters long!', 'danger');
                return;
            }
            
            const mobile = document.getElementById('mobile').value;
            if (!validateMobile(mobile)) {
                showAlert('Invalid mobile number! Must be 10 digits starting with 6-9', 'danger');
                return;
            }
            
            const studentId = document.getElementById('student_id').value;
            if (!validateStudentId(studentId)) {
                showAlert('Invalid student ID format! Use format: CS2023001', 'danger');
                return;
            }
            
            showLoading(submitBtn);
            
            formData.append('action', 'register');
            
            const result = await makeRequest('backend_api/auth.php', Object.fromEntries(formData));
            
            hideLoading(submitBtn);
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => {
                    window.location.href = result.data.redirect;
                }, 1500);
            } else {
                showAlert(result.message, 'danger');
            }
        });
    </script>
</body>
</html>
