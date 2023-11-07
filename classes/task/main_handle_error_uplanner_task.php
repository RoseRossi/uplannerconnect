<?php
/**
 * @package     uPlannerConnect
 * @copyright   Cristian Machado Mosquera <cristian.machado@correounivalle.edu.co>
 * @copyright   Daniel Eduardo Dorado <doradodaniel14@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uplannerconnect\task;

use local_uplannerconnect\application\repository\repository_type;
use local_uplannerconnect\infrastructure\api\handle_send_uplanner_task;
use coding_exception;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Task in charge of cleaning the daily records sent to uPlanner
 */
class main_handle_error_uplanner_task extends \core\task\scheduled_task
{
    /**
     * @inerhitdoc
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('syncerroruplannertask', 'local_uplannerconnect');
    }

    /**
     * @inerhitdoc
     */
    public function execute() {
        $time_now = time();
        $start_time = microtime();
        mtrace("Update cron started at: " . date('r', $time_now) . "\n");
        try {
            $handle_task = new handle_send_uplanner_task();
            $handle_task->process(repository_type::STATE_ERROR, 1000);
        } catch (moodle_exception $e) {
            error_log('main_handle_error_uplanner_task - execute: ' . $e->getMessage() . "\n");
        }
        mtrace("\n" . 'Cron completed at: ' . date('r', time()) . "\n");
        mtrace('Memory used: ' . display_size(memory_get_usage())."\n");
        $diff_time = microtime_diff($start_time, microtime());
        mtrace("Scheduled task late " . $diff_time . " seconds to finish.\n");
    }
}