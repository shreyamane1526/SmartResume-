// Resume Analyzer JavaScript functionality
let jobRoles = [];
let analysisCriteria = {};
let uploadedFile = null;
let selectedTargetRole = null;

document.addEventListener('DOMContentLoaded', function() {
    // Load job roles and analysis criteria
    loadJobRoles();
    loadAnalysisCriteria();
    
    // Initialize upload functionality
    initializeUpload();
    
    // Initialize form handlers
    initializeAnalyzerHandlers();
});

/**
 * Load job roles for target role selection
 */
async function loadJobRoles() {
    try {
        const response = await fetch('data/job-roles.json');
        jobRoles = await response.json();
        populateTargetRoleSelect();
    } catch (error) {
        console.error('Error loading job roles:', error);
        showAlert('danger', 'Failed to load job roles. Please refresh the page.');
    }
}

/**
 * Load analysis criteria
 */
async function loadAnalysisCriteria() {
    try {
        const response = await fetch('data/analysis-criteria.json');
        analysisCriteria = await response.json();
    } catch (error) {
        console.error('Error loading analysis criteria:', error);
    }
}

/**
 * Populate target role select dropdown
 */
function populateTargetRoleSelect() {
    const select = document.getElementById('targetRole');
    
    jobRoles.forEach(role => {
        const option = document.createElement('option');
        option.value = role.id;
        option.textContent = role.name;
        select.appendChild(option);
    });
    
    // Handle role selection
    select.addEventListener('change', function() {
        selectedTargetRole = jobRoles.find(role => role.id === this.value);
        updateAnalyzeButton();
    });
}

/**
 * Initialize file upload functionality
 */
function initializeUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('resumeFile');
    const fileInfo = document.getElementById('fileInfo');
    
    // Click to upload
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });
    
    // Remove file
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-file') || e.target.closest('.remove-file')) {
            removeFile();
        }
    });
}

/**
 * Handle file selection
 */
function handleFileSelect(file) {
    // Validate file type
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedTypes.includes(file.type)) {
        showAlert('danger', 'Please upload a PDF, DOC, or DOCX file.');
        return;
    }
    
    // Validate file size (5MB max)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        showAlert('danger', 'File size must be less than 5MB.');
        return;
    }
    
    uploadedFile = file;
    displayFileInfo(file);
    updateAnalyzeButton();
}

/**
 * Display file information
 */
function displayFileInfo(file) {
    const fileInfo = document.getElementById('fileInfo');
    const uploadArea = document.getElementById('uploadArea');
    
    fileInfo.querySelector('.file-name').textContent = file.name;
    fileInfo.querySelector('.file-size').textContent = formatFileSize(file.size);
    
    uploadArea.style.display = 'none';
    fileInfo.style.display = 'block';
}

/**
 * Remove uploaded file
 */
function removeFile() {
    uploadedFile = null;
    
    const fileInfo = document.getElementById('fileInfo');
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('resumeFile');
    
    fileInfo.style.display = 'none';
    uploadArea.style.display = 'block';
    fileInput.value = '';
    
    updateAnalyzeButton();
    hideResults();
}

/**
 * Update analyze button state
 */
function updateAnalyzeButton() {
    const analyzeBtn = document.getElementById('analyzeBtn');
    const canAnalyze = uploadedFile && selectedTargetRole;
    
    analyzeBtn.disabled = !canAnalyze;
    
    if (canAnalyze) {
        analyzeBtn.innerHTML = '<i class="fas fa-search me-2"></i>Analyze Resume';
    } else {
        const missingItems = [];
        if (!uploadedFile) missingItems.push('upload a resume');
        if (!selectedTargetRole) missingItems.push('select target role');
        
        analyzeBtn.innerHTML = `<i class="fas fa-search me-2"></i>Please ${missingItems.join(' and ')}`;
    }
}

/**
 * Initialize analyzer handlers
 */
function initializeAnalyzerHandlers() {
    const analyzeBtn = document.getElementById('analyzeBtn');
    const analyzeAgainBtn = document.getElementById('analyzeAgain');
    
    analyzeBtn.addEventListener('click', function() {
        if (uploadedFile && selectedTargetRole) {
            analyzeResume();
        }
    });
    
    if (analyzeAgainBtn) {
        analyzeAgainBtn.addEventListener('click', function() {
            resetAnalyzer();
        });
    }
}

/**
 * Analyze the uploaded resume
 */
function analyzeResume() {
    showLoading();
    hideResults();
    
    const formData = new FormData();
    formData.append('resume', uploadedFile);
    formData.append('targetRole', selectedTargetRole.id);
    
    fetch('php/analyze-resume.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            displayResults(data.analysis);
        } else {
            showAlert('danger', data.message || 'Failed to analyze resume. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        showAlert('danger', 'Network error. Please check your connection and try again.');
    });
}

/**
 * Display analysis results
 */
