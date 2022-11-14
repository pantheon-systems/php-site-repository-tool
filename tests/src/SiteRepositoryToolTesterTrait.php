<?php

namespace PhpSiteRepositoryTool;

/**
 * Trait SiteRepositoryToolTesterTrait.
 */
trait SiteRepositoryToolTesterTrait
{

    /**
     * Call a method that is regularly not publicly accessible (i.e. protected or private).
     */
    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
