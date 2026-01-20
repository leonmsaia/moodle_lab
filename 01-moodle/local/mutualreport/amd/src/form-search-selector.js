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
 * Search user selector module.
 *
 * @module    local_mutualreport/form-search-selector
 * @copyright 2025 e-abclearning.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import * as Utils from 'local_mutualreport/utils';

/**
 * Process the results for auto complete elements.
 *
 * @param {String} selector - The selector of the auto complete element.
 * @param {Array} results - An array of results returned by the transport function.
 * @return {Array} - A new array of user objects with value and label properties.
 */
export const processResults = (selector, results) => {
    return results;
};

/**
 * Load the list of users matching the query and render the selector labels for them.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {String} query The query string.
 * @param {Function} success A callback function receiving an array of results.
 * @param {Function} failure A function to call in case of failure, receiving the error message.
 */
export const transport = (selector, query, success, failure)  => {
    var promise;

    // Search within specific course if known and if the 'search within' dropdown is set
    // to search within course or activity.
    var args = {query: query};

    // Use a regular expression to find all attributes starting with "field_"
    const fieldAttributes = $(selector).get(0).attributes; // Get the DOM element's attributes
    for (let i = 0; i < fieldAttributes.length; i++) {

        const attr = fieldAttributes[i];

        // Get the value of the attribute if it starts with "fieldwithdefault_" and add it to the args
        if (attr.name.startsWith('fieldwithdefault_')) {
            let fieldname = attr.name.replace("fieldwithdefault_", "");
            var fieldvalue = $(selector).attr(attr.name);

            var defaultvalue = 0;
            var defaultfield = fieldAttributes.getNamedItem(fieldname + '_default');
            if (defaultfield) {
                defaultvalue = defaultfield.value;
            }

            args[fieldname] = Utils.getFirstValueFromSelectors(
                [fieldvalue],
                Utils.getUrlParameter(
                    fieldname,
                    defaultvalue
                )
            );
        }

        // Get the value of the attribute if it starts with "field_" and add it to the args
        if (attr.name.startsWith('field_')) {
            let fieldname = attr.name.replace("field_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = Utils.getFirstValueFromSelectors([fieldvalue], 0);
        }

        // Get the value of the attribute if it starts with "fieldstaticvalue_" and add it to the args
        if (attr.name.startsWith('fieldstaticvalue_')) {
            let fieldname = attr.name.replace("fieldstaticvalue_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = fieldvalue;
        }

        if (attr.name.startsWith('notgeturlparameter_')) {
            let fieldname = attr.name.replace("notgeturlparameter_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = !Utils.getUrlParameter(fieldvalue);
        } else if (attr.name.startsWith('geturlparameter_')) {
            let fieldname = attr.name.replace("geturlparameter_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = Utils.getUrlParameter(fieldvalue);
        }

        if (attr.name.startsWith('notgetlastpathnameparameter_')) {
            let fieldname = attr.name.replace("notgetlastpathnameparameter_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = !Utils.getLastPathNameParameter([fieldvalue]);
        } else if (attr.name.startsWith('getlastpathnameparameter_')) {
            let fieldname = attr.name.replace("getlastpathnameparameter_", "");
            var fieldvalue = $(selector).attr(attr.name);
            args[fieldname] = Utils.getLastPathNameParameter([fieldvalue]);
        }

        // Get the value of the attribute if it starts with "fieldischecked_" or "fieldisnotchecked_" and add it to the args
        if (attr.name.startsWith('fieldischecked_') || attr.name.startsWith('fieldisnotchecked_')) {
            const prefix = attr.name.startsWith('fieldischecked_') ? 'fieldischecked_' : 'fieldisnotchecked_';
            const fieldname = attr.name.replace(prefix, "");
            const fieldvalue = $(selector).attr(attr.name);
            const isChecked = $(fieldvalue).is(":checked");

            if ((prefix === 'fieldischecked_' && isChecked) || (prefix === 'fieldisnotchecked_' && !isChecked)) {
                args[fieldname] = true;
            } else {
                args[fieldname] = false;
            }
        }

    }

    // Call AJAX request.
    var methodname = $(selector).attr('methodname');

    // Always have to return list with id. All other parameters are managed in the template.
    promise = Ajax.call([{methodname: methodname, args: args}]);

    // When AJAX request returns, handle the results.
    promise[0].then(async function(results) {

        const list = await Promise.all(results.map(async (element) => {
            const template = $(selector).attr('template');
            const label = await Templates.render(template, element); // Wait for the template.
            return {
                value: element.id,
                label: label
            };
        }));
        success(list);

    }).fail(failure);
};