function displayResults(analysis) {
    const resultsContainer = document.getElementById('analysisResults');
    
    // Update overall score
    updateScoreDisplay(analysis.overallScore);
    
    // Update score description
    const scoreDescription = document.getElementById('scoreDescription');
    scoreDescription.textContent = getScoreDescription(analysis.overallScore);
    
    // Populate strengths
    populateList('strengthsList', analysis.strengths);
    
    // Populate improvements
    populateList('improvementsList', analysis.improvements);
    
    // Populate suggestions
    populateList('suggestionsList', analysis.suggestions);
    
    // Update keyword analysis
    updateKeywordAnalysis(analysis.keywords);
    
    // Show results
    resultsContainer.style.display = 'block';
    
    // Scroll to results
    resultsContainer.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Update score display with animation
 */
function updateScoreDisplay(score) {
    const scoreCircle = document.getElementById('scoreCircle');
    const scoreValue = document.getElementById('scoreValue');
    const scoreLabel = document.getElementById('scoreLabel');
    
    // Remove existing score classes
    scoreCircle.classList.remove('score-excellent', 'score-good', 'score-poor');
    
    // Add appropriate class based on score
    if (score >= 80) {
        scoreCircle.classList.add('score-excellent');
        scoreLabel.textContent = 'Excellent Resume';
    } else if (score >= 60) {
        scoreCircle.classList.add('score-good');
        scoreLabel.textContent = 'Good Resume';
    } else {
        scoreCircle.classList.add('score-poor');
        scoreLabel.textContent = 'Needs Improvement';
    }
    
    // Animate score counting
    animateScore(scoreValue, score);
}

/**
 * Animate score counting
 */
function animateScore(element, targetScore) {
    let currentScore = 0;
    const increment = targetScore / 30; // 30 frames for smooth animation
    
    const timer = setInterval(() => {
        currentScore += increment;
        
        if (currentScore >= targetScore) {
            currentScore = targetScore;
            clearInterval(timer);
        }
        
        element.textContent = Math.round(currentScore);
    }, 50);
}

/**
 * Get score description
 */
function getScoreDescription(score) {
    if (score >= 90) {
        return 'Outstanding! Your resume is well-optimized and highly competitive.';
    } else if (score >= 80) {
        return 'Great job! Your resume is strong with minor areas for improvement.';
    } else if (score >= 70) {
        return 'Good start! Your resume has solid content but could use some optimization.';
    } else if (score >= 60) {
        return 'Getting there! Several improvements will significantly boost your resume.';
    } else {
        return 'Needs work. Significant improvements required to make your resume competitive.';
    }
}

/**
 * Populate analysis lists
 */
function populateList(listId, items) {
    const list = document.getElementById(listId);
    list.innerHTML = '';
    
    if (items && items.length > 0) {
        items.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item;
            list.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'No specific items identified.';
        li.style.fontStyle = 'italic';
        li.style.opacity = '0.7';
        list.appendChild(li);
    }
}

/**
 * Update keyword analysis
 */
function updateKeywordAnalysis(keywordData) {
    const keywordsFound = document.getElementById('keywordsFound');
    const missingKeywords = document.getElementById('missingKeywords');
    const missingKeywordsList = document.getElementById('missingKeywordsList');
    
    keywordsFound.textContent = keywordData.found || 0;
    missingKeywords.textContent = keywordData.missing?.length || 0;
    
    // Display missing keywords
    missingKeywordsList.innerHTML = '';
    if (keywordData.missing && keywordData.missing.length > 0) {
        keywordData.missing.forEach(keyword => {
            const span = document.createElement('span');
            span.className = 'missing-keyword';
            span.textContent = keyword;
            missingKeywordsList.appendChild(span);
        });
    } else {
        const span = document.createElement('span');
        span.textContent = 'All important keywords are present!';
        span.style.color = 'var(--success-color)';
        span.style.fontStyle = 'italic';
        missingKeywordsList.appendChild(span);
    }
}

/**
 * Show loading state
 */
function showLoading() {
    const loadingState = document.getElementById('loadingState');
    const initialState = document.getElementById('initialState');
    
    initialState.style.display = 'none';
    loadingState.classList.add('show');
}

/**
 * Hide loading state
 */
function hideLoading() {
    const loadingState = document.getElementById('loadingState');
    loadingState.classList.remove('show');
}

/**
 * Hide results
 */
function hideResults() {
    const resultsContainer = document.getElementById('analysisResults');
    const initialState = document.getElementById('initialState');
    
    resultsContainer.style.display = 'none';
    
    if (!document.getElementById('loadingState').classList.contains('show')) {
        initialState.style.display = 'block';
    }
}

/**
 * Reset analyzer to initial state
 */
function resetAnalyzer() {
    removeFile();
    hideResults();
    
    const targetRole = document.getElementById('targetRole');
    targetRole.value = '';
    selectedTargetRole = null;
    
    const initialState = document.getElementById('initialState');
    initialState.style.display = 'block';
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Enhanced error handling for file operations
 */
function handleFileError(error) {
    console.error('File operation error:', error);
    
    let errorMessage = 'An error occurred while processing your file.';
    
    if (error.name === 'QuotaExceededError') {
        errorMessage = 'Storage quota exceeded. Please try with a smaller file.';
    } else if (error.name === 'SecurityError') {
        errorMessage = 'Security error. Please ensure your file is not corrupted.';
    } else if (error.name === 'NotReadableError') {
        errorMessage = 'File could not be read. Please try with a different file.';
    }
    
    showAlert('danger', errorMessage);
}

/**
 * Validate file content (basic check)
 */
function validateFileContent(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const content = e.target.result;
            
            // Basic validation - check if file has some text content
            if (file.type === 'application/pdf') {
                // For PDF, we'll rely on server-side validation
                resolve(true);
            } else {
                // For DOC/DOCX, check if it has some content
                if (content.length < 100) {
                    reject(new Error('File appears to be empty or corrupted.'));
                } else {
                    resolve(true);
                }
            }
        };
        
        reader.onerror = function() {
            reject(new Error('Failed to read file.'));
        };
        
        // Read as array buffer for basic validation
        reader.readAsArrayBuffer(file);
    });
}

// Add file content validation to file selection
const originalHandleFileSelect = handleFileSelect;
handleFileSelect = function(file) {
    validateFileContent(file)
        .then(() => {
            originalHandleFileSelect(file);
        })
        .catch(error => {
            handleFileError(error);
        });
};
