<?php
/**
 * AI System Diagnostic Tool
 * File: /admin/ai-diagnostic.php
 * 
 * Run this to check if your AI system is properly configured
 * Access: http://localhost/sms2-capstone/admin/ai-diagnostic.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/ai.php';
require_once __DIR__ . '/../../../../includes/authentication.php';

// Only allow authenticated users to access this
requireAuth();

// Optional: Restrict to admins only
if (($_SESSION['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'superadmin') {
    die('⛔ Access denied. Only administrators can run diagnostics.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI System Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .diagnostic-card { background: white; border-radius: 12px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .diagnostic-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; font-weight: 600; }
        .diagnostic-body { padding: 20px; }
        .status-ok { color: #28a745; font-weight: 600; }
        .status-error { color: #dc3545; font-weight: 600; }
        .status-warning { color: #ffc107; font-weight: 600; }
        .status-info { color: #17a2b8; font-weight: 600; }
        .check-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .check-row:last-child { border-bottom: none; }
        .check-label { font-weight: 500; }
        .code-block { background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin: 10px 0; font-family: monospace; font-size: 12px; overflow-x: auto; }
        .btn-action { margin-top: 15px; }
    </style>
</head>
<body>

<div class="container-lg" style="max-width: 900px; margin-top: 30px;">
    <div class="diagnostic-card">
        <div class="diagnostic-header">
            <i class="fas fa-stethoscope me-2"></i> AI System Diagnostic Report
        </div>
        <div class="diagnostic-body">

            <!-- ========== CONFIGURATION CHECK ========== -->
            <h5 class="mt-4 mb-3">1️⃣ Configuration Status</h5>
            
            <div class="check-row">
                <span class="check-label">Config File Exists</span>
                <span class="<?= file_exists(__DIR__ . '/../../../../config/ai.php') ? 'status-ok' : 'status-error' ?>">
                    <?= file_exists(__DIR__ . '/../../../../config/ai.php') ? '✅ YES' : '❌ NO' ?>
                </span>
            </div>

            <div class="check-row">
                <span class="check-label">ai_config() Function</span>
                <span class="<?= function_exists('ai_config') ? 'status-ok' : 'status-error' ?>">
                    <?= function_exists('ai_config') ? '✅ YES' : '❌ NO' ?>
                </span>
            </div>

            <div class="check-row">
                <span class="check-label">API Key Status</span>
                <?php
                    $apiKey = ai_config('GEMINI_API_KEY', '');
                    $keyStatus = 'status-error';
                    $keyText = '❌ NOT SET';
                    
                    if (!empty($apiKey)) {
                        if ($apiKey === 'your-api-key-here' || $apiKey === 'paste-your-api-key-here') {
                            $keyStatus = 'status-warning';
                            $keyText = '⚠️ PLACEHOLDER';
                        } else if (strlen($apiKey) >= 20) {
                            $keyStatus = 'status-ok';
                            $keyText = '✅ SET (' . substr($apiKey, 0, 5) . '...' . substr($apiKey, -5) . ')';
                        } else {
                            $keyStatus = 'status-warning';
                            $keyText = '⚠️ INVALID FORMAT';
                        }
                    }
                ?>
                <span class="<?= $keyStatus ?>"><?= $keyText ?></span>
            </div>

            <div class="check-row">
                <span class="check-label">Model</span>
                <span class="status-info"><?= ai_config('GEMINI_MODEL', 'gemini-2.5-flash') ?></span>
            </div>

            <div class="check-row">
                <span class="check-label">Timeout (seconds)</span>
                <span class="status-info"><?= ai_config('GEMINI_TIMEOUT', 20) ?></span>
            </div>

            <!-- ========== VALIDATION CHECK ========== -->
            <h5 class="mt-4 mb-3">2️⃣ Validation Results</h5>
            
            <?php
                $validation = validate_gemini_config();
                $statusClass = $validation['ok'] ? 'status-ok' : 'status-error';
            ?>
            
            <div class="check-row">
                <span class="check-label">Gemini Configuration Valid</span>
                <span class="<?= $statusClass ?>">
                    <?= $validation['ok'] ? '✅ YES' : '❌ NO' ?>
                </span>
            </div>

            <div class="diagnostic-body bg-light">
                <strong>Message:</strong><br>
                <span class="<?= $statusClass ?>"><?= htmlspecialchars($validation['message']) ?></span>
            </div>

            <!-- ========== DATABASE CHECK ========== -->
            <h5 class="mt-4 mb-3">3️⃣ Database Tables</h5>
            
            <?php
                try {
                    $pdo = facultyDb();
                    
                    // Check ai_insights_cache table
                    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_insights_cache'");
                    $cacheTableExists = $stmt->rowCount() > 0;
                    
                    // Check ai_api_logs table
                    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_api_logs'");
                    $logsTableExists = $stmt->rowCount() > 0;
                    
                    // Get cache table stats
                    if ($cacheTableExists) {
                        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM ai_insights_cache");
                        $cacheCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                    } else {
                        $cacheCount = 0;
                    }
                } catch (Exception $e) {
                    $cacheTableExists = false;
                    $logsTableExists = false;
                    $cacheCount = 0;
                    $dbError = $e->getMessage();
                }
            ?>
            
            <div class="check-row">
                <span class="check-label">ai_insights_cache Table</span>
                <span class="<?= $cacheTableExists ? 'status-ok' : 'status-error' ?>">
                    <?= $cacheTableExists ? '✅ EXISTS' : '❌ MISSING' ?>
                </span>
            </div>

            <div class="check-row">
                <span class="check-label">ai_api_logs Table</span>
                <span class="<?= $logsTableExists ? 'status-ok' : 'status-warning' ?>">
                    <?= $logsTableExists ? '✅ EXISTS' : '⚠️ MISSING (optional)' ?>
                </span>
            </div>

            <?php if ($cacheTableExists): ?>
            <div class="check-row">
                <span class="check-label">Cached Insights</span>
                <span class="status-info"><?= $cacheCount ?> records</span>
            </div>
            <?php endif; ?>

            <!-- ========== API TEST ========== -->
            <h5 class="mt-4 mb-3">4️⃣ API Test</h5>
            
            <?php
                $apiWorking = false;
                $apiMessage = '';
                
                if ($validation['ok']) {
                    try {
                        require_once __DIR__ . '/../../../../modules/faculty/services/GptAiService.php';
                        $ai = new GptAiService();
                        
                        if ($ai->isConfigured()) {
                            $testPrompt = "Say hello in one sentence.";
                            $result = $ai->generate($testPrompt, 50);
                            
                            if ($result['ok']) {
                                $apiWorking = true;
                                $apiMessage = '✅ API is responding correctly';
                            } else {
                                $apiMessage = '❌ API returned error: ' . $result['error'];
                            }
                        } else {
                            $apiMessage = '❌ Service not configured';
                        }
                    } catch (Exception $e) {
                        $apiMessage = '❌ ' . $e->getMessage();
                    }
                } else {
                    $apiMessage = '⏭️ Skipped (configuration invalid)';
                }
            ?>
            
            <div class="check-row">
                <span class="check-label">API Connection Test</span>
                <span class="<?= $apiWorking ? 'status-ok' : 'status-warning' ?>">
                    <?= $apiMessage ?>
                </span>
            </div>

            <!-- ========== SUMMARY ========== -->
            <h5 class="mt-4 mb-3">📊 Summary</h5>
            
            <div class="alert <?= ($validation['ok'] && $cacheTableExists) ? 'alert-success' : 'alert-warning' ?>" role="alert">
                <strong>Status:</strong>
                <?php if ($validation['ok'] && $cacheTableExists): ?>
                    ✅ All systems are ready! Your AI implementation should be working.
                <?php elseif ($validation['ok']): ?>
                    ⚠️ Configuration is valid but database tables are missing. Run the SQL setup.
                <?php else: ?>
                    ❌ Configuration issues detected. See details above.
                <?php endif; ?>
            </div>

            <!-- ========== RECOMMENDATIONS ========== -->
            <h5 class="mt-4 mb-3">📝 Next Steps</h5>
            
            <div class="diagnostic-body bg-light">
                <?php if (!$validation['ok']): ?>
                    <div class="alert alert-danger">
                        <strong>⚠️ Action Required:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Check your API key in <code>config/ai.php</code></li>
                            <li>Ensure it's not a placeholder value</li>
                            <li>Get a real key from: <a href="https://aistudio.google.com/apikey" target="_blank">https://aistudio.google.com/apikey</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!$cacheTableExists): ?>
                    <div class="alert alert-warning">
                        <strong>📊 Database Setup Required:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Go to phpMyAdmin</li>
                            <li>Select your <code>faculty_db</code> database</li>
                            <li>Click <strong>SQL</strong> tab</li>
                            <li>Paste and run the SQL from <code>create-ai-tables.sql</code></li>
                        </ol>
                    </div>
                <?php endif; ?>
                
                <?php if ($validation['ok'] && $cacheTableExists): ?>
                    <div class="alert alert-success">
                        <strong>🎉 Ready to Use!</strong>
                        <ul class="mb-0 mt-2">
                            <li>Click "AI Department Summary" button</li>
                            <li>Watch the analysis generate in real-time</li>
                            <li>Results will be cached for 24 hours</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========== TECHNICAL INFO ========== -->
            <h5 class="mt-4 mb-3">🔧 Technical Details</h5>
            
            <div class="code-block">
<strong>System Configuration:</strong>
<?php
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "MySQL: " . (function_exists('mysqli_get_server_info') ? 'Available' : 'Not available') . "\n";
    echo "cURL: " . (function_exists('curl_exec') ? 'Available' : 'Not available') . "\n";
    echo "JSON: " . (function_exists('json_encode') ? 'Available' : 'Not available') . "\n";
?>
            </div>

            <div class="code-block">
<strong>AI Configuration Values:</strong>
<?php
    echo "GEMINI_API_KEY: " . (empty(ai_config('GEMINI_API_KEY')) ? '(not set)' : substr(ai_config('GEMINI_API_KEY'), 0, 5) . '...' . substr(ai_config('GEMINI_API_KEY'), -5)) . "\n";
    echo "GEMINI_MODEL: " . ai_config('GEMINI_MODEL', 'gemini-2.5-flash') . "\n";
    echo "GEMINI_TIMEOUT: " . ai_config('GEMINI_TIMEOUT', 20) . "s\n";
    echo "GEMINI_MAX_RETRIES: " . ai_config('GEMINI_MAX_RETRIES', 3) . "\n";
?>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <div class="text-center text-white mt-5">
        <p class="mb-0">AI System Diagnostic Report</p>
        <small>Generated: <?= date('Y-m-d H:i:s') ?></small>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>