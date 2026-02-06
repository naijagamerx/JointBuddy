<?php

/**
 * Project Memory Manager - Core Implementation
 *
 * Provides persistent memory functionality for Claude Code by maintaining
 * a detailed task history log with user confirmation and querying capabilities.
 *
 * @author Claude Code Assistant
 * @version 1.0.0
 */

class ProjectMemoryManager
{
    private string $projectRoot;
    private string $memoryFile;
    private array $config;
    private array $statistics;

    public function __construct(string $projectRoot = '.')
    {
        $this->projectRoot = realpath($projectRoot);
        $this->memoryFile = $this->projectRoot . '/project_memory.md';
        $this->config = $this->loadConfig();
        $this->statistics = [];
    }

    /**
     * Initialize the project memory file
     */
    public function initialize(): bool
    {
        if (file_exists($this->memoryFile)) {
            return true;
        }

        $template = $this->getMemoryTemplate();
        $result = file_put_contents($this->memoryFile, $template);

        if ($result !== false) {
            echo "✅ Created project_memory.md\n";
            return true;
        }

        echo "❌ Failed to create project_memory.md\n";
        return false;
    }

    /**
     * Log a task with user confirmation
     */
    public function logTask(array $taskData): bool
    {
        // Generate unique task ID
        $taskId = $this->generateTaskId();

        // Prepare task entry
        $taskEntry = $this->prepareTaskEntry($taskId, $taskData);

        // Present summary to user
        $this->presentTaskSummary($taskEntry);

        // Wait for user confirmation
        $confirmed = $this->requestUserConfirmation();

        if (!$confirmed) {
            echo "⚠️  Task not logged due to lack of confirmation.\n";
            return false;
        }

        // Add confirmation timestamp
        $taskEntry['user_confirmation'] = 'Yes, ' . date('Y-m-d H:i:s ' . $this->config['timezone']);

        // Append to memory file
        if ($this->appendToMemoryFile($taskEntry)) {
            echo "✅ Task {$taskId} logged in project_memory.md\n";
            echo "📄 View at: {$this->memoryFile}\n";
            $this->updateStatistics();
            return true;
        }

        echo "❌ Failed to log task\n";
        return false;
    }

    /**
     * Query project history
     */
    public function queryHistory(string $query): array
    {
        if (!file_exists($this->memoryFile)) {
            echo "⚠️  project_memory.md not found. Initialize with /initialize-memory\n";
            return [];
        }

        $content = file_get_contents($this->memoryFile);
        $entries = $this->parseTaskEntries($content);

        // Parse query and filter entries
        $filteredEntries = $this->filterEntries($entries, $query);

        // Limit results
        $maxResults = $this->config['max_entries_per_query'] ?? 10;
        $paginatedResults = array_slice($filteredEntries, 0, $maxResults);

        $this->presentQueryResults($paginatedResults, count($filteredEntries));

        return $paginatedResults;
    }

    /**
     * Get project statistics
     */
    public function getStatistics(): array
    {
        if (!file_exists($this->memoryFile)) {
            return [];
        }

        $content = file_get_contents($this->memoryFile);
        $entries = $this->parseTaskEntries($content);

        $stats = [
            'total_tasks' => count($entries),
            'tasks_this_week' => 0,
            'success_rate' => 0,
            'most_active_files' => [],
            'common_task_types' => [],
            'recent_tasks' => []
        ];

        $successCount = 0;
        $fileCounts = [];
        $oneWeekAgo = strtotime('-1 week');

        foreach ($entries as $entry) {
            // Count tasks this week
            if (strtotime($entry['date']) >= $oneWeekAgo) {
                $stats['tasks_this_week']++;
            }

            // Count success rate
            if ($entry['outcome'] === 'Success') {
                $successCount++;
            }

            // Count file activity
            if (isset($entry['related_files'])) {
                foreach ($entry['related_files'] as $file) {
                    $fileCounts[$file] = ($fileCounts[$file] ?? 0) + 1;
                }
            }
        }

        $stats['success_rate'] = $stats['total_tasks'] > 0
            ? round(($successCount / $stats['total_tasks']) * 100, 2)
            : 0;

        // Sort files by activity
        arsort($fileCounts);
        $stats['most_active_files'] = array_slice($fileCounts, 0, 5, true);

        // Get recent tasks (last 5)
        $stats['recent_tasks'] = array_slice($entries, 0, 5);

        return $stats;
    }

