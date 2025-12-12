<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Check if file was uploaded
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    // Get target role
    $targetRole = $_POST['targetRole'] ?? '';
    if (empty($targetRole)) {
        throw new Exception('Target role is required');
    }

    $uploadedFile = $_FILES['resume'];
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($uploadedFile['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload PDF, DOC, or DOCX files only.');
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit');
    }

    // Extract text from uploaded file
    $resumeText = extractTextFromFile($uploadedFile);
    
    if (empty($resumeText)) {
        throw new Exception('Could not extract text from the uploaded file. Please ensure the file contains readable text.');
    }

    // Load analysis criteria for the target role
    $analysisCriteria = loadAnalysisCriteria($targetRole);
    
    // Perform analysis
    $analysis = analyzeResumeContent($resumeText, $analysisCriteria, $targetRole);
    
    // Return results
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);

} catch (Exception $e) {
    // Log error for debugging
    error_log('Resume analysis error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
if (!file_exists($uploadedFile['tmp_name'])) {
    throw new Exception('Uploaded file not found on server.');
}


/**
 * Extract text from uploaded file
 */
function extractTextFromFile($file) {
    $tempFile = $file['tmp_name'];
    $fileType = $file['type'];
    
    try {
        switch ($fileType) {
            case 'application/pdf':
                return extractTextFromPDF($tempFile);
            
            case 'application/msword':
                return extractTextFromDoc($tempFile);
            
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return extractTextFromDocx($tempFile);
            
            default:
                throw new Exception('Unsupported file type');
        }
    } catch (Exception $e) {
        error_log('Text extraction error: ' . $e->getMessage());
        throw new Exception('Failed to extract text from file: ' . $e->getMessage());
    }
}

/**
 * Extract text from PDF file
 */
function extractTextFromPDF($filePath) {
    // Simple PDF text extraction (you might want to use a more robust library)
    $text = '';
    
    // Try using pdftotext if available
    if (shell_exec('which pdftotext')) {
        $output = shell_exec("pdftotext '$filePath' -");
        if ($output !== null) {
            return $output;
        }
    }
    
    // Fallback: try to read PDF content directly (limited effectiveness)
    $content = file_get_contents($filePath);
    if (preg_match_all('/\(([^)]+)\)/', $content, $matches)) {
        $text = implode(' ', $matches[1]);
    }
    
    // Another approach: look for text between specific markers
    if (empty($text)) {
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            $text = implode(' ', $matches[1]);
            $text = preg_replace('/\[[^\]]*\]/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);
        }
    }
    
    return trim($text);
}

/**
 * Extract text from DOC file
 */
function extractTextFromDoc($filePath) {
    // Try using antiword if available
    if (shell_exec('which antiword')) {
        $output = shell_exec("antiword '$filePath'");
        if ($output !== null) {
            return $output;
        }
    }
    
    // Fallback: basic extraction (limited effectiveness)
    $content = file_get_contents($filePath);
    $text = '';
    
    // Remove binary characters and extract readable text
    $content = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $content);
    $text = preg_replace('/\s+/', ' ', $content);
    
    return trim($text);
}

/**
 * Extract text from DOCX file
 */
function extractTextFromDocx($filePath) {
    $text = '';
    
    try {
        $zip = new ZipArchive();
        
        if ($zip->open($filePath) === TRUE) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($xml !== false) {
                // Parse XML and extract text
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                
                // Remove all XML tags and get text content
                $text = strip_tags($dom->saveHTML());
                $text = html_entity_decode($text);
                $text = preg_replace('/\s+/', ' ', $text);
            }
        }
    } catch (Exception $e) {
        error_log('DOCX extraction error: ' . $e->getMessage());
    }
    
    return trim($text);
}

/**
 * Load analysis criteria for target role
 */
function loadAnalysisCriteria($targetRole) {
    $criteriaFile = '../data/analysis-criteria.json';
    
    if (!file_exists($criteriaFile)) {
        // Return default criteria if file doesn't exist
        return getDefaultAnalysisCriteria();
    }
    
    $criteriaData = json_decode(file_get_contents($criteriaFile), true);
    
    return $criteriaData[$targetRole] ?? getDefaultAnalysisCriteria();
}

/**
 * Get default analysis criteria
 */
function getDefaultAnalysisCriteria() {
    return [
        'requiredKeywords' => ['experience', 'skills', 'education', 'project', 'responsibility'],
        'preferredKeywords' => ['achievement', 'leadership', 'team', 'development', 'management'],
        'technicalKeywords' => ['software', 'technology', 'programming', 'system', 'database'],
        'scoringWeights' => [
            'keywords' => 30,
            'structure' => 25,
            'experience' => 20,
            'education' => 15,
            'skills' => 10
        ]
    ];
}

/**
 * Analyze resume content
 */
