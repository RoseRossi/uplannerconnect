<?php
/**
 * @package     uPlannerConnect
 * @copyright   Cristian Machado Mosquera <cristian.machado@correounivalle.edu.co>
 * @copyright   Daniel Eduardo Dorado <doradodaniel14@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_uplannerconnect\infrastructure\api;

use local_uplannerconnect\application\messages\connection;
use local_uplannerconnect\application\repository\general_repository;
use local_uplannerconnect\application\repository\messages_status_repository;
use local_uplannerconnect\application\repository\repository_type;
use local_uplannerconnect\infrastructure\api\client\abstract_uplanner_client;
use local_uplannerconnect\infrastructure\api\factory\uplanner_client_factory;
use local_uplannerconnect\infrastructure\email\email;
use local_uplannerconnect\infrastructure\file;
use local_uplannerconnect\infrastructure\log;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Class handle_clean_uplanner_task, handle remove success uPlanner task
 */
class handle_clean_uplanner_task
{
    /**
     * @var $uplanner_client_factory
     */
    private $uplanner_client_factory;

    /**
     * @var $file
     */
    private $file;

    /**
     * @var $log
     */
    private $log;

    /**
     * @var email
     */
    private $email;

    /**
     * @var messages_status_repository
     */
    private $message_repository;

    /**
     * @var general_repository
     */
    private $general_repository;

    /**
     * @var $task_di
     */
    private $task_id;

    /**
     * @var $current_date
     */
    private $current_date;

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * Construct
     *
     * @param $tasks_id
     */
    public function __construct(
        $tasks_id
    ) {
        $this->task_id = $tasks_id;
        $this->prefix = $this->task_id . '_';
        $this->current_date = date("F j, Y, g:i:s a");
        $this->uplanner_client_factory = new uplanner_client_factory();
        $this->email = new email();
        $this->message_repository = new messages_status_repository();
        $this->general_repository = new general_repository();
    }

    /**
     * Handle process remove state success registers uPlanner
     *
     * @param int $page_size
     * @return void
     */
    public function process($page_size = 1000) {
        try {
            $connection = connection::getInstance()->getConnection();
            if ($connection) {
                $this->create_log($this->prefix . $this->task_id . '_log');
                $this->log->add_line("------------------------------------------  UPLANNER - PROCESS START - FOREACH REPOSITORIES ------------------------------------------ ");
                $log_id = $this->general_repository->add_log_data();
                foreach (repository_type::ACTIVE_REPOSITORY_TYPES as $type => $repository_class) {
                    $this->log->add_line('------- CREATE REPOSITORY OBJECT: ' . $type . ' - ' . $repository_class  . PHP_EOL);
                    $repository = new $repository_class($type);
                    $uplanner_client = $this->uplanner_client_factory->create($type);
                    $this->start_process_per_repository(
                        $repository,
                        $uplanner_client,
                        $page_size
                    );
                }
                $this->log->add_line("------------------------------------------            ADD LOGS (COUNT LOGS)     ------------------------------------------ ");
                $this->general_repository->add_log_errors_data($log_id);
                $this->log->add_line("-------------------------------------- UPLANNER - DELETE LOGS (success and is_sucessful = 1)------------------------------------------ ");
                foreach (repository_type::ACTIVE_REPOSITORY_TYPES as $type => $repository_class) {
                    $repository = new $repository_class($type);
                    // Remove registers with operation complete
                    $condition = [
                        'success' => 1,
                        'is_sucessful' => 1
                    ];
                    $this->general_repository->delete_rows($repository::TABLE, $condition);
                }
                $this->log->add_line("------------------------------------------            UPLANNER - PROCESS FINISHED             ------------------------------------------ ");
                $this->send_email(
                    $this->prefix . $this->task_id . '_log',
                    $this->log
                );
                $this->log->reset_log();
            } else {
                mtrace('Connection failed, error invalid data or credentials' . PHP_EOL);
            }
        } catch (moodle_exception $e) {
            error_log('get_messages: ' . $e->getMessage() . PHP_EOL);
        }
    }

    /**
     * Start process per repository
     *
     * @param $repository
     * @param $uplanner_client
     * @param $page_size
     * @return void
     */
    private function start_process_per_repository(
        $repository,
        $uplanner_client,
        $page_size
    ) {
        try {
            if ($page_size <= 0) {
                return;
            }
            $fileCreated = $this->create_file($this->prefix . $uplanner_client->get_file_name());
            $this->log->add_line('********** UPLANNER - FILE IS CREATED: ' . $fileCreated);
            $offset = 0;
            $this->log->add_line('********** UPLANNER - PROCESS PER REPOSITORY - WHILE: ');
            while (true) {
                $data = [
                    'state' => repository_type::STATE_SEND,
                    'limit' => $page_size,
                    'offset' => $offset,
                ];
                $this->log->add_line('UPLANNER - DATA QUERY: ' . json_encode($data));
                $rows = $repository->getDataBD($data);
                $this->log->add_line('UPLANNER - DATA ROWS: ' . json_encode($rows));
                if (!$rows) {
                    break;
                }
                $this->log->add_line('UPLANNER - PROCESS - COMPARE LOGS ');
                $this->message_repository->process($repository, $rows, $this->log);
                $data = [
                    'state' => repository_type::STATE_SEND,
                    'limit' => $page_size,
                    'offset' => $offset,
                ];
                $rows = $repository->getDataBD($data);
                if ($fileCreated) {
                    $this->log->add_line('UPLANNER - ADD ROWS IN FILE ');
                    $this->add_rows_in_file($rows);
                    $this->log->add_line('UPLANNER - SEND EMAIL ');
                    $this->send_email($this->prefix . $uplanner_client->get_email_subject(), $this->file);
                    $this->log->add_line('UPLANNER - RESET FILE ');
                    $this->file->reset_csv($this->getHeaders());
                }
                $offset += count($rows);
            }
        } catch (moodle_exception $e) {
            error_log('handle_remove_success_uplanner_task - process: ' . $e->getMessage() . PHP_EOL);
        }
    }

    /**
     * Get headers
     *
     * @return string[]
     */
    private function getHeaders()
    {
        $headers = abstract_uplanner_client::FILE_HEADERS;
        $headers[] = 'is_sucessful';
        $headers[] = 'ds_error';

        return $headers;
    }

    /**
     * Create and add log file
     *
     * @param $file_name
     * @return void
     */
    private function create_log($file_name)
    {
        $this->log = new log($this->task_id, $file_name . '_date');
        $this->log->create_log(
            'TASK ' . strtoupper($this->task_id) . ' ' . $this->current_date
        );
    }

    /**
     * Create and add rows in file
     *
     * @param $file_name
     * @return bool
     */
    private function create_file($file_name)
    {
        $headers = $this->getHeaders();
        $this->file = new file($this->task_id, $file_name);
        return $this->file->create_csv($headers);
    }

    /**
     * Add rows in file
     *
     * @param $rows
     * @return void
     */
    private function add_rows_in_file($rows)
    {
        foreach ($rows as $row) {
            $data = [
                $row->json,
                $row->success,
                $row->is_sucessful,
                $row->ds_error
            ];
            $this->file->add_row($data);
        }
    }

    /**
     * Send email
     *
     * @param $subject
     * @param $file
     * @return bool
     */
    private function send_email($subject, $file): bool
    {
        $recipient_email = 'samuel.ramirez@correounivalle.edu.co';
        return $this->email->send(
            $recipient_email,
            $subject,
            $this->current_date,
            $file->get_path_file(),
            $file->get_virtual_name()
        );
    }
}