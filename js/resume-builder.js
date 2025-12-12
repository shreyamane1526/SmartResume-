// Resume Builder JavaScript functionality
let currentStep = 1;
let jobRoles = [];
let selectedJobRole = null;
let experienceCount = 1;
let educationCount = 1;
let certificationCount = 1;
let languageCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Load job roles
    loadJobRoles();
    
    // Initialize form handlers
    initializeFormHandlers();
    
    // Initialize dynamic sections
    initializeDynamicSections();
    
    // Check for template parameter
    checkTemplateParameter();
});

/**
 * Load job roles from data file
 */
async function loadJobRoles() {
    try {
        const response = await fetch('data/job-roles.json');
        jobRoles = await response.json();
        renderJobRoles();
    } catch (error) {
        console.error('Error loading job roles:', error);
        showAlert('danger', 'Failed to load job roles. Please refresh the page.');
    }
}

/**
 * Render job role selection cards
 */
function renderJobRoles() {
    const container = document.getElementById('jobRoleSelector');
    
    container.innerHTML = jobRoles.map(role => `
        <div class="job-role-card" data-role="${role.id}" onclick="selectJobRole('${role.id}')">
            <div class="job-role-icon">
                <i class="${role.icon}"></i>
            </div>
            <h4>${role.name}</h4>
            <p>${role.description}</p>
            <div class="role-skills">
                ${role.keySkills.slice(0, 3).map(skill => `<span class="skill-tag">${skill}</span>`).join('')}
            </div>
        </div>
    `).join('');
}

/**
 * Select a job role
 */
function selectJobRole(roleId) {
    // Remove previous selection
    document.querySelectorAll('.job-role-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select new role
    const selectedCard = document.querySelector(`[data-role="${roleId}"]`);
    selectedCard.classList.add('selected');
    
    selectedJobRole = jobRoles.find(role => role.id === roleId);
    
    // Enable next button
    document.getElementById('nextStep1').disabled = false;
    
    // Set up role-specific form fields
    setupRoleSpecificFields();
}

/**
 * Setup role-specific form fields
 */
function setupRoleSpecificFields() {
    if (!selectedJobRole) return;
    
    // Pre-populate technical skills based on role
    const technicalSkillsField = document.getElementById('technicalSkills');
    if (technicalSkillsField && selectedJobRole.suggestedSkills) {
        technicalSkillsField.placeholder = selectedJobRole.suggestedSkills.join('\n');
    }
    
    // Add role-specific tips
    addRoleSpecificTips();
}

/**
 * Add role-specific tips to form sections
 */
function addRoleSpecificTips() {
    // Add tips based on selected role
    const tips = {
        'full-stack-developer': [
            'Highlight both frontend and backend technologies',
            'Include full project lifecycle experience',
            'Mention database management skills'
        ],
        'data-analyst': [
            'Emphasize data visualization tools',
            'Include statistical analysis experience',
            'Mention specific database technologies'
        ],
        'mobile-developer': [
            'Specify mobile platforms (iOS/Android)',
            'Include app store deployment experience',
            'Mention mobile-specific frameworks'
        ]
    };
    
    // Display tips for current role
    if (selectedJobRole && tips[selectedJobRole.id]) {
        // Implementation for showing tips
    }
}

/**
 * Navigate to next step
 */
function nextStep() {
    if (validateCurrentStep()) {
        currentStep++;
        updateStepDisplay();
        scrollToTop();
        
        // Generate preview when reaching step 5
        if (currentStep === 5) {
            forcePreviewUpdate();
        }
    }
}

/**
 * Navigate to previous step
 */
function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepDisplay();
        scrollToTop();
    }
}

/**
 * Update step display
 */
function updateStepDisplay() {
    // Update progress indicator
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Show current form step
    document.querySelectorAll('.form-step').forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
    
    // Update page title
    const stepTitles = ['Job Role', 'Personal Info', 'Experience', 'Skills', 'Preview'];
    document.title = `${stepTitles[currentStep - 1]} - Resume Builder - SmartResume`;
}