function analyzeResumeContent($text, $criteria, $targetRole) {
    $text = strtolower($text);
    $wordCount = str_word_count($text);
    
    // Initialize analysis results
    $analysis = [
        'overallScore' => 0,
        'strengths' => [],
        'improvements' => [],
        'suggestions' => [],
        'keywords' => [
            'found' => 0,
            'missing' => []
        ]
    ];
    
    // Keyword analysis
    $keywordAnalysis = analyzeKeywords($text, $criteria);
    $analysis['keywords'] = $keywordAnalysis;
    
    // Structure analysis
    $structureScore = analyzeStructure($text);
    
    // Content analysis
    $contentScore = analyzeContent($text, $wordCount);
    
    // Experience analysis
    $experienceScore = analyzeExperience($text);
    
    // Education analysis
    $educationScore = analyzeEducation($text);
    
    // Skills analysis
    $skillsScore = analyzeSkills($text, $criteria);
    
    // Calculate overall score
    $weights = $criteria['scoringWeights'];
    $analysis['overallScore'] = round(
        ($keywordAnalysis['score'] * $weights['keywords'] / 100) +
        ($structureScore * $weights['structure'] / 100) +
        ($experienceScore * $weights['experience'] / 100) +
        ($educationScore * $weights['education'] / 100) +
        ($skillsScore * $weights['skills'] / 100)
    );
    
    // Generate strengths, improvements, and suggestions
    $analysis['strengths'] = generateStrengths($text, [
        'keywords' => $keywordAnalysis['score'],
        'structure' => $structureScore,
        'experience' => $experienceScore,
        'education' => $educationScore,
        'skills' => $skillsScore
    ]);
    
    $analysis['improvements'] = generateImprovements($text, [
        'keywords' => $keywordAnalysis['score'],
        'structure' => $structureScore,
        'experience' => $experienceScore,
        'education' => $educationScore,
        'skills' => $skillsScore
    ], $keywordAnalysis['missing']);
    
    $analysis['suggestions'] = generateSuggestions($targetRole, $analysis['overallScore'], $keywordAnalysis);
    
    return $analysis;
}

/**
 * Analyze keywords in resume
 */
function analyzeKeywords($text, $criteria) {
    $allKeywords = array_merge(
        $criteria['requiredKeywords'],
        $criteria['preferredKeywords'],
        $criteria['technicalKeywords']
    );
    
    $foundKeywords = 0;
    $missingKeywords = [];
    
    foreach ($allKeywords as $keyword) {
        if (strpos($text, strtolower($keyword)) !== false) {
            $foundKeywords++;
        } else {
            $missingKeywords[] = $keyword;
        }
    }
    
    $score = min(100, ($foundKeywords / count($allKeywords)) * 100);
    
    return [
        'score' => round($score),
        'found' => $foundKeywords,
        'missing' => array_slice($missingKeywords, 0, 10) // Limit to top 10 missing
    ];
}

/**
 * Analyze resume structure
 */
function analyzeStructure($text) {
    $score = 0;
    $maxScore = 100;
    
    // Check for common resume sections
    $sections = [
        'experience' => ['experience', 'work history', 'employment', 'career'],
        'education' => ['education', 'academic', 'degree', 'university', 'college'],
        'skills' => ['skills', 'competencies', 'abilities', 'proficiencies'],
        'contact' => ['email', 'phone', 'address', 'contact'],
        'summary' => ['summary', 'objective', 'profile', 'about']
    ];
    
    $foundSections = 0;
    foreach ($sections as $sectionName => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $foundSections++;
                break;
            }
        }
    }
    
    $score = ($foundSections / count($sections)) * $maxScore;
    
    // Check for proper length
    $wordCount = str_word_count($text);
    if ($wordCount >= 200 && $wordCount <= 800) {
        $score = min($maxScore, $score + 20);
    }
    
    return round($score);
}

/**
 * Analyze content quality
 */
function analyzeContent($text, $wordCount) {
    $score = 0;
    
    // Length check
    if ($wordCount >= 200 && $wordCount <= 800) {
        $score += 40;
    } elseif ($wordCount >= 100) {
        $score += 20;
    }
    
    // Action words check
    $actionWords = ['achieved', 'developed', 'managed', 'led', 'created', 'implemented', 'improved', 'increased', 'reduced', 'designed'];
    $actionWordCount = 0;
    foreach ($actionWords as $word) {
        if (strpos($text, $word) !== false) {
            $actionWordCount++;
        }
    }
    $score += min(30, $actionWordCount * 5);
    
    // Quantifiable achievements
    if (preg_match('/\d+%/', $text)) {
        $score += 15;
    }
    if (preg_match('/\$\d+/', $text)) {
        $score += 15;
    }
    
    return min(100, $score);
}

/**
 * Analyze experience section
 */
function analyzeExperience($text) {
    $score = 0;
    
    // Check for experience indicators
    $experienceIndicators = ['years', 'months', 'experience', 'worked', 'position', 'role', 'job', 'company'];
    $foundIndicators = 0;
    
    foreach ($experienceIndicators as $indicator) {
        if (strpos($text, $indicator) !== false) {
            $foundIndicators++;
        }
    }
    
    $score = min(100, ($foundIndicators / count($experienceIndicators)) * 100);
    
    // Check for company names (capitalized words)
    if (preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $text, $matches)) {
        $score = min(100, $score + (count($matches[0]) * 5));
    }
    
    return round($score);
}

