<?php

/**
 * Type.php
 * @author Jack Worman
 */

namespace JWorman\Serializer\Annotations;

use JWorman\AnnotationReader\AbstractAnnotation;

class Type extends AbstractAnnotation
{
    const CLASS_NAME = __CLASS__;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->value;
    }
}