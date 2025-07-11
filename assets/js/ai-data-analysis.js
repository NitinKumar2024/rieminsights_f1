/**
 * RiemInsights - AI Data Analysis JavaScript
 * Handles AI-powered data analysis functionality
 */

// Global variables
let currentFile = null;
let fileData = null;
let dataPreview = null;
let chartInstances = {};
let chatHistory = [];
let tokensRemaining = 0;
let summaryGenerated = false;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Initialize file upload handlers
    initFileUpload();
    
    // Initialize chat functionality
    initChat();
    
    // Initialize export functionality
    initExport();
    
    // Get tokens remaining from the page
    const tokenCountElement = document.querySelector('.token-count');
    if (tokenCountElement) {
        tokensRemaining = parseInt(tokenCountElement.textContent.replace(/,/g, ''), 10) || 0;
    }
});

/**
 * Initialize file upload functionality
 */
function initFileUpload() {
    const fileInput = document.getElementById('file-upload');
    const uploadForm = document.getElementById('upload-form');
    const fileUploadContainer = document.querySelector('.file-upload-container');
    
    if (fileInput && uploadForm) {
        fileInput.addEventListener('change', handleFileSelection);
        uploadForm.addEventListener('submit', handleFileUpload);
        
        // Add drag and drop functionality
        if (fileUploadContainer) {
            fileUploadContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });
            
            fileUploadContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });
            
            fileUploadContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelection({ target: fileInput });
                }
            });
        }
    }
}

/**
 * Handle file selection
 * @param {Event} event - The change event
 */
function handleFileSelection(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Update UI to show selected file
    const fileNameElement = document.getElementById('selected-filename');
    if (fileNameElement) {
        fileNameElement.textContent = file.name;
        fileNameElement.classList.remove('d-none');
    }
    
    // Enable the upload button
    const uploadButton = document.querySelector('#upload-form button[type="submit"]');
    if (uploadButton) {
        uploadButton.disabled = false;
    }
}

/**
 * Handle file upload
 * @param {Event} event - The submit event
 */
function handleFileUpload(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('file-upload');
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('Please select a file to upload', 'danger');
        return;
    }
    
    // Check file type
    const fileType = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'xlsx', 'xls'].includes(fileType)) {
        showAlert('Please upload a CSV or Excel file', 'danger');
        return;
    }
    
    // Check if user has enough tokens
    if (tokensRemaining <= 0) {
        showUpgradeModal();
        return;
    }
    
    // Show loading indicator
    showLoading('Uploading and processing your file...');
    
    // Read the file
    readFile(file);
}

/**
 * Read the uploaded file
 * @param {File} file - The file to read
 */
function readFile(file) {
    const fileType = file.name.split('.').pop().toLowerCase();
    
    if (fileType === 'csv') {
        // Parse CSV file
        Papa.parse(file, {
            header: true,
            dynamicTyping: true,
            complete: function(results) {
                processFileData(results.data, file.name);
            },
            error: function(error) {
                hideLoading();
                showAlert('Error parsing CSV file: ' + error.message, 'danger');
            }
        });
    } else if (['xlsx', 'xls'].includes(fileType)) {
        // Parse Excel file
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                
                // Convert to object array with headers
                const headers = jsonData[0];
                const rows = jsonData.slice(1).map(row => {
                    const obj = {};
                    headers.forEach((header, index) => {
                        obj[header] = row[index];
                    });
                    return obj;
                });
                
                processFileData(rows, file.name);
            } catch (error) {
                hideLoading();
                showAlert('Error parsing Excel file: ' + error.message, 'danger');
            }
        };
        reader.onerror = function() {
            hideLoading();
            showAlert('Error reading file', 'danger');
        };
        reader.readAsArrayBuffer(file);
    }
}

/**
 * Process the file data
 * @param {Array} data - The parsed file data
 * @param {string} fileName - The name of the file
 */
