<?php
// Import database schema
require_once 'admin/php/database-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Connected to database\n";
    
    // Read SQL file
    $sqlFile = 'admin/database/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
                echo "⚠ Skipped existing: " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    $pdo->commit();
    echo "\n✅ Database schema imported successfully!\n";
    
    // Test with some sample data
    echo "\nTesting database with sample data...\n";
    
    // Insert a test resume record
    $stmt = $pdo->prepare("INSERT INTO resume_history (user_name, user_email, job_role, template_used, action_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test User', 'test@example.com', 'Software Developer', 'developer-template', 'created']);
    echo "✓ Test resume record created\n";
    
    // Check if tables exist
    $tables = ['admin_users', 'resume_history', 'contact_submissions', 'website_analytics'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table' exists with $count records\n";
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
