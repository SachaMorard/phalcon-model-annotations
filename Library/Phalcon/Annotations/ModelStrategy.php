<?php
/*
 * This file is part of the PhalconModelAnnotations package.
 *
 * (c) Sacha Morard <sachamorard@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phalcon\Annotations;

use Phalcon\Mvc\ModelInterface;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Db\Index;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;

class ModelStrategy implements StrategyInterface
{
    const METADATA_SIZES_OF_FIELDS = 100;
    const METADATA_TABLE_INDEXES = 101;

    /**
     * Initializes the model's meta-data
     *
     * @param ModelInterface $model
     * @param DiInterface $di
     * @return mixed
     * @throws \Exception
     */
    public function getMetaData(ModelInterface $model, DiInterface $di)
    {
        /** @var Reflection $reflection */
        $reflection = $di->getAnnotations()->get($model);
        $properties = $reflection->getPropertiesAnnotations();
        if (!$properties) {
            throw new \Exception('There are no properties defined on the class');
        }

        $tableProperties = $reflection->getClassAnnotations();

        if ($tableProperties && $tableProperties->has('Source')) {
            $source = $tableProperties->get('Source')->getArguments()[0];
        } else {
            throw new \Exception('Model ' . get_class($model) . ' has no Source defined');
        }

        /** @var \Phalcon\Db\Adapter $database */
        $database = $di->get($source);
        $dbType = ucfirst($database->getType());
        $indexes = $this->getIndexes($reflection);

        return call_user_func_array(['\Phalcon\Annotations\DbStrategies\\'.$dbType, 'getMetaData'], [$reflection, $properties, $indexes]);
    }


    /**
     * @param \Phalcon\Annotations\Reflection $reflection
     * @return array
     */
    private function getIndexes(\Phalcon\Annotations\Reflection $reflection)
    {
        $indexes = array();
        $tableProperties = $reflection->getClassAnnotations();
        if ($tableProperties->has('Index')) {
            foreach ($tableProperties->getAll('Index') as $i) {

                $arguments = $i->getArguments();

                if (!isset($arguments['columns']) || !is_array($arguments['columns']) || empty($arguments['columns'])) {
                    continue;
                }

                $type = 'INDEX';
                if (isset($arguments['type'])) {
                    switch ($arguments['type']) {
                        case 'index':
                        case 'unique':
                            $type = strtoupper($arguments['type']);
                            break;
                        default:
                            break;
                    }
                }
                $idxName = 'IDX_' . $type . '_' . join('_', array_map('strtoupper', $arguments['columns']));
                if (isset($arguments['name'])) {
                    $idxName = $arguments['name'];
                }

                $index = new Index($idxName, $arguments['columns'], ($type !== 'INDEX') ? $type : null);
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /**
     * Initializes the model's column map
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param \Phalcon\DiInterface $di
     * @return array
     */
    public function getColumnMaps(ModelInterface $model, DiInterface $di)
    {
        $reflection = $di['annotations']->get($model);
        $columnMap = array();
        $reverseColumnMap = array();
        $renamed = false;
        foreach ($reflection->getPropertiesAnnotations() as $name => $collection) {
            if ($collection->has('Column')) {
                $arguments = $collection->get('Column')->getArguments();
                /**
                 * Get the column's name
                 */
                if (isset($arguments['column'])) {
                    $columnName = $arguments['column'];
                } else {
                    $columnName = $name;
                }
                $columnMap[$columnName] = $name;
                $reverseColumnMap[$name] = $columnName;
                if (!$renamed) {
                    if ($columnName != $name) {
                        $renamed = true;
                    }
                }
            }
        }
        return array(
            MetaData::MODELS_COLUMN_MAP => $columnMap,
            MetaData::MODELS_REVERSE_COLUMN_MAP => $reverseColumnMap
        );
    }

    /**
     * @param ModelInterface $model
     * @param DiInterface $di
     * @return array|null
     */
    public function getSizes(ModelInterface $model, DiInterface $di)
    {
        $reflection = $di['annotations']->get($model);
        $columnMap = array();
        foreach ($reflection->getPropertiesAnnotations() as $name => $collection) {
            if ($collection->has('Column')) {
                $arguments = $collection->get('Column')->getArguments();
                if (isset($arguments['size'])) {
                    if (isset($arguments['column'])) {
                        $columnName = $arguments['column'];
                    } else {
                        $columnName = $name;
                    }
                    $columnMap[$columnName] = $arguments['size'];
                }
                return $columnMap;
            }
        }
        return null;
    }
}