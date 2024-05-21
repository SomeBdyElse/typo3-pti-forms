<?php

namespace PrototypeIntegration\Forms;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class FormContext
{
    /**
     * @var string
     */
    protected $objectName;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext(): ControllerContext
    {
        return $this->controllerContext;
    }

    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
    }

    public function isObjectForm(): bool
    {
        return !empty($this->objectName);
    }
}
