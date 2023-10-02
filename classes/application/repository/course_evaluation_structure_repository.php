<?php
/**
 * @package     local_uplannerconnec
 * @copyright   Cristian Machado <cristian.machado@correounivalle.edu.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_uplannerconnect\application\repository;

use local_uplannerconnect\plugin_config\plugin_config;
use moodle_exception;

/**
   * Instancia una entidad de acorde a la funcionalidad que se requiera.
*/
class course_evaluation_structure_repository
{
    const STATE_DEFAULT = 0; //Estado por defecto
    const STATE_SEND = 1; //Estado de envío
    const STATE_ERROR = 2; //Estado de error

    private $moodle_query_handler;

    public function __construct() {
        //Instancia de la clase moodle_query_handler
        $this->moodle_query_handler = new moodle_query_handler();
    }

    /**
     * Actualiza los datos en la base de datos
     *
     * @param array $data
     * @return void
     */
    public function updateDataBD(array $data) : void
    {
        try {
            if (empty($data)) {
              error_log('Excepción capturada: ' . 'No hay datos para actualizar' . "\n");
              return;
            }

            //insertar datos en la base de datos
            $this->moodle_query_handler->executeQuery(
                sprintf(
                plugin_config::QUERY_UPDATE_COURSE_GRADES,
                plugin_config::TABLE_COURSE_EVALUATION,
                json_encode($data['json']),
                json_encode($data['response']),
                $data['success'],
                $data['id']
                )
            );
        }
        catch (moodle_exception $e) {
          error_log('Excepción capturada: ' . $e->getMessage() . "\n");
        }
    }

    /**
     * Guarda los datos en la base de datos
     *
     * @param array $data
     * @return void
     */
    public function saveDataBD(array $data) : void
    {
        try {
            if (empty($data)) {
              error_log('Excepción capturada: ' . 'No hay datos para guardar' . "\n");
              return;
            }

            //Insertar datos en la base de datos
            $this->moodle_query_handler->executeQuery(
                sprintf(
                plugin_config::QUERY_INSERT_COURSE_GRADES,
                plugin_config::TABLE_COURSE_EVALUATION,
                json_encode($data),
                '{"status": "Default response"}',
                0,
                $data['action']
                )
            );
        }
        catch (moodle_exception $e) {
          error_log('Excepción capturada: ' . $e->getMessage() . "\n");
        }
    }

    /**
     * Obtiene los datos en la base de datos
     *
     * @param array|null $data
     * @return array
     */
    public function getDataBD(array $data = null) : array
    {
        $dataQuery = [];
        try {
            if (empty($data)) {
              error_log('Excepción capturada: ' . 'El estado debe ser un número' . "\n");
              return $dataQuery;
            }

            //Obtener datos en la base de datos
            $dataQuery = $this->moodle_query_handler->executeQuery(
                sprintf(
                  plugin_config::QUERY_SELECT_COURSE_GRADES,
                plugin_config::TABLE_COURSE_EVALUATION,
                  $data['state'],
                  $data['limit'],
                  $data['offset']
                )
            );
        }
        catch (moodle_exception $e) {
          error_log('Excepción capturada: ' . $e->getMessage() . "\n");
        }
        return $dataQuery;
    }
}