/**
 * Validate current step
 */
function validateCurrentStep() {
    const currentFormStep = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    const requiredFields = currentFormStep.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        showAlert('warning', 'Please fill in all required fields before proceeding.');
    }
    
    // Special validation for step 1 (job role selection)
    if (currentStep === 1 && !selectedJobRole) {
        showAlert('warning', 'Please select a job role to continue.');
        return false;
    }
    
    return isValid;
}

/**
 * Initialize form handlers
 */
function initializeFormHandlers() {
    // Next button for step 1
    document.getElementById('nextStep1').addEventListener('click', function() {
        if (selectedJobRole) {
            nextStep();
        }
    });
    
    // Form input validation
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
        }
        
        // Update preview when in step 5
        debouncePreviewUpdate();
    });
    
    // Form change validation  
    document.addEventListener('change', function(e) {
        // Update preview when in step 5
        debouncePreviewUpdate();
    });
}

/**
 * Debounce function to limit preview updates
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize dynamic sections (experience, education, etc.)
 */
function initializeDynamicSections() {
    // Add Experience
    document.getElementById('addExperience').addEventListener('click', function() {
        addExperienceItem();
    });
    
    // Add Education
    document.getElementById('addEducation').addEventListener('click', function() {
        addEducationItem();
    });
    
    // Add Certification
    document.getElementById('addCertification').addEventListener('click', function() {
        addCertificationItem();
    });
    
    // Add Language
    document.getElementById('addLanguage').addEventListener('click', function() {
        addLanguageItem();
    });
    
    // Handle current job checkbox
    document.addEventListener('change', function(e) {
        if (e.target.name && e.target.name.includes('[current]')) {
            const endDateField = e.target.closest('.experience-item').querySelector('input[name*="[endDate]"]');
            if (e.target.checked) {
                endDateField.value = '';
                endDateField.disabled = true;
                endDateField.removeAttribute('required');
            } else {
                endDateField.disabled = false;
                endDateField.setAttribute('required', 'required');
            }
        }
    });
}

/**
 * Add new experience item
 */
function addExperienceItem() {
    const container = document.getElementById('experienceContainer');
    const experienceHTML = `
        <div class="experience-item" data-index="${experienceCount}">
            <div class="item-header">
                <h5>Experience #${experienceCount + 1}</h5>
                <button type="button" class="btn btn-sm btn-outline-danger remove-experience" onclick="removeExperience(${experienceCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Job Title *</label>
                        <input type="text" class="form-control" name="experience[${experienceCount}][title]" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Company *</label>
                        <input type="text" class="form-control" name="experience[${experienceCount}][company]" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="month" class="form-control" name="experience[${experienceCount}][startDate]" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="month" class="form-control" name="experience[${experienceCount}][endDate]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="experience[${experienceCount}][current]">
                            <label class="form-check-label">Currently working here</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Job Description</label>
                <textarea class="form-control" name="experience[${experienceCount}][description]" rows="4" placeholder="Describe your key responsibilities and achievements..."></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', experienceHTML);
    experienceCount++;
    
    // Show remove buttons for all experience items
    document.querySelectorAll('.remove-experience').forEach(btn => {
        btn.style.display = 'block';
    });
}

/**
 * Remove experience item
 */
function removeExperience(index) {
    const experienceItem = document.querySelector(`.experience-item[data-index="${index}"]`);
    if (experienceItem) {
        experienceItem.remove();
        
        // Hide remove button if only one experience left
        const remainingItems = document.querySelectorAll('.experience-item');
        if (remainingItems.length === 1) {
            remainingItems[0].querySelector('.remove-experience').style.display = 'none';
        }
    }
}

/**
 * Add new education item
 */
function addEducationItem() {
    const container = document.getElementById('educationContainer');
    const educationHTML = `
        <div class="education-item" data-index="${educationCount}">
            <div class="item-header">
                <h5>Education #${educationCount + 1}</h5>
                <button type="button" class="btn btn-sm btn-outline-danger remove-education" onclick="removeEducation(${educationCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Degree/Certificate *</label>
                        <input type="text" class="form-control" name="education[${educationCount}][degree]" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Institution *</label>
                        <input type="text" class="form-control" name="education[${educationCount}][institution]" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Year of Graduation</label>
                        <input type="number" class="form-control" name="education[${educationCount}][year]" min="1950" max="2030">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">GPA/Grade</label>
                        <input type="text" class="form-control" name="education[${educationCount}][gpa]" placeholder="Optional">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="education[${educationCount}][location]" placeholder="City, Country">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', educationHTML);
    educationCount++;
    
    // Show remove buttons for all education items
    document.querySelectorAll('.remove-education').forEach(btn => {
        btn.style.display = 'block';
    });
}

