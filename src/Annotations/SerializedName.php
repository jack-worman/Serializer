<?php

/**
 * SerializedName.php
 * @author Jack Worman
 */

namespace JWorman\Serializer\Annotations;

use JWorman\AnnotationReader\AbstractAnnotation;

/**
 * Class SerializedName
 * @package JWorman\Serializer\Annotations
 */
class SerializedName extends AbstractAnnotation
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     */
    protected function validateValue()
    {
        preg_match('/^[a-zA-Z_$]\w*$/', $this->value, $matches);
        return count($matches) === 1;
    }
}