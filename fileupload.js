
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    checkAuthState();
    
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file-input');
    const uploadBtn = document.getElementById('upload-btn');
    const fileList = document.getElementById('file-list');
    const uploadStatus = document.getElementById('upload-status');
    
    // Selected files storage
    let selectedFiles = [];
    
    // Initialize the file upload area
    initFileUpload();
    
    // Load existing files if user is logged in
    if (localStorage.getItem('userLoggedIn') === 'true') {
        loadUserFiles();
    }
    
    function initFileUpload() {
        // Click on drop area to open file dialog
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Prevent default behaviors for drag events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        // Highlight drop area when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        // Remove highlight when leaving or dropping
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        // Handle dropped files
        dropArea.addEventListener('drop', handleDrop, false);
        
        // Handle selected files via input
        fileInput.addEventListener('change', handleFiles);
        
        // Handle upload button click
        uploadBtn.addEventListener('click', uploadFiles);
    }
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight() {
        dropArea.classList.add('highlight');
    }
    
    function unhighlight() {
        dropArea.classList.remove('highlight');
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles({ target: { files } });
    }
    
    function handleFiles(e) {
        const files = Array.from(e.target.files);
        
        // Clear previous selections
        selectedFiles = [];
        fileList.innerHTML = '';
        
        // Process each file
        files.forEach((file, index) => {
            selectedFiles.push(file);
            
            // Create file item element
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Determine file icon based on type
            let fileIconClass = 'fa-file';
            if (file.type.match('image.*')) fileIconClass = 'fa-file-image';
            else if (file.type.match('application/pdf')) fileIconClass = 'fa-file-pdf';
            else if (file.type.match('text/csv') || file.name.endsWith('.csv')) fileIconClass = 'fa-file-csv';
            else if (file.type.match('application/vnd.ms-excel') || 
                    file.type.match('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') ||
                    file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                fileIconClass = 'fa-file-excel';
            }
            
            // Format file size
            const fileSize = formatFileSize(file.size);
            
            // Build file item content
            fileItem.innerHTML = `
                <div class="file-icon"><i class="fas ${fileIconClass}"></i></div>
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize}</div>
                <div class="file-remove" data-index="${index}"><i class="fas fa-times"></i></div>
                <div class="file-progress"><div class="progress-bar" id="progress-${index}"></div></div>
            `;
            
            // Add file item to list
            fileList.appendChild(fileItem);
            
            // Add remove handler
            const removeBtn = fileItem.querySelector('.file-remove');
            removeBtn.addEventListener('click', function() {
                const fileIndex = parseInt(this.getAttribute('data-index'));
                removeFile(fileIndex);
            });
        });
        
        // Enable or disable upload button
        uploadBtn.disabled = selectedFiles.length === 0;
        
        // Update status
        if (selectedFiles.length > 0) {
            updateStatus(`${selectedFiles.length} file(s) selected`, 'neutral');
        } else {
            updateStatus('', 'neutral');
        }
    }
    
    function removeFile(index) {
        // Remove file from array
        selectedFiles.splice(index, 1);
        
        // Rebuild file list UI
        fileList.innerHTML = '';
        selectedFiles.forEach((file, i) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            let fileIconClass = 'fa-file';
            if (file.type.match('image.*')) fileIconClass = 'fa-file-image';
            else if (file.type.match('application/pdf')) fileIconClass = 'fa-file-pdf';
            else if (file.type.match('text/csv') || file.name.endsWith('.csv')) fileIconClass = 'fa-file-csv';
            else if (file.type.match('application/vnd.ms-excel') || 
                    file.type.match('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') ||
                    file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                fileIconClass = 'fa-file-excel';
            }
            
            const fileSize = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <div class="file-icon"><i class="fas ${fileIconClass}"></i></div>
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize}</div>
                <div class="file-remove" data-index="${i}"><i class="fas fa-times"></i></div>
                <div class="file-progress"><div class="progress-bar" id="progress-${i}"></div></div>
            `;
            
            fileList.appendChild(fileItem);
            
            const removeBtn = fileItem.querySelector('.file-remove');
            removeBtn.addEventListener('click', function() {
                const fileIndex = parseInt(this.getAttribute('data-index'));
                removeFile(fileIndex);
            });
        });
        
        // Enable or disable upload button
        uploadBtn.disabled = selectedFiles.length === 0;
        
        // Update status
        if (selectedFiles.length > 0) {
            updateStatus(`${selectedFiles.length} file(s) selected`, 'neutral');
        } else {
            updateStatus('', 'neutral');
        }
    }
    
    function uploadFiles() {
        if (selectedFiles.length === 0) return;
        
        // Check if user is logged in
        if (localStorage.getItem('userLoggedIn') !== 'true') {
            updateStatus('Please log in to upload files', 'error');
            window.location.href = 'login.html';
            return;
        }
        
        // Create FormData instance
        const formData = new FormData();
        
        // Append each file to FormData
        selectedFiles.forEach((file, index) => {
            formData.append('files[]', file);
        });
        
        // Disable upload button during upload
        uploadBtn.disabled = true;
        
        // Update status
        updateStatus('Uploading files...', 'neutral');
        
        // Send AJAX request
        fetch('file_upload.php', {
            method: 'POST',
            body: formData,
            // Don't set Content-Type header, let browser set it with boundary
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatus('Files uploaded successfully!', 'success');
                // Clear selected files
                selectedFiles = [];
                fileList.innerHTML = '';
                // Load updated file list
                loadUserFiles();
            } else {
                updateStatus(`Upload failed: ${data.message}`, 'error');
                uploadBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            updateStatus('An error occurred during upload', 'error');
            uploadBtn.disabled = false;
        });
    }
    
    function loadUserFiles() {
        // This would typically fetch user's files from server
        // For now, we'll just display a sample or placeholder
        
        // In a real application, you'd make an AJAX request to get user's files
        // fetch('get_user_files.php')
        //   .then(response => response.json())
        //   .then(data => {
        //     // Display user's files
        //   });
        
        // For demonstration, add some sample files
        const sampleFiles = [
            { name: 'budget_2025.xlsx', size: 1024 * 1024 * 2.5, type: 'excel' },
            { name: 'receipt_march.pdf', size: 1024 * 512, type: 'pdf' },
            { name: 'tax_return.pdf', size: 1024 * 1024 * 3.2, type: 'pdf' }
        ];
        
        // Clear file list first
        fileList.innerHTML = '';
        
        // Add each file to the list
        sampleFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Determine icon based on file type
            let fileIconClass = 'fa-file';
            if (file.type === 'excel') fileIconClass = 'fa-file-excel';
            else if (file.type === 'pdf') fileIconClass = 'fa-file-pdf';
            else if (file.type === 'csv') fileIconClass = 'fa-file-csv';
            else if (file.type === 'image') fileIconClass = 'fa-file-image';
            
            const fileSize = formatFileSize(file.size);
            
            // Build file item content
            fileItem.innerHTML = `
                <div class="file-icon"><i class="fas ${fileIconClass}"></i></div>
                <div class="file-name">${file.name}</div>
                <div class="file-size">${fileSize}</div>
                <div class="file-download"><i class="fas fa-download"></i></div>
            `;
            
            fileList.appendChild(fileItem);
        });
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function updateStatus(message, type) {
        uploadStatus.textContent = message;
        uploadStatus.className = 'file-status';
        
        if (type === 'success') {
            uploadStatus.classList.add('status-success');
        } else if (type === 'error') {
            uploadStatus.classList.add('status-error');
        }
    }
});

// Function to handle logout
function handleLogout() {
    localStorage.removeItem('userLoggedIn');
    localStorage.removeItem('userName');
    window.location.href = 'login.html';
}
