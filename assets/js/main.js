/**
 * RiemInsights - Main JavaScript File
 * Handles core functionality for the data analytics platform
 */

// Global variables
let currentFile = null;
let fileData = null;
let dataPreview = null;
let chartInstances = {};
let chatHistory = [];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Initialize file upload handlers
    initFileUpload();
    
    // Initialize chat functionality
    initChat();
    
    // Initialize export functionality
    initExport();
    
    // Check for token availability
    checkTokenAvailability();
    
    // Fetch uploaded files for index page
    if (document.getElementById('uploaded-files-list')) {
        fetchUploadedFiles();
    }
});

/**
 * Initialize file upload functionality
 */
function initFileUpload() {
    const fileInput = document.getElementById('file-upload');
    const uploadForm = document.getElementById('upload-form');
    
    if (fileInput && uploadForm) {
        fileInput.addEventListener('change', handleFileSelection);
        uploadForm.addEventListener('submit', handleFileUpload);
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
        readCSV(file);
    } else if (['xlsx', 'xls'].includes(fileType)) {
        readExcel(file);
    }
}

/**
 * Read CSV file
 * @param {File} file - The CSV file to read
 */
function readCSV(file) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const contents = e.target.result;
        
        // Parse CSV using PapaParse
        Papa.parse(contents, {
            header: true,
            dynamicTyping: true,
            complete: function(results) {
                processFileData(file.name, results.data, results.meta.fields);
            },
            error: function(error) {
                hideLoading();
                showAlert('Error parsing CSV file: ' + error.message, 'danger');
            }
        });
    };
    
    reader.onerror = function() {
        hideLoading();
        showAlert('Error reading file', 'danger');
    };
    
    reader.readAsText(file);
}

/**
 * Read Excel file
 * @param {File} file - The Excel file to read
 */
function readExcel(file) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        
        // Parse Excel using SheetJS
        try {
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
            
            // Extract headers and data
            const headers = jsonData[0];
            const rows = jsonData.slice(1);
            
            // Convert to array of objects
            const formattedData = rows.map(row => {
                const obj = {};
                headers.forEach((header, index) => {
                    obj[header] = row[index];
                });
                return obj;
            });
            
            processFileData(file.name, formattedData, headers);
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

/**
 * Process the file data
 * @param {string} fileName - The name of the file
 * @param {Array} data - The parsed data
 * @param {Array} headers - The column headers
 */
function processFileData(fileName, data, headers) {
    // Store the data globally
    currentFile = fileName;
    fileData = data;
    
    // Get a preview of the data (first 20 rows)
    // dataPreview = data.slice(0, 20);
    dataPreview = data;
    
    // Save file to server
    saveFileToServer(fileName, data);
    
    // Display the data preview
    displayDataPreview(dataPreview, headers);
    
    // Analyze the data for null values and statistics
    analyzeData(data, headers);
    
    // Hide loading indicator
    hideLoading();
    
    // Show the data analysis section
    showSection('data-analysis-section');
}

/**
 * Save file data to server
 * @param {string} fileName - The name of the file
 * @param {Array} data - The parsed data
 */
function saveFileToServer(fileName, data) {
    // Create a FormData object
    const formData = new FormData();
    formData.append('action', 'save_file');
    formData.append('file_name', fileName);
    formData.append('file_data', JSON.stringify(data));
    
    // Add column headers and data preview
    if (data.length > 0) {
        const headers = Object.keys(data[0]);
        formData.append('column_headers', JSON.stringify(headers));
        
        // Get a preview of the data (first 20 rows)
        const preview = data.slice(0, 20);
        formData.append('data_preview', JSON.stringify(preview));
    }
    
    // Send the data to the server
    fetch('api/file_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Error parsing JSON response:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success) {
            console.log('File saved successfully:', data.file_id);
            // Store the file ID for future reference
            if (data.file_id) {
                sessionStorage.setItem('current_file_id', data.file_id);
            }
        } else {
            console.error('Error saving file:', data.message);
            showAlert('Error saving file: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error saving file:', error);
        showAlert('Error saving file: ' + error.message, 'danger');
    });
}

/**
 * Display data preview in a table
 * @param {Array} data - The data to display
 * @param {Array} headers - The column headers
 */
