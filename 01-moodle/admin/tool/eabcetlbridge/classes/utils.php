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

namespace tool_eabcetlbridge;

use core\component;

/**
 * Class for common functions
 *
 * @package   tool_eabcetlbridge
 * @category  classes
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Get child classes in a component matching the provided namespace.
     *
     * It checks that the class exists.
     *
     * e.g. get_component_classes_in_namespace('mod_forum', 'event')
     *
     * @param string|null $component A valid moodle component (frankenstyle) or null if searching all components
     * @param string $namespace Namespace from the component name or empty string if all $component classes.
     * @return array The full class name as key and the class path as value, empty array if $component is `null`
     * and $namespace is empty.
     */
    public static function get_child_classes($baseclass, $component = null, $namespace = '') {

        $childclasses = [];

        $classes = component::get_component_classes_in_namespace($component, $namespace);

        foreach ($classes as $classname => $classpath) {
            if (is_subclass_of($classname, $baseclass)) {
                $childclasses[] = $classname;
            }
        }

        return $childclasses;

    }
}
