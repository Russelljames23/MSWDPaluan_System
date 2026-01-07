// logs-manager.js
// Combined Logs Management System
const LogManager = {
    config: {
        currentFilter: 'all',
        currentSearch: '',
        currentLogType: 'both',
        currentDateRange: 'all',
        currentSort: {
            field: 'timestamp',
            direction: 'DESC'
        },
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        totalPages: 0,
        apiUrl: '/MSWDPALUAN_SYSTEM-MAIN/MSWDPALUAN_SYSTEM-MAIN/php/settings/combined_logs_backend.php'
    },

    init() {
        this.bindEvents();
        this.loadLogs();
        // this.startAutoRefresh(60000); // Uncomment for auto-refresh
    },

    bindEvents() {
        // Search with debounce
        const searchInput = document.getElementById('searchLogs');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.config.currentSearch = searchInput.value;
                    this.config.currentPage = 1;
                    this.loadLogs();
                }, 500);
            });
        }

        // Filter changes
        const logTypeFilter = document.getElementById('logTypeFilter');
        const dateRangeFilter = document.getElementById('dateRangeFilter');
        const userTypeFilter = document.getElementById('userTypeFilter');

        if (logTypeFilter) {
            logTypeFilter.addEventListener('change', (e) => {
                this.config.currentLogType = e.target.value;
                this.config.currentPage = 1;
                this.loadLogs();
            });
        }

        if (dateRangeFilter) {
            dateRangeFilter.addEventListener('change', (e) => {
                this.config.currentDateRange = e.target.value;
                this.config.currentPage = 1;
                this.loadLogs();
            });
        }

        if (userTypeFilter) {
            userTypeFilter.addEventListener('change', (e) => {
                this.config.currentFilter = e.target.value;
                this.config.currentPage = 1;
                this.loadLogs();
            });
        }

        // Action buttons
        const refreshBtn = document.getElementById('refreshLogs');
        const clearBtn = document.getElementById('clearFilters');
        const exportBtn = document.getElementById('exportLogs');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshLogs());
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearFilters());
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportLogs());
        }

        // Overlay
        const closeOverlayBtn = document.getElementById('closeOverlay');
        const overlay = document.getElementById('logOverlay');

        if (closeOverlayBtn) {
            closeOverlayBtn.addEventListener('click', () => this.hideOverlay());
        }

        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target.id === 'logOverlay') this.hideOverlay();
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideOverlay();
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                this.refreshLogs();
            }
        });
    },

    async loadLogs() {
        this.showLoading();

        const params = new URLSearchParams({
            search: this.config.currentSearch,
            filter: this.config.currentFilter,
            log_type: this.config.currentLogType,
            date_range: this.config.currentDateRange,
            sort: this.config.currentSort.field,
            order: this.config.currentSort.direction,
            page: this.config.currentPage,
            limit: this.config.itemsPerPage,
            _t: Date.now() // Cache busting
        });

        try {
            console.log('Fetching logs from:', `${this.config.apiUrl}?${params.toString().substring(0, 100)}...`);

            const response = await fetch(`${this.config.apiUrl}?${params}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP Error:', response.status, errorText);

                // Try to parse as JSON for detailed error
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error(`HTTP ${response.status}: ${errorData.error || errorData.debug || 'Server error'}`);
                } catch (e) {
                    throw new Error(`HTTP ${response.status}: Failed to load logs`);
                }
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || data.debug || 'Failed to load logs');
            }

            this.updateStats(data.data || []);
            this.renderTable(data.data || []);
            this.updatePagination(data);
            this.updateFilterIndicators(data.filters || {});

        } catch (error) {
            console.error('Error loading logs:', error);
            this.showError(error.message);
        } finally {
            this.hideLoading();
        }
    },

    updateStats(logs) {
        const sessions = logs.filter(log => log.log_type === 'session');
        const activities = logs.filter(log => log.log_type === 'activity');
        const activeSessions = sessions.filter(s => s.status === 'Active');

        // Update stats cards
        const totalSessionsEl = document.getElementById('totalSessions');
        const activeSessionsEl = document.getElementById('activeSessions');
        const totalActivitiesEl = document.getElementById('totalActivities');
        const avgDurationEl = document.getElementById('avgDuration');

        if (totalSessionsEl) totalSessionsEl.textContent = sessions.length;
        if (activeSessionsEl) activeSessionsEl.textContent = activeSessions.length;
        if (totalActivitiesEl) totalActivitiesEl.textContent = activities.length;

        // Calculate average duration
        const durations = sessions
            .map(s => s.duration)
            .filter(d => d !== 'N/A' && d !== 'Ongoing')
            .map(d => this.parseDuration(d));

        const avgDuration = durations.length > 0
            ? this.formatDuration(Math.round(durations.reduce((a, b) => a + b) / durations.length))
            : '0m';

        if (avgDurationEl) avgDurationEl.textContent = avgDuration;
    },

    parseDuration(duration) {
        // Convert "2h 30m" to minutes
        let minutes = 0;
        if (duration.includes('h')) {
            const hours = parseInt(duration) || 0;
            minutes += hours * 60;
        }
        if (duration.includes('m')) {
            const match = duration.match(/(\d+)m/);
            if (match) minutes += parseInt(match[1]) || 0;
        }
        return minutes;
    },

    formatDuration(minutes) {
        if (minutes < 60) return `${minutes}m`;
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    },

    renderTable(logs) {
        const tableBody = document.getElementById('logsTableBody');
        const table = document.getElementById('logsTable');
        const emptyState = document.getElementById('logsEmpty');

        if (!tableBody || !table || !emptyState) return;

        if (logs.length === 0) {
            table.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        table.classList.remove('hidden');
        emptyState.classList.add('hidden');

        tableBody.innerHTML = logs.map(log => this.createTableRow(log)).join('');

        // Add click handlers to view buttons
        tableBody.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const logId = e.target.dataset.logId;
                const logType = e.target.dataset.logType;
                this.showLogDetails(logId, logType);
            });
        });

        // Add row click handlers
        tableBody.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', (e) => {
                const logId = row.querySelector('.view-details')?.dataset?.logId;
                const logType = row.querySelector('.view-details')?.dataset?.logType;
                if (logId && logType) {
                    this.showLogDetails(logId, logType);
                }
            });
        });
    },

    createTableRow(log) {
        const isMobile = window.innerWidth < 768;
        const isTablet = window.innerWidth < 1024;

        // Status badge classes
        const statusColors = {
            'Active': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'Completed': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'N/A': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
        };

        const statusClass = statusColors[log.status] || statusColors['N/A'];

        // Format IP address
        const ipAddress = log.ip_address === '::1' ? 'Localhost' : (log.ip_address || 'N/A');

        return `
            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">${log.icon}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            ${log.log_type === 'session'
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'}">
                            ${log.log_type === 'session' ? 'Session' : 'Activity'}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900 dark:text-white">${log.user_name}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${log.user_type}</div>
                </td>
                ${!isMobile ? `
                <td class="px-4 py-3">
                    <div class="font-medium">${log.activity_type}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                        ${log.description}
                    </div>
                </td>
                ` : ''}
                <td class="px-4 py-3">
                    <div class="font-medium">${log.timestamp}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${log.time_ago}</div>
                </td>
                ${!isTablet ? `
                <td class="px-4 py-3">
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded font-mono">
                        ${ipAddress}
                    </code>
                </td>
                <td class="px-4 py-3">${log.duration}</td>
                ` : ''}
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                        ${log.status}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <button class="view-details px-3 py-1 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                            data-log-id="${log.log_id}" data-log-type="${log.log_type}">
                        View
                    </button>
                </td>
            </tr>
        `;
    },

    async showLogDetails(logId, logType) {
        try {
            // Fetch detailed log information
            const response = await fetch(`${this.config.apiUrl}?action=detail&id=${logId}&type=${logType}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to load details');
            }

            const log = data.data;
            const overlay = document.getElementById('logOverlay');
            const content = document.getElementById('overlayContent');
            const title = document.getElementById('overlayTitle');

            if (!overlay || !content || !title) return;

            title.textContent = `${logType === 'session' ? 'Session' : 'Activity'} Details`;

            // Format details content
            content.innerHTML = this.formatLogDetails(log, logType);

            // Show overlay
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

        } catch (error) {
            console.error('Error loading log details:', error);
            alert('Failed to load log details: ' + error.message);
        }
    },

    formatLogDetails(log, logType) {
        const formatField = (label, value, isCode = false) => `
            <div class="mb-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">${label}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white ${isCode ? 'font-mono bg-gray-50 dark:bg-gray-700 p-2 rounded' : ''}">
                    ${value || 'N/A'}
                </dd>
            </div>
        `;

        // Format login/logout times
        const loginTime = log.login_time ? new Date(log.login_time).toLocaleString() : 'N/A';
        const logoutTime = log.logout_time && log.logout_time !== '0000-00-00 00:00:00'
            ? new Date(log.logout_time).toLocaleString()
            : 'Still active';

        // Calculate duration
        const duration = logType === 'session' && log.login_time && log.logout_time && log.logout_time !== '0000-00-00 00:00:00'
            ? this.calculateDurationFromTimes(log.login_time, log.logout_time)
            : 'N/A';

        // User name
        const userName = log.firstname && log.lastname
            ? `${log.lastname}, ${log.firstname} (${log.user_type || 'N/A'})`
            : 'Unknown';

        return `
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Log ID', log.id || log.log_id)}
                    ${formatField('User', userName)}
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Type', logType === 'session' ? 'User Session' : 'System Activity')}
                    ${formatField('Status', logType === 'session' ? (log.logout_time && log.logout_time !== '0000-00-00 00:00:00' ? 'Completed' : 'Active') : 'Completed')}
                </div>
                
                ${logType === 'session' ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Login Time', loginTime)}
                    ${formatField('Logout Time', logoutTime)}
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('Duration', duration)}
                    ${formatField('Login Type', log.login_type || 'N/A')}
                </div>
                ` : `
                <div class="grid grid-cols-1 gap-4">
                    ${formatField('Activity Type', log.activity_type || 'N/A')}
                    ${formatField('Description', log.description || 'N/A')}
                </div>
                `}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${formatField('IP Address', log.ip_address || 'N/A', true)}
                    ${formatField('Timestamp', log.login_time || log.created_at ? new Date(log.login_time || log.created_at).toLocaleString() : 'N/A')}
                </div>
                
                ${log.user_agent ? `
                <div class="border-t dark:border-gray-700 pt-4">
                    ${formatField('User Agent', log.user_agent, true)}
                </div>
                ` : ''}
            </div>
        `;
    },

    calculateDurationFromTimes(startTime, endTime) {
        try {
            const start = new Date(startTime);
            const end = new Date(endTime);
            const diffMs = end - start;

            const hours = Math.floor(diffMs / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);

            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds}s`;
            } else {
                return `${seconds}s`;
            }
        } catch (e) {
            return 'N/A';
        }
    },

    hideOverlay() {
        const overlay = document.getElementById('logOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    },

    updatePagination(data) {
        this.config.totalItems = data.total;
        this.config.totalPages = data.total_pages;

        // Update pagination info
        const pageStart = document.getElementById('pageStart');
        const pageEnd = document.getElementById('pageEnd');
        const totalItems = document.getElementById('totalItems');
        const currentPage = document.getElementById('currentPage');
        const totalPages = document.getElementById('totalPages');

        if (pageStart) {
            pageStart.textContent = Math.min((this.config.currentPage - 1) * this.config.itemsPerPage + 1, data.total);
        }
        if (pageEnd) {
            pageEnd.textContent = Math.min(this.config.currentPage * this.config.itemsPerPage, data.total);
        }
        if (totalItems) {
            totalItems.textContent = data.total;
        }
        if (currentPage) {
            currentPage.textContent = this.config.currentPage;
        }
        if (totalPages) {
            totalPages.textContent = data.total_pages;
        }

        // Enable/disable pagination buttons
        const pagination = document.getElementById('logsPagination');
        if (!pagination) return;

        if (data.total_pages > 1) {
            pagination.classList.remove('hidden');

            const buttons = ['firstPage', 'prevPage', 'nextPage', 'lastPage'];
            buttons.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });

            const firstPage = document.getElementById('firstPage');
            const prevPage = document.getElementById('prevPage');
            const nextPage = document.getElementById('nextPage');
            const lastPage = document.getElementById('lastPage');

            if (firstPage) firstPage.disabled = this.config.currentPage === 1;
            if (prevPage) prevPage.disabled = this.config.currentPage === 1;
            if (nextPage) nextPage.disabled = this.config.currentPage === data.total_pages;
            if (lastPage) lastPage.disabled = this.config.currentPage === data.total_pages;

        } else {
            pagination.classList.add('hidden');
        }

        // Bind pagination events
        this.bindPaginationEvents();
    },

    bindPaginationEvents() {
        const firstPage = document.getElementById('firstPage');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');
        const lastPage = document.getElementById('lastPage');

        if (firstPage) {
            firstPage.onclick = () => {
                this.config.currentPage = 1;
                this.loadLogs();
            };
        }

        if (prevPage) {
            prevPage.onclick = () => {
                if (this.config.currentPage > 1) {
                    this.config.currentPage--;
                    this.loadLogs();
                }
            };
        }

        if (nextPage) {
            nextPage.onclick = () => {
                if (this.config.currentPage < this.config.totalPages) {
                    this.config.currentPage++;
                    this.loadLogs();
                }
            };
        }

        if (lastPage) {
            lastPage.onclick = () => {
                this.config.currentPage = this.config.totalPages;
                this.loadLogs();
            };
        }
    },

    updateFilterIndicators(filters) {
        // Update filter dropdowns to reflect current state
        const logTypeFilter = document.getElementById('logTypeFilter');
        const dateRangeFilter = document.getElementById('dateRangeFilter');
        const userTypeFilter = document.getElementById('userTypeFilter');

        if (logTypeFilter && filters.log_type) logTypeFilter.value = filters.log_type;
        if (dateRangeFilter && filters.date_range) dateRangeFilter.value = filters.date_range;
        if (userTypeFilter && filters.filter) userTypeFilter.value = filters.filter;
    },

    refreshLogs() {
        const btn = document.getElementById('refreshLogs');
        if (!btn) return;

        const originalText = btn.innerHTML;

        btn.innerHTML = `
            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Refreshing...
        `;

        this.config.currentPage = 1;
        this.loadLogs();

        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 1000);
    },

    clearFilters() {
        this.config.currentSearch = '';
        this.config.currentFilter = 'all';
        this.config.currentLogType = 'both';
        this.config.currentDateRange = 'all';
        this.config.currentPage = 1;

        const searchInput = document.getElementById('searchLogs');
        const logTypeFilter = document.getElementById('logTypeFilter');
        const dateRangeFilter = document.getElementById('dateRangeFilter');
        const userTypeFilter = document.getElementById('userTypeFilter');

        if (searchInput) searchInput.value = '';
        if (logTypeFilter) logTypeFilter.value = 'both';
        if (dateRangeFilter) dateRangeFilter.value = 'all';
        if (userTypeFilter) userTypeFilter.value = 'all';

        this.loadLogs();
    },

    exportLogs() {
        const params = new URLSearchParams({
            search: this.config.currentSearch,
            filter: this.config.currentFilter,
            log_type: this.config.currentLogType,
            date_range: this.config.currentDateRange,
            export: 'csv'
        });

        window.open(`${this.config.apiUrl}?${params}`, '_blank');
    },

    showLoading() {
        const loadingEl = document.getElementById('logsLoading');
        const tableEl = document.getElementById('logsTable');
        const paginationEl = document.getElementById('logsPagination');
        const emptyEl = document.getElementById('logsEmpty');

        if (loadingEl) loadingEl.classList.remove('hidden');
        if (tableEl) tableEl.classList.add('hidden');
        if (paginationEl) paginationEl.classList.add('hidden');
        if (emptyEl) emptyEl.classList.add('hidden');
    },

    hideLoading() {
        const loadingEl = document.getElementById('logsLoading');
        if (loadingEl) loadingEl.classList.add('hidden');
    },

    showError(message) {
        const tableBody = document.getElementById('logsTableBody');
        const table = document.getElementById('logsTable');
        const pagination = document.getElementById('logsPagination');

        if (!tableBody || !table) return;

        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-red-600 dark:text-red-400">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium">Error loading logs</h3>
                    <p class="mt-1 text-sm">${message}</p>
                    <button onclick="LogManager.loadLogs()" 
                            class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Try Again
                    </button>
                </td>
            </tr>
        `;

        table.classList.remove('hidden');
        if (pagination) pagination.classList.add('hidden');
    },

    startAutoRefresh(interval) {
        setInterval(() => {
            if (!document.hidden && document.visibilityState === 'visible') {
                this.loadLogs();
            }
        }, interval);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => LogManager.init());

// Handle window resize for responsive table
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        if (LogManager.config.currentSearch || LogManager.config.currentFilter !== 'all') {
            LogManager.loadLogs();
        }
    }, 250);
});