/**
 * Remove education item
 */
function removeEducation(index) {
    const educationItem = document.querySelector(`.education-item[data-index="${index}"]`);
    if (educationItem) {
        educationItem.remove();
        
        // Hide remove button if only one education left
        const remainingItems = document.querySelectorAll('.education-item');
        if (remainingItems.length === 1) {
            remainingItems[0].querySelector('.remove-education').style.display = 'none';
        }
    }
}

/**
 * Add certification item
 */
function addCertificationItem() {
    const container = document.getElementById('certificationsContainer');
    const certificationHTML = `
        <div class="certification-item" data-index="${certificationCount}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Certification Name</label>
                        <input type="text" class="form-control" name="certifications[${certificationCount}][name]" placeholder="e.g., AWS Certified Developer">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Issuing Organization</label>
                        <input type="text" class="form-control" name="certifications[${certificationCount}][issuer]" placeholder="e.g., Amazon Web Services">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="certifications[${certificationCount}][year]" min="1990" max="2030">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', certificationHTML);
    certificationCount++;
}

/**
 * Add language item
 */
function addLanguageItem() {
    const container = document.getElementById('languagesContainer');
    const languageHTML = `
        <div class="language-item" data-index="${languageCount}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Language</label>
                        <input type="text" class="form-control" name="languages[${languageCount}][name]" placeholder="e.g., English">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Proficiency Level</label>
                        <select class="form-control" name="languages[${languageCount}][level]">
                            <option value="">Select Level</option>
                            <option value="native">Native</option>
                            <option value="fluent">Fluent</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="basic">Basic</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-language" onclick="removeLanguage(${languageCount})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', languageHTML);
    languageCount++;
    
    // Show remove buttons for all language items
    document.querySelectorAll('.remove-language').forEach(btn => {
        btn.style.display = 'block';
    });
}

/**
 * Remove language item
 */
function removeLanguage(index) {
    const languageItem = document.querySelector(`.language-item[data-index="${index}"]`);
    if (languageItem) {
        languageItem.remove();
        
        // Hide remove button if only one language left
        const remainingItems = document.querySelectorAll('.language-item');
        if (remainingItems.length === 1) {
            remainingItems[0].querySelector('.remove-language').style.display = 'none';
        }
    }
}

/**
 * Update resume preview
 */
function updatePreview() {
    if (currentStep !== 5) return;
    
    const formData = collectFormData();
    generatePreview(formData);
}

/**
 * Force preview update (called when reaching step 5)
 */
function forcePreviewUpdate() {
    const formData = collectFormData();
    
    // Update template name display
    const templateNameEl = document.getElementById('previewTemplateName');
    if (templateNameEl && formData.jobRole) {
        templateNameEl.textContent = formData.jobRole.name || 'Professional';
    }
    
    generatePreview(formData);
}

/**
 * Real-time preview update (called on form changes)
 */
function debouncePreviewUpdate() {
    clearTimeout(window.previewUpdateTimeout);
    window.previewUpdateTimeout = setTimeout(() => {
        if (currentStep === 5) {
            updatePreview();
        }
    }, 500);
}

