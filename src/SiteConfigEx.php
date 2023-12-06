<?php

namespace A2nt\ConstantContact;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataExtension;

class SiteConfigEx extends DataExtension
{
    private static $db = [
        'CCAccessToken' => 'Varchar(255)',
        'CCRefreshToken' => 'Varchar(255)',
        'CCSubscribeListID' => 'Varchar(255)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $obj = $this->owner;
        $fields->addFieldToTab(
            'Root.Main',
            LiteralField::create(
                'CCAuthorizeButton',
                '<div class="form-group field"><div class="form__field-holder">'
                .'<a href="/constantcontact/requireTokenCC" target="_blank">Re-Authorize Constant Contact</a>'
                .'</div></div>'
            ),
        );

        if ($obj->CCAccessToken && $obj->CCRefreshToken) {
            $lists = Controller::getLists();
            $map = [];
            foreach ($lists as $i) {
                $map[$i['list_id']] =  $i['name'];
            }

            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create(
                    'CCSubscribeListID',
                    'Constant Contact List to Subscribe',
                    $map
                ),
            );
        }
    }
}
