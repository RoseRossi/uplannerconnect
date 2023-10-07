<?php
/**
 * @package     local_uplannerconnect
 * @athor       Cristian Machado <cristian.machado@correounivalle.edu.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_uplannerconnect\application\repository;

use local_uplannerconnect\plugin_config\plugin_config;
use moodle_exception;

/**
   * Instancia una entidad de acorde a la funcionalidad que se requiera
*/
class course_notes_repository
{
    const STATE_DEFAULT = 0;  //Estado por defecto
    const STATE_SEND = 1; //Estado de envío
    const STATE_ERROR = 2; //Estado de error

    private $general_repository;

    public function __construct()
    {
        $this->general_repository = new general_repository();
    }

    /**
     * Actualiza los datos en la base de datos
     *
     * @param array $data
     * @return void
     */
    public function updateDataBD(array $data) : void
    {
        $data['query'] = plugin_config::QUERY_UPDATE_COURSE_GRADES;
        $data['table'] = plugin_config::TABLE_COURSE_GRADE;
        $this->general_repository->updateDataBD($data);
    }

    /**
     * Guarda los datos en la base de datos
     *
     * @param array $data
     * @return void
     */
    public function saveDataBD(array $data) : void
    {
        $data['query'] = plugin_config::QUERY_INSERT_COURSE_GRADES;
        $data['table'] = plugin_config::TABLE_COURSE_GRADE;
        $this->general_repository->saveDataBD($data);
    }

    /**
     * Obtiene los datos en la base de datos
     *
     * @param array|null $data
     * @return array
     */
    public function getDataBD(array $data = null) : array
    {
        $data['query'] = plugin_config::QUERY_SELECT_COURSE_GRADES;
        $data['table'] = plugin_config::TABLE_COURSE_GRADE;
        return $this->general_repository->getDataBD($data);
    }

    /**
     * Delete registers by field state
     *
     * @param $state
     * @return bool
     */
    public function delete_data_bd($state): bool
    {
        return $this->general_repository->delete_data_bd($state);
    }

    /**
     * Delete registers by field state
     *
     * @param $state
     * @return void
     */
    public function add_log_data() : void
    {
        $this->general_repository->add_log_data([
            'query_insert' => plugin_config::QUERY_COUNT_LOGS,
            'table_insert' => plugin_config::TABLE_COURSE_GRADE,
            'query_log' => plugin_config::QUERY_INSERT_LOGS,
            'table_log' => plugin_config::TABLE_LOG
        ]);
    }
}