/**
 * Collect all form data
 */
function collectFormData() {
    const form = document.getElementById('resumeBuilderForm');
    const formData = new FormData(form);
    const data = {};
    
    // Basic information
    data.jobRole = selectedJobRole;
    data.personalInfo = {
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        linkedin: formData.get('linkedin'),
        website: formData.get('website'),
        objective: formData.get('objective')
    };
    
    // Experience
    data.experience = [];
    document.querySelectorAll('.experience-item').forEach((item, index) => {
        const exp = {
            title: item.querySelector(`input[name="experience[${index}][title]"]`)?.value || '',
            company: item.querySelector(`input[name="experience[${index}][company]"]`)?.value || '',
            startDate: item.querySelector(`input[name="experience[${index}][startDate]"]`)?.value || '',
            endDate: item.querySelector(`input[name="experience[${index}][endDate]"]`)?.value || '',
            current: item.querySelector(`input[name="experience[${index}][current]"]`)?.checked || false,
            description: item.querySelector(`textarea[name="experience[${index}][description]"]`)?.value || ''
        };
        if (exp.title && exp.company) {
            data.experience.push(exp);
        }
    });
    
    // Education
    data.education = [];
    document.querySelectorAll('.education-item').forEach((item, index) => {
        const edu = {
            degree: item.querySelector(`input[name="education[${index}][degree]"]`)?.value || '',
            institution: item.querySelector(`input[name="education[${index}][institution]"]`)?.value || '',
            year: item.querySelector(`input[name="education[${index}][year]"]`)?.value || '',
            gpa: item.querySelector(`input[name="education[${index}][gpa]"]`)?.value || '',
            location: item.querySelector(`input[name="education[${index}][location]"]`)?.value || ''
        };
        if (edu.degree && edu.institution) {
            data.education.push(edu);
        }
    });
    
    // Skills
    data.skills = {
        technical: formData.get('technicalSkills') ? formData.get('technicalSkills').split('\n').filter(skill => skill.trim()) : [],
        soft: formData.get('softSkills') ? formData.get('softSkills').split('\n').filter(skill => skill.trim()) : []
    };
    
    // Certifications
    data.certifications = [];
    document.querySelectorAll('.certification-item').forEach((item, index) => {
        const cert = {
            name: item.querySelector(`input[name="certifications[${index}][name]"]`)?.value || '',
            issuer: item.querySelector(`input[name="certifications[${index}][issuer]"]`)?.value || '',
            year: item.querySelector(`input[name="certifications[${index}][year]"]`)?.value || ''
        };
        if (cert.name) {
            data.certifications.push(cert);
        }
    });
    
    // Languages
    data.languages = [];
    document.querySelectorAll('.language-item').forEach((item, index) => {
        const lang = {
            name: item.querySelector(`input[name="languages[${index}][name]"]`)?.value || '',
            level: item.querySelector(`select[name="languages[${index}][level]"]`)?.value || ''
        };
        if (lang.name) {
            data.languages.push(lang);
        }
    });
    
    return data;
}

/**
 * Generate preview HTML
 */
