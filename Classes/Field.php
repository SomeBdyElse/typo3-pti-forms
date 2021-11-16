<?php
declare(strict_types=1);

namespace PrototypeIntegration\Forms;

use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Service\ExtensionService;

class Field
{
    protected FormContext $formContext;
    protected ExtensionService $extensionService;
    
    /**
     * True if this field is mapped to a property of its form's object
     */
    protected bool $propertyField = false;

    /**
     * The name attribute of the field.
     */
    protected string $name;
    
    protected array $attributes = [];

    /**
     * Whether or not the field will display the value submitted with the last request
     */
    protected bool $respectSubmittedDataValue = true;

    protected $value;

    protected $defaultValue;

    public function __construct(ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    public function render(): array
    {
        return array_merge($this->attributes, [
            'name' => $this->renderName(),
            'value' => $this->renderValue()
        ]);
    }
    
    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }
    
    public function setDefaultValue(string $defaultValue): Field
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function setFormContext(FormContext $formContext): Field
    {
        $this->formContext = $formContext;
        return $this;
    }

    /**
     * Sets the name of the field to the given property name and converts
     * the field to a property field.
     * 
     * @see $propertyField
     */
    public function setProperty(string $propertyName): Field
    {
        $this->name = $propertyName;
        $this->propertyField = true;
        return $this;
    }

    /**
     * The unprefixed name of the field.
     * This is different from renderName, which adds the required prefix
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Field
    {
        $this->name = $name;
        return $this;
    }

    public function setValue($value): Field
    {
        $this->value = $value;
        return $this;
    }

    public function isPropertyField(): bool
    {
        return $this->propertyField;
    }

    public function isPlainField(): bool
    {
        return ! $this->propertyField;
    }

    public function setPropertyField(bool $propertyField): Field
    {
        $this->propertyField = $propertyField;
        return $this;
    }
    
    public function setRespectSubmittedDataValue(bool $respectSubmittedDataValue): Field
    {
        $this->respectSubmittedDataValue = $respectSubmittedDataValue;
        return $this;
    }

    public function renderValue()
    {
        if (! is_null($this->value)) {
            return $this->value;
        }

        $originalRequest = $this->formContext->getControllerContext()->getRequest()->getOriginalRequest();
        $submitted = ! empty($originalRequest);
        if ($this->respectSubmittedDataValue && $submitted) {
            $submittedArguments = $originalRequest->getArguments();
            if ($this->isPlainField()) {
                return $submittedArguments[$this->name];
            } else {
                return $submittedArguments[$this->formContext->getObjectName()][$this->name];
            }

        }

        return $this->defaultValue;
    }

    public function getValidationMessages(): array
    {
        $submitted = ! empty($this->formContext->getControllerContext()->getRequest()->getOriginalRequest());

        if ($submitted) {
            $mappingResults = $this->getMappingResults();
            $mappingResultMessages = [
                $mappingResults->getErrors(),
                $mappingResults->getWarnings(),
                $mappingResults->getNotices()
            ];

            $messages = [];
            foreach ($mappingResultMessages as $messageArray) {
                /** @var Message $message */
                foreach ($messageArray as $message) {
                    $messages[] = $message->getMessage();
                }
            }

            return $messages;
        }

        return [];
    }
    
    protected function getMappingResults(): Result
    {
        if ($this->isPropertyField()) {
            $objectMappingResults = $this->formContext->getControllerContext()
                ->getRequest()
                ->getOriginalRequestMappingResults()
                ->forProperty($this->formContext->getObjectName());

            $mappingResults = $objectMappingResults->forProperty($this->name);
        } else {
            $mappingResults = $this->formContext->getControllerContext()
                ->getRequest()
                ->getOriginalRequestMappingResults()
                ->forProperty($this->name);
        }

        return $mappingResults;
    }

    public function renderName(): string
    {
        if ($this->isPropertyField()) {
            return $this->prefixPropertyFieldname($this->name);
        }
        return $this->prefixFieldname($this->name);
    }

    public function getFieldNamePrefix(): string
    {
        $request = $this->formContext->getControllerContext()->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $pluginName = $request->getPluginName();

        if ($extensionName !== null && $pluginName != null) {
            return $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        }
        return '';
    }

    public function prefixFieldname(string $fieldName): string
    {
        $fieldNameSegments = explode('[', $fieldName, 2);
        $fieldName = $this->getFieldNamePrefix() . '[' . $fieldNameSegments[0] . ']';
        if (count($fieldNameSegments) > 1) {
            $fieldName .= '[' . $fieldNameSegments[1];
        }
        return $fieldName;
    }

    public function prefixPropertyFieldname(string $propertyName): string
    {
        return sprintf('%s[%s][%s]',
            $this->getFieldNamePrefix(),
            $this->formContext->getObjectName(),
            $propertyName
        );
    }

    public function setAttribute(string $attribute, string $value): Field
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }

    public function setMultipleAttributes(array $array): Field
    {
        $this->attributes = array_merge($this->attributes, $array);
        return $this;
    }

    public function removeAttribute(string $attribute): Field
    {
        unset($this->attributes[$attribute]);
        return $this;
    }
}