/**
 * Analyze education section
 */
function analyzeEducation($text) {
    $score = 0;
    
    // Check for education keywords
    $educationKeywords = ['degree', 'bachelor', 'master', 'phd', 'diploma', 'certificate', 'university', 'college', 'school'];
    $foundKeywords = 0;
    
    foreach ($educationKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $foundKeywords++;
        }
    }
    
    $score = ($foundKeywords / count($educationKeywords)) * 100;
    
    // Check for graduation years
    if (preg_match('/20\d{2}/', $text)) {
        $score = min(100, $score + 20);
    }
    
    return round($score);
}

/**
 * Analyze skills section
 */
function analyzeSkills($text, $criteria) {
    $score = 0;
    
    // Check for technical skills
    $foundTechnicalSkills = 0;
    foreach ($criteria['technicalKeywords'] as $skill) {
        if (strpos($text, strtolower($skill)) !== false) {
            $foundTechnicalSkills++;
        }
    }
    
    $score = ($foundTechnicalSkills / count($criteria['technicalKeywords'])) * 100;
    
    // Check for skill section existence
    if (strpos($text, 'skills') !== false || strpos($text, 'competencies') !== false) {
        $score = min(100, $score + 25);
    }
    
    return round($score);
}

/**
 * Generate strengths based on analysis
 */
function generateStrengths($text, $scores) {
    $strengths = [];
    
    if ($scores['keywords'] >= 70) {
        $strengths[] = 'Good use of relevant keywords for your target role';
    }
    
    if ($scores['structure'] >= 80) {
        $strengths[] = 'Well-structured resume with clear sections';
    }
    
    if ($scores['experience'] >= 75) {
        $strengths[] = 'Strong work experience presentation';
    }
    
    if ($scores['education'] >= 70) {
        $strengths[] = 'Educational background is well documented';
    }
    
    if ($scores['skills'] >= 75) {
        $strengths[] = 'Technical skills are effectively highlighted';
    }
    
    if (strpos($text, '%') !== false || strpos($text, '$') !== false) {
        $strengths[] = 'Includes quantifiable achievements';
    }
    
    if (empty($strengths)) {
        $strengths[] = 'Resume contains basic required information';
    }
    
    return $strengths;
}

/**
 * Generate improvements based on analysis
 */
function generateImprovements($text, $scores, $missingKeywords) {
    $improvements = [];
    
    if ($scores['keywords'] < 60) {
        $improvements[] = 'Include more industry-relevant keywords';
    }
    
    if ($scores['structure'] < 70) {
        $improvements[] = 'Improve resume structure with clearer section headings';
    }
    
    if ($scores['experience'] < 60) {
        $improvements[] = 'Provide more detailed work experience descriptions';
    }
    
    if ($scores['skills'] < 60) {
        $improvements[] = 'Add more technical skills relevant to your field';
    }
    
    if (!empty($missingKeywords)) {
        $improvements[] = 'Consider adding these missing keywords: ' . implode(', ', array_slice($missingKeywords, 0, 5));
    }
    
    if (str_word_count($text) < 200) {
        $improvements[] = 'Expand content - resume appears too brief';
    }
    
    if (str_word_count($text) > 800) {
        $improvements[] = 'Consider condensing content for better readability';
    }
    
    return $improvements;
}

/**
 * Generate suggestions based on target role and score
 */
function generateSuggestions($targetRole, $overallScore, $keywordAnalysis) {
    $suggestions = [];
    
    // Role-specific suggestions
    $roleSuggestions = [
        'full-stack-developer' => [
            'Highlight both frontend and backend technologies',
            'Include specific programming languages and frameworks',
            'Mention full-stack project examples'
        ],
        'data-analyst' => [
            'Emphasize data visualization tools (Tableau, Power BI)',
            'Include statistical analysis experience',
            'Mention specific database technologies (SQL, NoSQL)'
        ],
        'mobile-developer' => [
            'Specify mobile platforms (iOS, Android, React Native)',
            'Include app store deployment experience',
            'Mention mobile development frameworks'
        ]
    ];
    
    if (isset($roleSuggestions[$targetRole])) {
        $suggestions = array_merge($suggestions, $roleSuggestions[$targetRole]);
    }
    
    // Score-based suggestions
    if ($overallScore < 60) {
        $suggestions[] = 'Consider using a professional resume template';
        $suggestions[] = 'Add more specific examples of your achievements';
    }
    
    if ($overallScore >= 80) {
        $suggestions[] = 'Your resume is strong - consider tailoring it for specific job postings';
    }
    
    // Keyword suggestions
    if ($keywordAnalysis['found'] < 5) {
        $suggestions[] = 'Research job postings in your field to identify missing keywords';
    }
    
    return array_unique($suggestions);
}
?>