function generatePreview(data) {
    const previewContainer = document.getElementById('resumePreview');
    
    if (!previewContainer) {
        console.error('Resume preview container not found');
        return;
    }

    // Show loading state
    previewContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Generating preview...</p>
        </div>
    `;
    
    // Ensure we have valid job role data
    if (!data.jobRole && selectedJobRole) {
        data.jobRole = selectedJobRole;
    }
    
    // Load the appropriate template
    const templateName = data.jobRole?.template || 'developer';
    loadTemplate(templateName, data)
        .then(html => {
            previewContainer.innerHTML = `
                <div class="resume-template-wrapper">
                    ${html}
                </div>
            `;
            
            // Add print styles for better preview
            addPreviewStyles();
        })
        .catch(error => {
            console.error('Error generating preview:', error);
            previewContainer.innerHTML = `
                <div class="resume-template-wrapper">
                    ${generateDefaultTemplate(data)}
                </div>
            `;
            addPreviewStyles();
        });
}

/**
 * Add styles for better preview display
 */
function addPreviewStyles() {
    if (!document.getElementById('preview-styles')) {
        const style = document.createElement('style');
        style.id = 'preview-styles';
        style.textContent = `
            .resume-template-wrapper {
                background: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                margin: 0 auto;
                max-width: 800px;
                min-height: 1000px;
                position: relative;
                transform-origin: top center;
                zoom: 0.8;
            }
            
            @media (max-width: 768px) {
                .resume-template-wrapper {
                    zoom: 0.6;
                }
            }
            
            .resume-preview-container {
                overflow-x: auto;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
                min-height: 600px;
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Load template and populate with data
 */
async function loadTemplate(templateName, data) {
    try {
        const response = await fetch(`templates/${templateName}-template.html`);
        if (!response.ok) {
            throw new Error(`Template not found: ${templateName}`);
        }
        let template = await response.text();
        
        // Process the template with our custom template engine
        template = processTemplate(template, data);
        
        return template;
    } catch (error) {
        console.error('Error loading template:', error);
        return generateDefaultTemplate(data);
    }
}

/**
 * Process template with custom template engine
 */
function processTemplate(template, data) {
    // Replace simple placeholders
    template = template.replace(/\{\{([^{}]+)\}\}/g, (match, path) => {
        const value = getNestedValue(data, path.trim());
        return value !== undefined && value !== null ? value : '';
    });
    
    // Handle conditional blocks
    template = processConditionals(template, data);
    
    // Handle loops
    template = processLoops(template, data);
    
    return template;
}

/**
 * Process conditional blocks
 */
function processConditionals(template, data) {
    // Handle {{#if condition}} ... {{/if}} blocks
    template = template.replace(/\{\{#if\s+([^}]+)\}\}([\s\S]*?)\{\{\/if\}\}/g, (match, condition, content) => {
        const value = getNestedValue(data, condition.trim());
        if (value && (Array.isArray(value) ? value.length > 0 : true)) {
            return processTemplate(content, data);
        }
        return '';
    });
    
    return template;
}

/**
 * Process loop blocks
 */
function processLoops(template, data) {
    // Handle {{#each array}} ... {{/each}} blocks
    template = template.replace(/\{\{#each\s+([^}]+)\}\}([\s\S]*?)\{\{\/each\}\}/g, (match, arrayPath, content) => {
        const array = getNestedValue(data, arrayPath.trim());
        if (!Array.isArray(array) || array.length === 0) {
            return '';
        }
        
        return array.map(item => {
            let itemContent = content;
            // Replace {{this}} with the current item (for simple arrays)
            itemContent = itemContent.replace(/\{\{this\}\}/g, item);
            
            // Replace object properties
            if (typeof item === 'object') {
                Object.keys(item).forEach(key => {
                    const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
                    itemContent = itemContent.replace(regex, item[key] || '');
                });
                
                // Handle nested conditionals within loops
                itemContent = itemContent.replace(/\{\{#if\s+([^}]+)\}\}([\s\S]*?)\{\{\/if\}\}/g, (condMatch, condition, condContent) => {
                    const value = item[condition.trim()];
                    return value ? condContent : '';
                });
            }
            
            return itemContent;
        }).join('');
    });
    
    return template;
}

/**
 * Generate default template if specific template fails
 */
function generateDefaultTemplate(data) {
    const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
    };

    return `
        <div class="resume-template" style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 800px; margin: 0 auto; background: white;">
            <!-- Header Section -->
            <div style="background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%); color: white; padding: 40px 30px; text-align: center;">
                <h1 style="margin: 0 0 10px 0; font-size: 32px; font-weight: bold;">
                    ${data.personalInfo.firstName || 'Your'} ${data.personalInfo.lastName || 'Name'}
                </h1>
                <p style="margin: 0 0 20px 0; font-size: 18px; opacity: 0.9;">
                    ${data.jobRole?.name || 'Professional'}
                </p>
                <div style="font-size: 14px; display: flex; justify-content: center; flex-wrap: wrap; gap: 20px;">
                    ${data.personalInfo.email ? `<span>📧 ${data.personalInfo.email}</span>` : ''}
                    ${data.personalInfo.phone ? `<span>📱 ${data.personalInfo.phone}</span>` : ''}
                    ${data.personalInfo.address ? `<span>📍 ${data.personalInfo.address}</span>` : ''}
                    ${data.personalInfo.linkedin ? `<span>💼 LinkedIn</span>` : ''}
                    ${data.personalInfo.website ? `<span>🌐 Portfolio</span>` : ''}
                </div>
            </div>
            
            <div style="display: flex; gap: 30px; padding: 30px;">
                <!-- Left Column -->
                <div style="flex: 2;">
                    ${data.personalInfo.objective ? `
                    <div style="margin-bottom: 30px;">
                        <h2 style="color: #1a237e; font-size: 20px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Professional Objective</h2>
                        <p style="text-align: justify; margin: 0;">${data.personalInfo.objective}</p>
                    </div>
                    ` : ''}
                    
                    ${data.experience.length ? `
                    <div style="margin-bottom: 30px;">
                        <h2 style="color: #1a237e; font-size: 20px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Work Experience</h2>
                        ${data.experience.map(exp => `
                            <div style="margin-bottom: 25px;">
                                <h3 style="color: #1a237e; font-size: 16px; margin: 0 0 5px 0;">${exp.title}</h3>
                                <p style="font-style: italic; color: #666; margin: 0 0 5px 0; font-size: 14px;">${exp.company}</p>
                                <p style="color: #888; font-size: 12px; margin: 0 0 10px 0;">
                                    ${formatDate(exp.startDate)} - ${exp.current ? 'Present' : formatDate(exp.endDate)}
                                </p>
                                ${exp.description ? `<p style="margin: 0; font-size: 13px; text-align: justify;">${exp.description}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                    
                    ${data.education.length ? `
                    <div style="margin-bottom: 30px;">
                        <h2 style="color: #1a237e; font-size: 20px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Education</h2>
                        ${data.education.map(edu => `
                            <div style="margin-bottom: 15px;">
                                <h3 style="color: #1a237e; font-size: 15px; margin: 0 0 5px 0;">${edu.degree}</h3>
                                <p style="color: #666; margin: 0; font-size: 13px;">
                                    ${edu.institution} ${edu.year ? `(${edu.year})` : ''}
                                    ${edu.gpa ? ` - GPA: ${edu.gpa}` : ''}
                                </p>
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                </div>
                
                <!-- Right Column -->
                <div style="flex: 1;">
                    ${data.skills.technical.length ? `
                    <div style="margin-bottom: 25px;">
                        <h2 style="color: #1a237e; font-size: 18px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Technical Skills</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            ${data.skills.technical.map(skill => `
                                <span style="background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%); color: white; padding: 6px 12px; border-radius: 15px; font-size: 11px; display: inline-block;">${skill}</span>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.skills.soft.length ? `
                    <div style="margin-bottom: 25px;">
                        <h2 style="color: #1a237e; font-size: 18px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Soft Skills</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            ${data.skills.soft.map(skill => `
                                <span style="background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%); color: white; padding: 6px 12px; border-radius: 15px; font-size: 11px; display: inline-block;">${skill}</span>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.certifications.length ? `
                    <div style="margin-bottom: 25px;">
                        <h2 style="color: #1a237e; font-size: 18px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Certifications</h2>
                        ${data.certifications.map(cert => `
                            <div style="margin-bottom: 12px;">
                                <h3 style="color: #1a237e; font-size: 14px; margin: 0 0 3px 0;">${cert.name}</h3>
                                ${cert.issuer ? `<p style="color: #666; margin: 0; font-size: 12px;">${cert.issuer} ${cert.year ? `(${cert.year})` : ''}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                    
                    ${data.languages.length ? `
                    <div style="margin-bottom: 25px;">
                        <h2 style="color: #1a237e; font-size: 18px; border-bottom: 2px solid #1a237e; padding-bottom: 8px; margin-bottom: 15px;">Languages</h2>
                        ${data.languages.map(lang => `
                            <div style="margin-bottom: 8px;">
                                <span style="font-weight: bold; font-size: 13px;">${lang.name}</span>
                                ${lang.level ? `<span style="color: #666; font-size: 12px;"> - ${lang.level}</span>` : ''}
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

/**
 * Handle resume download
 */
document.addEventListener('DOMContentLoaded', function() {
    const downloadBtn = document.getElementById('downloadResume');
    const emailBtn = document.getElementById('emailResume');
    
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            downloadResume();
        });
    }
    
    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            emailResume();
        });
    }
});

/**
 * Download resume as PDF
 */
function downloadResume() {
    const formData = collectFormData();
    const downloadBtn = document.getElementById('downloadResume');
    const originalText = downloadBtn.innerHTML;
    
    // Show loading state
    downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...';
    downloadBtn.disabled = true;
    
    // Submit to PHP for PDF generation
    fetch('php/generate-resume.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Failed to generate PDF');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${formData.personalInfo.firstName}_${formData.personalInfo.lastName}_Resume.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showAlert('success', 'Resume downloaded successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to generate PDF. Please try again.');
    })
    .finally(() => {
        downloadBtn.innerHTML = originalText;
        downloadBtn.disabled = false;
    });
}

/**
 * Email resume
 */
function emailResume() {
    const email = document.getElementById('email').value;
    if (!email) {
        showAlert('warning', 'Please ensure you have entered your email address.');
        return;
    }
    
    const formData = collectFormData();
    const emailBtn = document.getElementById('emailResume');
    const originalText = emailBtn.innerHTML;
    
    // Show loading state
    emailBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    emailBtn.disabled = true;
    
    // Submit to PHP for email
    fetch('php/generate-resume.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({...formData, action: 'email'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Resume has been sent to your email!');
        } else {
            showAlert('danger', data.message || 'Failed to send email. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to send email. Please try again.');
    })
    .finally(() => {
        emailBtn.innerHTML = originalText;
        emailBtn.disabled = false;
    });
}

/**
 * Check for template parameter in URL
 */
function checkTemplateParameter() {
    const urlParams = new URLSearchParams(window.location.search);
    const template = urlParams.get('template');
    
    if (template) {
        // Auto-select the role based on template
        const roleMapping = {
            'developer-modern': 'full-stack-developer',
            'backend-technical': 'backend-developer',
            'analyst-focus': 'data-analyst',
            'bi-analyst': 'business-analyst',
            'mobile-cross': 'mobile-developer',
            'ios-native': 'ios-developer'
        };
        
        const roleId = roleMapping[template];
        if (roleId) {
            setTimeout(() => {
                selectJobRole(roleId);
            }, 500);
        }
    }
}

/**
 * Utility functions
 */
function getNestedValue(obj, key) {
    return key.split('.').reduce((o, k) => o && o[k], obj);
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString + '-01');
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Helper functions for template population (kept for backward compatibility)
function populateExperience(template, experience) {
    // This is now handled by the processLoops function
    return template;
}

function populateEducation(template, education) {
    // This is now handled by the processLoops function
    return template;
}

function populateSkills(template, skills) {
    // This is now handled by the processLoops function
    return template;
}

function populateCertifications(template, certifications) {
    // This is now handled by the processLoops function
    return template;
}

function populateLanguages(template, languages) {
    // This is now handled by the processLoops function
    return template;
}

/**
 * Debounce function to limit preview updates
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
