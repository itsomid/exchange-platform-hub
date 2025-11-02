/**
 * Animated Tooltip Component
 * A reusable component for showing animated tooltips with custom icons and messages
 * 
 * Usage:
 * AnimatedTooltip.show(element, message, options)
 * 
 * @author Admin Panel
 * @version 1.0.0
 */

class AnimatedTooltip {
    /**
     * Default configuration options
     */
    static defaultOptions = {
        icon: 'fas fa-check',
        iconClass: 'check-icon me-1',
        placement: 'top',
        duration: 2000,
        customClass: 'custom-tooltip',
        backgroundColor: '#ffffff',
        textColor: '#28a745',
        borderColor: '#28a745'
    };

    /**
     * Show an animated tooltip on the specified element
     * 
     * @param {HTMLElement} element - The target element to show tooltip on
     * @param {string} message - The message to display in tooltip
     * @param {Object} options - Custom options to override defaults
     */
    static show(element, message, options = {}) {
        // Validate inputs
        if (!element || !message) {
            console.warn('AnimatedTooltip: Element and message are required');
            return null;
        }

        // Merge options with defaults
        const config = { ...this.defaultOptions, ...options };

        // Create tooltip content with icon
        const tooltipContent = `<i class="${config.icon} ${config.iconClass}"></i>${message}`;

        try {
            // Dispose any existing tooltip on this element
            const existingTooltip = bootstrap.Tooltip.getInstance(element);
            if (existingTooltip) {
                existingTooltip.dispose();
            }

            // Initialize Bootstrap tooltip with custom configuration
            const tooltip = new bootstrap.Tooltip(element, {
                title: tooltipContent,
                html: true,
                placement: config.placement,
                customClass: config.customClass,
                trigger: 'manual'
            });

            // Show the tooltip
            tooltip.show();

            // Hide and dispose tooltip after specified duration
            setTimeout(() => {
                try {
                    if (tooltip && typeof tooltip.hide === 'function') {
                        tooltip.hide();
                        setTimeout(() => {
                            if (tooltip && typeof tooltip.dispose === 'function') {
                                tooltip.dispose();
                            }
                        }, 200);
                    }
                } catch (error) {
                    console.warn('AnimatedTooltip: Error disposing tooltip', error);
                }
            }, config.duration);

            return tooltip;
        } catch (error) {
            console.error('AnimatedTooltip: Error creating tooltip', error);
            return null;
        }
    }

    /**
     * Show a success tooltip (green check icon)
     * 
     * @param {HTMLElement} element - The target element
     * @param {string} message - Success message
     * @param {Object} options - Additional options
     */
    static showSuccess(element, message, options = {}) {
        const successOptions = {
            icon: 'fas fa-circle-check',
            iconClass: 'check-icon me-1',
            textColor: '#28a745',
            borderColor: '#28a745',
            ...options
        };

        return this.show(element, message, successOptions);
    }

    /**
     * Show an error tooltip (red X icon)
     * 
     * @param {HTMLElement} element - The target element
     * @param {string} message - Error message
     * @param {Object} options - Additional options
     */
    static showError(element, message, options = {}) {
        const errorOptions = {
            icon: 'fas fa-times',
            iconClass: 'error-icon me-1',
            textColor: '#dc3545',
            borderColor: '#dc3545',
            ...options
        };

        return this.show(element, message, errorOptions);
    }

    /**
     * Show a warning tooltip (yellow warning icon)
     * 
     * @param {HTMLElement} element - The target element
     * @param {string} message - Warning message
     * @param {Object} options - Additional options
     */
    static showWarning(element, message, options = {}) {
        const warningOptions = {
            icon: 'fas fa-exclamation-triangle',
            iconClass: 'warning-icon me-1',
            textColor: '#ffc107',
            borderColor: '#ffc107',
            ...options
        };

        return this.show(element, message, warningOptions);
    }

    /**
     * Show an info tooltip (blue info icon)
     * 
     * @param {HTMLElement} element - The target element
     * @param {string} message - Info message
     * @param {Object} options - Additional options
     */
    static showInfo(element, message, options = {}) {
        const infoOptions = {
            icon: 'fas fa-info-circle',
            iconClass: 'info-icon me-1',
            textColor: '#17a2b8',
            borderColor: '#17a2b8',
            ...options
        };

        return this.show(element, message, infoOptions);
    }

    /**
     * Initialize the tooltip styles if not already present
     * This method should be called once when the component is first used
     */
    static initializeStyles() {
        // Check if styles are already added
        if (document.getElementById('animated-tooltip-styles')) {
            return;
        }

        const styleElement = document.createElement('style');
        styleElement.id = 'animated-tooltip-styles';
        styleElement.innerHTML = `
            .custom-tooltip .tooltip-inner {
                background-color: #ffffff !important;
                color: #28a745 !important;
                border: 1px solid #28a745 !important;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
                font-weight: 500 !important;
                padding: 5px 8px !important;
                border-radius: 6px !important;
                font-size: 10px !important;
            }

            .custom-tooltip .tooltip-arrow::before {
                border-top-color: #28a745 !important;
            }

            .check-icon {
                animation: checkAnimation 0.6s ease-in-out;
            }

            .error-icon {
                animation: errorAnimation 0.6s ease-in-out;
            }

            .warning-icon {
                animation: warningAnimation 0.6s ease-in-out;
            }

            .info-icon {
                animation: infoAnimation 0.6s ease-in-out;
            }

            @keyframes checkAnimation {
                0% {
                    transform: scale(0);
                    opacity: 0;
                }
                
                30% {
                    transform: scale(1.3);
                    opacity: 0.7;
                }
                
                60% {
                    transform: scale(0.9);
                    opacity: 0.9;
                }
                
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }

            @keyframes errorAnimation {
                0% {
                    transform: scale(0) rotate(-90deg);
                    opacity: 0;
                }
                
                50% {
                    transform: scale(1.2) rotate(0deg);
                    opacity: 0.8;
                }
                
                100% {
                    transform: scale(1) rotate(0deg);
                    opacity: 1;
                }
            }

            @keyframes warningAnimation {
                0% {
                    transform: scale(0);
                    opacity: 0;
                }
                
                25% {
                    transform: scale(1.1);
                    opacity: 0.6;
                }
                
                50% {
                    transform: scale(0.95);
                    opacity: 0.8;
                }
                
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }

            @keyframes infoAnimation {
                0% {
                    transform: scale(0);
                    opacity: 0;
                }
                
                40% {
                    transform: scale(1.15);
                    opacity: 0.7;
                }
                
                70% {
                    transform: scale(0.95);
                    opacity: 0.9;
                }
                
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        `;

        document.head.appendChild(styleElement);
    }
}

// Auto-initialize styles when the script loads
document.addEventListener('DOMContentLoaded', function () {
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined') {
        console.error('AnimatedTooltip: Bootstrap is required but not found');
        return;
    }

    AnimatedTooltip.initializeStyles();
});

// Make it globally available
window.AnimatedTooltip = AnimatedTooltip;