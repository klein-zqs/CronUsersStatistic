<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 ********************************************************************
 */


/**
 * @ilCtrl_isCalledBy ilCronUsersStatisticConfigGUI: ilObjComponentSettingsGUI
 *
 * Class ilCronUsersStatisticConfigGUI
 */
class ilCronUsersStatisticConfigGUI extends ilPluginConfigGUI
{
    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected $ilDB;  // Declare the database object
    protected $cron_users_statistic_repository;

    public function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->ilDB = $DIC->database();  // Add this line to inject the database object
        $this->cron_users_statistic_repository = new CronUsersStatisticRepository($this->ilDB);

    }

    /**
     * @param string $cmd
     *
     * Handles all commands, default is "configure"
     */
    public function performCommand(string $cmd): void
    {
        $this->setTabs();

        switch ($cmd) {
            case 'showStatistics':
                $this->activateTab('statistics');
                $this->showStatistics();
                break;
            case 'save':
                $this->$cmd();
                break;
            case 'configure':
            default:
                $this->activateTab('config');
                $this->configure();
                break;
        }
    }

    protected function activateTab(string $tab_id): void
    {
        global $ilTabs;
        $ilTabs->activateTab($tab_id);
    }


    /**
     * Show settings screen
     */
    public function configure(?ilPropertyFormGUI $form = null) : void
    {
        global $tpl;
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->initConfigurationForm();
        }
        $tpl->setContent($form->getHTML());
    }

    public function initConfigurationForm() : ilPropertyFormGUI
    {
        //create the form
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->getPluginObject()->txt("gui_title"));

        //add button
        $form->addCommandButton("save", $this->lng->txt("save"));

        //text input
        $setting = new ilCronUsersStatisticSettings();
        $text = new ilTextInputGUI($this->getPluginObject()->txt("email_recipient"), "email_recipient");
        $text->setValue($setting->get("email_recipient"));
        $text->setInfo($this->getPluginObject()->txt("email_recipient_info"));
        // $text->setRequired(true);
        $form->addItem($text);

        return $form;
    }

    public function save() : void
    {
        $form = $this->initConfigurationForm();
        if ($form->checkInput()) {
            $setting = new ilCronUsersStatisticSettings();
            $setting->setList($form->getInput("email_recipient"));
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->lng->txt("settings_saved"),
                true
            );
            $this->ctrl->redirect($this, "configure");
        }
        $this->configure($form);
    }

    public function showStatistics(): void
    {
        global $tpl;

        $this->tpl->setTitle($this->lng->txt("usr_statistics"));

        // Query the database to get statistics data
        // $query = "SELECT * FROM crn_usr_statistics ORDER BY stat_date DESC";
        // $res = $this->ilDB->query($query);

        $stats = $this->cron_users_statistic_repository->getStats();
        // Build HTML table to display results
        $table_html = "<table class='table'>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User Count</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach ($stats as $stat) {
        // while ($row = $this->ilDB->fetchAssoc($res)) {
            $table_html .= "<tr>
                <td>" . htmlspecialchars($stat['stat_date']) . "</td>
                <td>" . htmlspecialchars((string)$stat['user_count']) . "</td>
                <td>" . htmlspecialchars($stat['created_at']) . "</td>
            </tr>";
        }

        $table_html .= "</tbody></table>";

        // Set the content for the statistics tab
        $tpl->setContent($table_html);
    }


    protected function setTabs(): void
    {
        global $ilTabs;

        $ilTabs->addTab(
            "config",
            $this->lng->txt("configuration"),
            $this->ctrl->getLinkTarget($this, "configure")
        );

        $ilTabs->addTab(
            "statistics",
            $this->lng->txt("statistics"),
            $this->ctrl->getLinkTarget($this, "showStatistics")
        );
    }

}
