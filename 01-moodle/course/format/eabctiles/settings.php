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

/**
 * Settings used by the tiles course format
 *
 * @package format_eabctiles
 * @copyright  2019 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings = null; // We add our own settings pages and do not want the standard settings link.

    $settingscategory = new \format_eabctiles\local\admin_settingspage_tabs(
        'formatsettingeabctiles', get_string('pluginname', 'format_eabctiles')
    );

    // Colour settings.
    $page = new admin_settingpage('format_eabctiles/tab-colours', get_string('colours', 'format_eabctiles'));

    $page->add(
        new admin_setting_heading('other', get_string('other', 'format_eabctiles'), '')
    );

    $name = 'format_eabctiles/followthemecolour';
    $title = get_string('followthemecolour', 'format_eabctiles');
    $default = 0;
    $description = get_string('followthemecolour_desc', 'format_eabctiles');
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/subtileiconcolourbackground';
    $title = get_string('subtileiconcolourbackground', 'format_eabctiles');
    $description = get_string('subtileiconcolourbackground_desc', 'format_eabctiles');
    $default = 0;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $brandcolourdefaults = [
        '#1670CC' => get_string('colourblue', 'format_eabctiles'),
        '#00A9CE' => get_string('colourlightblue', 'format_eabctiles'),
        '#7A9A01' => get_string('colourgreen', 'format_eabctiles'),
        '#009681' => get_string('colourdarkgreen', 'format_eabctiles'),
        '#D13C3C' => get_string('colourred', 'format_eabctiles'),
        '#772583' => get_string('colourpurple', 'format_eabctiles'),
    ];
    $colournumber = 1;
    foreach ($brandcolourdefaults as $hex => $displayname) {
        $title = get_string('brandcolour', 'format_eabctiles') . ' ' . $colournumber;
        if ($colournumber === 1) {
            $title .= " - " . get_string('defaulttilecolour', 'format_eabctiles');
        }
        $page->add(
            new admin_setting_heading(
                'brand' . $colournumber,
                $title,
                ''
            )
        );
        // Colour picker for this brand.

        if ($colournumber === 1) {
            $visiblename = get_string('defaulttilecolour', 'format_eabctiles');
        } else {
            $visiblename = get_string('tilecolourgeneral', 'format_eabctiles') . ' ' . $colournumber;
        }
        $setting = new admin_setting_configcolourpicker(
            'format_eabctiles/tilecolour' . $colournumber,
            $visiblename,
            '',
            $hex
        );
        $page->add($setting);

        // Display name for this brand.
        $setting = new admin_setting_configtext(
            'format_eabctiles/colourname' . $colournumber,
            get_string('colournamegeneral', 'format_eabctiles') . ' ' . $colournumber,
            get_string('colourname_descr', 'format_eabctiles'),
            $displayname,
            PARAM_RAW,
            30
        );
        $page->add($setting);
        $colournumber++;
    }

    $settingscategory->add($page);

    // Modal activities / resources.
    $page = new admin_settingpage('format_eabctiles/tab-modalwindows', get_string('modalwindows', 'format_eabctiles'));
    $cachecallback = function() {
        \cache_helper::purge_by_event('format_eabctiles/modaladminsettingchanged');
    };

    // Modal windows for course modules.
    $allowedmodtypes = ['page' => 1]; // Number is default to on or off.
    $allmodtypes = get_module_types_names();
    $options = [];
    foreach (array_keys($allowedmodtypes) as $modtype) {
        if (isset($allmodtypes[$modtype])) {
            $options[$modtype] = $allmodtypes[$modtype];
        }
    }
    $name = 'format_eabctiles/modalmodules';
    $title = get_string('modalmodules', 'format_eabctiles');
    $description = get_string('modalmodules_desc', 'format_eabctiles');
    $setting = new admin_setting_configmulticheckbox(
        $name,
        $title,
        $description,
        $allowedmodtypes,
        $options
    );
    $setting->set_updatedcallback($cachecallback);
    $page->add($setting);

    // Modal windows for resources.
    $displayembed = get_string('display', 'form') . ': ' . get_string('resourcedisplayembed');
    $link = html_writer::link(
        "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options",
        "https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options"
    );
    $allowedresourcetypes = [
        'pdf' => get_string('displaytitle_mod_pdf', 'format_eabctiles') . " (pdf)",
        'url' => get_string('url') . ' (' . $displayembed . ')',
        'html' => get_string('displaytitle_mod_html', 'format_eabctiles') . " (HTML " . get_string('file') . ")",
    ];
    $name = 'format_eabctiles/modalresources';
    $title = get_string('modalresources', 'format_eabctiles');
    $description = get_string('modalresources_desc', 'format_eabctiles', ['displayembed' => $displayembed, 'link' => $link]);
    $setting = new admin_setting_configmulticheckbox(
        $name,
        $title,
        $description,
        ['pdf' => 1, 'url' => 1, 'html' => 1],
        $allowedresourcetypes
    );
    $page->add($setting);
    $setting->set_updatedcallback($cachecallback);
    $settingscategory->add($page);

    // Photo tile settings.
    $page = new admin_settingpage('format_eabctiles/tab-phototilesettings', get_string('phototilesettings', 'format_eabctiles'));

    $name = 'format_eabctiles/allowphototiles';
    $title = get_string('allowphototiles', 'format_eabctiles');
    $description = get_string('allowphototiles_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $choices = [];
    $stylestr = get_string('style', 'format_eabctiles');
    for ($i = 1; $i <= 2; $i++) {
        $choices[(string)$i] = $stylestr . ' ' . $i;
    }

    $setting = new admin_setting_configselect(
        'format_eabctiles/eabctilestyle',
        get_string('eabctilestyle', 'format_eabctiles'),
        get_string('eabctilestyle_desc', 'format_eabctiles'),
        "1",
        $choices);
    $page->add($setting);

    $name = 'format_eabctiles/showprogresssphototiles';
    $title = get_string('courseshowtileprogress', 'format_eabctiles');
    $description = get_string('showprogresssphototiles_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    // Tile title CSS adjustments.
    $page->add(
        new admin_setting_heading('transparenttitleadjustments', get_string('transparenttitleadjustments', 'format_eabctiles'),
            get_string('transparenttitleadjustments_desc', 'format_eabctiles'))
    );

    $opacities = [0.3, 0.2, 0.1, 0];
    $choices = [];
    foreach ($opacities as $op) {
        $choices[(string)$op] = (string)($op * 100) . "%";
    }
    $setting = new admin_setting_configselect(
        'format_eabctiles/phototiletitletransarency',
        get_string('phototiletitletransarency', 'format_eabctiles'),
        get_string('phototiletitletransarency_desc', 'format_eabctiles'),
        "0",
        $choices);
    $page->add($setting);

    // Tile title line height.
    $choices = [];
    for ($x = 30.0; $x <= 33.0; $x += 0.1) {
        $choices[(int)($x * 10)] = $x;
    }
    $setting = new admin_setting_configselect(
        'format_eabctiles/phototitletitlelineheight',
        get_string('phototitletitlelineheight', 'format_eabctiles'),
        '',
        305,
        $choices);
    $page->add($setting);

    // Tile title line line padding.
    $choices = [];
    for ($x = 0.0; $x <= 6.0; $x += 0.5) {
        $choices[(int)($x * 10)] = $x;
    }
    $setting = new admin_setting_configselect(
        'format_eabctiles/phototitletitlepadding',
        get_string('phototitletitlepadding', 'format_eabctiles'),
        '',
        40,
        $choices);
    $page->add($setting);
    $settingscategory->add($page);

    // Javascript navigation settings.
    $page = new admin_settingpage('format_eabctiles/tab-jsnav', get_string('jsnavsettings', 'format_eabctiles'));

    $name = 'format_eabctiles/usejavascriptnav';
    $title = get_string('usejavascriptnav', 'format_eabctiles');
    $description = get_string('usejavascriptnav_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/reopenlastsection';
    $title = get_string('reopenlastsection', 'format_eabctiles');
    $description = get_string('reopenlastsection_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/usejsnavforsinglesection';
    $title = get_string('usejsnavforsinglesection', 'format_eabctiles');
    $description = get_string('usejsnavforsinglesection_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/fittilestowidth';
    $title = get_string('fittilestowidth', 'format_eabctiles');
    $description = get_string('fittilestowidth_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $settingscategory->add($page);

    // Other settings.
    $page = new admin_settingpage('format_eabctiles/tab-other', get_string('other', 'format_eabctiles'));

    $page->add(new admin_setting_configtext(
        'format_eabctiles/motivosuspencion',
        get_string('listreasonsuspend', 'format_eabctiles'), 
        get_string('listreasonsuspend_help', 'format_eabctiles'),
        get_string('listreasonsuspend', 'format_eabctiles'), 
        PARAM_RAW));
        
        $page->add(new admin_setting_configtext(
        'format_eabctiles/motivosuspencionopen',
        get_string('listreasonsuspendopen', 'format_eabctiles'),
        get_string('listreasonsuspend_help', 'format_eabctiles'),
        '', 
        PARAM_RAW));

    $name = 'format_eabctiles/sendgrade_course';
    $title = 'Activar modo producción(envío ws de nota)';
    $description = 'Modo producción hace el envío de la nota de finalizar curso elearning, si se desactiva no enviará la nota pero hace el guardado para test';
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $page->add(
        new admin_setting_heading(
            'problemcourses',
            get_string('problemcourses', 'format_eabctiles'),
            html_writer::link(
                \format_eabctiles\local\course_section_manager::get_list_problem_courses_url(),
                get_string('checkforproblemcourses', 'format_eabctiles'),
                ['class' => 'btn btn-primary', 'target' => '_blank']
            )
        )
    );

    $page->add(
        new admin_setting_heading('other', get_string('other', 'format_eabctiles'),
            '')
    );


    $name = 'format_eabctiles/allowsubtilesview';
    $title = get_string('allowsubtilesview', 'format_eabctiles');
    $description = get_string('allowsubtilesview_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/showoverallprogress';
    $title = get_string('showoverallprogress', 'format_eabctiles');
    $description = get_string('showoverallprogress_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/progressincludesubsections';
    $title = get_string('progressincludesubsections', 'format_eabctiles');
    $description = get_string('progressincludesubsections_desc', 'format_eabctiles');
    $default = 0; // Core does not include it, so for now we do not do so by default.
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/showseczerocoursewide';
    $title = get_string('showseczerocoursewide', 'format_eabctiles');
    $description = get_string('showseczerocoursewide_desc', 'format_eabctiles');
    $default = 0;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/usetooltips';
    $title = get_string('usetooltips', 'format_eabctiles');
    $description = get_string('usetooltips_desc', 'format_eabctiles');
    $default = 0;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $setting = new admin_setting_configtext(
        'format_eabctiles/documentationurl',
        get_string('documentationurl', 'format_eabctiles'),
        get_string('documentationurl_descr', 'format_eabctiles'),
        'https://evolutioncode.uk/tiles/docs',
        PARAM_RAW,
        50
    );
    $page->add($setting);

    // Custom css.
    $name = 'format_eabctiles/customcss';
    $title = get_string('customcss', 'format_eabctiles');
    $description = get_string('customcssdesc', 'format_eabctiles');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $page->add($setting);

    $name = 'format_eabctiles/enablelinebreakfilter';
    $title = get_string('enablelinebreakfilter', 'format_eabctiles');
    $description = get_string('enablelinebreakfilter_desc', 'format_eabctiles', '<code>&amp;#8288;</code>');
    $default = 0;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/assumedatastoreconsent';
    $title = get_string('assumedatastoreconsent', 'format_eabctiles');
    $description = get_string('assumedatastoreconsent_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_eabctiles/usecourseindex';
    $title = get_string('usecourseindex', 'format_eabctiles');
    $description = get_string('usecourseindex_desc', 'format_eabctiles');
    $default = 1;
    $page->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $page->add(new admin_setting_heading(
        'experimentalfeatures', get_string('experimentalfeatures', 'format_eabctiles'), ''
    ));
    $name = 'format_eabctiles/highcontrastmodeallow';
    $title = get_string('highcontrastmodeallow', 'format_eabctiles');
    $default = 0;
    $page->add(new admin_setting_configcheckbox($name, $title, get_string('highcontrastmodeallow_desc', 'format_eabctiles'), $default));

    $settingscategory->add($page);

    $ADMIN->add('formatsettings', $settingscategory);
}
