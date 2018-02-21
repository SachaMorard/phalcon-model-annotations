<?php
/*
 * This file is part of the PhalconModelAnnotations package.
 *
 * (c) Sacha Morard <sachamorard@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phalcon\Annotations\DbStrategies;

use Phalcon\Annotations\ModelStrategy;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Db\Column;

class SqlDatabases
{

    /**
     * @param \Phalcon\Annotations\Reflection $reflection
     * @param $properties
     * @param array $indexes
     * @return array
     */
    public static function getMetaData(\Phalcon\Annotations\Reflection $reflection, $properties, array $indexes)
    {
        $attributes = array();
        $nullables = array();
        $dataTypes = array();
        $dataTypesBind = array();
        $numericTypes = array();
        $primaryKeys = array();
        $nonPrimaryKeys = array();
        $sizes = array();

        $source = null;
        $annotations = $reflection->getClassAnnotations();
        if ($annotations) {
            foreach ($annotations as $annotation) {
                switch ($annotation->getName()) {
                    case 'Source':
                        $arguments = $annotation->getArguments();
                        $source = $arguments[0];
                }
            }
        }
        $identity = false;
        foreach ($properties as $name => $collection) {
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
                /**
                 * Check for the 'type' parameter in the 'Column' annotation
                 */
                if (isset($arguments['type'])) {
                    switch ($arguments['type']) {
                        case 'integer':
                            $dataTypes[$columnName] = Column::TYPE_INTEGER;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_INT;
                            $numericTypes[$columnName] = true;

                            if ($source !== 'dbPostgresql' && !isset($arguments['size'])) {
                                $arguments['size'] = 11;
                            }
                            break;
                        case 'bigint':
                            $dataTypes[$columnName] = Column::TYPE_BIGINTEGER;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_INT;
                            $numericTypes[$columnName] = true;
                            break;
                        case 'timestamp':
                            $dataTypes[$columnName] = Column::TYPE_TIMESTAMP;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'string':
                            $dataTypes[$columnName] = Column::TYPE_VARCHAR;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            if (!isset($arguments['size'])) {
                                $arguments['size'] = 255;
                            }
                            break;
                        case 'datetime':
                            $dataTypes[$columnName] = Column::TYPE_DATETIME;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'text':
                            $dataTypes[$columnName] = Column::TYPE_TEXT;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'decimal':
                            $dataTypes[$columnName] = Column::TYPE_DECIMAL;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_DECIMAL;
                            $numericTypes[$columnName] = true;
                            break;
                        case 'float':
                            $dataTypes[$columnName] = Column::TYPE_FLOAT;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_DECIMAL;
                            $numericTypes[$columnName] = true;
                            break;
                        case 'date':
                            $dataTypes[$columnName] = Column::TYPE_DATE;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'boolean':
                            $dataTypes[$columnName] = Column::TYPE_BOOLEAN;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_BOOL;
                            break;
                        case 'json':
                            $dataTypes[$columnName] = Column::TYPE_JSON;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'jsonb':
                            $dataTypes[$columnName] = Column::TYPE_JSONB;
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                        case 'array':
                            $dataTypes[$columnName] = 100; //todo replace with Column::TYPE_ARRAY asap
                            $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                            break;
                    }
                } else {
                    $dataTypes[$columnName] = Column::TYPE_VARCHAR;
                    $dataTypesBind[$columnName] = Column::BIND_PARAM_STR;
                }
                /**
                 * Check for the 'nullable' parameter in the 'Column' annotation
                 */
                if (!$collection->has('Identity')) {
                    if (isset($arguments['nullable'])) {
                        if (!$arguments['nullable']) {
                            $nullables[] = $columnName;
                        }
                    }
                } else {
                    $nullables[] = $columnName;
                }

                if (isset($arguments['size'])) {
                    $sizes[$columnName] = $arguments['size'];
                }

                $attributes[] = $columnName;
                /**
                 * Check if the attribute is marked as primary
                 */
                if ($collection->has('Primary')) {
                    $primaryKeys[] = $columnName;
                } else {
                    $nonPrimaryKeys[] = $columnName;
                }
                /**
                 * Check if the attribute is marked as identity
                 */
                if ($collection->has('Identity')) {
                    $identity = $columnName;
                }
            }
        }
        return array(
            //Every column in the mapped table
            MetaData::MODELS_ATTRIBUTES => $attributes,
            //Every column part of the primary key
            MetaData::MODELS_PRIMARY_KEY => $primaryKeys,
            //Every column that isn't part of the primary key
            MetaData::MODELS_NON_PRIMARY_KEY => $nonPrimaryKeys,
            //Every column that doesn't allows null values
            MetaData::MODELS_NOT_NULL => $nullables,
            //Every column and their data types
            MetaData::MODELS_DATA_TYPES => $dataTypes,
            //The columns that have numeric data types
            MetaData::MODELS_DATA_TYPES_NUMERIC => $numericTypes,
            //The identity column, use boolean false if the model doesn't have
            //an identity column
            MetaData::MODELS_IDENTITY_COLUMN => $identity,
            //How every column must be bound/casted
            MetaData::MODELS_DATA_TYPES_BIND => $dataTypesBind,
            //Fields that must be ignored from INSERT SQL statements
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => array(),
            //Fields that must be ignored from UPDATE SQL statements
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => array(),
            // Default values
            MetaData::MODELS_DEFAULT_VALUES => array(),

            MetaData::MODELS_EMPTY_STRING_VALUES => array(),
            //Size of fields
            ModelStrategy::METADATA_SIZES_OF_FIELDS => $sizes,
            //Table indexes
            ModelStrategy::METADATA_TABLE_INDEXES => $indexes
        );
    }
}