<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a test user ID for testing purposes
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists in the database

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test File Upload</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test File Upload</h1>
        
        <div class="card mb-4">
            <div class="card-header">Upload CSV or Excel File</div>
            <div class="card-body">
                <form id="upload-form">
                    <div class="form-group">
                        <label for="file-upload">Select File</label>
                        <input type="file" class="form-control-file" id="file-upload" accept=".csv,.xlsx,.xls">
                        <small class="form-text text-muted">Supported formats: CSV, Excel (.xlsx, .xls)</small>
                    </div>
                    <div id="selected-filename" class="alert alert-info d-none"></div>
                    <button type="submit" class="btn btn-primary" disabled>Upload & Analyze</button>
                </form>
            </div>
        </div>
        
        <div id="result" class="alert d-none"></div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/papaparse@5.3.0/papaparse.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.16.9/dist/xlsx.full.min.js"></script>
    
    <script>
        // Initialize file upload handlers
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file-upload');
            const uploadForm = document.getElementById('upload-form');
            
            if (fileInput && uploadForm) {
                fileInput.addEventListener('change', handleFileSelection);
                uploadForm.addEventListener('submit', handleFileUpload);
            }
        });
        
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
        
        function handleFileUpload(event) {
            event.preventDefault();
            
            const fileInput = document.getElementById('file-upload');
            const file = fileInput.files[0];
            
            if (!file) {
                showResult('Please select a file to upload', 'danger');
                return;
            }
            
            // Check file type
            const fileType = file.name.split('.').pop().toLowerCase();
            if (!['csv', 'xlsx', 'xls'].includes(fileType)) {
                showResult('Please upload a CSV or Excel file', 'danger');
                return;
            }
            
            // Show loading
            showResult('Uploading and processing your file...', 'info');
            
            // Read the file
            if (fileType === 'csv') {
                readCSV(file);
            } else if (['xlsx', 'xls'].includes(fileType)) {
                readExcel(file);
            }
        }
        
        function readCSV(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const contents = e.target.result;
                
                // Parse CSV using PapaParse
                Papa.parse(contents, {
                    header: true,
                    dynamicTyping: true,
                    complete: function(results) {
                        saveFileToServer(file.name, results.data);
                    },
                    error: function(error) {
                        showResult('Error parsing CSV file: ' + error.message, 'danger');
                    }
                });
            };
            
            reader.onerror = function() {
                showResult('Error reading file', 'danger');
            };
            
            reader.readAsText(file);
        }
        
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
                    
                    saveFileToServer(file.name, formattedData);
                } catch (error) {
                    showResult('Error parsing Excel file: ' + error.message, 'danger');
                }
            };
            
            reader.onerror = function() {
                showResult('Error reading file', 'danger');
            };
            
            reader.readAsArrayBuffer(file);
        }
        
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
                    showResult('File saved successfully! File ID: ' + data.file_id, 'success');
                } else {
                    showResult('Error saving file: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showResult('Error saving file: ' + error.message, 'danger');
            });
        }
        
        function showResult(message, type) {
            const resultElement = document.getElementById('result');
            if (resultElement) {
                resultElement.textContent = message;
                resultElement.className = `alert alert-${type}`;
                resultElement.classList.remove('d-none');
            }
        }
    </script>
</body>
</html>