<?php





namespace WPRC\Catalog\WooCommerce\Product\Types;





defined('ABSPATH') || exit;











/** RC product type. */






use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract as WC_Product_Abstract;


use WPRC\Catalog\WooCommerce\Taxonomy\ProductBrand;





class WC_Product_Robot extends WC_Product_Abstract {





    const INSTOCK_LABEL = 'instock';





    const OUTOFSTOCK_LABEL = 'outofstock';





    /** @var string Product's type */    


    protected ?string $product_type = 'robot';





    /**


     * Robot model properties


     */


    protected ?string $robot_model_id           = null;     /** @var int|null Product's robot model ID */


    protected ?object $robot_model_data         = null;     /** @var Object|null Product's robot model */


    protected ?string $robot_model_name         = null;     /** @var string|null Product's robot model name */


    protected ?float $robot_model_reach         = null;     /** @var float|null Product's robot model reach */


    protected ?float $robot_model_payload       = null;     /** @var float|null Product's robot model payload */


    protected ?float $robot_model_repeatability = null;     /** @var float|null Product's robot model repeatability */


    protected ?float $robot_model_mass          = null;     /** @var float|null Product's robot model mass */


    protected  $robot_model_velocities          = null;     /** @var array|null Product's robot model velocities */


    protected  $robot_model_ranges              = null;     /** @var array|null Product's robot model ranges */


    protected  $robot_model_base_ip_grade       = null;     /** @var array|null Product's robot model base IP grade */


    protected  $robot_model_wrist_ip_grade      = null;     /** @var array|null Product's robot model wrist IP grade */


    protected  $robot_model_processes           = null;     /** @var array|null Product's robot model wrist IP grade */





    /**


     * Cabinet model properties


     */


    protected ?string $cabinet_model_id         = null;     /** @var int|null Product's cabinet model ID */


    protected ?object $cabinet_model_data       = null;     /** @var Object|null Product's cabinet model */


    protected ?string $cabinet_model_name       = null;     /** @var string|null Product's cabinet model name */





    /**


     * Misc


     */


    protected ?string $software_version         = null;     /** @var string|null Product's software version */


    protected ?float $cable_length              = null;     /** @var float|null Product's cable length */ 


    protected ?string $sale_type                = null;     /** @var string|null Product's sale type*/








    public function __construct( $product ) { parent::__construct( $product ); }    


