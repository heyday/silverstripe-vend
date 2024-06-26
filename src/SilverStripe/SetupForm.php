<?php

namespace Heyday\Vend\SilverStripe;

use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use Heyday\Vend\SilverStripe\VendToken;
use VendAPI\VendAPI;

/**
 * Class VendSetupForm
 */
class SetupForm extends Form
{
    /**
     * @var array
     */
    private static $allowed_actions = ['doSave'];


    public function __construct($controller, $name)
    {
        $this->addExtraClass('vend-form');

        $this->controller = $controller;
        $config = SiteConfig::current_site_config();

        $vendToken = VendToken::get()->first();
        $vendAccessToken = false;
        $vendShopName = false;

        if ($vendToken) {
            $vendAccessToken = $vendToken->AccessToken;
            $vendShopName = $config->VendShopName;
        }

        $fields = FieldList::create();
        $actions = FieldList::create();
        $fields->add(
            LiteralField::create(
                'vend',
                "<h1>Vend Integration</h1>"
            )
        );

        if (!is_null($vendAccessToken) && !empty($vendAccessToken)) {
            $url = $this->getAuthURL();
            $fields->add(
                LiteralField::create(
                    'explanation',
                    "<p>You're all setup!<br> If you need to reauthenticate then <a href='$url' target='_blank'>select this</a> to do so.</p>"
                )
            );
        } else {
            if (!is_null($vendShopName) && !empty($vendShopName)) {
                $url = $this->getAuthURL();
                $fields->add(
                    LiteralField::create(
                        'explanation',
                        "<p>Please authenticate by <a href='$url' target='_blank'>selecting this</a>.</p>"
                    )
                );
            } else {
                $fields->add(
                    LiteralField::create(
                        'explanation',
                        "<p>Please remember to set your app settings in a config file.</p>"
                    )
                );
            }
        }

        $fields->add(
            TextField::create(
                'VendShopName',
                'Vend Shop Name (as in: <Name>.vendhq.com)',
                $vendShopName
            )
        );

        $actions->push(FormAction::create('doSave', 'Save'));

        // Reduce attack surface by enforcing POST requests
        $this->setFormMethod('POST', true);

        parent::__construct($controller, $name, $fields, $actions);
    }

    /**
     * Returns the URL needed for shop owner authorisation
     * @return string
     */
    public function getAuthURL()
    {
        $clientID = Config::inst()->get(VendAPI::class, 'clientID');
        $redirectURI = Director::absoluteBaseURLWithAuth() . Config::inst()->get(VendAPI::class, 'redirectURI');
        return "https://secure.vendhq.com/connect?response_type=code&client_id=$clientID&redirect_uri=$redirectURI";
    }

    /**
     * @param $data
     * @return null
     */
    public function doSave($data)
    {
        $shopName = $data['VendShopName'];
        $config = SiteConfig::current_site_config();
        $config->VendShopName = $shopName;
        $config->write();
        $this->controller->redirectBack();
    }
}
