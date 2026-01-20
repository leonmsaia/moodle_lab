<?php
/**
 * CLI script to migrate user activity completions from Moodle 3.5 to 4.5
 *
 * This script connects directly to the Moodle 3.5 database and migrates
 * activity completions for a specific user, preserving the original
 * completion dates and states.
 *
 * Usage:
 *   php migrate_user_completions.php --username=USERNAME [--verbose] [--dry-run] [--force]
 *
 * Options:
 *   --username=USERNAME  Username of the user to migrate (required)
 *   --verbose            Show detailed output
 *   --dry-run            Simulate migration without actually writing data
 *   --force              Overwrite existing completions
 *   --help               Show this help message
 *
 * Examples:
 *   # Basic migration
 *   php migrate_user_completions.php --username=12213115
 *
 *   # Verbose dry run
 *   php migrate_user_completions.php --username=12213115 --verbose --dry-run
 *
 *   # Force overwrite existing completions
 *   php migrate_user_completions.php --username=12213115 --force
 *
 * @package    local_restoration
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use tool_eabcetlbridge\completion\completion_migrator;

/**
 * Custom output handler for logging to file
 */
class LoggingOutputHandler {
    private $log_file = null;
    private $echo_to_stdout = true;
    private $file_handle = null;

    public function __construct($log_file = null) {
        $this->log_file = $log_file;
        $this->echo_to_stdout = ($log_file === null);

        // Open file handle for writing with append mode
        if ($this->log_file) {
            $this->file_handle = fopen($this->log_file, 'a');
            if ($this->file_handle) {
                // Disable buffering for immediate writes
                stream_set_write_buffer($this->file_handle, 0);
            }
        }
    }

    public function output($message) {
        $message = $message . "\n";

        if ($this->echo_to_stdout) {
            echo $message;
        }

        if ($this->file_handle) {
            fwrite($this->file_handle, $message);
            fflush($this->file_handle);
        }
    }

    public function __destruct() {
        if ($this->file_handle) {
            fclose($this->file_handle);
        }
    }
}

// Global output handler (will be initialized later)
$OUTPUT_HANDLER = null;

/**
 * Custom echo function that respects log file setting
 */
function cli_output($message) {
    global $OUTPUT_HANDLER;
    if ($OUTPUT_HANDLER) {
        $OUTPUT_HANDLER->output($message);
    } else {
        echo $message . "\n";
    }
}

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    [
        'username' => null,
        'verbose' => false,
        'dry-run' => false,
        'force' => false,
        'log-file' => null,
        'help' => false,
        'migrate-course-date' => false,
        'force-course-date' => false
    ],
    [
        'h' => 'help',
        'v' => 'verbose'
    ]
);

// Show help if requested
if ($options['help'] || !$options['username']) {
    echo "Migrate User Activity Completions from Moodle 3.5 to 4.5\n\n";
    echo "This script migrates activity completions for a specific user,\n";
    echo "preserving the original completion dates and states.\n\n";
    echo "Usage:\n";
    echo "  php migrate_user_completions.php --username=USERNAME [OPTIONS]\n\n";
    echo "Required arguments:\n";
    echo "  --username=USERNAME    Username of the user to migrate\n\n";
    echo "Options:\n";
    echo "  --verbose, -v          Show detailed output\n";
    echo "  --dry-run              Simulate migration without writing data\n";
    echo "  --force                Overwrite existing completions\n";
    echo "  --migrate-course-date  Enable migration of course completion date\n";
    echo "  --force-course-date    Force overwrite of existing course completion date\n";
    echo "  --log-file=PATH        Save output to log file instead of stdout\n";
    echo "  --help, -h             Show this help message\n\n";
    echo "Examples:\n";
    echo "  # Basic migration\n";
    echo "  php migrate_user_completions.php --username=12213115\n\n";
    echo "  # Verbose dry run\n";
    echo "  php migrate_user_completions.php --username=12213115 --verbose --dry-run\n\n";
    echo "  # Force overwrite\n";
    echo "  php migrate_user_completions.php --username=12213115 --force\n\n";
    exit(0);
}

// Validate username
$username = trim($options['username']);
if (empty($username)) {
    cli_error("Error: Username cannot be empty\n");
}

// Check configuration
if (!isset($CFG->moodle35_db) || !is_array($CFG->moodle35_db)) {
    cli_error("Error: Missing \$CFG->moodle35_db configuration in config.php\n\n" .
              "Please add the following to your config.php:\n\n" .
              "\$CFG->moodle35_db = [\n" .
              "    'dbtype' => 'mysqli',\n" .
              "    'dblibrary' => 'native',\n" .
              "    'dbhost' => 'mutual35-db-1',\n" .
              "    'dbname' => 'moodle',\n" .
              "    'dbuser' => 'root',\n" .
              "    'dbpass' => '123456',\n" .
              "    'prefix' => 'mdl_',\n" .
              "    'dboptions' => [\n" .
              "        'dbport' => 3306,\n" .
              "        'dbcollation' => 'utf8mb4_unicode_ci'\n" .
              "    ]\n" .
              "];\n\n");
}

// Initialize output handler
$OUTPUT_HANDLER = new LoggingOutputHandler($options['log-file']);

// Display initial information
cli_output("");
cli_output("========================================");
cli_output("User Activity Completion Migration");
cli_output("========================================");
cli_output("Username: {$username}");
cli_output("Mode: " . ($options['dry-run'] ? "DRY RUN (simulation)" : "LIVE"));
cli_output("Verbose: " . ($options['verbose'] ? "Yes" : "No"));
cli_output("Force: " . ($options['force'] ? "Yes" : "No"));
cli_output("Migrate course date: " . ($options['migrate-course-date'] ? "Yes" : "No"));
cli_output("Force course date: " . ($options['force-course-date'] ? "Yes" : "No"));
if ($options['log-file']) {
    cli_output("Log file: " . $options['log-file']);
}
cli_output("========================================");
cli_output("");

// Confirm if not dry run and not logging to file
if (!$options['dry-run'] && !$options['verbose'] && !$options['log-file']) {
    echo "This will migrate completions from Moodle 3.5 to 4.5.\n";
    echo "Press Enter to continue, or Ctrl+C to cancel...\n";
    fgets(STDIN);
    echo "\n";
}

// Set up options for migrator
$migrator_options = [
    'verbose' => $options['verbose'],
    'dry_run' => $options['dry-run'],
    'force' => $options['force'],
    'output_handler' => $OUTPUT_HANDLER,
    'migrate_course_date' => $options['migrate-course-date'],
    'force_course_date' => $options['force-course-date']
];

// Create migrator and run
try {
    $start_time = microtime(true);

    $migrator = new completion_migrator($migrator_options);
    $stats = $migrator->migrate_user_completions($username);

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    cli_output("");
    cli_output("Migration completed in {$duration} seconds");
    cli_output("");

    // Exit with appropriate code
    if ($stats['errors'] > 0) {
        exit(1);
    } else {
        exit(0);
    }

} catch (moodle_exception $e) {
    cli_output("");
    cli_output("ERROR: " . $e->getMessage());
    if ($options['verbose'] && $e->debuginfo) {
        cli_output("Debug info: " . $e->debuginfo);
    }
    cli_output("");
    exit(1);
} catch (Exception $e) {
    cli_output("");
    cli_output("UNEXPECTED ERROR: " . $e->getMessage());
    if ($options['verbose']) {
        cli_output("Trace:");
        cli_output($e->getTraceAsString());
    }
    cli_output("");
    exit(1);
}