function displayDataPreview(data, headers) {
    const previewContainer = document.getElementById('data-preview');
    if (!previewContainer) return;
    
    // Create table
    let tableHTML = '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr>';
    
    // Add headers
    headers.forEach(header => {
        tableHTML += `<th>${header}</th>`;
    });
    
    tableHTML += '</tr></thead><tbody>';
    
    // Add rows
    data.forEach(row => {
        tableHTML += '<tr>';
        headers.forEach(header => {
            const value = row[header];
            const cellClass = value === null || value === undefined || value === '' ? 'table-danger' : '';
            tableHTML += `<td class="${cellClass}">${value !== null && value !== undefined ? value : '<span class="text-danger">NULL</span>'}</td>`;
        });
        tableHTML += '</tr>';
    });
    
    tableHTML += '</tbody></table></div>';
    
    // Add note about preview
    tableHTML += '<p class="text-muted"><small>NULL or missing values are highlighted in red.</small></p>';
    
    // Update the container
    previewContainer.innerHTML = tableHTML;
    
    // Update file info
    const fileInfoElement = document.getElementById('file-info');
    if (fileInfoElement) {
        fileInfoElement.innerHTML = `<strong>File:</strong> ${currentFile} | <strong>Total Rows:</strong> ${fileData.length} | <strong>Columns:</strong> ${headers.length}`;
    }
}

/**
 * Analyze the data for null values and statistics
 * @param {Array} data - The data to analyze
 * @param {Array} headers - The column headers
 */
