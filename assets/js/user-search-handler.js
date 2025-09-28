/**
 * User Search Handler
 * Manages user search functionality and navigation
 */

const userSearchHandler = (function() {
    let searchTimeout = null;

    // Initialize the user search handler
    function init() {
        initializeEventListeners();
    }

    // Set up event listeners
    function initializeEventListeners() {
        // Modal open/close
        const searchModal = document.getElementById('user-search-modal');
        const closeBtn = document.getElementById('user-search-modal-close');

        if (closeBtn) {
            closeBtn.addEventListener('click', hideModal);
        }

        // Close modal on background click
        if (searchModal) {
            searchModal.addEventListener('click', (e) => {
                if (e.target === searchModal) {
                    hideModal();
                }
            });
        }

        // Search input with debounce
        const searchInput = document.getElementById('user-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', handleSearchInput);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }

        // Add click handler for user search trigger (if exists in nav)
        const searchTrigger = document.querySelector('[data-action="search-users"]');
        if (searchTrigger) {
            searchTrigger.addEventListener('click', showModal);
        }
    }

    // Handle search input with debounce
    function handleSearchInput(e) {
        const query = e.target.value.trim();

        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Debounce search - wait 300ms after user stops typing
        searchTimeout = setTimeout(() => {
            if (query.length >= 2) {
                performSearch(query);
            } else if (query.length === 0) {
                clearSearchResults();
            }
        }, 300);
    }

    // Perform user search
    async function performSearch(query = null) {
        const searchInput = document.getElementById('user-search-input');
        const searchQuery = query || (searchInput ? searchInput.value.trim() : '');

        if (searchQuery.length < 2) {
            showSearchMessage('En az 2 karakter girin');
            return;
        }

        try {
            showSearchLoading();

            const response = await api.call('search_users', {
                query: searchQuery,
                limit: 20
            }, 'POST', false);

            if (response.success) {
                displaySearchResults(response.data.users || []);
            } else {
                showSearchMessage(response.message || 'Arama yapılırken bir hata oluştu');
            }
        } catch (error) {
            console.error('User search error:', error);
            showSearchMessage('Bağlantı hatası. Lütfen tekrar deneyin.');
        }
    }

    // Display search results
    function displaySearchResults(users) {
        const resultsContainer = document.getElementById('user-search-results');
        if (!resultsContainer) return;

        if (users.length === 0) {
            showSearchMessage('Kullanıcı bulunamadı');
            return;
        }

        resultsContainer.innerHTML = users.map(user => `
            <div class="user-search-result flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors"
                 data-username="${user.username}"
                 onclick="userSearchHandler.navigateToProfile('${user.username}')">
                <div class="flex-shrink-0 mr-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                        ${user.username.substring(0, 2).toUpperCase()}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-gray-800 dark:text-white">${user.username}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        ${user.total_score ? formatNumber(user.total_score) + ' puan' : 'Henüz puan yok'}
                        ${user.global_rank ? ' • #' + user.global_rank : ''}
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        `).join('');
    }

    // Show search loading state
    function showSearchLoading() {
        const resultsContainer = document.getElementById('user-search-results');
        if (!resultsContainer) return;

        resultsContainer.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Aranıyor...</span>
            </div>
        `;
    }

    // Show search message
    function showSearchMessage(message) {
        const resultsContainer = document.getElementById('user-search-results');
        if (!resultsContainer) return;

        resultsContainer.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                <p class="text-gray-600 dark:text-gray-400">${message}</p>
            </div>
        `;
    }

    // Clear search results
    function clearSearchResults() {
        const resultsContainer = document.getElementById('user-search-results');
        if (!resultsContainer) return;

        resultsContainer.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-users text-gray-400 text-2xl mb-2"></i>
                <p class="text-gray-600 dark:text-gray-400">Kullanıcı aramak için yazmaya başlayın</p>
            </div>
        `;
    }

    // Navigate to user profile
    function navigateToProfile(username) {
        if (!username) return;

        // Hide modal first
        hideModal();

        // Navigate to profile URL
        const profileUrl = `/profile/${username}`;
        window.location.href = profileUrl;
    }

    // Show search modal
    function showModal() {
        const modal = document.getElementById('user-search-modal');
        const modalContent = document.getElementById('user-search-modal-content');

        if (!modal || !modalContent) return;

        // Clear previous search
        const searchInput = document.getElementById('user-search-input');
        if (searchInput) {
            searchInput.value = '';
        }
        clearSearchResults();

        // Show modal with animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);

        // Focus search input
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }
    }

    // Hide search modal
    function hideModal() {
        const modal = document.getElementById('user-search-modal');
        const modalContent = document.getElementById('user-search-modal-content');

        if (!modal || !modalContent) return;

        // Animate out
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');

        // Hide after animation
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Add keyboard navigation
    function handleKeyboardNavigation(e) {
        const results = document.querySelectorAll('.user-search-result');
        const activeResult = document.querySelector('.user-search-result.active');

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();

            if (results.length === 0) return;

            let nextIndex = 0;

            if (activeResult) {
                const currentIndex = Array.from(results).indexOf(activeResult);
                if (e.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % results.length;
                } else {
                    nextIndex = currentIndex === 0 ? results.length - 1 : currentIndex - 1;
                }
                activeResult.classList.remove('active', 'bg-blue-50', 'dark:bg-blue-900/20');
            }

            results[nextIndex].classList.add('active', 'bg-blue-50', 'dark:bg-blue-900/20');
        } else if (e.key === 'Enter' && activeResult) {
            e.preventDefault();
            const username = activeResult.dataset.username;
            if (username) {
                navigateToProfile(username);
            }
        }
    }

    // Enhanced search input listener for keyboard navigation
    function enhanceSearchInput() {
        const searchInput = document.getElementById('user-search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', handleKeyboardNavigation);
        }
    }

    // Utility function for number formatting
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    // Public API
    return {
        init,
        showModal,
        hideModal,
        navigateToProfile,
        performSearch
    };
})();

// Make it globally available
window.userSearchHandler = userSearchHandler;