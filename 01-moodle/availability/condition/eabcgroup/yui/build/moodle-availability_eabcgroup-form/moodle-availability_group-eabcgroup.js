YUI.add('moodle-availability_eabcgroup-form', function (Y, NAME) {

/**
 * JavaScript for form editing eabcgroup conditions.
 *
 * @module moodle-availability_eabcgroup-form
 */
M.availability_eabcgroup = M.availability_eabcgroup || {};

/**
 * @class M.availability_eabcgroup.form
 * @extends M.core_availability.plugin
 */
M.availability_eabcgroup.form = Y.Object(M.core_availability.plugin);

/**
 * eabcgroups available for selection (alphabetical order).
 *
 * @property eabcgroups
 * @type Array
 */
M.availability_eabcgroup.form.eabcgroups = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} eabcgroups Array of objects containing eabcgroupid => name
 */
M.availability_eabcgroup.form.initInner = function(eabcgroups) {
    this.eabcgroups = eabcgroups;
};

M.availability_eabcgroup.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<label><span class="p-r-1">' + M.util.get_string('title', 'availability_eabcgroup') + '</span> ' +
            '<span class="availability-eabcgroup">' +
            '<select name="id" class="custom-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>' +
            '<option value="active">' + M.util.get_string('activeeabcgroup', 'availability_eabcgroup') + '</option>';
    for (var i = 0; i < this.eabcgroups.length; i++) {
        var eabcgroup = this.eabcgroups[i];
        // String has already been escaped using format_string.
        html += '<option value="' + eabcgroup.id + '">' + eabcgroup.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined &&
                node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', '' + json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'active');
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_eabcgroup.form.addedEvents) {
        M.availability_eabcgroup.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_eabcgroup select');
    }

    return node;
};

M.availability_eabcgroup.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = 'choose';
    } else if (selected !== 'active') {
        value.id = parseInt(selected, 10);
    }
};

M.availability_eabcgroup.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check eabcgroup item id.
    if (value.id && value.id === 'choose') {
        errors.push('availability_eabcgroup:error_selecteabcgroup');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