function processFileData(data, fileName) {
    // Store the data globally
    fileData = data;
    currentFile = fileName;
    
    // Create data preview
    createDataPreview(data);
    
    // Show the data analysis section
    document.getElementById('ai-data-analysis-section').classList.remove('d-none');
    
    // Hide the loading indicator
    hideLoading();
    
    // Generate AI summary
    generateAISummary(data, fileName);
    
    // Show welcome message in chat
    addAIMessage('Hello! I\'ve analyzed your data. You can ask me questions about it or request visualizations and insights.');
    
    // Show the no visualizations message
    document.getElementById('no-visualizations').classList.remove('d-none');
    
    // Scroll to the data preview section
    document.querySelector('.data-preview-section').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Create data preview tables
 * @param {Array} data - The parsed file data
 */
function createDataPreview(data) {
    if (!data || data.length === 0) {
        showAlert('The file contains no data', 'warning');
        return;
    }
    
    // Get the top 10 and bottom 10 rows
    const topRows = data.slice(0, 10);
    const bottomRows = data.slice(-10);
    
    // Get the headers (column names)
    const headers = Object.keys(data[0]);
    
    // Create the top rows table
    const topRowsTable = createTable(headers, topRows);
    document.getElementById('top-rows-preview').innerHTML = topRowsTable;
    
    // Create the bottom rows table
    const bottomRowsTable = createTable(headers, bottomRows);
    document.getElementById('bottom-rows-preview').innerHTML = bottomRowsTable;
    
    // Update file info
    const fileInfo = document.getElementById('file-info');
    if (fileInfo) {
        fileInfo.innerHTML = `<strong>File:</strong> ${currentFile} | <strong>Total Rows:</strong> ${data.length} | <strong>Columns:</strong> ${headers.length}`;
    }
}

/**
 * Create an HTML table from data
 * @param {Array} headers - The table headers
 * @param {Array} rows - The table rows
 * @returns {string} - The HTML table
 */
function createTable(headers, rows) {
    let tableHTML = '<table class="table table-striped table-sm">';
    
    // Add headers
    tableHTML += '<thead><tr>';
    headers.forEach(header => {
        tableHTML += `<th>${header}</th>`;
    });
    tableHTML += '</tr></thead>';
    
    // Add rows
    tableHTML += '<tbody>';
    rows.forEach(row => {
        tableHTML += '<tr>';
        headers.forEach(header => {
            const value = row[header];
            tableHTML += `<td>${value !== undefined && value !== null ? value : ''}</td>`;
        });
        tableHTML += '</tr>';
    });
    tableHTML += '</tbody>';
    
    tableHTML += '</table>';
    return tableHTML;
}

/**
 * Generate AI summary of the data
 * @param {Array} data - The parsed file data
 * @param {string} fileName - The name of the file
 */
function generateAISummary(data, fileName) {
    // Show loading indicator
    document.getElementById('ai-summary-loading').classList.remove('d-none');
    document.getElementById('ai-summary-content').classList.add('d-none');
    
    // Prepare the data for the API
    // Limit to 100 rows to avoid token limits
    const limitedData = data.slice(0, 100);
    
    // Create the request data
    const requestData = {
        action: 'analyze_data',
        question: 'Please provide a comprehensive summary of this dataset. Include information about the value ranges, and any notable patterns or insights. Format your response in markdown.',
        data: JSON.stringify(limitedData),
        file_name: fileName
    };
    
    // Call the DeepSeek API
    fetch('api/deepseek_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading indicator
        document.getElementById('ai-summary-loading').classList.add('d-none');
        
        if (data.success) {
            // Update tokens remaining
            updateTokensRemaining(data.tokens_remaining);
            
            // Update tokens used
            document.getElementById('summary-tokens-used').textContent = `${data.tokens_used} tokens`;
            
            // Display the summary
            const summaryContent = document.getElementById('ai-summary-content');
            summaryContent.innerHTML = marked.parse(data.response);
            summaryContent.classList.remove('d-none');
            
            // Set summary generated flag
            summaryGenerated = true;
            
            // Check if there's a visualization in the response
            if (data.visualization) {
                addVisualization(data.visualization, 'Summary Visualization');
            }
        } else {
            // Show error
            document.getElementById('ai-summary-content').innerHTML = `<div class="alert alert-danger">Error generating summary: ${data.message}</div>`;
            document.getElementById('ai-summary-content').classList.remove('d-none');
        }
    })
    .catch(error => {
        // Hide loading indicator
        document.getElementById('ai-summary-loading').classList.add('d-none');
        
        // Show error
        document.getElementById('ai-summary-content').innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        document.getElementById('ai-summary-content').classList.remove('d-none');
    });
}

/**
 * Initialize chat functionality
 */
function initChat() {
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const downloadChatBtn = document.getElementById('download-chat-btn');
    
    if (chatForm && chatInput) {
        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const message = chatInput.value.trim();
            if (!message) return;
            
            // Add user message to chat
            addUserMessage(message);
            
            // Clear input
            chatInput.value = '';
            
            // Process the message
            processUserMessage(message);
        });
        
        // Add suggestion chip functionality
        const suggestionChips = document.querySelectorAll('.suggestion-chip');
        suggestionChips.forEach(chip => {
            chip.addEventListener('click', function() {
                const suggestion = this.getAttribute('data-suggestion');
                chatInput.value = suggestion;
                chatInput.focus();
            });
        });
        
        // Add event listener for download chat button
        if (downloadChatBtn) {
            downloadChatBtn.addEventListener('click', function() {
                downloadChatHistory();
            });
        }
    }
}

/**
 * Add a user message to the chat
 * @param {string} message - The user message
 */
function addUserMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    const messageElement = document.createElement('div');
    messageElement.className = 'chat-message chat-message-user';
    
    const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageElement.innerHTML = `
        <div class="chat-bubble chat-bubble-user">${message}</div>
        <div class="chat-meta">${timestamp}</div>
    `;
    
    chatMessages.appendChild(messageElement);
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Add to chat history
    chatHistory.push({ role: 'user', content: message });
}

/**
 * Add an AI message to the chat
 * @param {string} message - The AI message
 */
function addAIMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    const messageElement = document.createElement('div');
    messageElement.className = 'chat-message chat-message-ai';
    
    const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    // Parse markdown in the message
    const parsedMessage = marked.parse(message);
    
    messageElement.innerHTML = `
        <div class="chat-bubble chat-bubble-ai">${parsedMessage}</div>
        <div class="chat-meta">${timestamp}</div>
    `;
    
    chatMessages.appendChild(messageElement);
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Add to chat history
    chatHistory.push({ role: 'assistant', content: message });
}

/**
 * Process a user message
 * @param {string} message - The user message
 */
function processUserMessage(message) {
    // Check if file is uploaded
    if (!fileData) {
        addAIMessage('Please upload a file first.');
        return;
    }
    
    // Check if user has enough tokens
    if (tokensRemaining <= 0) {
        addAIMessage('You have run out of tokens. Please upgrade your plan to continue using the AI features.');
        showUpgradeModal();
        return;
    }
    
    // Add loading message
    const chatMessages = document.getElementById('chat-messages');
    const loadingElement = document.createElement('div');
    loadingElement.className = 'chat-message chat-message-ai chat-loading';
    loadingElement.innerHTML = `
        <div class="chat-bubble chat-bubble-ai">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-primary mr-2" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <span>Thinking...</span>
            </div>
        </div>
    `;
    chatMessages.appendChild(loadingElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Prepare the data for the API
    // Limit to 100 rows to avoid token limits
    const limitedData = fileData.slice(0, 100);
    
    // Create the request data
    const requestData = {
        action: 'analyze_data',
        question: message,
        data: JSON.stringify(limitedData),
        file_name: currentFile
    };
    
    // Call the DeepSeek API
    fetch('api/deepseek_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Remove loading message
        chatMessages.removeChild(loadingElement);
        
        if (data.success) {
            // Update tokens remaining
            updateTokensRemaining(data.tokens_remaining);
            
            // Update tokens used
            document.getElementById('chat-tokens-used').textContent = `${data.tokens_used} tokens`;
            
            // Add AI response to chat
            addAIMessage(data.response);
            
            // Check if there's a visualization in the response
            if (data.visualization) {
                addVisualization(data.visualization, message);
                document.getElementById('no-visualizations').classList.add('d-none');
            }
        } else {
            // Show error
            addAIMessage(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        // Remove loading message
        chatMessages.removeChild(loadingElement);
        
        // Show error
        addAIMessage(`Error: ${error.message}`);
    });
}

/**
 * Add a visualization to the visualizations section
 * @param {Object} visualization - The visualization configuration
 * @param {string} title - The visualization title
 */
function addVisualization(visualization, title) {
    const visualizationsContainer = document.getElementById('ai-visualizations');
    if (!visualizationsContainer) return;
    
    // Create a unique ID for the chart
    const chartId = 'chart-' + Date.now();
    
    // Create the visualization container
    const container = document.createElement('div');
    container.className = 'visualization-container';
    container.innerHTML = `
        <div class="visualization-header">
            <div class="visualization-title">${title}</div>
            <div class="visualization-actions">
                <button class="btn btn-sm btn-outline-primary download-chart-btn" data-chart-id="${chartId}">
                    <i class="fas fa-download"></i> Download Chart
                </button>
            </div>
        </div>
        <div class="visualization-canvas-container">
            <canvas id="${chartId}"></canvas>
        </div>
    `;
    
    visualizationsContainer.appendChild(container);
    
    // Create the chart
    const ctx = document.getElementById(chartId).getContext('2d');
    const chart = new Chart(ctx, {
        type: visualization.type,
        data: visualization.data,
        options: {
            ...visualization.options,
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Store the chart instance
    chartInstances[chartId] = chart;
    
    // Add event listener for download button
    const downloadBtn = container.querySelector('.download-chart-btn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            downloadChart(chartId, title);
        });
    }
}

/**
 * Initialize export functionality
 */
function initExport() {
    const exportButton = document.getElementById('export-data');
    const exportFormat = document.getElementById('export-format');
    
    if (exportButton && exportFormat) {
        exportButton.addEventListener('click', function() {
            if (!fileData) {
                showAlert('No data to export', 'warning');
                return;
            }
            
            const format = exportFormat.value;
            exportData(fileData, format);
        });
    }
}

/**
 * Export data in the specified format
 * @param {Array} data - The data to export
 * @param {string} format - The export format (csv, json, xlsx)
 */
function exportData(data, format) {
    if (!data || data.length === 0) {
        showAlert('No data to export', 'warning');
        return;
    }
    
    const fileName = currentFile.split('.')[0] + '_export';
    
    switch (format) {
        case 'csv':
            exportCSV(data, fileName);
            break;
        case 'json':
            exportJSON(data, fileName);
            break;
        case 'xlsx':
            exportExcel(data, fileName);
            break;
        default:
            showAlert('Invalid export format', 'danger');
            break;
    }
}

/**
 * Export data as CSV
 * @param {Array} data - The data to export
 * @param {string} fileName - The file name
 */
function exportCSV(data, fileName) {
    const csv = Papa.unparse(data);
    downloadFile(csv, fileName + '.csv', 'text/csv');
}

/**
 * Export data as JSON
 * @param {Array} data - The data to export
 * @param {string} fileName - The file name
 */
function exportJSON(data, fileName) {
    const json = JSON.stringify(data, null, 2);
    downloadFile(json, fileName + '.json', 'application/json');
}

/**
 * Export data as Excel
 * @param {Array} data - The data to export
 * @param {string} fileName - The file name
 */
function exportExcel(data, fileName) {
    // Convert data to worksheet
    const worksheet = XLSX.utils.json_to_sheet(data);
    
    // Create workbook and add worksheet
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
    
    // Generate Excel file
    const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    
    // Convert to Blob and download
    const blob = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName + '.xlsx';
    a.click();
    
    URL.revokeObjectURL(url);
}

/**
 * Download a file
 * @param {string} content - The file content
 * @param {string} fileName - The file name
 * @param {string} contentType - The content type
 */
function downloadFile(content, fileName, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    
    URL.revokeObjectURL(url);
}

/**
 * Download a chart as an image
 * @param {string} chartId - The chart ID
 * @param {string} title - The chart title
 */
function downloadChart(chartId, title) {
    const chart = chartInstances[chartId];
    if (!chart) {
        showAlert('Chart not found', 'warning');
        return;
    }
    
    // Create a clean title for the filename
    const cleanTitle = title.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    const fileName = `chart_${cleanTitle}_${new Date().toISOString().slice(0, 10)}.png`;
    
    // Get the canvas element
    const canvas = document.getElementById(chartId);
    
    // Convert canvas to data URL
    const dataURL = canvas.toDataURL('image/png');
    
    // Create a link element
    const a = document.createElement('a');
    a.href = dataURL;
    a.download = fileName;
    a.click();
}

/**
 * Download chat history as text
 */
function downloadChatHistory() {
    if (!chatHistory || chatHistory.length === 0) {
        showAlert('No chat history to download', 'warning');
        return;
    }
    
    // Format chat history
    let content = 'AI Data Analysis Chat History\n';
    content += `Generated on: ${new Date().toLocaleString()}\n\n`;
    
    chatHistory.forEach((message, index) => {
        content += `${message.role === 'user' ? 'You' : 'AI'}: ${message.content}\n\n`;
    });
    
    // Download as text file
    const fileName = `chat_history_${new Date().toISOString().slice(0, 10)}.txt`;
    downloadFile(content, fileName, 'text/plain');
}

/**
 * Show an alert message
 * @param {string} message - The alert message
 * @param {string} type - The alert type (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) return;
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    alertsContainer.appendChild(alertElement);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertElement.classList.remove('show');
        setTimeout(() => {
            alertsContainer.removeChild(alertElement);
        }, 150);
    }, 5000);
}

/**
 * Show loading indicator
 * @param {string} message - The loading message
 */
function showLoading(message = 'Loading...') {
    const loadingContainer = document.getElementById('loading-container');
    if (!loadingContainer) return;
    
    loadingContainer.innerHTML = `
        <div class="loading-spinner"></div>
        <div class="loading-text">${message}</div>
    `;
    
    loadingContainer.classList.remove('d-none');
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    const loadingContainer = document.getElementById('loading-container');
    if (!loadingContainer) return;
    
    loadingContainer.classList.add('d-none');
}

/**
 * Show upgrade modal
 */
function showUpgradeModal() {
    const upgradeModal = document.getElementById('upgrade-modal');
    if (upgradeModal) {
        $(upgradeModal).modal('show');
    }
}

/**
 * Update tokens remaining
 * @param {number} tokens - The number of tokens remaining
 */
function updateTokensRemaining(tokens) {
    tokensRemaining = tokens;
    
    // Update the token count in the navbar
    const tokenCountElement = document.querySelector('.token-count');
    if (tokenCountElement) {
        tokenCountElement.textContent = tokens.toLocaleString();
    }
}