<?php
class Ccc_Vendor_Model_Product_Type
{
    const TYPE_SIMPLE = 'simple';
    const TYPE_BUNDLE = 'bundle';
    const TYPE_CONFIGURABLE = 'configurable';
    const TYPE_GROUPED = 'grouped';
    const TYPE_VIRTUAL = 'virtual';

    const DEFAULT_TYPE = 'simple';
    const DEFAULT_TYPE_MODEL = 'vendor/product_type_simple';
    const DEFAULT_PRICE_MODEL = 'vendor/product_type_price';

    protected static $_types;
    protected static $_compositeTypes;
    protected static $_priceModels;
    protected static $_typesPriority;

    public static function factory($product, $singleton = false)
    {
        $types = self::getTypes();
        $typeId = $product->getTypeId();

        if (!empty($types[$typeId]['model'])) {
            $typeModelName = $types[$typeId]['model'];
        } else {
            $typeModelName = self::DEFAULT_TYPE_MODEL;
            $typeId = self::DEFAULT_TYPE;
        }

        if ($singleton === true) {
            $typeModel = Mage::getSingleton($typeModelName);
        } else {
            $typeModel = Mage::getModel($typeModelName);
            $typeModel->setProduct($product);
        }
        $typeModel->setConfig($types[$typeId]);
        return $typeModel;
    }

    public static function priceFactory($productType)
    {
        if (isset(self::$_priceModels[$productType])) {
            return self::$_priceModels[$productType];
        }

        $types = self::getTypes();

        if (!empty($types[$productType]['price_model'])) {
            $priceModelName = $types[$productType]['price_model'];
        } else {
            $priceModelName = self::DEFAULT_PRICE_MODEL;
        }

        self::$_priceModels[$productType] = Mage::getModel($priceModelName);
        return self::$_priceModels[$productType];
    }

    public static function getOptionArray()
    {
        $options = array();
        foreach (self::getTypes() as $typeId => $type) {
            $options[$typeId] = Mage::helper('vendor')->__($type['label']);
        }

        return $options;
    }

    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, array('value' => '', 'label' => ''));
        return $options;
    }

    public static function getAllOptions()
    {
        $res = array();
        $res[] = array('value' => '', 'label' => '');
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
                'value' => $index,
                'label' => $value,
            );
        }
        return $res;
    }

    public static function getOptions()
    {
        $res = array();
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
                'value' => $index,
                'label' => $value,
            );
        }
        return $res;
    }

    public static function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    public static function getTypes()
    {
        if (is_null(self::$_types)) {
            $productTypes = Mage::getConfig()->getNode('global/vendor/product/type')->asArray();
            foreach ($productTypes as $productKey => $productConfig) {
                $moduleName = 'vendor';
                if (isset($productConfig['@']['module'])) {
                    $moduleName = $productConfig['@']['module'];
                }
                $translatedLabel = Mage::helper($moduleName)->__($productConfig['label']);
                $productTypes[$productKey]['label'] = $translatedLabel;
            }
            self::$_types = $productTypes;
        }

        return self::$_types;
    }

    public static function getCompositeTypes()
    {
        if (is_null(self::$_compositeTypes)) {
            self::$_compositeTypes = array();
            $types = self::getTypes();
            foreach ($types as $typeId => $typeInfo) {
                if (array_key_exists('composite', $typeInfo) && $typeInfo['composite']) {
                    self::$_compositeTypes[] = $typeId;
                }
            }
        }
        return self::$_compositeTypes;
    }

    public static function getTypesByPriority()
    {
        if (is_null(self::$_typesPriority)) {
            self::$_typesPriority = array();
            $a = array();
            $b = array();

            $types = self::getTypes();
            foreach ($types as $typeId => $typeInfo) {
                $priority = isset($typeInfo['index_priority']) ? abs(intval($typeInfo['index_priority'])) : 0;
                if (!empty($typeInfo['composite'])) {
                    $b[$typeId] = $priority;
                } else {
                    $a[$typeId] = $priority;
                }
            }

            asort($a, SORT_NUMERIC);
            asort($b, SORT_NUMERIC);

            foreach (array_keys($a) as $typeId) {
                self::$_typesPriority[$typeId] = $types[$typeId];
            }
            foreach (array_keys($b) as $typeId) {
                self::$_typesPriority[$typeId] = $types[$typeId];
            }
        }
        return self::$_typesPriority;
    }
}