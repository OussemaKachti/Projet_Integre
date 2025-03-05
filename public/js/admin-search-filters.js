// Enhanced AJAX Search and Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const searchForm = document.getElementById('advanced-search-form');
    const globalSearch = document.getElementById('global-search');
    const searchType = document.getElementById('search-type');
    const searchField = document.getElementById('search-field');
    const dateRange = document.getElementById('date-range');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const verificationFilter = document.getElementById('verification-filter');
    const activityFilter = document.getElementById('activity-filter');
    const tableBody = document.getElementById('user-table-body');
    const loadingIndicator = document.getElementById('loading-indicator');
    const clearSearch = document.getElementById('clear-search');
    const clearFilters = document.getElementById('clear-filters');
    const applyFilters = document.getElementById('apply-filters');
    const resultsCount = document.getElementById('results-count');
    const filterCount = document.getElementById('filter-count');
    const saveFilterPreset = document.getElementById('save-filter-preset');
    const filterPresetName = document.getElementById('filter-preset-name');
    const filterPresetsList = document.getElementById('filter-presets-list');
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Initialize variables
    let debounceTimeout;
    let activeFilters = 0;
    let savedFilters = JSON.parse(localStorage.getItem('adminFilterPresets') || '{}');
    
    // Initialize UI
    initializeUI();
    
    // Event listeners
    if (searchForm) {
      searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchResults();
      });
    }
    
    if (globalSearch) {
      globalSearch.addEventListener('input', debounceSearch);
    }
    
    if (dateRange) {
      dateRange.addEventListener('change', function() {
        const customDateFields = document.querySelectorAll('.date-range-custom');
        if (this.value === 'custom') {
          customDateFields.forEach(field => field.style.display = 'block');
        } else {
          customDateFields.forEach(field => field.style.display = 'none');
        }
      });
    }
    
    if (clearSearch) {
      clearSearch.addEventListener('click', clearSearchFields);
    }
    
    if (clearFilters) {
      clearFilters.addEventListener('click', clearFilterFields);
    }
    
    if (applyFilters) {
      applyFilters.addEventListener('click', fetchResults);
    }
    
    if (saveFilterPreset) {
      saveFilterPreset.addEventListener('click', saveCurrentFilters);
    }
    
    // Functions
    function initializeUI() {
      // Set initial active filter count
      countActiveFilters();
      
      // Load saved filter presets
      updateFilterPresetsList();
      
      // Initialize date range display
      if (dateRange && dateRange.value === 'custom') {
        document.querySelectorAll('.date-range-custom').forEach(field => {
          field.style.display = 'block';
        });
      }
      
      // Enable Bootstrap tooltips
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
    
    function debounceSearch() {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        if (globalSearch.value.length >= 2 || globalSearch.value.length === 0) {
          fetchResults();
        }
      }, 400);
    }
    
    function countActiveFilters() {
      activeFilters = 0;
      
      // Check search
      if (globalSearch && globalSearch.value) activeFilters++;
      
      // Check filters
      if (roleFilter && roleFilter.value) activeFilters++;
      if (statusFilter && statusFilter.value) activeFilters++;
      if (verificationFilter && verificationFilter.value) activeFilters++;
      if (activityFilter && activityFilter.value) activeFilters++;
      if (dateRange && dateRange.value) activeFilters++;
      
      // Update UI
      if (filterCount) {
        if (activeFilters > 0) {
          filterCount.textContent = activeFilters;
          filterCount.style.display = 'inline-block';
        } else {
          filterCount.style.display = 'none';
        }
      }
    }
    
    function clearSearchFields() {
      if (globalSearch) globalSearch.value = '';
      if (searchType) searchType.value = 'contains';
      if (searchField) searchField.value = 'all';
      if (dateRange) dateRange.value = '';
      if (dateFrom) dateFrom.value = '';
      if (dateTo) dateTo.value = '';
      
      // Hide custom date fields
      document.querySelectorAll('.date-range-custom').forEach(field => {
        field.style.display = 'none';
      });
      
      // Update count and fetch results
      countActiveFilters();
      fetchResults();
    }
    
    function clearFilterFields() {
      if (roleFilter) {
        Array.from(roleFilter.options).forEach(option => {
          option.selected = false;
        });
      }
      if (statusFilter) statusFilter.value = '';
      if (verificationFilter) verificationFilter.value = '';
      if (activityFilter) activityFilter.value = '';
      
      // Update count and fetch results
      countActiveFilters();
      fetchResults();
    }
    
    function saveCurrentFilters() {
      const name = filterPresetName.value.trim();
      if (!name) {
        // Show error message
        alert('Please enter a name for this filter preset');
        return;
      }
      
      // Collect current filter values
      const filterData = {
        search: globalSearch ? globalSearch.value : '',
        searchType: searchType ? searchType.value : 'contains',
        searchField: searchField ? searchField.value : 'all',
        dateRange: dateRange ? dateRange.value : '',
        dateFrom: dateFrom ? dateFrom.value : '',
        dateTo: dateTo ? dateTo.value : '',
        role: roleFilter ? Array.from(roleFilter.selectedOptions).map(o => o.value) : [],
        status: statusFilter ? statusFilter.value : '',
        verification: verificationFilter ? verificationFilter.value : '',
        activity: activityFilter ? activityFilter.value : ''
      };
      
      // Save to localStorage
      savedFilters[name] = filterData;
      localStorage.setItem('adminFilterPresets', JSON.stringify(savedFilters));
      
      // Update UI
      filterPresetName.value = '';
      updateFilterPresetsList();
    }
    
    function updateFilterPresetsList() {
      if (!filterPresetsList) return;
      
      filterPresetsList.innerHTML = '';
      
      const filterNames = Object.keys(savedFilters);
      if (filterNames.length === 0) {
        filterPresetsList.innerHTML = '<li><span class="dropdown-item-text">No saved filters</span></li>';
        return;
      }
      
      filterNames.forEach(name => {
        const li = document.createElement('li');
        const loadLink = document.createElement('a');
        loadLink.className = 'dropdown-item d-flex justify-content-between align-items-center';
        loadLink.href = '#';
        loadLink.textContent = name;
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-link text-danger p-0 ms-2';
        deleteBtn.innerHTML = '<i class="mdi mdi-delete"></i>';
        deleteBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          e.preventDefault();
          deleteFilterPreset(name);
        });
        
        loadLink.appendChild(deleteBtn);
        loadLink.addEventListener('click', (e) => {
          e.preventDefault();
          loadFilterPreset(name);
        });
        
        li.appendChild(loadLink);
        filterPresetsList.appendChild(li);
      });
    }
    
    function loadFilterPreset(name) {
      const preset = savedFilters[name];
      if (!preset) return;
      
      // Apply values to form elements
      if (globalSearch) globalSearch.value = preset.search || '';
      if (searchType) searchType.value = preset.searchType || 'contains';
      if (searchField) searchField.value = preset.searchField || 'all';
      if (dateRange) dateRange.value = preset.dateRange || '';
      if (dateFrom) dateFrom.value = preset.dateFrom || '';
      if (dateTo) dateTo.value = preset.dateTo || '';
      
      // Handle multi-select for role
      if (roleFilter) {
        Array.from(roleFilter.options).forEach(option => {
          option.selected = preset.role.includes(option.value);
        });
      }
      
      if (statusFilter) statusFilter.value = preset.status || '';
      if (verificationFilter) verificationFilter.value = preset.verification || '';
      if (activityFilter) activityFilter.value = preset.activity || '';
      
      // Show/hide custom date fields
      if (dateRange && dateRange.value === 'custom') {
        document.querySelectorAll('.date-range-custom').forEach(field => {
          field.style.display = 'block';
        });
      } else {
        document.querySelectorAll('.date-range-custom').forEach(field => {
          field.style.display = 'none';
        });
      }
      
      // Update count and fetch results
      countActiveFilters();
      fetchResults();
    }
    
    function deleteFilterPreset(name) {
      if (confirm(`Are you sure you want to delete the "${name}" filter preset?`)) {
        delete savedFilters[name];
        localStorage.setItem('adminFilterPresets', JSON.stringify(savedFilters));
        updateFilterPresetsList();
      }
    }
    
    function fetchResults(page = null) {
      if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
      }
      
      countActiveFilters();
      
      const params = new URLSearchParams();
      
      // Add search parameters
      if (globalSearch && globalSearch.value.trim() !== '') {
        params.append('q', globalSearch.value.trim());
        
        if (searchType && searchType.value !== 'contains') {
          params.append('searchType', searchType.value);
        }
        
        if (searchField && searchField.value !== 'all') {
          params.append('searchField', searchField.value);
        }
      }
      
      // Add date range parameters
      if (dateRange && dateRange.value) {
        params.append('dateRange', dateRange.value);
        
        if (dateRange.value === 'custom') {
          if (dateFrom && dateFrom.value) {
            params.append('dateFrom', dateFrom.value);
          }
          
          if (dateTo && dateTo.value) {
            params.append('dateTo', dateTo.value);
          }
        }
      }
      
      // Add filter parameters
      if (roleFilter) {
        const selectedRoles = Array.from(roleFilter.selectedOptions).map(o => o.value);
        if (selectedRoles.length > 0) {
          params.append('role', selectedRoles.join(','));
        }
      }
      
      if (statusFilter && statusFilter.value !== '') {
        params.append('status', statusFilter.value);
      }
      
      if (verificationFilter && verificationFilter.value !== '') {
        params.append('verification', verificationFilter.value);
      }
      
      if (activityFilter && activityFilter.value !== '') {
        params.append('activity', activityFilter.value);
      }
      
      // Add pagination parameter
      if (page) {
        params.append('page', page);
      }
      
      const url = '/admin?' + params.toString();
      
      // Update URL in browser without page reload
      window.history.replaceState(null, '', url);
      
      // Fetch the results via AJAX
      fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(html => {
        if (tableBody) {
          tableBody.innerHTML = html;
        }
        
        if (loadingIndicator) {
          loadingIndicator.style.display = 'none';
        }
        
        // Get updated pagination from server
        const paginationUrl = url + '&pagination_only=1';
        
        return fetch(paginationUrl, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
      })
      .then(response => response.text())
      .then(paginationHtml => {
        if (paginationContainer) {
          paginationContainer.innerHTML = paginationHtml;
          attachPaginationHandlers();
        }
        
        // Update results count if available
        if (resultsCount) {
          // Extract count from response if possible
          // This depends on how your backend provides this info
          const countMatch = paginationHtml.match(/Showing (\d+) results/);
          if (countMatch && countMatch[1]) {
            resultsCount.textContent = `Showing ${countMatch[1]} results`;
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        
        if (loadingIndicator) {
          loadingIndicator.style.display = 'none';
        }
        
        if (tableBody) {
          tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading data. Please try again.</td></tr>';
        }
      });
    }
    
    function attachPaginationHandlers() {
      const paginationLinks = document.querySelectorAll('.pagination a.page-link');
      
      paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const url = new URL(this.href);
          const page = url.searchParams.get('page');
          fetchResults(page);
        });
      });
    }
    
    // Initial pagination handlers
    attachPaginationHandlers();
  });