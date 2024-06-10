<?php

namespace PrototypeIntegration\Forms;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;

class FormContext
{
    /**
     * @var string
     */
    protected $objectName;

    /**
     * @var RequestInterface
     */
    protected $request;

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
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    public function isObjectForm(): bool
    {
        return !empty($this->objectName);
    }
}
