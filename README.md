# pti-form
Helper classes to render extbase forms in a PTI context. The classes act as replacements for the fluid view helpers:
* AbstractFormFieldViewHelper
* FormViewHelper

Their main concern is to render the correct field names for the form fields and the hidden fields that extbase requires to accept the form submission. 

## Sample usage
```php
$form = GeneralUtility::makeInstance(Form::class);
$form->setRequest($this->request);
$form->setObjectName('contactForm'); // optional

$messageField = GeneralUtility::makeInstance(TextArea::class);
$messageField->setProperty('message');
$form->addField($messageField)

$form = $form->render();
/*
[
    'fields' => [
        'message' => [
            'name' => 'tx_extension_plugin[contactForm][message]',
            'value' => '',
        ],
    ],
    'hiddenFields' => [
        [
            'name' => 'tx_extension_plugin[__referrer][@extension]',
            'value' => 'Extension'
        ],
        [
            'name' => 'tx_extension_plugin[__trustedProperties]',
            'value' => '…',
        ],
    ],
]
*/

$form['action'] = …
$form['method'] = …
```

Most extensions will extend the Field class to create select fields, textareas, etc.