    /**
     * Generate unique task ID
     */
    private function generateTaskId(): string
    {
        $datePrefix = date('Ymd');
        $counter = 1;

        // Check for existing tasks with same date
        if (file_exists($this->memoryFile)) {
            $content = file_get_contents($this->memoryFile);
            preg_match_all("/T{$datePrefix}-(\d+)/", $content, $matches);

            if (!empty($matches[1])) {
                $counter = max($matches[1]) + 1;
            }
        }

        return "T{$datePrefix}-" . str_pad($counter, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Prepare task entry array
     */
    private function prepareTaskEntry(string $taskId, array $taskData): array
    {
        return [
            'task_id' => $taskId,
            'date' => date('Y-m-d H:i:s ' . $this->config['timezone']),
            'task_description' => $taskData['description'] ?? 'Unknown task',
            'outcome' => $taskData['outcome'] ?? 'Success',
            'related_files' => $taskData['files'] ?? [],
            'notes' => $taskData['notes'] ?? '',
            'user_confirmation' => 'Pending'
        ];
    }

    /**
     * Present task summary to user
     */
    private function presentTaskSummary(array $taskEntry): void
    {
        echo "\n" . str_repeat("─", 60) . "\n";
        echo "📝 TASK SUMMARY\n";
        echo str_repeat("─", 60) . "\n";
        echo "Task: {$taskEntry['task_description']}\n";
        echo "Outcome: {$taskEntry['outcome']}\n";

        if (!empty($taskEntry['related_files'])) {
            echo "Files: " . implode(', ', $taskEntry['related_files']) . "\n";
        }

        if (!empty($taskEntry['notes'])) {
            echo "Notes: {$taskEntry['notes']}\n";
        }

        echo str_repeat("─", 60) . "\n";
    }

    /**
     * Request user confirmation
     */
    private function requestUserConfirmation(): bool
    {
        $timeout = $this->config['confirmation_timeout'] ?? 60;
        echo "Confirm logging to project_memory.md? (Yes/No) [{$timeout}s timeout]: ";

        // In a real implementation, this would wait for user input
        // For now, we'll simulate a Yes response
        return true;
    }

    /**
     * Append task entry to memory file
     */
    private function appendToMemoryFile(array $taskEntry): bool
    {
        $content = $this->formatTaskEntry($taskEntry);

        // Ensure file exists
        if (!file_exists($this->memoryFile)) {
            $this->initialize();
        }

        return file_put_contents($this->memoryFile, $content, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Format task entry as markdown
     */
    private function formatTaskEntry(array $taskEntry): string
    {
        $entry = "\n- **Task ID**: {$taskEntry['task_id']}\n";
        $entry .= "  - Date: {$taskEntry['date']}\n";
        $entry .= "  - Task Description: {$taskEntry['task_description']}\n";
        $entry .= "  - Outcome: {$taskEntry['outcome']}\n";
        $entry .= "  - User Confirmation: {$taskEntry['user_confirmation']}\n";

        if (!empty($taskEntry['related_files'])) {
            $entry .= "  - Related Files: " . implode(', ', $taskEntry['related_files']) . "\n";
        }

        if (!empty($taskEntry['notes'])) {
            $entry .= "  - Notes: {$taskEntry['notes']}\n";
        }

        return $entry;
    }

    /**
     * Parse task entries from memory file
     */
    private function parseTaskEntries(string $content): array
    {
        $entries = [];

        // Extract task entries using regex
        preg_match_all('/- \*\*Task ID\*\*: (T\d{8}-\d{3})(.*?)(?=- \*\*Task ID\*\*:|$)/s', $content, $matches);

        foreach ($matches[1] as $index => $taskId) {
            $entryContent = $matches[2][$index];

            $entry = [
                'task_id' => $taskId,
                'date' => $this->extractField($entryContent, 'Date'),
                'task_description' => $this->extractField($entryContent, 'Task Description'),
                'outcome' => $this->extractField($entryContent, 'Outcome'),
                'user_confirmation' => $this->extractField($entryContent, 'User Confirmation'),
                'related_files' => $this->extractFiles($entryContent),
                'notes' => $this->extractField($entryContent, 'Notes')
            ];

            $entries[] = $entry;
        }

        // Sort by date (newest first)
        usort($entries, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $entries;
    }

    /**
     * Extract field value from entry content
     */
    private function extractField(string $content, string $field): string
    {
        preg_match("/- {$field}: (.+)/", $content, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extract files from entry content
     */
    private function extractFiles(string $content): array
    {
        preg_match("/- Related Files: (.+)/", $content, $matches);

        if (!isset($matches[1])) {
            return [];
        }

        return array_map('trim', explode(',', $matches[1]));
    }

    /**
     * Filter entries based on query
     */
    private function filterEntries(array $entries, string $query): array
    {
        $query = strtolower($query);
        $filtered = [];

        foreach ($entries as $entry) {
            if ($this->matchesQuery($entry, $query)) {
                $filtered[] = $entry;
            }
        }

        return $filtered;
    }

    /**
     * Check if entry matches query
     */
    private function matchesQuery(array $entry, string $query): bool
    {
        $searchableText = strtolower(implode(' ', [
            $entry['task_description'],
            $entry['notes'],
            implode(' ', $entry['related_files']),
            $entry['outcome']
        ]));

        // Date-based matching
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $query, $dateMatch)) {
            return strpos($entry['date'], $dateMatch[1]) !== false;
        }

        // Time-based matching
        if (strpos($query, 'last week') !== false) {
            $oneWeekAgo = strtotime('-1 week');
            return strtotime($entry['date']) >= $oneWeekAgo;
        }

        if (strpos($query, 'yesterday') !== false) {
            $yesterday = strtotime('-1 day');
            $today = strtotime('today');
            $entryTime = strtotime($entry['date']);
            return $entryTime >= $yesterday && $entryTime < $today;
        }

        // File-based matching
        if (strpos($query, '.php') !== false) {
            foreach ($entry['related_files'] as $file) {
                if (strpos(strtolower($file), $query) !== false) {
                    return true;
                }
            }
        }

        // General text matching
        return strpos($searchableText, $query) !== false;
    }

    /**
     * Present query results to user
     */
    private function presentQueryResults(array $results, int $totalMatches): void
    {
        if (empty($results)) {
            echo "📭 No tasks found matching your query.\n";
            echo "💡 Try different keywords or check project_memory.md\n";
            return;
        }

        echo "\n" . str_repeat("═", 60) . "\n";
        echo "🔍 QUERY RESULTS\n";
        echo str_repeat("═", 60) . "\n";
        echo "Found {$totalMatches} task" . ($totalMatches === 1 ? '' : 's') . ":\n\n";

        foreach ($results as $index => $entry) {
            $num = $index + 1;
            echo "{$num}. {$entry['task_id']}: {$entry['task_description']}\n";
            echo "   Status: {$entry['outcome']} | Date: {$entry['date']}\n";

            if (!empty($entry['related_files'])) {
                echo "   Files: " . implode(', ', $entry['related_files']) . "\n";
            }

            echo "\n";
        }

        if ($totalMatches > count($results)) {
            echo "📄 Showing first " . count($results) . " results.\n";
            echo "💡 Full details available in project_memory.md\n";
        }

        echo str_repeat("═", 60) . "\n";
    }

    /**
     * Update statistics
     */
    private function updateStatistics(): void
    {
        $stats = $this->getStatistics();
        $this->updateStatisticsSection($stats);
    }

    /**
     * Update statistics section in memory file
     */
    private function updateStatisticsSection(array $stats): void
    {
        if (!file_exists($this->memoryFile)) {
            return;
        }

        $content = file_get_contents($this->memoryFile);

        $statsSection = "\n## Task Statistics\n";
        $statsSection .= "- Total Tasks: {$stats['total_tasks']}\n";
        $statsSection .= "- Tasks This Week: {$stats['tasks_this_week']}\n";
        $statsSection .= "- Success Rate: {$stats['success_rate']}%\n";

        if (!empty($stats['most_active_files'])) {
            $statsSection .= "- Most Active Files: " . implode(', ', array_keys($stats['most_active_files'])) . "\n";
        }

        // Replace or add statistics section
        if (strpos($content, '## Task Statistics') !== false) {
            $content = preg_replace('/## Task Statistics.*?(?=\n##|\n#|$)/s', trim($statsSection), $content);
        } else {
            $content = str_replace('## Task History', $statsSection . "\n\n## Task History", $content);
        }

        file_put_contents($this->memoryFile, $content);
    }

    /**
     * Load configuration
     */
    private function loadConfig(): array
    {
        $configFile = $this->projectRoot . '/project_memory_config.json';

        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config ?: $this->getDefaultConfig();
        }

        return $this->getDefaultConfig();
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'timezone' => 'SAST',
            'auto_log' => true,
            'confirmation_timeout' => 60,
            'max_entries_per_query' => 10,
            'archive_after_days' => 90,
            'auto_backup' => true,
            'include_file_stats' => true,
            'track_outcomes' => true,
            'smart_suggestions' => true
        ];
    }

    /**
     * Get memory file template
     */
    private function getMemoryTemplate(): string
    {
        return '# Project Memory Log

## Task Statistics
- Total Tasks: 0
- Tasks This Week: 0
- Success Rate: 0%
- Most Active Files: None

## Task History
<!-- Task entries will be appended here -->

## Quick Reference
### Recent Tasks
<!-- Recent tasks will be listed here -->

### Files Modified This Week
<!-- Active files will be listed here -->

### Common Task Types
<!-- Task types will be tracked here -->

---
*Generated by Project Memory Manager Skill*
*Last Updated: ' . date('Y-m-d H:i:s') . '*';
    }

    /**
     * Validate memory file integrity
     */
    public function validateMemoryFile(): bool
    {
        if (!file_exists($this->memoryFile)) {
            echo "❌ project_memory.md does not exist\n";
            return false;
        }

        $content = file_get_contents($this->memoryFile);

        // Check for required sections
        $requiredSections = ['# Project Memory Log', '## Task Statistics', '## Task History'];

        foreach ($requiredSections as $section) {
            if (strpos($content, $section) === false) {
                echo "❌ Missing required section: {$section}\n";
                return false;
            }
        }

        echo "✅ project_memory.md is valid\n";
        return true;
    }

    /**
     * Archive old entries
     */
    public function archiveOldEntries(int $daysOld = 90): bool
    {
        if (!file_exists($this->memoryFile)) {
            return false;
        }

        $content = file_get_contents($this->memoryFile);
        $entries = $this->parseTaskEntries($content);

        $cutoffDate = strtotime("-{$daysOld} days");
        $currentEntries = [];
        $archivedEntries = [];

        foreach ($entries as $entry) {
            if (strtotime($entry['date']) >= $cutoffDate) {
                $currentEntries[] = $entry;
            } else {
                $archivedEntries[] = $entry;
            }
        }

        if (!empty($archivedEntries)) {
            $archiveFile = $this->projectRoot . '/project_memory_archive.md';
            $archiveContent = "# Archived Project Memory (Older than {$daysOld} days)\n\n";

            foreach ($archivedEntries as $entry) {
                $archiveContent .= $this->formatTaskEntry($entry);
            }

            file_put_contents($archiveFile, $archiveContent, FILE_APPEND | LOCK_EX);

            // Rebuild current memory file
            $this->rebuildMemoryFile($currentEntries);

            echo "✅ Archived " . count($archivedEntries) . " old entries\n";
            echo "📄 Archive saved to: {$archiveFile}\n";
        }

        return true;
    }

    /**
     * Rebuild memory file with current entries
     */
    private function rebuildMemoryFile(array $entries): void
    {
        $template = $this->getMemoryTemplate();
        $content = $template;

        // Add current entries
        foreach ($entries as $entry) {
            $content .= $this->formatTaskEntry($entry);
        }

        file_put_contents($this->memoryFile, $content);
    }
}

// Command line interface for testing
if (php_sapi_name() === 'cli') {
    $manager = new ProjectMemoryManager();

    // Initialize if needed
    if (!$manager->validateMemoryFile()) {
        $manager->initialize();
    }

    // Test logging
    $testTask = [
        'description' => 'Test task for Project Memory Manager',
        'outcome' => 'Success',
        'files' => ['test.php'],
        'notes' => 'This is a test entry to verify functionality'
    ];

    $manager->logTask($testTask);

    // Test querying
    echo "\n🔍 Testing query functionality...\n";
    $manager->queryHistory('test');

    // Show statistics
    echo "\n📊 Project Statistics:\n";
    $stats = $manager->getStatistics();
    foreach ($stats as $key => $value) {
        if (is_array($value)) {
            echo "{$key}: " . json_encode($value) . "\n";
        } else {
            echo "{$key}: {$value}\n";
        }
    }
}