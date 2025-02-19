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
    // protected $ilDB;  // Declare the database object
    protected $cron_users_statistic_repository;
    protected ILIAS\UI\Factory $ui_factory;
    protected ILIAS\UI\Renderer $ui_renderer;
    protected $df;
    protected $refinery;
    protected $request;
    protected $current_user_date_format;
    protected $short_date_fomat;

    public function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $ilDB = $DIC->database();  // Add this line to inject the database object
        $this->cron_users_statistic_repository = new CronUsersStatisticRepository($ilDB);
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];
        $this->df = new \ILIAS\Data\Factory();
        $this->refinery = $DIC['refinery'];
        $this->request = $DIC->http()->request();
        $this->current_user_date_format = $this->df->dateFormat()->withTime24(
            $DIC['ilUser']->getDateFormat()
        );
        $this->short_date_fomat = $this->df->dateFormat()->germanShort();
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
            case 'save':
                $this->$cmd();
                break;
            case 'showStatistics':
            default:
                $this->activateTab('statistics');
                $this->showStatistics();
                break;
            
            // Configure not needed right now
            // case 'configure':
            // default:
            //     $this->activateTab('config');
            //     $this->configure();
            //     break;
        }
    }

    protected function activateTab(string $tab_id): void
    {
        global $ilTabs;
        $ilTabs->activateTab($tab_id);
    }


    /**
     * Show settings screen
     * Not needed right now
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

        $this->tpl->setTitle($this->getPluginObject()->txt("usr_statistics"));

        // Form for time interval filter input:
        // Duration does only work if start and end time are both given. Therefore switched to use two dateTime input fields instead 
        // $duration = $this->ui_factory->input()->field()->duration("Pick a time-span", "");
        $start_date = $this->ui_factory->input()->field()
            ->dateTime($this->getPluginObject()->txt("start_date"), "");
        $end_date = $this->ui_factory->input()->field()
            ->dateTime($this->getPluginObject()->txt("end_date"), "");

        $section = $this->ui_factory->input()->field()->section(
            ['start_date' => $start_date, 'end_date' => $end_date], // Inputs inside the section
            $this->getPluginObject()->txt("filter_entries_by_date") // The section title
        );
        $form = $this->ui_factory->input()->container()->form()->standard(
            '#',
            [
                'filter' => $section
            ]
        )->withAdditionalTransformation(
            $this->refinery->custom()->transformation(
                fn($v) =>[$v['filter']['start_date'] ?? null, $v['filter']['end_date'] ?? null] 
            )
        )->withAdditionalTransformation(
            $this->refinery->custom()->constraint(
                function ($v) {
                    $start = $v[0];
                    $end = $v[1];
                    
                    if (empty($start) || empty($end)) {
                        return true;
                    }
                    
                    return $end >= $start;
                }, $this->getPluginObject()->txt("start_date_before_end_date")
            )
        )->withRequest($this->request);
        
        $filter = $form->getData() ?? [null, null];

        // UI DATA TABLE
        $columns = [
            'stat_date' => $this->ui_factory->table()->column()->date($this->getPluginObject()->txt("date"), $this->short_date_fomat),
            'user_count' => $this->ui_factory->table()->column()->number($this->getPluginObject()->txt("user_count")),
            'created_at' => $this->ui_factory->table()->column()->date($this->getPluginObject()->txt("created_at"), $this->current_user_date_format)->withIsOptional(true),
        ];
        $table = $this->ui_factory->table()->data('Stats', $columns, $this->cron_users_statistic_repository)->withFilter($filter);

        $table_html = $this->ui_renderer->render([
            $form,
            $table->withRequest($this->request)
        ]);
        // Set the content for the statistics tab
        $tpl->setContent($table_html);
    }


    protected function setTabs(): void
    {
        global $ilTabs;

        // Config not needed for now
        // $ilTabs->addTab(
        //     "config",
        //     $this->lng->txt("configuration"),
        //     $this->ctrl->getLinkTarget($this, "configure")
        // );

        $ilTabs->addTab(
            "statistics",
            $this->getPluginObject()->txt("statistics"),
            $this->ctrl->getLinkTarget($this, "showStatistics")
        );
    }

}
