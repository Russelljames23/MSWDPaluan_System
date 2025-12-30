// /MSWDPALUAN_SYSTEM-MAIN/js/staff_theme.js
// STAFF-SPECIFIC THEME FUNCTIONS - Won't interfere with admin side

const StaffTheme = {
    // Initialize staff theme
    init: function() {
        const savedTheme = localStorage.getItem('staff_theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        let theme = 'light';
        if (savedTheme) {
            theme = savedTheme;
        } else if (systemPrefersDark) {
            theme = 'dark';
        }
        
        this.set(theme);
        
        // Only update nav UI if on a page with theme toggle
        if (typeof this.updateNavUI === 'function') {
            this.updateNavUI();
        }
        
        return theme;
    },
    
    // Set staff theme
    set: function(theme) {
        const root = document.documentElement;
        const wasDark = root.classList.contains('dark');
        const isDark = theme === 'dark';
        
        // Only make changes if needed
        if (isDark && !wasDark) {
            root.classList.add('dark');
            localStorage.setItem('staff_theme', 'dark');
        } else if (!isDark && wasDark) {
            root.classList.remove('dark');
            localStorage.setItem('staff_theme', 'light');
        }
        
        // Update nav UI if available
        if (typeof this.updateNavUI === 'function') {
            this.updateNavUI();
        }
        
        // Dispatch custom event for staff components only
        window.dispatchEvent(new CustomEvent('staffThemeChanged', {
            detail: { theme: theme }
        }));
    },
    
    // Toggle staff theme
    toggle: function() {
        const currentIsDark = document.documentElement.classList.contains('dark');
        const newTheme = currentIsDark ? 'light' : 'dark';
        this.set(newTheme);
    },
    
    // Update navigation UI (only for pages with theme toggle)
    updateNavUI: function() {
        const navThemeLightIcon = document.getElementById('nav-theme-light-icon');
        const navThemeDarkIcon = document.getElementById('nav-theme-dark-icon');
        const navThemeText = document.getElementById('nav-theme-text');
        
        // Only proceed if elements exist
        if (!navThemeLightIcon && !navThemeDarkIcon && !navThemeText) {
            return;
        }
        
        const isDark = document.documentElement.classList.contains('dark');
        
        if (navThemeLightIcon) {
            navThemeLightIcon.classList.toggle('hidden', !isDark);
        }
        if (navThemeDarkIcon) {
            navThemeDarkIcon.classList.toggle('hidden', isDark);
        }
        if (navThemeText) {
            navThemeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
    },
    
    // Handle system theme changes
    handleSystemThemeChange: function(e) {
        // Only update if staff hasn't explicitly set a preference
        if (!localStorage.getItem('staff_theme')) {
            this.set(e.matches ? 'dark' : 'light');
        }
    }
};

// Initialize staff theme immediately to prevent flash
StaffTheme.init();

// Set up system theme listener
const systemThemeMedia = window.matchMedia('(prefers-color-scheme: dark)');
systemThemeMedia.addEventListener('change', function(e) {
    StaffTheme.handleSystemThemeChange(e);
});

// Set up storage listener for staff theme changes
window.addEventListener('storage', function(e) {
    if (e.key === 'staff_theme') {
        const theme = e.newValue;
        const currentIsDark = document.documentElement.classList.contains('dark');
        const newIsDark = theme === 'dark';
        
        if ((newIsDark && !currentIsDark) || (!newIsDark && currentIsDark)) {
            StaffTheme.set(theme);
        }
    }
});

// Make StaffTheme globally available for staff pages
window.StaffTheme = StaffTheme;

// Set up theme toggle event listener when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const navThemeToggle = document.getElementById('nav-theme-toggle');
    
    if (navThemeToggle) {
        navThemeToggle.addEventListener('click', function() {
            StaffTheme.toggle();
        });
    }
    
    // Listen for staff theme changed events
    window.addEventListener('staffThemeChanged', function() {
        if (typeof StaffTheme.updateNavUI === 'function') {
            StaffTheme.updateNavUI();
        }
    });
});