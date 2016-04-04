<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use phpish\shopify\Shopify;

require_once(ROOT . DS . 'shopify'. DS . 'vendor'. DS. 'autoload.php');

require_once(ROOT . DS . 'conf.php');


/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link http://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class InstallController extends AppController
{

    public function install($shop)
    {
        $this->autoRender = false;

        isset($shop) or die ('Query parameter "shop" missing.');
        preg_match('/^[a-zA-Z0-9\-]+.myshopify.com$/', $shop) or die('Invalid myshopify.com store URL.');

        $install_url = Shopify::install_url($shop, SHOPIFY_APP_API_KEY);
        echo "<script> top.location.href='$install_url'</script>";
    }

    public function oauth()
    {
        $this->autoRender = false;

        Shopify::is_valid_request($_GET, SHOPIFY_APP_SHARED_SECRET) or die('Invalid Request! Request or redirect did not come from Shopify');


        # Step 2: http://docs.shopify.com/api/authentication/oauth#asking-for-permission
        if (!isset($_GET['code']))
        {
            $permission_url = Shopify::authorization_url($_GET['shop'], SHOPIFY_APP_API_KEY, array('read_content', 'read_themes',  'read_products', 'read_customers',  'read_orders', 'read_script_tags', 'read_fulfillments', 'read_shipping'));
            $permission_url = $permission_url . '&redirect_uri='.REDIRECT_URL;
            die("<script> top.location.href='$permission_url'</script>");
        }


        # Step 3: http://docs.shopify.com/api/authentication/oauth#confirming-installation
        try
        {
            # shopify\access_token can throw an exception
            $oauth_token = Shopify::access_token($_GET['shop'], SHOPIFY_APP_API_KEY, SHOPIFY_APP_SHARED_SECRET, $_GET['code']);

            $_SESSION['oauth_token'] = $oauth_token;
            $_SESSION['shop'] = $_GET['shop'];

            header("Location:".BASE_URL);
        }
        catch (ApiException $e)
        {
            # HTTP status code was >= 400 or response contained the key 'errors'
            echo $e;
            print_R($e->getRequest());
            print_R($e->getResponse());
        }
        catch (urlException $e)
        {
            # cURL error
            echo $e;
            print_R($e->getRequest());
            print_R($e->getResponse());
        }


    }
}
