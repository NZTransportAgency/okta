<?php

class OktaMemberExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'OktaID' => 'Varchar(255)',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Okta', ReadonlyField::create('OktaID', 'Okta ID'));
    }
}
