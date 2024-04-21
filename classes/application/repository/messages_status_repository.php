<?php
/**
 * @package     local_uplannerconnec
 * @copyright   Daniel Eduardo Dorado P. <doradodaniel14@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uplannerconnect\application\repository;

use Exception;
use local_uplannerconnect\application\messages\messages_resource;

/**
 * Loaded class to manipulate data in uplanner_esb_messages_status table
 */
class messages_status_repository
{
    /**
     * @var messages_resource
     */
    private $messages_resource;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->messages_resource = new messages_resource();
    }

    /**
     * get list by transactions
     *
     * @param $transactions_ids
     * @return array
     */
    public function get_list_by_transactions($transactions_ids)
    {
        return $this->messages_resource->get_messages(
            implode(',', $transactions_ids)
        );
    }

    /**
     * Compare message log in uPlanner
     *
     * @return void
     */
    public function process($repository, $rows, $log, $updateStatus = true)
    {
        error_log('------------------------------------------  PROCESS START - UPLANNER QUERY ------------------------------------------');
        try {
            $uv_transactions = $this->get_uv_transactions($rows);
            $log->add_line(' --- UV TRANSACTIONS: ' . json_encode($uv_transactions));
            $up_messages = $this->get_list_by_transactions(
                $uv_transactions
            );
            $log->add_line(' --- UP MESSAGES: ' . json_encode($up_messages));
            $log->add_line(' ---------------- FOREACH - COMPARE LOGS: ');
            foreach ($rows as $row) {
                $json = json_decode($row->json, true);
                $id_transaction = intval($json['transactionId']);
                $log->add_line(' --- TRANSACTION ID: ' . $id_transaction  . PHP_EOL);
                $filtered_messages = array_filter($up_messages, function ($message) use ($id_transaction) {
                    return $message['id_transaction'] == $id_transaction;
                });
                $message = reset($filtered_messages);
                $is_successful = 0;
                $ds_error = 'Not found in uPlanner database.';
                $state = $row->success;
                if ($message) {
                    if (($message['is_successful'] === 1 || $message['is_successful'] === '1')) {
                        $ds_error = '';
                        $is_successful = 1;
                    } else {
                        $ds_error = $message['ds_error'];
                        $state = repository_type::STATE_UP_ERROR;
                    }
                }
                $log->add_line(' --- UV LOG: ' . json_encode($row));
                $log->add_line(' --- UP LOG: ' . json_encode($message));
                $data = [
                    'is_sucessful' => $is_successful,
                    'ds_error' => $ds_error,
                    'id' => $row->id
                ];
                if ($updateStatus) {
                    $data['success'] = $state;
                }

                if ($updateStatus && !$message) {
                    $data['success'] = repository_type::STATE_UP_ERROR;
                }
                $log->add_line(' --- UV UPDATE: ' . json_encode($data));
                $repository->updateDataBD($data);
            }
        } catch (Exception $e) {
            error_log('messages_status_repository->process: ' . $e->getMessage() . PHP_EOL);
        }
    }

    /**
     * Return list transactions
     *
     * @param $rows
     * @return array
     */
    private function get_uv_transactions($rows)
    {
        return array_map(function ($row) {
            $json = json_decode($row->json, true);
            return intval($json['transactionId']);
        }, $rows);
    }
}