    private function catalog_robot_model(): ?\WPRC\Catalog\RobotModel\Domain\RobotModel
    {
        $modelId = absint($this->get_meta(WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID, true));
        if ($modelId <= 0 || !function_exists('rc_catalog')) {
            return null;
        }

        try {
            return rc_catalog()->robotModels()->find($modelId);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return \WP_Post[] */
    private function catalog_robot_applications(): array
    {
        $model = $this->catalog_robot_model();
        if (!$model || !function_exists('rc_catalog')) {
            return [];
        }

        try {
            return rc_catalog()->robotModels()->applications($model->id);
        } catch (\Throwable) {
            return [];
        }
    }


    private function catalog_axis_unit(string $unit): string
    {
        return match ($unit) {
            'deg' => '°',
            'deg_s' => '°/s',
            'mm_s' => 'mm/s',
            default => $unit,
        };
    }

    private function catalog_robot_controller(): ?\WPRC\Catalog\RobotModel\Domain\Controller
    {
        $controllerId = absint($this->get_meta(WC_Product_Abstract::META_ROBOT_CONTROLLER_ID, true));
        $model = $this->catalog_robot_model();
        if ($controllerId <= 0 || !$model || !function_exists('rc_catalog')) {
            return null;
        }

        try {
            foreach (rc_catalog()->robotModels()->compatibleControllers($model->id) as $controller) {
                if ($controller->id === $controllerId) {
                    return $controller;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }


    


    /**


     * Get product's robot model ID


     * 


     * @return int|null


     */


    public function get_robot_model_id( ): string  


    { 





        return (string) $this->lazy( 


            'robot_model_id', 


            fn() => $this->get_meta( WC_Product_Abstract::META_ROBOT_ID ) 


        );





    }





    /**


     * Get product's robot model data


     * 


     * @return object|null


     */


    public function get_robot_model_data( ): ?object


    {
        $model = $this->catalog_robot_model();
        if ($model) {
            return (object) [
                'id' => $model->id,
                'reference' => $model->reference,
                'name' => $model->title ?: $model->reference,
                'payload' => $model->payloadKg,
                'reach' => $model->reachMm,
                'mass' => $model->massKg,
                'repeatability' => $model->repeatabilityMm,
                'structure' => $model->structure,
                'axes_count' => $model->axesCount,
                'ip_base' => $model->ipBase,
                'ip_wrist' => $model->ipWrist,
            ];
        }

        return null;
    }





    /**


     * Get product's robot model name


     * 


     * @return string|null


     */


    public function get_robot_model_name( ): ?string


    {
        $model = $this->catalog_robot_model();
        if ($model) {
            return (string) ($model->title ?: $model->reference);
        }

        $legacy = trim((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_MODEL));
        return $legacy !== '' ? $legacy : null;
    }





    /**


     * Get product's robot model payload


     * 


     * @return float|null


     */


    public function get_robot_model_payload( ): ?float


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->payloadKg !== null) {
            return (float) $model->payloadKg;
        }

        $legacy = $this->get_meta(WC_Product_Abstract::META_ROBOT_PAYLOAD);
        return $legacy === '' || $legacy === null ? null : (float) $legacy;
    }





    /**


     * Get product's robot model reach


     * 


     * @return float|null


     */


    public function get_robot_model_reach( ): ?float


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->reachMm !== null) {
            return (float) $model->reachMm;
        }

        $legacy = $this->get_meta(WC_Product_Abstract::META_ROBOT_REACH);
        return $legacy === '' || $legacy === null ? null : (float) $legacy;
    }





    /**


     * Get product's robot model repeatability


     * 


     * @return float|null


     */


    public function get_robot_model_repeatability( ): ?float


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->repeatabilityMm !== null) {
            return (float) $model->repeatabilityMm;
        }

