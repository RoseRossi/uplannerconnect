<?php
/**
 * @package     uPlannerConnect
 * @copyright   cristian machado mosquera <cristian.machado@correounivalle.edu.co>
 * @copyright   daniel eduardo dorado <doradodaniel14@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


//Variables globales
require_once(__DIR__ . '/EnumEtities.php');

/**
   * @package uPlannerConnect
   * @author Cristian Machado <cristian.machado@correounivalle.edu.co>
   * @author Daniel Dorado <doradodaniel14@gmail.com>
   * @description Instancia una entidad de acorde a la funcionalidad que se requiera
*/
class ManagementEntityFactory {

    private $EnumEtities;

    /**
     * @package uPlannerConnect
     * @description constructor de la clase
     * @return void
    */
    public function __construct() {
        $this->EnumEtities = new EnumEtities();
    }

    /**
     * @package uPlannerConnect
     * @description retorna una instancia de la entidad de acuerdo al 
     *              tipo de dato que se requiera
    */
    public function create(array $data) {
        $this->EnumEtities->process($data);
    }

}