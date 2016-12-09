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

use Phalcon\Events\Event;
use Phalcon\Mvc\Model\Manager as ModelsManager;

class ModelListener extends \Phalcon\Mvc\User\Plugin
{
    /**
     * This is called after initialize the model
     *
     * @param Event $event
     * @param ModelsManager $manager
     * @param $model
     */
    public function afterInitialize(Event $event, ModelsManager $manager, $model)
    {
        //Reflector
        /** @var \Phalcon\Annotations\Reflection $reflector */
        $reflector = $this->annotations->get($model);
        /**
         * Read the annotations in the class' docblock
         */
        $annotations = $reflector->getClassAnnotations();
        if ($annotations) {
            /**
             * Traverse the annotations
             */
            foreach ($annotations as $annotation) {
                switch ($annotation->getName()) {
                    /**
                     * Initializes the model's source
                     */
                    case 'Source':
                        $arguments = $annotation->getArguments();
                        $model->setConnectionService($arguments[0]);
                        $manager->setModelSource($model, $arguments[1]);
                        break;
                    /**
                     * Initializes Has-Many relations
                     */
                    case 'HasMany':
                        $arguments = $annotation->getArguments();
                        if (!isset($arguments[3])) {
                            $arguments[3] = null;
                        }
                        $manager->addHasMany($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        break;
                    /**
                     * Initializes Has-Many-To-Many relations
                     */
                    case 'HasManyToMany':
                        $arguments = $annotation->getArguments();
                        if (!isset($arguments[6])) {
                            $arguments[6] = null;
                        }
                        $manager->addHasManyToMany($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6]);
                        break;
                    /**
                     * Initializes Has-One relations
                     */
                    case 'HasOne':
                        $arguments = $annotation->getArguments();
                        if (!isset($arguments[3])) {
                            $arguments[3] = null;
                        }
                        $manager->addHasOne($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        break;
                    /**
                     * Initializes Has-Many relations
                     */
                    case 'BelongsTo':
                        $arguments = $annotation->getArguments();
                        if (!isset($arguments[3])) {
                            $arguments[3] = null;
                        }
                        $manager->addBelongsTo($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        break;
                }
            }
        }
    }
}