        $legacy = $this->get_meta(WC_Product_Abstract::META_ROBOT_REPEATABILITY);
        return $legacy === '' || $legacy === null ? null : (float) $legacy;
    }





    /**


     * Get product's robot model mass


     * 


     * @return float|null


     */


    public function get_robot_model_mass( ): ?float


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->massKg !== null) {
            return (float) $model->massKg;
        }

        $legacy = $this->get_meta(WC_Product_Abstract::META_ROBOT_MASS);
        return $legacy === '' || $legacy === null ? null : (float) $legacy;
    }





    /**


     * Get product's robot model base IP grade


     * 


     * @return float|null


     */


    public function get_robot_model_base_ip_grade( ): ?string


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->ipBase !== null && $model->ipBase !== '') {
            return $model->ipBase;
        }

        $legacy = trim((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_IP_GRADE_BASE));
        return $legacy !== '' ? $legacy : null;
    }





    /**


     * Get product's robot model base IP grade


     * 


     * @return float|null


     */


    public function get_robot_model_wrist_ip_grade( ): ?string


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->ipWrist !== null && $model->ipWrist !== '') {
            return $model->ipWrist;
        }

        $legacy = trim((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_IP_GRADE_WRIST));
        return $legacy !== '' ? $legacy : null;
    }


    


    /**


     * Get product's robot model axis velocities


     * 


     * @return object|null


     */


    public function get_robot_model_axis_velocities( ): ?object  


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->axes !== []) {
            $values = [];
            foreach ($model->axes as $axis) {
                if ($axis->maxVelocity === null) {
                    continue;
                }
                $values['j' . $axis->axisNumber] = (object) [
                    'value' => $axis->maxVelocity,
                    'unit' => $this->catalog_axis_unit($axis->velocityUnit),
                ];
            }
            return $values !== [] ? (object) $values : null;
        }

        $legacy = json_decode((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_VELOCITIES));
        return is_object($legacy) ? $legacy : null;
    }





    /**


     * Get product's robot model axis ranges


     * 


     * @return object|null


     */


    public function get_robot_model_axis_ranges( ): ?object  


    {
        $model = $this->catalog_robot_model();
        if ($model && $model->axes !== []) {
            $values = [];
            foreach ($model->axes as $axis) {
                if ($axis->rangeMin !== null) {
                    $values['j' . $axis->axisNumber . '_min'] = (object) [
                        'value' => $axis->rangeMin,
                        'unit' => $this->catalog_axis_unit($axis->rangeUnit),
                    ];
                }
                if ($axis->rangeMax !== null) {
                    $values['j' . $axis->axisNumber . '_max'] = (object) [
                        'value' => $axis->rangeMax,
                        'unit' => $this->catalog_axis_unit($axis->rangeUnit),
                    ];
                }
            }
            return $values !== [] ? (object) $values : null;
        }

        $legacy = json_decode((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_RANGES));
        return is_object($legacy) ? $legacy : null;
    }





    /**


     * Get product's robot model axis ranges


     * 


     * @return object|null


     */


    public function get_robot_model_processes( ): ?array  


    {
        $applications = $this->catalog_robot_applications();
        if ($applications !== []) {
            return array_values(array_filter(array_map(
                static fn (\WP_Post $page): string => (string) $page->post_name,
                $applications
            )));
        }

        $processes = [];
        $raw = json_decode((string) $this->get_meta(WC_Product_Abstract::META_ROBOT_PROCESSES), true);
        foreach (is_array($raw) ? $raw : [] as $process => $params) {
            unset($params);
            $processes[] = (string) $process;
        }
        return $processes;
    }





    /**


     * Get product's cabinet model ID


     * 


     * @return string|null


     */


    public function get_cabinet_model_id( ): ?string  


    { 





        return (string) $this->lazy( 


            'cabinet_model_id', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_CABINET_ID ) 


        );





    }





    /**


     * Get product's cabinet model data


     * 


     * @return object


     */


    public function get_cabinet_model_data( ): ?object
    {
        $controller = $this->catalog_robot_controller();
        if (!$controller) {
            return null;
        }

        return (object) [
            'id' => $controller->id,
            'name' => $controller->name,
            'reference' => $controller->reference,
        ];
    }





    /**


     * Get product's cabinet name


     * 


     * @return string|null


     */


    public function get_cabinet_model_name( ): ?string  


    {
        $controller = $this->catalog_robot_controller();
        if ($controller) {
            return $controller->name . ($controller->reference ? ' — ' . $controller->reference : '');
        }

        $legacy = trim((string) $this->get_meta(WC_Product_Abstract::META_CABINET_MODEL));
        return $legacy !== '' ? $legacy : null;
    }





    /**


     * Get product's software version


     * 


     * @return string|null


     */


    public function get_software_version( ): ?string 


    {





        return (string) $this->lazy( 


            'software_version', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_SW_VERSION ),


            __( 'Non spécifiée', 'wprc') 


        );





    }





    /**


     * Get product's cable length


     * 


     * @return float|null


     */


    public function get_cable_length( ): ?float 


    { 





        return (float) $this->lazy( 


            'cable_length', 


            fn() => (float) $this->get_meta( WC_Product_Abstract::META_CABLE_LENGTH )


        );





    }


    


    


            


    public function get_condition( $key = '' ):string             { return (string) $this->get_meta( 'wprc_product_condition' ) ?? false; }


    


    public function get_warranty( $key = '' ):int                 { return (int) $this->get_meta( 'wprc_product_warranty' ) ?? false; }


}


