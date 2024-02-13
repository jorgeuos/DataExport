<?php
/**
 * © 2021 Jorge Powers. All rights reserved.
 *
 * @link https://jorgeuos.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExport;

use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Piwik;

/**
 * This class allows you to add, remove or rename menu items.
 * To configure a menu (such as Admin Menu, Top Menu, User Menu...) simply call the corresponding methods as
 * described in the API-Reference http://developer.piwik.org/api-reference/Piwik/Menu/MenuAbstract
 */
class Menu extends \Piwik\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        // $menu->addItem('DataExport_MyTopItem', null, $this->urlForDefaultAction(), $orderId = 30);
    }

    public function configureAdminMenu(MenuAdmin $menu)
    {
        // reuse an existing category. Execute the showList() method within the controller when menu item was clicked
        // $menu->addManageItem('DataExport_MyUserItem', $this->urlForAction('showList'), $orderId = 30);
        $menu->addPlatformItem(
            Piwik::translate('DataExport_DataExport'),
            $this->urlForDefaultAction('index'),
            $orderId = 30
        );

        // or create a custom category
        // $menu->addItem('CoreAdminHome_MenuManage', 'DataExport_MyUserItem', $this->urlForDefaultAction(), $orderId = 30);
    }
}
