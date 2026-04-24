// E-Voting System - Main JavaScript
// Utility Functions

// Show loading state
function showLoading(button) {
    if (button) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner spinner-sm"></span> Loading...';
    }
}

// Hide loading state
function hideLoading(button) {
    if (button && button.dataset.originalText) {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText;
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; margin-left: auto; font-size: 1.2rem;">&times;</button>
    `;
    alertDiv.style.display = 'flex';
    alertDiv.style.alignItems = 'center';

    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);

    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Form validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateMobile(mobile) {
    const re = /^[6-9]\d{9}$/;
    return re.test(mobile);
}

function validateStudentId(studentId) {
    const re = /^[A-Z]{2}\d{7}$/;
    return re.test(studentId);
}

// AJAX Helper
async function makeRequest(url, data = {}, method = 'POST') {
    try {
        const options = {
            method: method.toUpperCase()
        };

        let fetchUrl = url;

        // Handle GET parameters
        if (options.method === 'GET' || options.method === 'HEAD') {
            const queryParams = new URLSearchParams();
            if (!(data instanceof FormData)) {
                for (const key in data) {
                    queryParams.append(key, data[key]);
                }
            }
            const queryString = queryParams.toString();
            if (queryString) {
                fetchUrl += (fetchUrl.includes('?') ? '&' : '?') + queryString;
            }
        } else {
            // Handle POST/PUT/DELETE body
            if (data instanceof FormData) {
                options.body = data;
            } else {
                const formData = new FormData();
                for (const key in data) {
                    formData.append(key, data[key]);
                }
                options.body = formData;
            }
        }

        const response = await fetch(fetchUrl, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Request error:', error);
        return { success: false, message: 'Network error. Please try again.' };
    }
}

// OTP Functions
let otpTimer = null;
let otpTimeLeft = 300; // 5 minutes

function startOTPTimer(duration = 300) {
    otpTimeLeft = duration;
    const timerDisplay = document.getElementById('otpTimer');

    if (otpTimer) {
        clearInterval(otpTimer);
    }

    otpTimer = setInterval(() => {
        otpTimeLeft--;

        if (timerDisplay) {
            const minutes = Math.floor(otpTimeLeft / 60);
            const seconds = otpTimeLeft % 60;
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        if (otpTimeLeft <= 0) {
            clearInterval(otpTimer);
            if (timerDisplay) {
                timerDisplay.textContent = 'Expired';
            }
        }
    }, 1000);
}

function stopOTPTimer() {
    if (otpTimer) {
        clearInterval(otpTimer);
        otpTimer = null;
    }
}

// OTP Input Handler
function setupOTPInputs() {
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;

            // Only allow numbers
            if (!/^\d*$/.test(value)) {
                e.target.value = '';
                return;
            }

            // Move to next input
            if (value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            // Move to previous input on backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            const digits = pastedData.replace(/\D/g, '').split('');

            inputs.forEach((input, i) => {
                if (digits[i]) {
                    input.value = digits[i];
                }
            });

            if (digits.length >= inputs.length) {
                inputs[inputs.length - 1].focus();
            }
        });
    });
}

function getOTPValue() {
    const inputs = document.querySelectorAll('.otp-input');
    return Array.from(inputs).map(input => input.value).join('');
}

function clearOTPInputs() {
    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach(input => input.value = '');
    if (inputs.length > 0) {
        inputs[0].focus();
    }
}

// Generate OTP
async function generateOTP(email, mobile, purpose = 'login') {
    const result = await makeRequest('backend_api/otp.php', {
        action: 'generate',
        email: email,
        mobile: mobile,
        purpose: purpose
    });

    if (result.success) {
        console.log('=== OTP GENERATED ===');
        console.log('OTP:', result.data.otp);
        console.log('This is for DEMO purposes only!');
        console.log('In production, OTP will be sent via SMS/Email');
        console.log('====================');

        // Show OTP in alert for demo (remove in production)
        showAlert(`OTP Generated: ${result.data.otp} (Check console for details)`, 'info');

        startOTPTimer(result.data.expires_in || 300);
    }

    return result;
}

// Verify OTP
async function verifyOTP(email, mobile, otp, purpose = 'login') {
    const result = await makeRequest('backend_api/otp.php', {
        action: 'verify',
        email: email,
        mobile: mobile,
        otp: otp,
        purpose: purpose
    });

    if (result.success) {
        stopOTPTimer();
    }

    return result;
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on outside click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// File Upload Preview
function setupFileUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (input && preview) {
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

// Time ago
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`;

    return formatDate(dateString);
}

// Generate avatar color
function generateAvatarColor(name) {
    const colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
        '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52B788'
    ];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
}

// Get initials
function getInitials(name) {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

// Create avatar placeholder
function createAvatarPlaceholder(name) {
    const initials = getInitials(name);
    const color = generateAvatarColor(name);
    return `<div class="user-avatar-placeholder" style="background: ${color}">${initials}</div>`;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Confirm dialog
function confirmAction(message) {
    return confirm(message);
}

// Copy to clipboard
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showAlert('Copied to clipboard!', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showAlert('Failed to copy to clipboard', 'danger');
    }
}

// Export data as CSV
function exportToCSV(data, filename) {
    const csv = data.map(row => Object.values(row).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize tooltips (if using a tooltip library)
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = e.target.dataset.tooltip;
            document.body.appendChild(tooltip);

            const rect = e.target.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
        });

        element.addEventListener('mouseleave', () => {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Setup OTP inputs if present
    if (document.querySelector('.otp-input')) {
        setupOTPInputs();
    }

    // Initialize tooltips
    initTooltips();

    console.log('E-Voting System initialized');
});

// Handle page visibility change (pause timers when tab is hidden)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page is hidden
        if (otpTimer) {
            stopOTPTimer();
        }
    }
});
