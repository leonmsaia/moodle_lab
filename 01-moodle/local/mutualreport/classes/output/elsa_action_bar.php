<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_mutualreport\output;

use templatable;
use renderable;
use renderer_base;
use core\context;
use core\url as moodle_url;
use core\output\select_menu;
use local_mutualreport\url;
use local_mutualreport\godeep;
use local_mutualreport\reports\common;
use local_mutualreport\output\general_page;

/**
 * Renderable class for the general action bar in the elsa pages.
 *
 * This class is responsible for rendering the general navigation select menu in the gradebook pages.
 */
class elsa_action_bar implements templatable, renderable {

    /** @var \context $context The context object. */
    protected $context;

    /** @var moodle_url $activeurl The URL that should be set as active in the URL selector element. */
    protected $activeurl;

    /** @var string $pagetitle The title of the page. */
    protected $pagetitle;
    
    /** @var string $pageheading The heading of the page. */
    protected $pageheading;

    /** @var array $customparams */
    protected $customparams;

    /**
     * The class constructor.
     *
     * @param context $context The context object.
     * @param moodle_url $activeurl The URL that should be set as active in the URL selector element.
     * @param string $pagetitle
     * @param array $course
     */
    public function __construct(
            context $context,
            moodle_url $activeurl,
            string $pagetitle,
            $pageheading,
            $customparams = array()) {
        $this->context = $context;
        $this->activeurl = $activeurl;
        $this->pagetitle = $pagetitle;
        $this->pageheading = $pageheading;
        $this->customparams = $customparams;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param \renderer_base $output renderer to be used to render the action bar elements.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        $data = array();

        $selectmenu = $this->get_action_selector();

        if (is_null($selectmenu)) {
            return [];
        }

        $data = [
            'generalnavselector' => $selectmenu->export_for_template($output),
            'actionbarheading' => $this->pageheading,
            'actionbaricon' => $this->customparams['actionbaricon'] ?? 'fa-file-text-o',
        ];

        // Add a button to the action bar with a link.
        $actionbutton = $this->customparams['actionbutton'] ?? null;
        if ($actionbutton) {
            $data['actionbutton'] = $actionbutton->export_for_template($output);
        }

        return $data;
    }

    /**
     * Returns the template for the action bar.
     *
     * @return string
     */
    public function get_template(): string {
        return 'local_mutualreport/general_action_bar';
    }

    /**
     * Returns the URL selector object.
     *
     * @return \select_menu|null The URL select object.
     */
    protected function get_action_selector(): ?select_menu {
        global $USER;

        // Discover reports and check visibility.
        $baseclass = \local_mutualreport\report\report_base::class;
        $component = 'local_mutualreport';
        $namespace = 'report';
        $reportclasses = \local_mutualreport\utils::get_child_classes($baseclass, $component, $namespace);

        $groupeditems = [];
        $allreports = [];
        foreach ($reportclasses as $fullclassname) {
            $allreports[] = new $fullclassname();
        }

        // Sort reports by the defined sort order.
        $config = get_config('local_mutualreport');
        usort($allreports, function($a, $b) use ($config) {
            $namea = 'sort_order_' . $a->get_name();
            $nameb = 'sort_order_' . $b->get_name();
            $ordera = $config->$namea ?? 99;
            $orderb = $config->$nameb ?? 99;

            return $ordera <=> $orderb;
        });

        /** @var \local_mutualreport\report\report_base $report */
        foreach ($allreports as $report) {
            if (\local_mutualreport\utils::is_report_visible($report->get_name())) {
                $groupkey = $report->get_group();
                $groupeditems[$groupkey][$report->get_url()->out(false)] = $report->get_title();
            }
        }

        $menu = [];
        $groups = \local_mutualreport\report\report_base::get_groups();
        foreach ($groups as $groupkey => $groupname) {
            if (!empty($groupeditems[$groupkey])) {
                $menu[][$groupname] = $groupeditems[$groupkey];
            }
        }

        if (empty($menu)) {
            return null;
        }

        $selectmenu = new select_menu('gradesactionselect', $menu, $this->activeurl->out(false));
        $selectmenu->set_label(
            get_string('gradebooknavigationmenu', 'grades'),
            ['class' => 'sr-only']
        );

        return $selectmenu;
    }
}
