/**
 * Lightsail QBR Application - Main JavaScript
 * Provides interactive functionality and enhanced user experience
 */

// Global application object
const LightsailQBR = {
    // Configuration
    config: {
        animationDuration: 300,
        debounceDelay: 500,
        maxRetries: 3
    },

    // Initialize the application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
        this.initializeVoting();
        this.setupSearch();
        this.initializeTooltips();
        this.setupAutoRefresh();
        console.log('Lightsail QBR Application initialized');
    },

    // Setup global event listeners
    setupEventListeners: function() {
        // Handle navigation active states
        this.updateActiveNavigation();
        
        // Handle form submissions with loading states
        document.addEventListener('submit', this.handleFormSubmission.bind(this));
        
        // Handle dynamic content loading
        document.addEventListener('click', this.handleDynamicActions.bind(this));
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', this.handleKeyboardShortcuts.bind(this));
        
        // Handle window resize for responsive adjustments
        window.addEventListener('resize', this.debounce(this.handleResize.bind(this), 250));
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    },

    // Initialize UI components
    initializeComponents: function() {
        this.initializeCards();
        this.initializeModals();
        this.initializeAlerts();
        this.setupProgressBars();
        this.initializeCounters();
    },

    // Initialize interactive cards
    initializeCards: function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            // Add hover effects
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    },

    // Initialize modals
    initializeModals: function() {
        // Auto-focus first input in modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const firstInput = this.querySelector('input, textarea, select');
                if (firstInput) {
                    firstInput.focus();
                }
            });
        });
    },

    // Initialize alert auto-dismiss
    initializeAlerts: function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                this.fadeOut(alert);
            }, 5000);
        });
    },

    // Setup progress bars animation
    setupProgressBars: function() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.getAttribute('aria-valuenow');
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width + '%';
            }, 100);
        });
    },

    // Initialize animated counters
    initializeCounters: function() {
        const counters = document.querySelectorAll('.stats-number');
        counters.forEach(counter => {
            this.animateCounter(counter);
        });
    },

    // Animate counter numbers
    animateCounter: function(element) {
        const target = parseInt(element.textContent);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 16);
    },

    // Setup form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearFieldError(input));
            });
        });
    },

    // Validate form before submission
    validateForm: function(event) {
        const form = event.target;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
            this.showNotification('Please correct the errors before submitting.', 'error');
        }
    },

    // Validate individual field
    validateField: function(field) {
        const value = field.value.trim();
        const type = field.type;
        let isValid = true;
        let message = '';

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required.';
        }

        // Email validation
        if (type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address.';
        }

        // Password validation
        if (type === 'password' && value && value.length < 6) {
            isValid = false;
            message = 'Password must be at least 6 characters long.';
        }

        // Password confirmation
        if (field.name === 'confirm_password') {
            const password = document.querySelector('input[name="password"]');
            if (password && value !== password.value) {
                isValid = false;
                message = 'Passwords do not match.';
            }
        }

        this.setFieldValidation(field, isValid, message);
        return isValid;
    },

    // Set field validation state
    setFieldValidation: function(field, isValid, message) {
        const feedback = field.parentNode.querySelector('.invalid-feedback') || 
                        this.createFeedbackElement(field);

        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            feedback.textContent = '';
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            feedback.textContent = message;
        }
    },

    // Clear field error state
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = '';
        }
    },

    // Create feedback element
    createFeedbackElement: function(field) {
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
        return feedback;
    },

    // Initialize voting system
    initializeVoting: function() {
        const voteButtons = document.querySelectorAll('.vote-btn');
        voteButtons.forEach(button => {
            button.addEventListener('click', this.handleVote.bind(this));
        });
    },

    // Handle vote submission
    handleVote: function(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const projectId = button.dataset.projectId;
        const voteType = button.dataset.voteType;

        if (button.disabled) return;

        // Disable button to prevent double-clicking
        button.disabled = true;
        button.innerHTML = '<span class="loading"></span>';

        // Simulate API call (replace with actual AJAX call)
        setTimeout(() => {
            this.updateVoteDisplay(projectId, voteType);
            button.disabled = false;
            button.innerHTML = voteType === 'up' ? '👍' : '👎';
            this.showNotification('Vote recorded successfully!', 'success');
        }, 500);
    },

    // Update vote display
    updateVoteDisplay: function(projectId, voteType) {
        const voteCount = document.querySelector(`[data-project-id="${projectId}"] .vote-count`);
        if (voteCount) {
            let count = parseInt(voteCount.textContent);
            count += voteType === 'up' ? 1 : -1;
            voteCount.textContent = count;
            
            // Animate the change
            voteCount.style.transform = 'scale(1.2)';
            setTimeout(() => {
                voteCount.style.transform = 'scale(1)';
            }, 200);
        }
    },

    // Setup search functionality
    setupSearch: function() {
        const searchInputs = document.querySelectorAll('input[data-search="true"]');
        searchInputs.forEach(input => {
            input.addEventListener('input', this.debounce(this.handleSearch.bind(this), this.config.debounceDelay));
        });
    },

    // Handle search functionality
    handleSearch: function(event) {
        const query = event.target.value.toLowerCase();
        const targetSelector = event.target.dataset.searchTarget;
        const items = document.querySelectorAll(targetSelector);

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = text.includes(query);
            
            if (matches) {
                this.fadeIn(item);
            } else {
                this.fadeOut(item);
            }
        });
    },

    // Initialize tooltips
    initializeTooltips: function() {
        // Initialize Bootstrap tooltips if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },

    // Setup auto-refresh for dynamic content
    setupAutoRefresh: function() {
        const refreshElements = document.querySelectorAll('[data-auto-refresh]');
        refreshElements.forEach(element => {
            const interval = parseInt(element.dataset.autoRefresh) * 1000;
            setInterval(() => {
                this.refreshElement(element);
            }, interval);
        });
    },

    // Refresh element content
    refreshElement: function(element) {
        // Add loading indicator
        const originalContent = element.innerHTML;
        element.innerHTML = '<span class="loading"></span> Updating...';

        // Simulate content refresh (replace with actual AJAX call)
        setTimeout(() => {
            element.innerHTML = originalContent;
            this.showNotification('Content updated', 'info');
        }, 1000);
    },

    // Handle form submissions with loading states
    handleFormSubmission: function(event) {
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        
        if (submitButton && !submitButton.dataset.noLoading) {
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="loading"></span> Processing...';
            
            // Re-enable after form submission (in case of validation errors)
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }, 3000);
        }
    },

    // Handle dynamic actions
    handleDynamicActions: function(event) {
        const target = event.target;
        
        // Handle delete confirmations
        if (target.classList.contains('btn-delete') || target.dataset.action === 'delete') {
            event.preventDefault();
            this.confirmDelete(target);
        }
        
        // Handle status updates
        if (target.classList.contains('status-toggle')) {
            event.preventDefault();
            this.toggleStatus(target);
        }
        
        // Handle quick actions
        if (target.dataset.quickAction) {
            event.preventDefault();
            this.handleQuickAction(target);
        }
    },

    // Confirm delete action
    confirmDelete: function(element) {
        const itemName = element.dataset.itemName || 'this item';
        const message = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
        
        if (confirm(message)) {
            // Add loading state
            element.innerHTML = '<span class="loading"></span>';
            element.disabled = true;
            
            // Proceed with deletion (replace with actual AJAX call)
            setTimeout(() => {
                const row = element.closest('tr, .card, .list-item');
                if (row) {
                    this.fadeOut(row, () => row.remove());
                }
                this.showNotification(`${itemName} deleted successfully`, 'success');
            }, 500);
        }
    },

    // Toggle status
    toggleStatus: function(element) {
        const currentStatus = element.dataset.currentStatus;
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        element.innerHTML = '<span class="loading"></span>';
        element.disabled = true;
        
        // Simulate status update (replace with actual AJAX call)
        setTimeout(() => {
            element.dataset.currentStatus = newStatus;
            element.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            element.className = `btn btn-sm ${newStatus === 'active' ? 'btn-success' : 'btn-secondary'}`;
            element.disabled = false;
            this.showNotification('Status updated successfully', 'success');
        }, 500);
    },

    // Handle quick actions
    handleQuickAction: function(element) {
        const action = element.dataset.quickAction;
        const originalText = element.textContent;
        
        element.innerHTML = '<span class="loading"></span>';
        element.disabled = true;
        
        // Simulate action (replace with actual AJAX call)
        setTimeout(() => {
            element.textContent = originalText;
            element.disabled = false;
            this.showNotification(`${action} completed successfully`, 'success');
        }, 1000);
    },

    // Handle keyboard shortcuts
    handleKeyboardShortcuts: function(event) {
        // Ctrl/Cmd + K for search
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            const searchInput = document.querySelector('input[type="search"], input[data-search="true"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close modals
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(openModal);
                if (modal) modal.hide();
            }
        }
    },

    // Handle window resize
    handleResize: function() {
        // Adjust layout for mobile
        const isMobile = window.innerWidth < 768;
        document.body.classList.toggle('mobile-layout', isMobile);
        
        // Recalculate any dynamic layouts
        this.recalculateLayouts();
    },

    // Handle page visibility changes
    handleVisibilityChange: function() {
        if (document.hidden) {
            // Page is hidden, pause auto-refresh
            this.pauseAutoRefresh();
        } else {
            // Page is visible, resume auto-refresh
            this.resumeAutoRefresh();
        }
    },

    // Update active navigation
    updateActiveNavigation: function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href)) {
                link.classList.add('active');
            }
        });
    },

    // Recalculate layouts
    recalculateLayouts: function() {
        // Adjust card heights for equal height rows
        const cardRows = document.querySelectorAll('.row');
        cardRows.forEach(row => {
            const cards = row.querySelectorAll('.card');
            if (cards.length > 1) {
                let maxHeight = 0;
                cards.forEach(card => {
                    card.style.height = 'auto';
                    maxHeight = Math.max(maxHeight, card.offsetHeight);
                });
                cards.forEach(card => {
                    card.style.height = maxHeight + 'px';
                });
            }
        });
    },

    // Pause auto-refresh
    pauseAutoRefresh: function() {
        this.autoRefreshPaused = true;
    },

    // Resume auto-refresh
    resumeAutoRefresh: function() {
        this.autoRefreshPaused = false;
    },

    // Utility function: Debounce
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Utility function: Fade in element
    fadeIn: function(element, callback) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        const fade = () => {
            let opacity = parseFloat(element.style.opacity);
            if ((opacity += 0.1) <= 1) {
                element.style.opacity = opacity;
                requestAnimationFrame(fade);
            } else if (callback) {
                callback();
            }
        };
        requestAnimationFrame(fade);
    },

    // Utility function: Fade out element
    fadeOut: function(element, callback) {
        const fade = () => {
            let opacity = parseFloat(element.style.opacity) || 1;
            if ((opacity -= 0.1) >= 0) {
                element.style.opacity = opacity;
                requestAnimationFrame(fade);
            } else {
                element.style.display = 'none';
                if (callback) callback();
            }
        };
        requestAnimationFrame(fade);
    },

    // Utility function: Show notification
    showNotification: function(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show notification`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to page
        const container = document.querySelector('.notification-container') || document.body;
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            this.fadeOut(notification, () => notification.remove());
        }, 5000);
    },

    // Utility function: Validate email
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // AJAX helper function
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaults, ...options };
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
                throw error;
            });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    LightsailQBR.init();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LightsailQBR;
}
