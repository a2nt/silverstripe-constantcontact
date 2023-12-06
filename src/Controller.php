<?php

namespace A2nt\ConstantContact;

use Exception;
use SilverStripe\Control\Controller as ControlController;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;
use PHPFUI\ConstantContact\Client;
use PHPFUI\ConstantContact\Definition\ContactCreateOrUpdateInput;
use PHPFUI\ConstantContact\Definition\StreetAddress;
use PHPFUI\ConstantContact\V3\ContactLists;
use PHPFUI\ConstantContact\V3\Contacts\SignUpForm;

class Controller extends ControlController
{
    private static $apikey;
    private static $secret;
    private static $redirecturl = 'constantcontact/authorizeCC/';

    private static $allowed_actions = [
        'authorizeCC',
        'requireTokenCC',
    ];

    public static function getLists(): array
    {
        $client = self::getClientCC();

        $listEndPoint = new ContactLists($client);
        $lists = $listEndPoint->get();

        return isset($lists['lists']) ? $lists['lists'] : [];
    }

    /*'email_address' => 'test@test.com',
    'first_name' => 'Test',
    'last_name' => 'Test',
    'job_title' => 'Test',
    'company_name' => 'Test',
    'phone_number'  => 'Test',
    'custom_fields' => [],
    'anniversary' => 'TEST',
    'birthday_month' => 11,
    'birthday_day' => 1,
    'street_address' => new StreetAddress([])*/
    public static function Subscribe(array $data): array
    {
        $cfg = self::getSiteConfig();
        $listID = $cfg->CCSubscribeListID;
        if (!$listID) {
            return new Exception('ConstantContact Subscribe list is not selected!');
        }

        return self::addContact(
            array_merge(
                [
                    'list_memberships' => [
                        $listID
                    ]
                ],
                $data
            )
        );
    }

    public function authorizeCC()
    {
        $client = self::getClientCC();
        $client->acquireAccessToken($_GET);
        if (!$client->accessToken || !$client->refreshToken) {
            die('Something went wrong, plz try again!');
        }
        self::tokenDBupdate($client);

        echo '<script>setTimeout(window.close,3000)</script>';
        die('Authorized!');
    }

    public function requireTokenCC()
    {
        $client = self::getClientCC();
        \header('location: ' . $client->getAuthorizationURL());
        die();
    }

    private static function getSiteConfig(): SiteConfig
    {
        return SiteConfig::current_site_config();
    }

    private static function addContact(array $data): array
    {
        $form = new SignUpForm(self::getClientCC());

        return $form->post(new ContactCreateOrUpdateInput(
            $data
        ));
    }

    private static function getClientCC(): Client
    {
        $apikey = self::config()->get('apikey');
        $secret = self::config()->get('secret');
        $redirect_url = self::config()->get('redirecturl');

        if (!$apikey || !$secret) {
            return new Exception('ConstantContact api key or secret is not set');
        }

        $client = new Client(
            $apikey,
            $secret,
            Director::absoluteBaseURL().$redirect_url
        );

        $cfg = self::getSiteConfig();
        if ($cfg->CCAccessToken && $cfg->CCRefreshToken) {
            $client->accessToken = $cfg->CCAccessToken;
            $client->refreshToken = $cfg->CCRefreshToken;

            self::refreshToken($client);
        }

        return $client;
    }

    private static function refreshToken(Client $client)
    {
        $client->refreshToken();
        self::tokenDBupdate($client);
    }

    private static function tokenDBupdate(Client $client)
    {
        $cfg = self::getSiteConfig();
        $cfg->CCAccessToken = $client->accessToken;
        $cfg->CCRefreshToken = $client->refreshToken;
        $cfg->write();
    }
}