function analyzeData(data, headers) {
    const statsContainer = document.getElementById('data-stats');
    if (!statsContainer) return;
    
    // Initialize statistics
    const stats = {};
    headers.forEach(header => {
        stats[header] = {
            nullCount: 0,
            uniqueValues: new Set(),
            numeric: true,
            min: Number.MAX_VALUE,
            max: Number.MIN_VALUE,
            sum: 0,
            mean: 0
        };
    });
    
    // Calculate statistics
    data.forEach(row => {
        headers.forEach(header => {
            const value = row[header];
            
            // Check for null values
            if (value === null || value === undefined || value === '') {
                stats[header].nullCount++;
            } else {
                // Add to unique values
                stats[header].uniqueValues.add(value);
                
                // Check if numeric and update stats
                if (typeof value === 'number') {
                    stats[header].min = Math.min(stats[header].min, value);
                    stats[header].max = Math.max(stats[header].max, value);
                    stats[header].sum += value;
                } else {
                    stats[header].numeric = false;
                }
            }
        });
    });
    
    // Calculate means for numeric columns
    headers.forEach(header => {
        if (stats[header].numeric) {
            const validCount = data.length - stats[header].nullCount;
            stats[header].mean = validCount > 0 ? stats[header].sum / validCount : 0;
        }
    });
    
    // Create statistics display
    let statsHTML = '<div class="row">';
    
    // Add column statistics
    headers.forEach(header => {
        const columnStats = stats[header];
        statsHTML += `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-header">${header}</div>
                    <div class="card-body">
                        <p><strong>Missing Values:</strong> ${columnStats.nullCount} (${((columnStats.nullCount / data.length) * 100).toFixed(1)}%)</p>
                        <p><strong>Unique Values:</strong> ${columnStats.uniqueValues.size}</p>
                        ${columnStats.numeric ? `
                            <p><strong>Min:</strong> ${columnStats.min !== Number.MAX_VALUE ? columnStats.min : 'N/A'}</p>
                            <p><strong>Max:</strong> ${columnStats.max !== Number.MIN_VALUE ? columnStats.max : 'N/A'}</p>
                            <p><strong>Mean:</strong> ${columnStats.mean.toFixed(2)}</p>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    statsHTML += '</div>';
    
    // Update the container
    statsContainer.innerHTML = statsHTML;
    
    // Generate initial visualizations
    generateVisualizations(data, headers, stats);
}

/**
 * Generate initial visualizations
 * @param {Array} data - The data to visualize
 * @param {Array} headers - The column headers
 * @param {Object} stats - The data statistics
 */
function generateVisualizations(data, headers, stats) {
    const visualizationContainer = document.getElementById('visualizations');
    if (!visualizationContainer) return;
    
    // Clear previous visualizations
    visualizationContainer.innerHTML = '';
    
    // Create visualization options
    let optionsHTML = `
        <div class="mb-4">
            <h4>Visualize Your Data</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="chart-type">Chart Type</label>
                        <select class="form-control" id="chart-type">
                            <option value="bar">Bar Chart</option>
                            <option value="line">Line Chart</option>
                            <option value="pie">Pie Chart</option>
                            <option value="scatter">Scatter Plot</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="x-axis">X-Axis</label>
                        <select class="form-control" id="x-axis">
                            ${headers.map(header => `<option value="${header}">${header}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="y-axis">Y-Axis</label>
                        <select class="form-control" id="y-axis">
                            ${headers.map(header => `<option value="${header}">${header}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" id="generate-chart">Generate Chart</button>
        </div>
    `;
    
    // Create chart container
    optionsHTML += `
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <canvas id="data-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Chart Options</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="chart-title">Chart Title</label>
                            <input type="text" class="form-control" id="chart-title" placeholder="Enter chart title">
                        </div>
                        <div class="form-group">
                            <label for="color-scheme">Color Scheme</label>
                            <select class="form-control" id="color-scheme">
                                <option value="default">Default</option>
                                <option value="pastel">Pastel</option>
                                <option value="vibrant">Vibrant</option>
                                <option value="monochrome">Monochrome</option>
                            </select>
                        </div>
                        <button class="btn btn-success btn-sm" id="export-chart">Export Chart</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Update the container
    visualizationContainer.innerHTML = optionsHTML;
    
    // Initialize chart generation
    const generateChartButton = document.getElementById('generate-chart');
    if (generateChartButton) {
        generateChartButton.addEventListener('click', function() {
            const chartType = document.getElementById('chart-type').value;
            const xAxis = document.getElementById('x-axis').value;
            const yAxis = document.getElementById('y-axis').value;
            const chartTitle = document.getElementById('chart-title').value || 'Data Visualization';
            const colorScheme = document.getElementById('color-scheme').value;
            
            createChart(chartType, xAxis, yAxis, chartTitle, colorScheme, data);
        });
    }
    
    // Initialize chart export
    const exportChartButton = document.getElementById('export-chart');
    if (exportChartButton) {
        exportChartButton.addEventListener('click', exportChart);
    }
    
    // Generate a default chart (first two columns)
    if (headers.length >= 2) {
        setTimeout(() => {
            createChart('bar', headers[0], headers[1], 'Data Overview', 'default', data);
        }, 500);
    }
}

/**
 * Create a chart
 * @param {string} type - The chart type
 * @param {string} xAxis - The X-axis field
 * @param {string} yAxis - The Y-axis field
 * @param {string} title - The chart title
 * @param {string} colorScheme - The color scheme
 * @param {Array} data - The data to visualize
 */
function createChart(type, xAxis, yAxis, title, colorScheme, data) {
    const canvas = document.getElementById('data-chart');
    if (!canvas) return;
    
    // Destroy existing chart if any
    if (chartInstances.dataChart) {
        chartInstances.dataChart.destroy();
    }
    
    // Prepare the data
    const chartData = prepareChartData(type, xAxis, yAxis, colorScheme, data);
    
    // Create the chart
    const ctx = canvas.getContext('2d');
    chartInstances.dataChart = new Chart(ctx, {
        type: type,
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title,
                    font: {
                        size: 16
                    }
                },
                legend: {
                    display: type !== 'scatter',
                    position: 'top'
                },
                tooltip: {
                    enabled: true
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: xAxis
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: yAxis
                    }
                }
            }
        }
    });
}

/**
 * Prepare data for chart
 * @param {string} type - The chart type
 * @param {string} xAxis - The X-axis field
 * @param {string} yAxis - The Y-axis field
 * @param {string} colorScheme - The color scheme
 * @param {Array} data - The data to visualize
 * @returns {Object} - The prepared chart data
 */
function prepareChartData(type, xAxis, yAxis, colorScheme, data) {
    // Filter out rows with null values in the selected fields
    const filteredData = data.filter(row => {
        return row[xAxis] !== null && row[xAxis] !== undefined && row[xAxis] !== '' &&
               row[yAxis] !== null && row[yAxis] !== undefined && row[yAxis] !== '';
    });
    
    // Get color scheme
    const colors = getColorScheme(colorScheme, filteredData.length);
    
    // Prepare data based on chart type
    if (type === 'pie') {
        // For pie charts, aggregate data by x-axis values
        const aggregatedData = {};
        filteredData.forEach(row => {
            const key = row[xAxis].toString();
            if (!aggregatedData[key]) {
                aggregatedData[key] = 0;
            }
            aggregatedData[key] += parseFloat(row[yAxis]) || 1; // Use 1 for non-numeric values
        });
        
        return {
            labels: Object.keys(aggregatedData),
            datasets: [{
                data: Object.values(aggregatedData),
                backgroundColor: colors,
                borderWidth: 1
            }]
        };
    } else if (type === 'scatter') {
        // For scatter plots
        return {
            datasets: [{
                label: `${xAxis} vs ${yAxis}`,
                data: filteredData.map(row => ({
                    x: row[xAxis],
                    y: row[yAxis]
                })),
                backgroundColor: colors[0],
                borderColor: colors[0],
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        };
    } else {
        // For bar and line charts
        return {
            labels: filteredData.map(row => row[xAxis]),
            datasets: [{
                label: yAxis,
                data: filteredData.map(row => row[yAxis]),
                backgroundColor: type === 'line' ? colors[0] : colors,
                borderColor: type === 'line' ? colors[0] : 'rgba(0, 0, 0, 0.1)',
                borderWidth: 1,
                fill: type !== 'line'
            }]
        };
    }
}

/**
 * Get color scheme
 * @param {string} scheme - The color scheme name
 * @param {number} count - The number of colors needed
 * @returns {Array} - Array of colors
 */
function getColorScheme(scheme, count) {
    const schemes = {
        default: [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
        ],
        pastel: [
            'rgba(187, 222, 251, 0.7)',
            'rgba(255, 236, 179, 0.7)',
            'rgba(209, 196, 233, 0.7)',
            'rgba(200, 230, 201, 0.7)',
            'rgba(255, 205, 210, 0.7)',
            'rgba(225, 190, 231, 0.7)'
        ],
        vibrant: [
            'rgba(0, 123, 255, 0.7)',
            'rgba(220, 53, 69, 0.7)',
            'rgba(255, 193, 7, 0.7)',
            'rgba(40, 167, 69, 0.7)',
            'rgba(111, 66, 193, 0.7)',
            'rgba(23, 162, 184, 0.7)'
        ],
        monochrome: [
            'rgba(33, 33, 33, 0.9)',
            'rgba(66, 66, 66, 0.8)',
            'rgba(97, 97, 97, 0.7)',
            'rgba(117, 117, 117, 0.6)',
            'rgba(158, 158, 158, 0.5)',
            'rgba(189, 189, 189, 0.4)'
        ]
    };
    
    const selectedScheme = schemes[scheme] || schemes.default;
    
    // If we need more colors than available, repeat the scheme
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(selectedScheme[i % selectedScheme.length]);
    }
    
    return colors;
}

/**
 * Export the current chart
 */
function exportChart() {
    const canvas = document.getElementById('data-chart');
    if (!canvas || !chartInstances.dataChart) {
        showAlert('No chart to export', 'warning');
        return;
    }
    
    // Get chart title or use default
    const chartTitle = document.getElementById('chart-title').value || 'data_visualization';
    const fileName = chartTitle.replace(/\s+/g, '_').toLowerCase() + '.png';
    
    // Create a temporary link
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = fileName;
    
    // Trigger download
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Chart exported successfully', 'success');
}

/**
 * Initialize chat functionality
 */
function initChat() {
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    
    if (chatForm && chatInput) {
        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const question = chatInput.value.trim();
            if (!question) return;
            
            // Check if file is uploaded
            if (!fileData) {
                showAlert('Please upload a file first', 'warning');
                return;
            }
            
            // Check token availability
            if (!checkTokenAvailability(true)) {
                return;
            }
            
            // Add user message to chat
            addChatMessage('user', question);
            
            // Clear input
            chatInput.value = '';
            
            // Show loading indicator
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.innerHTML += '<div class="chat-loading"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Analyzing...</div>';
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Send to DeepSeek API
            sendToDeepSeek(question, fileData);
        });
    }
}

/**
 * Send data to DeepSeek API
 * @param {string} question - The user's question
 * @param {Array} data - The complete file data
 */
function sendToDeepSeek(question, data) {
    // Prepare the request data
    const requestData = {
        action: 'analyze_data',
        question: question,
        data: JSON.stringify(data),
        file_name: currentFile
    };
    
    // Send the request to the server
    fetch('api/deepseek_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Get the response text first
        return response.text();
    })
    .then(text => {
        // Try to parse the text as JSON
        try {
            // Check if the response starts with HTML tags (error page)
            if (text.trim().startsWith('<')) {
                throw new Error(`Server returned HTML instead of JSON. This usually indicates a PHP error. Response starts with: ${text.substring(0, 100)}...`);
            }
            return JSON.parse(text);
        } catch (e) {
            console.error('Error parsing JSON response:', text);
            throw new Error(`Invalid JSON response from server: ${e.message}. Response starts with: ${text.substring(0, 100)}...`);
        }
    })
    .then(data => {
        // Remove loading indicator
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            const loadingElement = chatMessages.querySelector('.chat-loading');
            if (loadingElement) {
                chatMessages.removeChild(loadingElement);
            }
        }
        
        if (data.success) {
            // Add AI response to chat
            addChatMessage('ai', data.response);
            
            // Update tokens used
            if (data.tokens_used) {
                updateTokensDisplay(data.tokens_remaining);
            }
            
            // Check if there's a visualization to display
            if (data.visualization) {
                displayAIVisualization(data.visualization);
            }
        } else {
            console.error('API error:', data.message);
            showAlert('Error: ' + data.message, 'danger');
            addChatMessage('ai', 'Sorry, I encountered an error while processing your request. Please try again or contact support if the issue persists.');
        }
    })
    .catch(error => {
        console.error('Error sending to DeepSeek:', error);
        showAlert('Error communicating with the AI service: ' + error.message, 'danger');
        
        // Remove loading indicator
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            const loadingElement = chatMessages.querySelector('.chat-loading');
            if (loadingElement) {
                chatMessages.removeChild(loadingElement);
            }
        }
        
        // Add error message to chat
        addChatMessage('ai', 'Sorry, I encountered an error while processing your request. Please try again or contact support if the issue persists.');
    });
}

/**
 * Add a message to the chat
 * @param {string} sender - 'user' or 'ai'
 * @param {string} message - The message content
 */
function addChatMessage(sender, message) {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.className = `chat-message ${sender}-message`;
    
    // Create message content
    const contentElement = document.createElement('div');
    contentElement.className = 'message-content';
    
    // Format message if it's from AI (might contain markdown)
    if (sender === 'ai') {
        // Use marked.js to parse markdown
        contentElement.innerHTML = marked.parse(message);
        
        // Add syntax highlighting to code blocks
        document.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightBlock(block);
        });
    } else {
        contentElement.textContent = message;
    }
    
    // Add sender icon
    const iconElement = document.createElement('div');
    iconElement.className = 'message-icon';
    iconElement.innerHTML = sender === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
    
    // Assemble message
    messageElement.appendChild(iconElement);
    messageElement.appendChild(contentElement);
    
    // Add to chat
    chatMessages.appendChild(messageElement);
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Add to history
    chatHistory.push({
        role: sender === 'user' ? 'user' : 'assistant',
        content: message
    });
}

/**
 * Display AI-generated visualization
 * @param {Object} visualization - The visualization data
 */
function displayAIVisualization(visualization) {
    // Create a new canvas for the AI visualization
    const aiVisualizationContainer = document.getElementById('ai-visualization');
    if (!aiVisualizationContainer) return;
    
    // Clear previous visualizations
    aiVisualizationContainer.innerHTML = '<h4>AI-Generated Visualization</h4><div class="card"><div class="card-body"><canvas id="ai-chart"></canvas></div></div>';
    
    const canvas = document.getElementById('ai-chart');
    if (!canvas) return;
    
    // Destroy existing chart if any
    if (chartInstances.aiChart) {
        chartInstances.aiChart.destroy();
    }
    
    // Create the chart
    const ctx = canvas.getContext('2d');
    chartInstances.aiChart = new Chart(ctx, visualization);
    
    // Show the container
    aiVisualizationContainer.classList.remove('d-none');
}

/**
 * Fetch and display uploaded files on the index page
 */
function fetchUploadedFiles() {
    const uploadedFilesList = document.getElementById('uploaded-files-list');
    if (!uploadedFilesList) return;
    
    fetch('api/file_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'list_files'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.files && data.files.length > 0) {
            // Create table to display files
            let html = '<table class="table table-hover">';
            html += '<thead><tr><th>File Name</th><th>Type</th><th>Size</th><th>Upload Date</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            data.files.forEach(file => {
                html += `<tr>
                    <td>${file.file_name}</td>
                    <td>${file.file_type}</td>
                    <td>${file.file_size}</td>
                    <td>${file.upload_date}</td>
                    <td>
                        <a href="data_analysis.php?file_id=${file.file_id}" class="btn btn-sm btn-primary"><i class="fas fa-chart-bar"></i> Analyze</a>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            uploadedFilesList.innerHTML = html;
        } else {
            uploadedFilesList.innerHTML = '<p class="text-center text-muted">No files uploaded yet. <a href="data_analysis.php">Upload your first file</a> to get started.</p>';
        }
    })
    .catch(error => {
        console.error('Error fetching files:', error);
        uploadedFilesList.innerHTML = '<p class="text-center text-danger">Error loading your files. Please refresh the page or try again later.</p>';
    });
}

/**
 * Initialize export functionality
 */
function initExport() {
    const exportDataButton = document.getElementById('export-data');
    if (exportDataButton) {
        exportDataButton.addEventListener('click', exportData);
    }
}

/**
 * Export the data
 */
function exportData() {
    if (!fileData) {
        showAlert('No data to export', 'warning');
        return;
    }
    
    // Get export format
    const exportFormat = document.getElementById('export-format').value;
    
    // Prepare file name
    const fileName = currentFile.split('.')[0] + '_export.' + exportFormat;
    
    if (exportFormat === 'csv') {
        exportAsCSV(fileName);
    } else if (exportFormat === 'json') {
        exportAsJSON(fileName);
    } else if (exportFormat === 'xlsx') {
        exportAsExcel(fileName);
    }
}

/**
 * Export data as CSV
 * @param {string} fileName - The file name
 */
function exportAsCSV(fileName) {
    // Get headers
    const headers = Object.keys(fileData[0]);
    
    // Create CSV content
    let csvContent = headers.join(',') + '\n';
    
    fileData.forEach(row => {
        const values = headers.map(header => {
            const value = row[header];
            return value !== null && value !== undefined ? `"${value}"` : '""';
        });
        csvContent += values.join(',') + '\n';
    });
    
    // Create a blob and download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Data exported as CSV', 'success');
}

/**
 * Export data as JSON
 * @param {string} fileName - The file name
 */
function exportAsJSON(fileName) {
    // Create a blob and download
    const blob = new Blob([JSON.stringify(fileData, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('Data exported as JSON', 'success');
}

/**
 * Export data as Excel
 * @param {string} fileName - The file name
 */
function exportAsExcel(fileName) {
    // Get headers
    const headers = Object.keys(fileData[0]);
    
    // Create worksheet
    const worksheet = XLSX.utils.json_to_sheet(fileData);
    
    // Create workbook
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
    
    // Generate Excel file and download
    XLSX.writeFile(workbook, fileName);
    
    showAlert('Data exported as Excel', 'success');
}

/**
 * Check token availability
 * @param {boolean} showMessage - Whether to show a message if tokens are insufficient
 * @returns {boolean} - Whether tokens are available
 */
function checkTokenAvailability(showMessage = false) {
    const tokenCountElement = document.querySelector('.token-count');
    if (!tokenCountElement) return true;
    
    const tokensRemaining = parseInt(tokenCountElement.textContent.replace(/,/g, ''), 10);
    
    if (tokensRemaining <= 0) {
        if (showMessage) {
            showAlert('You have no tokens remaining. Please upgrade your plan to continue.', 'warning');
        }
        
        // Show upgrade modal
        const upgradeModal = document.getElementById('upgrade-modal');
        if (upgradeModal) {
            const bsModal = new bootstrap.Modal(upgradeModal);
            bsModal.show();
        }
        
        return false;
    }
    
    return true;
}

/**
 * Update tokens display
 * @param {number} tokensRemaining - The number of tokens remaining
 */
function updateTokensDisplay(tokensRemaining) {
    const tokenCountElement = document.querySelector('.token-count');
    if (tokenCountElement) {
        tokenCountElement.textContent = tokensRemaining.toLocaleString();
    }
}

/**
 * Show an alert message
 * @param {string} message - The message to display
 * @param {string} type - The alert type (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) return;
    
    // Create alert element
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Add to container
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
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <div class="loading-message">${message}</div>
        </div>
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
    loadingContainer.innerHTML = '';
}

/**
 * Show a section
 * @param {string} sectionId - The ID of the section to show
 */
function showSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    
    section.classList.remove('d-none');
}