<?php

declare(strict_types=1);

namespace PrototypeIntegration\Forms;

use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Service\ExtensionService;

class Field
{
    protected FormContext $formContext;

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
     * Additional names required for trusted properties (required for e.g form upload fields)
     *
     * @var string[]
     */
    protected array $tokenNames = [];

    /**
     * Whether or not the field will display the value submitted with the last request
     */
    protected bool $respectSubmittedDataValue = true;

    protected $value;

    protected $defaultValue;

    public function __construct(protected ExtensionService $extensionService) {}

    public function render(): array
    {
        return array_merge($this->attributes, [
            'name' => $this->renderName(),
            'value' => $this->renderValue(),
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
        return !$this->propertyField;
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
        if (!is_null($this->value)) {
            return $this->value;
        }

        $originalRequest = $this->formContext->getRequest()->getAttribute('extbase')->getOriginalRequest();
        $submitted = !empty($originalRequest);
        if ($this->respectSubmittedDataValue && $submitted) {
            $submittedArguments = $originalRequest->getArguments();
            if ($this->isPlainField()) {
                return $submittedArguments[$this->name];
            }

            return $submittedArguments[$this->formContext->getObjectName()][$this->name];
        }

        return $this->defaultValue;
    }

    public function getValidationMessages(): array
    {
        $submitted = !empty($this->formContext->getRequest()->getAttribute('extbase')->getOriginalRequest());

        if ($submitted) {
            $mappingResults = $this->getMappingResults();
            $mappingResultMessages = [
                $this->tokenNames ? $mappingResults->getFlattenedErrors() : $mappingResults->getErrors(),
                $this->tokenNames ? $mappingResults->getFlattenedWarnings() : $mappingResults->getWarnings(),
                $this->tokenNames ? $mappingResults->getFlattenedNotices() : $mappingResults->getNotices(),
            ];

            $messages = [];
            foreach ($mappingResultMessages as $messageArray) {
                /** @var Message|Message[] $message */
                foreach ($messageArray as $message) {
                    if (is_array($message)) {
                        foreach ($message as $singleMessage) {
                            $messages[] = $singleMessage->getMessage();
                        }
                    } else {
                        $messages[] = $message->getMessage();
                    }
                }
            }

            return $messages;
        }

        return [];
    }

    protected function getMappingResults(): Result
    {
        if ($this->isPropertyField()) {
            $objectMappingResults = $this->formContext->getRequest()
                ->getAttribute('extbase')
                ->getOriginalRequestMappingResults()
                ->forProperty($this->formContext->getObjectName());

            $mappingResults = $objectMappingResults->forProperty($this->name);
        } else {
            $mappingResults = $this->formContext->getRequest()
                ->getAttribute('extbase')
                ->getOriginalRequestMappingResults()
                ->forProperty($this->name);
        }

        return $mappingResults;
    }

    public function renderName(): string
    {
        if ($this->isPropertyField()) {
            $renderName = $this->prefixPropertyFieldname($this->name);
        } else {
            $renderName = $this->prefixFieldname($this->name);
        }

        return $renderName . (isset($this->attributes['multiple']) ? '[]' : '');
    }

    /**
     * @return string[]
     */
    public function tokenNames(): array
    {
        if ($this->isPropertyField()) {
            $name = $this->prefixPropertyFieldname($this->name);
        } else {
            $name = $this->prefixFieldname($this->name);
        }
        if ($this->tokenNames) {
            $tokenNames = [];
            foreach ($this->tokenNames as $tokenName) {
                if (isset($this->attributes['multiple'])) {
                    $tokenNames[] = sprintf('%s[*][%s]', $name, $tokenName);
                } else {
                    $tokenNames[] = $name . '[' . $tokenName . ']';
                }
            }
            return $tokenNames;
        }
        return [$name];
    }

    public function getFieldNamePrefix(): string
    {
        $request = $this->formContext->getRequest();
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
        return sprintf(
            '%s[%s][%s]',
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

    /**
     * @return string[]
     */
    public function getTokenNames(): array
    {
        return $this->tokenNames;
    }

    /**
     * @param string[] $tokenNames
     * @return Field
     */
    public function setTokenNames(array $tokenNames): Field
    {
        $this->tokenNames = $tokenNames;
        return $this;
    }
}
