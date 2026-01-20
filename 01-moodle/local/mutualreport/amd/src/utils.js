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
 * URL properties module.
 *
 * @module    local_mutualreport/utils
 * @copyright 2023 e-abclearning.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

/**
 * Get the first value from a list of selectors
 * @param {Array} selectors - The list of selector
 * @param {String} defaultvalue - The default value
 * @returns {String} The first value from the selectors
 */
export const getFirstValueFromSelectors = (selectors, defaultvalue = '') => {
    for (let i = 0; i < selectors.length; i++) {
        var exists = $(selectors[i]).length;
        var value = $(selectors[i]).val();
        if (exists && value !== '' && typeof value !== 'undefined') {
            return value;
        }
    }
    return defaultvalue;
};

/**
 * Get the value of a URL parameter
 * @param {String} sParam
 * @param {*} defaultValue El valor que se devolverá si el parámetro no se encuentra.
 * @returns
 */
export const getUrlParameter = (sParam, defaultValue = 0) => {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }

    return defaultValue;
};

/**
 * Get the last path name URL parameter and compare it with the given parameter
 * @param {String} param The name of the parameter
 * @returns {Boolean} True if the last path name matches the given parameter
 */
export const getLastPathNameParameter = (param) => {
    var path = window.location.pathname.split('/').pop();

    // Convert to array if not.
    if (!Array.isArray(param)) {
        param = [param];
    }

    // Compare the path with each element of the array using the includes method
    return param.includes(path);
};
