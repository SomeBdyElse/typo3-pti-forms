<?php

namespace PrototypeIntegration\Forms;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Service\ExtensionService;

class Form
{
    /**
     * @var MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * @var ExtensionService
     */
    protected $extensionService;

    /**
     * @var HashService
     */
    protected $hashService;

    /**
     * @var FormContext
     */
    protected $formContext;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $hiddenFields = [];

    public function __construct(
        MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService,
        ExtensionService $extensionService,
        HashService $hashService
    ) {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
        $this->extensionService = $extensionService;
        $this->hashService = $hashService;
    }

    public function setRequest(RequestInterface $request)
    {
        $this->formContext = GeneralUtility::makeInstance(FormContext::class);
        $this->formContext->setRequest($request);
    }

    public function createField(string $identifier, string $nameOrProperty = '', bool $propertyField = null): Field
    {
        if (!$nameOrProperty) {
            $nameOrProperty = $identifier;
        }

        if (is_null($propertyField)) {
            $propertyField = $this->formContext->isObjectForm();
        }

        /** @var Field $field */
        $field = GeneralUtility::makeInstance(Field::class);
        $field->setFormContext($this->formContext);
        $field->setPropertyField($propertyField);
        $field->setName($nameOrProperty);

        $this->fields[$identifier] = $field;

        return $field;
    }

    public function createHiddenField(string $identifier, string $propertyOrName = '', string $type = ''): Field
    {
        $field = $this->createField($identifier, $propertyOrName, $type);
        $this->hiddenFields[] = $field;

        return $field;
    }

    public function createPlainHiddenField(string $identifier, string $propertyOrName = ''): Field
    {
        $field = $this->createField($identifier, $propertyOrName, false);
        $this->hiddenFields[] = $field;

        return $field;
    }

    public function render(): array
    {
        $fields = [];
        /** @var Field $field */
        foreach ($this->fields as $identifier => $field) {
            $fields[$identifier] = $field->render();
        }

        $hiddenFields = [];
        foreach ($this->hiddenFields as $field) {
            $hiddenFields[] = $field->render();
        }
        $hiddenFields = array_merge($hiddenFields, $this->renderHiddenFields());

        return [
            'fields' => $fields,
            'hiddenFields' => $hiddenFields,
        ];
    }

    protected function renderHiddenFields(): array
    {
        return array_merge(
            [$this->renderTrustedPropertiesField()],
            $this->renderHiddenReferrerFields()
        );
    }

    /**
     * @throws \TYPO3\CMS\EXTBASE\Security\Exception\InvalidArgumentForHashGenerationException
     * @return array
     */
    protected function renderTrustedPropertiesField(): array
    {
        /** @var Field $trustedPropertiesField */
        $trustedPropertiesField = $this->createPlainHiddenField('__trustedProperties');
        $trustedPropertiesField->setValue($this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken(
            $this->getFormFieldNames(),
            $trustedPropertiesField->getFieldNamePrefix()
        ));
        $trustedPropertiesField->setRespectSubmittedDataValue(false);

        return $trustedPropertiesField->render();
    }

    protected function renderHiddenReferrerFields(): array
    {
        $request = $this->formContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getControllerActionName();
        $actionRequest = [
            '@extension' => $extensionName,
            '@controller' => $controllerName,
            '@action' => $actionName,
        ];

        $hiddenFields = [];

        $hiddenFields[] = $this->createPlainHiddenField('__referrer[@extension]')
            ->setValue($extensionName)
            ->setRespectSubmittedDataValue(false)
            ->render();

        $hiddenFields[] = $this->createPlainHiddenField('__referrer[@controller]')
            ->setValue($controllerName)
            ->setRespectSubmittedDataValue(false)
            ->render();

        $hiddenFields[] = $this->createPlainHiddenField('__referrer[@action]')
            ->setValue($actionName)
            ->setRespectSubmittedDataValue(false)
            ->render();

        $hiddenFields[] = $this->createPlainHiddenField('__referrer[arguments]')
            ->setValue($this->hashService->appendHmac(base64_encode(serialize($request->getArguments()))))
            ->setRespectSubmittedDataValue(false)
            ->render();

        $hiddenFields[] = $this->createPlainHiddenField('__referrer[@request]')
            ->setValue($this->hashService->appendHmac(serialize($actionRequest)))
            ->setRespectSubmittedDataValue(false)
            ->render();

        return $hiddenFields;
    }

    protected function getFormFieldNames()
    {
        $fieldNames = [];
        /** @var Field $field */
        foreach ($this->fields as $field) {
            $fieldNames[] = $field->renderName();
        }

        return $fieldNames;
    }

    public function addField(Field $field, string $identifier = '')
    {
        if (empty($identifier)) {
            $identifier = $field->getName();
        }

        $field->setFormContext($this->formContext);
        $this->fields[$identifier] = $field;
    }

    public function addHiddenField(Field $field, string $identifier = '')
    {
        $this->addField($field, $identifier);
        if (empty($identifier)) {
            $identifier = $field->getName();
        }

        $this->hiddenFields[$identifier] = $field;
    }

    public function setObjectName(string $name)
    {
        $this->formContext->setObjectName($name);
    }
}
